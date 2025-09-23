<?php
include_once ('setup.php');
// 1st of month -1
$days = date('t');
$m = date('m');
$y = date('Y');


$total = 0;
for ($d = 1; $d <= $days; $d++) {

    $begin = new DateTime($y.'/'.$m.'/'.$d.' 00:00:00');
    $begin = strtotime($begin->format('m/d/Y H:i:s'));

    $end = new DateTime($y.'/'.$m.'/'.$d.' 23:59:59');
    $end = strtotime($end->format('m/d/Y H:i:s'));

    $sql = "
        SELECT SUM(grossamount::NUMERIC) + SUM(feeamount::NUMERIC)
          FROM transactions
         WHERE useraccountid = 20
           AND LOWER(transtype)=LOWER('payment')
           AND transdate BETWEEN ".$begin." AND ".$end."
        ";
        $subtotal = $DB->get_field_query($sql);
//Check Payment PAYMENT
    $total += $subtotal;

}
$avg = $total / $days;
$new = $avg * 0.01;
$new = number_format($new, 2, '.', '');

echo "<br />".$avg;
echo "<br />".$new;


?>