<?php
require_once('templateAdmin.class.php');

$page = new templateAdmin(LOGIN, SHOWMSG);

if (isset($CFG->adminmultiPath)) {
    $directory  = $CFG->adminmultiPath;
} else {
    $directory  = "adminmulti/";
}

$savebtn        = optional_param('savebtn', NULL, PARAM_TEXT);
$newfilename    = optional_param('newfilename', NULL, PARAM_TEXT);

$filename = "";
$img = "";
if (!empty($savebtn) && !empty($newfilename)) {
    try {
        $imageUp = $_FILES["imageUp"];
        $img = prefixImgUp($imageUp, $newfilename, $directory, $page);
        $filename = $directory.$img;

    } catch (Exception $e) {
        $page->messages->addErrorMsg("Error: ".$e->getMessage()." [Uploading Image]");
    } finally {
    }
}

echo $page->header('Admin Pages');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $CFG, $page, $filename, $img;

    if ($filename && file_exists(prefixFullPath($filename))) {
        echo "<p>You can reference this file using the below HTML:<br>\n";
        echo "&nbsp;&nbsp;<b>".htmlentities("<img src='".$page->utility->getPrefixAdminMultiImageURL($img)."'>")."</b>\n";
        echo "</p>\n";
        echo "<p><img src='".$page->utility->getPrefixAdminMultiImageURL($img)."'></p>\n";
    }

    echo "<form name='admin' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post' enctype='multipart/form-data'>\n";
    echo "  <div style='padding: 3px;'>\n";
    echo "    <table>\n";
    echo "      <tbody>\n";
    echo "        <tr>\n";
    echo "          <td>Image:</td>\n";
    echo "          <td><input type='file' name='imageUp' id='imageUp'> (gif & jpg only - Max:".(round(($CFG->IMG_MAX_UPLOAD/1000000),2))."MB)</td>\n";
    echo "        </tr>\n";
    echo "        <tr>\n";
    echo "          <td>New image name (no extension):</td>\n";
    echo "          <td><input type='text' name='newfilename' value=''/></td>\n";
    echo "        </tr>\n";
    echo "        <tr>\n";
    echo "          <td colspan='2'><input type='submit' name='savebtn' value='Upload'>&nbsp;&nbsp;<a href='/admin.php'>CANCEL</a></td>\n";
    echo "        </tr>\n";
    echo "      </tbody>\n";
    echo "    </table>\n";
    echo "  </div>\n";
    echo "</form>\n";

}

?>