<?php
require_once('templateAdmin.class.php');
DEFINE("FILEEXT",   ".inc");

$page = new templateAdmin(LOGIN, SHOWMSG);
$page->requireJS("https://cdn.tiny.cloud/1/5xrplszm20gv2hy8zmwsr77ujzs70m70owm5d8o6bf2tcg64/tinymce/5/tinymce.min.js");

if (isset($CFG->adminmulti)) {
    $directory  = $CFG->adminmulti;
} else {
    $directory  = $CFG->dataroot."adminmulti/";
}

$pagename       = optional_param('pagename', NULL, PARAM_TEXT);
$findbtn        = optional_param('findbtn', NULL, PARAM_TEXT);
$pagecontent    = optional_param('pagecontent', NULL, PARAM_RAW);
$savebtn        = optional_param('savebtn', NULL, PARAM_TEXT);

if (!empty($findbtn) & !empty($pagename)) {
    $filename = $directory.$pagename.FILEEXT;
    if (file_exists($filename)) {
        try {
            $fp = fopen($filename,'r');
            $pagecontent = fread($fp, filesize($filename));
            fclose($fp);
            $page->messages->addInfoMsg($pagename." found.");
        } catch (Exception $e) {
            $page->messages->addErrorMsg("Error: ".$e->getMessage()." [Reading file]");
            $pagecontent = null;
        } finally {
        }
    } else {
        $page->messages->addInfoMsg($pagename." not found.");
        $pagecontent = null;
    }
} elseif (!empty($savebtn) && !empty($pagecontent)) {
    $filename = $directory.$pagename.FILEEXT;
    try {
        $fp = fopen($filename, 'w');
        fwrite($fp, $pagecontent);
        fclose($fp);
        $page->messages->addSuccessMsg($pagename." created / updated.");
    } catch (Exception $e) {
        $page->messages->addErrorMsg("Error: ".$e->getMessage()." [Writing file]");
    } finally {
    }
}
$js = "
      var pageContent = document.admin.pagecontent.value;
      tinymce.get('mypagecontent').setContent(pageContent);
";
//$page->jsInit($js);

echo $page->header('Admin Pages');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $pagename, $pagecontent;

    $pagecontent = str_replace("'", "&#039;", $pagecontent);
    echo "<form class='filters' name ='admin' id ='admin' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "  <input type='hidden' name='pagecontent' id='pagecontent' value='".$pagecontent."'>\n";
    echo "  <span class='label'>Page: </span>\n";
    echo "  <input type='textbox' class='value' name='pagename' id='pagename' value='".$pagename."'>\n";
    echo "  <input type='submit' name='findbtn' value='Find'>\n";
    echo "  <div>&nbsp;</div>\n";
    if (!empty($pagename)) {
        echo "  <textarea name='mypagecontent' id='mypagecontent'></textarea>\n";
        echo "  <div>&nbsp;</div>\n";
        $onclick = "JavaScript: document.admin.pagecontent.value = tinymce.get(\"mypagecontent\").getContent();";
        echo "  <input type='submit' name='savebtn' value='Save' onclick='".$onclick."'>\n";
    }
    echo "  <a href='/admin.php'>CANCEL</a>\n";
    echo "</form>\n";

    echo "<script>\n";
    echo "  var pageContent = document.admin.pagecontent.value;\n";
    echo "  tinymce.init({\n";
    echo "    selector: '#mypagecontent',\n";
//    echo "    plugins: 'advlist autolink lists link image charmap print preview hr anchor pagebreak autoresize table',\n";
//    echo "    toolbar_mode: 'floating'\n";
    echo "    plugins: 'print preview paste searchreplace autolink autoresize save directionality code visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor toc insertdatetime advlist lists wordcount textpattern noneditable help charmap quickbars emoticons',\n";
    echo "    menubar: 'file edit view insert format tools table help',\n";
    echo "    toolbar: 'undo redo | bold italic underline strikethrough | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview print | insertfile image link anchor codesample',\n";
    echo "    toolbar_sticky: true,\n";
    echo "    cleanup_on_startup : true,\n";
    echo "    cleanup : true,\n";
    echo "    verify_html : false,\n";
    echo "    inline_styles : false,\n";
    echo "    setup: function (editor) {\n";
    echo "      editor.on('init', function (e) {\n";
    echo "        editor.setContent(pageContent);\n";
    echo "      });\n";
    echo "    }\n";
    echo "  });\n";
    echo "</script>\n";

}

?>