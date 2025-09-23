<?php
require_once('templateMarket.class.php');

$page = new templateMarket(LOGIN, SHOWMSG);
$page->display_BottomWidget = false;

echo $page->header('GTS');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $listingPage;

    echo "            <article>\n";
    echo "              <div class='entry-content'>\n";
    echo "                <iframe style='width: 100%; height: 100vh; border: none' src='https://gogts.net/gts-distribution-product-release-calendar/'></iframe>\n";
    echo "              </div>\n";
    echo "            </article>\n";
}

?>