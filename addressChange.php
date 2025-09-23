<?php
require_once('template.class.php');

$page = new template(LOGIN, SHOWMSG);
$page->requireJS('scripts/validateCats.js');

$userId         = optional_param('userId', $page->user->userId, PARAM_INT);
$update         = optional_param('update', NULL, PARAM_TEXT);

$dealerId = $userId;
$isMyProfile = ($dealerId == $page->user->userId) ? true : false;
$dealer = new User($userId);

$newPayTo = $UTILITY->getAddress($dealerId, ADDRESS_TYPE_REQUEST_PAY, $isMyProfile, true);
if (! $newPayTo) {
    if (!($newPayTo = $UTILITY->getAddress($dealerId, ADDRESS_TYPE_PAY, $isMyProfile, true))) {
        $page->messages->addErrorMsg("No requested or existing pay to address");
    }
}

$newShipTo = $UTILITY->getAddress($dealerId, ADDRESS_TYPE_REQUEST_SHIP, $isMyProfile, true);
if (! $newShipTo) {
    if (!($newShipTo = $UTILITY->getAddress($dealerId, ADDRESS_TYPE_SHIP, $isMyProfile, true))) {
        $page->messages->addErrorMsg("No requested or existing ship to address");
    }
}

if (isset($update)) {
    scrapeAddresses();
    if (updateAddresses($dealerId)) {
        header("location:dealerProfile.php?dealerId=".$dealer->userId."&pgsmsg=".URLEncode("Address change requested"));
    }
}

echo $page->header('Address Request');
echo mainContent($dealer);
echo $page->footer(true);

function mainContent($dealer) {
    global $page, $userId, $newPayTo, $newShipTo;
    
    //echo "New Pay:<br />\n<pre>";var_dump($newPayTo);echo "</pre><br />\n";
    //echo "New Ship:<br />\n<pre>";var_dump($newShipTo);echo "</pre><br />\n";

    echo "<h3>Address Change Request</h3>\n";
    echo "<form name ='update' action='addressChange.php' method='post' enctype='multipart/form-data'>\n";
    echo "  <table>\n";
    echo "    <thead>\n";
    echo "      <tr><th>Addresses</th><th>Pay To</th><th>Ship To</th></tr>\n";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    echo "      <tr>\n";
    echo "        <td>Company Name</td>\n";
    echo "        <td><input type='text' name='bill_companyname' id='bill_companyname' size='50' value=\"".$newPayTo['companyname']."\"></td>\n";
    echo "        <td><input type='text' name='ship_companyname' id='ship_companyname' size='50' value=\"".$newShipTo['companyname']."\"></td>\n";
    echo "      </tr>\n";
    echo "        <td>Street</td>\n";
    echo "        <td><input type='text' name='bill_street' id='bill_street' size='50' value='".$newPayTo['street']."'></td>\n";
    echo "        <td><input type='text' name='ship_street' id='ship_street' size='50' value='".$newShipTo['street']."'></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Street 2</td>\n";
    echo "        <td><input type='text' name='bill_street2' id='bill_street2' size='50' value='".$newPayTo['street2']."'></td>\n";
    echo "        <td><input type='text' name='ship_street2' id='ship_street2' size='50' value='".$newShipTo['street2']."'></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>City</td>\n";
    echo "        <td><input type='text' name='bill_city' id='bill_city' size='30' value='".$newPayTo['city']."'></td>\n";
    echo "        <td><input type='text' name='ship_city' id='ship_city' size='30' value='".$newShipTo['city']."'></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>State</td>\n";
    echo "        <td><input type='text' name='bill_state' id='bill_state'  size='2' value='".$newPayTo['state']."'></td>\n";
    echo "        <td><input type='text' name='ship_state' id='ship_state'  size='2' value='".$newShipTo['state']."'></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Zip</td>\n";
    echo "        <td><input type='text' name='bill_zip' id='bill_zip'  size='5' value='".$newPayTo['zip']."'></td>\n";
    echo "        <td><input type='text' name='ship_zip' id='ship_zip'  size='5' value='".$newShipTo['zip']."'></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Country</td>\n";
    echo "        <td><input type='text' name='bill_country' id='bill_country'  size='30' value='".$newPayTo['country']."'></td>\n";
    echo "        <td><input type='text' name='ship_country' id='ship_country'  size='30' value='".$newShipTo['country']."'></td>\n";
    echo "      </tr>\n";    
    echo "      <tr>\n";
    echo "        <td>Email</td>\n";
    echo "        <td><input type='text' name='bill_email' id='bill_email'  size='30' value='".$newPayTo['email']."'></td>\n";
    echo "        <td><input type='text' name='ship_email' id='ship_email'  size='30' value='".$newShipTo['email']."'></td>\n";
    echo "      </tr>\n";    
    echo "      <tr>\n";
    echo "        <td>Phone</td>\n";
    echo "        <td><input type='text' name='bill_phone' id='bill_phone'  size='30' value='".$newPayTo['phone']."'></td>\n";
    echo "        <td><input type='text' name='ship_phone' id='ship_phone'  size='30' value='".$newShipTo['phone']."'></td>\n";
    echo "      </tr>\n";    
    echo "      <tr>\n";
    echo "        <td>Fax</td>\n";
    echo "        <td><input type='text' name='bill_fax' id='bill_fax'  size='30' value='".$newPayTo['fax']."'></td>\n";
    echo "        <td><input type='text' name='ship_fax' id='ship_fax'  size='30' value='".$newShipTo['fax']."'></td>\n";
    echo "      </tr>\n";    
    echo "      <tr>\n";
    echo "        <td>Note</td>\n";
    echo "        <td><textarea name='bill_addressnote' id='bill_addressnote' cols='50' rows='5'>".$newPayTo['addressnote']."</textarea></td>\n";
    echo "        <td><textarea name='ship_addressnote' id='ship_addressnote' cols='50' rows='5'>".$newShipTo['addressnote']."</textarea></td>\n";
    echo "      </tr>\n";    
    echo "      <tr>\n";
    echo "        <td><input type='hidden' name='userId' value='".$userId."'</td>\n";
    echo "        <td><input type='submit' name='update' id='update' value='Save Request' /> <a href='dealerProfile.php?dealerId=".$dealer->userId."&pgimsg=".URLEncode("Update cancelled")."' class='cancel-button'>Cancel</a></td>\n";
    echo "      </tr>\n";
    echo "    </tbody>\n";
    echo "  </table><br />\n";
    
    echo "</form>\n";
}

function scrapeAddresses() {
    global $newPayTo, $newShipTo;
   
    $newPayTo['street'] = optional_param('bill_street', NULL, PARAM_TEXT);
    $newPayTo['street2'] = optional_param('bill_street2', NULL, PARAM_TEXT);
    $newPayTo['city'] = optional_param('bill_city', NULL, PARAM_TEXT);
    $newPayTo['state'] = optional_param('bill_state', NULL, PARAM_TEXT);
    $newPayTo['zip'] = optional_param('bill_zip', NULL, PARAM_TEXT);
    $newPayTo['country'] = optional_param('bill_country', NULL, PARAM_TEXT);
    $newPayTo['companyname'] = optional_param('bill_companyname', NULL, PARAM_RAW);
    $newPayTo['email'] = optional_param('bill_email', NULL, PARAM_TEXT);
    $newPayTo['phone'] = optional_param('bill_phone', NULL, PARAM_TEXT);
    $newPayTo['fax'] = optional_param('bill_fax', NULL, PARAM_TEXT);
    $newPayTo['addressnote'] = optional_param('bill_addressnote', NULL, PARAM_TEXT);

    $newShipTo['street'] = optional_param('ship_street', NULL, PARAM_TEXT);
    $newShipTo['street2'] = optional_param('ship_street2', NULL, PARAM_TEXT);
    $newShipTo['city'] = optional_param('ship_city', NULL, PARAM_TEXT);
    $newShipTo['state'] = optional_param('ship_state', NULL, PARAM_TEXT);
    $newShipTo['zip'] = optional_param('ship_zip', NULL, PARAM_TEXT);
    $newShipTo['country'] = optional_param('ship_country', NULL, PARAM_TEXT);
    $newShipTo['companyname'] = optional_param('ship_companyname', NULL, PARAM_RAW);
    $newShipTo['email'] = optional_param('ship_email', NULL, PARAM_TEXT);
    $newShipTo['phone'] = optional_param('ship_phone', NULL, PARAM_TEXT);
    $newShipTo['fax'] = optional_param('ship_fax', NULL, PARAM_TEXT);
    $newShipTo['addressnote'] = optional_param('ship_addressnote', NULL, PARAM_TEXT);
}

    

function updateAddresses($dealerId) {
    global $page, $newPayTo, $newShipTo;

    $process = null;
    $success = false;
    
    $requestTime = time();

    if ($newPayTo) {
        $success = true;
        $sql = null;
        $params = array();
        $params['street'] = $newPayTo['street'];
        $params['street2'] = $newPayTo['street2'];
        $params['city'] = $newPayTo['city'];
        $params['state'] = $newPayTo['state'];
        $params['zip'] = $newPayTo['zip'];
        $params['country'] = $newPayTo['country'];
        $params['companyname'] = $newShipTo['companyname'];
        $params['email'] = $newPayTo['email'];
        $params['phone'] = $newPayTo['phone'];
        $params['fax'] = $newPayTo['fax'];
        $params['addressnote'] = $newPayTo['addressnote'];
        if ($newPayTo['addresstypeid'] == ADDRESS_TYPE_REQUEST_PAY) {
            // Update existing
            $sql = "UPDATE usercontactinfo
                SET street=:street, street2=:street2, city=:city, state=:state, zip=:zip, country=:country
                    , companyname=:companyname, email=:email, phone=:phone, fax=:fax
                    , addressnote=:addressnote
                    , modifiedby=:modifiedby, modifydate=:modifydate
                WHERE usercontactid=:usercontactid";
            $params['modifiedby'] = $page->user->username;
            $params['modifydate'] = $requestTime;
            $params['usercontactid'] = $newPayTo['usercontactid'];
        } else {
            $sql = "INSERT INTO usercontactinfo (addresstypeid, userid
                        , street, street2, city, state, zip, country
                        , companyname, email, phone, fax
                        , addressnote
                        , createdby, createdate, modifiedby, modifydate)
                        VALUES (:addresstypeid, :userid
                            , :street, :street2, :city, :state, :zip, :country
                            , :companyname, :email, :phone, :fax
                            , :addressnote
                            , :createdby, :createdate, :modifiedby, :modifydate)";
            $params['addresstypeid'] = ADDRESS_TYPE_REQUEST_PAY;
            $params['userid'] = $dealerId;
            $params['createdby'] = $page->user->username;
            $params['createdate'] = $requestTime;
            $params['modifiedby'] = $page->user->username;
            $params['modifydate'] = $requestTime;
        }
        //echo "SQL:<br />\n<pre>".$sql."</pre><br />\n";
        //echo "Params:<br />\n<pre>";var_dump($params); echo "</pre><br />\n";
        $page->queries->AddQuery($sql, $params);
        unset($sql);
        unset($params);
    }
    
    if ($newShipTo) {
        $success = true;
        $sql = null;
        $params = array();
        $params['street'] = $newShipTo['street'];
        $params['street2'] = $newShipTo['street2'];
        $params['city'] = $newShipTo['city'];
        $params['state'] = $newShipTo['state'];
        $params['zip'] = $newShipTo['zip'];
        $params['country'] = $newShipTo['country'];
        $params['companyname'] = $newShipTo['companyname'];
        $params['email'] = $newShipTo['email'];
        $params['phone'] = $newShipTo['phone'];
        $params['fax'] = $newShipTo['fax'];
        $params['addressnote'] = $newShipTo['addressnote'];
        if ($newShipTo['addresstypeid'] == ADDRESS_TYPE_REQUEST_SHIP) {
            // Update existing
            $sql = "UPDATE usercontactinfo
                SET street=:street, street2=:street2, city=:city, state=:state, zip=:zip, country=:country
                    , companyname=:companyname, email=:email, phone=:phone, fax=:fax
                    , addressnote=:addressnote
                    , modifiedby=:modifiedby, modifydate=:modifydate
                WHERE usercontactid=:usercontactid";
            $params['modifiedby'] = $page->user->username;
            $params['modifydate'] = $requestTime;
            $params['usercontactid'] = $newShipTo['usercontactid'];
        } else {
            $sql = "INSERT INTO usercontactinfo (addresstypeid, userid
                        , street, street2, city, state, zip, country
                        , companyname, email, phone, fax
                        , addressnote
                        , createdby, createdate, modifiedby, modifydate)
                        VALUES (:addresstypeid, :userid
                            , :street, :street2, :city, :state, :zip, :country
                            , :companyname, :email, :phone, :fax
                            , :addressnote
                            , :createdby, :createdate, :modifiedby, :modifydate)";
            $params['addresstypeid'] = ADDRESS_TYPE_REQUEST_SHIP;
            $params['userid'] = $dealerId;
            $params['createdby'] = $page->user->username;
            $params['createdate'] = $requestTime;
            $params['modifiedby'] = $page->user->username;
            $params['modifydate'] = $requestTime;
        }
        //echo "SQL:<br />\n<pre>".$sql."</pre><br />\n";
        //echo "Params:<br />\n<pre>";var_dump($params); echo "</pre><br />\n";
        $page->queries->AddQuery($sql, $params);
        unset($sql);
        unset($params);
    }

    if ($page->queries->HasQueries()) {
        if ($page->queries->ProcessQueries()) {
            $page->messages->addSuccessMsg('You have requested an address update');
            $msgSubject = "Address request from ".$page->user->username." (".$page->user->userId.")";
            $requestingURL = "<a href='dealerProfile.php?dealerId=".$dealerId."' target=_blank>Requesting Profile</a>";
            $msgBody = $page->user->username." (".$page->user->userId.") is requesting an address change.\n".$requestingURL;
            $msgId = $page->iMessage->insertSystemMessage($page, ADMINUSERID, ADMINUSERNAME, $msgSubject, $msgBody, EMAIL);
            if ($msgId) {
                $page->messages->addInfoMsg('Admin notified of address request.');
            } else {
                $page->messages->addErrorMsg('Error notifying Admin of address request.');
            }
        } else {
            $success = false;
            $page->messages->addErrorMsg('Error requesting an address update');
        }
    } else {
        $page->messages->addInfoMsg("No updates requested");
    }

    return $success;
}


?>