<?php
require_once('template.class.php');

$page = new template(LOGIN, SHOWMSG);
$page->requireJS('scripts/datepicker.js');
$page->requireJS('scripts/jquery.autocomplete.js');

global $MESSAGES;

$eft = new electronicFundsTransfer();

$dealerName   = optional_param('dealername', NULL, PARAM_TEXT);
$fromDate   = optional_param('fromDate', NULL, PARAM_TEXT);
$getReport  = optional_param('getReport', NULL, PARAM_TEXT);
$toDate     = optional_param('toDate', NULL, PARAM_TEXT);
$fromSummary = optional_param('fromsummary', 0, PARAM_INT);

if (isset($getReport)) {
    $data = getEFTreport($dealerName, $fromDate, $toDate);
}

$autolookup = "
    $('#dealername').devbridgeAutocomplete({
        minChars: 3,
        lookup: paydealernames,
        onSelect: function (suggestion) {
            document.report.tapaydealerid.value = suggestion.userid;
        }
    });
";

$page->jsInit($autolookup);

echo $page->header('My Account Totals Report');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $data, $dealerName, $fromDate, $toDate, $fromSummary;
    echo "<h3 class='page-title'>EFT Account Totals Report</h3>\n";
    echo "<div class='entry-content'>\n";
    echo "  <form name='report' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "    <div class='filters' style='padding-top:10px;'>\n";
    echo "      <div class='filter-content' style='margin-top:0px; margin-bottom:0px;'>\n";
    echo "        <p>This page will display total EFT Receipts, Payments, Deposits and Withdrawals for the period specified.<br />You must enter a valid date range.<br />If you enter a specific member it will show all transactions with that member. Type the first 3 or more characters to get suggestions.<br />If you leave member blank it will summarize your transactions with each member.<br />The first and last transaction for each member in the summary encompasses all transactions, not just the specified date range.</p>\n";
    echo "        <label>Member:<input type='text' id='dealername' name='dealername' value='".$dealerName."'></label>\n";
    echo "        <input type='hidden' id='tapaydealerid' name='tapaydealerid' value='' />\n";
    echo "        <label>From:<input type='text' name='fromDate' id='fromDate' value='".$fromDate."'></label> \n";
    echo "        <label>To:<input type='text' name='toDate' id='toDate' value='".$toDate."'></label> \n";
    echo "        <input type='submit' name='getReport' id='getReport' value='GO'>\n";
    echo "      </div>\n";
    if ($dealerName) {
        if ($fromSummary) {
            echo "<input type='hidden' name='fromsummary' id='fromsummary' value='".$fromSummary."' />\n";
            echo "<a href=myEFTaccountTotals.php?fromDate=".$fromDate."&toDate=".$toDate."&getReport=GO>Back To Summary</a><br />\n";
        }
    }
    echo "    </div>\n";
    echo "  </form>\n";
    if ($dealerName) {
        echo "<div class='filter-values'>\n";
        echo "  <p><strong>MEMBER:</strong> ".strtoupper($dealerName)."&nbsp;&nbsp;&nbsp;&nbsp;<strong>From:</strong> ".$fromDate." <strong>To:</strong> ".$toDate."</p>\n";
        echo "</div>\n";
        if ($data) {
            echo "<div class='single-column'>\n";
            echo "  <table cellpadding='0' cellspacing='10'>\n";
            echo "    <thead>\n";
            echo "      <tr>\n";
            echo "        <th>Date</th>\n";
            echo "        <th>Type</th>\n";
            echo "        <th>Amount</th>\n";
            echo "        <th>Description</th>\n";
            echo "      </tr>\n";
            echo "    </thead>\n";
            echo "    <tbody>\n";
            foreach ($data as $d) {
                echo "      <tr>\n";
                echo "        <td data-label='Date'>".date('m/d/Y', $d['transdate'])."</td>\n";
                echo "        <td data-label='Type'>".$d['transtype']."</td>\n";
                echo "        <td data-label='Amount'>".$d['dgrossamount']."</td>\n";
                echo "        <td data-label='Description'>".$d['transdesc']."</td>\n";
                echo "      </tr>\n";
            }
            echo "    </tbody>\n";
            echo "  </table>\n";
        }
    } else {
        if ($data) {
            echo "<div class='single-column'>\n";
            echo "  <table cellpadding='0' cellspacing='10'>\n";
            echo "    <caption class='legend'><div style='float:left;'>Click <i class='fa-solid fa-square-plus'></i> to display detailed transactions.</div></caption>\n";
            echo "    <thead>\n";
            echo "      <tr>\n";
            echo "        <th>Member</th>\n";
            echo "        <th>First Transaction</th>\n";
            echo "        <th>Last Transaction</th>\n";
            echo "        <th>Payments</th>\n";
            echo "        <th>Receipts</th>\n";
            echo "        <th>Deposits</th>\n";
            echo "        <th>Withdraws</th>\n";
            echo "        <th>Fees</th>\n";
            echo "        <th>Others</th>\n";
            echo "        <th>Net</th>\n";
            echo "      </tr>\n";
            echo "    </thead>\n";
            echo "    <tbody>\n";
            foreach ($data as $d) {
                $detailLink = "<a href='myEFTaccountTotals.php?dealername=".$d['username']."&fromDate=".$fromDate."&toDate=".$toDate."&fromsummary=1&getReport=GO'><i class='fa-solid fa-square-plus'></i></a>";
                echo "      <tr>\n";
                echo "        <td data-label='Member'>".$detailLink." ".$d['username']."</td>\n";
                echo "        <td data-label='First Transaction'>".date('m/d/Y', $d['firsttrans'])."</td>\n";
                echo "        <td data-label='First Transaction'>".date('m/d/Y', $d['lasttrans'])."</td>\n";
                echo "        <td data-label='Payments' class='number'>".floatToMoney($d['payments'])."</td>\n";
                echo "        <td data-label='Receipts' class='number'>".floatToMoney($d['receipts'])."</td>\n";
                echo "        <td data-label='Deposits' class='number'>".floatToMoney($d['deposits'])."</td>\n";
                echo "        <td data-label='Withdraws' class='number'>".floatToMoney($d['withdraws'])."</td>\n";
                echo "        <td data-label='Fees' class='number'>".floatToMoney($d['fees'])."</td>\n";
                echo "        <td data-label='Others' class='number'>".floatToMoney($d['others'])."</td>\n";
                echo "        <td data-label='Net' class='number'>".floatToMoney($d['netamounts'])."</td>\n";
                echo "      </tr>\n";
            }
            echo "    </tbody>\n";
            echo "  </table>\n";
        }
    }
    echo "</div>\n";
    
    $members = getMembers();
    $js = "<SCRIPT LANGUAGE='JavaScript'>\n";
    $js .= "  var paydealernames = [\n";
    foreach($members as $m) {
        $js .= "    { value: \"".$m["username"]."\", userid: \"".$m["userid"]."\" },\n";
    }
    $js .= "    { value: '', userid: '' }\n";
    $js .= "  ];\n";
    $js .= "</SCRIPT>\n";
    echo $js;
}

function getMembers() {
    global $page;
    
    $sql = "SELECT UPPER(u.username) AS username, u.userid 
        FROM transactions t 
        JOIN users u ON u.userid=t.refaccountid 
        WHERE t.useraccountid=".$page->user->userId."
        GROUP BY u.userid, u.username
        ORDER BY 1";
    $userList = $page->db->sql_query($sql);
    
    return $userList;
}

function getEFTreport($dealerName, $fromDate, $toDate) {
    global $page;
    
    $data = null;
    $success = true;
    
    $from = null;
    $to = null;

    $dealer = strtoupper($dealerName);
    
    if (empty($fromDate)) {
        $page->messages->addErrorMsg("From Date is required.");
        $success = false;
    } else {
        $fromdt = date_create_from_format('m/d/Y', $fromDate);
        if ($fromdt) {
            $from = strtotime($fromDate);
        } else {
            $page->messages->addErrorMsg("Invalid From Date, must be mm/dd/yyyy.");
            $success = false;
        }
    }
    
    $to = strtotime($toDate);
    if (empty($toDate)) {
        $page->messages->addErrorMsg("To Date is required.");
        $success = false;
    } else {
        $todt = date_create_from_format('m/d/Y', $toDate);
        if ($todt) {
            $to = strtotime($toDate." 23:59:59");
        } else {
            $page->messages->addErrorMsg("Invalid To Date, must be mm/dd/yyyy.");
            $success = false;
        }
    }

    if ($success) {
        if ($dealerName) {
            $sql = "
                SELECT t.transdate, t.transtype, t.dgrossamount, t.transactionid, t.crossrefid, t.transdesc,
                       u.username
                 FROM transactions t
                 JOIN users u ON u.userid = t.refaccountid
                WHERE t.transdate BETWEEN ".$from." and ".$to."
                  AND t.useraccountid = ".$page->user->userId."
                  AND u.username = UPPER('".$dealer."')
                ORDER BY t.transdate DESC
            ";
            $data = $page->db->sql_query_params($sql);
        } else {
            $data = getEFTSummaryReport($from, $to);
        }
    }

    return $data;
}

function getEFTSummaryReport( $from, $to) {
    global $page;
    
    $data = null;
    $success = true;

    if ($success) {
        $sql = "
            SELECT u.username, CASE WHEN u.username IN ('ADMIN','FEES') THEN 0 ELSE 1 END AS normal, u.userid
                , min(transdate) as firsttrans
                , max(transdate) as lasttrans
                , sum(payment) as payments
                , sum(receipt) as receipts
                , sum(fee) as fees
                , sum(deposit) as deposits
                , sum(withdraw) as withdraws
                , sum(other) as others
                , sum(netamount) as netamounts
            FROM (    
                SELECT t.refaccountid, t.transdate
                    ,CASE WHEN t.transtype='PAYMENT' 
                        OR t.transtype='Payment' THEN t.dgrossamount ELSE NULL END AS payment
                    ,CASE WHEN t.transtype='RECEIPT' THEN t.dgrossamount ELSE NULL END AS receipt
                    ,CASE WHEN t.transtype='FEE' 
                        OR t.transtype='CREDIT FEE' 
                        OR t.transtype='MEMBERSHIP FEE' THEN t.dgrossamount ELSE NULL END AS fee
                    ,CASE WHEN t.transtype='DEPOSIT' 
                        OR t.transtype='TXFR-IN' 
                        OR t.transtype='CASHIN' THEN t.dgrossamount ELSE NULL END AS deposit
                    ,CASE WHEN t.transtype='WITHDRAWAL' 
                        OR t.transtype='WITHDRAWL' 
                        OR t.transtype='TXFR-OUT' 
                        OR t.transtype='CASHOUT' THEN t.dgrossamount ELSE NULL END AS withdraw
                    ,CASE WHEN t.transtype='PAYMENT' 
                        OR t.transtype='Payment'
                        OR t.transtype='RECEIPT'
                        OR t.transtype='FEE' 
                        OR t.transtype='CREDIT FEE' 
                        OR t.transtype='MEMBERSHIP FEE'
                        OR t.transtype='DEPOSIT' 
                        OR t.transtype='TXFR-IN' 
                        OR t.transtype='CASHIN'
                        OR t.transtype='WITHDRAWAL' 
                        OR t.transtype='WITHDRAWL' 
                        OR t.transtype='TXFR-OUT' 
                        OR t.transtype='CASHOUT' THEN NULL ELSE t.dgrossamount END AS other
                    ,dgrossamount AS netamount
                FROM transactions t
                WHERE t.useraccountid=".$page->user->userId."
                AND t.transdate BETWEEN ".$from." AND ".$to."
            ) mts
            JOIN users u on u.userid=mts.refaccountid
            GROUP BY u.username, u.userid
            ORDER BY 2,1
        ";
        $data = $page->db->sql_query_params($sql);
    }

    return $data;
}
?>