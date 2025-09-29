<?php
include_once('setup.php');

$messages = new Messages();

$page  = optional_param('page', NULL, PARAM_INT);
$search  = optional_param('search', NULL, PARAM_TEXT);

!empty($search) ? $rowsperpage = 2 : $rowsperpage = 25;

$count = getCount($search);
$totalpages = ceil($count / $rowsperpage);
if (!isset($page)) {
    $page = 1;
}
if ($page > 1) {
    $start = ($page * $rowsperpage) - $rowsperpage;
} else {
    $start = 0;
}


echo "<div style=' width: 80%; margin: auto;'>\n";
echo     getAlpha();
echo "</div>\n";
echo "<br />";
echo "<div style='clear: left; width: 80%; margin: auto;'>\n";
echo    getDigits();
echo "</div>\n";
echo "<div style='clear: left; width: 80%; margin: auto; text-align: center;'>\n";
echo "  <a href='?'>ALL</a>\n";
echo "</div>\n";

echo "<div style='clear: left; width: 80%; margin: auto; text-align: center;'>\n";
echo "  <table>\n";
echo "    <thead>\n";
echo "      <th>ID</th>\n";
echo "      <th>Username</th>\n";
echo "      <th>Company Name</th>\n";
echo "      <th>Joined</th>\n";
echo "    </thead>\n";
echo "    <tbody>\n";
$userData = getUsers($search, $start, $rowsperpage);
if (isset($userData)) {
    foreach ($userData as $key) {
        echo "      <tr>\n";
        echo "        <td><a href='proxy.php?proxiedId=".$key['username']."'>".$key['username']."</a></td>\n";
        echo "        <td>(".$key['firstname']." ".$key['lastname'].")</td>\n";
        echo "        <td>".$key['companyname']."</td>\n";
        echo "        <td>".$key['joined']."</td>\n";
        echo "      </tr>\n";
    }
} else {
    echo "      <tr>\n";
    echo "        <td>No more Users</td>\n";
    echo "      </tr>\n";
}

echo "    </tbody>\n";
echo "  </table>\n";
echo "</div>\n";
echo "<div style='clear: left; width: 80%; padding-top: 20px; margin: auto;'>\n";
echo "<a href='?page=".($page-1)."&search=".$search."' class='button'>Previous</a>\n";
for($x = 1; $x <= $totalpages; $x++) {
    echo "<a href='?page=".$x."&search=".$search."'>".$x."</a>\n";
}
echo "<a href='?page=".($page+1)."&search=".$search."' class='button'>NEXT</a>\n";
echo "</div>\n";

function getAlpha() {
    global $DB;
    $output = "";

    $sql = "
        SELECT l.alpha, u.letter
          FROM (
            SELECT chr(generate_series(65, 90)) as alpha
            ) l
          LEFT JOIN (
            SELECT DISTINCT LEFT(upper(username), 1) AS letter
            FROM users
            ) u ON u.letter = l.alpha
         ORDER BY l.alpha
    ";
    $alphas = $DB->sql_query_params($sql);
    foreach ($alphas as $letter) {
        $output .= "<div style='float: left; padding: 3px;'>";
        if (isset($letter['letter'])) {
            $output .= "<a href='?search=".$letter['letter']."'>".$letter['alpha']."</a>";
        } else {
            $output .= $letter['alpha'];
        }
        $output .= "</div>";
    }

    return $output;
}

function getDigits() {
    global $DB;
    $output = "";

    $sql = "
        SELECT l.digit, u.numeral
          FROM (
            SELECT generate_series(0, 9)::varchar AS digit
            ) l
          LEFT JOIN (
            SELECT DISTINCT LEFT(upper(username), 1) AS numeral
            FROM users
            ) u ON u.numeral = l.digit
         ORDER BY l.digit
    ";
    $rolodigits = $DB->sql_query_params($sql);
    foreach ($rolodigits as $digits) {
        $output .= "<div style='float: left; padding: 3px;'>";
        if (isset($digits['numeral'])) {
            $output .= "<a href='?search=".$digits['numeral']."'>".$digits['digit']."</a>";
        } else {
            $output .= $digits['digit'];
        }
        $output .= "</div>";
    }

    return $output;
}

function getCount($search) {
    global $DB;

    $sql ="
        SELECT COUNT(*)
          FROM users
         WHERE username ILIKE '".$search."%'
    ";
    $totaldata = $DB->sql_query_params($sql);
    foreach ($totaldata as $key) {
        foreach ($key as $k => $count) {
            $count;
        }
    }
    return $count;
}

function getUsers($search, $start, $rowsperpage) {
    global $DB;

    $sql ="
        SELECT u.userid, u.username, inttommddyyyy_slash(u.createdate) AS joined,
               con.companyname, ui.firstname, ui.lastname
          FROM users u
          LEFT JOIN usercontactinfo con ON con.userid = u.userid
                                       AND con.addresstypeid = 2
         WHERE u.username ILIKE '".$search."%'
         ORDER BY username
         LIMIT ".$rowsperpage." OFFSET ".$start."

    ";
    $find = $DB->sql_query_params($sql);

    return $find;
}

?>