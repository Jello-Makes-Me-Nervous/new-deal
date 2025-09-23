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

echo date('m/d/Y H:i:s')." - BEGIN external notification batch (daily)".$newline;

$timeblocks = array();
$timeblocks[5]  = array(0=>0, 5=>5, 10=>10, 15=>15, 20=>20, 25=>25, 30=>30, 35=>35, 40=>40, 45=>45, 50=>50, 55=>55);
$timeblocks[15] = array(0=>0, 15=>15, 30=>30, 45=>45);
$timeblocks[30] = array(0=>0, 30=>30);
$timeblocks[60] = array(0=>0);
$timeblocks[1440] = array(0=>0);

$subject  = "DealernetX Notification";
$message  = "Please log into your account regarding recent activity that requires your attention.\n";
$message .= " https://www.dealernetx.com\n";

$min = intval(date("i"));
$minminus1 = $min - 1; // handle case where cronjob starts 1 min late
echo "Min:".$min.$newline;

try {
    $notifications = getExternalNotifications();
    if (!empty($notifications)) {
        foreach($notifications as $n) {
            echo date('m/d/Y H:i:s')." ".$n["notification_type"].": ".$n["user"]." - ".$n["emailphone"].$newline;
            if ($n["notification_type"] == EMAILNOTIFICATIONTYPE) {
                $page->iMessage->sendExternalEmail($n["userid"], $subject, $message);
            } elseif ($n["notification_type"] == SMSNOTIFICATIONTYPE) {
                $page->iMessage->sendExternalSMS($n["userid"], $message);
            }
        }
    }

} catch (Exception $e) {
    echo "ERROR: ".$e->getMessage()." [Unable to setup temp tables]".$newline;
} finally {
    unset($params);
}

echo date('m/d/Y H:i:s')." - Finished".$newline;

function getExternalNotifications() {
    global $page;

    $sql = "DROP TABLE IF EXISTS extnotificationids_tmp";
    $rs = $page->db->sql_execute($sql);

    $sql = "
        CREATE TEMPORARY TABLE extnotificationids_tmp AS
        SELECT m.toid as id
          FROM messaging    m
          JOIN users        u   ON  u.userid    = m.toid
          JOIN userinfo     ui  ON  ui.userid   = u.userid
                                AND ui.userclassid IN (2,3,5)
         WHERE m.createdate BETWEEN u.lastlogin AND nowtoint()
        UNION
        SELECT o.offerto as id
          FROM offers       o
          JOIN users        u   ON  u.userid    = o.offerto
          JOIN userinfo     ui  ON  ui.userid   = u.userid
                                AND ui.userclassid IN (2,3,5)
         WHERE o.modifydate BETWEEN u.lastlogin AND nowtoint()
           AND o.offerstatus in ('PENDING', 'CANCELLED', 'VOID')
        UNION
        SELECT o.offerfrom as id
          FROM offers       o
          JOIN users        u   ON  u.userid    = o.offerfrom
          JOIN userinfo     ui  ON  ui.userid   = u.userid
                                AND ui.userclassid IN (2,3,5)
         WHERE o.modifydate BETWEEN u.lastlogin AND nowtoint()
           AND o.offerstatus in ('EXPIRED', 'DECLINED', 'ACCEPTED', 'VOID')
        UNION
        SELECT t.useraccountid as id
          FROM transactions t
          JOIN users        u   ON  u.userid    = t.useraccountid
          JOIN userinfo     ui  ON  ui.userid   = u.userid
                                AND ui.userclassid IN (2,3,5)
         WHERE t.createdate BETWEEN u.lastlogin AND nowtoint()
          AND t.transtype = 'RECEIPT'
        ORDER BY 1
    ";
    $rs = $page->db->sql_execute($sql);

    $sql = "
        SELECT np.userid, np.notification_type, np.emailphone, u.username,
               np.userid || ' - ' || u.username as user
          FROM notification_preferences     np
          JOIN users                        u   ON  u.userid    = np.userid
          JOIN extnotificationids_tmp       tmp ON  tmp.id      = u.userid
         WHERE np.frequency    = 1440
           AND np.isactive     = 1
           AND np.validated_on IS NOT NULL
        ORDER BY np.notification_type, np.userid
    ";

//    echo "<pre>".$sql."</pre>";
    $rs = $page->db->sql_query($sql);

    return $rs;
}
?>
