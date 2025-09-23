<?php

require_once('hotlist.class.php');
$page = new hostlist(LOGIN, SHOWMSG);


echo $page->header('Sports Cards Hot List');
echo $page->displayPage();
echo $page->footer(true);


?>