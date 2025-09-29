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
$dealerId = optional_param('dealerid', NULL, PARAM_INT);
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
if (empty($dealerId)) {
    $page->messages->addErrorMsg("Dealer Id required.");
} else {
    if ($dealerName = $page->utility->getDealersName($dealerId)) {
        $offerList = getAllOffersFiltered($dealerId, $offerId, $offerFilter, $offerDealer, $otherDealer, $fromDateTime, $toDateTime);
    } else {
        $page->messages->addErrorMsg("Unable to locate dealer id ".$dealerid);
    }
}

echo $page->header('Offers');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $dealerId, $dealerName, $offerId, $offerFilter, $offerDealer, $otherDealer, $fromDate, $toDate, $offerList, $page, $UTILITY;

    echo "<br />\n";

    echo "<h3>Offer Complaints ".$dealerName." (".$dealerId.")</h3>\n";
    echo "<form name='sub' id='sub' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post' enctype='multipart/form-data'>\n";
    echo "  <input type='hidden' name='dealerid' id='dealerid' value='".$dealerId."' />\n";
    echo "<table>\n";
    echo "  <tr><td>Status:</td><td>";
    echo "<select name='offerfilter' id='offerfilter' onchange='submit();' >\n";
    echo "          <option value='ALL'>All</option>\n";
    echo "          <option value='ACCEPTED' ".isChecked($offerFilter, "ACCEPTED").">Accepted</option>\n";
    echo "          <option value='ARCHIVED' ".isChecked($offerFilter, "ARCHIVED").">Archived</option>\n";
    echo "          <option value='VOID' ".isChecked($offerFilter, "VOID").">Void</option>\n";
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
        echo "Matches: ".count($offerList)."<br />\n";
        echo "<table>\n";
        echo "  <thead>\n";
        echo "    <tr><th>ID</th><th>Dealer</th><th>Created</th><th>Dispute Opened</th><th>Dispute Closed</th><th>Total</th><th>Details</th><th>Status</th></tr>";
        echo "  </thead>\n";
        echo "  <tbody>\n";
        foreach ($offerList as $offerInfo) {
            echo "    <tr>\n";
            echo "      <td title='ID:".$offerInfo['offerid']." Thread:".$offerInfo['threadid']."'><a href='offeradmin.php?offerid=".$offerInfo['offerid']."' target='_blank'>".$offerInfo['offerid']."</a></td>";
            echo "      <td>From ".$offerInfo['offerdealer']."(".$offerInfo['offerfrom'].") to ".$offerInfo['dealername']."(".$offerInfo['offerto'].")</td>";
            echo "      <td>".$offerInfo['createdt']."</td>";
            $complaintOpened = ($offerInfo['complaintopened']) ? date('m/d/Y H:i:s', $offerInfo['complaintopened']) : "";
            echo "      <td>".$complaintOpened."</td>";
            $complaintClosed = ($offerInfo['complaintclosed']) ? date('m/d/Y H:i:s', $offerInfo['complaintclosed']) : "";
            echo "      <td>".$complaintClosed."</td>";
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

function getAllOffersFiltered($dealerId, $offerId, $offerFilter, $offerDealer, $otherDealer, $fromDateTime, $toDateTime) {
    global $page;

    $sql = "SELECT ofr.offerid, ofr.offerfrom, ofr.offerto, ofr.transactiontype, ofr.offerstatus, ofr.threadid
            ,uf.username as offerdealer, ut.username as dealername
            ,offerdsubtotal
            ,to_char(to_timestamp(ofr.offerexpiration),'MM/DD/YYYY HH24:MI:SS') as expiresat
            ,to_char(to_timestamp(ofr.createdate),'MM/DD/YYYY HH24:MI:SS') as createdt
            ,ofr.carrierid AS hasshipping
            ,CASE WHEN ofr.offerfrom=".$dealerId." THEN ofr.disputetoopened ELSE ofr.disputefromopened END AS complaintopened
            ,CASE WHEN ofr.offerfrom=".$dealerId." THEN ofr.disputetoclosed ELSE ofr.disputefromclosed END AS complaintclosed
        FROM offers ofr
        JOIN users uf on uf.userid=ofr.offerfrom
        JOIN users ut on ut.userid=ofr.offerto
        JOIN (
            SELECT o.offerid
                ,CASE WHEN (m.toid=o.offerfrom OR m.fromid=o.offerfrom) THEN 
                    o.offerto 
                 ELSE o.offerfrom 
                 END AS complainedabout
            FROM offers o
            JOIN messaging m ON m.offerid = o.offerid
            WHERE m.messagetype = 'COMPLAINT'
              AND m.parentid=0
            GROUP BY 1,2
        ) cmp ON cmp.offerid=ofr.offerid AND cmp.complainedabout = ".$dealerId."
        WHERE (uf.userid=".$dealerId." OR ut.userid=".$dealerId.")
          AND ofr.offerstatus IN ('ACCEPTED','ARCHIVED','DECLINED','EXPIRED','CANCELLED','VOID')";

    switch ($offerFilter) {
        CASE 'SALES':
            $sql .= " AND ofr.offerstatus='ACCEPTED'\n";
            $sql .= " AND ofr.satisfiedbuy<>1\n";
            $sql .= " AND (";
            $sql .= "(ofr.transactiontype='For Sale' AND ofr.offerto=".$dealerId.")";
            $sql .= " OR ";
            $sql .= "(ofr.transactiontype='Wanted' AND ofr.offerfrom=".$dealerId.")";
            $sql .= ")\n";
            BREAK;
        CASE 'PURCHASES':
            $sql .= " AND ofr.offerstatus='ACCEPTED'\n";
            $sql .= " AND ofr.satisfiedsell<>1\n";
            $sql .= " AND (";
            $sql .= "(ofr.transactiontype='For Sale' AND offerfrom=".$dealerId.")";
            $sql .= " OR ";
            $sql .= "(ofr.transactiontype='Wanted' AND offerto=".$dealerId.")";
            $sql .= ")\n";
            BREAK;
        CASE 'For Sale':
            $sql .= " AND ofr.transactiontype='For Sale'";
            BREAK;
        CASE 'Wanted':
            $sql .= " AND ofr.transactiontype='Wanted'";
            BREAK;
        CASE 'PENDING':
            $sql .= " AND ofr.offerstatus='PENDING'";
            BREAK;
        CASE 'PENDINGIN':
            $sql .= " AND ofr.offerstatus='PENDING' AND ofr.offeredby<>".$dealerId;
            BREAK;
        CASE 'PENDINGOUT':
            $sql .= " AND ofr.offerstatus='PENDING' AND ofr.offeredby=".$dealerId;
            BREAK;
        CASE 'ARCHIVED':
            $sql .= " AND ofr.offerstatus='ARCHIVED'";
            BREAK;
        CASE 'VOID':
            $sql .= " AND ofr.offerstatus='VOID'";
            BREAK;
        CASE 'DECLINED':
            $sql .= " AND ofr.offerstatus='DECLINED'";
            BREAK;
        CASE 'EXPIRED':
            $sql .= " AND ofr.offerstatus='EXPIRED'";
            BREAK;
        CASE 'CANCELLED':
            $sql .= " AND ofr.offerstatus='CANCELLED'";
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
