<?php

require_once('templateMarket.class.php');

$page = new template(LOGIN, SHOWMSG);

$blastListingId     = optional_param('listingid', NULL, PARAM_INT);

$makeOffer          = optional_param('makeoffer', NULL, PARAM_TEXT);
$cancelOffer        = optional_param('canceloffer', NULL, PARAM_TEXT);
$offerExpiration    = optional_param('offerexpiration', NULL, PARAM_INT);
$offerText          = optional_param('offertext', NULL, PARAM_TEXT);
$offerCost          = optional_param('offercost', NULL, PARAM_TEXT);
$paymentTiming      = optional_param('paymenttiming', NULL, PARAM_TEXT);
$paymentMethod      = optional_param('paymentmethod', NULL, PARAM_TEXT);
$paysFees           = optional_param('paysfees', -1, PARAM_INT);
$offerNotes         = optional_param('offernotes', NULL, PARAM_TEXT);

//echo "Process Items:".$processItems."<br />\n";
//echo "Cart Items:".$cartItems."<br />\n";
//echo "Make Offer:".$makeOffer."<br />\n";
$itemInfo = NULL;
$billTo = NULL;
$shipTo = NULL;

$elitePaymentTiming = false;

if ($blastListingId) {
    if ($itemInfo = getBlastItem($blastListingId)) {
        if ($itemInfo['acceptoffers']) {
            $listingUserId = $itemInfo['listinguserid'];
            $offerType = $itemInfo['type'];
            if ($offerType == 'For Sale') {
                if ($page->user->hasUserRight(USERRIGHT_NAME_ELITE)) {
                    $elitePaymentTiming = true;
                }
                $billTo = $USER->getContactInfoType($listingUserId, BILLING);
                $shipTo = $USER->getContactInfoType($page->user->userId, SHIPPING);
            } else {
                if ($itemInfo['elitelisting']) {
                    $elitePaymentTiming = true;
                }
                $billTo = $USER->getContactInfoType($page->user->userId, BILLING);
                $shipTo = $USER->getContactInfoType($listingUserId, SHIPPING);
            }

            if (isset($cancelOffer)) {//cancel
                header("Location:blastview.php?listingid=".$blastListingId);
            }

            if (isset($makeOffer)) {//submit
                if ($newOfferId = createOffer()) {
                    header('Location:offer.php?offerid='.$newOfferId."&pgsmsg=".URLEncode("Created new offer"));
                    exit();
                }
            }
        } else {
            header("Location:blastview.php?listingid=".$blastListingId."&pgemsg=".URLEncode("This blast does not accept offers."));
            exit();
        }
    } else {
        $page->messages->addErrorMsg("Blast not found.");
    }
}
/*
echo "CartItemIds:";
var_dump($cartItems);
echo "<br />\n";
echo "Listing User Id:".$listingUserId."<br />\n";
echo "Listing Type:".$offerType."<br />\n";
echo "Cart:<br />\n<pre>";var_dump($shoppingCart);echo "</pre><br />\n";
exit;
*/

echo $page->header('Process Blast Offer');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $UTILITY, $itemInfo, $billTo, $shipTo, $elitePaymentTiming;
    global $offerExpiration, $offerType, $offerText, $offerCost, $paymentMethod, $paymentTiming, $paymentType, $paysFees, $offerNotes;

    if (isset($itemInfo)) {
        $offerTotal = 0;

        echo "<br />Subject: Offer by ".strtoupper($itemInfo['offeringusername'])." to ".strtoupper($itemInfo['listinguserid'])." on ".date('m/d/y h:i:sa')."<br />\n";
        echo strtoupper($itemInfo['type'])." by ".strtoupper($itemInfo['listingusername'])."<br />\n";

        echo "<table><theader><tr><th>Pay To</th><th>Ship To</th></tr></theader>\n";
        echo "<tr><td style='vertical-align:top'>";
        echo $page->user->formatOfferContactInfo($billTo);
        echo "</td><td style='vertical-align:top'>";
        echo $page->user->formatOfferContactInfo($shipTo);
        echo "</td>\n";
        echo "</tr>\n";
        echo "</table>\n";


        echo "<form name ='sub' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
        echo "  <input type='hidden' id='listingid' name='listingid' value='".$itemInfo['listingid']."'>\n";
        echo "<table width='80%' cellpadding='0' cellspacing='10'>\n";
        echo "  <thead>\n";
        echo "    <tr>\n";
        echo "      <th align='left'>Offer Text</th>\n";
        echo "    </tr>\n";
        echo "  </thead>\n";
        echo "  <tbody>\n";
        echo "    <tr>\n";
        echo "      <td><textarea name='offertext' id='offertext' rows=3 cols=120>".$offerText."</textarea></td>\n";
        echo "    </tr>\n";
        echo "  </tbody>\n";
        echo "</table>\n";
        echo "<table width='80%' cellpadding='0' cellspacing='10'>\n";
        echo "  <thead>\n";
        echo "    <tr>\n";
        echo "      <th align='left'>Offer Total</th>\n";
        echo "    </tr>\n";
        echo "  </thead>\n";
        echo "  <tbody>\n";
        echo "    <tr>\n";
        echo "      <td>$<input type='text' name='offercost' id='offercost' class='number' style='width: 12ch;' value='".$offerCost."' /></td>\n";
        echo "    </tr>\n";
        echo "  </tbody>\n";
        echo "</table>\n";

        echo "Valid for: 120 hours <input type='hidden' name='offerexpiration' id='offerexpiration' value='120' /><br />\n";
        echo "<br />\n";

        echo "<strong>NOTE:</strong> You have the ability to cancel this offer at anytime prior to its fulfillment from your <strong>&quot;Pending Offers&quot;</strong> folder. Once an offer is accepted, seller agrees to ship within one business day with tracking # provided and buyer agrees to pay using the terms and payment method stated without exception.<br />\n";
        echo "<br />\n";

        echo "<strong>Terms:<br />\n";
        echo "Buyer is responsible for all shipping charges on supplies orders.<br />\n";
        echo "Card boxes and cases qualify for free shipping within continental US on $300+ orders.<br />\n";
        echo "</strong><br />\n";


        echo "<strong>Payment Timing</strong><br />\n";
        echo "<select name='paymenttiming' id='paymenttiming' >\n";
        if ($elitePaymentTiming) {
            echo "<option value=''>Select Payment Timing</option>\n";
            echo "<option value='Payment due within 1 business day of offer acceptance (upfront)' ".(($paymentTiming=="Payment due within 1 business day of offer acceptance (upfront)") ? " selected " : "").">Payment due within 1 business day of offer acceptance (upfront)</option>\n";
            echo "<option value='Payment due within 2 business day2 of order delivery (on receipt)' ".(($paymentTiming=="Payment due within 2 business day of order delivery (on receipt)") ? " selected " : "").">Payment due within 2 business days of order delivery (on receipt)</option>\n";
        } else {
            echo "<option value='Payment due within 1 business day of offer acceptance (upfront)' ".(($paymentTiming=="Payment due within 1 business day of offer acceptance (upfront)") ? " selected " : "").">Payment due within 1 business day of offer acceptance (upfront)</option>\n";
        }
        echo "</select>\n";
        echo "<br />\n";
        echo "<br />\n";

        echo "<strong>Payment Method</strong><br />\n".paymentMethodDDM($itemInfo['listinguserid'], $offerType, $paymentMethod)."<br />\n<br />\n";

        echo "<strong>Member Responsible For Any Payment Processing Fees</strong><br />\n".getFeesDDM($paysFees, $itemInfo['listinguserid'], $page->user->userId)."<br />\n<br />\n";

        echo "<strong>Offer Notes</strong><br />\n";
        echo "<textarea name='offernotes' id='offernotes' rows='8' cols='90'>".$offerNotes."</textarea>\n";

        echo "<br />\n";
        echo "<br />\n";
        echo "<p style='color:red;font-weight:bold'>The text box above may only be used for order instructions.  We scan and analyze messages to identify potential fraud and policy violations. Failure to follow policies will trigger transaction fees and/or possible loss of account privileges.  If you are requesting a different price or quantity than what the member posted, you must use the <span style='color:blue;'>&quot;Counter Offer&quot;</span> button to send a private offer.</p>";
        echo "<input class='button' type='submit' name='makeoffer' id='makeoffer' value='Make Offer'>\n";
        echo "<a class='button' href='blastview.php?listingid=".$itemInfo['listingid']."'>Back to Blast</a>\n";
        echo "</form>\n";
    }

}

function getDealerPreferredPayment($listingUserId, $transactionType) {
    global $page;

    $preferredPayment = "";

    $sql = "
        SELECT pt.paymenttypename
          FROM preferredpayment     pp
          JOIN paymenttypes         pt  ON  pt.paymenttypeid    = pp.paymenttypeid
                                        AND pt.active           = 1
         WHERE pp.userid            = ".$listingUserId."
           AND pp.transactiontype   = '".$transactionType."'
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
    global $UTILITY, $page, $itemInfo, $billTo, $shipTo, $offerType;
    global $offerExpiration, $offerText, $offerCost, $paymentMethod, $paymentTiming, $paysFees, $offerNotes;
    $isValid = true;

    $returnOfferId = null;
    $offerId = null;


    if (($offerExpiration < 1) || ($offerExpiration > 120)) {
        $page->messages->addErrorMsg("Valid for(hours) must be between 1 and 120 hours");
        $isValid = false;
    }

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

    if (empty($offerText)) {
        $page->messages->addErrorMsg("Offer text is required");
        $isValid = false;
    }

    if (! is_numeric($offerCost)) {
        $page->messages->addErrorMsg("Price must be a numeric value");
        $isValid = false;
    } else {
        $offerTotal = moneyToFloat(floatval($offerCost));
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
        $params["listingUserId"]    = $itemInfo['listinguserid'];
        $params["transactionType"]  = $offerType;
        $params["paymentMethod"]    = $paymentMethod;
        $pMethod = $page->db->get_field_query($sql, $params);
        unset($params);

        $page->db->sql_begin_trans();

        if ($offerId = $UTILITY->nextval('offers_offerid_seq')) {
            if ($threadId = $UTILITY->nextval('offers_threadid_seq')) {
                $params = array();
                $params['offerid']          = $offerId;
                $params['threadid']         = $threadId;
                $params['offerfrom']        = $page->user->userId;
                $params['offerto']          = $itemInfo['listinguserid'];
                $params['offeredby']        = $page->user->userId;
                $params['offerstatus']      = 'PENDING';
                $params['transactiontype']  = $offerType;
                $params['offerdsubtotal']   = $offerTotal;
                $params['offerexpiration']  = $offerExpiration;
                $params['paymentmethod']    = (!empty($pMethod)) ? $pMethod : $paymentMethod;
                $params['paymenttiming']    = $paymentTiming;
                $params['paysfees']         = $paysFees;
                $params['offernotes']       = $offerNotes;
                $params['counterminimumdtotal']   = 0;
                $params['addrbillstreet']   = $billTo['street'];
                $params['addrbillstreet2']  = $billTo['street2'];
                $params['addrbillcity']     = $billTo['city'];
                $params['addrbillstate']    = $billTo['state'];
                $params['addrbillzip']      = $billTo['zip'];
                $params['addrbillcountry']  = $billTo['country'];
                $params['addrbillphone']    = $billTo['phone'];
                $params['addrbillemail']    = $billTo['email'];
                $params['addrbillnote']     = $billTo['addressnote'];
                $params['addrbillacctnote'] = $billTo['accountnote'];
                $params['addrbillfirstname']= $billTo['firstname'];
                $params['addrbilllastname'] = $billTo['lastname'];
                $params['addrbillcompanyname']   = $billTo['companyname'];
                $params['addrshipstreet']   = $shipTo['street'];
                $params['addrshipstreet2']  = $shipTo['street2'];
                $params['addrshipcity']     = $shipTo['city'];
                $params['addrshipstate']    = $shipTo['state'];
                $params['addrshipzip']      = $shipTo['zip'];
                $params['addrshipcountry']  = $shipTo['country'];
                $params['addrshipphone']    = $shipTo['phone'];
                $params['addrshipemail']    = $shipTo['email'];
                $params['addrshipnote']     = $shipTo['addressnote'];
                $params['addrshipacctnote'] = $shipTo['accountnote'];
                $params['addrshipfirstname']= $shipTo['firstname'];
                $params['addrshiplastname'] = $shipTo['lastname'];
                $params['addrshipcompanyname']   = $shipTo['companyname'];
                $params['createdby']        = $page->user->username;
                $params['modifiedby']       = $page->user->username;

                $sql = "INSERT INTO offers (offerid, threadid
                        ,offerto, offerfrom, offeredby, offerstatus
                        ,transactiontype, offerdsubtotal, offerexpiration
                        ,paymentmethod, paymenttiming, paysfees, offernotes, counterminimumdtotal
                        ,addrbillstreet, addrbillstreet2, addrbillcity, addrbillstate, addrbillzip, addrbillcountry
                        ,addrbillphone, addrbillemail
                        ,addrbillnote, addrbillacctnote, addrbillfirstname, addrbilllastname, addrbillcompanyname
                        ,addrshipstreet, addrshipstreet2, addrshipcity, addrshipstate, addrshipzip, addrshipcountry
                        ,addrshipphone, addrshipemail
                        ,addrshipnote, addrshipacctnote, addrshipfirstname, addrshiplastname, addrshipcompanyname
                        ,createdby, modifiedby)
                    VALUES (:offerid, :threadid
                        ,:offerto, :offerfrom, :offeredby, :offerstatus
                        ,:transactiontype, :offerdsubtotal, (nowtoint()+(:offerexpiration*60*60))
                        ,:paymentmethod, :paymenttiming, :paysfees, :offernotes, :counterminimumdtotal
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

                    $params = array();
                    $params['offerid'] = $offerId;
                    $params['threadid'] = $threadId;
                    $params['fromuserid'] = $page->user->userId;
                    $params['touserid'] = $itemInfo['listinguserid'];
                    $params['offerqty'] = 1;
                    $params['itemnotes'] = $offerText;
                    $params['offerdprice'] = $offerTotal;
                    $params['listingid'] = $itemInfo['listingid'];
                    $params['createdby'] = $page->user->username;
                    $params['modifiedby'] = $page->user->username;
                    $sql = "INSERT INTO offeritems( offerid, threadid
                                ,touserid, fromuserid, offerqty, itemnotes
                                ,listingid, lstcatid, lstsubcatid, lstboxtypeid
                                ,lstyear, lstuom, lstbxpercase, lsttitle
                                ,lsttype, lstminqty, lstqty, lstdprice, lstnotes, offerdprice, picture
                                ,createdby, modifiedby)
                            SELECT :offerid, :threadid
                                ,:touserid, :fromuserid, :offerqty, :itemnotes
                                ,listingid, categoryid, subcategoryid, boxtypeid
                                ,year, uom, boxespercase, title
                                ,type, minquantity, quantity, dprice, listingnotes, :offerdprice, picture
                                ,:createdby, :modifiedby
                            FROM listings
                            WHERE listingid = :listingid";
//echo "SQL:".$sql."<br />\n";
//echo "<pre>";var_dump($params);echo "</pre><br  />\n";
                    if ($page->db->sql_execute_params($sql, $params)) {
                        echo "Added offer item ".$itemId."<br />\n";
                    } else {
                        $page->messages->addErrorMsg("Error adding offer item");
                        $isValid = false;
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

        if ($isValid) {
            $page->db->sql_commit_trans();
            $returnOfferId = $offerId;
        } else {
            $page->db->sql_rollback_trans();
        }
    }

    return $returnOfferId;
}

function paymentMethodDDM($listingUserId, $transactionType, $paymentMethod) {
    global $page;

    $preferredPayment = "";

    $sql = "SELECT pt.paymenttypename                   AS ptname,
                   CASE WHEN pt.allowinfo='Yes' THEN CONCAT(pt.paymenttypename || ' - ' || pp.extrainfo)
                        WHEN pt.allowinfo='Optional' AND length(pp.extrainfo) > 0 THEN CONCAT(pt.paymenttypename || ' - ' || pp.extrainfo)
                        ELSE pt.paymenttypename
                    END AS paymenttypename
                FROM preferredpayment   pp
                JOIN paymenttypes       pt  ON  pt.paymenttypeid    = pp.paymenttypeid
                                            AND pt.active           = 1
                WHERE pp.userid             = ".$listingUserId."
                  AND pp.transactiontype    = '".$transactionType."'
                ORDER BY pt.paymenttypename";
    if ($preferred = $page->db->sql_query($sql)) {
        $preferredPayment = getSelectDDM($preferred, "paymentmethod", "ptname", "ptname", NULL, $paymentMethod, "Select Payment Method", 0)."\n";
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

function getBlastItem($blastListingId) {
    global $page;

    $blastCart = NULL;

    $sql = "
        SELECT lis.listingid, lis.status, lis.type, lis.acceptoffers,
               lis.userid as listinguserid, usr.username as listingusername,
               me.userid as offeringuserid, me.username as offeringusername,
               cat.categoryid, cat.categoryname, cat.categorydescription,
               sub.subcategoryid, sub.subcategoryname, sub.subcategorydescription,
               box.boxtypeid, box.boxtypename,
               lis.dprice, lis.year, lis.listingnotes, lis.minquantity, lis.picture, lis.quantity, lis.uom, lis.boxespercase, lis.picture,
               CASE WHEN ar.userrightid IS NULL THEN 0 ELSE 1 END AS elitelisting
          FROM listings             lis
          JOIN categories           cat ON  cat.categoryid      = lis.categoryid
          JOIN subcategories        sub ON  sub.subcategoryid   = lis.subcategoryid
          JOIN users                usr ON  usr.userid          = lis.userid
          JOIN boxtypes             box ON  box.boxtypeid       = lis.boxtypeId
          JOIN users                me  ON  me.userid           = ".$page->user->userId."
          LEFT JOIN assignedrights  ar  ON  ar.userid           = lis.userid
                                        AND ar.userrightid      = ".USERRIGHT_ELITE."
         WHERE lis.listingid=".$blastListingId;

    //echo "<pre>".$sql."</pre>";
    if($results = $page->db->sql_query($sql)) {
        $blastCart = reset($results);
    }

    return $blastCart;
}

?>