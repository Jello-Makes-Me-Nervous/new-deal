<?php
include_once('setup.php');
require_once('templateAdmin.class.php');
require_once("paginator.class.php");

$page = new templateAdmin(LOGIN, SHOWMSG);

//$pages   = optional_param('page', NULL, PARAM_INT);

$pagenum     = optional_param('page', 1, PARAM_INT);
$prevpage    = optional_param('prevpage', 0, PARAM_INT);
$search      = optional_param('search', NULL, PARAM_TEXT);
$addressRequest = optional_param('addrrequest', 0, PARAM_INT);
$eliteOnly = optional_param('eliteonly', 0, PARAM_INT);
$userclassid = optional_param('userclassid', NULL, PARAM_TEXT);
$perpage     = optional_param('perpage', NULL, PARAM_INT);
$perpage = (isset($perpage)) ? $perpage : $page->cfg->PERPAGE;

$totalRows = 0;

//$rowsperpage = empty($search) ? 10 : 25;
//$limit = 10;
$totalRows = getCount($search);
//$totalpages = ceil($count / $rowsperpage);
//if (!isset($pages)) {
//    $pages = 1;
//}
//if ($pages > 1) {
//    $start = ($pages - 1) * $rowsperpage;
//} else {
//    $start = 0;
//}

echo $page->header('Users');
echo mainContent($pagenum, $perpage);
echo $page->footer(true);

function mainContent($pagenum, $perpage) {
    global $count, $limit, $page, $rowsperpage, $search, $userclassid, $pagenum, $totalpages, $totalRows, $addressRequest, $eliteOnly;
    global $page, $UTILITY;

    echo "<div class='page-header'>\n";
    echo "  <div class='search-keys'>\n";
    echo     getAlpha();
    echo "  </div>\n";
    echo "  <div class='search-keys'>\n";
    echo     getDigits();
    echo "    <a class='key' href='userRolodex.php'>ALL</a>\n";
    echo "  </div>\n";
    echo "</div>\n";

    echo "<div class='entry-content'>\n";
    echo "  <form name = 'searchName' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo " <a href='register.php' class='button'>Register New User</a> \n";
    echo "Class: ".userClassDDM($userclassid, "All");
    echo "    <input type='text' name='search' id='search' value='".$search."' \>\n";
    echo "    Has Address Request:<input type='checkbox' id='addrrequest' name='addrrequest' value='1' ".$UTILITY->isChecked($addressRequest, 1)." />";
    echo "    Elite Only:<input type='checkbox' id='eliteonly' name='eliteonly' value='1' ".$UTILITY->isChecked($eliteOnly, 1)." />";
    echo "    <input type='submit' value='Search User'>\n";
    echo "  <table>\n";
    echo "    <thead>\n";
    echo "      <th>ID</th>\n";
    echo "      <th>Company Name</th>\n";
    echo "      <th>Joined</th>\n";
    echo "      <th>Class</th>\n";
    echo "      <th>Edit</th>\n";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    $userData = getUsers($search, $userclassid, $addressRequest, $eliteOnly, $pagenum, $perpage);
    if (isset($userData)) {
        foreach ($userData as $key) {
            $eliteUser = ($key['iselite']) ? " <span title='Elite Dealer'><i class='fas fa-star'></i></span>" : "";
            $blueStarUser = ($key['isbluestar']) ? " <span title='Above Standard Dealer'><i class='fas fa-star' style='color: #00f;'></i></span>" : "";
            $verifiedUser = ($key['isverified']) ? " <span title='Verified Dealer'><i class='fas fa-check' style='color: #090;'></i></span>" : "";
            
            echo "      <tr>\n";
            echo "        <td><a href='dealerProfile.php?dealerId=".$key['userid']."'>".$key['username']." (".$key['firstname']." ".$key['lastname'].")</a>".$eliteUser.$blueStarUser.$verifiedUser."</td>\n";
            echo "        <td>".$key['companyname']."</td>\n";
            echo "        <td>".$key['joined']."</td>\n";
            echo "        <td>".$key['userclassname']."</td>\n";
            echo "        <td class='fa-action-items'>\n";
            echo "          <a class='fas fa-edit' title='Edit' href='userUpdate.php?userId=".$key['userid']."'></a>\n";
            echo "          <a class='fas fa-credit-card' title='EFT Credit' href='EFTone.php?userId=".$key['userid']."'></a>\n";
            echo "          <a class='fas fa-user-cog' title='Rights' href='assignUserRights.php?userId=".$key['userid']."'></a>\n";
            echo "          <a class='fas fa-mask' title='Proxy' href='inProxy.php?proxiedId=".$key['userid']."'></a>\n";
            echo "        </td>\n";
            echo "      </tr>\n";
        }
    } else {
        echo "      <tr>\n";
        echo "        <td>No more Users</td>\n";
        echo "      </tr>\n";
    }

    echo "    </tbody>\n";
    echo "    <tfoot>\n";
    echo "      <tr>\n";
    echo "        <td colspan='5'>\n";
    echo "          <div class='pagination'>\n";
    $val = (empty($checked)) ? 0 : 1;
    if ($totalRows) {
        $pager = new Paginator($perpage, "page");
        $pager->set_total($totalRows);
        echo "            <nav role='navigation' aria-label='Pagination Navigation' class='text-filter'>\n";
        echo $pager->post_page_links("searchName");
        echo "\n";
        echo "            </nav>\n";
    }
    echo "          </div>\n";
    echo "        </td>\n";
    echo "      </tr>\n";
    echo "    </tfoot>\n";

    echo "  </table>\n";

   // echo $page->utility->pagination($limit, $pages, $search, $totalpages);
    echo "</div>\n";

}



function getAlpha() {
    global $page;

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
    $alphas = $page->db->sql_query_params($sql);
    foreach ($alphas as $letter) {
        if (isset($letter['letter'])) {
            $output .= "<a class='key' href='?search=".$letter['letter']."'>".$letter['alpha']."</a>";
        } else {
            $output .= "<span class='empty-key'>".$letter['alpha']."</span>\n";
        }
    }

    return $output;
}

function getDigits() {
    global $page;

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
    $rolodigits = $page->db->sql_query_params($sql);
    foreach ($rolodigits as $digits) {
        if (isset($digits['numeral'])) {
            $output .= "<a class='key' href='?search=".$digits['numeral']."'>".$digits['digit']."</a>";
        } else {
            $output .= "<span class='empty-key'>".$digits['digit']."</span>\n";
        }
    }

    return $output;
}

function getCount($search) {
    global $page;

    $sql ="
        SELECT COUNT(*)
          FROM users
          WHERE 1 = 1
    ";
    if ($search != "AllUsers") {
        $sql .= "AND username ILIKE '%".$search."%'";
    }

    $totaldata = $page->db->sql_query_params($sql);
    foreach ($totaldata as $key) {
        foreach ($key as $k => $count) {
            $count;
        }
    }
    return $count;
}

function getUsers($search, $userclassid, $addressRequest, $eliteOnly, $pagenum, $rowsperpage = NULL) {
    global $page;

    $perpage = (isset($perpage)) ? $perpage : $page->cfg->PERPAGE;
    
    $hasRequestSql = "";
    if ($addressRequest == 1) {
        $hasRequestSql = "
            JOIN (
                SELECT userid 
                FROM usercontactinfo 
                WHERE addresstypeid IN (".ADDRESS_TYPE_REQUEST_PAY.",".ADDRESS_TYPE_REQUEST_SHIP.")
                GROUP BY userid
            ) ru ON ru.userid=u.userid";        
    }
    
    $sql ="
        SELECT u.userid, u.username, inttommddyyyy_slash(u.createdate) AS joined, uc.userclassname,
               con.companyname,
               ui.firstname, ui.lastname,
               CASE WHEN ar.userrightid IS NULL THEN 0 ELSE 1 END AS iselite,
               CASE WHEN ar.userrightid IS NULL AND bar.userrightid IS NOT NULL THEN 1 ELSE 0 END AS isbluestar,
               CASE WHEN vdar.userrightid IS NULL THEN 0 ELSE 1 END AS isverified
          FROM users u ".$hasRequestSql."
          JOIN userinfo ui ON ui.userid = u.userid
          JOIN userclass uc ON uc.userclassid=ui.userclassid
          LEFT JOIN usercontactinfo con ON con.userid = u.userid
          LEFT JOIN assignedrights ar ON ar.userid=u.userid AND ar.userrightid=".USERRIGHT_ELITE."
          LEFT JOIN assignedrights bar ON bar.userid=u.userid AND bar.userrightid=".USERRIGHT_BLUESTAR."
          LEFT JOIN assignedrights vdar ON vdar.userid=u.userid AND vdar.userrightid=".USERRIGHT_ELITE."
          WHERE con.addresstypeid = 2
    ";
    if ($search != "AllUsers") {
        $sql .= "AND u.username ILIKE '%".$search."%' ";
    }

    if (!empty($userclassid)) {
        $sql .= " AND ui.userclassid='".$userclassid."' ";
    }
    
    if ($eliteOnly) {
        $sql .= " AND ar.userrightid IS NOT NULL ";
    }

    $sql .= " ORDER BY username LIMIT ".$rowsperpage;
    
    if ($pagenum > 1) {
        $offset = $rowsperpage * ($pagenum - 1);
        $sql .= " OFFSET ".$offset;
    }
    
    //echo "SQL:".$sql."<br \>\n";
    $find = $page->db->sql_query($sql);

    return $find;
}

?>