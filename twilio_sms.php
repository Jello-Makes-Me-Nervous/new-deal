<?php

require __DIR__ . '/vendor/autoload.php';
use Twilio\Rest\Client;

/***
 * Twilio Response status codes
 *   200 - No error
 *   400 - Bad request
 ***/


function sendSMS($toId, $tonumber, $message, $verbose = 0) {
    global $CFG;

    $msgSID = null;

    if (isset($CFG->twilioenabled) && $CFG->twilioenabled) {

        $account_sid    = $CFG->account_sid;
        $auth_token     = $CFG->auth_token;
        $twilio_number  = (isset($CFG->twilio_number)) ? $CFG->twilio_number : "+18336923479";

        if (isset($CFG->divertsmsto) && !empty($CFG->divertsmsto)) {
            $message    = "[DIVERTED - ".$tonumber."]\n".$message;
            $tonumber   = $CFG->divertsmsto;
        }
        echo ($verbose) ? "<br>SMS: ".$tonumber." - ".$message : "";

        $client = new Client($account_sid, $auth_token);
        $msg = array("from"=>$twilio_number, "body"=>$message);
        $msgSID = $client->messages->create($tonumber, $msg);
        echo ($verbose) ? "<br>msgSID: ".$msgSID : "";
    }

    return $msgSID;
}

?>
