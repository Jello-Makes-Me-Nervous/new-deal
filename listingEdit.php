<?php
require_once('templateAdmin.class.php');
$page = new templateMarket(LOGIN, SHOWMSG);

$isValid = true;

$listingId = optional_param('listingid', NULL, PARAM_INT);
$listing = new listing($listingId);

if ($listing) {
    if ( ! ($listing->listingUserId == $page->user->userId)) {
        $page->messages->addErrorMsg("Access denied.");
        $isValid = false;
    }
} else {
    $page->messages->addErrorMsg("Listing not found.");
    $isValid = false;
}


//echo "Listing:<br /><pre>";var_dump($listing);echo "</pre><br />\n";

$target = $CFG->listingsPath;

$addListing         = optional_param('addListing', NULL, PARAM_INT);
$boxespercase       = optional_param('boxespercase', NULL, PARAM_INT);
$boxTypeId          = optional_param('boxTypeId', NULL, PARAM_INT);
$categoryId         = optional_param('categoryId', NULL, PARAM_INT);
$commiteditListing  = optional_param('commiteditListing', NULL, PARAM_INT);
$deleteListing      = optional_param('deleteListing', NULL, PARAM_INT);
$editListing        = optional_param('editListing', NULL, PARAM_INT);
$isGaming           = optional_param('isGaming', NULL, PARAM_INT);
$listingId          = optional_param('listingId', NULL, PARAM_TEXT);
$listingNotes       = optional_param('listingNotes', NULL, PARAM_TEXT);
$releaseDate        = optional_param('releasedate', NULL, PARAM_TEXT);
$minQuantity        = optional_param('minQuantity', NULL, PARAM_INT);
$new                = optional_param('new', NULL, PARAM_INT);
//$picturePath        = optional_param('picturePath', NULL, PARAM_TEXT);
$dprice             = optional_param('dprice', NULL, PARAM_TEXT);
$quantity           = optional_param('quantity', NULL, PARAM_INT);
$save               = optional_param('save', NULL, PARAM_TEXT);
$status             = optional_param('status', 'OPEN', PARAM_TEXT);
$subCategoryId      = optional_param('subCategoryId', NULL, PARAM_INT);
$title              = optional_param('title', NULL, PARAM_TEXT);
$type               = optional_param('type', NULL, PARAM_TEXT);
$uom                = optional_param('uom', NULL, PARAM_TEXT);
$year               = optional_param('year', NULL, PARAM_TEXT);
$picturePath = NULL;
$pictureUp = NULL;
if (is_array($_FILES) && (count($_FILES) > 0)) {
    if (array_key_exists('pictureup', $_FILES)) {
        if (!(  empty($_FILES['pictureup']['name'])
             || empty($_FILES['pictureup']['type'])
             || empty($_FILES['pictureup']['tmp_name'])
             || ($_FILES['pictureup']['size'] < 1))) {
            $pictureUp = $_FILES['pictureup'];
        }
    }
}

if (!isset($listing->listingId)) {
    $add = $addListing = 1;
} else {
    $add = "commiteditListing = 1";
}
if (! empty($save)) {
    if ($listing->addListing($status, $type, $categoryId, $subCategoryId, $boxTypeId, $year, $dprice, $uom, $boxespercase, $minQuantity, $quantity, $listingNotes, $releaseDate, $picturePath, $pictureUp, $target, $title)) {
        setGlobalListingTypeId($categoryId);
        header("location:listing.php?subcategoryid=".$subCategoryId."&categoryid=".$categoryId."&boxtypeid=".$boxTypeId."&listingtypeid=".$listingTypeId."&year=".URLEncode($year),"&pgsmsg=".URLEncode("Successfully added listing."));
    }
}

echo $page->header('Edit Listing');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $CFG, $page, $isValid, $listing, $add, $boxTypeId, $categoryId, $listingId, $subCategoryId, $type, $year, $status, $uom, $boxespercase, $dprice, $quantity, $listingNotes, $releaseDate;

    if (! $isValid) {
        return;
    }

    echo "<article>\n";
    echo "  <div class='entry-content'>\n";
        if ($categoryId > 0 || !empty($listing->listingId)) {

            echo "<form name='sub' id='sub' action='".htmlentities($_SERVER['PHP_SELF'])."?".$add."' method='post' enctype='multipart/form-data' onsubmit='return checkUOM(), checkYear()'>\n";
            echo "  <input type='hidden' name='listingId' value='".$listing->listingId."'>\n";
            echo "  <input type='hidden' name='categoryId' value='".$listing->categoryId."'>\n";
            echo "  <table>\n";
            echo "    <tr>\n";
            echo "      <td>Status:</td>\n";
            echo "      <td><input type='radio' name='status' value='OPEN' ".checked($listing->status, "OPEN")."><label>Active</label></td>\n";
            echo "      <td><input type='radio' name='status' value='CLOSED' ".checked($listing->status, "CLOSED")."><label>Inactive</label></td>\n";
            echo "    </tr>\n";
            echo "    <tr>\n";
            echo "      <td>Transaction:</td>\n";
            echo "      <td><input type='radio' name='type' value='Wanted' ".checked($listing->type, "Wanted")."><label>Wanted</label></td>\n";
            echo "      <td><input type='radio' name='type' value='For Sale' ".checked($listing->type, "For Sale")."><label>For Sale</label></td>\n";
            echo "    </tr>\n";
            echo "    <tr>\n";
            echo "      <td>Category:</td>\n";
            echo "      <td colspan='2'>\n";
            echo "        ".getSelectDDM($page->utility->getcategories(), "categoryId", "categoryid", "categoryname",  NULL, $listing->categoryId, "Select",
                                             NULL, NULL, NULL, " onChange=\"javascript: this.form.submit();\"")."\n";
            echo "      </td>\n";
            echo "    </tr>\n";
            echo "    <tr>\n";
            echo "      <td>Sub-Cat:</td>\n";
            echo "      <td colspan='2'>\n";

            if (!isset($listing->listingId)) {
                echo "        ".getSelectDDM($page->utility->getsubCategories($listing->categoryId), "subCategoryId", "subcategoryid", "subcategoryname", NULL, $listing->subCategoryId)."\n";
            } else {
                echo "        ".getSelectDDM($page->utility->getsubCategories(), "subCategoryId", "subcategoryid", "subcategoryname", NULL, $listing->subCategoryId)."\n";
            }

            echo "      </td>\n";
            echo "    </tr>\n";
//SHOW IF CLASSIFIED///////////////////////////////////////////////////////////////
            if ($categoryId == 1261) {
                echo "    <tr>\n";
                echo "      <td>Title:</td>\n";
                echo "      <td colspan='2'><input type='text' name='title' id='title' size='50'></td>\n";
                echo "    </tr>\n";
            }
            echo "    <tr>\n";
            echo "      <td>Box Type:</td>\n";
            echo "      <td colspan='2'>\n";
            if (!isset($listing->listingId)) {
                echo "        ".getSelectDDM($page->utility->getboxtypes($listing->categoryId), "boxTypeId", "boxtypeid", "boxtypename", NULL, $listing->boxtypeId, "Select")."\n";
            } else {
                echo "        ".getSelectDDM($page->utility->getboxtypes(), "boxTypeId", "boxtypeid", "boxtypename", NULL, $listing->boxtypeId)."\n";
            }
            echo "      </td>\n";
            echo "    </tr>\n";
            echo "    <tr>\n";

            if (!isset($listing->listingId)) {
                if ($page->utility->getYearFormatTypeId($listing->categoryId) == 1) {
                    echo "      <td valign='top'>Years:</td>\n";
                    echo "      <td colspan='2'>\n";
                    echo "        <input type='text' name='year' id='year1' value='".$listing->year."' placeholder='YY/Y' size='4' onblur='checkYear1()'>\n";
                    echo "        <br />\n";
                    echo "        <span style='font-size: 0.8em;'>\n";
                    echo "          format: yyyy or yy/y for BK or HKY\n";
                    echo "          <br />\n";
                    echo "          Blank if n/a\n";
                    echo "        </span>\n";
                    echo "      </td>\n";
                }
                if ($page->utility->getYearFormatTypeId($listing->categoryId)== 2) {
                    echo "      <td valign='top'>Years:</td>\n";
                    echo "      <td colspan='2'>\n";
                    echo  "       <input type='text' name='year' id='year2' value='".$listing->year."' placeholder='YYYY' size='4' onblur='checkYear2()'>\n";
                    echo "        <br />\n";
                    echo "        <span style='font-size: 0.8em;'>\n";
                    echo "          format: yyyy or yy/y for BK or HKY\n";
                    echo "          <br />\n";
                    echo "          Blank if n/a\n";
                    echo "        </span>\n";
                    echo "      </td>\n";
                }
            } else {
                echo "      <td valign='top'>Years:</td>\n";
                echo "      <td colspan='2'>\n";
                echo "        <input type='text' name='year' id='year1' value='".$listing->year."' placeholder='YY/Y' onblur='checkYear1()' style='width:12ch;' >\n";
                echo "        <br />\n";
                echo "        <span style='font-size: 0.8em;'>\n";
                echo "          format: yyyy or yy/y for BK or HKY\n";
                echo "          <br />\n";
                echo "          Blank if n/a\n";
                echo "        </span>\n";
                echo "      </td>\n";
            }
            echo "    </tr>\n";
            if ($page->user->username == FACTORYCOSTNAME) {
                echo "    <tr>\n";
                echo "      <td>Release Date:</td>\n";
                echo "      <td><input type='text' name='releasedate' value='".$listing->releaseDate."' size='30'></td>\n";
                echo "    </tr>\n";
            }
            echo "    <tr>\n";
            echo "      <td>Price:</td>\n";
            echo "      <td><input type='text' name='dprice' value='".$listing->dprice."' size='7'></td>\n";
            echo "    </tr>\n";
//HIDE IF CLASSIFIED///////////////////////////////////////////////////////////////
            if ($listing->categoryId != 1261) {
                echo "    <tr>\n";
                echo "      <td>Unit:</td>\n";
                echo "      <td>\n";
                //$onChange = " onchange = \"($('#bpcspan').hidden(($(this).val() == 'case') ? true : false )\"";
                //$onChange = " onchange = \"alert(($(this).val() == 'case') ? 'show' : 'hide');\"";
                $onChange = " onchange = \"($(this).val() == 'case') ? $('#bpcspan').show() : $('#bpcspan').hide();\"";
                echo "        <select name='uom' id='uom' ".$onChange.">\n";
                echo "          <option value=''></option>\n";
                echo "          <option value='box' ".selected($listing->uom, "box").">Box</option>\n";
                echo "          <option value='case' ".selected($listing->uom, "case").">Case</option>\n";
                echo "          <option value='other' ".selected($listing->uom, "other").">Other</option>\n";
                echo "        </select>\n";
                $showBPC = ($listing->uom != "case") ? " style='display:none;'" : "";
                echo "&nbsp;<span name='bpcspan' id='bpcspan' ".$showBPC.">&nbsp;Boxes Per Case: <input type='text' name='boxespercase' id='boxespercase' value='".$listing->boxespercase."' size='4'></span></td>\n";
                echo "    </tr>\n";
            }
//HIDE IF CLASSIFIED///////////////////////////////////////////////////////////////
            if ($categoryId != 1261) {
                echo "    <tr>\n";
                echo "      <td>Quantity:</td>\n";
                echo "      <td><input type='text' name='quantity' value='".$listing->quantity."' size='4'><input type='hidden' name='minQuantity' value='1'></td>\n";
                echo "    </tr>\n";
            } else {
                echo "    <tr>\n";
                echo "      <td>\n";
                echo "        QTY:\n";
                echo "      </td>\n";
                echo "      <td>\n";
                echo "        <input type='text' name='quantity' value='' size='4'>\n";
                echo "      </td>\n";
                echo "    </tr>\n";
            }
            echo "    <tr>\n";
            echo "      <td colspan='3' align='left'>\n";
            echo "        Item Description/Notes:<br /><textarea name='listingNotes' id='listingNotes' cols='40' rows='4'>".$listingNotes."</textarea>\n";
            echo "      </td>\n";
            echo "    </tr>\n";
            echo "    <tr>\n";
            echo "      <td colspan='3' align='left'>\n";
            echo "        IMAGES\n";
            echo "      </td>\n";
            echo "    </tr>\n";
            /*
            echo "    <tr>\n";
            echo "      <td colspan='3' align='left'>\n";
            echo "          <span style='font-size: 0.8em;'>\n";
            echo "        Provide the link to an exixting image:\n";
            echo "        <br />\n";
            echo "        format: http//www.xxx.com/images/xyz.gif\n";
            echo "        <br />\n";
            echo "        <input type='text' name='picturePath' value=''>\n";
            echo "      </td>\n";
            echo "    </tr>\n";
            echo "    <tr>\n";
            echo "      <td colspan='3' align='left'>\n";
            echo "        OR\n";
            echo "      </td>\n";
            echo "    </tr>\n";
            */
            echo "    <tr>\n";
            echo "      <td colspan='3' align='left'>\n";
            echo "        Upload A New Image (gif & jpg only - Max:".(round(($CFG->IMG_MAX_UPLOAD/1000000),2))."MB)\n";
            echo "        <br />\n";
            echo "        <input type='file' name='pictureup' id='pictureup'>\n";//where to upload
            echo "      </td>\n";
            echo "    </tr>\n";
            echo "    <tr>\n";
            echo "      <td></td>\n";
            echo "      <td></td>\n";
            echo "      <td></td>\n";
            echo "    </tr>\n";
            echo "    <tr>\n";
            echo "      <td colspan='2' align='center'>\n";
            echo "        <input type='submit' name='save' id='save' value='SAVE'>\n";
            echo "      </td>\n";
            echo "      <td>\n";
            echo "        <a href='?cancelled' /><button>CANCEL</button>\n";
            echo "      </td>\n";
            echo "    </tr>\n";
            echo "  </table>\n";
            echo "  <input type='hidden' name='listingId' value=''>\n";
            echo "</form>\n";


    echo "  </div>\n";
    echo "</article>\n";

    }
}

function checked($check, $checked) {
    if ($check == $checked) {
        $data = " checked ";
    } else {
        $data = "";
    }

    return $data;
}

function selected($check, $checked) {
    if ($check == $checked) {
        $data = " selected ";
    } else {
        $data = "";
    }

    return $data;
}

?>