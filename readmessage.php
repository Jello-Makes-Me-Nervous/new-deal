<?php
require_once('templateCommon.class.php');

$page = new templateCommon(LOGIN, SHOWMSG, REDIRECTSAFE);

$messageId      = optional_param('messageId', NULL, PARAM_INT);
$markasunread   = optional_param('markasunread', NULL, PARAM_RAW);
$markasread     = optional_param('markasread', NULL, PARAM_RAW);
$return         = optional_param('return', NULL, PARAM_RAW);
$message = $page->iMessage->getMessage($messageId);
if (!empty($markasunread)) {
    if (($message["toid"] == $page->user->userId) && ($message["status"] == READSTATUS)) {
        $page->iMessage->updateStatus($page, $messageId, UNREADSTATUS);
    }
    if ($return == 'inbox') {
        header('Location: /mymessages.php');
        exit();
    } elseif ($return == 'outbox') {
        header('Location: /mysentmessages.php');
        exit();
    }
} else {
    if (!empty($markasread)) {
        if (($message["toid"] == $page->user->userId) && ($message["status"] == UNREADSTATUS)) {
            $page->iMessage->updateStatus($page, $messageId, READSTATUS);
        }
        if ($return == 'inbox') {
            header('Location: /mymessages.php');
            exit();
        } elseif ($return == 'outbox') {
            header('Location: /mysentmessages.php');
            exit();
        }
    } else {
        if (($message["toid"] == $page->user->userId) && ($message["status"] == UNREADSTATUS)) {
            $page->iMessage->setNotNew($page, $messageId);
        }
    }
}
$message = $page->iMessage->getMessage($messageId);
$latest = $page->iMessage->getLatestMsgOfThread($message["threadid"]);
if ($message["toid"] == $page->user->userId && $latest["messageid"] > $messageId) {
    $page->messages->addInfoMsg("You have already replied to this message on ".date("F j, Y h:i:sA", $latest["createdate"]));
} elseif (!empty($message["replyrequired"])) {
    $page->messages->addInfoMsg("You need to reply to this message.");
}
echo $page->header('Read Message');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $messageId, $message, $latest, $return;

    if ($message) {
        echo "<table>\n";
        echo "  <thead>\n";
        echo "    <tr>\n";
        echo "      <th scope='col' colspan='2'></th>\n";
        echo "    </tr>";
        echo "  </thead>\n";
        echo "  <tbody>\n";
        if (isset($message)) {
            echo "    <tr>\n";
            echo "      <td>Message ID</td>\n";
            echo "      <td>".$message["messageid"]."</td>\n";
            echo "    <tr>\n";
            if ($page->user->userId == $message["toid"]) {
                echo "      <td>From</td>\n";
                $url = "/dealerProfile.php?dealerId=".$message["fromid"];
                $link = "<a href='".$url."' target='_blank'>".$message["fromtext"]."</a>";
                $frominfo  = $link."<br>";
                $frominfo .= (empty($message["firstname"])) ? "" : $message["firstname"]." ".$message["lastname"]."<br>";
                $frominfo .= (empty($message["company"])) ? "" : $message["company"]."<br>";
                $frominfo .= (empty($message["street"])) ? "" : $message["street"]."<br>";
                $frominfo .= (empty($message["street2"])) ? "" : $message["street2"]."<br>";
                $frominfo .= (empty($message["street3"])) ? "" : $message["street3"]."<br>";
                $frominfo .= (empty($message["city"])) ? "" : $message["city"].", ";
                $frominfo .= (empty($message["state"])) ? "" : $message["state"]." ";
                $frominfo .= (empty($message["zip"])) ? "" : $message["zip"]."&nbsp;&nbsp;&nbsp;";
                $frominfo .= (empty($message["country"])) ? "" : $message["country"]."<br>";
                $frominfo .= (empty($message["phone"])) ? "" : "<b>Phone: </b>".$message["phone"]."<br>";
                $frominfo .= (empty($message["fax"])) ? "" : "<b>fax: </b>".$message["fax"]."<br>";
                $frominfo .= (empty($message["email"])) ? "" : "<b>eMail: </b>".$message["email"]."<br>";
                echo "      <td>".$frominfo."</td>\n";
            } else {
                echo "      <td>To</td>\n";
                echo "      <td>".$message['totext']."</td>\n";
            }
            echo "    </tr>";
            echo "    <tr>\n";
            echo "      <td>Sent</td>\n";
            echo "      <td>".date('F j, Y h:i:sA', $message["createdate"])."</td>\n";
            echo "    </tr>";
            if ($message["status"] == READSTATUS || $message["createdate"] <> $message["modifydate"]) {
                echo "    <tr>\n";
                echo "      <td>Initially Read</td>\n";
                echo "      <td>".date('F j, Y h:i:sA', $message["modifydate"])."</td>\n";
                echo "    </tr>";
            }
            echo "    <tr>\n";
            echo "      <td>Subject</td>\n";
            echo "      <td>".$message['subjecttext']."</td>\n";
            echo "    </tr>";
            echo "    <tr>\n";
            echo "      <td>Message</td>\n";
            echo "      <td>\n";
            if (!empty($message["messagetext"])                  &&
                (strpos($message["messagetext"], "<p") !== false ||
                 strpos($message["messagetext"], "<br") !== false)) {
                echo stripslashes($message['messagetext']);
            } else {
                echo stripslashes(nl2br($message['messagetext']));
            }
            if (!empty($message["offerid"])) {
                if ($message["messagetype"] == OFFERCHAT) {
                    $url = "offer.php?offerid=".$message['offerid']."&tabid=messages";
                    $subject = "Offer chat on ".$message["subjecttext"]." (reference #: ".$message["offerid"].")";
                    $offerlink = "<a href='".$url."'>".stripslashes($subject)."</a>";
                } elseif ($message["messagetype"] == COMPLAINT) {
                    if ($page->user->isAdmin()) {
                        $url = "offeradmin.php?offerid=".$message['offerid'];
                    } else {
                        $url = "offer.php?offerid=".$message['offerid']."&tabid=assistance";
                    }
                    $subject = "Assistance chat on ".$message["subjecttext"]." (reference #:".$message["offerid"].")";
                    $offerlink = "<a href='".$url."'>".stripslashes($subject)."</a>";
                } elseif ($message["messagetype"] == OFFERDOC) {
                    $url = "offer.php?offerid=".$message['offerid']."&tabid=documents";
                    $offerlink = "<a href='".$url."'>".$message["subjecttext"]."</a>";
                } else {
                    $url = "offer.php?offerid=".$message['offerid'];
                    $offerlink = "<a href='".$url."'>".$message["subjecttext"]."</a>";
                }
                echo "        <div style='padding-top:25px;'>\n";
                echo "          Click here for latest on ".$offerlink;
                echo "        </div>\n";
            }
            echo "      </td>\n";
            echo "    <tr>\n";
            if (!empty($message["attachmentname"])) {
                echo "    <tr>\n";
                echo "      <td>Attachment</td>\n";
                $url = $page->utility->getPrefixAttachmentImageURL($message["attachment"]);
                $link = "<a href='".$url."' target='_blank'>".$message["attachmentname"]."</a>";
                echo "      <td>".$link."</td>\n";
                echo "    </tr>";
            }
        } else {
            echo "    <tr>\n";
            echo "      <td colspan='2'>Message not found.</td>\n";
            echo "    </tr>";
        }
        echo "  </tbody>\n";
        echo "</table>\n";

        echo "<div style='padding: 3px;'>\n";
        if (canReply($message, $latest)) {
            echo "  <div style='float:left;'>\n";
            echo "<form name='adminreply' action='sendmessage.php' method='post' enctype='multipart/form-data'>\n";
            echo "  <input type='hidden' name='threadId' value='".$message["threadid"]."'>\n";
            echo "  <input type='hidden' name='replytoid' value='".$messageId."'>\n";
            echo "  <input type='submit' name='reply' value='Reply'>\n";
            echo "</form>\n";
            echo "  </div>\n";
        }
        echo "<form name='admin' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post' enctype='multipart/form-data'>\n";
        echo "  <div style='float:left;'>\n";
        if ($message["toid"] == $page->user->userId) {
            echo "  <input type='hidden' name='messageId' value='".$messageId."'>\n";
            echo "  <input type='submit' name='markasunread' value='Keep as NEW'>\n";
            echo "  <input type='submit' name='markasread' value='Mark as Read'>\n";
        } elseif ($message["fromid"] == $page->user->userId) {
            echo "  <a href='/mysentmessages.php'>Back to Outbox</a>\n";
        }
        echo "  <input type='hidden' name='return' value='".$return."'>\n";
        echo "</form>\n";
        echo "  </div>\n";
        echo "</div>\n";
        echo "<div>&nbsp;</div>\n";

        $mailThread = $page->iMessage->getMailThread($message["threadid"], 0);
        if (count($mailThread) > 1) {
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
                    echo "    <tr><td colspan='2' style='background-color:#DDD;'>&nbsp;</td></tr>";
                }
                if ($messageId == $m["messageid"] && !$first) {
                    echo "    <tr><td colspan='2'><b>** THIS MESSAGE **</b></td></tr>";
                } elseif ($messageId <> $m["messageid"]) {
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
                    echo "      <td>".stripslashes($m['messagetext'])."</td>\n";
                    echo "    <tr>\n";
                    if (!empty($m["attachmentname"])) {
                        echo "    <tr>\n";
                        echo "      <td>Attachment</td>\n";
                        $url = $page->utility->getPrefixAttachmentImageURL($m["attachment"]);
                        $link = "<a href='".$url."' target='_blank'>".$m["attachmentname"]."</a>";
                        echo "      <td>".$link."</td>\n";
                        echo "    </tr>";
                    }
                }
                $first = false;
            }
            echo "  </tbody>\n";
            echo "</table>\n";
        }
    } else {
        echo "<p class='errormsg'>ERROR: Message not found. [".$messageId."]</p>";
    }
}

function canReply($message, $latest) {
    global $page;

    $canreply = true;
    if ($message["fromid"]      <> SYSTEMUSERID             &&
        $message["toid"]        == $page->user->userId      &&
        $latest["messageid"]    == $message["messageid"]    &&
        (empty($message["offerid"]) || (!empty($message["replyrequired"])))) {
        $canreply = true;
    } else {
        $canreply = false;
    }

    return $canreply;
}
?>