<?php
require_once('templateMarket.class.php');

$page = new templateMarket(LOGIN, SHOWMSG);
$page->display_RightWidget = true;
require_once('listingfunctions.php');


$page->requireJS('scripts/listingPage.js');
$calendarJS = '
    $(function(){$(".usedatepicker").datepicker();});
';
$page->jsInit($calendarJS);

$listing = new listing();
$Cart = new shoppingcart($USER->userId);

$addToCart      = optional_param('addToCart', NULL, PARAM_TEXT);
$addToCartBTN   = optional_param('addToCartBTN', NULL, PARAM_TEXT);
$boxTypeId      = optional_param('boxtypeid', NULL, PARAM_INT);
$categoryId     = optional_param('categoryid', NULL, PARAM_INT);
$dealerName     = optional_param('dealerName', NULL, PARAM_TEXT);
$go             = optional_param('go', NULL, PARAM_TEXT);
$keyword        = optional_param('keyword', NULL, PARAM_TEXT);
$listingId      = optional_param('listingid', NULL, PARAM_TEXT);
$listingSince   = optional_param('listingSince', NULL, PARAM_INT);
$search         = optional_param('search', NULL, PARAM_TEXT);//array
$sort           = optional_param('sort', NULL, PARAM_TEXT);
$subCategoryId  = optional_param('subcategoryid', NULL, PARAM_INT);
$type           = optional_param('type', "both", PARAM_TEXT);
$uom            = optional_param('uom', "", PARAM_TEXT);
$uomId          = optional_param('uomid', "", PARAM_TEXT);
$year           = optional_param('year', NULL, PARAM_TEXT);

$productId      = optional_param('productid', NULL, PARAM_INT);
$showInactive   = optional_param('showinactive', 0, PARAM_INT);

$edit           = optional_param('edit', NULL, PARAM_TEXT);
$minquantity    = optional_param('minquantity', NULL, PARAM_INT);
$dprice         = optional_param('dprice', NULL, PARAM_TEXT);
$quantity       = optional_param('quantity', NULL, PARAM_INT);
$status         = optional_param('status', NULL, PARAM_TEXT);
$update         = optional_param('update', NULL, PARAM_TEXT);

$checkId        = optional_param('checkid', NULL, PARAM_INT);

$addCartClicked    = optional_param('addcart', NULL, PARAM_INT);

$referenceListingId = optional_param('referenceid', NULL, PARAM_INT);
if ($referenceListingId) {
    $sql = "
        SELECT l.*, c.categorytypeid
          FROM listings             l
          JOIN categories           c   ON  c.categoryid        = l.categoryid
                                        AND c.active            = 1
          JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                        AND sc.active           = 1
          JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                        AND bt.active           = 1
        WHERE l.listingid = ".$referenceListingId;

    if ($referenceListings = $page->db->sql_query($sql)) {
        $referenceListing = reset($referenceListings);
        $categoryId = $referenceListing['categoryid'];
        $subCategoryId = $referenceListing['subcategoryid'];
        $boxTypeId = $referenceListing['boxtypeid'];
        $year = $referenceListing['year'];
        $listingTypeId = $referenceListing['categorytypeid'];
    }
}

if ($categoryId) {
    setGlobalListingTypeId($categoryId);
    if ($listingTypeId == LISTING_TYPE_BLAST) {
        $blastId = "";
        if ($referenceListingId) {
            $blastid = "?listingid=".$referenceListingId;
        }
        header("location:blasts.php".$blastId);
    }
}

$year = (empty($year)) ? NULL : $year;

$categoryDescription    = "";
$subcategoryDescription = "";
$listings               = null;
$otherlistings          = null;
$picHLcost              = null;

$haveMatchingListings   = false;
$haveListingCriteria    = false;


//echo "addToCart:".$addToCart." listingTypeId:".$listingTypeId." categoryId:".$categoryId." subCategoryId:".$subCategoryId." boxTypeId:".$boxTypeId." year:".$year."<br />\n";
//echo "<pre>";var_dump($listingId);echo "</pre><br />\n";
if ((!empty($categoryId)) && (!empty($subCategoryId)) && (!empty($boxTypeId))) {
    if (($listingTypeId == LISTING_TYPE_GAMING) || (!empty($year))) {
        $haveListingCriteria = true;
    }
}

$myListings = NULL;

if ($haveListingCriteria) {
    $categoryDescription = $page->db->get_field_query("select categorydescription from categories where categoryid=".$categoryId);
    $subcategoryDescription = $page->db->get_field_query("select subcategorydescription from subcategories where subcategoryid=".$subCategoryId);
    $myListings = getListings(TRUE, $boxTypeId, $categoryId, NULL, NULL, NULL, $subCategoryId, NULL, NULL, $year, $uomId, NULL);
    if (!empty($update)) {
        updateListings();
    } else {
        if ($checkId && (! $page->user->isFactoryCost())) {
            $UTILITY->checkCollar($checkId, false);
        }
    }

    $listings = getListings(FALSE, $boxTypeId, $categoryId, $dealerName, $listingSince, $sort, $subCategoryId, $type, $keyword, $year, $uomId);
    if (is_array($listings) && (count($listings) > 0)) {
        $haveMatchingListings = true;
    }
    $otherlistings = getOtherListings($boxTypeId, $categoryId, $dealerName, $listingSince, $sort, $subCategoryId, $type, $keyword, $year, $uomId);
    if (is_array($otherlistings) && (count($otherlistings) > 0)) {
        $haveMatchingListings = true;
    }
    $picHLcost = getPicHighLowCost($boxTypeId, $categoryId, $dealerName, $listingSince, $sort, $subCategoryId, $type, $keyword, $year);
    $showSharedImages = userHasSharedImages($boxTypeId, $categoryId, $dealerName, $listingSince, $sort, $subCategoryId, $type, $keyword, $year);

    $gotOne = false;
    if (!empty($addToCart) && is_array($listingId)) {
        $i = 1;
        foreach ($listingId as $listId) {
            $l = new listing($listId);
            if ($Cart->addToCart($listId, $USER->userId, $l->listingUserId, $l->dprice, $l->quantity, $l->minQuantity, $l->categoryId, $l->subCategoryId, $l->boxtypeId, $l->listingNotes, $l->year, $USER->userId)) {
                $gotOne = true;
            }
            unset($l);

        } $i++;
        if ($gotOne) {
            $page->messages->addSuccessMsg("You have new item(s) in your <a href='shoppingCart.php' class='button'>Cart</a>");
        }
    }
    if ($addCartClicked) {
        $l = new listing($addCartClicked);
        if ($Cart->addToCart($addCartClicked, $USER->userId, $l->listingUserId, $l->dprice, $l->quantity, $l->minQuantity, $l->categoryId, $l->subCategoryId, $l->boxtypeId, $l->listingNotes, $l->year, $USER->userId)) {
            header("location:shoppingCart.php?pgsmsg=".URLEncode("You have a new item in your Cart"));
            //$page->messages->addSuccessMsg("You have new item(s) in your <a href='shoppingCart.php' class='button'>Cart</a>");
        }
        unset($l);
    }
    if (!empty($page->go)) {
        $page->messages->addErrorMsg("PAGE GO IS SET");
    }
}

$hasHistory = 0;
$hasPrices = 0;
$defaultFromDate = date('m/d/Y', strtotime('-30 days'));
if ($haveMatchingListings) {
    $hasHistory = hasOfferHistory($categoryId, $boxTypeId, $year, $subCategoryId, $uomId);
    $hasPrices = hasPriceHistory($categoryId, $boxTypeId, $year, $subCategoryId, NULL, $defaultFromDate, NULL, $uomId);
    $listingProducts = getListingProducts($categoryId, $boxTypeId, $year, $subCategoryId);

}
//echo "<pre>";var_dump($picHLcost);echo "</pre><br />\n";
echo $page->header('Main Listings');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $UTILITY, $categoryDescription, $subcategoryDescription, $listings, $otherlistings, $picHLcost, $haveMatchingListings;
    global $update, $addToCart, $addToCartBTN, $listingTypeId, $boxTypeId, $categoryId, $dealerName, $go, $keyword, $listingId, $listingSince,
           $search, $sort, $subCategoryId, $uomId, $type, $year, $uom, $edit, $showInactive;
    global $defaultFromDate, $hasHistory, $hasPrices, $myListings, $showSharedImages, $listingProducts, $productId;

    echo "      <div class='medium-blocks'><!--MEDIUM BLOCKS-->\n";
    if (empty($dealerName)) {
        echo "        <div class='block'><!--BLOCK-->\n";
        if ($haveMatchingListings) {
            echo "          <h1>".$year." ".$subcategoryDescription." ".$categoryDescription."</h1>\n";

            if (is_array($picHLcost) && (count($picHLcost) > 0)) {
                if (!empty($picHLcost[0]['picture'])) {
                    if ($picURL = $page->utility->getPrefixPublicImageURL($picHLcost[0]['picture'], THUMB150)) {
                        echo "            <img class='align-left' src='".$picURL."' alt='listing image' width='150px' height='150px'>\n";
                    }
                }

                echo "          <div style='float:left;'>\n";
                if (!empty($picHLcost[0]['cost']) && $picHLcost[0]['cost'] > 0.0) {
                    echo "            Factory Cost: <strong>".floatToMoney($picHLcost[0]['cost'])."</strong><br />\n";
                }
                if (!empty($picHLcost[0]['releasedate'])) {
                    echo "            Release Date: <strong>".date("F j, Y",$picHLcost[0]['releasedate'])."</strong><br />\n";
                }
                if ((!empty($picHLcost[0]['cost']) && $picHLcost[0]['cost'] > 0.0) ||
                    (!empty($picHLcost[0]['releasedate']))) {
                    echo "            Factory cost information supplied by <a href='productcalendar.php' target='_blank'><img src='/images/gts-fc.jpg'></a><br />\n";
                }
                echo "            Current High Bid: <strong>".floatToMoney($picHLcost[0]['highbuy'])."</strong><br/>\n";
                echo "            Current Low Ask: <strong>".floatToMoney($picHLcost[0]['lowsell'])."</strong>\n";
                if ($page->user->isAdmin() || $page->user->hasUserRightId(USERRIGHT_PRODUCT_ENTRY)) {
                    echo "            <br />\n";
                    echo "            <strong>Versions:</strong>\n";
                    if ($listingProducts && is_array($listingProducts) && (count($listingProducts) > 0)) {
                        $x = reset($listingProducts);
                        if (empty($x["exists"])) {
                            echo "            <a href='product.php?action=add&categoryid=".$categoryId."&subcategoryid=".$subCategoryId."&boxtypeid=".$boxTypeId."&year=".URLEncode($year)."' target='_blank' class=' fas fa-add'></a>";
                        } else {
                            echo "            <a href='productSKUs.php?categoryid=".$categoryId."&subcategoryid=".$subCategoryId."&boxtypeid=".$boxTypeId."&year=".URLEncode($year)."' target='_blank' class=' fas fa-edit'></a>";
                        }
                    } else {
                        echo "            <a href='product.php?action=add&categoryid=".$categoryId."&subcategoryid=".$subCategoryId."&boxtypeid=".$boxTypeId."&year=".URLEncode($year)."' target='_blank' class=' fas fa-add'></a>";
                    }
                }
                echo "<br />\n";
                if (!empty($listingProducts)) {
                    foreach ($listingProducts as $listingProduct) {
                        echo "            <div style='background-color:#FFFF00;'><b>UPC:</b> \n";
                        $upcs = "";
                        if (!empty($listingProduct["upcs"])) {
                            $UPCs = explode(",", $listingProduct["upcs"]);
                            foreach($UPCs as $upc) {
                                if (empty($upcs)) {
                                    $upcs .= "              <div style='display: inline;'>".$upc."</div>\n";
                                } else {
                                    $upcs .= "              <div style='padding-left:32px;'>".$upc."</div>\n";
                                }
                            }
                        }
                        if (empty($upcs)) {
//                            echo "------------";
                        } else {
                            echo $upcs;
                        }
                        if (!empty($upcs)) {
                            echo "              <div style='padding-left:32px;'>";
                        }
                        if (!empty($listingProduct['variation'])) {
                            echo " (".$listingProduct['variation'].") ";
                        }
                        if ($listingProduct['productnote']) {
                            echo " - ".$listingProduct['productnote'];
                        }
                        if (!empty($upcs)) {
                            echo "</div>\n";
                        }
                        echo "            </div><br /> <!-- UPC -->\n";
                    }

                }
                if ($showSharedImages) {
                    echo "            <br />\n";
                    echo "            <a href='sharedImages.php?subcategoryid=".$subCategoryId."&categoryid=".$categoryId."&boxtypeid=".$boxTypeId."&listingtypeid=".$listingTypeId."&year=".$year."' target='_blank' >Manage Shared Images</a><br />\n";
                }
                echo "          </div>\n";
            }
        } else {
            echo "<h3>No Matching Listing</h3><br />\n";
        }
        echo "        </div><!----BLOCK-->\n";
    }
    echo "\n";
    echo "        <div class='block filters'><!--BLOCK-->\n";
    $referrer = new ListingReferral();
    echo "<form id='sub' name='sub' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "  <input type='hidden' id='categoryid' name='categoryid' value='".$categoryId."' />\n";
    echo "  <input type='hidden' id='listingtypeid' name='listingtypeid' value='".$listingTypeId."' />\n";
    echo "  <input type='hidden' id='subcategoryid' name='subcategoryid' value='".$subCategoryId."' />\n";
    echo "  <input type='hidden' id='year' name='year' value='".$year."' />\n";
    echo "  <table>\n";
    echo "    <tr><th colspan=2>Filters</th></tr>\n";
    echo "    <tr><td>Box Type</td><td>".getBoxTypeDDM($categoryId, $subCategoryId, $year, $boxTypeId)."</td></tr>\n";
    echo "    <tr><td>UOM</td><td>".uomDDM($categoryId, $boxTypeId, $year, $subCategoryId, $uomId)."</td></tr>\n";
    echo "  </table>\n";
    echo $referrer->referralHiddens();
    echo "</form>\n";
    echo $referrer->referralLink();
    if ($hasHistory || $hasPrices) {
        echo "                    <p>";
        if ($hasHistory) {
            $historyURL = "offerhistory.php?categoryid=".$categoryId
                ."&boxtypeid=".$boxTypeId
                ."&year=".URLEncode($year)
                ."&subcategoryid=".$subCategoryId
                ."&uom=".$uomId;
            echo "<a class='history-button' href='".$historyURL."' target='_blank'>Offer History</a>";
        }
        if ($hasPrices) {
            $pricesURL = "priceguide.php?categoryid=".$categoryId
                ."&boxtypeid=".$boxTypeId
                ."&year=".URLEncode($year)
                ."&subcategoryid=".$subCategoryId
                ."&uom=".$uomId
                ."&fromdate=".$defaultFromDate;
            echo "<a class='history-button' href='".$pricesURL."' target='_blank'>Price History</a>\n";
        }
        echo "</p>\n";
    }
    echo "            </div><!----block-->\n";
    echo "\n";
    echo "        </div><!----medium blocks-->\n";
    echo "<form name='' id='' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "  <input type='hidden' name='listingtypeid' id='listingtypeid' value='".$listingTypeId."'>\n";
    echo "  <input type='hidden' id='categoryid' name='categoryid' value='".$categoryId."' />\n";
    echo "  <input type='hidden' id='subcategoryid' name='subcategoryid' value='".$subCategoryId."' />\n";
    echo "  <input type='hidden' id='year' name='year' value='".$year."' />\n";
    echo "  <input type='hidden' id='boxtypeid' name='boxtypeid' value='".$boxTypeId."' />\n";
    echo "  <input type='hidden' id='uom' name='uom' value='".$uomId."' />\n";
    if (!empty($listings)) {
        $legend = "<div style=float:left;'><strong>Action Legend:</strong><i class='fa-solid fa-cart-shopping'></i>Add To Cart|<i class='fas fa-truck'></i>Delivery Required By|<i class='fas fa-star'></i>Elite Dealer|<i class='fas fa-star' style='color:#00f;'></i>Above Standard Dealer|<i class='fas fa-money-bill-wave'></i>EFT Accepted</div>";
        $priceTitle = ($uomId == 'case') ? "Case Price" : "Box Price";
        if ($uomId == 'case') {
            $pricingCaption = "Bid / Ask Pricing is based on case pricing";
        } else {
            $pricingCaption = "Bid / Ask Pricing is based on box pricing";
        }
        echo "<table class='outer-table'>\n";
        echo "  <caption>".$legend." ".$pricingCaption."</caption>\n";
        echo "  <tbody>\n";
        echo "    <tr>\n";
        echo "      <td colspan=3 align=center>\n";
        if ($page->user->isVendor()) {
            echo "<a class='cancel-button' title='Add New Listing' href='listingCreate.php?categoryid=".$categoryId."&subcategoryid=".$subCategoryId."&boxtypeid=".$boxTypeId."&uom=".$uom."&year=".$year."' target='_blank'>Add New Listing</a>&nbsp;&nbsp;&nbsp;&nbsp;";
        }
        echo "<a class='cancel-button' title='Add Price Alert' href='priceAlert.php?subcategoryid=".$subCategoryId."&boxtypeid=".$boxTypeId."&categoryid=".$categoryId."&uom=".$uom."&listingtypeid=".$listingTypeId."&year=".$year."' target='_blank'>Add Price Alert</a>\n";
        echo "      </td>\n";
        echo "    </tr>\n";
        echo "    <tr style='vertical-align:top;'>\n";
        if ($page->user->canSell()) {
            echo "      <td>\n";
            echo "        <table>\n";
            echo "          <thead>\n";
            echo "            <tr class='addlisting'>\n";
            echo "              <th colspan='5'>Sell To";
            echo "              </th>\n";
            echo "            </tr>\n";
            echo "            <tr>\n";
            echo "              <th align='left'>&nbsp;</th>\n";
            echo "              <th align='left'>Dealer</th>\n";
            echo "              <th class='number' title='Maximum QTY'>Max Qty</th>\n";
            echo "              <th >Notes</th>\n";
            echo "              <th class='number no-border-right'>Bid Price</th>\n";
            echo "            </tr>\n";
            echo "          </thead>\n";
            echo "          <tbody>\n";
            $reverseOrder = "";
            // REGULAR WANTED LISTINGS
            foreach ($listings as $row) {
                if ($row['type'] == "Wanted") {
                    $rowWanted = "";
                    $rowWanted .= "            <tr>\n";
                    $rowWanted .= "              <td data-label='Offer'>";
                    $rowWanted .= offerActions($row);
                    $rowWanted .= "</td>\n";
                    if ($row['listinglogo']) {
                        $displayDealerName = "<img src='".$page->utility->getPrefixMemberImageURL($row['listinglogo'])."' title='".$row['username']."' width='75px' />";
                    } else {
                        $displayDealerName = $row['username'];
                    }
                    $rowWanted .= "              <td data-label='Dealer' style='white-space: nowrap;'><a href='dealerProfile.php?dealerId=".$row['dealerid']."' target='blank'>".$displayDealerName."</a></td>\n";
                    $rowWanted .= "              <td class='number' data-label='Max Qty'>".$row['quantity']."</td>\n";
                    $rowWanted .= "              <td data-label='Notes'>";

                    $casePricing = caseNotePricing($uomId, $row);
                    if (!empty($row['listingnotes'])) {
                        $rowWanted .= noteDisplay($row['listingnotes'], $casePricing);
                        //$rowWanted .= "<i class='fas fa-info-circle fa-1x' title='".$page->utility->alertFriendlyString($row['listingnotes'])."'  onClick=\"alert('".$page->utility->alertFriendlyString($row['listingnotes'])."');\"></i> ";
                        //$rowWanted .= getModalPanel($row['listingid'],str_replace("\\","",html_entity_decode($row['listingnotes'])));
                    } else {
                        $rowWanted .= $casePricing;
                    }
                    if (!empty($row['picture'])) {
                        if ($picURL = $page->utility->getPrefixListingImageURL($row['picture'])) {
                            $rowWanted .= "<a href='".$picURL."' target='_blank'><i class='fa-solid fa-camera'></i></a> ";
                        }
                    }
                    $rowWanted .= "</td>\n";
                    $displayPrice = ($uomId == 'case') ? floatToMoney($row['dprice']) : floatToMoney($row['boxprice']);
                    $rowWanted .= "              <td class='number' data-label='Box Price'>".$displayPrice."</td>\n";
                    $rowWanted .= "            </tr>\n";
                    $reverseOrder = $rowWanted.$reverseOrder;
                }
            }
            if (!empty($reverseOrder)) {
                echo $reverseOrder;
            }
            echo "          </tbody>\n";
            echo "        </table>\n";
            echo "      </td>\n";
            echo "      <td>&nbsp;</td>\n";
        }
        echo "      <td>\n";
        echo "        <table>\n";
        echo "          <thead>\n";
        echo "            <tr class='addlisting'>\n";
        echo "              <th colspan='5'>Buy From</th>\n";
        echo "            </tr>\n";
        echo "            <tr>\n";
        echo "              <th align='left'>&nbsp;</th>\n";
        echo "              <th align='left'>Dealer</th>\n";
        echo "              <th class='number' title='Maximum QTY'>Max Qty</th>\n";
        echo "              <th>Notes</th>\n";
        echo "              <th class='number no-border-right'>Ask Price</th>\n";
        echo "            </tr>\n";
        echo "          </thead>\n";
        echo "          <tbody>\n";
        // REGULAR FOR SALE LISTINGS
        foreach ($listings as $row) {
            if ($row['username'] != "COST") {
                if ($row['type'] == "For Sale") {
                    echo "            <tr>\n";
                    echo "              <td data-label='Offer'>";
                    echo offerActions($row);
                    echo "              </td>\n";
                    if ($row['listinglogo']) {
                        $displayDealerName = "<img src='".$page->utility->getPrefixMemberImageURL($row['listinglogo'])."' title='".$row['username']."' width='75px' />";
                    } else {
                        $displayDealerName = $row['username'];
                    }
                    echo "              <td data-label='Dealer' style='white-space: nowrap;'><a href='dealerProfile.php?dealerId=".$row['dealerid']."' target='blank'>".$displayDealerName."</a></td>\n";
                    echo "              <td data-label='Max Qty' class='number'>".$row['quantity']."</td>\n";
                    echo "              <td data-label='Notes'>";

                    $casePricing = caseNotePricing($uomId, $row);
                    if (!empty($row['listingnotes'])) {
                        echo noteDisplay($row['listingnotes'], $casePricing);
                    } else {
                        echo $casePricing;
                    }
                    if (!empty($row['picture'])) {
                        if ($picURL = $page->utility->getPrefixListingImageURL($row['picture'])) {
                            echo "<a href='".$picURL."' target='_blank'><i class='fa-solid fa-camera'></i></a> ";
                        }
                    }
                    echo "</td>\n";
                    $displayPrice = ($uomId == 'case') ? floatToMoney($row['dprice']) : floatToMoney($row['boxprice']);
                    echo "              <td data-label='Box Price' class='number'>".$displayPrice."</td>\n";
                    echo "            </tr>\n";
                }
            }
        }
        echo "          </tbody>\n";
        echo "        </table>\n";
        echo "      </td>\n";
        echo "    </tr>\n";
        echo "  </tbody>\n";
        echo "</table>\n";
    }
    if (!empty($otherlistings)) {
        $priceTitle = "Price";

        echo "<br /><br />\n";

        echo "<table class='outer-table'>\n";
        echo "  <thead>\n";
        echo "    <tr>\n";
        echo "      <th class='no-border-right multi-column'>Buy Offers";
        echo "</th>\n";
        echo "      <th class='no-border-right multi-column'>Other UOM</th>\n";
        echo "      <th class='no-border-left multi-column'>Sell Offers";
        echo "</th>\n";
        echo "    </tr>\n";
        echo "  </thead>\n";
        echo "  <tbody>\n";
        echo "    <tr>\n";
        echo "      <td class='double-table' colspan='2'>\n";
        echo "        <table>\n";
        echo "          <h2>Buy Offers</h2>\n";
        echo "          <thead>\n";
        echo "            <tr>\n";
        echo "              <th align='left'>Offer</th>\n";
        echo "              <th align='left'>Dealer</th>\n";
        echo "              <th class='number' title='Maximum QTY'>Max Qty</th>\n";
        echo "              <th>Notes</th>\n";
        echo "              <th class='number no-border-right'>".$priceTitle."</th>\n";
        echo "              <th></th>\n";
        echo "            </tr>\n";
        echo "          </thead>\n";
        echo "          <tbody>\n";
        $reverseOrder = "";
        // OTHER WANTED LISTINGS
        foreach ($otherlistings as $row) {
            if ($row['type'] == "Wanted") {
                $rowWanted = "";
                $rowWanted .= "            <tr>\n";
                $rowWanted .= "              <td data-label='Offer'>\n";
                $rowWanted .= offerActions($row);
                $rowWanted .= "              </td>\n";
                $rowWanted .= "              <td data-label='Dealer' style='white-space: nowrap;'><a href='dealerProfile.php?dealerId=".$row['dealerid']."' target='blank'>".$row['username']."</a></td>\n";
                $rowWanted .= "              <td data-label='Max Qty' class='number'>".$row['quantity']."</td>\n";
                $rowWanted .= "              <td data-label='Notes'>";
                if (!empty($row['listingnotes'])) {
                        $rowWanted .= noteDisplay($row['listingnotes']);
                }
                $rowWanted .= "</td>\n";
                $displayPrice = floatToMoney($row['dprice']);
                $rowWanted .= "              <td data-label='".$priceTitle."' class='number'>".$displayPrice."</td>\n";
                $rowWanted .= "            </tr>\n";
                $reverseOrder = $rowWanted.$reverseOrder;
            }
        }
        if (!empty($reverseOrder)) {
            echo $reverseOrder;
        }
        echo "          </tbody>\n";
        echo "        </table>\n";
        echo "      </td>\n";
        echo "      <td class='double-table' colspan='2'>\n";
        echo "        <h2>Sell Offers</h2>\n";
        echo "        <table>\n";
        echo "          <thead>\n";
        echo "            <tr>\n";
        echo "              <th align='left'>Offer</th>\n";
        echo "              <th align='left'>Dealer</th>\n";
        echo "              <th class='number' title='Maximum QTY'>Max Qty</th>\n";
        echo "              <th>Notes</th>\n";
        echo "              <th class='number no-border-right'>".$priceTitle."</th>\n";
        echo "              <th></th>\n";
        echo "            </tr>\n";
        echo "          </thead>\n";
        echo "          <tbody>\n";
        // OTHER FOR SALE LISTINGS
        foreach ($otherlistings as $row) {
            if ($row['username'] != "COST") {
                if ($row['type'] == "For Sale") {
                    echo "            <tr>\n";
                    echo "<td data-label='Offer'>";
                    echo offerActions($row);
                    echo "</td>\n";
                    echo "              <td data-label='Dealer' style='white-space: nowrap;'><a href='dealerProfile.php?dealerId=".$row['dealerid']."' target='blank'>".$row['username']."</a></td>\n";
                    echo "              <td data-label='Max Qty' class='number'>".$row['quantity']."</td>\n";
                    echo "              <td data-label='Notes'>";
                    if (!empty($row['listingnotes'])) {
                        echo noteDisplay($row['listingnotes']);
                        //echo "<i class='fas fa-info-circle fa-1x' title='".$page->utility->alertFriendlyString($row['listingnotes'])."' onClick=\"alert('".$page->utility->alertFriendlyString($row['listingnotes'])."');\"></i>";
                    }
                    echo "</td>\n";
                    $displayPrice = floatToMoney($row['dprice']);
                    echo "              <td data-label='".$priceTitle."' class='number'>".$displayPrice."</td>\n";
                    echo "            </tr>\n";
                }
            }
        }
        echo "          </tbody>\n";
        echo "        </table>\n";
        echo "      </td>\n";
        echo "    </tr>\n";
        echo "  </tbody>\n";
        echo "</table>\n";
    }
    echo "</form>\n";
    echo "\n";
    // EDITABLE MY LISTINGS
    if(!empty($myListings)) {
        echo "<form name ='edit' action='listing.php' method='post'>\n";
        echo "  <input type='hidden' name='listingtypeid' value='".$listingTypeId."' />\n";
        echo "  <input type='hidden' name='categoryid' value='".$categoryId."' />\n";
        echo "  <input type='hidden' name='subcategoryid' value='".$subCategoryId."' />\n";
        echo "  <input type='hidden' name='boxtypeid' value='".$boxTypeId."' />\n";
        echo "  <input type='hidden' name='uomid' value='".$uomId."' />\n";
        echo "  <input type='hidden' name='year' value='".$year."' />\n";
        echo offsetAnchor('mylistings');
        echo "  <table id='myListings'>\n";
        echo "    <caption>\n";
        echo "      <h4>Modify your matching offers below.</h4>\n";
        echo "    </caption>\n";
        echo "    <thead>\n";
        echo "      <tr>\n";
        echo "        <th>Listing ID</th>\n";
        echo "        <th>Buy / Sell</th>\n";
        echo "        <th>UOM</th>\n";
        echo "        <th>Max Qty</th>\n";
        echo "        <th>Price</th>\n";
        echo "        <th>Expires On</th>\n";
        echo "        <th>Deliver By</th>\n";
        echo "        <th>Active</th>\n";
        echo "      </tr>\n";
        echo "    </thead>\n";
        echo "    <tbody>\n";
        if (!empty($myListings)) {
            $haveInactive = false;
            foreach ($myListings as $l) {
                $listingId = $l['listingid'];
                $inactiverow = "";
                if ($l['status'] != 'OPEN') {
                    $haveInactive = true;
                    $inactiverow .= " class='inactivelisting'";
                    if (! $showInactive) {
                        $inactiverow .= " style='display:none;'";
                    }
                }
                $maxQty = $l['quantity'];
                $dprice = $l['dprice'];
                $status = $l['status'];
                $boxespercase = $l['boxespercase'];
                echo "      <tr".$inactiverow.">";
                echo "        <td data-label='Listing ID'>".$listingId."</td>\n";
                echo "        <td data-label='Buy / Sell'>".$l['type']."</td>\n";
                echo "        <td data-label='UOM'>".$l['uom']."</td>\n";
                echo "        <td data-label='Max Qty'>";
                echo "<input type='hidden' name='listingids[]' value='".$listingId."' />";
                echo "<input type='hidden' name='minquantity".$listingId."' id='minquantity".$listingId."' value='1' />";
                echo "<input type='hidden' name='boxespercase".$listingId."' id='boxespercase".$listingId."' value='".$l['boxespercase']."' />";
                echo "<input type='text' name='quantity".$listingId."' id='quantity".$listingId."' value='".$maxQty."' />";
                echo "</td>\n";
                echo "        <td data-label='Price'><input type='text' name='dprice".$listingId."' id='dprice".$listingId."' value='".floatTwoDecimal($dprice)."'></td>\n";
                if ($l['type'] == 'Wanted') {
                    echo "<td data-label='Expires On'><input type=text size=10 name='expireson".$listingId."' id='expireson".$listingId."' class='usedatepicker' value='".$l['expiresdt']."' />";
                    echo "<td data-label='Deliver By'><input type=text size=10 name='deliverby".$listingId."' id='deliverby".$listingId."' class='usedatepicker' value='".$l['deliverdt']."' />";
                } else {
                    echo "<td data-label='Expires On'>N/A<input type=hidden name='expireson".$listingId."' id='expireson".$listingId."' value='' />";
                    echo "<td data-label='Deliver By'>N/A<input type=hidden name='deliverby".$listingId."' id='deliverby".$listingId."' value='' />";
                }
                echo "        <td data-label='Active'><input type='checkbox' name='status".$listingId."' id='status".$listingId."' value='OPEN' ".checked("OPEN", $status)." ></td>\n";
                echo "      </tr>\n";
                echo "      <tr".$inactiverow.">";
                echo "<td>&nbsp;</td><td><strong>Notes:</td>";
                echo "<td colspan=6><textarea name='listingnotes".$listingId."' id='listingnotes".$listingId."' style='width:100%;' rows=1 title='Drag lower right corner to resize'>".$page->utility->inputFriendlyString($l['listingnotes'])."</textarea></td>";
                echo "</tr>\n";
            }
        }
        echo "    </tbody>\n";
        echo "  </table>\n";
        if ($haveInactive) {
            echo "  <input type='checkbox' name='showinactive' id='showinactive' value='1' ".checked("1", $showInactive)." onchange='toggleInactives();' />Show Inactive<br />\n";
        }
        echo "  <input type='submit' name='update' value='Save' />\n";
        echo "  <input type='submit' name='cancel' value='Cancel' />\n";
        echo "</form>\n";
    }
}
function noteDisplay($note, $casePricing=NULL) {
    global $page;

    $output = "";

    $safeString = $page->utility->alertFriendlyString($note);
    if (strlen($casePricing) > 0) {
        $output = $casePricing."<br />";
    }
    if (strlen($safeString) > 15) {
        $output .= substr($safeString, 0, 10);
        $output .= "...<i class='fas fa-plus-square fa-1x' title='More' onClick=\"alert('".$safeString."');\"></i>";
    } else {
        $output .= $safeString;
    }

    return $output;
}

function offerActions($listingData) {
    global $page;

    $output = "";
    $separator = " ";

    if ($listingData['dealerid'] == $page->user->userId) {
        $output .= "&nbsp;";
    } else {
        if ($listingData['iamblocked'] || $listingData['iblocked']) {
            $output .= "<i class='fa-solid fa-ban fa-sm' title='Blocked Dealer'></i>";
        } else {
            if ($listingData['isincart']) {
                $output .= "<i class='fa-solid fa-cart-shopping fa-sm' title='Already In Cart'></i>";
            } else {
                $output .= "<button type='submit' name='addcart' id='addcart".$listingData['listingid']."' value='".$listingData['listingid']."'><i class='fa-solid fa-cart-shopping' title='Add To Cart'></i></button>";
            }
        }
        //$separator = "<br />";
    }

    if ($listingData['iselite']) {
        $msg = "Elite Dealer";
        $output .= $separator."<i class='fas fa-star fa-2xs' title='".$msg."' onClick='alert(\"".$msg."\");' ></i>";
        //$separator = " ";
    }
    if ($listingData['isbluestar']) {
        $msg = "Above Standard Dealer";
        $output .= $separator."<i class='fas fa-star fa-2xs' style='color: #00f;' title='".$msg."' onClick='alert(\"".$msg."\");' ></i>";
        //$separator = " ";
    }
    if ($listingData['isverified']) {
        $msg = "Verified Dealer";
        $output .= $separator."<i class='fas fa-check fa-2xs' style='color: #090;' title='".$msg."' onClick='alert(\"".$msg."\");' ></i>";
        //$separator = " ";
    }
    if ($listingData['deliverby']) {
        $msg = "Required delivery by ".date('m/d/Y', $listingData['deliverby']);
        $output .= $separator."<i class='fas fa-truck fa-2xs' title='".$msg."' onClick='alert(\"".$msg."\");' ></i>";
        //$separator = " ";
    }
    if ($listingData['eftlister']) {
        $msg = "EFT Accepted";
        $output .= $separator."<i class='fas fa-money-bill-wave fa-2xs' title='".$msg."' onClick='alert(\"".$msg."\");' ></i>";
        //$separator = " ";
    }

    return $output;
}

function caseNotePricing($uomId, $listingData) {
    $note = "";

    if ($listingData['uom'] == 'case') {
        if ($uomId == 'case') {
            $note = "SEALED ".$listingData['boxespercase']."Bx Case";
        } else {
            $note = $listingData['boxespercase']." Bx Case @ ".floatToMoney($listingData['dprice']);
        }
    }

    return $note;
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

function dealersNameDDM() {

    global $UTILITY;
    $output = "          ".getSelectDDM($UTILITY->getDealers(), "dealerId", "userid", "username", NULL, NULL, "ALL")."\n";

    return $output;

}

function getListings($myListings = FALSE, $boxTypeId = NULL, $categoryId = NULL, $dealerName = NULL, $listingSince = NULL, $sort = NULL, $subCategoryId = NULL, $type = NULL, $keyword = NULL, $year, $uomId, $status='OPEN') {
    global $page;
    global $UTILITY;

    $dealerId = NULL;
    if ($myListings) {
        $dealerId = $page->user->userId;
    } else {
        if ($uomId == LISTING_UOMID_OTHER) {
            return NULL;
        }
    }

    $whereStatus = (empty($status)) ? "" : " AND lis.status='".$status."'";
    $typeWhere = ($page->user->canSell()) ? "" : " AND lis.type='For Sale'";

    $sql = "
        SELECT lis.listingid, lis.minquantity, lis.quantity, lis.listingnotes, lis.type, lis.categoryid, lis.subcategoryid, lis.boxtypeid
                ,lis.year, lis.status, lis.createdate, lis.uom, lis.boxespercase, lis.picture
                ,lu.userid as dealerid, lu.username, lui.listinglogo
                ,cat.categoryname, cat.categorydescription, sub.subcategoryname, sub.subcategorydescription
                ,box.boxtypename
                ,lis.dprice as dprice
                ,lis.boxprice
                ,CASE WHEN sc.shoppingcartid IS NULL THEN 0 ELSE 1 END AS isincart
                ,CASE WHEN ear.assignedrightsid IS NULL THEN 0 ELSE 1 END AS iselite
                ,CASE WHEN bar.assignedrightsid IS NOT NULL AND ear.assignedrightsid IS NULL THEN 1 ELSE 0 END AS isbluestar
                ,CASE WHEN vdar.assignedrightsid IS NULL THEN 0 ELSE 1 END AS isverified
                ,myblocks.blockedid as iblocked, blockme.blockedid as iamblocked
                ,carts.numcart
                ,lis.expireson, lis.deliverby
                , to_char(to_timestamp(dateof(expireson)),'mm/dd/yyyy') as expiresdt
                , to_char(to_timestamp(dateof(deliverby)),'mm/dd/yyyy') as deliverdt
                ,CASE WHEN eft.userid IS NULL THEN 0 ELSE 1 END AS eftlister
          FROM listings                 lis
          JOIN categories               cat ON  cat.categoryid          = lis.categoryid
                                            AND cat.active              = 1
          JOIN subcategories            sub ON  sub.subcategoryid       = lis.subcategoryid
                                            AND sub.active              = 1
          JOIN boxtypes                 box ON  box.boxtypeid           = lis.boxtypeid
                                            AND box.active              = 1
          JOIN users                    lu  ON  lu.userid               = lis.userid
          JOIN assignedrights           ar  on  ar.userid               = lis.userid
                                            AND ar.userrightid          = ".USERRIGHT_ENABLED."
          JOIN userinfo                 lui ON  lui.userid              = lis.userid
                                            AND lui.userclassid         = ".USERCLASS_VENDOR."
                                            AND ((lis.type='For Sale' AND lui.vacationsell=0)
                                                  OR
                                                (lis.type='Wanted' AND lui.vacationbuy=0))
          LEFT JOIN assignedrights      ear ON  ear.userid              = lu.userid
                                            AND ear.userrightid         = ".USERRIGHT_ELITE."
          LEFT JOIN assignedrights      bar ON  bar.userid              = lu.userid
                                            AND bar.userrightid         = ".USERRIGHT_BLUESTAR."
          LEFT JOIN assignedrights      stl ON  stl.userid              = lu.userid
                                            AND stl.userrightid         = ".USERRIGHT_STALE."
          LEFT JOIN assignedrights     vdar ON  vdar.userid             = lu.userid
                                            AND vdar.userrightid        = ".USERRIGHT_VERIFIED."
          LEFT JOIN preferredpayment    eft ON  eft.userid              = lu.userid
                                            AND eft.paymenttypeid       = ".PAYMENT_TYPE_ID_EFT."
                                            AND eft.transactiontype     = lis.type
          LEFT JOIN shoppingcart        sc  ON  sc.listingid            = lis.listingid
                                            AND sc.userid               = ".$page->user->userId."
          LEFT JOIN blockedmembers  myblocks ON myblocks.userid         = ".$page->user->userId."
                                            AND myblocks.blockeduserid  = lis.userid
          LEFT JOIN blockedmembers  blockme ON  blockme.userid          = lis.userid
                                            AND blockme.blockeduserid   = ".$page->user->userId."
          LEFT JOIN (
                SELECT sc.listingid, count(*) as numcart
                FROM shoppingcart sc
                GROUP BY sc.listingid
          ) carts ON carts.listingid=lis.listingid
         WHERE lu.username != '".FACTORYCOSTNAME."'
           AND stl.userid IS NULL
           ".$whereStatus."
           ".$typeWhere;

    if (!empty($categoryId)) {
        $sql .= "
           AND lis.categoryId = ".$categoryId;
    }
    if (!empty($subCategoryId)) {
        $sql .= "
           AND lis.subcategoryId = ".$subCategoryId;
    }
    if (!empty($boxTypeId)) {
        $sql .= "
           AND lis.boxTypeId = ".$boxTypeId;
    }
    if (!empty($year)) {
        $sql .= "
           AND lis.year = '".$year."'";
    }
    if (!empty($keyword)) {
        $sql .= "
         AND (lower(cat.categoryname) LIKE '".$keyword."%'
              OR lower(sub.subcategoryname) LIKE '".$keyword."%'
              OR lower(lis.year) LIKE '".$keyword."%'
              OR lower(box.boxtypename) LIKE '".$keyword."%')
        ";
    }
    if (!empty($dealerName)) {
        $sql .= "
           AND lu.username LIKE '".$dealerName."%'";
    }
    if (!empty($dealerId)) {
        $sql .= "
           AND lis.userid = ".$dealerId;
    }
    if (!empty($listingSince)) {
        $since = 60*60*24*$listingsince;
        $sql .= "
           AND lis.modifydate > todaytoint() - ".$since;
    }
    if (!empty($type)) {
        if ($type != "both") {
            $sql .= "
           AND lis.type = '".$type."'";
        }
    }
    if (empty($uomId)) {
        if (!$myListings) {
            $sql .= " AND (lis.uom='case' OR lis.uom='box')";
        }
    } else {
        $sql .= " AND lis.uom = '".$uomId."'";
    }
    if ($type == "Wanted") {
        $sql .= "
        ORDER BY boxprice DESC";
    } else {
        $sql .= "
        ORDER BY boxprice ASC";
    }

    //echo (($myListings)?"My ":"")."Listings:<pre>".$sql."</pre><br />\n";
    $info = $page->db->sql_query_params($sql);

    return $info;
}

function getOtherListings($boxTypeId = NULL, $categoryId = NULL, $dealerName = NULL, $listingSince = NULL, $sort = NULL, $subCategoryId = NULL, $type = NULL, $keyword = NULL, $year, $uomId, $status='OPEN') {
    global $page;
    global $UTILITY;

    if (! (empty($uomId) || ($uomId == LISTING_UOMID_OTHER))) {
        return NULL;
    }

    $whereStatus = " AND lis.status='".$status."'";
    if (empty($status)) {
        $whereStatus = "";
    }

    $sql = "
        SELECT lis.listingid, lis.minquantity, lis.quantity, lis.listingnotes, lis.type, lis.categoryid, lis.subcategoryid, lis.boxtypeid
               ,lis.year, lis.status, lis.createdate, lis.uom, lis.boxespercase
               ,lu.userid as dealerid, lu.username
               ,cat.categoryname, cat.categorydescription, sub.subcategoryname, sub.subcategorydescription
               ,box.boxtypename
               ,lis.dprice as dprice
               ,CASE WHEN ear.assignedrightsid IS NULL THEN 0 ELSE 1 END AS iselite
               ,CASE WHEN bar.assignedrightsid IS NOT NULL AND ear.assignedrightsid IS NULL THEN 1 ELSE 0 END AS isbluestar
               ,CASE WHEN vdar.assignedrightsid IS NULL THEN 0 ELSE 1 END AS isverified
               ,CASE
                    WHEN uom='case' THEN lis.dprice / lis.boxespercase
                    ELSE lis.dprice
                END AS boxprice
                ,lis.expireson, lis.deliverby
               ,myblocks.blockedid as iblocked, blockme.blockedid as iamblocked
               ,CASE WHEN sc.shoppingcartid IS NULL THEN 0 ELSE 1 END AS isincart
               ,CASE WHEN eft.userid IS NULL THEN 0 ELSE 1 END AS eftlister
          FROM listings                 lis
          JOIN categories               cat ON  cat.categoryid          = lis.categoryid
                                            AND cat.active              = 1
          JOIN subcategories            sub ON  sub.subcategoryid       = lis.subcategoryid
                                            AND sub.active              = 1
          JOIN boxtypes                 box ON  box.boxtypeid           = lis.boxtypeid
                                            AND box.active              = 1
          JOIN users                    lu  ON  lu.userid               = lis.userid
          JOIN assignedrights           ar  on  ar.userid               = lis.userid
                                            AND ar.userrightid          = ".USERRIGHT_ENABLED."
          JOIN userinfo                 lui ON  lui.userid              = lis.userid
                                            AND lui.userclassid         = ".USERCLASS_VENDOR."
                                            AND ((lis.type='For Sale' AND lui.vacationsell=0)
                                                  OR
                                                (lis.type='Wanted' AND lui.vacationbuy=0))
          LEFT JOIN assignedrights      ear ON  ear.userid              = lu.userid
                                            AND ear.userrightid         = ".USERRIGHT_BLUESTAR."
          LEFT JOIN assignedrights      bar ON  bar.userid              = lu.userid
                                            AND bar.userrightid         = ".USERRIGHT_ELITE."
          LEFT JOIN assignedrights      stl ON  stl.userid              = lu.userid
                                            AND stl.userrightid         = ".USERRIGHT_STALE."
          LEFT JOIN assignedrights     vdar ON  vdar.userid             = lu.userid
                                            AND vdar.userrightid        = ".USERRIGHT_VERIFIED."
          LEFT JOIN blockedmembers  myblocks ON myblocks.userid         = ".$page->user->userId."
                                            AND myblocks.blockeduserid  = lis.userid
          LEFT JOIN blockedmembers  blockme ON  blockme.userid          = lis.userid
                                            AND blockme.blockeduserid   = ".$page->user->userId."
          LEFT JOIN preferredpayment    eft ON  eft.userid              = lis.userid
                                            AND eft.paymenttypeid       = ".PAYMENT_TYPE_ID_EFT."
                                            AND eft.transactiontype     = lis.type
          LEFT JOIN shoppingcart        sc  ON  sc.listingid            = lis.listingid
                                            AND sc.userid               = ".$page->user->userId."
         WHERE lu.username  != '".FACTORYCOSTNAME."'
           AND lis.uom      = '".LISTING_UOMID_OTHER."'
           AND stl.userid IS NULL
           ".$whereStatus;

    if (!empty($categoryId)) {
        $sql .= "
           AND lis.categoryId = ".$categoryId;
    }
    if (!empty($subCategoryId)) {
        $sql .= "
           AND lis.subcategoryId = ".$subCategoryId;
    }
    if (!empty($boxTypeId)) {
        $sql .= "
           AND lis.boxTypeId = ".$boxTypeId;
    }
    if (!empty($year)) {
        $sql .= "
           AND lis.year = '".$year."'";
    }
    if (!empty($keyword)) {
        $sql .= "
         AND (lower(cat.categoryname) LIKE '".$keyword."%'
              OR lower(sub.subcategoryname) LIKE '".$keyword."%'
              OR lower(lis.year) LIKE '".$keyword."%'
              OR lower(box.boxtypename) LIKE '".$keyword."%')
        ";
    }
    if (!empty($dealerName)) {
        $sql .= "
           AND lu.username LIKE '".$dealerName."%'";
    }
    if (!empty($listingSince)) {
        $since = 60*60*24*$listingsince;
        $sql .= "
           AND lis.modifydate > todaytoint() - ".$since;
    }
    if (!empty($type)) {
        if ($type != "both") {
            $sql .= "
           AND lis.type = '".$type."'";
        }
    }
    if ($type == "Wanted") {
        $sql .= "
        ORDER BY boxprice DESC";
    } else {
        $sql .= "
        ORDER BY boxprice ASC";
    }

    //echo "Other Listings:<pre>".$sql."</pre><br />\n";
    $info = $page->db->sql_query_params($sql);

    return $info;
}

function updateListings() {
    global $page, $myListings;

    $success = true;

    $updatedListings = false;

    if (scrapeMyListings()) {
        foreach ($myListings as &$listing) {
            $listingId = $listing['listingid'];
            //echo "Scraped listing:<br />\n<pre>";var_dump($listing);echo "</pre><br />\n";

            if ($listing['updated']) {
                //echo "Save Listingid ".$listingId."<br />\n";
                if (!$updatedListings) {
                    $page->db->sql_begin_trans();
                    $updatedListings = true;
                }
                $sql = "UPDATE listings SET minquantity = :minquantity,
                                        quantity     = :quantity,
                                        dprice       = :dprice,
                                        boxprice     = :boxprice,
                                        listingnotes = :listingnotes,
                                        expireson    = :expireson,
                                        deliverby    = :deliverby,
                                        status       = :status,
                                        modifydate   = :modifydate,
                                        modifiedby   = :modifiedby
                     WHERE listingid = :listingid";
                $params = array();
                $params['listingid']        = $listing['listingid'];
                $params['minquantity']      = $listing['minquantity'];
                $params['quantity']         = $listing['quantity'];
                $params['dprice']           = $listing['dprice'];
                $params['boxprice']         = $listing['boxprice'];
                $params['listingnotes']     = $listing['listingnotes'];
                $params['expireson']        = $listing['expireson'];
                $params['deliverby']        = $listing['deliverby'];
                $params['status']           = $listing['status'];
                $params['modifydate']       = $listing['modifydate'];
                $params['modifiedby']       = $listing['modifiedby'];
                $result = $page->db->sql_execute_params($sql, $params);
                if (!empty($result)) {
                    if (! $page->user->isFactoryCost()) {
                        $page->utility->checkCollar($listingId);
                    }
                    if ($listing['status'] == 'CLOSED') {
                        if ($listing['numcart'] > 0) {
                            $sql = "DELETE FROM shoppingcart WHERE listingid=".$listing['listingid'];
                            if (! $result = $page->db->sql_execute($sql)) {
                                $page->messages->addWarningMsg("Unable to remove item ".$listing['listingid']." from shopping carts.");
                            }
                        }
                    }
                } else {
                    $page->messages->addErrorMsg("Error updating listings.");
                    $success = false;
                    break;
                }
            }
        }
        if ($updatedListings) {
            if ($success) {
                $page->db->sql_commit_trans();
                $page->messages->addSuccessMsg("Successfully updated your listings.");
            } else {
                $page->db->sql_rollback_trans();
            }
        }
    }

    return $success;
}

function scrapeMyListings() {
    global $page, $myListings;

    $success = true;
    $updatedListings = false;

    $newModifyDate = time();
    if (is_array($myListings) && (count($myListings) > 0)) {
        foreach ($myListings as &$listing) {
            $listingId = $listing['listingid'];
            $maxQty = optional_param('quantity'.$listing['listingid'], NULL, PARAM_INT);
            if ($maxQty < 1) {
                $page->messages->addErrorMsg("Error editing listing. Max quantity must be greater than 0.");
                $success = false;
            }
            $dprice = optional_param('dprice'.$listing['listingid'], NULL, PARAM_INT);
            if (! ($dprice > 0)) {
                $page->messages->addErrorMsg("Error editing listing. Price must be greater than 0.");
                $success = false;
            }
            $minQty = optional_param('minquantity'.$listingId, NULL, PARAM_INT);
            $maxQty = optional_param('quantity'.$listingId, NULL, PARAM_INT);
            $dprice = optional_param('dprice'.$listingId, NULL, PARAM_NUM_NO_COMMA);
            $boxespercase = optional_param('boxespercase'.$listingId, 1, PARAM_INT);
            $boxprice = $dprice / $boxespercase;
            $listingNotes = optional_param('listingnotes'.$listingId, NULL, PARAM_TEXT);
            $status = optional_param('status'.$listingId, NULL, PARAM_TEXT);
            if ($status != "OPEN") {
                $status = "CLOSED";
            }

            $newDeliverBy = optional_param("deliverby".$listingId, NULL, PARAM_TEXT);
            $newExpiresOn = optional_param("expireson".$listingId, NULL, PARAM_TEXT);


            if ( ! (($maxQty == $listing['quantity'])
            &&  ($dprice == $listing['dprice'])
            &&  ($listingNotes == $listing['listingnotes'])
            &&  ($newExpiresOn == $listing['expiresdt'])
            &&  ($newDeliverBy == $listing['deliverdt'])
            &&  ($status == $listing['status']))) {
                $tomorrowMorning = strtotime("tomorrow");
                $days180 = strtotime("today + 181 days")-1;
                $expiresDateTime = null;
                $deliverDateTime = null;
                if ($listing['type'] != 'Wanted') {
                    if ($newDeliverBy) {
                        $newDeliverBy = null;
                        $page->messages->addWarningMsg("Deliver By date ignored for For Sale listing ".$listingId.".");
                    }
                    if ($newExpiresOn) {
                        $newExpiresOn = null;
                        $page->messages->addWarningMsg("Expires On date ignored for For Sale listing ".$listingId.".");
                    }
                }
                if ($listing['type'] == 'Wanted') {
                    if (! ($newExpiresOn)) {
                        $newExpiresOn = date('m/d/Y', $days180);
                        $page->messages->addInfoMsg("Expires On date for listing ".$listingId.", set to default of 180 days (".$newExpiresOn.").");
                    }
                }
                if ($newExpiresOn) {
                    $expiresDateTime = strtotime($newExpiresOn." 23:59:59");
                    if ($expiresDateTime) {
                        $listing['expireson'] = $expiresDateTime;
                        if ($expiresDateTime < $tomorrowMorning) {
                            if ($status != 'CLOSED') {
                                $page->messages->addErrorMsg("Expires On date must be at least 1 day in the future for listing".$listingId.".");
                                $success = false;
                            }
                        }
                    } else {
                        $page->messages->addErrorMsg("Invalid Expires On date for listing".$listingId.".");
                        $success = false;
                    }
                } else {
                    $listing['expireson'] = null;
                }

                if ($newDeliverBy) {
                    $deliverDateTime = strtotime($newDeliverBy." 23:59:59");
                    if ($deliverDateTime) {
                        $listing['deliverby'] = $deliverDateTime;
                    } else {
                        $page->messages->addErrorMsg("Invalid Deliver By date for listing".$listingId.".");
                        $success = false;
                    }
                } else {
                    $listing['deliverby'] = null;
                }
                if ($expiresDateTime && $deliverDateTime) {
                    if ($deliverDateTime < $expiresDateTime) {
                        if ($status != 'CLOSED') {
                            $page->messages->addErrorMsg("Deliver By date must be greater than or equal to Expires On date for listing ".$listingId.".");
                            $success = false;
                        }
                    }
                }

                //echo "Listingid ".$listingId." updated<br />\n";
                $updatedListings = true;
                $listing['updated'] = true;
                $listing['status'] = $status;
                $listing['quantity'] = $maxQty;
                $listing['minquantity'] = $minQty;
                $listing['dprice'] = round($dprice,2);
                $listing['boxprice'] = round($boxprice,2);
                $listing['listingnotes'] = $listingNotes;
                $listing['expiresdt'] = $newExpiresOn;
                $listing['deliverdt'] = $newDeliverBy;
                $listing['modifydate'] = $newModifyDate;
                $listing['modifiedby'] = $page->user->username;
            } else {
                $listing['updated'] = false;
            }
        }
    }
    if (! $updatedListings) {
        $success = false;
    }

    return $success;
}

function userHasSharedImages($boxTypeId = NULL, $categoryId = NULL, $dealerName = NULL, $listingSince = NULL, $sort = NULL, $subCategoryId = NULL, $type = NULL, $keyword = NULL, $year) {
    global $page;

    $hasImages = 0;

    $andUserId = ($page->user->isAdmin()) ? "" : "AND l.userid=".$page->user->userId;
    $andListingYear = (empty($year)) ? " AND l.year IS NULL " : " AND l.year = '".$year."' ";
    $sql = "
        SELECT count(*)
          FROM sharedimages l
         WHERE l.categoryId         = ".$categoryId.$andListingYear.$andUserId."
           AND l.subcategoryId      = ".$subCategoryId."
           AND l.boxTypeId          = ".$boxTypeId."
           AND l.picture            IS NOT NULL
           AND l.uom IN ('box', 'case')
    ";
    $hasImages = $page->db->get_field_query($sql);

    return $hasImages;
}

function getPicHighLowCost($boxTypeId = NULL, $categoryId = NULL, $dealerName = NULL, $listingSince = NULL, $sort = NULL, $subCategoryId = NULL, $type = NULL, $keyword = NULL, $year) {
    global $page;

    $factoryCostID = $page->utility->getDealerId(FACTORYCOSTNAME);
    $random = rand();

    $sql = "DROP TABLE IF EXISTS tmp_high_buy_".$random;
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_low_sell_".$random;
    $page->queries->AddQuery($sql);

    $andListingYear = (empty($year)) ? " AND l.year IS NULL " : " AND l.year = '".$year."' ";
    $sql = "
        CREATE TEMPORARY TABLE tmp_high_buy_".$random." AS
        SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year, MAX(l.boxprice) AS highbuy
          FROM listings             l
          JOIN userinfo             lui ON  lui.userid      = l.userid
                                        AND lui.userclassid = ".USERCLASS_VENDOR."
                                        AND lui.vacationbuy = 0
          LEFT JOIN assignedrights  stl ON  stl.userid      = l.userid
                                        AND stl.userrightid = ".USERRIGHT_STALE."
         WHERE l.categoryid         = ".$categoryId.$andListingYear."
           AND l.subcategoryId      = ".$subCategoryId."
           AND l.type               = 'Wanted'
           AND l.boxtypeid          = ".$boxTypeId."
           AND l.uom IN ('box', 'case')
           AND l.status             = 'OPEN'
           AND l.userid             <> ".$factoryCostID."
           AND stl.userid IS NULL
        GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
        ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_low_sell_".$random." AS
        SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year, MIN(l.boxprice) AS lowsell
          FROM listings             l
          JOIN userinfo             lui ON  lui.userid       = l.userid
                                        AND lui.userclassid  = ".USERCLASS_VENDOR."
                                        AND lui.vacationsell = 0
          LEFT JOIN assignedrights  stl ON  stl.userid      = l.userid
                                        AND stl.userrightid = ".USERRIGHT_STALE."
         WHERE l.categoryid         = ".$categoryId.$andListingYear."
           AND l.subcategoryId      = ".$subCategoryId."
           AND l.type               = 'For Sale'
           AND l.boxtypeid          = ".$boxTypeId."
           AND l.uom IN ('box', 'case')
           AND l.status             = 'OPEN'
           AND l.userid             <> ".$factoryCostID."
           AND stl.userid IS NULL
        GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
        ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
    ";
    $page->queries->AddQuery($sql);

//foreach($page->queries->sqls as $sql) {
//    echo "<pre>".$sql.";</pre>";
//}

    $page->queries->ProcessQueries();

    $andBuyYear = (empty($year)) ? " AND hb.year IS NULL " : " AND hb.year = l.year ";
    $andSellYear = (empty($year)) ? " AND ls.year IS NULL " : " AND ls.year = l.year ";
    $sql = "
        SELECT hb.highbuy, ls.lowsell, p.factorycost AS cost, p.releasedate, p.picture
          FROM listings             l
          JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
          JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
          JOIN products             p   ON  p.categoryid        = l.categoryid
                                        AND p.subcategoryid     = l.subcategoryid
                                        AND p.boxtypeid         = l.boxtypeid
                                        AND isnull(p.year, '1') = isnull(l.year, '1')
          LEFT JOIN tmp_high_buy_".$random."
                                    hb  ON  hb.subcategoryid    = l.subcategoryid
                                        AND hb.boxtypeid        = l.boxtypeid
                                        AND hb.categoryid       = l.categoryid
                                       ".$andBuyYear."
          LEFT JOIN tmp_low_sell_".$random."
                                    ls  ON  ls.subcategoryid    = l.subcategoryid
                                        AND ls.boxtypeid        = l.boxtypeid
                                        AND ls.categoryid       = l.categoryid
                                       ".$andSellYear."
         WHERE l.categoryid         = ".$categoryId."
           AND l.subcategoryid      = ".$subCategoryId."
           AND l.boxtypeid          = ".$boxTypeId."
           AND l.uom IN ('box', 'case')
          ".$andListingYear."
         LIMIT 1
    ";

//    echo "<pre>".$sql."</pre>\n";
    $info = $page->db->sql_query_params($sql);

    unset($page->queries);
    $page->queries = new DBQueries("Factory Info cleanup");

    $sql = "DROP TABLE IF EXISTS tmp_high_buy_".$random;
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_low_sell_".$random;
    $page->queries->AddQuery($sql);
    $process = $page->queries->ProcessQueries();

    return $info;
}

function checked($check, $checked) {
    if ($check == $checked) {
        $data = "checked='checked'";
    } else {
        $data = "";
    }

    return $data;
}

function hasOfferHistory($categoryId, $boxTypeId, $year, $subCatId, $uom) {
    global $page, $listingTypeId;

    $returnData = null;

    if (!empty($categoryId) && (($listingTypeId == LISTING_TYPE_GAMING) || (!empty($year)))) {
        $boxTypeJoin = (!empty($boxTypeId)) ? "AND oh.boxtypeid      = ".$boxTypeId : "";
        $subcatJoin = (!empty($subCatId)) ? "AND oh.subcategoryid      = ".$subCatId : "";
        $uomWhere = (($uom == 'case') || ($uom == 'box')) ? " AND oh.uom = '".$uom."' " : "";
        $yearWhere = (!empty($year)) ? " AND oh.year='".$year."' " : "";
        $sql = "
            SELECT count(*) as numhistory
            FROM offer_history oh
            JOIN categories c ON c.categoryid=oh.categoryid AND c.active=1
            JOIN boxtypes bt ON bt.boxtypeid=oh.boxtypeid AND bt.active=1
            JOIN subcategories sc ON sc.subcategoryid=oh.subcategoryid AND sc.active=1
            WHERE oh.categoryid         = ".$categoryId."
                    ".$yearWhere."
                    ".$boxTypeJoin."
                    ".$subcatJoin."
                    ".$uomWhere;
        //echo "hasOfferHistory SQL:<br /><pre>".$sql."</pre><br />\n";
        $returnData = $page->db->get_field_query($sql);
    }

    return $returnData;
}

function hasPriceHistory($categoryId, $boxTypeId, $year, $subCatId, $transType, $fromDateTime, $toDateTime, $uom) {
    global $page, $listingTypeId;

    $returnData = null;

    if (!empty($categoryId) && (($listingTypeId == LISTING_TYPE_GAMING) || (!empty($year)))) {
        $boxTypeJoin = (!empty($boxTypeId)) ? "AND pg.boxtypeid      = ".$boxTypeId : "";
        $subcatJoin = (!empty($subCatId)) ? "AND pg.subcategoryid      = ".$subCatId : "";
        $fromDateWhere = ($fromDateTime) ? " AND pg.pgdate >= ".$fromDateTime." " : "";
        $toDateWhere = ($toDateTime) ? " AND pg.pgdate <= ".$toDateTime." " : "";
        $yearWhere = (!empty($year)) ? " AND pg.year='".$year."' " : "";
        $sql = "
            SELECT count(*) as numprice
            FROM price_guide pg
            JOIN categories c ON c.categoryid=pg.categoryid AND c.active=1
            JOIN boxtypes bt ON bt.boxtypeid=pg.boxtypeid AND bt.active=1
            JOIN subcategories sc ON sc.subcategoryid=pg.subcategoryid AND sc.active=1
            WHERE pg.categoryid         = ".$categoryId."
                    ".$yearWhere."
                    ".$boxTypeJoin."
                    ".$subcatJoin."
                    ".$fromDateWhere."
                    ".$toDateWhere;
        //echo "hasPriceHistory SQL:<br /><pre>".$sql."</pre><br />\n";
        $returnData = $page->db->get_field_query($sql);
    }

    return $returnData;
}

function getBoxTypeDDM($categoryId, $subCategoryId, $year, $boxTypeId = NULL) {
    global $page, $listingTypeId;

    if ((!empty($categoryId)) && (!empty($subCategoryId)) && ((($listingTypeId == LISTING_TYPE_GAMING) || (!empty($year))))) {
        if ($listingTypeId == LISTING_TYPE_GAMING) {
            $year = NULL;
        }
        $rs = $page->utility->getProductBoxTypesVariations($categoryId, $subCategoryId, $year);
        $onChange = " onchange = \"$('#uomid').val('');$('#productid').val('');submit();\"";
        $output = "          ".getSelectDDM($rs, "boxtypeid", "boxtypeid", "boxtypename2", NULL, $boxTypeId, NULL, 0, NULL, NULL, $onChange)."\n";
    } else {
        $output = "<input type='hidden' name='boxtypeid' id='boxtypeid' value='' />\n";
    }

    return $output;
}

function getProductDDM($categoryId, $subCategoryId, $year, $boxTypeId, $productId) {
    global $page, $listingTypeId;

    if ((!empty($categoryId)) && (!empty($subCategoryId)) && ((($listingTypeId == LISTING_TYPE_GAMING) || (!empty($year))))) {
        if ($listingTypeId == LISTING_TYPE_GAMING) {
            $year = NULL;
        }
        $rs = $page->utility->getListingProducts($categoryId, $year, $subCategoryId, $boxTypeId);
        $onChange = " onchange = 'submit();'";
        $output = "          ".getSelectDDM($rs, "productid", "productid", "productnamecnt", NULL, $productId, "All", NULL, NULL, NULL, $onChange)."\n";
    } else {
        $output = "<input type='hidden' name='productid' id='productid' value='' />\n";
    }

    return $output;
}

function getModalPanel($modalId, $modalContent) {
    $output = "";
    // Get the modal
    $output = "";
    $output .= "<a href='#' id='ModalLink".$modalId."' title='Click to view notes'><i class='fas fa-info-circle fa-1x'></i></a>\n";
    $output .= "<div id='myModal".$modalId."' class='modal'>\n";
    $output .= "  <!-- Modal content -->\n";
    $output .= "  <div class='modal-content'>\n";
    $output .= "      <span class='close".$modalId."'><i class='fa-solid fa-circle-xmark'></i></span>\n";
    $output .= "    ".$modalContent."\n";
    $output .= "  </div>\n";
    $output .= "</div>\n";
    $output .="<script>\n";
    $output .= "  var modal".$modalId." = document.getElementById('myModal'+'".$modalId."');\n";

    // Get the link that opens the modal
    $output .= "  var link".$modalId." = document.getElementById('ModalLink'+'".$modalId."');\n";

    // Get the <span> element that closes the modal
    $output .= "  var span".$modalId." = document.getElementsByClassName('close'+'".$modalId."')[0];\n";

    // When the user clicks on the button, open the modal
    $output .= "  link".$modalId.".onclick = function() {\n";
    $output .= "    console.log(link".$modalId.");";
    $output .= "    modal".$modalId.".style.display = \"block\";\n";
    $output .= "    return false;\n";
    $output .= "  }\n";

    // When the user clicks on <span> (x), close the modal
    $output .= "  span".$modalId.".onclick = function() {\n";
    $output .= "    modal".$modalId.".style.display = \"none\";\n";
    $output .= "}\n";

    // When the user clicks anywhere outside of the modal, close it
    $output .= "  window.onclick = function(event) {\n";
    $output .= "    if (event.target == modal".$modalId.") {\n";
    $output .= "      modal".$modalId.".style.display = 'none';\n";
    $output .= "    }\n";
    $output .= "  }\n";
    $output .= "</script>\n";
    return $output;
}

function getListingProducts($categoryId, $boxTypeId, $year, $subCategoryId) {
    global $page;

    $yr = (empty($year)) ? "NULL" : "'".$year."'";

    $sql = "
        SELECT p.productid, p.variation, p.productnote, upc.upcs,
               case when upc.productid IS NOT NULL then 1
                    else 0 end as exists
          FROM products             p
          LEFT JOIN (
            SELECT pr.productid, array_to_string(array_agg(pu.upc), ',') as upcs
              FROM products         pr
              LEFT JOIN product_upc pu  ON  pu.productid    = pr.productid
             WHERE pr.active            = 1
               AND pr.categoryid        = ".$categoryId."
               AND pr.subcategoryid     = ".$subCategoryId."
               AND pr.boxtypeid         = ".$boxTypeId."
               AND isnull(pr.year, '1') = isnull(".$yr.", '1')
            GROUP BY pr.productid
                     )              upc ON  upc.productid           = p.productid

         WHERE p.active             = 1
           AND p.categoryid         = ".$categoryId."
           AND p.subcategoryid      = ".$subCategoryId."
           AND p.boxtypeid          = ".$boxTypeId."
           AND isnull(p.year, '1')  = isnull(".$yr.", '1')
        ORDER BY p.variation COLLATE \"POSIX\"
    ";

//    echo "<pre>".$sql."</pre>\n";
    $products = $page->db->sql_query($sql);

    return $products;
}
?>