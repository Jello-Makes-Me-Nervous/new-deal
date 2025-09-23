<?php
define('CLI_SCRIPT', true);
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

$page = new template(NOLOGIN, NOSHOWMSG, REDIRECTSAFE);

echo date('m/d/Y H:i:s')." - BEGIN external notification batch".$newline;
dropTempTablesSQL();
createTempTablesSQL();

$timeblocks = array();
$timeblocks[5]  = array(0=>0, 5=>5, 10=>10, 15=>15, 20=>20, 25=>25, 30=>30, 35=>35, 40=>40, 45=>45, 50=>50, 55=>55);
$timeblocks[15] = array(0=>0, 15=>15, 30=>30, 45=>45);
$timeblocks[30] = array(0=>0, 30=>30);
$timeblocks[60] = array(0=>0);

$subject  = "DealernetX Notification";
$message  = "Please log into your account regarding recent activity that requires your attention.\n";
$message .= " https://www.dealernetx.com\n";

$min = intval(date("i"));
$minminus1 = $min - 1; // handle case where cronjob starts 1 min late
echo "Min:".$min.$newline;

try {
    $page->queries->ProcessQueries();
    foreach($timeblocks as $tb=>$minutes) {
        $fromtime   = null;
        $totime     = null;

        getTimeBlock($fromtime, $totime, $tb);
        if (array_key_exists($min, $minutes) || array_key_exists($minminus1, $minutes)) {
            echo date('m/d/Y H:i:s')." - ".$tb." - From: ".date("H:i:s", $fromtime)." - To: ".date("H:i:s", $totime).$newline;
            $ids = getUserIds($fromtime, $totime);
            if (!empty($ids)) {
                $notifications = getExternalNotifications($ids, $tb);
                if (!empty($notifications)) {
                    foreach($notifications as $n) {
                        echo date('m/d/Y H:i:s')." ".$n["notification_type"].": ".$n["user"]." - ".$n["emailphone"].$newline;
                        if ($n["notification_type"] == EMAILNOTIFICATIONTYPE) {
                            $page->iMessage->sendExternalEmail($n["userid"], $subject, $message);
                        } elseif ($n["notification_type"] == SMSNOTIFICATIONTYPE) {
                            $page->iMessage->sendExternalSMS($n["userid"], $message);
                        }
                    }
                }
            }
        } else {
            echo "Skipping ... ".$tb.$newline;
        }
    }

} catch (Exception $e) {
    echo "ERROR: ".$e->getMessage()." [Unable to setup temp tables]".$newline;
} finally {
    unset($params);
}

dropTempTablesSQL();
$page->queries->ProcessQueries();

echo date('m/d/Y H:i:s')." - Finished".$newline;

function dropTempTablesSQL() {
    global $page;

    $sql = "DROP TABLE IF EXISTS tmp_5min";
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_15min";
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_30min";
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_60min";
    $page->queries->AddQuery($sql);
}

function createTempTablesSQL() {
    global $page;

    $sql = "
        CREATE TEMPORARY TABLE tmp_5min AS
        SELECT row_number() over() as rownum, x.block
          FROM (
            SELECT generate_series(inttodatetime(startdatetime(nowtoint()))::timestamp - INTERVAL '1 hour',
                               inttodatetime(enddatetime(nowtoint()))::timestamp,
                               '5 minute'::interval)::timestamp as block
               )  x
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_15min AS
        SELECT row_number() over() as rownum, x.block
          FROM (
            SELECT generate_series(inttodatetime(startdatetime(nowtoint()))::timestamp - INTERVAL '1 hour',
                               inttodatetime(enddatetime(nowtoint()))::timestamp,
                               '15 minute'::interval)::timestamp as block
                )  x
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_30min AS
        SELECT row_number() over() as rownum, x.block
          FROM (
            SELECT generate_series(inttodatetime(startdatetime(nowtoint()))::timestamp - INTERVAL '1 hour',
                               inttodatetime(enddatetime(nowtoint()))::timestamp,
                               '30 minute'::interval)::timestamp as block
                )  x
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_60min AS
        SELECT row_number() over() as rownum, x.block
          FROM (
            SELECT generate_series(inttodatetime(startdatetime(nowtoint()))::timestamp - INTERVAL '2 hour',
                               inttodatetime(enddatetime(nowtoint()))::timestamp,
                               '60 minute'::interval)::timestamp as block
                )  x
    ";
    $page->queries->AddQuery($sql);

}

function getTimeBlock(&$fromtime, &$totime, $tb) {
    global $page;

    $tablename  = "tmp_".$tb."min";
    $interval   = $tb." minutes";
    $sql = "
        SELECT datetimetoint(b.block) as from_block,
               datetimetoint(a.block) as to_block
          FROM ".$tablename." a
          JOIN ".$tablename." b on b.rownum = a.rownum - 1
         WHERE CURRENT_TIMESTAMP - INTERVAL '".$interval."' <= a.block
           AND CURRENT_TIMESTAMP - INTERVAL '".$interval."' > b.block
    ";

//  echo "<pre>".$sql."</pre>";
    if ($rs = $page->db->sql_query($sql)) {
        $row        = reset($rs);
        $fromtime   = $row["from_block"];
        $totime     = $row["to_block"];
    }

    return $row;
}

function getUserIds($fromtime, $totime) {
    global $page;

    $sql = "
        SELECT toid as id
          FROM messaging
         WHERE createdate BETWEEN ".$fromtime." AND ".$totime."
        UNION
        SELECT offerto as id
          FROM offers
         WHERE modifydate BETWEEN ".$fromtime." AND ".$totime."
           AND offerstatus in ('PENDING', 'CANCELLED', 'VOID')
        UNION
        SELECT offerfrom as id
          FROM offers
         WHERE modifydate BETWEEN ".$fromtime." AND ".$totime."
           AND offerstatus in ('EXPIRED', 'DECLINED', 'ACCEPTED', 'VOID')
        UNION
        SELECT useraccountid as id
          FROM transactions
         WHERE createdate BETWEEN ".$fromtime." AND ".$totime."
          AND transtype = 'RECEIPT'
        ORDER BY 1
    ";

//    echo "<pre>".$sql."</pre>";
    $rs = $page->db->sql_query($sql);
    $ids = "";
    if (!empty($rs)) {
        foreach($rs as $row) {
            $ids .= (empty($ids)) ? $row["id"] : ",".$row["id"];
        }
    }

    return $ids;
}

function getExternalNotifications($ids, $frequency) {
    global $page;

    $sql = "
        SELECT np.userid, np.notification_type, np.emailphone, u.username,
               np.userid || ' - ' || u.username as user
          FROM notification_preferences     np
          JOIN users                        u   ON  u.userid    = np.userid
         WHERE np.frequency    = ".$frequency."
           AND np.isactive     = 1
           AND np.validated_on IS NOT NULL
           AND np.userid in (".$ids.")
        ORDER BY np.notification_type, np.userid
    ";

//    echo "<pre>".$sql."</pre>";
    $rs = $page->db->sql_query($sql);

    return $rs;
}
?>
