<?php

require_once('templateMarket.class.php');

$page = new templateMarket(LOGIN, SHOWMSG);
$page->requireJS('scripts/shoppingCart.js');
$iMessaging = new internalMessage();

$itemCount          = optional_param('itemCount', NULL, PARAM_INT);
$listingUserId      = optional_param('listingUserId', NULL, PARAM_INT);
$makeOffer          = optional_param('makeOffer', NULL, PARAM_TEXT);
$cancelOffer        = optional_param('cancelOffer', NULL, PARAM_TEXT);
$paymentTiming      = optional_param('paymenttiming', NULL, PARAM_TEXT);
$paymentMethod      = optional_param('paymentmethod', NULL, PARAM_TEXT);
$sellerPayment      = optional_param('sellerpayment', NULL, PARAM_TEXT);
$sellerExtra        = optional_param('sellerextra', 0, PARAM_INT);
$paysFees           = optional_param('paysfees', 0, PARAM_INT);
$offerNotes         = optional_param('offernotes', NULL, PARAM_TEXT);
$total              = optional_param('total', NULL, PARAM_TEXT);
$offerType          = optional_param('offertype', NULL, PARAM_TEXT);
////
$unTotal            = optional_param('unTotal', NULL, PARAM_TEXT);

$processItems       = optional_param('processitems', NULL, PARAM_TEXT);
$cartItems = null;

$offerExpirationDate = $page->utility->skipxBusinessDays($page->cfg->DEFAULT_OFFER_EXPIRATION);

$shoppingCart = new shoppingcart;
$shoppingCart->syncCartUpdates($page->user->userId);

$cartItems = null;

if (isset($cancelOffer)) {//submit
    header('Location:shoppingCart.php');
    exit();
}

$elitePaymentTiming = "upfront";

// Default to canSell sets Premium and Vendor to true Basic to false - later if its a buy we will change it
$canOfferIfBasic = $page->user->canSell();

$validItems = false;
if (!empty($processItems)) {
    $itemsText = explode(",", $processItems);
    foreach($itemsText as $itemIdText) {
        $cartItems[] = intval($itemIdText);
        $sql = "
            SELECT count(*)
              FROM shoppingcart sc
              JOIN listings     l   ON  l.listingid = sc.listingid
             WHERE sc.shoppingcartid = ".$itemIdText;

        if ($page->db->get_field_query($sql) == 1) {
            $validItems = true;
        } else {
            header("Location:shoppingCart.php?pgemsg=".URLEncode("Unable to load cart items for offer."));
        }
    }
}

if ($validItems) {
    $firstItem = reset($cartItems);
    if ($itemInfoList = $shoppingCart->getShoppingCart($page->user->userId, NULL, $firstItem)) {
        $itemInfo = reset($itemInfoList);
        $listingUserId = $itemInfo['listinguserid'];
        $offerType = $itemInfo['type'];
        if ($offerType == 'For Sale') {
            $canOfferIfBasic = $page->user->canOffer(); // Allows basic to buy too
            if ($page->user->hasUserRight(USERRIGHT_NAME_ELITE)) {
                $elitePaymentTiming = "both";
            }
            $shoppingCart->billTo = $USER->getContactInfoType($listingUserId, BILLING);
            $counterMinimumTotal = $shoppingCart->billTo['counterminimumdtotal'];
            $shoppingCart->shipTo = $USER->getContactInfoType($page->user->userId, SHIPPING);
        } else {
            if ($itemInfo['elitelisting']) {
                $elitePaymentTiming = "onreceipt";
            }
            $shoppingCart->billTo = $USER->getContactInfoType($page->user->userId, BILLING);
            $shoppingCart->shipTo = $USER->getContactInfoType($listingUserId, SHIPPING);
            $counterMinimumTotal = $shoppingCart->shipTo['counterminimumdtotal'];
        }
    } else {
        echo "Error getting item:".$firstItem."<br />\n";
    }
}

if (isset($cartItems)) {
    if ($canOfferIfBasic) {
        if (isset($makeOffer)) {//submit
            if ($newOfferId = createOffer()) {
                header('Location:offer.php?offerid='.$newOfferId."&tabid=items&pgsmsg=Created%20new%20offer");
                exit();
            }
        }
    } else {
        header('Location:shoppingCart.php?pgemsg='.URLEncode("You are not authorized to respond to Wanted offers. Contact your Admin for further details."));
        exit();
    }
} else {
    header('Location:shoppingCart.php?pgemsg='.URLEncode("You must select something from your cart to make an offer."));
    exit();
}

$displaySellerPayment = false;
$preferredSellerArray  = NULL;
$preferredMenu = NULL;
if (isset($cartItems)) {
    $preferredMenu = paymentMethodDDM($listingUserId, $offerType, $paymentMethod);
    if ($preferredSellerArray) {
        $page->jsInit($preferredSellerArray);
    }
}
echo $page->header('Process Shopping Cart');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $canOfferIfBasic,$shoppingCart, $processItems, $page, $UTILITY, $itemCount, $listingUserId, $elitePaymentTiming, $makeOffer, $offerExpirationDate, $cartItems;
    global $paymentMethod, $preferredMenu, $sellerExtra, $sellerPayment, $displaySellerPayment, $paymentTiming, $paymentType, $total, $offerType, $paysFees, $offerNotes;

    if (isset($cartItems)) {
        $offerTotal = 0;

        echo "<br />Subject: Offer by ".strtoupper($UTILITY->getUserName($page->user->userId))." to ".strtoupper($UTILITY->getUserName($listingUserId))." on ".date('m/d/y h:i:sa')."<br />\n";
        echo strtoupper($offerType)." by ".strtoupper($UTILITY->getUserName($listingUserId))."<br />\n";

        echo "<table>\n";
        echo "  <thead>\n";
        echo "    <tr>\n";
        echo "      <th>Pay To</th>\n";
        echo "      <th>Ship To</th>\n";
        echo "    </tr>\n";
        echo "  </thead>\n";
        echo "  </tbody>\n";
        echo "    <tr>\n";
        echo "      <td style='vertical-align:top'>";
        if ($page->user->isBasic()) {
            echo formatBasicBillTo($shoppingCart->billTo);
        } else {
            echo $page->user->formatOfferContactInfo($shoppingCart->billTo);
        }
        echo "</td>\n";
        echo "      <td style='vertical-align:top'>";
        echo $page->user->formatOfferContactInfo($shoppingCart->shipTo);
        echo "</td>\n";
        echo "    </tr>\n";
        echo "  </tbody>\n";
        echo "</table>\n";


        echo "<form name ='sub' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post' onsubmit='return validRange()'>\n";
        echo "  <input type='hidden' id='listingUserId' name='listingUserId' value='".$listingUserId."'>\n";
        echo "  <input type='hidden' id='offertype' name='offertype' value='".$offerType."'>\n";
        echo "  <input type='hidden' id='listingfee' name='listingfee' value='".$page->user->listingfee."'>\n";
        echo "  <input type='hidden' id='processitems' name='processitems' value='".$processItems."'>\n";
        echo "  <input type='hidden' name='itemCount' id='itemCount' value='".count($cartItems)."'> \n";
        echo "<table width='80%' cellpadding='0' cellspacing='10'>\n";
        echo "  <thead>\n";
        echo "    <tr>\n";
        echo "      <th align='left'>Item</th>\n";
        echo "      <th align='left'>QTY</th>\n";
        echo "      <th align='left'>Description</th>\n";
        echo "      <th align='left'>Price</th>\n";
        echo "    </tr>\n";
        echo "  </thead>\n";
        echo "  <tbody>\n";
        $i = 1;
        foreach ($cartItems as $offer) {
            $listingPriceMsg = "";
            // get the most updated info about the listed item
            $offerInfo = $shoppingCart->getShoppingCart($page->user->userId, NULL, $offer);
            $info = reset($offerInfo);

            if ($offerItemCounter = optional_param('dprice'.$offer, NULL, PARAM_TEXT)) {
                $strPrice = $offerItemCounter;
            } else {
                $strPrice = $info['currentdprice'];
            }
            $listingPrice = $info['currentdprice'];
            $dprice = moneyToFloat($strPrice);
            $p = $dprice;

            $listingFeeMsg = "";
            if ($listingPrice != $dprice) {
                $listPriceMsg = "(Listing Price: ".$info['currentdprice']."/".$info['uom'].")";
                if ($page->user->listingfee > 0.00) {
                    if ($offerType == 'Wanted') {
                        if ($listingPrice < $dprice) {
                            $listingFeeMsg = "Entire counter offer will be subject to a ".$page->user->listingfee."% transaction fee<br />".$listPriceMsg;
                        } else {
                            $listingFeeMsg = $listPriceMsg;
                        }
                    } else {
                        if ($offerType == 'For Sale') {
                            if ($listingPrice > $dprice) {
                                $listingFeeMsg = "Entire counter offer will be subject to a ".$page->user->listingfee."% transaction fee<br />".$listPriceMsg;
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
            }
            $listingFeeWarning = "<div style='font-weight:bold;' id='feewarning_".$offer."' name='feewarning_".$offer."'>".$listingFeeMsg."</div>";

            $unPrice = $info['unaltereddprice'];
            $unP = $unPrice;

            $offerItemQuantity = optional_param('offerQuantity'.$offer, NULL, PARAM_INT);

            if (($p > 0) && ($offerItemQuantity > 0)) {
                $offerTotal += $p * $offerItemQuantity;
            }

            echo "    <tr>\n";
            echo "      <td>".$i;
            echo "        <input type='hidden' id='cartitemid_".$offer."' name='cartitemid_".$offer."' class='item_id_value' value='".$i."'>\n";
            echo "        <input type='hidden' id='itemid_".$offer."' name='itemid_".$offer."' class='item_id_value' value='".$offer."'>\n";
            echo "        <input type='hidden' id='lstdprice_".$offer."' name='lstdprice".$offer."' value='".number_format($listingPrice,2)."' />\n";
            echo "        <input type='hidden' id='uom_".$offer."' name='uom_".$offer."' value='".$info['uom']."' />\n";
            echo "      </td>\n";
            echo "      <td><input type='text' id='offerQuantity".$offer."' name='offerQuantity".$offer."' size='3' style='text-align:right;' ".(($offerItemQuantity > 0) ? " value='".$offerItemQuantity."'" : "")." onchange='getItemAmounts();' /><br />(Max:".$info['currentqty'].")</td>\n";
            echo "      <td>";
            if (!empty($info['picture'])) {
                if ($imgURL = $UTILITY->getPrefixListingImageURL($info['picture'])) {
                    echo "<a href='".$imgURL."' target=_blank><img class='align-left' src='".$imgURL."' alt='listing image' width='50px' height='50px'></a> ";
                }
            }
            $variation = (empty($info['variation'])) ? "" : " - ".$info['variation'];
            $upc = (empty($info['upcs'])) ? "" : "<b>UPC: </b>".$info['upcs'];
            echo $info['year']." ".$info['subcategoryname']." ".$info['categorydescription']." ".$info['boxtypename'].$variation;
            if (!empty($upc)) {
                echo "<br/>     ".$upc;
            }
            if ($info['uom'] == 'case') {
                echo "<br />(".$info['boxespercase']." boxes per case)";
            }
            if ($info['deliverby']) {
                echo "<br /><strong>Delivery required by ".(date('m/d/Y', $info['deliverby']))."</strong>";
            }
            if ($info['listingnotes']) {
                echo "<br /><strong>Notes:</strong><span>".$info['listingnotes']."</span>";
            }
            echo "      </td>\n";
            if ($page->user->isVendor()) {
                echo "      <td>$<input type='text' id='dprice".$offer."' name='dprice".$offer."' size='6' style='text-align:right;' value='".number_format($p,2)."' onchange=\"checkCounterOffer('".$offer."'); getItemAmounts();\" /> / ".$info['uom'].$listingFeeWarning.$listingPriceMsg."</td>\n";
            } else {
                echo "      <td>$".number_format($p,2)." / ".$info['uom'].$listingPriceMsg." <input type='hidden' id='dprice".$offer."' name='dprice".$offer."' value='".number_format($p,2)."' /></td>\n";
            }
            echo "    </tr>\n";
            $i++;
        }

        echo "    <tr>\n";
        echo "      <td></td>\n";
        echo "      <td></td>\n";
        echo "      <td></td>\n";
        $totalValue = ($offerTotal > 0) ? " value='$".number_format($offerTotal,2)."'" : "";
        echo "      <td>Sub Total: <input type='text' name='total' id='total'".$totalValue." size='8' style='text-align:right;' readonly /> <input type='hidden' name='unTotal' id='unTotal' readonly/></td>\n";
        echo "    </tr>\n";
        echo "  </tbody>\n";
        echo "</table>\n";

        echo "Valid for: ".$page->cfg->DEFAULT_OFFER_EXPIRATION." business days (".date('m/d/Y H:i:s', $offerExpirationDate).")<br />\n";
        echo "<br />\n";

        echo "<strong>NOTE:</strong> You have the ability to cancel this offer at anytime prior to its fulfillment from your <strong>&quot;Pending Offers&quot;</strong> folder. Once an offer is accepted, seller agrees to ship within one business day with tracking # provided and buyer agrees to pay using the terms and payment method stated without exception.<br />\n";
        echo "<br />\n";

        echo "<strong>Terms:<br />\n";
        echo "Buyer is responsible for all shipping charges on supplies orders.<br />\n";
        echo "Card boxes and cases qualify for free shipping within continental US on $300+ orders.<br />\n";
        echo "</strong><br />\n";


        echo "<strong>Payment Timing</strong><br />\n";
        echo "<select name='paymenttiming' id='paymenttiming' >\n";
        if ($elitePaymentTiming  == "both") {
            echo "<option value=''>Select Payment Timing</option>\n";
        }
        if (($elitePaymentTiming  == "upfront") || ($elitePaymentTiming  == "both")) {
            echo "<option value='Payment due within 1 business day of offer acceptance (upfront)' ".(($paymentTiming=="Payment due within 1 business day of offer acceptance (upfront)") ? " selected " : "").">Payment due within 1 business day of offer acceptance (upfront)</option>\n";
        }
        if (($elitePaymentTiming  == "onreceipt") || ($elitePaymentTiming  == "both")) {
            echo "<option value='Payment due within 2 business days of order delivery (on receipt)' ".(($paymentTiming=="Payment due within 2 business days of order delivery (on receipt)") ? " selected " : "").">Payment due within 2 business days of order delivery (on receipt)</option>\n";
        }
        echo "</select><br />\n";
        if ($elitePaymentTiming == "onreceipt") {
            echo "This offer is being made to an elite/gold star member and the buyer agrees to make sure payment is received by the seller within 2 business days of delivery.<br />\n";
        }
        echo "<br />\n";

        echo "<strong>Payment Method</strong><br />\n".$preferredMenu."<br />\n<br />\n";
        if ($offerType == 'Wanted') {
            $sellerDisplayMode = ($displaySellerPayment) ? " style='display: block;'" : " style='display: none;'";
            echo "<div id='sellerpayinfo' name='sellerpayinfo' ".$sellerDisplayMode.">";
            echo "<strong>Sellers Payment Info</strong><br />\n<input type='text' name='sellerpayment' id='sellerpayment' style='width:50ch;' value='".$sellerPayment."' />";
            echo "<br />\n<br /></div>\n";
            echo "<input type='hidden' id='sellerextra' name='sellerextra' value='".$sellerExtra."' />\n";
        }
/*
        echo "<span title='Any agreed upon fee is for 3rd party payment processing fees and not inclusive of any Dealernet fees that may be incurred'><strong>Member responsible for 3% payment processing fee</strong></span><br />\n";
        echo getFeesDDM($paysFees, $listingUserId, $page->user->userId)."<br />\n<br />\n";
*/
        echo "<input type='hidden' id='paysfees' name='paysfees' value='".$paysFees."' />\n";

        if ($page->user->canSell()) {
            echo "<strong>Offer Notes</strong><br />\n";
            echo "<textarea name='offernotes' id='offernotes' rows='8' cols='90'>".$offerNotes."</textarea>\n";
            echo "<br />\n";
            echo "<br />\n";
            echo "<p style='color:red;font-weight:bold'>The text box above may only be used for order instructions.  We scan and analyze messages to identify potential fraud and policy violations. Failure to follow policies will trigger transaction fees and/or possible loss of account privileges.</p>";
        } else {
            echo "<input type='hidden' id='offernotes' name='offernotes' value=''>\n";
        }

        echo "<input class='button' type='submit' name='makeOffer' id='makeOffer' value='Make Offer'>\n";
        echo "<a class='button' href='shoppingCart.php'>Back to Cart</a>\n";
        echo "</form>\n";

    } else {
        echo "Send back to cart with message \"Nothing Selected\" \n";
    }

}

function getDealerSignature($dealerId) {
    global $page;

    $signature = null;

    $sql = "SELECT internalsig FROM userinfo WHERE userid=".$dealerId;

    if ($results = $page->db->sql_query($sql)) {
        if (is_array($results) && (count($results) > 0)) {
            $signature = $results[0]['internalsig'];
        }
    }
    return $signature;
}

function getDealerPreferredPayment($listingUserId, $transactionType) {
    global $page;

    $preferredPayment = "";

    $sql = "SELECT pt.paymenttypename
                FROM preferredpayment pp
                JOIN paymenttypes pt ON pt.paymenttypeid=pp.paymenttypeid AND pt.active=1
                WHERE pp.userid=".$listingUserId."
                  AND pp.transactiontype='".$transactionType."'
                ORDER BY pt.paymenttypename";
    if ($preferred = $page->db->sql_query($sql)) {
        $separator = "";
        foreach ($preferred as $preferredOne) {
            $preferredPayment .= $separator.$preferredOne['paymenttypename'];
            $separator = ", ";
        }
    }

    return $preferredPayment;
}

function createOffer() {
    global $UTILITY, $page, $shoppingCart, $cartItems, $offerType, $paymentMethod, $sellerPayment, $sellerExtra, $paymentTiming, $paysFees, $offerNotes, $listingUserId, $offerType,
        $counterMinimumTotal, $counterCollarLow, $counterCollarHigh, $offerExpirationDate;
    $isValid = true;
    $inTransaction = false;
    $itemQtys = array();
    $itemListingIds = array();
    $itemCountereds = array();
    $offerTotal = 0;
    $numInactivators = 0;

    $returnOfferId = null;
    $offerId = null;

    if (empty($paymentMethod)) {
        $page->messages->addErrorMsg("Payment Method is required");
        $isValid = false;
    }

    if (empty($paymentTiming)) {
        $page->messages->addErrorMsg("Payment Timing is required");
        $isValid = false;
    }

    if ($paysFees == -1) {
        $page->messages->addErrorMsg("Payment Fees is required");
        $isValid = false;
    }

    if (($sellerExtra == 1) && empty($sellerPayment)) {
        $page->messages->addErrorMsg("Seller Payment info is required.");
        $isValid = false;
    }

    $cartMgr = new shoppingcart();

    $counterTotal = 0.00;

    $hasCountered = false;
    if (is_array($cartItems) && (count($cartItems) > 0)) {
        foreach ($cartItems as $itemId) {
            $cartItem = $cartMgr->getShoppingCartItem($page->user->userId, $itemId);
            $offerItemCartId = optional_param('cartitemid_'.$itemId, NULL, PARAM_INT);
            $offerItemQuantity = optional_param('offerQuantity'.$itemId, NULL, PARAM_INT);
            $listPrice = $cartItem['currentdprice'];
            $counter = moneyToFloat(optional_param('dprice'.$itemId, NULL, PARAM_TEXT));
            if ((! $counter) || (! is_numeric($counter))) {
                $page->messages->addErrorMsg("Price for item ".$offerItemCartId." must be a non-zero numeric value");
                $isValid = false;
            } else {
                if (($offerItemQuantity > 0) && ($listPrice != $counter)) {
                    $hasCountered = true;
                }
                $counterTotal += ($counter * $offerItemQuantity);
            }
            //echo "List:".$listPrice." Offer:".$counter. "Qty:".$offerItemQuantity." RunningCounter:".$counterTotal." Minimum:".$counterMinimumTotal."<br />\n";
        }

        $invalidCounter = false;
        foreach ($cartItems as $itemId) {
            $cartItem = $cartMgr->getShoppingCartItem($page->user->userId, $itemId);
            $offerItemCartId = optional_param('cartitemid_'.$itemId, NULL, PARAM_INT);
            $offerItemQuantity = optional_param('offerQuantity'.$itemId, NULL, PARAM_INT);
            $offerItemCounter = moneyToFloat(optional_param('dprice'.$itemId, NULL, PARAM_TEXT));
            if ($offerItemQuantity > 0) {
                if (($offerItemQuantity < $cartItem['minquantity']) || ($offerItemQuantity > $cartItem['quantity'])) {
                    $page->messages->addErrorMsg("Quantity for item ".$offerItemCartId." must be between ".$cartItem['minquantity']." and ".$cartItem['quantity']);
                    $isValid = false;
                } else {
                    $dprice = $cartItem['currentdprice'];
                    $rawcounter = $offerItemCounter;
                    if (! is_numeric($rawcounter)) {
                        $page->messages->addErrorMsg("Price for item ".$offerItemCartId." must be a numeric value");
                        $isValid = false;
                        $invalidCounter = true;
                    } else {
                        $counter = floatval($rawcounter);
                        $itemsCountereds[$itemId] = 0;

                        if ($counter != $dprice) {
                            if ($offerType == 'For Sale') {
                                if ($counter > $dprice) {
                                    $page->messages->addWarningMsg("Counter offer for item ".$offerItemCartId." greater than asking price");
                                }
                            } else {
                                if ($dprice > $counter) {
                                    $page->messages->addWarningMsg("Counter offer for item ".$offerItemCartId." less than than offered price");
                                }
                            }
                            $itemsCountereds[$itemId] = 1;
                            $hasCountered = true;
                        }
                        $itemTotal = $offerItemQuantity * $counter;
                        $offerTotal += $itemTotal;
                        $itemListingIds[$itemId] = $cartItem['listingid'];
                        $itemQtys[$itemId] = $offerItemQuantity;
                        $itemPrices[$itemId] = $offerItemCounter;
                    }
                }
            } else {
                $page->messages->addErrorMsg("Quantity required for item ".$offerItemCartId);
                $isValid = false;
            }
        }
        if ($hasCountered && (! $invalidCounter)) {
            if ($counterTotal < $counterMinimumTotal) {
                $page->messages->addErrorMsg("Counter offers are not accepted on offers less than $".$counterMinimumTotal);
                $isValid = false;
            }
        }

        if ($page->user->isBasic()) {
            if ($offerTotal > $page->cfg->BASIC_USER_OFFER_MAX) {
                $limitMsg = "Please contact <a href='sendmessage.php?dept=1'>Help Desk</a> for the ability to place orders over ".floatToMoney($page->cfg->BASIC_USER_OFFER_MAX).".";
                $page->messages->addErrorMsg($limitMsg);
                $isValid = false;
            }
        }
    } else {
        $page->messages->addErrorMsg("No offer items specified");
        $isValid = false;
    }

    if ($isValid) {
        $sql = "
            SELECT CASE WHEN pt.allowinfo='Yes' THEN CONCAT(pt.paymenttypename || ' - ' || pp.extrainfo)
                        WHEN pt.allowinfo='Optional' AND length(pp.extrainfo) > 0 THEN CONCAT(pt.paymenttypename || ' - ' || pp.extrainfo)
                        ELSE pt.paymenttypename
                   END AS paymenttypename
              FROM preferredpayment     pp
              JOIN paymenttypes         pt  ON  pt.paymenttypeid    = pp.paymenttypeid
                                            AND pt.active           = 1
             WHERE pp.userid             = :listingUserId
               AND pp.transactiontype    = :transactionType
               AND pt.paymenttypename    = :paymentMethod
        ";
        $params = array();
        $params["listingUserId"]    = $listingUserId;
        $params["transactionType"]  = $offerType;
        $params["paymentMethod"]    = $paymentMethod;
        $pMethod = $page->db->get_field_query($sql, $params);
        unset($params);

        $page->db->sql_begin_trans();
        $inTransaction = true;

        if ($offerId = $UTILITY->nextval('offers_offerid_seq')) {
            if ($threadId = $UTILITY->nextval('offers_threadid_seq')) {
                $params = array();
                $params['offerid'] = $offerId;
                $params['threadid'] = $threadId;
                $params['offerfrom'] = $page->user->userId;
                $params['offerto'] = $listingUserId;
                $params['offeredby'] = $page->user->userId;
                $params['offerstatus'] = 'PENDING';
                $params['transactiontype'] = $offerType;
                $params['offerdsubtotal'] = $offerTotal;
                $params['offerexpiration'] = $offerExpirationDate;
                $params['paymentmethod'] = (!empty($pMethod)) ? $pMethod : $paymentMethod;
                $params['sellerpayment'] = $sellerPayment;
                $params['paymenttiming'] = $paymentTiming;
                $params['paysfees'] = $paysFees;
                $params['offernotes'] = $offerNotes;
                $params['counterminimumdtotal']   = $counterMinimumTotal;
                $params['countered'] = ($hasCountered) ? 1 : 0;
                $params['addrbillstreet']   = $shoppingCart->billTo['street'];
                $params['addrbillstreet2']   = $shoppingCart->billTo['street2'];
                $params['addrbillcity']   = $shoppingCart->billTo['city'];
                $params['addrbillstate']   = $shoppingCart->billTo['state'];
                $params['addrbillzip']   = $shoppingCart->billTo['zip'];
                $params['addrbillcountry']   = $shoppingCart->billTo['country'];
                $params['addrbillphone']   = $shoppingCart->billTo['phone'];
                $params['addrbillemail']   = $shoppingCart->billTo['email'];
                $params['addrbillnote']   = $shoppingCart->billTo['addressnote'];
                $params['addrbillacctnote']   = $shoppingCart->billTo['accountnote'];
                $params['addrbillfirstname']   = $shoppingCart->billTo['firstname'];
                $params['addrbilllastname']   = $shoppingCart->billTo['lastname'];
                $params['addrbillcompanyname']   = $shoppingCart->billTo['companyname'];
                $params['addrshipstreet']   = $shoppingCart->shipTo['street'];
                $params['addrshipstreet2']   = $shoppingCart->shipTo['street2'];
                $params['addrshipcity']   = $shoppingCart->shipTo['city'];
                $params['addrshipstate']   = $shoppingCart->shipTo['state'];
                $params['addrshipzip']   = $shoppingCart->shipTo['zip'];
                $params['addrshipcountry']   = $shoppingCart->shipTo['country'];
                $params['addrshipphone']   = $shoppingCart->shipTo['phone'];
                $params['addrshipemail']   = $shoppingCart->shipTo['email'];
                $params['addrshipnote']   = $shoppingCart->shipTo['addressnote'];
                $params['addrshipacctnote']   = $shoppingCart->shipTo['accountnote'];
                $params['addrshipfirstname']   = $shoppingCart->shipTo['firstname'];
                $params['addrshiplastname']   = $shoppingCart->shipTo['lastname'];
                $params['addrshipcompanyname']   = $shoppingCart->shipTo['companyname'];
                $params['createdby'] = $page->user->username;
                $params['modifiedby'] = $page->user->username;

                $sql = "INSERT INTO offers (offerid, threadid
                        ,offerto, offerfrom, offeredby, offerstatus
                        ,transactiontype, offerdsubtotal, offerexpiration, countered
                        ,paymentmethod, sellerpayment, paymenttiming, paysfees, offernotes, counterminimumdtotal
                        ,addrbillstreet, addrbillstreet2, addrbillcity, addrbillstate, addrbillzip, addrbillcountry
                        ,addrbillphone, addrbillemail
                        ,addrbillnote, addrbillacctnote, addrbillfirstname, addrbilllastname, addrbillcompanyname
                        ,addrshipstreet, addrshipstreet2, addrshipcity, addrshipstate, addrshipzip, addrshipcountry
                        ,addrshipphone, addrshipemail
                        ,addrshipnote, addrshipacctnote, addrshipfirstname, addrshiplastname, addrshipcompanyname
                        ,createdby, modifiedby)
                    VALUES (:offerid, :threadid
                        ,:offerto, :offerfrom, :offeredby, :offerstatus
                        ,:transactiontype, :offerdsubtotal, :offerexpiration, :countered
                        ,:paymentmethod, :sellerpayment, :paymenttiming, :paysfees, :offernotes, :counterminimumdtotal
                        ,:addrbillstreet, :addrbillstreet2, :addrbillcity, :addrbillstate, :addrbillzip, :addrbillcountry
                        ,:addrbillphone, :addrbillemail
                        ,:addrbillnote, :addrbillacctnote, :addrbillfirstname, :addrbilllastname, :addrbillcompanyname
                        ,:addrshipstreet, :addrshipstreet2, :addrshipcity, :addrshipstate, :addrshipzip, :addrshipcountry
                        ,:addrshipphone, :addrshipemail
                        ,:addrshipnote, :addrshipacctnote, :addrshipfirstname, :addrshiplastname, :addrshipcompanyname
                        ,:createdby, :modifiedby)";
//echo "SQL:".$sql."<br />\n";
//echo "<pre>";var_dump($params);echo "</pre><br  />\n";
                if ($page->db->sql_execute_params($sql, $params)) {
                    echo "Added offer id:".$offerId." thread:".$threadId."<br />\n";

                    foreach ($cartItems as $itemId) {
                        if ($isValid) {
                            $params = array();
                            $params['offerid'] = $offerId;
                            $params['threadid'] = $threadId;
                            $params['fromuserid'] = $page->user->userId;
                            $params['touserid'] = $listingUserId;
                            $params['offerqty'] = $itemQtys[$itemId];
                            $params['offerdprice'] = $itemPrices[$itemId];
                            $params['countered'] = $itemsCountereds[$itemId];
                            $params['listingid'] = $itemListingIds[$itemId];
                            $params['createdby'] = $page->user->username;
                            $params['modifiedby'] = $page->user->username;
                            $sql = "INSERT INTO offeritems( offerid, threadid
                                        ,touserid, fromuserid, offerqty
                                        ,listingid, lstcatid, lstsubcatid, lstboxtypeid
                                        ,lstyear, lstyear4, lstuom, lstbxpercase
                                        ,lsttype, lstminqty, lstqty, lstdprice, lstnotes, lstdeliverby, offerdprice, countered, picture
                                        ,createdby, modifiedby)
                                    SELECT :offerid, :threadid
                                        ,:touserid, :fromuserid, :offerqty
                                        ,listingid, categoryid, subcategoryid, boxtypeid
                                        ,year, year4, uom, boxespercase
                                        ,type, minquantity, quantity, dprice, listingnotes, deliverby, :offerdprice, :countered, picture
                                        ,:createdby, :modifiedby
                                    FROM listings
                                    WHERE listingid = :listingid";
                            if ($page->db->sql_execute_params($sql, $params)) {
                                echo "Added offer item ".$itemId."<br />\n";
                            } else {
                                $page->messages->addErrorMsg("Error adding offer item ".$itemId);
                                $isValid = false;
                            }

                            if ($isValid) {
                                $sql = "DELETE FROM shoppingcart where shoppingcartid=".$itemId;
                                if ($page->db->sql_execute($sql)) {
                                    echo "Deleted shopping cart item ".$itemId."<br />\n";
                                } else {
                                    $page->messages->addErrorMsg("Error deleting shopping cart item ".$itemId);
                                    $isValid = false;
                                }
                            }
                        }
                    }

                    if ($isValid) {
                        $preferenceId = ($offerType == 'Wanted') ? USERPREFERENCE_INACTIVATE_WANTED_ID: USERPREFERENCE_INACTIVATE_FORSALE_ID;
                        $sql = "UPDATE offeritems
                            SET listinginactivated=1
                            FROM assignedpreferences
                            WHERE offeritems.offerid=".$offerId."
                              AND offeritems.offerdprice=offeritems.lstdprice
                              AND offeritems.offerqty=offeritems.lstqty
                              AND assignedpreferences.userid=offeritems.touserid
                              AND assignedpreferences.preferenceid=".$preferenceId;
                        //echo "Set OI Inactivate SQL:<br /><pre>".$sql."</pre><br />\n";
                        $numInactivators = $page->db->sql_execute($sql);

                        $sql = "UPDATE listings
                                SET status='CLOSED'
                                WHERE listingid in (
                                    SELECT listingid
                                    FROM offeritems
                                    WHERE offerid=".$offerId."
                                      AND listinginactivated=1)";
                        //echo "Inactivate listing SQL:<br /><pre>".$sql."</pre><br />\n";
                        $page->db->sql_execute($sql);
                    }
                } else {
                    $page->messages->addErrorMsg("Error adding offer");
                    $isValid = false;
                }
            } else {
                $page->messages->addErrorMsg("Error getting threadid");
                $isValid = false;
            }
        } else {
            $page->messages->addErrorMsg("Error getting offerid");
            $isValid = false;
        }

        if ($inTransaction) {
            if ($isValid) {
                $page->db->sql_commit_trans();
                $returnOfferId = $offerId;
                $listingUsername = $page->db->get_field_query("SELECT username FROM users WHERE userid=".$listingUserId);
                $msgSubject = "New Offer Received";
                $msgText = "New Offer Received";
                if ($numInactivators) {
                    $msgText .= "\nNew offer ".$offerId." inactivated ".$numInactivators." listings.";
                }
                $page->iMessage->insertSystemMessage($page, $listingUserId, $listingUsername, $msgSubject, $msgText, EMAIL, NULL, NULL, $offerId);
            } else {
                $page->db->sql_rollback_trans();
            }
        }
    }

    return $returnOfferId;
}

function paymentMethodDDM($listingUserId, $transactionType, $paymentMethod) {
    global $page, $preferredSellerArray, $displaySellerPayment;

    $preferredPayment = "";

    $sql = "SELECT pt.paymenttypeid, pt.allowinfo,
                   pt.paymenttypename                   AS ptname,
                   CASE WHEN pt.allowinfo='Yes' THEN CONCAT(pt.paymenttypename || ' - ' || pp.extrainfo)
                        WHEN pt.allowinfo='Optional' AND length(pp.extrainfo) > 0 THEN CONCAT(pt.paymenttypename || ' - ' || pp.extrainfo)
                        ELSE pt.paymenttypename END     AS paymenttypename,
                   mpp.extrainfo                        AS mysellerinfo,
                   app.extrainfo                        AS mybuyerinfo
                FROM preferredpayment       pp
                JOIN paymenttypes           pt  ON  pt.paymenttypeid    = pp.paymenttypeid
                                                AND pt.active           = 1
                LEFT JOIN preferredpayment  mpp ON  mpp.userid          = ".$page->user->userId."
                                                AND mpp.paymenttypeid   = pp.paymenttypeid
                                                AND mpp.transactiontype <> pp.transactiontype
                LEFT JOIN preferredpayment  app ON  app.userid          = ".$page->user->userId."
                                                AND app.paymenttypeid   = pp.paymenttypeid
                                                AND app.transactiontype = pp.transactiontype
                WHERE pp.userid=".$listingUserId."
                  AND pp.transactiontype='".$transactionType."'
                ORDER BY pt.paymenttypename";
    if ($preferred = $page->db->sql_query($sql)) {
        $preferredPayment = getSelectDDM($preferred, "paymentmethod", "ptname", "ptname", NULL, $paymentMethod, "Select Payment Method", 0, NULL, NULL, "onChange='sellerPaymentInfo(this);'")."\n";
        if ($transactionType == 'Wanted') {
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

function formatBasicBillTo($contactInfo) {
    $output = "";
    if (!(empty($contactInfo['firstname']) && empty($contactInfo['lastname']))) {
        $output .= $contactInfo['firstname']." ".$contactInfo['lastname'];
        if (!empty($contactInfo['username'])) {
            $output .= " (".$contactInfo['username'].")";
        }
        $output .= "<br />";
    }
    if (strlen($contactInfo['companyname'])) {
        $output .= $contactInfo['companyname']."<br />";
    }
    return $output;
}

?>