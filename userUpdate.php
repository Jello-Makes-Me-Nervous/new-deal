<?php
require_once('template.class.php');

$page = new template(LOGIN, SHOWMSG);
$page->requireJS('scripts/validateCats.js');

$userId         = optional_param('userId', NULL, PARAM_INT);
$update         = optional_param('update', NULL, PARAM_TEXT);

$dealer = new User($userId);

$target = $CFG->memberLogosPath;
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

if (isset($update)) {
    scrapeUser($dealer);
    if (updateUser($dealer, $pictureUp, $target)) {
        header("location:dealerProfile.php?dealerId=".$dealer->userId."&pgsmsg=".URLEncode("User updated"));
    }
}

echo $page->header('Edit User');
echo mainContent($dealer);
echo $page->footer(true);

function mainContent($dealer) {
    global $CFG, $page, $userId;

    echo "<h3>Editing - ".$page->utility->htmlFriendlyString($dealer->firstname)."</h3>\n";
    echo "<form name ='update' action='userUpdate.php' method='post' enctype='multipart/form-data'>\n";
    echo "  <table>\n";
    echo "    <tbody>\n";
    echo "      <tr>\n";
    echo "        <td>ID</td>\n";
    echo "        <td>".$dealer->userId."</td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Create Date</td>\n";
    echo "        <td><input type='text' name='accountcreatedate' id='accountcreatedate' value='".$dealer->accountcreatedate."' /></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>User Name</td>\n";
    echo "        <td><input type='text' name='username' id='username' value='".$dealer->username."' /></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>User Class</td>\n";
    echo "        <td>".userClassDDM($dealer->userclassid)."</td>\n";
    echo "      </tr>\n";
    echo "        <td>Blue Star Mode</td>\n";
    echo "        <td>".blueStarModeDDM($dealer->bluestarmodeid)."</td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>First Name</td>\n";
    echo "        <td><input type='text' name='firstname' id='firstname' value='".$page->utility->inputFriendlyString($dealer->firstname)."' /></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Last Name</td>\n";
    echo "        <td><input type='text' name='lastname' id='lastname' value='".$page->utility->inputFriendlyString($dealer->lastname)."' /></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Forum Name</td>\n";
    echo "        <td><input type='text' name='forumname' id='forumname' value='".$dealer->forumname."' /></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Membership Fee</td>\n";
    echo "        <td><input type='text' name='membershipfee' id='membershipfee' value='".$dealer->membershipfee."' /></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Listing Fee</td>\n";
    echo "        <td><input type='text' name='listingfee' id='listingfee' value='".$dealer->listingfee."' /></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>EFT Withdraw PayPal ID</td>\n";
    echo "        <td><input type='text' name='paypalid' id='paypalid' value='".$dealer->paypalid."' /></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Ebay ID</td>\n";
    echo "        <td><input type='text' name='ebayid' id='ebayid' value='".$dealer->ebayid."' /></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Web URL</td>\n";
    echo "        <td><input type='text' name='weburl' id='weburl' value='".$dealer->weburl."' /></td>\n";
    echo "      </tr>\n";
    echo "    <tr>\n";
    echo "        <td>Listing Logo</td>\n";
    $imageLink = "";
    if ($dealer->listinglogo) {
        $imageLink = " <img src='".$page->utility->getPrefixMemberImageURL($dealer->listinglogo)."' width='50' height='50' style='border:1px solid #000;'/> ";
    } 
    echo "      <td align='left'>".$imageLink."</td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td align='left'>\n";
    echo "        Upload A New Image (gif & jpg only - Max:".(round(($CFG->IMG_MAX_UPLOAD/1000000),2))."MB)\n";
    echo "        <br />\n";
    echo "        <input type='file' name='pictureup' id='pictureup' />\n";
    echo "      </td>\n";
    echo "    </tr>\n";    echo "      <tr>\n";
    echo "        <td>Account Note</td>\n";
    echo "        <td><textarea name='accountnote' id='accountnote' cols='50' rows='5'>".$page->utility->textFriendlyString($dealer->accountnote)."</textarea></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Bank Info</td>\n";
    echo "        <td><textarea id='bankinfo' name='bankinfo' style='width:100%'>".$dealer->bankinfo."</textarea></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Ledger Balance</td>\n";
    echo "        <td>$".$page->utility->getUserLedgerBalance($dealer->userId)."</td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Credit Line</td>\n";
    echo "        <td>$<input type='text' name='dcreditline' id='dcreditline' value='".$dealer->dcreditline."' /></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td><input type='hidden' name='userId' value='".$userId."'</td>\n";
    echo "        <td><input type='submit' name='update' id='update' value='Update User' /> <a href='dealerProfile.php?dealerId=".$dealer->userId."&pgimsg=".URLEncode("Update cancelled")."' class='cancel-button'>Cancel</a></td>\n";
    echo "      </tr>\n";
    echo "    </tbody>\n";
    echo "  </table><br />\n";
    echo "  <table>\n";
    echo "    <thead>\n";
    echo "      <tr><th>Addresses</th><th>Pay To (as of ".date('m/d/Y',$dealer->address[BILLING]['modifydate']).")</th><th>Ship To (as of ".date('m/d/Y',$dealer->address[SHIPPING]['modifydate']).")</th></tr>\n";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    echo "      <tr>\n";
    echo "        <td>Company Name</td>\n";
    echo "        <td><input type='text' name='bill_companyname' id='bill_companyname' size='50' value='".$page->utility->inputFriendlyString($dealer->address[BILLING]['companyname'])."'></td>\n";
    echo "        <td><input type='text' name='ship_companyname' id='ship_companyname' size='50' value='".$page->utility->inputFriendlyString($dealer->address[SHIPPING]['companyname'])."'></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Phone</td>\n";
    echo "        <td><input type='text' name='bill_phone' id='bill_phone' size='50' value='".$dealer->address[BILLING]['phone']."'></td>\n";
    echo "        <td><input type='text' name='ship_phone' id='ship_phone' size='50' value='".$dealer->address[SHIPPING]['phone']."'></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Fax</td>\n";
    echo "        <td><input type='text' name='bill_fax' id='bill_fax' size='50' value='".$dealer->address[BILLING]['fax']."'></td>\n";
    echo "        <td><input type='text' name='ship_fax' id='ship_fax' size='50' value='".$dealer->address[SHIPPING]['fax']."'></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Email</td>\n";
    echo "        <td><input type='text' name='bill_email' id='bill_email' size='50' value='".$dealer->address[BILLING]['email']."'></td>\n";
    echo "        <td><input type='text' name='ship_email' id='ship_email' size='50' value='".$dealer->address[SHIPPING]['email']."'></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Street</td>\n";
    echo "        <td><input type='text' name='bill_street' id='bill_street' size='50' value='".$page->utility->inputFriendlyString($dealer->address[BILLING]['street'])."'></td>\n";
    echo "        <td><input type='text' name='ship_street' id='ship_street' size='50' value='".$page->utility->inputFriendlyString($dealer->address[SHIPPING]['street'])."'></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Street 2</td>\n";
    echo "        <td><input type='text' name='bill_street2' id='bill_street2' size='50' value='".$page->utility->inputFriendlyString($dealer->address[BILLING]['street2'])."'></td>\n";
    echo "        <td><input type='text' name='ship_street2' id='ship_street2' size='50' value='".$page->utility->inputFriendlyString($dealer->address[SHIPPING]['street2'])."'></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>City</td>\n";
    echo "        <td><input type='text' name='bill_city' id='bill_city' size='30' value='".$page->utility->inputFriendlyString($dealer->address[BILLING]['city'])."'></td>\n";
    echo "        <td><input type='text' name='ship_city' id='ship_city' size='30' value='".$page->utility->inputFriendlyString($dealer->address[SHIPPING]['city'])."'></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>State</td>\n";
    echo "        <td><input type='text' name='bill_state' id='bill_state'  size='2' value='".$dealer->address[BILLING]['state']."'></td>\n";
    echo "        <td><input type='text' name='ship_state' id='ship_state'  size='2' value='".$dealer->address[SHIPPING]['state']."'></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Zip</td>\n";
    echo "        <td><input type='text' name='bill_zip' id='bill_zip'  size='5' value='".$dealer->address[BILLING]['zip']."'></td>\n";
    echo "        <td><input type='text' name='ship_zip' id='ship_zip'  size='5' value='".$dealer->address[SHIPPING]['zip']."'></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Country</td>\n";
    echo "        <td><input type='text' name='bill_country' id='bill_country'  size='30' value='".$dealer->address[BILLING]['country']."'></td>\n";
    echo "        <td><input type='text' name='ship_country' id='ship_country'  size='30' value='".$dealer->address[SHIPPING]['country']."'></td>\n";
    echo "      </tr>\n";    
    echo "      <tr>\n";
    echo "        <td>Note</td>\n";
    echo "        <td><textarea name='bill_addressnote' id='bill_addressnote' cols='50' rows='5'>".$page->utility->textFriendlyString($dealer->address[BILLING]['addressnote'])."</textarea></td>\n";
    echo "        <td><textarea name='ship_addressnote' id='ship_addressnote' cols='50' rows='5'>".$page->utility->textFriendlyString($dealer->address[SHIPPING]['addressnote'])."</textarea></td>\n";
    echo "      </tr>\n";    
    echo "    </tbody>\n";
    echo "  </table><br />\n";
    
    echo "</form>\n";
}

function scrapeUser(&$dealer) {
    $dealer->accountcreatedate = optional_param('accountcreatedate', NULL, PARAM_TEXT);
    $dealer->username = optional_param('username', NULL, PARAM_TEXT);
    $dealer->userclassid = optional_param('userclassid', NULL, PARAM_TEXT);
    $dealer->bluestarmodeid = optional_param('bluestarmodeid', NULL, PARAM_INT);
    $dealer->firstname = optional_param('firstname', NULL, PARAM_TEXT);
    $dealer->lastname = optional_param('lastname', NULL, PARAM_TEXT);
    $dealer->forumname = optional_param('forumname', NULL, PARAM_TEXT);
    $dealer->membershipfee = nullTextNumeric('membershipfee');
    $dealer->listingfee = nullTextNumeric('listingfee');
    $dealer->paypalid = optional_param('paypalid', NULL, PARAM_TEXT);
    $dealer->ebayid = optional_param('ebayid', NULL, PARAM_TEXT);
    $dealer->weburl = optional_param('weburl', NULL, PARAM_TEXT);
    $dealer->accountnote = optional_param('accountnote', NULL, PARAM_TEXT);
    $dealer->bankinfo = optional_param('bankinfo', NULL, PARAM_TEXT);
    $dealer->dcreditline = nullTextNumeric('dcreditline');

    $updateNow = time();
    if ($dealer->address[BILLING]['companyname'] != optional_param('bill_companyname', NULL, PARAM_RAW)) $dealer->address[BILLING]['modifydate'] = $updateNow;
    if ($dealer->address[BILLING]['phone'] != optional_param('bill_phone', NULL, PARAM_TEXT)) $dealer->address[BILLING]['modifydate'] = $updateNow;
    if ($dealer->address[BILLING]['fax'] != optional_param('bill_fax', NULL, PARAM_TEXT)) $dealer->address[BILLING]['modifydate'] = $updateNow;
    if ($dealer->address[BILLING]['email'] != optional_param('bill_email', NULL, PARAM_TEXT)) $dealer->address[BILLING]['modifydate'] = $updateNow;
    if ($dealer->address[BILLING]['street'] != optional_param('bill_street', NULL, PARAM_TEXT)) $dealer->address[BILLING]['modifydate'] = $updateNow;
    if ($dealer->address[BILLING]['street2'] != optional_param('bill_street2', NULL, PARAM_TEXT)) $dealer->address[BILLING]['modifydate'] = $updateNow;
    if ($dealer->address[BILLING]['city'] != optional_param('bill_city', NULL, PARAM_TEXT)) $dealer->address[BILLING]['modifydate'] = $updateNow;
    if ($dealer->address[BILLING]['state'] != optional_param('bill_state', NULL, PARAM_TEXT)) $dealer->address[BILLING]['modifydate'] = $updateNow;
    if ($dealer->address[BILLING]['zip'] != optional_param('bill_zip', NULL, PARAM_TEXT)) $dealer->address[BILLING]['modifydate'] = $updateNow;
    if ($dealer->address[BILLING]['country'] != optional_param('bill_country', NULL, PARAM_TEXT)) $dealer->address[BILLING]['modifydate'] = $updateNow;
    if ($dealer->address[BILLING]['addressnote'] != optional_param('bill_addressnote', NULL, PARAM_TEXT)) $dealer->address[BILLING]['modifydate'] = $updateNow;

    $dealer->address[BILLING]['companyname'] = optional_param('bill_companyname', NULL, PARAM_RAW);
    $dealer->address[BILLING]['phone'] = optional_param('bill_phone', NULL, PARAM_TEXT);
    $dealer->address[BILLING]['fax'] = optional_param('bill_fax', NULL, PARAM_TEXT);
    $dealer->address[BILLING]['email'] = optional_param('bill_email', NULL, PARAM_TEXT);
    $dealer->address[BILLING]['street'] = optional_param('bill_street', NULL, PARAM_TEXT);
    $dealer->address[BILLING]['street2'] = optional_param('bill_street2', NULL, PARAM_TEXT);
    $dealer->address[BILLING]['city'] = optional_param('bill_city', NULL, PARAM_TEXT);
    $dealer->address[BILLING]['state'] = optional_param('bill_state', NULL, PARAM_TEXT);
    $dealer->address[BILLING]['zip'] = optional_param('bill_zip', NULL, PARAM_TEXT);
    $dealer->address[BILLING]['country'] = optional_param('bill_country', NULL, PARAM_TEXT);
    $dealer->address[BILLING]['addressnote'] = optional_param('bill_addressnote', NULL, PARAM_TEXT);

    if ($dealer->address[SHIPPING]['companyname'] != optional_param('ship_companyname', NULL, PARAM_RAW)) $dealer->address[SHIPPING]['modifydate'] = $updateNow;
    if ($dealer->address[SHIPPING]['phone'] != optional_param('ship_phone', NULL, PARAM_TEXT)) $dealer->address[SHIPPING]['modifydate'] = $updateNow;
    if ($dealer->address[SHIPPING]['fax'] != optional_param('ship_fax', NULL, PARAM_TEXT)) $dealer->address[SHIPPING]['modifydate'] = $updateNow;
    if ($dealer->address[SHIPPING]['email'] != optional_param('ship_email', NULL, PARAM_TEXT)) $dealer->address[SHIPPING]['modifydate'] = $updateNow;
    if ($dealer->address[SHIPPING]['street'] != optional_param('ship_street', NULL, PARAM_TEXT)) $dealer->address[SHIPPING]['modifydate'] = $updateNow;
    if ($dealer->address[SHIPPING]['street2'] != optional_param('ship_street2', NULL, PARAM_TEXT)) $dealer->address[SHIPPING]['modifydate'] = $updateNow;
    if ($dealer->address[SHIPPING]['city'] != optional_param('ship_city', NULL, PARAM_TEXT)) $dealer->address[SHIPPING]['modifydate'] = $updateNow;
    if ($dealer->address[SHIPPING]['state'] != optional_param('ship_state', NULL, PARAM_TEXT)) $dealer->address[SHIPPING]['modifydate'] = $updateNow;
    if ($dealer->address[SHIPPING]['zip'] != optional_param('ship_zip', NULL, PARAM_TEXT)) $dealer->address[SHIPPING]['modifydate'] = $updateNow;
    if ($dealer->address[SHIPPING]['country'] != optional_param('ship_country', NULL, PARAM_TEXT)) $dealer->address[SHIPPING]['modifydate'] = $updateNow;
    if ($dealer->address[SHIPPING]['addressnote'] != optional_param('ship_addressnote', NULL, PARAM_TEXT)) $dealer->address[SHIPPING]['modifydate'] = $updateNow;

    $dealer->address[SHIPPING]['companyname'] = optional_param('ship_companyname', NULL, PARAM_RAW);
    $dealer->address[SHIPPING]['phone'] = optional_param('ship_phone', NULL, PARAM_TEXT);
    $dealer->address[SHIPPING]['fax'] = optional_param('ship_fax', NULL, PARAM_TEXT);
    $dealer->address[SHIPPING]['email'] = optional_param('ship_email', NULL, PARAM_TEXT);
    $dealer->address[SHIPPING]['street'] = optional_param('ship_street', NULL, PARAM_TEXT);
    $dealer->address[SHIPPING]['street2'] = optional_param('ship_street2', NULL, PARAM_TEXT);
    $dealer->address[SHIPPING]['city'] = optional_param('ship_city', NULL, PARAM_TEXT);
    $dealer->address[SHIPPING]['state'] = optional_param('ship_state', NULL, PARAM_TEXT);
    $dealer->address[SHIPPING]['zip'] = optional_param('ship_zip', NULL, PARAM_TEXT);
    $dealer->address[SHIPPING]['country'] = optional_param('ship_country', NULL, PARAM_TEXT);
    $dealer->address[SHIPPING]['addressnote'] = optional_param('ship_addressnote', NULL, PARAM_TEXT);
}

    

function updateUser($dealer, $pictureUp, $target) {
    global $page;

    $process = null;
    $success = true;

    if (!empty($pictureUp)) {
        if ($dealer->listinglogo) {
            if (prefixDeleteImage($dealer->listinglogo, $page->cfg->memberLogosPath)) {
                $page->messages->addSuccessMsg("Previous logo deleted");
            } else {
                $page->messages->addErrorMsg("Unable to delete previous logo");
                $success = false;
            }
        }
        
        if ($success) {
            //echo "ID:".$dealer->userId." target:".$target."<br />\n<pre>"; var_dump($pictureUp); echo "</pre><br />\n";
            if ($img = prefixImgUp($pictureUp, $dealer->userId, $target, $page)) { //advertLib function
                $dealer->listinglogo = $img; //use this for the picture path
                //echo "img:".$img." picture:".$dealer->listinglogo."<br />\n";
                //exit;
            } else {
                $page->messages->addErrorMsg("Unable upload logo");
                $success = false;
            }
        }
    }
    
    if ($success) {
        $sql = "UPDATE users
               SET username   = :username
                    ,modifydate = nowtoint()
                    ,modifiedby = :modifiedby
               WHERE userid     = :userid";
    
        $params = array();
        $params['username']     = $dealer->username;
        $params['modifiedby']   = $page->user->username;
        $params['userid']       = $dealer->userId;
    
        $page->queries->AddQuery($sql, $params);
        unset($sql);
        unset($params);
    
        $sql = "UPDATE userinfo
            SET userclassid = :userclassid
                ,bluestarmodeid = :bluestarmodeid
                ,firstname = :firstname
                ,lastname = :lastname
                ,forumname = :forumname
                ,membershipfee = :membershipfee
                ,listingfee = :listingfee
                ,paypalid = :paypalid
                ,ebayid = :ebayid
                ,weburl = :weburl
                ,listinglogo = :listinglogo
                ,accountnote = :accountnote
                ,bankinfo = :bankinfo
                ,dcreditline = :dcreditline
                ,accountcreated = datetoint(:accountcreatedate)
                ,modifiedby = :modifiedby
                ,modifydate = nowtoint()
            WHERE userid = :userid
        ";
        $params = array();
        $params['userclassid'] = $dealer->userclassid;
        $params['bluestarmodeid'] = $dealer->bluestarmodeid;
        $params['firstname'] = $dealer->firstname;
        $params['lastname'] = $dealer->lastname;
        $params['forumname'] = $dealer->forumname;
        $params['membershipfee'] = $dealer->membershipfee;
        $params['listingfee'] = $dealer->listingfee;
        $params['paypalid'] = $dealer->paypalid;
        $params['ebayid'] = $dealer->ebayid;
        $params['listinglogo'] = $dealer->listinglogo;
        $params['weburl'] = $dealer->weburl;
        $params['accountnote'] = $dealer->accountnote;
        $params['bankinfo'] = $dealer->bankinfo;
        $params['dcreditline'] = $dealer->dcreditline;
        $params['accountcreatedate'] = $dealer->accountcreatedate;
        $params['modifiedby'] = $page->user->username;
        $params['userid'] = $dealer->userId;
    
        $page->queries->AddQuery($sql, $params);
    
        $sql = "UPDATE usercontactinfo
            SET companyname = :companyname
                ,street = :street
                ,street2 = :street2
                ,city = :city
                ,state = :state
                ,zip = :zip
                ,country = :country
                ,addressnote = :addressnote
                ,phone = :phone
                ,fax = :fax
                ,email = :email
                ,modifiedby = :modifiedby
                ,modifydate = :modifydate
             WHERE userid = :userid
               AND addresstypeid=:addresstypeid
        ";
    
        foreach ($dealer->address as $addressTypeId => $contact) {
            $params = array();
            $params['companyname']  = $contact['companyname'];
            $params['street']       = $contact['street'];
            $params['street2']      = $contact['street2'];
            $params['city']         = $contact['city'];
            $params['state']        = $contact['state'];
            $params['zip']          = $contact['zip'];
            $params['country']      = $contact['country'];
            $params['addressnote']  = $contact['addressnote'];
            $params['phone']        = $contact['phone'];
            $params['fax']          = $contact['fax'];
            $params['email']        = $contact['email'];
            $params['modifydate']   = $contact['modifydate'];
            $params['modifiedby']   = $page->user->username;
            $params['userid']       = $dealer->userId;
            $params['addresstypeid'] = $addressTypeId;
        
            $page->queries->AddQuery($sql, $params);
            unset($params);
        
            if ($dealer->bluestarmodeid == BLUESTAR_MODE_NO) {
                $params = array();
                $sql = "DELETE FROM assignedrights WHERE userid=".$dealer->userId." and userrightid=".USERRIGHT_BLUESTAR;
                $page->queries->AddQuery($sql, $params);
                unset($params);
            } else {
                if ($dealer->bluestarmodeid == BLUESTAR_MODE_YES) {
                    $params = array();
                    $sql = "INSERT INTO assignedrights (userid, userrightid, createdby, modifiedby)
                        SELECT u.userid, ".USERRIGHT_BLUESTAR." AS userrightid, '".$page->user->username."' AS createdby, '".$page->user->username."' AS modifiedby
                        FROM users u
                        LEFT JOIN assignedrights ar ON ar.userid=u.userid AND ar.userrightid=".USERRIGHT_BLUESTAR."
                        WHERE u.userid=".$dealer->userId." AND ar.userid IS NULL";
                    $page->queries->AddQuery($sql, $params);
                    unset($params);
                } else {
                }
            }
        }
        unset($sql);
    
        if ($page->queries->HasQueries()) {
            $process = $page->queries->ProcessQueries();
        }
        
        if ($process == TRUE) {
            $success = TRUE;
            $page->messages->addSuccessMsg('You have updated the user - '.$dealer->username);
        } else {
            $success = FALSE;
            $page->messages->addErrorMsg('Error?');
        }
    }

    return $success;
}

function blueStarModeDDM($blueStarModeId) {
    $output = ""; 
    
    $blueStarModes = array();
    $blueStarModes[] = array('bluestarmodeid' => BLUESTAR_MODE_AUTO, 'bluestarmode' => 'Auto');
    $blueStarModes[] = array('bluestarmodeid' => BLUESTAR_MODE_NO, 'bluestarmode' => 'No');
    $blueStarModes[] = array('bluestarmodeid' => BLUESTAR_MODE_YES, 'bluestarmode' => 'Yes');
    $output .= getSelectDDM($blueStarModes, "bluestarmodeid", "bluestarmodeid", "bluestarmode", NULL, $blueStarModeId);
    
    return $output;
}


?>