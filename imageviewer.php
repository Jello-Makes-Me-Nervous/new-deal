<?php
require_once('setup.php');

$image = optional_param('img', "", PARAM_RAW);

$completefilelocation = $CFG->dataroot.$image;

if (! file_exists($completefilelocation)) {
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