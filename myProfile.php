<?php
include_once('setup.php');
require_once('template.class.php');

$page = new template(LOGIN, SHOWMSG);

$editUserId    = optional_param('userid', $page->user->userId, PARAM_INT);
$authorId       = optional_param('authorId', NULL, PARAM_TEXT);
$dealerNote     = optional_param('dealerNote', NULL, PARAM_TEXT);
$dealerNoteId   = optional_param('dealerNoteId', NULL, PARAM_INT);
$rating         = optional_param('rating', NULL, PARAM_INT);
$updateNotes    = optional_param('updateNotes', NULL, PARAM_TEXT);
$updateRating   = optional_param('updateRating', NULL, PARAM_TEXT);

if (isset($updateRating)) {
    if (!NULL == (dealerRating($dealerId, $editUserId))) {
        insertUpdateRating($dealerId, $rating, $dealerNote, $dealerNoteId, $authorId);
    } else {
        insertUpdateRating($dealerId, $rating, $dealerNote, $dealerNoteId, $authorId, "insert");
    }
}

echo $page->header('My Profile');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $UTILITY, $editUserId;

    echo "<div style='margin: 20px; text-align: center;'>\n";
    echo "  <a href='userPreferences.php?userid=".$editUserId."' class='button'>User Preferences</a>\n";
    echo "  <a href='updatePassword.php?userid=".$editUserId."' class='button'>Change Password</a>\n";
    echo "  <a href='#' class='button'>Set Font Size?</a>\n";
    echo "  <a href='#' class='button'>Credit Card Info</a>\n";
    echo "  <a href='onVacation.php' class='button'>Go on Vacation</a>\n";
    echo "  <br /><br />".$UTILITY->getDealersName($editUserId)."(".$editUserId.")\n";
    echo "  <br />DealernetB2B member since: xxx\n";
    echo "  <table>\n";
    echo "    <tbody>\n";
    echo "      <tr>\n";
    echo "        <td>Transaction stuff here</td>\n";
    echo "      </tr>\n";
    echo "    </tbody>\n";
    echo "  </table>\n";
    echo "</div>\n";


    echo "<table class='outer-table'>\n";
    echo "  <tbody>\n";
    echo "    <tr>\n";
    echo "      <td class='double-table'>\n";
    echo "        <table>\n";
    echo "          <thead>\n";
    echo "            <tr>\n";
    echo "              <th>Bill-To/Payment Address</th>\n";
    echo "            </tr>\n";
    echo "          </thead>\n";
    echo "          <tbody>\n";
    echo "            <tr>\n";
    echo "              <td>\n";
    echo "               ".$UTILITY->formatAddress($editUserId, 1)."\n";
    echo "              </td>\n";
    echo "            </tr>\n";
    echo "          </tbody>\n";
    echo "        </table>\n";
    echo "      </td>\n";

    echo "      <td class='double-table'>\n";
    echo "        <table>\n";
    echo "          <thead>\n";
    echo "            <tr>\n";
    echo "              <th>Ship-To/Payment Address</th>\n";
    echo "            </tr>\n";
    echo "          </thead>\n";
    echo "          <tbody>\n";
    echo "            <tr>\n";
    echo "              <td>\n";
    echo "               ".$UTILITY->formatAddress($editUserId, 3)."\n";
    echo "              </td>\n";
    echo "            </tr>\n";
    echo "          </tbody>\n";
    echo "        </table>\n";
    echo "      </td>\n";
    echo "    </tr>\n";
    echo "  </tbody>\n";
    echo "</table>\n";

    echo "<table >\n";
    echo "  <thead>\n";
    echo "    <tr>\n";
    echo "      <th>Dealer Notes</th>\n";
    echo "    </tr>\n";
    echo "  </thead>\n";
    echo "  <tbody>\n";
    echo "    <tr>\n";
    echo "      <td>\n";
    echo "        <ul>\n";
    $dNotes = getDealerNotes();
    if (isset($dNotes)) {
        foreach ($dNotes as $dN) {
            echo "          <li>".$dN['dealernote']."</li>\n";
        }
    } else {
        echo "          <li>No notes</li>\n";

    }
    echo "        </ul>\n";
    echo "      </td>\n";
    echo "    </tr>\n";
    echo "  </tbody>\n";
    echo "</table>\n";

    echo "<a class='button' href='dealerNotes.php'>Edit Dealer Notes</a>\n";
}

function dealerRating($dealerId, $editUserId = NULL) {
    global $page, $dealerId, $editUserId;

    $sql = "
        SELECT dealerrating AS rating
          FROM dealerratings
         WHERE rateddealerid = ".$dealerId."
    ";
    if (isset($editUserId)) {
        $sql .= "
            AND rategivenid = ".$editUserId."
        ";
    }
    $data = $page->db->get_field_query($sql);

    return $data;
}


function getDealerInfo() {
    global $page, $editUserId;

    $sql = "
        SELECT addresstypeid, companyname, phone, fax, street, street2, city, state, zip, country
          FROM usercontactinfo
         WHERE userid = ".$editUserId."
    ";
     $data = $page->db->sql_query($sql);

      return $data;
}

function getDealerNotes() {
    global $page, $editUserId;
    
    $params = array();
    $params['dealerid'] = $editUserId;
    $params['authorid'] = $editUserId;
    $sql = "
        SELECT dealernote, dealernoteid
          FROM dealernotes
         WHERE dealerid = :dealerid
           AND authorid = :authorid
         ORDER BY createdate
     ";
    $data = $page->db->sql_query_params($sql, $params);

     return $data;
}
?>