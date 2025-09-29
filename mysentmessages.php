<?php
require_once("paginator.class.php");
require_once('templateMyMessages.class.php');

$page = new templateMyMessages(LOGIN, SHOWMSG, REDIRECTSAFE);

$pagenum              = optional_param('page', 1, PARAM_INT);
$prevpage             = optional_param('prevpage', 0, PARAM_INT);
$includeread          = optional_param('includeread', 1, PARAM_INT);
$ids2delete           = optional_param_array('messageid', array(), PARAM_INT);
$searchstring         = optional_param('searchstring', NULL, PARAM_RAW);
$fdate                = optional_param('fromdate', NULL, PARAM_RAW);
$tdate                = optional_param('todate', NULL, PARAM_RAW);
$perpage              = optional_param('perpage', NULL, PARAM_INT);
$perpage = (isset($perpage)) ? $perpage : $page->cfg->PERPAGE;

$fromdate = strtotime($fdate);
$todate = strtotime($tdate);

$totalRows = 0;
$includestatus = (empty($includeread)) ? "'".UNREADSTATUS."'" : "'".READSTATUS."', '".UNREADSTATUS."'";
$isAdmin = $page->user->hasUserRight("ADMIN");
if ($isAdmin && !empty($ids2delete)) {
    $page->iMessage->deleteMessages($page, implode(",", $ids2delete));
}

$calendarJS = '
    $(function(){$("#fromdate").datepicker();});
    $(function(){$("#todate").datepicker();});
';
$page->jsInit($calendarJS);

$page->header('my sent messages');
mainContent($pagenum, $perpage);
$page->footer(true);

function mainContent($pagenum, $perpage) {
    global $page;
    global $totalRows, $includestatus, $isAdmin, $searchstring, $fromdate, $todate, $fdate, $tdate;

    echo "<h3>My Sent Messages</h3>\n";
    echo "<form name='mymessages' id='mymessages' method='post' action='".$_SERVER['PHP_SELF']."'>\n";
    echo "  <table>\n";
    echo "    <tbody>\n";
    echo "      <tr>\n";
    echo "        <td>Find</td>\n";
    echo "        <td><input type='textbox' name='searchstring' value='".$searchstring."'></td>\n";
    echo "        <td>From</td>\n";
    echo "        <td><input type='textbox' name='fromdate' id='fromdate' value='".$fdate."'></td>\n";
    echo "        <td>To</td>\n";
    echo "        <td><input type='textbox' name='todate' id='todate' value='".$tdate."'></td>\n";
    echo "        <td><input type='submit' name='searchbtn' value='Search'></td>\n";
    echo "      </tr>\n";
    echo "    </tbody>\n";
    echo "  </table>\n";
    echo "  <div>&nbsp;</div>\n";
    echo "  <table>\n";
//    echo "    <caption>\n";
//    $checked = ($includestatus == "'".UNREADSTATUS."'") ? "" : "CHECKED";
//    echo "      <input type='checkbox' name='includeread' value='1' ".$checked." onclick='JavaScript: document.mymessages.submit();'>\n";
//    echo "      <label for='includeread'>Include read messages</label>\n";
//    echo "    </caption>\n";
    echo "    <thead>\n";
    echo "      <tr>\n";
    echo "        <th scope='col'>ID</th>\n";
    echo "        <th scope='col'>To</th>\n";
    echo "        <th scope='col'>Subject</th>\n";
    echo "        <th scope='col'>Sent / Read</th>\n";
    if ($isAdmin) {
        echo "        <th scope='col'>Delete</th>\n";
    }
    echo "      </tr>";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    if ((!empty($fromdate) && empty($todate)) ||
        (empty($fromdate) && !empty($todate))) {
        echo "      <tr><td colspan='4'><p class='errormsg'>ERROR: Must provide both a from and to date.</p></td></tr>\n";
    } else {
        $messages = $page->iMessage->getMessagesNoBody_Sent($includestatus, $pagenum, $perpage, $searchstring, $fromdate, $todate);
        if (empty($messages)) {
            echo "      <tr><td colspan='4'><p class='warningmsg'>No messages found.</p></td></tr>\n";
        } else {
            foreach($messages as $m) {
                $unreadclass = ($m["status"] == UNREADSTATUS) ? "class='unread'" : "";
                echo "      <tr ".$unreadclass.">\n";
                echo "        <td class='number' data-label='Message ID'>".$m["messageid"]."</td>\n";
                echo "        <td class='letter' data-label='To'>".$m["totext"]."</td>\n";
                $url = "readmessage.php?return=outbox&messageId=".$m['messageid'];
                $link = "<a class='letter' href='".$url."'>".stripslashes($m["subjecttext"])."</a>";
                echo "        <td class='letter' data-label='Subject'>".$link."</td>\n";
                echo "        <td class='date' data-label='Date Sent'>\n";
                echo "          ".date('F j, Y h:i:sA', $m["createdate"])."\n";
                if (!empty($m["datereplied"])) {
                    echo "          <br><i>Replied on: ".date('F j, Y h:i:sA', $m["datereplied"])."</i>\n";
                } elseif ($m["createdate"] <> $m["modifydate"]) {
                    echo "          <br><i>Read: ".date('F j, Y h:i:sA', $m["modifydate"])."</i>\n";
                }
                echo "        </td>\n";
                if ($isAdmin && empty($m["datereplied"])) {
                    echo "        <td  data-label='Delete Msg' class='indicator'><input type='checkbox' name='messageid[]' value='".$m["messageid"]."'></td>\n";
                } elseif($isAdmin) {
                    echo "        <td>&nbsp;</td>\n";
                }
                echo "      </tr>\n";
            }
        }
    }
    echo "    </tbody>\n";
    echo "    <tfoot>\n";
    if ($isAdmin) {
        echo "      <tr>\n";
        echo "        <td data-label='' colspan='5'>\n";
        echo "          <input type='submit' name='deletebtn' value='Delete Messages'>\n";
        echo "        </td>\n";
        echo "      </tr>\n";
    }
    echo "      <tr>\n";
    echo "        <td colspan='5'>\n";
    echo "          <div class='pagination'>\n";
    $val = (empty($checked)) ? 0 : 1;
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