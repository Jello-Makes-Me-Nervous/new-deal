<?php
require_once('templateMarket.class.php');

$page = new templateMarket(LOGIN, SHOWMSG);
$page->requireJS('scripts/shoppingCart.js');
$iMessaging = new internalMessage();

$blastListingId = optional_param('listingid', NULL, PARAM_INT);
$blast = NULL;

if ($blastListingId) {
    $blast = getBlast($blastListingId);
//    echo "Blast:<br />\n<pre>";var_dump($blast);echo "</pre><br />\n";
}

if (! $blast) {
    $page->messages->addErrorMsg("No blast specified/found");
}

echo $page->header('Blast');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $blast;

    if (! $blast) {
        return;
    }
    echo "<div class='entry-content'>\n";
    echo "  <h1>".$blast['title']."</h1>\n";
    echo "  <h2>".$blast['type']." BY ".$blast['listingdealer']."</h2>\n";
    echo "  <h3>Status: ".$blast['status']."</h3>\n";

    echo "  <div class='blast-desc'>".$blast['listingnotes']."</div>\n";

    if ($blast['picture']) {
        echo "  <div class='blast-pic'><img src='imageviewer.php?img=".$page->cfg->blastDocPath.$blast['picture']."' /></div>\n";
    }

    if ($blast['document']) {
        echo "  <div class='blast-doc'><iframe title='blast document' style='width:100%; height:500px;' src='imageviewer.php?img=".$page->cfg->blastDocPath.$blast['document']."'></iframe></div>\n";
    }

    if (! ($blast['listingdealerid'] == $page->user->userId)) {
        if ($blast['acceptoffers']) {
            echo "  <div class='blastofferbutton'><a href='blastoffer.php?listingid=".$blast['listingid']."' class='button'>Make Offer</a></div>\n";
        }
    } else {
        echo "  <div class='blasteditbutton'><a href='blastedit.php?action=edit&listingid=".$blast['listingid']."' class='button'>Edit</a></div>\n";
    }
    echo "</div>\n";
}

function getBlast($blastListingId) {
    global $page;

    $blast = null;

    $sql = "SELECT l.listingid, l.userid AS listingdealerid, u.username as listingdealer, l.type, l.title, l.listingnotes, l.picture, l.document, l.status, l.acceptoffers FROM listings l join users u on u.userid=l.userid where l.listingid=".$blastListingId;
    //echo "SQL:".$sql."<br />\n";
    if ($results = $page->db->sql_query($sql)) {
        $blast = reset($results);
    }

    return $blast;
}

?>