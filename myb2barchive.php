<?php
require_once("paginator.class.php");
require_once('templateMyMessages.class.php');

$page = new templateMyMessages(LOGIN, SHOWMSG, REDIRECTSAFE);
$page->forceNoCache = true;

$pagenum              = optional_param('page', 1, PARAM_INT);
$prevpage             = optional_param('prevpage', 0, PARAM_INT);
$perpage              = optional_param('perpage', 50, PARAM_INT);
$searchstring         = optional_param('searchstring', NULL, PARAM_RAW);
$fdate                = optional_param('fromdate', date("m/d/Y", strtotime('-30 days')), PARAM_RAW);
$tdate                = optional_param('todate', date("m/d/Y"), PARAM_RAW);
$origsearchstring     = optional_param('orig_searchstring', NULL, PARAM_RAW);
$origfdate            = optional_param('orig_fromdate', NULL, PARAM_RAW);
$origtdate            = optional_param('orig_todate', NULL, PARAM_RAW);
$searchbtn            = optional_param('searchbtn', NULL, PARAM_RAW);

$perpage = (isset($perpage)) ? $perpage : $page->cfg->PERPAGE;
if ($origsearchstring <> $searchstring ||
    $origfdate <> $fdate ||
    $origtdate <> $tdate) {
    $pagenum = 1;
}

$fromdate = strtotime($fdate);
$todate = strtotime($tdate);
$totalRows = 0;

$calendarJS = '
    $(function(){$("#fromdate").datepicker();});
    $(function(){$("#todate").datepicker();});
';
$page->jsInit($calendarJS);

$x = getMaxMessageDate();
$xx = strtotime($x);
$maxdate = date("m/d/Y g:i:sA", $xx);
$page->messages->addInfoMsg("The B2B Archive is all B2B messages from the beginning of time to <span style='color:RED;'>".$maxdate."</span>.<br>If we don't have your data check back tomorrow.");

$page->header('B2B Archive');
mainContent();
$page->footer(true);

function mainContent() {
    global $page, $totalRows, $searchstring, $fromdate, $todate, $fdate, $tdate, $pagenum, $perpage, $searchbtn, $prevpage;

    echo "<h3>B2B Archive</h3>\n";
    echo "<article>\n";
    echo "  <div class='entry-content'>\n";
    echo "    <form name='mymessages' id='mymessages' method='post' action='".$_SERVER['PHP_SELF']."'>\n";
    echo "      <input type='hidden' name='orig_searchstring' id='orig_searchstring' value='".$searchstring."'>\n";
    echo "      <input type='hidden' name='orig_fromdate' id='orig_fromdate' value='".$fdate."'>\n";
    echo "      <input type='hidden' name='orig_todate' id='orig_todate' value='".$tdate."'>\n";
    echo "      <table>\n";
    echo "        <tbody>\n";
    echo "          <tr>\n";
    echo "            <td>Keyword</td>\n";
    echo "            <td><input type='textbox' name='searchstring' value='".$searchstring."'></td>\n";
    echo "            <td>From Date</td>\n";
    echo "            <td><input type='textbox' name='fromdate' id='fromdate' value='".$fdate."'></td>\n";
    echo "            <td>To Date</td>\n";
    echo "            <td><input type='textbox' name='todate' id='todate' value='".$tdate."'></td>\n";
    echo "            <td><input type='submit' name='searchbtn' value='Search'></td>\n";
    echo "          </tr>\n";
    echo "        </tbody>\n";
    echo "      </table>\n";
    echo "      <div>&nbsp;</div>\n";
    echo "      <table>\n";
    echo "        <thead>\n";
    echo "          <tr>\n";
    echo "            <th scope='col'>ID</th>\n";
    echo "            <th scope='col'>To</th>\n";
    echo "            <th scope='col'>From</th>\n";
    echo "            <th scope='col'>Subject</th>\n";
    echo "            <th scope='col'>Sent / Read</th>\n";
    echo "          </tr>";
    echo "        </thead>\n";
    echo "        <tbody>\n";
    if ((!empty($fromdate) && empty($todate)) ||
        (empty($fromdate) && !empty($todate))) {
        echo "          <tr><td colspan='5'><p class='errormsg'>ERROR: Must provide both a from and to date.</p></td></tr>\n";
    } elseif ($searchbtn || $prevpage <> $pagenum) {
        $messages = searchmsg($pagenum, $perpage, $searchstring, $fromdate, $todate);
        if (empty($messages)) {
            echo "          <tr><td colspan='5'><p class='warningmsg'>No messages found.</p></td></tr>\n";
        } else {
            foreach($messages as $m) {
                echo "          <tr>\n";
                echo "            <td data-label='Message ID' class='number'>".$m["messageid"]."</td>\n";
                if ($m['tologo']) {
                    $displayDealerName = "<img src='".$page->utility->getPrefixMemberImageURL($m['tologo'])."' title='".$m['to_text']."' width='75px' />";
                } else {
                    $displayDealerName = $m['to_text'];
                }
                $url = "dealerProfile.php?dealerId=".$m['to_id'];
                $link = "<a href='".$url."' target='_blank'>".$displayDealerName."</a>";
                echo "            <td data-label='To'>\n";
                echo "              ".$link;
                echo "            </td>\n";
                $eliteUser = ($m['iselite']) ? " <span title='Elite Dealer'><i class='fas fa-star'></span>" : "";
                if ($m['fromlogo']) {
                    $displayDealerName = "<img src='".$page->utility->getPrefixMemberImageURL($m['fromlogo'])."' title='".$m['from_text']."' width='75px' />";
                } else {
                    $displayDealerName = $m['from_text'];
                }
                $url = "dealerProfile.php?dealerId=".$m['from_id'];
                $link = "<a href='".$url."' target='_blank'>".$displayDealerName."</a>";
                echo "            <td data-label='From'>\n";
                echo "              ".$link;
                echo "            </td>\n";
                $url = "readb2barchive.php?id=".$m['messageid'];
                $link = "<a href='".$url."' target='_blank'>".stripslashes($m["subj_text"])."</a>";
                echo "            <td data-label='Subject'>".$link."</td>\n";
                echo "            <td data-label='Date Sent' class='date'>\n";
                echo "              ".$m["create_date"]."\n";
                echo "              <br><i>Read: ".$m["modify_date"]."</i>\n";
                echo "            </td>\n";
                echo "          </tr>\n";
            }
        }
    }
    echo "        </tbody>\n";
    echo "        <tfoot>\n";
    echo "          <tr>\n";
    echo "            <td colspan='5'>\n";
    echo "              <div class='pagination'>\n";
    if ($totalRows) {
        $pager = new Paginator($perpage, "page");
        $pager->set_total($totalRows);
        echo "                <nav role='navigation' aria-label='Pagination Navigation' class='text-filter'>\n";
        echo $pager->post_page_links("mymessages");
        echo "\n";
        echo "                </nav>\n";
    }
    echo "              </div>\n";
    echo "            </td>\n";
    echo "          </tr>\n";
    echo "        </tfoot>\n";
    echo "      </table>\n";
    echo "    </form>\n";
    echo "  </div>\n";
    echo "</article>\n";
    echo "<br />\n";
}

function searchmsg($pagenum, $perpage, $keywordsearch, $fromdate, $todate) {
    global $page, $totalRows;

    $keyword    = "";
    if (!empty($keywordsearch)) {
        $searchstring = strtolower(trim($keywordsearch));
        $keyword .= "
        AND (   strpos(lower(msg.from_text), '".$searchstring."') > 0
                OR strpos(lower(msg.to_text), '".$searchstring."') > 0
                OR strpos(lower(msg.subj_text), '".$searchstring."') > 0
                OR strpos(msg.id::varchar, '".$searchstring."') > 0
            )
        ";

    }

    $selectSql = "
        SELECT id as messageid, parent_id,
               to_id, to_text, from_id, from_text,
               subj_text, create_date, modify_date,
               CASE WHEN ar.userid IS NOT NULL THEN 1
                    ELSE 0 END as iselite,
               ui.listinglogo as fromlogo,
               ui2.listinglogo as tologo
    ";
    $sql = "
          FROM b2b_archive.messages msg
          JOIN userinfo             ui  ON  ui.userId       = msg.from_id
          JOIN userinfo             ui2 ON  ui2.userId      = msg.to_id
          LEFT JOIN assignedrights  ar  ON  ar.userid       = ui.userid
                                        AND ar.userrightid  = 15 -- Elite
         WHERE datetimetoint(create_date) BETWEEN ".$fromdate." and ".$todate."
           AND msg.message_type IN ('EMAIL', 'BULKMAIL', 'OFFER')
           AND (from_id = ".$page->user->userId."
                OR to_id = ".$page->user->userId.")
          ".$keyword."
    ";
    $sqlPage = "
        ORDER BY create_date desc
        OFFSET ".($pagenum-1)*$perpage."
         LIMIT ".$perpage;

    $totalRows = $page->db->get_field_query("SELECT count(1) as cnt ".$sql);

//    echo "<pre>".$selectSql.$sql.$sqlPage."</pre>";
    $result = $page->db->sql_query_params($selectSql.$sql.$sqlPage);

    return $result;

}

function getMaxMessageDate() {
    global $page;

    $sql = "
        select max(m.create_date)
          from b2b_archive.messages         m
          join b2b_archive.messages_text    mt on mt.id = m.id
    ";

    return($page->db->get_field_query($sql));
}

?>