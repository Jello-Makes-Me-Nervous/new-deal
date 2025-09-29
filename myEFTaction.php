<?php
require_once('template.class.php');

$page = new template(LOGIN, SHOWMSG);
$page->requireJS('scripts/datepicker.js');
$page->requireJS('scripts/eft.js');
$page->requireJS('scripts/jquery.autocomplete.js');


global $MESSAGES;

$eft = new electronicFundsTransfer();

$userId = optional_param('userId', NULL, PARAM_INT);

$eftAction = optional_param('action', NULL, PARAM_TEXT);

if (($eftAction == 'cashin') || ($eftAction == 'transfer')) {
    $autolookup = "
        $('#paydealername').devbridgeAutocomplete({
            minChars: 4,
            lookup: paydealernames,
            onSelect: function (suggestion) {
                document.trans.tapaydealerid.value = suggestion.userid;
            }
        });
    ";

    $page->jsInit($autolookup);
}


echo $page->header('EFT');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $eft, $eftAction, $MESSAGES, $UTILITY;
    
    switch ($eftAction) {
        CASE 'cashin':
            echo $eft->displayEFTCashInForm();
            break;
        CASE 'cashout':
            echo $eft->displayEFTCashOutForm();
            break;
        CASE 'deposit':
            echo $eft->displayEFTDepositForm();
            break;
        CASE 'redeem':
            echo $eft->displayEFTRedeemForm();
            break;
        CASE 'transfer':
            echo $eft->displayEFTTransferForm();
            break;
        CASE 'pay':
            echo $eft->displayEFTOfferForm();
            break;
        DEFAULT:
            echo "No action selected<br />\n";
            break;
    }            
    
    if (($eftAction == 'cashin') || ($eftAction == 'transfer')) {
        $members = getMembers();
        $js = "<SCRIPT LANGUAGE='JavaScript'>\n";
        $js .= "  var paydealernames = [\n";
        foreach($members as $m) {
            $js .= "    { value: \"".$m["username"]."\", userid: \"".$m["userid"]."\" },\n";
        }
        $js .= "    { value: '', userid: '' }\n";
        $js .= "  ];\n";
        $js .= "</SCRIPT>\n";
        echo $js;
    }
}

function getMembers() {
    global $page;

    $sql = "
        select u.userid, u.username
          from users u
          join assignedrights ar on ar.userid=u.userid and ar.userrightid=1
        order by u.username
    ";

    $members = $page->db->sql_query($sql);

    return ($members);
}

?>