<?php
require_once('templateCommon.class.php');
DEFINE("FILEEXT",   ".inc");

$page = new templateCommon(NOLOGIN, SHOWMSG);

if (isset($CFG->adminmulti)) {
    $directory  = $CFG->adminmulti;
} else {
    $directory  = $CFG->dataroot."adminmulti/";
}

$id       = optional_param('id', NULL, PARAM_TEXT);

if (!empty($id)) {
    $filename = $directory.$id.FILEEXT;
    if (file_exists($filename)) {
        try {
            $fp = fopen($filename,'r');
            $content = fread($fp, filesize($filename));
            $pagecontent  = "<div style='padding:5px;'> <!-- admin page content -->\n";
            $pagecontent .= $content."\n";
            $pagecontent .= "</div> <!-- END admin page content -->\n";
            fclose($fp);
        } catch (Exception $e) {
            $page->messages->addErrorMsg("Error: ".$e->getMessage()." [Reading file]");
            $pagecontent = null;
        } finally {
        }
    } else {
        $page->messages->addInfoMsg($id." not found.");
        $pagecontent = null;
    }
}


echo $page->header('Admin Pages');
echo $pagecontent;
echo $page->footer(true);

?>