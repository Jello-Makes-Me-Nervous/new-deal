<?php
require_once('templateHome.class.php');

$page = new templateHome(NOLOGIN, SHOWMSG, REDIRECTSAFE);
$page->requireJS("https://www.google.com/recaptcha/api.js");

$dealer     = optional_param('dealer', NULL, PARAM_TEXT);
$honeypot   = optional_param('phone', NULL, PARAM_RAW);
$submitbtn  = optional_param('submitbtn', NULL, PARAM_TEXT);

if (!empty($submitbtn) and empty($honeypot)) {
    if (!empty($dealer)) {
        resetPassword($dealer);
    } else {
        $page->messages->addErrorMsg("ERROR: You must specify a dealer account name.");
    }
} elseif (!empty($honeypot)) {
    $page->messages->addInfoMsg("Thank you. If this is correct you will recieve and email shortly with your new password.");
}

echo $page->header('Reset Password');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $CFG, $page;

    echo "<h3>Reset Password</h3>\n";
    echo "<article>\n";
    echo "  <div>\n";
    echo "    <p>Enter your dealer name and if your account is found, a temporary password will be emailed to you.<br>Once you log in you will be able to change your password from the Account Tab / Menu > My Profile page.</p>\n";
    echo "    <form name='admin' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "      <div>\n";
    echo "        <label for='dealer'>Dealer name:</label>&nbsp;&nbsp;\n";
    echo "        <input type='text' name='dealer' id='dealer' value='' required>\n";
    if (isset($CFG->reCAPTCHA_SiteKey)) {
        echo "        <div>&nbsp;</div>\n";
        echo "        <div class='g-recaptcha' data-sitekey='".$CFG->reCAPTCHA_SiteKey."'></div>\n";
    }
    echo "        <br/>\n";
    echo "        <input type='text' name='phone' id='phone' size='12' maxlength='12' value='' class='ohnohoney'>\n";
    echo "        <p><input type='submit' value='submit' id='submitbtn' name='submitbtn'></p>\n";

    echo "      </div>\n";
    echo "    </form>\n";
    echo "  </div>\n";
    echo "</article>\n";
}

function resetPassword($dealer) {
    global $page;

    $pwd = $page->utility->generatePassword(6, "uds");
    $dealerid = $page->utility->getDealerId($dealer);

    if (!empty($dealerid)) {
        $subject = "Password Reset";
        $msg = "A request to reset the password of your account has been recieved.  We sent an external email with the new password.";

        $subjectText = "DealernetX: ".$subject;
        $messageText  = "<p>A request to reset the password of your account has been recieved.</p>";
        $messageText .= "<p>Your temporary password is ".$pwd."</p>";
        $messageText .= "<p>You can change your password on the My Profile page under the My Account Menu.</p>";
        $messageText .= "<p>Regards,<br>DealernetX admin team.</p>";

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
            $page->messages->addSuccessMsg("If the dealer exists, an email will be sent to the member.");
            $page->iMessage->sendExternalEmail($dealerid, $subjectText, $messageText);
            $page->iMessage->insertSystemMessage($page, $dealerid, $dealer, $subject, $msg, EMAIL);
        } catch (Exception $e) {
            $page->db->sql_rollback_trans();
            $page->messages->addErrorMsg("ERROR: ".$e->getMessage()." [Unable reset password.]");
        } finally {
        }
    } else {
        $page->messages->addWarningMsg("If the dealer exists, an email will be sent to the member.");
    }

}

?>