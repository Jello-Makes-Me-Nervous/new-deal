<?php
include('setup.php');
global $UTILITY;

$userId = 15;
echo "<h3>You are - ".strtoupper($UTILITY->getUserName($userId))."</h3>\n";
echo "<h3>These are pending offers ready to Approve Revise or Cancel</h3>\n";

$data = getAllOffers($userId);

if (!empty($data)) {
    foreach ($data as $d) {
// Define your key
        $offerid = $d['offerid'];
// Assign to the new array using all of the actual values
        $ofr[$offerid][] = $d;
     }
// Get all values inside the array, but without orderId in the keys:
    $ofr = array_values($ofr);

    $i = 0;
    foreach ($ofr as $key) {
//echo var_dump($ofr[$i]);
        $date = strtotime('+ '.$ofr[$i][0]['offerexpiration'].' hours', $ofr[$i][0]['createdate']);
        echo "<form name='sub".$ofr[$i][0]['offerid']."' action='offer.php' method='post'>\n";
        echo "  This offer will expire at ".$date = date( 'm/d/Y H:i:s A', $date)."<br />\n";
        echo "  From ".$UTILITY->getUserName($ofr[$i][0]['offerfrom'])." - ".$ofr[$i][0]['lsttype']." - ".$ofr[$i][0]['offersubtotal']." - ".$ofr[$i][0]['boxtypename']."\n";
        echo "  ".$ofr[$i][0]['offerid']." - ".$ofr[$i][0]['categoryname']." - ".$ofr[$i][0]['subcategoryname']." - ".$ofr[$i][0]['boxtypename']."\n";
        echo "  <input type='hidden' name='offerId' id='offerId' value='".$ofr[$i][0]['offerid']."'>\n";
        echo "  <input type='submit' name='submit' id='submit' value='Process Offer'>\n";
        echo "</form>\n";
        echo "<br/><br />";
        $i++;
    }
}


function getAllOffers($userId) {
    global $DB;
    global $UTILITY;

    $sql = "
        SELECT itm.offeritemid, itm.offerid, itm.touserid, itm.offerqty, itm.lstyear, itm.lstuom, itm.lstqty,
               itm.lsttype, itm.lstminqty, itm.lstprice, itm.lstnotes, itm.countered,
               ofr.offersubtotal, ofr.offerexpiration, ofr.offernotes, ofr.offerto, ofr.offerfrom, ofr.offerstatus, ofr.createdate,
               ofr.transactiontype, ofr.paymenttype, ofr.paymentmethod, ofr.paymenttiming, ofr.accepted, ofr.countered, ofr.moved,
               cat.categoryname, sub.subcategoryname, box.boxtypename, u.username
          FROM offeritems itm
          JOIN offers ofr           ON ofr.offerid = itm. offerid
          JOIN categories cat       ON cat.categoryid = itm.lstcatid
          JOIN subcategories sub    ON sub.subcategoryid = itm.lstsubcatid
          JOIN boxtypes box         ON box.boxtypeid = itm.lstboxtypeid
          JOIN users u              ON u.userid = itm.touserid
         WHERE ofr.offerto = ".$userId."
           AND ofr.offerstatus = 'PENDING'
           AND ofr.latest = 1
         ORDER BY ofr.offerexpiration
    ";

    $data = $DB->sql_query($sql);

    return $data;
}


?>
