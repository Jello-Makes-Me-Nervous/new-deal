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


echo $newline.date('m/d/Y H:i:s')." - BEGIN Mark unread system messages as read batch";
$sql =  "
    UPDATE messaging
       SET status       = '".READSTATUS."',
           modifiedby   = '".SYSTEMNAME."',
           modifydate   = nowtoint()
     WHERE status       = '".UNREADSTATUS."'
       AND fromid       = ".SYSTEMUSERID."
       AND inttodatetime(createdate)::TIMESTAMP + interval '7' day < now()
";

    try {
        $DB->sql_execute_params($sql);
    } catch (Exception $e) {
        $msg = "ERROR: ".$e->getMessage()." [Unable to update system messages as read]";
        echo $newline.$msg;
    } finally {
    }

echo $newline.date('m/d/Y H:i:s')." - FINISHED Mark unread system messages as read batch".$newline;

echo $newline.date('m/d/Y H:i:s')." - BEGIN Mark unread staff messages as read batch";
$sql =  "
    UPDATE messaging        msg
       SET status       = '".READSTATUS."',
           modifiedby   = '".SYSTEMNAME."',
           modifydate   = nowtoint()
      FROM assignedrights   ar
     WHERE msg.status           = '".UNREADSTATUS."'
       AND ar.userid            = msg.fromid
       AND ar.userrightid       = ".USERRIGHT_STAFF."
       AND msg.replyrequired    = 0
       AND inttodatetime(msg.createdate)::TIMESTAMP + interval '30' day < now()
";

    try {
        $DB->sql_execute_params($sql);
    } catch (Exception $e) {
        $msg = "ERROR: ".$e->getMessage()." [Unable to update staff messages as read]";
        echo $newline.$msg;
    } finally {
    }

echo $newline.date('m/d/Y H:i:s')." - FINISHED Mark unread staff messages as read batch".$newline;

?>