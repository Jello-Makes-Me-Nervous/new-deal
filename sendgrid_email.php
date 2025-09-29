<?php
require 'vendor/autoload.php';
// If you're using Composer (recommended)
// Comment out the above line if not using Composer
// require("<PATH TO>/sendgrid-php.php");
// If not using Composer, uncomment the above line and
// download sendgrid-php.zip from the latest release here,
// replacing <PATH TO> with the path to the sendgrid-php.php file,
// which is included in the download:
// https://github.com/sendgrid/sendgrid-php/releases


/***
 * Sendgrid Response status codes
 *   200 - No error
 *   201 - Successfully created
 *   202 - Accepted
 *   204 - Successfully deleted
 *   400 - Bad request
 *   401 - Requires authentication
 *   403 - From address doesn't match Verified Sender Identity.
 *         To learn how to resolve this error, see our Sender Identity requirements.
 *   403 - You are temporarily blocked from sending emails due to repeated bad requests.
 *   406 - Missing Accept header. For example: Accept: application/json
 *   429 - Too many requests/Rate limit exceeded
 *   500 - Internal server error
 ***/

DEFINE ("TEXT",     "text/plain");
DEFINE ("HTML",     "text/html");

function sendEmail($to, $subj, $body) {
    global $CFG;

    $retval = null;
    if (isset($CFG->sendgridenabled) && $CFG->sendgridenabled) {
        $htmlfooter             = "<p style='font-weight:bold;color:#CC0000;padding-top:10px;'>This mailbox is not monitored. Please email admin within DealernetX.</p>";
        $textfooter             = "\n\n".strip_tags($htmlfooter);

        $sendgrid_api_key_name  = (isset($CFG->sendgrid_api_key_name)) ? $CFG->sendgrid_api_key_name : "SENDGRID_API_KEY";
        $fromEmail              = (isset($CFG->fromemailaddress)) ? $CFG->fromemailaddress : "donotreply@dealernetx.com";
        $subject                = (empty($subj)) ? "A message from DealernetX" : trim($subj);

        if (isset($CFG->divertemailto) && !empty($CFG->divertemailto)) {
            $htmlDivertText = "<p>
/**************************************************<br>
 * This email is being diverted from ".$to."<br>
 **************************************************/</p>
            ";
            $textDivertText = trim(strip_tags($htmlDivertText));

            $textcontent = $textDivertText."\n\n".trim(strip_tags($body)).$textfooter;

            if ($body == strip_tags($body)) {
                $htmlcontent = $htmlDivertText.str_replace("/n", "<br>", $body).$htmlfooter;
            } else {
                $htmlcontent = $htmlDivertText.$body.$htmlfooter;
            }

            $to = $CFG->divertemailto;
        } else {
            $textcontent = trim(strip_tags($body));
            if ($body == strip_tags($body)) {
                $htmlcontent = str_replace("/n", "<br>", trim($body)).$htmlfooter;
            } else {
                $htmlcontent = trim($body).$htmlfooter;
            }
        }

        $x = str_replace(",",";",$to);
        $toArray = explode(";", $x);
        foreach ($toArray as $to) {
            $email = new \SendGrid\Mail\Mail();
            $email->setFrom($fromEmail, "DealernetX");
            $email->setSubject($subject);
            $email->addTo($to);
            $email->addContent(TEXT, $textcontent);
            $email->addContent(HTML, $htmlcontent);
            $sendgrid = new \SendGrid(getenv($sendgrid_api_key_name));
            try {
                $response = $sendgrid->send($email);
                $retval = $response->statusCode();
            } catch (Exception $e) {
                echo 'Caught exception: '. $e->getMessage() ."\n";
                $retval = $e->getCode();
            } finally {
                unset ($email);
            }
        }
    }

    return $retval;
}

?>
