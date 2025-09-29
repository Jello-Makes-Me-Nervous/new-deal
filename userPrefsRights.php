<?php
require_once('template.class.php');

$page = new template(LOGIN, SHOWMSG);

$messages = new Messages();

$editPreference     = optional_param('editPreference', NULL, PARAM_INT);
$preferenceId       = optional_param_array('preferenceid', NULL, PARAM_INT);
$preferenceName     = optional_param('preferencename', NULL, PARAM_INT);
$updatePreference   = optional_param('updatePreference', NULL, PARAM_INT);

$editRights           = optional_param('editRights', NULL, PARAM_INT);
$userRightId    = optional_param_array('userRightId', NULL, PARAM_INT);
$updateRights         = optional_param('updateRights', NULL, PARAM_INT);
$userrightname  = optional_param('userrightname', NULL, PARAM_INT);

$userId             = optional_param('userId', NULL, PARAM_INT);


if (isset($updatePreference)) {
    insertPreferences($userId, $preferenceName);
}

if (isset($updateRights)) {
    insertRights($userId, $userrightname);
}


echo $page->header('Assign User Preferences Rights');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $UTILITY, $editPreference, $messages, $preferenceId, $preferenceName, $userId, $editRights, $messages, $userRightId, $userRightName;

    echo "<h3>".$UTILITY->getUserName($userId)."</h3>\n";

    echo "<table>\n";
    echo "  <thead>\n";
    echo "    <tr>\n";
    echo "      <th>\n";
    echo "        <h3>Preferences</h3>\n";
    if (isset($editPreference)) {
        $disabled = NULL;
        echo "        <a href='' onclick=\"javascript:document.suba.submit();return false;\">Save</a>&nbsp - &nbsp\n";
        echo "        <a href='".htmlentities($_SERVER['PHP_SELF'])."?userId=".$userId."'/>Cancel</a>\n";
    } else {
        $disabled = "disabled";
        echo "        <a href='?editPreference=1&userId=".$userId."'> Edit Preferences</a> \n";
    }
    echo "      </th>\n";
    echo "    </tr>\n";
    echo "  </thead>\n";
    echo "<form id='suba' name ='suba' action='" . htmlentities($_SERVER['PHP_SELF']) . "' method='post'>\n";
    echo "    <tr>\n";
    echo "      <td>\n";
    echo "         <input type='hidden' name='updatePreference' value='1' >\n";
    echo "        <input type='hidden' name='userId' value='" . $userId . "' >\n";
    echo "          ".checkData($userId, $disabled)."\n";
    echo "          ".radioData($userId, $disabled)."\n";
    echo "    </td>\n";
    echo "  </tr>\n";
    echo "</table>\n";
    echo "</form>\n";

    echo "<table>\n";
    echo "  <thead>\n";
    echo "    <tr>\n";
    echo "      <th>\n";
    echo "        <h3>Rights</h3>\n";
    if (isset($editRights)) {
        $disabled = NULL;
        echo "        <a class='fa-edit'  href='' onclick='javascript:document.subb.submit();return false;'>Save</a>&nbsp - &nbsp\n";
        echo "        <a href='".htmlentities($_SERVER['PHP_SELF'])."?userId=".$userId."'/>Cancel</a>\n";
    } else {
        $disabled = "disabled";
        echo "        <a href='?editRights=1&userId=".$userId."'> Edit Rights</a> \n";
    }
    echo "      </th>\n";
    echo "    </tr>\n";
    echo "  </thead>\n";
    echo "<form id='subb' name ='subb' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "    <tr>\n";
    echo "      <td>\n";
    echo "          <input type='hidden' name='updateRights' value='1' >\n";
    echo "          <input type='hidden' name='userId' value='" . $userId . "' >\n";
    echo "          ".checkBox($userId, $disabled)."\n";
    echo "    </td>\n";
    echo "  </tr>\n";
    echo "</table>\n";
    echo "</form>\n";

}

function saveeditPreferenceBtns($editPreference) {
    $output = "";

    if ($editPreference == 1) {
        $disabled = NULL;
        $output .= "<a href='' onclick=\"javascript:document.suba.submit();return false;\">Save</a>&nbsp - &nbsp\n";
        $output .= "<a href='?'/>Cancel</a>\n";
    } else {
        $disabled = "disabled";
        $output .= "<a href='?editPreference=1'> editPreference</a> \n";
    }
    return $output;
}

function checkData($userId, $disabled) {

    $data = getCheckBox(getUserPreferences($userId), 'preferencename', "preferenceid", "description", "assignedpreferenceid", "readonly", 1, NULL, NULL, $disabled);

    return $data;
}

 function radioData($userId, $disabled) {

    $data = getRadioButton( getUserPreferencesRadio($userId), 'preferencename', 'grouping', "preferenceid", "description", "radioquestion", "assignedpreferenceid", NULL, 1, NULL, NULL, $disabled);

    return $data;
 }

function getUserPreferences($userId) {
    global $page;

    $sql = "
        SELECT up.preferenceId, up.preferenceName, up.description, up.readonly, up.defaultflag,
               ap.assignedPreferenceId
          FROM userPreferences           up
          LEFT JOIN assignedPreferences   ap  ON  ap.preferenceId   = up.preferenceId
                                         AND      ap.userid         = ".$userId."
         WHERE up.grouping IS NULL
         ORDER BY up.description COLLATE \"POSIX\"
    ";
    $data = $page->db-> sql_query_params($sql);

    return $data;
}

function getUserPreferencesRadio($userId) {
    global $page;

    $sql = "
        SELECT up.preferenceId, up.preferenceName, up.description, up.readonly, up.grouping, up.radioquestion,
               ap.assignedPreferenceId
          FROM userPreferences           up
          LEFT JOIN assignedPreferences   ap  ON  ap.preferenceId   = up.preferenceId
                                         AND      ap.userid         = ".$userId."
         WHERE up.grouping IS NOT NULL
         ORDER BY up.grouping COLLATE \"POSIX\"
    ";
    $data = $page->db-> sql_query_params($sql);

    return $data;
}


function insertPreferences($userId, $preferenceName) {
    global $page;
    global $USER;

    $queries = new DBQueries("",$messages);
    $sql = "
        DELETE FROM assignedpreferences WHERE userid = :userid
    ";
    $params = array();
    $params['userid'] = $userId;
    $queries->AddQuery($sql, $params);

    $sql = "
        INSERT INTO assignedpreferences( userid,  preferenceid,  createdby)
                                 VALUES(:userid, :preferenceid, :createdby)
   ";
   if (!empty($preferenceName)) {
        foreach ($preferenceName as $userPref) {
            $params = array();
            $params['userid']       = $userId;
            $params['preferenceid'] = $userPref;
            $params['createdby']    = $USER->userId;
            $queries->AddQuery($sql, $params, "add");
            unset($params);
        }
    }
    unset($sql);

    if ($queries->HasQueries()) {
        $queries->ProcessQueries();
    }
    if ($queries->ProcessQueries() == TRUE) {
        $page->messages->addSuccessMsg('You have editPreferenceed (user\'s name) Preferences');
    } else {
        $page->messages->addErrorMsg('Error?');
    }

}

function checkBox($userId, $disabled) {

    $data = getCheckBox(getUserRights($userId), 'userrightname', "userrightid", "description", "assignedrightsid", NULL, 2, NULL, NULL, $disabled);

    return $data;
}

function getUserRights($userid) {
    global $page;
    global $USER;

    $sql = "
        SELECT  ur.userRightId, ur.userRightName, ur.description, ur.active,
                ar.assignedRightsId
          FROM  userRights          ur
          LEFT JOIN assignedRights  ar  ON     ar.userRightId   = ur.userRightId
                                       AND     ar.userid        = ".$userid."
         ORDER BY ur.description COLLATE \"POSIX\"
        ";

    $data = $page->db->sql_query_params($sql);

    return $data;
}

function insertRights($userId, $userrightname) {
    global $page;
    global $USER;

    $queries = new DBQueries("",$messages);
    $sql = "
        DELETE FROM assignedrights WHERE userid = :userid
    ";
    $params = array();
    $params['userid'] = $userId;
    $queries->AddQuery($sql, $params);

    $sql = "
        INSERT INTO assignedrights( userid,  userrightid,  createdby)
                            VALUES(:userid, :userrightid, :createdby)
   ";
   if (!empty($userrightname)) {
        foreach ($userrightname as $userRight) {
            $params = array();
            $params['userid'] = $userId;
            $params['userrightid'] = $userRight;
            $params['createdby'] = $USER->userId;
            $queries->AddQuery($sql, $params, "add");
            unset($params);
        }
    }
    unset($sql);

    if ($queries->HasQueries()) {
        $queries->ProcessQueries();
    }
    if ($queries->ProcessQueries() == TRUE) {
        $page->messages->addSuccessMsg('You have updateRightsd '.$USER->username.' Rights');
    } else {
        $page->messages->addErrorMsg('Error?');
    }

}

?>