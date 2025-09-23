<?php
require_once("paginator.class.php");
require_once('templateMyMessages.class.php');

$page = new templateMyMessages(LOGIN, SHOWMSG, REDIRECTSAFE);
$page->forceNoCache = true;

$lastlogin  = optional_param('lastlogin', 0, PARAM_INT);
$pagenum    = optional_param('page', 1, PARAM_INT);
$prevpage   = optional_param('prevpage', 0, PARAM_INT);
$perpage    = optional_param('perpage', NULL, PARAM_INT);
$perpage = (isset($perpage)) ? $perpage : $page->cfg->PERPAGE;

if ($page->user->isProxied()) {
    $logins = displayLastloginData();
    if (empty($logins)) {
        $page->messages->addWarningMsg("Proxied to user has not logged in yet.");
    } elseif (!empty($lastlogin)) {
        $fromdate = $lastlogin;
    } else {
        $ll = reset($logins);
        $fromdate = $ll["logindate"];
    }
} else {
    $fromdate = $_SESSION['lastlogin'];
}
$todate = time();

$hasreplyneeded = false;
if ($page->iMessage->hasAdminMsgsRequiringReply($page->user->userId)) {
    $hasreplyneeded = true;
    $page->messages->addWarningMsg("Messages marked with <i class='fa fa-exclamation-triangle'></i> require a reply prior to proceeding.");
}

$page->header('activities');
mainContent($pagenum, $perpage);
$page->footer(true);

function mainContent($pagenum, $perpage) {
    global $page, $fromdate, $todate;
    $lastlogin = (empty($fromdate)) ? "" : " - ".date("l, F j, Y (h:iA)", $fromdate);

    echo "<h2>Activities Since".$lastlogin."</h2>\n";
    if ($page->user->isProxied()) {
        displayLastLoginForm();
        echo "<div>&nbsp;</div>\n";
    }
    myEFTs();
    echo "<div>&nbsp;</div>\n";
    myOffers();
    echo "<div>&nbsp;</div>\n";
    myMessages($pagenum, $perpage);
}

function displayLastLoginForm() {
    global $logins;

    echo "<form name='lastloginform' id='lastloginform' method='POST' action='".$_SERVER['PHP_SELF']."'>\n";
    echo "  <label for='lastlogin' style='font-weight: bold;'>Previous Login: </Label>\n";
    echo displayLastloginDDM($logins);
    echo "</form>\n";
}

function displayLastloginData() {
    global $page;

    $sql = "
        SELECT logindate,
               to_char(to_timestamp(logindate),'MM/DD/YYYY HH24:MI:SS') as loggedin
          FROM loginlog
         WHERE userid = ".$page->user->userId."
        ORDER BY logindate DESC limit 5
    ";

//  echo "<pre>".$sql."</pre>\n";
    $logins = $page->db->sql_query($sql);

    return $logins;
}

function displayLastloginDDM($logins) {
    global $fromdate;

    $onchange = "onchange=\"$('#lastloginform').submit();\"";
    $ddm = getSelectDDM($logins, "lastlogin", "logindate", "loggedin", NULL, $fromdate, NULL, 0, NULL, NULL, $onchange);

    return $ddm;
}

function myEFTs() {
    global $page, $fromdate, $todate;
    global $UTILITY;

    $efttrans = getTransactions($fromdate, $todate);
    echo "<h3>EFT Transactions</h3>\n";
    echo "  <table>\n";
    echo "    <thead>\n";
    echo "      <th>Date</th>\n";
    echo "      <th>Account</th>\n";
    echo "      <th>Description</th>\n";
    echo "      <th>Debit</th>\n";
    echo "      <th>Credit</th>\n";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    if (empty($efttrans)) {
        echo "        <tr><td colspan='5'><p class='warningmsg'>No transactions since your last login.</p></td>\n";
    } else {
        foreach ($efttrans as $t) {
            if ($t['dgrossamount'][0] == '-') {
                $credit = "";
                $debit = $t['dgrossamount'];
            } else {
                $credit = $t['dgrossamount'];
                $debit = "";
            }
            $account = "";
            echo "        <tr>\n";
            echo "          <td data-label='Date' class='date'>".$t['day']."</td>\n";
            if (!empty($t['refaccountid'])) {
                $username=$UTILITY->getUserName($t['refaccountid']);
            } else {
                $username = "";
            }
            echo "          <td data-label='Account'>".$username."</td>\n";
            if ($t['offerid']) {
                echo "          <td data-label='Description'><a href='offer.php?offerid=".$t['offerid']."' target=_blank>".$t['transdesc']."(#".$t['offerid'].")</a></td>\n";
            } else {
                echo "          <td data-label='Description'>".$t['transdesc']."</td>\n";
            }
            echo "          <td data-label='Debit' class='debit number'>".$debit."</td>\n";
            echo "          <td data-label='Credit' class='number'>".$credit."</td>\n";
            echo  "        </tr>\n";
        }
    }
    echo "    </tbody>\n";
    echo "  </table>\n";

}

function myOffers() {
    global $page, $fromdate, $todate;

    $offers = getOffers($fromdate, $todate);
    echo "<h3>Offer Activity</h3>\n";
    echo "<table>\n";
    echo "  <thead>\n";
    echo "    <tr>\n";
    echo "      <th>OID</th>\n";
    echo "      <th>Dealer</th>\n";
    echo "      <th>Created / Modified</th>\n";
    echo "      <th>Status</th></tr>";
    echo "  </thead>\n";
    echo "  <tbody>\n";
    if (empty($offers)) {
        echo "        <tr><td colspan='5'><p class='warningmsg'>No offer changes since your last login.</p></td>\n";
    } else {
        foreach ($offers as $o) {
            $dealerDirection = ($o['fromme']) ? "to" : "from";
            echo "    <tr>\n";
            echo "      <td data-label='OID' class='number'>".$o['offerid']."</td>\n";
            $url  = "offer.php?offerid=".$o['offerid'];
            $link = "<a href='".$url."' target='_blank'>".$dealerDirection." ".$o['dealername']." (".$o['transactiontype'].")</a>";
            echo "      <td data-label='Dealer'>".$link."</td>\n";
            echo "      <td data-label='Created' class='date'>".$o['modifieddt']."</td>\n";
            echo "      <td data-label='Status'>".$o['offerstatus']."</td>";
            echo "    </tr>\n";
        }
    }
    echo "  </tbody>\n";
    echo "</table>\n";

}

function myMessages($pagenum, $perpage) {
    global $page, $hasreplyneeded, $fromdate, $todate;

    $totalRows = 0;
    $includestatus = "'".READSTATUS."', '".UNREADSTATUS."'";
    $messages = $page->iMessage->getMessagesNoBody($includestatus, $pagenum, $perpage, null, $fromdate, $todate);
    echo "<h3>My Messages</h3>\n";
    echo "  <table>\n";
    echo "    <thead>\n";
    echo "      <tr>\n";
    echo "        <th scope='col' colspan='2'>ID</th>\n";
    echo "        <th scope='col'>From</th>\n";
    echo "        <th scope='col'>Subject</th>\n";
    echo "        <th scope='col'>Sent / Read</th>\n";
    echo "      </tr>";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    if (empty($messages)) {
        echo "      <tr><td colspan='5'><p class='warningmsg'>No messages received since your last login.</p></td></tr>\n";
    } else {
        foreach($messages as $m) {
            $unreadclass = ($m["status"] == UNREADSTATUS  ||
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
            $url = "readmessage.php?return=inbox&messageId=".$m['messageid'];
            $link = "<a href='".$url."' target='_blank'>".stripslashes($subject)."</a>";

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
    echo "    </tbody>\n";
    echo "    <tfoot>\n";
    echo "      <tr>\n";
    echo "        <td colspan='4'>\n";
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

}

function getTransactions($fromdate, $todate) {
    global $page;

    $sql = "
        SELECT inttommyy(transdate) AS date, inttommddyyyy_slash(transdate) AS day, transtype, dgrossamount, transdesc, refaccountid, crossrefid, offerid
          FROM transactions
         WHERE useraccountid = ".$page->user->userId."
           AND transdate BETWEEN ".$fromdate." AND ".$todate."
           AND transstatus = 'ACCEPTED'
         ORDER BY transdate DESC
    ";

//  echo "<pre>".$sql."</pre>\n";
    $data = $page->db->sql_query_params($sql);

    return $data;
}

function getOffers($fromdate, $todate) {
    global $page;

    $sql = "
        SELECT ofr.offerid, ofr.offerfrom, ofr.offerto, ofr.transactiontype, ofr.offerstatus, ofr.threadid, ofr.countered,
               CASE WHEN ofr.offerfrom = ".$page->user->userId." THEN 1 ELSE 0 END                      as fromme,
               CASE WHEN ofr.offerfrom = ".$page->user->userId." THEN ut.username ELSE uf.username END  as dealername,
               CASE WHEN ofr.offerfrom = ".$page->user->userId." THEN ut.userid ELSE uf.userid END      as dealerid,
               to_char(to_timestamp(ofr.offerexpiration),'MM/DD/YYYY HH24:MI:SS')                       as expiresat,
               to_char(to_timestamp(ofr.createdate),'MM/DD/YYYY HH24:MI:SS')                            as createdt,
               to_char(to_timestamp(ofr.modifydate),'MM/DD/YYYY HH24:MI:SS')                            as modifieddt,
               to_char(to_timestamp(ofr.completedon),'MM/DD/YYYY HH24:MI:SS')                           as completedat,
               to_char(to_timestamp(ofr.completedon),'MM/DD/YYYY')                                      as assistuntil
          FROM offers   ofr
          JOIN users    uf  on  uf.userid   = ofr.offerfrom
          JOIN users    ut  on  ut.userid   = ofr.offerto
         WHERE (ofr.offerfrom   = ".$page->user->userId."
                OR ofr.offerto  = ".$page->user->userId.")
           AND ofr.modifydate   >= ".$fromdate."
           AND ofr.modifydate   < ".$todate."
        ORDER BY ofr.threadid DESC, ofr.offerid DESC
    ";

//  echo "<pre>".$sql."</pre>\n";
    $offers = $page->db->sql_query($sql);

    return $offers;
}
?>