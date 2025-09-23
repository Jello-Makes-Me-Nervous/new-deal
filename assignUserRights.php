<?php
require_once('templateAdmin.class.php');

$page = new templateAdmin(LOGIN, SHOWMSG);

$editRights     = optional_param('editRights', NULL, PARAM_INT);
$userId         = optional_param('userId', NULL, PARAM_INT);
$updateRights   = optional_param('updateRights', NULL, PARAM_INT);
$userrightname  = optional_param('userrightname', NULL, PARAM_INT);

if (isset($updateRights)) {
    insertRights($userrightname, $userId);
}

echo $page->header('Assign User Rights');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $editRights, $messages, $page, $userRightId, $userRightName, $userId ;

    echo $page->utility->getDealersName($userId);
    if ($editRights == 1) {
        $disabled = NULL;
        echo "<div style='float: right; margin: 10px 500px 20px 5px;'><a class='button' href='' onclick=\"javascript:document.suba.submit();return false;\">Save</a>\n";
        echo "  <a class='button' href='?editRights=0&userId=".$userId."'/>Cancel</a></div>\n";
    } else {
        $disabled = "disabled";
        echo "             <div style='float: right; margin: 10px 500px 20px 5px;'><a class='button' href='?editRights=1&userId=".$userId."'> Edit Rights</a></div> \n";
    }
    echo "<form id='sub' name ='suba' action='" . htmlentities($_SERVER['PHP_SELF']) . "' method='post'>\n";
    echo "  <div style='float: right;padding-right: 50;'>\n";
    echo "      <input type='hidden' name='updateRights' value='1' >\n";
    echo "      <input type='hidden' name='userId' value='" . $userId . "' >\n";
    echo "  </div>\n";
    echo "  ".checkBoxes($userId, $disabled)."\n";
    echo "</form>\n";
}

function checkBoxes($userId, $disabled) {
    global $page;

    $data = getCheckBox(getUserRights($userId), 'userrightname', "userrightid", "userrightname", "assignedrightsid", NULL, 2, NULL, NULL, $disabled);

    return $data;
}

function getUserRights($userId) {
    global $page;
    global $USER;

    $sql = "
        SELECT  ur.userrightid, ur.userrightname, ur.description,
                ar.assignedrightsid
          FROM  userrights ur
          LEFT JOIN assignedrights ar  ON     ar.userrightid   = ur.userrightid
                                      AND     ar.userid        = ".$userId."
         ORDER BY ur.sortorder
        ";

    $data = $page->db->sql_query_params($sql);

    return $data;
}

function insertRights($userrightname, $userId) {
    global $page;
    global $USER;

    $page->queries = new DBQueries("",$messages);
    $sql = "
        DELETE FROM assignedrights WHERE userid = :userid
    ";
    $params = array();
    $params['userid'] = $userId;
    $page->queries->AddQuery($sql, $params);

    $sql = "
        INSERT INTO assignedrights( userid,  userrightid,  createdby)
                            VALUES(:userid, :userrightid, :createdby)
   ";
   $hasElite = false;
   if (!empty($userrightname)) {
        foreach ($userrightname as $userRight) {
            if ($userRight == USERRIGHT_ELITE) {
                $hasElite = true;
            }
            $params = array();
            $params['userid'] = $userId;
            $params['userrightid'] = $userRight;
            $params['createdby'] = $userId;
            $page->queries->AddQuery($sql, $params, "add");
            unset($params);
        }
    }
    unset($sql);
    if ($hasElite) {
        $sql = "INSERT INTO preferredpayment (userid, paymenttypeid, transactiontype, createdby, modifiedby)
            SELECT u.userid, 1::bigint AS paymenttypeid, 'Wanted' AS transactiontype, 'AdminX' AS createdby, 'AdminX' AS modifiedby 
            FROM users u
            LEFT JOIN preferredpayment pp ON pp.userid=u.userid AND pp.paymenttypeid=1 AND pp.transactiontype='Wanted'
            WHERE u.userid=".$userId." AND pp.preferredpaymentid IS NULL";
        $params = array();
        $page->queries->AddQuery($sql, $params, "elite EFT Wanted");

        $sql = "INSERT INTO preferredpayment (userid, paymenttypeid, transactiontype, createdby, modifiedby)
            SELECT u.userid, 1::bigint AS paymenttypeid, 'For Sale' AS transactiontype, 'AdminX' AS createdby, 'AdminX' AS modifiedby 
            FROM users u
            LEFT JOIN preferredpayment pp ON pp.userid=u.userid AND pp.paymenttypeid=1 AND pp.transactiontype='For Sale'
            WHERE u.userid=".$userId." AND pp.preferredpaymentid IS NULL";
        $params = array();
        $page->queries->AddQuery($sql, $params, "elite EFT For Sale");
    }

    if ($page->queries->HasQueries()) {
        $page->queries->ProcessQueries();
    }
    if ($page->queries->ProcessQueries() == TRUE) {
        $page->messages->addSuccessMsg('You have updated '.$page->utility->getDealersName($userId).' Rights');
    } else {
        $page->messages->addErrorMsg('Error?');
    }

}

?>