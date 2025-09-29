<?php
require_once('template.class.php');

$page = new template(LOGIN, SHOWMSG);

$messages = new Messages();

$editPreference     = optional_param('editPreference', NULL, PARAM_INT);
$preferenceId       = optional_param_array('preferenceid', NULL, PARAM_INT);
$preferenceName     = optional_param('preferencename', NULL, PARAM_INT);
$updatePreference   = optional_param('updatePreference', NULL, PARAM_INT);

if (isset($updatePreference)) {
    insertPreferences($preferenceName);
}

echo $page->header('User Preferences');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $UTILITY, $editPreference, $messages, $preferenceId, $preferenceName, $messages;

    echo "<table>\n";
    echo "  <thead>\n";
    echo "    <tr>\n";
    echo "      <th>\n";
    echo "        <h3>Preferences for ".$page->user->username."(".$page->user->userId.")</h3>\n";
    if (isset($editPreference)) {
        $disabled = NULL;
        echo "        <a class='button' href='' onclick=\"javascript:document.suba.submit();return false;\">Save</a>&nbsp - &nbsp\n";
        echo "        <a class='button' href='".htmlentities($_SERVER['PHP_SELF'])."?userId=".$page->user->userId."'/>Cancel</a>\n";
    } else {
        $disabled = "disabled";
        echo "        <a class='button' href='?editPreference=1&userId=".$page->user->userId."'> Edit Preferences</a> \n";
    }
    echo "      <br /><br /></th>\n";
    echo "    </tr>\n";
    echo "  </thead>\n";
    echo "<form id='suba' name ='suba' action='" . htmlentities($_SERVER['PHP_SELF']) . "' method='post'>\n";
    echo "    <tr>\n";
    echo "      <td>\n";
    echo "         <input type='hidden' name='updatePreference' value='1' >\n";
    echo "         <input type='hidden' name='userId' value='" .$page->user->userId. "' >\n";
    echo "         ".checkData($page->user->userId, $disabled)."<br />\n";
    echo "         ".radioData($page->user->userId, $disabled)."<br />\n";
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
    
    $preferenceChecks = getUserPreferences();

    if ($preferenceChecks) {
        $data = getCheckBox($preferenceChecks, 'preferencename', "preferenceid", "description", "assignedpreferenceid", "readonly", 1, NULL, NULL, $disabled);
    } else {
        $data = "No preference checkboxes to configure";
    }

    return $data;
}

function radioData($userId, $disabled) {
    $preferenceRadios = getUserPreferencesRadio();
    
    if ($preferenceRadios) {
        $data = getRadioButton( $preferenceRadios, 'preferencename', 'grouping', "preferenceid", "description", "radioquestion", "assignedpreferenceid", NULL, 1, NULL, NULL, $disabled);
    } else {
        $data = "No preference radios to configure";
    }

    return $data;
}

function getUserPreferences() {
    global $page;

    $sql = "SELECT up.preferenceid, up.preferencename, up.description, up.readonly, up.defaultflg,
               ap.assignedpreferencesid
          FROM userpreferences           up
          LEFT JOIN assignedpreferences   ap  ON  ap.preferenceId   = up.preferenceid
                                         AND      ap.userid         = ".$page->user->userId."
         WHERE up.grouping IS NULL
         ORDER BY up.description COLLATE \"POSIX\"";
    $data = $page->db-> sql_query($sql);

    return $data;
}

function getUserPreferencesRadio() {
    global $page;

    $sql = "SELECT up.preferenceid, up.preferenceName, up.description, up.readonly, up.grouping, up.radioquestion,
               ap.assignedpreferencesid
          FROM userpreferences           up
          LEFT JOIN assignedpreferences   ap  ON  ap.preferenceid   = up.preferenceid
                                         AND      ap.userid         = ".$page->user->userId."
         WHERE up.grouping IS NOT NULL
         ORDER BY up.grouping COLLATE \"POSIX\"";
    $data = $page->db-> sql_query($sql);

    return $data;
}


function insertPreferences($preferenceName) {
    global $page;
    global $USER;

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
        $page->messages->addSuccessMsg('You have edited Preferences');
    } else {
        $page->messages->addErrorMsg('Error?');
    }

}

function checkBox($disabled) {

    $data = getCheckBox(getUserRights($page->user->userId), 'userrightname', "userrightid", "description", "assignedrightsid", NULL, 2, NULL, NULL, $disabled);

    return $data;
}


?>