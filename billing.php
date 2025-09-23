<?php
DEFINE("STALERIGHT",            61);
DEFINE("MEMBERSHIPFEERIGHT",    62);
DEFINE ("NOTES_MAX",  3000);

require_once('templateAdmin.class.php');
require_once('templateBlank.class.php');

$printpreview   = optional_param('print', 0, PARAM_INT);
if (empty($printpreview)) {
    $page = new templateAdmin(LOGIN, SHOWMSG);
} else {
    $page = new templateBlank(LOGIN, SHOWMSG);
}

$dealer         = optional_param('dealer', NULL, PARAM_RAW);
$userclassid    = optional_param('userclassid', 0, PARAM_INT);
$filteramount   = optional_param('filteramount', NULL, PARAM_RAW);
$eftfeesonly    = optional_param('eftfeesonly', 0, PARAM_INT);
$orderbyday     = optional_param('orderbyday', 0, PARAM_INT);
$feeday         = optional_param('feeday', NULL, PARAM_RAW);
$searchbtn      = optional_param("searchbtn", NULL, PARAM_RAW);

$submitbtn      = optional_param("submitbtn", NULL, PARAM_RAW);
$memberids      = optional_param("memberids", NULL, PARAM_RAW);
$export         = optional_param('export', 0, PARAM_INT);


if (!empty($export)) {
    $x = export($dealer, $userclassid, $filteramount, $eftfeesonly, $orderbyday, $feeday);
    if (empty($x)) {
        $page->messages->addErrorMsg("ERROR: Unable to export billing information");
    }
}
if (!empty($submitbtn) && !empty($memberids)) {
    updateMembershipFees($memberids);
}


echo $page->header('EFT Billing Report');
if (empty($printpreview)) {
    echo mainContent();
} else {
    printpreview($dealer, $userclassid, $filteramount, $eftfeesonly, $orderbyday, $feeday);
}
echo $page->footer(true);

function mainContent() {
    global $page, $dealer, $userclassid, $filteramount, $eftfeesonly, $orderbyday, $feeday, $searchbtn, $submitbtn;

    echo "<h3>Billing Report</h3>\n";
    echo "<article>\n";
    echo "  <div class='entry-content'>\n";
    echo "    <form name ='search' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post' target='_self'>\n";
    echo "      <table class='table-condensed'>\n";
    echo "        <thead>\n";
    echo "          <tr>\n";
    echo "            <th>Dealer</th>\n";
    echo "            <th>Class</th>\n";
    echo "            <th>Amount</th>\n";
    echo "            <th>Day</th>\n";
    echo "            <th>Order by day</th>\n";
    echo "            <th>EFT Fees Only</th>\n";
    echo "            <th>&nbsp;</th>\n";
    echo "          </tr>\n";
    echo "          </thead>\n";
    echo "          <tbody>\n";
    echo "          <tr>\n";
    echo "            <td class='center'>\n";
    echo "              <input type='text' name='dealer' id='dealer' size='25' maxlength='100' value='".$dealer."'>\n";
    echo "            </td>\n";
    echo "            <td class='center'>\n";
    echo getUserClassDDM($userclassid);
    echo "            </td>\n";
    echo "            <td class='center'>\n";
    echo "              <input type='text' name='filteramount' id='filteramount' size='5' maxlength='5' value='".$filteramount."'>\n";
    echo "            </td>\n";
    echo "            <td class='center'>\n";
    echo "              <input type='text' name='feeday' id='feeday' size='2' maxlength='2' value='".$feeday."'>\n";
    echo "            </td>\n";
    echo "            <td class='center'>\n";
    $checked = (empty($orderbyday)) ? "" : "checked";
    echo "              <input type='checkbox' name='orderbyday' id='orderbyday' value='1' ".$checked.">\n";
    echo "            </td>\n";
    echo "            <td class='center'>\n";
    $checked = (empty($eftfeesonly)) ? "" : "checked";
    echo "              <input type='checkbox' name='eftfeesonly' id='eftfeesonly' value='1' ".$checked.">\n";
    echo "            </td>\n";
    echo "            <td class='center'>\n";
    echo "              <input type='submit' name='searchbtn' id='searchbtn' value='Search' onclick='document.search.target=\"_self\"; document.search.export.value=\"\"; document.search.print.value=\"\"; '>\n";
    echo "            </td>\n";
    echo "          </tr>\n";
    echo "        </tbody>\n";
    echo "      </table>\n";
    echo "      <input type='hidden' name='export' id='export' value=''>\n";
    echo "      <input type='hidden' name='print' id='print' value=''>\n";
    echo "    </form>\n";

    echo "    <form name ='billing' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    $memberids = "";
    if (!empty($searchbtn) || !empty($submitbtn)) {
        $data = getData($dealer, $userclassid, $filteramount, $eftfeesonly, $orderbyday, $feeday);
        echo "      <table border='1'>\n";
        echo "        <caption>\n";
        echo "          <a href='Javascript: document.search.target=\"_blank\"; document.search.print.value=\"1\"; document.search.submit();'v class='icon print'>Print</a>&nbsp;&nbsp;\n";
        echo "          <a href='Javascript: document.search.target=\"_self\"; document.search.export.value=\"1\"; document.search.submit();' class='icon export'>Export</a>\n";
        echo "        </caption>\n";
        echo "        <thead>\n";
        echo "          <tr>\n";
        echo "            <th>Dealer</th>\n";
        echo "            <th>User Class</th>\n";
        echo "            <th>Assigned Rights</th>\n";
        echo "            <th>Fee Right</th>\n";
        echo "            <th>Amount</th>\n";
        echo "            <th>Notes</th>\n";
        echo "          </tr>\n";
        echo "        </thead>\n";
        echo "        <tbody>\n";
        if (!empty($data)) {
            foreach ($data as $d) {
                $memberids .= (empty($memberids)) ? $d["userid"] : ",".$d["userid"];
                echo "          <tr>\n";
                $url = "/dealerProfile.php?dealerId=".$d["userid"];
                $link = "<a href='".$url."' target='_blank'>".$d["username"]."</a>";
                echo "            <td>".$link."<br>".$d["email"]."<br>".$d["accountcreated"]."</td>\n";
                echo "            <td class='center'>".$d['userclassname']."</td>\n";
                echo "            <td>".$d['assignedrights']."</td>\n";
                echo "            <td class='center'>\n";
                $checked = (empty($d["hasmemberfee"])) ? "" : "checked";
                $name = $d["userid"]."-membershipfee";
                echo "                <input type='checkbox' name='".$name."' id='".$name."' value='1' ".$checked.">\n";
                $value = (empty($d["hasmemberfee"])) ? "0" : "1";
                $name = $d["userid"]."-oldmembershipfee";
                echo "                <input type='hidden' name='".$name."' id='".$name."' value='".$value."'>\n";
                echo "            </td>\n";
                echo "            <td>\n";
                $name = $d["userid"]."-amount";
                echo "              <input type='text' name='".$name."' id='".$name."' size='5' maxlength='10' value='".$d["membershipfee"]."'>\n";
                $name = $d["userid"]."-oldamount";
                echo "              <input type='hidden' name='".$name."' id='".$name."' value='".$d["membershipfee"]."'>\n";
                echo "            </td>\n";
                echo "            <td>\n";
                $name = $d["userid"]."-notes";
                echo "              <textarea name='".$name."' id='".$name."' maxlength='".NOTES_MAX."' style='width: 98%; height: 100px;'>".$d['membershipfee_note']."</textarea>\n";
                $name = $d["userid"]."-oldnotes";
                echo "              <input type='hidden' name='".$name."' id='".$name."' value='".$d['membershipfee_note']."'>\n";
                echo "            </td>\n";
                echo "          </tr>\n";
            }
        } else {
                echo "          <tr><td colspan='6'>No matching records found.</td></tr>\n";
        }
        echo "        </tbody>\n";
        echo "      </table>\n";
        echo "      <p><input type='submit' name='submitbtn' id='submitbtn' value='submit' onclick='document.search.target=\"_self\"; document.search.export.value=\"\"; document.search.print.value=\"\"; '></p>\n";
    }
    echo "      <input type='hidden' name='memberids' id='memberids' value='".$memberids."'>\n";
    echo "      <input type='hidden' name='dealer' id='dealer' value='".$dealer."'>\n";
    echo "      <input type='hidden' name='filteramount' id='filteramount' value='".$filteramount."'>\n";
    echo "      <input type='hidden' name='feeday' id='feeday' value='".$feeday."'>\n";
    echo "      <input type='hidden' name='orderbyday' id='orderbyday' value='".$orderbyday."'>\n";
    echo "      <input type='hidden' name='eftfeesonly' id='eftfeesonly' value='".$eftfeesonly."'>\n";
    echo "    </form>\n";
    echo "  </div>\n";
    echo "</article>\n";
    echo "<br />\n";

}

function getUserClassData() {
    global $page;

    $sql = "
        SELECT userclassid, userclassname
          FROM userclass
        ORDER BY userclassname
    ";

    $result = $page->db->sql_query_params($sql);

    return $result;
}

function getUserClassDDM($selectedid) {

    $data = getUserClassData();

    return getSelectDDM($data, "userclassid", "userclassid", "userclassname", NULL, $selectedid, "Active", 0);
}

function getData($dealername, $userclassid, $filteramount, $eftfeesonly, $orderbyday, $feeday) {
    global $page;

    $dealer     = (empty($dealername)) ? "" : "AND lower(u.username) like  lower('%".$dealername."%')";
    $ucid       = (empty($userclassid)) ? "2,3,4,5" : $userclassid;
    $minfee     = (empty($filteramount)) ? -1 : $filteramount;
    $feeonly    = (empty($eftfeesonly)) ? "" : "AND ar.userid IS NOT NULL";
    $feedayonly = (empty($feeday)) ? "" : "AND EXTRACT(DAY FROM inttodate(ui.accountcreated)::TIMESTAMP) = ".$feeday."";
    $orderby    = (empty($orderbyday)) ? "ORDER BY u.username" : "ORDER BY EXTRACT(DAY FROM inttodate(ui.accountcreated)::TIMESTAMP), u.username";

    $sql = "
        SELECT ui.userid, u.username, uci.email,
               inttommddyyyy_slash(ui.accountcreated) as accountcreated,
               uc.userclassname, ur.assignedrights,
               case when ar.userrightid is null then null
                    else 'X' end  as hasmemberfee,
               ui.membershipfee, ui.membershipfee_note
          FROM users                    u
          JOIN userinfo                 ui  ON  ui.userid           = u.userid
          JOIN userclass                uc  ON  uc.userclassid      = ui.userclassid
          JOIN (
              SELECT x.userid, array_to_string(array_agg(x.userrightname), '<br>') as assignedrights
                FROM (
                    SELECT ar.userid, ur.userrightname
                      FROM assignedrights       ar
                      JOIN userrights           ur  ON  ur.userrightid  = ar.userrightid
                     WHERE ur.userrightid NOT IN (12, 61)
                    ORDER BY ar.userid, ur.userrightname
                    ) x
              GROUP BY x.userid
               )                        ur  ON  ur.userid           = u.userid
          LEFT JOIN usercontactinfo     uci ON  uci.userid           = u.userid
                                            AND uci.addresstypeid    = 1
          LEFT JOIN assignedrights      ar  ON  ar.userid           = u.userid
                                            AND ar.userrightid      = ".MEMBERSHIPFEERIGHT."
         WHERE uc.userclassid IN (".$ucid.")
           AND ui.membershipfee    >= ".$minfee."
           ".$dealer."
           ".$feeonly."
           ".$feedayonly."
        ".$orderby."
    ";

//    echo "<pre>".$sql."</pre>";
    $result = $page->db->sql_query_params($sql);

    return $result;
}

function updateMembershipFees($memberids) {
    global $page;

    $updateMembers = array();
    $aMemberIds = explode(",", $memberids);
    foreach($aMemberIds as $id) {
        $name = $id."-membershipfee";
        $oldname = $id."-oldmembershipfee";
        $feeright       = optional_param($name, 0, PARAM_INT);
        $oldfeeright    = optional_param($oldname, 0, PARAM_INT);
        if ($feeright <> $oldfeeright) {
            $exists = $page->db->get_field_query("SELECT userid FROM assignedrights WHERE userid = ".$id." AND userrightid = ".MEMBERSHIPFEERIGHT);
            $params = array();
            $params["userid"]       = $id;
            if ($feeright == 1) {
                if (!$exists) {
                    $sql = "
                        INSERT INTO assignedrights (userid, userrightid, createdby)
                        VALUES (:userid, ".MEMBERSHIPFEERIGHT.", :createdby)
                    ";
                    $params["createdby"]    = $page->user->username;

                    $page->queries->AddQuery($sql, $params);
                }
            } else {
                $sql = "
                    DELETE FROM assignedrights
                     WHERE userrightid = ".MEMBERSHIPFEERIGHT."
                       AND userid = :userid
                ";
                $page->queries->AddQuery($sql, $params);
            }
        }

        $name = $id."-amount";
        $oldname = $id."-oldamount";
        $amount       = optional_param($name, 0, PARAM_RAW);
        $oldamount    = optional_param($oldname, 0, PARAM_RAW);
        $name = $id."-notes";
        $oldname = $id."-oldnotes";
        $notes        = optional_param($name, NULL, PARAM_TEXT);
        $oldnotes     = optional_param($oldname, NULL, PARAM_TEXT);
        if (($amount <> $oldamount) ||
            ($notes <> $oldnotes)){
            $params = array();
            $params["userid"]               = $id;
            $params["membershipfee"]        = $amount;
            $params["membershipfee_note"]   = $notes;
            $params["modifiedby"]           = $page->user->username;

            $sql = "
                UPDATE userinfo
                   SET membershipfee        = :membershipfee,
                       membershipfee_note   = :membershipfee_note,
                       modifiedby           = :modifiedby,
                       modifydate           = nowtoint()
                 WHERE userid = :userid
            ";

            $page->queries->AddQuery($sql, $params);
        }
        unset($params);
    }

//    foreach($page->queries->sqls as $idx => $sql) {
//        echo "<pre>".$sql."</pre>";
//        echo "<pre>"; print_r($page->queries->params[$idx]); echo "</pre>";
//    }

    if ($page->queries->HasQueries()) {
        try {
            $page->db->sql_begin_trans();
            $page->queries->ProcessQueries();
            $page->messages->addSuccessMsg("Membership fees updated.");
            $page->db->sql_commit_trans();
        } catch (Exception $e) {
            $page->db->sql_rollback_trans();
            $page->messages->addErrorMsg("ERROR: ".$e->getMessage()." [Unable to membership fees.]");
        } finally {
        }
    }
}

function export($dealername, $userclassid, $filteramount, $eftfeesonly, $orderbyday, $feeday) {
    global $page;

    $data = getData($dealername, $userclassid, $filteramount, $eftfeesonly, $orderbyday, $feeday);
    $filename = "";
    if (!empty($data)) {
        $filename = date('Ymd_His')."_billingreport.csv";
        $page->utility->export($data, $filename);
    }

    return $filename;
}

function printpreview($dealername, $userclassid, $filteramount, $eftfeesonly, $orderbyday, $feeday) {
    global $page;

    echo "<h3>Blling Report</h3>\n";
    $data = getData($dealername, $userclassid, $filteramount, $eftfeesonly, $orderbyday, $feeday);
    if (!empty($data)) {
        $page->utility->printpreview($data);
    }
}

?>