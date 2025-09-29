<?php
require_once('templateMarket.class.php');

DEFINE("RESTRICTIONFORSALE", 86400);   // 1 day prior
DEFINE("RESTRICTIONWANTED",  172800);   // 2 days prior

$page = new templateMarket(LOGIN, SHOWMSG);
$calendarJS = '
    $(function(){$("#expireson").datepicker();});
    $(function(){$("#deliverby").datepicker();});
';
$page->jsInit($calendarJS);

$listing = new listing();

$target = $CFG->listingsPath;

$addListing         = optional_param('addListing', NULL, PARAM_INT);
$boxespercase       = optional_param('boxespercase', NULL, PARAM_INT);
$categoryid         = optional_param('categoryid', NULL, PARAM_INT);
$subcategoryid      = optional_param('subcategoryid', NULL, PARAM_INT);
$boxtypeid          = optional_param('boxtypeid', NULL, PARAM_INT);
$year               = optional_param('year', NULL, PARAM_TEXT);
$commiteditListing  = optional_param('commiteditListing', NULL, PARAM_INT);
$deleteListing      = optional_param('deleteListing', NULL, PARAM_INT);
$editListing        = optional_param('editListing', NULL, PARAM_INT);
$isGaming           = optional_param('isGaming', NULL, PARAM_INT);
$listingId          = optional_param('listingId', NULL, PARAM_TEXT);
$listingNotes       = optional_param('listingNotes', NULL, PARAM_TEXT);
$expiresOn          = optional_param('expireson', NULL, PARAM_TEXT);
$deliverBy          = optional_param('deliverby', NULL, PARAM_TEXT);
$minQuantity        = optional_param('minQuantity', NULL, PARAM_INT);
$new                = optional_param('new', NULL, PARAM_INT);
$dprice             = optional_param('dprice', NULL, PARAM_NUM_NO_COMMA);
$quantity           = optional_param('quantity', NULL, PARAM_INT);
$save               = optional_param('save', NULL, PARAM_TEXT);
$cancel             = optional_param('cancel', NULL, PARAM_TEXT);
$status             = optional_param('status', 'OPEN', PARAM_TEXT);
$title              = optional_param('title', NULL, PARAM_TEXT);
$type               = optional_param('type', NULL, PARAM_TEXT);
$uom                = optional_param('uom', NULL, PARAM_TEXT);
$shareImage         = optional_param('shareimg', 1, PARAM_INT);

if (strlen($year) == 0) {
    $year = NULL;
}

if (empty($categoryid)) {
    $url = "https://dealernetx.com/adminmulti.php?id=products";
    $link = "<a href='".$url."' target='_blank'>FAQ: Add Sports / Gaming Listing</a>";
    $page->messages->addInfoMsg("This is for supply listings only.  If you want to add a gaming or sports listing please click on this ".$link);
}

if ($cancel == "Cancel") {
    $cancelParams = "?pgimsg=".URLEncode("Create listing cancelled.");
    $cancelParams .= ($categoryid) ? ("&categoryid=".$categoryid) : "";
    $cancelParams .= ($year) ? ("&year=".URLEncode($year)) : "";
    $cancelParams .= ($boxtypeid) ? ("&boxtypeid=".$boxtypeid) : "";
    header("location:mylistingcats.php".$cancelParams);
}

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
    if ($uom != 'case') {
        $boxespercase = 1;
    }
    if (checkFactoryDups($status, $type, $categoryid, $subcategoryid, $boxtypeid, $year, $uom)) {
        $targetURL = NULL;
        $documentPath = NULL;
        $documentUp = [];
        $acceptOffers = 1;
        if ($newId = $listing->addListing($status, $type, $categoryid, $subcategoryid, $boxtypeid, $year,
                                          $dprice, $uom, $boxespercase,
                                          $minQuantity, $quantity, $listingNotes, NULL,
                                          $picturePath, $pictureUp,
                                          $target, $targetURL, $title,
                                          $documentPath, $documentUp,
                                          $acceptOffers, $expiresOn, $deliverBy, $shareImage)) {
            setGlobalListingTypeId($categoryid);
            $msgs = "&pgsmsg=".URLEncode("Successfully added listing.");
            if ($collarMsg = $UTILITY->checkCollar($newId, false)) {
                $msgs .= "&pgwmsg=".URLEncode($collarMsg);
            }
            $cattypeid = $page->db->get_field_query("SELECT categorytypeid FROM categories WHERE categoryid=".$categoryid);
            if ($cattypeid == LISTING_TYPE_SUPPLY) {
                header("location:supplySummary.php?subcategoryid=".$subcategoryid."&categoryid=".$categoryid.$msgs);
            } else {
                header("location:listing.php?subcategoryid=".$subcategoryid."&categoryid=".$categoryid."&boxtypeid=".$boxtypeid."&listingtypeid=".$listingTypeId."&year=".URLEncode($year).$msgs);
            }
        } else {
            if (!empty($pictureUp)) {
                $page->messages->addWarningMsg("For security reasons you must reselect your image.");
            }
        }
    }
} else {
    if (!empty($expiresOn)) {
        $expiresDateTime = strtotime($expiresOn);
        if (! $expiresDateTime) {
            $page->messages->addErrorMsg("Invalid Expires On Date");
        }
    }

    if (!empty($deliverBy)) {
        $deliverDateTime = strtotime($deliverBy);
        if (! $deliverDateTime) {
            $page->messages->addErrorMsg("Invalid Deliver By Date");
        }
    }
}

if (!empty($categoryid) && !empty($subcategoryid) && !empty($boxtypeid)) {
    $releasedate = $page->utility->getReleaseDate($categoryid, $subcategoryid, $boxtypeid, $year);
    if (!empty($releasedate) && (strtotime("now") < $releasedate)) {
        $d = date("F, j Y", $releasedate);
        $fsd = date("F, j Y", $releasedate - RESTRICTIONFORSALE);
        $bd = date("F, j Y", $releasedate - RESTRICTIONWANTED);
        $msg  = "This product releases on ".$d.".<br />";
        $msg .= "<span style='font-weight:normal;padding-left:25px;'> - You can post a listing to buy on ".$bd.".</span><br />";
        $msg .= "<span style='font-weight:normal;padding-left:25px;'> - You can post a listing to sell on ".$fsd.".</span>";
        $page->messages->addInfoMsg($msg);
    }
}

echo $page->header('Create Update Listing');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $CFG, $page, $add, $boxtypeid, $categoryid, $listingId, $subcategoryid, $type, $year, $status, $uom, $boxespercase, $dprice, $quantity, $listingNotes, $releasedate, $expiresOn, $deliverBy, $shareImage;

    $exists = null;
    $allowed = true;
    $disableIfNotWanted = ($type == 'For Sale') ? "disabled" : "";

    echo "<article>\n";
    echo "  <div class='entry-content'>\n";

    if ($categoryid <= 0) {
        echo "<form action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
        echo "  Category: ".getSelectDDM($page->utility->getcategories(1, false, LISTING_TYPE_SUPPLY), "categoryid", "categoryid", "categorydescription",  NULL, $categoryid, "Select",
                                         NULL, NULL, NULL, " onChange=\"javascript: this.form.submit();\"")."\n";
        echo "</form>\n";
    }

    if ($categoryid > 0) {
        $categoryTypeId = $page->db->get_field_query("SELECT categorytypeid FROM categories WHERE categoryid=".$categoryid);

        echo "  <form class='entry-form' name='sub' id='sub' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post' enctype='multipart/form-data' onsubmit='return checkUOM(), checkYear()'>\n";
        echo "    <input type='hidden' name='listingId' value='".$listingId."'>\n";
        echo "    <input type='hidden' name='categoryid' value='".$categoryid."'>\n";
        echo "    <input type='hidden' name='status' value='OPEN'>\n";
        if (empty($type)) {
            $catname = $page->utility->getCategoryDesc($categoryid);
            echo "    <div class='row'>\n";
            echo "      <div class='col-25'>Category:</div>\n";
            echo "      <div class='col-75'>".$catname."</div>\n";
            if (!empty($subcategoryid)) {
                echo "      <div class='col-25'>Subcategory:</div>\n";
                echo "      <div class='col-75'>\n";
                $subcatname = $page->utility->getSubCategoryName($subcategoryid);
                echo "        ".$subcatname."\n";
                echo "        <input type='hidden' name='subcategoryid' value='".$subcategoryid."'>\n";
                echo "      </div>\n";
            }
            if (!empty($boxtypeid)) {
                echo "      <div class='col-25'>Box Type:</div>\n";
                echo "      <div class='col-75'>\n";
                $boxtypename = $page->utility->getBoxTypeName($boxtypeid);
                echo "        ".$boxtypename."\n";
                echo "        <input type='hidden' name='boxtypeid' value='".$boxtypeid."'>\n";
                echo "      </div>\n";
            }
            if (!empty($year)) {
                echo "      <div class='col-25'>Year:</div>\n";
                echo "      <div class='col-75'>\n";
                echo "        ".$year."\n";
                echo "        <input type='hidden' name='year' value='".$year."'>\n";
                echo "      </div>\n";
            }
            echo "    </div>\n";
        }
        $wdisabled = "";
        $fsdisabled = "";
        if (!empty($releasedate)) {
            $restricteddate = $releasedate - RESTRICTIONFORSALE;
            $fsdisabled = (strtotime("now") < $restricteddate) ? "disabled" : "";
            $restricteddate = $releasedate - RESTRICTIONWANTED;
            $wdisabled = (strtotime("now") < $restricteddate) ? "disabled" : "";
            $allowed = (empty($fsdisabled) || empty($wdisabled)) ? true : false;
        }
        if ($allowed) {
            $style = (empty($type)) ? "font-weight:bold;padding-top:5px;padding-bottom:5px;" : "";
            echo "    <div class='row' style='".$style."'>\n";
            echo "      <div class='col-25'>\n";
            echo "        <legend>Listing Type:</legend>\n";
            echo "      </div>\n";
            echo "      <div class='col-75'>\n";
            $wantedOnClick = "onClick='$(\"#expireson\").attr(\"disabled\", false);$(\"#deliverby\").attr(\"disabled\", false);'";
            $forsaleOnClick = "onClick='$(\"#expireson\").attr(\"disabled\", true);$(\"#deliverby\").attr(\"disabled\", true);'";
            $onchange = (!empty($type)) ? NULL :  "onChange=\"javascript: this.form.submit();\"";
            echo "        <input type='radio' name='type' value='Wanted' ".$wantedOnClick." ".checked($type, "Wanted")." ".$onchange." ".$wdisabled."><label>Buy - post a listing looking to purchase this product</label><br>\n";
            echo "        <input type='radio' name='type' value='For Sale' ".$forsaleOnClick." ".checked($type, "For Sale")." ".$onchange." ".$fsdisabled."><label>Sell - post a listing looking to sell this product</label>\n";
        }
        echo "      </div>\n";
        echo "    </div>\n";
        if (empty($type)) {
            echo "    <div style='height:100px;'></div>\n";
        }
        if (!empty($type) && $allowed) {
            echo "    <div class='row'>\n";
            echo "      <div class='col-25'>\n";
            echo "        <label>Category:</label>\n";
            echo "      </div>\n";
            echo "      <div class='col-75'>\n";
            if (empty($categoryid)) {
                echo "      ".getSelectDDM($page->utility->getcategories(1, false, LISTING_TYPE_SUPPLY), "categoryid", "categoryid", "categoryname",  NULL, $categoryid, "Select",
                                               NULL, NULL, NULL, " onChange=\"javascript: this.form.submit();\"")."\n";
            } else {
                $catname = $page->utility->getCategoryDesc($categoryid);
                echo "      ".$catname."\n";
                echo "      <input type='hidden' name='categoryid' value='".$categoryid."'>\n";
            }
            echo "      </div>\n";
            echo "    </div>\n";
            if (!empty($categoryid)) {
                echo "    <div class='row'>\n";
                echo "      <div class='col-25'>\n";
                echo "        <label>Sub-Cat:</label>\n";
                echo "      </div>\n";
                echo "      <div class='col-75'>\n";
                if (empty($subcategoryid)) {
                    echo "      ".getSelectDDM($page->utility->getsubCategories($categoryid), "subcategoryid", "subcategoryid", "subcategoryname", NULL, $subcategoryid, "Select",
                                               NULL, NULL, NULL, " onChange=\"javascript: this.form.submit();\"")."\n";
                } else {
                    $subcatname = $page->utility->getSubCategoryName($subcategoryid);
                    echo "      ".$subcatname."\n";
                    echo "      <input type='hidden' name='subcategoryid' value='".$subcategoryid."'>\n";
                }
                echo "      </div>\n";
                echo "    </div>\n";

                if ($categoryTypeId == LISTING_TYPE_CLASSIFIED) {
                    echo "    <div class='row'>\n";
                    echo "      <div class='col-25'>\n";
                    echo "        <label>Title:</label>\n";
                    echo "      </div>\n";
                    echo "      <div class='col-75'>\n";
                    echo "        <input type='text' name='title' id='title' size='50'>\n";
                    echo "      </div>\n";
                    echo "    </div>\n";
                }
                if (!empty($subcategoryid)) {
                    echo "    <div class='row'>\n";
                    echo "      <div class='col-25'>\n";
                    echo "        <label>Box Type:<label>\n";
                    echo "      </div>\n";
                    echo "      <div class='col-75'>\n";
                    if ($categoryTypeId == LISTING_TYPE_SUPPLY) {
                        echo "Supplies<input type=hidden name=boxtypeid id=boxtypeid value='".BOX_TYPE_SUPPLIES."' />";
                    } else {
                        if ($categoryTypeId == LISTING_TYPE_CLASSIFIED) {
                            echo "Classified<input type=hidden name=boxtypeid id=boxtypeid value='".BOX_TYPE_CLASSIFIED."' />";
                        } else {
                            if (empty($boxtypeid)) {
                                echo "        ".getSelectDDM($page->utility->getProductBoxTypes($categoryid, $subcategoryid), "boxtypeid", "boxtypeid", "boxtypename", NULL, $boxtypeid, "Select",
                                               NULL, NULL, NULL, " onChange=\"javascript: this.form.submit();\"")."\n";
                            } else {
                                $boxtypename = $page->utility->getBoxTypeName($boxtypeid);
                                echo "      ".$boxtypename."\n";
                                echo "      <input type='hidden' name='boxtypeid' value='".$boxtypeid."'>\n";
                            }
                        }
                    }
                    echo "      </div>\n";
                    echo "    </div>\n";
                    if (($categoryTypeId == LISTING_TYPE_SPORTS && !empty($boxtypeid))  ||
                        ($categoryTypeId == LISTING_TYPE_SUPPLY) ||
                        ($categoryTypeId == LISTING_TYPE_GAMING)) {
                        if ($categoryTypeId == LISTING_TYPE_SPORTS) {
                            echo "    <div class='row'>\n";
                            echo "      <div class='col-25'>\n";
                            echo "        <label>Years:</label>\n";
                            echo "      </div>\n";
                            echo "      <div class='col-75'>\n";
                            $yearFormatTypeId = $page->utility->getYearFormatTypeId($categoryid);
                            if ( $yearFormatTypeId == 1) {
                                if (empty($year)) {
                                    echo "        <input type='text' name='year' id='year1' value='".$year."' placeholder='YY/Y' style='width:6em;' onblur='checkYear1()' />\n";
                                } else {
                                    echo "        ".$year."\n";
                                    echo "        <input type='hidden' name='year' id='year1' value='".$year."' />\n";
                                }
                            } else {
                                if ( $yearFormatTypeId == 2) {
                                    if (empty($year)) {
                                        echo  "       <input type='text' name='year' id='year2' value='".$year."' placeholder='YYYY' style='width:6em;' onblur='checkYear2()' />\n";
                                    } else {
                                        echo "        ".$year."\n";
                                        echo "        <input type='hidden' name='year' id='year1' value='".$year."' />\n";
                                    }
                                } else {
                                    echo "        <input type='hidden' name='year' value='' />N/A\n";
                                }
                            }
                            if (empty($year)) {
                                echo "    <div class='row'>\n";
                                echo "      <div class='col-75'>\n";
                                echo "        <input type='submit' value='Retrieve Product Information'>\n";
                                echo "      </div>\n";
                                echo "    </div>\n";
                            }
                            echo "      </div>\n";
                            echo "    </div>\n";
                        } else {
                            echo "        <input type='hidden' name='year' value='' />\n";
                        }

                        if (($categoryTypeId == LISTING_TYPE_SPORTS && !empty($year)) ||
                            ($categoryTypeId == LISTING_TYPE_GAMING)) {
                            $exists = $page->utility->doesProductExist($categoryid, $subcategoryid, $boxtypeid, $year);
                            if (empty($exists)) {
                                $url = "/listingCreate.php?categoryid=".$categoryid."&type=".$type;
                                $link = "<a href='".$url."'><b>start over</b></a>";
                                $url2 = "/sendmessage.php?dept=1";
                                $link2 = "<a href='".$url."' target='_blank'><b>Help Desk</b></a>";
                                echo "    <div class='row' style='padding: 10px;background-color: #CCC;border:1px solid black;'>\n";
                                echo "      <b>NOTICE: </b>\n";
                                echo "      <p>\n";
                                $msg  = "No product exists for Category / Subcategory / Boxtype";
                                $msg .= ($categoryTypeId == LISTING_TYPE_SPORTS) ? " / Year." : ".";
                                $msg .= "  Please ".$link." or send a message to the ".$link2." to create the product for you.\n";
                                echo "      ".$msg;
                                echo "      </p>\n";
                                echo "    </div>\n";
                            } else {
                                $upc = $page->utility->getUPC($categoryid, $subcategoryid, $boxtypeid, $year);
                                if (!empty($upc)) {
                                    $variation = $page->utility->getVariation($categoryid, $subcategoryid, $boxtypeid, $year);
                                    echo "    <div class='row'>\n";
                                    echo "      <div class='col-25'>\n";
                                    echo "        <label>Variation:</label>\n";
                                    echo "      </div>\n";
                                    echo "      <div class='col-75'>\n";
                                    echo "      ".$variation."\n";
                                    echo "      </div>\n";
                                    echo "    </div>\n";
                                    echo "    <div class='row'>\n";
                                    echo "      <div class='col-25'>\n";
                                    echo "        <label>UPC:</label>\n";
                                    echo "      </div>\n";
                                    echo "      <div class='col-75'>\n";
                                    echo "      ".$upc."\n";
                                    echo "      </div>\n";
                                    echo "    </div>\n";
                                    $url = "/sendmessage.php?dept=1";
                                    $link = "<a href='".$url."' target='_blank'>Help Desk</a>";
                                    echo "    <div class='row' style='padding: 10px;background-color: #CCC;border:1px solid black;'>\n";
                                    echo "      <b>NOTICE: </b>\n";
                                    echo "      <p>\n";
                                    if ((strpos($upc, "<br>") === false) && (strpos($upc, ",") === false)) {
                                        echo "        Please be sure that your product UPC matches <b>".$upc."</b>.\n";
                                    } else {
                                        $x = str_replace("<br>", ", ", $upc);
                                        echo "        Please be sure that your product UPC matches one of the UPCs shown: <b>".$x."</b>.\n";
                                    }
                                    echo "        <br/>If it does not, please find the correct product. If you are unable, please send a message to the ".$link."\n";
                                    echo "      </p>\n";
                                    echo "    </div>\n";
                                }
                            }
                        }

                        if (!empty($exists) ||
                            ($categoryTypeId == LISTING_TYPE_SUPPLY)) {
                            if (($categoryTypeId == LISTING_TYPE_SPORTS && !empty($year)) ||
                                ($categoryTypeId == LISTING_TYPE_GAMING) ||
                                ($categoryTypeId == LISTING_TYPE_SUPPLY)) {
                                echo "    <div class='row'>\n";
                                echo "      <div class='col-25'>\n";
                                echo "        <label>Price:<label>\n";
                                echo "      </div>\n";
                                echo "      <div class='col-75'>\n";
                                echo "        <input type='text' name='dprice' value='".$dprice."' style='text-align:right; width: 7em;'>\n";
                                echo "      </div>\n";
                                echo "    </div>\n";

                                echo "    <div class='row'>\n";
                                echo "      <div class='col-25'>\n";
                                echo "        <label>Unit:</label>\n";
                                echo "      </div>\n";
                                echo "      <div class='col-75'>\n";
                                if (($categoryTypeId == LISTING_TYPE_CLASSIFIED) || ($categoryTypeId == LISTING_TYPE_SUPPLY)) {
                                    echo "Other<input type='hidden' name='uom' id='uom' value='other' />";
                                } else {
                                    $onChange = " onchange = \"($(this).val() == 'case') ? $('#bpcspan').show() : $('#bpcspan').hide();\"";
                                    echo "        <select name='uom' id='uom' ".$onChange.">\n";
                                    echo "          <option value=''></option>\n";
                                    echo "          <option value='box' ".selected($uom, "box").">Box</option>\n";
                                    echo "          <option value='case' ".selected($uom, "case").">Case</option>\n";
                                    echo "          <option value='other' ".selected($uom, "other").">Other</option>\n";
                                    echo "        </select>\n";
                                    $showBPC = ($uom != "case") ? " style='display:none;'" : "";
                                    echo "&nbsp;<span name='bpcspan' id='bpcspan' ".$showBPC.">&nbsp;Boxes Per Case: <input type='text' name='boxespercase' id='boxespercase' value='".$boxespercase."' style='width:4em;'></span>\n";
                                }
                                echo "      </div>\n";
                                echo "    </div>\n";
                                echo "    <div class='row'>\n";
                                echo "      <div class='col-25'>\n";
                                echo "        <label>Quantity:</label>\n";
                                echo "      </div>\n";
                                echo "      <div class='col-75'>\n";
                                if ($categoryTypeId == LISTING_TYPE_CLASSIFIED) {
                                    echo "        <input type='text' name='quantity' value='' style='width: 6em;' />";
                                } else {
                                    echo "        <input type='text' name='quantity' value='".$quantity."' style='width: 6em;' /><input type='hidden' name='minQuantity' value='1'>";
                                }
                                echo "      </div>\n";
                                echo "    </div>\n";
                                echo "    <div class='row'>\n";
                                echo "      <div class='col-25'>\n";
                                echo "        <label>Expiration Date:</label>\n";
                                echo "      </div>\n";
                                echo "      <div class='col-75'>\n";
                                echo "        <input type=text size=10 name='expireson' id='expireson' value='".$expiresOn."' ".$disableIfNotWanted." />";
                                echo "      </div>\n";
                                echo "    </div>\n";
                                echo "    <div class='row'>\n";
                                echo "      <div class='col-25'>\n";
                                echo "        <label>Delivery Required By:</label>\n";
                                echo "      </div>\n";
                                echo "      <div class='col-75'>\n";
                                echo "        <input type=text size=10 name='deliverby' id='deliverby' value='".$deliverBy."' ".$disableIfNotWanted." />";
                                echo "      </div>\n";
                                echo "    </div>\n";
                                echo "    <div class='row'>\n";
                                echo "        <label>   Item Description/Notes:<br /></label><textarea name='listingNotes' id='listingNotes' cols='80' rows='8'>".$listingNotes."</textarea>\n";
                                echo "    </div>\n";
                                echo "    <div class='row'>\n";
                                echo "      <label>IMAGES</label>\n";
                                echo "      <label>Upload A New Image (gif & jpg only - Max:".(round(($CFG->IMG_MAX_UPLOAD/1000000),2))."MB)</label>\n";
                                echo "      <br />\n";
                                echo "      <input type='file' name='pictureup' id='pictureup'>\n";
                                echo "      <input type='hidden' id='privateimg' name='shareimg' value='0' />\n";
                                echo "    </div>\n";
                                echo "    <div class='row'>\n";
                                echo "      <input type='submit' name='save' id='save' value='SAVE'>\n";
                                echo "      <input type='submit' name='cancel' id='cancel' value='Cancel'>\n";
                                echo "    </div>\n";
                            } // year not empty or gaming or supply
                        }   // exists or supplies
                    }   // boxtype not empty or supply
                }   // subcat not empty
            }   // cat not empty
        }   // type not empty
        echo "    <input type='hidden' name='listingId' value=''>\n";
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

function selected($check, $checked) {
    if ($check == $checked) {
        $data = " selected ";
    } else {
        $data = "";
    }

    return $data;
}

function checkFactoryDups($status, $type, $categoryid, $subcategoryid, $boxtypeid, $year, $uom) {
    global $page;

    $isValid = true;

    // Do we need to be specific on type (Wanted/For Sale) and/or UOM?
    if ($page->user->isFactoryCost()) {
        $yearAnd =  ($year) ? " AND l.year='".$year."' " : "";
        $sql = "SELECT count(*)
            FROM listings l
            WHERE l.status='OPEN' ".$yearAnd."
              AND l.userid=".FACTORYCOSTID."
              AND l.categoryid=".$categoryid."
              AND l.subcategoryid=".$subcategoryid."
              AND l.boxtypeid='".$boxtypeid."'";
        $hasDups = $page->db->get_field_query($sql);
        if ($hasDups > 0) {
            $page->messages->addErrorMsg("There is already an active factory cost listing for this product.");
            $isValid = false;
        }
    }

    return $isValid;
}

?>