<?php
require_once('templateMarket.class.php');

$page = new templateMarket(LOGIN, SHOWMSG);

$dealerId  = optional_param('dealerId', NULL, PARAM_INT);

$classifieds = getClassifieds($dealerId);

echo $page->header('Classifieds');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $classifieds, $dealerId;

    echo "<article>\n";
    echo "  <div class='entry-content'>\n";
    if (!empty($classifieds)) {
        if (!empty($dealerId)) {
            echo "<a href='classifieds.php'>View All</a>\n";
        }
        echo "    <table>\n";
        echo "      <tbody>\n";

        foreach ($classifieds as $c) {
            echo "      <tr>\n";
            echo "        <td>\n";
            echo "          <a href=''>".$c['type'].": ".$c['title']."</a><br />\n";
            if (!empty($c['picture'])) {
                echo "  <img src='".$this->utility->getPrefixListingImageURL($c['picture'])."' alt='Classified picture' width='50px' height='50px'>\n";
            }
            echo "          <a href='dealerProfile.php?dealerId=".$c['userid']."'>".$c['username']."</a> <strong>Price:</strong> ".$c['price']." <strong>Quantity:</strong> ".$c['quantity']." <strong>Last Updated:</strong> ".$c['updated']."<br />\n";
            echo "        ".$c['listingnotes']."<br /><br />\n";
            echo "        </td>\n";
            echo "        <td><a href='classifieds.php?dealerId=".$c['userid']."'>View Dealers Classifieds</a></td>\n";
            echo "      </tr>\n";
        }
        echo "      </tbody>\n";
        echo "    </table>\n";
    }
    echo "  </div>\n";
    echo "</article>\n";
}

function getClassifieds($dealerId) {
    global $page;

    $dealerId = (!empty($dealerId)) ? $dealerId :  "";

    $sql = "
        SELECT l.type, l.quantity, l.dprice as price, l.listingnotes, l.listingnotes as title,
               inttommddyyyy_slash(l.modifydate) AS updated, l.userid, l.listingid,
               u.username, l.picture
          FROM listings     l
          JOIN users        u   ON u.userid = l.userid
         WHERE l.status     = 'OPEN'
           AND l.categoryid = 1261";
    if (!empty($dealerId)) {
       $sql .= "
           AND l.userid     = ".$dealerId."";
    }
    $sql .= "
        ORDER BY l.modifydate ASC";

//    echo "<pre>".$sql."</pre>";
    $data = $page->db->sql_query_params($sql);

    return $data;
}

?>