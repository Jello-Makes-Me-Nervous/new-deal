<?php
require_once('templateCommon.class.php');
DEFINE("EMAILTYPE", "EMAIL");

$page = new templateCommon(LOGIN, SHOWMSG, REDIRECTSAFE);
$isStaff = $page->user->isStaff();
$displayEditor  = optional_param('displayeditor', 0, PARAM_INT);

if ($isStaff && $displayEditor) {
    $page->requireJS("https://cdn.tiny.cloud/1/5xrplszm20gv2hy8zmwsr77ujzs70m70owm5d8o6bf2tcg64/tinymce/5/tinymce.min.js");
}

$threadId       = optional_param('threadId', 0, PARAM_INT);
$messageId      = optional_param('messageId', 0, PARAM_INT);
$replyToId      = optional_param('replytoid', 0, PARAM_INT);
$department     = optional_param('dept', 0, PARAM_INT);
$to             = optional_param('to', NULL, PARAM_RAW);
$subject        = optional_param('subject', NULL, PARAM_RAW);
$messagebody    = optional_param('messagebody', NULL, PARAM_RAW);
$sendbtn        = optional_param('sendbtn', NULL, PARAM_RAW);
$replyrequired  = optional_param('replyrequired', 0, PARAM_INT);

$entereduserclassid = NULL;
$userclasses = array();
if ($isStaff) {
    $userclasses = $page->iMessage->getUserClasses();
}

$message = null;
$success = 0;
$failure = 0;
if (!empty($sendbtn)) {
    $proceed = true;
    $filename = "";
    if (isset($_FILES["attachment"]["name"]) && !empty($_FILES["attachment"]["name"])) {
        if (isset($_FILES["attachment"]["error"]) && !empty($_FILES["attachment"]["error"])) {
            $page->messages->addErrorMsg("ERROR: Unable to upload file. [".$_FILES["attachment"]["error"]."]");
            $proceed = false;
        }
        $filename = $_FILES["attachment"]["name"];
    }
    if ($proceed) {
        if ($messageId) {
            $message = $page->iMessage->getMessage($messageId);
            $to         = $message["fromtext"];
            $toid       = $message["fromid"];
            $threadId   = $message["threadid"];
            $parentId   = $message["messageid"];
            $offerId    = $message["offerid"];
            if ($page->iMessage->insertMessage($page, $toid, $to, $subject, $messagebody, EMAIL,
                                     $threadId, $parentId, $offerId, $replyrequired, NULL, $filename, $_FILES["attachment"])) {
                $success++;
            } else {
                $failure++;
            }
        } elseif (!$isStaff) {
            $toid   = $page->utility->getUserId($to);
            $threadId = 0;
            $parentId = 0;
            $offerId = NULL;
            if ($page->iMessage->insertMessage($page, $toid, $to, $subject, $messagebody, EMAIL,
                                     $threadId, $parentId, $offerId, $replyrequired, NULL, $filename, $_FILES["attachment"])) {
                $success++;
            } else {
                $failure++;
            }
        } else {
            $threadId = 0;
            $parentId = 0;
            $offerId = NULL;
            $toarray = explode(",", $to);
            foreach($toarray as $to) {
                $to = trim($to);
                $toid = null;
                $entereduserclassid = null;
                foreach($userclasses as $uc) {
                    if (strtolower($uc["userclassname"]) == strtolower($to)) {
                        $entereduserclassid = $uc["userclassid"];
                    }
                }
                $everyone = (strtolower($to) == 'everyone') ? true : false;
                if (empty($entereduserclassid) && !$everyone) {
                    $toid   = $page->utility->getUserId($to);
                    if (!empty($toid)) {
                        if ($page->iMessage->insertMessage($page, $toid, $to, $subject, $messagebody, EMAIL,
                                                 $threadId, $parentId, $offerId, $replyrequired, NULL, $filename, $_FILES["attachment"])) {
                            $success++;
                        } else {
                            $failure++;
                        }
                    }
                } else {
                    if ($everyone) {
                        $recipients = $page->iMessage->getEveryone();
                    } else {
                        $recipients = $page->iMessage->getUsersInClass($entereduserclassid);
                    }
                    set_time_limit(300);
                    if (!empty($recipients)) {
                        foreach($recipients as $r) {
                            $to     = $r["username"];
                            $toid   = $r["userid"];
                            if ($page->iMessage->insertMessage($page, $toid, $to, $subject, $messagebody, BULKMAIL,
                                                     $threadId, $parentId, $offerId, $replyrequired, NULL, $filename, $_FILES["attachment"])) {
                                $success++;
                            } else {
                                $failure++;
                            }
                        }
                    }
                }
            }
        }
    }
    if ($success) {
        if ($replyToId) {
            $page->iMessage->updateStatus($page, $replyToId, READSTATUS);
        }
        $page->messages->addSuccessMsg($success." message(s) sent.");
    }
    if ($failure) {
        $page->messages->addSuccessMsg($failure." message(s) failed to send.");
    }
}


echo $page->header('Send Message');
if ($success) {
    echo displaySuccess();
} else {
    echo mainContent();
}
echo $page->footer(true);

function displaySuccess() {
    echo "<div>\n";
    $url    = "/mymessages.php";
    $link   = "<a href='".$url."'>here</a>";
    echo "  Click ".$link." to return to your inbox.\n";
    echo "</div>\n";
}

function mainContent() {
    global $CFG, $page, $threadId, $replyToId, $isStaff, $userclasses, $department, $to, $subject, $messagebody, $displayEditor;

    echo "<form name='admin' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post' enctype='multipart/form-data'>\n";
    if (!empty($replyToId)) {
        echo "    <input type='hidden' name='replytoid' id='replytoid' value='".$replyToId."'>\n";
    }
    echo "<table>\n";
    if ($isStaff) {
        echo "  <caption>\n";
        if (!empty($threadId)) {
            echo "    <input type='hidden' name='threadId' id='threadId' value='".$threadId."'>\n";
        }
        $onclick = "onclick='document.admin.submit(0);'";
        $checked = (empty($displayEditor)) ? "" : "CHECKED";
        echo "    <input type='checkbox' name='displayeditor' id='displayeditor' value='1' ".$checked." ".$onclick."> Use editor\n";
        echo "  </caption>\n";
    }
    echo "  <thead>\n";
    echo "    <tr>\n";
    echo "      <th colspan='2'></th>\n";
    echo "    </tr>";
    echo "  </thead>\n";
    echo "  <tbody>\n";
    $mailThread = $page->iMessage->getMailThread($threadId, 0);
    if ($mailThread) {
        $m = reset($mailThread);
        $to = $m["fromtext"];
        $subject = $m["subjecttext"];
        $messageId = $m["messageid"];
    } else {
        $to = (! isset($to)) ? "" : $to;
        $subject = (! isset($subject)) ? "" : $subject;
        $messagebody = (! isset($messagebody)) ? "" : $messagebody;
        $messageId = "";
    }
    echo "    <tr>\n";
    echo "      <td scope='col'>To</td>\n";
    if ($isStaff) {
        $keywords = "Everyone";
        foreach($userclasses as $uc) {
            $keywords .= (empty($keywords)) ? $uc["userclassname"] : ", ".$uc["userclassname"];
        }
        echo "      <td data-label='To'>\n";
        if ($mailThread) {
            echo "        <span><b>".$to."</b></span>\n";
            echo "        <input type='hidden' name='to' id='to' value='".$to."' >\n";
        } else {
            echo "        <input type='textbox' name='to' id='to' value='' >\n";
            echo "        <span class='keywords'><b>keywords: </b>".$keywords."</span>\n";
        }
        echo "        <input type='checkbox' name='replyrequired' id='replyrequired' value='1'>\n";
        echo "        <label for='replyrequired'>Reply Required</label>\n";
        echo "      </td>\n";
    } else {
        echo "      <td data-label='To'>\n";
        if ($mailThread) {
            echo "        <span><b>".$to."</b></span>\n";
            echo "        <input type='hidden' name='to' id='to' value='".$to."' >\n";
        } else {
            $depts = $page->iMessage->getAdminDepartments($department);
            echo getSelectDDM($depts, "to", "username", "department");
        }
        echo "      </td>\n";
    }
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td scope='col'>Subject</td>\n";
    echo "      <td data-label='Subject'><input type='textbox' name='subject' id='subject' value='".$subject."'></td>\n";
    echo "    </tr>";
    echo "    <tr>\n";
    echo "      <td scope='col'>Message</td>\n";
    echo "      <td data-label='Message'><textarea name='messagebody' id='messagebody' rows='5' cols='150'></textarea></td>\n";
    echo "    <tr>\n";
    echo "    <tr>\n";
    echo "      <td scope='col'>Attachment</td>\n";
    echo "      <td data-label='Attachment'><input type='file' name='attachment' id='attachment' /> (jpg, jpeg, png, gif, doc, docx, pdf, txt only - Max:".(round(($CFG->ATTACH_MAX_UPLOAD/1000000),2))."MB)</td>\n";
    echo "    <tr>\n";
    echo "  </tbody>\n";
    echo "</table>\n";

    echo "  <div>\n";
    if ($isStaff && $displayEditor) {
        $onclick = "JavaScript: document.admin.messagecontent.value = tinymce.get(\"messagebody\").getContent();";
    } else {
        $onclick = "$('#messagecontent').val($('#messagebody').val());\n";
    }
    echo "    <input type='submit' name='sendbtn' value='Send' onclick='".$onclick."'>\n";
    echo "    <input type='hidden' name='messageId'  id='messageId'value='".$messageId."'>\n";
    echo "    <input type='hidden' name='messagecontent' id='messagecontent' value='".$messagebody."'>\n";
    if (empty($messageId)) {
        echo "    <a href='/mymessages.php'>Cancel</a>\n";
    } else {
        echo "    <a href='/readmessage.php?messageId=".$messageId."'>Cancel</a>\n";
    }
    echo "  </div>\n";
    echo "</form>\n";
    echo "<div>&nbsp;</div>\n";

    if ($mailThread) {
        echo "<table>\n";
        echo "  <thead>\n";
        echo "    <tr>\n";
        echo "      <th colspan='2'>MESSAGE THREAD</th>\n";
        echo "    </tr>";
        echo "  </thead>\n";
        echo "  <tbody>\n";
        $first = true;
        foreach($mailThread as $m) {
            if (!$first) {
                echo "    <tr><td colspan='2' class='mail-thread-background'></td></tr>";
            }
            echo "    <tr>\n";
            echo "      <td colspan='2' class='center'><b>Message ID:</b> ".$m["messageid"]."</td>\n";
            echo "    </tr>";
            echo "    <tr>\n";
            echo "      <td>From</td>\n";
            echo "      <td>".$m['fromtext']."</td>\n";
            echo "    </tr>";
            echo "    <tr>\n";
            echo "      <td>Sent</td>\n";
            echo "      <td>".date('F j, Y h:i:sA', $m["createdate"])."</td>\n";
            echo "    </tr>";
            echo "    <tr>\n";
            echo "      <td>Subject</td>\n";
            echo "      <td>".$m['subjecttext']."</td>\n";
            echo "    </tr>";
            echo "    <tr>\n";
            echo "      <td>Message</td>\n";
            echo "      <td>".stripslashes(str_replace("\n","<br>",str_replace("\c\n","<br>",$m['messagetext'])))."</td>\n";
            echo "    <tr>\n";
            if (!empty($m["attachmentname"])) {
                echo "    <tr>\n";
                echo "      <td>Attachment</td>\n";
                $url = $page->utility->getPrefixAttachmentImageURL($m["attachment"]);
                $link = "<a href='".$url."' target='_blank'>".$m["attachmentname"]."</a>";
                echo "      <td>".$link."</td>\n";
                echo "    </tr>";
            }
            $first = false;
        }
        echo "  </tbody>\n";
        echo "</table>\n";
    }

    if ($isStaff && $displayEditor) {
        echo "<script>\n";
        echo "  var messageContent = document.admin.messagecontent.value;\n";
        echo "  tinymce.init({\n";
        echo "    selector: '#messagebody',\n";
        echo "    plugins: 'print preview paste searchreplace autolink autoresize save directionality code visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor insertdatetime advlist lists wordcount textpattern noneditable help charmap quickbars emoticons',\n";
        echo "    menubar: 'file edit view insert format tools table help',\n";
        echo "    toolbar: 'undo redo | bold italic underline strikethrough | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview print | insertfile image link anchor codesample',\n";
        echo "    toolbar_sticky: true,\n";
        echo "    cleanup_on_startup : true,\n";
        echo "    cleanup : true,\n";
        echo "    verify_html : false,\n";
        echo "    setup: function (editor) {\n";
        echo "      editor.on('init', function (e) {\n";
        echo "        editor.setContent(messageContent);\n";
        echo "      });\n";
        echo "    }\n";
        echo "  });\n";
        echo "</script>\n";
    }
}
?>