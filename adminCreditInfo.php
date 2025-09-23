<?php
require_once('templateAdmin.class.php');

$page = new templateAdmin(LOGIN, SHOWMSG);

$action = optional_param('action', NULL, PARAM_TEXT);
$dealerId = optional_param('dealerid', NULL, PARAM_TEXT);

if (empty($dealerId)) {
    $page->messages->addErrorMsg("Dealer ID is required.");
} else {
    if ($creditInfo = getCreditInfo($dealerId)) {
        if ($action == 'delete') {
            $sql = "DELETE FROM billinginfo WHERE userid=".$dealerId;
            if ($page->db->sql_execute($sql)) {
                header('Location:adminCreditInfoList.php?pgsmsg='.URLEncode("Successfully deleted billing info for dealer ".$creditInfo['username']));
                exit();
            } else {
                $page->messages->addErrorMsg("Error deleteing billing info for dealer ".$creditInfo['username']);
            }
        }
    }
}

echo $page->header('Dealer Billing Update');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $creditInfo;

    if ($creditInfo) {
        echo "<h3>Billing Info: ".$creditInfo['username']."(".$creditInfo['userid'].")</h3>\n";
        echo "  <table>\n";
        echo "    <thead>\n";
        echo "      <tr><th colspan=2>Credit Card Info</th></tr>\n";
        echo "    </thead>\n";
        echo "    <tbody>\n";
        echo "      <tr>\n";
        echo "        <td>Cardholder Name</td>\n";
        echo "        <td>".$creditInfo['cardholder']."</td>\n";
        echo "      </tr>\n";
        echo "      <tr>\n";
        echo "        <td>Card Type</td>\n";
        echo "        <td>".$creditInfo['cardtype']."</td>\n";
        echo "      </tr>\n";
        echo "      <tr>\n";
        echo "        <td>Card Number</td>\n";
        echo "        <td>".$creditInfo['cardnumber']."</td>\n";
        echo "      </tr>\n";
        echo "      <tr>\n";
        echo "        <td>Expiration Date</td>\n";
        echo "        <td>".$creditInfo['expiremonth']." / ".$creditInfo['expireyear']."</td>\n";
        echo "      </tr>\n";
        echo "      <tr>\n";
        echo "        <td>CVV</td>\n";
        echo "        <td>".$creditInfo['cvv']."</td>\n";
        echo "      </tr>\n";
        echo "      <tr>\n";
        echo "        <td>Street</td>\n";
        echo "        <td>".$creditInfo['street']."</td>\n";
        echo "      </tr>\n";
        echo "      <tr>\n";
        echo "        <td>Street 2</td>\n";
        echo "        <td>".$creditInfo['street2']."</td>\n";
        echo "      </tr>\n";
        echo "      <tr>\n";
        echo "        <td>City</td>\n";
        echo "        <td>".$creditInfo['city']."</td>\n";
        echo "      </tr>\n";
        echo "      <tr>\n";
        echo "        <td>State</td>\n";
        echo "        <td>".$creditInfo['state']."</td>\n";
        echo "      </tr>\n";
        echo "      <tr>\n";
        echo "        <td>Zip</td>\n";
        echo "        <td>".$creditInfo['zip']."</td>\n";
        echo "      </tr>\n";
        echo "      <tr>\n";
        echo "        <td>Country</td>\n";
        echo "        <td>".$creditInfo['country']."</td>\n";
        echo "      </tr>\n";
        echo "      <tr><td colspan=2><a href='adminCreditInfo.php?dealerid=".$creditInfo['userid']."&action=delete' class='cancel-button' onClick='return confirm(\"Are you sure you want to delete this billing info?\");'>Delete</a> <a href='adminCreditInfoList.php' class='cancel-button'>Cancel</a></td></tr>\n";
        echo "    </tbody>\n";
        echo "  </table><br />\n";
    }
}

function expirationDate() {
    echo "<select id='expiremonth' name='expiremonth'>";
    echo "<option value='1'>01</option>";
    echo "<option value='2'>02</option>";
    echo "<option value='3'>03</option>";
    echo "<option value='4'>04</option>";
    echo "<option value='5'>05</option>";
    echo "<option value='6'>06</option>";
    echo "<option value='7'>07</option>";
    echo "<option value='8'>08</option>";
    echo "<option value='9'>09</option>";
    echo "<option value='10'>10</option>";
    echo "<option value='11'>11</option>";
    echo "<option value='12'>12</option>";
    echo "</select>";
    echo " / ";
    echo "<select id='expireyear' name='expireyear'>";
    echo "<option value='2023'>2023</option>";
    echo "<option value='2024'>2024</option>";
    echo "<option value='2025'>2025</option>";
    echo "<option value='2026'>2026</option>";
    echo "<option value='2027'>2027</option>";
    echo "<option value='2028'>2028</option>";
    echo "<option value='2029'>2029</option>";
    echo "<option value='2030'>2030</option>";
    echo "</select>";
}

function saveCreditInfo() {
    global $page, $creditInfo;

    echo "Saving<br />\n";

    $success = true;

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
        $page->messages->addSuccessMsg("Updated billing info.");
    } else {
        $page->messages->addErrorMsg("Updated billing info.");
        $success = false;
    }

    return $success;
}

function validateCreditInfo() {
    global $page, $creditInfo;

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
    }
    if (empty($creditInfo['expiremonth'])) {
        $page->messages->addErrorMsg("Expiration Month is required.");
        $isValid = false;
    }
    if (empty($creditInfo['expireyear'])) {
        $page->messages->addErrorMsg("Expiration Year is required.");
        $isValid = false;
    }
    if (empty($creditInfo['cvv'])) {
        $page->messages->addErrorMsg("CVV is required.");
        $isValid = false;
    }
    if (empty($creditInfo['street'])) {
        $page->messages->addErrorMsg("Street is required.");
        $isValid = false;
    }
    //if (($creditInfo['street2'])) {
    //    $page->messages->addErrorMsg("Cardholder is required.");
    //    $isValid = false;
    //}
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
    echo "Scraping<br />\n";
    $creditInfo['cardholder'] = optional_param('cardholder', NULL, PARAM_TEXT);
    $creditInfo['cardtype'] = optional_param('cardtype', NULL, PARAM_TEXT);
    $creditInfo['cardnumber'] = optional_param('cardnumber', NULL, PARAM_INT);
    $creditInfo['expiremonth'] = optional_param('expiremonth', NULL, PARAM_INT);
    $creditInfo['expireyear'] = optional_param('expireyear', NULL, PARAM_INT);
    $creditInfo['cvv'] = optional_param('cvv', NULL, PARAM_INT);
    $creditInfo['street'] = optional_param('street', NULL, PARAM_TEXT);
    $creditInfo['street2'] = optional_param('street2', NULL, PARAM_TEXT);
    $creditInfo['city'] = optional_param('city', NULL, PARAM_TEXT);
    $creditInfo['state'] = optional_param('state', NULL, PARAM_TEXT);
    $creditInfo['zip'] = optional_param('zip', NULL, PARAM_INT);
    $creditInfo['country'] = optional_param('country', NULL, PARAM_TEXT);

    return $creditInfo;
}

function getCreditInfo($dealerId) {
    global $page;

    $creditInfo = null;

    $sql = "SELECT bi.billinginfoid, bi.userid, bi.status, bi.cardholder, bi.cardtype, bi.expiremonth, bi.expireyear
                , convert_from(decrypt(bi.cardnumber::bytea,bi.createdby::bytea,'aes'),'SQL_ASCII') as cardnumber
                , convert_from(decrypt(bi.cvv::bytea,bi.createdby::bytea,'aes'),'SQL_ASCII') as cvv
                , bi.street, bi.street2, bi.city, bi.state, bi.zip, bi.country
                , bi.createdby, bi.createdate, bi.modifiedby, bi.modifydate
                , u.username
            FROM billinginfo bi
            JOIN users u on u.userid=bi.userid
            WHERE bi.userid=".$dealerId;
    if ($results = $page->db->sql_query($sql)) {
        $creditInfo = reset($results);
    } else {
        $page->messages->addErrorMsg("unable to load dealer's billing information.");
    }

    return $creditInfo;
}


?>