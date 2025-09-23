<?php
require_once('templateCommon.class.php');
DEFINE ("MESSAGE_MAX",  250);

$page = new templateCommon(NOLOGIN, SHOWMSG);
$page->requireJS("https://www.google.com/recaptcha/api.js");

$name           = optional_param('name', NULL, PARAM_TEXT);
$contactinfo    = optional_param('contactinfo', NULL, PARAM_TEXT);
$message        = optional_param('message', NULL, PARAM_TEXT);
$dow            = optional_param('dow', NULL, PARAM_TEXT);
$honeypot       = optional_param('phone', NULL, PARAM_RAW);
$recaptcha      = optional_param('g-recaptcha-response', NULL, PARAM_RAW);
$submitbtn      = optional_param('submitbtn', NULL, PARAM_TEXT);
$pagemsg        = optional_param('pagemsg', NULL, PARAM_TEXT);

if (!empty($pagemsg)) {
    $page->messages->addErrorMsg($pagemsg);
}

if (!empty($submitbtn) && !empty($recaptcha) && empty($honeypot)) {
    if (strtolower(date("l")) == strtolower($dow)) {
        if ((strlen(trim($message)) > 0) && !empty(trim($name)) && !empty(trim($contactinfo))) {
            $msg  = "<p><b>Name:</b> ".$name."</p>";
            $msg .= "<p><b>Contact Info:</b> ".$contactinfo."</p>";
            $msg .= "<p><b>Message:</b><br>".$message."</p>";
            $page->iMessage->insertSystemMessage($page, ADMINUSERID, ADMINUSERNAME, "Contact Us", $msg, EMAIL);
            $page->messages->addSuccessMsg("Thank you. Someone from Dealernet will be in contact shortly.");
        }
    } else {
        $page->messages->addErrorMsg("ERROR: Incorrect day of week entered. You have 1 more attempt for today.");
    }
} elseif (!empty($honeypot)) {
    $page->messages->addInfoMsg("Thank you. Someone from Dealernet will be in contact shortly.");
}

echo $page->header('Contact Us');
echo mainContent();
echo $page->footer(true);


function mainContent() {
    global $CFG, $page;

    echo "<h3>Contact Us</h3>\n";
    echo "<article>\n";
    echo "  <div>\n";
    echo "    <form  name='registerform' action='contactus_nologin.php' method='post'  class='form'>\n";
    echo "      <table>\n";
    echo "        <thead>\n";
    echo "          <tr>\n";
    echo "            <th>Address</th>\n";
    echo "            <th>Message</th>\n";
    echo "          </tr>\n";
    echo "        </thead>\n";
    echo "        <tbody>\n";
    echo "          <tr>\n";
    echo "            <td rowspan='4'>\n";
    echo "              <b>Dealernet Inc</b><br/>\n";
    echo "              &nbsp;&nbsp;12226 Corporate Blvd<br/>\n";
    echo "              &nbsp;&nbsp;Suite 142-338<br/>\n";
    echo "              &nbsp;&nbsp;Orlando, FL 32817<br/>\n";
    echo "            </td>\n";
    echo "            <td>\n";
    echo "              <label for='name'>Name:</label><br>\n";
    echo "              <input type='text' name='name' id='name' size='25' maxlength='25' value='' required>\n";
    echo "            </td>\n";
    echo "          </tr>\n";
    echo "          <tr>\n";
    echo "            <td>\n";
    echo "              <label for='contactinfo'>Preferred Contact Info:</label><br>\n";
    echo "              <input type='text' name='contactinfo' id='contactinfo' size='25' maxlength='25' value='' placeholder='email address, phone number, etc' required>\n";
    echo "            </td>\n";
    echo "          </tr>\n";
    echo "          <tr>\n";
    echo "            <td>\n";
    echo "              <label for='message'>Message:</label><br>\n";
    echo "              <textarea style='width: 80%; height: 100px;' name='message' id='message' maxlength='".MESSAGE_MAX."' placeholder='Max message size is ".MESSAGE_MAX." characters' onkeyup='countChar(this)' required></textarea>\n";
    echo "              <div id='charNum' style='color:#AAA;'>".MESSAGE_MAX." characters left</div>\n";
    echo "            </td>\n";
    echo "          </tr>\n";
    echo "          <tr>\n";
    echo "            <td>\n";
    echo "              <label for='dow'>What is today's day of the week?</label><br>\n";
    echo "              <input type='text' name='dow' id='dow' size='25' maxlength='9' value='' placeholder='monday, tuesday, etc' required>\n";
    echo "            </td>\n";
    echo "          </tr>\n";
    echo "        </tbody>\n";
    echo "      <table>\n";
    if (isset($CFG->reCAPTCHA_SiteKey)) {
        echo "      <div class='g-recaptcha' data-sitekey='".$CFG->reCAPTCHA_SiteKey."'></div>\n";
    }
    echo "      <br/>\n";
    echo "      <input type='text' name='phone' id='phone' size='12' maxlength='12' value='' class='ohnohoney'>\n";
    echo "      <p><input type='submit' value='submit' id='submitbtn' name='submitbtn'></p>\n";
    echo "    </form>\n";
    echo "    <script language='JavaScript'>\n";
    echo "      function countChar(val) {\n";
    echo "        var len = val.value.length;\n";
    echo "        if (len >= ".MESSAGE_MAX.") {\n";
    echo "          val.value = val.value.substring(0, ".MESSAGE_MAX.");\n";
    echo "        } else {\n";
    echo "          $('#charNum').text(".MESSAGE_MAX." - len + ' characters left');\n";
    echo "        }\n";
    echo "      }\n";
    echo "    </script>\n";

    echo "  </div>\n";
    echo "</article>\n";
}
?>