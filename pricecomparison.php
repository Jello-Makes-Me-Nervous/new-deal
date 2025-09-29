<?php

require_once('templateMarket.class.php');
$page = new templateMarket(LOGIN, SHOWMSG);

$boxtypeid      = optional_param('boxtypeid', NULL, PARAM_INT);
$categoryid     = optional_param('categoryid', NULL, PARAM_INT);
$getyears       = optional_param('getyears', NULL, PARAM_TEXT);
$subcategoryid  = optional_param('subcategoryid', NULL, PARAM_INT);
$year           = optional_param('year', NULL, PARAM_TEXT);

if (!empty($getyears)) {
    $years = getYears($categoryid, $subcategoryid, $boxtypeid);
}
if (!empty($categoryid)) {
    $data = getInfo($categoryid, $subcategoryid, $boxtypeid, $year);
}

echo $page->header('Price Comparison Report');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $boxtypeid, $categoryid, $data, $page, $subcategoryid, $UTILITY, $year, $years;

    echo "<article>\n";
    echo "  <div class='entry-content'>\n";
    echo "    <form name ='search' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "      <table class='table-condensed'>\n";
    echo "        <thead>\n";
    echo "          <tr>\n";
    echo "            <th>Category:</th>\n";
    echo "            <th>Subcategory:</th>\n";
    echo "            <th>Box Type:</th>\n";
    echo "            <th colspan='2'>Year:</th>\n";
    echo "          </tr>\n";
    echo "          </thead>\n";
    echo "          <tbody>\n";
    echo "          <tr>\n";
    echo "            <td>\n";
    $cats = $UTILITY->getcategories("1");
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
    echo "            <td>\n";
    echo $searchbtn;
    echo "            </td>\n";
    echo "          </tr>\n";
    echo "        </tbody>\n";
    echo "      </table>\n";
    echo "    </form>\n";
    echo "    <div style='padding-top: 25px;'>&nbsp;</div>\n";
    echo "      <table border=1>\n";
    echo "        <thead>\n";
    echo "          <tr>\n";
    echo "            <th>ProductID</th>\n";
    echo "            <th>Name</th>\n";
    echo "            <th>Variation</th>\n";
    echo "            <th>UPC</th>\n";
    echo "            <th>My Box Buy</th>\n";
    echo "            <th>My Case Buy</th>\n";
    echo "            <th>My Box Sell</th>\n";
    echo "            <th>My Case Sell</th>\n";
    echo "            <th>B2B Box Buy</th>\n";
    echo "            <th>B2B Case Buy</th>\n";
    echo "            <th>B2B Box Sell</th>\n";
    echo "            <th>B2B Case Sell</th>\n";
    echo "          </tr>\n";
    echo "          </thead>\n";
    echo "          <tbody>\n";
    if (!empty($data)) {
        foreach ($data as $d) {
            echo "          <tr>\n";
            echo "            <td>".$page->user->userId.".".$d['year'].".".$categoryid.".".$d['subcategoryid'].".".$d['boxtypeid']."</td>\n";
            $url = "listing.php?categoryid=".$categoryid."&subcategoryid=".$d["subcategoryid"]."&boxtypeid=".$d['boxtypeid']."&year=".$d['year'];
            $link = "<a href='".$url."' target='_blank'>".$d['year']." ".$d['subcategoryname']." ".$d['categorydescription']." ".$d['boxtypename']."</a>";
            echo "            <td>".$link."</td>\n";
            echo "            <td>".$d['variation']."</td>\n";
            echo "            <td class='number'>".$d['upcs']."</td>\n";
            echo "            <td class='number'>".$d['mybuyboxhigh']."</td>\n";
            echo "            <td class='number'>".$d['mybuycasehigh']."</td>\n";
            echo "            <td class='number'>".$d['mysellboxlow']."</td>\n";
            echo "            <td class='number'>".$d['mysellcaselow']."</td>\n";
            echo "            <td class='number'>".$d['buyboxhigh']."</td>\n";
            echo "            <td class='number'>".$d['buycasehigh']."</td>\n";
            echo "            <td class='number'>".$d['sellboxlow']."</td>\n";
            echo "            <td class='number'>".$d['sellcaselow']."</td>\n";
            echo "          </tr>\n";
        }
    }
    echo "        </tbody>\n";
    echo "      </table>\n";
    echo "  </div>\n";
    echo "</article>\n";

}

function getInfo($categoryid, $subcategoryid = NULL, $boxtypeid = NULL, $year = NULL) {
    global $page;

    $sql = "
        SELECT c.categorydescription, s.subcategoryname, b.boxtypename, l.year, l.subcategoryid, l.boxtypeid, pu.upcs, p.variation,
               MAX(bbh.dprice) AS buyboxhigh, MAX(bch.dprice) AS buycasehigh,
               MAX(mbbh.dprice) AS mybuyboxhigh, MAX(mbch.dprice) AS mybuycasehigh,
               MIN(sbl.dprice) AS sellboxlow, MIN(scl.dprice) AS sellcaselow,
               MIN(msbl.dprice) AS mysellboxlow, MIN(mscl.dprice) AS mysellcaselow

          FROM listings         l
          JOIN categories       c   ON  c.categoryid    = l.categoryid
                                    AND c.active        = 1
          JOIN subcategories    s   ON  s.subcategoryid = l.subcategoryid
                                    AND s.active        = 1
                                    AND s.categoryid    = c.categoryid
          JOIN boxtypes         b   ON  b.boxtypeid     = l.boxtypeid
                                    AND b.active        = 1
          LEFT JOIN products    p   ON  p.active        = 1
                                    AND p.categoryid    = l.categoryid
                                    AND p.subcategoryid = l.subcategoryid
                                    AND p.boxtypeid     = l.boxtypeid
                                    AND isnull(p.year, '1') = isnull(l.year, '1')
          LEFT JOIN (
                SELECT u.productid, array_to_string(array_agg(upc), '<br>') as upcs
                  FROM product_upc  u
                  JOIN products     p   ON  p.productid = u.productid
                                        AND p.active    = 1
                GROUP BY u.productid
                  )           pu  ON  pu.productid        = p.productid
          LEFT JOIN listings    bbh ON  bbh.listingid   = l.listingid
                                    AND bbh.uom         = 'box'
                                    AND bbh.type        = 'Wanted'
          LEFT JOIN listings    bch ON  bch.listingid   = l.listingid
                                    AND bch.uom         = 'case'
                                    AND bch.type        = 'Wanted'

          LEFT JOIN listings   mbbh ON  mbbh.listingid  = l.listingid
                                    AND mbbh.uom        = 'box'
                                    AND mbbh.userid     = ".$page->user->userId."
                                    AND mbbh.type       = 'Wanted'
          LEFT JOIN listings   mbch ON  mbch.listingid  = l.listingid
                                    AND mbch.uom        = 'case'
                                    AND mbch.userid     = ".$page->user->userId."
                                    AND mbch.type       = 'Wanted'

          LEFT JOIN listings    sbl ON  sbl.listingid   = l.listingid
                                    AND sbl.uom         = 'box'
                                    AND sbl.type        = 'For Sale'
          LEFT JOIN listings    scl ON  scl.listingid   = l.listingid
                                    AND scl.uom         = 'case'
                                    AND scl.type        = 'For Sale'

          LEFT JOIN listings   msbl ON  msbl.listingid  = l.listingid
                                    AND msbl.uom        = 'box'
                                    AND msbl.userid     = ".$page->user->userId."
                                    AND msbl.type       = 'For Sale'
          LEFT JOIN listings   mscl ON  mscl.listingid  = l.listingid
                                    AND mscl.uom        = 'case'
                                    AND mscl.userid     = ".$page->user->userId."
                                    AND mscl.type       = 'For Sale'

         WHERE l.categoryid     = ".$categoryid."
           AND l.status         = 'OPEN'
    ";
    if (!empty($subcategoryid)) {
        $sql .= "       AND l.subcategoryid = ".$subcategoryid."
        ";
    }
    if (!empty($boxtypeid)) {
       $sql .= "   AND l.boxtypeid = ".$boxtypeid."
       ";
    }
    if (!empty($year)) {
        $sql .= "    AND l.year = '".$year."'
        ";
     }
    $sql .= "
         GROUP BY c.categorydescription, s.subcategoryname, b.boxtypename, l.year, l.subcategoryid, l.boxtypeid, pu.upcs, p.variation
    ";

//    echo "<pre>".$sql."</pre>";
    $result = $page->db->sql_query_params($sql);

    return $result;

}

function getSubcategories($categoryid, $boxtypeid = NULL, $year = NULL) {
    global $page;

    $sql = "
        SELECT DISTINCT(s.subcategoryname), s.subcategoryid
          FROM listings         l
          JOIN subcategories    s   ON  s.subcategoryid = l.subcategoryid
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
          FROM listings l
          JOIN boxtypes b ON b.boxtypeid = l.boxtypeid
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