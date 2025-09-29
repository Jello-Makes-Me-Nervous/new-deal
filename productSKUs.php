<?php
require_once('templateMarket.class.php');

$page = new templateMarket(LOGIN, SHOWMSG);
if (! ($page->user->isAdmin() || $page->user->hasUserRightId(USERRIGHT_PRODUCT_ENTRY))) {
    header('location:home.php?pgemsg='.URLEncode("The requested page required Product Entry access"));
    exit();
}

$page->requireJS('scripts/tabs.js');

// Temp Styles
$page->pageStyle(".filterlabel { font-weight: bold; margin-right:3px; }");
$page->pageStyle("div.filterbox { border-style: solid; border-width: 1px; border-color: #e5e5e5; overflow: hidden; padding: 5px 5px 5px 10px;}");
$page->pageStyle("div.filteritem { margin: 5px 10px 5px 0px; }");
$page->pageStyle("div.filtertitle { padding: 0.7rem 0rem 0.7rem 0rem; }");

$boxTypeId      = optional_param('boxtypeid', NULL, PARAM_INT);
$categoryId     = optional_param('categoryid', NULL, PARAM_INT);
$subCategoryId  = optional_param('subcategoryid', NULL, PARAM_INT);
$year           = optional_param('year', NULL, PARAM_TEXT);
$displayMode    = optional_param('displaymode', 'yr', PARAM_TEXT);

$productData = null;

$categoryDisplay = null;
if (empty($categoryId)) {
    $page->messages->addErrorMsg("Select a category below.");
} else {
    setGlobalListingTypeId($categoryId);
    if ($listingTypeId == LISTING_TYPE_BLAST) {
        $page->addErrorMsg("Listing type BLAST not supported for products");
    } else {
        $categoryDisplay = $page->db->get_field_query("select categorydescription from categories where categoryid=".$categoryId);
        if ($displayMode == 'yr') {
            if (($listingTypeId == LISTING_TYPE_SPORTS) && (empty($year))) {
                $boxTypeId = NULL;
                $subCategoryId = NULL;
            }
        } else {
            if (empty($subCategoryId)) {
                $boxTypeId = NULL;
                $year = NULL;
            }
        }
    //echo "Corrected listingTypeId:".$listingTypeId." CategoryID:".$categoryId." Year:".$year." BoxTypeId:".$boxTypeId." SubCategoryId:".$subCategoryId."<br />\n";
        if ($displayMode == 'yr') {
            if (($listingTypeId == LISTING_TYPE_GAMING) || (!empty($year))) {
                $page->setTimestamp("Start Products");
                $productData = getProducts($categoryId, $boxTypeId, $year, $subCategoryId);
                $page->setTimestamp("End Products");
            } else {
                $page->messages->addInfoMsg("Select a year to display matching products.");
            }
        } else {
            if ($subCategoryId) {
                $page->setTimestamp("Start Products");
                $productData = getProducts($categoryId, $boxTypeId, $year, $subCategoryId);
                $page->setTimestamp("End Products");
            } else {
                $page->messages->addInfoMsg("Select a subcategory to display matching products.");
            }
        }
    }
}

echo $page->header('Products');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $listingTypeId, $displayMode, $boxTypeId, $categoryId, $subCategoryId, $categoryDisplay, $year;
    global $productData;

    echo "<form id='sub' name='sub' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "  <input type='hidden' id='listingtypeid' name='listingtypeid' value='".$listingTypeId."' />\n";
    echo "  <input type='hidden' id='categoryid' name='categoryid' value='".$categoryId."' />\n";
    if ($listingTypeId == LISTING_TYPE_GAMING) {
        echo "  <input type='hidden' id='year' name='year' value='0' />\n";
    }

    // TABS
    echo "<div class='tab'>\n";
    $firstTabTitle = ($listingTypeId == LISTING_TYPE_SPORTS) ? "By Year" : "By Category";
    echo displayListingsTab('yr', $firstTabTitle)."\n";
    echo displayListingsTab('sc',"By Subcategory")."\n";
    echo "  <input type='hidden' id='displaymode' name='displaymode' value='".$displayMode."' />\n";
    echo "</div>\n";

    // FILTERS
    echo "  <div class='tabcontent' style='display:block;'>\n";
    echo "    <div class='filterbox'>\n";
    //echo categoryDisplay($categoryDisplay);
    echo categoryDDM($categoryId);
    if ($displayMode == 'yr') {
        if ($listingTypeId != LISTING_TYPE_GAMING) {
            $page->setTimestamp("Start Year DDM");
            echo yearDDM($categoryId, $year, $boxTypeId, $subCategoryId);
            $page->setTimestamp("Done Year DDM");
        }
        $page->setTimestamp("Start BoxType DDM");
        echo boxTypeDDM($categoryId, $year, $boxTypeId, $subCategoryId);
        $page->setTimestamp("Done BoxType DDM");
        $page->setTimestamp("Start Subcategory DDM");
        echo subCategoryDDM($categoryId, $year, $boxTypeId, $subCategoryId);
        $page->setTimestamp("Done Subcategory DDM");
    } else {
        $page->setTimestamp("Start Subcategory DDM");
        echo subCategoryDDM($categoryId, $year, $boxTypeId, $subCategoryId);
        $page->setTimestamp("Done Subcategory DDM");
        if ($listingTypeId != LISTING_TYPE_GAMING) {
            $page->setTimestamp("Start Year DDM");
            echo yearDDM($categoryId, $year, $boxTypeId, $subCategoryId);
            $page->setTimestamp("Done Year DDM");
        }
        $page->setTimestamp("Start BoxType DDM");
        echo boxTypeDDM($categoryId, $year, $boxTypeId, $subCategoryId);
        $page->setTimestamp("Done BoxType DDM");
    }
    echo "    <div style='float: left; margin: 10px 5px 20px 5px;'><a class='button' href='".getAddProductURL()."' target='_blank'>New</a></div>\n";
    echo "    <div style='float: left; margin: 10px 5px 20px 5px;'><a class='button' href='#' onClick=\"document.getElementById('sub').submit();\">Refresh</a></div>\n";
    echo "    </div>\n"; // Filterbox
    echo "</form>\n";
    echo "\n";

    // Products
    if (!empty($productData)) {
        echo "<table>\n";
        echo "  <thead>\n";
        echo "    <tr>\n";
        echo "      <th align='center'>Product Id</th>\n";
        echo "      <th align='center'>UPC</th>\n";
        echo "      <th align='center'>Category</th>\n";
        if ($displayMode == 'yr') {
            echo "      <th align='center'>Year</th>\n";
            echo "      <th align='center'>Subcategory</th>\n";
            echo "      <th align='center'>Box Type</th>\n";
        } else {
            echo "      <th align='center'>Subcategory</th>\n";
            echo "      <th align='center'>Box Type</th>\n";
            echo "      <th align='center'>Year</th>\n";
        }
        echo "      <th align='center'>Variation</th>\n";
        echo "      <th align='center'>Listings<br />(assigned/total)</th>\n";
        echo "      <th align='center'>Action</th>\n";
        echo "    </tr>\n";
        echo "  </thead>\n";
        echo "  <tbody>\n";
        $rowClass = "even";
        $prevState = 1;
        foreach ($productData as $data ) {
            if ($data["active"] <> $prevState) {
                $style = "";
                if (($prevState == 1) && ($data["active"] == 0)) {
                    $style = "style='background-color:#efbac6;'";
                }
                echo "    <tr ".$style."><td colspan='9' class='center'><b>Inactives</b></td></tr>\n";
                $prevState = $data["active"];
            }
            $hasNote = ($data['productnote']) ? true : false;

            $rowClass = ($rowClass == "odd") ? "even" : "odd";
            echo "    <tr class='".$rowClass."'>\n";
            echo "      <td class='number' ".(($hasNote) ? "rowspan=2" : "").">".$data['productid']."</td>\n";
            echo "      <td class='number'>".$data['upcs']."</td>\n";
            echo "      <td>".$data['categorydescription']."</td>\n";
            if ($displayMode == 'yr') {
                echo "      <td class='number'>".$data['year']."</td>\n";
                echo "      <td>".$data['subcategorydescription']."</td>\n";
                echo "      <td>".$data['boxtypename']."</td>\n";
            } else {
                echo "      <td>".$data['subcategorydescription']."</td>\n";
                echo "      <td>".$data['boxtypename']."</td>\n";
                echo "      <td class='number'>".$data['year']."</td>\n";
            }
            echo "      <td>".$data['variation']."</td>\n";
            $listingURL = getProductListingURL($data);
            if ($listingURL) {
                $listingDisplay = "        <a href='".$listingURL."' target='_blank'>".$data['numactual']." / ".$data['numpossible']."</a>";
            } else {
                $listingDisplay = $data['numactual']." / ".$data['numpossible'];
            }
            echo "      </td>";
            echo "      <td class='number'>".$listingDisplay."</td>\n";
            echo "      <td style='white-space:nowrap;'>";
            echo "        <a href='/product.php?productid=".$data['productid']."&action=edit' target='_blank' class=' fas fa-edit'></a>\n";
            if ($data['numactual'] == 0) {
                echo "        &nbsp;";
                echo "        <a href='/product.php?productid=".$data['productid']."&action=delete' target='_blank' class=' fas fa-trash'></a>\n";
            }
            echo "      </td>\n";
            echo "    </tr>\n";
            if ($hasNote) {
                echo "    <tr  class='".$rowClass." xs-font-size'>\n";
                echo "      <td colspan='8'><strong>Product Note:</strong>".$data['productnote']."</td>\n";
                echo "    </tr>\n";
            }
        }
        $page->setTimestamp("Done Products");
        echo "  </tbody>\n";
        echo "</table>\n";
    }
    echo " </div>\n"; // Tab Content
}

function getMaxYear($categoryId) {
    global $page;
    $page->setTimestamp("Start Max Year");

    $maxYear = NULL;
    $typeWhere = ($page->user->canSell()) ? "" : " AND l.type='For Sale'";

    $categoryYearFormat = $page->db->get_field_query("SELECT yearformattypeid FROM categories WHERE categoryid=".$categoryId);
    if ($categoryYearFormat) {
        if ($categoryYearFormat == 2) {
            $sql = "
                SELECT max(l.year) as maxyr
                  FROM categories   c
                  JOIN listings     l   ON  c.categoryid         = l.categoryid
                                        AND l.status             = 'OPEN' ".$typeWhere."
                                        AND length(trim(l.year)) = 4
                                        AND isnumeric(l.year)    = true
                WHERE c.categoryid = ".$categoryId;

            $maxYear = $page->db->get_field_query($sql);
        } else {
            if ($categoryYearFormat == 1) {
                $sql = "
                    SELECT max(
                            CASE WHEN substring(l.year FROM 1 FOR 2)::INTEGER > 50 THEN ('19'||substring(l.year FROM 1 FOR 2))
                                ELSE ('20'||substring(l.year FROM 1 FOR 2))
                                END) AS maxyr
                      FROM categories   c
                      JOIN listings     l   ON  c.categoryid            = l.categoryid
                                            AND l.status                = 'OPEN' ".$typeWhere."
                                            AND length(trim(l.year))    = 4
                                            AND position('/' in l.year) = 3
                                            AND isnumeric(substring(l.year FROM 1 FOR 2)) = true
                    WHERE c.categoryid = ".$categoryId;

                $maxOne = $page->db->get_field_query($sql);
                if ($maxOne) {
                    $yr = substr($maxOne, 2, 2);
                    $yrend = ($yr[1] == "9") ? "0" : intval($yr[1]) + 1;
                    $maxYear = $yr."/".$yrend;
                }
            }
        }
    }
    $page->setTimestamp("End Max Year");
    return $maxYear;
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

function categoryDisplay($categoryDisplay) {
    global $page, $listingTypeId;
    $output = "";
    $output = "<div class='filteritem filtertitle' style='float:left;'><label class='filterlabel'>Category:</label> ".$categoryDisplay."</div>\n";

    return $output;
}

function categoryDDM($categoryId = NULL) {
    global $page, $listingTypeId;
    $output = "";
    $divLabel = "<div class='filteritem' style='float:left;'><label class='filterlabel' for='categoryid'>Category:</label>";
    $divClose = "</div>\n";

    $categories = getProductCategories();
    $onChange = " onchange = \"$('#year').val('');$('#boxtypeid').val('');submit();\"";
    $output = $divLabel.getSelectDDM($categories, "categoryid", "categoryid", "categorydescription", NULL, $categoryId, "Select", 0, NULL, NULL, $onChange).$divClose;

    return $output;
}

function getProductCategories() {
    global $DB;

    $sql = "
        SELECT c.categoryid, c.categorydescription, c.active
          FROM categories   c
         WHERE c.active         = 1
           AND c.categorytypeid IN (1,2)
         GROUP BY c.categoryid, c.categorydescription, c.active
         ORDER BY active DESC, c.categorytypeid, categoryName COLLATE \"POSIX\"";

//      echo "<pre>".$sql."</pre>";
    $categoriesData = $DB->sql_query_params($sql);

    return $categoriesData;
}

function subCategoryDDM($categoryId = null, $year = null, $boxTypeId = NULL, $subCategoryId = NULL) {
    global $page, $displayMode, $listingTypeId;
    $output = "";
    $divLabel = "<div class='filteritem' style='float:left;'><label class='filterlabel' for='subcategoryid'>Subcategory:</label>";
    $divClose = "</div>\n";

    if ((!empty($categoryId))) {
        if ($displayMode == 'yr') {
            if (($listingTypeId == LISTING_TYPE_GAMING) || (!empty($year))) {
                $rs = getProductSubcategories($categoryId, $boxTypeId, $year);
                $onChange = " onchange = \"submit();\"";
                $output = $divLabel.getSelectDDM($rs, "subcategoryid", "subcategoryid", "subcategoryname", NULL, $subCategoryId, "All", 0, NULL, NULL, $onChange).$divClose;
            } else {
                $output = "<input type='hidden' name='subcategoryid' id='subcategoryid' value='' />\n";
            }
        } else {
            $rs = getProductSubcategories($categoryId);
            $onChange = " onchange = \"$('#year').val('');$('#boxtypeid').val('');submit();\"";
            $output = $divLabel.getSelectDDM($rs, "subcategoryid", "subcategoryid", "subcategoryname", NULL, $subCategoryId, "All", 0, NULL, NULL, $onChange).$divClose;
        }
    } else {
        $output = "<input type='hidden' name='subcategoryid' id='subcategoryid' value='' />\n";
    }

    return $output;
}

function getProductSubcategories($categoryId, $boxTypeId=NULL, $year=NULL) {
    global $DB;

    $yearWhere =  (empty($year)) ? "" : " AND p.year = '".$year."' ";
    $boxTypeWhere =  (empty($boxTypeId)) ? "" : " AND p.boxtypeid = '".$boxTypeId."' ";

    $sql = "
        SELECT p.subcategoryid, s.subcategoryname
          FROM products         p
          JOIN categories       c   ON  c.categoryid    = p.categoryid
                                    AND c.active        = 1
          JOIN subcategories    s   ON  s.subcategoryid = p.subcategoryid
                                    AND s.active        = 1
          JOIN boxtypes         bt  ON  bt.boxtypeid    = p.boxtypeid
                                    AND bt.active       = 1
         WHERE p.active     = 1
           AND p.categoryId = ".$categoryId.$boxTypeWhere.$yearWhere."
        GROUP BY p.subcategoryid, s.subcategoryname
        ORDER BY s.subcategoryname COLLATE \"POSIX\"";

    $subCatData = $DB->sql_query($sql);
//echo "SQL:".$sql."<br />\n";
    return $subCatData;
}

function yearDDM($categoryId, $year, $boxTypeId, $subCategoryId) {
    global $page, $displayMode;
    $output = "";
    $divLabel = "<div class='filteritem' style='float:left;'><label class='filterlabel' for='year'>Year:</label>";
    $divClose = "</div>\n";

    if (!empty($categoryId)) {
        if ($displayMode == 'yr') {
            $rs = getProductYears($categoryId);
            $onChange = " onchange = \"$('#boxtypeid').val('');$('#subcategoryid').val('');submit();\"";
            $output = $divLabel.getSelectDDM($rs, "year", "year", "yearname", NULL, $year, "Select", 0, NULL, NULL, $onChange).$divClose;
        } else {
            if (! empty($subCategoryId)) {
                $rs = getProductYears($categoryId, $boxTypeId, $subCategoryId);
                $onChange = " onchange = \"submit();\"";
                $output = $divLabel.getSelectDDM($rs, "year", "year", "yearname", NULL, $year, "All", 0, NULL, NULL, $onChange).$divClose;
            } else {
                $output = "<input type='hidden' name='year' id='year' value='' />\n";
            }
        }
    } else {
        $output = "<input type='hidden' name='year' id='year' value='' />\n";
    }

    return $output;
}

function getProductYears($categoryId, $boxTypeId=NULL, $subCategoryId=NULL) {
    global $page, $DB;

    $boxTypeWhere = ($boxTypeId) ? " AND p.boxtypeid=".$boxTypeId." " : "";
    $subCategoryWhere = ($subCategoryId) ? " AND p.subcategoryid=".$subCategoryId." " : "";

    $sql = "
        SELECT p.year, p.year as yearname, p.year4
          FROM products p
         WHERE p.active     = 1
          AND p.categoryId  = ".$categoryId.$boxTypeWhere.$subCategoryWhere."
        GROUP BY p.year, p.year4
        ORDER BY p.year4 DESC, p.year DESC
    ";

    $yearData = $DB->sql_query($sql);

    return $yearData;
}

function boxTypeDDM($categoryId = null, $year = null, $boxTypeId = NULL, $subCategoryId = NULL) {
    global $page, $displayMode, $listingTypeId;
    $output = "";
    $divLabel = "<div class='filteritem' style='float:left;'><label class='filterlabel' for='boxtypeid'>Box Type:</label>";
    $divClose = "</div>\n";

    if (!empty($categoryId)) {
        if ($displayMode == 'yr') {
            if (($listingTypeId == LISTING_TYPE_GAMING) || (!empty($year))) {
                $rs = getProductBoxTypes($categoryId, $year, $subCategoryId);
                $onChange = " onchange = \"submit();\"";
                $output = $divLabel.getSelectDDM($rs, "boxtypeid", "boxtypeid", "boxtypename", NULL, $boxTypeId, "All", 0, NULL, NULL, $onChange).$divClose;
            } else {
                $output = "<input type='hidden' name='boxtypeid' id='boxtypeid' value='' />\n";
            }
        } else {
            if (! empty($subCategoryId)) {
                $rs = getProductBoxTypes($categoryId, $year, $subCategoryId);
                $onChange = " onchange = \"submit();\"";
                $output = $divLabel.getSelectDDM($rs, "boxtypeid", "boxtypeid", "boxtypename", NULL, $boxTypeId, "All", 0, NULL, NULL, $onChange).$divClose;
            } else {
                $output = "<input type='hidden' name='boxtypeid' id='boxtypeid' value='' />\n";
            }
        }
    } else {
        $output = "<input type='hidden' name='boxtypeid' id='boxtypeid' value='' />\n";
    }

    return $output;
}

function getProductBoxTypes($categoryid = NULL, $year = NULL, $subcategoryid = NULL, $boxtypeid = NULL) {
    global $page, $DB;

    $sql = "
        SELECT bt.boxtypeid, bt.boxtypename, bt.active
          FROM boxtypes bt
          JOIN products p ON p.boxtypeid = bt.boxtypeid
        WHERE bt.active = 1";

    if (!empty($categoryid)) {
        $sql .= "
           AND p.categoryid     = ".$categoryid;
    }

    if (!empty($year)) {
        $sql .= "
           AND p.year           = '".$year."'";
    }

    if (!empty($subcategoryid)) {
        $sql .= "
           AND p.subcategoryid  = ".$subcategoryid;
    }

    $sql .= "
        GROUP BY bt.boxtypeid, bt.boxtypename, bt.active
        ORDER BY bt.active, bt.boxtypename COLLATE \"POSIX\"";

//    echo "<pre>".$sql."</pre>\n";
    $data = $DB->sql_query($sql);

    return $data;
}


function getProducts($categoryId, $boxTypeId, $year, $subCategoryId) {
    global $page, $displayMode;

    $products = NULL;

    if ($categoryId) {
        $andSubcategory = ($subCategoryId) ? " AND p.subcategoryid = ".$subCategoryId." " : "";
        $andBoxtype = ($boxTypeId) ? " AND p.boxtypeid = ".$boxTypeId." " : "";
        $andYear = ($year) ? " AND p.year = '".$year."' " : "";

        $sql = "
            SELECT productid, upcs, variation, productnote, picture, active,
                   categoryid, subcategoryid, boxtypeid, year, year4,
                   categoryname, subcategoryname, boxtypename,
                   categorydescription, subcategorydescription,
                   sum(ispossible) AS numpossible,
                   sum(isactual) AS numactual
            FROM (
                SELECT p.productid, upc.upcs, p.variation, p.productnote, p.picture, p.active,
                       c.categoryid, sc.subcategoryid, bt.boxtypeid, p.year, p.year4,
                       c.categoryname, sc.subcategoryname, bt.boxtypename,
                       c.categorydescription, sc.subcategorydescription,
                       CASE WHEN lp.listingid IS NOT NULL THEN 1 ELSE 0 END AS ispossible,
                       CASE WHEN p.productid = lp.productid THEN 1 ELSE 0 END AS isactual
                  FROM products         p
                  JOIN categories       c   ON  c.categoryid        = p.categoryid
                  JOIN subcategories    sc  ON  sc.subcategoryid    = p.subcategoryid
                  JOIN boxtypes         bt  ON  bt.boxtypeid        = p.boxtypeid
                  LEFT JOIN listings    lp  ON  lp.status           = 'OPEN'
                                            AND lp.categoryid       = p.categoryid
                                            AND lp.subcategoryid    = p.subcategoryid
                                            AND lp.boxtypeid        = p.boxtypeid
                                            AND isnull(lp.year, '1') = isnull(p.year, '1')
                  LEFT JOIN (
                        SELECT p.productid,
                               array_to_string(array_agg(pu.upc), '<br>') as upcs
                          FROM products     p
                          JOIN product_upc  pu  ON  pu.productid    = p.productid
                         WHERE p.active = 1
                        GROUP BY p.productid
                            )           upc ON  upc.productid       = p.productid
                 WHERE p.categoryid = ".$categoryId.$andSubcategory.$andBoxtype.$andYear."
                 ) prodlists
            GROUP BY productid, upcs, variation, productnote, picture, active,
                     categoryid, subcategoryid, boxtypeid, year, year4,
                     categoryname, subcategoryname, boxtypename,
                     categorydescription, subcategorydescription
            ";
        if ($displayMode == 'yr') {
            $sql .= "ORDER BY active desc, categorydescription COLLATE \"POSIX\", year4, year, subcategorydescription COLLATE \"POSIX\", boxtypename COLLATE \"POSIX\", upcs  COLLATE \"POSIX\"";
        } else {
            $sql .= "ORDER BY active desc, categorydescription COLLATE \"POSIX\", subcategorydescription COLLATE \"POSIX\", boxtypename COLLATE \"POSIX\", year4 DESC, year DESC, upcs  COLLATE \"POSIX\"";
        }

        //echo "getProducts SQL:<br />\n<pre>".$sql."</pre><br />\n";
        if ($results = $page->db->sql_query($sql)) {
            $products = $results;
        }
    }

    return $products;
}

function displayListingsTab($tabId, $tabLabel) {
    global $page, $displayMode;

    $isActive = ($tabId == $displayMode) ? " active" : "";
    return "  <button class='tablinks".$isActive."' onclick=\"$('#displaymode').val('".$tabId."');submit();\" >".$tabLabel."</button>";
}

function getAddProductURL() {
    global $page, $boxTypeId, $categoryId, $subCategoryId, $year;

    $urlParams = array();
    $urlParams[] = "action=add";
    if ($categoryId) $urlParams[] = "categoryid=".$categoryId;
    if ($subCategoryId) $urlParams[] = "subcategoryid=".$subCategoryId;
    if ($boxTypeId) $urlParams[] = "boxtypeid=".$boxTypeId;
    if ($year) $urlParams[] = "year=".URLEncode($year);

    $url = "/product.php?".implode('&', $urlParams);

    return $url;
}

function getProductListingURL($prodData) {
    global $page;

    $url = null;

    $urlParams = array();
    if ($prodData['categoryid']) $urlParams[] = "categoryid=".$prodData['categoryid'];
    if ($prodData['subcategoryid']) $urlParams[] = "subcategoryid=".$prodData['subcategoryid'];
    if ($prodData['boxtypeid']) $urlParams[] = "boxtypeid=".$prodData['boxtypeid'];
    if ($prodData['year']) $urlParams[] = "year=".URLEncode($prodData['year']);

    if (count($urlParams) > 0) {
        $url = "/listing.php?".implode('&', $urlParams);
    }

    return $url;
}


?>