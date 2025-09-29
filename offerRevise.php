<?php
require_once('templateOffer.class.php');

$page = new templateOffer(LOGIN, SHOWMSG);
$page->requireJS('scripts/shoppingCart.js');
$iMessaging = new internalMessage();

$itemCount          = optional_param('itemCount', NULL, PARAM_INT);
$listingUserId      = optional_param('listingUserId', NULL, PARAM_INT);
$makeOffer          = optional_param('makeOffer', NULL, PARAM_TEXT);
$offerId            = optional_param('offerid', NULL, PARAM_INT);
$offerToken         = optional_param('offertoken', NULL, PARAM_INT);
$offers             = optional_param('offers', NULL, PARAM_INT);
$paymentMethod      = optional_param('paymentmethod', NULL, PARAM_TEXT);
$sellerPayment      = optional_param('sellerpayment', NULL, PARAM_TEXT);
$sellerExtra        = optional_param('sellerextra', 0, PARAM_INT);
$paymentTiming      = optional_param('paymenttiming', NULL, PARAM_TEXT);
$paysFees           = optional_param('paysfees', 0, PARAM_TEXT);
$offerNotes         = optional_param('offernotes', NULL, PARAM_TEXT);
$revisedOffer       = optional_param('revisedOffer', NULL, PARAM_TEXT);
$total              = optional_param('total', NULL, PARAM_TEXT);
$type               = optional_param('type', NULL, PARAM_TEXT);

$offerExpirationDate = $page->utility->skipxBusinessDays($page->cfg->DEFAULT_OFFER_EXPIRATION);

$offerInfo = null;
$offerItems = null;

if ($offerId) {
    $offerInfo = getOfferInfo($offerId);
    if ($offerInfo) {
        $offerInfo['offerexpiration'] = $offerExpirationDate;
        $offerInfo['offernotes'] = $offerNotes; // Reset these for the revised offer
        $counterMinimumTotal = moneyToFloat($offerInfo['counterminimumdtotal']);

        $offerItems = getOfferItems($offerId);
        if (! $offerItems) {
            $page->messages->addErrorMsg("Error loading offer items");
        }
    } else {
        $page->messages->addErrorMsg("Error loading offer");
    }
} else {
    $page->messages->addErrorMsg("Error offerId required");
}

if ((!empty($revisedOffer)) && isset($offerInfo) && isset($offerItems)) {
    if ($offerToken == $offerInfo['modifydate']) {
        $offerInfo['offerdsubtotal'] = $total;
        $offerInfo['offerexpiration'] = $offerExpirationDate;
        $offerInfo['offerexpiresat'] = date( 'm/d/Y H:i:s A', $offerInfo['offerexpiration']);
        $offerInfo['paymentmethod'] = $paymentMethod;
        $offerInfo['sellerpayment'] = $sellerPayment;
        $offerInfo['paymenttiming'] = $paymentTiming;
        $offerInfo['paysfees'] = $paysFees;

        $index = 1;
        $offerInfo['countered'] = 0;
        foreach($offerItems as &$offerItem) {
            $offerItemId = $offerItem['offeritemid'];
            $offerItem['displayid'] = $index++;
            $revisedQty = optional_param('offerQuantity'.$offerItemId, NULL, PARAM_INT);
            $offerItem['offerqty'] = $revisedQty;
            $revisedPrice = moneyToFloat(optional_param('dprice'.$offerItemId, NULL, PARAM_TEXT));
            $offerItem['offerdprice'] = $revisedPrice;
            $offerItem['origdprice'] = moneyToFloat($offerItem['lstdprice']);
            if ($offerInfo['transactiontype'] == 'Wanted') {
                if (($offerItem['offerqty'] > 0) && ($offerItem['offerdprice'] != $offerItem['origdprice'])) {
                    $offerItem['countered'] = 1;
                    $offerInfo['countered'] = 1;
                } else {
                    $offerItem['countered'] = 0;
                }
            } else {
                if (($offerItem['offerqty'] > 0) && ($offerItem['offerdprice'] != $offerItem['origdprice'])) {
                    $offerItem['countered'] = 1;
                    $offerInfo['countered'] = 1;
                } else {
                    $offerItem['countered'] = 0;
                }
            }
            //echo "Revised type:".$offerInfo['transactiontype']." Orig:".$offerItem['origdprice']." Offered:".$offerItem['offerdprice']." ItemCountered:".$offerItem['countered']." OfferCountered:".$offerInfo['countered']."<br />\n";
        }

        $newOfferId = addOfferRevised($offerInfo, $offerItems);
        if ($newOfferId) {
            //exit;
            header("location:offer.php?offerid=".$newOfferId."&tabid=items&pgsmsg=Created%20new%20offer");
        }
    } else {
        header("location:offer.php?offerid=".$offerInfo['offerid']."&pgemsg=".URLEncode("Unable to complete revision. Offer was modified prior to the requested change."));
    }
}

$displaySellerPayment = false;
$preferredSellerArray  = NULL;
$preferredMenu = paymentMethodDDM($offerInfo['offerto'],$offerInfo['offerfrom'], $offerInfo['transactiontype'], $offerInfo['paymentmethod']);
if ($preferredSellerArray) {
    $page->jsInit($preferredSellerArray);
}

echo $page->header('Offer Revise');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $CFG, $UTILITY, $offerInfo, $offerItems, $itemCount, $listingUserId, $makeOffer, $offerId, $offerItemIds, $offers, $revisedOffer, $offerExpirationDate, $displaySellerPayment, $sellerExtra, $preferredMenu;

    $listingUserId = $offerInfo['offerto'];
    $offeringUserId = $offerInfo['offerfrom'];

    echo "<h3>Revise Offer(".$offerInfo['offerid'].") ".(($offerInfo['fromme']) ? "To" : "From")." Dealer:".$offerInfo['dealername']."(".$offerInfo['dealerid'].")</h3>\n";
    echo "Listing Type: ".$offerInfo['transactiontype']."<br />\n";
    echo "<br />\n";

    echo "<form name ='reviseOffer' id'reviseOffer' action='offerRevise.php' method='post' onsubmit='return validRange()'>\n";
    echo "  <input type='hidden' name='threadId' id='threadId' value='".$offerInfo['threadid']."'>\n";
    echo "  <input type='hidden' name='offerid' id='offerid' value='".$offerInfo['offerid']."'>\n";
    echo "  <input type='hidden' name='offertoken' id='offertoken' value='".$offerInfo['modifydate']."'>\n";
    echo "  <input type='hidden' id='offertype' name='offertype' value='".$offerInfo['transactiontype']."'>\n";
    echo "  <input type='hidden' id='listingfee' name='listingfee' value='".$offerInfo['counterfee']."'>\n";

    echo "<table><theader><tr><th>Pay To</th><th>Ship To</th></tr></theader>\n";
    echo "<tr><td style='vertical-align:top'>";
    echo $page->user->formatOfferContactInfo($offerInfo['billto']);
    if (($offerInfo['transactiontype'] == 'Wanted') && ($sellerExtra) && (!empty($offerInfo['sellerpayment']))) {
        if ($eom = strpos($offerInfo['paymentmethod'], " -")) {
            $method = substr($offerInfo['paymentmethod'], 0, $eom);
        } else {
            $method = "Payment";
        }
        echo "<strong>Seller ".$method." Info:</strong> ".$offerInfo['sellerpayment'];
    }

    echo "</td><td style='vertical-align:top'>";
    echo $page->user->formatOfferContactInfo($offerInfo['shipto']);
    echo "</td>\n";
    echo "</tr>\n";
    echo "</table>\n";

    echo  "<table>\n";
    echo  "  <thead>\n";
    echo  "    <tr>\n";
    echo  "      <th>Item</th>\n";
    echo  "      <th>QTY</th>\n";
    echo  "      <th>Description</th>\n";
    echo  "      <th>Price</th>\n";
    echo  "    </tr>\n";
    echo  "  </thead>\n";
    echo  "  <tbody>\n";
    $i = 1;
    foreach ($offerItems as $offerItem) {
        $listingPriceMsg = "";
        $offerItemId = $offerItem['offeritemid'];
        $dprice = $offerItem['offerdprice'];
        $listingPrice = $offerItem['lstdprice'];

        $listingFeeMsg = "";
        $priceMatch = ($page->user->userId == $offerInfo['offerto']) ? true : false;
        if ($listingPrice != $dprice) {
            $priceMatch = false;
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
        $listingFeeWarning = "<div style='font-weight:bold' id='feewarning_".$offerItemId."' name='feewarning_".$offerItemId."'>".$listingFeeMsg."</div>";

        if (! empty($offerItem['lstdeliverby'])) {
            $deliverBy = "<br /><strong>Delivery required by ".(date('m/d/Y', $offerItem['lstdeliverby']))."</strong>";
        } else {
            $deliverBy = "";
        }

        if (! empty($offerItem['lstnotes'])) {
            $listingNotes = "<br /><strong>Notes:</strong><span>".$offerItem['lstnotes']."</span>";
        } else {
            $listingNotes = "";
        }

        echo "    <tr style='vertical-align:top;'>\n";
        echo "      <td>".$i."</td>";
        if ($offerItem['lstcatid'] == CATEGORY_BLAST) {
            echo "      <td>N/A";
            echo "        <input type='hidden' id='maxqty".$offerItemId."' name='maxqty".$offerItemId."' value='".$offerItem['lstqty']."' />\n";
            echo "        <input type='hidden' id='minqty".$offerItemId."' name='minqty".$offerItemId."' value='".$offerItem['lstminqty']."' />\n";
            echo "        <input type='hidden' id='offeritemid".$offerItemId."' name='offeritemid".$offerItemId."' class='item_id_value' value='".$offerItem['offeritemid']."' />\n";
            echo "        <input type='hidden' id='offerQuantity".$offerItemId."' name='offerQuantity".$offerItemId."' value='".$offerItem['offerqty']."' />\n";
            echo "      </td>\n";
            echo "<td>";
            $link = "blastview.php?listingid=".$offerItem['listingid'];
            echo "<a href='".$link."' target=_blank>Blast: ".$offerItem['lsttitle']."</a> ".$listingNotes."<br />\n";
            echo $offerItem['itemnotes'];
            echo "</td>";
        } else {
            echo "      <td>";
            echo "        <input type='hidden' id='maxqty".$offerItemId."' name='maxqty".$offerItemId."' value='".$offerItem['lstqty']."' />\n";
            echo "        <input type='hidden' id='minqty".$offerItemId."' name='minqty".$offerItemId."' value='".$offerItem['lstminqty']."' />\n";
            echo "        <input type='hidden' id='offeritemid".$offerItemId."' name='offeritemid".$offerItemId."' class='item_id_value' value='".$offerItem['offeritemid']."' />\n";
            echo "        <input type='hidden' id='lstdprice_".$offerItemId."' name='lstdprice_".$offerItemId."' value='".$offerItem['lstdprice']."' />\n";
            echo "        <input type='hidden' id='uom_".$offerItemId."' name='uom_".$offerItemId."' value='".$offerItem['lstuom']."' />\n";
            echo "        <input type='text' id='offerQuantity".$offerItemId."' name='offerQuantity".$offerItemId."' size='3' style='text-align:right;' value='".$offerItem['offerqty']."' onchange='getItemAmounts();' /><br />\n";
            echo "        (Max:".$offerItem['lstqty'].")";
            echo "      </td>\n";
            echo "<td>";
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
            echo "<a href='".$link."' target=_blank>".$offerItem['lstyear']." ~ ".$offerItem['subcategorydescription']." ~ ".$offerItem['categorydescription']." ~ ".$offerItem['boxtypename']." ~ ".$offerItem['lstuom']."</a> ".$deliverBy.$listingNotes;
            echo "</td>";
        }
        if ($page->user->isVendor() && (! $priceMatch)) {
            if ($offerInfo['offerfrom'] == $page->user->userId) {
                $onChange = " onchange=\"checkCounterOffer('".$offerItemId."');getItemAmounts();\" ";
            } else {
                $onChange = " onchange=getItemAmounts(); ";
            }
            echo "      <td>$<input type='text' id='dprice".$offerItemId."' name='dprice".$offerItemId."' size='6' style='text-align:right;' value='".number_format($dprice,2)."' ".$onChange." /> / ".$offerItem['lstuom'].$listingFeeWarning."</td>\n";
        } else {
            echo "      <td>$".number_format($dprice,2)."<input type='hidden' id='dprice".$offerItemId."' name='dprice".$offerItemId."' value='".number_format($dprice,2)."' /> / ".$offerItem['lstuom'].$listingPriceMsg."</td>\n";
        }
        echo "    </tr>\n";
        $i++;
    }
    echo "<input type='hidden' name='itemCount' id='itemCount' value='".count($offerItems)."'> \n";

    echo  "    <tr>\n";
    echo  "      <td></td>\n";
    echo  "      <td></td>\n";
    echo  "      <td></td>\n";
    echo  "      <td>Sub Total: $<input type='text' name='total' id='total' size='8' style='text-align:right;' value='".$offerInfo['offerdsubtotal']."' readonly></td>\n";
    echo  "    </tr>\n";
    echo  "  </tbody>\n";
    echo  "</table>\n";
    echo  "Current offer will expire at ".$offerInfo['expiresat']."<br />\n";
    echo "Revised offer will be valid for: ".$CFG->DEFAULT_OFFER_EXPIRATION." business days (".date('m/d/Y H:i:s', $offerExpirationDate).")<br />\n";
    echo "<br />\n";

    echo "<strong>NOTE:</strong> You have the ability to cancel this offer at anytime prior to its fulfillment from your <strong>&quot;Pending Offers&quot;</strong> folder. Once an offer is accepted, seller agrees to ship within one business day with tracking # provided and buyer agrees to pay using the terms and payment method stated without exception.<br />\n";
    echo "<br />\n";

    echo "<strong>Terms:<br />\n";
    echo "Buyer is responsible for all shipping charges on supplies orders.<br />\n";
    echo "Card boxes and cases qualify for free shipping within continental US on $300+ orders.<br />\n";
    echo "</strong><br />\n";

    $elitePaymentTiming = "upfront";
    if ($offerInfo['transactiontype'] == 'For Sale') {
        if ($offerInfo['fromelite']) {
                $elitePaymentTiming = "both";
        }
    } else {
        if ($offerInfo['byelite']) {
            $elitePaymentTiming = "onreceipt";
        }
    }

    echo "<strong>Payment Timing</strong><br />\n";
    echo "<select name='paymenttiming' id='paymenttiming' >\n";
    if ($elitePaymentTiming  == "both") {
        echo "<option value=''>Select Payment Timing</option>\n";
    }
    if (($elitePaymentTiming  == "upfront") || ($elitePaymentTiming  == "both")) {
        echo "<option value='Payment due within 1 business day of offer acceptance (upfront)' ".(($offerInfo['paymenttiming']=="Payment due within 1 business day of offer acceptance (upfront)") ? " selected " : "").">Payment due within 1 business day of offer acceptance (upfront)</option>\n";
    }
    if (($elitePaymentTiming  == "onreceipt") || ($elitePaymentTiming  == "both")) {
        echo "<option value='Payment due within 2 business days of order delivery (on receipt)' ".(($offerInfo['paymenttiming']=="Payment due within 2 business days of order delivery (on receipt)") ? " selected " : "").">Payment due within 2 business days of order delivery (on receipt)</option>\n";
    }
    echo "</select>\n";
    echo "<br />\n";
    if ($elitePaymentTiming == "onreceipt") {
        echo "This offer is being made to an elite/gold star member and the buyer agrees to make sure payment is received by the seller within 2 business days of delivery.<br />\n";
    }
    echo "<br />\n";

    echo "<strong>Payment Method</strong><br />\n".$preferredMenu."<br />\n<br />\n";
    if ($offerInfo['transactiontype'] == 'Wanted') {
        $sellerDisplayMode = ($displaySellerPayment) ? " style='display: block;'" : " style='display: none;'";
        echo "<div id='sellerpayinfo' name='sellerpayinfo' ".$sellerDisplayMode.">";
        echo "<strong>Sellers Payment Info</strong><br />\n<input type='text' name='sellerpayment' id='sellerpayment' style='width:50ch;' value='".$offerInfo['sellerpayment']."' />";
        echo "<input type='hidden' id='sellerextra' name='sellerextra' value='".$sellerExtra."' />\n";
        echo "<br />\n<br /></div>\n";
    } else {
        echo "<input type='hidden' id='sellerpayment' name='sellerpayment' value='' />\n";
        echo "<input type='hidden' id='sellerextra' name='sellerextra' value='0' />\n";
    }
/*
    echo "<span title='Any agreed upon fee is for 3rd party payment processing fees and not inclusive of any Dealernet fees that may be incurred'><strong>Member responsible for 3% payment processing fee</strong></span><br />\n";
    echo getFeesDDM($offerInfo['paysfees'], $listingUserId, $offeringUserId)."<br />\n<br />\n";
*/
    echo "<input type='hidden' id='paysfees' name='paysfees' value='".$offerInfo['paysfees']."' />\n";

    echo "<strong>Revision Offer Notes</strong><br />\n";
    echo "<textarea name='offernotes' id='offernotes' rows='8' cols='90'>".$offerInfo['offernotes']."</textarea>\n";

    echo "<br />\n";
    echo "<input class='button' type='submit' name='revisedOffer' id='revisedOffer' value='Revise Offer'>";
    echo " <a class='button' href='offer.php?offerid=".$offerId."'>Cancel Revision</a>\n";
    echo "\n";
    echo "</form>\n";
    echo "<br />\n";
    echo "<br />\n";
    echo "<br />\n";

    $offerHistory = getOfferHistory($offerId);
    if (is_array($offerHistory) && (count($offerHistory) > 0)) {
        echo "<strong>Offer History</strong><br />\n";
        echo "<table>\n";
        echo "  <theader><tr><td>OID</td><td>Status</td><td>Created By</td><td>Created At</td><td>Modified By</td><td>Modified At</td><td>Expires</td><td>Total</td></tr></theader>\n";
        echo "  <tbody>\n";
        foreach ($offerHistory as $historyItem) {
            echo "    <tr>";
            if ($historyItem['offerid'] == $offerId) {
                echo "<td><a href='offer.php?offerid=".$historyItem['offerid']."' target=_blank>".$historyItem['offerid']."</a></td>";
                // On Revision allow them to show the original
                //echo "<td>".$historyItem['offerid']."</td>";
            } else {
                echo "<td><a href='offer.php?offerid=".$historyItem['offerid']."' target=_blank>".$historyItem['offerid']."</a></td>";
            }
            echo "<td>".$historyItem['offerstatus']."</td>";
            echo "<td>".$historyItem['createdby']."</td>";
            echo "<td>".$historyItem['createdat']."</td>";
            echo "<td>".$historyItem['modifiedby']."</td>";
            echo "<td>".$historyItem['modifiedat']."</td>";
            echo "<td>".$historyItem['expiresat']."</td>";
            echo "<td>".$historyItem['offerdsubtotal']."</td>";
            echo "</tr>\n";
        }
        echo "  </tbody>\n";
        echo "</table>\n";
    }
}

//update last input latest = 0
//insert new offer with threadid

function getOfferItems($offerId) {
    global $page, $UTILITY;

    $sql = "
        SELECT itm.offeritemid, itm.offerid, itm.touserid, itm.fromuserid, itm.offerqty, itm.offerdprice, itm.lstyear, itm.lstuom, itm.lstqty,
               itm.lstminqty, itm.lstdprice, itm.lstnotes, itm.countered, itm.lsttype, itm.listingid, itm.picture, itm.lsttitle, itm.itemnotes, itm.lstdeliverby,
               cat.categoryname, cat.categorydescription, sub.subcategoryname, sub.subcategorydescription, box.boxtypename, u.username,
               itm.lstcatid, itm.lstsubcatid, itm.lstyear, itm.lstboxtypeid, cat.categorytypeid as lstlistingtypeid, lis.expireson
          FROM offeritems itm
          JOIN offers ofr           ON ofr.offerid = itm.offerid
          JOIN listings lis         ON lis.listingid=itm.listingid
          JOIN categories cat       ON cat.categoryid = itm.lstcatid
          JOIN subcategories sub    ON sub.subcategoryid = itm.lstsubcatid
          JOIN boxtypes box         ON box.boxtypeid = itm.lstboxtypeid
          JOIN users u              ON u.userid = itm.touserid
          LEFT JOIN users up        ON up.userid=ofr.paysfees
         WHERE ofr.offerid = ".$offerId."
         ORDER BY itm.offeritemid
    ";
    $data = $page->db->sql_query($sql);

    return $data;
}

function addOfferRevised($offerInfo, $offerItems) {
    global $page, $UTILITY, $iMessaging, $counterMinimumTotal, $counterCollarLow, $counterCollarHigh, $sellerExtra;

    $isValid = true;
    $newOfferId = null;

    if (! $offerInfo) {
        $page->messages->addErrorMsg("Unable to locate original offer");
        $isValid = false;
    }

    if (empty($offerInfo['paymenttiming'])) {
        $page->messages->addErrorMsg("Payment Timing is required");
        $isValid = false;
    }

    if (empty($offerInfo['paymentmethod'])) {
        $page->messages->addErrorMsg("Payment Method is required");
        $isValid = false;
    }

    if ($offerInfo['paysfees'] < 0) {
        $page->messages->addErrorMsg("Member Responsible For Any Payment Processing Fees is required");
        $isValid = false;
    }

    //echo "myRevision:".$offerInfo['myrevision']." offerfrom:".$offerInfo['offerfrom']." type:".$offerInfo['transactiontype']." sellerExtra:".$sellerExtra." sellerPayment:".$offerInfo['sellerpayment']."<br />\n";
    // Only check Seller Payment if the current user is the seller.
    // Otherwise allow the buyer to revise and the seller will need to complete to accept
    if (($offerInfo['transactiontype'] == 'Wanted') && ($offerInfo['offerfrom'] == $page->user->userId)) {
        if (($sellerExtra == 1) && empty($offerInfo['sellerpayment'])) {
            $page->messages->addErrorMsg("Seller Payment Info is required for the selected Payment Method");
            $isValid = false;
        }
    }

    $totalQuantity = 0;
    $hasCountered = false;
    $offerType = $offerInfo['transactiontype'];
    foreach ($offerItems as $offerItem) {
        $totalQuantity += $offerItem['offerqty'];
        if ($offerItem['lstcatid'] != CATEGORY_BLAST) {
            $counter = $offerItem['offerdprice'];
            $origdprice = $offerItem['origdprice'];
            if (($offerItem['offerqty'] > 0) && ($counter != $origdprice)) {
                $hasCountered = true;
                if ($offerType == 'For Sale') {
                    if ($counter > $origdprice) {
                        $page->messages->addWarningMsg("Counter offer for item ".$offerItem['displayid']." greater than asking price");
                        //$isValid = false;

                    /*
                    } else {
                        $counterPercent = ($origdprice - $counter) / $origdprice;
                        if ($counterPercent < $counterCollarLow) {
                            $page->messages->addErrorMsg("Counter offer for item ".$offerItem['displayid']." must be between ".($counterCollarLow*100)."% and ".($counterCollarHigh*100)."%");
                            $isValid = false;
                        } else {
                            if ($counterPercent > $counterCollarHigh) {
                                $page->messages->addErrorMsg("Counter offer for item ".$offerItem['displayid']." must be between ".($counterCollarLow*100)."% and ".($counterCollarHigh*100)."%");
                                $isValid = false;
                            }
                        }
                    */
                    }
                } else {
                    if ($origdprice > $counter) {
                        $page->messages->addWarningMsg("Counter offer for item ".$offerItem['displayid']." less than than offered price");
                        //$isValid = false;
                    /*
                    } else {
                        $counterPercent = ($counter - $origdprice) / $origdprice;
                        if ($counterPercent < $counterCollarLow) {
                            $page->messages->addErrorMsg("Counter offer for item ".$offerItem['displayid']." must be between ".($counterCollarLow*100)."% and ".($counterCollarHigh*100)."%");
                            $isValid = false;
                        } else {
                            if ($counterPercent > $counterCollarHigh) {
                                $page->messages->addErrorMsg("Counter offer for item ".$offerItem['displayid']." must be between ".($counterCollarLow*100)."% and ".($counterCollarHigh*100)."%");
                                $isValid = false;
                            }
                        }
                    */
                    }
                }
            }
        }
    }

    if ($totalQuantity <= 0) {
        $page->messages->addErrorMsg("Offer must contain at least one item");
        $isValid = false;
    }

    $offerSubTotal = moneyToFloat($offerInfo['offerdsubtotal']);
    /*  DO NOT ENFORCE MINIMUM COUNTER ON OFFER REVISIONS
    echo "hasCountered:".(($hasCountered) ? "Y" : "N")." OfferTotal:".$offerSubTotal." counterMinimumTotal:".$counterMinimumTotal."<br />\n";
    if ($hasCountered && ($offerSubTotal < $counterMinimumTotal)) {
        $page->messages->addErrorMsg("Counter offers are not accepted on offers less than $".$counterMinimumTotal);
        $isValid = false;
    }
    */

    if ($isValid) {
        $page->db->sql_begin_trans();
        if ($newOfferId = $UTILITY->nextval('offers_offerid_seq')) {
            $params = array();
            $params['offerid'] = $newOfferId;
            $params['threadid'] = $offerInfo['threadid'];
            $params['offerto'] = $offerInfo['offerto'];
            $params['offerfrom'] = $offerInfo['offerfrom'];
            $params['offeredby'] = $page->user->userId;
            $params['offerstatus'] = 'PENDING';
            $params['transactiontype'] = $offerInfo['transactiontype'];
            $params['offerdsubtotal'] = $offerInfo['offerdsubtotal'];
            $params['countered'] = $offerInfo['countered'];
            $params['offerexpiration'] = $offerInfo['offerexpiration'];
            $params['paymentmethod'] = $offerInfo['paymentmethod'];
            $params['sellerpayment'] = $offerInfo['sellerpayment'];
            $params['paymenttiming'] = $offerInfo['paymenttiming'];
            $params['paysfees'] = $offerInfo['paysfees'];
            $params['offernotes'] = $offerInfo['offernotes'];
            $params['counterminimumdtotal']   = $offerInfo['counterminimumdtotal'];
            $params['addrbillstreet']   = $offerInfo['billto']['street'];
            $params['addrbillstreet2']   = $offerInfo['billto']['street2'];
            $params['addrbillcity']   = $offerInfo['billto']['city'];
            $params['addrbillstate']   = $offerInfo['billto']['state'];
            $params['addrbillzip']   = $offerInfo['billto']['zip'];
            $params['addrbillcountry']   = $offerInfo['billto']['country'];
            $params['addrbillphone']   = $offerInfo['billto']['phone'];
            $params['addrbillemail']   = $offerInfo['billto']['email'];
            $params['addrbillnote']   = $offerInfo['billto']['addressnote'];
            $params['addrbillacctnote']   = $offerInfo['billto']['accountnote'];
            $params['addrbillfirstname']   = $offerInfo['billto']['firstname'];
            $params['addrbilllastname']   = $offerInfo['billto']['lastname'];
            $params['addrbillcompanyname']   = $offerInfo['billto']['companyname'];
            $params['addrshipstreet']   = $offerInfo['shipto']['street'];
            $params['addrshipstreet2']   = $offerInfo['shipto']['street2'];
            $params['addrshipcity']   = $offerInfo['shipto']['city'];
            $params['addrshipstate']   = $offerInfo['shipto']['state'];
            $params['addrshipzip']   = $offerInfo['shipto']['zip'];
            $params['addrshipcountry']   = $offerInfo['shipto']['country'];
            $params['addrshipphone']   = $offerInfo['shipto']['phone'];
            $params['addrshipemail']   = $offerInfo['shipto']['email'];
            $params['addrshipnote']   = $offerInfo['shipto']['addressnote'];
            $params['addrshipacctnote']   = $offerInfo['shipto']['accountnote'];
            $params['addrshipfirstname']   = $offerInfo['shipto']['firstname'];
            $params['addrshiplastname']   = $offerInfo['shipto']['lastname'];
            $params['addrshipcompanyname']   = $offerInfo['shipto']['companyname'];
            $params['createdby'] = $page->user->username;
            $params['modifiedby'] = $page->user->username;

            $sql = "INSERT INTO offers (offerid, threadid
                        ,offerto, offerfrom, offeredby, offerstatus
                        ,transactiontype, offerdsubtotal, countered, offerexpiration
                        ,paymentmethod, sellerpayment, paymenttiming, paysfees, offernotes, counterminimumdtotal
                        ,addrbillstreet, addrbillstreet2, addrbillcity, addrbillstate, addrbillzip, addrbillcountry
                        ,addrbillphone, addrbillemail
                        ,addrbillnote, addrbillacctnote, addrbillfirstname, addrbilllastname, addrbillcompanyname
                        ,addrshipstreet, addrshipstreet2, addrshipcity, addrshipstate, addrshipzip, addrshipcountry
                        ,addrshipphone, addrshipemail
                        ,addrshipnote, addrshipacctnote, addrshipfirstname, addrshiplastname, addrshipcompanyname
                        ,createdby, modifiedby)
                VALUES(:offerid, :threadid
                    ,:offerto, :offerfrom, :offeredby, :offerstatus
                    ,:transactiontype, :offerdsubtotal, :countered, :offerexpiration
                    ,:paymentmethod, :sellerpayment, :paymenttiming, :paysfees, :offernotes, :counterminimumdtotal
                    ,:addrbillstreet, :addrbillstreet2, :addrbillcity, :addrbillstate, :addrbillzip, :addrbillcountry
                    ,:addrbillphone, :addrbillemail
                    ,:addrbillnote, :addrbillacctnote, :addrbillfirstname, :addrbilllastname, :addrbillcompanyname
                    ,:addrshipstreet, :addrshipstreet2, :addrshipcity, :addrshipstate, :addrshipzip, :addrshipcountry
                    ,:addrshipphone, :addrshipemail
                    ,:addrshipnote, :addrshipacctnote, :addrshipfirstname, :addrshiplastname, :addrshipcompanyname
                    ,:createdby, :modifiedby)";
            if ($page->db->sql_execute_params($sql, $params)) {
                foreach ($offerItems as $offerItem) {
                    if ($isValid) {
                        $offerItemId = $offerItem['offeritemid'];
                        $revisedQty = $offerItem['offerqty'];
                        $revisedPrice = $offerItem['offerdprice'];
                        $countered = $offerItem['countered'];
                        $isValid = addOfferItemsRevised($newOfferId, $offerItemId, $revisedQty, $revisedPrice, $countered);
                    }
                }

                if ($isValid) {
                    $sql = "UPDATE offers SET offerstatus='REVISED', modifydate=nowtoint(), modifiedby='".$page->user->username."' WHERE offerid=".$offerInfo['offerid'];
                    if ($page->db->sql_execute($sql)) {
                        echo "Updated prior offer<br />\n";
                    } else {
                        $page->messages->addErrorMsg("Error revising prior offer");
                    }
                }
            } else {
                $page->messages->addErrorMsg("Error adding offer");
                $isValid = false;
            }
        } else {
            $page->messages->addErrorMsg("Error getting offerid");
            $isValid = false;
        }

        if ($isValid) {
            $page->db->sql_commit_trans();
            $msgToId = ($offerInfo['offerto'] == $page->user->userId) ? $offerInfo['offerfrom'] : $offerInfo['offerto'];
            $msgToName = $page->db->get_field_query("SELECT username FROM users WHERE userid=".$msgToId);
            $msgSubject = "Offer Revised";
            $msgText = "Your offer has been revised by ".$page->user->username;
            $page->iMessage->insertSystemMessage($page, $msgToId, $msgToName, $msgSubject, $msgText, EMAIL, NULL, NULL, $newOfferId);
        } else {
            $newOfferId = null;
            $page->db->sql_rollback_trans();
        }

    }

    return $newOfferId;
}

function addOfferItemsRevised($newOfferId, $offerItemId, $revisedQty, $revisedPrice, $countered) {
    global $page, $UTILITY;
    $isValid = true;
    $result = "";

    $sql = "INSERT INTO offerItems(offerid, touserid, fromuserid, offerqty, offerdprice, countered, createdby, threadid, listingid
                              ,lstcatid, lstsubcatid, lstboxtypeid, lstyear, lstyear4, lstuom, lstbxpercase, lsttype
                              , lstminqty, lstqty, lstdprice, lstnotes, lstdeliverby, picture, itemnotes, lsttitle)
           (SELECT DISTINCT ".$newOfferId.", touserid, fromuserid, ".$revisedQty.", ".$revisedPrice.", ".$countered.", ".$page->user->userId.", threadid, listingid
                              ,lstcatid, lstsubcatid, lstboxtypeid, lstyear, lstyear4, lstuom, lstbxpercase, lsttype
                              , lstminqty, lstqty, lstdprice, lstnotes, lstdeliverby, picture, itemnotes, lsttitle
              FROM offeritems
             WHERE offeritemid = ".$offerItemId.")";
//echo "SQL:".$sql."<br />\n";
    if ($page->db->sql_execute_params($sql)) {
        echo "Added offer item from offer item id ".$offerItemId."<br />\n";
    } else {
        $page->messages->addErrorMsg("Error adding offer item from offer item id ".$offerItemId);
        $isValid = false;
    }

    return $isValid;
}

function getOfferInfo($offerId) {
    global $page;

    $offer = null;

    if ($offerId) {
        $sql = "SELECT ofr.offerid, ofr.threadid, ofr.offerfrom, ofr.offerto, ofr.transactiontype, ofr.offerstatus, ofr.offerdsubtotal
                , ofr.paymentmethod, ofr.paymenttiming, ofr.paymenttype, ofr.paysfees, ofr.offernotes, ofr.countered, ofr.counterminimumdtotal
                ,CASE WHEN ofr.offerfrom=".$page->user->userId." THEN 1 ELSE 0 END AS fromme
                ,CASE WHEN ofr.offerfrom=".$page->user->userId." THEN ut.username ELSE uf.username END AS dealername
                ,CASE WHEN ofr.offerfrom=".$page->user->userId." THEN ut.userid ELSE uf.userid END AS dealerid
                ,to_char(to_timestamp(ofr.createdate),'DD/MM/YYYY HH24:MI:SS') as createdat
                ,to_char(to_timestamp(ofr.offerexpiration),'MM/DD/YYYY HH24:MI:SS') as expiresat
                ,CASE WHEN ofr.offeredby=".$page->user->userId." THEN 1 ELSE 0 END AS myrevision
                ,ofr.offeredby, ofr.modifydate
                ,CASE WHEN ofr.offeredby=ut.userid THEN ut.username ELSE uf.username END AS revisedname
                ,up.username as whopaysfees
                ,ufi.firstname as fromfirstname, ufi.lastname as fromlastname
                ,uti.firstname as tofirstname, uti.lastname as tolastname
                ,addrbillstreet, addrbillstreet2, addrbillcity, addrbillstate, addrbillzip, addrbillcountry
                ,addrbillphone, addrbillemail
                ,addrbillnote, addrbillacctnote, addrbillfirstname, addrbilllastname, addrbillcompanyname
                ,addrshipstreet, addrshipstreet2, addrshipcity, addrshipstate, addrshipzip, addrshipcountry
                ,addrshipphone, addrshipemail
                ,addrshipnote, addrshipacctnote, addrshipfirstname, addrshiplastname, addrshipcompanyname
                ,arf.userrightid AS fromelite, art.userrightid AS byelite
                ,CASE
                    WHEN ((ofr.transactiontype='For Sale') AND (arf.userrightid IS NOT NULL)) THEN 1
                    WHEN ((ofr.transactiontype = 'Wanted') AND (art.userrightid IS NOT NULL)) THEN 1
                    ELSE 0 END AS elitepaymenttiming
                ,ufi.listingfee as counterfee
                ,ofr.sellerpayment
            FROM offers ofr
            JOIN users uf on uf.userid=ofr.offerfrom
            JOIN userinfo ufi on ufi.userid=uf.userid
            JOIN users ut on ut.userid=ofr.offerto
            JOIN userinfo uti on uti.userid=ut.userid
            LEFT JOIN users up on up.userid=ofr.paysfees
            LEFT JOIN assignedrights arf ON arf.userid = ofr.offerfrom AND arf.userrightid=".USERRIGHT_ELITE."
            LEFT JOIN assignedrights art ON art.userid = ofr.offerto AND art.userrightid=".USERRIGHT_ELITE."
            WHERE (ofr.offerfrom=".$page->user->userId." OR ofr.offerto=".$page->user->userId.")
            AND ofr.offerid=".$offerId;

        if ($results = $page->db->sql_query($sql)) {
            if (is_array($results) && (count($results) > 0)) {
                $offer = reset($results);
                $offer['billto'] = array();
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
            } else {
                $page->messages->addErrorMsg("Offer not found");
            }
        } else {
            $page->messages->addErrorMsg("Error getting offer id ".$offerId);
        }
    } else {
        $page->messages->addErrorMsg("Offer not specified");
    }
    return $offer;
}

function paymentMethodDDM($listingUserId, $offerinfDealer, $transactionType, $paymentMethod) {
    global $page, $preferredSellerArray, $displaySellerPayment, $sellerExtra;

    $preferredPayment = "";

    $sql = "SELECT pt.paymenttypeid, pt.allowinfo, pt.paymenttypename as ptname
                   ,CASE
                        WHEN pt.allowinfo='Yes' THEN CONCAT(pt.paymenttypename || ' - ' || pp.extrainfo)
                        WHEN pt.allowinfo='Optional' AND length(pp.extrainfo) > 0 THEN CONCAT(pt.paymenttypename || ' - ' || pp.extrainfo)
                        ELSE pt.paymenttypename
                    END AS paymenttypename
                   ,mpp.extrainfo AS mysellerinfo
                   ,app.extrainfo AS mybuyerinfo
                FROM preferredpayment pp
                JOIN paymenttypes pt ON pt.paymenttypeid=pp.paymenttypeid AND pt.active=1
                LEFT JOIN preferredpayment mpp ON mpp.userid=".$offerinfDealer." AND mpp.paymenttypeid=pp.paymenttypeid AND mpp.transactiontype<>pp.transactiontype
                LEFT JOIN preferredpayment app ON app.userid=".$offerinfDealer." AND app.paymenttypeid=pp.paymenttypeid AND app.transactiontype=pp.transactiontype
                WHERE pp.userid=".$listingUserId."
                  AND pp.transactiontype='".$transactionType."'
                ORDER BY pt.paymenttypename";
//echo "PaymentMethodSQL:<br />\n<pre>".$sql."</pre><br />\n";
    if ($preferred = $page->db->sql_query($sql)) {
        $onChange = NULL;
        if ($transactionType == 'Wanted') {
            $onChange = "onChange='sellerPaymentInfo(this);'";
            $preferredSellerArray = "var preferredSellerExtras = [";
            $separator = "";
            foreach ($preferred as $prefer) {
                if ($paymentMethod == $prefer['paymenttypename']) {
                    if ($prefer['allowinfo'] == 'Yes') {
                        $displaySellerPayment = true;
                        $sellerExtra = 1;
                    } else {
                        if ($prefer['allowinfo'] == 'Optional') {
                            $displaySellerPayment = true;
                            $sellerExtra = 2;
                        } else {
                            $sellerExtra = 0;
                        }
                    }
                }
                if ($prefer['mysellerinfo']) {
                    $preferredSellerArray .= $separator."['".$prefer['ptname']."','".$prefer['allowinfo']."','".$prefer['mysellerinfo']."']";
                } else {
                    if ($prefer['mybuyerinfo']) {
                        $preferredSellerArray .= $separator."['".$prefer['ptname']."','".$prefer['allowinfo']."','".$prefer['mybuyerinfo']."']";
                    } else {
                        $preferredSellerArray .= $separator."['".$prefer['ptname']."','".$prefer['allowinfo']."','']";
                    }
                }
                $separator = ",";
            }
            $preferredSellerArray .= "];";
        }
        $preferredPayment = getSelectDDM($preferred, "paymentmethod", "paymenttypename", "paymenttypename", NULL, $paymentMethod, "Select Payment Method", 0, NULL, NULL, $onChange)."\n";
    } else {
        $preferredPayment = "Unspecified";
    }

    return $preferredPayment;
}


function getFeesDDM($paysFees, $listingUserId, $offerUserId) {
    global $UTILITY;

    $spacers = "        ";
    $paysFeesDDM = $spacers."<select id='paysfees' name='paysfees'>\n";
    $paysFeesDDM .= $spacers."  <option value='-1'".(($paysFees==-1) ? " selected " : "").">Select</option>\n";
    $paysFeesDDM .= $spacers."  <option value='0'".(($paysFees==0) ? " selected " : "").">N/A</option>\n";
    $paysFeesDDM .= $spacers."  <option value='".$listingUserId."'".(($paysFees==$listingUserId) ? " selected " : "").">".strtoupper($UTILITY->getUserName($listingUserId))."</option>\n";
    $paysFeesDDM .= $spacers."  <option value='".$offerUserId."'".(($paysFees==$offerUserId) ? " selected " : "").">".strtoupper($UTILITY->getUserName($offerUserId))."</option>\n";
    $paysFeesDDM .= $spacers."</select>";
    return $paysFeesDDM;
}

function getOfferHistory($offerId) {
    global $page;

    $sql = "SELECT *, to_char(to_timestamp(createdate), 'MM/DD/YYY HH24:MI:SS') as createdat, to_char(to_timestamp(modifydate), 'MM/DD/YYYY HH24:MI:SS') as modifiedat, to_char(to_timestamp(offerexpiration), 'MM/DD/YYYY HH24:MI:SS') as expiresat FROM offers WHERE threadid IN (SELECT threadid FROM offers WHERE offerid=".$offerId.") ORDER BY createdate desc";
    $offerThread = $page->db->sql_query($sql);

    return $offerThread;
}

?>
