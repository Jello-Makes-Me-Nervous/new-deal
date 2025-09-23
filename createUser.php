<?php
require_once('template.class.php');

$page = new template(LOGIN, SHOWMSG);
$page->requireJS('scripts/password.js');
$page->requireJS('scripts/validateUser.js');
$iMessaging = new internalMessage();

$userCreate     = optional_param('userCreate', NULL, PARAM_TEXT);
$userName       = optional_param('userName', NULL, PARAM_TEXT);
$userPass       = optional_param('userPass', NULL, PARAM_TEXT);
$hint           = optional_param('hint', NULL, PARAM_TEXT);
$answer         = optional_param('answer', NULL, PARAM_TEXT);
$firstName      = optional_param('firstName', NULL, PARAM_TEXT);
$lastName       = optional_param('lastName', NULL, PARAM_TEXT);
$companyName    = optional_param('companyName', NULL, PARAM_TEXT);
$title          = optional_param('title', NULL, PARAM_TEXT);
$street         = optional_param('street', NULL, PARAM_TEXT);
$street2        = optional_param('street2', NULL, PARAM_TEXT);
$street3        = optional_param('street3', NULL, PARAM_TEXT);
$city           = optional_param('city', NULL, PARAM_TEXT);
$state          = optional_param('state', NULL, PARAM_TEXT);
$zip            = optional_param('zip', NULL, PARAM_TEXT);
$country        = optional_param('country', NULL, PARAM_TEXT);
$phone          = optional_param('phone', NULL, PARAM_TEXT);
$altPhone       = optional_param('altPhone', NULL, PARAM_TEXT);
$fax            = optional_param('fax', NULL, PARAM_TEXT);
$email          = optional_param('email', NULL, PARAM_TEXT);
$forumName      = optional_param('forumName', NULL, PARAM_TEXT);
$listingFee     = optional_param('listingFee', NULL, PARAM_TEXT);
$paypalId       = optional_param('paypalId', NULL, PARAM_TEXT);
$userRights     = optional_param('userRights', NULL, PARAM_TEXT);

if (isset($userCreate)) {
    createUser($userName, $userPass, $hint, $answer, $firstName, $lastName, $companyName, $title, $street, $street2, $street3,
               $city, $state, $zip, $country, $phone, $altPhone, $fax, $email, $forumName, $listingFee, $paypalId, $userRights);
}

echo $page->header('');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $UTILITY;

    $data = getUserRights();
    echo "<form name='newUser' id='newUser' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post' onsubmit='return validateUser();'>\n";
    echo "<table width='80%' cellpadding='0' cellspacing='10'>\n";
    echo "  <thead>\n";
    echo "    <tr>\n";
    echo "      <th colspan='2'>Create User</th>\n";
    echo "    </tr>\n";
    echo "  </thead>\n";
    echo "  <tbody>\n";
    echo "    <tr>\n";
    echo "      <td><span style='color: RED;'>*</span>User Name:</td>\n";
    echo "      <td><input type='text' name='userName' id='userName'></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td><span style='color: RED;'>*</span>Password:</td>\n";
    echo "      <td><input type='text' name='userPass' id='userPass'></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td><span style='color: RED;'>*</span>Verify Password:</td>\n";
    echo "      <td><input type='text' name='userPass2' id='userPass2' onchange='javascript: confirmPassword();' ></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td><span style='color: RED;'>*</span>Hint Question:</td>\n";
    echo "      <td><input type='text' name='hint' id='hint' ></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td><span style='color: RED;'>*</span>Hint Answer:</td>\n";
    echo "      <td><input type='text' name='answer' id='answer' ></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td><span style='color: RED;'>*</span>First Name:</td>\n";
    echo "      <td><input type='text' name='firstName' id='firstName' ></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td><span style='color: RED;'>*</span>Last Name:</td>\n";
    echo "      <td><input type='text' name='lastName' id='lastName' ></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td><span style='color: RED;'>*</span>Company Name:</td>\n";
    echo "      <td><input type='text' name='companyName' id='companyName' ></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td><span style='color: RED;'>*</span>Title:</td>\n";
    echo "      <td><input type='text' name='title' id='title' ></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td><span style='color: RED;'>*</span>Address:</td>\n";
    echo "      <td><input type='text' name='street' id='street' ></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td>&nbsp; Address Line 2:</td>\n";
    echo "      <td><input type='text' name='street2' id='street2' ></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td>&nbsp; Address Line 3:</td>\n";
    echo "      <td><input type='text' name='street3' id='street3' ></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td><span style='color: RED;'>*</span>City:</td>\n";
    echo "      <td><input type='text' name='city' id='city' ></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td><span style='color: RED;'>*</span>State:</td>\n";
    echo "      <td><input type='text' name='state' id='state' ></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td><span style='color: RED;'>*</span>Zip Postal Code:</td>\n";
    echo "      <td><input type='text' name='zip' id='zip' ></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td><span style='color: RED;'>*</span>Country:</td>\n";
    echo "      <td><input type='text' name='country' id='country' ></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td><span style='color: RED;'>*</span>Phone:</td>\n";
    echo "      <td><input type='text' name='phone' id='phone' ></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td>Alt Phone:</td>\n";
    echo "      <td><input type='text' name='altPhone' id='altPhone' ></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td>Fax:</td>\n";
    echo "      <td><input type='text' name='fax' id='fax' ></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td><span style='color: RED;'>*</span>Email:</td>\n";
    echo "      <td><input type='text' name='email' id='email' ></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td>Forum Name:</td>\n";
    echo "      <td><input type='text' name='forumName' id='forumName' ></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td><span style='color: RED;'>*</span>Listing Fees:</td>\n";
    echo "      <td><input type='text' name='listingFee' id='listingFee' ></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td>PayPal ID:</td>\n";
    echo "      <td><input type='text' name='paypalId' id='paypalId' ></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td colspan='2'><strong>System Privilages</strong></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td colspan='2'>\n";
    getCheckBox($data, 'userRights', 'userrightid', 'userrightname');
    echo "      </td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td></td>\n";
    echo "      <td><input type='submit' name='userCreate' value='Create User'></td>\n";
    echo "    </tr>\n";
    echo "  </tbody>\n";
    echo "</table>\n";
    echo "</form>\n";

}

function getUserRights() {
    global $page;

    $sql = "SELECT userrightid, userrightname FROM userrights";
    $data = $page->db->sql_query_params($sql);

    return $data;
}

function createUser($userName, $userPass, $hint, $answer, $firstName, $lastName, $companyName, $title, $street, $street2, $street3,
                    $city, $state, $zip, $country, $phone, $altPhone, $fax, $email, $forumName, $listingFee, $paypalId, $userRights) {
    global $page;

    $success = FALSE;
    $uName = strtoupper($userName);

    $sql = "SELECT username FROM users WHERE username = '".$uName."'";
    $used = $page->db->sql_query_params($sql);

    if($used < 1) {
        $sql = "SELECT crypt('".$userPass."', gen_salt('bf', 4));";

        $uPass = $page->db->sql_query_params($sql);
        $pass = $uPass[0]['crypt'];

        $sql = "SELECT NEXTVAL('users_userid_seq')";
        $userId = $page->db->get_field_query($sql);
        unset($sql);


        $sql = "
            INSERT INTO users( userid,  username,  userpass,  hint,  answer,  createdby)
                       VALUES(:userid, :username, :userpass, :hint, :answer, :createdby)
        ";
        $params = array();
        $params['userid']       = $userId;
        $params['username']     = $uName;
        $params['userpass']     = $pass;
        $params['hint']         = $hint;
        $params['answer']       = $answer;
        $params['createdby']    = $page->user->userId;

        $page->db->sql_execute_params($sql, $params);

        unset($sql);
        unset($params);


        $sql = "
            INSERT INTO userinfo( userid,  firstname,  lastname,  title,  forumname,  listingfee,  paypalid,  createdby)
                          VALUES(:userid, :firstname, :lastname, :title, :forumname, :listingfee, :paypalid, :createdby)
        ";
        $params = array();
        $params['userid']       = $userId;
        $params['firstname']    = $firstName;
        $params['lastname']     = $lastName;
        $params['title']        = $title;
        $params['forumname']    = $forumName;
        $params['listingfee']   = $listingFee;
        $params['paypalid']     = $paypalId;
        $params['createdby']    = $page->user->userId;

        $page->queries->AddQuery($sql, $params);
        unset($sql);
        unset($params);

        $sql = "
            INSERT INTO usercontactinfo( userid,  addresstypeid,  companyname,  street,  street2,  street3,  city,  state,  zip,  country,  phone,  altphone,  fax,  email,  createdby)
                                 VALUES(:userid, :addresstypeid, :companyname, :street, :street2, :street3, :city, :state, :zip, :country, :phone, :altphone, :fax, :email, :createdby)
        ";
        $params = array();
        $params['userid']           = $userId;
        $params['addresstypeid']    = 2;
        $params['companyname']      = $companyName;
        $params['street']           = $street;
        $params['street2']          = $street2;
        $params['street3']          = $street3;
        $params['city']             = $city;
        $params['state']            = $state;
        $params['zip']              = $zip;
        $params['country']          = $country;
        $params['phone']            = $phone;
        $params['altphone']         = $altPhone;
        $params['fax']              = $fax;
        $params['email']            = $email;
        $params['createdby']        = $page->user->userId;

        $page->queries->AddQuery($sql, $params);
        unset($sql);
        unset($params);


        foreach ($userRights as $rights => $right) {

            $sql = "
                INSERT INTO assignedrights( userid,  userrightid,  createdby)
                                    VALUES(:userid, :userrightid, :createdby)
            ";
            $params = array();
            $params['userid']       = $userId;
            $params['userrightid']  = $right;
            $params['createdby']    = $page->user->userId;

            $page->queries->AddQuery($sql, $params);
            unset($sql);
            unset($params);

        }

        if ($page->queries->HasQueries()) {
            $process = $page->queries->ProcessQueries();
        }
/////////////////////////////////////////////////////////////////////////////////
            if ($process == TRUE) {
                $success = TRUE;
                $page->messages->addSuccessMsg('You have added the User- '.$uName);
            } else {
                $page->messages->addErrorMsg('Error?');
            }

        return $success;

    } else {
        $page->messages->addErrorMsg('The user name - '.$uName.' is in use');
    }



//SELECT * FROM users WHERE username = '".$uName."' AND userpass = crypt('".uPass."', userpass);
}

?>