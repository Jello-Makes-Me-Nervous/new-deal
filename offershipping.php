<?php
require_once('templateOffer.class.php');

$page = new templateOffer(LOGIN, SHOWMSG);
$page->requireJS("/scripts/formValidation.js");

$offerId        = optional_param('offerid', NULL, PARAM_INT);
$doUpdate       = optional_param('doupdate', NULL, PARAM_INT);

$offerInfo = null;
$canEditShipping = false;

if ($offerId) {
    if ($offerInfo = getOfferInfo($offerId)) {
        if ($offerInfo['offerstatus'] == 'ACCEPTED') {
            if ($offerInfo['fromme']) {
                if ($offerInfo['transactiontype'] == 'Wanted') {
                    $canEditShipping = true;
                } else {
                    $page->messages->addErrorMsg("You are not the shipper");
                }
            } else {
                if ($offerInfo['transactiontype'] == 'For Sale') {
                    $canEditShipping = true;
                } else {
                    $page->messages->addErrorMsg("You are not the shipper");
                }
            }
        } else {
            $page->messages->addErrorMsg("Invalid offer status");
        }
    }
} else {
    $page->messages->addErrorMsg("No offer specified");
}

if ($canEditShipping) {
    if ($doUpdate) {
        if (updateShippingInfo($offerInfo)) {
            header("location:offer.php?offerid=".$offerInfo['offerid']."&pgsmsg=".URLEncode("Shipping updated"));
        }
    }
}
$jsCalendar = '
    $(function(){$("#shipdate").datepicker();});
';
$page->jsInit($jsCalendar);

echo $page->header('Offer Shipping');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $UTILITY, $offerInfo, $canEditShipping;

    if ($offerInfo) {
        echo "<h3>Offer(".$offerInfo['offerid'].") ".(($offerInfo['fromme']) ? "To" : "From")." Dealer:".$offerInfo['dealername']."(".$offerInfo['dealerid'].")</h3>\n";

        echo "<table><theader><tr><th>Pay To</th><th>Ship To</th></tr></theader>\n";
        echo "<tr><td style='vertical-align:top'>";
        echo $page->user->formatOfferContactInfo($offerInfo['billto']);
        echo "</td><td style='vertical-align:top'>";
        echo $page->user->formatOfferContactInfo($offerInfo['shipto']);
        echo "</td>\n";
        echo "</tr>\n";
        echo "</table>\n";

        if ($canEditShipping) {
            echo "<form name='offershipping' id='offershipping' action='offershipping.php' method='post'>\n";
            echo "<input type='hidden' id='offerid' name='offerid' value='".$offerInfo['offerid']."' />\n";
            echo "<input type='hidden' id='doupdate' name='doupdate' value='1' />\n";
            echo "<table class='sidehead'>\n";
            echo "<tr><th>Ship Date</th><td><input type=text name='shipdate' id='shipdate' value='".$offerInfo['shipdt']."' /></td></tr>\n";
            $rs = $page->db->sql_query("SELECT carrierid, carriername, sortorder from carriers where active=1 order by sortorder");
            echo "<tr><th>Carrier</th><td>".getSelectDDM($rs, "carrierid", "carrierid", "carriername", NULL, $offerInfo['carrierid'], "Select", NULL)."</td><tr>\n";
            echo "<tr><th>Tracking Numbers</th><td><input type='text' id='tracking' name='tracking' value='".$offerInfo['tracking']."' /></td></tr>\n";
            echo "<tfooter><tr><td colspan=2><input type='submit' name'updateshipping' id='updateshipping' value='Update' /> <a href='offer.php?offerid=".$offerInfo['offerid']."&pgimsg=".URLEncode("Shipping edit cancelled")."' name'cancelshipping' id='cancelshipping'>Cancel</a></td></tr></tfooter>\n";
            echo "</table>";
            echo "</form>\n";
        }
    }
}

function getOfferInfo($offerId) {
    global $page;
    
    $offer = null;
    
    if ($offerId) {
        $sql = "SELECT ofr.offerid, ofr.threadid, ofr.offerfrom, ofr.offerto, ofr.transactiontype, ofr.offerstatus, ofr.offerdsubtotal
                , ofr.paymentmethod, ofr.paymenttiming, ofr.paymenttype, ofr.paysfees, ofr.offernotes
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
                ,addrbillphone, addrbillemail
                ,addrbillnote, addrbillacctnote, addrbillfirstname, addrbilllastname, addrbillcompanyname
                ,CASE WHEN ofr.transactiontype='For Sale' THEN uf.userid ELSE ut.userid END AS addrshipuserid
                ,CASE WHEN ofr.transactiontype='For Sale' THEN uf.username ELSE ut.username END AS addrshipusername
                ,addrshipstreet, addrshipstreet2, addrshipcity, addrshipstate, addrshipzip, addrshipcountry
                ,addrshipphone, addrshipemail
                ,addrshipnote, addrshipacctnote, addrshipfirstname, addrshiplastname, addrshipcompanyname
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
                ,ofr.shipdate
                ,ofr.carrierid
                ,c.carriername
                ,ofr.tracking
            FROM offers ofr
            JOIN users uf on uf.userid=ofr.offerfrom
            JOIN userinfo ufi on ufi.userid=uf.userid
            JOIN users ut on ut.userid=ofr.offerto
            JOIN userinfo uti on uti.userid=ut.userid
            LEFT JOIN users up on up.userid=ofr.paysfees
            LEFT JOIN carriers c on c.carrierid=ofr.carrierid
            WHERE (ofr.offerfrom=".$page->user->userId." OR ofr.offerto=".$page->user->userId.")
            AND ofr.offerid=".$offerId;
        if ($results = $page->db->sql_query($sql)) {
            if (is_array($results) && (count($results) > 0)) {
                $offer = reset($results);
                $offer['shipdt'] = date('m/d/Y', $offer['shipdate']);
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
                $offer['disputetocloseeddate'] = ($offer['disputetoclosed']) ? date('m/d/Y', $offer['disputetoclosed']) : null;
                $offer['accepteddate'] = ($offer['acceptedon']) ? date('m/d/Y', $offer['acceptedon']) : null;
                $offer['completeddate'] = ($offer['completedon']) ? date('m/d/Y', $offer['completedon']) : null;
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

function updateShippingInfo(&$offerInfo) {
    global $page;
    
    $success = false;

    $lastYear = strtotime('-1 year');
    $nextYear = strtotime('+1 year');
    
    $checkDate = optional_param('shipdate', NULL, PARAM_RAW);
    if ($checkDate) {
        $dateStamp = strtotime($checkDate);
        $offerInfo['shipdt'] = $checkDate;
        $offerInfo['shipdate'] = $dateStamp;
        $d = DateTime::createFromFormat('m/d/Y', $checkDate);
        if ($d) {
            $dateCheck = strtotime($d->format('m/d/Y'));
            if ($dateStamp == $dateCheck) {
                if (($lastYear < $dateStamp) && ($dateStamp < $nextYear)) {
                    $success = true;
                } else {
                    $page->messages->addErrorMsg("Ship date must be within one year of the current date");
                }
            } else {
                $page->messages->addErrorMsg("Invalid ship date must be mm/dd/yyyy");
            }
        } else {
            $page->messages->addErrorMsg("Invalid ship date must be mm/dd/yyyy");
        }
    } else {
        $page->messages->addErrorMsg("Ship date is required");
        $offerInfo['shipdate'] = NULL;
        $offerInfo['shipdt'] = NULL;
    }

    $offerInfo['carrierid'] = optional_param('carrierid', NULL, PARAM_RAW);
    if (! $offerInfo['carrierid']) {
        $page->messages->addErrorMsg("Carrier is required");
        $success = false;
    }
    
    $offerInfo['tracking'] = optional_param('tracking', NULL, PARAM_TEXT);
    if (! $offerInfo['tracking']) {
        $page->messages->addErrorMsg("Tracking Number(s) is required");
        $success = false;
    }
    
    if ($success) {
        $sql = "UPDATE offers SET 
                shipdate=".(($offerInfo['shipdate']) ? $offerInfo['shipdate'] : "NULL")."
                ,carrierid=".(($offerInfo['carrierid']) ? $offerInfo['carrierid'] : "NULL")."
                ,tracking='".$offerInfo['tracking']."'
                ,modifydate=nowtoint()
                ,modifiedby='".$page->user->username."' 
            WHERE offerid=".$offerInfo['offerid'];
        if ($page->db->sql_execute_params($sql)) {
            $page->messages->addSuccessMsg("Updated shipping");
            $emailToId = ($offerInfo['offerto'] == $page->user->userId) ? $offerInfo['offerfrom'] : $offerInfo['offerto'];
            $emailToName = ($offerInfo['offerto'] == $page->user->userId) ? $offerInfo['offerfromname'] : $offerInfo['offertoname'];
            $emailSubject = "Offer Shipping Updated";
            $emailText = "Shipping info has been updated for Offer Id #".$offerInfo['offerid']." by ".$page->user->username;
            $page->iMessage->insertSystemMessage($page, $emailToId, $emailToName, $emailSubject, $emailText, EMAIL, NULL, NULL, $offerInfo['offerid']);
        } else {
            $page->messages->addErrorMsg("Error updating shipping");
            $success = false;
        }
    }
    
    if ($offerInfo['carrierid']) {
        $offerInfo['carriername'] = $page->db->get_field_query("SELECT carriername FROM carriers WHERE carrierid=".$offerInfo['carrierid']);
    } else {
        $offerInfo['carriername'] = NULL;
    }
    
    return $success;
}
?>