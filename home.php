<?php
require_once('templateHome.class.php');

$page = new templateHome(NOLOGIN, SHOWMSG);
$page->jsInit("getMyTimezone();");
$js = "
    <!-- Google tag (gtag.js) -->
    <script async src='https://www.googletagmanager.com/gtag/js?id=G-HGTH2XK15V'></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){
        dataLayer.push(arguments);
      }
      gtag('js', new Date());
      gtag('config', 'G-HGTH2XK15V');
    </script>
      ";

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
$filename = $directory."homepage.inc";
if (file_exists($filename)) {
    try {
        $fp = fopen($filename,'r');
        $content = fread($fp, filesize($filename));
        fclose($fp);
    } catch (Exception $e) {
        $page->messages->addErrorMsg("Error: ".$e->getMessage()." [Reading file]");
        $content = null;
    } finally {
    }
}

echo $page->header('Dealernet');
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