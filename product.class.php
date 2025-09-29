<?php

class product {

    public $productId = NULL;

    public $categoryId      = NULL;
    public $active          = 1;
    public $subCategoryId   = NULL;
    public $boxTypeId       = NULL;
    public $year            = NULL;
    public $year4           = NULL;
    public $versionId       = NULL;
    public $variation       = NULL;
    public $productSKU      = array();
    public $productNote     = NULL;
    public $releasedate     = NULL;
    public $factorycost     = NULL;
    public $picture         = NULL;
    public $categoryTypeId  = NULL;
    public $yearFormatTypeId = NULL;
    public $modifyDate      = NULL;
    public $modifedBy       = NULL;
    public $loaded          = false;
    public $singleChanged   = false;
    public $upcConflictId   = null;
    public $singleSKU       = 0;
    public $picturePath     = NULL;
    public $pictureUp       = NULL;

    public function __construct($productid = NULL) {
        global $page;

        $this->loaded = false;
        if (empty($productid)) {
            $this->scrapeProduct('add');
            if (is_object($page)) {
                if (!empty($this->categoryId) && !empty($this->subCategoryId) && !empty($this->boxTypeId)) {
                    $exists = $this->doesProductExist($this->categoryId, $this->subCategoryId, $this->boxTypeId, $this->year);
                    if (!empty($exists)) {
                        $product  = (empty($exists["year"])) ? "" : $exists["year"];
                        $product .= " ".$exists["subcategoryname"];
                        $product .= " ".$exists["categorydescription"];
                        $product .= " - ".$exists["boxtypename"];
                        $product .= (empty($exists["variation"])) ? "" : " (".$exists["variation"].")";
                        $product  = "<span style='background-color:#DDD;padding:5px'>".$product."</span>";
                        $url = "/product.php?action=edit&productid=".$exists["productid"];
                        $link = "<a href='".$url."'>Click Here</a>";
                        $active = (empty($exists["active"])) ? " that is <b>INACTIVE.</b>" : "";
                        $msg  = "There is an existing product that matches ".$product." ".$active;
                        $msg .= "<br/>If you want to edit this product instead of creating a new version / variation ".$link;
                        $page->messages->addWarningMsg($msg);
                    }
                }
            }
        } else {
            $this->loadData($productid);
        }
        $this->setOptions();
    }

    public function loadData($productid) {
        $this->loaded = false;

        if ($productData = $this->getProduct($productid)) {
            $this->productId          = $productData['productid'];
            $this->active             = $productData['active'];
            $this->categoryId         = $productData['categoryid'];
            $this->subCategoryId      = $productData['subcategoryid'];
            $this->boxTypeId          = $productData['boxtypeid'];
            $this->year               = $productData['year'];
            $this->year4              = $productData['year4'];
            if (strlen($this->year) == 0) {
                $this->year     = NULL;
                $this->year4    = NULL;
            }
            $this->variation          = $productData['variation'];
            $this->productSKU         = (empty($productData['upcs'])) ? NULL : explode(",", $productData['upcs']);
            $this->productNote        = $productData['productnote'];
            $this->releasedate        = $productData['releasedate'];
            $this->factorycost        = $productData['factorycost'];
            $this->picture            = $productData['picture'];
            $this->modifiedBy         = $productData['modifiedby'];
            $this->modifyDate         = $productData['modifydate'];
            $this->loaded = true;
        }

        return $this->loaded;
    }

    public function scrapeProduct($action) {
        $oldSingleFlag = $this->singleSKU;

        if ($action == 'add') {
            $this->categoryId       = optional_param('categoryid', NULL, PARAM_INT);
            $this->subCategoryId    = optional_param('subcategoryid', NULL, PARAM_INT);
            $this->boxTypeId        = optional_param('boxtypeid', NULL, PARAM_INT);
            $this->year             = trim(optional_param('year', NULL, PARAM_TEXT));
            if (strlen($this->year) == 0) {
                $this->year = NULL;
            }
        }
        $this->active               = optional_param('status', 1, PARAM_INT);
        $this->variation            = trim(optional_param('variation', NULL, PARAM_TEXT));
        if (empty($this->variation)) $this->variation = NULL;
        $sku                        = optional_param_array('productsku', array(), PARAM_TEXT);
        $this->productSKU = array();
        if (empty($sku)) {
            $this->productSKU = null;
        } else {
            if ($action == 'add' || $action == 'edit') {
                foreach($sku as $upc) {
                    $x = trim($upc);
                    if (!empty($x)) {
                        $this->productSKU[] = $x;
                    }
                }
            }
        }
        $this->productNote          = trim(optional_param('productnote', NULL, PARAM_TEXT));
        $releasedate                = optional_param('releasedate', 0, PARAM_TEXT);
        $this->factorycost          = optional_param('factorycost', 0, PARAM_NUM_NO_COMMA);
        $this->releasedate          = (empty($releasedate)) ? NULL : strtotime($releasedate);

        $this->picturePath = NULL;
        $this->pictureUp = NULL;
        if (is_array($_FILES) && (count($_FILES) > 0)) {
            if (array_key_exists('pictureup', $_FILES)) {
                if (!(  empty($_FILES['pictureup']['name'])
                     || empty($_FILES['pictureup']['type'])
                     || empty($_FILES['pictureup']['tmp_name'])
                     || ($_FILES['pictureup']['size'] < 1))) {
                    $this->pictureUp = $_FILES['pictureup'];
                }
            }
        }


    }

    public function setOptions() {
        global $page, $listingTypeId;

        if ($this->categoryId) {
            setGlobalListingTypeId($this->categoryId);
            $this->categoryTypeId = $listingTypeId;
            $this->yearFormatTypeId = $page->utility->getYearFormatTypeId($this->categoryId);
        }
    }

    public function checkCardTypeYear($categoryId,$years) {
        global $page;

        $listing = new listing();
        $year4 = $listing->checkCardTypeYear($categoryId, $years);

        return $year4;
    }

    public function validateProduct($action) {
        global $page;

        $isValid = true;

        $this->upcConflict = null;

        if (! $this->categoryId) {
            $page->messages->addErrorMsg("Category is required.");
            $isValid = false;
        }

        if (! $this->subCategoryId) {
            $page->messages->addErrorMsg("Subcategory is required.");
            $isValid = false;
        }

        if (! $this->boxTypeId) {
            $page->messages->addErrorMsg("Box Type is required.");
            $isValid = false;
        }

        if ($this->productSKU) {
            if (($action == 'add' || $action == 'edit') && is_array($this->productSKU)) {
                foreach($this->productSKU as $upc) {
                    if (! $page->utility->validateUPC($upc)) {
                        $page->messages->addErrorMsg("Invalid UPC - ".$upc);
                        $isValid = false;
                    }
                    if ($isValid) {
                        $notThisOne = ($action == 'edit') ? " AND productid <> ".$this->productId." " : "";

                        $sql = "
                            SELECT productid
                              FROM product_upc
                             WHERE upc = '".$upc."'
                              ".$notThisOne."
                            LIMIT 1";
                        $this->upcConflict = $page->db->get_field_query($sql);
                        if ($this->upcConflict) {
                            $otherURL = "<a href='/product.php?productid=".$this->upcConflict."&action=view' target='_blank'>$this->upcConflict</a>";
                            $page->messages->addErrorMsg("UPC ".$this->productSKU." is in use by productid ".$otherURL);
                            $isValid = false;
                        }
                    }
                }
            } else {
                if (! $page->utility->validateUPC($this->productSKU)) {
                    $page->messages->addErrorMsg("Invalid UPC.");
                    $isValid = false;
                }
                if ($isValid) {
                    $notThisOne = ($action == 'edit') ? " AND productid <> ".$this->productId." " : "";

                    $sql = "
                        SELECT productid
                          FROM product_upc
                         WHERE upc = '".$upc."'
                          ".$notThisOne."
                        LIMIT 1";
                    $this->upcConflict = $page->db->get_field_query($sql);
                    if ($this->upcConflict) {
                        $otherURL = "<a href='/product.php?productid=".$this->upcConflict."&action=view' target='_blank'>$this->upcConflict</a>";
                        $page->messages->addErrorMsg("UPC ".$this->productSKU." is in use by productid ".$otherURL);
                        $isValid = false;
                    }
                }
            }
        }

        if ($isValid) {
            $year4 = $this->checkCardTypeYear($this->categoryId, $this->year);
            if ($year4 < 0) {
                $isValid = false;
            } else {
                $this->year4 = $year4;
            }
        }

        if ($isValid) {
            $andYear = (empty($this->year)) ? "" : " AND p.year = '".$this->year."' ";
            $notThisOne = ($action == 'edit') ? " AND p.productid <> ".$this->productId." " : "";
            $andVariation = (empty($this->variation)) ? " AND (p.variation IS NULL OR length(p.variation) = 0) "
                : " AND p.variation = '".$this->variation."' ";
            $sql = "
                SELECT count(*)
                  FROM products p
                WHERE p.categoryid      = ".$this->categoryId."
                  AND p.subcategoryid   = ".$this->subCategoryId."
                  AND p.boxtypeid       = ".$this->boxTypeId.$andVariation.$andYear.$notThisOne;
            $sql .= $andVariation.$andYear.$notThisOne;
            $dups = $page->db->get_field_query($sql);
            if ($dups > 0) {
                $page->messages->addErrorMsg("This product already exists.");
                $isValid = false;
            }
        }

        if (isset($this->singleSKU)) {
            if (($this->singleSKU == 0) || ($this->singleSKU == 1)) {
                if ($isValid) {
                    if ($this->singleSKU == 1) {
                        $andYear = (empty($this->year)) ? "" : " AND p.year='".$this->year."' ";

                        $sql = "
                            SELECT count(*)
                              FROM products p
                             WHERE p.categoryid     = ".$this->categoryId."
                              AND p.subcategoryid   = ".$this->subCategoryId."
                              AND p.boxtypeid       = ".$this->boxTypeId."
                               AND p.variation      <> '".$this->variation."' ".$andYear;
                        $skus = $page->db->get_field_query($sql);
                        if ($skus > 0) {
                            $page->messages->addErrorMsg("This product is designated as single Version/SKU but another version already exists.");
                            $isValid = false;
                        }
                    }
                }
            } else {
                $page->messages->addErrorMsg("Single Version/SKU must be Yes or No.");
                $isValid = false;
            }
        } else {
            $page->messages->addErrorMsg("Single Version/SKU must be Yes or No.");
            $isValid = false;
        }


        return $isValid;
    }

    public function doesProductExist($catid, $subcatid, $btid, $year) {
        global $page;

        $data = null;
        if (!empty($this->categoryId) && !empty($this->subCategoryId) && !empty($this->boxTypeId)) {
            $yr = (empty($year)) ? "NULL" : "'".$year."'";

            $sql = "
                SELECT p.productid, c.categorydescription, sc.subcategoryname, bt.boxtypename, p.year, p.variation, p.active
                  FROM products         p
                  JOIN categories       c   ON  c.categoryid        = p.categoryid
                                            AND c.active            = 1
                  JOIN subcategories    sc  ON  sc.subcategoryid    = p.subcategoryid
                                            AND sc.active           = 1
                  JOIN boxtypes         bt  ON  bt.boxtypeid        = p.boxtypeid
                                            AND bt.active           = 1
                 WHERE p.categoryid         = ".$catid."
                   AND p.subcategoryid      = ".$subcatid."
                   AND p.boxtypeid          = ".$btid."
                   AND isnull(p.year, '1')  = isnull(".$yr.", '1')
                GROUP BY p.productid, c.categorydescription, sc.subcategoryname, bt.boxtypename, p.year, p.variation, p.active
                LIMIT 1
            ";

    //        echo "<pre>".$sql."</pre>";
            if ($rs = $page->db->sql_query($sql)) {
                $data = reset($rs);
            }
        }

        return $data;
    }

    public function getProduct($productid) {
        global $page;

        $productData = null;

        if ($productid) {
            $sql = "
                SELECT p.productid, p.categoryid, p.subcategoryid, p.boxtypeid,
                       p.year, p.year4, p.variation, p.uom, p.active, p.productnote,
                       p.picture, p.releasedate, p.factorycost,
                       isnull(p.modifiedby, p.createdby) as  modifiedby,
                       p.modifydate, upc.upcs
                  FROM products         p
                  CROSS JOIN (
                        SELECT array_to_string(array_agg(upc), ',') as upcs
                          FROM product_upc
                         WHERE productid = ".$productid."
                            )           upc
                 WHERE p.productid  = ".$productid."
            ";

            if ($result = $page->db->sql_query($sql)) {
                $productData = reset($result);
            } else {
                $page->messages->addErrorMsg("Product ID ".$productid." not found.");
            }
        }
//      echo "<pre>"; print_r($productData);echo "</pre>";

        return $productData;
    }

    public function addProduct() {
        global $page, $CFG;

        $success = false;
        $exists = $this->doesProductExist($this->categoryId, $this->subCategoryId, $this->boxTypeId, $this->year);
        if (!empty($exists)) {
            $product  = (empty($exists["year"])) ? "" : $exists["year"];
            $product .= " ".$exists["subcategoryname"];
            $product .= " ".$exists["categorydescription"];
            $product .= " - ".$exists["boxtypename"];
            $product .= (empty($exists["variation"])) ? "" : " (".$exists["variation"].")";
            $msg  = "ERROR: There is an existing product that matches ".$product;
            $page->messages->addErrorMsg($msg);
        } else {
            $productid = $page->utility->nextval("products_productid_seq");
            $page->queries = new DBQueries("Add product.");

            $sql = "
                INSERT INTO products(productid, active, categoryid, subcategoryid, boxtypeid, year, year4, variation, productnote, factorycost, releasedate, picture, createdby, modifiedby)
                VALUES (:productid, :active, :categoryid, :subcategoryid, :boxtypeid, :year, :year4, :variation, :productnote, :factorycost, :releasedate, :picture, :createdby, :modifiedby)
            ";

            $params = array();
            $params['productid']        = $productid;
            $params['active']           = $this->active;
            $params['categoryid']       = $this->categoryId;
            $params['subcategoryid']    = $this->subCategoryId;
            $params['boxtypeid']        = $this->boxTypeId;
            $params['year']             = $this->year;
            $params['year4']            = $this->year4;
            $params['variation']        = $this->variation;
            $params['productnote']      = $this->productNote;
            $params['releasedate']      = $this->releasedate;
            $params['factorycost']      = $this->factorycost;
            $params['picture']          = $this->picture;
            $params['createdby']        = $page->user->username;
            $params['modifiedby']       = $page->user->username;
            $page->queries->AddQuery($sql, $params);

            if (!empty($this->productSKU)) {
                $sql = "
                    INSERT INTO product_upc(productid, upc, createdby)
                    VALUES (:productid, :upc, :createdby)
                ";
                $params = array();
                $params['productid']    = $productid;
                $params['upc']          = (empty($this->productSKU)) ? NULL : reset($this->productSKU);
                $params['createdby']    = $page->user->username;
                $page->queries->AddQuery($sql, $params);
            }

            if (!empty($this->pictureUp)) {
                $sharedimageid = $page->utility->nextval("sharedimages_sharedimageid_seq");
                if ($picture = prefixImgUp($this->pictureUp, $sharedimageid, $CFG->sharedImagesPath, $page)) {
    //                $picture = (($targetURL) ? $targetURL : "").$img;
                    if (!empty($picture)) {
                        $sql = "
                            UPDATE products
                               SET picture   = :picture
                             WHERE productid = :productid
                        ";
                        $params = array();
                        $params['productid']    = $productid;
                        $params['picture']      = $picture;
                        $page->queries->AddQuery($sql, $params);
                    }
                }
            }

            try {
                $page->queries->ProcessQueries();
                $page->messages->addSuccessMsg("Created new product id ".$productid);
                $this->productId = $productid;
                $this->loadData($this->productId);
                $this->updateListings('add');
                $success = true;
                if (!empty($picture)) {
                    $x = $page->utility->getPrefixPublicImageURL($picture, THUMB100);
                    $x = $page->utility->getPrefixPublicImageURL($picture, THUMB150);
                }
            } catch (Exception $e) {
                $page->messages->addErrorMsg("Error adding product id ".$productid.".");
                $page->messages->addErrorMsg("Error: ".$e->getMessage()." [Reading file]");
                $this->productId = NULL;
                $success = false;
            } finally {
            }
        }

        return $success;
    }

    public function updateProduct() {
        global $page, $CFG;

        $success = false;
        $page->queries = new DBQueries("Update product.");

        $sql = "
            SELECT upc
              FROM product_upc
             WHERE productid = ".$this->productId;
             $UPCs = $page->db->sql_query($sql);
        if ($UPCs) {
            foreach($UPCs as $x) {
                $upcs[] = $x["upc"];
            }
            foreach($upcs as $upc) {
                if (is_array($this->productSKU) && !in_array($upc, $this->productSKU, true)) {
                    $sql = "
                        DELETE FROM product_upc
                         WHERE productid = :productid
                           AND upc       = :upc
                    ";
                    $params = array();
                    $params['productid'] = $this->productId;
                    $params['upc'] = $upc;
                    $page->queries->AddQuery($sql, $params);
                }
            }
            if (is_array($this->productSKU)) {
                foreach($this->productSKU as $upc) {
                    if (is_array($upcs) && !in_array($upc, $upcs, true)) {
                        $sql = "
                            INSERT INTO product_upc(productid, upc, createdby)
                            VALUES (:productid, :upc, :createdby)
                        ";
                        $params = array();
                        $params['productid'] = $this->productId;
                        $params['upc'] = $upc;
                        $params['createdby'] = $page->user->username;
                        $page->queries->AddQuery($sql, $params);
                    }
                }
            }
        } else {
            if (is_array($this->productSKU)) {
                foreach($this->productSKU as $upc) {
                    $sql = "
                        INSERT INTO product_upc(productid, upc, createdby)
                        VALUES (:productid, :upc, :createdby)
                    ";
                    $params = array();
                    $params['productid'] = $this->productId;
                    $params['upc'] = $upc;
                    $params['createdby'] = $page->user->username;
                    $page->queries->AddQuery($sql, $params);
                }
            }
        }
        $sql = "
            UPDATE products
               SET active       = :active,
                   variation    = :variation,
                   productnote  = :productnote,
                   releasedate  = :releasedate,
                   factorycost  = :factorycost,
                   picture      = :picture,
                   modifiedby   = :modifiedby,
                   modifydate   = nowtoint()
             WHERE productid    = :productid";

        $params = array();
        $params['active']       = $this->active;
        $params['variation']    = $this->variation;
        $params['productnote']  = $this->productNote;
        $params['releasedate']  = $this->releasedate;
        $params['factorycost']  = $this->factorycost;
        $params['picture']      = $this->picture;
        $params['modifiedby']   = $page->user->username;
        $params['productid']    = $this->productId;
        $page->queries->AddQuery($sql, $params);

        if (!empty($this->pictureUp)) {
            $sharedimageid = $page->utility->nextval("sharedimages_sharedimageid_seq");
            if ($picture = prefixImgUp($this->pictureUp, $sharedimageid, $CFG->sharedImagesPath, $page)) {
                if (!empty($picture)) {
                    $sql = "
                        UPDATE products
                           SET picture   = :picture
                         WHERE productid = :productid
                    ";
                    $params = array();
                    $params['productid']    = $this->productId;
                    $params['picture']      = $picture;
                    $page->queries->AddQuery($sql, $params);
                }
            }
        }


        try {
            $page->queries->ProcessQueries();
            $page->messages->addSuccessMsg("Updated product id ".$this->productId);
            $this->loadData($this->productId);
            $this->updateListings('edit');
            $success = true;
            if (!empty($picture)) {
                $x = $page->utility->getPrefixPublicImageURL($picture, THUMB100);
                $x = $page->utility->getPrefixPublicImageURL($picture, THUMB150);
            }
        } catch (Exception $e) {
            $page->messages->addErrorMsg("Error updating product id ".$this->productId.".");
            $page->messages->addErrorMsg("Error: ".$e->getMessage()." [Reading file]");
            $success = false;
        } finally {
        }

        return $success;
    }

    public function deleteProduct() {
        global $page;

        $success = true;

        $page->queries = new DBQueries("Delete product.");
        $sql = "DELETE FROM product_upc WHERE productid = ".$this->productId;
        $page->queries->AddQuery($sql);

        $sql = "DELETE FROM products WHERE productid = ".$this->productId;
        $page->queries->AddQuery($sql);

        try {
            $page->queries->ProcessQueries();
            $page->messages->addSuccessMsg("Successfully deleted product id ".$this->productId.".");
            $success = true;
        } catch (Exception $e) {
            $page->messages->addErrorMsg("Error deleting product id ".$this->productId.".");
            $page->messages->addErrorMsg("Error: ".$e->getMessage()." [Reading file]");
            $success = false;
        } finally {
        }

        return $success;
    }

    public function updateListings($action) {
        global $page;

        //echo "updateListings: action:".$action." SKU:".$this->productSKU." singleChanged:".(($this->singleChanged) ? "Y" : "N")." singleSKU:".$this->singleSKU."<br />\n";
        if ($action == 'add') {
            if ($this->active) {
                if (!empty($this->productSKU)) {
                    if ($this->singleSKU) {
                        $this->assignListings();
                    } else {
                        $page->messages->addSuccessMsg("Multi-SKU no listings have been assigned.");
                    }
                } else {
                    $page->messages->addSuccessMsg("No SKU - no listings have been assigned.");
                }
            } else {
                $page->messages->addSuccessMsg("Inactive - no listings have been assigned.");
            }
        } else {
            if ($this->active) {
                if ($this->singleChanged) {
                    if ($this->singleSKU) {
                        if (!empty($this->productSKU)) {
                            $this->assignListings();
                        } else {
                            $page->messages->addSuccessMsg("No SKU - no listings have been assigned.");
                        }
                    } else {
                        $this->unassignListings();
                    }
                } else {
                    $page->messages->addSuccessMsg("Single/Multi SKU unchanged - no listings have been affected.");
                }
            } else {
                $page->messages->addSuccessMsg("Inactive - no listings have been affected.");
            }
        }
    }

    public function assignListings() {
        global $page;

        $andYear = (empty($this->year)) ? "" : " AND year='".$this->year."' ";
        $sql = "
            UPDATE listings
               SET productid    = ".$this->productId.",
                   productdate  = ".$this->modifyDate.",
                   productby    = '".$page->user->username."'
             WHERE productid IS NULL
               AND status       = 'OPEN'
               AND categoryid   = ".$this->categoryId."
               AND subcategoryid= ".$this->subCategoryId."
               AND boxtypeid    = ".$this->boxTypeId.$andYear;
        $rowsUpdated = $page->db->sql_execute($sql);
        if ($rowsUpdated) {
            $page->messages->addSuccessMsg("Assigned ".$rowsUpdated." listings to this product.");
        }
    }

    public function unassignListings() {
        global $page;

        // What check should be used on productby ???  createdby, admin, page->user->username
        $sql = "
            UPDATE listings
               SET productid    = NULL
                   productdate  = NULL
                   productby    = NULL
            WHERE status    = 'OPEN'
              AND productid = ".$this->productId."
              AND productby = '".$page->user->username."'";
        $rowsUpdated = $page->db->sql_execute($sql);
        if ($rowsUpdated) {
            $page->messages->addSuccessMsg("Unassigned ".$rowsUpdated." listings from this product.");
        }
    }
}

?>