<?php
require_once('templateMarket.class.php');

DEFINE("MY_LISTING_THRESHOLD", 100);

$page = new templateMarket(LOGIN, SHOWMSG, REDIRECTSAFE);
$calendarJS = '
    $(function(){$(".usedatepicker").datepicker();});
';
$page->jsInit($calendarJS);

// Temp Styles
$page->pageStyle(".filterlabel { font-weight: bold; margin-right:3px; }");
$page->pageStyle("div.filterbox { border-style: solid; border-width: 1px; border-color: #e5e5e5; overflow: hidden; padding: 5px 5px 5px 10px;}");
$page->pageStyle("div.filteritem { margin: 5px 10px 5px 0px; }");
$page->pageStyle("div.filtertitle { padding: 0.7rem 0rem 0.7rem 0rem; }");
$page->pageStyle("div.captionbox { overflow:hidden; padding: 0px 5px 0px 5px;}");
$page->pageStyle("div.caption-right { float:right; margin-left: 15px;}");
$page->pageStyle("div.caption-left { float:left; margin-right: 15px;}");
$page->pageStyle(".captionlabel { font-weight: bold; margin-right:3px; }");

$boxTypeId  = optional_param('boxtypeid', NULL, PARAM_INT);
$categoryId = optional_param('categoryid', NULL, PARAM_INT);
$subCategoryId = optional_param('subcategoryid', NULL, PARAM_INT);
$year       = optional_param('year', NULL, PARAM_TEXT);
$cancelled = optional_param('cancel', NULL, PARAM_TEXT);
$deleteListing = optional_param('deleteid', NULL, PARAM_TEXT);
$update = optional_param('update', NULL, PARAM_TEXT);
$includeInactive = optional_param('inactive', 0, PARAM_INT);
$listingType = optional_param('listingtype', 'Both', PARAM_TEXT);
$hideNotes = optional_param('hidenotes', 0, PARAM_INT);
$displayMode = optional_param('displaymode', 'yr', PARAM_TEXT);

//echo "Raw CategoryID:".$categoryId." Year:".$year." BoxTypeId:".$boxTypeId." SubCategoryId:".$subCategoryId." ListingType:".$listingType." IncludeInactive:".$includeInactive."<br />\n";

$yearFormat = null;

if ($categoryId) {
    $yearFormat = $page->db->get_field_query("SELECT yearformattypeid FROM categories WHERE categoryid=".$categoryId);
}

if ($deleteListing) {
    doDeleteListing($deleteListing);
}

$listingData = null;
$listingCount = getMyListingCount($categoryId, $year, $boxTypeId, $subCategoryId, $includeInactive, $listingType);
if (($listingCount < MY_LISTING_THRESHOLD)
||  (($categoryId) && ($displayMode == 'yr') && ((!empty($year)) || ($yearFormat == YEAR_FORMAT_OTHER)))
||  (($categoryId) && ($displayMode == 'sc') && ($subCategoryId))) {
    $listingData = getMyListings($categoryId, $year, $boxTypeId, $subCategoryId, $includeInactive, $listingType);
} else {
    $page->messages->addInfoMsg("Over ".MY_LISTING_THRESHOLD." matches (".$listingCount."). Select additional criteria to limit matches and display results.");
}

if ($cancelled) {
    $page->messages->addInfoMsg("Item Edit Cancelled");
} else {
    if (! $deleteListing) {
        if ($update) {
            updateMyListings($listingData);
        }
    }
}

if ($listingData) {
    $page->messages->addInfoMsg("Status, price, quantity and notes can be edited below. All other changes require a new listing to be created.");
}

echo $page->header('My Listings');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $UTILITY, $listingData, $hideNotes;
    global $displayMode, $includeInactive, $boxTypeId, $categoryId, $year, $subCategoryId, $listingType, $yearFormat;

    echo "<form id='sub' name='sub' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "<input class='notehider' type='hidden' name='hidenotes' id='hidenotes' value='".$hideNotes."' />\n";

    // TABS
    echo "<div class='tab'>\n";
    echo displayListingsTab('yr',"By Year")."\n";
    echo displayListingsTab('sc',"By Subcategory")."\n";
    echo "  <input type='hidden' id='displaymode' name='displaymode' value='".$displayMode."' />\n";
    if ($yearFormat == YEAR_FORMAT_OTHER) {
        echo "  <input type='hidden' id='year' name='year' value='' />\n";
    }
    echo "</div>\n";
    echo "  <div class='tabcontent' style='display:block;'>\n";

    // CAPTIONS
    $addNewParams = "";
    $addNewParams .= ($categoryId) ? ("?categoryId=".$categoryId) : "";
    $addNewParams .= ($year) ? ("&year=".URLEncode($year)) : "";
    $addNewParams .= ($boxTypeId) ? ("&boxTypeId=".$boxTypeId) : "";
    $addNewParams .= ($subCategoryId) ? ("&subCategoryId=".$subCategoryId) : "";
    echo "    <div class='captionbox'>\n";
    echo "      <div class='caption-right'>\n";
    echo "        <label class='captionlabel' for='inactive'>Include Inactive</label>\n";
    echo "        <input type='checkbox' id='inactive' name='inactive' value='1' onChange='sub.submit();' ".$UTILITY->isChecked($includeInactive, 1)." />\n";
    echo "      </div>\n";
    echo "    </div>\n";


    // FILTERS
    echo "    <div class='filterbox'>\n";
    if ($displayMode == 'yr') {
        //echo listingTypeDDM($categoryId, $year, $boxTypeId, $subCategoryId, $listingType);
        echo listingTypeDDM($listingType);
        echo categoryDDM($categoryId, $listingType);
        if ($yearFormat != YEAR_FORMAT_OTHER) {
            echo yearDDM($categoryId, $year, $boxTypeId, $subCategoryId);
        }
        echo boxTypeDDM($categoryId, $year, $boxTypeId, $subCategoryId);
        echo subCategoryDDM($categoryId, $year, $boxTypeId, $subCategoryId);
    } else {
        //echo listingTypeDDM($categoryId, $year, $boxTypeId, $subCategoryId, $listingType);
        echo listingTypeDDM($listingType);
        echo categoryDDM($categoryId, $listingType);
        echo subCategoryDDM($categoryId, $year, $boxTypeId, $subCategoryId);
        echo boxTypeDDM($categoryId, $year, $boxTypeId, $subCategoryId);
        if ($yearFormat != YEAR_FORMAT_OTHER) {
            echo yearDDM($categoryId, $year, $boxTypeId, $subCategoryId);
        }
    }
    echo "      <div class='filteritem' style='float:left;'><input type='submit' name='refresh' value='Refresh' /></div>\n";
    echo "    </div>\n"; // Filterbox
    echo "</form>\n";
    echo "\n";

    // LISTINGS
    $listingIds = array();
    if ($listingData && is_array($listingData) && (count($listingData) > 0)) {
        echo "<SCRIPT language='JavaScript'>
            function confirmListingDelete(lid) {
                var confirmMsg = \"Are you sure you want to delete listing \"+lid+\" ?\\nThis action is permanent!\\nAny unsaved changes will also be discarded.\";
                if (confirm(confirmMsg)) {
                    $(\"#deleteid\").val(lid);
                    $(\"#items\").submit();
                }
                return(false);
            }
            </SCRIPT>\n";
        echo "<form id='items' name='items' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
        echo "<input type='hidden' name='inactive' id='inactive' value='".$includeInactive."' />\n";
        echo "<input type='hidden' name='listingtype' id='listingtype' value='".$listingType."' />\n";
        echo "<input class='notehider' type='hidden' name='hidenotes' id='hidenotes' value='".$hideNotes."' />\n";
        echo "<table>\n";
        echo "<caption>";
        echo " <span style='float: left;'>";
        if ($hideNotes) {
            echo "<a href='#' class='lnhide' style='float:left; display:none;' title='Hide Notes' onClick='$(\".lndata\").hide();$(\".lnhide\").hide(); $(\".lnshow\").show(); $(\".notehider\").val(1); return(false);'><i class='fa-solid fa-square-minus'></i></a><span class='lnhide' style='float:left; margin-left:5px; display:none;'>Hide Notes</span>";
            echo "<a href='#' class='lnshow' style='float:left;' title='Show Notes' onClick='$(\".lndata\").show();$(\".lnhide\").show(); $(\".lnshow\").hide(); $(\".notehider\").val(0); return(false);'><i class='fa-solid fa-square-plus'></i></a><span class='lnshow' style='float:left; margin-left:5px;'>Show Notes</span>";
        } else {
            echo "<a href='#' class='lnhide' style='float:left;' title='Hide Notes' onClick='$(\".lndata\").hide();$(\".lnhide\").hide(); $(\".lnshow\").show(); $(\".notehider\").val(1); return(false);'><i class='fa-solid fa-square-minus'></i></a><span class='lnhide' style='float:left; margin-left:5px;'>Hide Notes</span>";
            echo "<a href='#' class='lnshow' style='float:left; display:none;' title='Show Notes' onClick='$(\".lndata\").show();$(\".lnhide\").show(); $(\".lnshow\").hide(); $(\".notehider\").val(0); return(false);'><i class='fa-solid fa-square-plus'></i></a> <span class='lnshow' style='float:left; margin-left:5px; display:none;'>Show Notes</span>";
        }
        //echo " <span style='float: left;'>Use <i class='fa-solid fa-square-plus'></i> and <i class='fa-solid fa-square-minus'></i> to hide and show notes.</span>";
        echo "</span>";
        echo " <i class='fas fa-exclamation-triangle'></i> indicates the listing has pending offers.";
        echo "</caption>\n";
        echo "  <thead>\n";
        echo "    <tr>\n";
        echo "      <th align='left'>Listing ID</th><th>Active</th><th>Type</th><th>Product</th><th>Price</th><th>Qty</th><th>Expires</th><th>Deliver By</th><th>Delete</th>\n";
        echo "    </tr>\n";
        echo "  </thead>\n";
        echo "  <tbody>\n";
        foreach ($listingData as $listing) {
            $listingIds[] = $listing['listingid'];

            echo "    <tr>";
            echo "  \n";
            echo "<td class='number'>";
            echo "<a href='listing.php?subcategoryid=".$listing['subcategoryid']."&boxtypeid=".$listing['boxtypeid']."&categoryid=".$listing['categoryid']."&listingtypeid=".$listing['listingtypeid']."&checkid=".$listing['listingid']."&year=".URLEncode($listing['year'])."' target='_blank'>".$listing['listingid']."</a></td>";
            $hasPending = ($listing['numpending'] > 0) ? " <i class='fas fa-exclamation-triangle' title='You have pending offers for this product'></i> " : "";
            echo "<td><input type='checkbox' id='status".$listing['listingid']."' name='status".$listing['listingid']."' value=1 ".$UTILITY->isChecked($listing['status'], "OPEN")." />".$hasPending."</td>";
            echo "<td>".$listing['type']."</td>";
            $imgURL = ($listing['picture']) ? $UTILITY->getListingImageURL($listing['picture']) : NULL;
            $imgLinkURL = ($imgURL) ? (" ~ <a href='".$imgURL."' target='_blank'><img src='".$UTILITY->getListingImageURL($listing['picture'])."' style='height:20px;' /></a>") : "";
            $categoryDisplay = ($categoryId) ? "" : $listing['categorydescription']." ~ ";
            if ($displayMode == 'yr') {
                echo "<td>".$categoryDisplay.$listing['year']." ~ ".$listing['subcategorydescription']." ~ ".$listing['boxtypename'].$imgLinkURL."</td>";
            } else {
                echo "<td>".$categoryDisplay.$listing['subcategorydescription']." ~ ".$listing['year']." ~ ".$listing['boxtypename'].$imgLinkURL."</td>";
            }
            echo "<td align=right>$ <input type='text' class='number' name='dprice".$listing['listingid']."' id='dprice".$listing['listingid']."' value='".floatTwoDecimal($listing['dprice'])."' style='width:12ch;'  />/".$listing['uom']."<input type='hidden' name='boxespercase".$listing['listingid']."' id='boxespercase".$listing['listingid']."' value='".$listing['boxespercase']."' /></td>";
            echo "<td align=right><input type='text' class='number' name='quantity".$listing['listingid']."' id='quantity".$listing['listingid']."' size='4' value='".$listing['quantity']."'  style='width:8ch;' /></td>";
            if ($listing['type'] == 'Wanted') {
                echo "<td><input type=text size=10 name='expireson".$listing['listingid']."' id='expireson".$listing['listingid']."' class='usedatepicker' value='".$listing['expiresdt']."' />";
                echo "<td><input type=text size=10 name='deliverby".$listing['listingid']."' id='deliverby".$listing['listingid']."' class='usedatepicker' value='".$listing['deliverdt']."' />";
            } else {
                echo "<td>N/A<input type=hidden name='expireson".$listing['listingid']."' id='expireson".$listing['listingid']."' value='' />";
                echo "<td>N/A<input type=hidden name='deliverby".$listing['listingid']."' id='deliverby".$listing['listingid']."' value='' />";
            }
            if (($listing['numexisting'] > 0) || ($listing['numpending'] > 0)) {
                echo "<td title='This listing has existing/pending offers and can not be deleted.'>&nbsp</td>";
            } else {
                $confirmMsg = "Are you sure you want to delete listing ".$listing['listingid']." ?\\nThis action is permanent!\\nAny unsaved changes will also be discarded.";
                //echo "<td><a href='#' title='Permanently delete this listing' onClick='if (confirm(\"".$confirmMsg."\") { $(\"#deleteid\").val(\"".$listing['listingid']."\"); submit(); } else { return false; }' ><i class='fa-solid fa-trash'></i></a></td>";
                echo "<td><a href='#' title='Permanently delete this listing' onClick='confirmListingDelete(".$listing['listingid']."); return(false);' ><i class='fa-solid fa-trash'></i></a></td>";
            }
            echo "</tr>\n";
            $notesDisplay = ($hideNotes) ? " display: none;" : "";
            $lastModified = "<strong>Modified:</strong>".date('m-d-Y', $listing['modifydate']);
            echo "<tr class='lndata' style='vertical-align: top;".$notesDisplay."'><td><strong>Notes:</strong></td><td colspan='6'><textarea name='listingnotes".$listing['listingid']."'  id='listingnotes".$listing['listingid']."' style='width: 100%;' rows=1 title='drag bottom right corner to expand.'>".$page->utility->inputFriendlyString($listing['listingnotes'])."</textarea></td><td colspan=2>".$lastModified."</td></tr>\n";
        }
        echo "    <tr><td colspan=9>";
        echo "<input type='hidden' id='deleteid' name='deleteid' value='' />";
        echo "<input type='hidden' id='categoryid' name='categoryid' value='".$categoryId."' />";
        echo "<input type='hidden' id='year' name='year' value='".$year."' />";
        echo "<input type='hidden' id='boxtypeid' name='boxtypeid' value='".$boxTypeId."' />";
        echo "<input type='hidden' id='subcategoryid' name='subcategoryid' value='".$subCategoryId."' />";
        echo "<input type='hidden' name='listingids' value='".implode(',',$listingIds)."' />\n";
        echo "<input type='submit' name='update' id='update' value='Update Listings' /> <input type='submit' name='cancel' id='cancel' value='Cancel' />";
        echo "</td></tr>\n";
        echo "  </tbody>\n";
        echo "</table>\n";
        echo "</form>\n";
    }
    echo "</div>\n"; // TAB CONTENT
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

function getMyListings($categoryId, $year, $boxTypeId, $subCategoryId, $includeInactive, $listingType) {
    global $page, $displayMode, $yearFormat;

    $listingData = null;

    if ($displayMode == 'yr') {
        $andCategory = (!empty($categoryId)) ? "\n AND l.categoryid=".$categoryId." " : "";
        $andYear = (!empty($year)) ? "\n AND l.year='".$year."' " : "";
        $andType = (empty($listingType) || ($listingType == 'Both')) ? "" : " AND l.type='".$listingType."' ";
        $andSubCategory = ($subCategoryId) ? "\n AND l.subcategoryid=".$subCategoryId." " : "";
        $andBoxType = ($boxTypeId) ? "\n AND l.boxtypeid=".$boxTypeId." " : "";
        $andIsActive = (! $includeInactive) ? "\n AND l.status='OPEN' " : "";
        $sql = "SELECT l.listingid, l.status, l.type, l.dprice, l.uom, l.boxespercase, l.boxprice, l.quantity
                    , l.categoryid, l.subcategoryid, l.boxtypeid, c.categorytypeid as listingtypeid
                    , l.year, c.categorydescription, s.subcategoryname, s.subcategorydescription, b.boxtypename
                    , l.modifydate, l.picture, existings.numexisting, pendings.numpending, carts.numcart
                    , l.listingnotes
                    , l.expireson, l.deliverby
                    , to_char(to_timestamp(dateof(expireson)),'mm/dd/yyyy') as expiresdt
                    , to_char(to_timestamp(dateof(deliverby)),'mm/dd/yyyy') as deliverdt
                FROM listings l
                JOIN categories c on c.categoryid=l.categoryid
                JOIN subcategories s on s.subcategoryid=l.subcategoryid
                JOIN boxtypes b on b.boxtypeid=l.boxtypeid
                LEFT JOIN (
                    SELECT oi.listingid, count(*) as numexisting
                    FROM offers o
                    JOIN offeritems oi ON oi.offerid=o.offerid
                    WHERE o.offerto=".$page->user->userId."
                    GROUP BY oi.listingid
                ) existings ON existings.listingid=l.listingid
                LEFT JOIN (
                    SELECT oi.listingid, count(*) as numpending
                    FROM offers o
                    JOIN offeritems oi ON oi.offerid=o.offerid
                    WHERE o.offerto=".$page->user->userId."
                    AND o.offerstatus='PENDING'
                    GROUP BY oi.listingid
                ) pendings ON pendings.listingid=l.listingid
                LEFT JOIN (
                    SELECT sc.listingid, count(*) as numcart
                    FROM shoppingcart sc
                    GROUP BY sc.listingid
                ) carts ON carts.listingid=l.listingid
                WHERE l.userid=".$page->user->userId.$andCategory.$andType.$andSubCategory.$andBoxType.$andYear.$andIsActive."
                ";
        $sql .= "ORDER BY c.categorydescription COLLATE \"POSIX\", s.subcategorydescription COLLATE \"POSIX\", b.boxtypename COLLATE \"POSIX\", l.modifydate DESC";
        //echo "getMyListings yr SQL:<br />\n<pre>".$sql."</pre><br />\n";
        $listingData = $page->db->sql_query($sql);
    } else {
        $andCategory = (!empty($categoryId)) ? "\n AND l.categoryid=".$categoryId." " : "";
        $andYear = (!empty($year)) ? "\n AND l.year='".$year."' " : "";
        $andType = ($listingType == 'Both') ? "" : " AND l.type='".$listingType."' ";
        $andSubCategory = ($subCategoryId) ? "\n AND l.subcategoryid=".$subCategoryId." " : "";
        $andBoxType = ($boxTypeId) ? "\n AND l.boxtypeid=".$boxTypeId." " : "";
        $andIsActive = (! $includeInactive) ? "\n AND l.status='OPEN' " : "";
        $sql = "SELECT l.listingid, l.status, l.type, l.dprice, l.uom, l.boxespercase, l.boxprice, l.quantity
                    , l.categoryid, l.subcategoryid, l.boxtypeid, c.categorytypeid as listingtypeid
                    , l.year, l.year4, c.categorydescription, s.subcategoryname, s.subcategorydescription, b.boxtypename
                    , l.modifydate, l.picture, existings.numexisting, pendings.numpending, carts.numcart
                    , l.listingnotes
                    , l.expireson, l.deliverby
                    , to_char(to_timestamp(dateof(expireson)),'mm/dd/yyyy') as expiresdt
                    , to_char(to_timestamp(dateof(deliverby)),'mm/dd/yyyy') as deliverdt
                FROM listings l
                JOIN categories c on c.categoryid=l.categoryid
                JOIN subcategories s on s.subcategoryid=l.subcategoryid
                JOIN boxtypes b on b.boxtypeid=l.boxtypeid
                LEFT JOIN (
                    SELECT oi.listingid, count(*) as numexisting
                    FROM offers o
                    JOIN offeritems oi ON oi.offerid=o.offerid
                    WHERE o.offerto=".$page->user->userId."
                    GROUP BY oi.listingid
                ) existings ON existings.listingid=l.listingid
                LEFT JOIN (
                    SELECT oi.listingid, count(*) as numpending
                    FROM offers o
                    JOIN offeritems oi ON oi.offerid=o.offerid
                    WHERE o.offerto=".$page->user->userId."
                    AND o.offerstatus='PENDING'
                    GROUP BY oi.listingid
                ) pendings ON pendings.listingid=l.listingid
                LEFT JOIN (
                    SELECT sc.listingid, count(*) as numcart
                    FROM shoppingcart sc
                    GROUP BY sc.listingid
                ) carts ON carts.listingid=l.listingid
                WHERE l.userid=".$page->user->userId.$andCategory.$andType.$andSubCategory.$andBoxType.$andYear.$andIsActive."
                ";
        $sql .= "ORDER BY s.subcategorydescription COLLATE \"POSIX\", l.year4 DESC, b.boxtypename COLLATE \"POSIX\", l.modifydate DESC";
        //echo "getMyListings sub SQL:<br />\n<pre>".$sql."</pre><br />\n";
        $listingData = $page->db->sql_query($sql);
    }

    return $listingData;
}

function getMyListingCount($categoryId, $year, $boxTypeId, $subCategoryId, $includeInactive, $listingType) {
    global $page, $displayMode, $yearFormat;

    $listingCount = 0;

    $andCategory = (! empty($categoryId)) ? "\n AND l.categoryid=".$categoryId." " : "";
    $andYear = (($yearFormat != YEAR_FORMAT_OTHER) && (! empty($year))) ? "\n AND l.year='".$year."' " : "";
    $andType = (empty($listingType) || ($listingType == 'Both')) ? "" : " AND l.type='".$listingType."' ";
    $andSubCategory = ($subCategoryId) ? "\n AND l.subcategoryid=".$subCategoryId." " : "";
    $andBoxType = ($boxTypeId) ? "\n AND l.boxtypeid=".$boxTypeId." " : "";
    $andIsActive = (! $includeInactive) ? "\n AND l.status='OPEN' " : "";
    $sql = "SELECT count(*) AS listingcnt
            FROM listings l
            JOIN categories c on c.categoryid=l.categoryid
            JOIN subcategories s on s.subcategoryid=l.subcategoryid
            JOIN boxtypes b on b.boxtypeid=l.boxtypeid
            WHERE l.userid=".$page->user->userId.$andCategory.$andType.$andSubCategory.$andBoxType.$andYear.$andIsActive."
            ";
    //echo "getMyListingCount yr SQL:<br />\n<pre>".$sql."</pre><br />\n";
    $listingCount = $page->db->get_field_query($sql);

    return $listingCount;
}

function updateMyListings(&$listingData) {
    global $UTILITY, $page;

    $success = true;
    $updateListings = false;
    $listingsUpdated = array();

    if ($listingData && is_array($listingData) && (count($listingData) > 0)) {
        foreach ($listingData as &$listing) {
            $newActive = optional_param("status".$listing['listingid'], 0, PARAM_INT);
            $newStatus = ($newActive) ? 'OPEN' : 'CLOSED';
            $newPrice = optional_param("dprice".$listing['listingid'], NULL, PARAM_NUM_NO_COMMA);
            $newQuantity = optional_param("quantity".$listing['listingid'], NULL, PARAM_INT);
            $newBoxesPerCase = optional_param("boxespercase".$listing['listingid'], 1, PARAM_INT);
            $newNotes = optional_param("listingnotes".$listing['listingid'], NULL, PARAM_TEXT);
            $newDeliverBy = optional_param("deliverby".$listing['listingid'], NULL, PARAM_TEXT);
            $newExpiresOn = optional_param("expireson".$listing['listingid'], NULL, PARAM_TEXT);
            $newModifyDate = time();

            if ($listing['type'] != 'Wanted') {
                if ($newDeliverBy) {
                    $newDeliverBy = null;
                    $page->messages->addWarningMsg("Deliver By date ignored for For Sale listing ".$listing['listingid'].".");
                }
                if ($newExpiresOn) {
                    $newExpiresOn = null;
                    $page->messages->addWarningMsg("Expires On date ignored for For Sale listing ".$listing['listingid'].".");
                }
            }

            //echo "ID:".$listing['listingid']." Status:".$listing['status']."/".$newStatus." Price:".$listing['dprice']."/".$newPrice." Qty:".$listing['quantity']."/".$newQuantity."<br />\n";
//echo "ID:".$listing['listingid']." Deliver:".$listing['deliverdt']." New Deliver:".$newDeliverBy." Expire:".$listing['expiresdt']." New Expire:".$newExpiresOn."<br />\n";
            if (! (($listing['status'] == $newStatus)
                && ($listing['dprice'] == $newPrice)
                && ($listing['quantity'] == $newQuantity)
                && ($listing['deliverdt'] == $newDeliverBy)
                && ($listing['expiresdt'] == $newExpiresOn)
                && ($listing['listingnotes'] == $newNotes))) {
//echo "UpdateID:".$listing['listingid']."<br />\n";
                //echo "Validate listingid ".$listing['listingid']."<br />\n";
                if ($newQuantity <= 0) {
                    $page->messages->addErrorMsg("Quantity for listing ".$listing['listingid']." must be greater than 0.");
                    $success = false;
                }
                if (!(is_numeric($newPrice) && ($newPrice > 0.0))) {
                    $page->messages->addErrorMsg("Price for listing ".$listing['listingid']." must be greater than 0.");
                    $success = false;
                }

                $tomorrowMorning = strtotime("tomorrow");
                $days180 = strtotime("today + 181 days")-1;
                $expiresDateTime = null;
                $deliverDateTime = null;
                if ($listing['type'] == 'Wanted') {
                    if (! ($newExpiresOn)) {
                        $newExpiresOn = date('m/d/Y', $days180);
                        $page->messages->addInfoMsg("Expires On date for listing ".$listing['listingid']." set to default of 180 days (".$newExpiresOn.").");
                    }
                }
                if ($newExpiresOn) {
                    $expiresDateTime = strtotime($newExpiresOn." 23:59:59");
                    if ($expiresDateTime) {
                        $listing['expireson'] = $expiresDateTime;
                        if ($expiresDateTime < $tomorrowMorning) {
                            if ($newStatus != 'CLOSED') {
                                $page->messages->addErrorMsg("Expires On date must be at least 1 day in the future for listing".$listing['listingid'].".");
                                $success = false;
                            }
                        }
                    } else {
                        $page->messages->addErrorMsg("Invalid Expires On date for listing".$listing['listingid'].".");
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
                        $page->messages->addErrorMsg("Invalid Deliver By date for listing".$listing['listingid'].".");
                        $success = false;
                    }
                } else {
                    $listing['deliverby'] = null;
                }
//echo "expiresDateTime:".$expiresDateTime." deliverDateTime:".$deliverDateTime."<br />\n";
                if ($expiresDateTime && $deliverDateTime) {
                    if ($deliverDateTime < $expiresDateTime) {
                        if ($newStatus != 'CLOSED') {
                            $page->messages->addErrorMsg("Deliver By date must be greater than or equal to Expires On date for listing ".$listing['listingid'].".");
                            $success = false;
                        }
                    }
                }


                if ($page->user->isFactoryCost()) {
                    if ($newStatus == 'OPEN') {
                        $sql = "SELECT count(*)
                            FROM listings l
                            JOIN listings dup
                                ON dup.listingid <> l.listingid
                                AND dup.userid = l.userid
                                AND dup.categoryid = l.categoryid
                                AND dup.subcategoryid = l.subcategoryid
                                AND dup.boxtypeid = l.boxtypeid
                                AND dup.year = l.year
                                AND dup.status='OPEN'
                            WHERE l.listingid=".$listing['listingid'];
                        $dupcnt = $page->db->get_field_query($sql);
                        if ($dupcnt > 0) {
                            $page->messages->addErrorMsg("Enabling listing ".$listing['listingid']." would create a duplicate factory cost listing.");
                            $success = false;
                        }
                    }
                }

                $listing['status'] = $newStatus;
                $listing['dprice'] = $newPrice;
                $listing['boxprice'] = $newPrice/$newBoxesPerCase;
                $listing['listingnotes'] = $newNotes;
                $listing['deliverdt'] = $newDeliverBy;
                $listing['expiresdt'] = $newExpiresOn;
                $listing['quantity'] = $newQuantity;
                $listing['modifydate'] = $newModifyDate;
//$success=false;
//$page->messages->addErrorMsg("Force failure for debug listing id ".$listing['listingid'].".");
                if ($success) {
                    if (! $updateListings) {
                        $page->db->sql_begin_trans();
                        $updateListings = true;
                    }
                    $sql = "UPDATE listings SET quantity    = :quantity,
                                            dprice       = :dprice,
                                            boxprice     = :boxprice,
                                            listingnotes = :listingnotes,
                                            expireson    = :expireson,
                                            deliverby    = :deliverby,
                                            status       = :status,
                                            modifydate   = :modifydate,
                                            modifiedby   = '".$page->user->username."'
                         WHERE listingid = :listingid";
                    $params = array();
                    $params['listingid']        = $listing['listingid'];
                    $params['quantity']         = $listing['quantity'];
                    $params['dprice']           = $listing['dprice'];
                    $params['boxprice']         = $listing['boxprice'];
                    $params['listingnotes']     = $listing['listingnotes'];
                    $params['expireson']        = $listing['expireson'];
                    $params['deliverby']        = $listing['deliverby'];
                    $params['status']           = $listing['status'];
                    $params['modifydate']       = $listing['modifydate'];

                    $result = $page->db->sql_execute_params($sql, $params);
                    if (empty($result)) {
                        $page->messages->addErrorMsg("Error updating listing ".$listing['listingid']);
                        $success = false;
                    } else {
                        //$page->messages->addSuccessMsg("Updated listing ".$listing['listingid']);
                        $listingsUpdated[] = $listing['listingid'];
                        if ($listing['status'] == 'CLOSED') {
                            if ($listing['numcart'] > 0) {
                                $sql = "DELETE FROM shoppingcart WHERE listingid=".$listing['listingid'];
                                if (! $result = $page->db->sql_execute($sql)) {
                                    $page->messages->addWarningMsg("Unable to remove item ".$listing['listingid']." from shopping carts.");
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($updateListings) {
            if ($success) {
                $page->db->sql_commit_trans();
                $page->messages->addSuccessMsg("Listings updated");
                if (! $page->user->isFactoryCost()) {
                    foreach ($listingsUpdated as $listingId) {
                        $UTILITY->checkCollar($listingId);
                    }
                }
            } else {
                $page->db->sql_rollback_trans();
                $page->messages->addErrorMsg("Error updating listings");
            }
        } else {
            $page->messages->addErrorMsg("No listings updated");
        }
    }
}

function getSubCats($categoryId, $boxTypeId, $year, $listingType) {
    global $page, $includeInactive, $displayMode;

    $returnData = null;
    if (!empty($categoryId)) {
        if ($displayMode == 'yr') {
            $andInactive = ($includeInactive) ? "" : "\n                        AND l.status             = 'OPEN'\n";
            if ((!empty($boxTypeId)) && (!empty($year))) {
                $andType = ($listingType == 'Both') ? "" : " AND l.type='".$listingType."' ";
                $sql = "SELECT l.subcategoryid, sc.subcategoryname, l.boxtypeid, bt.boxtypename, l.uom
                            ,count(l.listingid) as listingcount
                        FROM listings         l
                            JOIN subcategories    sc  ON  sc.subcategoryid        = l.subcategoryid
                            JOIN boxtypes         bt  ON  bt.boxtypeid            = l.boxtypeid
                        WHERE l.categoryid         = ".$categoryId."
                            AND l.boxtypeid          = ".$boxTypeId."
                            AND isnull(l.year, '1')  = isnull('".$year."', '1')
                            AND l.userid             = ".$page->user->userId.$andInactive.$andType."
                        GROUP BY l.subcategoryid, sc.subcategoryname, l.boxtypeid, bt.boxtypename, l.uom
                        ORDER BY sc.subcategoryname COLLATE \"POSIX\"";

                //echo "getSubCats SQL:<pre>".$sql."</pre><br />\n";
                $returnData = $page->db->sql_query($sql);
            }
        } else {
            if (!empty($subCategoryId)) {
                $andType = ($listingType == 'Both') ? "" : " AND l.type='".$listingType."' ";
                $sql = "SELECT l.subcategoryid, sc.subcategoryname, l.boxtypeid, bt.boxtypename, l.uom
                            ,count(l.listingid) as listingcount
                        FROM listings         l
                            JOIN subcategories    sc  ON  sc.subcategoryid        = l.subcategoryid
                            JOIN boxtypes         bt  ON  bt.boxtypeid            = l.boxtypeid
                        WHERE l.categoryid         = ".$categoryId."
                            AND l.boxtypeid          = ".$boxTypeId."
                            AND isnull(l.year, '1')  = isnull('".$year."', '1')
                            AND l.userid             = ".$page->user->userId.$andInactive.$andType."
                        GROUP BY l.subcategoryid, sc.subcategoryname, l.boxtypeid, bt.boxtypename, l.uom
                        ORDER BY sc.subcategoryname COLLATE \"POSIX\"";

                //echo "getSubCats SQL:<pre>".$sql."</pre><br />\n";
                $returnData = $page->db->sql_query($sql);
            }
        }
    }

    return $returnData;
}

function categoryDDM($categoryId = NULL, $listingType = NULL) {
    global $page, $includeInactive;

    $output = "";
    $divLabel = "<div class='filteritem' style='float:left;'><label class='filterlabel' for='categoryid'>Category:</label>";
    $divClose = "</div>\n";

    $activeCategories = 1;
    $blasts = false;
    $categories = $page->utility->getMyCategories($page->user->userId, $activeCategories, $includeInactive, $blasts, $listingType);
    //$onChange = " onchange = \"$('#year').val('');$('#boxtypeid').val('');$('#subcategoryid').val('');$('#listingtype').val('Both');submit();\"";
    $onChange = " onchange = \"$('#year').val('');$('#boxtypeid').val('');$('#subcategoryid').val('');submit();\"";
    $output = $divLabel.getSelectDDM($categories, "categoryid", "categoryid", "categorydescription", NULL, $categoryId, "Select", 0, NULL, NULL, $onChange).$divClose;

    return $output;
}

function yearDDM($categoryId, $year, $boxTypeId, $subCategoryId) {
    global $page, $yearFormat, $includeInactive, $displayMode;

    $output = "";
    $divLabel = "<div class='filteritem' style='float:left;'><label class='filterlabel' for='year'>Year:</label>";
    $divClose = "</div>\n";

    if (!empty($categoryId)) {
        if ($displayMode == 'yr') {
            if ($yearFormat != YEAR_FORMAT_OTHER) {
                $rs = $page->utility->getMyYears($page->user->userId, $categoryId, $includeInactive);
                //$onChange = " onchange = \"$('#boxtypeid').val('');$('#subcategoryid').val('');$('#listingtype').val('Both');submit();\"";
                $onChange = " onchange = \"$('#boxtypeid').val('');$('#subcategoryid').val('');submit();\"";
                $output = $divLabel.getSelectDDM($rs, "year", "year", "yearname", NULL, $year, "Select", 0, NULL, NULL, $onChange).$divClose;
            } else {
                $output = "<input type='hidden' name='year' id='year' value='' />\n";
            }
        } else {
            if (!empty($subCategoryId)) {
                $rs = $page->utility->getMyYears($page->user->userId, $categoryId, $includeInactive, $boxTypeId, $subCategoryId);
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

function boxTypeDDM($categoryId = null, $year=NULL, $boxTypeId=NULL, $subCategoryId=NULL) {
    global $page, $yearFormat, $includeInactive, $listingType, $displayMode;

    $output = "";
    $divLabel = "<div class='filteritem' style='float:left;'><label class='filterlabel' for='boxtypeid'>Box Type:</label>";
    $divClose = "</div>\n";

    if (!empty($categoryId)) {
        if ($displayMode == 'yr') {
            if ((!empty($year)) || ($yearFormat == YEAR_FORMAT_OTHER)) {
                $rs = $page->utility->getMyBoxTypes($page->user->userId, $categoryId, $year, $includeInactive, $subCategoryId, $listingType);
                $onChange = " onchange = 'submit();'";
                $output = $divLabel.getSelectDDM($rs, "boxtypeid", "boxtypeid", "boxtypename", NULL, $boxTypeId, "All", 0, NULL, NULL, $onChange).$divClose;
            } else {
                $output = "<input type='hidden' name='boxtypeid' id='boxtypeid' value='' />\n";
            }
        } else {
            if (!empty($subCategoryId)) {
                $rs = $page->utility->getMyBoxTypes($page->user->userId, $categoryId, $year, $includeInactive, $subCategoryId);
                $onChange = " onchange = 'submit();'";
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

function subCategoryDDM($categoryId = null, $year=NULL, $boxTypeId = NULL, $subCategoryId=NULL) {
    global $page, $yearFormat, $includeInactive, $displayMode, $listingType;

    $output = "";
    $divLabel = "<div class='filteritem' style='float:left;'><label class='filterlabel' for='subcategoryid'>Subcategory:</label>";
    $divClose = "</div>\n";

    if (!empty($categoryId)) {
        if ($displayMode == 'yr') {
            if ((!empty($year)) || ($yearFormat == YEAR_FORMAT_OTHER)) {
                $rs = $page->utility->getMySubcategories($page->user->userId, $categoryId, $boxTypeId, $year, $includeInactive, $listingType);
                $onChange = " onchange = 'submit();'";
                $output = $divLabel.getSelectDDM($rs, "subcategoryid", "subcategoryid", "subcategoryname", NULL, $subCategoryId, "All", 0, NULL, NULL, $onChange).$divClose;
            } else {
                $output = "<input type='hidden' name='subcategoryid' id='subcategoryid' value='' />\n";
            }
        } else {
            $rs = $page->utility->getMySubcategories($page->user->userId, $categoryId, NULL, NULL, $includeInactive, $listingType);
            //$onChange = " onchange = \"$('#boxtypeid').val('');$('#year').val('');$('#listingtype').val('Both');submit();\"";
            $onChange = " onchange = \"$('#boxtypeid').val('');$('#year').val('');submit();\"";
            $output = $divLabel.getSelectDDM($rs, "subcategoryid", "subcategoryid", "subcategoryname", NULL, $subCategoryId, "Select", 0, NULL, NULL, $onChange).$divClose;
        }
    } else {
        $output = "<input type='hidden' name='subcategoryid' id='subcategoryid' value='' />\n";
    }

    return $output;
}

function listingTypeDDM($listingType, $categoryId = null, $year=NULL, $boxTypeId=NULL, $subCategoryId=NULL) {
    global $page, $yearFormat, $includeInactive, $displayMode;

    $output = "";
    $divLabel = "<div class='filteritem' style='float:left;'><label class='filterlabel' for='listingtype'>Buy/Sell:</label>";
    $divClose = "</div>\n";

    $rs = $page->utility->getMyListingTypes($page->user->userId, $categoryId, $year, $includeInactive, $subCategoryId, $boxTypeId);
    //$onChange = " onchange = 'submit();'";
    $onChange = " onchange = \"$('#categoryid').val('');$('#year').val('');$('#boxtypeid').val('');$('#subcategoryid').val('');submit();\"";
    $output = $divLabel.getSelectDDM($rs, "listingtype", "listingtype", "listingtypename", NULL, $listingType, "Both", "Both", NULL, NULL, $onChange).$divClose;

    return $output;
}

function displayListingsTab($tabId, $tabLabel) {
    global $page, $displayMode;

    $isActive = ($tabId == $displayMode) ? " active" : "";
    return "  <button class='tablinks".$isActive."' onclick=\"$('#displaymode').val('".$tabId."');submit();\" >".$tabLabel."</button>";
}

function doDeleteListing($listingId) {
    global $page;

    $success = true;

    if ($listingId) {
        $andUser = ($page->user->isAdmin()) ? "" : " AND userid=".$page->user->userId;
        $sql = "SELECT listingid, picture FROM listings WHERE listingid=".$listingId.$andUser;
        $listings = $page->db->sql_query($sql);
        if ($listings) {
            $sql = "SELECT count(*) AS numoffers
                FROM offers o
                JOIN offeritems oi ON oi.offerid=o.offerid
                WHERE oi.listingid=".$listingId;
            $hasExisting = $page->db->get_field_query($sql);
            if ($hasExisting > 0) {
                $page->messages->addErrorMsg("Unable to delete listing ".$listingId." there are existing offers.");
                $success = false;
            } else {
                $page->db->sql_begin_trans();

                $sql = "SELECT count(*) AS numcart
                    FROM shoppingcart sc
                    WHERE sc.listingid=".$listingId;
                $hasCart = $page->db->get_field_query($sql);
                if ($hasCart) {
                    $numCart = $page->db->sql_execute("DELETE FROM shoppingcart WHERE listingid=".$listingId);
                    if ($numCart <= 0) {
                        $page->messages->addErrorMsg("Error deleting shopping cart item(s) for listing ".$listingId.".");
                        $success = false;
                    }
                }
                if ($success) {
                    $numDeleted = $page->db->sql_execute("DELETE FROM listings WHERE listingid=".$listingId);
                    if ($numDeleted <= 0) {
                        $page->messages->addErrorMsg("Error deleting listing ".$listingId.".");
                        $success = false;
                    }
                }
                if ($success) {
                    $page->db->sql_commit_trans();
                    $page->messages->addSuccessMsg("Listing ".$listingId." deleted.");
                    $listing = reset($listings);
                    if ($listing['picture']) {
                        $pictureFile = $page->cfg->listings.$listing['picture'];
                        if (file_exists($pictureFile)) {
                            if (unlink($pictureFile)) {
                                $page->messages->addInfoMsg("Picture file deleted.");
                            } else {
                                $page->messages->addWarningMsg("Unable to delete picture file.");
                            }
                        } else {
                            $page->messages->addWarningMsg("Picture file did not exist.");
                        }
                    }
                } else {
                    $page->db->sql_rollback_trans();
                    $cartMsg = ($numCart > 0) ? " and no cart items were affected" : "";
                    $page->messages->addErrorMsg("Listing ".$listingId." was NOT deleted".$cartMsg.".");
                }
            }
        } else {
            $page->messages->addErrorMsg("Error deleting listing. Listing ID not found or access denied.");
            $success = false;
        }
    } else {
        $page->messages->addErrorMsg("Listing ID required for delete.");
        $success = false;
    }

    return $success;
}

?>