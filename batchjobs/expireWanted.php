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

echo date('m/d/Y H:i:s')." - BEGIN expire wanted listings batch".$newline;

$thisMorning = strtotime("today");
$tomorrowMorning = strtotime("tomorrow");
$twoDaysMorning = strtotime("today+2 days");


echo "Expire listings that expire before ".date('m/d/Y H:i:s', $thisMorning).$newline;
echo "Warn listings that expire before ".date('m/d/Y H:i:s', $twoDaysMorning)." and after ".date('m/d/Y H:i:s', $tomorrowMorning)." unless created after ".date('m/d/Y H:i:s', $thisMorning).$newline;
//exit;
try {
    // Expirations
    echo "Update listings that expire before ".date('m/d/Y H:i:s', $thisMorning).$newline;
    $users = getUserList($thisMorning);
    if ($users) {
        foreach($users as $user) {
            $listings = getListings($thisMorning, $user['userid']);
            $msgbody = "";
            if ($listings) {
                foreach($listings as $listing) {
                    $product = $listing['categorydescription']."~".$listing['year']."~".$listing['subcategoryname']."~".$listing['boxtypename'];
                    $link = "listing.php?showinactive=1&referenceid=".$listing['listingid'];
                    $msgbody .= "Note: <a href='".$link."' target='_blank'>".$product."</a> expired at ".date('m/d/Y H:i:s', $listing['expireson'])."<br />\n";
                    $sql = "UPDATE listings SET status='CLOSED', modifiedby='admin', modifydate=nowtoint() WHERE listingid=".$listing['listingid']." RETURNING listingid";
                    $result = $page->db->sql_query($sql);
                    if (isset($result)) {
                        echo "Expired listing ".$product." id ".$listing['listingid']." user ".$listing['username']."(".$listing['userid'].")".$newline;
                    } else {
                        echo "ERROR expiring listing ".$product." id ".$listing['listingid']." user ".$listing['userid'].$newline;
                    }
                }
            }
            $subject = "Expired Listings";
            echo "User: ".$user['username']."(".$user['userid'].") Subject:".$subject.$newline;
            echo $msgbody.$newline;
            if ($page->iMessage->insertSystemMessage($page, $user['userid'], $user['username'], $subject, $msgbody, EMAIL)) {
                echo "Sent message".$newline;
            } else {
                echo "ERROR sending message".$newline;
            }
        }
    } else {
        echo "No expirations".$newline;
    }

    // Warnings
    $users = getUserList($tomorrowMorning, $tomorrowMorning, $thisMorning);
    echo $newline."Warn listings that expire before ".date('m/d/Y H:i:s', $twoDaysMorning)." that have not been warned".$newline;
    if ($users) {
        foreach($users as $user) {
            $listings = getListings($twoDaysMorning, $user['userid'], $tomorrowMorning, $thisMorning);
            $msgbody = "";
            if ($listings) {
                foreach($listings as $listing) {
                    $product = $listing['categorydescription']."~".$listing['year']."~".$listing['subcategoryname']."~".$listing['boxtypename'];
                    $link = "listing.php?showinactive=1&referenceid=".$listing['listingid'];
                    $msgbody .= "Warning: <a href='".$link."' target='_blank'>".$product."</a> will expire at ".date('m/d/Y H:i:s', $listing['expireson'])."<br />\n";
                }
            }
            $subject = "Listings Expiring Soon";
            echo "User: ".$user['username']."(".$user['userid'].") Subject:".$subject.$newline;
            echo $msgbody.$newline;
            if ($page->iMessage->insertSystemMessage($page, $user['userid'], $user['username'], $subject, $msgbody, EMAIL)) {
                echo "Sent message".$newline;
            } else {
                echo "ERROR sending message".$newline;
            }
        }
    } else {
        echo "No upcoming expirations".$newline;
    }

} catch (Exception $e) {
    echo $newline."ERROR: ".$e->getMessage()." [Unable to process expirations and warnings]".$newline;
} finally {
    echo $newline.date('m/d/Y H:i:s')." - Completed processing queries".$newline;
}

function getUserList($when, $andAfter=NULL, $orCreated=NULL) {
    global $page, $newline;
    
    $andSkipDays = "";
    if (isset($andAfter) && isset($orCreated)) {
        $andSkipDays = " AND (l.expireson > ".$andAfter." OR l.createdate > ".$orCreated.")";
    }
    
    $sql = "SELECT l.userid, u.username
        FROM listings l
        JOIN users u ON u.userid=l.userid
        WHERE l.status = 'OPEN' 
        AND l.type = 'Wanted' 
        AND l.expireson IS NOT NULL 
        AND l.expireson < ".$when.$andSkipDays;
    echo $newline.$newline."Expire Users SQL:".$newline.$sql.$newline;
    $users = $page->db->sql_query($sql);
    
    return $users;
}

function getListings($when, $who, $andAfter=NULL, $orCreated=NULL) {
    global $page, $newline;
    
    $listings = null;
    
    $andSkipDays = "";
    if (isset($andAfter) && isset($orCreated)) {
        $andSkipDays = " AND (l.expireson > ".$andAfter." OR l.createdate > ".$orCreated.")";
    }
    
    $sql = "SELECT l.*, u.username, u.userid
            , c.categorydescription, bt.boxtypename, s.subcategoryname
        FROM listings l
        JOIN users u ON u.userid=l.userid
        JOIN userinfo ui ON ui.userid=u.userid
        JOIN categories c ON c.categoryid=l.categoryid
        JOIN boxtypes bt ON bt.boxtypeid=l.boxtypeid
        JOIN subcategories s ON s.subcategoryid=l.subcategoryid
        WHERE l.status = 'OPEN' 
        AND l.type = 'Wanted' 
        AND l.userid=".$who."
        AND l.expireson IS NOT NULL
        AND l.expireson < ".$when.$andSkipDays;
    echo $newline.$newline."SQL:".$newline.$sql.$newline;
    $listings = $page->db->sql_query($sql);
    
    return $listings;
}
?>
