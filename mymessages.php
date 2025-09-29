<?php
require_once("paginator.class.php");
require_once('templateMyMessages.class.php');

$page = new templateMyMessages(LOGIN, SHOWMSG, REDIRECTSAFE);
$page->forceNoCache = true;
//$page->messages->addInfoMsg("We are displaying the last 7 days of system messages, 30 days of staff messages and all messages from our Dealernet community that are unread.  Please use the search box to locate any other message or click the checkbox to include read messages in your inbox.");

$pagenum              = optional_param('page', 1, PARAM_INT);
$prevpage             = optional_param('prevpage', 0, PARAM_INT);
$includeread          = optional_param('includeread', 0, PARAM_INT);
$searchstring         = optional_param('searchstring', NULL, PARAM_RAW);
$fdate                = optional_param('fromdate', NULL, PARAM_RAW);
$tdate                = optional_param('todate', NULL, PARAM_RAW);
$perpage              = optional_param('perpage', NULL, PARAM_INT);
$perpage = (isset($perpage)) ? $perpage : $page->cfg->PERPAGE;

$fromdate = strtotime($fdate);
$todate = strtotime($tdate);
$totalRows = 0;
$includestatus = (empty($includeread)) ? "'".UNREADSTATUS."'" : "'".READSTATUS."', '".UNREADSTATUS."'";

$calendarJS = '
    $(function(){$("#fromdate").datepicker();});
    $(function(){$("#todate").datepicker();});
';
$page->jsInit($calendarJS);

$hasreplyneeded = false;
if ($page->iMessage->hasAdminMsgsRequiringReply($page->user->userId)) {
    $hasreplyneeded = true;
    $page->messages->addWarningMsg("Messages marked with <i class='fa fa-exclamation-triangle'></i> require a reply prior to proceeding.");
}

$page->header('my messages');
mainContent($pagenum, $perpage);
$page->footer(true);

function mainContent($pagenum, $perpage) {
    global $page, $messages, $hasreplyneeded;
    global $totalRows, $includestatus, $searchstring, $fromdate, $todate, $fdate, $tdate, $pagenum, $perpage;

    $blasts = array();
    $havemsgs = false;
    $messages = $page->iMessage->getMessagesNoBody($includestatus, $pagenum, $perpage, $searchstring, $fromdate, $todate);
    echo "<h3>My Messages</h3>\n";
    echo "<form name='mymessages' id='mymessages' method='post' action='".$_SERVER['PHP_SELF']."'>\n";
    echo "  <table>\n";
    echo "    <tbody>\n";
    echo "      <tr>\n";
    echo "        <td>Keyword</td>\n";
    echo "        <td><input type='textbox' name='searchstring' value='".$searchstring."'></td>\n";
    echo "        <td>From Date</td>\n";
    echo "        <td><input type='textbox' name='fromdate' id='fromdate' value='".$fdate."'></td>\n";
    echo "        <td>To Date</td>\n";
    echo "        <td><input type='textbox' name='todate' id='todate' value='".$tdate."'></td>\n";
    echo "        <td><input type='submit' name='searchbtn' value='Search'></td>\n";
    echo "      </tr>\n";
    echo "    </tbody>\n";
    echo "  </table>\n";
    echo "  <div>&nbsp;</div>\n";
    echo "  <table>\n";
    echo "    <caption class='right'>\n";
    if ($hasreplyneeded) {
        echo "      <i class='fa fa-exclamation-triangle'></i> - Reply required.\n";
    }
    $checked = ($includestatus == "'".UNREADSTATUS."'") ? "" : "CHECKED";
    echo "      <input type='checkbox' name='includeread' value='1' ".$checked." onclick='JavaScript: document.mymessages.submit();'>\n";
    echo "      <label for='includeread'>Include read messages</label>\n";
    echo "    </caption>\n";
    echo "    <thead>\n";
    echo "      <tr>\n";
    echo "        <th scope='col' colspan='2'>ID</th>\n";
    echo "        <th scope='col'>From</th>\n";
    echo "        <th scope='col'>Subject</th>\n";
    echo "        <th scope='col'>Sent / Read / Expires / Completes</th>\n";
    echo "      </tr>";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    if ((!empty($fromdate) && empty($todate)) ||
        (empty($fromdate) && !empty($todate))) {
        echo "      <tr><td colspan='5'><p class='errormsg'>ERROR: Must provide both a from and to date.</p></td></tr>\n";
    } else {
        if (empty($messages)) {
            echo "      <tr><td colspan='5'><p class='warningmsg'>No messages found.</p></td></tr>\n";
        } else {
            $firstrecord = reset($messages);
            $prevReplyReq = $firstrecord["replyneeded"];
            $prevstaffsystem = $firstrecord["staffsystem"];
            foreach($messages as $m) {
                if ($m["messagetype"] == BLASTTYPE) {
                    $blasts[] = $m;
                } else {
                    $havemsgs = true;
                    if ($prevReplyReq <> $m["replyneeded"]) {
                        $prevReplyReq = $m["replyneeded"];
                        echo "      <tr><td colspan='5'>&nbsp;</td></tr>\n";
                    } elseif ($prevstaffsystem <> $m["staffsystem"]) {
                        $prevstaffsystem = $m["staffsystem"];
                        echo "      <tr><td colspan='5'>&nbsp;</td></tr>\n";
                    }
                    $unreadclass = ((($m["status"] == UNREADSTATUS) && ($m["createdate"] == $m["modifydate"]))  ||
                                    $m["status"] == PENDINGSTATUS ||
                                    $m["status"] == ACCEPTEDSTATUS) ? "class='unread'" : "";
                    echo "      <tr ".$unreadclass.">\n";
                    $colspan = 2;
                    if (!empty($m["replyneeded"])) {
                        echo "        <td data-label='Reply Needed' class='indicator'><i class='fa fa-exclamation-triangle'></i></td>\n";
                        $colspan = 1;
                    }
                    echo "        <td data-label='Message ID' colspan='".$colspan."' class='number'>".$m["messageid"]."</td>\n";
                    $eliteUser = ($m['iselite']) ? " <span title='Elite Dealer'><i class='fas fa-star'></span>" : "";
                    if ($m['listinglogo']) {
                        $displayDealerName = "<img src='".$page->utility->getPrefixMemberImageURL($m['listinglogo'])."' title='".$m['fromtext']."' width='75px' />";
                    } else {
                        $displayDealerName = $m['fromtext'];
                    }
                    $url = "dealerProfile.php?dealerId=".$m['fromid'];
                    $link = "<a href='".$url."' target='_blank'>".$displayDealerName."</a>";
                    echo "        <td data-label='From'>\n";
                    echo "          ".$link;
                    echo "        </td>\n";
                    $subject = $m["subjecttext"];
                    if ($m["messagetype"] == OFFERCHAT) {
                        $subject = "Offer chat on ".$m["subjecttext"]." (reference #: ".$m["offerid"].")";
                    } elseif ($m["messagetype"] == COMPLAINT) {
                        $subject = "Assistance chat on ".$m["subjecttext"]." (reference #:".$m["offerid"].")";
                    } elseif ($m["messagetype"] == OFFER) {
                        $subject = "New offer ".$m["subjecttext"]." (reference #:".$m["offerid"].")";
                    } elseif ($m["messagetype"] == OFFERDOC) {
                        $subject = "New document uploaded for ".$m["subjecttext"]." (reference #:".$m["offerid"].")";
                    }
                    if ($m["messagetype"] == BLASTTYPE) {
                        $url = "blastview.php?listingid=".$m['messageid'];
                        $link = "<a href='".$url."' target='_blank'>".stripslashes($subject)."</a>";
                    } else {
                        $url = "readmessage.php?return=inbox&messageId=".$m['messageid'];
                        $link = "<a href='".$url."'>".stripslashes($subject)."</a>";
                    }
                    echo "        <td data-label='Subject'>".$link."</td>\n";
                    echo "        <td data-label='Date Sent' class='date'>\n";
                    echo "          ".date('F j, Y h:i:sA', $m["createdate"])."\n";
                    if (!empty($m["datereplied"])) {
                        echo "          <br><i>Replied on: ".date('F j, Y h:i:sA', $m["datereplied"])."</i>\n";
                    } elseif ($m["status"] == PENDINGSTATUS) {
                        echo "          <br><i>Expires on: ".date('F j, Y h:i:sA', $m["offerexpiration"])."</i>\n";
                    } elseif ($m["status"] == ACCEPTEDSTATUS) {
                        echo "          <br><i>Completes on: ".date('F j, Y h:i:sA', $m["completedon"])."</i>\n";
                    } elseif ($m["createdate"] <> $m["modifydate"]) {
                        echo "          <br><i>Read: ".date('F j, Y h:i:sA', $m["modifydate"])."</i>\n";
                    }
                    echo "        </td>\n";
                    echo "      </tr>\n";
                }
            }
            if (!empty($blasts)) {
                if ($havemsgs) {
                    echo "      <tr><td colspan='5'>&nbsp;</td></tr>\n";
                    echo "    </tbody>\n";
                    echo "    <thead>\n";
                    echo "      <tr>\n";
                    echo "        <th scope='col' colspan='2'>ID</th>\n";
                    echo "        <th scope='col'>From</th>\n";
                    echo "        <th scope='col'>BLASTS</th>\n";
                    echo "        <th scope='col'>Sent / Read / Expires / Completes</th>\n";
                    echo "      </tr>";
                    echo "    </thead>\n";
                    echo "    <tbody>\n";
                }
                foreach($blasts as $m) {
                    if ($prevReplyReq <> $m["replyneeded"]) {
                        $prevReplyReq = $m["replyneeded"];
                        echo "      <tr><td colspan='5'>&nbsp;</td></tr>\n";
                    }
                    $unreadclass = ((($m["status"] == UNREADSTATUS) && ($m["createdate"] == $m["modifydate"]))  ||
                                    $m["status"] == PENDINGSTATUS ||
                                    $m["status"] == ACCEPTEDSTATUS) ? "class='unread'" : "";
                    echo "      <tr ".$unreadclass.">\n";
                    $colspan = 2;
                    if (!empty($m["replyneeded"])) {
                        echo "        <td data-label='Reply Needed' class='indicator'><i class='fa fa-exclamation-triangle'></i></td>\n";
                        $colspan = 1;
                    }
                    echo "        <td data-label='Message ID' colspan='".$colspan."' class='number'>".$m["messageid"]."</td>\n";
                    $eliteUser = ($m['iselite']) ? " <span title='Elite Dealer'><i class='fas fa-star'></span>" : "";
                    if ($m['listinglogo']) {
                        $displayDealerName = "<img src='".$page->utility->getPrefixMemberImageURL($m['listinglogo'])."' title='".$m['fromtext']."' width='75px' />";
                    } else {
                        $displayDealerName = $m['fromtext'];
                    }
                    $url = "dealerProfile.php?dealerId=".$m['fromid'];
                    $link = "<a href='".$url."' target='_blank'>".$displayDealerName."</a>";
                    echo "        <td data-label='From'>\n";
                    echo "          ".$link;
                    echo "        </td>\n";
                    $subject = $m["subjecttext"];
                    $url = "blastview.php?listingid=".$m['messageid'];
                    $link = "<a href='".$url."' target='_blank'>".stripslashes($subject)."</a>";
                    echo "        <td data-label='Subject'>".$link."</td>\n";
                    echo "        <td data-label='Date Sent' class='date'>\n";
                    echo "          ".date('F j, Y h:i:sA', $m["createdate"])."\n";
                    if (!empty($m["datereplied"])) {
                        echo "          <br><i>Replied on: ".date('F j, Y h:i:sA', $m["datereplied"])."</i>\n";
                    } elseif ($m["createdate"] <> $m["modifydate"]) {
                        echo "          <br><i>Read: ".date('F j, Y h:i:sA', $m["modifydate"])."</i>\n";
                    }
                    echo "        </td>\n";
                    echo "      </tr>\n";
                }
            }
        }
    }
    echo "    </tbody>\n";
    echo "    <tfoot>\n";
    echo "      <tr>\n";
    echo "        <td colspan='5'>\n";
    echo "          <div class='pagination'>\n";
    if ($totalRows) {
        $pager = new Paginator($perpage, "page");
        $pager->set_total($totalRows);
        echo "            <nav role='navigation' aria-label='Pagination Navigation' class='text-filter'>\n";
        echo $pager->post_page_links("mymessages");
        echo "\n";
        echo "            </nav>\n";
    }
    echo "          </div>\n";
    echo "        </td>\n";
    echo "      </tr>\n";
    echo "    </tfoot>\n";
    echo "  </table>\n";
    echo "</form>\n";

}

?>