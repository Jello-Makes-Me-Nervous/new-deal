<?php
require_once('template.class.php');

if (!(isset($page) && is_object($page))) {
    $page = new template(NOLOGIN, SHOWMSG);
    $factoryCostID = $page->utility->getDealerId(FACTORYCOSTNAME);

    require_once('productinfo_upc_app.php');
    require_once('productinfo_ids_app.php');

    $id      = optional_param('id', 0, PARAM_INT);
    $kw      = optional_param('kw', 0, PARAM_TEXT);
    $verbose = optional_param('verbose', 0, PARAM_INT);

    $oneday = (60*60*24);
    $onehour = (60*60);
    $joshbday = 1109980800;
    $now  = strtotime("now");
    if ($id+$joshbday-$onehour <= $now && $id+$joshbday+$onehour >= $now) {
        $matches = getProductInfoKeyword($kw);
        if ($verbose) {
            echo "<pre>Matched:<br>";print_r($matches);echo "</pre>";
        }
        $x = processKeywordResults($matches);
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


function getProductInfoKeyword($keywordsearch) {
    global $page, $factoryCostID, $verbose;

    $keyword    = "";
    if (!empty($keywordsearch)) {
        $searchstring = strtolower(trim($keywordsearch));
        $searcharray = explode(" ",$searchstring);
        if ($verbose) {
            echo "<br>Search String: ".$searchstring;
            echo "<pre>Keywords:<br>";print_r($searcharray);echo "</pre>";
        }
        foreach($searcharray as $sa) {
            if (!empty($sa)) {
                $keyword .= " AND (lower(p.year) LIKE '%".$sa."%'\n";
                $keyword .=  "     OR lower(c.categorydescription) LIKE '%".$sa."%'\n";
                $keyword .=  "     OR lower(sc.subcategoryname) LIKE '%".$sa."%'\n";
                $keyword .=  "     OR lower(bt.boxtypename) LIKE '%".$sa."%'\n";
                $keyword .=  "     OR lower(pu.upc) LIKE '%".$sa."%'\n";
                $keyword .=  "     OR lower(p.variation) LIKE '%".$sa."%')\n";
            }
        }
    }

    $sql = "
        SELECT DISTINCT p.categoryid, c.categorydescription, c.categorytypeid,
                        p.subcategoryid, sc.subcategoryname,
                        p.boxtypeid, bt.boxtypename,
                        p.year,
                        pu.upc,
                        p.variation
          FROM products         p
          JOIN categories       c   ON  c.categoryid            = p.categoryid
                                    AND c.active                = 1
          JOIN subcategories    sc  ON  sc.subcategoryid        = p.subcategoryid
                                    AND sc.active               = 1
                                    AND sc.secondary            = 0
          JOIN boxtypes         bt  ON  bt.boxtypeid            = p.boxtypeid
                                    AND bt.active               = 1
          LEFT JOIN (
                SELECT u.productid, array_to_string(array_agg(u.upc), ',') as upc
                  FROM product_upc  u
                  JOIN products     pr  ON  pr.productid        = u.productid
                                        AND pr.active           = 1
                GROUP BY u.productid
                    )           pu  ON  pu.productid        = p.productid

         WHERE 1 = 1
           ".$keyword."
        ORDER BY c.categorydescription, sc.subcategoryname, bt.boxtypename, p.year
    ";

//  echo "<pre>";print_r($sql);echo "</pre>";
    $rs = $page->db->sql_query_params($sql);


    return $rs;
}

function processKeywordResults($matches) {
    global $verbose;

    $x = null;
    if ($matches) {
        $products = array();
        if (count($matches) == 1) {
            $one = reset($matches);
            if ($verbose) {
                echo "<pre>One<br>";print_r($one);echo "</pre>";
            }
            if (isset($one["upc"]) && !empty($one["upc"])) {
                if (strpos($one["upc"], ",") === false) {
                    $upc = $one["upc"];
                } else {
                    $x = explode(',', $one["upc"]);
                    $upc = reset($x);
                }
                $match = getProductInfoUPC($upc);
                if ($match) {
                    $x = processUPCResults($match);
                }
            } elseif (!empty($one["categoryid"]) && !empty($one["subcategoryid"]) && !empty($one["boxtypeid"])) {
                if ($verbose) {
                    echo "<br>Ids:".$one["categoryid"]." - ".$one["subcategoryid"]." - ".$one["boxtypeid"];
                }
                $match = getProductInfoIds($one["categoryid"], $one["subcategoryid"], $one["boxtypeid"], $one["year"]);
                if ($verbose) {
                    echo "<pre>Matched Ids<br>";print_r($match);echo "</pre>";
                }
                if ($match) {
                    $x = processIdsResults($match);
                }
            }
        } else {
            $idx = 0;
            foreach ($matches as $m) {
                $product = "";
                $product .= (empty($m["year"])) ? "" : $m["year"];
                $product .= (empty($product)) ? $m["subcategoryname"] : " ".$m["subcategoryname"];
                $product .= " ".$m["categorydescription"];
                $product .= " ".$m["boxtypename"];
                $product .= (empty($m["variation"])) ? "" : " - ".$m["variation"];
                $product .= (empty($m["upc"])) ? "" : " - ".$m["upc"];
                if (!empty($m["upc"])) {
                    if (strpos($m["upc"], ",") === false) {
                        $upc = $m["upc"];
                    } else {
                        $x = explode(',', $m["upc"]);
                        $upc = reset($x);
                    }
                    $link  = "https://dealernetx.com/productinfo_app.php";
                    $link .= "?upc=".$upc;
                } else {
                    $link  = "https://dealernetx.com/productinfo_app.php";
                    $link .= "?cid=".$m["categoryid"];
                    $link .= "&scid=".$m["subcategoryid"];
                    $link .= "&btid=".$m["boxtypeid"];
                    $link .= (empty($m["year"])) ? "" : "&yr=".$m["year"];
                }
                $products[$idx]["product"] = $product;
                $products[$idx]["link"]    = $link;
                $idx++;
            }
            if ($verbose) {
                echo "<pre>";print_r($products);echo "</pre>";
            }
            $x = json_encode($products);
        }
    } else {
        $error["code"] = -1;
        $error["msg"] = "Keywords not found.";
        if ($verbose) {
            echo "<pre>";print_r($error);echo "</pre>";
        }
        $x = json_encode($error);
    }

    return $x;
}

?>