<?php
require 'vendor/autoload.php'; // If you're using Composer (recommended)
// Comment out the above line if not using Composer
// require("<PATH TO>/sendgrid-php.php");
// If not using Composer, uncomment the above line and
// download sendgrid-php.zip from the latest release here,
// replacing <PATH TO> with the path to the sendgrid-php.php file,
// which is included in the download:
// https://github.com/sendgrid/sendgrid-php/releases

DEFINE ("TEXT", "text/plain");
DEFINE ("HTML", "text/html");

$email = new \SendGrid\Mail\Mail();
$email->setFrom("donotreply@dealernetx.com", "DealernetX");
$email->setSubject("Sending with SendGrid is Fun");
$email->addTo("jeffstone@jeffstoneassoc.com", "El Jeffe");
$email->addContent(TEXT, "Now is the time for all good men to come to the aid of their country");
$email->addContent(HTML, "<strong>and easy to do anywhere, even with PHP</strong>");
$sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
try {
    $response = $sendgrid->send($email);
    $retval = $response->statusCode;
        echo "<pre>\n";
        print_r ($response);
        echo "</pre>\n";
//    print $response->statusCode() . "\n";
//    print_r($response->headers());
//    print $response->body() . "\n";
} catch (Exception $e) {
    echo 'Caught exception: '. $e->getMessage() ."\n";
}

    return $retval;
?>
