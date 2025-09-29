<?php

class listing {

    public $listingId;
    public $listingUserId;
    public $categoryId;
    public $categoryName;
    public $subCategoryId;
    public $subCategoryName;
    public $boxtypeId;
    public $boxtypeName;
    public $year;
    public $uom;
    public $boxespercase;
    public $minQuantity;
    public $quantity;
    public $title;
    public $type;
    public $acceptOffers;
    public $dprice;
    public $listingNotes;
    public $releaseDate;
    public $status;
    public $picture;
    public $document;
    public $displayDesc;
    public $loaded;


    public function __construct($listingId = NULL) {
        $this->loaded = false;
        $this->listingId = $listingId;
        if (!empty($listingId)) {
            $this->loadData($listingId);
        }
    }

    public function loadData($listingId) {
        global $UTILITY;
        $l = null;
        if ($listing = $this->getListing($listingId)) {
            $l = reset($listing);

            $this->listingUserId    = $l["listinguserid"];
            $this->categoryId       = $l["categoryid"];
            $this->categoryName     = $l["categoryname"];
            $this->subCategoryId    = $l["subcategoryid"];
            $this->subCategoryName  = $l["subcategoryname"];
            $this->boxtypeId        = $l["boxtypeid"];
            $this->boxtypeName      = $l["boxtypename"];
            $this->year             = $l["year"];
            $this->uom              = $l["uom"];
            $this->boxespercase     = $l["boxespercase"];
            $this->minQuantity      = $l["minquantity"];
            $this->quantity         = $l["quantity"];
            $this->title            = $l["title"];
            $this->type             = $l["type"];
            $this->acceptOffers     = $l["acceptoffers"];
            $this->dprice           = $l["dprice"];
            $this->boxprice         = $l["boxprice"];
            $this->listingNotes     = $l["listingnotes"];
            $this->releaseDate      = $l["releasedate"];
            $this->status           = $l["status"];
            $this->picture          = $l["picture"];
            $this->document         = $l["document"];
            $this->displayDesc      = NULL;
            $this->loaded = true;
        }
        return $l;
    }

    public function checkCardTypeYear($categoryId,$years) {
        global $page;

        $year4 = null; // Null means no year required / allowed

        $formatId = $page->db->get_field_query("SELECT yearformattypeid FROM categories WHERE categoryid=".$categoryId);

        if ($formatId) {
            if ($formatId == YEAR_FORMAT_4DIGIT) {
                $year4 = -1; // Year required not valid
                if (preg_match("#^[0-9]{4}$#", $years)) {
                    $digits4 = intval($years);
                    if ((1900 < $digits4) && ($digits4 < 2050)) {
                        $year4 = $digits4;
                    } else {
                        $page->messages->addErrorMsg("Year must be a 4 digit value between 1900 and 2050.");
                    }
                } else {
                        $page->messages->addErrorMsg("Invalid year format. Year must be a 4 digit value between 1900 and 2050.");
                }
            } else {
                if ($formatId == YEAR_FORMAT_2SLASH) {
                    $year4 = -1; // Year required not valid
                    if (preg_match("#^[0-9]{2}/[0-9]{1}$#", $years)) {
                        $year2 = intval(substr($years, 0, 2));
                        $nextYear = intval(substr($years, 1, 1)) + 1;
                        $nextDigit = ($nextYear == 10) ? '0' : strval($nextYear);
                        if ($nextDigit ==  substr($years, 3, 1)) {
                            if ($year2 > 50) {
                                $year4 = 1900 + $year2;
                            } else {
                                $year4 = 2000 + $year2;
                            }
                        } else {
                            $page->messages->addErrorMsg("Invalid following year. Year must be a 2 digit year followed by a slash and the next year. (e.g. ".$year2."/".$nextDigit.")");
                        }
                    } else {
                        $page->messages->addErrorMsg("Invalid year format. Year must be a 2 digit year followed by a slash and the next year. (e.g. 19/0)");
                    }
                } else {
                    if ($formatId == YEAR_FORMAT_OTHER) {
                        if ($years) {
                            $page->messages->addErrorMsg("Year is not supported for this listing category.");
                        }
                    }
                }
            }
        } else {
            $page->messages->addErrorMsg("Category does not have a valid year format");
        }

        return $year4;
    }

    public function validate ($yearFormatType, $quantity, $minQuantity) {
         global $UTILITY;

         if ($UTILITY->checkAmounts($more, $less) == TRUE) {

         }
    }

    public function getListing($listingId) {
        global $DB, $USER, $UTILITY;

        $sql = "
            SELECT lis.listingid, lis.categoryid, lis.subCategoryId, lis.boxtypeid, lis.year, lis.title, lis.acceptoffers,
                   lis.uom, lis.boxespercase, lis.type, lis.minquantity, lis.quantity, lis.dprice, lis.boxprice, lis.userid AS listinguserid,
                   lis.listingnotes, lis.releasedate, lis.status, lis.picture, lis.document, cat.categoryName, sub.subCategoryName, box.boxtypename
              FROM listings         lis
              JOIN categories       cat     ON lis.categoryid = cat.categoryId
              JOIN subcategories    sub     ON lis.subCategoryId = sub.subCategoryId
              JOIN boxtypes         box     ON lis.boxtypeid = box.boxtypeid
             WHERE lis.listingid = ".$listingId."
        ";
//user rights mall users
        //echo "<pre>".$sql."</pre>";
        $listing = $DB->sql_query($sql);

        return $listing;
    }


    public function commitEditListing($listingId, $status, $type, $categoryId, $subCategoryId, $boxTypeId, $year, $dprice, $uom, $boxespercase,
                                      $minQuantity, $quantity, $listingNotes, $picturePath, $pictureUp, $modifiedBy, $target, $title=NULL, $acceptOffers=1) {
        global $DB, $MESSAGES, $USER, $UTILITY;

        $success = TRUE;

        $categoryName = $UTILITY->getcategoryName($categoryId)."-".$year;
        $moreless = $UTILITY->checkAmounts($quantity, $minQuantity);
        $yearFormat = $this->checkCardTypeYear($categoryId, $year);

        if (empty($moreless)) {
            $MESSAGES->addErrorMsg('Error - MIN-QTY > QTY ');
            $success = false;
        }

// NEED TO MAKE THIS WORK
/*
        if (! $yearFormat)) {
            $MESSAGES->addErrorMsg('Error - Please check the year format ');
            $success = false;
        }
*/
        if ($success) {
            if (!empty($pictureUp['name'])) {
                if (file_exists($picturePath)) {
                    unlink($picturePath);
                }
                $img = imgUp($pictureUp, $listingId, $target, $page);
                $picture = $target.$img;
            } else {
                if (!empty($picturePath)) {
                    $picture = $picturePath;
                } else {
                    $picture = "";
                }
            }

            // if ($UTILITY->checkDuplicateName('boxTypes', 'boxTypeName', $boxTypeName, $originalName) == FALSE) {// need to add this all over!!!
                //($active == 0 ? $active = 0 : $active = 1);
                //($isGaming == 0 ? $isGaming = 0 : $isGaming = 1);

            $sql = "
                UPDATE listings SET status          = :status,
                                    type            = :type,
                                    categoryId      = :categoryId,
                                    subCategoryId   = :subCategoryId,
                                    boxTypeId       = :boxTypeId,
                                    year            = :year,
                                    dprice          = :dprice,
                                    uom             = :uom,
                                    boxespercase    = :boxespercase,
                                    minQuantity     = :minQuantity,
                                    quantity        = :quantity,
                                    listingNotes    = :listingNotes,
                                    picture         = :picture,
                                    title           = :title,
                                    acceptoffers    = :acceptoffers,
                                    modifiedBy      = :modifiedBy
                 WHERE listingId = :listingId
            ";
            $params = array();
            $params['listingId']        = $listingId;
            $params['status']           = $status;
            $params['type']             = $type;
            $params['categoryId']       = $categoryId;
            $params['subCategoryId']    = $subCategoryId;
            $params['boxTypeId']        = $boxTypeId;
            $params['year']             = $year;
            $params['dprice']           = $dprice;
            $params['uom']              = $uom;
            $params['boxespercase']     = $boxespercase;
            $params['minQuantity']      = $minQuantity;
            $params['quantity']         = $quantity;
            $params['listingNotes']     = $listingNotes;
            $params['picture']          = $picture;
            $params['title']            = $title;
            $params['acceptoffers']     = $acceptOffers;
            $params['modifiedBy']       = $USER->userId;

            $result = $DB->sql_execute_params($sql, $params);
            if ($result > 0) {
                $MESSAGES->addSuccessMsg('You have successfully edited ');
            } else {
                $MESSAGES->addErrorMsg('Error updating listing');
                $success = FALSE;
            }
        }

        return $success;
    }

    public function deleteListing($listingId, $picturePath) {
        global $DB;
        global $MESSAGES;

        $success = FALSE;
        $sql = "
            DELETE FROM listings WHERE listingid = ".$listingId."
        ";
        $delete = $DB->sql_execute_params($sql);

        if (file_exists($picturePath)) {
            unlink($picturePath);
        }
        if ($delete > 0) {
            $success = TRUE;
            $MESSAGES->addSuccessMsg('You have successfully deleted ');
        } else {
            $MESSAGES->addErrorMsg('Can not delete');
        }

        return $success;

    }

    public function nextval() {
        global $DB;

        $sql = "
            SELECT nextval('listings_listingid_seq')
        ";
        $imgId = $DB->get_field_query($sql);

        return $imgId;
    }

    public function nextSharedImage() {
        global $DB;

        $sql = "
            SELECT nextval('sharedimages_sharedimageid_seq')
        ";
        $imgId = $DB->get_field_query($sql);

        return $imgId;
    }

    public function addListing($status, $type, $categoryId, $subCategoryId, $boxTypeId, $year, $dprice, $uom, $boxespercase,
                               $minQuantity, $quantity, $listingNotes, $releaseDate,
                               $picturePath, array $pictureUp = NULL,
                               $target, $targetURL=NULL, $title = NULL,
                               $documentPath = NULL, array $documentUp = NULL,
                               $acceptOffers=1, $expiresOn=NULL, $deliverBy=NULL, $shareImage=0) {
        global $page, $DB, $MESSAGES, $USER, $UTILITY;

        $picture = NULL;
        $document = NULL;
        $id = NULL;
        $newProduct = false;

        $success = true;

        if (($type == 'Wanted') || ($type == 'For Sale')) {
            $sql = "SELECT count(pp.transactiontype) as numconfigged
                FROM preferredpayment pp
                JOIN paymenttypes pt ON pt.paymenttypeid=pp.paymenttypeid AND pt.active=1
                WHERE userid=".$page->user->userId."
                  AND transactiontype='".$type."'";
            $configged = $page->db->get_field_query($sql);
            if (! $configged) {
                $page->messages->addErrorMsg("You must add a preferred payment method for ".$type." transactions before you can add a ".$type." listing");
                $success = false;
                return $success;
            }
        } else {
            $page->messages->addErrorMsg("Transaction Type must be Wanted or For Sale");
            $success = false;
            return $success;
        }

        if (empty($uom)) {
            $page->messages->addErrorMsg("UOM must be selected");
            $success = false;
        } else {
            if ($uom == 'case') {
                if ($boxespercase < 1) {
                    $page->messages->addErrorMsg("You must specify the number of boxes per case.");
                    $success = false;
                }
            } else {
                if ($boxespercase != 1) {
                    $boxespercase = 1;
                }
            }
        }

        if (! ($dprice > 0)) {
            $page->messages->addErrorMsg("Price must be greater than 0.");
            $success = false;
        }

        $categoryName = $UTILITY->getcategoryName($categoryId)."-".$year;////////////////////////
        if ($quantity > 0) {
            $moreless = $UTILITY->checkAmounts($quantity, $minQuantity);
            if (! $moreless) {
                $page->messages->addErrorMsg('Error - MIN-QTY > QTY ');
                $success = false;
            }
        } else {
            $page->messages->addErrorMsg('Quantity must be greater than 0.');
            $success = false;
        }

        $year4 = $this->checkCardTypeYear($categoryId, $year);
        //echo "Year:".$year." Year4:".$year4."<br />\n";
        if ($year4) { // Category requires a year
            if ($year4 < 0) { // Invalid year entered
                $success = false;
            }
        }

        if ($success) {
            $doCheck = true;
            $yearWhere = "";
            if ($year) {
                //echo "addListing Current:".(date("Y"))." Entered:".$year."<br />\n";
                $currentYear = date("Y");
                $previousYear = $currentYear - 1;
                if (($year == $currentYear) || ($year == $previousYear)) {
                    $yearWhere = " and year='".$year."' ";
                } else {
                    $doCheck = false;
                }
            }

            if ($doCheck) {
                $categoryTypeId = $page->db->get_field_query("SELECT categorytypeid FROM categories WHERE categoryid=".$categoryId);
                if ($categoryTypeId != LISTING_TYPE_BLAST) {
                    $sql = "select count(*)
                        from listings l
                        where l.categoryid=".$categoryId."
                          and l.subcategoryid=".$subCategoryId."
                          and l.boxtypeid=".$boxTypeId."
                          and l.status='OPEN' ".$yearWhere;
                    //echo "Do Check<br />\n SQL:<pre>".$sql."</pre><br />\n";
                    $existing = $page->db->get_field_query($sql);
                    if ($existing == 0) {
                        if (($categoryTypeId == LISTING_TYPE_SPORTS) || ($categoryTypeId == LISTING_TYPE_GAMING)) {
                            $newProduct = true;
                        }
                    }
                }
            }
        }

        $expiresDateTime = null;
        $deliverDateTime = null;
        $tomorrowMorning = strtotime("tomorrow");

        if ($type == "Wanted") {
            if (empty($expiresOn)) {
                $expiresDateTime = strtotime("today + 181 days")-1;
                $expiresOn = date('m/d/Y', $expiresDateTime);
                $page->messages->addInfoMsg("Expires On date set to default of 180 days (".$expiresOn.")");
            } else {
                $expiresDateTime = strtotime($expiresOn." 23:59:59");
                if (! $expiresDateTime) {
                    $page->messages->addErrorMsg("Invalid Expires On Date");
                    $success = false;
                } else {
                    if ($expiresDateTime < $tomorrowMorning) {
                        $page->messages->addErrorMsg("Expires On date must be at least 1 day in the future.");
                        $success = false;
                    }
                }
            }
            if (!empty($deliverBy)) {
                $deliverDateTime = strtotime($deliverBy." 23:59:59");
                if (! $deliverDateTime) {
                    $page->messages->addErrorMsg("Invalid Deliver By Date");
                    $success = false;
                } else {
                    if ($deliverDateTime < $tomorrowMorning) {
                        $page->messages->addErrorMsg("Deliver By date must be at least 1 day in the future.");
                        $success = false;
                    }
                    if ($expiresDateTime && $deliverDateTime) {
                        if ($deliverDateTime < $expiresDateTime) {
                            $page->messages->addErrorMsg("Deliver By date must be greater than or equal to Expires On date.");
                            $success = false;
                        }
                    }
                }
            }
        } else {
            if (!empty($deliverBy)) {
                $page->messages->addWarningMsg("Deliver By Date ignored for For Sale listings.");
            }
        }


        if ($success) {
            $id = $this->nextval();
            if (!empty($picturePath)) {
                $picture = $picturePath;
            } else {
                if (!empty($pictureUp)) {
                    //echo "ID:".$id."<br />\ntarget:".$target."<br />\ntargetURL:".$targetURL."<br />\nPictureUp:<br />\n<pre>"; var_dump($pictureUp); echo "</pre><br />\n";
                    if ($img = prefixImgUp($pictureUp, $id, $target, $page)) { //advertLib function
                        $picture = (($targetURL) ? $targetURL : "").$img; //use this for the picture path
                    } else {
                        $success = false;
                    }
                } else {
                    $picture = NULL;
                }
            }
        }

        if ($success) {
            if (!empty($documentPath)) {
                $document = $documentPath;
            } else {
                if (!empty($documentUp)) {
                    $documentTarget = $page->cfg->blastDocPath;
                    //echo "ID:".$id."<br />\ntarget:".$documentTarget."<br />\ntargetURL:".$targetURL."<br />\nDocumentUp:<br />\n<pre>"; var_dump($documentUp); echo "</pre><br />\n";
                    if ($doc = prefixDocUp($documentUp, $id, $documentTarget, $page)) { //advertLib function
                        $document = (($targetURL) ? $targetURL : "").$doc;
                    } else {
                        $success = false;
                    }
                } else {
                    $document = NULL;
                }
            }
        }

//if ($UTILITY->checkCardTypeYear() {

//ADD TITLE TO EVERYTHING!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        if ($success) {
            $sql = "
                INSERT INTO listings( listingid,  userId,  status,  type,  categoryid,  subcategoryid,  boxtypeid,  year,  year4,  dprice,  uom,  boxespercase, boxprice,
                                      minquantity,  quantity, title,  listingnotes, releasedate, expireson, deliverby, acceptoffers,  picture, document,  createdby,  modifiedby)
                              VALUES(:listingid, :userid, :status, :type, :categoryid, :subcategoryid, :boxtypeid, :year, :year4, :dprice, :uom, :boxespercase, :boxprice,
                                     :minquantity, :quantity, :title, :listingnotes, :releasedate, :expireson, :deliverby, :acceptoffers, :picture, :document, :createdby, :modifiedby)
            ";

            $params = array();
            $params['userid']           = $USER->userId;;
            $params['listingid']        = $id;
            $params['status']           = $status;
            $params['type']             = $type;
            $params['categoryid']       = $categoryId;
            $params['subcategoryid']    = $subCategoryId;
            $params['boxtypeid']        = $boxTypeId;
            $params['year']             = $year;
            $params['year4']            = $year4;
            $params['dprice']           = $dprice;
            $params['uom']              = $uom;
            $params['boxespercase']     = $boxespercase;
            $params['boxprice']         = $dprice / $boxespercase;
            $params['minquantity']      = $minQuantity;
            $params['quantity']         = $quantity;
            $params['listingnotes']     = $listingNotes;
            $params['releasedate']      = $releaseDate;
            $params['expireson']        = $expiresDateTime;
            $params['deliverby']        = $deliverDateTime;
            $params['title']            = $title;
            $params['acceptoffers']     = $acceptOffers;
            $params['picture']          = $picture;
            $params['document']         = $document;
            $params['createdby']        = $USER->username;
            $params['modifiedby']       = $USER->username;
//echo "SQL:".$sql."<br />\n";
//echo "Params:<br />\n<pre>"; var_dump($params); echo "</pre><br />\n";
//exit;
            $result = $DB->sql_execute_params($sql, $params);
            if ($result > 0) {
                $page->messages->addSuccessMsg('You have successfully added ');
                if ($newProduct) {
                    $sql = "SELECT c.categorydescription, l.year, l.year4, bt.boxtypename, s.subcategoryname
                        FROM listings l
                        JOIN categories c ON c.categoryid=l.categoryid
                        JOIN boxtypes bt ON bt.boxtypeid=l.boxtypeid
                        JOIN subcategories s on s.subcategoryid=l.subcategoryid
                        WHERE l.listingid=".$id;
                    if ($results = $page->db->sql_query($sql)) {
                        $listing = reset($results);
                        $msgBody = "New Product Added\n".
                            "Category:".$listing['categorydescription']." | ".
                            "Year(4):".$listing['year']."(".$listing['year4'].") | ".
                            "Subcategory:".$listing['subcategoryname']." | ".
                            "Box Type:".$listing['boxtypename']."<br />".
                            "Link: <a href='listing.php?referenceid=".$id."' target='_blank'>Listing #".$id."</a>";
                        $page->iMessage->insertSystemMessage($page, ADMINUSERID, ADMINUSERNAME, "New Product Added", $msgBody, EMAIL);
                    }
                }
            } else {
                $page->messages->addErrorMsg('Error adding listing');
                $success = false;
            }
        }

        if ($success) {
            $success = $id;
        }

        if ($success) {
            if ($picture && $shareImage && $page->user->hasUserRightId(USERRIGHT_IMAGES)) {
                $publicId = $this->nextSharedImage();
                //echo "addListing: copy listing image ".$picture." to shared image ".$publicId."<br />\n";
                $publicFile = prefixCopyPublicImage($page->cfg->listings, $picture, $page->cfg->sharedImages, $publicId);
                if ($publicFile) {
                    //echo "addListing: copied listing image ".$picture." to shared image ".$publicFile."<br />\n";
                    $sql = "INSERT INTO sharedimages (sharedimageid, listingid, userid, categoryid, subcategoryid, boxtypeid, year, year4, uom, type, picture, listingpicture, createdby, modifiedby)
                        SELECT ".$publicId." AS sharedimageid, listingid, userid, categoryid, subcategoryid, boxtypeid, year, year4, uom, type, '".$publicFile."' AS picture, picture AS listingpicture, '".$page->user->username."' as createdby, '".$page->user->username."' AS modifiedby
                        FROM listings where listingid=".$id;
                    if ($page->db->sql_execute_params($sql)) {
                        $page->messages->addSuccessMsg('Add public image reference.');
                        //echo "addListing: added shared image record<br />\n";
                    } else {
                        //echo "addListing: error adding shared image record<br />\n";
                        $page->messages->addWarningMsg('Unable to add public image reference.');
                    }
                }
            }
        }

        return $success;
    }
}

?>