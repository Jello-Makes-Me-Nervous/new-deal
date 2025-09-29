<?php
include_once('setup.php');
require_once('template.class.php');

$page = new template(LOGIN, SHOWMSG);

$action     = optional_param('updatePass', NULL, PARAM_TEXT);
$editUserId = optional_param('userid', $page->user->userId, PARAM_TEXT);
$oldPassword = trim(optional_param('password', "", PARAM_TEXT));
$newPassword = trim(optional_param('newPassword', "", PARAM_TEXT));
$confirmPassword = trim(optional_param('confirmPassword', "", PARAM_TEXT));

if ($page->user->hasUserRight('ADMIN') || $page->user->userId == $editUserId) {
    if ($action == 'Update Password') {
        if (validatePassword($editUserId, $oldPassword, $newPassword, $confirmPassword)) {
            if (updatePassword($editUserId, strtoupper($newPassword))) {
                $page->messages->addSuccessMsg("Updated password");
            }
        }
    }
    echo $page->header('Password');
    echo mainContent();
    echo $page->footer(true);
} else {
    $page->messages->addWarningMsg("You are not supposed to be here.");
    echo $page->header('Password');
    echo $page->footer(true);
}

function mainContent() {
    global $page, $UTILITY, $editUserId;

    if ($page->user->hasUserRight('ADMIN')) {
        echo "Update Password Dealer: ".$UTILITY->getDealersName($editUserId)."(".$editUserId.")\n";
    }

    echo "<form name ='sub2' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    if ($page->user->hasUserRight('ADMIN')) {
        echo "  <input type='hidden' name='userid' id='userid' value='".$editUserId."' />\n";
    }
    echo "  <table>\n";
    echo "    <tbody>\n";
    if (! $page->user->hasUserRight('ADMIN')) {
        echo "      <tr>\n";
        echo "        <td>Old Password: </td>\n";
        echo "        <td><input type='password' name='password' id='password'></td>\n";
        echo "      </tr>\n";
    }
    echo "      <tr>\n";
    echo "        <td>New Password: </td>\n";
    echo "        <td><input type='password' name='newPassword' id='newPassword'></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Confirm Password: </td>\n";
    echo "        <td><input type='password' name='confirmPassword' id='confirmPassword' ></td>\n";
    echo "      </tr>\n";
    echo "    </tbody>\n";
    echo "  </table>\n";
    echo "  <input class='button' type='submit' name='updatePass' value='Update Password'>\n";
    echo "</form>\n";
}

function validatePassword($editUserId, $oldPassword, $newPassword, $confirmPassword) {
    global $page, $DB;
    $success = FALSE;

    if (! $page->user->hasUserRight('ADMIN')) {
        if (strlen($oldPassword) > 1) {
            $upperOld = strtoupper($oldPassword);
            $sql = "SELECT userid FROM users WHERE userid=".$editUserId." AND userpass = crypt('".$upperOld."', userpass)";
            $result = $DB->sql_query($sql);
            if ($result <= 0) {
                $page->messages->addErrorMsg("Incorrect old password");
                return $success;
            }
        } else {
            $page->messages->addErrorMsg("Old password required");
            return $success;
        }
    }

    if (strlen($newPassword > 5)) {
        if ($newPassword == $confirmPassword) {
            $success = TRUE;
        } else {
            $page->messages->addErrorMsg("New and confirm passwords do not match");
        }
    } else {
        $page->messages->addErrorMsg("New password must be 5 characters or longer");
    }

    return $success;
}
function updatePassword($editUserId, $newPassword) {
    global $page, $DB;

    $success = FALSE;

    $upperNewPassword = strtoupper($newPassword);
    $sql = "update users set userpass = crypt('".$upperNewPassword."', gen_salt('bf')) where userid=".$editUserId;

    $result = $DB->sql_execute($sql);
    if ($result > 0) {
        $success = TRUE;
    } else {
        $page->messages->addErrorMsg("Error updating password result:".$result);
    }

    return $success;
}
?>