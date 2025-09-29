<?php
//////////////////////////////////////////////REMOVE COUNTERED OFFERS FROM Pending or change how revised/counterd is dealt with
require_once('templateAdmin.class.php');

$page = new templateAdmin(LOGIN, SHOWMSG);
$calendarJS = '
    $(function(){$("#fromdate").datepicker();});
    $(function(){$("#todate").datepicker();});
';
$page->jsInit($calendarJS);


$transactionType = optional_param('transactiontype', NULL, PARAM_TEXT);
$myAction = optional_param('myaction', "Buy", PARAM_TEXT);
$offerId = optional_param('offerid', NULL, PARAM_TEXT);
$offerDealer = optional_param('offerdealer', NULL, PARAM_TEXT);
$otherDealer = optional_param('otherdealer', NULL, PARAM_TEXT);
$fromDate = optional_param('fromdate', NULL, PARAM_TEXT);
$fromDateTime = NULL;
$toDate = optional_param('todate', NULL, PARAM_TEXT);
$toDateTime = NULL;

$offerFilter = optional_param('offerfilter', "ALL", PARAM_TEXT);

if (!empty($fromDate)) {
    $fromDateTime = strtotime($fromDate);
    if (! $fromDateTime) {
        $page->messages->addErrorMsg("Invalid From Date");
    }
}
if (!empty($toDate)) {
    $toDateTime = strtotime($toDate);
    if (! $toDateTime) {
        $page->messages->addErrorMsg("Invalid To Date");
    }
}

$offerList = NULL;
//$offerList = getAllOffers($offerStatus, $transactionType, $myAction, $otherDealer, $fromDateTime, $toDateTime);
if (! (empty($offerId) && empty($offerDealer) && empty($otherDealer) && empty($fromDateTime) && empty($toDateTime))) {
    $offerList = getAllOffersFiltered($offerId, $offerFilter, $offerDealer, $otherDealer, $fromDateTime, $toDateTime);
}

echo $page->header('Offers');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $offerId, $offerFilter, $offerDealer, $otherDealer, $fromDate, $toDate, $offerList, $page, $UTILITY;

    echo "<br />\n";

    echo "<h3>Offers</h3>\n";
    echo "<form name='sub' id='sub' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post' enctype='multipart/form-data'>\n";
    echo "<table>\n";
    echo "  <tr><td>Status:</td><td>";
    echo "<select name='offerfilter' id='offerfilter' onchange='submit();' >\n";
    echo "          <option value='ALL'>All</option>\n";
    echo "          <option value='SALES' ".isChecked($offerFilter, "SALES").">Sales</option>\n";
    echo "          <option value='PURCHASES' ".isChecked($offerFilter, "PURCHASES").">Purchases</option>\n";
    echo "          <option value='For Sale' ".isChecked($offerFilter, "For Sale").">For Sale</option>\n";
    echo "          <option value='Wanted' ".isChecked($offerFilter, "Wanted").">Wanted</option>\n";
    echo "          <option value='PENDING' ".isChecked($offerFilter, "PENDING").">Pending</option>\n";
    echo "          <option value='PENDINGIN' ".isChecked($offerFilter, "PENDINGIN").">Pending Incoming</option>\n";
    echo "          <option value='PENDINGOUT' ".isChecked($offerFilter, "PENDINGOUT").">Pending Outgoing</option>\n";
    echo "          <option value='ARCHIVED' ".isChecked($offerFilter, "ARCHIVED").">Archived</option>\n";
    echo "          <option value='DECLINED' ".isChecked($offerFilter, "DECLINED").">Declined</option>\n";
    echo "          <option value='EXPIRED' ".isChecked($offerFilter, "EXPIRED").">Expired</option>\n";
    echo "          <option value='CANCELLED' ".isChecked($offerFilter, "CANCELLED").">Cancelled</option>\n";
    echo "          <option value='VOID' ".isChecked($offerFilter, "VOID").">Void</option>\n";
    echo "          <option value='COMPLAINT' ".isChecked($offerFilter, "COMPLAINT").">Complaint</option>\n";
    echo "        </select></td></tr>\n";
    echo "  <tr><td>OfferId:</td><td><input type=text size=10 name='offerid' id='offerid' value='".$offerId."' /></td></tr>\n";
    echo "  <tr><td>Offerer:</td><td><input type=text size=10 name='offerdealer' id='offerdealer' value='".$offerDealer."' /></td></tr>\n";
    echo "  <tr><td>Dealer:</td><td><input type=text size=10 name='otherdealer' id='otherdealer' value='".$otherDealer."' /></td></tr>\n";
    echo "  <tr><td>Offer Date:</td><td><input type=text size=10 name='fromdate' id='fromdate' value='".$fromDate."' /> TO <input type=text size=10 name='todate' id='todate' value='".$toDate."' /></td></tr>\n";
    echo "   <tr><td>&nbsp;</td><td><input type='submit' name='refresh' id='refresh' value='Refresh'></td></tr>\n";
    echo "</table>\n";
    echo "</form>\n";
    echo "<br />\n";

    if (is_array($offerList) && (count($offerList) > 0)) {
        echo "<table>\n";
        echo "  <thead>\n";
        echo "    <tr><th>ID</th><th>Dealer</th><th>Created</th><th>Total</th><th>Details</th><th>Status</th></tr>";
        echo "  </thead>\n";
        echo "  <tbody>\n";
        foreach ($offerList as $offerInfo) {
            echo "    <tr>\n";
            echo "      <td title='ID:".$offerInfo['offerid']." Thread:".$offerInfo['threadid']."'><a href='offeradmin.php?offerid=".$offerInfo['offerid']."' target='_blank'>".$offerInfo['offerid']."</a></td>";
            echo "      <td>From ".$offerInfo['offerdealer']."(".$offerInfo['offerfrom'].") to ".$offerInfo['dealername']."(".$offerInfo['offerto'].")</td>";
            echo "      <td>".$offerInfo['createdt']."</td>";
            echo "      <td align=right>".floatToMoney($offerInfo['offerdsubtotal'])."</td>";
            switch ($offerInfo['offerstatus']) {
                CASE "PENDING":
                    echo "      <td>Expires: ".$offerInfo['expiresat']."</td>";
                    break;
                CASE "EXPIRED":
                    echo "      <td>Expired: ".$offerInfo['expiresat']."</td>";
                    break;
                CASE "ACCEPTED":
                    echo "      <td>".acceptedActions($offerInfo)."</td>";
                    break;
                CASE "DECLINED":
                CASE "CANCELLED":
                CASE "REVISED":
                CASE "ARCHIVED":
                CASE "VOID":
                    echo "      <td>&nbsp;</td>";
                    break;
            }
            echo "      <td>".$offerInfo['offerstatus']."</td>";
            echo "    </tr>\n";
        }
        echo "  </tbody>\n";
        echo "</table>\n";
    } else {
        echo "You have no matching offers<br />\n";
    }
}

function getAllOffersFiltered($offerId, $offerFilter, $offerDealer, $otherDealer, $fromDateTime, $toDateTime) {
    global $page;

    $sql = "SELECT ofr.offerid, ofr.offerfrom, ofr.offerto, ofr.transactiontype, ofr.offerstatus, ofr.threadid
            ,uf.username as offerdealer, ut.username as dealername
            ,offerdsubtotal
            ,to_char(to_timestamp(ofr.offerexpiration),'MM/DD/YYYY HH24:MI:SS') as expiresat
            ,to_char(to_timestamp(ofr.createdate),'MM/DD/YYYY HH24:MI:SS') as createdt
            ,ofr.carrierid AS hasshipping
        FROM offers ofr
        JOIN users uf on uf.userid=ofr.offerfrom
        JOIN users ut on ut.userid=ofr.offerto
        WHERE 1=1\n";

    switch ($offerFilter) {
        CASE 'SALES':
            $sql .= " AND offerstatus='ACCEPTED'\n";
            $sql .= " AND satisfiedbuy<>1\n";
            $sql .= " AND (";
            $sql .= "(transactiontype='For Sale' AND offerto=".$page->user->userId.")";
            $sql .= " OR ";
            $sql .= "(transactiontype='Wanted' AND offerfrom=".$page->user->userId.")";
            $sql .= ")\n";
            BREAK;
        CASE 'PURCHASES':
            $sql .= " AND offerstatus='ACCEPTED'\n";
            $sql .= " AND satisfiedsell<>1\n";
            $sql .= " AND (";
            $sql .= "(transactiontype='For Sale' AND offerfrom=".$page->user->userId.")";
            $sql .= " OR ";
            $sql .= "(transactiontype='Wanted' AND offerto=".$page->user->userId.")";
            $sql .= ")\n";
            BREAK;
        CASE 'For Sale':
            $sql .= " AND transactiontype='For Sale'";
            BREAK;
        CASE 'Wanted':
            $sql .= " AND transactiontype='Wanted'";
            BREAK;
        CASE 'PENDING':
            $sql .= " AND offerstatus='PENDING'";
            BREAK;
        CASE 'PENDINGIN':
            $sql .= " AND offerstatus='PENDING' AND offeredby<>".$page->user->userId;
            BREAK;
        CASE 'PENDINGOUT':
            $sql .= " AND offerstatus='PENDING' AND offeredby=".$page->user->userId;
            BREAK;
        CASE 'ARCHIVED':
            $sql .= " AND offerstatus='ARCHIVED'";
            BREAK;
        CASE 'VOID':
            $sql .= " AND offerstatus='VOID'";
            BREAK;
        CASE 'DECLINED':
            $sql .= " AND offerstatus='DECLINED'";
            BREAK;
        CASE 'EXPIRED':
            $sql .= " AND offerstatus='EXPIRED'";
            BREAK;
        CASE 'CANCELLED':
            $sql .= " AND offerstatus='CANCELLED'";
            BREAK;
        default:
            BREAK;
    }

    if (!empty($offerId)) {
        $sql .= " AND ofr.offerId = ".$offerId;
    }

    if (!empty($fromDateTime)) {
        $sql .= " AND ofr.createdate >= ".$fromDateTime;
    }

    if (!empty($toDateTime)) {
        $sql .= " AND ofr.createdate < ".$toDateTime+86400;
    }

    if (!empty($offerDealer)) {
        $sql .= " AND uf.username ilike '%".$offerDealer."%'";
    }

    if (!empty($otherDealer)) {
        $sql .= " AND ut.username ilike '%".$otherDealer."%'";
    }

    $sql .= " ORDER BY ofr.threadid DESC, ofr.offerid DESC";

    //echo "SQL:<pre>".$sql."</pre><br />\n";

    $offers = $page->db->sql_query($sql);

    return $offers;
}

function isChecked($check, $checked) {

    if ($check == $checked) {
        $data = " selected";
    } else {
        $data = "";
    }

    return $data;
}

function acceptedActions($offerInfo) {
    $output = "";

    $output .= "<a href='offeradmin.php?offerid=".$offerInfo['offerid']."' title='Seller happiness not set'>\n";
    $output .= "  <span class='fa-stack fa-sm'>\n";
    $output .= "    <i class='fas fa-star fa-stack-2x'></i>\n";
    $output .= "    <i class='fas fa-stack-1x fa-sm'>5</i>\n";
    $output .= "  </span>\n";
    $output .= "</a>";
    $output .= "<a href='offeradmin.php?offerid=".$offerInfo['offerid']."#shipping' title='Shipping Info'>\n";
    $output .= "    <i class='fas fa-truck ".(($offerInfo['hasshipping']) ? "shipped' " : " not-shipped'")."></i>\n";
    $output .= "</a> ";
    $output .= " <a href='offeradmin.php?offerid=".$offerInfo['offerid']."' title='No offer messages about shipment'><i class='fas fa-comment'></i></a>";
    $output .= " <a href='offeradmin.php?offerid=".$offerInfo['offerid']."' title='Contact ADMIN for assistance'><i class='fas fa-hands-helping'></i></a>";

    return $output;
}

?>
