<?php
require_once('template.class.php');
/////////////////////////////////////////ONLY EXTERNAL MAIL

$page = new template(LOGIN, SHOWMSG);
//$page->requireJS('scripts/validateCats.js');

$signatureText  = optional_param('signatureText', NULL, PARAM_TEXT);
$updateSig      = optional_param('updateSig', NULL, PARAM_TEXT);

if (isset($updateSig)) {
    updateSignature($signatureText);
}

echo $page->header('Manage Signature');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page;

    $signature = getSignature();
    echo "<form name = 'sub' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "  <table>\n";
    echo "    <thead>\n";
    echo "      <tr>\n";
    echo "        <th>Manage Signature</th>\n";
    echo "      </tr>\n";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    echo "      <tr>\n";
    echo "        <td><textarea name='signatureText' id='signatureText' rows='4' cols='60'>".$signature."</textarea></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>\n";
    echo "          <input type='submit' name='updateSig' id='updateSig' value='";
    if (isset($signature)) {
        echo "Update Signature'>\n";
    } else {
        echo "Submit Signature'>\n";
    }
    echo "        </td>\n";
    echo "      </tr>\n";
    echo "    </tbody>\n";
    echo "  </table>\n";
    echo "</form>\n";
}

function updateSignature($signatureText) {
    global $page;

    $sql = "
        INSERT INTO signature(userid, signaturetext) VALUES(".$page->user->userId.", '".$signatureText."')
        ON CONFLICT (userid)
        DO UPDATE SET signaturetext = '".$signatureText."'
    ";

    $result = $page->db->sql_execute_params($sql);
}

function getSignature() {
    global $page;

    $sql = "
        SELECT signaturetext FROM signature WHERE userid = ".$page->user->userId."
    ";

    $data = $page->db->get_field_query($sql);

    return $data;
}
?>