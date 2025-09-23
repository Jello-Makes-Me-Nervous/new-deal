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

echo $newline.date('m/d/Y H:i:s')." - BEGIN Inactive Listings batch".$newline;

$modifiedBefore = $DB->get_field_query("SELECT now() - interval '".$CFG->INACTIVE_LISTING_STALE_DAYS."' day");

echo "INACTIVE_LISTING_STALE_DAYS: ".$CFG->INACTIVE_LISTING_STALE_DAYS." Before:".$modifiedBefore.$newline;

$success = true;
$numInserted = 0;
$numDeleted = 0;

$sql =  "INSERT INTO archivesecondary
    SELECT l.*
    FROM listings l
      LEFT JOIN shoppingcart sct on sct.listingid=l.listingid
      LEFT JOIN offeritems oi ON oi.listingid=l.listingid
    WHERE l.status='CLOSED' 
      AND l.modifydate < datetimetoint((now() - interval '".$CFG->INACTIVE_LISTING_STALE_DAYS."' day)::TIMESTAMP WITHOUT TIME ZONE)
      AND sct.shoppingcartid IS NULL
      AND oi.offeritemid IS NULL";
      
try {
    echo "Attempt archive".$newline;
    $numInserted = $DB->sql_execute_params($sql);
} catch (Exception $e) {
    $msg = "ERROR: ".$e->getMessage()." [Unable to Back Up Inactive Listings]";
    echo $newline.$msg.$newline;
    $success = false;
} finally {
}

if ($success) {
    echo date('m/d/Y H:i:s')." Archived ".$numInserted." rows.".$newline;
}

if ($success) {
    $sql =  "DELETE FROM listings 
        WHERE listingid IN ( 
            SELECT l.listingid 
            FROM archivesecondary a
            JOIN listings l on l.listingid=a.listingid
        )";
    try {
        echo "Attempt delete".$newline;
        $numDeleted = $DB->sql_execute_params($sql);
    } catch (Exception $e) {
        $msg = "ERROR: ".$e->getMessage()." [Unable to Delete Inactive Listings]";
        echo $newline.$msg.$newline;
        $success = false;
    } finally {
    }
    
    if ($success) {
        echo date('m/d/Y H:i:s')." Deleted ".$numDeleted." rows.".$newline;
    }
}

echo $newline.date('m/d/Y H:i:s')." - Finished Inactive Listings batch".$newline;
?>