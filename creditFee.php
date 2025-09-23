<?php
include_once ('setup.php');

//if credit used > 0 take creditused / by number of days in the month and * by 0.01 to get the fee and then create the transaction
// call this function with a cronjob on the last day of the month $eft->getEndOfMonth
function creditFee() {
    global $DB;
    global $UTILITY;

     $sql = "
        SELECT creditused
          FROM creditlines
         WHERE creditused > 0::MONEY
    ";

    $creditused = $DB->sql_query_params($sql);

        foreach ($creditused as $row) {
            $used = $UTILITY->cleanMoney($row['creditused']);
            //days in the month
            $days = date('t');
            //amount used / days = average daily credit used
            $daily = $used / $days;
            //fee per day
            $fee = $daily * 0.01;
            //fee per day * days
            $fees = $fee * $days;
            //$totalFee = round($fees, 2);
            $totalFee = number_format($fees, 2, '.', '');

            echo floatval($row['creditused']);

            echo "<br />USED:".$used." || Days:".$days." || Daily:".$daily." || Fee:".$fee." || Fees:".$fees." || TotalFee:".$totalFee;
            echo "<br />USED * .01 = ". $used * .01;

            //create the transaction for the fee
        }
}
echo creditFee();

//doing math with money?
//payment check credit check payment amount against ledgerbalance if not enough check available credit
function paymentCreditCheck($id, $payment) {
    global $DB;
    global $UTILITY;

    $output = "";

    $sql = "
        SELECT ledgerbalance
          FROM userinfo
         WHERE userid = ".$id."
    ";
    $avaliable = $DB->get_field_query($sql);
    $cash = $UTILITY->cleanMoney($avaliable);

    if ($cash < $payment) {
        $balance = $payment - $cash;
        $sql = "
            SELECT creditamount, creditused
              FROM creditlines
             WHERE userid = ".$id." AND suspended = '0'
        ";
        $avaliable = $DB->get_field_query($sql);
        $credit = $UTILITY->cleanMoney($avaliable);

        if ($credit >= $balance) {
            //continue
            $output .= "<br />"."Continue with credit?\n";
//make the payment here with credit or send a message (Continue with credit?)
        } else {
            //message
            $output .= "<br />"."Insufficient funds ask for credit or raise current credit limit?\n";
        }
    } else {
        $output .= "<br />"."Cash is King!";
    }
}
echo paymentCreditCheck(13, 7000);

function receiptCreditCheck($id, $receipt) {
    global $DB;
    global $UTILITY;

    $output = "";

    $sql = "
            SELECT creditused
              FROM creditlines
             WHERE userid = ".$id." AND suspended = '0'
        ";
        $owed = $DB->get_field_query($sql);
        $creditDebt = $UTILITY->cleanMoney($owed);

        if ($creditDebt > 0 && $creditDebt >= $receipt) {
            //$receipt inserts to creditused
            $output .= "Pay to credit = ".$receipt;
        } elseif ($creditDebt > 0 && $creditDebt < $receipt) {
            $insertReceipt = $receipt - $creditDebt;
            //pay to credit (make creditused = 0)
            $output .=  "Pay creditused to = 0";
            //update ledgerbalance with insertReceipt
            $output .= "Add ".$insertReceipt." to ledgerbalance";
        } if ($creditDebit = 0) {
            //update ledgerbalance full amount
            $output .= "Add ".$receipt." to ledgerbalance";
        }

        return $output;
}


?>