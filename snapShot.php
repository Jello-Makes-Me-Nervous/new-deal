<?php
require_once('templateMarket.class.php');

$page = new templateMarket(LOGIN, SHOWMSG);
$page->requireJS('scripts/populateSubcatBoxtype.js');

$boxTypeId  = optional_param('boxTypeId', NULL, PARAM_INT);
$categoryId = optional_param('categoryId', NULL, PARAM_INT);
$find       = optional_param('find', NULL, PARAM_TEXT);
$year       = optional_param('year', NULL, PARAM_TEXT);
$subCatData = null;

if (isset($find) && !empty($categoryId)) {
    $subCatData = getsubCats($categoryId, $boxTypeId, $year);
}

echo $page->header('Market Snapshot');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $boxTypeId, $categoryId, $find, $year, $UTILITY, $subCatData;

    echo "<form id='sub' name='sub' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "  <table>\n";
    echo "    <tr>\n";
    echo "      <td>\n";
    echo categoryDDM($categoryId);
    echo "      </td>\n";
    echo "      <td>Box Type:\n";
    echo boxTypeDDM($categoryId, $boxTypeId);
    echo "      </td>\n";
    echo "      <td>Year: <input type='text' name='year' id='year' value='".$year."' size='4' ></td>\n";
    echo "      <td><input class='button' type='submit' name='find' value='Go'></td>\n";
    echo "    </tr>\n";
    echo "  </table>\n";
    echo "</form>\n";
    echo "\n";

    echo "<table>\n";
    echo "  <thead>\n";
    echo "    <tr>\n";
    echo "      <th align='left'>Product</th>\n";
    echo "      <th align='left'>Factory</th>\n";
    echo "      <th align='left'>High Buy/ Low Sell</th>\n";
    echo "    </tr>\n";
    echo "  </thead>\n";
    echo "  <tbody>\n";
    if (!empty($subCatData)) {
        foreach ($subCatData as $data ) {
            echo "    <tr>\n";
            $link = "listing.php?subCategoryId=".$data['subcategoryid']."&boxTypeId=".$data['boxtypeid']."&categoryId=".$categoryId."&uom=".$data['uom']."&year=".$year;
            echo "      <td><a href='".$link."'>".$data['subcategoryname']." - ".$data['boxtypename']."</a></td>\n";
            echo "      <td>".$data['factorycost']."</td>\n";
            echo "      <td>".$data['highbuy']." / ".$data['lowsell']."</td>\n";
            echo "    </tr>\n";
        }
    }
    echo "  </tbody>\n";
    echo "</table>\n";

    echo "<script language = javascript>\n";
    echo getBoxTypesJS();
    echo "</script>\n";

}

function getBoxTypesJS() {
    global $page;


    $rs = $page->utility->getboxTypes(NULL);

    $output = "\n";
    $output .= "  var boxtypedata = [\n";
    foreach ($rs as $sc) {
         $output .= "[".$sc["categoryid"].", '".$sc["boxtypeid"]."', '".$sc["boxtypename"]."', ".$sc["categorytypeid"]."],\n";
    }
    $output .= "    [0,'',0,0,'']\n";
    $output .= "  ];\n";

    return $output;
}

function getsubCats($categoryId, $boxTypeId, $year) {
    global $page;

    $factoryCostID = $page->utility->getDealerId(FACTORYCOSTNAME);
    $yr = (empty($year)) ? "null" : "'".$year."'";
    $bt = (empty($boxTypeId)) ? "l.boxtypeid" : $boxTypeId;
    $sql = "
        SELECT l.subcategoryid, sc.subcategoryname, l.boxtypeid, bt.boxtypename, l.uom,
               hb.highbuy, ls.lowsell, f.dprice as factorycost, COUNT(1) AS listingcount
          FROM listings         l
          JOIN subcategories    sc  ON  sc.subcategoryid    = l.subcategoryid
          JOIN boxtypes         bt  ON  bt.boxtypeid        = l.boxtypeid
          LEFT JOIN listings    f   ON  f.categoryid        = l.categoryid
                                    AND f.subcategoryid     = l.subcategoryid
                                    AND f.boxtypeid         = l.boxtypeid
                                    AND isnull(f.year, '1') = isnull(l.year, '1')
                                    AND f.userid            = ".$factoryCostID."
                                    AND f.uom               = 'box'
                                    AND f.status            = 'OPEN'
          LEFT JOIN (
                SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year,
                       MAX(CASE WHEN l.uom = 'case' THEN l.dprice /l.boxespercase
                                ELSE l.dprice END ) AS highbuy
                  FROM listings     l
                 WHERE l.categoryid         = ".$categoryId."
                   AND l.type               = 'Wanted'
                   AND l.boxtypeid          = ".$bt."

                   AND l.uom IN ('box', 'case')
              GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
          )                     hb  ON  hb.subcategoryid    = l.subcategoryid
                                    AND hb.boxtypeid        = l.boxtypeid
                                    AND hb.categoryid       = l.categoryid

          LEFT JOIN (
                SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year,
                       MIN(CASE WHEN l.uom = 'case' THEN l.dprice /l.boxespercase
                                ELSE l.dprice END ) AS lowsell
                  FROM listings     l
                 WHERE l.categoryid         = ".$categoryId."
                   AND l.type               = 'For Sale'
                   AND l.boxtypeid          = ".$bt."

                   AND l.uom IN ('box', 'case')
              GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
          )                     ls  ON  ls.subcategoryid    = l.subcategoryid
                                    AND ls.boxtypeid        = l.boxtypeid
                                    AND ls.categoryid       = l.categoryid

         WHERE l.categoryid         = ".$categoryId."
           AND l.boxtypeid          = ".$bt."
           AND l.uom                = 'box'

         GROUP BY l.subcategoryid, sc.subcategoryname, l.boxtypeid, bt.boxtypename, l.uom, hb.highbuy, ls.lowsell, f.dprice
         ORDER BY sc.subcategoryname COLLATE \"POSIX\"
    ";
//echo "<pre>";
//echo $sql;
//echo "</pre>";
    $data = $page->db->sql_query_params($sql);

    return $data;
}

function categoryDDM($categoryId = NULL) {
    global $page;

    $categories = $page->utility->getcategories();

    $onChange = "
        onchange = 'javascript: populateBoxType(this.value);
        if (isGaming(this.value)) {
            document.getElementById(\"year\").disabled = true;
        } else {
            document.getElementById(\"year\").disabled = false;
        }'";

    $output = "        ".getSelectDDM($categories, "categoryId", "categoryid", "categoryname", NULL, $categoryId, "Select", 0, NULL, NULL, $onChange)."\n";

    return $output;
}

function boxTypeDDM($categoryId = null, $boxTypeId = NULL) {
    global $page;

    if (!empty($categoryId)) {
        $rs = $page->utility->getboxTypes($categoryId);
    } else {
        $rs = array();
        $rs[] = array("boxtypeid" => 0, "boxtypename" => "");
    }

    $output = "          ".getSelectDDM($rs, "boxTypeId", "boxtypeid", "boxtypename", NULL, $boxTypeId)."\n";

    return $output;
}


?>