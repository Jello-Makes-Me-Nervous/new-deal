<?php
// SAME AS Category Type
define ("LISTING_TYPE_SPORTS", 1);
define ("LISTING_TYPE_GAMING", 2);
define ("LISTING_TYPE_SUPPLY", 3);
define ("LISTING_TYPE_CLASSIFIED", 4);
define ("LISTING_TYPE_BLAST", 5);

define ("CATEGORY_BLAST", 1603);
define ("SUBCATEGORY_BLAST", 40001);

define ("BOX_TYPE_BLAST", 91);
define ("BOX_TYPE_SUPPLIES", 94);
define ("BOX_TYPE_CLASSIFIED", 95);

define ("CATEGORY_CLASSIFIEDS", 1261);

define ("LISTING_UOMID_OTHER", "other");

define ("TRANSACTION_TYPE_WANTED", "Wanted");
define ("TRANSACTION_TYPE_FOR_SALE", "For Sale");

define ("USERRIGHT_LIMITED_BLAST", 16);
define ("USERRIGHT_UNLIMITED_BLAST", 17);
define ("USERRIGHT_OFFER_HISTORY_LITE", 59);
define ("USERRIGHT_OFFER_HISTORY", 60);

define ("PAYMENT_TYPE_ID_EFT", 1);

define ("USERNAME_ADMIN",   "ADMIN");
define ("USERNAME_FEES",    "FEES");
define ("FEES_USERID",      "12560");

define ("ADDRESS_TYPE_PAY", 1);
define ("ADDRESS_TYPE_CONTACT", 2);
define ("ADDRESS_TYPE_SHIP", 3);
define ("ADDRESS_TYPE_REQUEST_PAY", 11);
define ("ADDRESS_TYPE_REQUEST_CONTACT", 12);
define ("ADDRESS_TYPE_REQUEST_SHIP", 13);

define ("YEAR_FORMAT_2SLASH", 1);
define ("YEAR_FORMAT_4DIGIT", 2);
define ("YEAR_FORMAT_OTHER", 3);

define ("THUMB100",     "thumb100");
define ("THUMB150",     "thumb150");

$supplyNoteTruncateSize = 200;

$counterMinimumLeast = 0.00;
$counterMinimumMost = 1500.00;
$counterMinimumTotal = 100.00;
$counterCollarLow = 0.03;
$counterCollarHigh = 0.20;

$daysAcceptedToCompleted = 14;

$listingTypeId = optional_param('listingtypeid', LISTING_TYPE_SPORTS, PARAM_INT);

function setGlobalListingTypeId($categoryId) {
    global $DB, $listingTypeId;
    if ($categoryId) {
        $listingTypeId = $DB->get_field_query("SELECT categorytypeid FROM categories WHERE categoryid=".$categoryId);
    }
}

function moneyToFloat($money) {
    $clean = str_replace("$", "", str_replace(",", "", $money));
    $amount = floatval($clean);
    return $amount;
}

function floatToMoney($float) {
    $amount = "";
    if (isset($float)) {
        $amount = number_format($float, 2);
        if ($float < 0) {
            $amount = str_replace("-", "-$", $amount);
        } else {
            $amount = "$".$amount;
        }
    }
    return $amount;
}

function floatTwoDecimal($float) {
    $amount = "";
    if (isset($float)) {
        $amount = number_format($float, 2);
    }
    return $amount;
}

function nullTextNumeric($name, $paramType=PARAM_TEXT) {
    $value = optional_param($name, NULL, $paramType);
    if (isset($value) && empty($value)) {
        $value = null;
    }
    return $value;
}

class ListingReferral {
    public $referCategoryId = NULL;
    public $referCategoryName = NULL;
    public $referSubCategoryId = NULL;
    public $referSubCategoryName = NULL;
    public $referYear = NULL;
    public $referListingTypeId = NULL;
    public $referBoxTypeId = NULL;
    public $referBoxTypeName = NULL;
    public $boxTypeId = NULL;


    public function __construct() {
        global $listingTypeId;

        $this->referCategoryId = optional_param('refercatid', NULL, PARAM_INT);
        if (empty($this->referCategoryId)) {
            $this->referCategoryId = optional_param('categoryid', NULL, PARAM_INT);
        }
        $this->referSubCategoryId = optional_param('refersubcatid', NULL, PARAM_INT);
        if (empty($this->referSubCategoryId)) {
            $this->referSubCategoryId = optional_param('subcategoryid', NULL, PARAM_INT);
        }
        $this->referYear = optional_param('referyear', NULL, PARAM_TEXT);
        if (empty($this->referYear)) {
            $this->referYear = optional_param('year', NULL, PARAM_TEXT);
        }
        $this->referListingTypeId = optional_param('referlistingtypeid', NULL, PARAM_INT);
        if (empty($this->referListingTypeId)) {
            $this->referListingTypeId = $listingTypeId;
        }
        $this->referBoxTypeId = optional_param('referboxtypeid', NULL, PARAM_INT);
        if (empty($this->referBoxTypeId)) {
            $this->referBoxTypeId = optional_param('boxtypeid', NULL, PARAM_INT);
        }

        if (! empty($this->referCategoryId)) {
            $this->loadNames();
        }
    }

    public function loadNames() {
        global $DB;

        $names = array();
        $sql = "
            select c.categorydescription, s.subcategorydescription, b.boxtypename
              from listings         l
              join categories       c   on  c.categoryid    = l.categoryid
              join subcategories    s   on  s.subcategoryid = l.subcategoryid
              join boxtypes         b   on  b.boxtypeid     = l.boxtypeid ";
        if (!empty($this->referCategoryId)) {
            $sql .= "
             where l.categoryid = ".$this->referCategoryId;
        }
        if (!empty($this->referSubCategoryId)) {
            $sql .= "
               and l.subcategoryid = ".$this->referSubCategoryId;
        }
        if (!empty($this->referBoxTypeId)) {
            $sql .= "
               and l.boxtypeid = ".$this->referBoxTypeId;
        }
        $sql .= "
            limit 1";

        //echo "<pre>".$sql."</pre>";
        if ($x = $DB->sql_query($sql)) {
            $names = reset($x);
            $this->referCategoryName    = $names['categorydescription'];
            $this->referSubCategoryName = $names['subcategorydescription'];
            $this->referBoxTypeName     = $names['boxtypename'];
            $this->boxTypeName          = $names['boxtypename'];
        }

    }

    public function referralLink() {
        $backText = "Back To: ".$this->referYear." ".$this->referCategoryName;
        $link = " <a href='listings.php?categoryid=".$this->referCategoryId."&year=".$this->referYear."&listingtypeid=".$this->referListingTypeId."'>".$backText."</a> ";
        return $link;
    }

    public function referralHiddens() {
        $hiddens = "";
        $hiddens .= "<input type='hidden' id='refercatid' name='refercatid' value='".$this->referCategoryId."' />\n";
        $hiddens .= "<input type='hidden' id='refersubcatid' name='refersubcatid' value='".$this->referSubCategoryId."' />\n";
        $hiddens .= "<input type='hidden' id='referyear' name='referyear' value='".$this->referYear."' />\n";
        $hiddens .= "<input type='hidden' id='referboxtypeid' name='referboxtypeid' value='".$this->referBoxTypeId."' />\n";
        $hiddens .= "<input type='hidden' id='referlistingtypeid' name='referlistingtypeid' value='".$this->referListingTypeId."' />\n";

        return $hiddens;
    }
}

class utility {

    public $mon;
    public $year;

    public function __construct() {

        $this->mon  = optional_param('mon', 0, PARAM_INT);
        $this->year = date('Y', strtotime("+".$this->mon." months"));
    }


    public function checked($checked){
        if (!empty($checked)) {
            $checked = "checked";
        } elseif (!isset($checked)) {
            $checked = "";
        }
        return $checked;
    }

    public function isChecked($value, $target, $response="checked"){
        if ($value && $target && ($value == $target)) {
            return $response;
        } else {
            return "";
        }
    }

    public function checkAmounts($more, $less) {

        $success = FALSE;
        if ($more >= $less) {
           $success = TRUE;
        }

        return $success;
    }

    public function checkDuplicateName($table, $columnName, $name, $originalName = NULL) {
        global $DB;

        if (isset($originalName) && $name == $originalName) {
             $checkDuplicateName = FALSE;
        } else {
            $sql = "
                SELECT ".$columnName." FROM ".$table." WHERE ".$columnName." = :columnval
            ";
            $params = array();
            $params["columnval"] = $name;
            $rs = $DB->sql_query_params($sql, $params);

            if (empty($rs)) {
                $checkDuplicateName = FALSE;
            } else {
                $checkDuplicateName = TRUE;
            }
        }

        return $checkDuplicateName;

    }

    public function checkDupNameSubCat($name, $categoryId, $newcategoryId =NULL, $originalName = NULL) {
        global $DB;

        $checkDupNameSubCat = FALSE;
        if (isset($originalName) && $name == $originalName && $categoryId == $newcategoryId) {
        } else {
            $sql = "
                SELECT subCategoryName
                  FROM subCategories
                 WHERE lower(subCategoryName) = lower('".$name."')
                   AND categoryId = ".$categoryId;

            $subCategoryName = $DB->get_field_query($sql);

            if ($subCategoryName) {
                $checkDupNameSubCat = TRUE;
            }
        }
        return $checkDupNameSubCat;

    }

    public function getDigitsDDM($countTo) {
        $i = 0;
        $digit = array();
        for ($i = 1; $i <= $countTo; $i++) {
            $digit[] = array("id" => $i, "value" => $i);
        }
        return $digit;
    }

    public function getAddress($userId, $addressTypeId, $isMe=true, $showName=false) {
        global $DB;

        $addr = null;

        $sql = "
            SELECT uc.companyname, uc.street, uc.street2, uc.city, uc.state, uc.country, uc.zip, uc.phone, uc.fax, uc.email, uc.addressnote,
                ui.firstname, ui.lastname,
                uc.userid, uc.usercontactid, uc.addresstypeid, uc.createdate, uc.modifydate
             FROM usercontactinfo uc
             LEFT JOIN userinfo ui on ui.userid=uc.userid
            WHERE uc.userid = ".$userId."
              AND uc.addresstypeid = ".$addressTypeId."
        ";
        $address = $DB->sql_query($sql);

        if (isset($address)) {
            $addr = reset($address);
        }

        return $addr;
    }

    public function displayAddress($addr, $userId, $addressTypeId, $isMe=true, $showName=false) {
        global $page;

        if ($addr) {
            if (!(empty($addr['firstname']) && empty($addr['lastname']))) {
                if ($showName) {
                    echo $this->htmlFriendlyString($addr['firstname']." ".$addr['lastname'])."<br />\n";
                }
            }
            if (!empty($addr['companyname'])) {
                echo $this->htmlFriendlyString($addr['companyname'])."<br />\n";
            }
            echo $this->htmlFriendlyString($addr['street'])."<br />\n";
            if (!empty($addr['street2'])) {
                echo $this->htmlFriendlyString($addr['street2'])."<br />\n";
            }
            echo "".$addr['city'].", ".$addr['state']." ".$addr['zip']."<br />\n";
            if (!empty($addr['country'])) {
                echo $this->htmlFriendlyString($addr['country'])."<br />\n";
            }
            if (!empty($addr['addressnote'])) {
                echo $this->htmlFriendlyString($addr['addressnote'])."<br />\n";
            }
            if ($isMe || $page->user->isAdmin() || $page->user->isStaff()) {
                if (!empty($addr['phone'])) {
                    echo "Phone: ".$addr['phone']."<br />\n";
                }
                if (!empty($addr['fax'])) {
                    echo "Fax: ".$addr['fax']."<br />\n";
                }
                if (!empty($addr['email'])) {
                    echo "Email: ".$addr['email']."<br />\n";
                }
            }
        } else {
            echo "No information avaliable.\n";
        }
    }

    public function getAddressAsOf($userId, $addressTypeId) {
        global $DB;

        $asOfStr = "";

        $asOf = $DB->get_field_query("SELECT modifydate FROM usercontactinfo WHERE userid=".$userId." AND addresstypeid=".$addressTypeId);
        if ($asOf) {
            $asOfStr = "(as of ".date('m/d/Y', $asOf).")";
        }

        return $asOfStr;
    }

    public function formatAddress($userId, $addressTypeId, $isMe=true, $showName=false) {
        global $DB;

        $addr = $this->getAddress($userId, $addressTypeId, $isMe, $showName);
        $this->displayAddress($addr, $userId, $addressTypeId, $isMe, $showName);
    }

    public function getBoxTypeName($boxTypeId) {
        global $DB;

       $sql = "SELECT boxtypename FROM boxtypes WHERE boxtypeid = ".$boxTypeId;
       $boxTypeName = $DB->get_field_query($sql);

        return $boxTypeName;
    }

    public function getboxTypes($categoryId = NULL) {
        global $DB;

        $catid = (is_null($categoryId)) ? "cat.categoryId" : $categoryId;

        $sql = "
            SELECT box.boxtypeName, box.boxtypeId, box.active,
                   ctp.categorytypename, ctp.categorytypeid,
                   cat.categorytypeid, cat.categoryid
              FROM boxtypes         box
              JOIN categorytypes    ctp ON  ctp.categorytypeid  = box.categorytypeid
              JOIN categories       cat ON  cat.categorytypeid  = box.categorytypeid
        ";
        if (!empty($categoryId)) {
            $sql .=
            "WHERE cat.categoryid = ".$catid."
            ";
        }
        $sql .=  "
            ORDER BY active DESC, boxtypeName COLLATE \"POSIX\"
        ";
        //echo "SQL:<br />\n<pre>".$sql."</pre><br />\n";
        $boxTypesData = $DB->sql_query_params($sql);

        return $boxTypesData;
    }

    public function getListingBoxTypes($categoryId = NULL, $year = NULL, $subCategoryId = NULL, $boxTypeId = NULL) {
        global $page, $DB;

        $typeWhere = ($page->user->canSell()) ? "" : " AND l.type         = 'For Sale'";
        $sql = "
            SELECT box.boxtypeid, box.active, box.boxtypename,
                   box.boxtypename || ' (' || count(*) || ')' as boxtypenamecnt
              FROM boxtypes             box
              JOIN listings             l   ON  l.status            = 'OPEN'
                                            AND l.boxtypeid         = box.boxtypeid
                                            AND l.userid            <> ".FACTORYCOSTID."
              JOIN userinfo             ui  ON  ui.userid           = l.userid
                                            AND ui.userclassid      = 3
                                            AND ((l.type='For Sale' AND ui.vacationsell=0)
                                                  OR
                                                 (l.type='Wanted' AND ui.vacationbuy=0))
              JOIN assignedrights       ar  ON  ar.userid           = l.userid
                                            AND ar.userrightid      = 1
              LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                            AND stl.userrightid     = ".USERRIGHT_STALE."
        ";

        $sql .= "
             WHERE box.active       = 1
               AND stl.userid IS NULL
               ".$typeWhere;

        if (!empty($categoryId)) {
            $sql .= "
               AND l.categoryid     = ".$categoryId;
        }

        if (!empty($year)) {
            $sql .= "
               AND l.year           = '".$year."'";
        }

        if (!empty($subCategoryId)) {
            $sql .= "
               AND l.subcategoryid  = ".$subCategoryId;
        }

        $sql .= "
            GROUP BY box.boxtypeid, box.boxtypename, box.active
            ORDER BY box.active, box.boxtypename COLLATE \"POSIX\"
        ";

//      echo "BoxType SQL:<br />\n<pre>".$sql."</pre><br />\n";
        $boxTypesData = $DB->sql_query($sql);

        return $boxTypesData;
    }

    public function getProductBoxTypes($categoryid = NULL, $subcategoryid = NULL, $year = NULL, $boxtypeid = NULL) {
        global $page, $DB;

        $category    = (!empty($categoryid))    ? $categoryid : "c.categoryid";
        $subcategory = (!empty($subcategoryid)) ? $subcategoryid : "sc.subcategoryid";
        $boxtype     = (!empty($boxtypeid))     ? $boxtypeid : "bt.boxtypeid";
        $year        = (empty($year))           ? "" : "AND p.year = '".$year."' ";
        $sql = "
            SELECT bt.boxtypeid, bt.boxtypename
              FROM products         p
              JOIN categories       c   ON  c.categoryid        = p.categoryid
                                        AND c.active            = 1
              JOIN subcategories    sc  ON  sc.subcategoryid    = p.subcategoryid
                                        AND sc.active           = 1
              JOIN boxtypes         bt  ON  bt.boxtypeid        = p.boxtypeid
                                        AND bt.active           = 1
             WHERE p.categoryid     = ".$categoryid."
               AND p.subcategoryid  = ".$subcategory."
               AND p.boxtypeid      = ".$boxtype."
              ".$year."
            GROUP BY bt.boxtypeid, bt.boxtypename
            ORDER BY bt.boxtypename COLLATE \"POSIX\"
        ";

//      echo "<pre>".$sql."</pre>\n";
        $data = $DB->sql_query($sql);

        return $data;
    }

    public function getListingBoxTypesVariations($categoryId = NULL, $year = NULL, $subCategoryId = NULL, $boxTypeId = NULL) {
        global $page, $DB;

        $typeWhere = ($page->user->canSell()) ? "" : " AND l.type         = 'For Sale'";
        $sql = "
            SELECT box.boxtypeid, box.active, box.boxtypename,
                   case when p.variation is null then box.boxtypename
                        else trim(both ' ' from trim(trailing '.' from box.boxtypename)) || ' - ' || p.variation end as boxtypename2,
                   case when p.variation is null then box.boxtypename || ' (' || count(*) || ')'
                        else trim(both ' ' from trim(trailing '.' from box.boxtypename)) || ' - ' || p.variation || ' (' || count(*) || ') ' end as boxtypenamecnt
              FROM boxtypes             box
              JOIN listings             l   ON  l.status            = 'OPEN'
                                            AND l.boxtypeid         = box.boxtypeid
                                            AND l.userid            <> ".FACTORYCOSTID."
              JOIN userinfo             ui  ON  ui.userid           = l.userid
                                            AND ui.userclassid      = 3
                                            AND ((l.type='For Sale' AND ui.vacationsell=0)
                                                  OR
                                                 (l.type='Wanted' AND ui.vacationbuy=0))
              JOIN assignedrights       ar  ON  ar.userid           = l.userid
                                            AND ar.userrightid      = 1
              LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                            AND stl.userrightid     = ".USERRIGHT_STALE."
              LEFT JOIN products        p   ON  p.categoryid        = l.categoryid
                                            AND p.subcategoryid     = l.subcategoryid
                                            AND p.boxtypeid         = l.boxtypeid
                                            AND isnull(p.year, '1') = isnull(l.year, '1')
                                            AND p.sku IS NOT NULL
                ";

        $sql .= "
             WHERE box.active       = 1
               AND stl.userid IS NULL
               ".$typeWhere;

        if (!empty($categoryId)) {
            $sql .= "
               AND l.categoryid     = ".$categoryId;
        }

        if (!empty($year)) {
            $sql .= "
               AND l.year           = '".$year."'";
        }

        if (!empty($subCategoryId)) {
            $sql .= "
               AND l.subcategoryid  = ".$subCategoryId;
        }

        $sql .= "
            GROUP BY box.boxtypeid, box.boxtypename, box.active, p.variation
            ORDER BY box.active, box.boxtypename COLLATE \"POSIX\"
        ";

//      echo "BoxType SQL:<br />\n<pre>".$sql."</pre><br />\n";
        $boxTypesData = $DB->sql_query($sql);

        return $boxTypesData;
    }

    public function getProductBoxTypesVariations($categoryid = NULL, $subcategoryid = NULL, $year = NULL, $boxtypeid = NULL) {
        global $page, $DB;

        $category    = (!empty($categoryid))    ? $categoryid : "c.categoryid";
        $subcategory = (!empty($subcategoryid)) ? $subcategoryid : "sc.subcategoryid";
        $boxtype     = (!empty($boxtypeid))     ? $boxtypeid : "bt.boxtypeid";
        $year        = (empty($year))           ? "" : "AND p.year = '".$year."' ";
        $sql = "
            SELECT bt.boxtypeid, bt.boxtypename,
                   case when p.variation is null then bt.boxtypename
                        else trim(both ' ' from trim(trailing '.' from bt.boxtypename)) || ' - ' || p.variation end as boxtypename2
              FROM products         p
              JOIN categories       c   ON  c.categoryid        = p.categoryid
                                        AND c.active            = 1
              JOIN subcategories    sc  ON  sc.subcategoryid    = p.subcategoryid
                                        AND sc.active           = 1
              JOIN boxtypes         bt  ON  bt.boxtypeid        = p.boxtypeid
                                        AND bt.active           = 1
             WHERE p.categoryid     = ".$category."
               AND p.subcategoryid  = ".$subcategory."
               AND p.boxtypeid      = ".$boxtype."
              ".$year."
            GROUP BY bt.boxtypeid, bt.boxtypename, p.variation
            ORDER BY bt.boxtypename COLLATE \"POSIX\"
        ";

//      echo "<pre>".$sql."</pre>\n";
        $boxTypesData = $DB->sql_query($sql);

        return $boxTypesData;
    }

    public function getListingProducts($categoryId = NULL, $year = NULL, $subCategoryId = NULL, $boxTypeId = NULL) {
        global $page, $DB;

        $typeWhere = ($page->user->canSell()) ? "" : " AND l.type         = 'For Sale'";
        $sql = "
            SELECT p.productid, '('||count(*)||')' as productnamecnt
              FROM products             p
              JOIN listings             l   ON  l.status                = 'OPEN'
                                            AND l.userid                <> ".FACTORYCOSTID."
                                            AND l.categoryid            = p.categoryid
                                            AND l.subcategoryid         = p.subcategoryid
                                            AND l.boxtypeid             = p.boxtypeid
                                            AND isnull(l.year,'1')      = isnull(p.year, '1')
                                            AND isnull(l.productid, 0)  = p.productid
              JOIN userinfo             ui  ON  ui.userid               = l.userid
                                            AND ui.userclassid          = 3
                                            AND ((l.type='For Sale' AND ui.vacationsell=0)
                                                  OR
                                                 (l.type='Wanted' AND ui.vacationbuy=0))
              JOIN assignedrights       ar  ON  ar.userid               = l.userid
                                            AND ar.userrightid          = 1
              LEFT JOIN assignedrights  stl ON  stl.userid              = l.userid
                                            AND stl.userrightid         = ".USERRIGHT_STALE."
        ";

        $sql .= "
             WHERE p.active = 1
               AND stl.userid IS NULL
               ".$typeWhere;

        if (!empty($categoryId)) {
            $sql .= "
               AND l.categoryid     = ".$categoryId;
        }

        if (!empty($year)) {
            $sql .= "
               AND l.year           = '".$year."'";
        }

        if (!empty($subCategoryId)) {
            $sql .= "
               AND l.subcategoryid  = ".$subCategoryId;
        }

        if (!empty($boxTypeId)) {
            $sql .= "
               AND l.boxtypeid  = ".$boxTypeId;
        }

        $sql .= "
            GROUP BY p.productid
        ";

//echo "BoxType SQL:<br />\n<pre>".$sql."</pre><br />\n";
        $boxTypesData = $DB->sql_query($sql);

        return $boxTypesData;
    }

    public function getMyBoxTypes($userId, $categoryId = NULL, $year = NULL, $inactiveListings = 0, $subCategoryId=NULL, $listingType=NULL) {
        global $DB;

        $andListingStatus = ($inactiveListings) ? "" : "\n AND l.status='OPEN' ";
        $andCategoryId = ($categoryId) ? "\n AND l.categoryid=".$categoryId : "";
        $andSubCategoryId = ($subCategoryId) ? "\n AND l.subcategoryid=".$subCategoryId : "";
        $andYear = ($year) ? "\n AND l.year='".$year."' " : "";

        $andListingType = "";
        if ($listingType && ($listingType != 'Both')) {
            $andListingType = "\n AND l.type='".$listingType."' ";
        }

        $sql = "
            SELECT box.boxtypeid, box.active\n, concat(box.boxtypename||' ('||count(l.listingid)||')') as boxtypename
              FROM boxtypes   box
              JOIN listings   l   ON  l.boxtypeid = box.boxtypeid
                                  AND l.userid    = ".$userId.$andListingStatus.$andListingType.$andCategoryId.$andSubCategoryId.$andYear."
            GROUP BY box.boxtypeid, box.boxtypename, box.active
            ORDER BY box.active, box.boxtypename COLLATE \"POSIX\"";

        //echo "getMyBoxTypes SQL:<pre>".$sql."</pre><br />\n";
        $boxTypesData = $DB->sql_query($sql);

        return $boxTypesData;
    }

    public function getMyListingTypes($userId, $categoryId = NULL, $year = NULL, $inactiveListings = 0, $subCategoryId=NULL, $boxTypeId=NULL) {
        global $DB;

        $andListingStatus = ($inactiveListings) ? "" : "\n AND l.status='OPEN' ";
        $andCategoryId = ($categoryId) ? "\n AND l.categoryid=".$categoryId : "";
        $andSubCategoryId = ($subCategoryId) ? "\n AND l.subcategoryid=".$subCategoryId : "";
        $andBoxTypeId = ($boxTypeId) ? "\n AND l.boxtypeid=".$boxTypeId : "";
        $andYear = ($year) ? "\n AND l.year='".$year."' " : "";

        $sql = "
            SELECT l.type as listingtype, concat(l.type||' ('||count(l.listingid)||')') as listingtypename
              FROM listings l
             WHERE l.userid=".$userId.$andListingStatus.$andCategoryId.$andSubCategoryId.$andYear.$andBoxTypeId."
            GROUP BY l.type
            ORDER BY l.type COLLATE \"POSIX\"";

        //echo "getMyListingTypes SQL:<pre>".$sql."</pre><br />\n";
        $listingTypeData = $DB->sql_query($sql);

        return $listingTypeData;
    }

    public function getListingYears($categoryId, $boxTypeId=NULL, $subCategoryId=NULL) {
        global $page, $DB;

        $boxTypeWhere = ($boxTypeId) ? " AND l.boxtypeid=".$boxTypeId." " : "";
        $subCategoryWhere = ($subCategoryId) ? " AND l.subcategoryid=".$subCategoryId." " : "";
        $typeWhere = ($page->user->canSell()) ? "" : " AND l.type='For Sale' ";

        $sql = "
            SELECT l.year, l.year as yearname, l.year4
              FROM listings             l
              JOIN userinfo             ui  ON ui.userid        = l.userid
                                            AND ui.userclassid  = 3
                                            AND ((l.type = 'For Sale' AND ui.vacationsell = 0)
                                                  OR
                                                 (l.type = 'Wanted' AND ui.vacationbuy = 0))
              JOIN assignedrights       ar  ON  ar.userid       = l.userid
                                            AND ar.userrightid  = 1
              LEFT JOIN assignedrights  stl ON  stl.userid      = l.userid
                                            AND stl.userrightid = ".USERRIGHT_STALE."
             WHERE l.status     = 'OPEN'
               AND l.categoryId = ".$categoryId.$boxTypeWhere.$subCategoryWhere."
               AND l.userid     <> ".FACTORYCOSTID."
               AND stl.userid IS NULL
             ".$typeWhere."
            GROUP BY l.year, l.year4
            ORDER BY l.year4 DESC";

        $yearData = $DB->sql_query($sql);

        return $yearData;
    }

    public function getProductYears($categoryid, $boxtypeid=NULL, $subcategoryid=NULL) {
        global $page, $DB;

        $subcategory = (!empty($subcategoryid)) ? $subcategoryid : "sc.subcategoryid";
        $boxtype     = (!empty($boxtypeid))     ? $boxtypeid : "bt.boxtypeid";

        $sql = "
            SELECT p.year, p.year as yearname, p.year4
              FROM products         p
              JOIN categories       c   ON  c.categoryid        = p.categoryid
                                        AND c.active            = 1
              JOIN subcategories    sc  ON  sc.subcategoryid    = p.subcategoryid
                                        AND sc.active           = 1
              JOIN boxtypes         bt  ON  bt.boxtypeid        = p.boxtypeid
                                        AND bt.active           = 1
             WHERE p.categoryid     = ".$categoryid."
               AND p.subcategoryid  = ".$subcategory."
               AND p.boxtypeid      = ".$boxtype."
            GROUP BY p.year, p.year4
            ORDER BY p.year4 DESC
        ";

        $data = $DB->sql_query($sql);

        return $data;
    }

    public function getMyYears($userId, $categoryId, $inactiveListings=0, $boxTypeId=NULL, $subCategoryId=NULL) {
        global $DB;

        $boxTypeWhere = ($boxTypeId) ? " AND l.boxtypeid=".$boxTypeId." " : "";
        $subCategoryWhere = ($subCategoryId) ? " AND l.subcategoryid=".$subCategoryId." " : "";
        $andListingStatus = ($inactiveListings) ? "" : " AND l.status='OPEN' ";

        $sql = "SELECT l.year, concat(l.year||' ('||count(l.listingid)||')') as yearname, l.year4
            FROM listings l
            WHERE l.userid=".$userId." AND l.categoryId=".$categoryId.$boxTypeWhere.$subCategoryWhere.$andListingStatus."
            GROUP BY l.year, l.year4
            ORDER BY l.year4 DESC, l.year DESC";

        //echo "getMyYears SQL:<pre>".$sql."</pre><br />\n";
        $yearData = $DB->sql_query($sql);

        return $yearData;
    }

    public function getMySubCategories($userId, $categoryId, $boxTypeId, $year, $inactiveListings=0, $listingType=NULL) {
        global $DB;

        $yearWhere =  (empty($year)) ? "" : "\n AND l.year='".$year."' ";
        $boxTypeWhere =  (empty($boxTypeId)) ? "" : "\n AND l.boxtypeid='".$boxTypeId."' ";
        $andListingStatus = ($inactiveListings) ? "" : "\n AND l.status='OPEN' ";

        $andListingType = "";
        if ($listingType && ($listingType != 'Both')) {
            $andListingType = "\n AND l.type='".$listingType."' ";
        }

        $sql = "SELECT l.subcategoryid, concat(s.subcategoryname||'('||count(l.subcategoryid)||')') as subcategoryname
            FROM listings l
            JOIN subcategories s on s.subcategoryid=l.subcategoryid
            WHERE l.userid=".$userId."
              AND l.categoryId=".$categoryId.$andListingStatus.$yearWhere.$boxTypeWhere.$andListingType."
            GROUP BY l.subcategoryid, s.subcategoryname
            ORDER BY s.subcategoryname COLLATE \"POSIX\"";

        //echo "getMySubCategories SQL:<pre>".$sql."</pre><br />\n";
        $subCatData = $DB->sql_query($sql);

        return $subCatData;
    }

    public function getListingSubCategories($categoryId, $boxTypeId=NULL, $year=NULL, $excludeSecondary=TRUE) {
        global $DB;

        $yearWhere =  (empty($year)) ? "" : " AND l.year='".$year."' ";
        $boxTypeWhere =  (empty($boxTypeId)) ? "" : " AND l.boxtypeid='".$boxTypeId."' ";
        $secondaryWhere = ($excludeSecondary) ? " AND s.secondary=0 " : "";

        $sql = "
            SELECT l.subcategoryid,
                   concat(s.subcategoryname||' ('||count(l.subcategoryid)||')') as subcategoryname,
                   concat(CASE WHEN s.secondary = 1 THEN '- ' ELSE '' END||s.subcategoryname||' ('||count(l.subcategoryid)||')') as subcategoryname2,
                   s.secondary
              FROM listings             l
              JOIN userinfo             ui  ON  ui.userid       = l.userid
                                            AND ui.userclassid  = 3
                                            AND ((l.type='For Sale' AND ui.vacationsell=0)
                                                  OR
                                                 (l.type='Wanted' AND ui.vacationbuy=0))
              JOIN categories           c   ON  c.categoryid    = l.categoryid
                                            AND c.active        = 1
              JOIN subcategories        s   ON  s.subcategoryid = l.subcategoryid
                                            AND s.active        = 1
              JOIN boxtypes             bt  ON  bt.boxtypeid    = l.boxtypeid
                                            AND bt.active       = 1
              JOIN assignedrights       ar  ON  ar.userid       = ui.userid
                                            AND ar.userrightid  = 1
              LEFT JOIN assignedrights  stl ON  stl.userid      = ui.userid
                                            AND stl.userrightid = ".USERRIGHT_STALE."
            WHERE l.status      = 'OPEN'
              AND l.categoryid  = ".$categoryId.$boxTypeWhere.$yearWhere.$secondaryWhere."
              AND stl.userid IS NULL
            GROUP BY l.subcategoryid, s.secondary, s.subcategoryname
            ORDER BY s.secondary, s.subcategoryname COLLATE \"POSIX\"";

        $subCatData = $DB->sql_query($sql);

//        echo "<pre>".$sql."</pre>\n";
        return $subCatData;
    }

    public function getProductSubcategories($categoryid, $boxtypeid=NULL, $year=NULL, $excludesecondary=TRUE) {
        global $DB;

        $year =  (empty($year)) ? "" : "AND p.year = '".$year."' ";
        $boxtype =  (empty($boxtypeid)) ? "bt.boxtypeid" : $boxtypeid;
        $secondary = ($excludesecondary) ? "AND sc.secondary = 0 " : "";

        $sql = "
            SELECT p.subcategoryid, sc.subcategoryname
              FROM products         p
              JOIN categories       c   ON  c.categoryid        = p.categoryid
                                        AND c.active            = 1
              JOIN subcategories    sc  ON  sc.subcategoryid    = p.subcategoryid
                                        AND sc.active           = 1
              JOIN boxtypes         bt  ON  bt.boxtypeid        = p.boxtypeid
                                        AND bt.active           = 1
             WHERE p.categoryid     = ".$categoryid."
               AND p.boxtypeid      = ".$boxtype."
              ".$year."
              ".$secondary."
            GROUP BY p.subcategoryid, sc.subcategoryname
            ORDER BY sc.subcategoryname COLLATE \"POSIX\"";

//      echo "<pre>".$sql."</pre>\n";
        $data = $DB->sql_query($sql);

        return $data;
    }

    public function getcategories($active = 1, $blasts=false, $cattypeid = null) {
        global $DB;

        $sql = "
            SELECT categoryid, categoryname, categorydescription, active, categorytypeid, yearformattypeid
              FROM categories
             WHERE 1 = 1
        ";
        if (isset($active)) {
            $sql .= " AND active = ".$active;
        }
        if (!$blasts) {
            $sql .= " AND categoryid <> ".CATEGORY_BLAST;
        }
        if (!empty($cattypeid)) {
            $sql .= " AND categorytypeid = ".$cattypeid;
        }
        $sql .= " ORDER BY active DESC, categoryName COLLATE \"POSIX\"";

        $categoriesData = $DB->sql_query_params($sql);

          return $categoriesData;
    }

    public function getListingCategories($categorytypeid = 1) {
        global $DB;

        $sql = "
            SELECT c.categoryid, concat(c.categorydescription||' ('||count(l.listingid)||')') as categorydescription, c.active
              FROM categories   c
              JOIN listings     l   ON  l.status        = 'OPEN'
                                    AND l.categoryid    = c.categoryid
              JOIN (
                SELECT ui.userid, ui.vacationtype,
                       CASE WHEN ui.onvacation IS NOT NULL
                                 AND ui.onvacation < nowtoint()
                                 AND (ui.returnondate IS NULL OR ui.returnondate > nowtoint())
                                 AND (ui.vacationtype='Buy' OR ui.vacationtype='Both')
                                 THEN 1
                            ELSE 0 END          AS buyvacation,
                       CASE WHEN ui.onvacation IS NOT NULL
                                 AND ui.onvacation < nowtoint()
                                 AND (ui.returnondate IS NULL OR ui.returnondate > nowtoint())
                                 AND (ui.vacationtype='Sell' OR ui.vacationtype='Both')
                                 THEN 1
                           ELSE 0 END           AS sellvacation
                  FROM userinfo             ui
                  JOIN assignedrights       ar  ON  ar.userid       = ui.userid
                                                AND ar.userrightid  = 1
                  LEFT JOIN assignedrights  stl ON  stl.userid      = ui.userid
                                                AND stl.userrightid = ".USERRIGHT_STALE."
                 WHERE ui.userclassid   = 3
                   AND stl.userid IS NULL
                    )           u   ON  u.userid        = l.userid
                                    AND ((l.type             = 'For Sale'
                                          AND u.sellvacation = 0)
                                          OR
                                         (l.type             = 'Wanted'
                                          AND u.buyvacation  = 0))
             WHERE c.active         = 1
               AND c.categorytypeid IN (".$categorytypeid.")
            GROUP BY c.categoryid, c.categoryname
            ORDER BY active DESC, c.categorytypeid, categoryName COLLATE \"POSIX\"";

//      echo "<pre>".$sql."</pre>";
        $categoriesData = $DB->sql_query_params($sql);

        return $categoriesData;
    }

    public function getMyCategories($userId, $activeCategories = 1, $inactiveListings=0, $blasts=false, $listingType=null) {////////////////////////////////////////////////////
        global $DB;

        $sql = "SELECT c.categoryid
                , concat(c.categoryname||' ('||count(l.listingid)||')') as categoryname
                , concat(c.categorydescription||' ('||count(l.listingid)||')') as categorydescription
                , c.active
            FROM categories c
            JOIN listings l on l.categoryid=c.categoryid
            WHERE l.userid=".$userId;
        if ($activeCategories) {
            $sql .= "\n AND c.active = ".$activeCategories." ";
        }
        if (! $inactiveListings) {
            $sql .= "\n AND l.status = 'OPEN' ";
        }
        if (! $blasts) {
            $sql .= "\n AND c.categoryid <> ".CATEGORY_BLAST." ";
        }
        if ($listingType && ($listingType != 'Both')) {
            $sql .= "\n AND l.type='".$listingType."' ";
        }
        $sql .= "\n GROUP BY c.categoryid, c.categoryname ORDER BY active DESC, categoryName COLLATE \"POSIX\"";

        //echo "getMyCategories:<pre>".$sql."</pre><br />\n";
        $categoriesData = $DB->sql_query_params($sql);

        return $categoriesData;
    }

    public function getListingUOMs($categoryId, $boxTypeId, $year, $subCategoryId) {
        global $page, $DB;

        $yearStr =  (empty($year)) ? "" : "AND l.year='".$year."'";
        $typeWhere = ($page->user->canSell()) ? "" : " AND l.type='For Sale'";

        $sql = "SELECT l.uom as uomid, concat(l.uom||' ('||count(*)||')') as uomname
            FROM listings l
            JOIN subcategories s on s.subcategoryid=l.subcategoryid
            JOIN userinfo ui
                ON ui.userid=l.userid
                AND ui.userclassid=3
                AND (
                    (l.type='For Sale' AND ui.vacationsell=0)
                    OR
                    (l.type='Wanted' AND ui.vacationbuy=0)
                    )
            JOIN assignedrights ar on ar.userid=l.userid AND ar.userrightid=1
            LEFT JOIN assignedrights stl on stl.userid=l.userid AND stl.userrightid=".USERRIGHT_STALE."
            WHERE l.status='OPEN' ".$typeWhere."
              AND l.categoryId=".$categoryId."
              AND l.boxtypeid=".$boxTypeId."
              ".$yearStr."
              AND l.subcategoryid='".$subCategoryId."'
              AND l.userid<>".FACTORYCOSTID."
              AND stl.userid IS NULL
            GROUP BY l.uom
            ORDER BY l.uom";

        $uomData = $DB->sql_query($sql);
//echo "Listing UOMs SQL:<br /><pre>".$sql."</pre><br />\n";
        return $uomData;
    }

    public function getCategoryName($categoryId) {
        global $DB;

       $sql = "
        SELECT categoryname FROM categories WHERE categoryId = ".$categoryId."
       ";
        $categoryName = $DB->get_field_query($sql);

        return $categoryName;
    }

    public function getCategoryDesc($categoryId) {
        global $DB;

       $sql = "
        SELECT categorydescription FROM categories WHERE categoryId = ".$categoryId."
       ";
        $categoryDesc = $DB->get_field_query($sql);

        return $categoryDesc;
    }

    public function getCategoryType() {
        global $DB;

        $sql = "
            SELECT categoryTypeId, categoryTypeName
              FROM categoryTypes
             ORDER BY sort
        ";
          $info = $DB->sql_query_params($sql);

          return $info;
    }

    public function getCategoryTypeName($categoryTypeId) {
        global $DB;

       $sql = "
        SELECT categoryTypeName FROM categoryTypes WHERE categoryTypeId = ".$categoryTypeId."
       ";
        $info = $DB->get_field_query($sql);

        return $info;
    }

    public function doesProductExist($catid, $subcatid, $boxtypeid, $year = null) {
        global $DB;

        $yr = (empty($year)) ? "NULL" : "'".$year."'";
        $sql = "
            SELECT productid
              FROM products
             WHERE categoryid         = ".$catid."
               AND subcategoryid      = ".$subcatid."
               AND boxtypeid          = ".$boxtypeid."
               AND isnull(year, '1')  = isnull(".$yr.", '1')
        ";
        $exists = $DB->get_field_query($sql);

        return $exists;
    }

    public function getUPC($catid, $subcatid, $boxtypeid, $year = null) {
        global $DB;

        $yr = (empty($year)) ? "NULL" : "'".$year."'";
        $sql = "
            SELECT array_to_string(array_agg(pu.upc), '<br>') as upcs
              FROM products     p
              JOIN product_upc  pu  ON  pu.productid    = p.productid
             WHERE p.categoryid         = ".$catid."
               AND p.subcategoryid      = ".$subcatid."
               AND p.boxtypeid          = ".$boxtypeid."
               AND isnull(p.year, '1')  = isnull(".$yr.", '1')
        ";
        $upc = $DB->get_field_query($sql);

        return $upc;
    }

    public function getVariation($catid, $subcatid, $boxtypeid, $year = null) {
        global $DB;

        $yr = (empty($year)) ? "NULL" : "'".$year."'";
        $sql = "
        SELECT variation
          FROM products
         WHERE categoryid       = ".$catid."
           AND subcategoryid    = ".$subcatid."
           AND boxtypeid        = ".$boxtypeid."
           AND isnull(year, '1')  = isnull(".$yr.", '1')
        LIMIT 1
        ";
        $upc = $DB->get_field_query($sql);

        return $upc;
    }

    public function getReleaseDate($catid, $subcatid, $boxtypeid, $year = null) {
        global $DB;

        $yr = (empty($year)) ? "NULL" : "'".$year."'";
        $sql = "
            SELECT p.releasedate
              FROM products     p
              JOIN categories   cat ON  cat.categoryid  = p.categoryid
             WHERE p.categoryid         = ".$catid."
               AND p.subcategoryid      = ".$subcatid."
               AND p.boxtypeid          = ".$boxtypeid."
               AND ((cat.categorytypeid = ".LISTING_TYPE_SPORTS."
                     AND p.year = '".$year."')
                    OR
                    (cat.categorytypeid = ".LISTING_TYPE_GAMING."
                     AND p.year IS NULL))
            LIMIT 1
        ";
        $rd = $DB->get_field_query($sql);

        return $rd;
    }

    public function getListingTypeId($categoryid) {
        global $DB;

        $typeid = null;
        if ($categoryid) {
            $typeid = $DB->get_field_query("SELECT categorytypeid FROM categories WHERE categoryid=".$categoryid);
        }

        return ($typeid);
    }

    public function getDealers() {
        global $DB;

        $sql = "
            SELECT us.userId, us.userName, inf.onvacation
              FROM users us
              JOIN userinfo inf ON inf.userid = us.userid
             ORDER BY userName
        ";
//or WHERE onvaction = 1
          $data = $DB->sql_query_params($sql);

          return $data;
    }

    public function getDealerId($dealerName) {
        global $DB;

       $sql = "
        SELECT userid, username FROM users WHERE lower(username) = lower('".$dealerName."')
       ";
        $data = $DB->get_field_query($sql);

        return $data;
    }

    public function getDealersName($dealerId) {
        global $DB;

        $sql = "SELECT username FROM users WHERE userid = ".$dealerId."";
        $data = $DB->get_field_query($sql);

        return $data;
    }

    public function getImessaegeInfo($offerId) {
        global $DB;

       $sql = "SELECT threadid, parentid FROM offers WHERE offerid = ".$offerId." ORDER BY threadId DESC LIMIT 1";
       $data = $DB->get_field_query($sql);

        return $data;
    }

    public function getSubCategories($categoryId = NULL, $active = 1) {
        global $DB;

        $isactive = ($active == 0 || $active == 1) ? $active : "0,1";
        $catid = (is_null($categoryId)) ? "cat.categoryid" : $categoryId;
        $sql = "
            SELECT sub.subcategoryid, sub.subcategoryname, sub.active,
                   cat.categoryname, cat.categoryid
              FROM subCategories    sub
              JOIN categories       cat ON  cat.categoryid = sub. categoryid
             WHERE sub.active IN (".$isactive.")
               AND cat.active IN (".$isactive.")
               AND sub.categoryid = ".$catid."
             ORDER BY sub.active DESC, cat.categoryname, sub.subcategoryname COLLATE \"POSIX\"";

          $info = $DB->sql_query_params($sql);

          return $info;
    }

    public function getSubCategoryName($subCategoryId) {
        global $DB;

        $sql = "SELECT subCategoryName FROM subCategories WHERE subCategoryId = ".$subCategoryId;
        $subCategoryName = $DB->get_field_query($sql);

        return $subCategoryName;
    }

    public function getUsersByName($userName) {
        global $DB;

        $sql = "SELECT username FROM users WHERE lower(username) LIKE lower('%".$userName."%')";
        $data = $DB->sql_query_params($sql);

        return $data;
    }

    public function getUserId($username) {
        global $DB;

        $sql = "SELECT userid FROM users WHERE lower(username) = lower('".$username."')";
        $result = $DB->get_field_query($sql);

        return $result;
    }

    public function getUserName($userId) {
        global $DB;

        $sql = "SELECT username FROM users WHERE userid = ".$userId;
        $result = $DB->get_field_query($sql);

        return $result;
    }

    public function getUserLedgerBalance($userid) {
        global $DB;

        $sql = "SELECT sum(dgrossamount) AS ledgerbalance FROM transactions WHERE useraccountid = '".$userid."'";
        $result = $DB->get_field_query($sql);

        return $result;
    }

    public function getYearFormatType() {
        global $DB;

        $sql = "
            SELECT yearformattypeid, yearformattype
              FROM yearformattype
             ORDER BY sort
        ";
        $yearFormatTypeData = $DB->sql_query_params($sql);

        return $yearFormatTypeData;
    }

//getSYearFormatTypeName - DDM gets ID use the ID the show the name
    public function getSYearFormatTypeName($yearFormatTypeId) {
        global $DB;

        $sql = "SELECT yearformattype FROM yearformattype WHERE yearformattypeid = ".$yearFormatTypeId;
        $yearFormatTypeName = $DB->get_field_query($sql);

        return $yearFormatTypeName;
    }

    public function getYearFormatTypeId($categoryId) {
        global $DB;

        $sql = "SELECT yearformattypeid FROM categories WHERE categoryId = ".$categoryId;
        $categoryInfo = $DB->get_field_query($sql);

        return $categoryInfo;
    }

    public function nextval($nextval) {
        global $DB;

        $sql = "SELECT nextval('".$nextval."')";
        $info = $DB->get_field_query($sql);

        return $info;
    }

    public function radioChecked($value, $row) {
        if ($value == $row) {
            $checked = "checked";
        } else {
            $checked = "";
        }
        return $checked;
    }

    public function removeRow($row) {

        if (!empty($row)) {
        $row = reset($row);
        }
        return $row;
    }

    public function selected($value, $row) {
        if ($value == $row) {
            $selected = "selected";
        } else {
            $selected = "";
        }
        return $selected;
    }

    public function dateToInt($mmddyyySlash) {

        $today = strtotime(date($mmddyyySlash, time()). '00:00:00');

        return $today;
    }

    public function folderType($folderTypeId) {
        global $DB;

        $sql = "SELECT folderType FROM folderTypes WHERE folderTypeId = ".$folderTypeId;
        $data = $DB->get_field_query($sql);

        return $data;
    }

    public function pagination($limit, $pages, $search, $totalpages) {
        if ($totalpages >=1 && $pages <= $totalpages) {
            $counter = 1;
            $output = "";
            $link = "";
            if ($pages > ($limit/2)) {
                 $link .= "<a href=\"?page=1\">1 </a> ... ";
            }
            for ($x=$pages; $x<=$totalpages;$x++) {

                if($counter < $limit)
                    $link .= "<a href=\"?page=" .$x."\">".$x." </a>";

                $counter++;
            }

            if ($pages < $totalpages - ($limit/2)) {
                if ($pages >= 2) {
                    echo "<a href='?page=".($pages-1)."&search=".$search."' class='button'>Previous</a>\n";
                }
                $output .= $link .= "... " . "<a href=\"?page=" .$totalpages."\">".$totalpages." </a>\n";
                $output .= "<a href='?page=".($pages+1)."&search=".$search."' class='button'>NEXT</a>\n";
            }
        }

        return $output;
    }

    public function checkBlockedIP() {
        global $DB;

        // BLOCKEDIPS are not in the database - skip for now
        return;

        $succsess = FALSE;
        $ipdata = "";
        $mobile = 0;

//////USER INFO
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        if (
            preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$userAgent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($userAgent,0,4))) {
            $mobile = 1;
        }

        if ($mobile == 0) {
//// Get real visitor IP behind CloudFlare network
            if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
                $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
                $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            }
            $client  = @$_SERVER['HTTP_CLIENT_IP'];
            $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
            $remote  = $_SERVER['REMOTE_ADDR'];

            if (filter_var($client, FILTER_VALIDATE_IP)) {
                $ip = $client;
            } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
                $ip = $forward;
            } else {
                $ip = $remote;
            }
//check for blocked ip here//////////
            $sql = "
                SELECT userid, blockeduserip
                  FROM blockedips
                 WHERE blockeduserip = '".$ip."'";

            $blocked = $DB->sql_query($sql);
            if (isset($blocked)) {
                //$MESSAGES->addErrorMsg("This IP - ".$ip."has been blocked. Contact Admin for details.");
//block force logout
            session_destroy();
//block from all pages
            header('location:forbidden.php?ip='.$ip);
            exit();

            }
        }


    }

    public function checkAdmin() {

    }

    public function getListingImageURL($picture) {
        $url = null;

        $lastslash = strrpos($picture, "/");
        $namelen = strlen($picture);
        if ($namelen) {
            if ($lastslash) {
                $imgname = substr($picture, $lastslash+1, ($namelen-$lastslash)-1);
                $url = "viewImage.php?img=".$imgname;
            } else {
                $firstchar = substr($picture, 0, 1);
                if (is_numeric($firstchar)) {
                    $url = "viewImage.php?img=".$picture;
                }
            }
        }

        return $url;
    }

    public function getPrefixPublicImageURL($picture, $type = NULL) {
        global $CFG;

        $url = null;

        if ($picture) {
            $lastslash = strrpos($picture, "/", -1);
            $namelen = strlen($picture);
            if ($namelen) {
                if ($lastslash) {
                    if (str_contains($picture, "images/listings")) {
                        $imgname = substr($picture, $lastslash+1, ($namelen-$lastslash)-1);
                        $url = "imageviewer.php?img=".$CFG->sharedImagesPath.$imgname;
                    }
                } else {
                    $firstchar = substr($picture, 0, 1);
                    if (is_numeric($firstchar)) {
                        $imagepath = $CFG->dataroot.$CFG->sharedImagesPath;
                        $image = $imagepath.$picture;
                        if (empty($type)) {
                            $url = "imageviewer.php?img=".$CFG->sharedImagesPath.$picture;
                        } elseif (file_exists($image)) {
                            $thumb = "";
                            if ($type == THUMB100) {
                                $thumbpath  = $CFG->dataroot.$CFG->sharedImagesThumb100Path;
                                $webpath    = $CFG->sharedImagesThumb100Path;
                                $thumb      = $thumbpath.$picture;
                                $width      = 100;
                                $height     = 100;
                            } elseif ($type == THUMB150) {
                                $thumbpath  = $CFG->dataroot.$CFG->sharedImagesThumb150Path;
                                $webpath    = $CFG->sharedImagesThumb150Path;
                                $thumb      = $thumbpath.$picture;
                                $width      = 150;
                                $height     = 150;
                            }
                            if (file_exists($thumb)) {
                                $url = "imageviewer.php?img=".$webpath.$picture;
                            } else {
                                $this->makeThumbnail($imagepath, $thumbpath, $picture, $width, $height);
                                if (file_exists($thumb)) {
                                    $url = "imageviewer.php?img=".$webpath.$picture;
                                } else {
                                    $url = "imageviewer.php?img=".$CFG->sharedImagesPath.$picture;
                                }
                            }
                        } else {
                            $url = "/images/spacer.gif";
                        }
                    }
                }
            }
        }

        return $url;
    }

    public function getPrefixListingImageURL($picture) {
        global $CFG;

        $url = null;

        if ($picture) {
            $lastslash = strrpos($picture, "/", -1);
            $namelen = strlen($picture);
            if ($namelen) {
                if ($lastslash) {
                    if (str_contains($picture, "images/listings")) {
                        $imgname = substr($picture, $lastslash+1, ($namelen-$lastslash)-1);
                        $url = "imageviewer.php?img=".$CFG->listingsPath.$imgname;
                    }
                } else {
                    $firstchar = substr($picture, 0, 1);
                    if (is_numeric($firstchar)) {
                        $url = "imageviewer.php?img=".$CFG->listingsPath.$picture;
                    }
                }
            }
        }

        return $url;
    }

    public function getPrefixMemberImageURL($picture) {
        global $CFG;

        $url = null;

        if ($picture) {
            $url = "imageviewer.php?img=".$CFG->memberLogosPath.$picture;
        }

        return $url;
    }

    public function getPrefixAttachmentImageURL($picture) {
        global $CFG;

        $url = null;

        if ($picture) {
            $url = "imageviewer.php?img=".$CFG->attachmentsPath.$picture;
        }

        return $url;
    }

    public function getPrefixAdminMultiImageURL($picture) {
        global $CFG;

        $url = null;

        if ($picture) {
            $url = "imageviewer.php?img=".$CFG->adminmultiPath.$picture;
        }

        return $url;
    }

    public function getPrefixAdvertImageURL($picture) {
        global $CFG;

        $url = null;

        if ($picture) {
            $url = "imageviewer.php?img=".$CFG->advertPath.$picture;
        }

        return $url;
    }

    public function getPrefixBlastURL($picture) {
        global $CFG;

        $url = null;

        if ($picture) {
            $url = "imageviewer.php?img=".$CFG->blastDocPath.$picture;
        }

        return $url;
    }

    function checkCollar($checkId, $isUpdate=true) {
        global $page;

        $msg = null;

        //echo "Check price collar for listing ".$checkId."<br />\n";

        $sql = "SELECT l.type, l.boxprice, max(buyprice) as highbuy, min(buyprice) as lowbuy, max(sellprice) as highsell, min(sellprice) as lowsell
            FROM listings l
            JOIN (
                SELECT
                    CASE WHEN ol.type='Wanted' THEN ol.boxprice ELSE null END AS buyprice
                    ,CASE WHEN ol.type='For Sale' THEN ol.boxprice ELSE null END AS sellprice
                FROM listings l
                JOIN listings ol
                    ON ol.listingid <> l.listingid
                    AND ol.userid <> ".FACTORYCOSTID."
                    AND ol.status = 'OPEN'
                    AND ol.uom IN ('box','case')
                    AND ol.categoryid=l.categoryid
                    AND ol.subcategoryid=l.subcategoryid
                    AND ol.boxtypeid=l.boxtypeid
                    AND ol.year=l.year
                WHERE l.listingid=".$checkId."
            ) typeprice on 1=1
            WHERE l.listingid=".$checkId."
              AND l.status='OPEN'
              AND l.uom IN ('box','case')
            GROUP BY l.type, l.boxprice";
        //echo "SQL:<br />\n<pre>".$sql."</pre><br />\n";
        $collars = $page->db->sql_query_params($sql);
        if ($collars) {
            $collar = reset($collars);
            //echo "Type:".$collar['type']." Collar Price:".$collar['boxprice']." LowSell:".$collar['lowsell']." HighSell:".$collar['highsell']." LowBuy:".$collar['lowbuy']." HighBuy:".$collar['highbuy']."<br />\n";
            if ($collar['boxprice']) {
                if ($collar['type'] == 'Wanted') {
                    $high = isset($collar['highbuy']) ? ($collar['highbuy'] * 1.25) : null;
                    $low = isset($collar['lowbuy']) ? ($collar['lowbuy'] * 0.35) : null;
                } else {
                    $high = isset($collar['highsell']) ? ($collar['highsell'] * 1.5) : null;
                    $low = isset($collar['lowsell']) ? ($collar['lowsell'] * 0.8) : null;
                }

                //echo "Price:".$collar['boxprice']." Low:".$low." High:".$high."<br />\n";

                if (($high && ($collar['boxprice'] > $high))
                ||  ($low && ($collar['boxprice'] < $low))) {
                    if ($isUpdate) {
                        $msg = "Listing ".$checkId." has been updated, but the new price of ".floatToMoney($collar['boxprice'])." is outside of price collar ".floatToMoney($low)." to ".floatToMoney($high).".";
                    } else {
                        $msg = "Listing ".$checkId." price of ".floatToMoney($collar['boxprice'])." is outside of price collar ".floatToMoney($low)." to ".floatToMoney($high).".";
                    }
                    $page->messages->addWarningMsg($msg);
                }
            }
        }

        return $msg;
    }

    /***
     * Generates a password of N length containing at least one of the
     * designated pools of characters.  Available pools are:
     *   - lower case letters,
     *   - uppercase letters,
     *   - digits,
     *   - special characters.
     * The remaining characters in the password are chosen at random
     * from the designated sets.
     *
     * The available characters in each set are user friendly
     *   - There are no ambiguous characters such as i, l, 1, o, 0, etc.
     *     this makes it much easier for users to manually type or
     *     speak their passwords.
     ***/
    public function generatePassword($length = 8, $setstouse = "luds") {
        $lowercaseletters = "abcdefghjkmnpqrstuvwxyz";
        $uppercaseletters = "ABCDEFGHJKMNPQRSTUVWXYZ";
        $digits           = "23456789";
        $specials         = "!@#$%&*?^";
        $sets = array();
        if(strpos($setstouse, "l") !== false)
            $sets[] = $lowercaseletters;
        if(strpos($setstouse, "u") !== false)
            $sets[] = $uppercaseletters;
        if(strpos($setstouse, "d") !== false)
            $sets[] = $digits;
        if(strpos($setstouse, "s") !== false)
            $sets[] = $specials;

        $all = "";
        $password = "";
        foreach($sets as $set){
            $password .= $set[array_rand(str_split($set))];
            $all .= $set;
        }

        $all = str_split($all);
        for($i = 0; $i < $length - count($sets); $i++) {
            $password .= $all[array_rand($all)];
        }

        $password = str_shuffle($password);

        return $password;
    }

    function ordinalSuffix($number) {
        $ends = array('th','st','nd','rd','th','th','th','th','th','th');
        if ((($number % 100) >= 11) && (($number%100) <= 13))
            return $number. 'th';
        else
            return $number. $ends[$number % 10];
    }

    function is_multi_array( $arr ) {
        rsort( $arr );
        return isset( $arr[0] ) && is_array( $arr[0] );
    }

    function export($exportdata, $filename) {
        if (!empty($exportdata)) {
            $data = array();
            if ($this->is_multi_array($exportdata[0])) {
                $data = $exportdata;
            } else {
                $data[] = $exportdata;
            }
//echo "<pre>";
//print_r($data);
//echo "</pre>";
//exit();
            header("Content-type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=".$filename);
            header("Pragma: no-cache");
            header("Expires: 0");
            $x = 0;
            foreach($data as $dim) {
                $x++;
                $firstrow = true;
                echo "\n";
                foreach($dim as $r) {
                    if ($firstrow) {
                        $colHeaders = array_keys($r);
                        $colCount = count($colHeaders);
                        $firstrow = false;
                        $x = 0;
                        foreach($colHeaders as $h) {
                            if ($h <> "sortorder" && $h <> "rownum" && strpos($h, "skip_") === FALSE) {
                                echo ucwords(str_replace("_"," ",$h));
                                $x++;
                                if ($x < $colCount) {
                                    echo ",";
                                }
                            }
                        }
                        echo "\n";
                    }
                    $x = 0;
                    foreach($colHeaders as $h) {
                        if ($h <> "sortorder" && $h <> "rownum" && strpos($h, "skip_") === FALSE) {
                            $tmp = str_replace("<br>", "\n", $r[$h]);
                            if (strpos($tmp,",") > -1 || strpos($tmp,"\n") > -1) {
                                echo "\"".$tmp."\"";
                                $x++;
                                if ($x < $colCount) {
                                  echo ",";
                                }
                            } else {
                                echo $tmp;
                                $x++;
                                if ($x < $colCount) {
                                  echo ",";
                                }
                            }
                        }
                    }
                    echo "\n";
                }
                echo "\n";
            }
            exit;
        }
    }

    function printpreview($data) {
        if (!empty($data)) {
            echo "<table>\n";
            $firstrow = true;
            foreach($data as $r) {
                if ($firstrow) {
                    $colHeaders = array_keys($r);
                    $colCount = count($colHeaders);
                    $firstrow = false;
                    $x = 0;
                    echo "  <thead>\n";
                    echo "    <tr>\n";
                    foreach($colHeaders as $h) {
                        if ($h <> "sortorder" && $h <> "rownum" && strpos($h, "skip_") === FALSE) {
                            echo "      <th>\n";
                            echo "        ".ucwords(str_replace("_"," ",$h))."\n";
                            echo "      </th>\n";
                            $x++;
                        }
                    }
                    echo "    </tr>\n";
                    echo "  </thead>\n";
                    echo "  <tbody>\n";
                }
                $x = 0;
                echo "    <tr>\n";
                foreach($colHeaders as $h) {
                    if ($h <> "sortorder" && $h <> "rownum" && strpos($h, "skip_") === FALSE) {
                        echo "      <td>".$r[$h]."</td>\n";
                        $x++;
                    }
                }
                echo "    </tr>\n";
            }
            echo "  </tbody>\n";
            echo "</table>\n";
        }
    }

    public function alertFriendlyString($string) {
        return strip_tags(str_replace("\r","",str_replace("\n","\\n",str_replace("\"","", str_replace("'","", $string)))));
    }

    public function htmlFriendlyString($string) {
        return str_replace("\n","<br>",stripslashes($string));
    }

    public function inputFriendlyString($string) {
        return str_replace("'","&#39;",stripslashes($string));
    }

    public function textFriendlyString($string) {
        return stripslashes($string);
    }

    public function getHolidays($yr = null) {

        $year = (empty($yr)) ? date('Y') : $yr;
        $holidays = array();
        $holidays[strtotime($year."-01-01")]                        = "New Years Day";
        $holidays[strtotime("third Monday of January ".$year)]      = "MLK Day";
        $holidays[strtotime("third Monday of February ".$year)]     = "Presidents Day";
        $holidays[strtotime("last Monday of May ".$year)]           = "Memorial Day";
        $holidays[strtotime($year."-06-19")]                        = "Juneteenth";
        $holidays[strtotime($year."-07-04")]                        = "Independence Day";
        $holidays[strtotime("first Monday of September ".$year)]    = "Labor Day";
        $holidays[strtotime("second Monday of October ".$year)]     = "Columbus Day";
        $holidays[strtotime($year."-11-11")]                        = "Veterans Day";
        $holidays[strtotime("fourth Thursday of November ".$year)]  = "Thanksgiving Day";
        $holidays[strtotime($year."-12-24")]                        = "Christmas Eve";
        $holidays[strtotime($year."-12-25")]                        = "Christmas Day";
        $holidays[strtotime($year."-12-31")]                        = "New Years Eve";


        $nextyear = date('Y', strtotime('+1 year'));
        $holidays[strtotime($nextyear."-01-01")]                        = "New Years Day";
        $holidays[strtotime("third Monday of January ".$nextyear)]      = "MLK Day";
        $holidays[strtotime("third Monday of February ".$nextyear)]     = "Presidents Day";
        $holidays[strtotime("last Monday of May ".$nextyear)]           = "Memorial Day";
        $holidays[strtotime($nextyear."-06-19")]                        = "Juneteenth";
        $holidays[strtotime($nextyear."-07-04")]                        = "Independence Day";
        $holidays[strtotime("first Monday of September ".$nextyear)]    = "Labor Day";
        $holidays[strtotime("second Monday of October ".$nextyear)]     = "Columbus Day";
        $holidays[strtotime($nextyear."-11-11")]                        = "Veterans Day";
        $holidays[strtotime("fourth Thursday of November ".$nextyear)]  = "Thanksgiving Day";
        $holidays[strtotime($nextyear."-12-24")]                        = "Christmas Eve";
        $holidays[strtotime($nextyear."-12-25")]                        = "Christmas Day";
        $holidays[strtotime($nextyear."-12-31")]                        = "New Years Eve";


        return $holidays;
    }

    /***
     * skipxBusinessDays
     *   - Must pass in a zero or positive number of days to skip.
     *   - Enter 0 for current day (business or not)
     *   - We add 23:59:59 to the end of target
     ***/
    public function skipxBusinessDays($numberofdays = 0, $verbose = 0) {
        $numdays    = ($numberofdays < 0) ? 0 : $numberofdays;
        $holidays   = $this->getHolidays();
        $oneDay     = (60*60*24);
        $endOfDay   = (60*60*24)-1;   // 23:59:59
        $today      = strtotime("today");
        echo ($verbose) ? "<br>Start: ".date("m/d/Y H:i:s (l)",$today)." skipping:".$numdays." days" : "";
        /***
         * Outer loop controls the # of business days to skip.
         ***/
        $x      = 1;
        $target = $today;
        while ($numdays > 0) {
            $foundNextBusinessDay = false;
            $target = $target + $oneDay;
            echo ($verbose) ? "<br>Day ".$x." to go ".$numdays : "";
            /***
             * Inner loop: skips weekends and holidays. If target is weekday; it finds target.
             ***/
            do {
                $day = strtolower(date("l", $target));
                if ($day == "saturday" || $day == "sunday") {
                    echo ($verbose) ? "<br>Skipping: ".$day : "";
                    $target = $target + $oneDay;
                } elseif($target == array_key_exists($target, $holidays)) {
                    echo ($verbose) ? "<br>Skipping: ".$holidays[$target]." ".date("(l)",$target) : "";
                    $target = $target + $oneDay;
                } else {
                    echo ($verbose) ? "<br>Found BD: ".date("m/d/Y (l)",$target) : "";
                    $foundNextBusinessDay = true;
                }
            } while (!$foundNextBusinessDay);
            $x++;
            $numdays--;
        }
        $target += $endOfDay;
        echo ($verbose) ? "<br><b>Winner Winner: ".date("m/d/Y (l)",$target)."</b>" : "";

        return $target;
    }

    public function calculateUPCCheckDigit($upc_code) {
        $checkDigit = -1; // -1 == failure
        $upc = substr($upc_code,0,11);
        // send in a 11 or 12 digit upc code only
        if (strlen($upc) == 11 && strlen($upc_code) <= 12) {
            $oddPositions   = $upc[0] + $upc[2] + $upc[4] + $upc[6] + $upc[8] + $upc[10];
            $oddPositions  *= 3;
            $evenPositions  = $upc[1] + $upc[3] + $upc[5] + $upc[7] + $upc[9];
            $sumEvenOdd     = $oddPositions + $evenPositions;
            $checkDigit     = (10 - ($sumEvenOdd % 10)) % 10;
        }
        return $checkDigit;
    }

    public function calculateEANCheckDigit($upc_code) {
        $checkDigit = -1; // -1 == failure
        $upc = substr($upc_code,0,13);
        // send in a 13 digit JAN . EAN code only
        if (strlen($upc) == 13) {
            $oddPositions   = $upc[0] + $upc[2] + $upc[4] + $upc[6] + $upc[8] + $upc[10];
            $evenPositions  = $upc[1] + $upc[3] + $upc[5] + $upc[7] + $upc[9] + $upc[11];
            $evenPositions *= 3;
            $sumEvenOdd     = $oddPositions + $evenPositions;
            $checkDigit     = (10 - ($sumEvenOdd % 10)) % 10;
        }
        return $checkDigit;
    }

    public function validateUPC($upcCode) {
        $isValid = false;

        if (strlen($upcCode) == 12) {
            $checkDigit = $this->calculateUPCCheckDigit($upcCode);
            if ($checkDigit >= 0) {
                $lastDigit = $upcCode[11];
                if ($checkDigit == $lastDigit) {
                    $isValid = true;
                }
            }
        } elseif (strlen($upcCode) == 13) {
            $checkDigit = $this->calculateEANCheckDigit($upcCode);
            if ($checkDigit >= 0) {
                $lastDigit = $upcCode[12];
                if ($checkDigit == $lastDigit) {
                    $isValid = true;
                }
            }
        }

        return $isValid;
    }

    public function makeThumbnail($imgdir, $thumbdir, $img, $width = 100, $height = 100) {
        $imgt = NULL;
        $thumbnail_width    = $width;
        $thumbnail_height   = $height;
        $image = $imgdir.$img;
        $image_details      = getimagesize($image);
        if (is_array($image_details)) {
            $original_width     = $image_details[0];
            $original_height    = $image_details[1];
            if ($original_width > $original_height) {
                $new_width  = $thumbnail_width;
                $new_height = intval($original_height * $new_width / $original_width);
            } else {
                $new_height = $thumbnail_height;
                $new_width  = intval($original_width * $new_height / $original_height);
            }
            $dest_x = intval(($thumbnail_width - $new_width) / 2);
            $dest_y = intval(($thumbnail_height - $new_height) / 2);
            if ($image_details[2] == IMAGETYPE_GIF) {
                $imgt           = "imagegif";
                $imgcreatefrom  = "imagecreatefromgif";
            }
            if ($image_details[2] == IMAGETYPE_JPEG) {
                $imgt           = "imagejpeg";
                $imgcreatefrom  = "imagecreatefromjpeg";
                $quality        = 100;
            }
            if ($image_details[2] == IMAGETYPE_PNG) {
                $imgt           = "imagepng";
                $imgcreatefrom  = "imagecreatefrompng";
            }
            if ($imgt) {
                try {
                    // Create copy of old image in memory
                    $old_image = $imgcreatefrom($image);
                    // Create a new image of x/y dimensions in memory
                    $new_image = imagecreatetruecolor($thumbnail_width, $thumbnail_height);
                    // Disable alpha blending to preserve transparency
                    imagealphablending($new_image, false);
                    // Allocate a transparent color (black with full transparency)
                    $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
                    // Fill the image with the transparent color
                    imagefill($new_image, 0, 0, $transparent);
                    // Enable saving of the alpha channel
                    imagesavealpha($new_image, true);
                    // copy resampled/resized image to new image
                    imagecopyresampled($new_image, $old_image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);
                    // save image
                    $imgt($new_image, $thumbdir.$img);
                    // destroy image
                    imagedestroy($new_image);
                    imagedestroy($old_image);
                } catch (Exception $e) {
                    echo "<br>Error: ".$e->getMessage();
                    if (is_object($this->messages)) {
                        $this->messages->addErrorMsg("ERROR: ".$e->getMessage()." [Unable to create thumbnail]");
                    }
                } finally {
                }
            }
        }
    }

}
?>