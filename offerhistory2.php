<?php
require_once('templateMarket.class.php');

DEFINE("LITERESTRICTION",   14);

$page = new templateMarket(LOGIN, SHOWMSG);

$hasOfferHistory = ($page->user->hasUserRightId(USERRIGHT_OFFER_HISTORY));

if ($hasOfferHistory) {
    $js = '
        $(function(){$("#fromdate").datepicker();});
        $(function(){$("#todate").datepicker();});
    ';
    $page->jsInit($js);

    $catids         = optional_param('selectedcats', NULL, PARAM_TEXT);
    $categoryids    = str_replace("[", "", str_replace("]", "", str_replace("][", ", ", $catids)));
    $btids          = optional_param('selectedbts', NULL, PARAM_TEXT);
    $boxtypeids     = str_replace("[", "", str_replace("]", "", str_replace("][", ", ", $btids)));
    $yrs            = optional_param('years', NULL, PARAM_TEXT);
    $years = null;
    $yearstr = "";
    if (!empty($yrs)) {
        $x = explode("\n", $yrs);
        if (count($x)) {
            $years = "";
            foreach($x as $y) {
                $y = trim($y);
                if (!empty($y)) {
                    $years .= (empty($years)) ? $y : ", ".$y;
                }
            }
            $yearstr = str_replace(", ", "\n", $years);
        } else {
            $years = null;
        }
    }
    $subcats        = optional_param('subcats', NULL, PARAM_TEXT);


    $categoryId     = optional_param('categoryid', NULL, PARAM_INT);
    $year           = optional_param('year', NULL, PARAM_TEXT);
    $boxTypeId      = optional_param('boxtypeid', NULL, PARAM_INT);
    $subCatId       = optional_param('subcategoryid', NULL, PARAM_TEXT);
    $uom            = optional_param('uom', 'both', PARAM_TEXT);
    $transType      = optional_param('transtype', 'Both', PARAM_TEXT);
    $fromDate       = optional_param('fromdate', NULL, PARAM_RAW);
    $toDate         = optional_param('todate', NULL, PARAM_RAW);
    $export         = optional_param('export', 0, PARAM_INT);

    $categoryids    = (!empty($categoryId)) ? $categoryId : $categoryids;
    $boxtypeids     = (!empty($boxTypeId)) ? $boxTypeId : $boxtypeids;
    $years          = (!empty($year)) ? $year : $years;
    $yearstr        = (!empty($year)) ? $year : $yearstr;
    $subcats        = (!empty($subCatId)) ? getSubCatName($subCatId) : $subcats;
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

    if (!empty($export) && !empty($categoryids)) {
        $data = getProducts4Export($categoryids, $boxtypeids, $years, $subcats, $transType, $fromDateTime, $toDateTime, $uom);
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

            $exportdata[] = $data;
            $subCatId = getSubCatId($subcats);
            $historydata = getHistory($categoryids, $boxtypeids, $years, $subcats, $transType, $fromDateTime, $toDateTime, $uom, $subCatId);
            foreach($historydata as &$d) {
                $d['transactiondate'] = date('m/d/Y', $d['transactiondate']);
                if (!$page->user->isStaff()) {
                    unset($d["buyer"]);
                    unset($d["seller"]);
                }
            }
            $exportdata[] = $historydata;
            export($exportdata, $filename."_offerhistory.csv");
        }
    }

    if (!empty($categoryids)) {
        $products = getProducts($categoryids, $boxtypeids, $years, $subcats, $transType, $fromDateTime, $toDateTime, $uom, $subCatId);
        $items = null;
        if ($subCatId) {
            $items = getHistory($categoryId, $boxTypeId, $year, $subcats, $transType, $fromDateTime, $toDateTime, $uom, $subCatId);
            if ($items) {
                foreach($items as &$d) {
                    if (!$page->user->isStaff()) {
                        unset($d["buyer"]);
                        unset($d["seller"]);
                    }
                }
            }
        }
    } else {
        $page->messages->addErrorMsg("Must Select at least 1 category.");
    }
} else {
    $page->messages->addErrorMsg("You do not have permission to view this page.");
}

$page->requireJS("/scripts/jquery.multi-select.js");
$page->requireStyle("/styles/multi-select.css");

$instructions = "
<span class='li'><b>YOU MUST SELECT AT LEAST 1 CATEGORY.</b></span>
<span class='li'>Click on a category to select it; click on a selected category to unselected it.</span>
<span class='li'>Enter 1 product year per line. Product years like 23/4 should be entered as 2023.</span>
<span class='li'>Enter 1 subcategory per line. Multiple words on a line will be interpreted as 1 subcategory, like Bowman Chrome. The word University will match subcategories like Bowman Chrome University, Bowman Best University, Bowman Chrome University Sapphire Edition and Bowman University - Alabama, etc.</span>
<span class='li'>Click on a box type to select it; click on a selected box type to unselected in.</span>
";
$page->messages->AddInfoMsg($instructions);

echo $page->header('Offer History 2');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $hasOfferHistory, $UTILITY;
    global $boxTypeId, $categoryId, $year, $subCatId, $uom, $products, $items, $transType, $fromDate, $toDate, $uom;
    global $categoryids, $boxtypeids, $years, $yearstr, $subcats;

    echo "<h1>Offer History 2</h1>\n";
    if ($hasOfferHistory) {
        echo "<form id='sub' class='form-inline' name='sub' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
        echo "  <table>\n";
        echo "    <tr>\n";
        echo "      <td>\n";
        $cats = getCategories($categoryids);
        echo getMultiSelect($cats, "categoryid", "categoryid", "categorydescription", "Categories", "selectedcats");
        echo "      </td>\n";
        echo "      <td><div class='ms_header'>Years</div>\n";
        echo "        <textarea name='years' rows='8' style='width:100%;'>".$yearstr."</textarea>\n";
        echo "      </td>\n";
        echo "      <td><div class='ms_header'>Subcategories</div>\n";
        echo "        <textarea name='subcats' rows='8' style='width:100%;'>".$subcats."</textarea>\n";
        echo "      </td>\n";
        echo "      <td colspan='2'>\n";
        $bts = getBoxtypes($boxtypeids);
        echo getMultiSelect($bts, "boxtypeid", "boxtypeid", "boxtypename", "Box Types", "selectedbts");
        echo "      </td>\n";
        echo "    </tr>\n";
        echo "    <tr>\n";
        echo "      <td colspan='2'>Transaction: \n";
        echo "        <input type='radio' name='transtype' value='Wanted' ".$page->utility->isChecked($transType, "Wanted")."><label>Wanted</label></input>\n";
        echo "        <input type='radio' name='transtype' value='For Sale' ".$page->utility->isChecked($transType, "For Sale")."><label>For Sale</label></input>\n";
        echo "        <input type='radio' name='transtype' value='Both' ".$page->utility->isChecked($transType, "Both")."><label>Both</label></input>\n";
        echo "      </td>\n";
        echo "      <td>From Date: <input type=text size=10 name='fromdate' id='fromdate' value='".$fromDate."' /></td>\n";
        echo "      <td>To Date: <input type=text size=10 name='todate' id='todate' value='".$toDate."' /></td>";
        echo "      <td>UOM: ".getUOMDDM($uom)."</td>\n";
        echo "    </tr>\n";
        echo "    <tr>\n";
        echo "      <td colspan='5' align='center'><input type='submit' name='filter' id='filter' onclick='$(\"#export\").val(\"\");' /></td>\n";
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
                $historyURL = "offerhistory2.php?categoryid=".$product['categoryid']
                    ."&boxtypeid=".$product['boxtypeid']
                    ."&year=".URLEncode($product['year4'])
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
                echo "      <td data-label='Product'><a href='".$productURL."' target='_blank' >".$yearStr.$product['subcategoryname']." ~ ".$product['boxtypename']." ".$product['categorydescription']."</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href='".$historyURL."'><i class='fa-solid fa-magnifying-glass'></i></a></td>";
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

function getProducts($categoryids, $boxtypeids, $years, $subcatsNL, $transType, $fromDateTime, $toDateTime, $uom, $subCatId) {
    global $page;

    $returnData = null;

    if (!empty($categoryids)) {
        $boxTypeWhere = (!empty($boxtypeids)) ? "AND oh.boxtypeid IN (".$boxtypeids.")" : "";
        $typeWhere = ($transType == 'Both') ? "" : "AND oh.type='".$transType."' ";
        $fromDateWhere = ($fromDateTime) ? "AND oh.transactiondate >= ".$fromDateTime." " : "";
        $toDateWhere = ($toDateTime) ? "AND oh.transactiondate <= ".$toDateTime." " : "";
        $uomWhere = (($uom == 'case') || ($uom == 'box')) ? "AND oh.uom = '".$uom."' " : "";
        $yearWhere = ($years) ? "AND oh.year4 IN (".$years.")" : "";

        if (!empty($subCatId)) {
            $subcatWhere = "AND oh.subcategoryid     = ".$subCatId;
        } else {
            $subcats = null;
            if (!empty($subcatsNL)) {
                $x = explode("\n", $subcatsNL);
                if (count($x)) {
                    $subcats = "";
                    $alphanumericonly = "/[^a-zA-Z0-9]+/";
                    $scstart = " alphanumericonly(lower(sc.subcategoryname)) LIKE ('%";
                    $scend   = "%')\n";
                    foreach($x as $sc) {
                        $sc = trim($sc);
                        if (!empty($sc)) {
                            $subcats .= (empty($subcats)) ? $scstart.strtolower(preg_replace($alphanumericonly, "", $sc)).$scend : " OR ".$scstart.strtolower(preg_replace($alphanumericonly, "", $sc)).$scend;
                        }
                    }
                    $subcats = "AND (".$subcats.")";
                }
            }
            $subcatWhere = $subcats;
        }

        $sql = "
            SELECT oh.categoryid, c.categorydescription, c.categorytypeid,
                   oh.subcategoryid, sc.subcategoryname,
                   oh.boxtypeid, bt.boxtypename,
                   oh.year, oh.year4, pu.upcs, p.variation,
                   count(*) as numitems,
                   sum(oh.boxquantity) as totalboxes,
                   sum(oh.quantity*oh.price)::numeric(12,2) as totalamount,
                   CASE WHEN sum(oh.boxquantity) > 0 THEN ((sum(oh.quantity*oh.price))/(sum(oh.boxquantity)))::numeric(12,2) ELSE 0.00 END AS avgamount,
                   l.boxprice as factorycost,
                   max(oh.boxprice) as highprice,
                   min(oh.boxprice) as lowprice
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

            LEFT JOIN listings  l   ON  l.categoryid        = c.categoryid
                                    AND l.subcategoryid     = oh.subcategoryid
                                    AND l.boxtypeid         = oh.boxtypeid
                                    AND l.year              = oh.year
                                    AND l.userid            = ".FACTORYCOSTID."
                                    AND l.uom               = 'box'
                                    AND l.status            = 'OPEN'
            WHERE oh.categoryid IN (".$categoryids.")
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
                     oh.year, oh.year4, pu.upcs, p.variation,
                     l.boxprice
            ORDER BY c.categorydescription, sc.subcategoryname, bt.boxtypename, oh.year
        ";

//      echo "<pre>".$sql."</pre><br />\n";
        $returnData = $page->db->sql_query($sql);
    }

    return $returnData;
}

function getHistory($categoryids, $boxtypeids, $years, $subcatsNL, $transType, $fromDateTime, $toDateTime, $uom) {
    global $page, $listingTypeId;

    $rs = null;
    if (!empty($categoryids)) {
        $boxTypeWhere = (!empty($boxtypeids)) ? "AND oh.boxtypeid IN (".$boxtypeids.")" : "";
        $uomWhere       = (($uom == 'case') || ($uom == 'box')) ? " AND oh.uom = '".$uom."' " : "";
        $typeWhere      = ($transType == 'Both') ? "" : "AND oh.type='".$transType."' ";
        $fromDateWhere  = ($fromDateTime) ? " AND oh.transactiondate >= ".$fromDateTime." " : "";
        $toDateWhere    = ($toDateTime) ? " AND oh.transactiondate <= ".$toDateTime." " : "";
        $yearWhere = ($years) ? "AND oh.year4 IN (".$years.")" : "";

        if (!empty($subCatId)) {
            $subcatWhere = "AND oh.subcategoryid     = ".$subCatId;
        } else {
            $subcats = null;
            if (!empty($subcatsNL)) {
                $x = explode("\n", $subcatsNL);
                if (count($x)) {
                    $subcats = "";
                    $alphanumericonly = "/[^a-zA-Z0-9]+/";
                    $scstart = " alphanumericonly(lower(sc.subcategoryname)) LIKE ('%";
                    $scend   = "%')\n";
                    foreach($x as $sc) {
                        $sc = trim($sc);
                        if (!empty($sc)) {
                            $subcats .= (empty($subcats)) ? $scstart.strtolower(preg_replace($alphanumericonly, "", $sc)).$scend : " OR ".$scstart.strtolower(preg_replace($alphanumericonly, "", $sc)).$scend;
                        }
                    }
                    $subcats = "AND (".$subcats.")";
                }
            }
            $subcatWhere = $subcats;
        }
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
             WHERE oh.categoryid IN (".$categoryids.")
               AND oh.quantity  > 0
               ".$boxTypeWhere."
               ".$yearWhere."
               ".$subcatWhere."
               ".$typeWhere."
               ".$fromDateWhere."
               ".$toDateWhere."
               ".$uomWhere."
            ORDER BY c.categorydescription, sc.subcategoryname, bt.boxtypename, oh.year, oh.transactiondate DESC";

//      echo "<pre>".$sql."</pre>\n";
        $rs = $page->db->sql_query($sql);
    }

    return $rs;
}

function getSubCatName($subcatid) {
    global $page;

    $sql = "
        SELECT subcategoryname
          FROM subcategories
         WHERE subcategoryid = ".$subcatid;

    $name = $page->db->get_field_query($sql);

    return $name;
}

function getSubCatId($subcat) {
    global $page;

    $sql = "
        SELECT subcategoryid
          FROM subcategories
         WHERE subcategoryname = '".$subcat."'";

    $id = $page->db->get_field_query($sql);

    return $id;
}

function getCategories($selectedids) {
    global $page;

    $catids = (empty($selectedids)) ? "0" : $selectedids;
    $sql = "
        SELECT c.categorydescription, c.categoryid,
                case when sel.categoryid is not null then 1
                     else 0 end as selected
          FROM categories       c

          LEFT JOIN categories  sel ON  sel.categoryid      = c.categoryid
                                    AND sel.categoryid in (".$catids.")
         WHERE c.active = 1
         ORDER BY c.categorytypeid, c.categorydescription COLLATE \"POSIX\"
    ";

//  echo "<pre>".$sql."</pre>";
    $rs = $page->db->sql_query_params($sql);

    return $rs;
}

function getBoxtypes($selectedids) {
    global $page;

    $btids = (empty($selectedids)) ? "0" : $selectedids;
    $sql = "
        SELECT bt.boxtypeid, bt.boxtypename,
                case when sel.boxtypeid is not null then 1
                     else 0 end as selected
          FROM boxtypes         bt
          LEFT JOIN boxtypes    sel ON  sel.boxtypeid      = bt.boxtypeid
                                    AND sel.boxtypeid in (".$btids.")
         WHERE bt.active = 1
         ORDER BY bt.boxtypename COLLATE \"POSIX\"
    ";

//  echo "<pre>".$sql."</pre>";
    $rs = $page->db->sql_query_params($sql);

    return $rs;
}

function getMultiSelect($data, $idname, $valuefield, $displayfield, $title, $selectedfieldname) {
    global $page;

    $header = "<div class='ms_header'>".$title."</div>";
    $selectedheader = "<div class='ms_header'>Selected ".$title."</div>";
    $options  = "{";
    $options .= "selectableHeader:\"".$header."\",";
    $options .= "selectionHeader: \"".$selectedheader."\",";
    $options .= "afterSelect: function(values){ $('#".$selectedfieldname."').val($('#".$selectedfieldname."').val() + '[' + values + ']'); },";
    $options .= "afterDeselect: function(values){ $('#".$selectedfieldname."').val($('#".$selectedfieldname."').val().replace('[' + values + ']', '')); }";
    $options .= "}";
    $js  = "
        $(\"#".$idname."\").multiSelect(".$options.");
    ";
    $page->jsInit($js);

    $output =  "<select id='".$idname."' multiple='multiple'/>\n";
    $selectedids = "";
    foreach ($data as $d) {
        $selected = (isset($d["selected"]) && !empty($d["selected"])) ? "selected" : "";
        $output .= "  <option value='".$d[$valuefield]."' ".$selected.">".$d[$displayfield]."</option>\n";
        $selectedids .= (!empty($selected)) ? "[".$d[$valuefield]."]" : "";
    }
    $output .= "</select>\n";
    $output .= "<input type='hidden' name='".$selectedfieldname."' id='".$selectedfieldname."' value='".$selectedids."'>\n";

    return $output;
}

function getProducts4Export($categoryids, $boxtypeids, $years, $subcatsNL, $transType, $fromDateTime, $toDateTime, $uom) {
    global $page, $listingTypeId;

    $returnData = null;

    if (!empty($categoryids)) {
        $boxTypeWhere = (!empty($boxtypeids)) ? "AND oh.boxtypeid IN (".$boxtypeids.")" : "";
        $subcatWhere = (!empty($subCatId)) ? "AND oh.subcategoryid      = ".$subCatId : "";
        $typeWhere = ($transType == 'Both') ? "" : " AND oh.type='".$transType."' ";
        $fromDateWhere = ($fromDateTime) ? " AND oh.transactiondate >= ".$fromDateTime." " : "";
        $toDateWhere = ($toDateTime) ? " AND oh.transactiondate <= ".$toDateTime." " : "";
        $uomWhere = (($uom == 'case') || ($uom == 'box')) ? " AND oh.uom = '".$uom."' " : "";
        $yearWhere = ($years) ? "AND oh.year4 IN (".$years.")" : "";

        $subcats = null;
        if (!empty($subcatsNL)) {
            $x = explode("\n", $subcatsNL);
            if (count($x)) {
                $subcats = "";
                $alphanumericonly = "/[^a-zA-Z0-9]+/";
                $scstart = " alphanumericonly(lower(sc.subcategoryname)) LIKE ('%";
                $scend   = "%')\n";
                foreach($x as $sc) {
                    $sc = trim($sc);
                    if (!empty($sc)) {
                        $subcats .= (empty($subcats)) ? $scstart.strtolower(preg_replace($alphanumericonly, "", $sc)).$scend : " OR ".$scstart.strtolower(preg_replace($alphanumericonly, "", $sc)).$scend;
                    }
                }
                $subcats = "AND (".$subcats.")";
            }
        }
        $subcatWhere = $subcats;

        $sql = "
            SELECT c.categorydescription                    as category,
                   sc.subcategoryname                       as subcategory,
                   bt.boxtypename                           as box_type,
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

              LEFT JOIN listings    l   ON  l.categoryid        = oh.categoryid
                                        AND l.subcategoryid     = oh.subcategoryid
                                        AND l.boxtypeid         = oh.boxtypeid
                                        AND l.year              = oh.year
                                        AND l.userid            = ".FACTORYCOSTID."
                                        AND l.uom               = 'box'
                                        AND l.status            = 'OPEN'
             WHERE oh.categoryid IN (".$categoryids.")
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

//      echo "<pre>".$sql."</pre><br />\n";
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