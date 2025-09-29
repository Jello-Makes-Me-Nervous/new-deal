<?php
require_once('template.class.php');

$page = new template(LOGIN, SHOWMSG);

$proxiedId  = optional_param('proxiedId', NULL, PARAM_INT);
$realUserId = optional_param('realUserId', NULL, PARAM_INT);
$doUnproxy = optional_param('unproxy', NULL, PARAM_INT);

if (!empty($proxiedId)) {
    $USER->proxy($proxiedId);
}
if ($doUnproxy) {
    $USER->reverseProxy();
}

echo $page->header('Proxy');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page;

    echo "Current user: ".$page->user->username."(".$page->user->userId.")<br />\n";

}
?>