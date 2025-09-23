<?php
define('CLI_SCRIPT',        true);
define("MAXMSGSIZE",        10000);
define("MAXALERTSPERMSG",   40);

GLOBAL $CFG, $DB, $UTILITY;

if (CLI_SCRIPT) {
    $newline = "\n";
    $batchdir = dirname($argv[0])."/";
} else {
    $newline = "<BR />\n";
    $batchdir = getcwd()."/";
}

require_once($batchdir.'../config.php');
require_once($batchdir.'../setup.php');
require_once($batchdir.'../template.class.php');

echo $newline.date('m/d/Y H:i:s')." - BEGIN price alert batch";

// We need a page object to handle the page messaging associated with
// the internal messaging.
$page = new template(NOLOGIN, NOSHOWMSG);

$factoryCostID = $page->utility->getDealerId(FACTORYCOSTNAME);
$random = rand();
$sql = "DROP TABLE IF EXISTS tmp_high_buy_".$random;
$page->queries->AddQuery($sql);
$sql = "DROP TABLE IF EXISTS tmp_low_sell_".$random;
$page->queries->AddQuery($sql);

$sql = "
    CREATE TEMPORARY TABLE tmp_high_buy_".$random." AS
    SELECT l.type, l.categoryid, l.subcategoryid, l.boxtypeid, l.year, MAX(l.boxprice) AS highbuy
      FROM listings             l
      JOIN categories           c   ON  c.categoryid        = l.categoryid
                                    AND c.active            = 1
                                    AND c.categorytypeid IN (".LISTING_TYPE_GAMING.",".LISTING_TYPE_SPORTS.")
      JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                    AND sc.active           = 1
      JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                    AND bt.active           = 1
      JOIN assignedrights       a   ON  a.userid            = l.userid
                                    AND a.userrightid       = 1
      JOIN userinfo             u   ON  u.userid            = l.userid
                                    AND u.userclassid       = 3
                                    AND u.vacationbuy       = 0
      LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                    AND stl.userrightid     = ".USERRIGHT_STALE."
     WHERE l.type               = 'Wanted'
       AND l.status             = 'OPEN'
       AND l.userid             <> ".$factoryCostID."
       AND l.uom IN ('box', 'case')
       AND stl.userid IS NULL
    GROUP BY l.type, l.categoryid, l.subcategoryid, l.boxtypeid, l.year
    ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
";
$page->queries->AddQuery($sql);

$sql = "
    CREATE TEMPORARY TABLE tmp_low_sell_".$random." AS
    SELECT l.type, l.categoryid, l.subcategoryid, l.boxtypeid, l.year, MIN(l.boxprice) AS lowsell
      FROM listings             l
      JOIN categories           c   ON  c.categoryid        = l.categoryid
                                    AND c.active            = 1
                                    AND c.categorytypeid IN (".LISTING_TYPE_GAMING.",".LISTING_TYPE_SPORTS.")
      JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                    AND sc.active           = 1
      JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                    AND bt.active           = 1
      JOIN assignedrights       a   ON  a.userid            = l.userid
                                    AND a.userrightid       = 1
      JOIN userinfo             u   ON  u.userid            = l.userid
                                    AND u.userclassid       = 3
                                    AND u.vacationsell      = 0
      LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                    AND stl.userrightid     = ".USERRIGHT_STALE."
     WHERE l.type               = 'For Sale'
       AND l.status             = 'OPEN'
       AND l.userid             <> ".$factoryCostID."
       AND l.uom IN ('box', 'case')
       AND stl.userid IS NULL
    GROUP BY l.type, l.categoryid, l.subcategoryid, l.boxtypeid, l.year
    ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
";
$page->queries->AddQuery($sql);

$page->queries->ProcessQueries();

$sql =  "
    SELECT a.alertid, a.type, u.userid, u.username,
           c.categoryid, c.categorydescription, c.categorytypeid,
           sc.subcategoryid, sc.subcategoryname,
           bt.boxtypeid, bt.boxtypename,
           a.year, a.alert_price, a.triggering_price
      FROM (
        SELECT pa.alertid, pa.userid, pa.type, pa.categoryid, pa.subcategoryid, pa.boxtypeid,
               pa.year, pa.dprice as alert_price, hb.highbuy as triggering_price
          FROM pricealerts              pa
          JOIN tmp_high_buy_".$random." hb  ON  hb.categoryid                   = pa.categoryid
                                            AND hb.subcategoryid                = pa.subcategoryid
                                            AND hb.boxtypeid                    = pa.boxtypeid
    										AND isnull(hb.year::varchar,'1')    = isnull(pa.year::varchar,'1')
                                            AND hb.type                         = pa.type
         WHERE pa.dprice <= hb.highbuy
           AND pa.status = 'OPEN'
        UNION
        SELECT pa.alertid, pa.userid, pa.type, pa.categoryid, pa.subcategoryid, pa.boxtypeid,
               pa.year, pa.dprice as alert_price, ls.lowsell as triggering_price
          FROM pricealerts              pa
          JOIN tmp_low_sell_".$random." ls  ON  ls.categoryid                   = pa.categoryid
                                            AND ls.subcategoryid                = pa.subcategoryid
                                            AND ls.boxtypeid                    = pa.boxtypeid
    										AND isnull(ls.year::varchar,'1')    = isnull(pa.year::varchar,'1')
                                            AND ls.type                         = pa.type
         WHERE pa.dprice >= ls.lowsell
           AND pa.status = 'OPEN'
            )                   a
      JOIN categories           c   ON  c.categoryid        = a.categoryid
      JOIN subcategories        sc  ON  sc.subcategoryid    = a.subcategoryid
      JOIN boxtypes             bt  ON  bt.boxtypeid        = a.boxtypeid
      JOIN users                u   ON  u.userid            = a.userid
    ORDER BY u.username, a.type DESC, c.categorydescription, sc.subcategoryname, bt.boxtypename, a.year, a.alert_price
";

$rs = $DB->sql_query_params($sql);

unset($page->queries);
$page->queries = new DBQueries("price alert cleanup");

$sql = "DROP TABLE IF EXISTS tmp_high_buy_".$random;
$page->queries->AddQuery($sql);
$sql = "DROP TABLE IF EXISTS tmp_low_sell_".$random;
$page->queries->AddQuery($sql);
$process = $page->queries->ProcessQueries();

if (is_array($rs) && (count($rs) > 0)) {
    echo $newline."Found ".count($rs)." triggered alerts";
    $time = strtotime("now");

    $a = reset($rs);
    $prevMember = $a["username"];
    $prevMemberId = $a["userid"];
    $prevType   = $a["type"];
    $lastuserid = null;
    $lastusername = null;
    $alertList  = array();
    $alertCSV = "";
    $alertList[$a["type"]]["charlength"] =  0;
    $x = 1;
    foreach ($rs as $a) {
        if ($prevMember <> $a["username"]) {
            echo $newline;
            sendInternalMessage($prevMemberId, $prevMember, $alertList);
            markAsTriggered($time, $alertCSV);
            $alertCSV = "";

            unset($alertList);
            $alertList  = array();
            $prevType = $a["type"];
            $prevMember = $a["username"];
            $prevMemberId = $a["userid"];
            $alertList[$a["type"]]["charlength"] = 0;
            $x = 1;
        } elseif ($prevType <> $a["type"]) {
            $prevType = $a["type"];
            $alertList[$a["type"]]["charlength"] =  0;
            $x = 1;
        }
        $alert = $a["categorydescription"]." ".$a["subcategoryname"]." ".$a["boxtypename"];
        $alert = (empty($a["year"])) ? $alert : $a["year"]." ".$alert;
        $alertprice = " ($".number_format($a["alert_price"], 2).")";

        $url = "/listing.php?categoryid=".$a["categoryid"]."&subcategoryid=".$a["subcategoryid"]."&boxtypeid=".$a["boxtypeid"]."&listingtypeid=".$a["categorytypeid"]."&year=".$a["year"];
        $link = "<a href='".$url."' target='_blank'>".$alert."</a>".$alertprice;
        $item = "<div style='padding: 2px 0px 2px 25px;'>".$link."</div>";
        $alertList[$a["type"]][] = $item;
        $alertList[$a["type"]]["charlength"] += strlen($item);

        echo $newline."[".$x."] ".date('m/d/Y H:i:s')." - ".$a["username"]." - ".$a["type"].": ".$alert;
        $alertCSV .= (empty($alertCSV)) ? $a["alertid"] : ",".$a["alertid"];
        $lastuserid = $a["userid"];
        $lastusername = $a["username"];
        $x++;
    }
    sendInternalMessage($lastuserid, $lastusername, $alertList);
    markAsTriggered($time, $alertCSV);

}
unset($page);
echo $newline.date('m/d/Y H:i:s')." - Finished".$newline;

function markAsTriggered($time, $ids) {
    global $page, $DB, $newline;

    $sql = "
        UPDATE pricealerts
           SET status       = 'Triggered',
               modifiedby   = 'batch',
               modifydate   = ".$time."
         WHERE alertid in (".$ids.")
    ";

//      echo "<pre>".$sql."</pre>";
        try {
            $DB->sql_execute($sql);
        } catch (Exception $e) {
            $msg = "ERROR: ".$e->getMessage()." [Unable to update price alerts as triggered.]";
            echo $newline.$msg;
        } finally {
        }

}

function sendInternalMessage($userid, $username, $list) {
    global $page;

    // about 45 - 50 will max out the message size so anything over 40
    //  we will split into wanted / for sales
    if (!empty($list)) {
        $totalcnt = 0;
        foreach($list as $type=>$alerts) {
            if (!empty($alerts)) {
                $count[$type] = count($alerts);
                $totalcnt += count($alerts);
            }
        }

        $subject = "Triggered Price Alerts";
        if ($totalcnt <= MAXALERTSPERMSG) {
            $msg = "\n";
            foreach($list as $type=>$alerts) {
                if (!empty($alerts)) {
                    $msg .= "<h4>".$type."</h4>";
                    foreach($alerts as $idx=>$a) {
                        if ($idx <> "charlength") {
                            $msg .= $a;
                        }
                    }
                }
            }
            $page->iMessage->insertSystemMessage($page, $userid, $username, $subject, $msg, EMAIL);
        } else {
            $msg = "";
            $x = 0;
            foreach($list as $type=>$alerts) {
                if (!empty($alerts)) {
                    $msg .= "<h4>".$type."</h4>";
                    $maxmsgs = ceil(count($alerts)/MAXALERTSPERMSG);
                    $alertspermsg = round(count($alerts) / $maxmsgs);
                    $x = 0;
                    $y = 1;
                    foreach($alerts as $idx=>$a) {
                        $x++;
                        if ($x <= $alertspermsg) {
                            if ($idx <> "charlength") {
                                $msg .= $a;
                            }
                        } else {
                            $msgsubject = $subject." - ".$type." (".$y." of ".$maxmsgs.")";
                            $page->iMessage->insertSystemMessage($page, $userid, $username, $msgsubject, $msg, EMAIL);
                            $msg = "<h4>".$type."</h4>";
                            if ($idx <> "charlength") {
                                $msg .= $a;
                            }
                            $y++;
                            $x = 0;
                        }
                    }
                    if ($x > 0) {
                        $msgsubject = $subject." - ".$type." (".$y." of ".$maxmsgs.")";
                        $page->iMessage->insertSystemMessage($page, $userid, $username, $msgsubject, $msg, EMAIL);
                        $msg = "";
                        $x = 0;
                        $y++;
                    }
                }
            }
        }
    }

}
?>