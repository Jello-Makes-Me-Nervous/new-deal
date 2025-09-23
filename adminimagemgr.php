<?php
require_once('templateAdmin.class.php');
DEFINE("PNGMIME",   "image/png");
DEFINE("JPGMIME",   "image/jpeg");
DEFINE("GIFMIME",   "image/gif");

$page = new templateAdmin(LOGIN, SHOWMSG);

if (isset($CFG->adminmulti)) {
    $directory  = $CFG->adminmulti;
} else {
    $directory  = $CFG->dataroot."adminmulti/";
}

$pagefilter     = optional_param('pagefilter', "", PARAM_TEXT);
$findbtn        = optional_param('findbtn', NULL, PARAM_TEXT);

$filename       = optional_param('filename', NULL, PARAM_TEXT);

if (!empty($filename)) {
    $fname = $directory.$filename;
    try {
        unlink($fname);
        $page->messages->addSuccessMsg($filename." deleted.");
        $findbtn = "x";
    } catch (Exception $e) {
        $page->messages->addErrorMsg("Error: ".$e->getMessage()." [deleting file]");
    } finally {
    }
}
$filelist = array();
if ((!empty($findbtn)) && (!empty($pagefilter))) {
    $filelist = getFileList($directory, $pagefilter);
}

echo $page->header('Admin Pages');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $pagefilter, $filelist;

    echo "<form class='filters' name ='admin' id ='admin' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "  <input type='hidden' name='filename' id='filename' value=''>\n";
    echo "  <span class='label'>Image Name Filter: </span>\n";
    echo "  <input type='textbox' class='value' name='pagefilter' id='pagefilter' value='".$pagefilter."'>\n";
    echo "  <input type='submit' name='findbtn' value='Find'>\n";
    echo "</form>\n";
    echo "<div>&nbsp;</div>\n";
    echo "<div style='float: right; margin: 0px 500px 10px 5px;'>\n";
    echo "  <a class='button' href='adminimageupload.php'>Upload New</a>\n";
    echo "</div>\n";
    if ($filelist) {
        echo "<table>\n";
        echo "  <thead>\n";
        echo "    <tr>\n";
        echo "      <th colspan='2'>Name</th>\n";
        echo "      <th>Type</th>\n";
        echo "      <th>Width x Height</th>\n";
        echo "      <th>Uploaded</th>\n";
        echo "      <th>HTML IMG Tag</th>\n";
        echo "      <th></th>\n";
        echo "    </tr>\n";
        echo "  </thead>\n";
        echo "  <tbody>\n";
        $x = 0;
        foreach($filelist as $f) {
            $x++;
            echo "    <tr>\n";
            echo "      <td>".$x."</td>\n";
            $link = "<a href='".$page->utility->getPrefixAdminMultiImageURL($f["name"])."' target='_blank'>".$f["name"]."</a>";
            echo "      <td>".$link."</td>\n";
            echo "      <td>".$f["type"]."</td>\n";
            echo "      <td>".$f["size"][0]." x ".$f["size"][1]."</td>\n";
            echo "      <td>".$f["lastmod"]."</td>\n";
            echo "      <td>".htmlentities("<img src='".$page->utility->getPrefixAdminMultiImageURL($f["name"])."' height='x' width='y'>")."</td>\n";
            $deleteConfirm = "Are you sure you want to delete ... ".$f["name"]."?";
            $onclick = "JavaScript: if (confirm(\"".$deleteConfirm."\")) { document.admin.filename.value = \"".$f["name"]."\"; document.admin.submit(); } else {}";
            $link = "<a href='javascript: void(0);' onclick='".$onclick."'>[delete]</a>";
            echo "      <td>".$link."</td>\n";
            echo "    </tr>\n";
        }
        echo "  </tbody>\n";
        echo "</table>\n";
    }

}

function getFileList($dir, $pagefilter) {
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
        } elseif (is_readable($filename)) {
            $type = mime_content_type($filename);
            if ($type == PNGMIME || $type == JPGMIME || $type == GIFMIME) {
                $includeThisFile = true;
                if ($pagefilter) {
                    if (stripos($entry, $pagefilter, 0) !== FALSE) {
                        $includeThisFile = true;
                    } else {
                        $includeThisFile = false;
                    }
                }
                
                if ($includeThisFile) {
                    $retval[] = array(
                      "name" => $entry,
                      "shortname" => $fnshort,
                      "ext" => substr($entry, strlen($entry)-3, 3),
                      "type" => $type,
                      "size" => getimagesize($filename),
                      "lastmod" => date('m/d/Y g:i A', filemtime($filename))
                    );
                }
            }
        }
    }
    $d->close();

    return $retval;
}
?>