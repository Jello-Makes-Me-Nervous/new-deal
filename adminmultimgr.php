<?php
require_once('templateAdmin.class.php');
DEFINE("FILEEXT",   "inc");

$page = new templateAdmin(LOGIN, SHOWMSG);

if (isset($CFG->adminmulti)) {
    $directory  = $CFG->adminmulti;
} else {
    $directory  = $CFG->dataroot."adminmulti/";
}

$pagefilter     = optional_param('pagefilter', "admin", PARAM_TEXT);
$findbtn        = optional_param('findbtn', NULL, PARAM_TEXT);

$pagename       = optional_param('pagename', NULL, PARAM_TEXT);

if (!empty($pagename)) {
    $filename = $directory.$pagename.".".FILEEXT;
    try {
        unlink($filename);
        $page->messages->addSuccessMsg($pagename." deleted.");
        $findbtn = "x";
    } catch (Exception $e) {
        $page->messages->addErrorMsg("Error: ".$e->getMessage()." [deleting file]");
    } finally {
    }
}
$filelist = array();
if (!empty($findbtn) & !empty($pagefilter)) {
    $filelist = getFileList($directory);
}

echo $page->header('Admin Pages');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $pagefilter, $filelist;

    echo "<form class='filters' name ='admin' id ='admin' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "  <input type='hidden' name='pagename' id='pagename' value=''>\n";
    echo "  <span class='label'>Page Filter: </span>\n";
    echo "  <input type='textbox' class='value' name='pagefilter' id='pagefilter' value='".$pagefilter."'>\n";
    echo "  <input type='submit' name='findbtn' value='Find'>\n";
    echo "</form>\n";
    echo "<div>&nbsp;</div>\n";
    if ($filelist) {
        echo "<table>\n";
        echo "  <thead>\n";
        echo "    <tr>\n";
        echo "      <th colspan='2'>Name</th>\n";
        echo "      <th>Type</th>\n";
        echo "      <th>Last Modified</th>\n";
        echo "      <th></th>\n";
        echo "    </tr>\n";
        echo "  </thead>\n";
        echo "  <tbody>\n";
        $x = 0;
        foreach($filelist as $f) {
            if ($f["ext"] == FILEEXT && stripos($f["shortname"], $pagefilter, 0) !== FALSE) {
                $x++;
                echo "    <tr>\n";
                echo "      <td>".$x."</td>\n";
                $url = "adminmulti.php?id=".$f["shortname"];
                $link = "<a href='".$url."' target='_blank'>".$f["shortname"]."</a>";
                echo "      <td>".$link."</td>\n";
                echo "      <td>".$f["type"]."</td>\n";
                echo "      <td>".$f["lastmod"]."</td>\n";
                $deleteConfirm = "Are you sure you want to delete ... ".$f["shortname"]."?";
                $onclick = "JavaScript: if (confirm(\"".$deleteConfirm."\")) { document.admin.pagename.value = \"".$f["shortname"]."\"; document.admin.submit(); } else {}";
                $link = "<a href='javascript: void(0);' onclick='".$onclick."'>[delete]</a>";
                echo "      <td>".$link."</td>\n";
                echo "    </tr>\n";
            }
        }
        echo "  </tbody>\n";
        echo "</table>\n";
    }

}

function getFileList($dir) {
    // array to hold return value
    $retval = array();

    // open pointer to directory and read list of files
    $d = @dir($dir);
    while(false !== ($entry = $d->read())) {
        // skip hidden files
        if($entry[0] == ".") {
            continue;
        }
        $filename = $dir.$entry;
        $fnshort = substr($entry, 0, strlen($entry)-4);
        if(is_dir($filename)) {
        } elseif(is_readable($filename)) {
            $retval[] = array(
              "name" => $entry,
              "shortname" => $fnshort,
              "ext" => substr($entry, strlen($entry)-3, 3),
              "type" => mime_content_type($filename),
              "lastmod" => date('m/d/Y g:i A', filemtime($filename))
            );
        }
    }
    $d->close();

    return $retval;
}
?>