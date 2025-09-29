<?php
require_once('templateCommon.class.php');

$page = new templateCommon(LOGIN, SHOWMSG);
$iMessage = new internalMessage();

$messageId      = optional_param('messageId', NULL, PARAM_INT);

echo $page->header('Read Mail');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $iMessage, $messageId, $threadId, $mailThread, $parentId;

    $message = $iMessage->getOneMail($messageId);
    $m = reset($message);
    echo "<table>\n";
    echo "  <thead>\n";
    echo "    <tr>\n";
    echo "      <th colspan='2'></th>\n";
    echo "    </tr>";
    echo "  </thead>\n";
    echo "  <tbody>\n";
    if (isset($m)) {
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
    } else {
        echo "    <tr>\n";
        echo "      <td colspan='2'>Message not found.</td>\n";
        echo "    </tr>";
    }
    echo "  </tbody>\n";
    echo "</table>\n";

    if (isset($m["parentId"]) && $m["parentId"] <> 0) {
        $mailThread = $iMessage->getMailThread($m["threadId"], $messageId);
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
                    echo "    <tr><td colspan='2' style='background-color:#CCC;'>&nbsp;</td></tr>";
                }
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
                $first = false;
            }
            echo "  </tbody>\n";
            echo "</table>\n";
        }
    }

}
?>