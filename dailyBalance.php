<?php
include_once ('setup.php');
$eft = new electronicFundsTransfer();

$days = date('t');
$total = $eft->getEndOfMonthTotal();
$dailyAverage = $total / $days;
echo $dailyAverage;
?>