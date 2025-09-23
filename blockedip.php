<?php
require_once('templateHome.class.php');

$page = new templateHome(NOLOGIN, SHOWMSG, REDIRECTSAFE);

if (isset($CFG->adminmulti)) {
    $directory  = $CFG->adminmulti;
} else {
    $directory  = $CFG->dataroot."adminmulti/";
}
$client  = @$_SERVER['HTTP_CLIENT_IP'];
$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
$remote  = $_SERVER['REMOTE_ADDR'];

$ip = null;
if (filter_var($client, FILTER_VALIDATE_IP)) {
    $ip = $client;
} elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
    $ip = $forward;
} else {
    $ip = $remote;
}

$instructions  = "";
if (!empty($ip)) {
    $instructions  = "<P><b>IP Address: ".$ip."</b></P>";
}
$link = "<a href='/contactus_nologin.php'>here</a>";
$instructions .= "<b>DealernetX has blocked access. If you think this is in error, please contact admin by sending a message ".$link.".</b><br>";

$content = "<div style='margin:5px; padding:5px; border:1px solid #000; background-color:#EEE;'>".$instructions."</div>\n";

$filename = $directory."blockedip.inc";
if (file_exists($filename)) {
    try {
        $fp = fopen($filename,'r');
        $content = fread($fp, filesize($filename));
        fclose($fp);
    } catch (Exception $e) {
        $page->messages->addErrorMsg("Error: ".$e->getMessage()." [Reading file]");
        $content = "<div style='margin:5px; padding:5px; border:1px solid #000; background-color:#EEE;'>".$instructions."</div>\n";
    } finally {
    }
}

echo $page->header('Blocked IP Address');
echo mainContent();
echo $page->footer();

function mainContent() {
    global $content;

    echo "            <article>\n";
    echo "              <div class='entry-content'>\n";
    echo $content;
    echo "              </div> <!-- entry-content -->\n";
    echo "            </article>\n";
}


?>