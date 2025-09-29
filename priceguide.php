<?php
require_once('templateMarket.class.php');

$page = new templateMarket(LOGIN, SHOWMSG);
$calendarJS = '
    $(function(){$("#fromdate").datepicker();});
    $(function(){$("#todate").datepicker();});
';
$page->jsInit($calendarJS);

$defaultFromDate = date('m/d/Y', strtotime('-30 days'));

$boxTypeId  = optional_param('boxtypeid', NULL, PARAM_INT);
$categoryId = optional_param('categoryid', NULL, PARAM_INT);
$year       = optional_param('year', NULL, PARAM_TEXT);
$subCatId   = optional_param('subcategoryid', NULL, PARAM_TEXT);
$uom        = optional_param('uom', 'both', PARAM_TEXT);
$transType  = optional_param('transtype', 'Both', PARAM_TEXT);
$fromDate   = optional_param('fromdate', $defaultFromDate, PARAM_RAW);
$fromDateTime = NULL;
$toDate = optional_param('todate', NULL, PARAM_RAW);
$toDateTime = NULL;

if (!empty($fromDate)) {
    $fromDateTime = strtotime($fromDate);
    if (! $fromDateTime) {
        $page->messages->addErrorMsg("Invalid From Date");
    }
}
if (!empty($toDate)) {
    $toDateTime = strtotime($toDate." 23:59:59");
    if (! $toDateTime) {
        $page->messages->addErrorMsg("Invalid To Date");
    }
}

if ($categoryId) {
    setGlobalListingTypeId($categoryId);
}
$products = getProducts($categoryId, $boxTypeId, $year, $subCatId, $transType, $fromDateTime, $toDateTime, $uom);
$items = null;
if ($subCatId) {
    $items = getHistory($categoryId, $boxTypeId, $year, $subCatId, $transType, $fromDateTime, $toDateTime, $uom);
}

echo $page->header('Price Guide');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $boxTypeId, $categoryId, $year, $subCatId, $uom, $UTILITY, $products, $items, $transType, $fromDate, $toDate, $uom;

    echo "<h1>Price Guide</h1>\n";
    echo "<form id='sub' name='sub' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "  <table>\n";
    echo "    <tr>\n";
    echo "      <td>Category:\n";
    echo categoryDDM($categoryId);
    echo "      </td>\n";
    echo "      <td>Year:";
    echo yearDDM($categoryId, $year);
    echo "      </td>\n";
    echo "      <td>Box Type:\n";
    echo boxTypeDDM($categoryId, $year, $boxTypeId);
    echo "      </td>\n";
    echo "    </tr>\n";
    echo "  <tr>";
    echo "      <td>Subcategory:";
    echo subcategoryDDM($categoryId, $boxTypeId, $year, $subCatId);
    echo "      </td>\n";
    echo "      <td>From Date: <input type=text size=10 name='fromdate' id='fromdate' value='".$fromDate."' /></td><td>To Date: <input type=text size=10 name='todate' id='todate' value='".$toDate."' /></td>";
    echo "  </tr>\n";
    echo "  <tr><td colspan='3' align='center'><input type='submit' name='filter' id='filter' /></td></tr>\n";
    echo "  </table>\n";
    echo "</form>\n";
    echo "\n";

    if ($products && is_array($products) && (count($products) > 0)) {
        echo "<table>\n";
        echo "  <caption>All prices are box prices</caption>\n";
        echo "  <thead>\n";
        echo "    <tr>\n";
        echo "      <th>Product</th>\n";
        echo "      <th>Variation</th>\n";
        echo "      <th>UPC</th>\n";
        echo "      <th>Current High Buy</th>\n";
        echo "      <th>Historical High Buy</th>\n";
        echo "      <th>Current Low Sell</th>\n";
        echo "      <th>Historical Low Sell</th>\n";
        echo "    </tr>\n";
        echo "  </thead>\n";
        echo "  <tbody>\n";
        foreach ($products as $product) {
            $hasReal = ($product['hasreal'] > 0) ? "title='Has For Sale Listings'" : "";
            $inSecondary = false;
            $rowClass = "";
            $rowStyle = "";
            $secondaryClassPrefix= "secondary";
            if ($product['secondary']) {
                $secondaryOnly = ($subCatId && ($subCatId == $product['subcategoryid'])) ? true : false;
                $rowClass = " class='".$secondaryClassPrefix."sc' ";
                if (!$secondaryOnly) {
                    $rowStyle = " style='display:none;' ";
                }
                if (! $inSecondary) {
                    echo "<tr>";
                    echo "<td colspan='5'>";
                    if (! $secondaryOnly) {
                        echo "<a title='Show secondary subcategories' href='#' onClick='$(\".".$secondaryClassPrefix."sc\").show();$(\".".$secondaryClassPrefix."toggle\").hide(); return(false);' class='".$secondaryClassPrefix."toggle' ><i class='fa-solid fa-plus'></i></a>";
                        echo "<a title='Hide secondary subcategories' href='#' onClick='$(\".".$secondaryClassPrefix."sc\").hide();$(\".".$secondaryClassPrefix."toggle\").show(); return(false);' class='".$secondaryClassPrefix."sc' ".$rowStyle."><i class='fa-solid fa-minus'></i></a>";
                    }
                    echo " <strong>Secondary Subcategories</strong>";
                    echo "</td>";
                    echo "</tr>\n";
                    $inSecondary = true;
                }
            }
            echo "    <tr ".$rowClass." ".$rowStyle.">\n";
            $historyURL = "priceguide.php?categoryid=".$product['categoryid']
                ."&boxtypeid=".$product['boxtypeid']
                ."&year=".URLEncode($product['year'])
                ."&subcategoryid=".$product['subcategoryid']
                ."&fromdate=".$fromDate
                ."&todate=".$toDate;
            echo "<td data-label='Product'><a href='".$historyURL."' >".$product['subcategorydescription']." ~ ".$product['boxtypename']."</a></td>";
            echo "<td data-label='Variation' class='number'>".$product['variation']."</td>";
            echo "<td data-label='UPC' class='number'>".$product['upcs']."</td>";
            echo "<td data-label='Current High Buy' class='number'>".floatToMoney($product['highbuyprice'])."</td>";
            echo "<td data-label='Historical High Buy' class='number'>".floatToMoney($product['minhighbuy'])." - ".floatToMoney($product['maxhighbuy'])."</td>";
            echo "<td data-label='Current Low Sell' class='number'>".floatToMoney($product['lowsellprice'])."</td>";
            echo "<td data-label='Historical Low Sell' class='number'>".floatToMoney($product['minlowsell'])." - ".floatToMoney($product['maxlowsell'])."</td>";
            echo "</tr>\n";
        }
        echo "  </tbody>\n";
        echo "</table>\n";
        if (count($products) == 1) {
            $product = reset($products);
            if ($items) {
                echo "<h3 class='center'>History</h3>\n";
                echo "<table>\n";
                echo "  <thead>\n";
                echo "      <th>Date</th>\n";
                echo "      <th>High Buy Price</th>\n";
                echo "      <th>Low Sell Price</th>\n";
                echo "    </tr>\n";
                echo "  </thead>\n";
                echo "  <tbody>\n";
                foreach ($items as $item) {
                    echo "<tr>";
                    echo "<td data-label='Date' class='date'>".date('m/d/Y', $item['pgdate'])."</td>";
                    echo "<td data-label='High Buy Price' class='number'>".floatToMoney($item['highbuyprice'])." ".trendIndicator($item['buytrend'])."</td>";
                    echo "<td data-label='Low Sell Price' class='number'>".floatToMoney($item['lowsellprice'])." ".trendIndicator($item['selltrend'])."</td>";
                    echo "</tr>";
                }
                echo "  </tbody>\n";
                echo "</table>\n";
            }
        }
    }
}

function trendIndicator($trendDirection) {
    $indicator = "";

    switch ($trendDirection) {
        case 'U':
            $indicator =  "&nbsp;&nbsp;(<i class='fa-solid fa-arrow-up' style='color: #00ff00;'></i>)"   ;
            break;
        case 'D':
            $indicator =  "&nbsp;&nbsp;(<i class='fa-solid fa-arrow-down' style='color: #ff0000;'></i>)"   ;
            break;
        default:
//            $indicator =  "&nbsp;&nbsp;(<i class='fa-solid fa-dash fa-sm' style='color: #0000ff;'></i>)"   ;
            $indicator =  "&nbsp;&nbsp;(&nbsp;&nbsp;&nbsp;&nbsp;)"   ;
            break;
    }

    return $indicator;
}

function getBoxTypesJS() {
    global $page;


    $rs = $page->utility->getboxTypes(NULL);

    $output = "\n";
    $output .= "  var boxtypedata = [\n";
    foreach ($rs as $sc) {
         $output .= "[".$sc["categoryid"].", '".$sc["boxtypeid"]."', '".$sc["boxtypename"]."', ".$sc["categorytypeid"]."],\n";
    }
    $output .= "    [0,'',0,0,'']\n";
    $output .= "  ];\n";

    return $output;
}

function getUOMDDM($uom) {
    global $page;

    $output = "";

    $output .= "<select name='uom' id='uom'>\n";
    $output .= "<option value='both' ".$page->utility->isChecked($uom, "both", "selected").">All</option>\n";
    $output .= "<option value='box' ".$page->utility->isChecked($uom, "box", "selected").">Box</option>\n";
    $output .= "<option value='case' ".$page->utility->isChecked($uom, "case", "selected").">Case</option>\n";
    $output .= "</select>\n";

    return $output;
}


function getProducts($categoryId, $boxTypeId, $year, $subCatId, $transType, $fromDateTime, $toDateTime, $uom) {
    global $page, $listingTypeId;

    $returnData = null;

    if ((!empty($categoryId)) && (($listingTypeId == LISTING_TYPE_GAMING) || (!empty($year)))) {
        $yearWhere = (! empty($year)) ? "AND pg.year = '".$year."' " : "";
        $maxPriceDate = $page->db->get_field_query("SELECT max(pgdate) FROM price_guide");
        $boxTypeJoin = (!empty($boxTypeId)) ? "AND pg.boxtypeid      = ".$boxTypeId : "";
        $subcatJoin = (!empty($subCatId)) ? "AND pg.subcategoryid      = ".$subCatId : "";
        $fromDateWhere = ($fromDateTime) ? " AND pg.pgdate >= ".$fromDateTime." " : "";
        $toDateWhere = ($toDateTime) ? " AND pg.pgdate <= ".$toDateTime." " : "";
        $sql = "
            SELECT hist.categoryid, hist.categorydescription
                , hist.boxtypeid, hist.boxtypename
                , hist.subcategoryid, hist.subcategorydescription, hist.secondary
                , hist.year, hist.year4, hist.upcs, hist.variation
                , hist.numitems
                , hist.maxhighbuy
                , hist.minhighbuy
                , hist.hasreal
                , hist.maxlowsell
                , hist.minlowsell
                , lpg.highbuyprice
                , lpg.lowsellprice
            FROM (
                SELECT pg.categoryid, c.categorydescription
                    , pg.boxtypeid, bt.boxtypename
                    , pg.subcategoryid, sc.subcategorydescription, sc.secondary
                    , pg.year, pg.year4, pu.upcs, p.variation
                    , count(*) as numitems
                    , max(pg.highbuyprice) as maxhighbuy
                    , min(pg.highbuyprice) as minhighbuy
                    , sum(pg.sellcount) as hasreal
                    , max(pg.lowsellprice) as maxlowsell
                    , min(pg.lowsellprice) as minlowsell
                FROM price_guide    pg
                JOIN categories     c   ON  c.categoryid        = pg.categoryid
                                        AND c.active            = 1
                JOIN boxtypes       bt  ON  bt.boxtypeid        = pg.boxtypeid
                                        AND bt.active           = 1
                JOIN subcategories  sc  ON  sc.subcategoryid    = pg.subcategoryid
                                        AND sc.active           = 1
                                        AND sc.secondary        = 0
                LEFT JOIN products  p   ON  p.active            = 1
                                        AND p.categoryid        = pg.categoryid
                                        AND p.subcategoryid     = pg.subcategoryid
                                        AND p.boxtypeid         = pg.boxtypeid
                                        AND isnull(p.year, '1') = isnull(pg.year, '1')
                LEFT JOIN (
                    SELECT u.productid, array_to_string(array_agg(upc), '<br>') as upcs
                      FROM product_upc  u
                      JOIN products     p   ON  p.productid = u.productid
                                            AND p.active    = 1
                    GROUP BY u.productid
                          )         pu  ON  pu.productid        = p.productid

                WHERE pg.categoryid         = ".$categoryId."
                        ".$yearWhere."
                        ".$boxTypeJoin."
                        ".$subcatJoin."
                        ".$fromDateWhere."
                        ".$toDateWhere."
                GROUP BY pg.categoryid, c.categorydescription
                        , pg.boxtypeid, bt.boxtypename
                        , pg.subcategoryid, sc.subcategorydescription, sc.secondary
                        , pg.year, pg.year4, pu.upcs, p.variation
            ) hist
            LEFT JOIN price_guide   lpg ON  lpg.pgdate      = ".$maxPriceDate."
                                        AND lpg.categoryid  = hist.categoryid
                                        AND lpg.boxtypeid   = hist.boxtypeid
                                        AND lpg.subcategoryid= hist.subcategoryid
                                        AND lpg.year        = hist.year
                                        AND lpg.year4       = hist.year4
            ORDER BY hist.secondary, hist.subcategorydescription, hist.boxtypename COLLATE \"POSIX\"";
        //echo "Product SQL:<br />\n<pre>".$sql."</pre><br />\n";
        $returnData = $page->db->sql_query($sql);
    }

    return $returnData;
}

function getHistory($categoryId, $boxTypeId, $year, $subCatId, $transType, $fromDateTime, $toDateTime, $uom) {
    global $page, $listingTypeId;

    $returnData = null;

    if ((!empty($categoryId)) && (!empty($subCatId)) && (($listingTypeId == LISTING_TYPE_GAMING) || (!empty($year)))) {
        $yearWhere = (! empty($year)) ? "AND pg.year = '".$year."' " : "";
        $boxTypeJoin = (!empty($boxTypeId)) ? "AND pg.boxtypeid      = ".$boxTypeId : "";
        $subcatJoin = (!empty($subCatId)) ? "AND pg.subcategoryid      = ".$subCatId : "";
        $fromDateWhere = ($fromDateTime) ? " AND pg.pgdate >= ".$fromDateTime." " : "";
        $toDateWhere = ($toDateTime) ? " AND pg.pgdate <= ".$toDateTime." " : "";
        $sql = "
            SELECT pg.pgdate, pg.highbuyprice, pg.buytrend, pg.buycount, pg.lowsellprice, pg.selltrend , pg.sellcount
              FROM price_guide      pg
              JOIN categories       c   ON  c.categoryid    = pg.categoryid
                                        AND c.active        = 1
              JOIN boxtypes         bt  ON  bt.boxtypeid    = pg.boxtypeid
                                        AND bt.active       = 1
              JOIN subcategories    sc  ON  sc.subcategoryid= pg.subcategoryid
                                        AND sc.active       = 1
             WHERE pg.categoryid         = ".$categoryId."
                    ".$yearWhere."
                    ".$boxTypeJoin."
                    ".$subcatJoin."
                    ".$fromDateWhere."
                    ".$toDateWhere."
            ORDER BY pg.pgdate DESC";

        //echo "SQL:<br />\n<pre>".$sql."</pre><br />\n";
        $returnData = $page->db->sql_query($sql);
    }

    return $returnData;
}

function categoryDDM($categoryId = NULL) {
    global $page;

    $output = "";

    $sql = "SELECT c.categoryid, c.categorydescription
        FROM price_guide pg
        JOIN categories c ON c.categoryid=pg.categoryid AND c.active=1 AND c.categorytypeid IN (".LISTING_TYPE_SPORTS.",".LISTING_TYPE_GAMING.")
        GROUP BY c.categoryid, c.categorydescription
        ORDER BY c.categorydescription COLLATE \"POSIX\"";
    if ($categories = $page->db->sql_query($sql)) {
        $onChange = " onchange = \"$('#year').val('');$('#boxtypeid').val('');submit();\"";
        $output .= "        ".getSelectDDM($categories, "categoryid", "categoryid", "categorydescription", NULL, $categoryId, "Select", 0, NULL, NULL, $onChange);
    } else {
        $output .= "No matching categories";
    }

    return $output;
}


function boxTypeDDM($categoryId = null, $year=NULL, $boxTypeId = NULL) {
    global $page, $listingTypeId;

    $output = "";

    if ((!empty($categoryId)) && (($listingTypeId == LISTING_TYPE_GAMING) || (!empty($year)))) {
        $yearWhere = (! empty($year)) ? " AND pg.year = '".$year."' " : "";
        $sql = "SELECT bt.boxtypeid, bt.boxtypename
            FROM price_guide pg
            JOIN boxtypes bt ON bt.boxtypeid=pg.boxtypeid AND bt.active=1
            WHERE pg.categoryid=".$categoryId.$yearWhere."
            GROUP BY bt.boxtypeid, bt.boxtypename
            ORDER BY bt.boxtypename COLLATE \"POSIX\"";
        if ($boxtypes = $page->db->sql_query($sql)) {
            $onChange = " onchange = 'submit();'";
            $output .= "          ".getSelectDDM($boxtypes, "boxtypeid", "boxtypeid", "boxtypename", NULL, $boxTypeId, "Select", 0, NULL, NULL, $onChange);
        } else {
            $output .= "No matching box types";
            $output .= " <input type='hidden' name='boxtypeid' id='boxtypeid' value='' />";
        }
    } else {
        $output .= "<input type='hidden' name='boxtypeid' id='boxtypeid' value='' />";
    }

    return $output;
}

function yearDDM($categoryId, $year) {
    global $page, $listingTypeId;

    $output = "";

    if ($listingTypeId != LISTING_TYPE_GAMING) {
        if (!empty($categoryId)) {
            $sql = "SELECT pg.year, pg.year4
                FROM price_guide pg
                WHERE pg.categoryid=".$categoryId."
                GROUP BY year, year4
                ORDER BY year4 DESC, year DESC";

            if ($years = $page->db->sql_query($sql)) {
                $onChange = " onchange = \"$('#boxtypeid').val('');submit();\"";
                $output = "          ".getSelectDDM($years, "year", "year", "year", NULL, $year, "Select", 0, NULL, NULL, $onChange);
            } else {
                $output .= "No matching years.";
                $output .= " <input type='hidden' name='year' id='year' value='' />";
            }
        } else {
            $output .= "<input type='hidden' name='year' id='year' value='' />";
        }
    } else {
        $output .= "<input type='hidden' name='year' id='year' value='' />";
    }

    return $output;
}


function subcategoryDDM($categoryId, $boxTypeId, $year, $subCatId) {
    global $page, $listingTypeId;

    $output = "";

    if ((!empty($categoryId)) && (!empty($boxTypeId)) && (($listingTypeId == LISTING_TYPE_GAMING) || (!empty($year)))) {
        $yearWhere = (! empty($year)) ? " AND pg.year = '".$year."' " : "";
        $sql = "SELECT sc.subcategoryid
                , sc.secondary
                , (CASE WHEN sc.secondary=1 THEN '- ' ELSE '' END)||sc.subcategorydescription AS subcategorydescription
            FROM price_guide pg
            JOIN subcategories sc ON sc.subcategoryid=pg.subcategoryid AND sc.active=1 AND sc.secondary=0
            WHERE pg.categoryid=".$categoryId.$yearWhere."
              AND pg.boxtypeid=".$boxTypeId."
            GROUP BY sc.subcategoryid, sc.subcategorydescription
            ORDER BY sc.secondary, sc.subcategorydescription COLLATE \"POSIX\"";
        if ($subcategories = $page->db->sql_query($sql)) {
            $onChange = " onchange = 'submit();'";
            $output .= "          ".getSelectDDM($subcategories, "subcategoryid", "subcategoryid", "subcategorydescription", NULL, $subCatId, "Select", 0, NULL, NULL, $onChange)."\n";
        } else {
            $output .= "No matching subcategories";
            $output .= " <input type='hidden' name='subcategoryid' id='subcategoryid' value='' />";
        }
    } else {
        $output .= "<input type='hidden' name='subcategoryid' id='subcategoryid' value='' />";
    }

    return $output;
}


?>