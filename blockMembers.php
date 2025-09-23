<?php
require_once('template.class.php');

$page = new template(LOGIN, SHOWMSG);
$page->requireJS('scripts/multiSelectJS.php');
//$page->requireJS('scripts/multiSelectAssignedJS.php?idnameB=bthat&selectAssigned=selectOne');

//$ = optional_param('', NULL, PARAM_INT);
$blocked        = optional_param('blocked', NULL, PARAM_TEXT);
$blockUsers     = optional_param('blockUsers', NULL, PARAM_TEXT);

if (isset($blockUsers)) {
    updateBlockedMembers($blocked);
}
//var_dump($blocked);

/*
//TODO
//Put multiselect in UI
//get $blocked from form
$block = implode(',' , $blocked);
$block;//store in database
$blocked = explode(',' , $block);//from database  foreach echo with utility getdealername in selected box
//check the array $blocked for the value to block or unblock in listings and email
if(in_array('16', $blocked)) {
    echo "<br />Block";
} else {
    echo "<br />Unblocked";
}

userid blocking this user
delete and insert on update
*/

echo $page->header('Block Members');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $UTILITY, $blocked;

    $dealers = getUnblockedMembers();
    $blockedMembers = getBlockedMembers($page->user->userId);

    echo "<form name ='blockers' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "<table>\n";
    echo "  <thead>\n";
    echo "    <tr>\n";
    echo "      <th></th>\n";
    echo "      <th></th>\n";
    echo "    </tr>\n";
    echo "  </thead>\n";
    echo "  <tbody>\n";

    echo "    <tr>\n";
    echo "      <td colspan='2'>\n";
    echo          multiSelect($dealers, $blockedMembers, "unblocked", "blocked")."\n";
    echo "      <td>\n";
    echo "    </tr>\n";

    echo "    <tr>\n";
    echo "      <td colspan='2'>\n";
    echo "        <input type='submit' name='blockUsers' id='blockUsers' value='Block Members' onclick=\"javascript: selectAssigned(blocked);\">\n";
    echo "        </select>\n";
    echo "      <td>\n";
    echo "    </tr>\n";


    echo "  </tbody>\n";
    echo "</table>\n";
    echo "</form>\n";

}

function multiSelect($dataA, $dataB, $aname, $bname) {

    $output = getMultiSelectBoxes($dataA, $dataB, $aname, $bname, "userid", "username", "BLOCK MEMBERS", "UNBLOCKED", "BLOCKED", NULL, NULL, NULL, NULL, "multi-select", "width: 10em;")."\n";

    return $output;
}

function updateBlockedMembers($blocked) {
    global $page;

    $sql = "
        DELETE FROM blockedmembers WHERE userid = ".$page->user->userId;
    $page->db->sql_execute_params($sql);

    $params = array();
    $params['userid']           = $page->user->userId;
    $params['createdby']        = $page->user->userId;

    foreach ($blocked as $block) {
        echo $block;
         $sql = "
        INSERT INTO blockedmembers( userid,  blockeduserid,  createdby)
                            VALUES(:userid, :blockeduserid, :createdby)
        ";
        $params['blockeduserid'] = $block;

        $page->queries->AddQuery($sql, $params);
    }

    if ($page->queries->HasQueries()) {
        $process = $page->queries->ProcessQueries();
    }
    if ($process == TRUE) {
        $success = TRUE;
        $page->messages->addSuccessMsg('You have updated blocked members');
    } else {
        $success = FALSE;
        $page->messages->addErrorMsg('Error?');

    }

}

function getBlockedMembers($userId) {
    global $page;

    $sql = "
        SELECT b.blockeduserid AS userid, u.username
          FROM blockedmembers b
          JOIN users u ON u.userid = b.blockeduserid
         WHERE b.userid = ".$page->user->userId."
         ORDER BY u.username
    ";

    $info = $page->db->sql_query_params($sql);

    return $info;

}

function getUnblockedMembers() {
    global $page;

    $sql = "
        SELECT u.username, u.userid
          FROM users u
          LEFT OUTER JOIN blockedmembers b ON (u.userid = b.blockeduserid AND b.userid = ".$page->user->userId.")
          WHERE b.blockeduserid IS NULL
          ORDER BY u.username
    ";

    $info = $page->db->sql_query_params($sql);

    return $info;
}

?>