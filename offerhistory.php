<?php
require_once('templateMarket.class.php');

DEFINE("LITERESTRICTION",   14);

$page = new templateMarket(LOGIN, SHOWMSG);

$hasOfferHistory_lite = ($page->user->hasUserRightId(USERRIGHT_OFFER_HISTORY_LITE));
$hasOfferHistory = ($hasOfferHistory_lite ||
                    $page->user->hasUserRightId(USERRIGHT_OFFER_HISTORY));

if ($hasOfferHistory) {
    $calendarJS = '
        $(function(){$("#fromdate").datepicker();});
        $(function(){$("#todate").datepicker();});
    ';
    $page->jsInit($calendarJS);

    $categoryId     = optional_param('categoryid', NULL, PARAM_INT);
    $year           = optional_param('year', NULL, PARAM_TEXT);
    $boxTypeId      = optional_param('boxtypeid', NULL, PARAM_INT);
    $subCatId       = optional_param('subcategoryid', NULL, PARAM_TEXT);
    $uom            = optional_param('uom', 'both', PARAM_TEXT);
    $transType      = optional_param('transtype', 'Both', PARAM_TEXT);
    $fromDate       = optional_param('fromdate', NULL, PARAM_RAW);
    $toDate         = optional_param('todate', NULL, PARAM_RAW);
    $export         = optional_param('export', 0, PARAM_INT);

    setGlobalListingTypeId($categoryId);
    if ($listingTypeId != LISTING_TYPE_SPORTS) {
        $year = NULL;
    }
    $fromDateTime   = NULL;
    $toDateTime     = NULL;

    if (!empty($fromDate)) {
        $fromDateTime = strtotime($fromDate);
        if (!$fromDateTime) {
            $page->messages->addErrorMsg("Invalid From Date");
        }
    }
    if (!empty($toDate)) {
        $toDateTime = strtotime($toDate." 23:59:59");
        if (!$toDateTime) {
            $page->messages->addErrorMsg("Invalid To Date");
        }
    }
    if ($hasOfferHistory_lite) {
        $from = strtotime("today -".LITERESTRICTION." days");
        $to = strtotime("today");
        if (empty($fromDate) || ($fromDateTime < $from)) {
            if (empty($fromDate)) {
                $fromDate = date("m/d/Y", $from);
                $fromDateTime = strtotime($fromDate);
            } else {
                $fromDate = date("m/d/Y", $from);
                $fromDateTime = strtotime($fromDate);
                $page->messages->addWarningMsg("Offer history lite users can only view history for the past ".LITERESTRICTION." days.");
            }
        }
        if (empty($toDate) || ($toDateTime < $from)) {
            if (empty($toDate)) {
                $toDate = date("m/d/Y", $to);
                $toDateTime = strtotime($toDate);
            } else {
                $toDate = date("m/d/Y", $to);
                $toDateTime = strtotime($toDate);
                $page->messages->addWarningMsg("Offer history lite users can only view history for the past ".LITERESTRICTION." days.");
            }
        }
    }

    if (!empty($export)) {
        $data = getProducts4Export($categoryId, $boxTypeId, $year, $subCatId, $transType, $fromDateTime, $toDateTime, $uom);
        if (empty($data)) {
            $page->messages->addErrorMsg("ERROR: Unable to export offer history information");
        } else {
            $x = reset($data);
            $yr     = preg_replace("/[^a-zA-Z0-9]+/", "", $x["year"]);
            $cat    = preg_replace("/[^a-zA-Z0-9]+/", "", $x["category"]);
            $subcat = (!empty($subCatId)) ? preg_replace("/[^a-zA-Z0-9]+/", "", $x["subcategory"]) : "";
            $bt     = (!empty($boxTypeId)) ? preg_replace("/[^a-zA-Z0-9]+/", "", $x["box_type"])  : "";
            $filename = "";
            $filename .= (!empty($yr)) ? $yr : "";
            $filename .= (!empty($subcat)) ? "_".$subcat : "";
            $filename .= (!empty($cat)) ? "_".$cat : "";
            $filename .= (!empty($bt)) ? "_".$bt : "";
            if ($hasOfferHistory && !$hasOfferHistory_lite) {
                $exportdata[] = $data;
                $historydata = getHistory($categoryId, $boxTypeId, $year, $subCatId, $transType, $fromDateTime, $toDateTime, $uom);
                foreach($historydata as &$d) {
                    $d['transactiondate'] = date('m/d/Y', $d['transactiondate']);
                    if (!$page->user->isStaff()) {
                        unset($d["buyer"]);
                        unset($d["seller"]);
                    }
                }
                $exportdata[] = $historydata;
                export($exportdata, $filename."_offerhistory.csv");
            } elseif ($export == 1) {
                export($data, $filename."_offerhistory.csv");
            } elseif ($export == 2) {
                $data = getHistory($categoryId, $boxTypeId, $year, $subCatId, $transType, $fromDateTime, $toDateTime, $uom);
                if (empty($data)) {
                    $page->messages->addErrorMsg("ERROR: Unable to export offer history information");
                } else {
                    foreach($data as &$d) {
                        $d['transactiondate'] = date('m/d/Y', $d['transactiondate']);
                        if (!$page->user->isStaff()) {
                            unset($d["buyer"]);
                            unset($d["seller"]);
                        }
                    }
                    export($data, $filename."_offerhistory.csv");
                }
            }
        }
    }

    $products = getProducts($categoryId, $boxTypeId, $year, $subCatId, $transType, $fromDateTime, $toDateTime, $uom);
    $items = null;
    if ($subCatId) {
        $items = getHistory($categoryId, $boxTypeId, $year, $subCatId, $transType, $fromDateTime, $toDateTime, $uom);
        if ($items) {
            foreach($items as &$d) {
                if (!$page->user->isStaff()) {
                    unset($d["buyer"]);
                    unset($d["seller"]);
                }
            }
        }
    }
}

echo $page->header('Offer History');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $hasOfferHistory, $boxTypeId, $categoryId, $year, $subCatId, $uom, $UTILITY, $products, $items, $transType, $fromDate, $toDate, $uom;

    echo "<h1>Offer History</h1>\n";
    if ($hasOfferHistory) {
        echo "<form id='sub' class='form-inline' name='sub' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
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
        echo "      <td>Subcategory:";
        echo subcategoryDDM($categoryId, $boxTypeId, $year, $subCatId);
        echo "      </td>\n";
        echo "    </tr>\n";
        echo "    <tr>\n";
        echo "      <td>Transaction: \n";
        echo "        <input type='radio' name='transtype' value='Wanted' ".$page->utility->isChecked($transType, "Wanted")."><label>Wanted</label></input>\n";
        echo "        <input type='radio' name='transtype' value='For Sale' ".$page->utility->isChecked($transType, "For Sale")."><label>For Sale</label></input>\n";
        echo "        <input type='radio' name='transtype' value='Both' ".$page->utility->isChecked($transType, "Both")."><label>Both</label></input>\n";
        echo "      </td>\n";
        echo "      <td>From Date: <input type=text size=10 name='fromdate' id='fromdate' value='".$fromDate."' /></td>\n";
        echo "      <td>To Date: <input type=text size=10 name='todate' id='todate' value='".$toDate."' /></td>";
        echo "      <td>UOM: ".getUOMDDM($uom)."</td>\n";
        echo "    </tr>\n";
        echo "    <tr>\n";
        echo "      <td colspan='4' align='center'><input type='submit' name='filter' id='filter' onclick='$(\"#export\").val(\"\");' /></td>\n";
        echo "    </tr>\n";
        echo "  </table>\n";
        echo "  <input type='hidden' name='export' id='export' value=''>\n";
        echo "</form>\n";
        echo "\n";

        if ($products && is_array($products) && (count($products) > 0)) {
            echo "<table>\n";
            echo "  <caption>\n";
            echo "    <span class='indicator' style='padding-right:15px;'><i class='fa fa-triangle-exclamation'></i>Factory Cost, High Price and Low Price are box prices</span>\n";
            if (empty($subCatId)) {
                echo "    <a href='Javascript: $(\"#export\").val(\"1\"); document.sub.submit();' class='indicator'><i class='fa fa-file-export'></i>Export</a>\n";
            }
            echo "  </caption>\n";
            echo "  <thead>\n";
            echo "    <tr>\n";
            echo "      <th>Product</th>\n";
            echo "      <th>Variation</th>\n";
            echo "      <th>UPC</th>\n";
            echo "      <th>Total Boxes</th>\n";
            echo "      <th>Total Amount</th>\n";
            echo "      <th>Average Amount</th>\n";
            echo "      <th>Factory Cost</th>\n";
            echo "      <th>High Price</th>\n";
            echo "      <th>Low Price</th>\n";
            echo "    </tr>\n";
            echo "  </thead>\n";
            echo "  <tbody>\n";
            foreach ($products as $product) {
                echo "    <tr>";
                $historyURL = "offerhistory.php?categoryid=".$product['categoryid']
                    ."&boxtypeid=".$product['boxtypeid']
                    ."&year=".URLEncode($product['year'])
                    ."&subcategoryid=".$product['subcategoryid']
                    ."&transtype=".$transType
                    ."&fromdate=".$fromDate
                    ."&todate=".$toDate
                    ."&uom=".$uom;
                $productURL = "listing.php?categoryid=".$product['categoryid']
                    ."&listingtypeid=".$product['categorytypeid']
                    ."&boxtypeid=".$product['boxtypeid']
                    ."&year=".URLEncode($product['year'])
                    ."&subcategoryid=".$product['subcategoryid'];
                $yearStr = ($product['year']) ? $product['year']." ~ " : "";
                echo "      <td data-label='Product'><a href='".$productURL."' target='_blank' >".$product['subcategoryname']." ~ ".$product['boxtypename']."</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href='".$historyURL."'><i class='fa-solid fa-magnifying-glass'></i></a></td>";
                echo "      <td data-label='Variation'>".$product['variation']."</td>";
                echo "      <td data-label='UPC' class='number'>".$product['upcs']."</td>";
                echo "      <td data-label='Total Boxes'class='number'>".$product['totalboxes']."</td>";
                echo "      <td data-label='Total Amount' class='number'>".floatToMoney($product['totalamount'])."</td>";
                echo "      <td data-label='Average Amount' class='number'>".floatToMoney($product['avgamount'])."</td>";
                echo "      <td data-label='Factory Cost' class='number'>".floatToMoney($product['factorycost'])."</td>";
                echo "      <td data-label='High Price' class='number'>".floatToMoney($product['highprice'])."</td>";
                echo "      <td data-label='Low Price' class='number'>".floatToMoney($product['lowprice'])."</td>";
                echo "    </tr>\n";
            }
            echo "  </tbody>\n";
            echo "</table>\n";
            if (count($products) == 1) {
                $product = reset($products);
                if ($items) {
                    echo "<table>\n";
                    echo "  <caption>\n";
                    echo "    <a href='Javascript: $(\"#export\").val(\"2\"); document.sub.submit();' class='indicator'><i class='fa fa-file-export'></i>Export</a>\n";
                    echo "  </caption>\n";
                    echo "  <thead>\n";
                    echo "    <tr class='addlisting'>\n";
                    echo "      <th colspan='9'>Transactions</th>\n";
                    echo "    </tr>\n";
                    echo "    <tr>\n";
                    if ($page->user->isStaff()) {
                        echo "      <th>Buyer</th>\n";
                        echo "      <th>Seller</th>\n";
                    }
                    echo "      <th>Box Price</th>\n";
                    echo "      <th>UOM</th>\n";
                    echo "      <th>Quantity</th>\n";
                    echo "      <th>Price</th>\n";
                    echo "      <th>Amount</th>\n";
                    echo "      <th>Date</th>\n";
                    echo "      <th>Type</th>\n";
                    echo "    </tr>\n";
                    echo "  </thead>\n";
                    echo "  <tbody>\n";
                    foreach ($items as $item) {
                        echo "  <tr>";
                        if ($page->user->isStaff()) {
                            echo "      <td data-label='Buyer' class='center'>".$item['buyer']."</td>\n";
                            echo "      <td data-label='Seller' class='center'>".$item['seller']."</td>\n";
                        }
                        echo "      <td data-label='Box Price' class='number'>".$item['boxprice']."</td>\n";
                        echo "      <td data-label='UOM' class='indicator'>".$item['uom']."</td>\n";
                        echo "      <td data-label='Quantity' class='number'>".$item['quantity']."</td>\n";
                        echo "      <td data-label='Price' class='number'>".floatToMoney($item['price'])."</td>\n";
                        echo "      <td data-label='Amount' class='number'>".floatToMoney($item['totalamount'])."</td>\n";
                        echo "      <td data-label='Date' class='date'>".date('m/d/Y', $item['transactiondate'])."</td>\n";
                        echo "      <td data-label='Type'>".$item['type']."</td>\n";
                        echo "  </tr>";
                    }
                    echo "  </tbody>\n";
                    echo "</table>\n";
                }
            }
        }
    } else {
        $contactURL = "/sendmessage.php";
        echo "Offer History is an add on service available for an additionbal fee.<br />\nContact the Help Desk <a href='".$contactURL."' target='_blank'>here</a> to request more information or access to offer history.<br />\n";
    }
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
    $output .= "  <option value='both' ".$page->utility->isChecked($uom, "both", "selected").">All</option>\n";
    $output .= "  <option value='box' ".$page->utility->isChecked($uom, "box", "selected").">Box</option>\n";
    $output .= "  <option value='case' ".$page->utility->isChecked($uom, "case", "selected").">Case</option>\n";
    $output .= "</select>\n";

    return $output;
}

function getProducts($categoryId, $boxTypeId, $year, $subCatId, $transType, $fromDateTime, $toDateTime, $uom) {
    global $page, $listingTypeId;

    $returnData = null;

    if ($categoryId && (($listingTypeId != LISTING_TYPE_SPORTS) || (!empty($year)))) {
        $boxTypeWhere = (!empty($boxTypeId)) ? "AND oh.boxtypeid      = ".$boxTypeId : "";
        $subcatWhere = (!empty($subCatId)) ? "AND oh.subcategoryid      = ".$subCatId : "";
        $typeWhere = ($transType == 'Both') ? "" : " AND oh.type='".$transType."' ";
        $fromDateWhere = ($fromDateTime) ? " AND oh.transactiondate >= ".$fromDateTime." " : "";
        $toDateWhere = ($toDateTime) ? " AND oh.transactiondate <= ".$toDateTime." " : "";
        $uomWhere = (($uom == 'case') || ($uom == 'box')) ? " AND oh.uom = '".$uom."' " : "";
        $yearWhere = ($year) ? "AND oh.year = '".$year."' " : "";
        $sql = "
            SELECT oh.categoryid, c.categorydescription, c.categorytypeid
                   , oh.subcategoryid, sc.subcategoryname
                   , oh.boxtypeid, bt.boxtypename
                   , oh.year, oh.year4, pu.upcs, p.variation
                   , count(*) as numitems
                   , sum(oh.boxquantity) as totalboxes
                   , sum(oh.quantity*oh.price)::numeric(12,2) as totalamount
                   , CASE WHEN sum(oh.boxquantity) > 0 THEN ((sum(oh.quantity*oh.price))/(sum(oh.boxquantity)))::numeric(12,2) ELSE 0.00 END AS avgamount
                   , l.boxprice as factorycost
                   , max(oh.boxprice) as highprice
                   , min(oh.boxprice) as lowprice
            FROM offer_history  oh
            JOIN categories     c   ON  c.categoryid        = oh.categoryid
                                    AND c.active            = 1
            JOIN boxtypes       bt  ON  bt.boxtypeid        = oh.boxtypeid
                                    AND bt.active           = 1
            JOIN subcategories  sc  ON  sc.subcategoryid    = oh.subcategoryid
                                    AND sc.active           = 1
            LEFT JOIN products  p   ON  p.active            = 1
                                    AND p.categoryid        = oh.categoryid
                                    AND p.subcategoryid     = oh.subcategoryid
                                    AND p.boxtypeid         = oh.boxtypeid
                                    AND isnull(p.year, '1') = isnull(oh.year, '1')
            LEFT JOIN (
                    SELECT u.productid, array_to_string(array_agg(upc), '<br>') as upcs
                      FROM product_upc  u
                      JOIN products     p   ON  p.productid = u.productid
                                            AND p.active    = 1
                    GROUP BY u.productid
                      )         pu  ON  pu.productid        = p.productid
            LEFT JOIN listings  l   ON  l.categoryid        = ".$categoryId."
                                    AND  l.subcategoryid    = oh.subcategoryid
                                    AND  l.boxtypeid        = oh.boxtypeid
                                    AND  l.year             = oh.year
                                    AND  l.userid           = ".FACTORYCOSTID."
                                    AND  l.uom              = 'box'
                                    AND  l.status           = 'OPEN'
            WHERE oh.categoryid = ".$categoryId."
               AND oh.quantity  > 0
                ".$boxTypeWhere."
                ".$yearWhere."
                ".$subcatWhere."
                ".$typeWhere."
                ".$fromDateWhere."
                ".$toDateWhere."
                ".$uomWhere."
            GROUP BY oh.categoryid, c.categorydescription, c.categorytypeid
                , oh.boxtypeid, bt.boxtypename
                , oh.subcategoryid, sc.subcategoryname
                , oh.year, oh.year4, pu.upcs, p.variation
                , l.boxprice
            ORDER BY c.categorydescription, sc.subcategoryname, bt.boxtypename, oh.year
        ";
        //echo "Product SQL:<br />\n<pre>".$sql."</pre><br />\n";
        $returnData = $page->db->sql_query($sql);
    }

    return $returnData;
}

// NOT USED
function getProductsSplit($categoryId, $boxTypeId, $year, $subCatId, $transType, $fromDateTime, $toDateTime) {
    global $page;

    $returnData = null;

    if (!empty($categoryId) && (!empty($year))) {
        $boxTypeJoin = (!empty($boxTypeId)) ? "AND oh.boxtypeid      = ".$boxTypeId : "";
        $subcatJoin = (!empty($subCatId)) ? "AND oh.subcategoryid      = ".$subCatId : "";
        $boxTypeJoin = (!empty($boxTypeId)) ? "AND oh.boxtypeid      = ".$boxTypeId : "";
        $subcatJoin = (!empty($subCatId)) ? "AND oh.subcategoryid      = ".$subCatId : "";
        $typeWhere = ($transType == 'Both') ? "" : " AND oh.type='".$transType."' ";
        $fromDateWhere = ($fromDateTime) ? " AND oh.transactiondate >= ".$fromDateTime." " : "";
        $toDateWhere = ($toDateTime) ? " AND oh.transactiondate <= ".$toDateTime." " : "";
        $sql = "
            SELECT pt.categoryid, pt.categorydescription
                , pt.subcategoryid, pt.subcategorydescription
                , pt.boxtypeid, pt.boxtypename
                , pt.year, pt.year4
                , count(*) as numitems
                , pt.factorycost
                , max(pt.buyprice) as highbuy
                , min(pt.buyprice) as lowbuy
                , max(pt.sellprice) as highsell
                , min(pt.sellprice) as lowsell
            FROM (
                SELECT c.categoryid, c.categorydescription
                    , sc.subcategoryid, sc.subcategoryname
                    , bt.boxtypeid, bt.boxtypename
                    , oh.year, oh.year4
                    , l.boxprice as factorycost
                    , CASE WHEN oh.type='Wanted' THEN oh.boxprice ELSE NULL END AS buyprice
                    , CASE WHEN oh.type='For Sale' THEN oh.boxprice ELSE NULL END AS sellprice
                FROM offer_history oh
                JOIN categories c ON c.categoryid=oh.categoryid AND c.active=1
                JOIN boxtypes bt ON bt.boxtypeid=oh.boxtypeid AND bt.active=1
                JOIN subcategories sc ON sc.subcategoryid=oh.subcategoryid AND sc.active=1
                LEFT JOIN listings l ON l.categoryid=".$categoryId."
                   AND l.subcategoryid=oh.subcategoryid
                   AND l.boxtypeid=oh.boxtypeid
                   AND l.year=oh.year
                   AND l.userid=".FACTORYCOSTID."
                   AND l.uom='box'
                   AND l.status='OPEN'
                WHERE oh.categoryid         = ".$categoryId."
                        AND isnull(oh.year, '1')  = isnull('".$year."', '1')
                        ".$boxTypeJoin."
                        ".$subcatJoin."
            ) pt
            GROUP BY pt.categoryid, pt.categorydescription
                , pt.boxtypeid, pt.boxtypename
                , pt.subcategoryid, pt.subcategorydescription
                , pt.year, pt.year4
                , pt.factorycost
            ORDER BY c.categorydescription, sc.subcategoryname, bt.boxtypename, oh.year
        ";
//      echo "Product SQL:<br />\n<pre>".$sql."</pre><br />\n";
        $returnData = $page->db->sql_query($sql);
    }

    return $returnData;
}

function getHistory($categoryId, $boxTypeId, $year, $subCatId, $transType, $fromDateTime, $toDateTime, $uom) {
    global $page, $listingTypeId;

    $returnData = null;
    if ((!empty($categoryId)) && (($listingTypeId != LISTING_TYPE_SPORTS) || (!empty($year)))) {
        $btid           = (!empty($boxTypeId)) ? $boxTypeId : "bt.boxtypeid";
        $scid           = (!empty($subCatId)) ? $subCatId : "sc.subcategoryid";
        $yearValue      = ($year == NULL) ? 1 : $year;
        $uomWhere       = (($uom == 'case') || ($uom == 'box')) ? " AND oh.uom = '".$uom."' " : "";
        $typeWhere      = ($transType == 'Both') ? "" : "AND oh.type='".$transType."' ";
        $fromDateWhere  = ($fromDateTime) ? " AND oh.transactiondate >= ".$fromDateTime." " : "";
        $toDateWhere    = ($toDateTime) ? " AND oh.transactiondate <= ".$toDateTime." " : "";
        $sql = "
            SELECT c.categorydescription, sc.subcategoryname, bt.boxtypename, oh.year,
                   oh.type, oh.boxprice, oh.uom, oh.price, oh.quantity, (oh.price * oh.quantity) as totalamount, oh.transactiondate,
                   CASE WHEN o.transactiontype = 'For Sale' THEN ut.username
                        WHEN o.transactiontype = 'Wanted' THEN uf.username
                        ELSE NULL END       as buyer,
                   CASE WHEN o.transactiontype = 'For Sale' THEN uf.username
                        WHEN o.transactiontype = 'Wanted' THEN ut.username
                        ELSE NULL END       as seller
              FROM offer_history    oh
              JOIN categories       c   ON  c.categoryid        = oh.categoryid
                                        AND c.active            = 1
              JOIN boxtypes         bt  ON  bt.boxtypeid        = oh.boxtypeid
                                        AND bt.active           = 1
              JOIN subcategories    sc  ON  sc.subcategoryid    = oh.subcategoryid
                                        AND sc.active           = 1
              LEFT JOIN offers      o   ON  o.offerid           = oh.offerid
              LEFT JOIN users       uf  ON  uf.userid           = o.offerfrom
              LEFT JOIN users       ut  ON  ut.userid           = o.offerto
             WHERE oh.categoryid        = ".$categoryId."
               AND oh.quantity          > 0
               AND oh.boxtypeid         = ".$btid."
               AND isnull(oh.year, '1') = '".$yearValue."'
               AND oh.subcategoryid     = ".$scid."
               ".$typeWhere."
               ".$uomWhere."
               ".$fromDateWhere."
               ".$toDateWhere."
            ORDER BY c.categorydescription, sc.subcategoryname, bt.boxtypename, oh.year, oh.transactiondate DESC";

//          echo "History SQL:<br />\n<pre>".$sql."</pre><br />\n";
        $returnData = $page->db->sql_query($sql);
    }

    return $returnData;
}

function categoryDDM($categoryId = NULL) {
    global $page;

    $output = "";

    $sql = "
        SELECT c.categoryid, c.categorydescription
          FROM offer_history    oh
          JOIN categories       c   ON  c.categoryid    = oh.categoryid
                                    AND c.active        = 1
                                    AND c.categorytypeid IN (".LISTING_TYPE_SPORTS.",".LISTING_TYPE_GAMING.")
         WHERE oh.quantity > 0
        GROUP BY c.categoryid, c.categorydescription
        ORDER BY c.categorytypeid, c.categorydescription COLLATE \"POSIX\"";
    if ($categories = $page->db->sql_query($sql)) {
        $onChange = " onchange = \"$('#year').val('');$('#boxtypeid').val('');$('#export').val('');submit();\"";
        $output .= "        ".getSelectDDM($categories, "categoryid", "categoryid", "categorydescription", NULL, $categoryId, "Select", 0, NULL, NULL, $onChange);
    } else {
        $output .= "No matching categories";
    }

    return $output;
}

function boxTypeDDM($categoryId = null, $year=NULL, $boxTypeId = NULL) {
    global $page, $listingTypeId;

    $output = "";

    if ((!empty($categoryId)) && (($listingTypeId != LISTING_TYPE_SPORTS) || (!empty($year)))) {
        $yearAnd = ($listingTypeId == LISTING_TYPE_SPORTS) ? " AND oh.year='".$year."' " : "";
        $sql = "
            SELECT bt.boxtypeid, bt.boxtypename
              FROM offer_history    oh
              JOIN boxtypes         bt  ON  bt.boxtypeid    = oh.boxtypeid
                                        AND bt.active       = 1
             WHERE oh.categoryid=".$categoryId.$yearAnd."
               AND oh.quantity > 0
            GROUP BY bt.boxtypeid, bt.boxtypename
            ORDER BY bt.boxtypename COLLATE \"POSIX\"";
        if ($boxtypes = $page->db->sql_query($sql)) {
            $onChange = " onchange = '$('#export').val('');submit();'";
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

    if (($listingTypeId == LISTING_TYPE_SPORTS) && (!empty($categoryId))) {
        $sql = "
            SELECT oh.year, oh.year4
              FROM offer_history oh
            WHERE oh.categoryid=".$categoryId."
              AND oh.quantity > 0
            GROUP BY year, year4
            ORDER BY year4 DESC, year DESC";

        if ($years = $page->db->sql_query($sql)) {
            $onChange = " onchange = \"$('#boxtypeid').val('');$('#export').val('');submit();\"";
            $output = "          ".getSelectDDM($years, "year", "year", "year", NULL, $year, "Select", 0, NULL, NULL, $onChange);
        } else {
            $output .= "No matching years.";
            $output .= " <input type='hidden' name='year' id='year' value='' />";
        }
    } else {
        $output .= "<input type='hidden' name='year' id='year' value='' />";
    }

    return $output;
}

function subcategoryDDM($categoryId, $boxTypeId, $year, $subCatId) {
    global $page, $listingTypeId;

    $output = "";

    if ((!empty($categoryId)) && (!empty($boxTypeId)) && (($listingTypeId != LISTING_TYPE_SPORTS) || (!empty($year)))) {
        $yearAnd = ($listingTypeId == LISTING_TYPE_SPORTS) ? " AND oh.year='".$year."' " : "";
        $sql = "
            SELECT sc.subcategoryid, sc.subcategoryname
              FROM offer_history    oh
              JOIN subcategories    sc  ON  sc.subcategoryid    = oh.subcategoryid
                                        AND sc.active           = 1
             WHERE oh.categoryid = ".$categoryId.$yearAnd."
               AND oh.quantity > 0
               AND oh.boxtypeid = ".$boxTypeId."
            GROUP BY sc.subcategoryid, sc.subcategoryname
            ORDER BY sc.subcategoryname COLLATE \"POSIX\"";
        if ($subcategories = $page->db->sql_query($sql)) {
            $onChange = " onchange = '$('#export').val('');submit();'";
            $output .= "          ".getSelectDDM($subcategories, "subcategoryid", "subcategoryid", "subcategoryname", NULL, $subCatId, "Select", 0, NULL, NULL, $onChange)."\n";
        } else {
            $output .= "No matching subcategories";
            $output .= " <input type='hidden' name='subcategoryid' id='subcategoryid' value='' />";
        }
    } else {
        $output .= "<input type='hidden' name='subcategoryid' id='subcategoryid' value='' />";
    }

    return $output;
}

function getProducts4Export($categoryId, $boxTypeId, $year, $subCatId, $transType, $fromDateTime, $toDateTime, $uom) {
    global $page, $listingTypeId;

    $returnData = null;

    if ($categoryId && (($listingTypeId != LISTING_TYPE_SPORTS) || (!empty($year)))) {
        $boxTypeWhere = (!empty($boxTypeId)) ? "AND oh.boxtypeid      = ".$boxTypeId : "";
        $subcatWhere = (!empty($subCatId)) ? "AND oh.subcategoryid      = ".$subCatId : "";
        $typeWhere = ($transType == 'Both') ? "" : " AND oh.type='".$transType."' ";
        $fromDateWhere = ($fromDateTime) ? " AND oh.transactiondate >= ".$fromDateTime." " : "";
        $toDateWhere = ($toDateTime) ? " AND oh.transactiondate <= ".$toDateTime." " : "";
        $uomWhere = (($uom == 'case') || ($uom == 'box')) ? " AND oh.uom = '".$uom."' " : "";
        $yearWhere = ($year) ? "AND oh.year = '".$year."' " : "";
        $sql = "
            SELECT c.categorydescription                    as category,
                   bt.boxtypename                           as box_type,
                   sc.subcategoryname                       as subcategory,
                   oh.year,
                   pu.upcs,
                   p.variation,
                   count(*)                                 as num_items,
                   sum(oh.boxquantity)                      as total_boxes,
                   sum(oh.quantity*oh.price)::numeric(12,2) as total_amount,
                   CASE WHEN sum(oh.boxquantity) > 0 THEN ((sum(oh.quantity*oh.price))/(sum(oh.boxquantity)))::numeric(12,2)
                        ELSE 0.00 END                       as avg_amount,
                   l.boxprice                               as factorycost,
                   max(oh.boxprice)                         as highprice,
                   min(oh.boxprice)                         as lowprice
              FROM offer_history    oh
              JOIN categories       c   ON  c.categoryid        = oh.categoryid
                                        AND c.active            = 1
              JOIN boxtypes         bt  ON  bt.boxtypeid        = oh.boxtypeid
                                        AND bt.active           = 1
              JOIN subcategories    sc  ON  sc.subcategoryid    = oh.subcategoryid
                                        AND sc.active           = 1
              LEFT JOIN products    p   ON  p.active            = 1
                                        AND p.categoryid        = oh.categoryid
                                        AND p.subcategoryid     = oh.subcategoryid
                                        AND p.boxtypeid         = oh.boxtypeid
                                        AND isnull(p.year, '1') = isnull(oh.year, '1')
              LEFT JOIN (
                    SELECT u.productid, array_to_string(array_agg(upc), '\n') as upcs
                      FROM product_upc  u
                      JOIN products     p   ON  p.productid = u.productid
                                            AND p.active    = 1
                    GROUP BY u.productid
                        )           pu  ON  pu.productid        = p.productid
              LEFT JOIN listings    l   ON  l.categoryid        = ".$categoryId."
                                        AND l.subcategoryid     = oh.subcategoryid
                                        AND l.boxtypeid         = oh.boxtypeid
                                        AND l.year              = oh.year
                                        AND l.userid            = ".FACTORYCOSTID."
                                        AND l.uom               = 'box'
                                        AND l.status            = 'OPEN'
             WHERE oh.categoryid         = ".$categoryId."
               AND oh.quantity  > 0
                ".$boxTypeWhere."
                ".$yearWhere."
                ".$subcatWhere."
                ".$typeWhere."
                ".$fromDateWhere."
                ".$toDateWhere."
                ".$uomWhere."
            GROUP BY oh.categoryid, c.categorydescription, c.categorytypeid,
                     oh.boxtypeid, bt.boxtypename,
                     oh.subcategoryid, sc.subcategoryname,
                     oh.year, oh.year4, pu.upcs, p.variation, l.boxprice
            ORDER BY c.categorydescription, sc.subcategoryname, bt.boxtypename, oh.year";
        //echo "Product SQL:<br />\n<pre>".$sql."</pre><br />\n";
        $returnData = $page->db->sql_query($sql);
    }

    return $returnData;
}

function export($data, $fname) {
    global $page;

    if (!empty($data)) {
        $filename = date('Ymd_His')."_".$fname;
        $page->utility->export($data, $filename);
    }

    return $filename;
}

?>