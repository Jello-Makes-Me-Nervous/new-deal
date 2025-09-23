<?php
require_once('template.class.php');

$page = new template(LOGIN, SHOWMSG);

echo $page->header('EMPTY');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page;

    echo "<article>\n";//////////////////////////////
    echo "  <div class='entry-content'>\n";///////////////////
    echo "EMPTY\n";
    echo $page->user->isSuperAdmin();
    //echo " ".$page->user->dumpUser()."\n";
    echo "  </div>\n";//////////////////////////////////////
    echo "</article>\n";///////////////////////////////////

}

?>