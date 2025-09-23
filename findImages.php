<?php
require_once('template.class.php');

$page = new template(LOGIN, SHOWMSG, PRIMARYNAV);
//$page->requireJS('scripts/');
$iMessaging = new internalMessage();

//$ = optional_param('', NULL, PARAM_);

echo $page->header('');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $UTILITY;

    echo "<img src='viewImage.php?i=freddy.jpg'>\n";
}

?>