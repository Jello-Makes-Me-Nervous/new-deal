<?php
require_once('templateCommon.class.php');

$page = new templateCommon(NOLOGIN, SHOWMSG);
$page->requireJS("/scripts/formValidation.js");
$page->requireJS("https://www.google.com/recaptcha/api.js");

$firstname      = optional_param('firstname', NULL, PARAM_RAW);
$lastname       = optional_param('lastname', NULL, PARAM_RAW);
$companyname    = optional_param('companyname', NULL, PARAM_RAW);
$street         = optional_param('street', NULL, PARAM_RAW);
$street2        = optional_param('street2', NULL, PARAM_RAW);
$city           = optional_param('city', NULL, PARAM_RAW);
$state          = optional_param('state', NULL, PARAM_RAW);
$zip            = optional_param('zip', NULL, PARAM_RAW);
$country        = optional_param('country', NULL, PARAM_RAW);
$phone          = optional_param('phone', NULL, PARAM_RAW);
$email          = optional_param('email', NULL, PARAM_RAW);
$password       = optional_param('password', NULL, PARAM_RAW);
$hint           = optional_param('hint', "x", PARAM_RAW);
$answer         = optional_param('answer', "x", PARAM_RAW);
$referral       = optional_param('referral', NULL, PARAM_RAW);
$ebayid         = optional_param('ebayid', NULL, PARAM_RAW);
$paypalid       = optional_param('paypalid', NULL, PARAM_RAW);
$submitbtn      = optional_param('submitbtn', NULL, PARAM_RAW);

$honeypot       = optional_param('dow', NULL, PARAM_RAW);
$recaptcha      = optional_param('g-recaptcha-response', NULL, PARAM_RAW);

if (!empty($recaptcha) && empty($honeypot)) {
    if (!empty($submitbtn)) {
        if (!doesEmailExist($email)) {
            if (!empty($firstname) && !empty($lastname) && !empty($street) && !empty($city) &&
                !empty($state) && !empty($state) && !empty($zip) && !empty($country) &&
                !empty($phone) && !empty($email) && !empty($password) && !empty($hint) && !empty($answer)) {
                createUser($firstname, $lastname, $companyname, $street, $street2, $city, $state, $zip,
                           $country, $phone, $email, $password, $hint, $answer, $referral, $ebayid, $paypalid);
            } else {
                $page->messages->addErrorMsg("ERROR: Missing fields. Please try agian or contact Dealernet admin for assistance.");
            }
        } else {
            $url  = "/contactus_nologin.php";
            $link = "<a href='".$url."'>contact Dealernet Admin</a>";
            $page->messages->addErrorMsg("ERROR: User with this email already exists. Please ".$link." for assistance.");
            $subject = "New User Registration - Possible Dup / eMail Exists";
            sendInternalMsg(NULL, $subject);
        }
    }
} else {
    $page->messages->addWarningMsg("You need to complete the recaptcha to register for access.");
}
$js = "
const toggleRegistrationPassword = document.querySelector('#toggleRegistrationPassword');
const registrationPassword = document.querySelector('#id_registrationpassword');

  toggleRegistrationPassword.addEventListener('click', function (e) {
    // toggle the type attribute
    const type = registrationPassword.getAttribute('type') === 'password' ? 'text' : 'password';
    registrationPassword.setAttribute('type', type);
    // toggle the eye slash icon
    this.classList.toggle('fa-eye-slash');
});
";
$page->jsInit($js);

echo $page->header('Register');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $CFG;

    echo "<FORM  NAME='RegisterForm' ACTION='register.php'  OnSubmit='return VerifyFields(this)' METHOD='POST'  CLASS='form'>\n";
    echo "  <P CLASS='required'> Fields marked with an <IMG SRC='images/asterick.gif' HEIGHT='8' WIDTH='8' BORDER='0' ALT='required field'> are required.</P>\n";
    echo "  <TABLE CELLPADDING='5' CELLSPACING='0' BORDER='0' CLASS='form-table'>\n";
    echo "    <TR>\n";
    echo "      <TD colspan='2' class='table-copy' style='border: medium double rgb(0,0,255); text-align: center;'>Some emails from Dealernet get marked as spam by some spam filters.<br>Please add the dealernetx.com domain to your friendly domains in your spam filters.<br>Adding admin@dealernetx.com to your address book may help as well.</TD>\n";
    echo "    </TR>\n";
    echo "     <TR>\n";
    echo "      <TD CLASS='form-label'><IMG SRC='images/asterick.gif' HEIGHT='8' WIDTH='8' BORDER='0' ALT='Required'>&nbsp;First Name:</TD>\n";
    echo "      <TD><INPUT TYPE='text' NAME='firstname' SIZE='30' MAXLENGTH='30' VALUE='' CLASS='input' required></TD>\n";
    echo "    </TR>\n";
    echo "    <TR>\n";
    echo "      <TD CLASS='form-label'><IMG SRC='images/asterick.gif' HEIGHT='8' WIDTH='8' BORDER='0' ALT='Required'>&nbsp;Last Name:</TD>\n";
    echo "      <TD><INPUT TYPE='text' NAME='lastname' SIZE='30' MAXLENGTH='30' VALUE='' CLASS='input' required></TD>\n";
    echo "    </TR>\n";
    echo "    <TR>\n";
    echo "      <TD CLASS='form-label'><IMG SRC='images/spacer.gif' HEIGHT='8' WIDTH='8' BORDER='0'>&nbsp;Company Name:</TD>\n";
    echo "      <TD><INPUT TYPE='text' NAME='companyname' SIZE='30' MAXLENGTH='30' VALUE='' CLASS='input'></TD>\n";
    echo "    </TR>\n";
    echo "    <TR>\n";
    echo "      <TD CLASS='form-label'><IMG SRC='images/asterick.gif' HEIGHT='8' WIDTH='8' BORDER='0' ALT='Required'>&nbsp;Address:</TD>\n";
    echo "      <TD><INPUT TYPE='text' NAME='street' SIZE='30' MAXLENGTH='30' VALUE='' CLASS='input' required></TD>\n";
    echo "    </TR>\n";
    echo "    <TR>\n";
    echo "      <TD CLASS='form-label'><IMG SRC='images/spacer.gif' HEIGHT='8' WIDTH='8' BORDER='0'>&nbsp;Address Line 2:</TD>\n";
    echo "      <TD><INPUT TYPE='text' NAME='street2' SIZE='30' MAXLENGTH='30' VALUE='' CLASS='input'></TD>\n";
    echo "    </TR>\n";
    echo "    <TR>\n";
    echo "      <TD CLASS='form-label'><IMG SRC='images/asterick.gif' HEIGHT='8' WIDTH='8' BORDER='0' ALT='Required'>&nbsp;City:</TD>\n";
    echo "      <TD><INPUT TYPE='text' NAME='city' SIZE='30' MAXLENGTH='30' VALUE='' CLASS='input' required></TD>\n";
    echo "    </TR>\n";
    echo "    <TR>\n";
    echo "      <TD CLASS='form-label'><IMG SRC='images/asterick.gif' HEIGHT='8' WIDTH='8' BORDER='0' ALT='Required'>&nbsp;State:</TD>\n";
    echo "      <TD><INPUT TYPE='text' NAME='state' SIZE='2' MAXLENGTH='2' VALUE='' CLASS='input' required></TD>\n";
    echo "    </TR>\n";
    echo "    <TR>\n";
    echo "      <TD CLASS='form-label'><IMG SRC='images/asterick.gif' HEIGHT='8' WIDTH='8' BORDER='0' ALT='Required'>&nbsp;Zip Code:</TD>\n";
    echo "      <TD><INPUT TYPE='text' NAME='zip' SIZE='10' MAXLENGTH='10' VALUE='' CLASS='input' required></TD>\n";
    echo "    </TR>\n";
    echo "    <TR>\n";
    echo "      <TD CLASS='form-label'><IMG SRC='images/asterick.gif' HEIGHT='8' WIDTH='8' BORDER='0' ALT='Required'>&nbsp;Country:</TD>\n";
    echo "      <TD><INPUT TYPE='text' NAME='country' SIZE='30' MAXLENGTH='30' VALUE='' CLASS='input' required></TD>\n";
    echo "    </TR>\n";
    echo "    <TR>\n";
    echo "      <TD CLASS='form-label'><IMG SRC='images/asterick.gif' HEIGHT='8' WIDTH='8' BORDER='0' ALT='Required'>&nbsp;Mobile Phone:</TD>\n";
    echo "      <TD><INPUT TYPE='text' NAME='phone' SIZE='15' MAXLENGTH='15' VALUE='' CLASS='input'></TD>\n";
    echo "    </TR>\n";
    echo "    <TR>\n";
    echo "      <TD CLASS='form-label'><IMG SRC='images/asterick.gif' HEIGHT='8' WIDTH='8' BORDER='0' ALT='Required'>&nbsp;E-mail [user name]:</TD>\n";
    echo "      <TD><INPUT TYPE='text' NAME='email' SIZE='30' MAXLENGTH='100' VALUE='' CLASS='input' required></TD>\n";
    echo "    </TR>\n";
    echo "    <TR>\n";
    echo "      <TD CLASS='form-label'><IMG SRC='images/asterick.gif' HEIGHT='8' WIDTH='8' BORDER='0' ALT='Required' required>&nbsp;Password:</TD>\n";
    echo "      <TD>\n";
    echo "        <INPUT TYPE='password' NAME='password' ID='id_registrationpassword' SIZE='30' MAXLENGTH='100' VALUE='' CLASS='input'>\n";
    echo "        <i class='far fa-eye' id='toggleRegistrationPassword' style='margin-left: -30px; cursor: pointer;'></i>\n";
    echo "      </TD>\n";
    echo "    </TR>\n";
//    echo "    <TR>\n";
//    echo "      <TD CLASS='form-label'><IMG SRC='images/asterick.gif' HEIGHT='8' WIDTH='8' BORDER='0' ALT='Required'>&nbsp;Password Hint:</TD>\n";
//    echo "      <TD><INPUT TYPE='text' NAME='hint' SIZE='30' MAXLENGTH='100' VALUE='' CLASS='input' required></TD>\n";
//    echo "    </TR>\n";
//    echo "    <TR>\n";
//    echo "      <TD CLASS='form-label'><IMG SRC='images/asterick.gif' HEIGHT='8' WIDTH='8' BORDER='0' ALT='Required'>&nbsp;Hint Answer:</TD>\n";
//    echo "      <TD><INPUT TYPE='text' NAME='answer' SIZE='30' MAXLENGTH='100' VALUE='' CLASS='input' required></TD>\n";
//    echo "    </TR>\n";
    echo "    <TR>\n";
    echo "      <TD CLASS='form-label'><IMG SRC='images/spacer.gif' HEIGHT='8' WIDTH='8' BORDER='0'>&nbsp;EIN / Tax Id #:</TD>\n";
    echo "      <TD><INPUT TYPE='text' NAME='ein' SIZE='11' MAXLENGTH='11' VALUE='' CLASS='input'></TD>\n";
    echo "    </TR>\n";
    echo "     <TR>\n";
    echo "      <TD CLASS='form-label'><IMG SRC='images/asterick.gif' HEIGHT='8' WIDTH='8' BORDER='0' ALT='Required'>&nbsp;Referral:</TD>\n";
    echo "      <TD><INPUT TYPE='text' NAME='referral' SIZE='30' VALUE='' CLASS='input' required><BR><SPAN CLASS='footnote'>If none, enter N/A</SPAN></TD>\n";
    echo "    </TR>\n";
    echo "    <TR>\n";
    echo "      <TD CLASS='form-label'><IMG SRC='images/spacer.gif' HEIGHT='8' WIDTH='8' BORDER='0'>&nbsp;eBay ID:</TD>\n";
    echo "      <TD><INPUT TYPE='text' NAME='ebayid' SIZE='30' MAXLENGTH='30' VALUE='' CLASS='input'></TD>\n";
    echo "    </TR>\n";
    echo "    <TR>\n";
    echo "      <TD CLASS='form-label'><IMG SRC='images/spacer.gif' HEIGHT='8' WIDTH='8' BORDER='0'>&nbsp;PayPal ID:</TD>\n";
    echo "      <TD><INPUT TYPE='text' NAME='paypalid' SIZE='30' MAXLENGTH='30' VALUE='' CLASS='input'></TD>\n";
    echo "    </TR>\n";
    echo "  </TABLE>\n";
    if (isset($CFG->reCAPTCHA_SiteKey)) {
        echo "  <div class='g-recaptcha' data-sitekey='".$CFG->reCAPTCHA_SiteKey."'></div>\n";
    }
    echo "  <br/>\n";
    echo "  <input type='text' name='dow' id='dow' size='12' maxlength='12' value='' class='ohnohoney'>\n";
    echo "  <P><INPUT TYPE='submit' VALUE='Submit Registration' ID='submitbtn' NAME='submitbtn'></P>\n";
    echo "  <P>Subject to Terms and  Conditions of Use.</P>\n";
    echo "</FORM>\n";

    echo "<SCRIPT LANGUAGE='JavaScript' TYPE='text/javascript'>\n";
    echo "<!--\n";
    echo "\n";
    echo "  function VerifyFields(f) {\n";
    echo "    var a = [\n";
    echo "              [/^firstname$/,   'First Name',      'text',    true,   50],\n";
    echo "              [/^lastname$/,    'Last Name',       'text',    true,   50],\n";
    echo "              [/^compname$/,    'Company Name',    'text',    false,  30],\n";
    echo "              [/^street$/,      'Address',         'text',    true,   100],\n";
    echo "              [/^street2$/,     'Address 2',       'text',    false,  100],\n";
    echo "              [/^city$/,        'City',            'text',    true,   50],\n";
    echo "              [/^state$/,       'State',           'text',    true,   2],\n";
    echo "              [/^zip$/,         'Zip Code',        'text',    true,   10],\n";
    echo "              [/^country$/,     'Country',         'text',    true,   30],\n";
    echo "              [/^phone$/,       'Phone',           'text',    true,   20],\n";
    echo "              [/^email$/,       'E-mail address',  'email',   true,   100],\n";
    echo "              [/^password$/,    'Password',        'text',    true,   100],\n";
    echo "              [/^hint$/,        'Password Hint',   'text',    true,   100],\n";
    echo "              [/^answer$/,      'Answer',          'text',    true,   100],\n";
    echo "              [/^ein$/,         'EIN / Tax ID #',  'text',    false,  11],\n";
    echo "              [/^referral$/,    'Referral',        'text',    true,   100]\n";
    echo "            ];\n";
    echo "\n";
    echo "    m = '';\n";
    echo "    for (i = 0; i < f.elements.length; i++) {\n";
    echo "        for (j = 0; j < a.length; j++) {\n";
    echo "           if (f.elements[i].name.match(a[j][0])) {\n";
    echo "               m = m + VerifyTextField(f.elements[i], a[j][1], a[j][2], a[j][3], a[j][4]);\n";
    echo "               break;\n";
    echo "           }\n";
    echo "        }\n";
    echo "    }\n";
    echo "\n";
    echo "    if (m != '') {\n";
    echo "        alert('The following fields contain values that are not permitted or are missing values:\\n\\n' + m);\n";
    echo "        return false;\n";
    echo "    } else {\n";
    echo "        return true;\n";
    echo "    }\n";
    echo "  }\n";
    echo "\n";
    echo "//-->\n";
    echo "\n";
    echo "</SCRIPT>\n";
}

function createUser($firstname, $lastname, $companyname, $street, $street2, $city, $state, $zip,
                    $country, $phone, $email, $password, $hint, $answer, $referral, $ebayid, $paypalid) {
    global $page;

    $success = false;
    $exists = $page->db->get_field_query("SELECT username FROM users WHERE lower(username) = lower('email')");
    if ($exists) {
        $page->messages->addErrorMsg("ERROR: User with this email already exists. Please contact Dealernet Admin for assistance.");
    } else {
        $addresstypes   = $page->db->sql_query("SELECT addresstypeid FROM addresstype");
        $new            = $page->db->get_field_query("SELECT userclassid FROM userclass WHERE lower(userclassname) = 'inactive'");
        $rights         = $page->db->sql_query("SELECT userrightid FROM userrights WHERE lower(userrightname) IN ('enabled', 'eft enabled')");
        $preferences    = $page->db->sql_query("SELECT preferenceid FROM userpreferences WHERE lower(preference) IN ('inbox blasts', 'auto-inactivate for sale', 'auto-inactivate wanted')");

        $userid = $page->utility->nextval('users_userid_seq');

        if (empty($new) || empty($userid) || empty($rights) || empty($preferences) || empty($addresstypes)) {
            $page->messages->addErrorMsg("ERROR: unable to get user class / right / preference / id. Please contact Dealernet admin for assistance.");
        } else {
            $sql = "
                INSERT INTO users( userid,  username,  userpass,  createdby)
                           VALUES(:userid, :username, crypt(:userpass, gen_salt('bf')), :createdby)
            ";

            $params = array();
            $params['userid']       = $userid;
            $params['username']     = $email;
            $params['userpass']     = strtoupper($password);
            $params['createdby']    = $email;

            $page->queries->AddQuery($sql, $params);
            unset($sql);
            unset($params);


            if (!empty($addresstypes)) {
                $sql = "
                    INSERT INTO usercontactinfo ( userid,  addresstypeid,  companyname,  street,  street2,  city,  state,  zip, country,  phone,  email,  createdby)
                                         values (:userid, :addresstypeid, :companyname, :street, :street2, :city, :state, :zip, :country, :phone, :email, :createdby)
                ";

                $params = array();
                $params['userid']           = $userid;
                $params['companyname']      = (empty($companyname)) ? null : $companyname;
                $params['street']           = $street;
                $params['street2']          = (empty($street2)) ? null : $street2;
                $params['city']             = $city;
                $params['state']            = $state;
                $params['zip']              = $zip;
                $params['country']          = $country;
                $params['phone']            = $phone;
                $params['email']            = $email;
                $params['createdby']        = $email;

                foreach($addresstypes as $at) {
                    $params['addresstypeid']    = $at["addresstypeid"];
                    $page->queries->AddQuery($sql, $params);
                }
                unset($sql);
                unset($params);
            }


            $sql = "
                INSERT INTO userinfo ( userid,  firstname,  userclassid,  lastname,  eintaxid,  ebayid,  paypalid, createdby)
                              values (:userid, :firstname, :userclassid, :lastname, :eintaxid, :ebayid, :paypalid, :createdby)
            ";

            $params = array();
            $params['userid']       = $userid;
            $params['firstname']    = $firstname;
            $params['lastname']     = $lastname;
            $params['userclassid']  = $new;
            $params['eintaxid']     = (empty($eintaxid)) ? null : $eintaxid;
            $params['ebayid']       = (empty($ebayid)) ? null : $ebayid;
            $params['paypalid']     = (empty($paypalid)) ? null : $paypalid;
            $params['createdby']    = $email;

            $page->queries->AddQuery($sql, $params);
            unset($sql);
            unset($params);

            $sql = "
                INSERT INTO transactions( crossrefid,  useraccountid,  refaccountid,  transtype, transstatus, transdate,  dgrossamount,  accountname,  transdesc, offerid,  createdby, createdate, modifiedby, modifydate)
                                  VALUES(:crossrefid, :useraccountid, :refaccountid, :transtype, :transstatus, :transdate, :dgrossamount, :accountname, :transdesc, :offerid, :createdby, :createdate, :modifiedby, :modifydate)
            ";
            $asOfDate = time();
            $asOfStr = date('m/d/Y H:i:s', $asOfDate);
            $params = array();
            $params['crossrefid']       = -1;
            $params['useraccountid']    = $userid;
            $params['refaccountid']     = NULL;
            $params['transtype']        = EFT_TRAN_TYPE_BALANCE;
            $params['transstatus']      = "ACCEPTED";
            $params['transdate']        = $asOfDate;
            $params['dgrossamount']     = 0.00;
            $params['accountname']      = $email;
            $params['transdesc']        = "Initial Balance as of ".$asOfStr;
            $params['offerid']          = NULL;
            $params['createdby']        = $email;
            $params['createdate']       = $asOfDate;
            $params['modifiedby']       = $email;
            $params['modifydate']       = $asOfDate;

            $page->queries->AddQuery($sql, $params);
            unset($sql);
            unset($params);

            $sql = "
                INSERT INTO assignedrights( userid,  userrightid,  createdby)
                                    values(:userid, :userrightid, :createdby)
            ";
            $params = array();
            $params['userid']       = $userid;
            $params['createdby']    = $email;

            foreach($rights as $r) {
                $params['userrightid']  = $r["userrightid"];
                $page->queries->AddQuery($sql, $params);
            }
            unset($sql);
            unset($params);


            $sql = "
                INSERT INTO assignedpreferences( userid,  preferenceid, value,  createdby)
                                    values(:userid, :preferenceid, :value, :createdby)
            ";
            $params = array();
            $params['userid']       = $userid;
            $params['createdby']    = $email;

            foreach($preferences as $r) {
                $params['preferenceid'] = $r["preferenceid"];
                $params['value']        = $r["preferenceid"];
                $page->queries->AddQuery($sql, $params);
            }
            unset($sql);
            unset($params);

            try {
                $page->db->sql_begin_trans();
                $page->queries->ProcessQueries();
                $page->messages->addSuccessMsg('You have created the user - '.$email);
                $success = true;
                sendInternalMsg($userid);
            } catch (Exception $e) {
                $page->db->sql_rollback_trans();
                $page->messages->addErrorMsg("ERROR: ".$e->getMessage()." [Unable to create user.]");
                $success = false;
            } finally {
                if ($success) {
                    $page->db->sql_commit_trans();
                }
            }
        }
    }

    return $success;
}

function sendInternalMsg($userid, $subject = "New User Registration") {
    global $page;


    $msg = "<div class='filters'>\n";
    foreach($_POST as $name=>$value) {
        if ($name == "dow") {
            $msg .= "  <label style='color:#D30000;'>".$name.":</label>&nbsp;&nbsp;".$value."<br/>\n";
        } else {
            $msg .= ($name == "g-recaptcha-response") ? "" : "  <label>".$name.":</label>&nbsp;&nbsp;".$value."<br/>\n";
        }
    }
    $msg .= "</div>\n";
    $msg2 = "<hr>\n";
    $msg2 .= "<div class='filters'>\n";
    $msg2 .= "  <label style='color:#D30000;'>*** If dow has a value then the form was not completed by a human. ***</label><br/>\n";
    $msg2 .= "  <label>Browser</label>&nbsp;&nbsp;".$_SERVER["HTTP_SEC_CH_UA"]."<br/>\n";
    $msg2 .= "  <label>Platform</label>&nbsp;&nbsp;".$_SERVER["HTTP_SEC_CH_UA_PLATFORM"]."<br/>\n";
    $msg2 .= "  <label>User Agent</label>&nbsp;&nbsp;".$_SERVER["HTTP_USER_AGENT"]."<br/>\n";
    $msg2 .= "  <label>IP Address</label>&nbsp;&nbsp;".$_SERVER["REMOTE_ADDR"]."<br/>\n";
    $msg2 .= "  <label>Network</label>&nbsp;&nbsp;".gethostbyaddr($_SERVER["REMOTE_ADDR"])."<br/>\n";
    $msg2 .= "  <label>Language</label>&nbsp;&nbsp;".$_SERVER["HTTP_ACCEPT_LANGUAGE"]."<br/>\n";
    if (isset($_POST["g-recaptcha-response"])) {
        $msg2 .= "  <label>g-recaptcha-response</label>&nbsp;&nbsp;".$_POST["g-recaptcha-response"]."<br/>\n";
    }
    $msg2 .= "</div>\n";

    $page->iMessage->insertSystemMessage($page, ADMINUSERID, ADMINUSERNAME, $subject, $msg.$msg2, EMAIL);
    if (!empty($userid)) {
        $subjectText = "DealernetX: ".$subject;
        $messageText = "<p>You have supplied the following information to DealernetX:</p>".$msg;
        $page->iMessage->sendExternalEmail($userid, $subjectText, $messageText);
    }
}

function doesEmailExist($email) {
    global $page;

    $sql = "
        SELECT case when strpos(lower(u.username), lower(:username)) > 0 then 1
                    when b.userid IS NOT NULL AND strpos(lower(b.email), lower(:billingemail)) > 0 then 1
                    when s.userid IS NOT NULL AND strpos(lower(s.email), lower(:shippingemail)) > 0 then 1
                    else 0 end as email_exists
          FROM users                    u
          LEFT JOIN usercontactinfo     b   ON  b.userid        = u.userid
                                            AND b.addresstypeid = 1
          LEFT JOIN usercontactinfo     s   ON  s.userid        = b.userid
                                            AND s.addresstypeid = 3
         WHERE strpos(lower(u.username), lower(:username2)) > 0
        ORDER BY 1 DESC
        LIMIT 1
    ";

    $params = array();
    $params["username"]         = strtolower($email);
    $params["username2"]         = strtolower($email);
    $params["billingemail"]     = strtolower($email);
    $params["shippingemail"]    = strtolower($email);

    $exists = $page->db->get_field_query($sql, $params);

    return $exists;
}
?>