<?php
require_once('templateMarket.class.php');

$page = new templateMarket(LOGIN, SHOWMSG);

echo $page->header('Marketplace Template Test Page');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    echo "<B>Page Content</B>\n";
}
?>