<?php
require_once('templateMarket.class.php');

$page = new templateMarket(LOGIN, SHOWMSG);

$dealerId  = optional_param('dealerid', NULL, PARAM_INT);
$blasterId  = optional_param('blasterid', NULL, PARAM_INT);
$includeInactive  = optional_param('inactive', NULL, PARAM_INT);
$blastAdLink  = optional_param('blastad', NULL, PARAM_INT);

$hasBlasts = ($page->user->hasUserRight('Email Blast Limited') || $page->user->hasUserRight('Email Blast Unlimited')) ? true : false;
$myBlasts = ($hasBlasts && (!empty($dealerId)) && ($dealerId == $page->user->userId)) ? true : false;

$blasts = getBlasts($dealerId, $includeInactive, $blasterId);

if ($blastAdLink && $blasterId && (! $dealerId) && is_array($blasts) && (count($blasts) == 1)) {
    $blast = reset($blasts);
    header("location:blastview.php?listingid=".$blast['listingid']);
}

$pagetitle = ($hasBlasts && ($dealerId == $page->user->userId)) ? "My Blasts" : "Blasts";

if ($myBlasts) {
    $hasLogo = $page->db->get_field_query("SELECT listinglogo FROM userinfo WHERE userid=".$page->user->userId);
    if (! $hasLogo) {
        $page->messages->addWarningMsg("You should have a listing logo if you are going to have blasts. Contact the administrator to set one up.");
    }
}

if (!($blasts || $myBlasts)) {
    $page->messages->addInfoMsg("There are no blasts available at this time.");
}

echo $page->header($pagetitle);
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $hasBlasts, $myBlasts, $blasts, $dealerId, $blasterId, $includeInactive;


    if ($myBlasts) {
        echo "<br />\n<a href='blastedit.php?action=add' name='createblastbtn' id='createblastbtn' class='button'>Add New</a><br /><br />\n";
        if ($includeInactive) {
            echo "<a href='blasts.php?dealerid=".$page->user->userId."&inactive=0'>Exclude inactive</a><br />\n";
        } else {
            echo "<a href='blasts.php?dealerid=".$page->user->userId."&inactive=1'>Include inactive</a><br />\n";
        }
    } else {
        $blastDDM = getBlasterDDM($blasterId);
        if ($blastDDM) {
            echo "<form id='sub' name='sub' action='blasts.php' method='post'>\n";
            echo "Blaster: ".$blastDDM;
            echo "<br />\n";
            echo "</form><br />\n";
        }
    }

    if (!empty($blasts)) {
        foreach ($blasts as $data ) {
            echo "<div class='card'>";
            if ($data['picture']) {
                echo "  <img src='imageviewer.php?img=".$page->cfg->blastDocPath.$data['picture']."'>";
            }
            echo "  <h2>".$data['title']."</h2>";
            echo "<p>Type: ".$data['type']."</p>";
            if ($myBlasts) {
                echo "<p>Status: ".$data['status']."</p>";
            } else {
                echo "<p>By: ".$data['dealername']."</p>";
                echo "<p>Created: ".$data['createdt']."</p>";
            }
            $link = "blastview.php?listingid=".$data['listingid'];
            echo "<p><button><a href='".$link."'>Read More</a>";
            echo "</div>";
        }
    }
}

function getBlasts($dealerId, $includeInactive, $blasterId) {
    global $page;

    $returnData = null;

    $sql = "SELECT l.listingid, l.title, u.username as dealername, l.type, l.status, l.picture, l.document, l.listingnotes, inttommddyyyy_slash(l.createdate) as createdt, inttommddyyyy_slash(l.modifydate) as modifydt
            FROM listings l
                JOIN categories c ON  c.categoryid = l.categoryid AND c.categorytypeid=".LISTING_TYPE_BLAST."
                JOIN users u on u.userid=l.userid
                JOIN assignedrights ar ON ar.userid=u.userid AND ar.userrightid in (".USERRIGHT_LIMITED_BLAST.",".USERRIGHT_UNLIMITED_BLAST.")
            ";
    if ($dealerId) {
        $sql .= "WHERE l.userid=".$dealerId." ";
        if (($dealerId != $page->user->userId) || (! $includeInactive)) {
            $sql .= " AND l.status = 'OPEN' ";
        }
    } else {
        $sql .= " WHERE l.status = 'OPEN' ";
    }

    if ($blasterId) {
        $sql .= " AND l.userid=".$blasterId." ";
    }

    $sql .= " ORDER BY l.createdate DESC";

    //echo "<pre>".$sql."</pre>";
    $returnData = $page->db->sql_query($sql);

    return $returnData;
}

function getBlasterDDM($blasterId) {
    global $page;

    $output = NULL;
    
    $sql = "SELECT u.userid, u.username
            FROM listings l
                JOIN categories c ON  c.categoryid = l.categoryid AND c.categorytypeid=".LISTING_TYPE_BLAST."
                JOIN users u on u.userid=l.userid
            GROUP BY u.userid, u.username
           ";
    if ($dealers = $page->db->sql_query($sql)) {
        $onChange = " onchange = \"submit();\"";
        $output = "          ".getSelectDDM($dealers, "blasterid", "userid", "username", NULL, $blasterId, "All", 0, NULL, NULL, $onChange)."\n";
    }

    return $output;
}

?>