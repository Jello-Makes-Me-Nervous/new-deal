<?php
require_once('templateAdmin.class.php');

$page = new templateAdmin(LOGIN, SHOWMSG);

$creditInfoList = getCreditInfoList();

echo $page->header('Dealer Billing Updates');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $creditInfoList;

    if ($creditInfoList && is_array($creditInfoList) && (count($creditInfoList) > 0)) {
        echo "<h3>Billing Info Updates</h3>\n";
        echo "<table>\n";
        echo "  <theader>\n";
        echo "    <tr><th>Dealer</th><th>Status</th><th>Modified</th></tr>\n";
        echo "  </theader>\n";
        echo "  <tbody>\n";
        foreach ($creditInfoList as $creditInfo) {
            $detailURL = "<a href='adminCreditInfo.php?dealerid=".$creditInfo['userid']."'>".$creditInfo['username']." (".$creditInfo['userid'].")</a>";
            echo "    <tr><td>".$detailURL."</td><td>".$creditInfo['status']."</td><td>".date('m/d/Y H:i:s', $creditInfo['modifydate'])."</td></tr>\n";
        }
        echo "  </tbody>\n";
        echo "</table>\n";
    }
}

function getCreditInfoList() {
    global $page;
    
    $sql = "SELECT bi.billinginfoid, bi.userid, bi.status, u.username
                , bi.createdby, bi.createdate, bi.modifiedby, bi.modifydate
            FROM billinginfo bi
            JOIN users u on u.userid=bi.userid
            ORDER by bi.modifydate";
    $creditInfoList = $page->db->sql_query($sql);
    
    if (! ($creditInfoList && is_array($creditInfoList) && (count($creditInfoList) > 0))) {
        $page->messages->addInfoMsg("There are no pending credit info updates.");
    }
    
    return $creditInfoList;    
}
    

?>