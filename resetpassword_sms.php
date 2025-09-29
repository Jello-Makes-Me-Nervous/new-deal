<?php
require_once('templateHome.class.php');
require_once("twilio_sms.php");

$page = new templateHome(NOLOGIN, SHOWMSG, REDIRECTSAFE);
$page->requireJS("https://www.google.com/recaptcha/api.js");

$js  = "\n";
$js .= "$('.numbersOnly').keyup(function () {\n";
$js .= "  if (this.value != this.value.replace(/[^0-9\.]/g, '')) {\n";
$js .= "    this.value = this.value.replace(/[^0-9\.]/g, '');\n";
$js .= "  }\n";
$js .= "});\n";
$page->jsInit($js);

$dealer     = optional_param('dealer', NULL, PARAM_TEXT);
$phone      = optional_param('phone', NULL, PARAM_TEXT);
$honeypot   = optional_param('username', NULL, PARAM_RAW);
$submitbtn  = optional_param('submitbtn', NULL, PARAM_TEXT);

//echo "<pre>";print_r($_POST);echo "</pre>";

if (!empty($submitbtn) and empty($honeypot)) {
    if (!empty($dealer) && !empty($phone)) {
        resetPassword($dealer, $phone);
    } else {
        $page->messages->addErrorMsg("ERROR: You must specify a dealer account name and phone.");
    }
} elseif (!empty($honeypot)) {
    $page->messages->addInfoMsg("Thank you. If this is correct you will recieve a SMS text shortly with your new password.");
}

echo $page->header('Reset Password');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $CFG, $page;

    echo "<h3>Reset Password</h3>\n";
    echo "<article>\n";
    echo "  <div>\n";
    echo "    <p>Enter your dealer name and if your account is found, a temporary password will be texted to you.<br>Once you log in you will be able to change your password from the Account Tab / Menu > My Profile page.</p>\n";
    echo "    <form name='admin' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "      <div>\n";
    echo "        <label for='dealer'>DealernetX member ID:</label>&nbsp;&nbsp;\n";
    echo "        <input type='text' name='dealer' id='dealer' value='' required>\n";
    echo "        <br/><div style='clear:both;'>&nbsp;</div>\n";
    echo "        <label for='dealer'>Phone Number to receive SMS text:</label>&nbsp;&nbsp;\n";
    echo "        <input type='text' name='phone' id='phone' value='' style='width:450px;' class='numbersOnly' placeholder='#s only ([country code][phone number including area code])' required>\n";
    echo "        <br><b><i>#s only ... no spaces, dashes, brackets or parentheses, etc.<br>Please include the country code if outside the US and Canada.</i></b>\n";
    echo "        <br/>\n";
    if (isset($CFG->reCAPTCHA_SiteKey)) {
        echo "        <div>&nbsp;</div>\n";
        echo "        <div class='g-recaptcha' data-sitekey='".$CFG->reCAPTCHA_SiteKey."'></div>\n";
    }
    echo "        <br/>\n";
    echo "        <input type='text' name='username' id='username' size='12' maxlength='12' value='' class='ohnohoney'>\n";
    echo "        <p><input type='submit' value='submit' id='submitbtn' name='submitbtn'></p>\n";

    echo "      </div>\n";
    echo "    </form>\n";
    echo "  </div>\n";
    echo "</article>\n";
}

function resetPassword($dealer, $phonenumber) {
    global $page;

    $pwd = $page->utility->generatePassword(8, "d");
    $dealerid = $page->utility->getDealerId($dealer);

    if (!empty($dealerid)) {
        $phone = doesPhoneNumberMatch($dealerid, $phonenumber);
        if (!empty($phone)) {
            $subject = "Password Reset";
            $msg = "A request to reset the password of your account has been recieved.  We sent a text with the new password.";

            $subjectText = "DealernetX: ".$subject;
            $messageText  = "A request to reset the password of your account has been recieved.\n";
            $messageText .= "Your temporary password is ".$pwd;
//            $messageText .= "<p>You can change your password on the My Profile page under the My Account Menu.</p>";
//            $messageText .= "<p>Regards,<br>DealernetX admin team.</p>";

            $sql = "
                UPDATE users
                   SET userpass     = crypt(:pwd, gen_salt('bf')),
                       modifiedby   = :modifiedby,
                       modifydate   = nowtoint()
                 WHERE userid = :userid
            ";

            $params = array();
            $params["pwd"]          = $pwd;
            $params["userid"]       = $dealerid;
            $params["modifiedby"]   = "resetpwd";

            try {
                $page->db->sql_execute_params($sql, $params);
                $page->messages->addSuccessMsg("If the dealer exists, an SMS text will be sent to the member.");
                $msgSID = sendSMS(NULL, $phone, $messageText);
                $page->iMessage->insertSystemMessage($page, $dealerid, $dealer, $subject, $msg, EMAIL);
            } catch (Exception $e) {
                $page->db->sql_rollback_trans();
                $page->messages->addErrorMsg("ERROR: ".$e->getMessage()." [Unable reset password.]");
            } finally {
            }
        } else {
            $pagemsg= "Phone number not found; please send message to admin.";
            $redirect = "location:contactus_nologin.php?pagemsg=".$pagemsg;
            header($redirect);
            exit();
        }
    } else {
        $page->messages->addWarningMsg("If the dealer exists, an SMS text will be sent to the member.");
    }

}

function doesPhoneNumberMatch($dealerid, $phonenumber) {
    global $page;

    $sql = "
        SELECT emailphone
          FROM notification_preferences
         WHERE notification_type = 'SMS'
           AND isactive = 1
           AND userid   = ".$dealerid."
           AND emailphone like '%".$phonenumber."'
    ";
    $phone = $page->db->get_field_query($sql);

    return $phone;
}

?>