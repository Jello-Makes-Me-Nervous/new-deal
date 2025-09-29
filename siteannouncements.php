<?php
require_once('templateHome.class.php');

$page = new templateHome(LOGIN, SHOWMSG, REDIRECTSAFE);

if (! $page->verifyPaymentMethods()) {
    header('Location:dealerProfile.php');
    exit();
}

if (isset($CFG->adminmulti)) {
    $directory  = $CFG->adminmulti;
} else {
    $directory  = $CFG->dataroot."adminmulti/";
}

$content = null;
$filename = $directory."siteannouncements.inc";
if (file_exists($filename)) {
    try {
        $fp = fopen($filename,'r');
        $content = fread($fp, filesize($filename));
        fclose($fp);
    } catch (Exception $e) {
        $page->messages->addErrorMsg("Error: ".$e->getMessage()." [Reading file]");
        $pagecontent = null;
    } finally {
    }
}

echo $page->header('Site Announcements');
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