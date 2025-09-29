<?php
include('setup.php');

//if admin user.class proxy and reverse
$proxiedId  = optional_param('proxiedId', NULL, PARAM_INT);
$realUserId = optional_param('realUserId', NULL, PARAM_INT);
$btnReverse = optional_param('btnReverse', NULL, PARAM_INT);

if (!empty($proxiedId)) {
    $USER->proxy($proxiedId);
}
if (isset($btnReverse)) {
    $USER->reverseProxy();
}
/*
echo "<form name ='sub' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
echo "  <select name='proxiedId' onChange=\"javascript: this.form.submit();\">\n";
echo "    <option value=''> SELECT </option>\n";
echo "    <option value='6'> FRED </option>\n";
echo "  </select>\n";
echo "</form>\n";
*/

if (isset($_SESSION['realUserId'])) {
    echo "<a href='?btnReverse=1'>Back</a>\n";
}


?>