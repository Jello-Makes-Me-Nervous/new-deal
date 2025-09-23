<?php
include_once('setup.php');
require_once('template.class.php');

$page = new template(LOGIN, SHOWMSG);

$dealerId   = optional_param('dealerId', $page->user->userId, PARAM_INT);
$add        = optional_param('add', NULL, PARAM_TEXT);
$deleteId   = optional_param('deleteId', NULL, PARAM_INT);
$note       = optional_param('note', NULL, PARAM_RAW);

$isMyProfile = ($dealerId == $page->user->userId) ? true : false;
$authorId = $page->user->userId;

if (isset($add)) {
    addNote($dealerId, $authorId, $note);
}
if (isset($deleteId)) {
    deleteNote($deleteId);
}

echo $page->header('My Notes');
echo mainContent();
echo $page->footer(true);


function mainContent() {
    global $page, $UTILITY, $dealerId, $authorId;
    echo "<div style='margin: 20px; text-align: center;'>\n";
    echo "<a href='dealerProfile.php?dealerId=".$dealerId."' title='Return to dealer profile'>".$UTILITY->getDealersName($dealerId)."(".$dealerId.")</a>\n";
    echo "</div>\n";

    echo "<div>\n";
    echo "<p>\n";
    echo "<ul>\n";
    $dNotes = getDealerNotes($dealerId, $authorId);
    if (isset($dNotes)) {
        foreach ($dNotes as $dN) {
            echo "  <li  style='margin-top: 30px;'>";
            echo $dN['dealernote'];
            echo "&nbsp;&nbsp;&nbsp;<a class='fas fa-trash-alt' title='Delete this note' href='dealerNotes.php?dealerId=".$dealerId."&deleteId=".$dN['dealernoteid']."' onclick=\"javascript: return confirm('Are you sure you want to delete this note?');\"></a>\n";
            echo "</li>\n";
        }
    } else {
        echo "  <li>No notes</li>\n";
    }
    echo "</ul>\n";
    echo "</p>\n";
    echo "<table>\n";
    echo "  <thead>\n";
    echo "    <tr>\n";
    echo "      <th>Add Dealer Note</th>\n";
    echo "    </tr>\n";
    echo "  </thead>\n";
    echo "  <tbody>\n";
    echo "    <tr>\n";
    echo "      <td>\n";
    echo "        <form name ='sub2' action='dealerNotes.php?dealerId=".$dealerId."' method='post'>\n";
    echo "        New Note: <input type='text' name='note' id='note' size='80'><br /><br />\n";
    echo "        <input class='button' type='submit' name='add' value='Add'>&nbsp;&nbsp;&nbsp;&nbsp;\n";
    echo "        <input class='button' type='submit' name='reset' value='Reset'>\n";
    echo "        </form>\n";
    echo "      </td>\n";
    echo "    </tr>\n";
    echo "  </tbody>\n";
    echo "</table>\n";
    echo "</div>\n";
}

function getDealerNotes($dealerId, $authorId) {
    global $page;

    $sql = "
        SELECT dealernote, dealernoteid
          FROM dealernotes
         WHERE dealerid = ".$dealerId."
           AND authorid = ".$authorId."
         ORDER BY createdate
     ";
    $data = $page->db->sql_query_params($sql);
     return $data;

}

function addNote($dealerId, $authorId, $note) {
    global $page;

    $cleanNote = strip_tags($note);
    $params = array();
    $params['dealerid'] = $dealerId;
    $params['authorid'] = $authorId;
    $params['dealernote'] = $cleanNote;
    $params['createdby'] = $page->user->username;
    $sql = "INSERT INTO dealernotes( dealerid,  authorid,  dealernote,  createdby)
            VALUES(:dealerid, :authorid, :dealernote, :createdby)";

    $result = $page->db->sql_execute_params($sql, $params);
    if($result) {
        $page->messages->addSuccessMsg("Added a new note.");
    } else {
        $page->messages->addErrorMsg("Error");
    }
}

function deleteNote($dealeteId) {
    global $page;

    $sql = "DELETE FROM dealernotes WHERE dealernoteid = ".$dealeteId;

    $result = $page->db->sql_execute_params($sql);
    if($result) {
        $page->messages->addSuccessMsg("Note Deleted.");
    } else {
        $page->messages->addErrorMsg("Error");
    }

}
?>