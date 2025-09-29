<?php
require_once('templateOffer.class.php');

$page = new templateOffer(LOGIN, SHOWMSG);
$page->requireJS("/scripts/formValidation.js");
$page->requireJS('scripts/tabs.js');
$page->requireStyle("/styles/chatstyles.css' type='text/css' media='all'");

$offerId        = optional_param('offerid', NULL, PARAM_INT);

if ($offerId) {
    $isMine = $page->db->get_field_query("select count(*) from offers where offerid=".$offerId." and (offerto=".$page->user->userId." or offerfrom=".$page->user->userId.")");
    if (! $isMine) {
        if ($page->user->isAdmin()) {
            header('Location:offeradmin.php?offerid='.$offerId);
            exit();
        }
    }
}

$offerToken     = optional_param('offertoken', NULL, PARAM_INT);
$action         = optional_param('action', NULL, PARAM_TEXT);
$updatelistings = optional_param('updatelistings', NULL, PARAM_TEXT);

$toid               = optional_param('toid', NULL, PARAM_INT);
$parentid           = optional_param('parentid', NULL, PARAM_INT);
$threadid           = optional_param('threadid', NULL, PARAM_INT);
$subject            = optional_param('subject', NULL, PARAM_RAW);
$messagebody        = optional_param('message', NULL, PARAM_RAW);
$showMsgs           = optional_param('showmsgs', 0, PARAM_INT);

$adtoid             = optional_param('adtoid', NULL, PARAM_INT);
$adparentid         = optional_param('adparentid', NULL, PARAM_INT);
$adthreadid         = optional_param('adthreadid', NULL, PARAM_INT);
$adsubject          = optional_param('adsubject', NULL, PARAM_RAW);
$admessagebody      = optional_param('admessage', NULL, PARAM_RAW);
$adshowMsgs         = optional_param('adshowmsgs', 0, PARAM_INT);

$selectedTab        = optional_param('tabid', 'offerdata', PARAM_TEXT);
$showListingEdit    = optional_param('showlistingedit', 1, PARAM_INT);
$mode               = optional_param('mode', NULL, PARAM_RAW);
$offerdocumentsid   = optional_param('docid', NULL, PARAM_INT);
$docdescription     = optional_param('description', NULL, PARAM_RAW);


$acceptingOfferOnListings   = false;
$offerInfo                  = null;
$offerItems                 = null;
$offerHistory               = null;
$offerTransactions          = null;
$offerModified              = null;
$myListings                 = null;
$showOffer                  = false;
$terminatingOffer           = false;

if ($offerId) {
    switch ($action) {
        CASE 'CONFACCEPT':
        CASE 'CONFDECLINE':
        CASE 'CONFCANCEL':
            break;
        CASE 'DECLINED':
        CASE 'CANCELLED':
            $terminatingOffer = true;
        CASE 'ACCEPTED':
            doOfferAction($action, $offerId, $offerToken);
            break;
        CASE 'SATISFY':
            setMySatisfied($offerId);
            break;
    }

    switch ($mode) {
        CASE 'savedoc': $filename = "";
                        if (empty($docdescription)) {
                            $mode = "adddoc";
                            $page->messages->addErrorMsg("ERROR: Document name is a required field.");
                        } elseif (isset($_FILES["document"]["name"]) && empty($_FILES["document"]["name"]) ||
                                  !isset($_FILES["document"]["name"])) {
                            $mode = "adddoc";
                            $page->messages->addErrorMsg("ERROR: Please select a file to be uploaded.");
                        } else {
                            if (isset($_FILES["document"]["name"]) && !empty($_FILES["document"]["name"])) {
                                if (isset($_FILES["document"]["error"]) && !empty($_FILES["document"]["error"])) {
                                    $page->messages->addErrorMsg("ERROR: Unable to upload file. [".$_FILES["document"]["error"]."]");
                                } else {
                                    $filename = $_FILES["document"]["name"];
                                    uploadOfferDocument($offerId, $docdescription);
                                }
                            }
                        }
                        break;
        CASE 'deldoc':  if (!empty($offerdocumentsid)) {
                            deleteOfferDocument($offerdocumentsid, $offerId);
                        }
                        break;
    }

    if ($offerInfo = getOfferInfo($offerId)) {
        $offerItems = getOfferItems($offerId);
        $offerHistory = getOfferHistory($offerId);
        $offerTransactions = getOfferTransactions($offerInfo);

        if (($action == 'ACCEPTED') && ($offerInfo['offerto'] == $page->user->userId)) {
            $acceptingOfferOnListings = true;
        }

        if ($action == 'CONFACCEPT') {
            $needSellerInfo = needSellerInfo($offerInfo);
            if ($needSellerInfo == 'Yes') {
                if (empty($offerInfo['sellerpayment'])) {
                    $page->messages->addErrorMsg("The current payment method requires seller payment info in order for you to accept.");
                    $action = null;
                }
            }
        }

        switch ($offerInfo['offerstatus']) {
            // User can not view offer in these statuses unless we just executed the change
            CASE 'DECLINED':
            CASE 'CANCELLED':
            CASE 'EXPIRED':
                if ($terminatingOffer) {
                    $showOffer = true;
                } else {
                    $page->messages->addErrorMsg("Invalid offer status. Offer is ".$offerInfo['offerstatus'].".");
                }
                break;
            DEFAULT:
                $showOffer = true;
                break;
        }
    } else {
        $page->messages->addErrorMsg("Error getting offer");
    }
} else {
    $page->messages->addErrorMsg("No offer specified");
}

if ($showOffer) { // Valid offer access
    // Were listing auto-inactivated
    if (($offerInfo['offerto'] == $page->user->userId)
    &&  (($offerInfo['offerstatus'] == 'PENDING') || ($offerInfo['offerstatus'] == 'DECLINED'))
    &&  ($offerItems && is_array($offerItems) && (count($offerItems) > 0))) {
        foreach ($offerItems as $offerItem) {
            if ($offerItem['listinginactivated']) {
                $listingLink = "<a href='listing.php?referenceid=".$offerItem['listingid']."&showinactive=1' target='_blank'>VIEW</a>";
                $page->messages->addWarningMsg("Listing ID ".$offerItem['listingid']." was automatically inactivated when this offer was placed. ".$listingLink);
            }
        }
    }

    if(!empty($messagebody)) {
        $subject        = trim($subject);
        $messagebody    = trim($messagebody);
        $to             = $page->utility->getUserName($toid);
        if (!empty($toid) && !empty($to) && !empty($subject) && !empty($messagebody)) {
            $page->iMessage->insertMessage($page, $toid, $to, $subject, $messagebody, OFFERCHAT, $threadid, $parentid, $offerId);
            header('Location:offer.php?offerid='.$offerId."&tabid=messages");
            exit();
        }
    }

    if(!empty($admessagebody)) {
        $adsubject        = trim($adsubject);
        $admessagebody    = trim($admessagebody);
        $adto             = $page->utility->getUserName($adtoid);
        //echo "adto:".$adto."(".$adtoid.") adsubject:".$adsubject." admessagebody:".$admessagebody."<br />\n";
        //exit;
        if (!empty($adtoid) && !empty($adto) && !empty($adsubject) && !empty($admessagebody)) {
            $page->iMessage->insertMessage($page, $adtoid, $adto, $adsubject, $admessagebody, COMPLAINT, $adthreadid, $adparentid, $offerId);
            header('Location:offer.php?offerid='.$offerId."&tabid=assistance");
            exit();
        }
    }
    $js = "
        var objDiv = document.getElementById('offerchatdiv');
        var objAdDiv = document.getElementById('adminchatdiv');
        if (objDiv) {
            objDiv.scrollTop = objDiv.scrollHeight;
        }
        if (objAdDiv) {
            objAdDiv.scrollTop = objAdDiv.scrollHeight;
        }
    ";
    $page->jsInit($js);

    if ($updatelistings) {
        updateListings();
    }
    if (($offerInfo['offerstatus'] == 'ACCEPTED') && ($offerInfo['offerto'] == $page->user->userId)) {
        $myListings = getOfferItemsListings($offerId);
    }
    if (! $showMsgs) {
        if (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED')) {
            $msgCount = $page->db->get_field_query("select count(*) as msgcount from messaging where offerid=".$offerInfo['offerid']." and messagetype='".OFFERCHAT."'");
            if ($msgCount > 0) {
                $showMsgs = 1;
            }
        }
    }

    if (! $adshowMsgs) {
        if (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED')) {
            $sql = "select count(*) as msgcount
                    from messaging
                    where offerid=".$offerInfo['offerid']."
                    and messagetype='".COMPLAINT."'
                    and (toid=".$page->user->userId." OR fromid=".$page->user->userId.")";
            $admsgCount = $page->db->get_field_query($sql);
            if ($admsgCount > 0) {
                $adshowMsgs = 1;
            }
        }
    }

    if ($showMsgs || $adshowMsgs) {
        $page->iMessage->updateOfferStatus($page, $offerId, READSTATUS);
    }

    $offerInfo['tovacationend'] = null;
    $offerInfo['fromvacationend'] = null;
    if ($offerId) {
        $toVacationType = ($offerInfo['transactiontype'] == 'Wanted') ? 'Buy' : 'Sell';
        $sql = "SELECT ui.vacationtype, ui.onvacation, ui.returnondate FROM userinfo ui WHERE ui.userid=".$offerInfo['offerto']." AND ui.onvacation IS NOT NULL AND ui.onvacation < nowtoint() AND (ui.returnondate IS NULL OR ui.returnondate > nowtoint()) AND (ui.vacationtype='Both' OR ui.vacationtype='".$toVacationType."')";
        //echo "SQL:".$sql."<br />\n";
        if ($toVacations = $page->db->sql_query($sql)) {
            $toVacation = reset($toVacations);
            $offerInfo['tovacationend'] = date('m/d/Y', $toVacation['returnondate']);
            $toName = ($offerInfo['fromme']) ? $offerInfo['dealername'] : $page->user->username;
            $page->messages->addWarningMsg("Dealer ".$toName." is on vacation until ".$offerInfo['tovacationend']." order processing may be affected");
        }

        $fromVacationType = ($offerInfo['transactiontype'] == 'Wanted') ? 'Sell' : 'Buy';
        $sql = "SELECT ui.vacationtype, ui.onvacation, ui.returnondate FROM userinfo ui WHERE ui.userid=".$offerInfo['offerfrom']." AND ui.onvacation IS NOT NULL AND ui.onvacation < nowtoint() AND (ui.returnondate IS NULL OR ui.returnondate > nowtoint()) AND (ui.vacationtype='Both' OR ui.vacationtype='".$fromVacationType."')";
        //echo "SQL:".$sql."<br />\n";
        if ($fromVacations = $page->db->sql_query($sql)) {
            $fromVacation = reset($fromVacations);
            $offerInfo['fromvacationend'] = date('m/d/Y', $fromVacation['returnondate']);
            $fromName = ($offerInfo['fromme']) ? $page->user->username : $offerInfo['dealername'];
            $page->messages->addWarningMsg("Dealer ".$fromName." is on vacation until ".$offerInfo['fromvacationend']." order processing may be affected");
        }
    }

    $addressNewPeriod = strtotime("-".$CFG->ADDRESS_WARNING_DAYS." days");
    if (!($USER->userId == $offerInfo['billto']['userid'])) {
        if ($addressEdited = $page->db->get_field_query("SELECT modifydate FROM usercontactinfo WHERE userid=".$offerInfo['billto']['userid']." AND addresstypeid=".ADDRESS_TYPE_PAY)) {
            if ($offerInfo['createdate'] > $addressEdited) {
                if ($addressEdited > $addressNewPeriod) {
                    $page->messages->addWarningMsg("NOTE: Dealers Pay To address has changed within the last ".$CFG->ADDRESS_WARNING_DAYS." days. Be sure to use the address supplied in this offer.");
                }
            }
        }
    }
    if (!($USER->userId == $offerInfo['shipto']['userid'])) {
        if ($addressEdited = $page->db->get_field_query("SELECT modifydate FROM usercontactinfo WHERE userid=".$offerInfo['shipto']['userid']." AND addresstypeid=".ADDRESS_TYPE_SHIP)) {
            if ($offerInfo['createdate'] > $addressEdited) {
                if ($addressEdited > $addressNewPeriod) {
                    $page->messages->addWarningMsg("NOTE: Dealers Ship To address has changed within the last ".$CFG->ADDRESS_WARNING_DAYS." days. Be sure to use the address supplied in this offer.");
                }
            }
        }
    }

    if ($offerInfo['countered']) {
        $page->messages->addWarningMsg("This offer includes a counter offer price for at least one item.");
        if ($offerInfo['transactiontype'] == 'Wanted') {
            $hasUnder = false;
            foreach ($offerItems as $offerItem) {
                if (($offerItem['offerqty'] > 0) && ($offerItem['offerdprice'] < $offerItem['lstdprice'])) {
                    $hasUnder = true;
                    break;
                }
            }
            if ($hasUnder) {
                $page->messages->addWarningMsg("This offer includes a counter offer price that is less than the listing price for at least one item.");
            }
        } else {
            $hasOver = false;
            foreach ($offerItems as $offerItem) {
                if (($offerItem['offerqty'] > 0) && ($offerItem['offerdprice'] > $offerItem['lstdprice'])) {
                    $hasOver = true;
                    break;
                }
            }
            if ($hasOver) {
                $page->messages->addWarningMsg("This offer includes a counter offer price that is more than the listing price for at least one item.");
            }
        }
    }
}

echo $page->header('Offer');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $UTILITY, $USER, $showOffer, $offerId, $offerInfo, $offerItems, $offerHistory, $offerTransactions,$selectedTab,
           $myListings, $showMsgs, $adshowMsgs, $showListingEdit, $acceptingOfferOnListings, $mode, $action, $allowSeller;

    if (! $showOffer) { // Invalid offer access
        return;
    }

    $amountPaid = 0;

    if ($offerInfo['offerstatus'] == 'ACCEPTED') {
        $amountPaid = getOfferPayments($offerInfo['offerid']);
    }

    $allowProfileLink = true;
    $otherDealerName = $offerInfo['dealername'];
    if ($allowProfileLink) {
        $otherDealerName = "<a href='dealerProfile.php?dealerId=".$offerInfo['dealerid']."' target='_blank'>".$offerInfo['dealername']."</a>";
    }

    $offerTitle = "";
    if ($offerInfo['fromme']) {
        if ($offerInfo['transactiontype'] == 'Wanted') {
            $offerTitle = "Sell To ".$otherDealerName;
        } else {
            $offerTitle = "Purchase From ".$otherDealerName;
        }
    } else {
        if ($offerInfo['transactiontype'] == 'Wanted') {
            $offerTitle = "Purchase From ".$otherDealerName;
        } else {
            $offerTitle = "Sell To ".$otherDealerName;
        }
    }


    echo "<h3>Offer Id #".$offerInfo['offerid'].": ".$offerTitle."</h3>\n";

    if ($offerInfo['offerstatus'] == 'PENDING') {
            echo "<div>\n";
        if ($offerInfo['myrevision']) {
            if ($action == 'CONFCANCEL') {
                echo $page->messages->showMessage("Are you sure you wish to cancel offer #".$offerInfo['offerid'], MSG_TYPE_WARNING);
                echo "  <a class='button' href='offer.php?offerid=".$offerId."&offertoken=".$offerInfo['modifydate']."&action=CANCELLED'>Yes</a>\n";
                echo "  <a class='button' href='offer.php?offerid=".$offerId."'>No</a>\n";
            } else {
                echo "  <a class='button' href='offer.php?offerid=".$offerId."&offertoken=".$offerInfo['modifydate']."&action=CONFCANCEL'>Cancel Offer</a>\n";
                echo "  <a class='button' href='offer.php?offerid=".$offerId."'>Refresh</a>\n";
          }
        } else {
            if ($action == 'CONFDECLINE') {
                echo $page->messages->showMessage("Are you sure you wish to decline offer #".$offerInfo['offerid'], MSG_TYPE_WARNING);
                echo "  <a class='button' href='offer.php?offerid=".$offerId."&offertoken=".$offerInfo['modifydate']."&action=DECLINED'>Yes</a>\n";
                echo "  <a class='button' href='offer.php?offerid=".$offerId."'>No</a>\n";
            } else {
                if ($action == 'CONFACCEPT') {
                    echo $page->messages->showMessage("Are you sure you wish to accept offer #".$offerInfo['offerid']." ?", MSG_TYPE_WARNING);
                    $sellerStr = ($offerInfo['updateseller']) ? "&sellerpayment=".URLEncode($offerInfo['sellerpayment']) : "";
                    echo "  <a class='button' href='offer.php?offerid=".$offerId."&offertoken=".$offerInfo['modifydate']."&action=ACCEPTED".$sellerStr."'>Yes</a>\n";
                    echo "  <a class='button' href='offer.php?offerid=".$offerId."'>No</a>\n";
                } else {
                    $allowSellerInfo = needSellerInfo($offerInfo);
                    if ($allowSellerInfo && ($allowSellerInfo != 'No')) {
                        $modeStr = ($allowSeller == 'Optional') ? "allows optional" : "requires";
                        echo "<div style='border: solid 1px; padding: 5px;'>\n";
                        echo $page->messages->showMessage("The buyer has revised the offer and the payment method ".$modeStr." seller payment information. Please review/provide your seller payment information if you would like to Accept this offer.", MSG_TYPE_WARNING);
                        echo "  <form name='acceptwanted' id='acceptwanted' action='offer.php' method='post'>\n";
                        echo "    <input type='hidden' name='offerid' id='offerid' value='".$offerId."' />";
                        echo "    <input type='hidden' name='action' id='action' value='CONFACCEPT' />";
                        echo "    <input type='hidden' name='offertoken' id='offertoken' value='".$offerInfo['modifydate']."' />";
                        echo "    <strong>Payment Method:</strong> ".$offerInfo['paymentmethod']."<br />\n";
                        echo "    <strong>Seller Payment Info:</strong> <input type='text' size='50' name='sellerpayment' id='sellerpayment' value='".$offerInfo['sellerpayment']."' />";
                        echo "    <input type='submit' name='submit' id='submit' value='Accept' />\n";
                        echo "  </form>\n";
                        echo "</div><br />\n";
                    } else {
                        echo "  <a class='button' href='offer.php?offerid=".$offerId."&offertoken=".$offerInfo['modifydate']."&action=CONFACCEPT'>Accept</a>\n";
                    }
                    echo "  <a class='button' href='offer.php?offerid=".$offerId."&offertoken=".$offerInfo['modifydate']."&action=CONFDECLINE'>Decline</a>\n";
                    echo "  <a class='button' href='offerRevise.php?offerid=".$offerId."&offertoken=".$offerInfo['modifydate']."'>Revise</a>\n";
                    echo "  <a class='button' href='offer.php?offerid=".$offerId."'>Refresh</a>\n";
                }
            }
        }
        echo "</div><br />\n";
    }

    if (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED')) {
        echo "<div style='float:right;'>";
        echo "<a href='invoice.php?offerid=".$offerInfo['offerid']."' target=_blank title='Invoice View'><i class='fas fa-print'></i></a>";
        echo "</div><br />\n";
    }

    echo "<div class='tab'>\n";
    if (!($USER->userId == $offerInfo['billto']['userid'])) {
        echo displayPageTab('payto',"Pay To: <b>".$offerInfo['billto']['firstname']." ".$offerInfo['billto']['lastname']."</b>(".$offerInfo['billto']['username'].")")."\n";
    }
    if (!($USER->userId == $offerInfo['shipto']['userid'])) {
        echo displayPageTab('shipto',"Ship To: <b>".$offerInfo['shipto']['firstname']." ".$offerInfo['shipto']['lastname']."</b>(".$offerInfo['shipto']['username'].")")."\n";
    }
    echo displayPageTab('offerdata',"Details")."\n";
    echo displayPageTab('items',"Items")."\n";
    if ($showMsgs) {
        echo displayPageTab('messages',"Messages")."\n";
    }
    if ($adshowMsgs) {
        echo displayPageTab('assistance',"Assistance")."\n";
    }
    if (is_array($offerHistory) && (count($offerHistory) > 1)) {
        echo displayPageTab('history',"Offer History")."\n";
    }

    if (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED')) {
        echo displayPageTab("documents","Documents")."\n";
    }
    echo "</div>\n";

    $includePhoneEmail = (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED')) ? true : false;

    if (!($USER->userId == $offerInfo['billto']['userid'])) {
        echo "<div id='payto' class='tabcontent' ".displayTabIfActive('payto').">\n";
        //echo "  <h3>Pay To</h3>\n";
        echo $page->user->formatOfferContactInfo($offerInfo['billto'], $includePhoneEmail);
        if ($offerInfo['sellerpayment']) {
            if ($eom = strpos($offerInfo['paymentmethod'], " -")) {
                $method = substr($offerInfo['paymentmethod'], 0, $eom);
            } else {
                $method = "Payment";
            }
            echo "<br />\n<strong>Seller ".$method." Info:</strong> ".$offerInfo['sellerpayment'];
        }
        echo "</div>\n";
    }
    if (!($USER->userId == $offerInfo['shipto']['userid'])) {
        echo "<div id='shipto' class='tabcontent' ".displayTabIfActive('shipto').">\n";
        //echo "  <h3>Ship To</h3>\n";
        echo $page->user->formatOfferContactInfo($offerInfo['shipto'], $includePhoneEmail);
        echo "</div>\n";
    }

    echo "<div id='offerdata' class='tabcontent' ".displayTabIfActive('offerdata').">\n";
    if ($offerInfo['offernotes']) {
        echo "<table><caption style='float:left;'><strong>Offer Notes:</strong></caption><tr><td>".$page->utility->htmlFriendlyString($offerInfo['offernotes'])."</td></tr></table>\n";
    }
    echo "<table class='sidehead'>\n";
    $acceptInfo = ($offerInfo['offerstatus'] == 'ACCEPTED')? "&nbsp;&nbsp;&nbsp;<strong>Accepted:</strong>".$offerInfo['accepteddate']."&nbsp;&nbsp;&nbsp;<strong>" : "";
    echo "<tr><th>Offer Id</th><td>".$offerInfo['offerid']."</td></tr>\n";
    echo "<tr><th>Status</th><td>".$offerInfo['offerstatus'].$acceptInfo."</td></tr>\n";
    echo "<tr><th>Last Revised By</th><td>".$offerInfo['revisedname']."</td>";
    echo "</tr>\n";
    //echo "<tr><th>Type</th><td>".$offerInfo['transactiontype']."</td></tr>\n";
    echo "<tr><th>Offer Total</th><td>".$offerInfo['offerdsubtotal']."</td></tr>\n";
    echo "<tr><th>Payment Timing</th><td>".$offerInfo['paymenttiming']."</td></tr>\n";
    echo "<tr><th>Payment Method</th><td>".$offerInfo['paymentmethod'];
    if ((($offerInfo['offerstatus'] == 'ACCEPTED') && ($offerInfo['paymentmethod'] == 'EFT'))
    && ((($offerInfo['offerfrom'] == $page->user->userId) && ($offerInfo['transactiontype'] == 'For Sale'))
      ||  (($offerInfo['offerto'] == $page->user->userId) && ($offerInfo['transactiontype'] == 'Wanted'))))  {
        if ($amountPaid < $offerInfo['offerdsubtotal']) {
            echo "&nbsp;&nbsp;&nbsp;<a href='myEFTaction.php?action=pay&offerid=".$offerInfo['offerid']."' target=_blank><button>Make Payment</button></a>";
        }
    }
    echo "</td></tr>\n";
    if ($offerInfo['sellerpayment']) {
        echo "<tr><th>Seller Payment Info</th><td>".$offerInfo['sellerpayment']."</td></tr>\n";
    }
    if (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED')) {
        if (is_array($offerTransactions) && (count($offerTransactions) > 0)) {
            echo "<tr><th>Transactions</th><td>";
            $separator = "";
            foreach($offerTransactions as $trans) {
                echo $separator.$trans['transdesc']."&nbsp;".floatToMoney($trans['dgrossamount']);
                $separator = "<br />\n";
            }
            echo "</td></tr>\n";
        }
    }
    //echo "<tr><th title='Any agreed upon fee is for 3rd party payment processing fees and not inclusive of any Dealernet fees that may be incurred.'>Member Responsible<br />For 3% Payment Processing Fee</th><td>".$offerInfo['whopaysfees']."</td></tr>\n";
    echo "<tr><th>Created</th><td>".$offerInfo['createdat']."</td></tr>\n";
    if ($offerInfo['offerstatus'] == 'PENDING') {
        echo "<tr><th>Expires</th><td>".$offerInfo['expiresat']."</td></tr>\n";
    } else {
        if ($offerInfo['offerstatus'] == 'EXPIRED') {
            echo "<tr><th>Expired</th><td>".$offerInfo['expiresat']."</td></tr>\n";
        }
    }

    if ($offerInfo['offerstatus'] == 'ARCHIVED'){
        echo "<tr><th>Accepted</th><td>".$offerInfo['accepteddate']."</td></tr>\n";
        echo "<tr><th>Archived</th><td>".$offerInfo['completeddate']."</td></tr>\n";
    }

    if (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED')){
        $canEditShipping = false;
        if ($offerInfo['offerstatus'] == 'ACCEPTED') {
            if ($offerInfo['fromme']) {
                if ($offerInfo['transactiontype'] == 'Wanted') {
                    $canEditShipping = true;
                }
            } else {
                if ($offerInfo['transactiontype'] == 'For Sale') {
                    $canEditShipping = true;
                }
            }
        }

        $shipInfo = "Not Provided";
        if ($offerInfo['carriername']) {
            $shipInfo =  $offerInfo['carriername']." ";
            if ($offerInfo['shipdate']) {
                $shipInfo .= " on ".date('m/d/Y', $offerInfo['shipdate'])." ";
            }
            if ($offerInfo['tracking']) {
                $shipInfo .=  "<br />Tracking: ";
                $shipInfo .= "<a href='https://google.com/search?q=".URLEncode($offerInfo['tracking'])."' target=_blank>".$offerInfo['tracking']."</a>";
            }
        }

        if ($canEditShipping) {
            $shipInfo .= "<br /><a href='offershipping.php?offerid=".$offerInfo['offerid']."'>Edit</a>";
        }
        echo "<tr><th>".offsetAnchor('shipping')."Shipping</th><td>".$shipInfo."</td></tr>\n";

        echo "<tr><th>".offsetAnchor('satisfaction')."Transaction Rating</th>";
        echo "<td>";
        for ($indexSatisfied=0; $indexSatisfied <6; $indexSatisfied++) {
            if ($indexSatisfied == $offerInfo['mysatisfied']) {
                echo " <span class='fa-stack fa-sm'><i class='fas fa-star fa-stack-2x'></i><i class='fas fa-stack-1x fa-sm'>".$indexSatisfied."</i></span> ";
            } else {
                if (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['rateuntiltime'] > strtotime("now"))) {
                    echo " <a href='offer.php?offerid=".$offerInfo['offerid']."&offertoken=".$offerInfo['modifydate']."&action=SATISFY&mysatisfied=".$indexSatisfied."'>";
                    echo "<span class='fa-stack fa-sm'><i class='fas fa-star fa-stack-2x not-rated'></i><i class='fas fa-stack-1x fa-sm not-rated'>".$indexSatisfied."</i></span>";
                    echo "</a> ";
                }
            }
        }
        echo " <strong>(rate until ".$offerInfo['rateuntildate'].")</strong>";
        echo "</td></tr>\n";
        if (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['chatuntiltime'] > strtotime("now"))) {
            if (! $showMsgs) {
                echo "<tr><th>".offsetAnchor('chats')."Messages</th>";
                echo "<td><a href='offer.php?offerid=".$offerInfo['offerid']."&showmsgs=1&tabid=messages'>Message Dealer</a>";
                echo " <strong>(chat until ".$offerInfo['chatuntildate'].")</strong></td></tr>\n";
            }
        }
        if (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['assistuntiltime'] > strtotime("now"))) {
            if (! $adshowMsgs) {
                echo "<tr><th>".offsetAnchor('assistance')."Admin Assistance</th>";
                echo "<td><a href='offer.php?offerid=".$offerInfo['offerid']."&adshowmsgs=1&tabid=assistance'>Request Assistance</a>";
                echo " <strong>(available until ".$offerInfo['assistuntildate'].")</strong></td></tr>\n";
            }
        } else {
            if ($offerInfo['offerstatus'] == 'ARCHIVED') {
                $assistMsgBody = "Please provide Admin Assistance with offer id ".$offerInfo['offerid'].".";
                echo "<tr><th>Admin Assistance</th><td><a href='sendmessage.php?dept=1&subject=".URLEncode("Admin Assistance")."&messagebody=".URLEncode($assistMsgBody)."' target='_blank'>Request Special Assistance</a> (click and provide details in the message to request to reopen this offer)</td></tr>\n";
            }
        }
        if (($offerInfo['offerfrom'] == $page->user->userId) && ($offerInfo['disputefromopened'] || $offerInfo['disputefromopened'])) {
            echo "<tr><th>Dispute</th><td><strong>Opened:</strong> ".$offerInfo['disputefromopeneddate']."&nbsp;&nbsp;&nbsp;<strong>Closed:</strong> ".$offerInfo['disputefromcloseddate']."</td></tr>\n";
        }
        if (($offerInfo['offerto'] == $page->user->userId) && ($offerInfo['disputetoopened'] || $offerInfo['disputetoopened'])) {
            echo "<tr><th>Dispute</th><td><strong>Opened:</strong>".$offerInfo['disputetoopeneddate']."&nbsp;&nbsp;&nbsp;<strong>Closed:</strong> ".$offerInfo['disputetocloseddate']."</td></tr>\n";
        }
    }
    echo "</table>\n";
    echo "</div>\n";

    echo "<div id='messages' class='tabcontent' ".displayTabIfActive('messages').">\n";
    if ($showMsgs) {
        echo "<div class='messagecontent'>\n";
        if (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED')) {
            if ($showMsgs) {
                echo offsetAnchor('chats');
                echo "<table><tr><td>";
                displayOfferChat($offerId, $offerInfo);
                echo "</td></tr></table>\n";
                echo "<br />\n";
            }
        }
        echo "</div> <!-- End Message Content -->\n\n";
    }
    echo "</div>\n <!-- End Messages Tab -->\n";
    echo "<div id='assistance' class='tabcontent' ".displayTabIfActive('assistance').">\n";
    if ($adshowMsgs) {
        echo "<div class='assistancecontent'>\n";
        if (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED')) {
            if ($adshowMsgs) {
                echo offsetAnchor('assistance');
                echo "<table><tr><td>";
                displayAdminChat($offerId, $offerInfo);
                echo "</td></tr></table>\n";
                echo "<br />\n";
            }
        }
        echo "</div> <!-- End Assistance Content -->\n\n";
    }
    echo "</div>\n <!-- End Assistance Tab -->\n";
    echo "<div id='items' class='tabcontent' ".displayTabIfActive('items').">\n";

    if (is_array($offerItems) && (count($offerItems) > 0)) {
        $firstItem = reset($offerItems);
        $isBlastOffer = ($firstItem['lstcatid'] == CATEGORY_BLAST) ? true : false;

        $itemEditDisplayMode = ($showListingEdit) ? "" : "style='display:none'";
        $itemHideDisplayMode = ($showListingEdit) ? "style='display:none'" : "";

        if (! $isBlastOffer) {
            if (($offerInfo['offerstatus'] == 'ACCEPTED') && ($offerInfo['offerto'] == $page->user->userId)) {
                echo "<a class='fas fa-edit' title='Show Listing Editor' name='showiedit' id='showiedit' href='#' onclick=\"$(this).hide();$('#itemeditor').show();$('#hideiedit').show();return false;\" ".$itemHideDisplayMode."></a>";
                echo "<a class='fas fa-edit' title='Hide Listing Editor' name='hideiedit' id='hideiedit' href='#' onclick=\"$(this).hide();$('#showiedit').show();;$('#itemeditor').hide();return false;\"  ".$itemEditDisplayMode."></a>";
            }
        }
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
            $offerItemId = $offerItem['offeritemid'];
            $offerListingIds[] = $offerItem['listingid'];
            if (! empty($offerItem['lstnotes'])) {
                $listingNotes = "<br /><strong>Notes:</strong><span>".$offerItem['lstnotes']."</span>";
            } else {
                $listingNotes = "";
            }
            if (! empty($offerItem['lstdeliverby'])) {
                $deliverBy = "<br /><strong>Delivery required by ".(date('m/d/Y', $offerItem['lstdeliverby']))."</strong>";
            } else {
                $deliverBy = "";
            }
            echo "    <tr>\n";
            if ($offerItem['lstcatid'] == CATEGORY_BLAST) {
                echo "<td>\n";
                $link = "blastview.php?listingid=".$offerItem['listingid'];
                echo "<a href='".$link."' target=_blank>Blast: ".$offerItem['lsttitle']."</a> ".$listingNotes."<br />\n";
                echo $offerItem['itemnotes'];
                echo "</td>\n";
                echo "<td align='right'>N/A</td>\n";
            } else {
                echo "<td>\n";
                if (!empty($offerItem['picture'])) {
                    if ($imgURL = $UTILITY->getPrefixListingImageURL($offerItem['picture'])) {
                        echo "<a href='".$imgURL."' target=_blank><img class='align-left' src='".$imgURL."' alt='listing image' width='50px' height='50px'></a> ";
                    }
                }
                if ($offerItem['lstlistingtypeid'] == LISTING_TYPE_SUPPLY) {
                    $pageTarget = "supplySummary.php";
                } else {
                    $pageTarget = "listing.php";
                }
                $link = $pageTarget."?subcategoryid=".$offerItem['lstsubcatid']."&boxtypeid=".$offerItem['lstboxtypeid']."&categoryid=".$offerItem['lstcatid']."&listingtypeid=".$offerItem['lstlistingtypeid']."&year=".$offerItem['lstyear'];
                $product  = $offerItem['lstyear']." ~ ".$offerItem['subcategorydescription']." ~ ".$offerItem['categorydescription']." ~ ".$offerItem['boxtypename'];
                $product .= (empty($offerItem['variation'])) ? "" : " ~ ".$offerItem['variation'];
                $product .= " ~ ".$offerItem['lstuom'];
                echo "<a href='".$link."' target=_blank>".$product."</a> ".$deliverBy.$listingNotes;
                echo "</td>\n";
                $upcs = (empty($offerItem['upcs'])) ? "" : $offerItem['upcs'];
                echo "<td data-label='UPC' class='number'>".$upcs."</td>\n";
                echo "<td align='right'>";
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
                echo "</td>\n";
            }

            $listingPrice = $offerItem['lstdprice'];
            $dprice = $offerItem['offerdprice'];
            $listingFeeMsg = "";
            $listPriceMsg = "";
            if ($listingPrice != $dprice) {
                $listPriceMsg = "(Listed Price: ".$offerItem['lstdprice']."/".$offerItem['lstuom'].")";
                $listingFeeMsg = $listPriceMsg;
                if (($offerInfo['offerfrom'] == $page->user->userId) && $offerInfo['countered'] && ($offerInfo['counterfee'] > 0.00)) {
                    $listingFeeMsg = "Entire counter offer will be subject to a ".$offerInfo['counterfee']."% transaction fee<br />".$listPriceMsg;
                }
            }
            $listingFeeWarning = "<div style='font-weight:bold;' id='feewarning_".$offerItemId."' name='feewarning_".$offerItemId."'>".$listingFeeMsg."</div>";
            echo "<td align='right'>".floatToMoney($offerItem['offerdprice']).$listingFeeWarning."</td>";
            echo "<td align='right'>".floatToMoney($offerItem['offercost'])."</td>";
            echo "</tr>\n";
        }
        if (count($offerItems) > 1) {
            echo "<tr><td colspan='3'>&nbsp;</td><th>Total</th><td align='right'>".floatToMoney($offerInfo['offerdsubtotal'])."</td></tr>\n";
        }
        echo "  </tbody>\n";
        echo "</table>\n";

        if (! $isBlastOffer) {
            if (($offerInfo['offerstatus'] == 'ACCEPTED') && ($offerInfo['offerto'] == $page->user->userId)) {
                if (is_array($myListings) && (count($myListings) > 0)) {
                    echo "<div name='itemeditor' id='itemeditor' ".$itemEditDisplayMode.">\n";
                    echo "<form name ='listingupdate' action='offer.php?offerid=".$offerInfo['offerid']."' method='post'>\n";
                    echo "<input type='hidden' name='offerlistingids' value='".implode(',',$offerListingIds)."' />\n";
                    echo "<input type='hidden' id='tabid' name='tabid' value='items' />\n";
                    echo "<input type='hidden' id='showlistingedit' name='showlistingedit' value='1' />\n";
                    echo "<strong>Update Listings</strong><br />\n";
                    echo "<table>\n";
                    echo "  <caption><div style='float:left;'>Click Listing ID for full editor</div></caption>\n";
                    echo "  <thead>\n";
                    echo "    <tr>\n";
                    echo "      <th>ListingID</th>\n";
                    echo "      <th>Product</th>\n";
                    echo "      <th>UPC</th>\n";
                    echo "      <th>Qty</th>\n";
                    echo "      <th>Price</th>\n";
                    echo "      <th>Active</th>\n";
                    echo "    </tr>\n";
                    echo "  </thead>\n";
                    echo "  <tbody>\n";
                    foreach ($myListings as $myListing) {
                        $editorLink = "mylistingcats.php?displaymode=sc"
                            ."&categoryid=".$myListing['categoryid']
                            ."&subcategoryid=".$myListing['subcategoryid']
                            ."&boxtypeid=".$myListing['boxtypeid']
                            ."&listingtype=".$myListing['type'];
                        if (!empty($myListing['year'])) {
                            $editorLink .= "&year=".URLEncode($myListing['year']);
                        }
                        if ($myListing['status'] == 'CLOSED') {
                            $editorLink .= "&inactive=1";
                        }
                        $datesmsg = "";
                        $expiresmsg = ($myListing['expiresdt']) ? " (Expires: ".$myListing['expiresdt'].") " : "";
                        $delivermsg = ($myListing['deliverdt']) ? " (Deliver By: ".$myListing['deliverdt'].") " : "";
                        if (!(empty($expiresmsg) && empty($delivermsg))) {
                            $datesmsg = "<br>".$expiresmsg.$delivermsg;
                        }
                        echo "    <tr>\n";
                        echo "      <td><a href='".$editorLink."' target='_blank' title='Click for full editor'>".$myListing['listingid']."<input type='hidden' name='listingids[]' value='".$myListing['listingid']."' /></a></td>\n";
                        $product  = $myListing['year']." ~ ".$myListing['subcategorydescription']." ~ ".$myListing['categorydescription']." ~ ".$myListing['boxtypename'];
                        $product .= (empty($myListing['variation'])) ? "" : " ~ ".$myListing['variation'];
                        $product .= " ~ ".$myListing['uom'].$datesmsg;
                        echo "      <td>".$product."</td>\n";
                        $upcs = (empty($myListing['upcs'])) ? "" : $myListing['upcs'];
                        echo "      <td data-label='UPC' class='number'>".$upcs."</td>\n";
                        echo "      <td align=right><input type='text' name='quantity".$myListing['listingid']."' id='quantity".$myListing['listingid']."' size='4' value='".$myListing['quantity']."' /></td>\n";
                        echo "      <td align=right><input type='text' name='dprice".$myListing['listingid']."' id='dprice".$myListing['listingid']."' size='8' value='".$myListing['dprice']."' /></td>\n";
                        echo "      <td><input type='checkbox' name='status".$myListing['listingid']."' id='status".$myListing['listingid']."' value='OPEN' ".(("OPEN" == $myListing['status']) ? " checked " : "")." ></td>\n";
                        echo "    </tr>\n";
                    }
                    echo "  </tbody>\n";
                    echo "  <tfooter><tr><td colspan=7><input type=submit id='updatelistings' name='updatelistings' value='Update Listings' /></td></tr></tfooter>\n";
                    echo "</table>\n";
                    echo "</form>\n";
                    echo "</div>\n";
                }
            }
        }
    }
    echo "</div>\n";
    if (is_array($offerHistory) && (count($offerHistory) > 1)) {
        echo "<div id='history' class='tabcontent' ".displayTabIfActive('history').">\n";
        echo "<table>\n";
        echo "  <thead>\n";
        echo "    <tr>\n";
        echo "      <th>OID</th>\n";
        echo "      <th>Status</th>\n";
        echo "      <th>Created By</th>\n";
        echo "      <th>Created At</th>\n";
        echo "      <th>Modified By</th>\n";
        echo "      <th>Modified At</th>\n";
        echo "      <th>Expires</th>\n";
        echo "      <th>Total</th>\n";
        echo "    </tr>\n";
        echo "  </thead>\n";
        echo "  <tbody>\n";
        $currentOffer = true;
        foreach ($offerHistory as $historyItem) {
            echo "    <tr>";
            if ($historyItem['offerid'] == $offerId) {
                echo "<td>".$historyItem['offerid']."</td>\n";
            } else {
                if ($currentOffer) { // First history item while viewing a revision
                    if ($offerInfo['offerstatus'] == 'REVISED') {
                        echo "<td><a href='offer.php?offerid=".$historyItem['offerid']."'>".$historyItem['offerid']."</a></td>\n";
                    } else {
                        echo "<td>".$historyItem['offerid']."</td>\n";
                    }
                } else {
                    if (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED')) {
                        echo "<td><a href='offer.php?offerid=".$historyItem['offerid']."' target=_blank>".$historyItem['offerid']."</a></td>\n";
                    } else {
                        echo "<td>".$historyItem['offerid']."</td>\n";
                    }
                }
            }
            $currentOffer = false;
            echo "<td>".$historyItem['offerstatus']."</td>\n";
            echo "<td>".$historyItem['createdby']."</td>\n";
            echo "<td>".$historyItem['createdat']."</td>\n";
            echo "<td>".$historyItem['modifiedby']."</td>\n";
            echo "<td>".$historyItem['modifiedat']."</td>\n";
            echo "<td>".$historyItem['expiresat']."</td>\n";
            echo "<td align='right'>".$historyItem['offerdsubtotal']."</td>\n";
            echo "</tr>\n";
        }
        echo "  </tbody>\n";
        echo "</table>\n";
        echo "</div>\n";
    }

    echo "<div id='documents' class='tabcontent', ".displayTabIfActive('documents').">\n";
    displayOfferDocuments($offerInfo['offerid']);
    echo "</div>\n";
}

function displayTabIfActive($tabId) {
    global $page, $selectedTab;

    $isActive = ($tabId == $selectedTab) ? "style='display:block;'" : "";
    return $isActive;
}

function displayPageTab($tabId, $tabLabel) {
    global $page, $selectedTab;

    $isActive = ($tabId == $selectedTab) ? " active" : "";
    return "  <button class='tablinks".$isActive."' onclick='openTab(event, \"".$tabId."\")'>".$tabLabel."</button>";
}

function getOfferInfo($offerId) {
    global $page, $CFG;

    $offer = null;

    if ($offerId) {
        $sql = "
            SELECT ofr.offerid, ofr.threadid, ofr.offerfrom, ofr.offerto, ofr.transactiontype, ofr.offerstatus, ofr.offerdsubtotal
                 , ofr.paymentmethod, ofr.paymenttiming, ofr.paymenttype, ofr.paysfees, ofr.offernotes, ofr.countered, ofr.createdate, ofr.modifydate
                 ,CASE WHEN ofr.offerfrom=".$page->user->userId." THEN 1 ELSE 0 END AS fromme
                 ,CASE WHEN ofr.offerfrom=".$page->user->userId." THEN ut.username ELSE uf.username END AS dealername
                 ,CASE WHEN ofr.offerfrom=".$page->user->userId." THEN ut.userid ELSE uf.userid END AS dealerid
                 ,to_char(to_timestamp(ofr.createdate),'MM/DD/YYYY HH24:MI:SS') as createdat
                 ,to_char(to_timestamp(ofr.offerexpiration),'MM/DD/YYYY HH24:MI:SS') as expiresat
                 ,CASE WHEN ofr.offeredby=".$page->user->userId." THEN 1 ELSE 0 END AS myrevision
                 ,ofr.offeredby
                 ,CASE WHEN ofr.offeredby=ut.userid THEN ut.username ELSE uf.username END AS revisedname
                 ,CASE WHEN up.userid IS NULL THEN 'N/A' ELSE up.username END AS whopaysfees
                 ,ufi.firstname as fromfirstname, ufi.lastname as fromlastname
                 ,uti.firstname as tofirstname, uti.lastname as tolastname
                 ,CASE WHEN ofr.transactiontype='Wanted' THEN uf.userid ELSE ut.userid END AS addrbilluserid
                 ,CASE WHEN ofr.transactiontype='Wanted' THEN uf.username ELSE ut.username END AS addrbillusername
                 ,addrbillstreet, addrbillstreet2, addrbillcity, addrbillstate, addrbillzip, addrbillcountry
                 ,addrbillnote, addrbillacctnote, addrbillfirstname, addrbilllastname, addrbillcompanyname
                 ,addrbillphone, addrbillemail
                 ,CASE WHEN ofr.transactiontype='For Sale' THEN uf.userid ELSE ut.userid END AS addrshipuserid
                 ,CASE WHEN ofr.transactiontype='For Sale' THEN uf.username ELSE ut.username END AS addrshipusername
                 ,addrshipstreet, addrshipstreet2, addrshipcity, addrshipstate, addrshipzip, addrshipcountry
                 ,addrshipnote, addrshipacctnote, addrshipfirstname, addrshiplastname, addrshipcompanyname
                 ,addrshipphone, addrshipemail
                 ,ofr.satisfiedbuy, ofr.satisfiedsell
                 ,ofr.disputetoopened, ofr.disputetoclosed
                 ,ofr.disputefromopened, ofr.disputefromclosed
                 ,ofr.acceptedon, ofr.completedon
                 ,CASE WHEN ofr.offerstatus='ACCEPTED' AND ofr.completedon IS NOT NULL AND ofr.completedon < nowtoint() THEN 1 else 0 END AS iscompleted
                 ,uti.listingfee, ufi.listingfee as counterfee
                 ,uf.username as offerfromname, ut.username as offertoname
                 ,CASE WHEN ofr.offerfrom=".$page->user->userId." THEN
                    CASE WHEN ofr.transactiontype='Wanted' THEN ofr.satisfiedsell ELSE ofr.satisfiedbuy END
                  ELSE
                    CASE WHEN ofr.transactiontype='For Sale' THEN ofr.satisfiedsell ELSE ofr.satisfiedbuy END
                  END AS mysatisfied
                 ,ofr.shipdate, ofr.carrierid, c.carriername, ofr.tracking
                 ,ofr.sellerpayment
            FROM offers         ofr
            JOIN users          uf  on  uf.userid           = ofr.offerfrom
            JOIN userinfo       ufi on  ufi.userid          = uf.userid
            JOIN users          ut  on  ut.userid           = ofr.offerto
            JOIN userinfo       uti on  uti.userid          = ut.userid
            LEFT JOIN users     up  on  up.userid           = ofr.paysfees
            LEFT JOIN carriers  c   on  c.carrierid         = ofr.carrierid
            WHERE (ofr.offerfrom=".$page->user->userId." OR ofr.offerto=".$page->user->userId.")
            AND ofr.offerid=".$offerId;
        if ($results = $page->db->sql_query($sql)) {
            if (is_array($results) && (count($results) > 0)) {
                $offer = reset($results);
                $offer['billto'] = array();
                $offer['billto']['userid'] = $offer['addrbilluserid'];
                $offer['billto']['username'] = $offer['addrbillusername'];
                $offer['billto']['companyname'] = $offer['addrbillcompanyname'];
                $offer['billto']['firstname'] = $offer['addrbillfirstname'];
                $offer['billto']['lastname'] = $offer['addrbilllastname'];
                $offer['billto']['street'] = $offer['addrbillstreet'];
                $offer['billto']['street2'] = $offer['addrbillstreet2'];
                $offer['billto']['city'] = $offer['addrbillcity'];
                $offer['billto']['state'] = $offer['addrbillstate'];
                $offer['billto']['zip'] = $offer['addrbillzip'];
                $offer['billto']['country'] = $offer['addrbillcountry'];
                $offer['billto']['phone'] = $offer['addrbillphone'];
                $offer['billto']['email'] = $offer['addrbillemail'];
                $offer['billto']['addressnote'] = $offer['addrbillnote'];
                $offer['billto']['accountnote'] = $offer['addrbillacctnote'];
                $offer['shipto'] = array();
                $offer['shipto']['userid'] = $offer['addrshipuserid'];
                $offer['shipto']['username'] = $offer['addrshipusername'];
                $offer['shipto']['companyname'] = $offer['addrshipcompanyname'];
                $offer['shipto']['firstname'] = $offer['addrshipfirstname'];
                $offer['shipto']['lastname'] = $offer['addrshiplastname'];
                $offer['shipto']['street'] = $offer['addrshipstreet'];
                $offer['shipto']['street2'] = $offer['addrshipstreet2'];
                $offer['shipto']['city'] = $offer['addrshipcity'];
                $offer['shipto']['state'] = $offer['addrshipstate'];
                $offer['shipto']['zip'] = $offer['addrshipzip'];
                $offer['shipto']['country'] = $offer['addrshipcountry'];
                $offer['shipto']['phone'] = $offer['addrshipphone'];
                $offer['shipto']['email'] = $offer['addrshipemail'];
                $offer['shipto']['addressnote'] = $offer['addrshipnote'];
                $offer['shipto']['accountnote'] = $offer['addrshipacctnote'];
                $offer['disputefromopeneddate'] = ($offer['disputefromopened']) ? date('m/d/Y', $offer['disputefromopened']) : null;
                $offer['disputefromcloseddate'] = ($offer['disputefromclosed']) ? date('m/d/Y', $offer['disputefromclosed']) : null;
                $offer['disputetoopeneddate'] = ($offer['disputetoopened']) ? date('m/d/Y', $offer['disputetoopened']) : null;
                $offer['disputetocloseddate'] = ($offer['disputetoclosed']) ? date('m/d/Y', $offer['disputetoclosed']) : null;
                $offer['accepteddate'] = ($offer['acceptedon']) ? date('m/d/Y', $offer['acceptedon']) : null;
                $offer['completeddate'] = ($offer['completedon']) ? date('m/d/Y', $offer['completedon']) : null;

                $offer['rateuntildate'] = ($offer['accepteddate']) ? date('m/d/Y', strtotime($CFG->ACCEPTED_CLOSE_RATING." days", $offer['acceptedon'])) : null;
                $offer['rateuntiltime'] = ($offer['rateuntildate']) ? strtotime($offer['rateuntildate']." 23:59:59") : null;
                $offer['chatuntildate'] = ($offer['accepteddate']) ? date('m/d/Y', strtotime($CFG->ACCEPTED_CLOSE_CHAT." days", $offer['acceptedon'])) : null;
                $offer['chatuntiltime'] = ($offer['chatuntildate']) ? strtotime($offer['chatuntildate']." 23:59:59") : null;
                $offer['assistuntildate'] = ($offer['completedon']) ? date('m/d/Y', $offer['completedon']) : null;
                $offer['assistuntiltime'] = ($offer['assistuntildate']) ? strtotime($offer['assistuntildate']." 23:59:59") : null;

                getPaymentMethodDetails($offer);
            } else {
                $page->messages->addErrorMsg("Offer not found");
            }
        } else {
            $page->messages->addErrorMsg("Error getting offer");
        }
    } else {
        $page->messages->addErrorMsg("Offer not specified");
    }
    return $offer;
}

function getPaymentMethodDetails(&$offerInfo) {
    global $page;

    $offerInfo['allowinfo'] = 'No';
    $offerInfo['updateseller'] = false;
    $paymentMethodNameOff = strpos($offerInfo['paymentmethod'], " -");

    if (! (($paymentMethodNameOff === false) || ($paymentMethodNameOff < 1))) {
        $paymentMethodName = substr($offerInfo['paymentmethod'], 0, $paymentMethodNameOff);
        $sql = "SELECT * FROM paymenttypes WHERE paymenttypename='".$paymentMethodName."' AND active=1 LIMIT 1";
        if ($results = $page->db->sql_query($sql)) {
            $paymentMethod = reset($results);
            $offerInfo['allowinfo'] = $paymentMethod['allowinfo'];
            if (($offerInfo['allowinfo'] == 'Yes') || ($offerInfo['allowinfo'] == 'Optional')) {
                $sellerPayment = optional_param('sellerpayment', null, PARAM_TEXT);
                //echo "sellerPayment:".$sellerPayment." offerInfo['sellerpayment']:".$offerInfo['sellerpayment']."<br />\n";
                if (isset($sellerPayment)) {
                    //echo "sellerPayment is set<br />\n";
                    if ($offerInfo['sellerpayment'] != $sellerPayment) {
                        //echo "sellerPayment is different<br />\n";
                        $offerInfo['sellerpayment'] = $sellerPayment;
                        $offerInfo['updateseller'] = true;
                    }
                }
            }
        } else {
            $page->messages->addWarningMsg("Unable to get payment method details.");
        }
    }
}

function needSellerInfo($offerInfo) {
    $needInfo = null;

    if ($offerInfo['fromme']) {
        if ($offerInfo['transactiontype'] == 'Wanted') {
            if ($offerInfo['offerstatus'] == 'PENDING') {
                if (! $offerInfo['myrevision']) {
                    $needInfo = $offerInfo['allowinfo'];
                }
            }
        }
    }
    //echo "needSellerInfo: fromme:".$offerInfo['fromme']." trantype:".$offerInfo['transactiontype']." status:".$offerInfo['offerstatus']." myrevision:".$offerInfo['myrevision']." allowinfo:".$offerInfo['allowinfo']." needInfo:".$needInfo."<br />\n";

    return $needInfo;
}

function getOfferItems($offerId) {
    global $page;

    $offerItems = null;

    if ($offerId) {
        $sql = "
            SELECT ofr.offerid, ofr.offerfrom, ofr.offerto, ofr.transactiontype, ofr.offerstatus
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
                  ,oi.lstnotes, oi.itemnotes, oi.countered, oi.lstdeliverby
                  ,l.picture, l.quantity as listingquantity, l.dprice as listingdprice, l.status as listingstatus
                  ,l.expireson, oi.listinginactivated
                  , pu.upcs, p.variation
              FROM offers           ofr
              JOIN users            uf  ON  uf.userid           = ofr.offerfrom
              JOIN users            ut  ON  ut.userid           = ofr.offerto
              JOIN offeritems       oi  ON  oi.offerid          = ofr.offerid
              JOIN categories       cat ON  cat.categoryid      = oi.lstcatid
              JOIN subcategories    sub ON  sub.subcategoryid   = oi.lstsubcatid
              JOIN boxtypes         bt  ON  bt.boxtypeid        = oi.lstboxtypeid
              LEFT JOIN listings    l   ON  l.listingid         = oi.listingid
              LEFT JOIN products    p   ON  p.active            = 1
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

              WHERE ofr.offerid = ".$offerId;
        //echo "offerItems SQL:<br /><pre>".$sql."</pre></br />\n";
        if ($results = $page->db->sql_query($sql)) {
            if (is_array($results) && (count($results) > 0)) {
                $offerItems = $results;
            } else {
                $page->messages->addErrorMsg("Offer has no items");
            }
        } else {
            $page->messages->addErrorMsg("Error getting offer items");
        }
    }

    return $offerItems;
}

function getOfferItemsListings($offerId) {
    global $page;

    $myListings = null;

    if ($offerId) {
        $sql = "
            SELECT l.listingid, l.quantity, l.dprice, l.status, l.year, l.year4, l.picture, l.uom, l.type,
                   c.categorydescription, sc.subcategorydescription, bt.boxtypename,
                   l.categoryid, l.subcategoryid, l.boxtypeid,
                   inttodate(l.expireson) as expiresdt,
                   inttodate(l.deliverby) as deliverdt,
                   oi.listinginactivated,
                   pu.upcs, p.variation
              FROM offeritems       oi
              JOIN listings         l   ON  l.listingid         = oi.listingid
              JOIN categories       c   ON  c.categoryid        = l.categoryid
              JOIN subcategories    sc  ON  sc.subcategoryid    =  l.subcategoryid
              JOIN boxtypes         bt  ON  bt.boxtypeid        = l.boxtypeid
              LEFT JOIN products    p   ON  p.active            = 1
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

            WHERE oi.offerid=".$offerId;
        if ($results = $page->db->sql_query($sql)) {
            if (is_array($results) && (count($results) > 0)) {
                $myListings = $results;
            } else {
                $page->messages->addErrorMsg("Offer has no item listings");
            }
        } else {
            $page->messages->addErrorMsg("Error getting offer item listings");
        }
    }

    return $myListings;
}

function getOfferTransactions($offerInfo) {
    global $page;

    $transactions = null;

    if (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED')) {
        $sql = "SELECT * FROM transactions WHERE offerid IS NOT NULL AND offerid=".$offerInfo['offerid']." AND useraccountid=".$page->user->userId;

        $transactions = $page->db->sql_query($sql);
    }

    return $transactions;
}

function setMySatisfied($offerId) {
    global $page;

    $mySatisfied = optional_param('mysatisfied', NULL, PARAM_INT);

    if (isset($mySatisfied)) {
        $satisfiedField = null;
        $offerInfo = getOfferInfo($offerId);
        if ($offerInfo['offerto'] == $page->user->userId) {
            if ($offerInfo['transactiontype'] == 'Wanted') {
                $satisfiedField = 'satisfiedbuy';
            } else {
                $satisfiedField = 'satisfiedsell';
            }
        } else {
            if ($offerInfo['offerfrom'] == $page->user->userId) {
                if ($offerInfo['transactiontype'] == 'Wanted') {
                    $satisfiedField = 'satisfiedsell';
                } else {
                    $satisfiedField = 'satisfiedbuy';
                }
            }
        }
        if ($satisfiedField) {
            $sql = "UPDATE offers SET ".$satisfiedField."=".$mySatisfied." WHERE offerid=".$offerId;
            //echo "SQL:".$sql."<br />\n";
            if($page->db->sql_execute($sql)) {
                $page->messages->addSuccessMsg("Set Transaction Rating to ".$mySatisfied);
            } else {
                $page->messages->addErrorMsg("Error setting Transaction Rating to ".$mySatisfied);
            }

/*
            $sql = "UPDATE offers SET completedon=nowtoint() WHERE offerid=".$offerId." AND satisfiedsell>0 AND satisfiedbuy>0";
            //echo "SQL:".$sql."<br />\n";
            $result = $page->db->sql_execute($sql);
            if($result == 1) {
                $page->messages->addSuccessMsg("Set completed based on ratings");
                //echo "Completed result:<pre>";var_dump($result);echo"</pre><br />\n";
            }
*/
        }
    }
}

function doOfferAction($action, $offerId, $offerToken) {
    global $page, $daysAcceptedToCompleted, $selectedTab;

    $isValid = 0;
    $inTransaction = false;

    $offerModified = $page->db->get_field_query("SELECT modifydate FROM offers WHERE offerid=".$offerId);
    //echo "OfferToken:".$offerToken." OfferModified:".$offerModified."<br />\n";
    if ($offerToken == $offerModified) {
        switch ($action) {
            CASE 'ACCEPTED':
            CASE 'DECLINED':
                $sql = "SELECT count(*) AS isvalid
                    FROM offers
                    WHERE offerid=".$offerId."
                    AND (offerto=".$page->user->userId." OR offerfrom=".$page->user->userId.")
                    AND offeredby <> ".$page->user->userId."
                    AND offerstatus='PENDING'";
                $isValid = $page->db->get_field_query($sql);
                break;
            CASE 'CANCELLED':
                $sql = "SELECT count(*) AS isvalid
                    FROM offers
                    WHERE offerid=".$offerId."
                    AND offeredby=".$page->user->userId."
                    AND offerstatus='PENDING'";
                $isValid = $page->db->get_field_query($sql);
                break;
        }
    } else {
        $isValid = false;
        $page->messages->addErrorMsg("Unable to complete request. Offer was modified prior to the requested change.");
    }

    $offerInfo = null;

    if ($action == 'ACCEPTED') {
        if ($offerInfo = getOfferInfo($offerId)) {
            if (needSellerInfo($offerInfo)) {
                if (($offerInfo['allowinfo'] == 'Yes') && empty($offerInfo['sellerpayment'])) {
                    $page->messages->addErrorMsg("Unable to Accept offer. Seller Payment Info is required.");
                    $isValid = false;
                }
            }
        } else {
            $page->messages->addErrorMsg("Unable to complete request. Offer details not found.");
            $isValid = false;
        }
    }

//$isValid = false;
//$page->messages->addErrorMsg("DEBUG ERROR");

    if ($isValid) {
        $page->db->sql_begin_trans();
        $inTransaction = true;
        if ($action == "ACCEPTED") {
            if ($offerInfo) {
                $eft = new electronicFundsTransfer();
                $isValid = $eft->makeAcceptFees($offerInfo, false); // false means DB transaction will be handled here not the function
            } else {
                $page->messages->addErrorMsg("Unable to get offer for fees");
                $isValid = false;
            }
        }

        if ($isValid && ($action == "ACCEPTED")) {
            if ($offerInfo['updateseller']) {
                $sql = "UPDATE offers SET sellerpayment='".$offerInfo['sellerpayment']."' WHERE offerid=".$offerId;
                $result = $page->db->sql_execute($sql);
                if (! isset($result)) {
                    $page->messages->addErrorMsg("Unable to set seller payment info.");
                    $isValid = false;
                }
            }
        }

        if ($isValid) {
            $sql = "UPDATE offers
                SET offerstatus = '".$action."'
                    ,modifiedby='".$page->user->username."'
                    ,modifydate=nowtoint()
                ";
            if ($action == 'ACCEPTED') {
                $acceptedOn = time();
                $completedOn = strtotime(date('m/d/Y', strtotime("+".$daysAcceptedToCompleted." days"))." 23:59:59");
                //echo "acceptedOn:".$acceptedOn." completedOn:".$completedOn." days:".$daysAcceptedToCompleted."<br />\n";
                $sql .= " ,acceptedon = ".$acceptedOn."
                    ,completedon=".$completedOn."
                    ";
            }
            $sql .= "WHERE offerid = ".$offerId;
            $result = $page->db->sql_execute($sql);
            if (isset($result)) {
                if ($action == 'ACCEPTED') {
                    $sql = "UPDATE offeritems SET accepteddate=nowtoint() WHERE offerid=".$offerId;
                    $result = $page->db->sql_execute($sql);
                    if (! isset($result)) {
                        $isValid = 0;
                        $page->messages->addErrorMsg("Unable to update offer item acceptance");
                    }
                }
            } else {
                //echo "SQL:".$sql."<br />\n";
                //echo "Result:<pre>";var_dump($result);echo "</pre><br />\n";
                $page->messages->addErrorMsg("Unable to update offer");
                $isValid = 0;
            }
        }

        if ($isValid) {
            if ($action == 'ACCEPTED') {
                // Only create offer history items for Box or Case
                $sql = "INSERT INTO offer_history (transactiondate, type, categoryid, subcategoryid, boxtypeid, year, year4, uom, boxespercase
                        , quantity, listprice, price, boxquantity, boxlistprice, boxprice, offerid
                        , createdby, modifiedby)
                    SELECT o.acceptedon, o.transactiontype, oi.lstcatid, oi.lstsubcatid, oi.lstboxtypeid, oi.lstyear, oi.lstyear4, oi.lstuom, oi.lstbxpercase
                        ,oi.offerqty, oi.lstdprice, oi.offerdprice
                        ,CASE WHEN oi.lstuom='case' THEN (oi.offerqty*oi.lstbxpercase)::numeric(12,2) ELSE oi.offerqty END AS boxquantity
                        ,CASE WHEN oi.lstuom='case' THEN (oi.lstdprice/oi.lstbxpercase)::numeric(12,2) ELSE oi.lstdprice END AS boxlistprice
                        ,CASE WHEN oi.lstuom='case' THEN (oi.offerdprice/oi.lstbxpercase)::numeric(12,2) ELSE oi.offerdprice END AS boxprice
                        ,oi.offerid
                        ,'".$page->user->username."' AS createdby, '".$page->user->username."' AS modifiedby
                    FROM offers o
                    JOIN offeritems oi ON oi.offerid=o.offerid AND oi.lstuom IN ('box', 'case')
                    WHERE o.offerid=".$offerId;
                $result = $page->db->sql_execute($sql);
                if (! isset($result)) {
                    $isValid = 0;
                    $page->messages->addErrorMsg("Unable to update offer history");
                }
            }
        }

        if ($isValid) {
            $page->db->sql_commit_trans();
            $page->messages->addSuccessMsg("Offer status set to ".$action);

            $emailToId = $page->db->get_field_query("SELECT CASE WHEN offerto=".$page->user->userId." THEN offerfrom ELSE offerto END as emailtoid FROM offers WHERE offerid=".$offerId);
            if ($emailToId) {
                $emailToName = $page->db->get_field_query("SELECT username FROM users WHERE userid=".$emailToId);
                $emailSubject = "Offer Updated";
                $emailText = "Offer Id #".$offerId." has been ".$action." by ".$page->user->username;
                if (($action == 'DECLINED') ||($action == 'CANCELLED') || ($action == 'EXPIRED')) {
                    $msgOfferId = NULL;
                } else {
                    $msgOfferId = $offerId;
                }
                $page->iMessage->insertSystemMessage($page, $emailToId, $emailToName, $emailSubject, $emailText, EMAIL, NULL, NULL, $msgOfferId);
            }
            if ($action == 'ACCEPTED') {
                $selectedTab = 'items';
            }
        } else {
            if ($inTransaction) {
                $page->db->sql_rollback_trans();
            }
            $page->messages->addErrorMsg("Unable to update offer");
        }
    }

    return $isValid;
}

function getOfferHistory($offerId) {
    global $page;

    $sql = "SELECT *, to_char(to_timestamp(createdate),'MM/DD/YYYY HH24:MI:SS') as createdat, to_char(to_timestamp(modifydate), 'MM/DD/YYYY HH24:MI:SS') as modifiedat, to_char(to_timestamp(offerexpiration), 'MM/DD/YYYY HH24:MI:SS') as expiresat FROM offers WHERE threadid IN (SELECT threadid FROM offers WHERE offerid=".$offerId.") ORDER BY createdate desc";
    $offerThread = $page->db->sql_query($sql);

    return $offerThread;
}

function getOfferPayments($offerId) {
    global $page;

    $sql = "SELECT sum(dgrossamount) as paid FROM transactions WHERE offerid=".$offerId." AND transtype='RECEIPT'";

    $amountPaid = $page->db->get_field_query($sql);

    return $amountPaid;
}

function updateListings() {
    global $page;

    $updatedListings = false;
    $success = true;

    $listingIds = optional_param('listingids', NULL, PARAM_TEXT);
    if (is_array($listingIds) && (count($listingIds) > 0)) {
        $updatedListingIds = array();
        $page->db->sql_begin_trans();
        foreach ($listingIds as $listingId) {
            $sql = "select * from listings where listingid=".$listingId;
            if ($result = $page->db->sql_query($sql)) {
                $listingInfo = reset($result);
                $maxQty = optional_param('quantity'.$listingId, NULL, PARAM_INT);
                $dprice = optional_param('dprice'.$listingId, NULL, PARAM_NUM_NO_COMMA);
                $status = optional_param('status'.$listingId, NULL, PARAM_TEXT);
                if ($status != "OPEN") {
                    $status = "CLOSED";
                }
                if (($listingInfo['quantity'] != $maxQty)
                ||  ($listingInfo['dprice'] != $dprice)
                ||  ($listingInfo['status'] != $status)) {
                    if ($maxQty < 1) {
                        $page->messages->addErrorMsg("Quantity for listing ".$listingId." must be greater than 0.");
                        $success = false;
                        break;
                    }

                    if (! ($dprice > 0)) {
                        $page->messages->addErrorMsg("Price for listing ".$listingId." must be greater than 0.");
                        $success = false;
                    } else {
                        if ($listingInfo['boxespercase'] > 0) {
                            $boxprice = round(($dprice / $listingInfo['boxespercase']), 2);
                        } else {
                            $page->messages->addErrorMsg("Error updating listing ".$listingId." invalid boxes per case.");
                            $success = false;
                            break;
                        }
                    }

                    if ($success) {
                        $sql = "UPDATE listings SET quantity    = :quantity,
                                                dprice       = :dprice,
                                                boxprice     = :boxprice,
                                                status       = :status,
                                                modifiedby   = :modifiedby,
                                                modifydate   = nowtoint()
                             WHERE listingid = :listingid";
                        $params = array();
                        $params['listingid']        = $listingId;
                        $params['quantity']         = $maxQty;
                        $params['dprice']           = $dprice;
                        $params['boxprice']         = $boxprice;
                        $params['status']           = $status;
                        $params['modifiedby']       = $page->user->username;

                        //echo "Update Listing SQL:<br /><pre>".$sql."\nParams:\n";var_dump($params);echo "</pre><br />\n";
                        $result = $page->db->sql_execute_params($sql, $params);
                        if (empty($result)) {
                            $page->messages->addErrorMsg("Error updating listing ".$listingId);
                            $success = false;
                            break;
                        } else {
                            $updatedListings = true;
                            $updatedListingIds[] = $listingId;
                            //$page->messages->addSuccessMsg("Successfully updated listing ".$listingId);
                        }
                    }
                }
            } else {
                $page->messages->addErrorMsg("Error updating listing ".$listingId." listing not found.");
                $success = false;
                break;
            }
        }
        if ($success) {
            if (count($updatedListingIds) > 0) {
                $page->db->sql_commit_trans();
                $page->messages->addSuccessMsg("Successfully updated listings: ".implode(', ', $updatedListingIds));
            } else {
                $page->db->sql_rollback_trans();
            }
        } else {
            $page->db->sql_rollback_trans();
            $page->messages->addErrorMsg("Updates rolled back.");
        }
    }
}

function displayOfferChat($offerid, $theOffer) {
    global $page;

    $offerinfo = $page->iMessage->getOfferinfo($offerid);

    if (($offerinfo['offerstatus'] == 'ACCEPTED') || ($theOffer['chatuntiltime'] > strtotime('now'))) {
        echo "<FORM  NAME='offerchat' ID='offerchat' ACTION='offer.php'  OnSubmit='return VerifyFields(this)' METHOD='POST'>\n";
        echo "<strong>(chat available until ".$theOffer['chatuntildate'].")</strong>\n";
    }

    echo "  <div id='offerchatdiv' class='commentArea' style='border:1px solid #EEE;width:450px; height:400px;overflow: auto;'>\n";
    if ($offerinfo["offerfrom"] == $page->user->userId) {
        echo "    <div style='float:left;'>".$offerinfo["to_username"]."</div>\n";
        echo "    <div style='float:right;'>".$page->user->username."</div>\n";
    } else {
        echo "    <div style='float:left;'>".$offerinfo["from_username"]."</div>\n";
        echo "    <div style='float:right;'>".$page->user->username."</div>\n";
    }
    if ($messages = $page->iMessage->getOfferThread($offerid)) {
        $prevchatdate = 0;
        foreach($messages as $m) {
            if (date("m/d/Y H", $prevchatdate) <> date("m/d/Y H", $m["createdate"])) {
                $prevchatdate = $m["createdate"];
                if (strtotime("today") < $m["createdate"] &&
                    strtotime("now") > $m["createdate"]) {
                    echo "<div class='chatDate'>Today, ".date("h:iA", $m["createdate"])."</div>";
                } else {
                    echo "<div class='chatDate'>".date("l, F j, Y", $m["createdate"])."</div>";
                }
            }
            if ($m["fromid"] == $page->user->userId) {
                echo "    <div class='bubbledMe'>";
                echo stripslashes(str_replace("\n","<br>",str_replace("\c\n","<br>",$m['messagetext'])));
                echo "</div>\n";
                echo "    <div class='stampedMe'>".date('g:i a',$m["createdate"])."</div>";
            } else {
                echo "    <div class='bubbledNotMe'>";
                echo stripslashes(str_replace("\n","<br>",str_replace("\c\n","<br>",$m['messagetext'])));
                echo "</div>\n";
                echo "    <div class='stampedNotMe'>".date('g:i a',$m["createdate"])."</div>";
            }
            $lastmessage = $m;
        }
    }
    echo "  </div>\n";

    if (($offerinfo['offerstatus'] == 'ACCEPTED') || ($theOffer['chatuntiltime'] > strtotime('now'))) {
        echo "  <textarea style='width:425px;height:135px;margin-top:5px;' name='message' id='message'></textarea>\n";
        echo "  <input type='hidden'  id='offerid' name='offerid' value='".$offerinfo["offerid"]."' />\n";
        echo "  <input type='hidden'  id='tabid' name='tabid' value='messages' />\n";
        $toid = ($offerinfo["offerto"] == $page->user->userId) ? $offerinfo["offerfrom"] : $offerinfo["offerto"];
        echo "  <input type='hidden' name='toid' value='".$toid."'>\n";
        $subject = (isset($lastmessage["subjecttext"]) && !empty($lastmessage["subjecttext"])) ? $lastmessage["subjecttext"] : $offerinfo["transactiontype"]." by ".$offerinfo["from_username"];
        echo "  <input type='hidden' name='subject' value='".$subject."'>\n";
        $parentid = (isset($lastmessage["messageid"]) && !empty($lastmessage["messageid"])) ? $lastmessage["messageid"] : 0;
        echo "  <input type='hidden' name='parentid' value='".$parentid."'>\n";
        $threadid = (isset($lastmessage["threadid"]) && !empty($lastmessage["threadid"])) ? $lastmessage["threadid"] : 0;
        echo "  <input type='hidden' name='threadid' value='".$threadid."'>\n";
        echo "  <a href=javascript:void(0);' style='font-weight:bold;' name='submitbtn' onclick='Javascript:if (VerifyFields(document.offerchat)) { document.offerchat.submit();} else { return false;} '>SEND</a>\n";
        echo "<br /><strong>(chat available until ".$theOffer['chatuntildate'].")</strong>\n";
        echo "</FORM>\n";

        echo "<SCRIPT LANGUAGE='JavaScript' TYPE='text/javascript'>\n";
        echo "<!--\n";
        echo "\n";
        echo "  function VerifyFields(f) {\n";
        echo "    var a = [\n";
        echo "              [/^message$/,   'Message',      'text',    true,   5000],\n";
        echo "            ];\n";
        echo "\n";
        echo "    m = '';\n";
        echo "    for (i = 0; i < f.elements.length; i++) {\n";
        echo "        for (j = 0; j < a.length; j++) {\n";
        echo "           if (f.elements[i].name.match(a[j][0])) {\n";
        echo "               m = m + VerifyTextField(f.elements[i], a[j][1], a[j][2], a[j][3], a[j][4]);\n";
        echo "               break;\n";
        echo "           }\n";
        echo "        }\n";
        echo "    }\n";
        echo "\n";
        echo "    if (m != '') {\n";
        echo "        alert('The following fields contain values that are not permitted or are missing values:\\n\\n' + m);\n";
        echo "        return false;\n";
        echo "    } else {\n";
        echo "        return true;\n";
        echo "    }\n";
        echo "  }\n";
        echo "\n";
        echo "//-->\n";
        echo "\n";
        echo "</SCRIPT>\n";
    }
}

function displayAdminChat($offerid, $theOffer) {
    global $page;

    $offerinfo = $page->iMessage->getOfferinfo($offerid);

    if (($offerinfo['offerstatus'] == 'ACCEPTED') || ($theOffer['assistuntiltime'] > strtotime('now'))) {
        echo "<FORM  NAME='adminchat' ID='adminchat' ACTION='offer.php'  OnSubmit='return VerifyFields(this)' METHOD='POST'>\n";
        echo "<strong>(assistance available until ".$theOffer['assistuntildate'].")</strong>\n";
    }

    echo "  <div id='adminchatdiv' class='commentArea' style='border:1px solid #EEE;width:450px; height:400px;overflow: auto;'>\n";
    if ($offerinfo["offerfrom"] == $page->user->userId) {
        echo "    <div style='float:left;'>".ADMINUSERNAME."</div>\n";
        echo "    <div style='float:right;'>".$page->user->username."</div>\n";
    } else {
        echo "    <div style='float:right;'>".$page->user->username."</div>\n";
        echo "    <div style='float:left;'>".ADMINUSERNAME."</div>\n";
    }
    if ($messages = $page->iMessage->getComplaintThread($offerid, $page->user->userId)) {
        $prevchatdate = 0;
        foreach($messages as $m) {
            if (date("m/d/Y H", $prevchatdate) <> date("m/d/Y H", $m["createdate"])) {
                $prevchatdate = $m["createdate"];
                if (strtotime("today") < $m["createdate"] &&
                    strtotime("now") > $m["createdate"]) {
                    echo "<div class='chatDate'>Today, ".date("h:iA", $m["createdate"])."</div>";
                } else {
                    echo "<div class='chatDate'>".date("l, F j, Y", $m["createdate"])."</div>";
                }
            }
            if ($m["fromid"] == $page->user->userId) {
                echo "    <div class='bubbledMe'>";
                echo stripslashes(str_replace("\n","<br>",str_replace("\c\n","<br>",$m['messagetext'])));
                echo "</div>\n";
                echo "    <div class='stampedMe'>".date('g:i a',$m["createdate"])."</div>";
            } else {
                echo "    <div class='bubbledNotMe'>\n";
                echo stripslashes(str_replace("\n","<br>",str_replace("\c\n","<br>",$m['messagetext'])));
                echo "</div>\n";
                echo "    <div class='stampedNotMe'>".date('g:i a',$m["createdate"])."</div>";
            }
            $lastmessage = $m;
        }
    }
    echo "  </div>\n";

    if (($offerinfo['offerstatus'] == 'ACCEPTED') || ($theOffer['assistuntiltime'] > strtotime('now'))) {
        echo "  <textarea style='width:425px;height:135px;margin-top:5px;' name='admessage' id='admessage'></textarea>\n";
        echo "  <input type='hidden' name='offerid' value='".$offerinfo["offerid"]."' />\n";
        echo "  <input type='hidden' name='tabid' value='assistance' />\n";
        $toid = ADMINUSERID;
        echo "  <input type='hidden' name='adtoid' value='".$toid."'>\n";
        $subject = (isset($lastmessage["subjecttext"]) && !empty($lastmessage["subjecttext"])) ? $lastmessage["subjecttext"] : $offerinfo["transactiontype"]." by ".$offerinfo["from_username"];
        echo "  <input type='hidden' name='adsubject' value='".$subject."'>\n";
        $parentid = (isset($lastmessage["messageid"]) && !empty($lastmessage["messageid"])) ? $lastmessage["messageid"] : 0;
        echo "  <input type='hidden' name='adparentid' value='".$parentid."'>\n";
        $threadid = (isset($lastmessage["threadid"]) && !empty($lastmessage["threadid"])) ? $lastmessage["threadid"] : 0;
        echo "  <input type='hidden' name='adthreadid' value='".$threadid."'>\n";
        echo "  <a href=javascript:void(0);' style='font-weight:bold;' name='submitbtn' onclick='Javascript:if (VerifyAdminFields(document.adminchat)) { document.adminchat.submit();} else { return false;} '>SEND</a>\n";
        echo "<br /><strong>(assistance available until ".$theOffer['assistuntildate'].")</strong>\n";
        echo "</FORM>\n";

        echo "<SCRIPT LANGUAGE='JavaScript' TYPE='text/javascript'>\n";
        echo "<!--\n";
        echo "\n";
        echo "  function VerifyAdminFields(f) {\n";
        echo "    var a = [\n";
        echo "              [/^message$/,   'Message',      'text',    true,   5000],\n";
        echo "            ];\n";
        echo "\n";
        echo "    m = '';\n";
        echo "    for (i = 0; i < f.elements.length; i++) {\n";
        echo "        for (j = 0; j < a.length; j++) {\n";
        echo "           if (f.elements[i].name.match(a[j][0])) {\n";
        echo "               m = m + VerifyTextField(f.elements[i], a[j][1], a[j][2], a[j][3], a[j][4]);\n";
        echo "               break;\n";
        echo "           }\n";
        echo "        }\n";
        echo "    }\n";
        echo "\n";
        echo "    if (m != '') {\n";
        echo "        alert('The following fields contain values that are not permitted or are missing values:\\n\\n' + m);\n";
        echo "        return false;\n";
        echo "    } else {\n";
        echo "        return true;\n";
        echo "    }\n";
        echo "  }\n";
        echo "\n";
        echo "//-->\n";
        echo "\n";
        echo "</SCRIPT>\n";
    } else {
        $assistMsgBody = "Please provide Admin Assistance with offer id ".$offerid.".";
        echo "<a href='sendmessage.php?dept=1&subject=".URLEncode("Admin Assistance")."&messagebody=".URLEncode($assistMsgBody)."' target='_blank'>Request additional assistance</a><br />\nClick and provide details in the message to request to reopen this offer.\n";
    }
}

function displayOfferDocuments($offerid) {
    global $CFG, $page, $mode;

    echo "<form name='offerdocs' id='offerdocs' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post' enctype='multipart/form-data'>\n";
    echo "  <table>\n";
    echo "    <caption>\n";
    echo "      <p>\n";
    echo "        <a href='javascript:document.offerdocs.mode.value=\"adddoc\"; document.offerdocs.submit();'>Upload Document</a>\n";
    echo "      </p>\n";
    echo "    </caption>\n";
    echo "    <thead>\n";
    echo "      <tr>\n";
    echo "        <th>Document</th>\n";
    echo "        <th>Uploaded By</th>\n";
    echo "        <th colspan='2'>Upload Date</th>\n";
    echo "      </tr>\n";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    $docs = getOfferDocumentsData($offerid);
    if (empty($docs)) {
        if (!empty($mode)) {
            echo "      <tr><td colspan='4'>No Documents have been uploaded.</td></tr>\n";
        }
    }
    if ($mode == "adddoc") {
        echo "      <tr>\n";
        echo "        <td><input type='text' name='description' maxlength='250' value='' required></td>\n";
        echo "        <td colspan='2'>\n";
        echo "          <input type='file' name='document' id='document' required>\n";
        echo "          <br />(Max:".(round(($CFG->ATTACH_MAX_UPLOAD/1000000),2))."MB)\n";
        echo "        </td>\n";
        echo "        <td class='fa-action-items'>\n";
        echo "          <a class='fas fa-check-circle' title='Save' href='' onclick='javascript:document.offerdocs.mode.value=\"savedoc\"; document.offerdocs.submit();return false;'></a>\n";
        echo "          <a class='fas fa-times-circle' title='Cancel' href='".htmlentities($_SERVER['PHP_SELF'])."?offerid=".$offerid."&tabid=documents' />\n";
        echo "        </td>\n";
        echo "      </tr>\n";
    }
    if (!empty($docs)) {
        foreach($docs as $d) {
            echo "      <tr>\n";
            $doc  = $CFG->offerDocsPath.$d["location"];
            $url  = "/imageviewer.php?img=".$doc;
            $link = "<a href='".$url."' target='_blank'>".htmlspecialchars_decode($d["description"], ENT_QUOTES)."</a>";
            echo "        <td>".$link."</td>\n";
            echo "        <td>".$d["username"]."</td>\n";
            echo "        <td>".date("F j, Y h:i:sA", $d["createdate"])."</td>\n";
            echo "        <td>\n";
            if ($d["userid"] == $page->user->userId) {
                $onclick = "JavaScript:document.offerdocs.mode.value=\"deldoc\"; document.offerdocs.docid.value=\"".$d["offerdocumentsid"]."\"; document.offerdocs.submit();";
                echo "          <a  class='fas fa-trash-alt' title='Delete' href='".$onclick."' \n";
                echo "            onclick=\"javascript: return confirm('Are you sure you want to permanently delete - ".addslashes($d['description'])."')\"></a>\n";
            } else {
                echo "          &nbsp;\n";
            }
            echo "        </td>\n";
            echo "      </tr>\n";
        }
    }
    echo "    </tbody>\n";
    echo "  </table>\n";
    echo "  <input type='hidden' name='offerid' id='offerid' value='".$offerid."'/>\n";
    echo "  <input type='hidden' name='docid' id='docid' value=''/>\n";
    echo "  <input type='hidden' name='mode' id='mode' value=''/>\n";
    echo "  <input type='hidden' name='tabid' id='tabid' value='documents'/>\n";
    echo "</form>\n";

}

function getOfferDocumentsData($offerid) {
    global $page;

    $sql = "
        SELECT u.userid, u.username,
               od.offerdocumentsid, od.description, od.location, od.createdate
          FROM offer_documents      od
          JOIN users                u   ON  u.userid        = od.userid
          JOIN offers               o   ON  o.offerid       = od.offerid
                                        AND o.offerid       = ".$offerid."
                                        AND (o.offerto      = ".$page->user->userId."
                                             OR o.offerfrom = ".$page->user->userId.")
         WHERE od.isdeleted = 0
        ORDER BY od.createdate, od.description
    ";

    $rs = $page->db->sql_query_params($sql);

    return $rs;
}

function uploadOfferDocument($offerid, $docdescription) {
    global $CFG, $page;

    $documentid = $page->utility->nextval("offer_documents_offerdocumentsid_seq");
    $offerdir = $CFG->offerDocs.$offerid."/";
    if (!is_dir($offerdir)) {
        mkdir($offerdir);
    }
    $filename   = $page->iMessage->attachFile($_FILES["document"], $documentid, $offerdir, $page);

    if (!empty($filename) && !empty($docdescription)) {
        $sql = "
            INSERT INTO offer_documents(offerdocumentsid, offerid, userid, description, location, createdby)
            VALUES (:offerdocumentsid, :offerid, :userid, :description, :location, :createdby)
        ";
        $params = array();
        $params["offerdocumentsid"] = $documentid;
        $params["offerid"]          = $offerid;
        $params["userid"]           = $page->user->userId;
        $params["description"]      = $docdescription;
        $params["location"]         = $offerid."/".$filename;
        $params["createdby"]        = $page->user->username;

        try {
            $page->db->sql_execute_params($sql, $params);
            $page->messages->addSuccessMsg("Offer document has been uploaded.");
            if ($otherside = getOtherSide($offerid)) {
                $subject  = "Document uploaded for offer #".$offerid;
                $message  = "A document was uploaded by ".$page->user->username." for offer #".$offerid;
                $page->iMessage->insertSystemMessage($page, $otherside["othersideid"], $otherside["otherside"], $subject, $message, OFFERDOC, null, null, $offerid);
            }

        } catch (Exception $e) {
            $page->messages->addErrorMsg("ERROR: ".$e->getMessage()." [Unable to upload document.]");
        } finally {
        }
    } else {
        $page->messages->addErrorMsg("ERROR: Unable to upload document.");
    }
}

function deleteOfferDocument($offerdocumentsid, $offerid) {
    global $CFG, $page;

    $sql = "
        UPDATE offer_documents
           SET isdeleted    = 1,
               modifiedby   = :modifiedby,
               modifydate   = nowtoint()
         WHERE offerdocumentsid = ".$offerdocumentsid."
           AND userid           = ".$page->user->userId."
           AND offerid          = ".$offerid."
    ";
    $params = array();
    $params["modifiedby"] = $page->user->username;

    try {
        $page->db->sql_execute_params($sql, $params);
        $page->messages->addSuccessMsg("Offer document has been deleted.");
    } catch (Exception $e) {
        $page->messages->addErrorMsg("ERROR: ".$e->getMessage()." [Unable to delete document.]");
    } finally {
    }

}

function getOtherSide($offerid) {
    global $page;

    $sql = "
        SELECT CASE WHEN offerto = ".$page->user->userId." THEN offerfrom
                    ELSE offerto END                    AS othersideid,
               cASE WHEN offerto = ".$page->user->userId." THEN ofr.username
                    ELSE oto.username END               AS otherside
          FROM offers       o
          JOIN users        oto ON  oto.userid = o.offerto
          JOIN users        ofr ON  ofr.userid = o.offerfrom
         WHERE offerid = ".$offerid."
        LIMIT 1
    ";

    $record = null;
    if ($rs = $page->db->sql_query_params($sql)) {
        $record = reset($rs);
    }

    return $record;
}
?>