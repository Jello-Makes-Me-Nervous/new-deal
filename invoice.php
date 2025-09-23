<?php
require_once('templateBlank.class.php');

$page = new templateBlank(LOGIN, SHOWMSG, REDIRECT, SHOWLOGO);
$page->requireJS("/scripts/formValidation.js");
$page->requireJS('scripts/tabs.js');
$page->requireStyle("/styles/chatstyles.css' type='text/css' media='all'");

$offerId        = optional_param('offerid', NULL, PARAM_INT);

$offerInfo = null;
$offerItems = null;
$offerHistory = null;
$offerTransactions = null;

if ($offerId) {
    if ($offerInfo = getOfferInfo($offerId)) {
        $offerItems = getOfferItems($offerId);
        $offerHistory = getOfferHistory($offerId);
        $offerTransactions = getOfferTransactions($offerInfo);
    }
} else {
    $page->messages->addErrorMsg("No offer specified");
}

if ($offerInfo && (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED'))) {
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
} else {
    $page->messages->addErrorMsg("Invoice view only available for Accepted and Archived orders");
}

echo $page->header('Invoice');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $UTILITY, $USER, $offerId, $offerInfo, $offerItems, $offerHistory, $offerTransactions, $showMsgs, $adshowMsgs, $showListingEdit, $acceptingOfferOnListings;

    if (! ($offerInfo && (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED')))) {
        return;
    }
    
    $amountPaid = 0;

    if ($offerInfo) {
        echo "<h3>Offer #".$offerInfo['offerid']." - Accepted On: ".date('m/d/Y', $offerInfo['acceptedon'])."</h3>\n";

        echo "<table>\n";
        echo "<thead><tr><th>Pay To</th><th>Ship To</th></tr></thead>\n";
        echo "<tbody>\n";
        echo "<tr><td>";
        echo $page->user->formatOfferContactInfo($offerInfo['billto']);
        if ($offerInfo['transactiontype'] == 'Wanted') {
            if (!empty($offerInfo['sellerpayment'])) {
                if ($eom = strpos($offerInfo['paymentmethod'], " -")) {
                    $method = substr($offerInfo['paymentmethod'], 0, $eom);
                } else {
                    $method = "Payment";
                }
                echo "<strong>Seller ".$method." Info:</strong> ".$offerInfo['sellerpayment'];
            }
        } else {
            echo "<strong>Payment Method:</strong> ".$offerInfo['paymentmethod'];
        }
        echo "</td>";
        echo "<td>";
        echo $page->user->formatOfferContactInfo($offerInfo['shipto']);
        echo "</td></tr>\n";
        echo "</tbody>\n";
        echo "</table>\n";
/*
        echo "<div>\n";
        echo "<table class='sidehead'>\n";
        echo "<tr><th>Status</th><td>".$offerInfo['offerstatus']."</td></tr>\n";
        echo "<tr><th>Payment Timing</th><td>".$offerInfo['paymenttiming']."</td></tr>\n";
        echo "<tr><th>Payment Method</th><td>".$offerInfo['paymentmethod']."</td></tr>\n";
        echo "<tr><th>Payment Processing<br />Fees Paid By</th><td>".$offerInfo['whopaysfees']."</td></tr>\n";
        if (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED')){
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
        }

        if (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED')) {
            echo "<tr><th>Accepted</th><td>".$offerInfo['accepteddate']."</td></tr>\n";
            if ($offerInfo['offerstatus'] == 'ARCHIVED'){
                echo "<tr><th>Completed</th><td>".$offerInfo['completeddate']."</td></tr>\n";
            }
        }
        echo "</table>\n";
        if ($offerInfo['offernotes']) {
            echo "<strong>Offer Notes:</strong><br />\n";
            echo "<table><tr><td>".str_replace("\n","<br>",$offerInfo['offernotes'])."</td></tr></table>\n";
            echo "<br />\n";
        }
        echo "</div>\n";
*/
        if ($offerInfo['offernotes']) {
            echo "<div id='notes'>\n";
            echo "<strong>Offer Notes:</strong><br />\n";
            echo "<table><tr><td>".str_replace("\n","<br>",$offerInfo['offernotes'])."</td></tr></table>\n";
            echo "<br />\n";
            echo "</div>\n";
        }
     
        echo "<div id='items'>\n";
        echo "<strong>Items:</strong> ";
        echo "<br />\n";

        if (is_array($offerItems) && (count($offerItems) > 0)) {
            $offerListingIds = array();
            echo "<table>\n";
            echo "  <theader><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></theader>\n";
            echo "  <tbody>\n";
            foreach ($offerItems as $offerItem) {
                $offerItemId = $offerItem['offeritemid'];
                $offerListingIds[] = $offerItem['listingid'];
                echo "    <tr>";
                echo "<td>";
                if (!empty($offerItem['picture'])) {
                    if ($imgURL = $UTILITY->getListingImageURL($offerItem['picture'])) {
                        echo "<img class='align-left' src='".$imgURL."' alt='listing image' width='50px' height='50px'> ";
                    }
                }
                if ($offerItem['lstcatid'] == CATEGORY_BLAST) {
                    echo "Blast: ".$offerItem['lsttitle']."<br />\n";
                    echo $offerItem['itemnotes'];
                    echo "</td>";
                    echo "<td align='right'>N/A</td>";
                } else {
                    echo $offerItem['lstyear']." ~ ".$offerItem['subcategorydescription']." ~ ".$offerItem['categorydescription']." ~ ".$offerItem['boxtypename']." ~ ".$offerItem['lstuom'];
                    if (! empty($offerItem['lstdeliverby'])) {
                        echo "<br /><strong>Delivery required by ".(date('m/d/Y', $offerItem['lstdeliverby']))."</strong>";
                    }
                    if ($offerItem['lstnotes']) {
                        echo "<br />\n".substr($offerItem['lstnotes'],0,250);
                    }
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

                $listingPrice = $offerItem['lstdprice'];
                $dprice = $offerItem['offerdprice'];
                $listingFeeMsg = "";
                $listPriceMsg = "";
                if ($listingPrice != $dprice) {
                    $listPriceMsg = "(Listing Price: ".$offerItem['lstdprice']."/".$offerItem['lstuom'].")";
                    if ($offerInfo['offerfrom'] == $page->user->userId) {
                        if ($offerInfo['counterfee'] > 0.00) {
                            if ($offerInfo['transactiontype'] == 'Wanted') {
                                if ($listingPrice < $dprice) {
                                    $listingFeeMsg = "Entire counter offer will be subject to a ".$offerInfo['counterfee']."% transaction fee<br />".$listPriceMsg;
                                } else {
                                    $listingFeeMsg = $listPriceMsg;
                                }
                            } else {
                                if ($offerInfo['transactiontype'] == 'For Sale') {
                                    if ($listingPrice > $dprice) {
                                        $listingFeeMsg = "Entire counter offer will be subject to a ".$offerInfo['counterfee']."% transaction fee<br />".$listPriceMsg;
                                    } else {
                                        $listingFeeMsg = $listPriceMsg;
                                    }
                                } else {
                                    $listingFeeMsg = $listPriceMsg;
                                }
                            }
                        } else {
                            $listingFeeMsg = $listPriceMsg;
                        }
                    } else {
                        $listingFeeMsg = $listPriceMsg;
                    }
                }
                $listingFeeWarning = "<div style='font-weight:bold;' id='feewarning_".$offerItemId."' name='feewarning_".$offerItemId."'>".$listingFeeMsg."</div>";
                echo "<td align='right'>".floatToMoney($offerItem['offerdprice']).$listingFeeWarning."</td>";
                echo "<td align='right'>".floatToMoney($offerItem['offercost'])."</td>";
                echo "</tr>\n";
            }
            if (count($offerItems) > 0) {
                echo "<tr><td colspan='2'>&nbsp;</td><th>Total</th><td align='right'>".floatToMoney($offerInfo['offerdsubtotal'])."</td></tr>\n";
            }
            echo "  </tbody>\n";
            echo "</table>\n";
        }
        echo "</div>\n";
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
                ,ofr.createdate, ofr.modifydate, ofr.acceptedon, ofr.completedon
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
                ,oi.lstyear, oi.lstuom, oi.lstbxpercase, oi.lsttitle, oi.lstdeliverby
                ,oi.offerqty, oi.lstqty as maxqty, oi.lstminqty as minqty, oi.lstdprice, oi.offerdprice, (oi.offerqty*oi.offerdprice) as offercost
                ,oi.revisedqty, oi.reviseddprice, (oi.revisedqty*oi.reviseddprice) as revisedcost
                ,bt.boxtypename, cat.categoryname, cat.categorydescription, sub.subcategoryname, sub.subcategorydescription
                ,oi.lstnotes, oi.itemnotes
                ,l.picture, l.quantity as listingquantity, l.dprice as listingdprice, l.status as listingstatus
                ,l.expireson
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

function getOfferTransactions($offerInfo) {
    global $page;

    $transactions = null;

    if (($offerInfo['offerstatus'] == 'ACCEPTED') || ($offerInfo['offerstatus'] == 'ARCHIVED')) {
        $sql = "SELECT * FROM transactions WHERE offerid IS NOT NULL AND offerid=".$offerInfo['offerid']." AND useraccountid=".$page->user->userId;

        $transactions = $page->db->sql_query($sql);
    }

    return $transactions;
}

function getOfferHistory($offerId) {
    global $page;

    $sql = "SELECT *, to_char(to_timestamp(createdate),'MM/DD/YYYY HH24:MI:SS') as createdat, to_char(to_timestamp(modifydate), 'MM/DD/YYYY HH24:MI:SS') as modifiedat, to_char(to_timestamp(offerexpiration), 'MM/DD/YYYY HH24:MI:SS') as expiresat FROM offers WHERE threadid IN (SELECT threadid FROM offers WHERE offerid=".$offerId.") ORDER BY createdate desc";
    $offerThread = $page->db->sql_query($sql);

    return $offerThread;
}
?>