<?php
require_once('templateMarket.class.php');

$page = new templateMarket(LOGIN, SHOWMSG);
$page->requireJS("/scripts/populateSubCatBoxtype.js");


$addToCart      = optional_param('addToCart', NULL, PARAM_TEXT);
$addToCartBTN   = optional_param('addToCartBTN', NULL, PARAM_TEXT);
$boxTypeId      = optional_param('boxTypeId', NULL, PARAM_INT);
$categoryId     = optional_param('categoryId', NULL, PARAM_INT);
$dealerId       = optional_param('dealerId', NULL, PARAM_INT);
$dealerName     = optional_param('dealerName', NULL, PARAM_TEXT);
$go             = optional_param('go', NULL, PARAM_TEXT);
$keyword        = optional_param('keyword', NULL, PARAM_TEXT);
$listingId      = optional_param('listingId', NULL, PARAM_TEXT);
$listingSince   = optional_param('listingSince', NULL, PARAM_INT);
$search         = optional_param('search', NULL, PARAM_TEXT);//array
$sort           = optional_param('sort', NULL, PARAM_TEXT);
$subCategoryId  = optional_param('subCategoryId', NULL, PARAM_INT);
$type           = optional_param('type', "both", PARAM_TEXT);
$uom            = optional_param('uom', "NUL", PARAM_TEXT);
$year           = optional_param('year', NULL, PARAM_TEXT);


$listing = new listing();
$Cart = new shoppingcart($USER->userId);

$listings = showresults($page->boxTypeId, $page->categoryId, $page->dealerName, $page->dealerId, $page->listingSince, $page->sort, $page->subCategoryId, $page->type, $page->keyword);

if (!empty($addToCart) && is_array($listingId)) {
    $i = 1;
    foreach ($listingId as $listId) {
        $l = new listing($listId);
        $Cart->addToCart($listId, $USER->userId, $l->listingUserId, $l->dprice, $l->quantity, $l->minQuantity, $l->categoryId, $l->subCategoryId, $l->boxtypeId, $l->listingNotes, $l->year, $USER->userId);
        unset($l);

    } $i++;
    $page->messages->addSuccessMsg("You have added ".$i." new Item(s) to your <a href='shoppingCart.php' class='button'>Cart</a>");
//header('location:shoppingCart.php');//change this or add continue shopping button to shopping cart
}
if (!empty($page->go)) {
    if (!empty($page->dealerId) || !empty($page->dealerName) || !empty($page->categoryId) ||!empty($page->keyword)) {
        $listings = showresults($page->boxTypeId, $page->categoryId, $page->dealerName, $page->dealerId, $page->listingSince, $page->sort, $page->subCategoryId, $page->type, $page->keyword);

        $addToCartBTN = "<input class='button' type='submit' name='addToCart' value='Add To Cart'>\n";
    } else {
       echo $msg = "Please choose a search option";
    }

}

echo $page->header('Main Listings');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $addToCart, $addToCartBTN, $boxTypeId, $categoryId, $dealerId, $dealerName, $go, $keyword, $listingId, $listings, $listingSince,
           $search, $sort, $subCategoryId, $type, $UTILITY, $year, $uom;

    echo "      <div class='medium-blocks'><!--MEDIUM BLOCKS-->\n";
    echo "\n";
    echo "            <div class='block'><!--BLOCK-->\n";
    echo "                <h1>ADVERTISEMENT</h1>\n";
    echo "                    <p>ad info</p><!----block-->\n";
    echo "            </div>\n";
    echo "\n";
    echo "            <div class='block filters'><!--BLOCK-->\n";
    echo "                <form class='filter-group short' name ='listingSearch' id='' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "                    <ul>\n";
    echo "                        <li>\n";
    echo "                            <label>Box Type:</label>\n";
    echo boxTypeDDM($categoryId, $boxTypeId);;
    echo "                        </li>\n";
    echo "                        <li>\n";
    echo "                            <label>Unit:</label>".$uom."\n";
    echo "                            <select name='uom'>\n";
    echo "                            <option value=''>Boxes Only</option>\n";
    echo "                            <option value=''>Cases Only</option>\n";
    echo "                            <option value=''>Include Cases w/Boxes</option>\n";
    echo "                            </select>\n";
    echo "                        </li>\n";
    echo "                    </ul>\n";
    echo "                </form>\n";
    echo "                <button class='showhide_show' onclick='toggle()'>Show Filters</button>\n";
    echo "\n";
    echo "                </a>\n";
    echo "                <div id='detail-filters' class='hide' >\n";
    echo "                    <div class='filter-content'>\n";
    echo "                      <form name ='listingSearch' id='' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "                         <h3>Listing Filters</h3>\n";
    echo "                         <p>\n";
    echo "                           <label>Category:</label>".categoryDDM($categoryId)."<br />\n";
    echo "                           <label>Sub Category:</label>".subCategoryDDM($categoryId, $subCategoryId)."<br />\n";
    echo "                           <label>Box Type:</label>".boxTypeDDM($categoryId, $boxTypeId)."<br />\n";
    echo "                           <label>Dealer Name:</label>".dealersNameDDM()."<br />\n";
    echo "                           <label>Or Dealer Name:</label><input class='search-field' type='text' name='dealerName'><br />\n";
    echo "                           <label>Year(Keyword Search):</label><input class='search-field' type='text' name='keyword' value='".$keyword."'><br />\n";
    echo "                           <input class='button' type='submit' name='go' value='Go' id='submit'><br />\n";
    echo "                         </p>\n";
//echo "                         <label>Year</label><input maxlength='4' size='4' type='text' /><input class='wp-block-button__link wp-block-button is-style-fill' type='submit' value='Go' /><br />\n";
    echo "                      </form>\n";
    echo "                    </div>\n";
    echo "                    <p></p></div><p><a class='history-button' href='#'>Offer History</a><a class='history-button' href='#'>Price History</a>\n";
    echo "            </div><!----block-->\n";
    echo "\n";
    echo "        </div><!----medium blocks-->\n";
    echo "<form name='' id='' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo $addToCartBTN;

    echo "<br /><br />\n";
    if (isset($listings)) {
        echo " <table class='outer-table'>\n";
        echo "<caption>\n";
        echo "    <h4>Click on the price to make a counter offer.</h4>\n";
        echo "</caption>\n";
        echo "<thead>\n";
        echo "    <tr>\n";
        echo "        <th class='no-border-right multi-column'>\n";
        echo "          Buy Offers <a title='Add a Buy Offer' href='#'><i class='fas fa-plus-square fa-1x'></i></a>\n";
        echo "        </th>\n";
        echo "        <th class='no-border-right multi-column'>\n";
        echo "          Price Alert <a title='Add Price Alert' href='priceAlert.php?subCategoryId=".$subCategoryId."&boxTypeId=".$boxTypeId."&categoryId=".$categoryId."&uom=".$uom."'><i class='fas fa-plus-square fa-1x'></i></a>\n";
        echo "        </th>\n";
        echo "        <th class='no-border-left multi-column'>\n";
        echo "          Sell Offers <a title='Add a Sell Offer' href='#'><i class='fas fa-plus-square fa-1x'></i></a>\n";
        echo "        </th>\n";
        echo "    </tr>\n";
        echo "</thead>\n";
        echo "<tbody>\n";
        echo "    <tr>\n";
        echo "        <td class='double-table' colspan='2'>\n";
        echo "            <table>\n";
        echo "  <thead>\n";
        echo "    <tr>\n";
        echo "      <th align='left'>Offer</th>\n";
//        echo "      <th align='left'>Type</th>\n";
        echo "      <th align='left'></th>\n";
        echo "      <th class='number' title='Minimum QTY'>Min Qty</th>\n";
        echo "      <th class='number' title='Maximum QTY'>Max Qty</th>\n";
        echo "      <th class='number'>Notes</th>\n";
        echo "      <th class='number no-border-right'>Price</th>\n";
        echo "      <th></th>\n";
        echo "    </tr>\n";
        echo "  </thead>\n";
        echo "  <tbody>\n";
        foreach ($listings as $row) {
            if ($row['type'] == "Wanted") {
                echo "    <tr>\n";
                echo "      <td><input type='checkbox' name='listingId[]' value='".$row['listingid']."'</td>\n";
//                echo "      <td>".$row['type']."</td>\n";
                echo "      <td><a href='#'>".$row['username']."</a></td>\n";
                echo "      <td class='number'>".$row['minquantity']."</td>\n";
                echo "      <td class='number'>".$row['quantity']."</td>\n";
                echo "      <td><i class='fas fa-info-circle fa-1x' title='".$row['listingnotes']."'></i></td>\n";
                echo "      <td class='number'>".$row['dprice']."</td>\n";
                echo "    </tr>\n";
                }
        }
        echo "  </tbody>\n";
        echo "</table>\n";
        echo "        </td>\n";
        echo "        <td class='double-table' colspan='2'>\n";
        echo "            <table>\n";
        echo "                <thead>\n";
        echo "                    <tr>\n";
        echo "      <th align='left'>Offer</th>\n";
//        echo "      <th align='left'>Type</th>\n";
        echo "      <th align='left'></th>\n";
        echo "      <th class='number' title='Minimum QTY'>Min Qty</th>\n";
        echo "      <th class='number' title='Maximum QTY'>Max Qty</th>\n";
        echo "      <th class='number'>Notes</th>\n";
        echo "      <th class='number no-border-right'>Price</th>\n";
        echo "      <th></th>\n";
        echo "                    </tr>\n";
        echo "                </thead>\n";
        echo "                <tbody>\n";
        foreach ($listings as $row) {
            if ($row['type'] == "For Sale") {
                echo "    <tr>\n";
                echo "      <td><input type='checkbox' name='listingId[]' value='".$row['listingid']."'</td>\n";
//                echo "      <td>".$row['type']."</td>\n";
                echo "      <td><a href='#'>".$row['username']."</a></td>\n";
                echo "      <td class='number'>".$row['minquantity']."</td>\n";
                echo "      <td class='number'>".$row['quantity']."</td>\n";
                echo "      <td><i class='fas fa-info-circle fa-1x' title='".$row['listingnotes']."'></i></td>\n";
                echo "      <td class='number'>".$row['dprice']."</td>\n";
                echo "    </tr>\n";
            }
        }
        echo "                </tbody>\n";
        echo "            </table>\n";
        echo "        </td>\n";
        echo "    </tr>\n";
        echo " </tbody>\n";
        echo " </table>\n";
    }
    echo "</form>\n";
    echo "\n";
    echo "<script language = javascript>\n";
    echo getSubCategoriesJS();
    echo getBoxTypesJS();
    echo "</script>\n";
}



function getBoxTypesJS() {
    global $UTILITY;


    $rs = $UTILITY->getboxTypes(NULL);

    $output = "\n";
    $output .= "  var boxtypedata = [\n";
    foreach ($rs as $sc) {
         $output .= "    [".$sc["categoryid"].", '".addslashes($sc["categorytypename"])."', ".$sc["categorytypeid"].", ".$sc["boxtypeid"].", '".addslashes($sc["boxtypename"])."'],\n";
    }
    $output .= "    [0,'',0,0,'']\n";
    $output .= "  ];\n";

    return $output;
}

function getSubCategoriesJS() {
    global $UTILITY;

    $rs = $UTILITY->getSubCategories(NULL, 1);

    $output = "\n";
    $output .= "  var subcatdata = [\n";
    foreach ($rs as $sc) {
         $output .= "    [".$sc["categoryid"].", '".addslashes($sc["categoryname"])."', ".$sc["subcategoryid"].", '".addslashes($sc["subcategoryname"])."'],\n";
    }
    $output .= "    [0,'',0,'']\n";
    $output .= "  ];\n";

    return $output;
}

function categoryDDM($categoryId = NULL) {
    global $UTILITY;

    $onChange = " onchange = 'javascript: populateSubCat(this.value); populateBoxType(this.value);'";
    $output = "          ".getSelectDDM($UTILITY->getcategories(), "categoryId", "categoryid", "categoryname", NULL, $categoryId, "Select", 0, NULL, NULL, $onChange)."\n";

    return $output;
}

function dealersNameDDM() {

    global $UTILITY;
    $output = "          ".getSelectDDM($UTILITY->getDealers(), "dealerId", "userid", "username", NULL, NULL, "ALL")."\n";

    return $output;

}

function subCategoryDDM($categoryId = NULL, $subCategoryId = NULL) {
    global $UTILITY;

    if (!empty($categoryId)) {
        $rs = $UTILITY->getSubCategories($categoryId, 1);
    } else {
        $rs = array();
        $rs[] = array("subcategoryid" => 0, "subcategoryname" => "");
    }
    $output = "          ".getSelectDDM($rs, "subCategoryId", "subcategoryid", "subcategoryname", NULL, $subCategoryId)."\n";

    return $output;
}

function boxTypeDDM($categoryId = NULL, $boxTypeId = NULL) {
    global $UTILITY;

    if (!empty($categoryId)) {
        $rs = $UTILITY->getboxTypes($categoryId);
    } else {
        $rs = array();
        $rs[] = array("boxtypeid" => 0, "boxtypename" => "");
    }
    $output =  "          ".getSelectDDM($rs, "boxTypeId", "boxtypeid", "boxtypename", NULL, $boxTypeId)."\n";

    return $output;
}

function showresults($boxTypeId = NULL, $categoryId = NULL, $dealerName = NULL, $dealerId = NULL, $listingSince = NULL, $sort = NULL, $subCategoryId = NULL, $type = NULL, $keyword = NULL) {
    global $page;
    global $UTILITY;

    $sql = "
        SELECT lis.listingid, lis.minquantity, lis.quantity, lis.listingnotes, lis.dprice, lis.type, us.username
          FROM listings lis
          JOIN users us             ON us.userid = lis.userid
          JOIN categories cat       ON cat.categoryid = lis.categoryid
          JOIN subcategories sub    ON sub.subcategoryid = lis.subcategoryid
          JOIN boxtypes box         ON box.boxtypeid = lis.boxtypeid
         WHERE 1 = 1
    ";
    if (!empty($keyword)) {

        $sql .= "
                     AND (lower(cat.categoryname) LIKE '".$keyword."%'
                      OR lower(sub.subcategoryname) LIKE '".$keyword."%'
                      OR lower(lis.year) LIKE '".$keyword."%'
                      OR lower(box.boxtypename) LIKE '".$keyword."%')
                ";
    }
    $sql .= "       AND lis.status = 'OPEN' \n";

    if (!empty($categoryId)) {
        $sql .= "           AND lis.categoryId = ".$categoryId." \n";
    }
    if (!empty($dealerName)) {
        $sql .= "           AND us.username LIKE '".$dealerName."%' \n";
    }
    if (!empty($dealerId)) {
        $sql .= "           AND lis.userid = ".$dealerId." \n";
    }
    if (!empty($listingSince)) {
        $since = 60*60*24*$listingsince;
        $sql .= "           AND li.modifydate > todaytoint() - ".$since." \n";
    }
    if (!empty($type)) {
        if ($type != "both") {
            $sql .= "           AND lis.type = '".$type."'\n";
        }
    }
    if (!empty($subCategoryId)) {
        $sql .= "           AND lis.subcategoryId = ".$subCategoryId." \n";
    }
    if (!empty($boxTypeId)) {
            $sql .= "           AND lis.boxTypeId = ".$boxTypeId." \n";
    }
    if ($type == "Wanted") {
        $sql .= "         ORDER BY lis.type DESC, lis.dprice DESC";
    } else {
        $sql .= "         ORDER BY lis.type DESC, lis.dprice ASC";
    }

    $info = $page->db->sql_query_params($sql);

    return $info;

}


?>