<?php
require_once('templateMarket.class.php');
require_once('product.class.php');

$page = new templateMarket(LOGIN, SHOWMSG);

if (! ($page->user->isAdmin() || $page->user->hasUserRightId(USERRIGHT_PRODUCT_ENTRY))) {
    header('location:home.php?pgemsg='.URLEncode("The requested page required Product Entry access"));
    exit();
}

if ($page->user->hasUserRightId(USERRIGHT_PRODUCT_ENTRY)) {
    $page->messages->addInfoMsg("UPC and Product images are required when creating a new product.");
}

$calendarJS = '
    $(function(){$("#releasedate").datepicker();});
';
$page->jsInit($calendarJS);

$target = $CFG->listingsPath;

$action             = optional_param('action', "view", PARAM_TEXT);
$save               = optional_param('save', NULL, PARAM_TEXT);
$update             = optional_param('update', NULL, PARAM_TEXT);
$cancel             = optional_param('cancel', NULL, PARAM_TEXT);

$productId          = optional_param('productid', NULL, PARAM_INT);

$product = new product($productId);

if ($action == 'confirmdelete') {
    if ($product->productId) {
        if ($product->loaded) {
            $product->deleteProduct();
        } else {
            $page->messages->addErrorMsg("Product id ".$productId." not found.");
        }
    } else {
        $page->messages->addErrorMsg("Product id required for delete.");
    }
}

if ($save == 'SAVE') {
    $product->scrapeProduct('add');
    if ($product->validateProduct('add')) {
        if ($product->addProduct()) {
            $productId = $product->productId;
            $action = 'edit';
        }
    }
}

if ($update == 'UPDATE') {
    $product->scrapeProduct('edit');
    if ($product->validateProduct('edit')) {
        $product->updateProduct();
    }
}

switch ($action) {
    CASE 'add':
        $pageTitle = "Add Product";
        break;
    CASE 'edit':
        $pageTitle = "Edit Product ".$productId;
        break;
    CASE 'delete':
        $pageTitle = "Delete Product ".$productId;
        break;
    CASE 'confirmdelete':
        $pageTitle = "Delete Product ".$productId;
        break;
    DEFAULT:
        $pageTitle = "Product ".$productId;
        break;
}

echo $page->header($pageTitle);
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $CFG, $page, $add, $listingId, $type, $status, $uom, $boxespercase, $dprice, $quantity, $listingNotes, $releaseDate, $expiresOn, $deliverBy, $shareImage;
    global $product, $action, $pageTitle;

    $disableOnEdit = ($action != 'add') ? "disabled" : NULL;
    $disableOnView = (($action == 'delete') || ($action == 'view')) ? "disabled" : NULL;

    echo "<h1>".$pageTitle."</h1>\n";
    echo "<article>\n";
    echo "  <div class='entry-content'>\n";

    if ($product->categoryId <= 0) {
        echo "<form action='".htmlentities($_SERVER['PHP_SELF'])."' method='post' enctype='multipart/form-data'>\n";
        echo " Category: ".getSelectDDM($page->utility->getcategories(), "categoryid", "categoryid", "categorydescription",  NULL, $product->categoryId, "Select",
                                         NULL, NULL, NULL, " onChange=\"javascript: this.form.submit();\"")."\n";
        echo "  <input type='hidden' id='action' name='action' value='".$action."' />\n";
        echo "</form>\n";
    } else {
        if ($action == 'delete') {
            echo "<div>\n";
            echo "Are you sure you ant to permanently delete this product?";
            echo "<a class='button' href='product.php?productid=".$product->productId."&action=confirmdelete'>Yes</a>";
            echo "<a class='button' href='product.php?productid=".$product->productId."&action=view'>Cancel</a>";
            echo "</div><br />\n";
        }

        echo "  <form class='entry-form' name='sub' id='sub' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post' enctype='multipart/form-data' >\n";
        echo "    <input type='hidden' name='action' id='action' ' value='".$action."'/>\n";
        echo "    <input type='hidden' name='productid' id='productid' ' value='".$product->productId."' />\n";
        if ($product->productId) {
            echo "    <div class='row'>\n";
            echo "      <div class='col-25'>\n";
            echo "        Product ID:";
            echo "      </div>\n";
            echo "      <div class='col-75'>\n";
            echo "        ".$product->productId."\n";
            echo "      </div>\n";
            echo "    </div>\n";
        }
        echo "    <div class='row'>\n";
        echo "      <div class='col-25'>\n";
        echo "        Status:";
        echo "      </div>\n";
        echo "      <div class='col-75'>\n";
        echo "        <input type='radio' name='status' value='1' ".checked($product->active, "1")." ".$disableOnView." ><label>Active</label>\n";
        echo "        <input type='radio' name='status' value='0' ".checked($product->active, "0")." ".$disableOnView." ><label>Inactive</label>\n";
        echo "      </div>\n";
        echo "    </div>\n";
        echo "    <div class='row'>\n";
        echo "      <div class='col-25'>\n";
        echo "        <label>Category:</label>\n";
        echo "      </div>\n";
        echo "      <div class='col-75'>\n";
        echo "      ".getSelectDDM($page->utility->getcategories(), "categoryid", "categoryid", "categoryname",  NULL, $product->categoryId, "Select",
                                       NULL, NULL, NULL, " onChange=\"javascript: this.form.submit();\"", $disableOnEdit)."\n";
        echo "      </div>\n";
        echo "    </div>\n";
        echo "    <div class='row'>\n";
        echo "      <div class='col-25'>\n";
        echo "        <label>Subcategory:</label>\n";
        echo "      </div>\n";
        echo "      <div class='col-75'>\n";
        echo "      ".getSelectDDM($page->utility->getSubCategories($product->categoryId), "subcategoryid", "subcategoryid", "subcategoryname", NULL, $product->subCategoryId, "Select", 0, NULL, NULL, NULL, $disableOnEdit)."\n";
        echo "      </div>\n";
        echo "    </div>\n";
        echo "    <div class='row'>\n";
        echo "      <div class='col-25'>\n";
        echo "        <label>Box Type:<label>\n";
        echo "      </div>\n";
        echo "      <div class='col-75'>\n";
        if ($product->categoryTypeId == LISTING_TYPE_SUPPLY) {
            echo "Supplies<input type=hidden name=boxtypeid id=boxtTypeid value='".BOX_TYPE_SUPPLIES."' />";
        } else {
            echo "        ".getSelectDDM($page->utility->getboxtypes($product->categoryId), "boxtypeid", "boxtypeid", "boxtypename", NULL, $product->boxTypeId, "Select", 0, NULL, NULL, NULL, $disableOnEdit)."\n";
        }
        echo "      </div>\n";
        echo "    </div>\n";
        echo "    <div class='row'>\n";
        echo "      <div class='col-25'>\n";
        echo "        <label>Year(s):</label>\n";
        echo "      </div>\n";
        echo "      <div class='col-75'>\n";
        if ( $product->yearFormatTypeId == 1) {
            echo "        <input type='text' name='year' id='year1' value='".$product->year."' placeholder='YY/Y' style='width:6em;' onblur='checkYear1()' ".$disableOnEdit." />\n";
        } else {
            if ( $product->yearFormatTypeId == 2) {
                echo  "       <input type='text' name='year' id='year2' value='".$product->year."' placeholder='YYYY' style='width:6em;' onblur='checkYear2()' ".$disableOnEdit." />\n";
            } else {
                echo "        <input type='hidden' name='year' value='' />N/A\n";
            }
        }
        echo "      </div>\n";
        echo "    </div>\n";
        echo "    <div class='row'>\n";
        echo "      <div class='col-25'>\n";
        echo "        <label>Variation:</label>\n";
        echo "      </div>\n";
        echo "      <div class='col-75'>\n";
        echo  "       <input type='text' name='variation' id='variation' value='".$product->variation."' style='width:32em;'  ".$disableOnView."  />\n";
        echo "      </div>\n";
        echo "    </div>\n";
        echo "    <div class='row'>\n";
        echo "      <div class='col-25'>\n";
        echo "        <label>UPC:</label>\n";
        echo "      </div>\n";
        echo "      <div class='col-75'>\n";
        $required = ($page->user->hasUserRightId(USERRIGHT_PRODUCT_ENTRY)) ? "required" : null;
        if ($action == "add" && !empty($product->productSKU)) {
            echo  "       <input type='text' name='productsku[]' id='productsku' value='' style='width:32em;' ".$disableOnView." ".$required." />\n";
        } elseif (($action == "view" || $action == "edit" || $action == "delete" || $action == "confirmdelete") && !empty($product->productSKU)) {
            $x = 0;
            foreach($product->productSKU as $upc) {
                $x++;
                echo  "       <input type='text' name='productsku[]' id='productsku".$x."' value='".$upc."' style='margin-top:2px;width:32em;' ".$disableOnView." /><br>\n";
            }
            if ($action == "edit") {
                $x++;
                echo  "       <input type='text' name='productsku[]' id='productsku".$x."' value='' style='margin-top:2px;width:32em;' ".$disableOnView." ".$required." />\n";
            }
        } else {
            echo  "       <input type='text' name='productsku[]' id='productsku' value='' style='width:32em;'  ".$disableOnView." ".$required." />\n";
        }
        echo "      </div>\n";
        echo "    </div>\n";
        echo "    <div class='row'>\n";
        echo "      <div class='col-25'>\n";
        echo "        <label>Factory Cost:</label>\n";
        echo "      </div>\n";
        echo "      <div class='col-75'>\n";
        $fc = (empty($product->factorycost) || $product->factorycost == 0.0) ? null : $product->factorycost;
        echo  "       <input type='text' name='factorycost' id='factorycost' value='".$fc."' style='width:32em;'  ".$disableOnView."  />\n";
        echo "      </div>\n";
        echo "    </div>\n";
        echo "    <div class='row'>\n";
        echo "      <div class='col-25'>\n";
        echo "        <label>Release Date:</label>\n";
        echo "      </div>\n";
        echo "      <div class='col-75'>\n";
        $releasedate = (empty($product->releasedate)) ? NULL : date("m/d/Y", $product->releasedate);
        echo  "       <input type='text' name='releasedate' id='releasedate' value='".$releasedate."' style='width:32em;'  ".$disableOnView."  />\n";
        echo "      </div>\n";
        echo "    </div>\n";
        echo "    <div class='row'>\n";
        echo "      <div class='col-25'>\n";
        echo "        Product Image:\n";
        echo "      </div>\n";
        if ($picURL = $page->utility->getPrefixPublicImageURL($product->picture, THUMB150)) {
            echo "      <img class='align-left' src='".$picURL."' alt='product image' width='150px' height='150px'>\n";
        }
        echo "      <label>Upload A New Image (gif, jpg + png only - Max:".(round(($CFG->IMG_MAX_UPLOAD/1000000),2))."MB)</label>\n";
        echo "      <br />\n";
        $required = ($page->user->hasUserRightId(USERRIGHT_PRODUCT_ENTRY) && $action == "add") ? "required" : null;
        echo "      <input type='file' name='pictureup' id='pictureup' ".$required.">\n";
        echo "    </div>\n";
        echo "    <div class='row'>\n";
        echo "      <div class='col-25'>\n";
        echo "        Description/Notes:\n";
        echo "      </div>\n";
        echo "      <div class='col-75'>\n";
        echo "        <textarea name='productnote' id='productnote' cols='80' rows='8' ".$disableOnView." >".$product->productNote."</textarea>\n";
        echo "      </div>\n";
        echo "    </div>\n";
        echo "    <div class='row'>\n";
        if ($action == 'add') {
            echo "      <input type='submit' name='save' id='save' value='SAVE'>\n";
            echo "      <input type='submit' name='cancel' id='cancel' value='Cancel'>\n";
        } else {
            if ($action == 'edit') {
                echo "      <input type='submit' name='update' id='update' value='UPDATE'>\n";
                echo "      <input type='submit' name='cancel' id='cancel' value='Cancel'>\n";
            }
        }
        echo "    </div>\n";
        echo "  </form>\n";
        echo "</div>\n";
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

?>