<?php
require_once('templateMarket.class.php');

$page = new templateMarket(LOGIN, SHOWMSG);

$search     = optional_param('search', NULL, PARAM_INT);
$supplyId   = optional_param('supplyId', NULL, PARAM_INT);

echo $page->header('Supplies');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $supplyId, $search, $UTILITY;

    if (isset($search)) {
//TODO
    }

    if(!isset($search)) {
        echo "<a href='supplies.php?search=1'>SEARCH ALL SUPPLIES</a>\n";
        echo "<br />\n";
        echo "<ul>\n";
        $data = getSupplyCategories();
        foreach ($data as $d) {
            echo "  <li>\n";
            echo "    <a href='supplySummary.php?categoryid=".$d['categoryid']."'>".$d['categoryname']."</a>\n";
            echo "  </li>\n";
        }
        echo "</ul>\n";
    }

}


function getSupplyCategories() {
    global $page;

    $sql = "
        SELECT categoryid, categoryname FROM categories WHERE categorytypeid = 3 AND active = 1
    ";

    $data = $page->db->sql_query($sql);

    return $data;

}

function getSupplySubCategories($categoryId) {
    global $page;

    $sql = "
        SELECT subcategoryid, subcategoryname FROM subcategories WHERE categoryid = ".$categoryId." AND active = 1
    ";

    $data = $page->db->sql_query($sql);

    return $data;

}

function getSupplies() {
    global $page;

    $sql = "
        SELECT l.listingid, l.categoryid, l.subcategoryid, l.boxtypeid, l.type,
               l.minquantity, l.quantity, l.dprice, l.listingnotes, l.picture,
               c.categoryname,
    ";

    $data = $page->db->sql_query($sql);

    return $data;

}

?>