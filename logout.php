<?php
require_once('template.class.php');

$page = new template(LOGIN, SHOWMSG, REDIRECTSAFE);
$page->bypassMustReplyCheck = true;

$oLogin = new login();
$oLogin->logout();
unset($oLogin);
header('Location: home.php');
exit();

echo $page->header('Logout');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    echo "<article>\n";
    echo "  <div class='entry-content'>\n";
    echo "    You have been logged out.";
    echo "  </div>\n";
    echo "</article>\n";
}

?>