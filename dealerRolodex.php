<?php
include_once('setup.php');
require_once('templateStaff.class.php');
require_once("paginator.class.php");

$page = new templateStaff(LOGIN, SHOWMSG);

$city           = strtolower(optional_param('city', NULL, PARAM_TEXT));
$companyName    = strtolower(optional_param('companyName', NULL, PARAM_TEXT));
$dealerName     = strtolower(optional_param('dealerName', NULL, PARAM_TEXT));
$email          = strtolower(optional_param('email', NULL, PARAM_TEXT));
$lastName       = strtolower(optional_param('lastName', NULL, PARAM_TEXT));
$phone          = strtolower(optional_param('phone', NULL, PARAM_TEXT));
$search         = strtolower(optional_param('search', NULL, PARAM_TEXT));
$state          = strtolower(optional_param('state', NULL, PARAM_TEXT));
$street         = strtolower(optional_param('street', NULL, PARAM_TEXT));
$zip            = strtolower(optional_param('zip', NULL, PARAM_TEXT));
$vacationType   = optional_param('onvacation', NULL, PARAM_TEXT);
$x              = optional_param('x', NULL, PARAM_INT);
$go             = optional_param('go', NULL, PARAM_RAW);
$pagenum        = optional_param('page', 1, PARAM_INT);
$perpage        = optional_param('perpage', 25, PARAM_INT);

$totalRows = getCount();

echo $page->header('Dealer Rolodex');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $UTILITY;
    global $search, $pagenum, $perpage, $count, $totalpages,
           $dealerName, $lastName, $companyName, $street, $email,
           $phone, $city, $state, $zip,
           $vacationType, $x, $go, $totalRows;

    echo "<form class='filters' name='search' action='".htmlentities($_SERVER['PHP_SELF'])."#go' method='post'>\n";
    echo "  <div class='entry-content'>\n";
    echo "    <table class='filter-group'>\n";
    echo "      <thead>\n";
    echo "        <th colspan='5' align=left>All fields are optional.<br />All fields except the Dealer field can be partially filled in<br />e.g. typing the word \"hill\" in the city field will return all dealers that live in a city with the word \"hill\" in it<br />i.e. Hillside, Drexel Hill, Sharon Hill.</th>\n";
    echo "      </thead>\n";
    echo "      <tbody>\n";
    echo "        <tr>\n";
    echo "          <td>Dealer:<input type='text' name='dealerName' id='dealerName' value='".$dealerName."'></td>\n";
    echo "          <td>Last:<input type='text' name='lastName' id='lastName' value='".$lastName."'></td>\n";
    echo "          <td>Company:<input type='text companyName' name='companyName' id='' value='".$companyName."'></td>\n";
    echo "          <td>&nbsp;</td>\n";
    echo "        </tr>\n";
    echo "        <tr>\n";
    echo "          <td>Address:<input type='text' name='street' id='street' value='".$street."'></td>\n";
    echo "          <td>City:<input type='text' name='city' id='city' value='".$city."'></td>\n";
    echo "          <td>State:<input type='text' name='state' id='state' value='".$state."' size='6'></td>\n";
    echo "          <td>Zip:<input type='text' name='zip' id='zip' value='".$zip."' size='6'></td>\n";
    echo "        </tr>\n";
    echo "        <tr>\n";
    echo "          <td>Email:<input type='text' name='email' id='email' value='".$email."'></td>\n";
    echo "          <td>Phone:<input type='text' name='phone' id='phone' value='".$phone."'></td>\n";
    echo "          <td>Vacation:\n";
    echo "            <select name='onvacation' id='onvacation'>";
    echo "              <option value=''>Select</option>\n";
    //echo "              <option value='No' ".$UTILITY->selected('No', $vacationType).">No</option>\n";
    echo "              <option value='Yes' ".$UTILITY->selected('Yes', $vacationType).">Yes (Buy, Sell or Both)</option>\n";
    //echo "              <option value='Buy' ".$UTILITY->selected('Buy', $vacationType).">Buy</option>\n";
    //echo "              <option value='Sell' ".$UTILITY->selected('Sell', $vacationType).">Sell</option>\n";
    //echo "              <option value='Both' ".$UTILITY->selected('Both', $vacationType).">Both</option>\n";
    echo "            </select>\n";
    echo "          </td>\n";
    echo "          <td>&nbsp;</td>\n";
    echo "        </tr>\n";
    echo "        <tr>\n";
    echo "          <td colspan=4><input class='button' type='submit' name='go' id='go' value='Go'></td>\n";
    echo "        </tr>\n";
    echo "      </tbody>\n";
    echo "    </table>\n";
    echo "  </div>\n";
    echo "<br />\n";

    if (!empty($dealerName) || !empty($lastName) || !empty($companyName) || !empty($street) ||
        !empty($email) || !empty($phone) || !empty($city) || !empty($state) || !empty($zip) || !empty($vacationType)) {
        //$userData = getUsers($pagenum, $perpage, $dealerName, $lastName, $companyName, $street, $email, $phone, $city, $state, $zip);
        $userData = getUsers($pagenum, $perpage);
        echo "<div name='searchresults' id='searchresults' class='entry-content'>\n";
        echo "  <table class='filter-group'>\n";
        echo "    <thead>\n";
        echo "      <th>Contact</th>\n";
        echo "      <th>Company Name</th>\n";
        if ($vacationType && ($vacationType != "No")) {
            echo "      <th>Vacation</th>\n";
        }
        echo "    </thead>\n";
        echo "    <tbody>\n";
        if (isset($userData)) {
            foreach ($userData as $key) {
                echo "      <tr>\n";
                echo "        <td><a href='dealerProfile.php?dealerId=".$key['userid']."'>".$key['lastname'].", ".$key['firstname']."&nbsp;&nbsp;&nbsp;(".$key['username'].")</a></td>\n";
                echo "        <td>".$key['companyname']."</td>\n";
                if ($vacationType && ($vacationType != "No")) {
                    echo "        <td>".$key['vacationtype']." <strong>From:</strong> ".date('m/d/Y', $key['onvacation'])." <strong>To:</strong> ".date('m/d/Y', $key['returnondate'])."</td>\n";
                }

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
        echo "        <td colspan='4'>\n";
        echo "          <div class='pagination'>\n";
        if ($totalRows) {
            $pager = new Paginator($perpage, "page");
            $pager->set_total($totalRows);
            echo "            <nav role='navigation' aria-label='Pagination Navigation' class='text-filter'>\n";
            echo $pager->post_page_links("search");
            echo "\n";
            echo "            </nav>\n";
        }
        echo "          </div>\n";
        echo "        </td>\n";
        echo "      </tr>\n";
        echo "    </tfoot>\n";

        echo "  </table>\n";
        echo "</div>\n";

        echo "<br/>\n";
    } else {
        echo "No criteria selected<br />\n";
    }
    echo "</form>\n";

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

function getCount() {
    global $page, $dealerName, $lastName, $companyName, $street, $email, $phone, $city, $state, $zip, $vacationType;

    $sql ="
        SELECT count(*) as numdealers
          FROM users u
          JOIN userinfo ui ON ui.userid = u.userid
          LEFT JOIN usercontactinfo con ON con.userid = u.userid
           AND con.addresstypeid = 2
         WHERE  1 = 1
        ";
    if (!empty($dealerName)) {
        $sql .= "           AND LOWER(u.username) LIKE '%".$dealerName."%' \n";
    }
    if (!empty($lastName)) {
        $sql .= "           AND LOWER(ui.lastname) LIKE '%".$lastName."%' \n";
    }
    if (!empty($companyName)) {
        $sql .= "           AND LOWER(con.companyname) LIKE '%".$companyName."%' \n";
    }
    if (!empty($street)) {
        $sql .= "           AND LOWER(con.street) LIKE '%".$street."%' \n";
    }
    if (!empty($email)) {
        $sql .= "           AND LOWER(con.email) LIKE '%".$email."%' \n";
    }
    if (!empty($phone)) {
        $sql .= "           AND LOWER(con.phone) LIKE '%".$phone."%' \n";
    }
    if (!empty($city)) {
        $sql .= "           AND LOWER(con.city) LIKE '%".$city."%' \n";
    }
    if (!empty($state)) {
        $sql .= "           AND LOWER(con.state) LIKE '%".$state."%' \n";
    }
    if (!empty($zip)) {
        $sql .= "           AND LOWER(con.zip) LIKE '%".$zip."%' \n";
    }
    if (!empty($vacationType)) {
        switch($vacationType) {
            CASE 'Buy':
            CASE 'Sell':
            CASE 'Both':
                $sql .= " AND (ui.vacationtype = '".$vacationType."' AND ui.onvacation IS NOT NULL AND ui.onvacation <= todaytoint() AND ui.returnondate IS NOT NULL AND ui.returnondate > todaytoint())\n";
            CASE 'Yes':
                $sql .= " AND (ui.onvacation IS NOT NULL AND ui.onvacation <= todaytoint() AND ui.returnondate IS NOT NULL AND ui.returnondate > todaytoint())\n";
                BREAK;
            CASE 'No':
                $sql .= " AND (ui.onvacation IS NULL OR ui.onvacation > todaytoint() OR ui.returnondate IS NULL OR ui.returnondate < todaytoint())\n";
                BREAK;
        }
    }

    $numdealers = $page->db->get_field_query($sql);

    return $numdealers;
}

function getUsers($pagenum, $perpage) {
    global $page, $dealerName, $lastName, $companyName, $street, $email, $phone, $city, $state, $zip, $vacationType;

//               CASE WHEN (ui.onvacation IS NOT NULL AND ui.onvacation <= todaytoint() AND ui.returnondate IS NOT NULL AND ui.returnondate > todaytoint()) THEN ui.vacationtype ELSE NULL END as vacationtype

    $sql ="
        SELECT u.userid, u.username,
               con.companyname,
               ui.firstname, ui.lastname,
               con.companyname, con.street, con.street2, con.city, con.state, con.zip, con.phone,
               ui.vacationtype, ui.onvacation, ui.returnondate
          FROM users u
          JOIN userinfo ui ON ui.userid = u.userid
          LEFT JOIN usercontactinfo con ON con.userid = u.userid
           AND con.addresstypeid = 2
         WHERE  1 = 1
        ";
        if (!empty($dealerName)) {
            $sql .= "           AND LOWER(u.username) LIKE '%".$dealerName."%' \n";
        }
        if (!empty($lastName)) {
            $sql .= "           AND LOWER(ui.lastname) LIKE '%".$lastName."%' \n";
        }
        if (!empty($companyName)) {
            $sql .= "           AND LOWER(con.companyname) LIKE '%".$companyName."%' \n";
        }
        if (!empty($street)) {
            $sql .= "           AND LOWER(con.street) LIKE '%".$street."%' \n";
        }
        if (!empty($email)) {
            $sql .= "           AND LOWER(con.email) LIKE '%".$email."%' \n";
        }
        if (!empty($phone)) {
            $sql .= "           AND LOWER(con.phone) LIKE '%".$phone."%' \n";
        }
        if (!empty($city)) {
            $sql .= "           AND LOWER(con.city) LIKE '%".$city."%' \n";
        }
        if (!empty($state)) {
            $sql .= "           AND LOWER(con.state) LIKE '%".$state."%' \n";
        }
        if (!empty($zip)) {
            $sql .= "           AND LOWER(con.zip) LIKE '%".$zip."%' \n";
        }
        if (!empty($vacationType)) {
            switch($vacationType) {
                CASE 'Buy':
                CASE 'Sell':
                CASE 'Both':
                    $sql .= " AND (ui.vacationtype = '".$vacationType."' AND ui.onvacation IS NOT NULL AND ui.onvacation <= todaytoint() AND ui.returnondate IS NOT NULL AND ui.returnondate > todaytoint())\n";
                CASE 'Yes':
                    $sql .= " AND (ui.onvacation IS NOT NULL AND ui.onvacation <= todaytoint() AND ui.returnondate IS NOT NULL AND ui.returnondate > todaytoint())\n";
                    BREAK;
                CASE 'No':
                    $sql .= " AND (ui.onvacation IS NULL OR ui.onvacation > todaytoint() OR ui.returnondate IS NULL OR ui.returnondate < todaytoint())\n";
                    BREAK;
            }
        }

    $sql .="
        ORDER BY username
        OFFSET ".($pagenum-1)*$perpage."
         LIMIT ".$perpage;

    $find = $page->db->sql_query_params($sql);

    return $find;
}

?>