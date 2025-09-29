<?php
require_once('templateAdmin.class.php');

$page = new templateAdmin(LOGIN, SHOWMSG);
$page->requireJS('scripts/validateCats.js');

if ($page->user->hasUserRight("ADMIN") == false) {
    header('location:home.php');
    exit();
}

$userName       = optional_param('userName', NULL, PARAM_TEXT);
$created        = optional_param('created', NULL, PARAM_TEXT);
$answer         = optional_param('answer', NULL, PARAM_TEXT);
$hint           = optional_param('hint', NULL, PARAM_TEXT);
$firstName      = optional_param('firstName', NULL, PARAM_TEXT);
$lastName       = optional_param('lastName', NULL, PARAM_TEXT);
$companyName    = optional_param('companyName', NULL, PARAM_TEXT);
$street         = optional_param('street', NULL, PARAM_TEXT);
$street2        = optional_param('street2', NULL, PARAM_TEXT);
$city           = optional_param('city', NULL, PARAM_TEXT);
$state          = optional_param('state', NULL, PARAM_TEXT);
$zip            = optional_param('zip', NULL, PARAM_TEXT);
$country        = optional_param('country', NULL, PARAM_TEXT);
$phone          = optional_param('phone', NULL, PARAM_TEXT);
$fax            = optional_param('fax', NULL, PARAM_TEXT);
$email          = optional_param('email', NULL, PARAM_TEXT);
$forumName      = optional_param('forumName', NULL, PARAM_TEXT);
$listingFee     = optional_param('listingFee', NULL, PARAM_TEXT);
$paypalId       = optional_param('paypalId', NULL, PARAM_TEXT);
$ebayId         = optional_param('ebayId', NULL, PARAM_TEXT);
$webURL         = optional_param('webURL', NULL, PARAM_TEXT);
$newUser        = optional_param('newUser', NULL, PARAM_TEXT);
$userPass       = optional_param('userPass', NULL, PARAM_TEXT);


if (isset($newUser)) {
    createUser();
}

echo $page->header('New User');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page;

    echo "<form name ='update' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "  <table>\n";
    echo "    <thead>\n";
    echo "      <tr>\n";
    echo "        <th colspan='2'>Create New User</th>\n";
    echo "      </tr>\n";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    echo "      <tr>\n";
    echo "        <td>User Name</td>\n";
    echo "        <td><input type='text' name='userName' id='userName' value=''></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Hint</td>\n";
    echo "        <td><input type='text' name='hint' id='hint' value=''></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Answer</td>\n";
    echo "        <td><input type='text' name='answer' id='answer' value=''></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>User Pass</td>\n";
    echo "        <td><input type='text' name='userPass' id='userPass' value=''></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "      <tr>\n";
    echo "        <td>First Name</td>\n";
    echo "        <td><input type='text' name='firstName' id='firstName' value=''></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Last Name</td>\n";
    echo "        <td><input type='text' name='lastName' id='lastName' value=''></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Company Name</td>\n";
    echo "        <td><input type='text' name='companyName' id='companyName' value=''></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Address</td>\n";
    echo "        <td><input type='text' name='street' id='street' value=''></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Address 2</td>\n";
    echo "        <td><input type='text' name='street2' id='street2' value=''></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>City</td>\n";
    echo "        <td><input type='text' name='city' id='city' value=''></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>State</td>\n";
    echo "        <td><input type='text' name='state' id='state' value=''></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Zip</td>\n";
    echo "        <td><input type='text' name='zip' id='zip' value=''></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Country</td>\n";
    echo "        <td><input type='text' name='country' id='country' value=''></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Phone</td>\n";
    echo "        <td><input type='text' name='phone' id='phone' value=''></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Fax</td>\n";
    echo "        <td><input type='text' name='fax' id='fax' value=''></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Email</td>\n";
    echo "        <td><input type='text' name='email' id='email' value=''></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Forum Name</td>\n";
    echo "        <td><input type='text' name='forumName' id='forumName' value=''></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Listing Fee</td>\n";
    echo "        <td><input type='text' name='listingFee' id='listingFee' value=''></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>PayPal ID</td>\n";
    echo "        <td><input type='text' name='paypalId' id='paypalId' value=''></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Ebay ID</td>\n";
    echo "        <td><input type='text' name='ebayId' id='ebayId' value=''></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Web URL</td>\n";
    echo "        <td><input type='text' name='webURL' id='webURL' value=''></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td><input type='submit' name='newUser' id='newUser' value='Add New User'></td>\n";
    echo "      </tr>\n";
    echo "    </tbody>\n";
    echo "  </table>\n";
    echo "</form>\n";
}


function createUser() {
    global $page, $UTILITY;
    global $userPass, $userName, $firstName, $lastName, $companyName, $street, $street2, $city, $state,
           $zip, $country, $phone, $fax, $email, $forumName, $listingFee, $paypalId, $ebayId, $webURL;

    $success = "";

    $userId = $UTILITY->nextval('users_user_id_seq');
    //TOOO Password stuff
    $sql = "
        INSERT INTO users( userId,  userName,  answer,  hint,  createdBy  userPass)
                   VALUES(:userId, :userName, :answer, :hint, :createdBy, crypt(:userPass, gen_salt('bf')),)
    ";

    $params = array();
    $params['userId']       = $userId;
    $params['userName']     = $userName;
    $params['userPass']     = $userPass;
    $params['answer']       = $answer;
    $params['hint']         = $hint;
    $params['createdBy']    = $_SESSION['userId'];

    $userId = $page->db->sql_execute_params($sql, $params);
    unset($sql);
    unset($params);

    $sql = "
        INSERT INTO usercontactinfo ( userId, companyName,  street,  street2,  city,  state,  zip, country,  phone,  fax,  email,  createdBy)
                             VALUES (:userId, :companyName, :street, :street2, :city, :state, :zip, country, :phone, :fax, :email, :createdBy)
    ";

    $params = array();
    $params['companyName']  = $companyName;
    $params['street']       = $street;
    $params['street2']      = $street2;
    $params['city']         = $city;
    $params['state']        = $state;
    $params['zip']          = $zip;
    $params['country']      = $country;
    $params['phone']        = $phone;
    $params['fax']          = $fax;
    $params['email']        = $email;
    $params['userId']       = $userId;
    $params['createdBy']    = $_SESSION['userId'];

    $page->queries->AddQuery($sql, $params);
    unset($sql);
    unset($params);

    $sql = "
        INSERT INTO userinfo ( userId, firstName,  lastName,  forumName,  listingFee,  paypalId,  ebayId,  webURL,  createdBy)
                      vALUES (:userId,:firstName, :lastName, :forumName, :listingFee, :paypalId, :ebayId, :webURL, :createdBy)
    ";

    $params = array();
    $params['firstName']    = $firstName;
    $params['lastName']     = $lastName;
    $params['forumName']    = $forumName;
    $params['listingFee']   = $listingFee;
    $params['paypalId']     = $paypalId;
    $params['ebayId']       = $ebayId;
    $params['webURL']       = $webURL;
    $params['userId']       = $userId;
    $params['createdBy']    = $_SESSION['userId'];

    $page->queries->AddQuery($sql, $params);

    if ($page->queries->HasQueries()) {
        $process = $page->queries->ProcessQueries();
    }

    if ($process == TRUE) {
        $eft = new electronicFundsTransfer();
        $initialBalanceDate = strtotime(date('m/d/Y')) - 1;
        $eft->makeInitialBalance($userId, $userName, $initialBalanceDate);
    }
    if ($process == TRUE) {
        $success = TRUE;
        $page->messages->addSuccessMsg('You have created the user - '.$userName);
    } else {
        $success = FALSE;
        $page->messages->addErrorMsg('Error?');
    }

    unset($sql);
    unset($params);

    return $success;

}


?>