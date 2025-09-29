<?php
require_once('template.class.php');

if (!(isset($page) && is_object($page))) {
    $page = new template(NOLOGIN, SHOWMSG);
}
$direct = false;
require_once('productinfo_upc_app.php');
require_once('productinfo_ids_app.php');
require_once('productinfo_kw_app.php');

$id         = optional_param('id', 0, PARAM_INT);
$upc        = optional_param('upc', 0, PARAM_RAW);
$kw         = optional_param('kw', 0, PARAM_TEXT);
$jsonout    = optional_param('json', 0, PARAM_INT);
$verbose    = optional_param('verbose', 0, PARAM_INT);
$catid      = optional_param('cid', 0, PARAM_INT);
$subcatid   = optional_param('scid', 0, PARAM_INT);
$btid       = optional_param('btid', 0, PARAM_INT);
$year       = optional_param('yr', 0, PARAM_TEXT);

$oneday = (60*60*24);
$onehour = (60*60);
$joshbday = 1109980800;
$now  = strtotime("now");
if ($verbose) {
    echo "<br>Now: ".date("m/d/Y H:i:s", $now);
    echo "<br>Low: ".date("m/d/Y H:i:s", $id+$joshbday-$onehour);
    echo "<br>High: ".date("m/d/Y H:i:s", $id+$joshbday+$onehour);
}

$x = null;
if ($id+$joshbday-$onehour <= $now && $id+$joshbday+$onehour >= $now) {
    $factoryCostID = $page->utility->getDealerId(FACTORYCOSTNAME);
    if (!empty($upc) && strlen($upc) < 15) {
        $match = getProductInfoUPC($upc);
        if ($match) {
            $x = processUPCResults($match);
        }
    } elseif (!empty($catid) && !empty($subcatid) && !empty($btid)) {
        $match = getProductInfoIds($catid, $subcatid, $btid, $year);
        if ($verbose) {
            echo "<pre>";print_r($match);echo "</pre>";
        }
        if ($match) {
            $x = processIdsResults($match);
        }
    } elseif (!empty($kw)) {
        $matches = getProductInfoKeyword($kw);
        if ($verbose) {
            echo "<pre>";print_r($matches);echo "</pre>";
        }
        if ($matches) {
            $x = processKeywordResults($matches);
        }
        $x = json_encode($x);
    } else {
        $x["code"] = -2;
        $x["msg"] = "Unknown request.";
        if ($verbose) {
            echo "<pre>";print_r($error);echo "</pre>";
        }
        $x = json_encode($x);
    }
}

echo $x;
exit();

?>