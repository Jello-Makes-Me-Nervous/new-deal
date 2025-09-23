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

DEFINE("FEESDEALERNAME",                "FEES");

/**
 * Positional Arguments
 * 1st - day of month
 **/
if (isset($argv[1]) && !empty($argv[1])) {
   $day = $argv[1];
} elseif (!CLI_SCRIPT) {
    $day = optional_param("day", 0, PARAM_INT);
    if (empty($day)) {
       $day = date("j");
    }
} else {
    $day = date("j");
}

echo $newline."Day of Month: ".$day;
echo $newline.date('m/d/Y H:i:s')." - BEGIN Monthly EFT Membership Fee batch";
if (!empty($day)) {
    $sql =  "
        SELECT u.userid, u.username, ui.membershipfee, EXTRACT(DAY FROM inttodate(ui.accountcreated)::TIMESTAMP)
          FROM users            u
          JOIN userinfo         ui  ON  ui.userid           = u.userid
                                    AND ui.membershipfee    > 0
          JOIN assignedrights   ar  ON  ar.userid           = u.userid
                                    AND ar.userrightid      = 1               -- enabled
          JOIN assignedrights   mfr ON  mfr.userid          = u.userid
                                    AND mfr.userrightid     = 62              -- eft membership fee
         WHERE EXTRACT(DAY FROM inttodate(ui.accountcreated)::TIMESTAMP) = ".$day."
        ORDER BY u.username
    ";
}
$rs = $DB->sql_query_params($sql);
if (count($rs) > 0) {
    echo $newline."Found ".count($rs)." members";

    // We need a page object to handle the page messaging associated with
    // the internal messaging.
    $page = new template(NOLOGIN, NOSHOWMSG);

    $sql = "
        INSERT INTO transactions( crossrefid,  useraccountid,  refaccountid,  transtype,  transstatus,  dgrossamount,  accountname,  transdesc, offerid,  createdby, modifiedby)
                          VALUES(:crossrefid, :useraccountid, :refaccountid, :transtype, :transstatus, :dgrossamount, :accountname, :transdesc, :offerid, :createdby, :modifiedby)
    ";
    $feesId = $UTILITY->getDealerId(FEESDEALERNAME);
    foreach ($rs as $m) {
        echo $newline.date('m/d/Y H:i:s')." - ".$m["username"]." - ".$m["membershipfee"];

        $crossRefId = $UTILITY->nextval("transactions_crossrefid_seq");

        $params = array();
        $params['crossrefid']       = $crossRefId;
        $params['useraccountid']    = $m["userid"];
        $params['refaccountid']     = $feesId;
        $params['transtype']        = EFT_TRAN_TYPE_MEMBERSHIP_FEE;
        $params['transstatus']      = "ACCEPTED";
        $params['dgrossamount']     = $m["membershipfee"]*-1;
        $params['accountname']      = FEESDEALERNAME;
        $params['transdesc']        = "Membership Fees for the ".$UTILITY->ordinalSuffix($day);
        $params['offerid']          = NULL;
        $params['createdby']        = "Membership Fee Batch";
        $params['modifiedby']       = "Membership Fee Batch";

        $params2 = array();
        $params2['crossrefid']       = $crossRefId;
        $params2['useraccountid']    = $feesId;
        $params2['refaccountid']     = $m["userid"];
        $params2['transtype']        = EFT_TRAN_TYPE_MEMBERSHIP_FEE;
        $params2['transstatus']      = "ACCEPTED";
        $params2['dgrossamount']     = $m["membershipfee"];
        $params2['accountname']      = $m["username"];
        $params2['transdesc']        = "Membership Fees for the ".$UTILITY->ordinalSuffix($day);
        $params2['offerid']          = NULL;
        $params2['createdby']        = "Membership Fee Batch";
        $params2['modifiedby']       = "Membership Fee Batch";

        try {
            $DB->sql_begin_trans();
            if ($DB->sql_execute_params($sql, $params)) {
                if ($DB->sql_execute_params($sql, $params2)) {
                    $subject = "Membership Fees";
                    $msg = "Your membership fees were processed for the ".$UTILITY->ordinalSuffix($day);
                    $page->iMessage->insertSystemMessage($page, $m["userid"], $m["username"], $subject, $msg, EMAIL);
                } else {
                    $DB->sql_rollback_trans();
                    $subject = "Error: Membership Fees";
                    $msg = "Error processing membership fees for ".$m["username"];
                    $page->iMessage->insertSystemMessage($page, ADMINUSERID, ADMINUSERNAME, $subject, $msg, EMAIL);
                }
            } else {
                $DB->sql_rollback_trans();
                $subject = "Error: Membership Fees";
                $msg = "Error processing membership fees for ".$m["username"];
                $page->iMessage->insertSystemMessage($page, ADMINUSERID, ADMINUSERNAME, $subject, $msg, EMAIL);
            }
            $DB->sql_commit_trans();
        } catch (Exception $e) {
            $DB->sql_rollback_trans();
            $msg = "ERROR: ".$e->getMessage()." [Unable to process eft membership fees for ".$m["username"].".]";
            echo $newline.$msg;
            $subject = "Error: Membership Fees";
            $msg = "Error processing membership fees for ".$m["username"];
            $page->iMessage->insertSystemMessage($page, ADMINUSERID, ADMINUSERNAME, $subject, $msg, EMAIL);
        } finally {
            unset($params);
            unset($params2);
        }
    }
    unset($page);
}
echo $newline.date('m/d/Y H:i:s')." - Finished";

?>