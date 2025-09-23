<?php
require_once("paginator.class.php");
require_once('templateWithSideBars.class.php');

$page = new templateWithSideBars(LOGIN, SHOWMSG);

$pagenum              = optional_param('page', 1, PARAM_INT);
$prevpage             = optional_param('prevpage', 0, PARAM_INT);
$searchstring         = optional_param('searchstring', NULL, PARAM_RAW);
$fdate                = optional_param('fromdate', NULL, PARAM_RAW);
$tdate                = optional_param('todate', NULL, PARAM_RAW);
$perpage              = optional_param('perpage', NULL, PARAM_INT);
$perpage = (isset($perpage)) ? $perpage : $page->cfg->PERPAGE;

$fdate = (empty($fdate)) ? date("m/d/Y", strtotime('last month')) : $fdate;
$tdate = (empty($tdate)) ? date("m/d/Y") : $tdate;

$fromdate = strtotime($fdate);
$todate = strtotime($tdate);
$totalRows = 0;

$calendarJS = '
    $(function(){$("#fromdate").datepicker();});
    $(function(){$("#todate").datepicker();});
';
$page->jsInit($calendarJS);

$page->header('EFT Totals Report');
mainContent($pagenum, $perpage);
$page->footer(true);

function mainContent($pagenum, $perpage) {
    global $page;
    global $totalRows, $searchstring, $fromdate, $todate, $fdate, $tdate;

    echo "<h3>EFT Totals Report</h3>\n";
    echo "<form name='search' id='search' method='post' action='".$_SERVER['PHP_SELF']."'>\n";
    echo "  <table>\n";
    echo "    <tbody>\n";
    echo "      <tr>\n";
    echo "        <td>Member</td>\n";
    echo "        <td><input type='textbox' name='searchstring' value='".$searchstring."'></td>\n";
    echo "        <td>From Date</td>\n";
    echo "        <td><input type='textbox' name='fromdate' id='fromdate' value='".$fdate."'></td>\n";
    echo "        <td>To Date</td>\n";
    echo "        <td><input type='textbox' name='todate' id='todate' value='".$tdate."'></td>\n";
    echo "        <td><input type='submit' name='searchbtn' value='Search'></td>\n";
    echo "      </tr>\n";
    echo "    </tbody>\n";
    echo "  </table>\n";
    echo "  <div>&nbsp;</div>\n";
    echo "  <table>\n";
    echo "    <thead>\n";
    echo "      <tr>\n";
    echo "        <th scope='col'>Date</th>\n";
    echo "        <th scope='col'>Type</th>\n";
    echo "        <th scope='col'>Amount</th>\n";
    echo "        <th scope='col'>Description</th>\n";
    echo "      </tr>";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    if ((!empty($fromdate) && empty($todate)) ||
        (empty($fromdate) && !empty($todate))) {
        echo "      <tr><td colspan='4'><p class='errormsg'>ERROR: Must provide both a from and to date.</p></td></tr>\n";
    } else {
        if (!empty($searchstring)) {
            $transactions = getData($searchstring, $fromdate, $todate);
            if (empty($transactions)) {
                echo "      <tr><td colspan='4'><p class='warningmsg'>No transactions found.</p></td></tr>\n";
            } else {
echo "</pre>";
print_r($transactions);
echo "</pre>";
                foreach ($transactions as $t) {
                    echo "      <tr>\n";
                    echo "        <td data-label='Date'>".date('m/d/Y', $t['transdate'])."</td>\n";
                    echo "        <td data-label='Type'>".$t['transtype']."</td>\n";
                    echo "        <td data-label='Amount'>".$t['dgrossamount']."</td>\n";
                    echo "        <td data-label='Description'>".$t['transdesc']."</td>\n";
                    echo "      </tr>\n";
                }
            }
        }
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
        echo $pager->post_page_links("efttotalsrpt");
        echo "\n";
        echo "            </nav>\n";
    }
    echo "          </div>\n";
    echo "        </td>\n";
    echo "      </tr>\n";
    echo "    </tfoot>\n";
    echo "  </table>\n";
    echo "</form>\n";
}

function getData($searchstring, $fromdate, $todate){
    global $page;

    $memberid = $page->utility->getDealerId($searchstring);

    $sql = "
        SELECT t.transactionid, t.transdate, t.transtype, t.dgrossamount,
               t.refaccountid, t.useraccountid, t.transdesc
          FROM transactions         t
          JOIN users                u   ON  u.userid    = t.refaccountid
          JOIN users                u2  ON  u2.userid   = t.useraccountid
         WHERE t.dgrossamount > 0
           AND t.transdate BETWEEN startdatetime(".$fromdate.") AND enddatetime(".$todate.")
           AND (   (u.userid = ".$memberid." AND t.useraccountid = ".$page->user->userId.")
                OR (u2.userid = ".$memberid." AND t.refaccountid = ".$page->user->userId."))
       UNION
        SELECT t.transactionid, t.transdate, t.transtype, t.dgrossamount,
               t.refaccountid, t.useraccountid, t.transdesc
          FROM transactions_archive t
          JOIN users                u   ON  u.userid    = t.refaccountid
          JOIN users                u2  ON  u2.userid   = t.useraccountid
         WHERE t.dgrossamount > 0
           AND t.transdate BETWEEN startdatetime(".$fromdate.") AND enddatetime(".$todate.")
           AND (   (u.userid = ".$memberid." AND t.useraccountid = ".$page->user->userId.")
                OR (u2.userid = ".$memberid." AND t.refaccountid = ".$page->user->userId."))
       ORDER BY transdate, transtype
    ";

    $result = null;
    try {
        if (!empty($memberid)) {
            $result = $page->db->sql_query_params($sql);
        } else {
            $page->messages->addErrorMsg("Error: you must supply a valid member name.");
        }
    } catch (Exception $e) {
        $page->messages->addErrorMsg("Error: ".$e->getMessage()." [Retrieving report data]");
        $result = null;
    } finally {
    }

    return $result;

}
?>