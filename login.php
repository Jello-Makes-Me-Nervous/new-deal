<?php
require_once('template.class.php');

$page = new template(NOLOGIN, SHOWMSG, REDIRECTSAFE);
$page->jsInit("getMyTimezone();");

$loginBtn     = optional_param('loginBtn', NULL, PARAM_ALPHA);
$userName     = optional_param('userName', NULL, PARAM_TEXT);
$userPass     = optional_param('userPass', NULL, PARAM_RAW);
$mytime       = optional_param('tz', NULL, PARAM_RAW);
$mytime2      = optional_param('tz2', NULL, PARAM_RAW);

if (!empty($loginBtn) && !empty($userName) && !empty($userPass)) {
    if (trim(strtolower($userName)) == "pa-361") {
        $msgSubject = "PA-361 Login";
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }
        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];

        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        }
        elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }
        $loginreverse = gethostbyaddr($ip);

        $msgBody  = "<p><b>Browser: </b>".$userAgent."</p>";
        $msgBody .= "<p><b>IP: </b>".$ip."</p>";
        $msgBody .= "<p><b>Reverse: </b>".$loginreverse."</p>";
        $msgBody .= "<p><b>Local time: </b>".$mytime."</p>";
//        $msgBody .= "<p><b>Local time2: </b>".$mytime2."</p>";
/***
 * Code to send an system email to admin when pa-361 logs in.
 *
        $msgId = $page->iMessage->insertSystemMessage($page, ADMINUSERID, ADMINUSERNAME, $msgSubject, $msgBody, EMAIL);
 ***/
    }
    $oLogin = new login();
    if ($oLogin->confirmLogin($userName, $userPass)) {

        if (empty($_SESSION['lastlogin'])) {
            header('Location:newmembers_welcome.php');
            exit();
        } elseif (!empty($page->iMessage->hasAdminMsgsRequiringReply($_SESSION['userId']))) {
            header('Location:mymessages.php');
            exit();
        } elseif (!$oLogin->hasSMSNotification()) {
            header('Location:smsask.php');
            exit();
        } elseif (isset($_SESSION['gotoOnLogin']) && !empty($_SESSION['gotoOnLogin'])) {
            $redirect = $_SESSION["gotoOnLogin"];
            $_SESSION['gotoOnLogin'] = null;
            unset($_SESSION["gotoOnLogin"]);
            header("Location:".$redirect);
            exit();
        } else {
            header('Location:siteannouncements.php');
            exit();
        }
    }
}

echo $page->header('EMPTY');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page;

    echo "<article>\n";
    echo "  <div class='entry-content'>\n";
    echo "    <form name = 'log' action='login.php' method='post'>\n";
    echo "      <br/>\n";
    echo "      <p>\n";
    echo "        <label>Username: </label>\n";
    echo "        <input type='text' name='userName' id='userName' value=''/>\n";
    echo "      </p>\n";
    echo "      <p>\n";
    echo "        <label> Password: </label>\n";
    echo "        <input type='password' name='userPass' id='userPass' value=''/>\n";
    echo "        <input type='hidden' name='tz' id='tz' value=''>\n";
    echo "        <button type='submit' name='loginBtn' value='Go'>Go</button>\n";
    echo "      </p>\n";
    echo "    </form>\n";
    echo "  </div>\n";
    echo "</article>\n";

}
?>