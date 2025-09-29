<?php
require_once('templateAdmin.class.php');
$page = new templateAdmin(LOGIN, SHOWMSG);

$searchstring   = optional_param('searchstring', NULL, PARAM_RAW);
$sortby         = optional_param('sortby', "dealer", PARAM_RAW);

echo $page->header('Last Login Report');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $searchstring, $sortby;

    echo "<h3>Dealernet Login Report</h3>\n";
    echo "<article>\n";
    echo "  <div class='entry-content'>\n";
    echo "    <form name='search' id='search' method='post' action='".$_SERVER['PHP_SELF']."'>\n";
    echo "      <input type='hidden' name='export' id='export' value=''>\n";
    echo "      <input type='hidden' name='print' id='print' value=''>\n";
    echo "      <table>\n";
    echo "        <tbody>\n";
    echo "          <tr>\n";
    echo "            <td>\n";
    echo "              <label for='dealer'>Dealer</label><br>\n";
    echo "              <input type='text' name='searchstring' id='searchstring' value='".$searchstring."' class='input' style='width:250px;'>\n";
    echo "            </td>\n";
    echo "            <td>\n";
    echo "              <label for='sortby'>Sort By:</label><br>\n";
    $checked = (empty($sortby) || $sortby == "dealer") ? "CHECKED" : "";
    echo "              <div style='width: 75px;display:inline;white-space: nowrap;'>\n";
    echo "                &nbsp;&nbsp;<input type='radio' name='sortby' id='sortby' value='dealer' class='input' ".$checked.">\n";
    echo "                <label for='type'>Dealer</label>\n";
    echo "              </div>\n";
    $checked = ($sortby == "date") ? "CHECKED" : "";
    echo "              <div style='width: 75px;display:inline;white-space: nowrap;'>\n";
    echo "                &nbsp;&nbsp;<input type='radio' name='sortby' id='sortby' value='date' class='input' ".$checked.">\n";
    echo "                <label for='type'>Date</label>\n";
    echo "              </div>\n";
    echo "            </td>\n";
    echo "            <td><input type='submit' name='searchbtn' value='Search' onclick='document.search.target=\"_self\"; document.search.export.value=\"\"; document.search.print.value=\"\"; '></td>\n";
    echo "          </tr>\n";
    echo "        </tbody>\n";
    echo "      </table>\n";
    echo "    </form>\n";
    echo "    <div>&nbsp;</div>\n";
    echo "    <table border='1'>\n";
    echo "      <thead>\n";
    echo "        <tr>\n";
    echo "          <th>User</th>\n";
    echo "          <th>Date</th>\n";
    echo "        </tr>\n";
    echo "      </thead>\n";
    echo "      <tbody>\n";
    $data = getLastLog();
    $i = 1;
    foreach ($data as $d) {
        echo "        <tr>\n";
        echo "          <td>[".$i."]&nbsp;&nbsp;".$d['username']."</td>\n";
        echo "          <td>".$d['day']."</td>\n";
        echo "        </tr>\n";
        $i++;
    }
    echo "      </tbody>\n";
    echo "    </table>\n";
    echo "  </div>\n";
    echo "</article>\n";
    echo "<br />\n";

}


function getLastLog() {
    global $page, $searchstring, $sortby;

    $result = array();

    $keyword    = "";
    if (!empty($searchstring)) {
        $searchstring = strtolower(trim($searchstring));
        $keyword = "AND (strpos(lower(u.username), '".$searchstring."') > 0)";
    }

    SWITCH ($sortby) {
        CASE "date":   $sortby = "2 DESC";
                    break;
        DEFAULT:    $sortby = "u.username";
    }
    $sql = "
        SELECT u.username, MAX(inttodatetime(l.logindate)) as day
          FROM loginlog         l
          JOIN users            u   ON  l.userid        = u.userid
          JOIN assignedrights   ar  ON  ar.userid       = u.userid
                                    AND ar.userrightid  = 1 -- enabled
          WHERE 1 = 1
            ".$keyword."
         GROUP BY u.username
         ORDER BY ".$sortby."
    ";

    $result = $page->db->sql_query_params($sql);

    return $result;

}

?>