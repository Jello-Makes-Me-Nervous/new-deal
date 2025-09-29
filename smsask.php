<?php
require_once('templateHome.class.php');

$page = new templateHome(LOGIN, SHOWMSG, REDIRECTSAFE);

if (isset($CFG->adminmulti)) {
    $directory  = $CFG->adminmulti;
} else {
    $directory  = $CFG->dataroot."adminmulti/";
}

$instructions  = "<b>DealernetX requires you to provide a mobile number to receive notifications (daily activity reminder).</b><br>";
$instructions .= " These external notifications are triggered by any of the following:\n";
$instructions .= " <ul>\n";
$instructions .= "   <li>An offer you are involved in is updated</li>\n";
$instructions .= "   <li>An EFT payment was received</li>\n";
$instructions .= "   <li>There is correspondence between you and another member or the site administrators.</li>\n";
$instructions .= "   <li>A Price Alert triggered.</li>\n";
$instructions .= " </ul>\n";
$link = "<a href='/notificationPreferences.php?dealerId=".$page->user->userId."'>here</a>";
$instructions .= "<p><b>You can setup these notifications by going to Account > Profile > Notifications or by clicking ".$link.".</b></p>";

$content = "<div style='margin:5px; padding:5px; border:1px solid #000; background-color:#EEE;'>".$instructions."</div>\n";

$filename = $directory."smsask.inc";
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

echo $page->header('SMS Notifications');
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