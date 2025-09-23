<?php
require_once("paginator.class.php");
require_once('templateAdmin.class.php');
require_once('templateBlank.class.php');

$printpreview   = optional_param('print', 0, PARAM_INT);
if (empty($printpreview)) {
    $page = new templateAdmin(LOGIN, SHOWMSG);
} else {
    $page = new templateBlank(LOGIN, SHOWMSG);
}

$searchstring   = optional_param('searchstring', NULL, PARAM_RAW);
$fdate          = optional_param('fromdate', date("m/d/Y"), PARAM_RAW);
$tdate          = optional_param('todate', date("m/d/Y"), PARAM_RAW);
$export         = optional_param('export', 0, PARAM_INT);

$fromdate = strtotime($fdate);
$todate = strtotime($tdate);

if (!empty($export)) {
    $x = export($searchstring, $fromdate, $todate);
    if (empty($x)) {
        $page->messages->addErrorMsg("ERROR: Unable to export login information");
    }
}

$calendarJS = '
    $(function(){$("#fromdate").datepicker();});
    $(function(){$("#todate").datepicker();});
';
$page->jsInit($calendarJS);

$page->header('Dealernet Login Report');
if (empty($printpreview)) {
    echo mainContent();
} else {
    printpreview($searchstring, $fromdate, $todate);
}
$page->footer(true);

function mainContent() {
    global $page, $searchstring, $fromdate, $todate, $fdate, $tdate;

    $data = getData($searchstring, $fromdate, $todate);
    echo "<h3>Dealernet Login Report</h3>\n";
    echo "<form name='search' id='search' method='post' action='".$_SERVER['PHP_SELF']."'>\n";
    echo "  <input type='hidden' name='export' id='export' value=''>\n";
    echo "  <input type='hidden' name='print' id='print' value=''>\n";
    echo "  <table>\n";
    echo "    <tbody>\n";
    echo "      <tr>\n";
    echo "        <td>Dealer</td>\n";
    echo "        <td><input type='textbox' name='searchstring' value='".$searchstring."'></td>\n";
    echo "        <td>From Date</td>\n";
    echo "        <td><input type='textbox' name='fromdate' id='fromdate' value='".$fdate."'></td>\n";
    echo "        <td>To Date</td>\n";
    echo "        <td><input type='textbox' name='todate' id='todate' value='".$tdate."'></td>\n";
    echo "        <td><input type='submit' name='searchbtn' value='Search' onclick='document.search.target=\"_self\"; document.search.export.value=\"\"; document.search.print.value=\"\"; '></td>\n";
    echo "      </tr>\n";
    echo "    </tbody>\n";
    echo "  </table>\n";
    echo "  <div>&nbsp;</div>\n";
    echo "  <table>\n";
    echo "        <caption>\n";
    echo "          <a href='Javascript: document.search.target=\"_blank\"; document.search.print.value=\"1\"; document.search.submit();'v class='icon print'>Print</a>&nbsp;&nbsp;\n";
    echo "          <a href='Javascript: document.search.target=\"_self\"; document.search.export.value=\"1\"; document.search.submit();' class='icon export'>Export</a>\n";
    echo "        </caption>\n";
    echo "    <thead>\n";
    echo "      <tr>\n";
    echo "        <th scope='col' colspan='2'>Dealer</th>\n";
    echo "        <th scope='col'>Login Date</th>\n";
    echo "        <th scope='col'>Browser</th>\n";
    echo "        <th scope='col'>IP Address</th>\n";
    echo "        <th scope='col'>Network</th>\n";
    echo "      </tr>";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    if ((!empty($fromdate) && empty($todate)) ||
        (empty($fromdate) && !empty($todate))) {
        echo "      <tr><td colspan='6'><p class='errormsg'>ERROR: Must provide both a from and to date.</p></td></tr>\n";
    } else {
        if (empty($data)) {
            echo "      <tr><td colspan='6'><p class='warningmsg'>No logins found.</p></td></tr>\n";
        } else {
            $x = 0;
            foreach($data as $d) {
                $x++;
                echo "      <tr>\n";
                echo "        <td data-label='#'>".$x."</td>\n";
                echo "        <td data-label='Dealer'>".$d["username"]."</td>\n";
                echo "        <td data-label='Login Date' class='date'>\n";
                echo "          ".date('F j, Y h:i:sA', $d["logindate"])."\n";
                echo "        </td>\n";
                echo "        <td data-label='Login Browser'>".$d["loginbrowser"]."</td>\n";
                echo "        <td data-label='Ip Address'>".$d["ipaddress"]."</td>\n";
                echo "        <td data-label='Network'>".$d["loginreverse"]."</td>\n";
                echo "      </tr>\n";
            }
        }
    }
    echo "    </tbody>\n";
    echo "  </table>\n";
    echo "</form>\n";
}

function getData($dealername=NULL, $fromdate=NULL, $todate=NULL) {
    global $page;

    $dealer = (empty($dealername))  ? "" : "AND lower(u.username) like  lower('%".$dealername."%')";
    $fdate  = (empty($fromdate))    ? "todaytoint()" : "startdatetime(".$fromdate.")";
    $tdate  = (empty($todate))    ? "enddatetime(todaytoint())" : "enddatetime(".$todate.")";

    $sql = "
        SELECT u.userid, u.username,
               ll.logindate, ll.loginbrowser, ll.ipaddress, ll.loginreverse
          FROM loginlog     ll
          JOIN users        u   ON  u.userid    = ll.userid
         WHERE logindate BETWEEN ".$fdate." AND ".$tdate."
           ".$dealer."
        ORDER BY u.username, ll.logindate DESC
    ";

//    echo "<pre>".$sql."</pre>";
    $result = $page->db->sql_query_params($sql);

    return $result;
}

function export($searchstring, $fromdate, $todate) {
    global $page;

    $data = getData($searchstring, $fromdate, $todate);
    $filename = "";
    if (!empty($data)) {
        $filename = date('Ymd_His')."_loginreport.csv";
        $page->utility->export($data, $filename);
    }

    return $filename;
}

function printpreview($searchstring, $fromdate, $todate) {
    global $page;

    echo "<h3>Dealernet Login Report</h3>\n";
    $data = getData($searchstring, $fromdate, $todate);
    if (!empty($data)) {
        $page->utility->printpreview($data);
    }
}

?>