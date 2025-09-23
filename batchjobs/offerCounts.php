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
require_once($batchdir.'../metric.class.php');
require_once($batchdir.'../template.class.php');

$page = new template(NOLOGIN, NOSHOWMSG, REDIRECTSAFE);

echo $newline.date('m/d/Y H:i:s')." - BEGIN Offer Count batch".$newline;

$metrics = new DealerMetrics();
$metrics->reloadMetrics();
echo $newline.date('m/d/Y H:i:s')." - Finished Offer Counts batch".$newline;
?>