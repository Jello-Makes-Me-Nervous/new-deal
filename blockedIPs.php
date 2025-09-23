<?php
require_once('templateAdmin.class.php');

$page = new templateAdmin(LOGIN, SHOWMSG);

$blockIP    = optional_param('blockIP', NULL, PARAM_TEXT);
$block      = optional_param('block', NULL, PARAM_TEXT);
$unblock    = optional_param('unblock', NULL, PARAM_TEXT);

if(isset($block)) {
    blockIP($blockIP);
}
if(isset($unblock)) {
    unblockIP($unblock);
}

echo $page->header('Block IPs');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $USER;

    echo "<article>\n";//////////////////////////////
    echo "  <div class='entry-content'>\n";///////////////////
    echo "    <form name ='blockForm' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "      <table>\n";
    echo "        <thead>\n";
    echo "          <tr>\n";
    echo "            <th align='left'>USER</th>\n";
    echo "            <th align='left'>IP</th>\n";
    echo "            <th align='left'></th>\n";
    echo "          </tr>\n";
    echo "        </thead>\n";
    echo "        <tbody>\n";
    echo "          <tr>\n";
    echo "            <td></td>\n";
    echo "            <td><input type='text' name='blockIP' id='blockIP'></td>\n";
    echo "            <td><input type='submit' name='block' value='Add Blocked IP'></td>\n";
    echo "          </tr>\n";
    $blocked = getBlockedIPs();
    if (isset($blocked)) {
        foreach ($blocked as $block) {
            echo "          <tr>\n";
            echo "            <td>".$block['userid']."</td>\n";
            echo "            <td>".$block['blockeduserip']."</td>\n";
            echo "            <td><a href='?unblock=".$block['blockeduserip']."' >UNBLOCK</a></td>\n";
            echo "          </tr>\n";
        }
    }
    echo "        </tbody>\n";
    echo "      </table>\n";
    echo "    </form>\n";
    echo "  </div>\n";//////////////////////////////////////
    echo "</article>\n";///////////////////////////////////

}

function getBlockedIPs() {
    global $page;

    $sql = "
        SELECT b.userid, b.blockeduserip, u.username
          FROM blockedips b
          JOIN users u ON u.userid = b.userid
    ";
     $data = $page->db->sql_query_params($sql);

     return $data;
}

function blockIP($blockIP) {
    global $page;

    $sql = "
        SELECT userid
          FROM loginlog
         WHERE ipaddress = '".$blockIP."'
     ";

    $data = $page->db->sql_query_params($sql);

    $sql = "
        INSERT INTO blockedips( userid,  blockeduserip,  createdby)
                        VALUES(:userid, :blockeduserip, :createdby)
    ";

    $params = array();
    $params['userid']           = $data['0']['userid'];
    $params['blockeduserip']    = $blockIP;
    $params['createdby']        = $page->user->userId;

    $page->db->sql_execute_params($sql, $params);

}

function unblockIP($unblock) {
    global $page;

    $sql = "
        DELETE FROM blockedips WHERE blockeduserip = '".$unblock."'
    ";

    $page->db->sql_execute_params($sql);
    $page->messages->addSuccessMsg("You have remove the block from ".$unblock);
}

?>