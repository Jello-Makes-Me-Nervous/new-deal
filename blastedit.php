<?php
require_once('templateMarket.class.php');
///ADD RIGHT TO UPLOAD IMAGE/////////////////
$page = new templateMarket(LOGIN, SHOWMSG);
$page->requireJS("https://cdn.tiny.cloud/1/5xrplszm20gv2hy8zmwsr77ujzs70m70owm5d8o6bf2tcg64/tinymce/5/tinymce.min.js");


$target = $CFG->blastDocPath;
$targetURL = NULL;
$listingId   = optional_param('listingid', NULL, PARAM_TEXT);
$action      = optional_param('action', 'edit', PARAM_TEXT);
$picturePath = NULL;
//$picturePath = optional_param('picturepath', NULL, PARAM_TEXT);
$documentPath = NULL;
//$documentPath = optional_param('documentpath', NULL, PARAM_TEXT);

$pictureUp = NULL;
$documentUp = NULL;
if (is_array($_FILES) && (count($_FILES) > 0)) {
    if (array_key_exists('pictureup', $_FILES)) {
        if (!(  empty($_FILES['pictureup']['name'])
             || empty($_FILES['pictureup']['type'])
             || empty($_FILES['pictureup']['tmp_name'])
             || ($_FILES['pictureup']['size'] < 1))) {
            $pictureUp = $_FILES['pictureup'];
        }
    }
    if (array_key_exists('documentup', $_FILES)) {
        if (!(  empty($_FILES['documentup']['name'])
             || empty($_FILES['documentup']['type'])
             || empty($_FILES['documentup']['tmp_name'])
             || ($_FILES['documentup']['size'] < 1))) {
            $documentUp = $_FILES['documentup'];
        }
    }
}

$listing = new listing($listingId);
if ($action == "add") {
    if (!$page->user->hasUserRight('Email Blast Unlimited')) {
        if (!$page->user->hasUserRight('Email Blast Limited')) {
            header("location:blasts.php?dealerid=".$page->user->userId."&pgemsg=You%20are%20not%20authorized%20to%20create%20blasts");
        } else {
            $activeBlasts = $page->db->get_field_query("select count(*) as numblasts from listings where categoryid=".CATEGORY_BLAST." and userid=".$page->user->userId." and status='OPEN'");
            //echo "Active blasts:".$activeBlasts."<br />\n";
            if ($activeBlasts > 2) {
                header("location:blasts.php?dealerid=".$page->user->userId."&pgemsg=You%20are%20only%20allowed%203%20active%20blasts");
            }
        }
    }
}

if ($action == "save") {
    scrapeBlast($action);
    if (validateBlast($pictureUp, $documentUp)) {
        if ($listing->addListing($listing->status, $listing->type, $listing->categoryId, $listing->subCategoryId, $listing->boxtypeId, $listing->year, $listing->dprice, $listing->uom, $listing->boxespercase, $listing->minQuantity, $listing->quantity, $listing->listingNotes, $listing->releaseDate, $picturePath, $pictureUp, $target, $targetURL, $listing->title, $documentPath, $documentUp, $listing->acceptOffers)) {
            header("Location:blasts.php?dealerid=".$page->user->userId."&pgsmsg=Created%20new%20blast");
        } else {
            $action = "add";
        }
    }
} else if ($action == "update") {
    scrapeBlast($action);
    if (validateBlast($pictureUp, $documentUp)) {
        if ($listing->commitEditListing($listing->listingId, $listing->status, $listing->type, $listing->categoryId, $listing->subCategoryId
            , $listing->boxtypeId, $listing->year, $listing->dprice, $listing->uom, $listing->boxespercase
            , $listing->minQuantity, $listing->quantity, $listing->listingNotes, $listing->picture, $pictureUp, $page->user->username, NULL, $listing->title, $listing->acceptOffers)) {
            header("Location:blastview.php?listingid=".$listing->listingId."&dealerid=".$page->user->userId."&pgsmsg=Updated%20blast");
        } else {
            $action = "edit";
        }
    }
}

if (! $listing->loaded) {
    $listing->categoryId = CATEGORY_BLAST;
    $listing->subCategoryId = SUBCATEGORY_BLAST;
    $listing->boxtypeId = BOX_TYPE_BLAST;
    $listing->year = null;
    $listing->year4 = null;
    $listing->uom = LISTING_UOMID_OTHER;
    $listing->dprice = 1.00;
    $listing->boxespercase = 1;
    $listing->boxprice = 1.00;
    $listing->dprice = 1.00;
    $listing->quantity = 1;
    $listing->minquantity = 1;
    $listing->status = 'OPEN';
    $listing->type = TRANSACTION_TYPE_FOR_SALE;
    $listing->acceptOffers = 1;
}
$pagetitle = ($listingId) ? "Edit Blast" : "Add Blast";
echo $page->header($pagetitle);
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $CFG, $page, $action, $listing, $add, $boxTypeId, $categoryId, $listingId, $subCategoryId, $type, $year, $status, $uom;
/*
echo "Listing:<br />\n<pre>";
var_dump($listing);
echo "</pre><br />\n";
exit;
*/
    $pageaction = $action;
    switch ($action) {
        CASE 'add': $pageaction = 'save';
            break;
        CASE 'edit': $pageaction = 'update';
            break;
    }

    echo "<article>\n";
    echo "  <div class='entry-content'>\n";
    echo "<form name='sub' id='sub' action='".htmlentities($_SERVER['PHP_SELF'])."?".$add."' method='post' enctype='multipart/form-data'>\n";
    echo "  <input type='hidden' id='action' name='action' value='".$pageaction."' />\n";
    echo "  <input type='hidden' id='listingid' name='listingid' value='".$listing->listingId."' />\n";
    echo "  <input type='hidden' id='categoryid' name='categoryid' value='".$listing->categoryId."' />\n";
    echo "  <input type='hidden' id='subcategoryid' name='subcategoryid' value='".$listing->subCategoryId."' />\n";
    echo "  <input type='hidden' id='boxtypeid' name='boxtypeid' value='".$listing->boxtypeId."' />\n";
    echo "  <input type='hidden' id='year' name='year' value='".$listing->year."' />\n";
    echo "  <input type='hidden' id='dprice' name='dprice' value='".$listing->dprice."' />\n";
    echo "  <input type='hidden' id='boxprice' name='boxprice' value='".$listing->boxprice."' />\n";
    echo "  <input type='hidden' id='boxespercase' name='boxespercase' value='".$listing->boxespercase."' />\n";
    echo "  <input type='hidden' id='uom' name='uom' value='".$listing->uom."' />\n";
    echo "  <input type='hidden' id='quantity' name='quantity' value='".$listing->quantity."' />\n";
    echo "  <table>\n";
    echo "    <tr>\n";
    echo "      <td>Status:</td>\n";
    echo "      <td><input type='radio' name='status' value='OPEN' ".checked($listing->status, "OPEN")." /><label>Active</label></td>\n";
    echo "      <td><input type='radio' name='status' value='CLOSED' ".checked($listing->status, "CLOSED")." /><label>Inactive</label></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td>Transaction:</td>\n";
    if ($action == "add") {
        echo "      <td><input type='radio' name='type' value='Wanted' ".checked($listing->type, "Wanted")." /><label>Wanted</label></td>\n";
        echo "      <td><input type='radio' name='type' value='For Sale' ".checked($listing->type, "For Sale")." /><label>For Sale</label></td>\n";
    } else {
        echo "      <td colspan=2>".$listing->type."<input type='hidden' name='type' id='type' value='".$listing->type."' /></td>\n";
    }
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td>Accept Offers:</td>\n";
    if ($action == "add") {
        echo "      <td><input type='radio' name='acceptoffers' value='1' ".checked($listing->acceptOffers, 1)." /><label>Yes</label></td>\n";
        echo "      <td><input type='radio' name='acceptoffers' value='0' ".checked($listing->acceptOffers, 0)." /><label>No</label></td>\n";
    } else {
        echo "      <td colspan=2>".(($listing->acceptOffers) ? "Yes" : "No")."<input type='hidden' name='acceptoffers' id='accetpoffers' value='".$listing->acceptOffers."' /></td>\n";
    }
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td>Title:</td>\n";
    if ($action == "add") {
        echo "      <td colspan='2'><input type='text' name='title' id='title' size='50' value='".str_replace("'","&#39;",$listing->title)."' /></td>\n";
    } else {
        echo "      <td colspan='2'>".str_replace("'","&#39;",$listing->title)."</td>\n";
    }
    echo "    </tr>\n";
    if ($page->user->username == FACTORYCOSTNAME) {
        echo "    <tr>\n";
        echo "      <td>Release Date:</td>\n";
        if ($action == "add") {
            echo "      <td colspan='2'><input type='text' name='releasedate' id='releasedate' size='30' value='".str_replace("'","&#39;",$listing->releaseDate)."' /></td>\n";
        } else {
            echo "      <td colspan='2'>".str_replace("'","&#39;",$listing->releaseDate)."</td>\n";
        }
        echo "    </tr>\n";
    }
    echo "      <td colspan='3' align='left'>\n";
    echo "        Item Description/Notes:<br />";
    if ($action == "add") {
        echo "<textarea name='listingnotes' id='listingnotes' cols='40' rows='4'>".$listing->listingNotes."</textarea>\n";
    } else {
        echo $listing->listingNotes;
    }
    echo "      </td>\n";
    echo "    </tr>\n";
    $documentLink = "";
    if ($listing->document) {
        $documentLink = " <a href='".$page->utility->getPrefixBlastURL($listing->document)."' target=_blank>view</a>";
        echo "      <td colspan = '3' align='left'>DOCUMENT: ".$documentLink."</td>\n";
    } else {
        if ($action == "add") {
            echo "      <td colspan = '3' align='left'>\n";
            echo "        Upload A New Document (pdf only - Max:".(round(($CFG->DOC_MAX_UPLOAD/1000000),2))."MB)\n";
            echo "        <br />\n";
            echo "        <input type='file' name='documentup' id='documentup' />\n";//where to upload
            echo "      </td>\n";
        }
    }
    echo "    </tr>\n";
    echo "    <tr>\n";
    $imageLink = "";
    if ($listing->picture) {
        $imageLink = " <img src='".$page->utility->getPrefixBlastURL($listing->picture)."' width='50' height='50'> ";
        echo "      <td colspan = '3' align='left'>IMAGE: ".$imageLink."</td>\n";
    } else {
        if ($action == "add") {
            echo "      <td colspan='3' align='left'>\n";
            echo "        Upload A New Image (gif & jpg only - Max:".(round(($CFG->IMG_MAX_UPLOAD/1000000),2))."MB)\n";
            echo "        <br />\n";
            echo "        <input type='file' name='pictureup' id='pictureup' />\n";//where to upload
            echo "      </td>\n";
        }
    }
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td colspan='3'></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td colspan='2' align='center'>\n";
    echo "        <input type='submit' name='save' id='save' value='SAVE' />\n";
    echo "      </td>\n";
    echo "      <td><a href='blasts.php?dealerid=".$page->user->userId."'>CANCEL</a>\n";
    echo "      </td>\n";
    echo "    </tr>\n";
    echo "  </table>\n";
    echo "</form>\n";

    echo "  </div>\n";
    echo "</article>\n";
    echo "<script>\n";
    echo "  tinymce.init({\n";
    echo "     selector: 'textarea',\n";
    echo "    plugins: 'advlist autolink lists link image charmap print preview hr anchor pagebreak',\n";
    echo "    toolbar_mode: 'floating',\n";
    echo "  });\n";
    echo "</script>\n";
}

function checked($check, $checked) {
    if ($check == $checked) {
        $data = "checked='checked'";
    } else {
        $data = "";
    }

    return $data;
}

function scrapeBlast($action) {
    global $page, $listing;

    $listing->status = optional_param('status', NULL, PARAM_TEXT);
    
    if ($action == "save") {
        $listing->listingId = optional_param('listingid', NULL, PARAM_INT);
        $listing->categoryId = optional_param('categoryid', NULL, PARAM_INT);
        $listing->subCategoryId = optional_param('subcategoryid', NULL, PARAM_INT);
        $listing->boxtypeId = optional_param('boxtypeid', NULL, PARAM_TEXT);
        $listing->year = optional_param('year', NULL, PARAM_TEXT);
        $listing->dprice = optional_param('dprice', NULL, PARAM_TEXT);
        $listing->boxprice = optional_param('boxprice', NULL, PARAM_TEXT);
        $listing->boxespercase = optional_param('boxespercase', NULL, PARAM_TEXT);
        $listing->uom = optional_param('uom', NULL, PARAM_TEXT);
        $listing->quantity = optional_param('quantity', NULL, PARAM_INT);
        $listing->type = optional_param('type', NULL, PARAM_TEXT);
        $listing->acceptOffers = optional_param('acceptoffers', NULL, PARAM_TEXT);
        $listing->title = optional_param('title', NULL, PARAM_RAW);
        $listing->listingNotes = optional_param('listingnotes', NULL, PARAM_RAW);
        //$listing->picture = optional_param('picturepath', NULL, PARAM_TEXT);
        $listing->document = optional_param('documentpath', NULL, PARAM_TEXT);
    }
}

function validateBlast($pictureUp, $documentUp) {
    global $page, $listing, $action;

    $success = true;

    if (empty($listing->title)) {
        $page->messages->addErrorMsg("Title is required");
        $success = false;
    }
    if (empty($listing->listingNotes)
    &&  empty($listing->picture)
    &&  empty($listing->document)
    &&  (! isset($pictureUp))
    &&  (! isset($documentUp))) {
        $page->messages->addErrorMsg("Description, Document or Image is required");
        $success = false;
    }

    return $success;
}
?>