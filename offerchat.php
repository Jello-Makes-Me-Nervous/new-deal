<?php
require_once('templateCommon.class.php');

$page = new templateCommon(NOLOGIN, SHOWMSG);
$page->requireJS("/scripts/formValidation.js");
$page->requireStyle("/styles/chatstyles.css' type='text/css' media='all'");

$toid           = optional_param('toid', NULL, PARAM_INT);
$parentid       = optional_param('parentid', NULL, PARAM_INT);
$threadid       = optional_param('threadid', NULL, PARAM_INT);
$offerid        = optional_param('offerid', 192, PARAM_INT);
$subject        = optional_param('subject', NULL, PARAM_RAW);
$messagebody    = optional_param('message', NULL, PARAM_RAW);

if(!empty($messagebody)) {
    $subject        = trim($subject);
    $messagebody    = trim($messagebody);
    $to             = $page->utility->getUserName($toid);
    if (!empty($toid) && !empty($to) && !empty($subject) && !empty($messagebody)) {
        $page->iMessage->insertMessage($page, $toid, $to, $subject, $messagebody, OFFERCHAT, $threadid, $parentid, $offerid);
    }
}
$js = "
    var objDiv = document.getElementById('offerchatdiv');
    objDiv.scrollTop = objDiv.scrollHeight;
";
$page->jsInit($js);

echo $page->header('offer chat');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $offerid;

    $offerinfo = $page->iMessage->getOfferinfo($offerid);
    echo "<FORM  NAME='offerchat' ID='offerchat' ACTION='offerchat.php'  OnSubmit='return VerifyFields(this)' METHOD='POST'>\n";
    echo "  <div id='offerchatdiv' class='commentArea' style='border:1px solid #EEE;width:450px; height:400px;overflow: auto;'>\n";
    if ($offerinfo["offerfrom"] == $page->user->userId) {
        echo "    <div style='float:left;'>".$offerinfo["to_username"]."</div>\n";
        echo "    <div style='float:right;'>".$page->user->username."</div>\n";
    } else {
        echo "    <div style='float:left;'>".$offerinfo["from_username"]."</div>\n";
        echo "    <div style='float:right;'>".$page->user->username."</div>\n";
    }
    if ($messages = $page->iMessage->getOfferThread($offerid)) {
        $prevchatdate = 0;
        foreach($messages as $m) {
            if (date("m/d/Y H", $prevchatdate) <> date("m/d/Y H", $m["createdate"])) {
                $prevchatdate = $m["createdate"];
                if (strtotime("today") < $m["createdate"] &&
                    strtotime("now") > $m["createdate"]) {
                    echo "<div class='chatDate'>Today, ".date("h:iA", $m["createdate"])."</div>";
                } else {
                    echo "<div class='chatDate'>".date("l, F j, Y", $m["createdate"])."</div>";
                }
            }
            if ($m["fromid"] == $page->user->userId) {
                echo "    <div class='bubbledMe'>\n";
                echo "      ".stripslashes(str_replace("\n","<br>",str_replace("\c\n","<br>",$m['messagetext'])))."\n";
                echo "    </div>\n";
            } else {
                echo "    <div class='bubbledNotMe'>\n";
                echo "      ".stripslashes(str_replace("\n","<br>",str_replace("\c\n","<br>",$m['messagetext'])))."\n";
                echo "    </div>\n";
            }
            $lastmessage = $m;
        }
    }
    echo "  </div>\n";
    echo "  <textarea style='width:425px;height:45px;margin-top:5px;' name='message' id='message'></textarea>\n";
    echo "  <input type='hidden' name='offerid' value='".$offerinfo["offerid"]."'>\n";
    $toid = ($offerinfo["offerto"] == $page->user->userId) ? $offerinfo["offerfrom"] : $offerinfo["offerto"];
    echo "  <input type='hidden' name='toid' value='".$toid."'>\n";
    $subject = (isset($lastmessage["subjecttext"]) && !empty($lastmessage["subjecttext"])) ? $lastmessage["subjecttext"] : $offerinfo["transactiontype"]." by ".$offerinfo["username"];
    echo "  <input type='hidden' name='subject' value='".$subject."'>\n";
    $parentid = (isset($lastmessage["messageid"]) && !empty($lastmessage["messageid"])) ? $lastmessage["messageid"] : 0;
    echo "  <input type='hidden' name='parentid' value='".$parentid."'>\n";
    $threadid = (isset($lastmessage["threadid"]) && !empty($lastmessage["threadid"])) ? $lastmessage["threadid"] : 0;
    echo "  <input type='hidden' name='threadid' value='".$threadid."'>\n";
    echo "  <a href=javascript:void(0);' style='font-weight:bold;' name='submitbtn' onclick='Javascript:if (VerifyFields(document.offerchat)) { document.offerchat.submit();} else { return false;} '>SEND</a>\n";
    echo "</FORM>\n";

    echo "<SCRIPT LANGUAGE='JavaScript' TYPE='text/javascript'>\n";
    echo "<!--\n";
    echo "\n";
    echo "  function VerifyFields(f) {\n";
    echo "    var a = [\n";
    echo "              [/^message$/,   'Message',      'text',    true,   500],\n";
    echo "            ];\n";
    echo "\n";
    echo "    m = '';\n";
    echo "    for (i = 0; i < f.elements.length; i++) {\n";
    echo "        for (j = 0; j < a.length; j++) {\n";
    echo "           if (f.elements[i].name.match(a[j][0])) {\n";
    echo "               m = m + VerifyTextField(f.elements[i], a[j][1], a[j][2], a[j][3], a[j][4]);\n";
    echo "               break;\n";
    echo "           }\n";
    echo "        }\n";
    echo "    }\n";
    echo "\n";
    echo "    if (m != '') {\n";
    echo "        alert('The following fields contain values that are not permitted or are missing values:\\n\\n' + m);\n";
    echo "        return false;\n";
    echo "    } else {\n";
    echo "        return true;\n";
    echo "    }\n";
    echo "  }\n";
    echo "\n";
    echo "//-->\n";
    echo "\n";
    echo "</SCRIPT>\n";
}
?>