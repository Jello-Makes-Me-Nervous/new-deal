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
$userclassid    = optional_param('userclassid', 0, PARAM_INT);
$sortby         = optional_param('sortby', "total desc", PARAM_RAW);
$export         = optional_param('export', 0, PARAM_INT);

$fromdate = strtotime($fdate);
$todate = strtotime($tdate);

if (!empty($export)) {
    $x = export($searchstring, $fromdate, $todate, $userclassid, $sortby);
    if (empty($x)) {
        $page->messages->addErrorMsg("ERROR: Unable to export offer totals information");
    }
}

$calendarJS = '
    $(function(){$("#fromdate").datepicker();});
    $(function(){$("#todate").datepicker();});
';
$page->jsInit($calendarJS);

$page->header('Dealernet Offer Totals Report');
if (empty($printpreview)) {
    echo mainContent();
} else {
    printpreview($searchstring, $fromdate, $todate, $userclassid, $sortby);
}
$page->footer(true);

functiON mainContent() {
    global $page, $searchstring, $fromdate, $todate, $fdate, $tdate, $userclassid, $sortby;

    $data = getData($searchstring, $fromdate, $todate, $userclassid, $sortby);
    echo "<h3>Dealernet Offer Totals Report</h3>\n";
    echo "<form name='search' id='search' method='post' action='".$_SERVER['PHP_SELF']."'>\n";
    echo "  <input type='hidden' name='export' id='export' value=''>\n";
    echo "  <input type='hidden' name='print' id='print' value=''>\n";
    echo "  <table>\n";
    echo "    <tbody>\n";
    echo "      <tr>\n";
    echo "        <td>Dealer</td>\n";
    echo "        <td><input type='textbox' name='searchstring' value='".$searchstring."'></td>\n";
    echo "        <td>FROM Date</td>\n";
    echo "        <td><input type='textbox' name='fromdate' id='fromdate' value='".$fdate."'></td>\n";
    echo "        <td>To Date</td>\n";
    echo "        <td><input type='textbox' name='todate' id='todate' value='".$tdate."'></td>\n";
    echo "        <td>Class</td>\n";
    echo "        <td>\n";
    echo getUserClassDDM($userclassid);
    echo "        </td>\n";
    echo "        <td>\n";
    echo "          <label for='sortby'>Sort By:</label><br>\n";
    $checked = (empty($sortby) || $sortby == "dealer") ? "CHECKED" : "";
    echo "          <div style='width: 75px;display:inline;white-space: nowrap;'>\n";
    echo "            &nbsp;&nbsp;<input type='radio' name='sortby' id='sortby' value='dealer' class='input' ".$checked.">\n";
    echo "            <label for='type'>Dealer</label>\n";
    echo "          </div>\n";
    $checked = ($sortby == "total desc") ? "CHECKED" : "";
    echo "          <div style='width: 75px;display:inline;white-space: nowrap;'>\n";
    echo "            &nbsp;&nbsp;<input type='radio' name='sortby' id='sortby' value='total desc' class='input' ".$checked.">\n";
    echo "            <label for='type'>Total</label>\n";
    echo "          </div>\n";
    echo "        </td>\n";
    echo "        <td><input type='submit' name='searchbtn' value='Search' onclick='document.search.target=\"_self\"; document.search.export.value=\"\"; document.search.print.value=\"\"; '></td>\n";
    echo "      </tr>\n";
    echo "    </tbody>\n";
    echo "  </table>\n";
    echo "  <div>&nbsp;</div>\n";
    echo "  <table>\n";
    echo "        <caption>\n";
    echo "          <a href='Javascript: document.search.target=\"_blank\"; document.search.print.value=\"1\"; document.search.submit();'v class='icON print'>Print</a>&nbsp;&nbsp;\n";
    echo "          <a href='Javascript: document.search.target=\"_self\"; document.search.export.value=\"1\"; document.search.submit();' class='icON export'>Export</a>\n";
    echo "        </caption>\n";
    echo "    <thead>\n";
    echo "      <tr>\n";
    echo "        <th scope='col' colspan='2'>Dealer</th>\n";
    echo "        <th scope='col'>Class</th>\n";
    echo "        <th scope='col'>Purchases</th>\n";
    echo "        <th scope='col'>Sales</th>\n";
    echo "        <th scope='col'>Total</th>\n";
    echo "      </tr>";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    $totals = 0;
    if ((!empty($fromdate) && empty($todate)) ||
        (empty($fromdate) && !empty($todate))) {
        echo "      <tr><td colspan='6'><p class='errormsg'>ERROR: Must provide both a FROM AND to date.</p></td></tr>\n";
    } else {
        if (empty($data)) {
            echo "      <tr><td colspan='6'><p class='warningmsg'>No accepted offers found.</p></td></tr>\n";
        } else {
            $x = 0;
            foreach($data as $d) {
                $x++;
                echo "      <tr>\n";
                echo "        <td data-label='#' class='number'>".$x."</td>\n";
                echo "        <td data-label='Dealer'>".$d["dealer"]."</td>\n";
                echo "        <td data-label='Class'>".$d["userclassname"]."</td>\n";
                echo "        <td data-label='Purchases' class='number'> $ ".number_format($d["purchases"],2)."</td>\n";
                echo "        <td data-label='Sales' class='number'> $ ".number_format($d["sales"],2)."</td>\n";
                echo "        <td data-label='Total' class='number'> $ ".number_format($d["total"],2)."</td>\n";
                echo "      </tr>\n";
                $totals += $d["purchases"];
            }
        }
    }
    echo "    </tbody>\n";
    echo "    <tfoot>\n";
    echo "      <tr>\n";
    echo "        <td colspan='3'>&nbsp;</td>\n";
    echo "        <td class='number'><b> $ ".number_format($totals,2)."</b></td>\n";
    echo "        <td colspan='2'>&nbsp;</td>\n";
    echo "      </tr>\n";
    echo "    </foot>\n";
    echo "  </table>\n";
    echo "</form>\n";
}

functiON getUserClassData() {
    global $page;

    $sql = "
        SELECT userclassid, userclassname
          FROM userclass
        ORDER BY userclassname
    ";

    $result = $page->db->sql_query_params($sql);

    return $result;
}

functiON getUserClassDDM($selectedid) {

    $data = getUserClassData();

    return getSelectDDM($data, "userclassid", "userclassid", "userclassname", NULL, $selectedid, "All Classes", 0);
}

functiON getData($dealername=NULL, $fromdate=NULL, $todate=NULL, $userclassid=NULL, $sortby='total desc') {
    global $page;

    $dealer = (empty($dealername))  ? "" : "AND lower(u.username) like  lower('%".$dealername."%')";
    $fdate  = (empty($fromdate))    ? "todaytoint()" : "startdatetime(".$fromdate.")";
    $tdate  = (empty($todate))      ? "enddatetime(todaytoint())" : "enddatetime(".$todate.")";
    $ucid   = (empty($userclassid)) ? "1,2,3,4,5" : $userclassid;

    $sql = "
        SELECT userid, dealer, userclassname, sum(purchase) as purchases, sum(sale) as sales,
               sum(purchase) + sum(sale) as total
        FROM (
          SELECT u.userid, u.username as dealer, uc.userclassname, sum(o.offerdsubtotal) as purchase, 0 as sale
            FROM offers         o
            JOIN users          u   ON u.userid         = o.offerto
            JOIN userinfo       ui  ON ui.userid        = u.userid
            JOIN userclass      uc  ON uc.userclassid   = ui.userclassid
           WHERE o.offerstatus in ('ACCEPTED', 'ARCHIVED')
             AND o.transactiontype = 'Wanted'
             AND uc.userclassid IN (".$ucid.")
             AND o.createdate BETWEEN ".$fdate." AND ".$tdate."
           ".$dealer."
          GROUP BY u.userid, u.username, uc.userclassname

          UNION
          SELECT u.userid, u.username as dealer, uc.userclassname, sum(o.offerdsubtotal) as purchase, 0 as sale
            FROM offers         o
            JOIN users          u   ON u.userid         = o.offerfrom
            JOIN userinfo       ui  ON ui.userid        = u.userid
            JOIN userclass      uc  ON uc.userclassid   = ui.userclassid
           WHERE o.offerstatus in ('ACCEPTED', 'ARCHIVED')
             AND o.transactiontype = 'For Sale'
             AND uc.userclassid IN (".$ucid.")
             AND o.createdate BETWEEN ".$fdate." AND ".$tdate."
           ".$dealer."
          GROUP BY u.userid, u.username, uc.userclassname

          UNION
          SELECT u.userid, u.username as dealer, uc.userclassname, 0 as purchase, sum(o.offerdsubtotal) as sale
            FROM offers   o
            JOIN users    u ON u.userid = o.offerto
            JOIN userinfo   ui  ON ui.userid = u.userid
            JOIN userclass   uc  ON uc.userclassid = ui.userclassid
           WHERE o.offerstatus in ('ACCEPTED', 'ARCHIVED')
             AND o.transactiontype = 'For Sale'
             AND uc.userclassid IN (".$ucid.")
             AND o.createdate BETWEEN ".$fdate." AND ".$tdate."
           ".$dealer."
          GROUP BY u.userid, u.username, uc.userclassname

          UNION
          SELECT u.userid, u.username as dealer, uc.userclassname, 0 as purchase, sum(o.offerdsubtotal) as sale
            FROM offers   o
            JOIN users    u ON u.userid = o.offerfrom
            JOIN userinfo   ui  ON ui.userid = u.userid
            JOIN userclass   uc  ON uc.userclassid = ui.userclassid
           WHERE o.offerstatus in ('ACCEPTED', 'ARCHIVED')
             AND o.transactiontype = 'Wanted'
             AND uc.userclassid IN (".$ucid.")
             AND o.createdate BETWEEN ".$fdate." AND ".$tdate."
           ".$dealer."
          GROUP BY u.userid, u.username, uc.userclassname
                ) x
        GROUP BY userid, dealer, userclassname
        ORDER BY ".$sortby."
    ";

//  echo "<pre>".$sql."</pre>";
    $result = $page->db->sql_query_params($sql);

    return $result;
}

functiON export($searchstring, $fromdate, $todate, $userclassid, $sortby) {
    global $page;

    $data = getData($searchstring, $fromdate, $todate, $userclassid, $sortby);
    $filename = "";
    if (!empty($data)) {
        $filename = date('Ymd_His')."_offertotalsreport.csv";
        $page->utility->export($data, $filename);
    }

    return $filename;
}

functiON printpreview($searchstring, $fromdate, $todate, $userclassid, $sortby) {
    global $page;

    echo "<h3>Dealernet Offer Totals Report</h3>\n";
    $data = getData($searchstring, $fromdate, $todate, $userclassid, $sortby);
    if (!empty($data)) {
        $page->utility->printpreview($data);
    }
}

?>