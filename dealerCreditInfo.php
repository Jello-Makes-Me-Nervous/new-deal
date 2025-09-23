<?php
require_once('template.class.php');

$page = new template(LOGIN, SHOWMSG, REDIRECTSAFE);
$page->requireJS("/scripts/creditcard.js");

$update = optional_param('update', NULL, PARAM_TEXT);
$delete = optional_param('delete', NULL, PARAM_TEXT);

$creditInfo = getCreditInfo();

$firstExpirationYear = 0 + date('Y');
$numExpirationYears = 9;
$lastExpirationYear = $firstExpirationYear + $numExpirationYears;

if ($update == 'Save') {
    scrapeCreditInfo();
    if (validateCreditInfo()) {
        if (saveCreditInfo()) {
            header('Location:dealerProfile.php?pgsmsg='.URLEncode("Successfully updated membership billing info."));
            exit();
        }
    }
} else {
    if ($delete == 'Delete') {
        if (deleteCreditInfo()) {
            header('Location:dealerProfile.php?pgsmsg='.URLEncode("Successfully deleted membership billing info update."));
            exit();
        }
    }
}

echo $page->header('Credit card for membership fees');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $creditInfo;

    echo "<h3 style='text-align:center;'>Credit Card information provided is for Dealernet vendor membership fees only.<br>All payments for purchases can be made directly to the seller.</h3>\n";
    echo "<form name ='update' action='dealerCreditInfo.php' method='post' enctype='multipart/form-data' onsubmit='javascript: return validateCC();'>\n";
    echo "  <table>\n";
    echo "    <thead>\n";
    echo "      <tr><th colspan=2>Credit Card Info</th></tr>\n";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    echo "      <tr>\n";
    echo "        <td>Cardholder Name</td>\n";
    echo "        <td><input type='text' name='cardholder' id='cardholder' size='50' value=\"".$creditInfo['cardholder']."\"></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Card Type</td>\n";
    echo "        <td>";
    cardType($creditInfo['cardtype']);
    echo "        </td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Card Number</td>\n";
    echo "        <td><input type='text' name='cardnumber' id='cardnumber' size='50' value=\"".$creditInfo['cardnumber']."\"></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Expiration Date</td>\n";
    echo "        <td>";
    expirationDate($creditInfo['expiremonth'], $creditInfo['expireyear']);
    echo "        </td>\n";
    echo "        </td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>CVV</td>\n";
    echo "        <td><input type='text' name='cvv' id='cvv' size='50' value=\"".$creditInfo['cvv']."\"></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Street</td>\n";
    echo "        <td><input type='text' name='street' id='street' size='50' value='".$creditInfo['street']."'></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Street 2</td>\n";
    echo "        <td><input type='text' name='street2' id='street2' size='50' value='".$creditInfo['street2']."'></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>City</td>\n";
    echo "        <td><input type='text' name='city' id='city' size='30' value='".$creditInfo['city']."'></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>State</td>\n";
    echo "        <td><input type='text' name='state' id='state'  size='2' value='".$creditInfo['state']."'></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Zip</td>\n";
    echo "        <td><input type='text' name='zip' id='zip'  size='5' value='".$creditInfo['zip']."'></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Country</td>\n";
    echo "        <td><input type='text' name='country' id='country'  size='30' value='".$creditInfo['country']."'></td>\n";
    echo "      </tr>\n";
    echo "      <tr><td colspan=2>";
    echo "<input type='submit' name='update' id='update' value='Save' />";
    echo " <a href='dealerProfile.php?dealerId=".$page->user->userId."&pgimsg=".URLEncode("Membership billing info update cancelled")."' class='cancel-button'>Cancel</a>";
    if ($creditInfo['billinginfoid'] !=  -1) {
        echo "<input type='submit' name='delete' id='delete' onClick='return confirm(\"Are you sure you want to delete your billing info?\");' value='Delete' />";
    }
    echo "</td></tr>\n";
    echo "    </tbody>\n";
    echo "  </table><br />\n";

    echo "</form>\n";
}

function cardType($cardType) {
    global $page;

    echo "<select id='cardtype' name='cardtype'>";
    echo "<option ".$page->utility->isChecked($cardType, "Visa", "selected")." value='Visa'>Visa</option>";
    echo "<option ".$page->utility->isChecked($cardType, "Master Card", "selected")." value='Master Card'>Master Card</option>";
    echo "<option ".$page->utility->isChecked($cardType, "American Express", "selected")." value='American Express'>American Express</option>";
    echo "<option ".$page->utility->isChecked($cardType, "Discover", "selected")." value='Discover'>Discover</option>";
    echo "</select>";
}

function expirationDate($expireMonth, $expireYear) {
    global $page, $firstExpirationYear, $lastExpirationYear;

    echo "<select id='expiremonth' name='expiremonth'>";
    for ($expirationMonth = 1; $expirationMonth <= 12; $expirationMonth++) {
        echo "<option ".$page->utility->isChecked($expireMonth, $expirationMonth, "selected")." value='".$expirationMonth."'>".str_pad($expirationMonth, 2, "0", STR_PAD_LEFT)."</option>";
    }
    echo "</select>";
    echo " / ";
    echo "<select id='expireyear' name='expireyear'>";
    for ($expirationYear = $firstExpirationYear; $expirationYear <= $lastExpirationYear; $expirationYear++) {
        echo "<option ".$page->utility->isChecked($expireYear, $expirationYear, "selected")." value='".$expirationYear."'>".$expirationYear."</option>";
    }
    echo "</select>";
}

function deleteCreditInfo() {
    global $page, $creditInfo, $CFG;

    $success = true;

    if ($creditInfo['billinginfoid'] == -1) {
        $page->messages->addErrorMsg("Error no billing info updates to delete.");
        $success = false;
    } else {
        $sql = "DELETE FROM billinginfo WHERE billinginfoid=".$creditInfo['billinginfoid'];

        if (! $page->db->sql_execute($sql)) {
            $page->messages->addErrorMsg("Error deleting billing info update.");
            $success = false;
        }
    }

    if ($success) {
        $msgSubject = "Billing Info Deleted";
        $msgBody = $page->user->username." (".$page->user->userId.") has deleted their new billing information.\n";
        $msgId = $page->iMessage->insertSystemMessage($page, $CFG->BILLING_USERID, $CFG->BILLING_USERNAME, $msgSubject, $msgBody, EMAIL);
    }

    return $success;
}

function saveCreditInfo() {
    global $page, $creditInfo, $CFG;

    $success = true;

    if ($creditInfo['billinginfoid'] == -1) {
        $sql = "INSERT INTO billinginfo (userid, status, cardholder, cardtype, expiremonth, expireyear
                    , cardnumber, cvv
                    , street, street2, city, state, zip, country, createdby, modifiedby)
                    VALUES (:userid, :status, :cardholder, :cardtype, :expiremonth, :expireyear
                        , encrypt(:cardnumber,'".$page->user->username."','aes'), encrypt(:cvv,'".$page->user->username."','aes')
                        , :street, :street2, :city, :state, :zip, :country, '".$page->user->username."', '".$page->user->username."')";
        $params = array();
        $params['userid'] = $creditInfo['userid'];
        $params['status'] = $creditInfo['status'];
        $params['cardholder'] = $creditInfo['cardholder'];
        $params['cardtype'] = $creditInfo['cardtype'];
        $params['expiremonth'] = $creditInfo['expiremonth'];
        $params['expireyear'] = $creditInfo['expireyear'];
        $params['cardnumber'] = $creditInfo['cardnumber'];
        $params['cvv'] = $creditInfo['cvv'];
        $params['street'] = $creditInfo['street'];
        $params['street2'] = $creditInfo['street2'];
        $params['city'] = $creditInfo['city'];
        $params['state'] = $creditInfo['state'];
        $params['zip'] = $creditInfo['zip'];
        $params['country'] = $creditInfo['country'];

        if ($page->db->sql_execute_params($sql, $params)) {
            $page->messages->addSuccessMsg("Added billing info.");
        } else {
            $page->messages->addErrorMsg("Error adding billing info.");
            $success = false;
        }
    } else {
        $sql = "UPDATE billinginfo SET status = 'Updated'
                    , cardholder = :cardholder
                    , cardtype = :cardtype
                    , expiremonth = :expiremonth
                    , expireyear = :expireyear
                    , cardnumber = encrypt(:cardnumber,'".$page->user->username."','aes')
                    , cvv = encrypt(:cvv,'".$page->user->username."','aes')
                    , street = :street
                    , street2 = :street2
                    , city = :city
                    , state = :state
                    , zip = :zip
                    , country = :country
                    , modifiedby = '".$page->user->username."'
                    , modifydate = nowtoint()
                WHERE billinginfoid = ".$creditInfo['billinginfoid'];
        $params = array();
        $params['cardholder'] = $creditInfo['cardholder'];
        $params['cardtype'] = $creditInfo['cardtype'];
        $params['expiremonth'] = $creditInfo['expiremonth'];
        $params['expireyear'] = $creditInfo['expireyear'];
        $params['cardnumber'] = $creditInfo['cardnumber'];
        $params['cvv'] = $creditInfo['cvv'];
        $params['street'] = $creditInfo['street'];
        $params['street2'] = $creditInfo['street2'];
        $params['city'] = $creditInfo['city'];
        $params['state'] = $creditInfo['state'];
        $params['zip'] = $creditInfo['zip'];
        $params['country'] = $creditInfo['country'];

        if (! $page->db->sql_execute_params($sql, $params)) {
            $page->messages->addErrorMsg("Error updating billing info.");
            $success = false;
        }
    }

    if ($success) {
        $msgSubject = "Billing Info Updated";
        $billingInfoURL = "<a href='adminCreditInfo.php?dealerid=".$page->user->userId."' target=_blank>New Billing Info</a>";
        $msgBody = $page->user->username." (".$page->user->userId.") has supplied new billing information.\n".$billingInfoURL;
        $msgId = $page->iMessage->insertMessage($page, $CFG->BILLING_USERID, $CFG->BILLING_USERNAME, $msgSubject, $msgBody, EMAIL);
    }

    return $success;
}

function validateCreditInfo() {
    global $page, $creditInfo, $firstExpirationYear, $lastExpirationYear;

    $isValid = true;

    if (empty($creditInfo['cardholder'])) {
        $page->messages->addErrorMsg("Cardholder is required.");
        $isValid = false;
    }
    if (empty($creditInfo['cardtype'])) {
        $page->messages->addErrorMsg("Card Type is required.");
        $isValid = false;
    }
    if (empty($creditInfo['cardnumber'])) {
        $page->messages->addErrorMsg("Card Number is required.");
        $isValid = false;
    } else {
        if (! preg_match("/^\\d+$/", $creditInfo['cardnumber'])) {
            $page->messages->addErrorMsg("Card Number must be all numeric digits.");
            $isValid = false;
        }
    }
    if (empty($creditInfo['expiremonth'])) {
        $page->messages->addErrorMsg("Expiration Month is required.");
        $isValid = false;
    } else {
        if (is_numeric($creditInfo['expiremonth'])) {
            if (($creditInfo['expiremonth'] < 1) || ($creditInfo['expiremonth'] > 12)) {
                $page->messages->addErrorMsg("Expiration Month must be a number between 1 and 12.");
                $isValid = false;
            }
        } else {
            $page->messages->addErrorMsg("Expiration Month must be a number between 1 and 12.");
            $isValid = false;
        }
    }
    if (empty($creditInfo['expireyear'])) {
        $page->messages->addErrorMsg("Expiration Year is required.");
        $isValid = false;
    } else {
        if (is_numeric($creditInfo['expireyear'])) {
            if (($creditInfo['expireyear'] < $firstExpirationYear) || ($creditInfo['expireyear'] > $lastExpirationYear)) {
                $page->messages->addErrorMsg("Expiration year must be a number between ".$firstExpirationYear." and ".$lastExpirationYear.".");
                $isValid = false;
            }
        } else {
            $page->messages->addErrorMsg("Expiration year must be a number between ".$firstExpirationYear." and ".$lastExpirationYear.".");
            $isValid = false;
        }
    }
    if (empty($creditInfo['cvv'])) {
        $page->messages->addErrorMsg("CVV is required.");
        $isValid = false;
    }
    if (empty($creditInfo['street'])) {
        $page->messages->addErrorMsg("Street is required.");
        $isValid = false;
    }
    if (empty($creditInfo['city'])) {
        $page->messages->addErrorMsg("City is required.");
        $isValid = false;
    }
    if (empty($creditInfo['state'])) {
        $page->messages->addErrorMsg("State is required.");
        $isValid = false;
    }
    if (empty($creditInfo['zip'])) {
        $page->messages->addErrorMsg("Zip is required.");
        $isValid = false;
    }
    if (empty($creditInfo['country'])) {
        $page->messages->addErrorMsg("Country is required.");
        $isValid = false;
    }

    return $isValid;
}

function scrapeCreditInfo() {
    global $creditInfo;

    $creditInfo['cardholder'] = optional_param('cardholder', NULL, PARAM_TEXT);
    $creditInfo['cardtype'] = optional_param('cardtype', NULL, PARAM_TEXT);
    $creditInfo['cardnumber'] = str_replace(" ", "", optional_param('cardnumber', NULL, PARAM_TEXT));
    $creditInfo['expiremonth'] = optional_param('expiremonth', NULL, PARAM_TEXT);
    $creditInfo['expireyear'] = optional_param('expireyear', NULL, PARAM_TEXT);
    $creditInfo['cvv'] = optional_param('cvv', NULL, PARAM_TEXT);
    $creditInfo['street'] = optional_param('street', NULL, PARAM_TEXT);
    $creditInfo['street2'] = optional_param('street2', NULL, PARAM_TEXT);
    $creditInfo['city'] = optional_param('city', NULL, PARAM_TEXT);
    $creditInfo['state'] = optional_param('state', NULL, PARAM_TEXT);
    $creditInfo['zip'] = optional_param('zip', NULL, PARAM_TEXT);
    $creditInfo['country'] = optional_param('country', NULL, PARAM_TEXT);

    return $creditInfo;
}

function getCreditInfo() {
    global $page;

    $creditInfo = null;

    $sql = "SELECT bi.billinginfoid, bi.userid, bi.status, bi.cardholder, bi.cardtype, bi.expiremonth, bi.expireyear
                , convert_from(decrypt(bi.cardnumber::bytea,u.username::bytea,'aes'),'SQL_ASCII') as cardnumber
                , convert_from(decrypt(bi.cvv::bytea,u.username::bytea,'aes'),'SQL_ASCII') as cvv
                , bi.street, bi.street2, bi.city, bi.state, bi.zip, bi.country
                , bi.createdby, bi.createdate, bi.modifiedby, bi.modifydate
            FROM billinginfo bi
            JOIN users u ON u.userid=bi.userid
            WHERE bi.userid=".$page->user->userId;
    if ($results = $page->db->sql_query($sql)) {
        $creditInfo = reset($results);
    } else {
        $creditInfo = array();
        $creditInfo['billinginfoid'] = -1;
        $creditInfo['userid'] = $page->user->userId;
        $creditInfo['status'] = 'New';
        $creditInfo['cardholder'] = null;
        $creditInfo['cardtype'] = null;
        $creditInfo['cardnumber'] = null;
        $creditInfo['expiremonth'] = null;
        $creditInfo['expireyear'] = null;
        $creditInfo['cvv'] = null;
        $creditInfo['street'] = null;
        $creditInfo['street2'] = null;
        $creditInfo['city'] = null;
        $creditInfo['state'] = null;
        $creditInfo['zip'] = null;
        $creditInfo['country'] = "USA";
    }

    return $creditInfo;
}
?>