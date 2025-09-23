<?php

require_once('templateMarket.class.php');
$page = new templateMarket(LOGIN, SHOWMSG);

$hotlist    = optional_param('hotlist', NULL, PARAM_TEXT);
$kw         = optional_param('keywordsearch', NULL, PARAM_TEXT);

if (!empty($hotlist)) {
    if ($hotlist == 'g') {
        header("Location: hotlist_gaming.php?bph=1&keywordsearch=".$kw);
    } else {
        header("Location: hotlist_sports.php?bph=1&keywordsearch=".$kw);
    }
}

echo $page->header('Hot List');
echo mainContent();
echo $page->footer(true);

function mainContent() {

    echo "<h3>Hot List</h3>\n";
    echo "<article>\n";
    echo "  <div class='entry-content'>\n";
    echo "    <form name ='hotlist' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "      <div style='width: 75px;display:inline;white-space: nowrap;'>\n";
    echo "      &nbsp;&nbsp;<input type='radio' name='hotlist' id='hotlistG' value='g' class='input' onclick='JavaScript: this.form.submit();'>\n";
    echo "      <label for='hotlistG'>Gaming</label>\n";
    echo "      <div style='width: 75px;display:inline;white-space: nowrap;'>\n";
    echo "      &nbsp;&nbsp;<input type='radio' name='hotlist' id='hotlistS' value='s' class='input' onclick='JavaScript: this.form.submit();'>\n";
    echo "      <label for='hotlistG'>Sports</label>\n";
    echo "      </div><br>\n";
    echo "    </form>\n";
    echo "  </div>\n";
    echo "</article>\n";
    echo "<div style='height:100px;'></div>\n";
}


?>