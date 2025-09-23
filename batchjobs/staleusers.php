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


echo $newline.date('m/d/Y H:i:s')." - BEGIN Set Stale Uusers batch";
$sql =  "
    SELECT u.userid, u.username, inttodatetime(u.lastlogin) as lastlogin, inttodatetime(u.lastlogin)::TIMESTAMP + interval '2' day as llplus2days
      FROM users                u
      JOIN userinfo             ui  ON  ui.userid       = u.userid
                                    AND ui.userclassid  = 3
      LEFT JOIN assignedrights  ar  ON  ar.userid       = u.userid
                                    AND ar.userrightid  = 61  -- stale users
      LEFT JOIN assignedrights  er  ON  er.userid       = u.userid
                                    AND er.userrightid  = 15  -- elite users
      LEFT JOIN assignedrights  br  ON  br.userid       = u.userid
                                    AND br.userrightid  = 64  -- blue star users
     WHERE ar.userid IS NULL AND u.userid <> 3101 -- factory cost
       AND ( 
           (inttodatetime(u.lastlogin)::TIMESTAMP + interval '3' day < now())
        OR
           ((er.userid IS NULL AND br.userid IS NULL) AND (inttodatetime(u.lastlogin)::TIMESTAMP + interval '2' day < now()))
       )
";
$rs = $DB->sql_query_params($sql);
if (is_array($rs) && (count($rs) > 0)) {
    echo $newline."Found ".count($rs)." stale members";

    $sql = "
        INSERT INTO assignedrights (userid, userrightid, createdby)
        SELECT u.userid, 61, 'Batch'
          FROM users                u
          JOIN userinfo             ui  ON  ui.userid       = u.userid
                                    AND ui.userclassid      = 3
          LEFT JOIN assignedrights  ar  ON  ar.userid       = u.userid
                                        AND ar.userrightid  = 61  -- stale users
         WHERE inttodatetime(u.lastlogin)::TIMESTAMP + interval '3' day < now()
           AND ar.userid IS NULL
           AND u.userid <> 3101 -- factory cost
    ";

    try {
        $DB->sql_execute_params($sql);
    } catch (Exception $e) {
        $msg = "ERROR: ".$e->getMessage()." [Unable to process stale users]";
        echo $newline.$msg;
    } finally {
    }

}
echo $newline.date('m/d/Y H:i:s')." - FINISHED Set Stale Users batch".$newline;

?>