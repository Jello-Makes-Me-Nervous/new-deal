<?php

require_once('templateMarket.class.php');
$page = new templateMarket(LOGIN, SHOWMSG);

DEFINE("DEFAULTHRS",    3);
DEFINE("MAXHRSSINCE",   72);
DEFINE("MAXHRSALSO",   1999);

$dealer             = optional_param('dealer', NULL, PARAM_RAW);
$categoryid         = optional_param('categoryid', NULL, PARAM_INT);
$boxtypeid          = optional_param('boxtypeid', NULL, PARAM_INT);
$subcategoryid      = optional_param('subcategoryid', NULL, PARAM_INT);
$year               = optional_param('year', NULL, PARAM_RAW);
$keywordsearch      = optional_param('keywordsearch', NULL, PARAM_RAW);
$type               = optional_param('type', NULL, PARAM_RAW);
$hourssince         = optional_param('hourssince', DEFAULTHRS, PARAM_INT);
$sortby             = optional_param('sortby', "date", PARAM_RAW);
$searchbtn          = optional_param("searchbtn", NULL, PARAM_RAW);
$originalcategoryid = optional_param('origcatid', NULL, PARAM_INT);

if (!empty($originalcategoryid) && $categoryid <>$originalcategoryid) {
    $boxtypeid          = 0;
    $subcategoryid      = 0;
//    $year               = "";
}

$proceed = true;
$maxHoursSince = MAXHRSSINCE;
if (empty($dealer) && empty($categoryid)) {
    if (empty($hourssince)) {
        $page->messages->addErrorMsg("A dealer, category or hours since must be entered.");
        $proceed = false;
    } elseif ($hourssince > $maxHoursSince) {
        $page->messages->addErrorMsg("Without a dealer or category selected; hours since must be a reasonable number (1-".$maxHoursSince.").");
        $proceed = false;
    }
} else {
    $maxHoursSince = MAXHRSALSO;
    if ($hourssince > $maxHoursSince) {
        $page->messages->addErrorMsg("Hours since must be a reasonable number (1-".$maxHoursSince.").");
        $proceed = false;
    }
}

if ($type == "W") {
    $type = "Wanted";
} elseif ($type == "FS") {
    $type = "For Sale";
}


echo $page->header('Market Snapshot');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $dealer, $categoryid, $subcategoryid, $boxtypeid, $year;
    global $keywordsearch, $type, $hourssince, $sortby, $searchbtn, $proceed;

    echo "<h3>Market Snapshot</h3>\n";
    echo "<article>\n";
    echo "  <div class='entry-content'>\n";
    echo "    <form name ='search' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "      <table class='table-condensed'>\n";
    echo "        <tbody>\n";
    echo "          <tr>\n";
    echo "            <td>\n";
    echo "              <label for='dealer'>Dealer</label><br>\n";
    echo "              <input type='text' name='dealer' id='dealer' size='15' value='".$dealer."' class='input' style='width:100px;'>\n";
    echo "            </td>\n";
    echo "            <td>\n";
    echo "              <label for='categoryid'>Category</label><br>\n";
    $cats = getCategories();
    echo getSelectDDM($cats, "categoryid", "categoryid", "categorydescription", NULL, $categoryid, "Select", 0, NULL, NULL, "onchange='this.form.submit(search)'");
    echo "            </td>\n";
    if (!empty($categoryid)) {
        echo "            <td>\n";
        echo "              <label for='subcategoryid'>Subcategory</label><br>\n";
        $subcats = getSubcategories($categoryid, $boxtypeid, $year);
        echo getSelectDDM($subcats, "subcategoryid", "subcategoryid", "subcategoryname", "Select a Category", $subcategoryid, "Select", 0, NULL, NULL, "onchange='this.form.submit(search)'");
        echo "            </td>\n";
        echo "            <td>\n";
        echo "              <label for='boxtypeid'>Box Type</label><br>\n";
        $boxes = getBoxtypes($categoryid, $boxtypeid, $year);
        echo getSelectDDM($boxes, "boxtypeid", "boxtypeid", "boxtypename", NULL, $boxtypeid, "Select", 0, NULL, NULL, "onchange='this.form.submit(search)'");
        echo "            </td>\n";
        echo "            <td>\n";
        echo "              <label for='year'>Year</label><br>\n";
        echo "              <input type='text' name='year' id='year' size='5' maxlength='4' value='".$year."' class='input' style='width:60px;'>\n";
        echo "            </td>\n";
        echo "          </tr>\n";
    } else {
        echo "            <td colspan='3'>&nbsp;</td>\n";
    }
    echo "          <tr>\n";
    echo "            <td>\n";
    echo "              <label for='keyword'>Keyword Search</label><br>\n";
    echo "              <input type='text' name='keywordsearch' id='keywordsearch' size='15' value='".$keywordsearch."' class='input'>\n";
    echo "            </td>\n";
    echo "            <td>\n";
    $checked = ($type == "Wanted") ? "CHECKED" : "";
    echo "              <div style='width: 75px;display:inline;white-space: nowrap;'>\n";
    echo "                <input type='radio' name='type' id='type' value='Wanted' class='input' ".$checked.">\n";
    echo "                <label for='type'>Wanted</label>\n";
    echo "              </div>\n";
    $checked = ($type == "For Sale") ? "CHECKED" : "";
    echo "              <div style='width: 75px;display:inline;white-space: nowrap;'>\n";
    echo "                <input type='radio' name='type' id='type' value='For Sale' class='input' ".$checked.">\n";
    echo "                <label for='type'>For Sale</label>\n";
    echo "              </div>\n";
    $checked = (empty($type)) ? "CHECKED" : "";
    echo "              <div style='width: 75px;display:inline;white-space: nowrap;'>\n";
    echo "                <input type='radio' name='type' id='type' value='' class='input' ".$checked.">\n";
    echo "                <label for='type'>Both</label>\n";
    echo "              </div>\n";
    echo "            </td>\n";
    echo "            <td>\n";
    echo "              <div style='display:inline;white-space: nowrap;'>\n";
    echo "              <label for='keyword'>Listings since</label> \n";
    echo "              <input type='text' name='hourssince' id='hourssince' maxlength='4' value='".$hourssince."' class='input' style='width:6ch;'>\n";
    echo "               (hrs ago)\n";
    echo "              </div>\n";
    echo "            </td>\n";
    echo "            <td>\n";
    echo "              <label for='sortby'>Sort By:</label><br>\n";
    $checked = ($sortby == "dealer") ? "CHECKED" : "";
    echo "              <div style='width: 75px;display:inline;white-space: nowrap;'>\n";
    echo "                &nbsp;&nbsp;<input type='radio' name='sortby' id='sortby' value='dealer' class='input' ".$checked.">\n";
    echo "                <label for='type'>Dealer</label>\n";
    echo "              </div>\n";
    $checked = ($sortby == "cat") ? "CHECKED" : "";
    echo "              <div style='width: 75px;display:inline;white-space: nowrap;'>\n";
    echo "                &nbsp;&nbsp;<input type='radio' name='sortby' id='sortby' value='cat' class='input' ".$checked.">\n";
    echo "                <label for='type'>Category</label>\n";
    echo "              </div>\n";
    $checked = ($sortby == "subcat") ? "CHECKED" : "";
    echo "              <div style='width: 75px;display:inline;white-space: nowrap;'>\n";
    echo "                &nbsp;&nbsp;<input type='radio' name='sortby' id='sortby' value='subcat' class='input' ".$checked.">\n";
    echo "                <label for='type'>Subcategory</label>\n";
    echo "              </div>\n";
    $checked = ($sortby == "dprice") ? "CHECKED" : "";
    echo "              <div style='width: 75px;display:inline;white-space: nowrap;'>\n";
    echo "                &nbsp;&nbsp;<input type='radio' name='sortby' id='sortby' value='price' class='input' ".$checked.">\n";
    echo "                <label for='type'>Price</label>\n";
    echo "              </div>\n";
    $checked = ($sortby == "year") ? "CHECKED" : "";
    echo "              <div style='width: 75px;display:inline;white-space: nowrap;'>\n";
    echo "                &nbsp;&nbsp;<input type='radio' name='sortby' id='sortby' value='year' class='input' ".$checked.">\n";
    echo "                <label for='type'>Year</label>\n";
    echo "              </div>\n";
    $checked = (empty($sortby) || $sortby == "date") ? "CHECKED" : "";
    echo "              <div style='width: 75px;display:inline;white-space: nowrap;'>\n";
    echo "                &nbsp;&nbsp;<input type='radio' name='sortby' id='sortby' value='date' class='input' ".$checked.">\n";
    echo "                <label for='type'>Date</label>\n";
    echo "              </div>\n";
    echo "            </td>\n";
    echo "            <td class='center'>\n";
    echo "              <input type='submit' name='searchbtn' id='searchbtn' value='Search'>\n";
    echo "            </td>\n";
    echo "          </tr>\n";
    echo "        </tbody>\n";
    echo "      </table>\n";
    echo "      <input type='hidden' name='origcatid' id='origcatid' value='".$categoryid."'>\n";
    echo "    </form>\n";

    if ($proceed) {
        $data = getData($dealer, $categoryid, $subcategoryid, $boxtypeid, $year, $keywordsearch, $type, $hourssince, $sortby);
        $tHeaderStart  = "      <table class='table-condensed'>\n";
        $tHeaderStart .= "        <thead>\n";
        $tHeaderStart .= "          <tr>\n";
        $tHeaderStart .= "            <th scope='col' colspan='2'>###</th>\n";
        $tHeaderStart .= "            <th scope='col'>UPC</th>\n";
        $tHeaderStart .= "            <th scope='col'>$/Unit</th>\n";
        $tHeaderStart .= "          </tr>\n";
        $tHeaderStart .= "        </thead>\n";
        $tHeaderStart .= "        <tbody>\n";

        $tHeaderEnd  = "        </tbody>\n";
        $tHeaderEnd .= "      </table>\n";

        if (empty($data)) {
            echo str_replace("###", "", $tHeaderStart);
            echo "          <tr><td colspan='3'>No matching listings found.</td></tr>\n";
            echo $tHeaderEnd;
        } else {
            $prevtype = "";
            $prevuom = "";
            $first = true;
            $inSecondary = false;
            $rowClass = "";
            $rowStyle = "";
            foreach ($data as $l) {
                if ($prevtype <> $l["type"] || $prevuom  <> $l["uom"]) {
                    if ($first) {
                        echo str_replace("###", ucfirst($l["type"]), $tHeaderStart);
                        $first = false;
                    } else {
                        echo $tHeaderEnd;
                        echo str_replace("###", ucfirst($l["type"]), $tHeaderStart);
                    }
                    $prevtype = $l["type"];
                    $prevuom  = $l["uom"];
                    $secondaryClassPrefix= "secondary".substr($l["type"],0,1).$l['uom'];
                    $inSecondary = false;
                    $rowClass = "";
                    $rowStyle = "";
                }
                if ($l['secondary']) {
                    $secondaryOnly = ($subcategoryid && ($subcategoryid == $l['subcategoryid'])) ? true : false;
                    $rowClass = " class='".$secondaryClassPrefix."sc' ";
                    if (!$secondaryOnly) {
                        $rowStyle = " style='display:none;' ";
                    }
                    if (! $inSecondary) {
                        echo "<tr>";
                        echo "<td colspan='4'>";
                        if (! $secondaryOnly) {
                            echo "<a title='Show secondary subcategories' href='#' onClick='$(\".".$secondaryClassPrefix."sc\").show();$(\".".$secondaryClassPrefix."toggle\").hide(); return(false);' class='".$secondaryClassPrefix."toggle' ><i class='fa-solid fa-plus'></i></a>";
                            echo "<a title='Hide secondary subcategories' href='#' onClick='$(\".".$secondaryClassPrefix."sc\").hide();$(\".".$secondaryClassPrefix."toggle\").show(); return(false);' class='".$secondaryClassPrefix."sc' ".$rowStyle."><i class='fa-solid fa-minus'></i></a>";
                        }
                        echo " <strong>Secondary Subcategories</strong>";
                        echo "</td>";
                        echo "</tr>\n";
                        $inSecondary = true;
                    }
                }
                echo "    <tr ".$rowClass." ".$rowStyle.">\n";
                $eliteUser = ($l['iselite']) ? " <i title='Elite Dealer' class='fas fa-star'></i>" : "";
                $blueStarUser = ($l['isbluestar']) ? " <i title='Above Standard Dealer' class='fas fa-star' style='color: #00f;'></i>" : "";
                $verifiedUser = ($l['isverified']) ? " <i title='Verified Dealer' class='fas fa-check' style='color: #090;'></i>" : "";
                if ($l['listinglogo']) {
                    $displayDealerName = "<img src='".$page->utility->getPrefixMemberImageURL($l['listinglogo'])."' title='".$l['username']."' width='75px' />";
                } else {
                    $displayDealerName = $l['username'];
                }
                $url = "dealerProfile.php?dealerId=".$l['userid'];
                $link = "<a href='".$url."' target='blank'>".$displayDealerName."</a> ".$eliteUser.$blueStarUser.$verifiedUser;
                echo "            <td data-label='Dealer'>".$link."</td>\n";

                $url  = "/listing.php?listingtypeid=".$l["categorytypeid"]."&categoryid=".$l["categoryid"]."&subcategoryid=".$l["subcategoryid"]."&boxtypeid=".$l["boxtypeid"]."&year=".$l["year"];
                $url .= (empty($l["year"])) ? "" : "&year=".$l["year"];
                $listingDesc  = $l["year"]." ".$l["subcategoryname"]." ".$l["categorydescription"];
                $listingDesc .= " <b>".$l["boxtypename"]."</b>";
                $listingDesc = (empty($l["variation"])) ? $listingDesc : $listingDesc." <b>~ ".$l["variation"]."</b>";
                $listingDesc .= " ~ qty: ".$l["quantity"];
                if ($l['uom'] == "case") {
                    $listingDesc .= " ~ SEALED ".$l['boxespercase']." BOX CASE";
                }
                $link = "<a href='".$url."' target='_blank'>".$listingDesc."</a>";
                if (!empty($l['listingnotes'])) {
                    $link .= "<i class='fas fa-info-circle fa-1x' title='".$l['listingnotes']."'  onClick=\"alert('".$page->utility->alertFriendlyString($l['listingnotes'])."');\"></i> ";
                }
                if (!empty($l['picture'])) {
                    if ($picURL = $page->utility->getPrefixListingImageURL($l['picture'])) {
                        $link .= "<a href='".$picURL."' target='_blank'><i class='fa-solid fa-camera'></i></a> ";
                    }
                }
                echo "            <td data-label='Listing'>".$link."</td>\n";

                echo "            <td data-label='UPC' class='number'>";
                if (!empty($l['upcs'])) {
                    $upcs = str_replace(",", "<br>", $l["upcs"]);
                    echo "              ".$upcs;
                }
                echo "            </td>\n";

                echo "            <td data-label='Price / Unit' class='number'>$ ".$l["dprice"]." / ".$l["uom"]."</td>\n";
                echo "          </tr>\n";
            }
            echo $tHeaderEnd;
        }
    }
    echo "  </div>\n";
    echo "</article>\n";
    echo "<br />\n";

}

function getCategories() {
    global $page;

    $sql = "
        SELECT c.categorydescription, c.categoryid
          FROM categories       c
          JOIN categorytypes    ct  ON  ct.categorytypeid = c.categorytypeid
                                    AND ct.categorytypeid in (1,2)
         WHERE c.active = 1
         ORDER BY c.categorydescription
    ";

    $result = $page->db->sql_query_params($sql);

    return $result;
}

function getSubcategories($categoryid, $boxtypeid = NULL, $year = NULL) {
    global $page;

    $sql = "
        SELECT s.subcategoryid
            ,s.secondary
            ,(CASE WHEN s.secondary=1 THEN '- ' ELSE '' END)||s.subcategoryname AS subcategoryname
          FROM listings         l
          JOIN subcategories    s   ON  s.subcategoryid     = l.subcategoryid
                                    AND s.active            = 1
                                    AND s.secondary         = 0
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
        GROUP BY s.secondary, s.subcategoryname, s.subcategoryid
        ORDER BY s.secondary, s.subcategoryname COLLATE \"POSIX\"
    ";

//    echo "<pre>".$sql."</pre>";
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
        if (!empty($year)) {
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

//    echo "<pre>".$sql."</pre>";
    $result = $page->db->sql_query_params($sql);

    return $result;

}

function getData($dealername, $categoryid, $subcategoryid, $boxtypeid, $year, $keywordsearch, $buysell, $hourssince, $sortby) {
    global $page, $maxHoursSince;

    $dealer     = (empty($dealername))      ? "" : "AND lower(u.username) like  lower('%".$dealername."%')";
    $cid        = (empty($categoryid))      ? "" : "AND c.categoryid = ".$categoryid;
    $scid       = (empty($subcategoryid))   ? "" : "AND sc.subcategoryid = ".$subcategoryid;
    $btid       = (empty($boxtypeid))       ? "" : "AND b.boxtypeid = ".$boxtypeid;
    $yr         = (empty($year))            ? "" : "AND l.year = '".$year."'";
    $type       = (empty($buysell))         ? "" : "AND l.type = '".$buysell."'";

    if (!empty($dealername) && empty($hourssince)) {
        $hrssince   = "";
    } else {
        if (empty($hourssince)) {
            $hourssince = DEFAULTHRS;
        } elseif ($hourssince > ($maxHoursSince)) {
            $hourssince = $maxHoursSince;
        }
        $hoursago   = $hourssince * 60*60;
        $hrssince   = "AND l.modifydate > (nowtoint() - ".$hoursago.")";
    }

    $keyword    = "";
    if (!empty($keywordsearch)) {
        $searchstring = strtolower(trim($keywordsearch));
        $keyword = "
           AND (   strpos(lower(c.categorydescription), '".$searchstring."') > 0
                OR strpos(lower(sc.subcategoryname), '".$searchstring."') > 0
                OR strpos(lower(b.boxtypename), '".$searchstring."') > 0
                OR strpos(lower(l.year), '".$searchstring."') > 0
                OR strpos(lower(l.listingnotes), '".$searchstring."') > 0
                OR strpos(lower(u.username), '".$searchstring."') > 0
                OR strpos(lower(p.sku), '".$searchstring."') > 0
               )
        ";
    }

    SWITCH ($sortby) {
        CASE "dealer":  $sortby = "u.username";
                        break;
        CASE "cat":     $sortby = "c.categorydescription";
                        break;
        CASE "subcat":  $sortby = "sc.subcategoryname";
                        break;
        CASE "price":   $sortby = "l.dprice";
                        break;
        CASE "year":    $sortby = "l.year4 DESC";
                        break;
        DEFAULT:        $sortby = "l.modifydate";
    }

    $sql = "
        SELECT l.listingid, u.userid, u.username, ui.listinglogo,
               l.categoryid, c.categorydescription, c.categorytypeid,
               l.subcategoryid, sc.subcategoryname, sc.secondary,
               l.boxtypeid, b.boxtypename,
               l.year, l.year4,
               l.type, l.uom, l.dprice, l.quantity, l.boxprice,
               l.listingnotes, l.picture, l.boxespercase,
               pu.upcs, p.variation,
               CASE WHEN ar.userid IS NOT NULL THEN 1
                    ELSE 0 END as iselite,
               CASE WHEN bar.userid IS NOT NULL AND ar.userid IS NULL THEN 1
                    ELSE 0 END as isbluestar,
               CASE WHEN vdar.userid IS NOT NULL THEN 1
                    ELSE 0 END as isverified
          FROM listings             l
          JOIN users                u   ON  u.userid            = l.userid
          JOIN userinfo             ui  ON  ui.userid           = u.userid
          JOIN categories           c   ON  c.categoryid        = l.categoryid
                                        AND c.active            = 1
                                        AND c.categorytypeid in (1,2)
          JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                        AND sc.active           = 1
                                        AND sc.secondary        = 0
          JOIN boxtypes             b   ON  b.boxtypeid         = l.boxtypeid
                                        AND b.active            = 1
          LEFT JOIN products        p   ON  p.active            = 1
                                        AND p.categoryid        = l.categoryid
                                        AND p.subcategoryid     = l.subcategoryid
                                        AND p.boxtypeid         = l.boxtypeid
                                        AND isnull(p.year, '1') = isnull(l.year, '1')
          LEFT JOIN (
                    SELECT u.productid, array_to_string(array_agg(upc), ',') as upcs
                      FROM product_upc  u
                      JOIN products     p   ON  p.productid     = u.productid
                                            AND p.active        = 1
                    GROUP BY u.productid
                   )                    pu  ON  pu.productid        = p.productid
          LEFT JOIN assignedrights  ar  ON  ar.userid           = u.userid
                                        AND ar.userrightid      = 15 -- Elite
          LEFT JOIN assignedrights  bar ON  bar.userid          = u.userid
                                        AND bar.userrightid     = 64 -- Blue Star
          LEFT JOIN assignedrights vdar ON  vdar.userid         = u.userid
                                        AND vdar.userrightid    = 65 -- Verified
          LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                        AND stl.userrightid     = ".USERRIGHT_STALE."
         WHERE l.status = 'OPEN'
           AND stl.userid IS NULL
           ".$hrssince."
           ".$dealer."
           ".$cid."
           ".$scid."
           ".$btid."
           ".$yr."
           ".$type."
           ".$keyword."
        ORDER BY l.uom, l.type DESC, sc.secondary, ".$sortby;

    //echo "marketsnapshot getData SQL:<pre>".$sql."</pre>";
    $result = $page->db->sql_query_params($sql);

    return $result;
}
?>