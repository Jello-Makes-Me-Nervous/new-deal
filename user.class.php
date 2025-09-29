<?php
DEFINE("BILLING", 1);
DEFINE("CONTACT", 2);
DEFINE("SHIPPING", 3);
DEFINE("ADMIN", "bolurADMIN");

DEFINE("USERCLASS_NEW", 1);
DEFINE("USERCLASS_BASIC", 5);
DEFINE("USERCLASS_PREMIUM", 2);
DEFINE("USERCLASS_VENDOR", 3);
DEFINE("USERCLASS_SUSPENDED", 4);

DEFINE("USERRIGHT_ENABLED", 1);
DEFINE("USERRIGHT_ELITE", 15);
DEFINE("USERRIGHT_IMAGES", 23);
DEFINE("USERRIGHT_STALE", 61);
DEFINE("USERRIGHT_NAME_ELITE", "Elite User");
DEFINE("USERRIGHT_ADMIN", 2);
DEFINE("USERRIGHT_STAFF", 11);
DEFINE("USERRIGHT_EFT_ENABLED", 20);
DEFINE("USERRIGHT_EFT_MEMBERSHIP", 62);
DEFINE("USERRIGHT_PRODUCT_ENTRY", 63);
DEFINE("USERRIGHT_BLUESTAR", 64);
DEFINE("USERRIGHT_VERIFIED", 65);
DEFINE("USERRIGHT_NAME_PRODUCT_ENTRY", "Product Entry");

DEFINE("USERPREFERENCE_INACTIVATE_FORSALE_ID", 2);
DEFINE("USERPREFERENCE_INACTIVATE_WANTED_ID", 3);

DEFINE("USERPREFERENCE_INACTIVATE_FORSALE", "Auto-Inactivate For Sale");
DEFINE("USERPREFERENCE_INACTIVATE_WANTED", "Auto-Inactivate Wanted");

DEFINE("BLUESTAR_MODE_AUTO", -1);
DEFINE("BLUESTAR_MODE_NO", 0);
DEFINE("BLUESTAR_MODE_YES", 1);


class user {

    public $userId;
    public $isenabled;
    public $username;
    public $userclassid;
    public $bluestarmodeid;
    public $userclassname;
    public $firstname;
    public $lastname;
    public $forumname;
    public $onvacation;
    public $returnondate;
    public $externalsig;
    public $internalsig;
    public $dcreditline;
    public $listinglogo;
    public $listinglogolocal;
    public $weburl;
    public $accountnote;
    public $bankinfo;
    public $membershipfee;
    public $listingfee;
    public $paypalid;
    public $ebayid;
    public $eintaxid;
    public $createdate;
    public $accountcreatedate;
//add ids
    public $referral;
    public $accepted;
    public $declined;
    public $revised;
    public $expired;

    public $address;
    public $userRights;
    public $userPrefs;

    public function __construct($userId) {
        $this->userId  = $userId;
        $this->address = array();
        $this->address[BILLING] = array();
        $this->address[CONTACT] = array();
        $this->address[SHIPPING] = array();
        if (!empty($userId)) {
            $this->loadData($this->userId);
        } else {
            $this->loadAnonymousUser();
        }
    }
////////////////////////////////////////
    public function isLoggedIn() {
        $isLoggedIn = false;
        if ($this->userId > 0) {
            if ($this->isenabled) {
                $isLoggedIn = true;
            }
        }
        return $isLoggedIn;
    }
///////////////////////////////////////////
    public function isNew() {
        $result = false;
        if ($this->userclassid == USERCLASS_NEW) {
            return $result = true;
        }
        return $result;
    }
    public function isBasic() {
        $result = false;
        if ($this->userclassid == USERCLASS_BASIC) {
            return $result = true;
        }
        return $result;
    }
    public function isPremium() {
        $result = false;
        if ($this->userclassid == USERCLASS_PREMIUM) {
            return $result = true;
        }
        return $result;
    }
    public function isVendor() {
        $result = false;
        if ($this->userclassid == USERCLASS_VENDOR) {
            return $result = true;
        }
        return $result;
    }
    public function isFactoryCost() {
        $result = false;
        if ($this->userId == FACTORYCOSTID) {
            return $result = true;
        }
        return $result;
    }
    public function isSuspended() {
        $result = false;
        if ($this->userclassid == USERCLASS_SUSPENDED) {
            return $result = true;
        }
        return $result;
    }
    public function canBuy() {
        $result = false;
        if (($this->userclassid == USERCLASS_BASIC) || ($this->userclassid == USERCLASS_PREMIUM) || ($this->userclassid == USERCLASS_VENDOR)) {
            return $result = true;
        }
        return $result;
    }
    public function canSell() {
        $result = false;
        if (($this->userclassid == USERCLASS_PREMIUM) || ($this->userclassid == USERCLASS_VENDOR)) {
            return $result = true;
        }
        return $result;
    }
    public function canOffer() {
        $result = false;
        if (($this->userclassid == USERCLASS_BASIC) || ($this->userclassid == USERCLASS_PREMIUM) || ($this->userclassid == USERCLASS_VENDOR)) {
            return $result = true;
        }
        return $result;
    }
    public function canList() {
        $result = false;
        if ($this->userclassid == USERCLASS_VENDOR) {
            return $result = true;
        }
        return $result;
    }
    public function canCounter() {
        $result = false;
        if ($this->userclassid == USERCLASS_VENDOR) {
            return $result = true;
        }
        return $result;
    }
///////////////////////////////////////////
    public function dealerHasRightOrAdmin($dealerId, $rightId) {
        global $DB;

        $hasRight = FALSE;
        
        $sql = "SELECT 1 
            FROM assignedrights ar 
            WHERE ar.userid=".$dealerId." 
              AND ar.userrightid=".USERRIGHT_ADMIN."
              LIMIT 1";
        $hasAdmin = $DB->get_field_query($sql);
        
        if ($hasAdmin) {
            $hasRight = TRUE;
        } else {
            $sql = "SELECT 1 
                FROM assignedrights ar 
                WHERE ar.userid=".$dealerId." 
                  AND ar.userrightid=".USERRIGHT_STAFF."
                  LIMIT 1";
                  
            $hasStaff = $DB->get_field_query($sql);
            if ($hasStaff) {
                $hasRight = TRUE;
            }
        }
        return $hasRight;
    }
    public function hasRightOrAdmin($rightId) {
        $hasRight = FALSE;
        if ($this->isAdmin()) {
            $hasRight = TRUE;
        } else {
            $hasRight = $this->hasUserRightId($rightId);
        }
        return $hasRight;
    }
    public function hasUserRightId($rightId) {
        $hasRight = FALSE;
        if($rightId && is_array($this->userRights) && (count($this->userRights) > 0)) {
            if (array_key_exists($rightId, $this->userRights)) {
                $hasRight = TRUE;
            }
        }
        return $hasRight;
    }
    public function hasUserRight($rightName) {
        $hasRight = FALSE;
        if(!empty($this->userRights)) {
            foreach ($this->userRights as $key => $value) {
                if (isset($value["userrightname"]) && $value["userrightname"] == $rightName) {
                    $hasRight = TRUE;
                }
            }
        }
        return $hasRight;
    }
// multiple admins
    public function isAdmin() {
        return $this->hasUserRight('ADMIN');
    }
    public function isStaff() {
        $hasIt = false;
        if ($this->isAdmin()) {
            $hasIt = true;
        } else {
            if ($this->hasUserRight('STAFF')) {
                $hasIt = true;
            }
        }
        return $hasIt;
    }
//Only one Super Admin with the username of ADMIN
    public function isSuperAdmin() {
        $isSuperAdmin = FALSE;
        if ($this->username == "ADMIN") {
            $isSuperAdmin = TRUE;
        }
        return $isSuperAdmin;
    }

    public function hasUserPrefs($preferenceid) {
        $hasPreference = FALSE;
        if ($this->userPrefs) {
            foreach ($this->userPrefs as $key => $value) {
                if ($key == $preferenceid) {
                    $hasPreference = TRUE;
                }
            }
        }
        return $hasPreference;
    }

    public function isProxiedAdmin() {
        global $page;

        $isPA = 0;

        if ($this->isProxied()) {
            $isPA = $page->db->get_field_query("SELECT count(*) FROM assignedrights ar WHERE ar.userrightid=".USERRIGHT_ADMIN." AND ar.userid=".$_SESSION['realUserId']);
        }

        return $isPA;
    }

    public function isProxied() {
        if (isset($_SESSION['realUserId'])) {
            if (isset($_SESSION['userId'])) {
                if (! ($_SESSION['realUserId'] == $_SESSION['userId'])) {
                    return true;
                }
            }
        }
        return false;
    }

    public function proxy($proxiedId) {
        global $page;

        if ($this->isAdmin()) {
            if (! $this->isProxied()) {
                $_SESSION['realUserId'] = $this->userId;
                $_SESSION['userId'] = $proxiedId;
                $this->userId = $_SESSION['userId'];
                $this->loadData($this->userId);
                $page->messages->addSuccessMsg("Proxied as ".$this->username." successfully");
            } else {
                $page->messages->addErrorMsg("Already proxied");
            }
        } else {
            $page->messages->addErrorMsg("Admin access required to proxy");
        }

//echo $this->dumpUser();
    }

    public function reverseProxy() {
        global $page;

        if ($this->isProxied()) {
            $_SESSION['userId'] = $_SESSION['realUserId'];
            unset($_SESSION['realUserId']);
            $this->userId = $_SESSION['userId'];
            $this->loadData($this->userId);
            $page->messages->addSuccessMsg("Unproxied successfully");
        } else {
            $page->messages->addErrorMsg("Not proxied");
        }
//echo $this->dumpUser();
    }

    public function dumpUser() {
        $output = "<table>";
        $info = (get_object_vars($this));
        foreach ($info as $key => $value) {
            switch (gettype($value)) {
                 case 'integer':
                 case 'double':
                 case 'string':
                 case 'NULL': //uknown and object
                                $output .= "<tr>";
                                $output .=   "<td style='font-weight: bold;'>".$key.":</td>";
                                $output .=   "<td colspan='2'>".$value."</td>";
                                $output .= "</tr>";
                                break;
                 case 'boolean':
                                $output .= "<tr>";
                                $output .=   "<td style='font-weight: bold;'>".$key.":</td>";
                                $b = ($value === FALSE) ? "FALSE" : "TRUE";
                                $output .=   "<td colspan='2'>".$b."</td>";
                                $output .= "</tr>";
                                break;
                 case 'array':

                                $output .= "<tr>";
                                $output .=   "<td style='font-weight: bold;'>".$key.":</td>";
                                $output .=   "<td colspan='2'>".gettype($value)."</td>";
                                $output .= "</tr>";
                                foreach($value as $idx => $val ){
                                    if (is_array($val)) {
                                        foreach( $val as $x => $v ){
                                            $output .= "<tr>";
                                            $output .=   "<td></td>";
                                            $output .=   "<td>".$x."</td>";
                                            $output .=   "<td>".$v."</td>";
                                            $output .= "</tr>";
                                        }
                                    } else {
                                        $output .= "<tr>";
                                        $output .=   "<td></td>";
                                        $output .=   "<td colspan='2'>".$val."</td>";
                                        $output .= "</tr>";
                                    }
                                }
                 default:

                      break;
            }
        }
        $output .= "</table>";
        return $output;
    }

    public function formatOfferContactInfo($contactInfo, $includePhoneEmail=false) {
        $output = "";
        if (!(empty($contactInfo['firstname']) && empty($contactInfo['lastname']))) {
            $output .= $contactInfo['firstname']." ".$contactInfo['lastname'];
            if (!empty($contactInfo['username'])) {
                $output .= " (".$contactInfo['username'].")";
            }
            $output .= "<br />";
        }
        if (strlen($contactInfo['companyname'])) {
            $output .= $contactInfo['companyname']."<br />";
        }
        $output .= $contactInfo['street']."<br />";
        if (strlen($contactInfo['street2'])) {
            $output .= $contactInfo['street2']."<br />";
        }
        $output .= $contactInfo['city']." ".$contactInfo['state']." ".$contactInfo['zip']."<br />";
        if (strlen($contactInfo['country'])) {
            $output .= $contactInfo['country']."<br />";
        }
        if ($includePhoneEmail) {
            if (strlen($contactInfo['phone'])) {
                $output .= $contactInfo['phone']."<br />";
            }
            if (strlen($contactInfo['email'])) {
                $output .= $contactInfo['email']."<br />";
            }
        }
        if (strlen($contactInfo['addressnote'])) {
            $output .= $contactInfo['addressnote']."<br />";
        }
        if (strlen($contactInfo['accountnote'])) {
            $output .= $contactInfo['accountnote']."<br />";
        }
        return $output;
    }

    public function formatAddress($addressTypeId) {
        //mailing address format form passed in type
        $output = "";
        foreach ($this->address as $keys => $value) {
            if ($keys == $addressTypeId) {
                $output .= $value['companyname']."<br />";
                $output .= $value['street']." ".$value['street2']."<br />";
                $output .= $value['city']." ".$value['state']." ".$value['zip']."<br />";
                $output .= $value['country']."<br />";
            }
        }
        return $output;
    }

    private function loadData($userId) {
        $this->clearProperties($userId);
        $ui = $this->getUserInfo($userId);
        if (!empty($ui)) {
            $keys = array_keys($ui);
            foreach($keys as $k) {
                if (property_exists($this, $k)) {
                    $this->$k = $ui[$k];
                }
            }
            $addresses =$this->getContactInfo($userId);
            if (!empty($addresses)) {
                foreach($addresses as $a) {
                    $this->address[$a["addresstypeid"]] = $a;
                }
            }
            $rights = $this->getUserRights($userId);
            if (!empty($rights)) {
                foreach($rights as $r) {
                    $this->userRights[$r["userrightid"]] = $r;
                }
            }
            $prefs = $this->getUserPrefs($userId);
            if (!empty($prefs)) {
                foreach($prefs as $p) {
                    $this->userPrefs[$p["preferenceid"]] = $p;
                }
            }
        }
    }

    private function clearProperties($userId) {
        $keys = get_object_vars($this);
        foreach($keys as $idx=>$k) {
            switch (gettype($this->$idx)) {
                case "array" :  unset($this->$idx);
                                break;
                default:        $this->$idx = NULL;
                                break;
            }
        }
        $this->userId = $userId;
        $this->address = array();
        $this->address[BILLING] = array();
        $this->address[CONTACT] = array();
        $this->address[SHIPPING] = array();
    }

    private function getUserInfo($userId) {
        global $DB;

        $sql = "
            SELECT ui.userclassid, ui.bluestarmodeid, ucl.userclassname, ui.firstname, ui.lastname, ui.forumname, ui.onvacation, ui.returnondate, ui.externalsig, ui.internalsig, ui.listinglogo,
                   ui.listinglogolocal, ui.weburl, ui.membershipfee, ui.listingfee, ui.paypalid, ui.ebayid, ui.accountnote, ui.eintaxid, ui.referral,
                   ui.bankinfo, ui.dcreditline,
                   ar.assignedrightsid as isenabled,
                   uc.accepted, uc.declined, uc.revised, uc.expired, us.username, inttommddyyyy_slash(ui.accountcreated) AS accountcreatedate
              FROM users                us
              JOIN userinfo             ui  ON  ui.userid           = us.userid
              JOIN userclass            ucl ON  ucl.userclassid     = ui.userclassid
              LEFT JOIN assignedrights  ar  ON  ar.userid           = us.userid
                                            AND ar.userrightid      = 1
              LEFT JOIN usercounts      uc  ON  uc.userid           = ui.userid
             WHERE us.userid = ".$userId."
        ";

        $row = $DB->sql_query_params($sql);
        if (!empty($row)) {
            $row = reset($row);
        }
        return $row;
    }

    private function getContactInfo($userId) {
        global $DB;

        $sql = "
           SELECT at.addresstypeid AS typeid, at.addresstypename,
                  uc.usercontactid, uc.companyName, uc.phone, uc.altphone, uc.fax, uc.email,
                  uc.addresstypeid, uc.street, uc.street2, uc.city, uc.state, uc.zip, uc.country,
                  uc.addressnote, uc.modifydate
             FROM addresstype           at
             LEFT JOIN usercontactinfo  uc   ON     uc.addresstypeid   = at.addresstypeid
                                            AND     uc.userid          = ".$userId."
             WHERE uc.addresstypeid IN (1,2,3)
        ";
        $rows = $DB->sql_query_params($sql);
        return $rows;
    }

    public function getContactInfoType($addressUserId, $addressTypeId) {
        global $DB;

        $sql = "
           SELECT at.addresstypeid AS typeid, at.addresstypename,
                  uc.usercontactid, uc.companyname, uc.phone, uc.altphone, uc.fax, uc.email,
                  uc.addresstypeid, uc.street, uc.street2, uc.city, uc.state, uc.zip, uc.country,
                  uc.addressnote,
                  u.userid, u.username,
                  ui.firstname, ui.lastname, ui.accountnote,
                  ui.counterminimumdtotal
             FROM addresstype           at
             LEFT JOIN usercontactinfo  uc   ON     uc.addresstypeid   = at.addresstypeid
                                            AND     uc.userid          = ".$addressUserId."
             LEFT JOIN userinfo         ui   ON     ui.userid          = uc.userid
             LEFT JOIN users            u    ON     u.userid           = uc.userid
             WHERE at.addresstypeid = ".$addressTypeId;
        $rows = $DB->sql_query_params($sql);
        $row = reset($rows);
        return $row;
    }

    private function getUserRights($userId) {
        global $DB;

        $sql = "
            SELECT  ur.userRightId, ur.userRightName, ur.description
              FROM  userRights          ur
              JOIN  assignedRights      ar   ON     ar.userRightId   = ur.userRightId
                                            AND     ar.userid        = ".$userId."
             ORDER BY ur.description COLLATE \"POSIX\"
        ";
        $rows = $DB->sql_query_params($sql);

        return $rows;
    }

    private function getUserPrefs($userId) {
        global $DB;

        $sql = "
            SELECT  ap.assignedprefid, ap.value,
                    up.preferenceid, up.preference, up.description, up.inputtype
              FROM  assignedpreferences ap
              JOIN  userpreferences     up  ON  ap.preferenceId   = up.preferenceId
             WHERE  ap.userid         = ".$userId."
             ORDER BY up.preference COLLATE \"POSIX\"
        ";
        $rs = $DB->sql_query_params($sql);

        return $rs;
    }

    private function loadAnonymousUser() {

    }

    public function isBelowStandard() {
        global $DB;
        
        $sql = "SELECT 1 AS isbelow
            FROM userinfo ui
            LEFT JOIN assignedrights aar ON aar.userid=ui.userid AND aar.userrightid=".USERRIGHT_ENABLED."
            LEFT JOIN assignedrights sar ON sar.userid=ui.userid AND sar.userrightid=".USERRIGHT_STALE."
            LEFT JOIN assignedrights elar ON elar.userid=ui.userid AND elar.userrightid=".USERRIGHT_ELITE."
            LEFT JOIN assignedrights bsar ON bsar.userid=ui.userid AND bsar.userrightid=".USERRIGHT_BLUESTAR."
            WHERE ui.userid=".$this->userId."
              AND userclassid=".USERCLASS_VENDOR."
              AND aar.userid IS NOT NULL
              AND sar.userid IS NULL
              AND elar.userid IS NULL
              AND bsar.userid IS NULL";
        $isBelow = $DB->get_field_query($sql);
        
        return $isBelow;
    }
}

function userClassDDM($userClassId, $noneLabel=NULL, $noneValue=0) {
    global $page;

    $output = "";

    $sql = "SELECT userclassid, userclassname, sortorder FROM userclass ORDER BY sortorder";
    $userclasses = $page->db->sql_query($sql);

    $output = "          ".getSelectDDM($userclasses, "userclassid", "userclassid", "userclassname", USERCLASS_NEW, $userClassId, $noneLabel, $noneValue)."\n";

    return $output;
}

function listingUserHasInactivate($listingUserId, $listingType) {
    global $page;
    
    $preferenceId = ($listingType == 'Wanted') ? USERPREFERENCE_INACTIVATE_WANTED_ID: USERPREFERENCE_INACTIVATE_FORSALE_ID;
    
    $sql = "SELECT count(*) AS hasinactivate FROM assignedpreferences WHERE userid=".$listingUserId." AND preferenceid=".$preferenceId;
    $hasInactivate = $page->db->get_field_query($sql);
    
    return $hasInactivate;
}

?>