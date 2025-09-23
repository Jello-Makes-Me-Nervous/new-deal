<?php
require_once('templateCommon.class.php');

$page = new templateCommon(LOGIN, SHOWMSG);

$attach         = optional_param('attach', NULL, PARAM_FILE);
$messageId      = optional_param('messageId', NULL, PARAM_INT);
$messageText    = optional_param('messageText', NULL, PARAM_TEXT);
$messageType    = optional_param('messageType', NULL, PARAM_TEXT);
$parentId       = optional_param('parentId', NULL, PARAM_INT);
$toId           = optional_param('toId', NULL, PARAM_INT);
$toText         = optional_param('toText', NULL, PARAM_TEXT);
$threadId       = optional_param('threadId', NULL, PARAM_INT);
$subjectText    = optional_param('subjectText', NULL, PARAM_TEXT);
$reply          = optional_param('reply', NULL, PARAM_INT);
$replyInfo      = optional_param('replyInfo', NULL, PARAM_INT);
$send           = optional_param('send', NULL, PARAM_TEXT);
$sendMail       = optional_param('sendMail', NULL, PARAM_TEXT);
$sendReply      = optional_param('sendReply', NULL, PARAM_TEXT);

$userName = $UTILITY->getUserName($page->user->userId);

$iMessage = new internalMessage();
if($reply == 1) {
    $replyInfo = $iMessage->getOneMail($messageId);
    $info = reset($replyInfo);
}

if(isset($send)){
    $attach = $_FILES["attach"];
    $toText = $UTILITY->getDealersName($toId);
    $iMessage->insertMessage($page->user->userId, $userName, $toId, $toText, $subjectText, $messageText, $messageType, NULL, NULL, NULL, NULL, $attach);
}

if(isset($sendReply)) {
    $iMessage->insertMessage($page->user->userId, $userName, $toId, $toText, $subjectText, $messageText, $messageType, $threadId, $messageId, NULL, $parentId);
}

echo $page->header('Create Mail');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $reply, $messageId, $parentId, $replyInfo, $threadId;

    echo "<form name ='sub2' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post' enctype='multipart/form-data'>\n";
    echo "  <table width='80%' cellpadding='0' cellspacing='10'>\n";
    echo "    <thead>\n";
    echo "      <tr>\n";
    echo "        <th align='left'></th>\n";
    echo "        <th align='left'></th>\n";
    echo "      </tr>\n";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    echo populateReplyInfo($reply, $messageId, $replyInfo);
    echo "      <tr>\n";
    echo "        <td colspan='2'>Message:<br /><textarea name='messageText' rows='5' cols='60'></textarea></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    if($reply != 1) {
        echo "        <td><input type='submit' name='send' value='Send Mail'></td>\n";
    } elseif($reply == 1) {
        echo "        <td>\n";
        echo "          <input type='submit' name='sendReply' value='Send Reply'>\n";
        echo "          <input type='hidden' name='messageId' value='".$messageId."'>\n";
        echo "          <input type='hidden' name='threadId' value='".$threadId."'>\n";
        echo "          <input type='hidden' name='parentId' value='".$parentId."'>\n";
        echo "        </td>\n";
    }
    echo "      </tr>\n";
    echo "    </tbody>\n";
    echo "  </table>\n";
    echo "</form>\n";
}


function populateReplyInfo($reply, $messageId, $replyInfo) {
    global $CFG, $page, $messageType, $subjectText, $toId;

    $iMessage = new internalMessage();

     $output = "";

    if($reply != 1) {
        $output .= "      <tr>\n";
        $output .= "        <td colspan='2'>Message Type:".$iMessage->getMailTypeDDM($messageType)."</td>\n";
        $output .= "      </tr>\n";
        $output .= "      <tr>\n";
        $output .= "        <td>To: ".dealersDDM($toId)."</td>\n";
        $output .= "        <td>Subject:<input type='text' name='subjectText' value='".$subjectText."'></td>\n";
        $output .= "      </tr>\n";
        $output .= "      <tr>\n";
        $output .= "        <td>Attachment: <input type='file' name='attach' id='attach' /> (jpg, jpeg, png, gif, doc, docx, pdf, txt only - Max:".(round(($CFG->ATTACH_MAX_UPLOAD/1000000),2))."MB)</td>\n";
        $output .= "      </tr>\n";
    } elseif($reply == 1) {

        $info = reset($replyInfo);

        $output .= "      <tr>\n";
        $output .= "        <td colspan='2'>Message Type: <input type='text' name='messageType' value='".$info['messagetype']."' readonly></td>\n";
        $output .= "      </tr>\n";
        $output .= "      <tr>\n";
        $output .= "        <td>To: <input type='text' name='toText' value='".$info['fromtext']."' readonly></td>\n";
        $output .= "        <td>\n";
        $output .= "          Subject: <input type='text' name='subjectText' value='".$info['subjecttext']."' readonly>\n";
        $output .= "          <input type='hidden' name='toId' value='".$info['fromid']."'/n";
        $output .= "        </td>\n";
        $output .= "      </tr>\n";
    }

    return $output;

}

function dealersDDM($toId = NULL) {
    global $page;


    $data = getUsers();

    $output = getSelectDDM($data, "toId", "userid", "username", NULL, $toId, "Select");

    return $output;
}

function getUsers() {
    global $page;

    $sql = "SELECT userid, username FROM users ORDER BY username";

    $dealerId = $page->db->sql_query_params($sql);

    return $dealerId;
}

?>