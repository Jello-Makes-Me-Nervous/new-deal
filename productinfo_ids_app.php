<?php
require_once('template.class.php');

if (!(isset($page) && is_object($page))) {
    $page = new template(NOLOGIN, SHOWMSG);
    $factoryCostID = $page->utility->getDealerId(FACTORYCOSTNAME);

    $id         = optional_param('id', 0, PARAM_INT);
    $verbose    = optional_param('verbose', 0, PARAM_INT);
    $catid      = optional_param('cid', 0, PARAM_INT);
    $subcatid   = optional_param('scid', 0, PARAM_INT);
    $btid       = optional_param('btid', 0, PARAM_INT);
    $year       = optional_param('yr', 0, PARAM_TEXT);

    $oneday = (60*60*24);
    $onehour = (60*60);
    $joshbday = 1109980800;
    $now  = strtotime("now");
    if ($id+$joshbday-$onehour <= $now && $id+$joshbday+$onehour >= $now) {
        $match = getProductInfoIds($catid, $subcatid, $btid, $year);
        if ($match) {
            if ($verbose) {
                echo "<pre>";print_r($match);echo "</pre>";
            }
            $x = processIdsResults($match);
        } else {
            $error["code"] = -1;
            $error["msg"] = "Ids not found.";
            if ($verbose) {
                echo "<pre>";print_r($error);echo "</pre>";
            }
            $x = json_encode($error);
        }
    } else {
        $error["code"] = -2;
        $error["msg"] = "Unknown request.";
        if ($verbose) {
            echo "<pre>";
            print_r($error);
            $x = json_encode($error);
            echo "</pre>";
        } else {
            $x = json_encode($error);
        }
    }

    echo $x;
    exit();
}


function getProductInfoIds($catid, $subcatid, $btid, $yr) {
    global $page, $factoryCostID;

    $year = (empty($yr)) ? "NULL" : $yr;
    $random = rand();
    $sql = "DROP TABLE IF EXISTS tmp_high_buy_box_".$random;
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_low_sell_box_".$random;
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_high_buy_case_".$random;
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_low_sell_case_".$random;
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_factory_".$random;
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_upc_".$random;
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_total_traded_".$random;
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_30day_traded_".$random;
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_last_trade_".$random;
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_hist_hilo_".$random;
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_pix_".$random;
    $page->queries->AddQuery($sql);

    $yr = (empty($year) || $year == 'NULL') ? "NULL" : "'".$year."'";
    $sql = "
        CREATE TEMPORARY TABLE tmp_high_buy_box_".$random." AS
        SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year,
               MAX(l.boxprice) AS highbuy_box
          FROM listings             l
          JOIN categories           c   ON  c.categoryid        = l.categoryid
                                        AND c.categoryid        = ".$catid."
                                        AND c.active            = 1
          JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                        AND sc.subcategoryid    = ".$subcatid."
                                        AND sc.active           = 1
          JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                        AND bt.boxtypeid        = ".$btid."
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
           AND l.uom IN ('box')
           AND l.userid             <> ".$factoryCostID."
           AND stl.userid IS NULL
           AND isnull(l.year, '1') = isnull(".$yr.", '1')
        GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_low_sell_box_".$random." AS
        SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year,
               MIN(l.boxprice) AS lowsell_box
          FROM listings             l
          JOIN categories           c   ON  c.categoryid        = l.categoryid
                                        AND c.categoryid        = ".$catid."
                                        AND c.active            = 1
          JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                        AND sc.subcategoryid    = ".$subcatid."
                                        AND sc.active           = 1
          JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                        AND bt.boxtypeid        = ".$btid."
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
           AND l.uom IN ('box')
           AND l.userid             <> ".$factoryCostID."
           AND stl.userid IS NULL
           AND isnull(l.year, '1') = isnull(".$yr.", '1')
        GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_high_buy_case_".$random." AS
        SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year,
               MAX(l.boxprice) AS highbuy_case
          FROM listings             l
          JOIN categories           c   ON  c.categoryid        = l.categoryid
                                        AND c.categoryid        = ".$catid."
                                        AND c.active            = 1
          JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                        AND sc.subcategoryid    = ".$subcatid."
                                        AND sc.active           = 1
          JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                        AND bt.boxtypeid        = ".$btid."
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
           AND l.uom IN ('case')
           AND l.userid             <> ".$factoryCostID."
           AND stl.userid IS NULL
           AND isnull(l.year, '1') = isnull(".$yr.", '1')
        GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_low_sell_case_".$random." AS
        SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year,
               MIN(l.boxprice) AS lowsell_case
          FROM listings             l
          JOIN categories           c   ON  c.categoryid        = l.categoryid
                                        AND c.categoryid        = ".$catid."
                                        AND c.active            = 1
          JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                        AND sc.subcategoryid    = ".$subcatid."
                                        AND sc.active           = 1
          JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                        AND bt.boxtypeid        = ".$btid."
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
           AND l.uom IN ('case')
           AND l.userid             <> ".$factoryCostID."
           AND stl.userid IS NULL
           AND isnull(l.year, '1') = isnull(".$yr.", '1')
        GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_upc_".$random." AS
        SELECT p.productid, c.categoryid, c.categorydescription, c.categorytypeid,
               sc.subcategoryid, sc.subcategoryname,
               bt.boxtypeid, bt.boxtypename,
               p.year, p.sku as upc, p.variation, p.factorycost, p.picture

          FROM products         p
          JOIN categories       c   ON  c.categoryid        = p.categoryid
                                    AND c.categoryid        = ".$catid."
                                    AND c.active            = 1
          JOIN subcategories    sc  ON  sc.subcategoryid    = p.subcategoryid
                                    AND sc.subcategoryid    = ".$subcatid."
                                    AND sc.active           = 1
          JOIN boxtypes         bt  ON  bt.boxtypeid        = p.boxtypeid
                                    AND bt.boxtypeid        = ".$btid."
                                    AND bt.active           = 1
         WHERE isnull(p.year, '1') = isnull(".$yr.", '1')
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_total_traded_".$random." AS
        SELECT oh.categoryid, oh.subcategoryid, oh.boxtypeid, oh.year,
               sum(oh.boxquantity) as totalboxes
          FROM offer_history    oh
          JOIN categories       c   ON  c.categoryid        = oh.categoryid
                                    AND c.categoryid        = ".$catid."
                                    AND c.active            = 1
          JOIN subcategories    sc  ON  sc.subcategoryid    = oh.subcategoryid
                                    AND sc.subcategoryid    = ".$subcatid."
                                    AND sc.active           = 1
          JOIN boxtypes         bt  ON  bt.boxtypeid        = oh.boxtypeid
                                    AND bt.boxtypeid        = ".$btid."
                                    AND bt.active           = 1
         WHERE isnull(oh.year, '1') = isnull(".$yr.", '1')
        GROUP BY oh.categoryid, oh.subcategoryid, oh.boxtypeid, oh.year
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_30day_traded_".$random." AS
        SELECT oh.categoryid, oh.subcategoryid, oh.boxtypeid, oh.year,
               sum(oh.boxquantity) as totalboxes_30
          FROM offer_history    oh
          JOIN categories       c   ON  c.categoryid        = oh.categoryid
                                    AND c.categoryid        = ".$catid."
                                    AND c.active            = 1
          JOIN subcategories    sc  ON  sc.subcategoryid    = oh.subcategoryid
                                    AND sc.subcategoryid    = ".$subcatid."
                                    AND sc.active           = 1
          JOIN boxtypes         bt  ON  bt.boxtypeid        = oh.boxtypeid
                                    AND bt.boxtypeid        = ".$btid."
                                    AND bt.active           = 1
         WHERE to_timestamp(oh.transactiondate)::date > NOW() - INTERVAL '30 days'
           AND isnull(oh.year, '1') = isnull(".$yr.", '1')
        GROUP BY oh.categoryid, oh.subcategoryid, oh.boxtypeid, oh.year
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_last_trade_".$random." AS
        SELECT oh.categoryid, oh.subcategoryid, oh.boxtypeid, oh.year,
               oh.boxquantity, oh.boxprice, inttommddyyyy(oh.transactiondate) as transactiondate
          FROM offer_history    oh
          JOIN categories       c   ON  c.categoryid        = oh.categoryid
                                    AND c.categoryid        = ".$catid."
                                    AND c.active            = 1
          JOIN subcategories    sc  ON  sc.subcategoryid    = oh.subcategoryid
                                    AND sc.subcategoryid    = ".$subcatid."
                                    AND sc.active           = 1
          JOIN boxtypes         bt  ON  bt.boxtypeid        = oh.boxtypeid
                                    AND bt.boxtypeid        = ".$btid."
                                    AND bt.active           = 1
         WHERE isnull(oh.year, '1') = isnull(".$yr.", '1')
        ORDER BY oh.transactiondate DESC
        LIMIT 1
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_hist_hilo_".$random." AS
        SELECT oh.categoryid, oh.subcategoryid, oh.boxtypeid, oh.year,
               max(boxprice)::numeric(12,2) as highprice, min(boxprice)::numeric(12,2) AS lowprice
          FROM offer_history    oh
          JOIN categories       c   ON  c.categoryid        = oh.categoryid
                                    AND c.categoryid        = ".$catid."
                                    AND c.active            = 1
          JOIN subcategories    sc  ON  sc.subcategoryid    = oh.subcategoryid
                                    AND sc.subcategoryid    = ".$subcatid."
                                    AND sc.active           = 1
          JOIN boxtypes         bt  ON  bt.boxtypeid        = oh.boxtypeid
                                    AND bt.boxtypeid        = ".$btid."
                                    AND bt.active           = 1
         WHERE isnull(oh.year, '1') = isnull(".$yr.", '1')
        GROUP BY oh.categoryid, oh.subcategoryid, oh.boxtypeid, oh.year
    ";
    $page->queries->AddQuery($sql);


//    foreach($page->queries->sqls as $sql) {
//        echo "<pre>";print_r($sql);echo ";</pre>";
//    }

    $page->queries->ProcessQueries();

    $sql = "
        SELECT DISTINCT c.categorydescription, sc.subcategoryname, bt.boxtypename, p.year,
               p.variation,
               p.sku                    as upc,
               hbb.highbuy_box,
               lsb.lowsell_box,
               hbc.highbuy_case,
               lsc.lowsell_case,
               p.factorycost            as factory_cost,
               tt.totalboxes            as total_boxes,
               t30.totalboxes_30        as total_boxes_30,
               lst.boxquantity          as last_qty,
               lst.boxprice             as last_price,
               lst.transactiondate      as last_transaction_date,
               hilo.highprice           as high_price,
               hilo.lowprice            as low_price,
               p.picture,
               c.categoryid             as skip_catid,
               c.categorytypeid         as skip_cattypeid,
               sc.subcategoryid         as skip_subcatid,
               bt.boxtypeid             as skip_btid
          FROM products                             p
          JOIN categories                           c   ON  c.categoryid            = p.categoryid
                                                        AND c.categoryid            = ".$catid."
                                                        AND c.active                = 1
          JOIN subcategories                        sc  ON  sc.subcategoryid        = p.subcategoryid
                                                        AND sc.subcategoryid        = ".$subcatid."
                                                        AND sc.active               = 1
          JOIN boxtypes                             bt  ON  bt.boxtypeid            = p.boxtypeid
                                                        AND bt.boxtypeid            = ".$btid."
                                                        AND bt.active               = 1
          LEFT JOIN tmp_high_buy_box_".$random."    hbb ON  hbb.categoryid          = p.categoryid
                                                        AND hbb.subcategoryid       = p.subcategoryid
                                                        AND hbb.boxtypeid           = p.boxtypeid
                                                        AND isnull(hbb.year, '1')   = isnull(p.year, '1')
          LEFT JOIN tmp_low_sell_box_".$random."    lsb ON  lsb.categoryid          = p.categoryid
                                                        AND lsb.subcategoryid       = p.subcategoryid
                                                        AND lsb.boxtypeid           = p.boxtypeid
                                                        AND isnull(lsb.year, '1')   = isnull(p.year, '1')
          LEFT JOIN tmp_high_buy_case_".$random."   hbc ON  hbc.categoryid          = p.categoryid
                                                        AND hbc.subcategoryid       = p.subcategoryid
                                                        AND hbc.boxtypeid           = p.boxtypeid
                                                        AND isnull(hbc.year, '1')   = isnull(p.year, '1')
          LEFT JOIN tmp_low_sell_case_".$random."   lsc ON  lsc.categoryid          = p.categoryid
                                                        AND lsc.subcategoryid       = p.subcategoryid
                                                        AND lsc.boxtypeid           = p.boxtypeid
                                                        AND isnull(lsc.year, '1')   = isnull(p.year, '1')
          LEFT JOIN tmp_total_traded_".$random."    tt  ON  tt.categoryid           = p.categoryid
                                                        AND tt.subcategoryid        = p.subcategoryid
                                                        AND tt.boxtypeid            = p.boxtypeid
                                                        AND isnull(tt.year, '1')    = isnull(p.year, '1')
          LEFT JOIN tmp_30day_traded_".$random."    t30 ON  t30.categoryid          = p.categoryid
                                                        AND t30.subcategoryid       = p.subcategoryid
                                                        AND t30.boxtypeid           = p.boxtypeid
                                                        AND isnull(t30.year, '1')   = isnull(p.year, '1')
          LEFT JOIN tmp_last_trade_".$random."      lst ON  lst.categoryid          = p.categoryid
                                                        AND lst.subcategoryid       = p.subcategoryid
                                                        AND lst.boxtypeid           = p.boxtypeid
                                                        AND isnull(lst.year, '1')   = isnull(p.year, '1')
          LEFT JOIN tmp_hist_hilo_".$random."      hilo ON  hilo.categoryid         = p.categoryid
                                                        AND hilo.subcategoryid      = p.subcategoryid
                                                        AND hilo.boxtypeid          = p.boxtypeid
                                                        AND isnull(hilo.year, '1')  = isnull(p.year, '1')
         WHERE isnull(p.year, '1')   = isnull(".$yr.", '1')
        ORDER BY c.categorydescription, sc.subcategoryname, bt.boxtypename, p.year
        LIMIT 50
    ";

//  echo "<pre>";print_r($sql);echo "</pre>";
    $rs = $page->db->sql_query_params($sql);
    $one = null;
    if (!empty($rs)) {
        $one = reset($rs);
    }

    return $one;
}

function processIdsResults($match) {
    global $verbose;

        $link  = "https://dealernetx.com/listing.php";
        $link .= "?categoryid=".$match["skip_catid"];
        $link .= "&subcategoryid=".$match["skip_subcatid"];
        $link .= "&boxtypeid=".$match["skip_btid"];
        $link .= "&listingtypeid=".$match["skip_cattypeid"];
        $link .= "&year=".$match["year"];
        $info = array();
        foreach($match as $item=>$dp) {
            if (strpos($item, "skip_") === false) {
                $info[$item] = $dp;
            }
        }
        $info["link"] = $link;
        if ($verbose) {
            echo "<pre>";print_r($info);echo "</pre>";
        }
        $x = json_encode($info);

    return $x;
}

?>