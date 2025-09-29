<?php
//////////////////////////////////////////////REMOVE COUNTERED OFFERS FROM Pending or change how revised/counterd is dealt with
require_once('templateOffer.class.php');

$page = new templateOffer(LOGIN, SHOWMSG);
$calendarJS = '
    $(function(){$("#fromdate").datepicker();});
    $(function(){$("#todate").datepicker();});
';
$page->jsInit($calendarJS);


$transactionType    = optional_param('transactiontype', NULL, PARAM_TEXT);
$myAction           = optional_param('myaction', "Buy", PARAM_TEXT);
$otherDealer        = optional_param('otherdealer', NULL, PARAM_TEXT);
$filterKeyword      = optional_param('filterkeyword', NULL, PARAM_TEXT);
$fromDate           = optional_param('fromdate', NULL, PARAM_RAW);
$fromDateTime       = NULL;
$toDate             = optional_param('todate', NULL, PARAM_RAW);
$toDateTime         = NULL;
$offerFilter        = optional_param('offerfilter', "ALL", PARAM_TEXT);
$eftOnly            = optional_param('eftonly', NULL, PARAM_INT);

$includeRated   = 1;
$excludeRated   = 0;
$checkRated     = false;
switch($offerFilter) {
    case 'SALESUNRATED':
    case 'PURCHASESUNRATED':
        $includeRated = 0;
        $excludeRated = 1;
        $checkRated = true;
        break;
    case 'ALL':
    case 'SALESALL':
    case 'PURCHASESALL':
        if (empty($fromDate)) {
            $fromDate = date("m/01/Y", strtotime("-3 months"));
            $page->messages->addInfoMsg("For \"ALL\" searches we have defaulted to 3 months ago. To go deeper simply reset the offer from date");
        }
        break;
}


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

$offerList = getAllOffersFiltered($offerFilter, $otherDealer, $filterKeyword, $fromDateTime, $toDateTime, $excludeRated, $eftOnly);
$offerItems = array();
if ($offerList) {
    loadOfferItems();
}


echo $page->header('Offers');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $offerFilter, $otherDealer, $filterKeyword, $fromDate, $toDate, $excludeRated, $checkRated, $eftOnly, $offerList, $offerItems, $page, $UTILITY;

    echo "<br />\n";

    echo "<h3>Offers</h3>\n";
    echo "<form name='sub' id='sub' action='".htmlentities($_SERVER['PHP_SELF'])."#results' method='post' enctype='multipart/form-data'>\n";
    echo "<table>\n";
    echo "  <tr>\n";
    echo "    <td>Status:</td>\n";
    echo "    <td>\n";
    echo "      <select name='offerfilter' id='offerfilter' onchange='submit();' >\n";
    echo "          <option value='ALL'>All</option>\n";
    echo "          <option value='SALES' ".isChecked($offerFilter, "SALES").">Sales - Last 14 Days</option>\n";
    echo "          <option value='SALESALL' ".isChecked($offerFilter, "SALESALL").">Sales - All</option>\n";
    echo "          <option value='SALESUNRATED' ".isChecked($offerFilter, "SALESUNRATED").">Sales - Unrated Only</option>\n";
    echo "          <option value='PURCHASES' ".isChecked($offerFilter, "PURCHASES").">Purchases - Last 14 Days</option>\n";
    echo "          <option value='PURCHASESALL' ".isChecked($offerFilter, "PURCHASESALL").">Purchases - All</option>\n";
    echo "          <option value='PURCHASESUNRATED' ".isChecked($offerFilter, "PURCHASESUNRATED").">Purchases - Unrated Only</option>\n";
    echo "          <option value='PENDINGIN' ".isChecked($offerFilter, "PENDINGIN").">Pending Incoming</option>\n";
    echo "          <option value='PENDINGOUT' ".isChecked($offerFilter, "PENDINGOUT").">Pending Outgoing</option>\n";
    echo "          <option value='DECLINED' ".isChecked($offerFilter, "DECLINED").">Declined</option>\n";
    echo "          <option value='EXPIRED' ".isChecked($offerFilter, "EXPIRED").">Expired</option>\n";
    echo "          <option value='CANCELLED' ".isChecked($offerFilter, "CANCELLED").">Cancelled</option>\n";
    echo "          <option value='VOID' ".isChecked($offerFilter, "VOID").">Void</option>\n";
    echo "          <option value='ACCEPT6MOS' ".isChecked($offerFilter, "ACCEPT6MOS").">Acceptance (6 months)</option>\n";
    echo "          <option value='EXPIRE6MOS' ".isChecked($offerFilter, "EXPIRE6MOS").">Expiration (6 months)</option>\n";
    echo "        </select>\n";
    echo "    </td>\n";
    echo "  </tr>\n";
    echo "  <tr>\n";
    echo "    <td>Dealer:</td>\n";
    echo "    <td><input type=text size=10 name='otherdealer' id='otherdealer' value='".$otherDealer."' /></td>\n";
    echo "  </tr>\n";
    echo "  <tr>\n";
    echo "    <td>Offer Date:</td>\n";
    echo "    <td><input type=text size=10 name='fromdate' id='fromdate' value='".$fromDate."' /> TO <input type=text size=10 name='todate' id='todate' value='".$toDate."' /></td>\n";
    echo "  </tr>\n";
    echo "  <tr>\n";
    echo "    <td>Keyword:</td>\n";
    echo "    <td><input type=text size=10 name='filterkeyword' id='filterkeyword' value='".$filterKeyword."' /><br />(Category, Subcategory, Year, Notes)</td>\n";
    echo "  </tr>\n";
    echo "  <tr>\n";
    echo "    <td>".offsetAnchor('results')."EFT Only:</td>\n";
    echo "    <td><input type=checkbox name='eftonly' id='eftonly' value='1' ".(($eftOnly) ? "checked" : "")." onClick='submit();' /></td>\n";
    echo "  </tr>\n";
    echo "   <tr>\n";
    echo "    <td>&nbsp;</td>\n";
    echo "    <td><input type='submit' name='refresh' id='refresh' value='Refresh'></td>\n";
    echo "  </tr>\n";
    echo "</table>\n";
    echo "</form>\n";

    if (is_array($offerList) && (count($offerList) > 0)) {
        $ratedCaption = "";
        if ($checkRated && $excludeRated) {
            if ($offerFilter == "SALESUNRATED") {
                echo $page->messages->showMessage("Rated Sales have been excluded. Select 'Sales - Last 14 Days' or 'Sales - All' in the Status filter above to see rated and unrated Sales.", MSG_TYPE_WARNING);
            } else {
                echo $page->messages->showMessage("Rated Purchases have been excluded. Select 'Purchases - Last 14 Days' or 'Purchases - All' in the Status filter above to see rated and unrated Purchases.", MSG_TYPE_WARNING);
            }
        }
        echo "<table>\n";
        echo "  <caption class='legend'><div style='float:left;'>Click <i class='fa-solid fa-square-plus'></i> to display items.</div>".$ratedCaption."<strong>Detail Legend:</strong><i class='fas fa-star'></i>Rating|<i class='fas fa-comment offer-chat'></i>Messages|<i class='fas fa-truck shipped'></i>Shipping|<i class='fas fa-message-question admin-assist'></i>Assistance</caption>\n";
        echo "  <thead>\n";
        echo "    <tr>\n";
        echo "      <th>OID</th>\n";
        echo "      <th>Dealer</th>\n";
        echo "      <th>Created</th>\n";
        echo "      <th>Total</th>\n";
        echo "      <th>Details</th>\n";
        echo "      <th>Assistance Until</th>\n";
        echo "      <th>Status</th>\n";
        echo "    </tr>";
        echo "  </thead>\n";
        echo "  <tbody>\n";
        foreach ($offerList as $offerInfo) {
            $dealerDirection = ($offerInfo['fromme']) ? "to" : "from";
            echo "    <tr>\n";
            echo "      <td data-label='OID' class='number'>";
            echo "<a href='#' class='oihide".$offerInfo['offerid']."' style='float:left; display:none;' title='Hide Items' onClick='$(\".oidata".$offerInfo['offerid']."\").hide();$(\".oihide".$offerInfo['offerid']."\").hide(); $(\".oishow".$offerInfo['offerid']."\").show();return(false);'><i class='fa-solid fa-square-minus'></i></a>";
            echo "<a href='#' class='oishow".$offerInfo['offerid']."' style='float:left;' title='Show Items' onClick='$(\".oidata".$offerInfo['offerid']."\").show();$(\".oihide".$offerInfo['offerid']."\").show(); $(\".oishow".$offerInfo['offerid']."\").hide();return(false);'><i class='fa-solid fa-square-plus'></i></a>";
            echo $offerInfo['offerid'];
            echo "</td>";
            echo "      <td data-label='Dealer'>";
            if (($offerInfo['offerstatus'] != 'PENDING') && ($offerInfo['offerstatus'] != 'ACCEPTED') && ($offerInfo['offerstatus'] != 'ARCHIVED')) {
                echo $dealerDirection." ".$offerInfo['dealername']." (".$offerInfo['transactiontype'].") ";
            } else {
                echo "<a href='offer.php?offerid=".$offerInfo['offerid']."'>".$dealerDirection." ".$offerInfo['dealername']." (".$offerInfo['transactiontype'].")</a>";
            }
            echo "</td>";
            echo "      <td data-label='Created'>".$offerInfo['createdt']."</td>";
            $counteredMsg = ($offerInfo['countered'] && ($offerInfo['offerto'] == $page->user->userId)) ? "<br /><span class='errormsg'>* COUNTER OFFER</span>" : "";
            echo "      <td data-label='Total' align=right>".floatToMoney($offerInfo['offerdsubtotal']).$counteredMsg."</td>";
            switch ($offerInfo['offerstatus']) {
                CASE "PENDING":
                    echo "      <td data-label='Details'>Expires: ".$offerInfo['expiresat']."</td>";
                    break;
                CASE "EXPIRED":
                    echo "      <td data-label='Details'>Expired: ".$offerInfo['expiresat']."</td>";
                    break;
                CASE "ACCEPTED":
                    echo "      <td data-label='Details'>".acceptedActions($offerInfo)."</td>";
                    break;
                CASE "ARCHIVED":
                    echo "      <td data-label='Details'>".acceptedActions($offerInfo)."</td>";
                    break;
                CASE "VOID":
                    echo "      <td data-label='Details'>".acceptedActions($offerInfo)."</td>";
                    break;
                CASE "DECLINED":
                CASE "CANCELLED":
                CASE "REVISED":
                    echo "      <td>&nbsp;</td>";
                    break;
            }
            echo "      <td data-label='Assistance Until'>".$offerInfo['assistuntil']."</td>";
            echo "      <td data-label='Status'>".$offerInfo['offerstatus']."</td>";
            echo "    </tr>\n";
            if (array_key_exists($offerInfo['offerid'], $offerItems) && is_array($offerItems[$offerInfo['offerid']]) && (count($offerItems[$offerInfo['offerid']]) > 0)) {
                echo "<tr class='oidata".$offerInfo['offerid']."' style='display:none;'><td data-label='Items' colspan=6>";
                displayOfferItems($offerItems[$offerInfo['offerid']], $offerInfo['offerdsubtotal']);
                echo "</td></tr>\n";
            }
        }
        echo "  </tbody>\n";
        echo "</table>\n";
    } else {
        echo "You have no matching offers<br />\n";
    }
}

function expireMyOffers() {
    global $page;

    $isValid = true;

    $sql = "
        UPDATE offers
           SET offerstatus  = 'EXPIRED',
               modifydate   = offerexpiration,
               modifiedby   = '".$page->user->username."'
          FROM users  u, users  u2
         WHERE (offers.offerfrom        = ".$page->user->userId."
                OR offers.offerto       = ".$page->user->userId.")
           AND offers.offerstatus       = 'PENDING'
           AND offers.offerexpiration IS NOT NULL
           AND offers.offerexpiration   < nowtoint()
           AND u.userid                 = offers.offeredby
           AND u2.userid                = offers.offerto
        RETURNING offers.offerid, offers.offerfrom, offers.offerto, offers.offeredby, u.username, u2.username as touser
    ";
    $results = $page->db->sql_query($sql);
//echo "Expire SQL:<br /><pre>".$sql."</pre><br />\n";
//echo "<pre>";var_dump($results);echo "</pre><br />\n";
    if (isset($results)) {
        if (is_array($results) and (count($results) > 0)) {
            foreach ($results as $offer) {
                $toId = $offer['offeredby'];
                $toText = $offer['username'];
                $subjectText = "Offer Expired";
                $messageText = "Offer ".$offer['offerid']." expired. ".$offer['touser']." has let your offer expire and you can either resubmit or make a new offer to another member at this time.";
                $messageType = EMAIL;
                $offerId = $offer['offerid'];
                $replyRequired = 0;
                //echo "Create message for offerid ".$offerId."<br />\n";
                $page->iMessage->insertSystemMessage($page, $toId, $toText, $subjectText, $messageText,
                                                     $messageType, NULL, NULL, $offerId, $replyRequired);
            }
        }
    }

    return $isValid;
}

function archiveMyOffers() {
    global $page, $CFG;

    $isValid = false;

    $sql = "UPDATE offers
        SET offerstatus='ARCHIVED'
            ,satisfiedsell = CASE WHEN satisfiedsell=0 THEN ".$CFG->DEFAULT_RATING." ELSE satisfiedsell END
            ,satisfiedbuy = CASE WHEN satisfiedbuy=0 THEN ".$CFG->DEFAULT_RATING." ELSE satisfiedbuy END
        WHERE (offerfrom=".$page->user->userId." OR offerto=".$page->user->userId.")
          AND offerstatus='ACCEPTED'
          AND (disputefromopened IS NULL OR disputefromclosed IS NOT NULL)
          AND (disputetoopened IS NULL OR disputetoclosed IS NOT NULL)
          AND completedon IS NOT NULL
          AND completedon<nowtoint()";
    $result = $page->db->sql_execute($sql);
    if (isset($result)) {
        $isValid = true;
    } else {
        $page->messages->addErrorMsg("Error expiring offers");
    }

    return $isValid;
}

function getAllOffersFiltered($offerFilter, $otherDealer, $filterKeyword, $fromDateTime, $toDateTime, $excludeRated, $eftOnly) {
    global $page;

    expireMyOffers();
    archiveMyOffers();

    $sql = "
        SELECT ofr.offerid, ofr.offerfrom, ofr.offerto, ofr.transactiontype, ofr.offerstatus, ofr.threadid, ofr.countered
              ,CASE WHEN ofr.offerfrom=".$page->user->userId." THEN 1 ELSE 0 END as fromme
              ,CASE WHEN ofr.offerfrom=".$page->user->userId." THEN ut.username ELSE uf.username END as dealername
              ,CASE WHEN ofr.offerfrom=".$page->user->userId." THEN ut.userid ELSE uf.userid END as dealerid
              ,offerdsubtotal
              ,to_char(to_timestamp(ofr.offerexpiration),'MM/DD/YYYY HH24:MI:SS') as expiresat
              ,to_char(to_timestamp(ofr.createdate),'MM/DD/YYYY HH24:MI:SS') as createdt
              ,to_char(to_timestamp(ofr.completedon),'MM/DD/YYYY HH24:MI:SS') as completedat
              ,to_char(to_timestamp(ofr.completedon),'MM/DD/YYYY') as assistuntil
              ,CASE WHEN ofr.offerfrom=".$page->user->userId." THEN
                    CASE WHEN ofr.transactiontype='Wanted' THEN ofr.satisfiedsell ELSE ofr.satisfiedbuy END
                    ELSE
                    CASE WHEN ofr.transactiontype='For Sale' THEN ofr.satisfiedsell ELSE ofr.satisfiedbuy END
                    END AS mysatisfied
             ,ofr.satisfiedsell, ofr.satisfiedbuy
             ,haschats.offerid AS offerchats
             ,hascomplaints.offerid as complaintchats
             ,ofr.carrierid AS hasshipping
        FROM offers             ofr
        JOIN users              uf  ON  uf.userid   = ofr.offerfrom
        JOIN users              ut  ON  ut.userid   = ofr.offerto
        LEFT JOIN (
            SELECT offerid
              FROM messaging
             WHERE messagetype = '".OFFERCHAT."'
               AND (fromid = ".$page->user->userId."
                    OR toid = ".$page->user->userId.")
            GROUP BY offerid
                  )        haschats ON  haschats.offerid    = ofr.offerid
        LEFT JOIN (
            SELECT offerid
              FROM messaging
             WHERE messagetype = '".COMPLAINT."'
               AND (fromid = ".$page->user->userId."
                    OR toid = ".$page->user->userId.")
            GROUP BY offerid
                  )   hascomplaints ON  hascomplaints.offerid   = ofr.offerid
        WHERE (ofr.offerfrom = ".$page->user->userId."
               OR ofr.offerto = ".$page->user->userId.")
    ";

    switch ($offerFilter) {
        CASE 'SALESUNRATED':
            $sql .= " AND (ofr.offerstatus='ACCEPTED' OR ofr.offerstatus='ARCHIVED')\n";
            $sql .= " AND ofr.satisfiedsell=0\n";
            $sql .= " AND (";
            $sql .= "(ofr.transactiontype='For Sale' AND ofr.offerto=".$page->user->userId.")";
            $sql .= " OR ";
            $sql .= "(ofr.transactiontype='Wanted' AND ofr.offerfrom=".$page->user->userId.")";
            $sql .= ")\n";
            BREAK;
        CASE 'SALES':
            $sql .= " AND (ofr.offerstatus='ACCEPTED')\n";
            $sql .= " AND (";
            $sql .= "(ofr.transactiontype='For Sale' AND ofr.offerto=".$page->user->userId.")";
            $sql .= " OR ";
            $sql .= "(ofr.transactiontype='Wanted' AND ofr.offerfrom=".$page->user->userId.")";
            $sql .= ")\n";
            BREAK;
        CASE 'SALESALL':
            $sql .= " AND (ofr.offerstatus='ACCEPTED' OR ofr.offerstatus='ARCHIVED')\n";
            $sql .= " AND (";
            $sql .= "(ofr.transactiontype='For Sale' AND ofr.offerto=".$page->user->userId.")";
            $sql .= " OR ";
            $sql .= "(ofr.transactiontype='Wanted' AND ofr.offerfrom=".$page->user->userId.")";
            $sql .= ")\n";
            BREAK;
        CASE 'PURCHASESUNRATED':
            if ($eftOnly) {
                $sql .= " AND ofr.paymentmethod='EFT'\n";
            }
            $sql .= " AND (ofr.offerstatus='ACCEPTED' OR ofr.offerstatus='ARCHIVED')\n";
            $sql .= " AND ofr.satisfiedbuy=0\n";
            $sql .= " AND (";
            $sql .= "(ofr.transactiontype='For Sale' AND ofr.offerfrom=".$page->user->userId.")";
            $sql .= " OR ";
            $sql .= "(ofr.transactiontype='Wanted' AND ofr.offerto=".$page->user->userId.")";
            $sql .= ")\n";
            BREAK;
        CASE 'PURCHASES':
            if ($eftOnly) {
                $sql .= " AND ofr.paymentmethod='EFT'\n";
            }
            $sql .= " AND ofr.offerstatus='ACCEPTED'\n";
            $sql .= " AND (";
            $sql .= "(ofr.transactiontype='For Sale' AND ofr.offerfrom=".$page->user->userId.")";
            $sql .= " OR ";
            $sql .= "(ofr.transactiontype='Wanted' AND ofr.offerto=".$page->user->userId.")";
            $sql .= ")\n";
            BREAK;
        CASE 'PURCHASESALL':
            if ($eftOnly) {
                $sql .= " AND ofr.paymentmethod='EFT'\n";
            }
            $sql .= " AND (ofr.offerstatus='ACCEPTED' OR ofr.offerstatus='ARCHIVED')\n";
            $sql .= " AND (";
            $sql .= "(ofr.transactiontype='For Sale' AND ofr.offerfrom=".$page->user->userId.")";
            $sql .= " OR ";
            $sql .= "(ofr.transactiontype='Wanted' AND ofr.offerto=".$page->user->userId.")";
            $sql .= ")\n";
            BREAK;
        CASE 'PENDINGIN':
            $sql .= " AND ofr.offerstatus='PENDING' AND ofr.offeredby<>".$page->user->userId;
            BREAK;
        CASE 'PENDINGOUT':
            $sql .= " AND ofr.offerstatus='PENDING' AND ofr.offeredby=".$page->user->userId;
            BREAK;
        CASE 'ARCHSALES':
            $sql .= " AND ofr.offerstatus='ARCHIVED'\n";
            $sql .= " AND (";
            $sql .= "(ofr.transactiontype='For Sale' AND ofr.offerto=".$page->user->userId.")";
            $sql .= " OR ";
            $sql .= "(ofr.transactiontype='Wanted' AND ofr.offerfrom=".$page->user->userId.")";
            $sql .= ")\n";
            BREAK;
        CASE 'ARCHPURCHASES':
            $sql .= " AND ofr.offerstatus='ARCHIVED'\n";
            $sql .= " AND (";
            $sql .= "(ofr.transactiontype='For Sale' AND ofr.offerfrom=".$page->user->userId.")";
            $sql .= " OR ";
            $sql .= "(ofr.transactiontype='Wanted' AND ofr.offerto=".$page->user->userId.")";
            $sql .= ")\n";
            BREAK;
        CASE 'ARCHIVED':
            $sql .= " AND ofr.offerstatus='ARCHIVED'";
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
        CASE 'VOID':
            $sql .= " AND ofr.offerstatus='VOID'";
            BREAK;
        CASE 'EXPIRE6MOS':
            $lastSix = strtotime('-6 months');
            $sql .= " AND ofr.createdate > ".$lastSix."\n";
            $sql .= " AND (";
            $sql .= "   (ofr.offerstatus IN ('ARCHIVED','ACCEPTED')) ";
            $sql .= "   OR ";
            $sql .= "   ((ofr.offerstatus = 'EXPIRED') AND (ofr.offeredby <> ".$page->user->userId.")) ";
            $sql .= "   OR ";
            $sql .= "   ((ofr.offerstatus = 'DECLINED') AND (ofr.countered=0) AND (ofr.offeredby <> ".$page->user->userId.")) ";
            $sql .= " )\n";
            BREAK;
        CASE 'ACCEPT6MOS':
            $lastSix = strtotime('-6 months');
            $sql .= " AND ofr.createdate > ".$lastSix."\n";
            $sql .= " AND (";
            $sql .= "   (ofr.offerstatus IN ('ARCHIVED','ACCEPTED')) ";
            $sql .= "   OR ";
            $sql .= "   ((ofr.offerstatus = 'EXPIRED') AND (ofr.offeredby <> ".$page->user->userId.")) ";
            $sql .= "   OR ";
            $sql .= "   ((ofr.offerstatus = 'DECLINED') AND (ofr.countered=0) AND (ofr.offeredby <> ".$page->user->userId.")) ";
            $sql .= " )\n";
            BREAK;
    }

    if (!empty($fromDateTime)) {
        $sql .= " AND ofr.createdate >= ".$fromDateTime;
    }

    if (!empty($toDateTime)) {
        $sql .= " AND ofr.createdate < ".$toDateTime+86400;
    }

    if (!empty($otherDealer)) {
        $sql .= " AND (";
        $sql .= "(ofr.offerfrom=".$page->user->userId." AND ut.username ilike '%".$otherDealer."%')";
        $sql .= " OR ";
        $sql .= "(ofr.offerto=".$page->user->userId." AND uf.username ilike '%".$otherDealer."%')";
        $sql .= ")";
    }

    if (! empty($filterKeyword)) {
        $sql .= "
            AND ofr.offerid IN (
                SELECT offerid
                FROM offeritems     oi
                JOIN categories     c   on  c.categoryid    = oi.lstcatid
                JOIN subcategories  sc  on  sc.subcategoryid= oi.lstsubcatid
                JOIN boxtypes       bt  on  bt.boxtypeid    = oi.lstboxtypeid
                WHERE oi.lstnotes ilike '%".$filterKeyword."%'
                   OR oi.lstyear ilike '%".$filterKeyword."%'
                   OR c.categorydescription ilike '%".$filterKeyword."%'
                   OR sc.subcategorydescription ilike '%".$filterKeyword."%'
                   OR bt.boxtypename ilike '%".$filterKeyword."%'
            )";
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
function loadOfferItems() {
    global $page, $offerList, $offerItems;

    if (is_array($offerList) && (count($offerList) > 0)) {
        $offerIds = array();
        foreach ($offerList as $offer) {
            $offerItems[$offer['offerid']] = array();
        }
        if (is_array($offerItems) && (count($offerItems) > 0)) {
            $keys = array_keys($offerItems);
            $sql = "SELECT ofr.offerid, ofr.offerfrom, ofr.offerto, ofr.transactiontype, ofr.offerstatus
                    ,CASE WHEN ofr.offerfrom=".$page->user->userId." THEN 1 ELSE 0 END as fromme
                    ,CASE WHEN ofr.offerfrom=".$page->user->userId." THEN ut.username ELSE uf.username END as dealername
                    ,CASE WHEN ofr.offerfrom=".$page->user->userId." THEN ut.userid ELSE uf.userid END as dealerid
                    ,inttodatetime(ofr.offerexpiration) as expiresat
                    ,inttodatetime(oi.accepteddate) as acceptdt
                    ,oi.offeritemid, oi.listingid, oi.lstcatid, oi.lstsubcatid, oi.lstyear, oi.lstboxtypeid, cat.categorytypeid as lstlistingtypeid
                    ,oi.lstyear, oi.lstuom, oi.lstbxpercase, oi.lsttitle
                    ,oi.offerqty, oi.lstqty as maxqty, oi.lstminqty as minqty, oi.lstdprice, oi.offerdprice, (oi.offerqty*oi.offerdprice) as offercost
                    ,oi.revisedqty, oi.reviseddprice, (oi.revisedqty*oi.reviseddprice) as revisedcost
                    ,bt.boxtypename, cat.categoryname, cat.categorydescription, sub.subcategoryname, sub.subcategorydescription
                    ,oi.lstnotes, oi.itemnotes
                    ,l.picture, l.quantity as listingquantity, l.dprice as listingdprice, l.status as listingstatus
                    , pu.upcs, p.variation
                FROM offers         ofr
                JOIN users          uf  ON  uf.userid           = ofr.offerfrom
                JOIN users          ut  ON  ut.userid           = ofr.offerto
                JOIN offeritems     oi  ON  oi.offerid          = ofr.offerid
                JOIN categories     cat on  cat.categoryid      = oi.lstcatid
                JOIN subcategories  sub on  sub.subcategoryid   = oi.lstsubcatid
                JOIN boxtypes       bt  ON  bt.boxtypeid        = oi.lstboxtypeid
                LEFT JOIN listings  l   on  l.listingid         = oi.listingid
                LEFT JOIN products  p   ON  p.active            = 1
                                        AND p.categoryid        = oi.lstcatid
                                        AND p.subcategoryid     = oi.lstsubcatid
                                        AND p.boxtypeid         = oi.lstboxtypeid
                                        AND isnull(p.year, '1') = isnull(oi.lstyear, '1')
                LEFT JOIN (
                    SELECT u.productid, array_to_string(array_agg(upc), '<br>') as upcs
                      FROM product_upc  u
                      JOIN products     p   ON  p.productid = u.productid
                                            AND p.active    = 1
                    GROUP BY u.productid
                        )           pu  ON  pu.productid        = p.productid

                WHERE ofr.offerid in (".implode(",",$keys).")";
            if ($offerItemsRecs = $page->db->sql_query($sql)) {
                foreach ($offerItemsRecs as $offerItem) {
                    $offerItems[$offerItem['offerid']][$offerItem['offeritemid']] = $offerItem;
                }
            }
        }
    } else {
        echo "Offer List empty<br />\n";
    }
}

function acceptedActions($offerInfo) {
    $output = "";

    if ($offerInfo['mysatisfied'] > 0) {
        $output .= "<a href='offer.php?offerid=".$offerInfo['offerid']."#satisfaction' title='Transaction Rating'>\n";
        $output .= "  <span class='fa-stack fa-sm'>\n";
        $output .= "    <i class='fas fa-star fa-stack-2x'></i>\n";
        $output .= "    <i class='fas fa-stack-1x fa-sm'>".$offerInfo['mysatisfied']."</i>\n";
        $output .= "  </span>\n";
        $output .= "</a> ";
    } else {
        $output .= "<a href='offer.php?offerid=".$offerInfo['offerid']."#satisfaction' title='Transaction Rating'>\n";
        $output .= "    <i class='fas fa-star not-rated'></i>\n";
        $output .= "</a> ";
    }
    $output .= "<a href='offer.php?offerid=".$offerInfo['offerid']."#shipping' title='Shipping Info'>\n";
    $output .= "    <i class='fas fa-truck ".(($offerInfo['hasshipping']) ? "shipped' " : " not-shipped'")."></i>\n";
    $output .= "</a> ";
    $output .= "<a href='offer.php?offerid=".$offerInfo['offerid']."&tabid=messages' title='Offer Chat'>\n";
    $output .= "    <i class='fas fa-comment ".(($offerInfo['offerchats']) ? "offer-chat'" : "no-offer-chat'")."></i>\n";
    $output .= "</a> ";
    if ($offerInfo['complaintchats']) {
        $output .= " <a href='offer.php?offerid=".$offerInfo['offerid']."&tabid=assistance' title='ADMIN assistance'>\n";
        $output .= "     <i class='fas fa-message-question admin-assist'></i></a>";
    }
    $output .= " <a href='invoice.php?offerid=".$offerInfo['offerid']."' target=_blank title='Invoice View'>\n";
    $output .= "    <i class='fas fa-print'></i>\n";
    $output .= "</a> ";

    return $output;
}

function displayOfferItems($offerItems, $offerSubtotal) {
    global $page, $UTILITY;

    if (is_array($offerItems) && (count($offerItems) > 0)) {
        $offerListingIds = array();
        echo "<table>\n";
        echo "  <thead>\n";
        echo "    <tr>\n";
        echo "      <th>Product</th>\n";
        echo "      <th>UPC</th>\n";
        echo "      <th>Qty</th>\n";
        echo "      <th>Unit Price</th>\n";
        echo "      <th>Subtotal</th>\n";
        echo "    </tr>\n";
        echo "  </thead>\n";
        echo "  <tbody>\n";
        foreach ($offerItems as $offerItem) {
            if (! empty($offerItem['lstnotes'])) {
                $listingNotes = "<br /><strong>Notes: </strong><span>".$offerItem['lstnotes']."</span>";
            } else {
                $listingNotes = "";
            }
            echo "    <tr>";
            if (!empty($offerItem['picture'])) {
                if ($imgURL = $UTILITY->getListingImageURL($offerItem['picture'])) {
                    echo "<a href='".$imgURL."' target=_blank><img class='align-left' src='".$imgURL."' alt='listing image' width='50px' height='50px'></a> ";
                }
            }
            if ($offerItem['lstcatid'] == CATEGORY_BLAST) {
                echo "<td data-label='Product'>";
                $link = "blastview.php?listingid=".$offerItem['listingid'];
                echo "<a href='".$link."' target=_blank>Blast: ".$offerItem['lsttitle']."</a> ".$listingNotes."<br />\n";
                echo $offerItem['itemnotes'];
                echo "</td>";
                echo "<td align='right'>N/A</td>";
                echo "<td align='right'>N/A</td>";
            } else {
                echo "<td data-label='Product'>";
                $link = "listing.php?subcategoryid=".$offerItem['lstsubcatid']."&boxtypeid=".$offerItem['lstboxtypeid']."&categoryid=".$offerItem['lstcatid']."&listingtypeid=".$offerItem['lstlistingtypeid']."&year=".$offerItem['lstyear'];
                $desc = $offerItem['lstyear']." ~ ".$offerItem['subcategorydescription']." ~ ".$offerItem['categorydescription']." ~ ".$offerItem['boxtypename']." ~ ".$offerItem['lstuom'];
                $desc .= (empty($offerItem['variation'])) ? "" : " ~ ".$offerItem['variation'];
                echo "<a href='".$link."' target=_blank>".$desc."</a> ".$listingNotes;
                echo "</td>";
                $upcs = (empty($offerItem['upcs'])) ? "" : $offerItem['upcs'];
                echo "<td data-label='UPC' class='number'>".$upcs."</td>\n";
                echo "<td data-label='Qty' align='right'>";
                echo $offerItem['offerqty'];
                if ($offerItem['offerqty'] > 0) {
                    if ($offerItem['offerqty'] < $offerItem['minqty']) {
                        echo "<span style='font-weight:bold;color:red;' title='Less than minimum quantity of ".$offerItem['minqty']."'> * </span>";
                    } else {
                        if ($offerItem['offerqty'] > $offerItem['maxqty']) {
                            echo "<span style='font-weight:bold;color:red;' title='Greater than maximum quantity of ".$offerItem['maxqty']."'> * </span>";
                        }
                    }
                }
                echo "</td>";
            }
            echo "<td data-label='Unit Price' align='right'>".floatToMoney($offerItem['offerdprice'])."</td>";
            echo "<td data-label='Offer Subtotal' align='right'>".floatToMoney($offerItem['offercost'])."</td>";
            echo "</tr>\n";
        }
        if (count($offerItems) > 1) {
            echo "<tr><td colspan='3'>&nbsp;</td><th>Total</th><td data-label='Total' align='right'>".floatToMoney($offerSubtotal)."</td></tr>\n";
        }
        echo "  </tbody>\n";
        echo "</table>\n";
    }
}
?>
