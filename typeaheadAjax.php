<?php
require_once('templateHome.class.php');

$page = new templateHome(LOGIN, SHOWMSG);

$autolookup = "
    $('#member').devbridgeAutocomplete({
        serviceUrl: '/getmembers.php',
        paramName: 'member',
        onSelect: function (suggestion) {
            document.bob.userid.value = suggestion.userid;
        }
    });
";

// This messes w/ the b2b stylesheets, so if important we need to figure out which is in conflict.
//$page->requireStyle('/styles/autocomplete.css');
$page->requireJS('/scripts/jquery.autocomplete.js');
$page->jsInit($autolookup);

echo $page->header('Dealernet');
echo mainContent();
echo $page->footer(true);

function mainContent() {

    echo "          <form name='bob' id='bob' method='post' action='typeahead.php'>\n";
    echo "            <article>\n";
    echo "              <div class='entry-content'>\n";
    echo "                <label for='member'>Member:</label>\n";
    echo "                <input type='text' name='member' id='member' value='' style='width:300px;'>\n";
    echo "                <input type='hidden' name='userid' id='userid' value=''>\n";
    echo "              </div> <!-- entry-content -->\n";
    echo "            </article>\n";
    echo "          </form>\n";
    $members = getMembers();
    $js = "<SCRIPT LANGUAGE='JavaScript'>\n";
    $js .= "  var members = [\n";
    foreach($members as $m) {
        $js .= "    { value: \"".$m["username"]."\", userid: \"".$m["userid"]."\" },\n";
    }
    $js .= "    { value: '', userid: '' }\n";
    $js .= "  ];\n";
    $js .= "</SCRIPT>\n";
    echo $js;
}

function getMembers() {
    global $page;

    $sql = "
        select userid, username
          from users
        order by username
    ";

    $members = $page->db->sql_query($sql);

    return ($members);
}

?>