<?php
require_once('template.class.php');

$page = new template(LOGIN, SHOWMSG);
$ip = optional_param('ip', NULL, PARAM_TEXT);

echo $page->header('Home');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page;

    echo "<article>\n";//////////////////////////////
    echo "  <div class='entry-content'>\n";///////////////////
    echo "This IP - ".$ip."has been blocked. Contact Admin for details.\n";
    echo $page->user->userId;
    echo "  </div>\n";//////////////////////////////////////
    echo "</article>\n";///////////////////////////////////

}

?>