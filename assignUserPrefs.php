<?php
require_once('templateCommon.class.php');

$page = new templateCommon(LOGIN, SHOWMSG);

$messages = new Messages();

$editPreference     = optional_param('editPreference', NULL, PARAM_INT);
$preferenceId       = optional_param_array('preferenceid', NULL, PARAM_INT);
$preferenceName     = optional_param('preferencename', NULL, PARAM_INT);
$updatePreference   = optional_param('updatePreference', NULL, PARAM_INT);
$userId             = optional_param('userId', NULL, PARAM_INT);


if (isset($updatePreference)) {
    insertPreferences($userId, $preferenceName);
}

echo $page->header('Assign User Preferences');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $editPreference, $messages, $preferenceId, $preferenceName, $userId;

    echo $messages->displayMessages();

    if ($editPreference == 1) {
        $disabled = NULL;
        echo "<a href='' onclick=\"javascript:document.suba.submit();return false;\">Save</a>&nbsp - &nbsp\n";
        echo "<a href='?'/>Cancel</a>\n";
    } else {
        $disabled = "disabled";
        echo "             <a href='?editPreference=1'> Edit Preferences</a> \n";
    }
    echo "<form id='sub' name ='suba' action='" . htmlentities($_SERVER['PHP_SELF']) . "' method='post'>\n";
    echo "  <div style='float: right;padding-right: 50;'>\n";
    echo "      <input type='hidden' name='updatePreference' value='1' >\n";
    echo "      <input type='hidden' name='userId' value='" . $userId . "' >\n";
    echo "  </div>\n";
    checkData($userId, $disabled);
    radioData($userId, $disabled);
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

function checkData($disabled) {
    global $page;

    $data = getCheckBox(getUserPreferences($page->user->userId), 'preferencename', "preferenceid", "description", "assignedpreferenceid", "readonly", 1, NULL, NULL, $disabled);

    return $data;
}

 function radioData($disabled) {
    global $page;

    $data = getRadioButton( getUserPreferencesRadio($page->user->userId), 'preferencename', 'grouping', "preferenceid", "description", "radioquestion", "assignedpreferenceid", NULL, 1, NULL, NULL, $disabled);

    return $data;
 }

function getUserPreferences() {
    global $page;

    $sql = "
        SELECT up.preferenceId, up.preferenceName, up.description, up.readonly, up.defaultflg,
               ap.assignedPreferencesId
          FROM userPreferences up
          LEFT JOIN assignedPreferences ap  ON  ap.preferenceId     = up.preferenceId
                                           AND  ap.userid           = ".$page->user->userId."
         WHERE up.grouping IS NULL
         ORDER BY up.description
    ";
    $data = $page->db-> sql_query_params($sql);

    return $data;
}

function getUserPreferencesRadio() {
    global $page;

    $sql = "
        SELECT up.preferenceId, up.preferenceName, up.description, up.readonly, up.grouping, up.radioquestion,
               ap.assignedPreferencesId
          FROM userPreferences up
          LEFT JOIN assignedPreferences ap ON  ap.preferenceId     = up.preferenceId
                                          AND  ap.userid           = ".$page->user->userId."
         WHERE up.grouping IS NOT NULL
         ORDER BY up.grouping
    ";
    $data = $page->db-> sql_query_params($sql);

    return $data;
}


function insertPreferences($preferenceName) {
    global $page;

    $queries = new DBQueries("",$messages);
    $sql = "
        DELETE FROM assignedpreferences WHERE userid = :userid
    ";
    $params = array();
    $params['userid'] = $page->user->userId;
    $queries->AddQuery($sql, $params);

    $sql = "
        INSERT INTO assignedpreferences( userid,  preferenceid,  createdby)
                                 VALUES(:userid, :preferenceid, :createdby)
   ";
   if (!empty($preferenceName)) {
        foreach ($preferenceName as $userPref) {
            $params = array();
            $params['userid']       = $page->user->userId;
            $params['preferenceid'] = $userPref;
            $params['createdby']    = $page->user->userId;
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

?>