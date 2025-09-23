<?php
require_once('template.class.php');

$page = new template(LOGIN, SHOWMSG);

//echo "<pre>";
//print_r($_POST);
//echo "</pre>";
$member = optional_param("query", "", PARAM_RAW);
$members = getMembers($member);
//$data = "{";
//$data .= "  \"query\": member,";
//$data .= "  \"suggestions\": [";
//foreach($members as $m) {
//    $data .= "    { value: \"".$m["username"]."\", userid: \"".$m["userid"]."\" },";
//}
//$data .= "    { value: '', userid: '' }";
//$data .= "  ];";
//$data .= "}";
//$obj = new stdClass();
//$obj->suggestions = $data;
//header('Content-Type: application/json');
$data = array();
foreach($members as $m) {
    $data[] = array("value"=>$m["username"], "userid"=>$m["userid"]);
}
header("Content-type: application/json");
echo json_encode($data);

function getMembers($member="") {
    global $page;

    $sql = "
        select userid, username
          from users
         where username like '%".$member."%'
        order by username
    ";

    $members = $page->db->sql_query($sql);

    return ($members);
}

?>