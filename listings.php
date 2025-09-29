<?php
require_once('templateMarket.class.php');

$page = new templateMarket(LOGIN, SHOWMSG);
$page->requireJS('scripts/tabs.js');

// Temp Styles
$page->pageStyle(".filterlabel { font-weight: bold; margin-right:3px; }");
$page->pageStyle("div.filterbox { border-style: solid; border-width: 1px; border-color: #e5e5e5; overflow: hidden; padding: 5px 5px 5px 10px;}");
$page->pageStyle("div.filteritem { margin: 5px 10px 5px 0px; }");
$page->pageStyle("div.filtertitle { padding: 0.7rem 0rem 0.7rem 0rem; }");

$boxTypeId  = optional_param('boxtypeid', NULL, PARAM_INT);
$categoryId = optional_param('categoryid', NULL, PARAM_INT);
$subCategoryId = optional_param('subcategoryid', NULL, PARAM_INT);
$year       = optional_param('year', NULL, PARAM_TEXT);
$displayMode = optional_param('displaymode', 'yr', PARAM_TEXT);

//echo "Raw CategoryID:".$categoryId." Year:".$year." BoxTypeId:".$boxTypeId." SubCategoryId:".$subCategoryId."<br />\n";

$subCatData = null;
$categoryDisplay = null;
if (empty($categoryId)) {
    $page->messages->addErrorMsg("Select a category from the Marketplace menu above.");
} else {
    setGlobalListingTypeId($categoryId);
    if ($listingTypeId == LISTING_TYPE_BLAST) {
        header("location:blasts.php");
    }

    $categoryDisplay = $page->db->get_field_query("select categorydescription from categories where categoryid=".$categoryId);
    if ($displayMode == 'yr') {
        if (($listingTypeId == LISTING_TYPE_SPORTS) && (empty($year))) {
            $year = getMaxYear($categoryId);
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
            $page->setTimestamp("Start Subcategories");
            $subCatData = getSubCategories($categoryId, $boxTypeId, $year, $subCategoryId);
            $page->setTimestamp("End Subcategories");
        } else {
            $page->messages->addInfoMsg("Select a year to display matching listings.");
        }
    } else {
        if ($subCategoryId) {
            $page->setTimestamp("Start Subcategories");
            $subCatData = getSubCategories($categoryId, $boxTypeId, $year, $subCategoryId);
            $page->setTimestamp("End Subcategories");
        } else {
            $page->messages->addInfoMsg("Select a subcategory to display matching listings.");
        }
    }
}

echo $page->header('Listings');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $listingTypeId, $displayMode, $boxTypeId, $categoryId, $subCategoryId, $categoryDisplay, $year, $subCatData;

    if ($categoryId) {
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
        echo categoryDisplay($categoryDisplay);
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
        echo "    </div>\n"; // Filterbox
        echo "</form>\n";
        echo "\n";

        // LISTINGS
        echo "<table>\n";
        echo "  <caption>\n";
        echo "    <div class='captionbox'>\n";
        echo "      <div class='caption-right'>\n";
        echo "        <span style='padding-right:25px;'>Factory Cost, High Bid and Low Ask are box prices</span>\n";
        if ($page->user->isAdmin() || $page->user->hasUserRightId(USERRIGHT_PRODUCT_ENTRY)) {
            $addNewParams = "";
            $addNewParams .= ($categoryId) ? ("?categoryid=".$categoryId) : "";
            $addNewParams .= ($year) ? ("&year=".URLEncode($year)) : "";
            $addNewParams .= ($boxTypeId) ? ("&boxtypeid=".$boxTypeId) : "";
            $addNewParams .= ($subCategoryId) ? ("&subcategoryid=".$subCategoryId) : "";
            $addNewButton = "<a href='productSKUs.php".$addNewParams."' name='productsku' id='productsku' class='captionlabel' target='_blank'><i class='fa-solid fa-circle-plus'></i> Add Product</a>";
            echo "        ".$addNewButton."\n";
        }
        echo "      </div>\n";
        echo "    </div>\n";
        echo "  </caption>\n";
        echo "  <thead>\n";
        echo "    <tr>\n";
        echo "      <th align='center'>UPC</th>\n";
        echo "      <th align='center'></th>\n";
        echo "      <th align='left'>Product</th>\n";
        echo "      <th align='center'>Variation</th>\n";
        echo "      <th align='center'>Release Date</th>\n";
        echo "      <th align='center'>Factory Cost</th>\n";
        echo "      <th align='center'>High Bid</th>\n";
        echo "      <th align='center'>Low Ask</th>\n";
        echo "    </tr>\n";
        echo "  </thead>\n";
        echo "  <tbody>\n";
        if (!empty($subCatData)) {
            $inSecondary = false;
            $rowClass = "";
            $rowStyle = "";
            foreach ($subCatData as $data ) {
                if ($data['secondary']) {
                    $secondaryOnly = ($subCategoryId && ($subCategoryId == $data['subcategoryid'])) ? true : false;
                    $rowClass = " class='secondarysc' ";
                    if (!$secondaryOnly) {
                        $rowStyle = " style='display:none;' ";
                    }
                    if (! $inSecondary) {
                        echo "<tr>";
                        echo "<td colspan='5'>";
                        if (! $secondaryOnly) {
                            echo "<a title='Show secondary subcategories' href='#' onClick='$(\".secondarysc\").show();$(\".secondarytoggle\").hide(); return(false);' class='secondarytoggle' ><i class='fa-solid fa-plus'></i></a>";
                            echo "<a title='Hide secondary subcategories' href='#' onClick='$(\".secondarysc\").hide();$(\".secondarytoggle\").show(); return(false);' class='secondarysc' ".$rowStyle."><i class='fa-solid fa-minus'></i></a>";
                        }
                        echo " <strong>Secondary Subcategories</strong>";
                        echo "</td>";
                        echo "</tr>\n";
                        $inSecondary = true;
                    }
                }
                echo "    <tr ".$rowClass." ".$rowStyle.">\n";
                echo "      <td data-label='UPC' >";
                if ($page->user->isAdmin() || $page->user->hasUserRightId(USERRIGHT_PRODUCT_ENTRY)) {
                    if (!empty($data['productid'])) {
                        if (!empty($data['upcs'])) {
                            echo " <div style='float:right;'><a href='productSKUs.php?categoryid=".$data['categoryid']."&subcategoryid=".$data['subcategoryid']."&boxtypeid=".$data['boxtypeid']."&year=".URLEncode($data['year'])."' target='_blank' class=' fas fa-edit'></a></div>";
                        } else {
                            echo " <div style='float:right;'><a href='productSKUs.php?categoryid=".$data['categoryid']."&subcategoryid=".$data['subcategoryid']."&boxtypeid=".$data['boxtypeid']."&year=".URLEncode($data['year'])."' target='_blank' class=' fas fa-add'></a></div>";
                        }
                    } else {
                        echo " <a href='product.php?action=add&categoryid=".$data['categoryid']."&subcategoryid=".$data['subcategoryid']."&boxtypeid=".$data['boxtypeid']."&year=".URLEncode($data['year'])."' target='_blank' class=' fas fa-add'></a>";
                    }
                }
                if (!empty($data['upcs'])) {
                    $upcs = str_replace(",", "<br>", $data["upcs"]);
                    echo "<div style='float:right;padding-right: 10px;'>".$upcs."</div>";
                }
                echo "</td>\n";
                if (empty($data["haslistings"])) {
                    $url = "JavaScript:void(0);";
                    $onclick = "onclick=\"JavaScript:alert('There are no active listings for this product. \\nClick Add New Listing to create one.');\"";
                } else {
                    $listingYearParam = ($data['year']) ? "&year=".$data['year'] : "";
                    $url = "listing.php?subcategoryid=".$data['subcategoryid']."&boxtypeid=".$data['boxtypeid']."&categoryid=".$categoryId."&listingtypeid=".$listingTypeId.$listingYearParam;
                    $onclick = "";
                }
                $link = null;
                if (!empty($data["picture"])) {
                    $picURL = $page->utility->Getprefixpublicimageurl($data["picture"], THUMB100);
                } else {
                    $picURL = "/images/spacer.gif";
                }
                $img = "<img src='".$picURL."' alt='product image' width='100' height='100'>";
                $link = "<a href='".$url."' ".$onclick.">".$img."</a>";
                echo "      <td data-label='Picture' class='center'>".$link."</td>\n";
                if ($displayMode == 'yr') {
                    $product  = $data['year']." - ".$data['subcategoryname']." - ".$data['boxtypename'];
                } else {
                    $product  = $data['subcategoryname']." - ".$data['year']." - ".$data['boxtypename'];
                }
                $link = "<a href='".$url."' ".$onclick.">".$product."</a>";
                echo "      <td data-label='Product'>".$link."</td>\n";
                echo "      <td data-label='Variation' class='center'>".$data['variation']."</td>\n";
                $releasedate = (empty($data['releasedate'])) ? NULL : date("m/d/Y", $data['releasedate']);
                echo "      <td data-label='Release Date' class='date'>".$releasedate."</td>\n";
                $fc = (empty($data['factorycost']) || $data['factorycost'] == 0.0) ? NULL : floatToMoney($data['factorycost']);
                echo "      <td data-label='Factory Cost' class='number'>".$fc."</td>\n";
                if (empty($data["haslistings"])) {
                    $url = "<a class='button' title='Add New Listing' href='listingCreate.php?categoryid=".$data["categoryid"]."&subcategoryid=".$data["subcategoryid"]."&boxtypeid=".$data["boxtypeid"]."&year=".$data["year"]."' target='_blank'>Add New Listing</a>";
                    echo "      <td data-label='Create Listing' class='number' colspan='2' style='padding:15px;'>".$url."</td>\n";
                } else {
                    echo "      <td data-label='High Bid' class='number'>".floatToMoney($data['highbuy'])."</td>\n";
                    echo "      <td data-label='Low Ask' class='number'>".floatToMoney($data['lowsell'])."</td>\n";
                }
                echo "    </tr>\n";
            }
        }
        $page->setTimestamp("Done Listings");
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
                FROM categories c
                JOIN listings l ON  c.categoryid            = l.categoryid
                                AND l.status                = 'OPEN' ".$typeWhere."
                                AND length(trim(l.year))    = 4
                                AND isnumeric(l.year)       = true
                WHERE c.categoryid = ".$categoryId;

            $maxYear = $page->db->get_field_query($sql);
        } else {
            if ($categoryYearFormat == 1) {
                $sql = "
                    SELECT max(
                           CASE WHEN substring(l.year FROM 1 FOR 2)::INTEGER > 50 THEN ('19'||substring(l.year FROM 1 FOR 2))
                                ELSE ('20'||substring(l.year FROM 1 FOR 2))
                                END) AS maxyr
                    FROM categories     c
                    JOIN listings       l   ON c.categoryid             = l.categoryid
                                            AND l.status                = 'OPEN' ".$typeWhere."
                                            AND length(trim(l.year))    = 4
                                            AND position('/' in l.year) = 3
                                            AND isnumeric(substring(l.year FROM 1 FOR 2)) = true
                    WHERE c.categoryid =".$categoryId;

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

    $categories = $page->utility->getListingCategories($listingTypeId);
    $onChange = " onchange = \"$('#year').val('');$('#boxtypeid').val('');submit();\"";
    $output = $divLabel.getSelectDDM($categories, "categoryid", "categoryid", "categorydescription", NULL, $categoryId, "Select", 0, NULL, NULL, $onChange).$divClose;

    return $output;
}

function boxTypeDDM($categoryId = null, $year = null, $boxTypeId = NULL, $subCategoryId = NULL) {
    global $page, $displayMode, $listingTypeId;
    $output = "";
    $divLabel = "<div class='filteritem' style='float:left;'><label class='filterlabel' for='boxtypeid'>Box Type:</label>";
    $divClose = "</div>\n";

    if (!empty($categoryId)) {
        if ($displayMode == 'yr') {
            if (($listingTypeId == LISTING_TYPE_GAMING) || (!empty($year))) {
                $rs = $page->utility->getProductBoxTypes($categoryId, $subCategoryId, $year);
                $onChange = " onchange = \"submit();\"";
                $output = $divLabel.getSelectDDM($rs, "boxtypeid", "boxtypeid", "boxtypename", NULL, $boxTypeId, "All", 0, NULL, NULL, $onChange).$divClose;
            } else {
                $output = "<input type='hidden' name='boxtypeid' id='boxtypeid' value='' />\n";
            }
        } else {
            if (! empty($subCategoryId)) {
                $rs = $page->utility->getProductBoxTypes($categoryId, $subCategoryId, $year);
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

function subCategoryDDM($categoryId = null, $year = null, $boxTypeId = NULL, $subCategoryId = NULL) {
    global $page, $displayMode, $listingTypeId;
    $output = "";
    $divLabel = "<div class='filteritem' style='float:left;'><label class='filterlabel' for='subcategoryid'>Subcategory:</label>";
    $divClose = "</div>\n";

    if ((!empty($categoryId))) {
        if ($displayMode == 'yr') {
            if (($listingTypeId == LISTING_TYPE_GAMING) || (!empty($year))) {
                $rs = $page->utility->getProductSubcategories($categoryId, $boxTypeId, $year);
                $onChange = " onchange = \"submit();\"";
                $output = $divLabel.getSelectDDM($rs, "subcategoryid", "subcategoryid", "subcategoryname", NULL, $subCategoryId, "All", 0, NULL, NULL, $onChange).$divClose;
            } else {
                $output = "<input type='hidden' name='subcategoryid' id='subcategoryid' value='' />\n";
            }
        } else {
            $rs = $page->utility->getProductSubcategories($categoryId);
            $onChange = " onchange = \"$('#year').val('');$('#boxtypeid').val('');submit();\"";
            $output = $divLabel.getSelectDDM($rs, "subcategoryid", "subcategoryid", "subcategoryname", NULL, $subCategoryId, "All", 0, NULL, NULL, $onChange).$divClose;
        }
    } else {
        $output = "<input type='hidden' name='subcategoryid' id='subcategoryid' value='' />\n";
    }

    return $output;
}

function yearDDM($categoryid, $year, $boxtypeid, $subcategoryid) {
    global $page, $displayMode;
    $output = "";
    $divLabel = "<div class='filteritem' style='float:left;'><label class='filterlabel' for='year'>Year:</label>";
    $divClose = "</div>\n";

    if (!empty($categoryid)) {
        if ($displayMode == 'yr') {
            $rs = $page->utility->getProductYears($categoryid);
            $onChange = " onchange = \"$('#boxtypeid').val('');$('#subcategoryid').val('');submit();\"";
            $output = $divLabel.getSelectDDM($rs, "year", "year", "yearname", NULL, $year, "Select", 0, NULL, NULL, $onChange).$divClose;
        } else {
            if (!empty($subCategoryId)) {
                $rs = $page->utility->getProductYears($categoryid, $boxtypeid, $subcategoryid);
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

function getSubCategories($categoryId, $boxTypeId, $year, $subCategoryId) {
    global $page, $displayMode, $listingTypeId;

    $factoryCostID = $page->utility->getDealerId(FACTORYCOSTNAME);
    if ($listingTypeId == LISTING_TYPE_GAMING) {
        $yearAnd = "null";
        $p_yearAnd = "null";
    } else {
        $yearAnd = (empty($year)) ? "l.year" : "'".$year."'";
        $p_yearAnd = (empty($year)) ? "p.year" : "'".$year."'";
    }
    $bt = (empty($boxTypeId)) ? "l.boxtypeid" : $boxTypeId;
    $p_bt = (empty($boxTypeId)) ? "p.boxtypeid" : $boxTypeId;
    $subcat = (empty($subCategoryId)) ? "l.subcategoryid" : $subCategoryId;
    $p_subcat = (empty($subCategoryId)) ? "p.subcategoryid" : $subCategoryId;
    $random = rand();

    $sql = "DROP TABLE IF EXISTS tmp_high_buy_".$random;
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_low_sell_".$random;
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_upcs_".$random;
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_listing_cnt_".$random;
    $page->queries->AddQuery($sql);

    $typeWhere = ($page->user->canSell()) ? "" : " AND l.type='For Sale'";

    $sql = "
        CREATE TEMPORARY TABLE tmp_high_buy_".$random." AS
        SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year, MAX(l.boxprice) AS highbuy
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
          LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                        AND stl.userrightid     = ".USERRIGHT_STALE."
         WHERE l.categoryid         = ".$categoryId."
           AND l.type               = 'Wanted'
           AND l.boxtypeid          = ".$bt."
           AND l.subcategoryid      = ".$subcat."
           AND isnull(l.year, '1')  = isnull(".$yearAnd.", '1')
           AND l.uom IN ('box', 'case')
           AND l.status             = 'OPEN'
           AND l.userid             <> ".$factoryCostID."
           AND stl.userid IS NULL
           ".$typeWhere."
        GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
        ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_low_sell_".$random." AS
        SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year, MIN(l.boxprice) AS lowsell
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
          LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                        AND stl.userrightid     = ".USERRIGHT_STALE."
         WHERE l.categoryid         = ".$categoryId."
           AND l.type               = 'For Sale'
           AND l.boxtypeid          = ".$bt."
           AND l.subcategoryid      = ".$subcat."
           AND isnull(l.year, '1')  = isnull(".$yearAnd.", '1')
           AND l.uom IN ('box', 'case')
           AND l.status             = 'OPEN'
           AND l.userid             <> ".$factoryCostID."
           AND stl.userid IS NULL
           ".$typeWhere."
        GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
        ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_listing_cnt_".$random." AS
        SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year, count(1) as listingcount
          FROM listings             l
          JOIN categories           c   ON  c.categoryid        = l.categoryid
                                        AND c.active            = 1
          JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                        AND sc.active           = 1
          JOIN assignedrights       ar  ON  ar.userid           = l.userid
                                        AND ar.userrightid      = 1
          JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                        AND bt.active           = 1
          JOIN userinfo             ui  ON  ui.userid           = l.userid
                                        AND ui.userclassid      = 3
                                        AND ((l.type='For Sale' AND ui.vacationsell=0)
                                             OR
                                             (l.type='Wanted' AND ui.vacationbuy=0))
          LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                        AND stl.userrightid     = ".USERRIGHT_STALE."
         WHERE l.categoryid         = ".$categoryId."
           AND l.boxtypeid          = ".$bt."
           AND l.subcategoryid      = ".$subcat."
           AND isnull(l.year, '1')  = isnull(".$yearAnd.", '1')
           AND l.status             = 'OPEN'
           AND l.userid             <> ".$factoryCostID."
           AND stl.userid IS NULL
           ".$typeWhere."
        GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
        ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
    ";
    $page->queries->AddQuery($sql);

    $upcyr = (empty($year)) ? "p.year" : "'".$year."'";
    $sql = "
        CREATE TEMPORARY TABLE tmp_upcs_".$random." AS
        SELECT p.productid, p.categoryid, p.subcategoryid, p.boxtypeid, p.year, pu.upcs, p.variation
          FROM products             p
          LEFT JOIN (
                SELECT u.productid, array_to_string(array_agg(u.upc), ',') as upcs
                  FROM product_upc  u
                  JOIN products     pr  ON  pr.productid        = u.productid
                                        AND pr.active           = 1
                GROUP BY u.productid
               )                    pu  ON  pu.productid        = p.productid
         WHERE p.active             = 1
           AND p.categoryid         = ".$categoryId."
           AND p.subcategoryid      = ".$p_subcat."
           AND p.boxtypeid          = ".$p_bt."
           AND isnull(p.year, '1')  = isnull(".$upcyr.", '1')
        ORDER BY p.categoryid, p.subcategoryid, p.boxtypeid, p.year
    ";
    $page->queries->AddQuery($sql);

//foreach($page->queries->sqls as $sql) {
//    echo "<pre>".$sql.";</pre>";
//}

    $page->queries->ProcessQueries();

    $sql = "
        SELECT p.categoryid, p.subcategoryid, sc.subcategoryname, sc.secondary, p.boxtypeid, bt.boxtypename, p.year,
               hb.highbuy, ls.lowsell, p.factorycost, p.releasedate, p.picture,
               lcnt.listingcount, upc.upcs, upc.variation, upc.productid,
               min(l.listingid) as haslistings
          FROM products             p
          JOIN categories           c   ON  c.categoryid        = p.categoryid
                                        AND c.active            = 1
          JOIN subcategories        sc  ON  sc.subcategoryid    = p.subcategoryid
                                        AND sc.active           = 1
          JOIN boxtypes             bt  ON  bt.boxtypeid        = p.boxtypeid
                                        AND bt.active           = 1
          LEFT JOIN listings        l   ON  l.categoryid        = p.categoryid
                                        AND l.subcategoryid     = p.subcategoryid
                                        AND l.boxtypeid         = p.boxtypeid
                                        AND isnull(l.year, '1') = isnull(p.year, '1')
                                        AND l.status            = 'OPEN'
                                        AND l.userid            <> ".$factoryCostID."
          LEFT JOIN assignedrights  ar  ON  ar.userid           = l.userid
                                        AND ar.userrightid      = 1
          LEFT JOIN userinfo        ui  ON ui.userid            = l.userid
                                        AND ui.userclassid      = 3
                                        AND ((l.type='For Sale' AND ui.vacationsell=0)
                                             OR
                                             (l.type='Wanted' AND ui.vacationbuy=0))
          LEFT JOIN tmp_high_buy_".$random."
                                    hb  ON  hb.subcategoryid    = p.subcategoryid
                                        AND hb.boxtypeid        = p.boxtypeid
                                        AND hb.categoryid       = p.categoryid
                                        AND isnull(hb.year, '1')= isnull(p.year, '1')
          LEFT JOIN tmp_low_sell_".$random."
                                    ls  ON  ls.subcategoryid    = p.subcategoryid
                                        AND ls.boxtypeid        = p.boxtypeid
                                        AND ls.categoryid       = p.categoryid
                                        AND isnull(ls.year, '1')= isnull(p.year, '1')
          LEFT JOIN tmp_listing_cnt_".$random."
                                   lcnt ON  lcnt.subcategoryid  = p.subcategoryid
                                        AND lcnt.boxtypeid      = p.boxtypeid
                                        AND lcnt.categoryid     = p.categoryid
                                        AND isnull(lcnt.year, '1') = isnull(p.year, '1')
          LEFT JOIN tmp_upcs_".$random."
                                    upc ON  upc.subcategoryid   = p.subcategoryid
                                        AND upc.boxtypeid       = p.boxtypeid
                                        AND upc.categoryid      = p.categoryid
                                        AND isnull(upc.year, '1') = isnull(p.year, '1')
          LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                        AND stl.userrightid     = ".USERRIGHT_STALE."
         WHERE p.categoryid         = ".$categoryId."
           AND p.boxtypeid          = ".$p_bt."
           AND p.subcategoryid      = ".$p_subcat."
           AND isnull(p.year, '1')  = isnull(".$p_yearAnd.", '1')
           AND stl.userid IS NULL
           ".$typeWhere."
         GROUP BY p.categoryid, p.subcategoryid, sc.subcategoryname, sc.secondary,
                  p.boxtypeid, bt.boxtypename, p.year, hb.highbuy, ls.lowsell,
                  p.factorycost, p.releasedate, p.picture,
                  lcnt.listingcount, upc.upcs, upc.variation, upc.productid
         ";
    if ($displayMode == 'yr') {
        $sql .= "ORDER BY sc.secondary, sc.subcategoryname COLLATE \"POSIX\", bt.boxtypename";
    } else {
        $sql .= "ORDER BY sc.secondary, sc.subcategoryname COLLATE \"POSIX\", bt.boxtypename, p.year DESC";
    }

//    echo "<pre>".$sql."</pre>";
    $data = $page->db->sql_query_params($sql);

    //echo "<pre>";print_r($data);echo "</pre>";
    unset($page->queries);
    $page->queries = new DBQueries("sub cat summary cleanup");

    $sql = "DROP TABLE IF EXISTS tmp_high_buy_".$random;
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_low_sell_".$random;
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_listing_cnt_".$random;
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_upcs_".$random;
    $page->queries->AddQuery($sql);
    $process = $page->queries->ProcessQueries();

    return $data;
}

function displayListingsTab($tabId, $tabLabel) {
    global $page, $displayMode;

    $isActive = ($tabId == $displayMode) ? " active" : "";
    return "  <button class='tablinks".$isActive."' onclick=\"$('#displaymode').val('".$tabId."');submit();\" >".$tabLabel."</button>";
}


?>