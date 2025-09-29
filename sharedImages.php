<?php
require_once('templateMarket.class.php');

$page = new templateMarket(LOGIN, SHOWMSG);
$page->display_BottomWidget = false;

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

$doDelete       = optional_param('dodelete', 0, PARAM_INT);
$sharedImageId  = optional_param('siid', 0, PARAM_INT);

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
if ($sharedImageId) {
    $sql = "
        SELECT l.*, c.categorytypeid
          FROM sharedimages         l
          JOIN categories           c   ON  c.categoryid        = l.categoryid
                                        AND c.active            = 1
          JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                        AND sc.active           = 1
          JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                        AND bt.active           = 1
        WHERE l.sharedimageid = ".$sharedImageId;

    if ($referenceListings = $page->db->sql_query($sql)) {
        $referenceListing = reset($referenceListings);
        $categoryId = $referenceListing['categoryid'];
        $subCategoryId = $referenceListing['subcategoryid'];
        $boxTypeId = $referenceListing['boxtypeid'];
        $year = $referenceListing['year'];
        $listingTypeId = $referenceListing['categorytypeid'];
        $referenceListingId = $referenceListing['listingid'];
    }
} else {
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
}

if ($categoryId) {
    setGlobalListingTypeId($categoryId);
}

$year = (empty($year)) ? NULL : $year;

$categoryDescription    = "";
$subcategoryDescription = "";
$listings               = null;
$otherlistings          = null;
$picHLcost              = null;

$haveMatchingImages   = false;
$haveListingCriteria    = false;

if ($doDelete) {
    if ($sharedImageId) {
        deleteSharedImage($sharedImageId);
        $sharedImageId = 0;
    } else {
        $page->messages->addErrorMsg("Error deleting shared image ".$sharedImageId);
    }
}

if ((!empty($categoryId)) && (!empty($subCategoryId)) && (!empty($boxTypeId))) {
    if (($listingTypeId == LISTING_TYPE_GAMING) || (!empty($year))) {
        $haveListingCriteria = true;
    }
}

if ($haveListingCriteria) {
    $showSharedImages = userHasSharedImages($sharedImageId, $boxTypeId, $categoryId, $subCategoryId, $year);
    $sharedImages = null;
    if ($showSharedImages) {
        $sharedImages = getSharedImages($sharedImageId, $boxTypeId, $categoryId, $subCategoryId, $year);
        if ($sharedImages && is_array($sharedImages) && (count($sharedImages) > 0)) {
            //echo "SHARED IMAGES:<br />\n<pre>";
            //var_dump($sharedImages);
            //echo "</pre><br />\n";
            $haveMatchingImages = true;
        } else {
            $page->messages->addErrorMsg("No shared images to display");
        }
    } else {
        $page->messages->addErrorMsg("No matching shared images to display");
    }
    $categoryDescription = $page->db->get_field_query("select categorydescription from categories where categoryid=".$categoryId);
    $subcategoryDescription = $page->db->get_field_query("select subcategorydescription from subcategories where subcategoryid=".$subCategoryId);
    $boxTypeName = $page->db->get_field_query("select boxtypename from boxtypes where boxtypeid=".$boxTypeId);
} else {
    $page->messages->addErrorMsg("No shared images for supplied criteria.");
}

echo $page->header('Shared Image Management');
mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $haveMatchingImages, $sharedImages, $year, $subcategoryDescription, $categoryDescription, $boxTypeName;
    
    if ($haveMatchingImages) {
        echo "          <h1>Shared Images: ".$year." ".$subcategoryDescription." ".$categoryDescription." - ".$boxTypeName."</h1>\n";
        
        echo "<table>\n";
        echo "<thead><tr><th>Image</th><th>Image Id</th><th>User</th><th>Listing</th><th>Action</th></tr></thead>\n";
        echo "<tbody>\n";
        foreach ($sharedImages as $sharedImage) {
            $picURL = $page->utility->getPrefixPublicImageURL($sharedImage['picture']);
            echo "<td><img src='".$picURL."' alt='shared listing image' width='150px' height='150px'></td>\n";
            echo "<td>".$sharedImage['sharedimageid']."</td>\n";
            echo "<td>".$sharedImage['username']."</td>\n";
            if ($sharedImage['listingid']) {
                $listing = "<a href='listing.php?referenceid=".$sharedImage['listingid']."' target='_blank'>".$sharedImage['listingid']."</a>";
            } else {
                $listing = "N/A";
            }
            echo "<td>".$listing."</td>\n";
            $confirmMsg = "Are you sure you want to delete public image ".$sharedImage['sharedimageid']." ?\\nThis action is permanent!";
            $deleteURL = "sharedImages.php?siid=".$sharedImage['sharedimageid']."&dodelete=1";
            echo "<td><a href='".$deleteURL."' title='Permanently delete this public image' onClick=\"return confirm('".$confirmMsg."');\"' ><i class='fa-solid fa-trash'></i></a></td>";
            echo "</tr>\n";
        }
        echo "</tbody>\n";
        echo "</table>\n";
    }
}    

function userHasSharedImages($sharedImageId, $boxTypeId = NULL, $categoryId = NULL, $subCategoryId = NULL, $year) {
    global $page;

    $hasImages = 0;
    
    $andUserId = ($page->user->isAdmin()) ? "" : "AND l.userid=".$page->user->userId;
    $andListingYear = (empty($year)) ? " AND l.year IS NULL " : " AND l.year = '".$year."' ";
    $andSharedId = ($sharedImageId) ? " AND l.sharedimageid=".$sharedImageId." " : "";
    
    $sql = "
        SELECT count(*)
          FROM sharedimages l
         WHERE l.categoryId         = ".$categoryId.$andSharedId.$andListingYear.$andUserId."
           AND l.subcategoryId      = ".$subCategoryId."
           AND l.boxTypeId          = ".$boxTypeId."
           AND l.picture            IS NOT NULL
           AND l.uom IN ('box', 'case')
    ";
    $hasImages = $page->db->get_field_query($sql);
    
    return $hasImages;
}

function getSharedImages($sharedImageId, $boxTypeId = NULL, $categoryId = NULL, $subCategoryId = NULL, $year) {
    global $page;

    $sharedImages = null;
    
    $andUserId = ($page->user->isAdmin()) ? "" : "AND l.userid=".$page->user->userId;
    $andListingYear = (empty($year)) ? " AND l.year IS NULL " : " AND l.year = '".$year."' ";
    $andSharedId = ($sharedImageId) ? " AND l.sharedimageid=".$sharedImageId." " : "";

    $sql = "
        SELECT l.*, c.categorydescription, sc.subcategoryname, bt.boxtypename, u.username
          FROM sharedimages l
          JOIN categories c ON c.categoryid = l.categoryid
          JOIN subcategories sc ON sc.subcategoryid = l.subcategoryid
          JOIN boxtypes bt ON bt.boxtypeid = l.boxtypeid
          JOIN users u ON u.userid=l.userid
         WHERE l.categoryId         = ".$categoryId.$andSharedId.$andListingYear.$andUserId."
           AND l.subcategoryId      = ".$subCategoryId."
           AND l.boxTypeId          = ".$boxTypeId."
           AND l.picture            IS NOT NULL
           AND l.uom IN ('box', 'case')
    ";
    $sharedImages = $page->db->sql_query($sql);
    
    return $sharedImages;
}

function deleteSharedImage($sharedImageId) {
    global $page;
    
    $success = true;
    
    $andUserId = ($page->user->isAdmin()) ? "" : " AND userid=".$page->user->userId;
    
    $sql = "SELECT picture FROM sharedimages WHERE sharedimageid=".$sharedImageId.$andUserId." LIMIT 1";
    //echo "deleteSharedImage:".$sql."<br />\n";
    $picture = $page->db->get_field_query($sql);
    if ($picture) {
        $sql = "WITH deleted AS (DELETE FROM sharedimages WHERE sharedimageid=".$sharedImageId.$andUserId." RETURNING *) SELECT count(*) FROM deleted";
        //echo "deleteSharedImage:".$sql."<br />\n";
        if ($page->db->sql_query($sql)) {
            $page->messages->addInfoMsg("Public image record ".$sharedImageId." deleted.");
            $pictureFile = $page->cfg->sharedImages.$picture;
            if (file_exists($pictureFile)) {
                if (unlink($pictureFile)) {
                    $page->messages->addInfoMsg("Public image file ".$sharedImageId." deleted.");
                } else {
                    $page->messages->addWarningMsg("Error deleting public image file for ".$sharedImageId.".");
                }
            } else {
                $page->messages->addWarningMsg("Public image file for ".$sharedImageId." file did not exist.");
            }
        } else {
            $page->messages->addErrorMsg("Error deleting public image ".$sharedImageId.".");
            $success = false;
        }
    } else {
        $page->messages->addErrorMsg("Unable to delete public image ".$sharedImageId.". It does not exist or you do not have permissions.");
        $success = false;
    }
    
    return $success;
}

?>