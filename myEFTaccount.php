<?php
require_once('templateWithSideBars.class.php');

$page = new templateWithSidebars(LOGIN, SHOWMSG, REDIRECTSAFE);
$page->requireJS('scripts/datepicker.js');
$page->requireJS('scripts/eft.js');

global $MESSAGES;

$eft = new electronicFundsTransfer();

$userId = optional_param('userId', NULL, PARAM_INT);

$page->leftsidebar = formatLeftSidebar();
$page->display_StandardLeftWidget = true;

echo $page->header('EFT');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $eft, $MESSAGES, $UTILITY;

    echo "<h1 class='page-title'>Account Summary</h1>\n";

    echo $eft->displayMonthlySelector();

    echo "<div style='float:left;'>";
    echo getMonthlyDDM();
    echo getYearDDM();
    echo "</div>";
    echo "<div style='float:right;padding-right:5px;'><strong>Current Balance:</strong> ".floatToMoney($eft->getLedgerBalance())."</div>\n";

    echo $eft->displayMonthlyTransactions();
    echo $eft->displayEFTtotalsHorizontal();
/*
/////MY stuff for referencs///////////////////////////////////////////////////////////
    echo "Today: ".time()." - ".date('m/d/Y', time())."\n";
    echo "<br />Ledger Balance: ".$eft->ledgerBalance;
    echo "<br />END Total: ".$eft->getEndOfMonthTotal()."\n ";
    echo "<br />Begin: ".$eft->getBeginOfMonth()." - ".date('m/d/Y', $eft->getBeginOfMonth())."\n ";
    echo "<br />BeginYear: ".$eft->getBeginYear()." - ".date('m/d/Y', $eft->getBeginYear())."\n ";
    echo "<br />EndYear: ".$eft->getEndYear()." - ".date('m/d/Y', $eft->getEndYear())."\n ";
    echo "<br />DailyAverage: ".$eft->dailyAverage()."\n ";
    echo "<br />This Month: ".$eft->usedMonthly()."\n ";
    echo "<br />\n";
    echo "<br />Begin Month: ".$eft->getBeginOfMonth()." - ".date('m/d/Y', $eft->getBeginOfMonth())."\n ";
    echo "<br />End Month: ".$eft->getEndOfMonth()." - ".date('m/d/Y', $eft->getEndOfMonth())."\n ";


echo "<br />-12 months - ".$now =  time() - 1597489474;

echo "<br />Today - 2 days<br />";
echo $t = time();
echo "<br />\n";
echo "2 days - ".$IIdays = 60*60*24*2;
echo "<br />Today - 2 Days - \n";
echo $t - $IIdays;
echo "<br />";
//echo $days = timeAddSubtractDays("plus", 2);
//echo "<br />";
//echo date('m/d/Y', $days);
*/

}
function formatLeftSidebar() {
    global $page, $eft;

    $output = "           <div id='primary-widget' class='standard-left-sidebar shadow'><!-- PRIMARY WIDGET -->\n";
    $output .= "             <aside class='rules'>\n";
    $output .= "               <header>\n";
    $output .= "                 <h3>Rules</h3>\n";
    $output .= "               </header>\n";
    $output .= "               <ul>\n";
    $output .= "                 <li><b>Transfer credits to other members:</b>\n";
    $output .= "                   <ul><li>No fees</li><li>No limits</li></ul>\n";
    $output .= "                 </li>\n";
    $output .= "                 <li><b>Withdraw credits via Paypal:</b>\n";
    $output .= "                   <ul>\n";
    $output .= "                     <li>Dealernet WD Fee = ".floatToMoney($page->cfg->EFT_REDEEM_FEE)."</li>\n";
    //$output .= "                       <ul>\n";
    $output .= "                         <li>Fee is waived if user is set up for Auto-EFT billing AND accepts EFT credits as a payment option</li>\n";
    $output .= "                         <li>All Paypal processing fees are incurred by user</li>\n";
    $output .= "                         <li>Limit 1 request per 30 days with maximum ".floatToMoney($page->cfg->EFT_MAX_REDEEM_AMOUNT)."</li>\n";
    //$output .= "                       </ul>\n";
    $output .= "                     </li>\n";
    $output .= "                   </ul>\n";
    $output .= "                 </li>\n";
    $output .= "               </ul>\n";
    $output .= "             </aside>\n";
    $output .= "             <aside>\n";
    $output .= "               <header>\n";
    $output .= "                 <h3>Actions</h3>\n";
    $output .= "               </header>\n";
    $output .= "               <div class='side-menu'>\n";
    $output .= "                 <div class='side-menu-container font-inherit'>\n";
    $output .= "                   <ul class='menu-items'>\n";
    if ($page->user->isAdmin()) {
        $output .= "                     <li><a href='myEFTaction.php?action=cashin'>Admin Cash In</a></li>\n";
        $output .= "                     <li><a href='myEFTaction.php?action=cashout'>Admin Cash Out</a></li>\n";
    }
    if ($eft->isPA || empty($eft->lastDeposit)) {
        $output .= "                     <li><a href='myEFTaction.php?action=deposit'>Deposit</a></li>\n";
    } else {
        $onclick = "JavaScript: alert(\"Limit one deposit per ".$page->cfg->EFT_DEPOSIT_DAYS." day period. Your last deposit was ".$eft->lastDeposit['transdt']."\");";
        $output .= "                     <li><a href='Javascript:void(0);' onclick='".$onclick."'>Deposit</a></li>\n";
    }
    $output .= "                     <li><a href='myEFTaction.php?action=redeem'>Withdraw</a></li>\n";
    $output .= "                     <li><a href='myEFTaction.php?action=transfer'>Transfer To Member</a></li>\n";
    $output .= "                     <li><a href='offers.php?offerfilter=PURCHASES&eftonly=1'>Pay For Purchase</a></li>\n";
    $output .= "                   </ul>\n";
    $output .= "                 </div> <!-- side menu container -->\n";
    $output .= "               </div> <!-- side-menu -->\n";
    $output .= "             </aside>\n";
    $output .= "           </div> \n";

    return $output;
}

function getMonthlyDDM() {
    GLOBAL $eft;

    $months = array();

    $onChange = "onchange='changeDate()'";

    $months[] = array ('month'=> 1, 'monthname' => 'January');
    $months[] = array ('month'=> 2, 'monthname' => 'February');
    $months[] = array ('month'=> 3, 'monthname' => 'March');
    $months[] = array ('month'=> 4, 'monthname' => 'April');
    $months[] = array ('month'=> 5, 'monthname' => 'May');
    $months[] = array ('month'=> 6, 'monthname' => 'June');
    $months[] = array ('month'=> 7, 'monthname' => 'July');
    $months[] = array ('month'=> 8, 'monthname' => 'August');
    $months[] = array ('month'=> 9, 'monthname' => 'September');
    $months[] = array ('month'=> 10, 'monthname' => 'October');
    $months[] = array ('month'=> 11, 'monthname' => 'November');
    $months[] = array ('month'=> 12, 'monthname' => 'December');

    $output = "          ".getSelectDDM($months, "mon", "month", "monthname", NULL, $eft->mon, NULL, 0, NULL, NULL, $onChange)."\n";

    return $output;
}

function getYearDDM() {
    GLOBAL $eft;

    $year = array();

    $onChange = "onchange='changeDate()'";

    $years[] = array ('year'=> 2025, 'yearname' => '2025');
    $years[] = array ('year'=> 2024, 'yearname' => '2024');
    $years[] = array ('year'=> 2023, 'yearname' => '2023');
    $years[] = array ('year'=> 2022, 'yearname' => '2022');
    $years[] = array ('year'=> 2021, 'yearname' => '2021');
    $years[] = array ('year'=> 2020, 'yearname' => '2020');
    $years[] = array ('year'=> 2019, 'yearname' => '2019');
    $years[] = array ('year'=> 2018, 'yearname' => '2018');

    $output = "          ".getSelectDDM($years, "year", "year", "yearname", NULL, $eft->year, NULL, 0, NULL, NULL, $onChange)."\n";

    return $output;
}
/*
function timeAddSubtractDays($operator, $days) {
    $today = time();
    $d = 60*60*24*$days;
    if ($operator == "plus") {
        $data = $today + $d;
    } elseif ($operator == "minus") {
        $data = $today - $d;
    }

    return $data;
}
*/
?>