<?php
require_once('templateAdmin.class.php');

$page = new templateAdmin(LOGIN, SHOWMSG);
$page->requireJS("/scripts/formValidation.js");
$page->requireStyle("/styles/chatstyles.css' type='text/css' media='all'");

$offerId        = optional_param('offerid', NULL, PARAM_INT);
$action         = optional_param('action', NULL, PARAM_TEXT);
$updatelistings = optional_param('updatelistings', NULL, PARAM_TEXT);

$showMsgs       = optional_param('showmsgs', 0, PARAM_INT);

$dltoid           = optional_param('dltoid', NULL, PARAM_INT);
$dlparentid       = optional_param('dlparentid', NULL, PARAM_INT);
$dlthreadid       = optional_param('dlthreadid', NULL, PARAM_INT);
$dlsubject        = optional_param('dlsubject', NULL, PARAM_RAW);
$dlmessagebody    = optional_param('dlmessage', NULL, PARAM_RAW);
$dlshowMsgs       = optional_param('dlshowmsgs', 0, PARAM_INT);

$dotoid           = optional_param('dotoid', NULL, PARAM_INT);
$doparentid       = optional_param('doparentid', NULL, PARAM_INT);
$dothreadid       = optional_param('dothreadid', NULL, PARAM_INT);
$dosubject        = optional_param('dosubject', NULL, PARAM_RAW);
$domessagebody    = optional_param('domessage', NULL, PARAM_RAW);
$doshowMsgs       = optional_param('doshowmsgs', 0, PARAM_INT);

$updateDispute    = optional_param('updatedispute', 0, PARAM_INT);

$reverseFees      = optional_param('reversefees', 1, PARAM_INT);

if ($updateDispute) {
    if ($offerId) {
        $setStrings = array();
        $tmpStr = optional_param('disputetoopened', NULL, PARAM_RAW);
        $setStrings['disputetoopened'] = "disputetoopened=".((empty($tmpStr)) ? "NULL" : strtotime($tmpStr));
        $tmpStr = optional_param('disputetoclosed', NULL, PARAM_RAW);
        $setStrings['disputetoclosed'] = "disputetoclosed=".((empty($tmpStr)) ? "NULL" : strtotime($tmpStr." 23:59:59"));
        $tmpStr = optional_param('disputefromopened', NULL, PARAM_RAW);
        $setStrings['disputefromopened'] = "disputefromopened=".((empty($tmpStr)) ? "NULL" : strtotime($tmpStr));
        $tmpStr = optional_param('disputefromclosed', NULL, PARAM_RAW);
        $setStrings['disputefromclosed'] = "disputefromclosed=".((empty($tmpStr)) ? "NULL" : strtotime($tmpStr." 23:59:59"));
        $tmpStr = optional_param('accepteddate', NULL, PARAM_RAW);
        $setStrings['accepteddate'] = "acceptedon=".((empty($tmpStr)) ? "NULL" : strtotime($tmpStr));
        $tmpStr = optional_param('completeddate', NULL, PARAM_RAW);
        $setStrings['completeddate'] = "completedon=".((empty($tmpStr)) ? "NULL" : strtotime($tmpStr." 23:59:59"));

        $disputeStatus = optional_param('disputestatus', NULL, PARAM_TEXT);
        if ($disputeStatus) {
            $offerStatus = $page->db->get_field_query("SELECT offerstatus FROM offers WHERE offerid=".$offerId);
            if ($offerStatus != $disputeStatus) {
                if (($offerStatus = 'ARCHIVED') && ($disputeStatus == 'ACCEPTED')) {
                    $setStrings['offerstatus'] = "offerstatus='".$disputeStatus."'";
                }
            }
        }

        $sql = "UPDATE offers
                SET ".implode(",",$setStrings)."
                WHERE offerid=".$offerId;
        if ($page->db->sql_execute_params($sql)) {
            $page->messages->addSuccessMsg("Updated dispute/acceptance dates");
        } else {
            $page->messages->addErrorMsg("Error updating dispute dates");
        }
    } else {
        $page->messages->addErrorMsg("Error updating dispute dates, no offerid");
    }
}


if(!empty($dlmessagebody)) {
    $dlsubject        = trim($dlsubject);
    $dlmessagebody    = trim($dlmessagebody);
    $dlto             = $page->utility->getUserName($dltoid);
    //echo "adto:".$adto."(".$adtoid.") adsubject:".$adsubject." admessagebody:".$admessagebody."<br />\n";
    //exit;
    if (!empty($dltoid) && !empty($dlto) && !empty($dlsubject) && !empty($dlmessagebody)) {
        $page->iMessage->insertMessage($page, $dltoid, $dlto, $dlsubject, $dlmessagebody, COMPLAINT, $dlthreadid, $dlparentid, $offerId);
        header('Location:offeradmin.php?offerid='.$offerId);
        exit();
    }
}

if(!empty($domessagebody)) {
    $dosubject        = trim($dosubject);
    $domessagebody    = trim($domessagebody);
    $doto             = $page->utility->getUserName($dotoid);
    //echo "adto:".$adto."(".$adtoid.") adsubject:".$adsubject." admessagebody:".$admessagebody."<br />\n";
    //exit;
    if (!empty($dotoid) && !empty($doto) && !empty($dosubject) && !empty($domessagebody)) {
        $page->iMessage->insertMessage($page, $dotoid, $doto, $dosubject, $domessagebody, COMPLAINT, $dothreadid, $doparentid, $offerId);
        header('Location:offeradmin.php?offerid='.$offerId);
        exit();
    }
}
$js = "
    var objDiv = document.getElementById('offerchatdiv');
    var objDLDiv = document.getElementById('dlchatdiv');
    var objDODiv = document.getElementById('dochatdiv');
    if (objDiv) { objDiv.scrollTop = objDiv.scrollHeight; }
    if (objDLDiv) { objDLDiv.scrollTop = objDLDiv.scrollHeight; }
    if (objDODiv) { objDODiv.scrollTop = objDODiv.scrollHeight;}
";
$page->jsInit($js);

$jsCalendar = '
    $(function(){$("#disputetoopened").datepicker();});
    $(function(){$("#disputetoclosed").datepicker();});
    $(function(){$("#disputefromopened").datepicker();});
    $(function(){$("#disputefromclosed").datepicker();});
    $(function(){$("#accepteddate").datepicker();});
    $(function(){$("#completeddate").datepicker();});
';
$page->jsInit($jsCalendar);

$offerInfo = null;
$offerItems = null;
$offerHistory = null;
$offerTransactions = null;

if ($offerId) {
    if ($action == 'VOID') {
        voidOffer($offerId, $reverseFees);
    }

    if ($offerInfo = getOfferInfo($offerId)) {
        $offerItems = getOfferItems($offerId);
        $offerHistory = getOfferHistory($offerId);
        $offerTransactions = getOfferTransactions($offerInfo);
    }
} else {
    $page->messages->addErrorMsg("No offer specified");
}

if (! $showMsgs) {
    if  (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED') || ($offerInfo['offerstatus'] == 'VOID')) {
        $msgCount = $page->db->get_field_query("select count(*) as msgcount from messaging where offerid=".$offerInfo['offerid']." and messagetype='".OFFERCHAT."'");
        if ($msgCount > 0) {
            $showMsgs = 1;
        }
    }
}

if (! $dlshowMsgs) {
    if  (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED') || ($offerInfo['offerstatus'] == 'VOID')) {
        $sql = "select count(*) as msgcount
                from messaging
                where offerid=".$offerInfo['offerid']."
                and messagetype='".COMPLAINT."'
                and (toid=".$offerInfo['offerto']." OR fromid=".$offerInfo['offerto'].")";
        $dlmsgCount = $page->db->get_field_query($sql);
        if ($dlmsgCount > 0) {
            $dlshowMsgs = 1;
        }
    }
}

if (! $doshowMsgs) {
    if  (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED') || ($offerInfo['offerstatus'] == 'VOID')) {
        $sql = "select count(*) as msgcount
                from messaging
                where offerid=".$offerInfo['offerid']."
                and messagetype='".COMPLAINT."'
                and (toid=".$offerInfo['offerto']." OR fromid=".$offerInfo['offerfrom'].")";
        $domsgCount = $page->db->get_field_query($sql);
        if ($domsgCount > 0) {
            $doshowMsgs = 1;
        }
    }
}

if ($dlshowMsgs || $doshowMsgs) {
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
        $page->messages->addWarningMsg("Dealer ".$offerInfo['listername']." is on vacation until ".$offerInfo['tovacationend']." order processing may be affected");
    }

    $fromVacationType = ($offerInfo['transactiontype'] == 'Wanted') ? 'Sell' : 'Buy';
    $sql = "SELECT ui.vacationtype, ui.onvacation, ui.returnondate FROM userinfo ui WHERE ui.userid=".$offerInfo['offerfrom']." AND ui.onvacation IS NOT NULL AND ui.onvacation < nowtoint() AND (ui.returnondate IS NULL OR ui.returnondate > nowtoint()) AND (ui.vacationtype='Both' OR ui.vacationtype='".$fromVacationType."')";
    //echo "SQL:".$sql."<br />\n";
    if ($fromVacations = $page->db->sql_query($sql)) {
        $fromVacation = reset($fromVacations);
        $offerInfo['fromvacationend'] = date('m/d/Y', $fromVacation['returnondate']);
        $page->messages->addWarningMsg("Dealer ".$offerInfo['dealername']." is on vacation until ".$offerInfo['fromvacationend']." order processing may be affected");
    }
}
echo $page->header('Offer');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $UTILITY, $offerId, $offerInfo, $offerItems, $offerHistory, $offerTransactions, $showMsgs, $dlshowMsgs, $doshowMsgs;

    $amountPaid = 0;

    if ($offerInfo) {
        if  (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED') || ($offerInfo['offerstatus'] == 'VOID')) {
            $amountPaid = getOfferPayments($offerInfo['offerid']);
        }

        echo "<h3>Offer(".$offerInfo['offerid'].") From Dealer:".$offerInfo['dealername']."(".$offerInfo['dealerid'].") To Dealer:".$offerInfo['listername']."(".$offerInfo['listerid'].")</h3>\n";

        if (($offerInfo['offerstatus'] != 'VOID') && ($offerInfo['offerstatus'] != 'REVISED')) {
            echo "<div>\n";
            echo "  <a class='button' href='offeradmin.php?offerid=".$offerId."&action=VOID&reversefees=1' onClick=\"return confirm('Are you sure you want to void this offer and REVERSE all fees?');\">Void Offer - All Transactions</a>\n";
            echo "  <a class='button' href='offeradmin.php?offerid=".$offerId."&action=VOID&reversefees=0' onClick=\"return confirm('Are you sure you want to void this offer and KEEP all fees?');\">Void Offer - Retain Fees</a>\n";
            echo "</div><br />\n";
        }

        echo "<table><theader><tr><th>Pay To</th><th>Ship To</th></tr></theader>\n";
        echo "<tr><td style='vertical-align:top'>";
        echo $page->user->formatOfferContactInfo($offerInfo['billto']);
        echo "</td><td style='vertical-align:top'>";
        echo $page->user->formatOfferContactInfo($offerInfo['shipto']);
        echo "</td>\n";
        echo "</tr>\n";
        echo "</table>\n";

        echo "<table class='sidehead'>\n";
        echo "<tr><th>Status</th><td>".$offerInfo['offerstatus']."</td><td rowspan=8>&nbsp;</td></tr>\n";
        echo "<tr><th>Last Revised By</th><td>".$offerInfo['revisedname']."</td></tr>\n";
        echo "<tr><th>Type</th><td>".$offerInfo['transactiontype']."</td></tr>\n";
        echo "<tr><th>Offer Total</th><td>".$offerInfo['offerdsubtotal']."</td></tr>\n";
        echo "<tr><th>Payment Timing</th><td>".$offerInfo['paymenttiming']."</td></tr>\n";
        echo "<tr><th>Payment Method</th><td>".$offerInfo['paymentmethod'];
        if  (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED') || ($offerInfo['offerstatus'] == 'VOID')) {
            if ($offerInfo['paymentmethod'] == 'EFT') {
                if ($amountPaid > 0) {
                    echo "&nbsp;&nbsp;&nbsp;(".floatToMoney($amountPaid)." paid)";
                }
            }
        }
        echo "</td></tr>\n";
        if ($offerInfo['sellerpayment']) {
            echo "<tr><th>Seller Payment Info</th><td>".$offerInfo['sellerpayment']."</td></tr>\n";
        }
        echo "<tr><th title='Any agreed upon fee is for 3rd party payment processing fees and not inclusive of any Dealernet fees that may be incurred.'>Member Responsible<br />For 3% Payment Processing Fee</th><td>".$offerInfo['whopaysfees']."</td></tr>\n";
        echo "<tr><th>Created</th><td>".$offerInfo['createdat']."</td></tr>\n";
        echo "<tr><th>Expires</th><td>".$offerInfo['expiresat']."</td></tr>\n";
        if  (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED') || ($offerInfo['offerstatus'] == 'VOID')) {
            if (is_array($offerTransactions) && (count($offerTransactions) > 0)) {
                echo "<tr><th>Transactions</th><td>";
                $separator = "";
                foreach($offerTransactions as $trans) {
                    echo $separator.$trans['useraccountname'].": ".$trans['transdesc']."&nbsp;".floatToMoney($trans['dgrossamount']);
                    $separator = "<br />\n";
                }
                echo "</td></tr>\n";
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
            echo "<tr><th>Shipping</th><td>".$shipInfo."</td></tr>\n";
            echo "<tr><th>Satisfied</th><td>Buyer:".$offerInfo['satisfiedbuy']." / Seller: ".$offerInfo['satisfiedsell']."</td></tr>\n";
            if (! $showMsgs) {
                echo "<tr><th>Messages</th><td>No Messages</td></tr>\n";
            }
            if (! $dlshowMsgs) {
                echo "<tr><th>".$offerInfo['listername']." Dispute</th><td><a href='offeradmin.php?offerid=".$offerInfo['offerid']."&dlshowmsgs=1'>No Dispute</a></td></tr>\n";
            }
            if (! $doshowMsgs) {
                echo "<tr><th>".$offerInfo['dealername']." Dispute</th><td><a href='offeradmin.php?offerid=".$offerInfo['offerid']."&doshowmsgs=1'>No Dispute</a></td></tr>\n";
            }
        }
        echo "</table>\n";

        if  (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED') || ($offerInfo['offerstatus'] == 'VOID')) {
            $listingDisputeAlert = ($offerInfo['disputetoopeneddate'] && (! $offerInfo['disputetocloseddate'])) ? " <i class='fa fa-exclamation-triangle'>" : "";
            $offeringDisputeAlert = ($offerInfo['disputefromopeneddate'] && (! $offerInfo['disputefromcloseddate'])) ? " <i class='fa fa-exclamation-triangle'>" : "";
            echo "<form name='offeradmindisputes' id='' action='offeradmin.php' method='post'>\n";
            echo "  <input type='hidden' name='offerid' id='offerid' value='".$offerId."' />\n";
            echo "  <input type='hidden' name='updatedispute' id='updatedispute' value='1' />\n";
            echo "  <table>\n";
            echo "    <theader>\n";
            echo "      <tr><th colspan=2>".$offerInfo['listername']." Dispute".$listingDisputeAlert."</th><th colspan=2>".$offerInfo['dealername']." Dispute".$offeringDisputeAlert."</th></tr>\n";
            echo "      <tr><th>Opened</th><th>Closed</th><th>Opened</th><th>Closed</th></tr>\n";
            echo "    </theader>\n";
            echo "    <tbody>\n";
            echo "      <tr>\n";
            echo "        <td><input type=text name='disputetoopened' id='disputetoopened' value='".$offerInfo['disputetoopeneddate']."' /></td>\n";
            echo "        <td><input type=text name='disputetoclosed' id='disputetoclosed' value='".$offerInfo['disputetocloseddate']."' /></td>\n";
            echo "        <td><input type=text name='disputefromopened' id='disputefromopened' value='".$offerInfo['disputefromopeneddate']."' /></td>\n";
            echo "        <td><input type=text name='disputefromclosed' id='disputefromclosed' value='".$offerInfo['disputefromcloseddate']."' /></td>\n";
            echo "      </tr>\n";
            echo "      <tr><th colspan=4>Acceptance Dates</th></tr>\n";
            echo "      <tr><th>Accepted</th><th>Complete On</th><th colspan=2>Status</th></tr>\n";
            echo "      <tr>\n";
            echo "        <td><input type=text name='accepteddate' id='accepteddate' value='".$offerInfo['accepteddate']."' /></td>\n";
            echo "        <td><input type=text name='completeddate' id='completeddate' value='".$offerInfo['completeddate']."' /></td>\n";
            if (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'VOID')) {
                echo "        <td colspan=2>".$offerInfo['offerstatus']."</td>\n";
            } else {
                echo "<td colspan=2>\n";
                echo "<select name='disputestatus' id='disputestatus'>\n";
                echo "  <option value='ACCEPTED' ".$page->utility->isChecked($offerInfo['offerstatus'], "ACCEPTED", 'selected').">ACCEPTED</option>\n";
                echo "  <option value='ARCHIVED' ".$page->utility->isChecked($offerInfo['offerstatus'], "ARCHIVED", 'selected').">ARCHIVED</option>\n";
                echo "</select>";
                echo "</td>\n";
            }
            echo "      </tr>\n";
            echo "    </tbody>\n";
            echo "    <tfooter>\n";
            echo "      <tr>\n";
            echo "        <td><input type='submit' name'updatedisputes' id='updatedisputes' value='Update' /></td>\n";
            echo "        <td colspan=3>Notes to Admin: ";
            echo "<a href='#' class='noteshide' style='display:none;' title='Hide Notes' onClick='$(\".notesdata\").hide();$(\".noteshide\").hide(); $(\".notesshow\").show();return(false);'><i class='fa-solid fa-square-minus'></i></a>";
            echo "<a href='#' class='notesshow' title='Show Notes' onClick='$(\".notesdata\").show();$(\".noteshide\").show(); $(\".notesshow\").hide();return(false);'><i class='fa-solid fa-square-plus'></i></a>";
            echo "        </td>\n";
            echo "      </tr>\n";
            echo "      <tr class='notesdata' style='display:none;'>\n";
            echo "        <td data-label='Items' colspan=4>";
            echo "          <ul>\n";
            echo "            <li>If the offer is ACCEPTED and you delete the Completed On date it will not get ARCHIVED until you supply a Completed On date</li>\n";
            echo "            <li>If the offer is ACCEPTED and and a dispute is OPEN\n";
            echo "              <ul>the Completed On date will be ignored until all disputes are closed</li>\n";
            echo "                <li>When the disputes are closed the batch will ARCHIVE the offer</li>";
            echo "                <li>The Archive Date will be the Completed On date.</li>\n";
            echo "                <li>If the Archive Date matters, it should be set as part of closing a dispute.</li>\n";
            echo "              </ul>\n";
            echo "            </li>\n";
            echo "            <li>Offer batch will ARCHIVE offer when:\n";
            echo "              <ul>\n";
            echo "                <li>Offer Status is ACCEPTED</li>\n";
            echo "                <li>No disputes have been opened OR both disputes are closed</li>\n";
            echo "                <li>The Completed On date is set AND has passed (time for Completed On is 23:59:59)</li>\n";
            echo "                <li>NOTE: setting the status back to ACCEPTED without opening a dispute or changing the Completed On date will be reversed by the batch on next execution (hourly)</li>\n";
            echo "              </ul>\n";
            echo "            </li>\n";
            echo "            <li>If offer is ARCHIVED starting an assistance chat above will be visible to the dealer, but they can not respond.</li>\n";
            echo "          </ul>\n";
            echo "        </td>\n";
            echo "      </tr>\n";
            echo "     </tfooter>\n";
            echo "  </table>\n";
            echo "</form><br />\n";
        }


        echo "<br />\n";
        if ($offerInfo['offernotes']) {
            echo "<strong>Offer Notes:</strong><br />\n";
            echo "<table><tr><td>".$offerInfo['offernotes']."</td></tr></table>\n";
            echo "<br />\n";
        }
        if  (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED') || ($offerInfo['offerstatus'] == 'VOID')) {
            if ($showMsgs) {
                echo "<strong>Messages:</strong><br />\n";
                echo "<table><tr><td>";
                displayOfferChat($offerId);
                echo "</td></tr></table>\n";
                echo "<br />\n";
            } else {
            }
        }
        if  (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED') || ($offerInfo['offerstatus'] == 'VOID')) {
            if ($dlshowMsgs) {
                echo "<strong>".$offerInfo['listername']." Dispute:</strong><br />\n";
                echo "<table><tr><td>";
                displayAdminChat($offerId, true, $offerInfo['offerto'], $offerInfo['listername']);
                echo "</td></tr></table>\n";
                echo "<br />\n";
            } else {
            }
        }
        if  (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED') || ($offerInfo['offerstatus'] == 'VOID')) {
            if ($doshowMsgs) {
                echo "<strong>".$offerInfo['dealername']." Dispute:</strong><br />\n";
                echo "<table><tr><td>";
                displayAdminChat($offerId, false, $offerInfo['offerfrom'], $offerInfo['dealername']);
                echo "</td></tr></table>\n";
                echo "<br />\n";
            } else {
            }
        }
        echo "<strong>Items:</strong><br />\n";
        if (is_array($offerItems) && (count($offerItems) > 0)) {
            $offerListingIds = array();
            echo "<table>\n";
            echo "  <theader><tr><th>OID</th><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></theader>\n";
            echo "  <tbody>\n";
            foreach ($offerItems as $offerItem) {
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
                echo "    <tr>";
                echo "<td>".$offerItem['offeritemid']."</td>";
                if (!empty($offerItem['picture'])) {
                    if ($imgURL = $UTILITY->getListingImageURL($offerItem['picture'])) {
                        echo "<a href='".$imgURL."' target=_blank><img class='align-left' src='".$imgURL."' alt='listing image' width='50px' height='50px'></a> ";
                    }
                }
                if ($offerItem['lstcatid'] == CATEGORY_BLAST) {
                    echo "<td>";
                    $link = "blastview.php?listingid=".$offerItem['listingid'];
                    echo "<a href='".$link."' target=_blank>Blast: ".$offerItem['lsttitle']."</a> ".$listingNotes."<br />\n";
                    echo $offerItem['itemnotes'];
                    echo "</td>";
                    echo "<td align='right'>N/A</td>";
                } else {
                    echo "<td>";
                    if ($offerItem['lstlistingtypeid'] == LISTING_TYPE_SUPPLY) {
                        $pageTarget = "supplySummary.php";
                    } else {
                        $pageTarget = "listing.php";
                    }
                    $link = $pageTarget."?subcategoryid=".$offerItem['lstsubcatid']."&boxtypeid=".$offerItem['lstboxtypeid']."&categoryid=".$offerItem['lstcatid']."&listingtypeid=".$offerItem['lstlistingtypeid']."&year=".$offerItem['lstyear'];
                    echo "<a href='".$link."' target=_blank>".$offerItem['lstyear']." ~ ".$offerItem['subcategorydescription']." ~ ".$offerItem['categorydescription']." ~ ".$offerItem['boxtypename']." ~ ".$offerItem['lstuom']."</a> ".$deliverBy.$listingNotes;
                    echo "</td>";
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
                    echo "</td>";
                }
                echo "<td align='right'>".$offerItem['offerdprice']."</td>";
                echo "<td align='right'>".$offerItem['offercost']."</td>";
                echo "</tr>\n";
            }
            if (count($offerItems) > 1) {
                echo "<tr><td colspan='3'>&nbsp;</td><th>Total</th><td align='right'>".$offerInfo['offerdsubtotal']."</td></tr>\n";
            }
            echo "  </tbody>\n";
            echo "</table>\n";
        }

        if (is_array($offerHistory) && (count($offerHistory) > 1)) {
            echo "<strong>Offer History</strong><br />\n";
            echo "<table>\n";
            echo "  <theader><tr><th>OID</th><th>Status</th><th>Created By</th><th>Created At</th><th>Modified By</th><th>Modified At</th><th>Expires</th><th>Total</th></tr></theader>\n";
            echo "  <tbody>\n";
            foreach ($offerHistory as $historyItem) {
                echo "    <tr>";
                if ($historyItem['offerid'] == $offerId) {
                    echo "<td>".$historyItem['offerid']."</td>";
                } else {
                    echo "<td><a href='offeradmin.php?offerid=".$historyItem['offerid']."' target=_blank>".$historyItem['offerid']."</a></td>";
                }
                echo "<td>".$historyItem['offerstatus']."</td>";
                echo "<td>".$historyItem['createdby']."</td>";
                echo "<td>".$historyItem['createdat']."</td>";
                echo "<td>".$historyItem['modifiedby']."</td>";
                echo "<td>".$historyItem['modifiedat']."</td>";
                echo "<td>".$historyItem['expiresat']."</td>";
                echo "<td align='right'>".$historyItem['offerdsubtotal']."</td>";
                echo "</tr>\n";
            }
            echo "  </tbody>\n";
            echo "</table>\n";
        }
    }
}

function voidOffer($offerId, $reverseFees) {
    global $page;

    $success = false;
    $inTransaction = false;


    if ($offerInfo = getOfferInfo($offerId)) {
        $page->db->sql_begin_trans();
        $inTransaction = true;
        $sql = "UPDATE offers SET offerstatus='VOID'
                , satisfiedbuy=0
                , satisfiedsell=0
                , modifiedby='".$page->user->username."'
                , modifydate=nowtoint()
            WHERE offerid=".$offerId;
        if ($page->db->sql_execute($sql)) {
            $page->messages->addSuccessMsg("Updated offer status to VOID.");
            if (reverseOfferTransactions($offerInfo, $reverseFees)) {
                $page->messages->addSuccessMsg("Reversed transactions.");

                $historyCount = $page->db->get_field_query("SELECT count(*) FROM offer_history WHERE offerid=".$offerId);
                if ($historyCount > 0) {
                    if ($page->db->sql_execute("DELETE FROM offer_history WHERE offerid=".$offerId)) {
                        $page->messages->addSuccessMsg("Removed offer history.");
                    } else {
                        // Don't Stop on this...
                        $page->messages->addWarningMsg("Error removing offer history.");
                    }
                }

                $msgSubject = "Offer ".$offerId." has been VOIDED";
                if ($reverseFees) {
                    $msgBody = "Offer ".$offerId." has been VOIDED and all EFT transactions have been reversed.";
                } else {
                    $msgBody = "Offer ".$offerId." has been VOIDED and EFT transactions between dealers have been reversed. Fees still apply.";
                }
                if ($page->iMessage->insertSystemMessage($page, $offerInfo['offerfrom'], $offerInfo['dealername'], $msgSubject, $msgBody, EMAIL, NULL, NULL, $offerId)) {
                    $page->messages->addSuccessMsg("Notified dealer ".$offerInfo['dealername']);
                    if ($page->iMessage->insertSystemMessage($page, $offerInfo['offerto'], $offerInfo['listername'], $msgSubject, $msgBody, EMAIL, NULL, NULL, $offerId)) {
                        $page->messages->addSuccessMsg("Notified dealer ".$offerInfo['listername']);
                        $success = true;
                    } else {
                        $page->messages->addErrorMsg("Error notifying dealer ".$offerInfo['listername']);
                    }
                } else {
                    $page->messages->addErrorMsg("Error notifying dealer ".$offerInfo['dealername']);
                }
            } else {
                $page->messages->addErrorMsg("Error reversing transactions.");
            }
        } else {
            $page->messages->addErrorMsg("Error setting offer status to VOID.");
        }
    } else {
        $page->messages->addErrorMsg("Unable to void offer, offer not found.");
    }

    if ($success) {
        $page->db->sql_commit_trans();
        $page->messages->addSuccessMsg("Offer status set to VOID.");
    } else {
        if ($inTransaction) {
            $page->db->sql_rollback_trans();
        }
        $page->messages->addErrorMsg("Unable to update offer");
    }

    return $success;
}

function reverseOfferTransactions($offerInfo, $reverseFees) {
    global $page;

    $success = true;

    $transactions = getOfferTransactions($offerInfo);
    if (is_array($transactions) && (count($transactions) > 0)) {
        foreach ($transactions as $transaction) {
            if ($reverseFees || (($transaction['useraccountid'] != FEES_USERID) && ($transaction['refaccountid'] != FEES_USERID))) {
                $sql = "
                    INSERT INTO transactions( crossrefid,  useraccountid,  refaccountid,  transtype, transstatus,  dgrossamount,  accountname,  transdesc, offerid,  createdby, modifiedby)
                                      VALUES(:crossrefid, :useraccountid, :refaccountid, :transtype, :transstatus, :dgrossamount, :accountname, :transdesc, :offerid, :createdby, :modifiedby)
                ";
                $params = array();
                $params['crossrefid']       = $transaction['crossrefid'];
                $params['useraccountid']    = $transaction['refaccountid']; // Swap user and ref
                $params['refaccountid']     = $transaction['useraccountid'];// Swap user and ref
                $params['transtype']        = $transaction['transtype'];
                $params['transstatus']      = $transaction['transstatus'];
                $params['dgrossamount']     = $transaction['dgrossamount'];
                $params['accountname']      = $transaction['refaccountname']; //Leave original ref name
                $params['transdesc']        = "Reverse ".$transaction['transdesc'];
                $params['offerid']          = $transaction['offerid'];
                $params['createdby']        = $page->user->username;
                $params['modifiedby']       = $page->user->username;
                if ($page->db->sql_execute_params($sql, $params)) {
                    $page->messages->addSuccessMsg("Success ".$params['transdesc']." ".$transaction['accountname']."/".$transaction['refaccountname']." ".floatToMoney($params['dgrossamount']));
                } else {
                    $page->messages->addErrorMsg("Error reversing transactions.");
                    $success = false;
                    break;
                }
            }
        }
    }

    return $success;
}

function getOfferInfo($offerId) {
    global $page;

    $offer = null;

    if ($offerId) {
        $sql = "SELECT ofr.offerid, ofr.threadid, ofr.offerfrom, ofr.offerto, ofr.transactiontype, ofr.offerstatus, ofr.offerdsubtotal
                , ofr.paymentmethod, ofr.paymenttiming, ofr.paymenttype, ofr.paysfees, ofr.offernotes
                ,uf.username as dealername, uf.userid as dealerid
                ,ut.username as listername, ut.userid as listerid
                ,to_char(to_timestamp(ofr.createdate),'MM/DD/YYYY HH24:MI:SS') as createdat
                ,to_char(to_timestamp(ofr.offerexpiration),'MM/DD/YYYY HH24:MI:SS') as expiresat
                ,ofr.offeredby
                ,CASE WHEN ofr.offeredby=ut.userid THEN ut.username ELSE uf.username END AS revisedname
                ,CASE WHEN up.userid IS NULL THEN 'N/A' ELSE up.username END AS whopaysfees
                ,ufi.firstname as fromfirstname, ufi.lastname as fromlastname
                ,uti.firstname as tofirstname, uti.lastname as tolastname
                ,CASE WHEN ofr.transactiontype='Wanted' THEN uf.userid ELSE ut.userid END AS addrbilluserid
                ,CASE WHEN ofr.transactiontype='Wanted' THEN uf.username ELSE ut.username END AS addrbillusername
                ,addrbillstreet, addrbillstreet2, addrbillcity, addrbillstate, addrbillzip, addrbillcountry
                ,addrbillphone, addrbillemail
                ,addrbillnote, addrbillacctnote, addrbillfirstname, addrbilllastname, addrbillcompanyname
                ,CASE WHEN ofr.transactiontype='For Sale' THEN uf.userid ELSE ut.userid END AS addrshipuserid
                ,CASE WHEN ofr.transactiontype='For Sale' THEN uf.username ELSE ut.username END AS addrshipusername
                ,addrshipstreet, addrshipstreet2, addrshipcity, addrshipstate, addrshipzip, addrshipcountry
                ,addrshipphone, addrshipemail
                ,addrshipnote, addrshipacctnote, addrshipfirstname, addrshiplastname, addrshipcompanyname
                ,ofr.satisfiedbuy, ofr.satisfiedsell
                ,ofr.disputetoopened, ofr.disputetoclosed, ofr.disputefromopened, disputefromclosed
                ,ofr.acceptedon, ofr.completedon
                ,ofr.sellerpayment
                ,ofr.shipdate, ofr.carrierid, c.carriername, ofr.tracking
            FROM offers ofr
            JOIN users uf on uf.userid=ofr.offerfrom
            JOIN userinfo ufi on ufi.userid=uf.userid
            JOIN users ut on ut.userid=ofr.offerto
            JOIN userinfo uti on uti.userid=ut.userid
            LEFT JOIN users up on up.userid=ofr.paysfees
            LEFT JOIN carriers c on c.carrierid=ofr.carrierid
            WHERE ofr.offerid=".$offerId;
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
                $offer['disputetoopeneddate'] = (isset($offer['disputetoopened']) ? date('m/d/Y', $offer['disputetoopened']) : null);
                $offer['disputetocloseddate'] = (isset($offer['disputetoclosed']) ? date('m/d/Y', $offer['disputetoclosed']) : null);
                $offer['disputefromopeneddate'] = (isset($offer['disputefromopened']) ? date('m/d/Y', $offer['disputefromopened']) : null);
                $offer['disputefromcloseddate'] = (isset($offer['disputefromclosed']) ? date('m/d/Y', $offer['disputefromclosed']) : null);
                $offer['accepteddate'] = (isset($offer['acceptedon']) ? date('m/d/Y', $offer['acceptedon']) : null);
                $offer['completeddate'] = (isset($offer['completedon']) ? date('m/d/Y', $offer['completedon']) : null);
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

function getOfferTransactions($offerInfo) {
    global $page;

    $transactions = null;

    if (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED') || ($offerInfo['offerstatus'] == 'VOID')) {
        $sql = "SELECT t.*, u.username as useraccountname, ru.username as refaccountname
            FROM transactions t
                JOIN users u ON u.userid=t.useraccountid
                JOIN users ru ON ru.userid=t.refaccountid
            WHERE t.offerid IS NOT NULL
                AND t.offerid=".$offerInfo['offerid']."
            ORDER BY useraccountid, transdate";

        $transactions = $page->db->sql_query($sql);
    }

    return $transactions;
}
function getOfferItems($offerId) {
    global $page;

    $offerItems = null;

    if ($offerId) {
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
                ,oi.lstdeliverby
                ,l.picture, l.quantity as listingquantity, l.dprice as listingdprice, l.status as listingstatus
            FROM offers ofr
            JOIN users uf ON uf.userid=ofr.offerfrom
            JOIN users ut ON ut.userid=ofr.offerto
            JOIN offeritems oi ON oi.offerid=ofr.offerid
            JOIN categories cat on cat.categoryid=oi.lstcatid
            JOIN subcategories sub on sub.subcategoryid=oi.lstsubcatid
            JOIN boxtypes bt ON bt.boxtypeid=oi.lstboxtypeid
            LEFT JOIN listings l on l.listingid=oi.listingid
            WHERE ofr.offerid=".$offerId;
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

function displayOfferChat($offerid) {
    global $page;

    $offerinfo = $page->iMessage->getOfferInfoAdmin($offerid);
    echo "  <div id='offerchatdiv' class='commentArea' style='border:1px solid #EEE;width:450px; height:400px;overflow: auto;'>\n";
    echo "    <div style='float:left;'>".$offerinfo["to_username"]."</div>\n";
    echo "    <div style='float:right;'>".$offerinfo["from_username"]."</div>\n";
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
            if ($m["fromid"] == $offerinfo['offerfrom']) {
                echo "    <div class='bubbledMe'>";
                echo stripslashes(str_replace("\n","<br>",str_replace("\c\n","<br>",$m['messagetext'])));
                echo "</div>\n";
                echo "    <div class='stampedMe'>".date('g:i a',$m["createdate"])."</div>";
            } else {
                echo "    <div class='bubbledNotMe'>";
                echo stripslashes(str_replace("\n","<br>",str_replace("\c\n","<br>",$m['messagetext'])));
                echo "</div>\n";
                echo "<div class='stampedNotMe'>".date('g:i a',$m["createdate"])."</div>";
            }
            $lastmessage = $m;
        }
    }
    echo "  </div>\n";
}

function displayAdminChat($offerid, $isLister, $chatUserId, $chatUserName) {
    global $page;

    $prefix = ($isLister) ? 'dl' : 'do';
    $offerinfo = $page->iMessage->getOfferinfoAdmin($offerid);

    echo "<FORM  NAME='".$prefix."chat' ID='".$prefix."chat' ACTION='offeradmin.php'  OnSubmit='return Verify".$prefix."Fields(this)' METHOD='POST'>\n";
    echo "  <div id='".$prefix."chatdiv' class='commentArea' style='border:1px solid #EEE;width:450px; height:400px;overflow: auto;'>\n";
    echo "    <div style='float:left;'>".$chatUserName."</div>\n";
    echo "    <div style='float:right;'>".ADMINUSERNAME."</div>\n";
    if ($messages = $page->iMessage->getComplaintThread($offerid, $chatUserId)) {
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
                echo "    <div class='bubbledMe'>\n";
                echo "      ".stripslashes(str_replace("\n","<br>",str_replace("\c\n","<br>",$m['messagetext'])))."\n";
                echo "    </div>\n";
            } else {
                echo "    <div class='bubbledNotMe'>\n";
                echo "      ".stripslashes(str_replace("\n","<br>",str_replace("\c\n","<br>",$m['messagetext'])))."\n";
                echo "    </div>\n";
            }
            $lastmessage = $m;
        }
    }
    echo "  </div>\n";
    echo "  <textarea style='width:425px;height:135px;margin-top:5px;' name='".$prefix."message' id='".$prefix."message'></textarea>\n";
    echo "  <input type='hidden' name='offerid' value='".$offerinfo["offerid"]."'>\n";
    echo "  <input type='hidden' name='".$prefix."toid' value='".$chatUserId."'>\n";
    $subject = (isset($lastmessage["subjecttext"]) && !empty($lastmessage["subjecttext"])) ? $lastmessage["subjecttext"] : $offerinfo["transactiontype"]." by ".$offerinfo["from_username"];
    echo "  <input type='hidden' name='".$prefix."subject' value='".$subject."'>\n";
    $parentid = (isset($lastmessage["messageid"]) && !empty($lastmessage["messageid"])) ? $lastmessage["messageid"] : 0;
    echo "  <input type='hidden' name='".$prefix."parentid' value='".$parentid."'>\n";
    $threadid = (isset($lastmessage["threadid"]) && !empty($lastmessage["threadid"])) ? $lastmessage["threadid"] : 0;
    echo "  <input type='hidden' name='".$prefix."threadid' value='".$threadid."'>\n";
    echo "  <a href=javascript:void(0);' style='font-weight:bold;' name='submitbtn' onclick='Javascript:if (Verify".$prefix."Fields(document.".$prefix."chat)) { document.".$prefix."chat.submit();} else { return false;} '>SEND</a>\n";
    echo "</FORM>\n";

    echo "<SCRIPT LANGUAGE='JavaScript' TYPE='text/javascript'>\n";
    echo "<!--\n";
    echo "\n";
    echo "  function Verify".$prefix."Fields(f) {\n";
    echo "    var a = [\n";
    echo "              [/^".$prefix."message$/,   'Message',      'text',    true,   5000],\n";
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
?>