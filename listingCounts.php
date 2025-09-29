<?php

require_once('templateAdmin.class.php');
$page = new templateAdmin(LOGIN, SHOWMSG);

$isAdmin = $page->user->hasUserRight("ADMIN");
if (!$isAdmin) {
    $page->messages->addErrorMsg("ERROR: You do not have access to view this page.");
} elseif (!empty(optional_param("search", NULL, PARAM_RAW)) || !empty(optional_param('categoryid', NULL, PARAM_INT))) {
    $boxtypeid      = optional_param('boxtypeid', NULL, PARAM_INT);
    $categoryid     = optional_param('categoryid', NULL, PARAM_INT);
    $subcategoryid  = optional_param('subcategoryid', NULL, PARAM_INT);
    $year           = optional_param('year', NULL, PARAM_TEXT);

    $data = getTotals($categoryid, $subcategoryid, $boxtypeid, $year);
}

echo $page->header('Listing Counts');
if ($isAdmin) {
    echo mainContent();
}
echo $page->footer(true);

function mainContent() {
    global $boxtypeid, $categoryid, $data, $subcategoryid, $year;

    echo "<article>\n";
    echo "  <div class='entry-content'>\n";
    echo "    <form name ='search' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "      <table class='table-condensed'>\n";
    echo "        <thead>\n";
    echo "          <tr>\n";
    echo "            <th>Categorey:</th>\n";
    echo "            <th>Subcategory:</th>\n";
    echo "            <th>Box Type:</th>\n";
    echo "            <th colspan='2'>Year:</th>\n";
    echo "          </tr>\n";
    echo "          </thead>\n";
    echo "          <tbody>\n";
    echo "          <tr>\n";
    echo "            <td>\n";
    $cats = getCategories();
    echo getSelectDDM($cats, "categoryid", "categoryid", "categorydescription", NULL, $categoryid, "Select", 0, NULL, NULL, "onchange='this.form.submit(search)'");
    echo "            </td>\n";
    $scddm = "";
    $btddm = "";
    $yrddm = "";
    $searchbtn = "";
    if (!empty($categoryid))  {
        $subcats = getSubcategories($categoryid, $boxtypeid, $year);
        $scddm = getSelectDDM($subcats, "subcategoryid", "subcategoryid", "subcategoryname", "Select a Category", $subcategoryid, "Select", 0, NULL, NULL, "onchange='this.form.submit(search)'");
        $boxes = getBoxtypes($categoryid, $boxtypeid, $year);
        $btddm = getSelectDDM($boxes, "boxtypeid", "boxtypeid", "boxtypename", NULL, $boxtypeid, "Select", 0, NULL, NULL, "onchange='this.form.submit(search)'");
        $years = getYears($categoryid, $subcategoryid, $boxtypeid);
        $yrddm = getSelectDDM($years, "year", "year", "year", NULL, $year, "Select", 0, NULL, NULL, "onchange='this.form.submit(search)'");
        $searchbtn = "              <input type='submit' name='search' value='Search'\n";
    }
    echo "            <td>\n";
    echo $scddm;
    echo "            </td>\n";
    echo "            <td>\n";
    echo $btddm;
    echo "            </td>\n";
    echo "            <td>\n";
    echo $yrddm;
    echo "            </td>\n";
    echo "            <td class='center'>\n";
    echo $searchbtn;
    echo "            </td>\n";
    echo "          </tr>\n";
    echo "        </tbody>\n";
    echo "      </table>\n";

    echo "    </form>\n";
    echo "    <table border='1'>\n";
    echo "      <thead>\n";
    echo "        <tr>\n";
    echo "          <th>Product</th>\n";
    echo "          <th>Listing Status</th>\n";
    echo "          <th>Listing Type</th>\n";
    echo "          <th>Count</th>\n";
    echo "        </tr>\n";
    echo "      </thead>\n";
    echo "      <tbody>\n";
    $total = 0;
    $prevproduct = "";
    if (!empty($data)) {
        foreach ($data as $d) {
            echo "        <tr>\n";
            $product = $d['year']." ".$d['subcategoryname']." ".$d['categorydescription']." ".$d['boxtypename'];
            if ($product == $prevproduct) {
                $link = "";
            } else {
                $url = "listing.php?categoryid=".$categoryid."&subcategoryid=".$d["subcategoryid"]."&boxtypeid=".$d['boxtypeid']."&year=".$d['year'];
                $link = "<a href='".$url."' target='_blank'>".$product."</a>";
                $prevproduct = $product;
            }
            echo "            <td>".$link."</td>\n";
            echo "          <td>".$d['status']."</td>\n";
            echo "          <td>".$d['type']."</td>\n";
            echo "          <td>".$d['counts']."</td>\n";
            echo "        </tr>\n";
            $total += $d['counts'];
        }
        echo "        <tr>\n";
        echo "          <th class='right'>TOTAL</th>\n";
        echo "          <td></td>\n";
        echo "          <td></td>\n";
        echo "          <td>".$total."</td>\n";
        echo "        </tr>\n";
    }
    echo "      </tbody>\n";
    echo "    </table>\n";
    echo "  </div>\n";
    echo "</article>\n";
    echo "<br />\n";

}

function getCategories() {
    global $page;

    $sql = "
        SELECT categorydescription, categoryid
          FROM categories
         WHERE active = 1
         ORDER BY categorydescription
    ";

    $result = $page->db->sql_query_params($sql);

    return $result;
}

function getTotals($categoryid = NULL, $subcategoryid = NULL, $boxtypeid = NULL, $year= NULL) {
    global $page;

    $result = array();

    $sql = "
        SELECT l.categoryid, c.categorydescription,
               l.subcategoryid, s.subcategoryname,
               l.boxtypeid, b.boxtypename,
               l.year, l.status, l.type,
               COUNT(1) as counts
          FROM listings         l
          JOIN categories       c   ON  c.categoryid        = l.categoryid
                                    AND c.active            = 1
          JOIN subcategories    s   ON  s.subcategoryid     = l.subcategoryid
                                    AND s.active            = 1
          JOIN boxtypes         b   ON  b.boxtypeid         = l.boxtypeid
                                    AND b.active            = 1
    ";
    if (!empty($categoryid)) {
        $sql .= "WHERE l.categoryid = ".$categoryid."
        ";
    }
    if (!empty($subcategoryid)) {
        $sql .= "AND l.subcategoryid = ".$subcategoryid."
        ";
    }
    if (!empty($boxtypeid)) {
        $sql .= "AND l.boxtypeid = ".$boxtypeid."
       ";
    }
    if (!empty($year)) {
        $sql .= "AND l.year = '".$year."'
        ";
     }
     $sql .= "GROUP BY l.categoryid, c.categorydescription, l.subcategoryid, s.subcategoryname, l.boxtypeid, b.boxtypename, l.year, l.status, l.type
              ORDER BY c.categorydescription, s.subcategoryname, b.boxtypename, l.year, l.status DESC, l.type
     ";

    $result = $page->db->sql_query_params($sql);

    return $result;

}

function getSubcategories($categoryid, $boxtypeid = NULL, $year = NULL) {
    global $page;

    $sql = "
        SELECT DISTINCT(s.subcategoryname), s.subcategoryid
          FROM listings         l
          JOIN subcategories    s   ON  s.subcategoryid     = l.subcategoryid
                                    AND s.active            = 1
         WHERE l.categoryid = ".$categoryid."
        ";
        if (!empty($boxtypeid)) {
            $sql .= "   AND l.boxtypeid = ".$boxtypeid."
            ";
        }
        if (!empty($year)) {
            $sql .= "   AND l.year = '".$year."'
            ";
        }
    $sql .= "
        ORDER BY s.subcategoryname
    ";

    $result = $page->db->sql_query_params($sql);

    return $result;
}

function getBoxtypes($categoryid, $subcategoryid = NULL, $year = NULL) {
    global $page;

    $sql = "
        SELECT DISTINCT(b.boxtypename), b.boxtypeid
          FROM listings         l
          JOIN boxtypes         b   ON  b.boxtypeid     = l.boxtypeid
                                    AND b.active        = 1
         WHERE l.categoryid = ".$categoryid."
        ";
        if (!empty($boxtypeid)) {
            $sql .= "   AND l.subcategoryid = ".$subcategoryid."
            ";
        }
        if (!empty($boxtypeid)) {
            $sql .= "   AND l.year = '".$year."'
            ";
        }
    $sql .= "
        ORDER BY b.boxtypename
    ";

    $result = $page->db->sql_query_params($sql);

    return $result;
}

function getYears($categoryid, $subcategoryid = NULL, $boxtypeid = NULL) {
    global $page;
    $sql = "";
    $sql .= "
        SELECT DISTINCT(year)
          FROM listings
         WHERE categoryid  = ".$categoryid."
         AND year != ''
    ";
    if (!empty($subcategoryid)) {
        $sql .= "       AND subcategoryid = ".$subcategoryid."
        ";
    }
    if (!empty($boxtypeid)) {
       $sql .= "   AND boxtypeid = ".$boxtypeid."
       ";
    }
    $sql .= "
         ORDER BY year DESC
    ";

    $result = $page->db->sql_query_params($sql);

    return $result;

}
?>