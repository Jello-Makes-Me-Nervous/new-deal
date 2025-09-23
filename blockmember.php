<?php
require_once('templateCommon.class.php');

$page = new templateCommon(NOLOGIN, SHOWMSG, REDIRECTSAFE);

$blockedmembers = optional_param('blockedmembers', NULL, PARAM_RAW);
$submitbtn      = optional_param('submitbtn', NULL, PARAM_RAW);

if (!empty($submitbtn)) {
    if (empty($blockedmembers)) {
        $blocked = "0";
    } else {
        $blocked = implode(",", $blockedmembers);
    }
    updateBlockedMembers($blocked, $blockedmembers);
}

$unblocked  = getUnblockedMembers();
$blocked    = getBlockedMembers();
$page->messages->addWarningMsg("When there are a lot of unblocked members, the moving of a member from the blocked to the unblocked is slower as we need to sort a large list of unblocked members.");

echo $page->header('Block Member');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $CFG, $unblocked, $blocked;

    echo "<h3>Block Members</h3>\n";
    echo "<form name='blockmember' id='blockmember' action='blockmember.php' method='post' onsubmit='selectBlocked();'>\n";
    echo "  <table>\n";
    echo "    <caption>\n";
    echo "      <p><b>Click on the member's name to move from unblocked to blocked and vice versa<b></p>\n";
    echo "    </caption>\n";
    echo "    <tbody>\n";
    echo "      <tr>\n";
    echo "        <td style='width:100px;background-color:#CCC;font-weight:bold;'>Unblocked Members</td>\n";
    echo "        <td style='width: 300px;'>\n";
    $onclick = "javascript: var op = new Option(this.options[this.selectedIndex].text, this.value); var oA = document.getElementById(\"blockedmembers\"); oA.add(op); sortSelectByValue(oA, this.value); this.remove(this.selectedIndex);";
    echo "          <select multiple name='unblockedmembers[]' id='unblockedmembers' size='25' onclick='".$onclick."' style='width: 300px;'>\n";
    if (!empty($unblocked)) {
        foreach($unblocked as $ub) {
            echo "            <option value='".$ub["userid"]."'>".$ub["username"]."</option>\n";
        }
    }
    echo "          </select>\n";
    echo "        </td>\n";
    echo "        <td style='width:50px;text-align:center;vertical-align:middle;'>\n";
    echo "          <img src='/images/switch.png' height='21' width='21' >\n";
    echo "        </td>\n";
    echo "        <td style='width: 300px;'>\n";
    $onclick = "javascript: var op = new Option(this.options[this.selectedIndex].text, this.value); var oUn = document.getElementById(\"unblockedmembers\"); oUn.add(op); sortSelectByValue(oUn, this.value); this.remove(this.selectedIndex);";
    echo "          <select multiple name='blockedmembers[]' id='blockedmembers' size='25' onclick='".$onclick."' style='width: 300px;'>\n";
    if (!empty($blocked)) {
        foreach($blocked as $b) {
            echo "            <option value='".$b["userid"]."'>".$b["username"]."</option>\n";
        }
    }
    echo "          </select>\n";
    echo "        </td>\n";
    echo "        <td style='width:100px;background-color:#CCC;font-weight:bold;'>Blocked Members</td>\n";
    echo "      </tr>\n";
    echo "    </tbody>\n";
    echo "  </table>\n";
    echo "  <br/>\n";
    echo "  <p><input type='submit' value='submit' id='submitbtn' name='submitbtn'></p>\n";
    echo "</form>\n";

    $js  = "<script language=javascript>\n";
    $js .= "  function sortSelectByValue(selElem, selectedValue) {\n";
    $js .= "    var tmpAry = new Array();\n";
    $js .= "    for (var i=0;i<selElem.options.length;i++) {\n";
    $js .= "      tmpAry[i] = new Array();\n";
    $js .= "      tmpAry[i][0] = selElem.options[i].value;\n";
    $js .= "      tmpAry[i][1] = selElem.options[i].text;\n";
    $js .= "    }\n";
    $js .= "    tmpAry.sort(sortFunction);\n";
    $js .= "    while (selElem.options.length > 0) {\n";
    $js .= "      selElem.options[0] = null;\n";
    $js .= "    }\n";
    $js .= "    for (var i=0;i<tmpAry.length;i++) {\n";
    $js .= "      var op = new Option(tmpAry[i][1], tmpAry[i][0]);\n";
    $js .= "      selElem.options[i] = op;\n";
    $js .= "      selElem.selectedIndex = -1;\n";
    $js .= "    }\n";
    $js .= "    return;\n";
    $js .= "  }\n";
    $js .= "\n";
    $js .= "  function selectBlocked() {\n";
    $js .= "    for (var i=0;i<blockedmembers.options.length;i++) {\n";
    $js .= "      blockedmembers.options[i].selected = 'selected';\n";
    $js .= "    }\n";
    $js .= "  }\n";    $js .= "\n";
    $js .= "  function sortFunction(a, b) {\n";
    $js .= "    if (a[1] === b[1]) {\n";
    $js .= "      return 0;\n";
    $js .= "    } else {\n";
    $js .= "      return (a[1] < b[1]) ? -1 : 1;\n";
    $js .= "    }\n";
    $js .= "  }\n";
    $js .= "</script>\n";

    echo $js;
}

function getUnblockedMembers() {
    global $page;

    $sql = "
        SELECT u.userid, u.username
          FROM users                u
          JOIN userinfo             ui  ON  ui.userid       = u.userid
                                        AND ui.userclassid <> 1 -- new
          JOIN assignedrights       ar  ON  ar.userid       = u.userid
                                        AND ar.userrightid  = 1
          LEFT JOIN blockedmembers  b   ON  b.blockeduserid = u.userid
                                        AND b.userid        = ".$page->user->userId."
         WHERE b.blockeduserid IS NULL
        ORDER BY u.username
    ";

//    echo "<pre>".$sql."</pre>";
    $result = null;
    try {
        $result = $page->db->sql_query_params($sql);
    } catch (Exception $e) {
        $page->messages->addErrorMsg("Error: ".$e->getMessage()." [Retrieving unblocked members]");
    } finally {
    }

    return $result;
}

function getBlockedMembers() {
    global $page;

    $sql = "
        SELECT u.userid, u.username
          FROM users                u
          JOIN userinfo             ui  ON  ui.userid       = u.userid
                                        AND ui.userclassid <> 1 -- new
          JOIN assignedrights       ar  ON  ar.userid       = u.userid
                                        AND ar.userrightid  = 1
          JOIN blockedmembers       b   ON  b.blockeduserid = u.userid
                                        AND b.userid        = ".$page->user->userId."
        ORDER BY u.username
    ";

    $result = null;
    try {
        $result = $page->db->sql_query_params($sql);
    } catch (Exception $e) {
        $page->messages->addErrorMsg("Error: ".$e->getMessage()." [Retrieving blocked members]");
    } finally {
    }

    return $result;
}

function updateBlockedMembers($blocked, $blockedmembers) {
    global $page;

    $sql = "
        DELETE FROM blockedmembers
         WHERE userid = ".$page->user->userId."
           AND blockeduserid NOT IN (".$blocked.")
    ";
    $page->queries->AddQuery($sql);
    if (!empty($blockedmembers)) {
        $sql = "
            INSERT INTO blockedmembers(userid, blockeduserid, createdby)
            SELECT u.userid, bu.userid, :createdby
              FROM users                u
              JOIN users                bu  ON  bu.userid           = :blockeduserid
              LEFT JOIN blockedmembers  bm  ON  bm.userid           = u.userid
                                            AND bm.blockeduserid    = bu.userid
             WHERE u.userid = :userid
               AND bm.userid IS NULL
        ";

        foreach ($blockedmembers as $bmid) {
            $params = array();
            $params["userid"]           = $page->user->userId;
            $params["blockeduserid"]    = $bmid;
            $params["createdby"]        = $page->user->username;

            $page->queries->AddQuery($sql, $params);
            unset($params);
        }
    }

    try {
        if ($page->queries->HasQueries()) {
            if ($page->queries->ProcessQueries()) {
                $page->messages->addSuccessMsg("Blocked members updated.");
            }
        }
    } catch (Exception $e) {
        $page->messages->addErrorMsg("ERROR: ".$e->getMessage()." [Unable to update blocked members.]");
        $messageId = 0;
    } finally {
    }
}
?>