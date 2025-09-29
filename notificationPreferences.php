<?php

require_once('template.class.php');

DEFINE("VERIFICATIONCODELEN",           6);
DEFINE("VERIFICATIONCODEEXPIRATION",    15);
DEFINE("NACOUNTRYCODE",                 "1");
DEFINE("TWILIOPLUS",                    "+");


//Workflow Steps
DEFINE("WF_READY",      1);
DEFINE("WF_CODESENT",   2);
DEFINE("WF_VERIFIED",   3);

$page = new template(LOGIN, SHOWMSG, REDIRECTSAFE);
$pageTitle = "Notification Preferences";

$types = array();
//$types[] = EMAILNOTIFICATIONTYPE;
$types[] = SMSNOTIFICATIONTYPE;

$dealerid = null;

/****************************************************
 * Get form parameters
 ****************************************************/
$notifications = paramInit();

/****************************************************
 * Process form submission
 ****************************************************/
if (!empty($notifications)) {
    updateNotificationSettings($notifications);
}


/****************************************************
 * Determine workflow state of each channel;
 * this determines the form fields displayed + processing
 ****************************************************/
$emailpreferences   = null;
$emailmode          = null;
//$emailpreferences   = getPreferences(EMAILNOTIFICATIONTYPE);
//$emailmode  = determineEmailMode($emailpreferences);
$smspreferences     = getPreferences(SMSNOTIFICATIONTYPE);
$smsmode    = determineSMSMode($smspreferences);

$emailactive = true;
if (!empty($emailpreferences)) {
    $ep = reset($emailpreferences);
    if (empty($ep["isactive"])) {
        $js  = "\n";
        $js .= "  $('#".EMAILNOTIFICATIONTYPE."').find(':input').each(function(){\n";
        $js .= "    if ($(this).prop('type') == 'radio' && $(this).attr('checked')) {\n";
        $js .= "      $(this).attr('disabled', true);\n";
        $js .= "    } else if ($(this).prop('type') == 'checkbox') {\n";
        $js .= "      $(this).attr('disabled', true);\n";
        $js .= "    } else if ($(this).prop('type') == 'select-one') {\n";
        $js .= "      $(this).attr('disabled', true);\n";
        $js .= "      $(this).css('background-color', '#EEE');\n";
        $js .= "    } else {\n";
        $js .= "      $(this).attr('disabled', true);\n";
        $js .= "      $(this).prop('readonly', true);\n";
        $js .= "      $(this).css('background-color', '#EEE');\n";
        $js .= "    }\n";
        $js .= "  });\n";
        $js .= "  $('.keepactive').attr('disabled', false);\n";
        $js .= "\n";
        $page->jsInit($js);

        $emailactive = false;
    }
}

$smsactive = true;
if (!empty($smspreferences)) {
    $sp = reset($smspreferences);
    if (empty($sp["isactive"])) {
        $js  = "\n";
        $js .= "  $('#".SMSNOTIFICATIONTYPE."').find(':input').each(function(){\n";
        $js .= "    if ($(this).prop('type') == 'radio' && $(this).attr('checked')) {\n";
        $js .= "      $(this).attr('disabled', true);\n";
        $js .= "    } else if ($(this).prop('type') == 'checkbox') {\n";
        $js .= "      $(this).attr('disabled', true);\n";
        $js .= "    } else if ($(this).prop('type') == 'select-one') {\n";
        $js .= "      $(this).attr('disabled', true);\n";
        $js .= "      $(this).css('background-color', '#EEE');\n";
        $js .= "    } else {\n";
        $js .= "      $(this).attr('disabled', true);\n";
        $js .= "      $(this).prop('readonly', true);\n";
        $js .= "      $(this).css('background-color', '#EEE');\n";
        $js .= "    }\n";
        $js .= "  });\n";
        $js .= "  $('.keepactive').attr('disabled', false);\n";
        $js .= "\n";
        $page->jsInit($js);

        $smsactive = false;
    }
}
$js  = "\n";
$js .= "$('.numbersOnly').keyup(function () {\n";
$js .= "  if (this.value != this.value.replace(/[^0-9\.]/g, '')) {\n";
$js .= "    this.value = this.value.replace(/[^0-9\.]/g, '');\n";
$js .= "  }\n";
$js .= "});\n";
$page->jsInit($js);


if (!$emailactive || !$smsactive) {
    $url    = "/sendmessage.php?dept=1&subject=Inactive%20Notification%20Channel";
    $link   = "<a href='".$url."'>Help Desk</a>";
  $page->messages->AddWarningMsg("Warning: You have an inactive notification channel.  To make active check <b><i>Active</i></b> below.");
}

$js  = "\n";
$js .= "        $(document).ready(function() {\n";
$js .= "          var isSubmitting = false;\n";
$js .= "          var myform = $('#notifications');\n";
$js .= "          var original = myform.serialize();\n";
$js .= "          myform.submit(function(){\n";
$js .= "            isSubmitting = true;\n";
$js .= "            window.onbeforeunload = null;\n";
$js .= "          });\n";
$js .= "          $(window).on('beforeunload', function(){\n";
$js .= "            if(isSubmitting || myform.serialize() == original) {\n";
$js .= "              return undefined;\n";
$js .= "            } else {\n";
$js .= "              return 'It appears that you made some edits and have not saved your work.  Are you sure you want to leave?'\n";
$js .= "            }\n";
$js .= "          })\n";
$js .= "        });\n";
$js .= "        $('text').keydown(function(event){\n";
$js .= "          if(event.keyCode == 13) {\n";
$js .= "            event.preventDefault();\n";
$js .= "            return false;\n";
$js .= "          }\n";
$js .= "        });\n";
$js .= "\n";
$page->jsInit($js);


$sendEmailVerificationCode  = false;
$sendSMSVerificationCode    = false;

echo $page->header($pageTitle);
echo mainContent();
echo $page->footer(true);


function paramInit() {
    global $page, $types, $dealerid;


    /****************************************************
     * Only staff can modify a member's record (inactive)
     ****************************************************/
    if ($page->user->isStaff()) {
        $dealerid   = optional_param('dealerId', NULL, PARAM_INT);
        $dealerid   = (empty($dealerid)) ? $page->user->userId : $dealerid;
    } else {
        $dealerid   = $page->user->userId;
    }

    $notifications = array();
    $submitbtn      = optional_param("submitbtn", null, PARAM_RAW);
    $sendnewcode    = optional_param("sendnewcode", null, PARAM_RAW);
    if (!empty($submitbtn) || !empty($sendnewcode)) {
        foreach($types as $t) {
            $preferenceid       = optional_param($t."_preferenceid", NULL, PARAM_INT);
            $notification_type  = optional_param($t."_notification_type", null, PARAM_TEXT);
            $frequency          = optional_param($t."_frequency", 60, PARAM_INT);
            $orig_frequency     = optional_param($t."_orig_frequency", 15, PARAM_INT);
            $emailphone         = optional_param($t."_emailphone", NULL, PARAM_TEXT);
            $orig_emailphone    = optional_param($t."_orig_emailphone", NULL, PARAM_TEXT);
            $verificationcode   = optional_param($t."_verificationcode", NULL, PARAM_INT);
            $acceptedterms      = optional_param($t."_acceptedterms", NULL, PARAM_INT);
            $isactive           = optional_param($t."_isactive", 0, PARAM_INT);
            $orig_isactive      = optional_param($t."_orig_isactive", 0, PARAM_INT);
            $sendnewcode        = optional_param($t."_sendnewcode", 0, PARAM_INT);
            $deletechannel      = optional_param($t."_deletechannel", 0, PARAM_INT);
            $emailphone         = str_replace(" ","",$emailphone);
            $emailphone         = str_replace("(","",$emailphone);
            $emailphone         = str_replace(")","",$emailphone);
            $emailphone         = str_replace("[","",$emailphone);
            $emailphone         = str_replace("]","",$emailphone);
            $emailphone         = str_replace("-","",$emailphone);
            if ($t == SMSNOTIFICATIONTYPE) {
                if (strlen($emailphone) == 10) {    // Assume country code = 1 is needed
                    $emailphone = NACOUNTRYCODE.$emailphone;
                }
                if (strpos($emailphone, TWILIOPLUS) === false) {
                    $emailphone = TWILIOPLUS.$emailphone;
                }
                if (strpos($emailphone, TWILIOPLUS."1") === false) {
                    $frequency = 1440;
                }
            }
            if (!empty($preferenceid) || !empty($emailphone) || !empty($orig_emailphone) ||
                ($isactive <> $orig_isactive)) {
                $oNotification = new stdClass();
                $oNotification->preferenceid        = $preferenceid;
                $oNotification->userid              = $dealerid;
                $oNotification->notification_type   = $notification_type;
                $oNotification->frequency           = $frequency;
                $oNotification->orig_frequency      = $orig_frequency;
                $oNotification->emailphone          = $emailphone;
                $oNotification->orig_emailphone     = $orig_emailphone;
                $oNotification->verificationcode    = $verificationcode;
                $oNotification->acceptedterms       = $acceptedterms;
                $oNotification->isactive            = $isactive;
                $oNotification->orig_isactive       = $orig_isactive;
                $oNotification->sendnewcode         = $sendnewcode;
                $oNotification->deletechannel       = $deletechannel;

                $notifications[$t] = $oNotification;
            }
            if ($t == SMSNOTIFICATIONTYPE && empty($acceptedterms) && !empty($emailphone) && empty($deletechannel) && !empty($orig_isactive)) {
                $page->messages->AddErrorMsg("ERROR: ".$notification_type." term acceptance is required for SMS / text messaging.");
                unset($notifications[$t]);
            }
        }

//echo "<pre>";
//print_r($_POST);
//echo "</pre>";
//echo "<pre>";
//print_r($notifications);
//echo "</pre>";

        return $notifications;
    }
}

function mainContent() {
    global $page, $emailpreferences, $smspreferences;
    global $emailmode, $smsmode, $dealerid;

    displayInstructions();
    echo "<div>\n";
    $url  = htmlentities($_SERVER['PHP_SELF']);
    $url .= ($dealerid <> $page->user->userId) ? "?dealerId=".$dealerid : "";
    echo "  <form name='notifications' id='notifications' method='POST' action='".$url."'>\n";
//    displayEmailForm();
//    echo "<div>&nbsp;</div>\n";
    displaySMSForm();
    echo "    <input type='submit' name='submitbtn' id='submitbtn' value='Save'>\n";
    echo "  </form>\n";
    echo "</div>\n";
}

function displayInstructions() {
    $instructions  = "<b>DealernetX requires you to provide a mobile number to receive notifications (daily activity reminder).</b>";
    $instructions .= " Additionally, users can select to receive hourly notifications.\n";
    $instructions .= " These external notifications are triggered by any of the following:\n";
    $instructions .= " <ul>\n";
    $instructions .= "   <li>An offer you are involved in is updated</li>\n";
    $instructions .= "   <li>An EFT payment was received</li>\n";
    $instructions .= "   <li>There is correspondence between you and another member or the site administrators.</li>\n";
    $instructions .= "   <li>A Price Alert triggered.</li>\n";
    $instructions .= " </ul>\n";
    $instructions .= " When setting up a new or changing an existing notification phone number. DealernetX will send to you a ".VERIFICATIONCODELEN." digit code to that phone number.  You will need to enter that code in the <b>Verification Code</b> field prior to the number being accepted. <b>NOTE:</b>You must click <b><i>Save</i></b> at the bottom of the page to update any changes you have made to your notifications.\n";

    echo "<div style='margin:5px; padding:5px; border:1px solid #000; background-color:#EEE;'>".$instructions."</div>\n";

}

function displayEmailForm() {
    global $page, $emailmode, $emailpreferences, $dealerid;

    if (!empty($emailpreferences)) {
        $preference = reset($emailpreferences);
    } else {
        $preference = array("preferenceid"=>null,
                              "notification_type"=>EMAILNOTIFICATIONTYPE,
                              "frequency"=>15,
                              "emailphone"=>null,
                              "verificationcode"=>null,
                              "validated_on"=>null,
                              "acceptedterms_on"=>null,
                              "verification_sent"=>null,
                              "isactive"=>1,
                              "sendnewcode"=>0,
                              "deletechannel"=>0);
    }

    echo "  <h3>Email Notification</h3>\n";
    echo "  <table id='".EMAILNOTIFICATIONTYPE."'>\n";
    echo "    <tbody>\n";
    echo "      <tr>\n";
    echo "        <td style='width:200px;font-weight:bold;'>Email</td>\n";
    echo "        <td scope='row'>\n";
    echo "          <input type='text' name='".EMAILNOTIFICATIONTYPE."_emailphone' value='".$preference["emailphone"]."'>\n";
    echo "        </td>\n";
    echo "        <td rowspan='4' style='width: 25px;'>\n";
    if (!empty($preference["isactive"])) {
        $confirmed = "
            var notificationaction;
            var emailtable = $('#".EMAILNOTIFICATIONTYPE."');
            emailtable.find(':input').each(function(){
              if ($(this).prop('disabled')) {
                $(this).css('background-color' , '#FFF');
                $(this).prop('disabled', false);
                notificationaction = 0;
              } else {
                $(this).css('background-color' , '#EEE');
                $(this).prop('disabled', true);
                notificationaction = 1;
              }
            });
            $('#".EMAILNOTIFICATIONTYPE."_deletechannel').val(1);
            $('.keepactive').attr('disabled', false);
            if (notificationaction == 0) {
              alert('Cleared. Click again to mark for deletion.');
            } else {
              alert('Marked for deletion. Click again to clear.\\n Click Save to commit the change.');
            }
        ";
        if (empty($preference["emailphone"])) {
            echo "          <a class='fas fa-trash-alt' title='Delete ".EMAILNOTIFICATIONTYPE." channel' href='javascript:void(0);' onclick='JavaScript:alert(\"Nothing to delete as there is no email channel established.\");'></a>\n";
        } else {
            echo "          <a class='fas fa-trash-alt' title='Delete ".EMAILNOTIFICATIONTYPE." channel' href='javascript:void(0);' onclick=\"".$confirmed."\"></a>\n";
        }
    }
    echo "        </td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td scope='row' style='width:200px;font-weight:bold;'>Frequency</td>\n";
    echo "        <td>\n";
    echo getFrequencyDDM(EMAILNOTIFICATIONTYPE."_frequency", $preference["frequency"]);
    echo "        </td>\n";
    echo "      </tr>\n";
    if ($emailmode == WF_CODESENT) {
        echo "      <tr>\n";
        echo "        <td scope='row' style='width:200px;font-weight:bold;'>Verification Code</td>\n";
        $timeout = $preference["verification_sent"] + (15*60); // 15 minutes from time it was sent
        $placeholder = "Verification code expires on ".date("m/d/Y h:i:s a", $timeout);
        echo "        <td>\n";
        echo "          <input type='text' name='".EMAILNOTIFICATIONTYPE."_verificationcode' value='' placeholder='".$placeholder."' maxlength='".VERIFICATIONCODELEN."'>\n";
        echo "          <input type='submit' style='box-shadow: 0 0 0; margin-top:5px; float:right;' name='sendnewcode' value='Send New Code' onClick='javascript: if (confirm(\"This will reset your verification code. Are you sure you want to do that?\\n\\nIf you were attempting to save the entered code, please click cancel below and then save at the bottom of the page.\")) { $(\"#".EMAILNOTIFICATIONTYPE."_sendnewcode\").val(1); $(\"#notifications\").submit(); } else { return false; }'>\n";
        echo "        </td>\n";
        echo "      </tr>\n";
    } elseif ($emailmode == WF_VERIFIED) {
        echo "      <tr>\n";
        echo "        <td scope='row' style='width:200px;font-weight:bold;'>Validated On</td>\n";
        echo "        <td>".date("m/d/Y h:i:s a", $preference["validated_on"])."</td>\n";
        echo "      </tr>\n";
    }
    if ($page->user->isStaff() || empty($preference["isactive"])) {
        echo "      <tr>\n";
        echo "        <td scope='row' style='width:200px;font-weight:bold;'>Active</td>\n";
        $checked = (empty($preference["isactive"])) ? "" : "CHECKED";
        echo "        <td><input type='checkbox' name='".EMAILNOTIFICATIONTYPE."_isactive' value='1' ".$checked." class='keepactive'></td>\n";
        echo "      </tr>\n";
    }
    echo "      <tr style='visibility:collapse'>\n";
    echo "        <td colspan='3'>\n";
    $type = (empty($preference["notification_type"])) ? EMAILNOTIFICATIONTYPE : $preference["notification_type"];
    echo  "         <input type='hidden' name='".EMAILNOTIFICATIONTYPE."_notification_type' value='".$type."' class='keepactive'>\n";
    echo  "         <input type='hidden' name='".EMAILNOTIFICATIONTYPE."_preferenceid' value='".$preference["preferenceid"]."' class='keepactive'>\n";
    echo  "         <input type='hidden' name='".EMAILNOTIFICATIONTYPE."_orig_emailphone' value='".$preference["emailphone"]."' class='keepactive'>\n";
    echo  "         <input type='hidden' name='".EMAILNOTIFICATIONTYPE."_orig_frequency' value='".$preference["frequency"]."'>\n";
    echo  "         <input type='hidden' name='".EMAILNOTIFICATIONTYPE."_orig_isactive' value='".$preference["isactive"]."' class='keepactive'>\n";
    echo  "         <input type='hidden' name='".EMAILNOTIFICATIONTYPE."_sendnewcode' id='".EMAILNOTIFICATIONTYPE."_sendnewcode' value='0'>\n";
    echo  "         <input type='hidden' name='".EMAILNOTIFICATIONTYPE."_deletechannel' id='".EMAILNOTIFICATIONTYPE."_deletechannel' value='0' class='keepactive'>\n";
    echo "        </td>\n";
    echo "      </tr>\n";
    echo "    </tbody>\n";
    echo "  </table>\n";

}

function displaySMSForm() {
    global $page, $smsmode, $smspreferences, $dealerid;

    $hidden = "";
    if (!empty($smspreferences)) {
        $preference = reset($smspreferences);
    } else {
        $preference = array("preferenceid"=>null,
                              "notification_type"=>SMSNOTIFICATIONTYPE,
                              "frequency"=>15,
                              "emailphone"=>null,
                              "verificationcode"=>null,
                              "validated_on"=>null,
                              "acceptedterms_on"=>null,
                              "verification_sent"=>null,
                              "isactive"=>1,
                              "sendnewcode"=>0,
                              "deletechannel"=>0);
    }
    echo "  <h3>SMS / Text Notification</h3>\n";
    echo "  <table id='".SMSNOTIFICATIONTYPE."'>\n";
    echo "    <tbody>\n";
    echo "      <tr>\n";
    echo "        <td style='width:200px;font-weight:bold;'>Mobile Phone Number</td>\n";
    echo "          <td scope='row'>\n";
    echo "            <input type='text' name='".SMSNOTIFICATIONTYPE."_emailphone' value='".$preference["emailphone"]."' class='numbersOnly' placeholder='#s only ([country code][phone number including area code])'>\n";
    echo "            <br><b><i>#s only ... no spaces, dashes or parentheses, etc.<br>Please include the country code if outside the US and Canada.</i></b>\n";
    echo "          </td>\n";
    echo "        <td rowspan='5' style='width: 25px;'>\n";
    if (!empty($preference["isactive"])) {
        $confirmed = "
            var notificationaction;
            var emailtable = $('#".SMSNOTIFICATIONTYPE."');
            emailtable.find(':input').each(function(){
              if ($(this).prop('disabled')) {
                $(this).css('background-color' , '#FFF');
                $(this).prop('disabled', false);
                notificationaction = 0;
              } else {
                $(this).css('background-color' , '#EEE');
                $(this).prop('disabled', true);
                notificationaction = 1;
              }
            });
            $('#".SMSNOTIFICATIONTYPE."_deletechannel').val(1);
            $('.keepactive').attr('disabled', false);
            if (notificationaction == 0) {
              alert('Cleared. Click again to mark for deletion.');
            } else {
              alert('Marked for deletion.');
            }
            $('#submitbtn').trigger('click');
        ";
        if (empty($preference["emailphone"])) {
            echo "          <a class='fas fa-trash-alt' title='Delete ".SMSNOTIFICATIONTYPE." channel' href='javascript:void(0);' onclick='JavaScript:alert(\"Nothing to delete as there is no SMS channel established.\");'></a>\n";
        } else {
            echo "          <a class='fas fa-trash-alt' title='Delete ".SMSNOTIFICATIONTYPE." channel' href='javascript:void(0);' onclick=\"".$confirmed."\"></a>\n";
        }
    }
    echo "        </td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td style='width:200px;font-weight:bold;'>Frequency</td>\n";
    echo "        <td scope='row'>\n";
    echo getFrequencyDDM(SMSNOTIFICATIONTYPE."_frequency", $preference["frequency"]);
    echo "        </td>\n";
    echo "      </tr>\n";
    if ($smsmode == WF_CODESENT) {
        echo "      <tr>\n";
        echo "        <td style='width:200px;font-weight:bold;'>Verification Code</td>\n";
        $timeout = $preference["verification_sent"] + (15*60); // 15 minutes from time it was sent
        $placeholder = "Verification code expires on ".date("m/d/Y h:i:s a", $timeout);
        echo "        <td scope='row'>\n";
        echo "          <input type='text' name='".SMSNOTIFICATIONTYPE."_verificationcode' value='' placeholder='".$placeholder."' maxlength='".VERIFICATIONCODELEN."'>\n";
        echo "          <input type='submit' style='box-shadow: 0 0 0; margin-top:5px; float:right;' name='sendnewcode' value='Send New Code' onClick='javascript: if (confirm(\"This will reset your verification code. Are you sure you want to do that?\\n\\nIf you were attempting to save the entered code, please click cancel below and then save at the bottom of the page.\")) { $(\"#".SMSNOTIFICATIONTYPE."_sendnewcode\").val(1); $(\"#notifications\").submit(); } else { return false; }'>\n";
        echo "        </td>\n";
        echo "      </tr>\n";
    } elseif ($smsmode == WF_VERIFIED) {
        echo "      <tr>\n";
        echo "        <td style='width:200px;font-weight:bold;'>Validated On</td>\n";
        echo "        <td scope='row'>".date("m/d/Y h:i:s a", $preference["validated_on"])."</td>\n";
        echo "      </tr>\n";
    }
    echo "      <tr>\n";
    echo "        <td style='width:200px;font-weight:bold;'>SMS Terms</td>\n";
    echo "        <td scope='row'>\n";
    echo "          <label for='acceptedterms'>\n";
    $checked    = "";
    $onclick    = "";
    $accepted   = "";
    if (!empty($preference["acceptedterms_on"])) {
        $checked    = "CHECKED";
        $onclick    = "onclick='$(this).prop(\"checked\", true);'";
        $accepted   = "<i>Terms accepted on: ".date("m/d/Y h:i:s a", $preference["acceptedterms_on"])."</i><br>\n";
    }
    echo "            ".$accepted;
    echo "            <input type='checkbox' name='".SMSNOTIFICATIONTYPE."_acceptedterms' id='acceptedterms' value='1' ".$checked." ".$onclick.">\n";
    echo "            By completing this SMS authorization and checking this opt-in, you are agreeing to receive SMS messages from DealernetX (833-692-3479).\n";
    echo "            <b>Depending on your cell phone plan, message and data rates may apply.</b>\n";
    $link = "<a href='/sendmessage.php?dept=1'>HELP DESK</a> ";
    echo "            <b>If you have any questions, please contact ".$link."</b>.\n";
    echo "          </label>\n";
    echo "        </td>\n";
    echo "      </tr>\n";
    if ($page->user->isStaff() || empty($preference["isactive"])) {
        echo "      <tr>\n";
        echo "        <td style='width:200px;font-weight:bold;'>Active</td>\n";
        $checked = (empty($preference["isactive"])) ? "" : "CHECKED";
        echo "        <td scope='row'><input type='checkbox' name='".SMSNOTIFICATIONTYPE."_isactive' value='1' ".$checked." class='keepactive'></td>\n";
        echo "      </tr>\n";
    }
    echo "      <tr style='visibility:collapse'>\n";
    echo "        <td colspan='3'>\n";
    $type = (empty($preference["notification_type"])) ? SMSNOTIFICATIONTYPE : $preference["notification_type"];
    echo  "         <input type='hidden' name='".SMSNOTIFICATIONTYPE."_notification_type' value='".$type."' class='keepactive'>\n";
    echo  "         <input type='hidden' name='".SMSNOTIFICATIONTYPE."_preferenceid' value='".$preference["preferenceid"]."' class='keepactive'>\n";
    echo  "         <input type='hidden' name='".SMSNOTIFICATIONTYPE."_orig_emailphone' value='".$preference["emailphone"]."' class='keepactive'>\n";
    echo  "         <input type='hidden' name='".SMSNOTIFICATIONTYPE."_orig_frequency' value='".$preference["frequency"]."'>\n";
    echo  "         <input type='hidden' name='".SMSNOTIFICATIONTYPE."_orig_isactive' value='".$preference["isactive"]."' class='keepactive'>\n";
    echo  "         <input type='hidden' name='".SMSNOTIFICATIONTYPE."_sendnewcode' id='".SMSNOTIFICATIONTYPE."_sendnewcode' value='0'>\n";
    echo  "         <input type='hidden' name='".SMSNOTIFICATIONTYPE."_deletechannel' id='".SMSNOTIFICATIONTYPE."_deletechannel' value='0' class='keepactive'>\n";
    echo "        </td>\n";
    echo "      </tr>\n";
    echo "    </tbody>\n";
    echo "  </table>\n";

}

function getFrequencyDDM($name, $selectedid = null) {
    global $page;

    $frequncies = array();
//    $frequencies[] = array("name"=>"Immediate",     "value"=>-1,    "default"=>0);
//    $frequencies[] = array("name"=>"Every 5 minutes",   "value"=>5,     "default"=>0);
//    $frequencies[] = array("name"=>"Every 15 minutes",  "value"=>15,    "default"=>1);
//    $frequencies[] = array("name"=>"Every 30 minutes",  "value"=>30,    "default"=>0);
    $frequencies[] = array("name"=>"On the hour",       "value"=>60,    "default"=>0);
    $selected = (empty($selectedid)) ? 1 : 0;
    $frequencies[] = array("name"=>"Daily (overnight)",       "value"=>1440,    "default"=>$selected);

    return (getSelectDDM($frequencies, $name, "value", "name", "default", $selectedid));
}

function getPreferences($type = EMAILNOTIFICATIONTYPE) {
    global $page, $dealerid;

    $sql = "
        SELECT preferenceid, notification_type, frequency, emailphone,
               verificationcode, validated_on, acceptedterms_on,
               verification_sent, isactive
          FROM notification_preferences
         WHERE userid               = ".$dealerid."
           AND notification_type    = '".$type."'
        ORDER BY frequency, emailphone
    ";

    $rs = $page->db->sql_query_params($sql);

    return $rs;
}

function determineEmailMode($data) {
    if (empty($data)) {
        $emailmode = WF_READY;
    } else {
        $d = reset($data);
        if (!empty($d["verification_sent"]) && empty($d["validated_on"])) {
            $emailmode = WF_CODESENT;
        } elseif (!empty($d["emailphone"]) && !empty($d["validated_on"])) {
            $emailmode = WF_VERIFIED;
        }
    }

    return $emailmode;
}

function determineSMSMode($data) {
    $smsmode = WF_READY;
    if (empty($data)) {
        $smsmode = WF_READY;
    } else {
        $d  = reset($data);
        if (!empty($d["acceptedterms_on"]) && !empty($d["verification_sent"]) && empty($d["validated_on"])) {
            $smsmode = WF_CODESENT;
        } elseif (!empty($d["acceptedterms_on"]) && !empty($d["validated_on"])) {
            $smsmode = WF_VERIFIED;
        }
    }

    return $smsmode;
}

function updateNotificationSettings($preferences) {
    global $page, $types;
    global $sendEmailVerificationCode, $sendSMSVerificationCode;

    $params = null;
    $verificationcode = array();
    $sendverificationto = null;
    if (!empty($preferences)) {
        foreach($preferences as $idx=>$np) {
//echo "<pre>";print_r($np);echo "</pre>";
            $sendverificationto = $np->userid;
            if (!empty($np->preferenceid) && !empty($np->sendnewcode)) {
                $verificationcode[$idx] = resetVerificationCode($np);
            } elseif (!empty($np->preferenceid) && !empty($np->orig_emailphone) &&
                      !empty($np->deletechannel)) {
                deletePreference($np);
                $page->messages->AddSuccessMsg($np->notification_type." notification channel deleted.");
            } elseif (!empty($np->preferenceid) &&
                      !empty($np->emailphone) && !empty($np->orig_emailphone)   &&
                      ($np->orig_emailphone <> $np->emailphone)                 &&
                      ($np->isactive == $np->orig_isactive)) {
                deletePreference($np);
                $page->messages->AddSuccessMsg($np->notification_type." notification channel deleted.");
                $verificationcode[$idx] = insertPerference($np);
            } elseif (!empty($np->preferenceid) && !empty($np->emailphone) &&
                      !empty($np->verificationcode)) {
                $error = 0;
                $verification_sent = getVerificationSentOn($np);
                if (empty($verification_sent)) {
                    $error = 1;
                    $page->messages->AddErrorMsg("ERROR: ".$np->notification_type." verification code does not match.  Please re-enter.");
                } else {
                    $minutes = abs($verification_sent - time()) / 60;
                    // allow for a 5 minute cushion
                    if ($minutes > (VERIFICATIONCODEEXPIRATION + 5)) {
                        $error = 1;
                        $page->messages->AddErrorMsg("ERROR: ".$np->notification_type." verification code not entered within the time allotted and has been reset.");
                        $verificationcode[$idx] = resetVerificationCode($np);
                    } else {
                        setValidatedOn($np);
                        $page->messages->AddSuccessMsg($np->notification_type." verification code successfully entered.");
                    }
                }
            } elseif (!empty($np->preferenceid) && !empty($np->emailphone) &&
                      ($np->frequency <> $np->orig_frequency) && !empty($np->isactive)&&
                      ($np->isactive == $np->orig_isactive)) {
                setFrequency($np);
                $page->messages->AddSuccessMsg($np->notification_type." frequency successfully updated.");
            } elseif ($page->user->isStaff() && empty($np->preferenceid)   &&
                      empty($np->isactive) && ($np->isactive <> $np->orig_isactive)) {
                insertInactivePerference($np);
                $active = (empty($np->isactive)) ? "inactive" : "active";
                $page->messages->AddSuccessMsg($np->notification_type." active status successfully set to ".$active.".");
            } elseif (!empty($np->preferenceid)   &&
                      !empty($np->isactive) && ($np->isactive == $np->orig_isactive)) {
                deletePreference($np);
                $page->messages->AddSuccessMsg($np->notification_type." deleted.");
            } elseif (($page->user->isStaff() && !empty($np->preferenceid)  &&
                      ($np->isactive <> $np->orig_isactive))                ||
                      (empty($np->orig_isactive) && !empty($np->preferenceid)  &&
                      ($np->isactive <> $np->orig_isactive))) {
                setActiveStatus($np);
                $active = (empty($np->isactive)) ? "inactive" : "active";
                $page->messages->AddSuccessMsg($np->notification_type." active status successfully set to ".$active.".");
            } elseif (empty($np->preferenceid) && !empty($np->emailphone)) {
                $verificationcode[$idx] = insertPerference($np);
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
                    if ($sendEmailVerificationCode) {
                        sendEmailSecurityCode($verificationcode[EMAILNOTIFICATIONTYPE], $sendverificationto);
                    }
                    if ($sendSMSVerificationCode) {
                        sendSMSSecurityCode($verificationcode[SMSNOTIFICATIONTYPE], $sendverificationto);
                    }
                } else {
                    $page->db->sql_rollback_trans();
                    $page->messages->AddErrorMsg("Rollback: Unable to update notification channels.");
                }
            }
        } catch (Exception $e) {
            $page->db->sql_rollback_trans();
            $page->messages->AddErrorMsg("ERROR: ".$e->getMessage()." [Unable to update notification channels]");
        } finally {
            unset($params);
            unset($page->queries);
            $page->queries  = new DBQueries("", $page->messages);
        }

    }
}

function deletePreference($pref) {
    global $page;

    $sql = "
        DELETE FROM notification_preferences
         WHERE preferenceid = ".$pref->preferenceid."
           AND userId       = ".$pref->userid;
    $page->queries->AddQuery($sql);
}

function insertPerference($pref) {
    global $page, $sendEmailVerificationCode, $sendSMSVerificationCode;

    $params = array();
    $verificationcode = $page->utility->generatePassword(VERIFICATIONCODELEN, "d");
    if ($pref->notification_type == EMAILNOTIFICATIONTYPE) {
        $sql = "
            INSERT INTO notification_preferences(userid, notification_type, frequency, emailphone, verificationcode, verification_sent, createdby)
            VALUES (:userid, :notification_type, :frequency, :emailphone, :verificationcode, :verification_sent, :createdby)
        ";
        $sendEmailVerificationCode = true;
    } else {
        $sql = "
            INSERT INTO notification_preferences(userid, notification_type, frequency, emailphone, verificationcode, verification_sent, acceptedterms_on, createdby)
            VALUES (:userid, :notification_type, :frequency, :emailphone, :verificationcode, :verification_sent, :acceptedterms_on, :createdby)
        ";
        $sendSMSVerificationCode = true;
        $params["acceptedterms_on"]     = (empty($pref->acceptedterms)) ? NULL : time();
    }
    $params["userid"]               = $pref->userid;
    $params["notification_type"]    = $pref->notification_type;
    $params["frequency"]            = $pref->frequency;
    $params["emailphone"]           = $pref->emailphone;
    $params["verificationcode"]     = $verificationcode;
    $params["verification_sent"]    = time();
    $params["createdby"]            = $page->user->username;
    $page->queries->AddQuery($sql, $params);

    $msg = "An ".$pref->notification_type." verification code was sent to ".$pref->emailphone." please enter it in the appropriate field below.";
    $page->messages->AddSuccessMsg($msg);

    return $verificationcode;
}

function insertInactivePerference($pref) {
    global $page;

    $params = array();
    $sql = "
        INSERT INTO notification_preferences(userid, notification_type, isactive, createdby)
        VALUES (:userid, :notification_type, :isactive, :createdby)
    ";
    $params["userid"]               = $pref->userid;
    $params["notification_type"]    = $pref->notification_type;
    $params["isactive"]             = $pref->isactive;
    $params["createdby"]            = $page->user->username;
    $page->queries->AddQuery($sql, $params);

    if (empty($pref->isactive)) {
        $msg = $pref->notification_type." channel has been inactivated.";
    } else {
        $msg = $pref->notification_type." channel has been activated.";
    }
    $page->messages->AddSuccessMsg($msg);

}

function getVerificationSentOn($pref) {
    global $page;

    $sql = "
        SELECT verification_sent
          FROM notification_preferences
         WHERE userid               = ".$pref->userid."
           AND verificationcode     = ".$pref->verificationcode."
           AND notification_type    = '".$pref->notification_type."'
    ";
    $verification_sent = $page->db->get_field_query($sql);

    return $verification_sent;
}

function resetVerificationCode($pref) {
    global $page, $sendEmailVerificationCode, $sendSMSVerificationCode;

    $sql = "
        UPDATE notification_preferences
           SET verificationcode     = :verificationcode,
               verification_sent    = :verification_sent, ";
    if (empty($pref->sendnewcode)) {
        $sql .= "
               frequency            = :frequency,";
    }
    $sql .= "
               modifiedby           = :modifiedby,
               modifieddate         = nowtoint()
         WHERE preferenceid = :preferenceid
           AND userid       = :userid
    ";
    $verificationcode = $page->utility->generatePassword(VERIFICATIONCODELEN, "d");
    $params = array();
    $params["preferenceid"]         = $pref->preferenceid;
    $params["userid"]               = $pref->userid;
    $params["verificationcode"]     = $verificationcode;
    $params["verification_sent"]    = time();
    if (empty($pref->sendnewcode)) {
        $params["frequency"]            = $pref->frequency;
    }
    $params["modifiedby"]           = $page->user->username;

    $page->queries->AddQuery($sql, $params);
    if ($pref->notification_type == EMAILNOTIFICATIONTYPE) {
        $sendEmailVerificationCode = true;
    } else {
        $sendSMSVerificationCode = true;
    }
    $msg = "A new ".$pref->notification_type." verification code was sent; please enter it in the appropriate field below.";
    $page->messages->AddSuccessMsg($msg);
    return $verificationcode;
}

function setValidatedOn($pref) {
    global $page;

    $sql = "
        UPDATE notification_preferences
           SET validated_on = :validated_on,
               frequency    = :frequency,
               modifiedby   = :modifiedby,
               modifieddate = nowtoint()
         WHERE preferenceid = :preferenceid
           AND userid       = :userid
    ";
    $params = array();
    $params["preferenceid"] = $pref->preferenceid;
    $params["userid"]       = $pref->userid;
    $params["validated_on"] = time();
    $params["frequency"]    = $pref->frequency;
    $params["modifiedby"]   = $page->user->username;

    $page->queries->AddQuery($sql, $params);
}

function setActiveStatus($pref) {
    global $page;

    $sql = "
        UPDATE notification_preferences
           SET isactive     = :isactive,
               modifiedby   = :modifiedby,
               modifieddate = nowtoint()
         WHERE preferenceid = :preferenceid
           AND userid       = :userid
    ";
    $params = array();
    $params["preferenceid"] = $pref->preferenceid;
    $params["userid"]       = $pref->userid;
    $params["isactive"]     = $pref->isactive;
    $params["modifiedby"]    = $page->user->username;

    $page->queries->AddQuery($sql, $params);

}

function setFrequency($pref) {
    global $page;

    $sql = "
        UPDATE notification_preferences
           SET frequency    = :frequency,
               modifiedby   = :modifiedby,
               modifieddate = nowtoint()
         WHERE preferenceid = :preferenceid
           AND userid       = :userid
    ";
    $params = array();
    $params["preferenceid"] = $pref->preferenceid;
    $params["userid"]       = $pref->userid;
    $params["frequency"]    = $pref->frequency;
    $params["modifiedby"]   = $page->user->username;

    $page->queries->AddQuery($sql, $params);

}

function sendEmailSecurityCode($verificationcode, $userid) {
    global $page;

    $subject = "An important message from DealernetX";
    $message  = "<p>Your secure code expires in ".VERIFICATIONCODEEXPIRATION." minutes.</p>\n";
    $message .= "<p>".$verificationcode."</p>\n";
    $message .= "<p>Don't share this code or forward this email to anyone else.</p>";
    $page->iMessage->sendExternalEmail($userid, $subject, $message);
}

function sendSMSSecurityCode($verificationcode, $userid) {
    global $page;

    $subject = "";
    $message  = "Your verification code: ".$verificationcode."\n";
    $message .= "Code expires in ".VERIFICATIONCODEEXPIRATION." minutes. Msg&Data rate may apply\n";
    $page->iMessage->sendExternalSMS($userid, $message);
}

?>