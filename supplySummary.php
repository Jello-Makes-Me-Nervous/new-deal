<?php
require_once('templateMarket.class.php');

$page = new templateMarket(LOGIN, SHOWMSG);
$page->requireJS('scripts/modalPopup.js');

$listing = new listing();
$Cart = new shoppingcart($USER->userId);

$addToCart      = optional_param('addToCart', NULL, PARAM_TEXT);
$boxTypeId      = optional_param('boxTypeId', NULL, PARAM_INT);
$categoryid     = optional_param('categoryid', NULL, PARAM_INT);
$find           = optional_param('find', NULL, PARAM_TEXT);
$keyword        = optional_param('keyword', NULL, PARAM_TEXT);
$subcategoryid  = optional_param('subcategoryid', NULL, PARAM_INT);
$supply         = optional_param('supply', NULL, PARAM_INT);
$type           = optional_param('type', NULL, PARAM_TEXT);
$year           = optional_param('year', NULL, PARAM_TEXT);


if (!empty($addToCart) && is_array($supply)) {
    $i = 0;
    foreach ($supply as $listId) {
        $l = new listing($listId);
        $Cart->addToCart($listId, $USER->userId, $l->listingUserId, $l->dprice, $l->quantity, $l->minQuantity, $l->categoryId, $l->subCategoryId, $l->boxtypeId, $l->listingNotes, $l->year, $USER->userId);
        unset($l);
        $i++;
    }
    $page->messages->addSuccessMsg("You have added ".$i." new Item(s) to your <a href='shoppingCart.php' class='button'>Cart</a>");
    //header('location:shoppingCart.php');//change this or add continue shopping button to shopping cart
}

echo $page->header('Supply Summary');
echo mainContent();
echo $page->footer(true);


function mainContent() {
    global $page, $boxTypeId, $categoryid, $find, $keyword, $subcategoryid, $type, $year, $UTILITY, $supplyNoteTruncateSize;

    echo "<div class='page-header-left'>\n";
    echo "  <form id='sub' class='filters' name='sub' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "    <label>Categories</label>\n";
    echo categoryDDM($categoryid);
    echo "    <label>SubCategory</label>\n";
    echo subCategoryDDM($categoryid, $subcategoryid);
    echo "    <label>Search</label>\n";
    echo "    <input placeholder= 'Member ID or Keyword' type='text' name='keyword' value='".$keyword."'> <input type='submit' name='find' value='Go'>\n";
    echo "  </form>\n";
    echo "</div>\n";
    echo "\n";

    if (!empty($categoryid)) {
        $data = getListings($categoryid, $subcategoryid, $type, $keyword);
        echo "<form id='sub' name='offer' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
        echo "  <div style='float:left;padding-left:25px;'>\n";
        echo "    <input class='button' type='submit' name='addToCart' value='Add To Cart'>\n";
        echo "  </div>\n";
        $url = "listingCreate.php?listingtypeid=".LISTING_TYPE_SUPPLY."&categoryid=".$categoryid."&subcategoryid=".$subcategoryid;
        echo "  <div style='float:right;padding-right:25px;padding-top:25px;'>\n";
        echo "    <a class='button' title='Add New Listing' href='".$url."' target='_blank'>Add New Listing</a>\n";
        echo "  </div>\n";
        echo "  <div style='clear:both;'></div>\n";
        echo "  <div class='card-wrap-outer'>\n";
        if (!empty($data)) {
            foreach ($data as $d) {
                echo "  <div class='supply-card'>\n";
                echo "    <input type='checkbox' name='supply[]' value='".$d['listingid']."'>\n";
                echo "    <div class='supply-details'>\n";
                if (!empty($d['picture'])) {
                    if ($imgURL = $page->utility->getPrefixListingImageURL($d['picture'])) {
                        $img = "<img src='".$imgURL."' alt='Supplies picture' >\n";
                        echo "    <a href='".$imgURL."' alt='Supplies picture' target='_blank'>".$img."</a>\n";
                    } else {
                        echo "    <img src='/images/noImageProvided.png' alt='Supplies picture'>\n";
                    }
                } else {
                    echo "    <img src='/images/noImageProvided.png' alt='Supplies picture'>\n";
                }
                echo "    ".$d['subcategorydescription']."<br />\n";
                $fullNoteLen = strlen($d['listingnotes']);
                if ( $fullNoteLen > $supplyNoteTruncateSize) {
                    echo substr($d['listingnotes'],0,$supplyNoteTruncateSize);
                    $panel = "";
                    $panel .= " <a href='#' id='modalLink".$d['listingid']."'>(more ...)</a>\n";
                    $panel .= "<div id='myModal".$d['listingid']."' class='modal'>\n";
                    $panel .= "  <!-- Modal content -->\n";
                    $panel .= "  <div class='modal-content'>";
                    $panel .= "    <span class='ModalClose".$d['listingid']."'><i class='fa-solid fa-circle-xmark'></i></span><br />\n";
                    $panel .= "    ".$d['listingnotes'];
                    $panel .= "  </div>\n";
                    $panel .= "</div>\n";
                    $panel .= "<script>makeModalPopup(".$d['listingid'].");</script>\n";
                    echo $panel."<br />\n";
                } else {
                    echo "    ".$d['listingnotes']."<br />\n";
                }
                echo "    <strong>".$d['type']." By ".$d['username']."</strong><br />\n";
                echo "    Price: ".$d['dprice']."\n";
                echo "    </div>\n";
                echo "  </div>\n";
            }
        } else {
            echo "<span style='color:red;'><b>No supplies found.</b></span>";
        }
        echo "</div>";
    } else {
        echo "No Results\n";
    }
    if (!empty($data)) {
        echo "  <div style='float:left;padding-left:25px;'>\n";
        echo "    <input class='button' type='submit' name='addToCart' value='Add To Cart'>\n";
        echo "  </div>\n";
        $url = "listingCreate.php?listingtypeid=".LISTING_TYPE_SUPPLY."&categoryid=".$categoryid."&subcategoryid=".$subcategoryid;
        echo "  <div style='float:right;padding-right:25px;padding-top:25px;'>\n";
        echo "    <a class='button' title='Add New Listing' href='".$url."' target='_blank'>Add New Listing</a>\n";
        echo "  </div>\n";
        echo "  <div style='clear:both;'></div>\n";
    }
    echo "</form>\n";

    echo "<script language = javascript>\n";
    echo getSubCatJS();
    echo "</script>\n";

}

function getSupplyCategories() {
    global $page;

    $sql = "
        SELECT categoryid, categoryname
          FROM categories
         WHERE categorytypeid = 3
           AND active = 1
        ORDER BY categoryname
    ";

    $data = $page->db->sql_query($sql);

    return $data;

}

function categoryDDM($categoryid = NULL) {
    global $UTILITY;

    $onChange = " onchange = \"$('#subcategoryid').val('');submit();\"";
    $output = "          ".getSelectDDM(getSupplyCategories(), "categoryid", "categoryid", "categoryname", NULL, $categoryid, NULL, 0, NULL, NULL, $onChange)."\n";

    return $output;
}

function getSupplySubCategories() {
    global $page;

    $sql = "
        SELECT c.categoryid, c.categoryname, s.subcategoryname, s.subcategoryid
          FROM categories       c
          JOIN subcategories    s   ON s.categoryid = c.categoryid
         WHERE c.categorytypeid = 3
           AND s.active         = 1
        ORDER BY subcategoryname
    ";

    $data = $page->db->sql_query($sql);

    return $data;

}

function getSubCatJS() {

    $rs = getSupplySubCategories();

    $output = "\n";
    $output .= "  var subcatdata = [\n";
    foreach ($rs as $sc) {
         $output .= "    [".$sc["categoryid"].", '".addslashes($sc["categoryname"])."', ".$sc["subcategoryid"].", '".addslashes($sc["subcategoryname"])."'],\n";
    }
    $output .= "    [0,'',0,'']\n";
    $output .= "  ];\n";

    return $output;
}


function subCategoryDDM($categoryid = NULL, $subcategoryid = NULL) {
    global $UTILITY;

    if (!empty($categoryid)) {
        $rs = $UTILITY->getSubCategories($categoryid, 1);
    } else {
        $rs = array();
        $rs[] = array("subcategoryid" => 0, "subcategoryname" => "");
    }
    $onChange = " onchange = \"submit();\"";
    $output = "          ".getSelectDDM($rs, "subcategoryid", "subcategoryid", "subcategoryname", NULL, $subcategoryid, 'Select', 0, NULL, NULL, $onChange)."\n";

    return $output;
}

function getListings($categoryid = null, $subcategoryid = NULL, $type = NULL, $keyword = NULL) {
    global $page;

    $sql = "
        SELECT l.listingid, l.minquantity, l.quantity, l.listingnotes, l.picture, l.dprice, l.type,
               c.categoryname, c.categorydescription, s.subcategoryname, s.subcategorydescription, u.username
          FROM listings             l
          JOIN categories           c   ON  c.categoryid    = l.categoryid
          JOIN subcategories        s   ON  s.subcategoryid = l.subcategoryid
          JOIN users                u   ON  u.userid        = l.userid
          JOIN userinfo             ui  ON  ui.userid       = l.userid
                                        AND ui.userclassid  = ".USERCLASS_VENDOR."
	      JOIN assignedrights       eur ON  eur.userid      = l.userid
	                                    AND eur.userrightid = ".USERRIGHT_ENABLED."
	      LEFT JOIN assignedrights  stl ON  stl.userid      = l.userid
	                                    AND stl.userrightid = ".USERRIGHT_STALE."
         WHERE l.status         = 'OPEN'
           AND c.categorytypeid = 3
    ";

    if ($categoryid != NULL) {
        $sql .= "
           AND l.categoryid     = ".$categoryid;
    }
    if (isset($subcategoryid) && ($subcategoryid > 0)) {
        $sql .= "
           AND l.subcategoryid  = ".$subcategoryid;
    }
    if ($type != NULL) {
        $sql .= "
           AND type             = '".$type."'";
    }
    if ($keyword != NULL) {
        $key = strtolower($keyword);
        $k = trim($key);
        $sql .= "
           AND (LOWER(u.username) LIKE '%".$k."%'
                OR LOWER(s.subcategoryname) LIKE '%".$k."%'
                OR LOWER(l.listingnotes) LIKE '%".$k."%')";
    }

    //echo "<pre>".$sql."</pre>";
    $data = $page->db->sql_query($sql);

    return $data;
}


?>