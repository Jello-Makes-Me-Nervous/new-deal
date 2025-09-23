<?php
define('CLI_SCRIPT', true);
GLOBAL $CFG, $DB, $UTILITY;

if (CLI_SCRIPT) {
    $newline = "\n";
    $batchdir = dirname($argv[0])."/";
} else {
    $newline = "<BR />\n";
    $batchdir = getcwd()."/";
}

require_once($batchdir.'../config.php');
require_once($batchdir.'../setup.php');
require_once($batchdir.'../template.class.php');

$page = new template(NOLOGIN, NOSHOWMSG, REDIRECTSAFE);

echo $newline.date('m/d/Y H:i:s')." - BEGIN Offer Expiration";

$results = NULL;

$sql = "
    UPDATE offers
       SET offerstatus  = 'EXPIRED',
           modifydate   = offerexpiration,
           modifiedby   = 'Batch'
      FROM users  u, users  u2
     WHERE offers.offerstatus       = 'PENDING'
       AND offers.offerexpiration IS NOT NULL
       AND offers.offerexpiration   < nowtoint()
       AND u.userid                 = offers.offeredby
       AND u2.userid                = offers.offerto
    RETURNING offers.offerid, offers.offerfrom, offers.offerto, offers.offeredby, u.username, u2.username as touser
";
//echo $newline;
//echo "Expire SQL:";
//echo $newline;
//echo $sql;
//echo $newline;
try {
    $results = $page->db->sql_query($sql);
    //echo $newline."Results:".$newline;
    //var_dump($results);
    //echo $newline;
} catch (Exception $e) {
    $msg = "ERROR: ".$e->getMessage()." [Unable to process expired offers]";
    echo $newline.$msg;
    $results = NULL;
} finally {
}
//echo $newline;
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
                                                 $messageType, NULL, NULL, NULL, $replyRequired);
            echo $newline."Expired offerid ".$offerId." messaged user ".$toText."(".$toId.")";
        }
    //} else {
    //    echo $newline."No updates";
    }
//} else {
//    echo $newline."No results";
}
echo $newline.date('m/d/Y H:i:s')." - FINISHED Offer Expiration";
echo $newline.date('m/d/Y H:i:s')." - BEGIN Offer Archive";

$defaultRating =
$sql = "UPDATE offers
    SET offerstatus='ARCHIVED'
        ,satisfiedsell = CASE WHEN satisfiedsell=0 THEN ".$CFG->DEFAULT_RATING." ELSE satisfiedsell END
        ,satisfiedbuy = CASE WHEN satisfiedbuy=0 THEN ".$CFG->DEFAULT_RATING." ELSE satisfiedbuy END
        ,modifydate=nowtoint()
        ,modifiedby='Batch'
    WHERE offerstatus='ACCEPTED'
      AND completedon IS NOT NULL
      AND completedon < nowtoint()
      AND (disputefromopened IS NULL OR disputefromclosed IS NOT NULL)
      AND (disputetoopened IS NULL OR disputetoclosed IS NOT NULL)";
try {
    $results = $page->db->sql_execute($sql);
    echo $newline."Archived ".$results." offers";
    //echo $newline."Results:".$newline;
    //var_dump($results);
    //echo $newline;
} catch (Exception $e) {
    $msg = "ERROR: ".$e->getMessage()." [Unable to process archive offers]";
    echo $newline.$msg;
} finally {
}

echo $newline.date('m/d/Y H:i:s')." - FINISHED Offer Archive".$newline;
?>