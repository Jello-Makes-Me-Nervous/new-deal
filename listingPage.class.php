<?php

class listingPage {



    public $addListing;
    public $boxespercase;
    public $boxTypeId;
    public $boxtypeName;
    public $categoryId;
    public $categoryName;
    public $commiteditListing;
    public $deleteListing;
    public $editListing;
    public $isGaming;
    public $listing;
    public $listingId;
    public $listingNotes;
    public $minQuantity;
    public $new;
    public $picturePath;
    public $pictureUp;
    public $dprice;
    public $quantity;
    public $status;
    public $subCategoryId;
    public $subCategoryName;
    public $type;
    public $uom;
    public $year;
    /*
    public function __construct($listing = NULL) {
        $this->listing = $listing;
    }
*/
    public function __construct($listingId = NULL) {
        if (!empty($listingId)) {
            $this->listing = new listing($listingId);
        }
        $this->paramInit();
    }

    public function paramInit() {
        $this->addListing         = optional_param('addListing', NULL, PARAM_INT);
        $this->boxespercase       = optional_param('boxespercase', NULL, PARAM_INT);
        $this->boxTypeId          = optional_param('boxtypeId', NULL, PARAM_INT);
        $this->categoryId         = optional_param('categoryId', NULL, PARAM_INT);
        $this->commiteditListing  = optional_param('commiteditListing', NULL, PARAM_INT);
        $this->deleteListing      = optional_param('deleteListing', NULL, PARAM_INT);
        $this->editListing        = optional_param('editListing', NULL, PARAM_INT);
        $this->isGaming           = optional_param('isGaming', NULL, PARAM_INT);
        $this->listingId          = optional_param('listingId', NULL, PARAM_TEXT);
        $this->listingNotes       = optional_param('listingNotes', NULL, PARAM_TEXT);
        $this->minQuantity        = optional_param('minQuantity', NULL, PARAM_INT);
        $this->new                = optional_param('new', NULL, PARAM_INT);
        $this->picturePath        = optional_param('picturePath', NULL, PARAM_TEXT);
        $this->pictureUp          = optional_param('pictureUp', NULL, PARAM_TEXT);
        $this->dprice             = optional_param('dprice', NULL, PARAM_TEXT);
        $this->quantity           = optional_param('quantity', NULL, PARAM_INT);
        $this->status             = optional_param('status', NULL, PARAM_TEXT);
        $this->subCatId           = optional_param('subcatid', NULL, PARAM_INT);
        $this->type               = optional_param('type', NULL, PARAM_TEXT);
        $this->uom                = optional_param('uom', NULL, PARAM_TEXT);
        $this->year               = optional_param('year', NULL, PARAM_INT);
    }

    public function displayListing() {

        $this->listing->getListing($listingId);
        $output = "";
        $output .= "<a href='?new=1'>Add A New Listing</a><br /><br />\n";
        $output .= "  <table>\n";
        $output .= "    <tr>\n";
        $output .= "      <td>ID: </td>\n";
        $output .= "      <td>".$this->listing->listingId."</td>\n";
        $output .= "    </tr>\n";
        $output .= "    <tr>\n";
        $output .= "      <td>Status: </td>\n";
        $output .= "      <td>".$this->listing->status."</td>\n";
        $output .= "    </tr>\n";
        $output .= "    <tr>\n";
        $output .= "      <td>Type: </td>\n";
        $output .= "      <td>".$this->listing->type."</td>\n";
        $output .= "    </tr>\n";
        $output .= "    <tr>\n";
        $output .= "      <td>Category : </td>\n";
        $output .= "      <td>".$this->listing->categoryName."</td>\n";
        $output .= "    </tr>\n";
        $output .= "    <tr>\n";
        $output .= "      <td>Subcategory: </td>\n";
        $output .= "      <td>".$this->listing->subCategoryName."</td>\n";
        $output .= "    </tr>\n";
        $output .= "    <tr>\n";
        $output .= "      <td>Boxtype: </td>\n";
        $output .= "      <td>".$this->listing->boxtypeName."</td>\n";
        $output .= "    </tr>\n";
        $output .= "    <tr>\n";
        $output .= "      <td>Year: </td>\n";
        $output .= "      <td>".$this->listing->year."</td>\n";
        $output .= "    </tr>\n";
        $output .= "    <tr>\n";
        $output .= "      <td>Price: </td>\n";
        $output .= "      <td>".$this->listing->dprice."</td>\n";
        $output .= "    </tr>\n";
        $output .= "    <tr>\n";
        $output .= "      <td>Unit: </td>\n";
        $output .= "      <td>".$this->listing->uom."</td>\n";
        $output .= "    </tr>\n";
        $output .= "    <tr>\n";
        $output .= "      <td>Boxes Per Case: </td>\n";
        $output .= "      <td>".$this->listing->boxespercase."</td>\n";
        $output .= "    </tr>\n";
        $output .= "    <tr>\n";
        $output .= "      <td>Quantitys: </td>\n";
        $output .= "      <td>Min-".$this->listing->minQuantity." | Total-".$this->listing->quantity."</td>\n";
        $output .= "    </tr>\n";
        $output .= "    <tr>\n";
        $output .= "      <td>Notes: </td>\n";
        $output .= "      <td>".$this->listing->listingNotes."</td>\n";
        $output .= "    </tr>\n";
        $output .= "    <tr>\n";
        $output .= "      <td colspan='3' align='left'>\n";
        $output .= "        DISPLAY IMAGE\n";
        $output .= "      </td>\n";
        $output .= "    </tr>\n";
        $output .= "    <tr>\n";
        $output .= "      <td>\n";
        $output .= "        <a href='?editListingForm=".$this->listing->listingId."#editA".$this->listing->listingId."'>Edit</a>\n";
        $output .= "      </td>\n";
        $output .= "      <td>\n";
        $output .= "        <a href='?deleteListing=1&blistingId=".$this->listing->listingId."&listingId=".$this->listing->listingId."&picturePath=".$this->listing->picture."'\n";
        $output .= "          onclick=\"javascript: return confirm('Are you sure you want to permently delete this listing - ".$this->listing->listingId."')\">Delete</a>\n";
        $output .= "      </td>\n";
        $output .= "    </tr>\n";
        $output .= "  </table/>\n";
        $output .= "<br />\n";

        return $output;

    }

    public function addEditListingForm($categoryId = NULL) {
        global $CFG, $UTILITY;

        if (!is_object($this->listing)) {
            $this->listing = new listing();
        }
        if (!isset($this->listing->listingId)) {
            $add = $this->addListing = 1;
        } else {
            $add = "commiteditListing = 1";
        }

        $output = "";

        $output .= "<form action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
        $output .= " PICK ".getSelectDDM($UTILITY->getcategories(), "categoryId", "categoryid", "categoryname",  NULL, $categoryId, "Select",
                                         NULL, NULL, NULL, " onChange=\"javascript: this.form.submit();\"")."\n";
        $output .= "</form>\n";


        if ($categoryId > 0 || !empty($this->listing->listingId)) {

            $output .= "<form name='sub' id='sub' action='".htmlentities($_SERVER['PHP_SELF'])."?".$add."' method='post' enctype='multipart/form-data' onsubmit='return checkUOM(), checkYear()'>\n";
            $output .= "  <input type='hidden' name='listingId' value='".$this->listing->listingId."'>\n";
            $output .= "  <input type='hidden' name='categoryId' value='".$categoryId."'>\n";
            $output .= "  <table>\n";
            $output .= "    <tr>\n";
            $output .= "      <td>Listing ID:</td>\n";
            $output .= "      <td>".$this->listing->listingId."</td>\n";
            $output .= "      <td></td>\n";
            $output .= "    </tr>\n";
            $output .= "    <tr>\n";
            $output .= "      <td>Status:</td>\n";
            $output .= "      <td><input type='radio' name='status' value='active' ".$UTILITY->radioChecked("active", $this->listing->status)."><label>Active</label></td>\n";
            $output .= "      <td><input type='radio' name='status' value='inactive' ".$UTILITY->radioChecked("inactive", $this->listing->status)."><label>Inactive</label></td>\n";
            $output .= "    </tr>\n";
            $output .= "    <tr>\n";
            $output .= "      <td>Transaction:</td>\n";
            $output .= "      <td><input type='radio' name='type' value='wanted' ".$UTILITY->radioChecked("Wanted", $this->listing->type)."><label>Wanted</label></td>\n";
            $output .= "      <td><input type='radio' name='type' value='forsale' ".$UTILITY->radioChecked("For Sale", $this->listing->type)."><label>For Sale</label></td>\n";
            $output .= "    </tr>\n";
            if (!empty($this->listing->listingId)) {
                $output .= "    <tr>\n";
                $output .= "      <td>Category:</td>\n";
                $output .= "      <td colspan='2'>\n";
                $output .= "        ".getSelectDDM($UTILITY->getcategories(), "categoryId", "categoryid", "categoryname",  NULL, $this->listing->categoryId)."\n";
                $output .= "      </td>\n";
                $output .= "    </tr>\n";
            }
            $output .= "    <tr>\n";
            $output .= "      <td>Sub-Cat:</td>\n";
            $output .= "      <td colspan='2'>\n";
            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            if (!isset($this->listing->listingId)) {
                $output .= "        ".getSelectDDM($UTILITY->getsubCategories($categoryId), "subCategoryId", "subcategoryid", "subcategoryname", NULL, NULL, "Select")."\n";
            } else {
               $output .= "        ".getSelectDDM($UTILITY->getsubCategories(), "subCategoryId", "subcategoryid", "subcategoryname", NULL, $this->listing->subCategoryId)."\n";
            }
            //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            $output .= "      </td>\n";
            $output .= "    </tr>\n";
            $output .= "    <tr>\n";
            $output .= "      <td>Box Type:</td>\n";
            $output .= "      <td colspan='2'>\n";
            if (!isset($this->listing->listingId)) {
                $output .= "        ".getSelectDDM($UTILITY->getboxtypes(), "boxtypeid", "boxtypeid", "boxtypename", NULL, NULL, "Select")."\n";
            } else {
                $output .= "        ".getSelectDDM($UTILITY->getboxtypes(), "boxtypeid", "boxtypeid", "boxtypename", NULL, $this->listing->boxtypeId)."\n";
            }
            $output .= "      </td>\n";
            $output .= "    </tr>\n";
            $output .= "    <tr>\n";
//adding the year format based on the category selection
            if (!isset($this->listing->listingId)) {
                if ($UTILITY->getYearFormatTypeId($categoryId) == 1) {
                    $output .= "      <td valign='top'>Years:</td>\n";
                    $output .= "      <td colspan='2'>\n";
                    $output .= "        <input type='text' name='year' id='year1' placeholder='YY/Y' size='4' onblur='checkYear1()'>\n";
                    //pattern='#^[0-9]{2}/[0-9]{1}$#'
                    $output .= "        <br />\n";
                    $output .= "        <span style='font-size: 0.8em;'>\n";
                    $output .= "          format: yyyy or yy/y for BK or HKY\n";
                    $output .= "          <br />\n";
                    $output .= "          Blank if n/a\n";
                }
                if ($UTILITY->getYearFormatTypeId($categoryId)== 2) {
                    $output .= "      <td valign='top'>Years:</td>\n";
                    $output .= "      <td colspan='2'>\n";
                    $output .=  "       <input type='text' name='year' id='year2' placeholder='YYYY' size='4' onblur='checkYear2()'>\n";
                    //pattern='#^[0-9]{4}$#'
                    $output .= "        <br />\n";
                    $output .= "        <span style='font-size: 0.8em;'>\n";
                    $output .= "          format: yyyy or yy/y for BK or HKY\n";
                    $output .= "          <br />\n";
                    $output .= "          Blank if n/a\n";
                }
//show the year if it's an edit
            } else {
                $output .= "        <input type='text' name='year' value='".$this->listing->year."' size='4' readonly>\n";
            }
            $output .= "        </span>\n";
            $output .= "      </td>\n";
            $output .= "    </tr>\n";
            $output .= "    <tr>\n";
            $output .= "      <td>Price:</td>\n";
            $output .= "      <td><input type='text' name='dprice' value='".$this->listing->dprice."' size='7'></td>\n";
            $output .= "    </tr>\n";
            $output .= "    <tr>\n";
            $output .= "      <td>Unit:</td>\n";
            $output .= "      <td>\n";
            $output .= "        <select name='uom' id='uom' readonly>\n";
            $output .= "          <option value=''></option>\n";
            $output .= "          <option value='box' ".$UTILITY->selected("box", $this->listing->uom).">Box</option>\n";
            $output .= "          <option value='case' ".$UTILITY->selected("case", $this->listing->uom).">Case</option>\n";
            $output .= "        </select>\n";
            $output .= "      </td>\n";
            $output .= "    </tr>\n";
            $output .= "    <tr>\n";
            $output .= "      <td></td>\n";
            $output .= "      <td colspan='2'>Boxes Per Case: <input type='text' name='boxespercase' id='boxespercase' value='".$this->listing->boxespercase."' size='4'></td>\n";
            $output .= "    </tr>\n";
            $output .= "    <tr>\n";
            $output .= "      <td></td>\n";
            $output .= "      <td></td>\n";
            $output .= "      <td></td>\n";
            $output .= "    </tr>\n";
            $output .= "    <tr>\n";
            $output .= "      <td colspan='2'>\n";
            $output .= "        MIN QTY: <input type='text' name='minQuantity' value='".$this->listing->minQuantity."' size='4'>\n";
            $output .= "      </td>\n";
            $output .= "      <td align='right'>\n";
            $output .= "        QTY: <input type='text' name='quantity' value='".$this->listing->quantity."' size='4'>\n";
            $output .= "      </td>\n";
            $output .= "    </tr>\n";
            $output .= "    <tr>\n";
            $output .= "      <td colspan='3' align='left'>\n";
            $output .= "        Item Description/Notes:<br /><textarea name='listingNotes' cols='40' rows='4'>".$this->listing->listingNotes."</textarea>\n";
            $output .= "      </td>\n";
            $output .= "    </tr>\n";
            $output .= "    <tr>\n";
            $output .= "      <td colspan='3' align='left'>\n";
            $output .= "        IMAGES\n";
            $output .= "      </td>\n";
            $output .= "    </tr>\n";
            $output .= "    <tr>\n";
            $output .= "      <td colspan='3' align='left'>\n";
            $output .= "          <span style='font-size: 0.8em;'>\n";
            $output .= "        Provide the link to an exixting image:\n";
            $output .= "        <br />\n";
            $output .= "        format: http//www.xxx.com/images/xyz.gif\n";
            $output .= "        <br />\n";
            $output .= "        <input type='text' name='picturePath' value='".$this->listing->picture."'>\n";
            $output .= "      </td>\n";
            $output .= "    </tr>\n";
            $output .= "    <tr>\n";
            $output .= "      <td colspan='3' align='left'>\n";
            $output .= "        OR\n";
            $output .= "      </td>\n";
            $output .= "    </tr>\n";
            $output .= "    <tr>\n";
            $output .= "      <td colspan='3' align='left'>\n";
            $output .= "        Upload A New Image (gif & jpg only - Max:".(round(($CFG->IMG_MAX_UPLOAD/1000000),2))."MB)\n";
            $output .= "        <br />\n";
            $output .= "        <input type='file' name='pictureUp' id='pictureUp'>\n";//where to upload
            $output .= "      </td>\n";
            $output .= "    </tr>\n";
            $output .= "    <tr>\n";
            $output .= "      <td></td>\n";
            $output .= "      <td></td>\n";
            $output .= "      <td></td>\n";
            $output .= "    </tr>\n";
            $output .= "    <tr>\n";
            $output .= "      <td colspan='2' align='center'>\n";
            $output .= "        <input type='submit' name='save' id='save' value='SAVE'>\n";
//$output .= "        <a href='' onclick=\"javascript:document.sub.submit();return false;\"><button>SAVE</button></a>\n";
            $output .= "      </td>\n";
            $output .= "      <td>\n";
            $output .= "        <a href='?cancelled' /><button>CANCEL</button>\n";//////////////////////////////////////????????????
            $output .= "      </td>\n";
            $output .= "    </tr>\n";
            $output .= "  </table>\n";
            $output .= "  <input type='hidden' name='listingId' value='".$this->listing->listingId."'>\n";
            $output .= "</form>\n";

//JAVASCRIPT///////////////////////////////////////////////////////////////////////
            $output .= "<script src='scripts/validate.js'></script>\n";

            $output .= "<script>\n";
            $output .= "  function checkYear1() {\n";
            $output .= "    var year1 = document.getElementById('year1').value;\n";
            $output .= "    if (typeof year1 !== 'undefined') {\n";
            $output .= "      if(year1.match(/^[0-9]{2}[/][0-9]{1}$/)){\n";
            $output .= "        return true;\n";
            $output .= "      } else {\n";
            $output .= "        alert('Accepted Format for Year: 00/0');\n";
            $output .= "        return false;\n";
            $output .= "      }\n";
            $output .= "    }\n";
            $output .= "  }\n";

            $output .= "  function checkYear2() {\n";
            $output .= "    var year2 = document.getElementById('year2').value;\n";
            $output .= "    if (typeof year2 !== 'undefined') {\n";
            $output .= "      if(year2.match(/^[0-9]{4}$/)){\n";
            $output .= "        return true;\n";
            $output .= "      } else {\n";
            $output .= "        alert('Accepted Format for Year: 0000');\n";
            $output .= "        return false;\n";
            $output .= "      }\n";
            $output .= "    }\n";
            $output .= "  }\n";
            $output .= "\n";
            $output .= "\n";
            $output .= "\n";
            $output .= "\n";
            $output .= "\n";

            $output .= "  function checkUOM() {\n";
            $output .= "    var uom = document.getElementById('uom').value;\n";
            $output .= "    var bpc = parseInt(document.getElementById('boxespercase').value);\n";
            $output .= "    if (uom === 'case' &&  bpc <= 0 || uom === 'case' && isNaN(bpc)) {\n";
            $output .= "      alert('Enter # of Boxes Per Case');\n";
            $output .= "      return false;\n";
            $output .= "    }\n";
            $output .= "  }\n";
            $output .= "</script>\n";
            ////////////////////////////////////////////////////////////////javascript//////

        }


        return $output;

    }

 }
?>