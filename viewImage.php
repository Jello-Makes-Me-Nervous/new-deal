<?php
require_once('setup.php');

$image = optional_param('img', "", PARAM_RAW);

$ads_completefilelocation = $CFG->advert.$image;
$listings_completefilelocation = $CFG->listings.$image;
$admin_completefilelocation = $CFG->adminmulti.$image;
$attachment_completefilelocation = $CFG->attachments.$image;

if (file_exists($ads_completefilelocation)) {
    $completefilelocation = $ads_completefilelocation;
} elseif (file_exists($listings_completefilelocation)) {
    $completefilelocation = $listings_completefilelocation;
} elseif (file_exists($admin_completefilelocation)) {
    $completefilelocation = $admin_completefilelocation;
} elseif (file_exists($attachment_completefilelocation)) {
    $completefilelocation = $attachment_completefilelocation;
} else {
   $completefilelocation = __DIR__."/images/imageNotFound.png";
}

$path_parts = pathinfo($completefilelocation);
$mimetype = "";
SWITCH ($path_parts['extension']) {
    CASE 'gif':
    CASE 'jpg':
    CASE 'jpeg':
    CASE 'png': $mimetype = "image/".$path_parts['extension'];
                break;
    CASE 'pdf': $mimetype = "application/pdf";
                break;
    CASE 'doc': $mimetype = "application/msword";
                break;
    CASE 'docx': $mimetype = "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
                break;
    CASE 'txt': $mimetype = "text/plain";
                break;
}

header("Content-type: ".$mimetype);
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: '.filesize($completefilelocation));
ob_clean();
flush();
readfile($completefilelocation);
exit;

?>