<?php
require_once('template.class.php');

if (!(isset($page) && is_object($page))) {
    $page = new template(NOLOGIN, SHOWMSG);
    $factoryCostID = $page->utility->getDealerId(FACTORYCOSTNAME);

    $id      = optional_param('id', 0, PARAM_INT);
    $upc     = optional_param('upc', 0, PARAM_RAW);
    $verbose = optional_param('verbose', 0, PARAM_INT);
    $jsonout = optional_param('json', 1, PARAM_INT);

    $oneday = (60*60*24);
    $onehour = (60*60);
    $joshbday = 1109980800;
    $now  = strtotime("now");
    if ($id+$joshbday-$onehour <= $now && $id+$joshbday+$onehour >= $now) {
        if (!empty($upc) && strlen($upc) < 15) {
            $match = getProductInfoUPC($upc);
            if ($match) {
                if ($verbose) {
                    echo "<pre>";print_r($match);echo "</pre>";
                }
                $x = processUPCResults($match);
            } else {
                $error["code"] = -1;
                $error["msg"] = "UPC not found.";
                if ($verbose) {
                    echo "<pre>";print_r($error);echo "</pre>";
                }
                $x = json_encode($error);
            }
        } else {
            $error["code"] = -1;
            $error["msg"] = "UPC not found.";
            if ($verbose) {
                echo "<pre>";print_r($error);echo "</pre>";
            }
            $x = json_encode($error);
        }
    } else {
        $error["code"] = -2;
        $error["msg"] = "Unknown request.";
        if ($verbose) {
            echo "<pre>";print_r($error);echo "</pre>";
        }
        $x = json_encode($error);
    }

    echo $x;
    exit();
}


function getProductInfoUPC($upc) {
    global $page, $factoryCostID;

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

    $sql = "
        CREATE TEMPORARY TABLE tmp_high_buy_box_".$random." AS
        SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year,
               MAX(l.boxprice) AS highbuy_box
          FROM listings             l
          JOIN categories           c   ON  c.categoryid        = l.categoryid
                                        AND c.active            = 1
          JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                        AND sc.active           = 1
          JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                        AND bt.active           = 1
          JOIN assignedrights       a   ON  a.userid            = l.userid
                                        AND a.userrightid       = 1
          JOIN userinfo             u   ON  u.userid            = l.userid
                                        AND u.userclassid       = 3
                                        AND u.vacationbuy       = 0
          JOIN products             p   ON  p.categoryid        = l.categoryid
                                        AND p.subcategoryid     = l.subcategoryid
                                        AND p.boxtypeid         = l.boxtypeid
                                        AND isnull(p.year, '1') = isnull(l.year, '1')
          JOIN product_upc          pu  ON  pu.productid        = p.productid
                                        AND pu.upc              = '".$upc."'
          LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                        AND stl.userrightid     = ".USERRIGHT_STALE."
         WHERE l.type               = 'Wanted'
           AND l.status             = 'OPEN'
           AND l.uom IN ('box')
           AND l.userid             <> ".$factoryCostID."
           AND stl.userid IS NULL
        GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_low_sell_box_".$random." AS
        SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year,
               MIN(l.boxprice) AS lowsell_box
          FROM listings             l
          JOIN categories           c   ON  c.categoryid        = l.categoryid
                                        AND c.active            = 1
          JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                        AND sc.active           = 1
          JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                        AND bt.active           = 1
          JOIN assignedrights       a   ON  a.userid            = l.userid
                                        AND a.userrightid       = 1
          JOIN userinfo             u   ON  u.userid            = l.userid
                                        AND u.userclassid       = 3
                                        AND u.vacationsell      = 0
          JOIN products             p   ON  p.categoryid        = l.categoryid
                                        AND p.subcategoryid     = l.subcategoryid
                                        AND p.boxtypeid         = l.boxtypeid
                                        AND isnull(p.year, '1') = isnull(l.year, '1')
          JOIN product_upc          pu  ON  pu.productid        = p.productid
                                        AND pu.upc              = '".$upc."'
          LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                        AND stl.userrightid     = ".USERRIGHT_STALE."
         WHERE l.type               = 'For Sale'
           AND l.status             = 'OPEN'
           AND l.uom IN ('box')
           AND l.userid             <> ".$factoryCostID."
           AND stl.userid IS NULL
        GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_high_buy_case_".$random." AS
        SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year,
               MAX(l.boxprice) AS highbuy_case
          FROM listings             l
          JOIN categories           c   ON  c.categoryid        = l.categoryid
                                        AND c.active            = 1
          JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                        AND sc.active           = 1
          JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                        AND bt.active           = 1
          JOIN assignedrights       a   ON  a.userid            = l.userid
                                        AND a.userrightid       = 1
          JOIN userinfo             u   ON  u.userid            = l.userid
                                        AND u.userclassid       = 3
                                        AND u.vacationbuy       = 0
          JOIN products             p   ON  p.categoryid        = l.categoryid
                                        AND p.subcategoryid     = l.subcategoryid
                                        AND p.boxtypeid         = l.boxtypeid
                                        AND isnull(p.year, '1') = isnull(l.year, '1')
          JOIN product_upc          pu  ON  pu.productid        = p.productid
                                        AND pu.upc              = '".$upc."'
          LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                        AND stl.userrightid     = ".USERRIGHT_STALE."
         WHERE l.type               = 'Wanted'
           AND l.status             = 'OPEN'
           AND l.uom IN ('case')
           AND l.userid             <> ".$factoryCostID."
           AND stl.userid IS NULL
        GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_low_sell_case_".$random." AS
        SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year,
               MIN(l.boxprice) AS lowsell_case
          FROM listings             l
          JOIN categories           c   ON  c.categoryid        = l.categoryid
                                        AND c.categorytypeid    = ".LISTING_TYPE_SPORTS."
                                        AND c.active            = 1
          JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                        AND sc.active           = 1
          JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                        AND bt.active           = 1
          JOIN assignedrights       a   ON  a.userid            = l.userid
                                        AND a.userrightid       = 1
          JOIN userinfo             u   ON  u.userid            = l.userid
                                        AND u.userclassid       = 3
                                        AND u.vacationsell      = 0
          JOIN products             p   ON  p.categoryid        = l.categoryid
                                        AND p.subcategoryid     = l.subcategoryid
                                        AND p.boxtypeid         = l.boxtypeid
                                        AND isnull(p.year, '1') = isnull(l.year, '1')
          JOIN product_upc          pu  ON  pu.productid        = p.productid
                                        AND pu.upc              = '".$upc."'
          LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                        AND stl.userrightid     = ".USERRIGHT_STALE."
         WHERE l.type               = 'For Sale'
           AND l.status             = 'OPEN'
           AND l.uom IN ('case')
           AND l.userid             <> ".$factoryCostID."
           AND stl.userid IS NULL
        GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_upc_".$random." AS
        SELECT p.productid, c.categoryid, c.categorydescription, c.categorytypeid,
               sc.subcategoryid, sc.subcategoryname,
               bt.boxtypeid, bt.boxtypename,
               p.year, pu.upc, p.variation, p.picture, p.factorycost

          FROM products         p
          JOIN product_upc      upc ON  upc.productid        = p.productid
                                    AND upc.upc              = '".$upc."'
          JOIN (
                SELECT u.productid, array_to_string(array_agg(u.upc), ',') as upc
                  FROM product_upc  u
                  JOIN products     pr  ON  pr.productid        = u.productid
                                        AND pr.active           = 1
                GROUP BY u.productid
               )                pu  ON  pu.productid        = p.productid
          JOIN categories       c   ON  c.categoryid        = p.categoryid
                                    AND c.active            = 1
          JOIN subcategories    sc  ON  sc.subcategoryid    = p.subcategoryid
                                    AND sc.active           = 1
          JOIN boxtypes         bt  ON  bt.boxtypeid        = p.boxtypeid
                                    AND bt.active           = 1
         WHERE p.active = 1
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_total_traded_".$random." AS
        SELECT oh.categoryid, oh.subcategoryid, oh.boxtypeid, oh.year,
               sum(oh.boxquantity) as totalboxes
          FROM offer_history    oh
          JOIN products         p   ON  p.categoryid        = oh.categoryid
                                    AND p.subcategoryid     = oh.subcategoryid
                                    AND p.boxtypeid         = oh.boxtypeid
                                    AND isnull(p.year, '1') = isnull(oh.year, '1')
          JOIN product_upc      pu  ON  pu.productid        = p.productid
                                        AND pu.upc          = '".$upc."'
        GROUP BY oh.categoryid, oh.subcategoryid, oh.boxtypeid, oh.year
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_30day_traded_".$random." AS
        SELECT oh.categoryid, oh.subcategoryid, oh.boxtypeid, oh.year,
               sum(oh.boxquantity) as totalboxes_30
          FROM offer_history    oh
          JOIN products         p   ON  p.categoryid        = oh.categoryid
                                    AND p.subcategoryid     = oh.subcategoryid
                                    AND p.boxtypeid         = oh.boxtypeid
                                    AND isnull(p.year, '1') = isnull(oh.year, '1')
          JOIN product_upc      pu  ON  pu.productid        = p.productid
                                        AND pu.upc          = '".$upc."'
         WHERE to_timestamp(oh.transactiondate)::date > NOW() - INTERVAL '30 days'
        GROUP BY oh.categoryid, oh.subcategoryid, oh.boxtypeid, oh.year
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_last_trade_".$random." AS
        SELECT oh.categoryid, oh.subcategoryid, oh.boxtypeid, oh.year,
               oh.boxquantity, oh.boxprice, inttommddyyyy(oh.transactiondate) as transactiondate
          FROM offer_history    oh
          JOIN products         p   ON  p.categoryid        = oh.categoryid
                                    AND p.subcategoryid     = oh.subcategoryid
                                    AND p.boxtypeid         = oh.boxtypeid
                                    AND isnull(p.year, '1') = isnull(oh.year, '1')
          JOIN product_upc      pu  ON  pu.productid        = p.productid
                                    AND pu.upc              = '".$upc."'
        ORDER BY oh.transactiondate DESC
        LIMIT 1
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_hist_hilo_".$random." AS
        SELECT oh.categoryid, oh.subcategoryid, oh.boxtypeid, oh.year,
               max(boxprice)::numeric(12,2) as highprice, min(boxprice)::numeric(12,2) AS lowprice
          FROM offer_history    oh
          JOIN products         p   ON  p.categoryid        = oh.categoryid
                                    AND p.subcategoryid     = oh.subcategoryid
                                    AND p.boxtypeid         = oh.boxtypeid
                                    AND isnull(p.year, '1') = isnull(oh.year, '1')
          JOIN product_upc      pu  ON  pu.productid        = p.productid
                                    AND pu.upc              = '".$upc."'
        GROUP BY oh.categoryid, oh.subcategoryid, oh.boxtypeid, oh.year
    ";
    $page->queries->AddQuery($sql);


//foreach ($page->queries->sqls as $sql) {
//    echo "<pre>".$sql.";</pre>\n";
//}

    $page->queries->ProcessQueries();

    $sql = "
        SELECT upc.categorydescription, upc.subcategoryname, upc.boxtypename, upc.year,
               upc.variation, upc.upc, upc.picture,
               upc.factorycost          as factory_cost,
               hbb.highbuy_box, lsb.lowsell_box,
               hbc.highbuy_case, lsc.lowsell_case,
               tt.totalboxes            as total_boxes,
               t30.totalboxes_30        as total_boxes_30,
               lst.boxquantity          as last_qty,
               lst.boxprice             as last_price,
               lst.transactiondate      as last_transaction_date,
               hilo.highprice           as high_price,
               hilo.lowprice            as low_price,
               upc.categoryid           as skip_catid,
               upc.categorytypeid       as skip_cattypeid,
               upc.subcategoryid        as skip_subcatid,
               upc.boxtypeid            as skip_btid
          FROM tmp_upc_".$random."                  upc
          LEFT JOIN tmp_high_buy_box_".$random."    hbb ON  hbb.categoryid          = upc.categoryid
                                                        AND hbb.subcategoryid       = upc.subcategoryid
                                                        AND hbb.boxtypeid           = upc.boxtypeid
                                                        AND isnull(hbb.year, '1')   = isnull(upc.year, '1')
          LEFT JOIN tmp_low_sell_box_".$random."    lsb ON  lsb.categoryid          = upc.categoryid
                                                        AND lsb.subcategoryid       = upc.subcategoryid
                                                        AND lsb.boxtypeid           = upc.boxtypeid
                                                        AND isnull(lsb.year, '1')   = isnull(upc.year, '1')
          LEFT JOIN tmp_high_buy_case_".$random."   hbc ON  hbc.categoryid          = upc.categoryid
                                                        AND hbc.subcategoryid       = upc.subcategoryid
                                                        AND hbc.boxtypeid           = upc.boxtypeid
                                                        AND isnull(hbc.year, '1')   = isnull(upc.year, '1')
          LEFT JOIN tmp_low_sell_case_".$random."   lsc ON  lsc.categoryid          = upc.categoryid
                                                        AND lsc.subcategoryid       = upc.subcategoryid
                                                        AND lsc.boxtypeid           = upc.boxtypeid
                                                        AND isnull(lsc.year, '1')   = isnull(upc.year, '1')
          LEFT JOIN tmp_total_traded_".$random."    tt  ON  tt.categoryid           = upc.categoryid
                                                        AND tt.subcategoryid        = upc.subcategoryid
                                                        AND tt.boxtypeid            = upc.boxtypeid
                                                        AND isnull(tt.year, '1')    = isnull(upc.year, '1')
          LEFT JOIN tmp_30day_traded_".$random."    t30 ON  t30.categoryid          = upc.categoryid
                                                        AND t30.subcategoryid       = upc.subcategoryid
                                                        AND t30.boxtypeid           = upc.boxtypeid
                                                        AND isnull(t30.year, '1')    = isnull(upc.year, '1')
          LEFT JOIN tmp_last_trade_".$random."      lst ON  lst.categoryid          = upc.categoryid
                                                        AND lst.subcategoryid       = upc.subcategoryid
                                                        AND lst.boxtypeid           = upc.boxtypeid
                                                        AND isnull(lst.year, '1')   = isnull(upc.year, '1')
          LEFT JOIN tmp_hist_hilo_".$random."      hilo ON  hilo.categoryid         = upc.categoryid
                                                        AND hilo.subcategoryid      = upc.subcategoryid
                                                        AND hilo.boxtypeid          = upc.boxtypeid
                                                        AND isnull(hilo.year, '1')  = isnull(upc.year, '1')
    ";

//    echo "<pre>".$sql.";</pre>\n";
    $one = null;
    if ($rs = $page->db->sql_query_params($sql)) {
        $one = reset($rs);
    }

    return $one;
}

function processUPCResults($match) {
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