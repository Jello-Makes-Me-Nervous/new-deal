<?php
require_once('template.class.php');
DEFINE("CHECKBOX",          "checkbox");
DEFINE("CHECKBOXPREFIX",    "cb_");
DEFINE("RADIO",             "radio");
DEFINE("RADIOPREFIX",       "r_");
DEFINE("TEXTBOX",           "text");
DEFINE("TEXTBOXPREFIX",     "tb_");

$page = new template(LOGIN, SHOWMSG, REDIRECTSAFE);
$pageTitle = "User Preferences";

$dealerid = null;

/****************************************************
 * Get form parameters
 ****************************************************/
$preferences = paramInit();

/****************************************************
 * Process form submission
 ****************************************************/
if (!empty($preferences)) {
    updateAssignedPreferences($preferences);
}


echo $page->header($pageTitle);
echo mainContent();
echo $page->footer(true);


function paramInit() {
    global $page, $dealerid;

    if ($page->user->isStaff()) {
        $dealerid   = optional_param('dealerId', NULL, PARAM_INT);
        $dealerid   = (empty($dealerid)) ? $page->user->userId : $dealerid;
    } else {
        $dealerid   = $page->user->userId;
    }

    $preferences = array();
    $submitbtn      = optional_param("submitbtn", NULL, PARAM_RAW);
    if (!empty($submitbtn)) {
        /***
         * each input type will have its own prefix and retrieved their special way
         * cb_ prefix = checkboxes
         ***/
        $prefix = CHECKBOXPREFIX;
        $checkboxids    = optional_param("checkboxids", NULL, PARAM_RAW);
        $cbids = explode(",", $checkboxids);
        foreach($cbids as $id) {
            $preferences[$id]["type"]           = CHECKBOX;
            $preferences[$id]["userid"]         = $dealerid;
            $preferences[$id]["preferenceid"]   = $id;
            $paramname = $prefix."preferenceid_".$id;
            $preferences[$id]["value"]          = optional_param($paramname, 0, PARAM_RAW);
        }
    }
//echo "Preferences Entered:<br /><pre>";
//print_r($preferences);
//echo "</pre><br />\n";

    return $preferences;

}

function mainContent() {
    global $page, $dealerid;

    displayInstructions();
    echo "<div>\n";
    $url  = htmlentities($_SERVER['PHP_SELF']);
    $url .= ($dealerid <> $page->user->userId) ? "?dealerId=".$dealerid : "";
    echo "  <form name='preferences' id='preferences' method='POST' action='".$url."'>\n";
    echo "    <div>&nbsp;</div>\n";
    displayForm();
    echo "    <div>&nbsp;</div>\n";
    echo "    <input type='submit' name='submitbtn' value='Save'>\n";
    echo "  </form>\n";
    echo "</div>\n";
}

function displayInstructions() {
    $instructions  = "<p>Please click on the preference that you would like to enable for your DealernetX experience.</p>";
    echo "<div style='margin:5px; padding:5px; border:1px solid #000; background-color:#EEE;'>".$instructions."</div>\n";
}

function displayForm() {
    $preferences = getPreferences();

    if ($preferences) {
        $checkboxids = null;
        foreach($preferences as $idx=>$pref) {
            if ($idx == CHECKBOX) {
                $x = reset($pref);
                echo displayCBs($x["prefix"]."preferenceid", $pref, "preferenceid", "preference", "description", "assignedprefid");
                echo "    <div>&nbsp;</div>\n";
                $checkboxids = implode(",",array_keys($pref));
            }
        }
        echo "    <input type='hidden' name='checkboxids' value='".$checkboxids."'>\n";
    }
}

function displayCBs($name, $data, $idfield, $namefield, $descriptionfield, $checkedfield) {
    $output = "";
    foreach($data as $d) {
        $checked = (empty($d[$checkedfield])) ? "" : "CHECKED";
        $label      = "<b>".$d[$namefield].":</b> ".$d[$descriptionfield];
        $output .= "  <input type='checkbox' name='".$name."_".$d[$idfield]."' id='".$name."_".$d[$idfield]."' value='".$d[$idfield]."' ".$checked.">&nbsp;".$label."<br/>\n";
    }
    $output .= "\n";

    return $output;
}

function getPreferences() {
    global $page, $dealerid;

    $sql = "
        SELECT up.preferenceid, up.preference, up.description,
               up.inputtype, ap.assignedprefid, ap.value,
               case when up.inputtype = '".CHECKBOX."'  then '".CHECKBOXPREFIX."'
                    when up.inputtype = '".RADIO."'     then '".RADIOPREFIX."'
                    when up.inputtype = '".TEXTBOX."'   then '".TEXTBOXPREFIX."'
                    else '' end as prefix
          FROM userpreferences          up
          LEFT JOIN assignedpreferences ap  ON  ap.preferenceid = up.preferenceid
                                            AND ap.userid       = ".$dealerid."
         WHERE up.isactive = 1
        ORDER BY up.inputtype, up.preference
    ";

    $rs = $page->db->sql_query_params($sql);
    $preferences = array();
    $previnputtype = "";
    if (count($rs)) {
        $radio      = array();
        $checkbox   = array();
        $textbox    = array();
        foreach($rs as $r) {
            if ($r["inputtype"] == CHECKBOX) {
                $checkbox[$r["preferenceid"]] = $r;
            } elseif ($r["inputtype"] == RADIO) {
                $radio[$r["preferenceid"]] = $r;
            } elseif ($r["inputtype"] == TEXTBOX) {
                $textbox[$r["preferenceid"]] = $r;
            }
        }
        if (!empty($checkbox)) {
            $preferences[CHECKBOX] = $checkbox;
        }
        if (!empty($radio)) {
            $preferences[RADIO] = $radio;
        }
        if (!empty($textbox)) {
            $preferences[TEXTBOX] = $textbox;
        }
    }

    return $preferences;
}

function updateAssignedPreferences($preferences) {
    global $page;

    if ($preferences) {
        foreach($preferences as $idx=>$p) {
            if ($p["type"] == CHECKBOX) {
                if (!empty($p["preferenceid"]) && !empty($p["userid"])) {
                    /***
                     * For checkboxes empty values means it was unchecked; so delete
                     ***/
                    if (empty($p["value"])) {
                        if (doesPreferenceExist($p)) {
                            $sql = "
                                DELETE FROM assignedpreferences
                                 WHERE userid       = ".$p["userid"]."
                                   AND preferenceid = ".$p["preferenceid"];
                            $page->queries->AddQuery($sql);
                        }
                    } else {
                        if (!doesPreferenceExist($p)) {
                            $sql = "
                                INSERT INTO assignedpreferences(userid, preferenceid, value, createdby)
                                VALUES (:userid, :preferenceid, :value, :createdby)
                            ";
                            $params = array();
                            $params["userid"]       = $p["userid"];
                            $params["preferenceid"] = $p["preferenceid"];
                            $params["value"]        = $p["value"];
                            $params["createdby"]    = $page->user->username;

                            $page->queries->AddQuery($sql, $params);
                            unset($params);
                        }
                    }
                }
            }
        }
//foreach($page->queries->sqls as $idx=>$sql) {
//    echo "<pre>".$sql."<br>";
//    print_r($page->queries->params[$idx]);
//    echo "</pre>";
//}
        try {
            if ($page->queries->HasQueries()) {
                $page->db->sql_begin_trans();
                if($page->queries->ProcessQueries()) {
                    $page->db->sql_commit_trans();
                    $page->messages->AddSuccessMsg("User preferences updated.");
                } else {
                    $page->db->sql_rollback_trans();
                    $page->messages->AddErrorMsg("Rollback: Unable to update user preferences.");
                }
            } else {
                $page->messages->AddInfoMsg("No preferences updated; nothing to update.");
            }
        } catch (Exception $e) {
            $page->db->sql_rollback_trans();
            $page->messages->AddErrorMsg("ERROR: ".$e->getMessage()." [Unable to update user preferences]");
        } finally {
            unset($params);
            unset($page->queries);
            $page->queries  = new DBQueries("", $page->messages);
        }
    }
}

function doesPreferenceExist($preference) {
    global $page;

    $sql = "
        SELECT preferenceid
          FROM assignedpreferences
         WHERE userid       = ".$preference["userid"]."
           AND preferenceid = ".$preference["preferenceid"];

    $exists = $page->db->get_field_query($sql);

    return $exists;
}

?>