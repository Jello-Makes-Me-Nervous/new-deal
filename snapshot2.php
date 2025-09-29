<?php
require_once('templateMarket.class.php');

$page = new templateMarket(LOGIN, SHOWMSG);

$dealerId  = optional_param('dealerid', NULL, PARAM_INT);
$blasterId  = optional_param('blasterid', NULL, PARAM_INT);

echo $page->header($pagetitle);
echo mainContent();
echo $page->footer(true);

function mainContent() {
    displayFilter();
    displayListings();
}

function displayFilter() {
}

function displayListings() {
}

?>