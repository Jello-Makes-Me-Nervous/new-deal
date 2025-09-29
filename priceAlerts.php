<?php
require_once('templateMarket.class.php');

DEFINE("MAXWOFILTER",   50);

$page = new templateMarket(LOGIN, SHOWMSG);

$addalert       = optional_param('addalert', NULL, PARAM_RAW);
$alertid        = optional_param('aid', NULL, PARAM_INT);

$categoryid     = optional_param('categoryid', NULL, PARAM_INT);
$subcategoryid  = optional_param('subcategoryid', NULL, PARAM_INT);
$boxtypeid      = optional_param('boxtypeid', NULL, PARAM_INT);
$year           = optional_param('year', NULL, PARAM_RAW);
$mode           = optional_param('mode', NULL, PARAM_RAW);
$dprice         = optional_param('dprice', NULL, PARAM_RAW);
$status         = optional_param('status', "OPEN", PARAM_RAW);
$type           = optional_param('type', NULL, PARAM_RAW);
$uom            = optional_param('uom', "box", PARAM_RAW);
$hibuy          = str_replace("$", "", optional_param('hibuy', NULL, PARAM_RAW));
$losell         = str_replace("$", "", optional_param('losell', NULL, PARAM_RAW));

$listingTypeId = null;
setGlobalListingTypeId($categoryid);
$canadd = false;
if (($listingTypeId == LISTING_TYPE_SPORTS && !empty($categoryid) && !empty($subcategoryid) && !empty($boxtypeid) && !empty($year)) ||
    ($listingTypeId == LISTING_TYPE_GAMING && !empty($categoryid) && !empty($subcategoryid) && !empty($boxtypeid))) {
    $canadd = true;
}

if (!empty($alertid)) {
    deleteAlert($alertid);
} elseif (!empty($addalert) && !empty($type) && !empty($dprice)) {
    if ($type == 'Wanted' && !empty($hibuy) && $dprice <= $hibuy) {
        $page->messages->addWarningMsg("Price alert NOT added as the price (".$dprice.") is equal to or lower than the high buy (".$hibuy.")");
    } elseif ($type == 'For Sale' && !empty($losell) && $dprice >= $losell) {
        $page->messages->addWarningMsg("Price alert NOT added as the price (".$dprice.") is equal to or higher than the low sell (".$losell.")");
    } else {
        insertAlert($status, $type, $categoryid, $subcategoryid, $boxtypeid, $year, $dprice, $uom);
    }
}
$hilo = getHiBuyLoSell($categoryid, $subcategoryid, $boxtypeid, $year, $listingTypeId);
$hibuy  = (empty($hilo['hibuy'])) ? "" : "$".number_format($hilo['hibuy'], 2);
$losell  = (empty($hilo['losell'])) ? "" : "$".number_format($hilo['losell'], 2);

$count = getPriceAlertCounts($categoryid, $listingTypeId, $subcategoryid, $boxtypeid, $year);
if ($count > MAXWOFILTER) {
    $page->messages->addWarningMsg("You have too many alerts to display without a filter; please select a filter.");
}

echo $page->header('My Price Alerts');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $categoryid, $subcategoryid, $boxtypeid, $year, $listingTypeId, $count, $hibuy, $losell;

    echo "<form name ='pa_form' id ='pa_form' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "  <h3>My Price Alerts</h3>\n";
    displayFilters();
    echo "  <div>&nbsp;</div>\n";
    if ($count <= MAXWOFILTER || !empty($categoryid)) {
        displayAlerts();
    }
    echo "  <input type='hidden' name='aid' id='aid' value=''>\n";
    echo "  <input type='hidden' name='hibuy' id='hibuy' value='".$hibuy."'>\n";
    echo "  <input type='hidden' name='losell' id='losell' value='".$losell."'>\n";
    echo "  <input type='hidden' name='mode' id='mode' value=''>\n";
    echo "</form>\n";

}

function displayFilters() {
    global $page, $categoryid, $subcategoryid, $boxtypeid, $year, $listingTypeId;

    echo "  <table>\n";
    echo "    <thead>\n";
    echo "      <tr>\n";
    echo "        <th>Category</th>\n";
    echo "        <th>Subcategory</th>\n";
    echo "        <th>Boxtype</th>\n";
    if ($listingTypeId == LISTING_TYPE_SPORTS) {
        echo "        <th>Year</th>\n";
    }
//    echo "        <th></th>\n";
    echo "      </tr>\n";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    echo "      <tr>\n";
    echo "        <td>\n";
    $cattpes = LISTING_TYPE_SPORTS.",".LISTING_TYPE_GAMING;
    echo categoryDDM($cattpes, $categoryid);
    echo "        </td>\n";
    if (!empty($categoryid)) {
        echo "        <td>\n";
        echo subCategoryDDM($categoryid, $subcategoryid);
        echo "        </td>\n";
        echo "        <td>\n";
        echo boxTypeDDM($categoryid, $subcategoryid, $boxtypeid);
        echo "        </td>\n";
        if ($listingTypeId == LISTING_TYPE_SPORTS) {
            echo "        <td>\n";
            echo yearDDM($categoryid, $subcategoryid, $boxtypeid, $year);
            echo "        </td>\n";
        }
//        echo "        <td><input type='submit' name='go' id='go' value='Go'></td>\n";
    }
    echo "    </tr>\n";
    echo "  </table>\n";

}

function displayAlerts() {
    global $page, $categoryid, $subcategoryid, $boxtypeid, $year, $listingTypeId, $mode, $canadd, $hibuy, $losell;

    echo "<table>\n";
    if (empty($mode) && $canadd) {
        echo "  <caption>\n";
        echo "    <a href='JavaScript: void(0);' onclick='Javascript: $(\"#mode\").val(\"add\"); document.pa_form.submit();'><i class='fa-solid fa-circle-plus'></i> Add Price Alert</a>\n";
        echo "    <br/>\n";
        echo "  </caption>\n";
    }
    echo "  <thead>\n";
    echo "    <tr>\n";
    echo "      <th>Price Alert Item</th>\n";
    echo "      <th>Buy/Sell</th>\n";
    echo "      <th>Price</th>\n";
    echo "      <th>High Buy / Low Sell</th>\n";
    echo "      <th></th>\n";
    echo "    </tr>\n";
    echo "  </thead>\n";
    echo "  <tbody>\n";

    if ($mode == "add") {
        if (!empty($hibuy) || !empty($losell)) {
            $oNames = new ListingReferral();
            if (isset($oNames->referCategoryName) && isset($oNames->referSubCategoryName) && isset($oNames->referBoxTypeName)) {
                echo "    <tr>\n";
                $label = ucwords($oNames->referBoxTypeName)." ".$oNames->referSubCategoryName." ".$oNames->referCategoryName;
                $label = (empty($year)) ? $label : $year." ".$label;
                $url    = "listing.php?categoryid=".$categoryid."&subcategoryid=".$subcategoryid."&boxtypeid=".$boxtypeid."&listingtypeid=".$listingTypeId."&year=".$year;
                $link   = "<a href='".$url."' target='_blank'>".$label."</a>";
                echo "      <td>".$link."</td>\n";
                echo "      <td class='center'>\n";
                echo "        <input type='radio' name='type' id='type' value='Wanted' required>Wanted  &nbsp;\n";
                echo "        <input type='radio' name='type' id='type' value='For Sale'>For Sale\n";
                echo "      </td>\n";
                echo "      <td>\n";
                echo "        <input type='text' name='dprice' id='dprice' size='4' value='' required>\n";
                echo "        <input type='hidden' name='uom' id='uom' value='box'>\n";
                echo "      </td>\n";
                echo "      <td class='center'>".$hibuy." / ".$losell."</td>\n";
                echo "      <td class='center'>\n";
                echo "        <input type='submit' id='addalert' name='addalert' value='Add'>\n";
                $url    = "priceAlerts.php?categoryid=".$categoryid."&subcategoryid=".$subcategoryid."&boxtypeid=".$boxtypeid."&listingtypeid=".$listingTypeId."&year=".$year;
                echo "        <input type='button' value='Cancel' onclick='javascript: window.location.href=\"".$url."\"'>\n";
                echo "      </td>\n";
                echo "    </tr>\n";
            }
        } else {
            echo "      <tr><td class='center' colspan='5'>No listings found; please change your filters.</td></tr>\n";
        }
    }

    $alerts = getPriceAlerts($categoryid, $listingTypeId, $subcategoryid, $boxtypeid, $year);
    if (!empty($alerts)) {
        foreach ($alerts as $a) {
            echo "    <tr>\n";
            $label = ucwords($a['boxtypename'])." ".$a['subcategorydescription']." ".$a['categorydescription'];
            $label = (empty($a['year'])) ? $label : $a['year']." ".$label;
            $url    = "listing.php?categoryid=".$a["categoryid"]."&subcategoryid=".$a["subcategoryid"]."&boxtypeid=".$a["boxtypeid"]."&listingtypeid=".$a["categorytypeid"]."&year=".$a["year"];
            $link   = "<a href='".$url."' target='_blank'>".$label."</a>";
            echo "      <td>".$link."</td>\n";
            echo "      <td class='center'>".$a['type']."</td>\n";
            echo "      <td class='number'>$ ".number_format($a['dprice'],2)."</td>\n";
            $hibuy  = (empty($a['highbuy'])) ? "" : "$".number_format($a['highbuy'], 2);
            $losell  = (empty($a['lowsell'])) ? "" : "$".number_format($a['lowsell'], 2);
            echo "      <td class='center'>".$hibuy." / ".$losell."</td>\n";
            echo "      <td class='fa-action-items'>\n";
            $confirm  = "Are you sure you want to delete this alert?";
            $confirm .= "\\n    ".$a["type"].": ".$label." @ $".number_format($a["dprice"], 2);
            echo "        <a class='fas fa-trash-alt' title='Delete ".$label."' href='javascript:void(0);' onclick=\"javascript: if (confirm('".$confirm."')) { document.pa_form.aid.value=".$a["alertid"]."; document.pa_form.submit(); }\"></a>\n";
            echo "      </td>\n";
            echo "    </tr>\n";
        }
    } else {
        echo "      <tr><td class='center' colspan='5'>No associated alerts found.</td></tr>\n";
    }

    echo "  </tbody>\n";
    echo "</table>\n";
}

function getPriceAlertCounts($categoryid, $listingTypeId, $subcategoryid, $boxtypeid, $year) {
    global $page;

    $catid      = (empty($categoryid)) ? "pa.categoryid" : $categoryid;
    $subcatid   = (empty($subcategoryid)) ? "pa.subcategoryid" : $subcategoryid;
    $boxtypeid  = (empty($boxtypeid)) ? "pa.boxtypeid" : $boxtypeid;
    $yr         = (empty($year) || $listingTypeId == LISTING_TYPE_GAMING) ? "pa.year" : "'".$year."'";
    $sql = "
        SELECT count(1) as alert_cnt
          FROM pricealerts      pa
          JOIN categories           c   ON  c.categoryid        = pa.categoryid
                                        AND c.active            = 1
          JOIN subcategories        sc  ON  sc.subcategoryid    = pa.subcategoryid
                                        AND sc.active           = 1
          JOIN boxtypes             bt  ON  bt.boxtypeid        = pa.boxtypeid
                                        AND bt.active           = 1
         WHERE pa.userid        = ".$page->user->userId."
           AND pa.status        = 'OPEN'
           AND c.categoryid     = ".$catid."
           AND sc.subcategoryid = ".$subcatid."
           AND bt.boxtypeid     = ".$boxtypeid."
           AND pa.year          = ".$yr."
    ";

    $count = $page->db->get_field_query($sql);

    return $count;

}

function getPriceAlerts($categoryid, $listingTypeId, $subcategoryid, $boxtypeid, $year) {
    global $page;

    $factoryCostID = $page->utility->getDealerId(FACTORYCOSTNAME);
    $catid      = (empty($categoryid)) ? "c.categoryid" : $categoryid;
    $subcatid   = (empty($subcategoryid)) ? "sc.subcategoryid" : $subcategoryid;
    $boxtypeid  = (empty($boxtypeid)) ? "bt.boxtypeid" : $boxtypeid;
    $yr         = (empty($year)) ? "l.year" : "'".$year."'";

    $random = rand();
    $sql = "DROP TABLE IF EXISTS tmp_high_buy_".$random;
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_low_sell_".$random;
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_high_buy_".$random." AS
        SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year, MAX(l.boxprice) AS highbuy
          FROM listings             l
          JOIN categories           c   ON  c.categoryid        = l.categoryid
                                        AND c.active            = 1
          JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                        AND sc.active           = 1
          JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                        AND bt.active           = 1
          JOIN assignedrights       a   ON  a.userid            = l.userid
                                        AND a.userrightid       = 1
          JOIN userinfo             u   ON  u.userid            = l.userid
                                        AND u.userclassid       = 3
                                        AND u.vacationbuy       = 0
          LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                        AND stl.userrightid     = ".USERRIGHT_STALE."
         WHERE l.type               = 'Wanted'
           AND l.status             = 'OPEN'
           AND l.uom IN ('box', 'case')
           AND l.categoryid         = ".$catid."
           AND l.subcategoryid      = ".$subcatid."
           AND l.boxtypeid          = ".$boxtypeid."
           AND (c.categorytypeid    = ".LISTING_TYPE_GAMING."
                OR (c.categorytypeid= ".LISTING_TYPE_SPORTS."
                    AND l.year      = ".$yr."))
           AND l.userid             <> ".$factoryCostID."
           AND stl.userid IS NULL
        GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
        ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE tmp_low_sell_".$random." AS
        SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year, MIN(l.boxprice) AS lowsell
          FROM listings             l
          JOIN categories           c   ON  c.categoryid        = l.categoryid
                                        AND c.active            = 1
          JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                        AND sc.active           = 1
          JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                        AND bt.active           = 1
          JOIN assignedrights       a   ON  a.userid            = l.userid
                                        AND a.userrightid       = 1
          JOIN userinfo             u   ON  u.userid            = l.userid
                                        AND u.userclassid       = 3
                                        AND u.vacationsell      = 0
          LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                        AND stl.userrightid     = ".USERRIGHT_STALE."
         WHERE l.type               = 'For Sale'
           AND l.status             = 'OPEN'
           AND l.uom IN ('box', 'case')
           AND l.categoryid         = ".$catid."
           AND l.subcategoryid      = ".$subcatid."
           AND l.boxtypeid          = ".$boxtypeid."
           AND (c.categorytypeid    = ".LISTING_TYPE_GAMING."
                OR (c.categorytypeid= ".LISTING_TYPE_SPORTS."
                    AND l.year      = ".$yr."))
           AND l.userid             <> ".$factoryCostID."
           AND stl.userid IS NULL
        GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
        ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
    ";
    $page->queries->AddQuery($sql);

    $page->queries->ProcessQueries();

    $yr         = (empty($year)) ? "pa.year" : "'".$year."'";
    $sql = "
        SELECT pa.alertid, pa.status, pa.type, pa.categoryid, pa.subcategoryid, pa.year, pa.boxtypeid, pa.dprice, pa.uom,
               c.categorydescription, sc.subcategorydescription, c.categoryname, sc.subcategoryname, bt.boxtypename,
               c.categorytypeid, hb.highbuy, ls.lowsell
          FROM pricealerts                      pa
          JOIN categories                   c   ON  c.categoryid        = pa.categoryid
                                                AND c.active            = 1
          JOIN subcategories                sc  ON  sc.subcategoryid    = pa.subcategoryid
                                                AND sc.active           = 1
          JOIN boxtypes                     bt  ON  bt.boxtypeid        = pa.boxtypeid
                                                AND bt.active           = 1
          LEFT JOIN tmp_high_buy_".$random."    hb  ON  hb.categoryid           = c.categoryid
                                                    AND hb.subcategoryid        = sc.subcategoryid
                                                    AND hb.boxtypeid            = bt.boxtypeid
                                                    AND (c.categorytypeid       = ".LISTING_TYPE_GAMING."
                                                         OR (c.categorytypeid   = ".LISTING_TYPE_SPORTS."
                                                             AND hb.year        = ".$yr."))
          LEFT JOIN tmp_low_sell_".$random."    ls  ON  ls.categoryid           = c.categoryid
                                                    AND ls.subcategoryid        = sc.subcategoryid
                                                    AND ls.boxtypeid            = bt.boxtypeid
                                                    AND (c.categorytypeid       = ".LISTING_TYPE_GAMING."
                                                         OR (c.categorytypeid   = ".LISTING_TYPE_SPORTS."
                                                             AND ls.year        = ".$yr."))
         WHERE pa.userid        = ".$page->user->userId."
           AND pa.status        = 'OPEN'
           AND pa.categoryid    = ".$catid."
           AND pa.subcategoryid = ".$subcatid."
           AND pa.boxtypeid     = ".$boxtypeid."
           AND (c.categorytypeid    = ".LISTING_TYPE_GAMING."
                OR (c.categorytypeid= ".LISTING_TYPE_SPORTS."
                    AND pa.year     = ".$yr."))
        ORDER BY pa.type DESC, c.categoryname, sc.subcategoryname, bt.boxtypename, pa.year";

//  echo "<pre>".$sql."</pre>";
    $alerts = $page->db->sql_query_params($sql);

    unset($page->queries);
    $page->queries = new DBQueries("price alert cleanup");

    $sql = "DROP TABLE IF EXISTS tmp_high_buy_".$random;
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS tmp_low_sell_".$random;
    $page->queries->AddQuery($sql);
    $process = $page->queries->ProcessQueries();

    return $alerts;
}

function getListingCategories($categorytypeid = 1) {
    global $page;

    $sql = "
        SELECT c.categoryid, c.categorytypeid, c.categorydescription
          FROM categories   c
          JOIN listings     l   ON  l.status        = 'OPEN'
                                AND l.categoryid    = c.categoryid
         WHERE c.active         = 1
           AND c.categorytypeid IN (".$categorytypeid.")
        GROUP BY c.categoryid, c.categorytypeid, c.categorydescription
        ORDER BY c.categorytypeid, c.categorydescription COLLATE \"POSIX\"";

//      echo "<pre>".$sql."</pre>";
    $rs = $page->db->sql_query_params($sql);

    return $rs;
}

function categoryDDM($categorytypeid = 1, $categoryid = null) {
    global $page, $listingTypeId;

    $categories = getListingCategories($categorytypeid);

    $onChange = " onchange = \"$('#subcategoryid').val('');$('#year').val('');$('#boxtypeid').val('');submit();\"";

    $output = "        ".getSelectDDM($categories, "categoryid", "categoryid", "categorydescription", NULL, $categoryid, "Select", 0, NULL, NULL, $onChange)."\n";

    return $output;
}

function getListingSubCategories($categoryid = null) {
    global $page;

    $sql = "
        SELECT sc.subcategoryid, sc.subcategoryname
          FROM subCategories    sc
          JOIN categories       c   ON  c.categoryid    = sc.categoryid
                                    AND c.active        = 1
                                    AND c.categoryid    = ".$categoryid."
          JOIN listings         l   ON  l.status        = 'OPEN'
                                    AND l.categoryid    = c.categoryid
                                    AND l.subcategoryid = sc.subcategoryid
         WHERE sc.active = 1
         GROUP BY sc.subcategoryid, sc.subcategoryname
         ORDER BY sc.subcategoryname COLLATE \"POSIX\"";

      $rs = $page->db->sql_query_params($sql);

      return $rs;
}

function subCategoryDDM($categoryid, $subcategoryid = null) {
    global $page, $listingTypeId;

    $subcategories = getListingSubCategories($categoryid);

    $onChange = " onchange = \"$('#year').val('');$('#boxtypeid').val('');submit();\"";

    $output = "        ".getSelectDDM($subcategories, "subcategoryid", "subcategoryid", "subcategoryname", NULL, $subcategoryid, "Select", 0, NULL, NULL, $onChange)."\n";

    return $output;
}

function getListingBoxTypes($categoryid = null, $subcategoryid = null) {
    global $page;

    $subcat = (empty($subcategoryid)) ? "" : "AND l.subcategoryid = ".$subcategoryid;
    $sql = "
        SELECT b.boxtypeid, b.boxtypename
          FROM boxtypes     b
          JOIN listings     l   ON  l.status        = 'OPEN'
                                AND l.boxtypeid     = b.boxtypeid
                                AND l.categoryid    = ".$categoryid."
                                ".$subcat."
         WHERE b.active = 1
        GROUP BY b.boxtypeid, b.boxtypename
        ORDER BY b.boxtypename COLLATE \"POSIX\"
    ";

    $rs = $page->db->sql_query($sql);

    return $rs;
}

function boxTypeDDM($categoryid = null, $subcategoryid = null, $boxtypeid = null) {
    global $page, $listingTypeId;

    $rs = getListingBoxTypes($categoryid, $subcategoryid);
    $onChange = " onchange = \"submit();\"";
    $output = getSelectDDM($rs, "boxtypeid", "boxtypeid", "boxtypename", NULL, $boxtypeid, "All", 0, NULL, NULL, $onChange)."\n";

    return $output;
}

function getListingYears($categoryid, $subcategoryid = null, $boxtypeid = null, $year = NULL) {
    global $page;

    $subcat = (empty($subcategoryid)) ? "" : "AND l.subcategoryid = ".$subcategoryid;
    $btid   = (empty($boxtypeid)) ? "" : "AND l.boxtypeid = ".$boxtypeid;
    $sql = "
        SELECT l.year, l.year as yearname, l.year4
          FROM listings             l
          JOIN categories           c   ON  c.categoryid        = l.categoryid
                                        AND c.active            = 1
          JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                        AND sc.active           = 1
          JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                        AND bt.active           = 1
          JOIN assignedrights       a   ON  a.userid            = l.userid
                                        AND a.userrightid       = 1
          JOIN userinfo             u   ON  u.userid            = l.userid
                                        AND u.userclassid       = 3
                                        AND u.vacationsell      = 0
          LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                        AND stl.userrightid     = ".USERRIGHT_STALE."
         WHERE l.status     = 'OPEN'
           AND l.categoryId = ".$categoryid."
           ".$subcat."
           ".$btid."
           AND stl.userid IS NULL
        GROUP BY l.year, l.year4
        ORDER BY l.year4 DESC";

//  echo "<pre>".$sql."</pre>";
    $yearData = $page->db->sql_query($sql);

    return $yearData;
}

function yearDDM($categoryid, $subcategoryid = null, $boxtypeid = null, $year = NULL) {
    global $page;

    $rs = getListingYears($categoryid, $subcategoryid, $boxtypeid);
    $onChange = " onchange = \"submit();\"";
    $output = getSelectDDM($rs, "year", "year", "yearname", NULL, $year, "Select", 0, NULL, NULL, $onChange)."\n";

    return $output;
}

function insertAlert($status, $type, $categoryid, $subcategoryid, $boxtypeid, $year, $dprice, $uom) {
    global $page;

     $success = "";
     $sql = "
        INSERT INTO pricealerts( userid,  status,  type,  categoryid,  subcategoryid,  year,  boxtypeid,  dprice,  uom,  createdby)
                         VALUES(:userid, :status, :type, :categoryid, :subcategoryid, :year, :boxtypeid, :dprice, :uom, :createdby)
    ";
    $params = array();
    $params['userid']           = $page->user->userId;
    $params['status']           = $status;
    $params['type']             = $type;
    $params['categoryid']       = $categoryid;
    $params['subcategoryid']    = $subcategoryid;
    $params['year']             = $year;
    $params['boxtypeid']        = $boxtypeid;
    $params['dprice']           = $dprice;
    $params['uom']              = $uom;
    $params['createdby']        = $page->user->username;

    try {
        $page->db->sql_execute_params($sql, $params);
        $page->messages->addSuccessMsg("Price alert added.");
    } catch (Exception $e) {
        $page->messages->addErrorMsg("ERROR: ".$e->getMessage());
    } finally {
        unset($params);
    }

}

function deleteAlert($alertid) {
    global $page;

     $success = "";
     $sql = "
        DELETE FROM pricealerts
         WHERE userid   = ".$page->user->userId."
           AND alertid  = ".$alertid;

    try {
        $page->db->sql_execute($sql);
        $page->messages->addSuccessMsg("Price alert deleted.");
    } catch (Exception $e) {
        $page->messages->addErrorMsg("ERROR: ".$e->getMessage());
    } finally {
        unset($params);
    }

}

function getHiBuyLoSell($categoryid, $subcategoryid, $boxtypeid, $year, $listingtypeid) {
    global $page;

    $factoryCostID = $page->utility->getDealerId(FACTORYCOSTNAME);
    $catid      = (empty($categoryid)) ? "c.categoryid" : $categoryid;
    $subcatid   = (empty($subcategoryid)) ? "sc.subcategoryid" : $subcategoryid;
    $boxtypeid  = (empty($boxtypeid)) ? "bt.boxtypeid" : $boxtypeid;
    $yr         = (empty($year) || $listingtypeid == LISTING_TYPE_GAMING) ? "l.year" : "'".$year."'";
    $hibuyselect = "
        SELECT max(l.boxprice) as hibuy";
    $losellselect = "
        SELECT min(l.boxprice) as losell";
    $fstype = "
           AND l.type             = 'For Sale'";
    $wtype = "
           AND l.type             = 'Wanted'";
    $where = "";
    if (isset($alert["year"]) && !empty($year)) {
        $where .= "
           AND l.year             = '".$year."'";
    }
    $sql = "
          FROM listings             l
          JOIN categories           c   ON  c.categoryid        = l.categoryid
                                        AND c.active            = 1
          JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                        AND sc.active           = 1
          JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                        AND bt.active           = 1
          JOIN assignedrights       a   ON  a.userid            = l.userid
                                        AND a.userrightid       = 1
          JOIN userinfo             u   ON  u.userid            = l.userid
                                        AND u.userclassid       = 3
                                        AND u.vacationsell      = 0
          LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                        AND stl.userrightid     = ".USERRIGHT_STALE."
         WHERE l.status           = 'OPEN'
           AND l.categoryid       = ".$catid."
           AND l.subcategoryid    = ".$subcatid."
           AND l.boxtypeid        = ".$boxtypeid."
           AND l.uom IN ('box', 'case')
           AND (c.categorytypeid    = ".LISTING_TYPE_GAMING."
                OR (c.categorytypeid= ".LISTING_TYPE_SPORTS."
                    AND l.year      = ".$yr."))
           AND l.userid           <> ".$factoryCostID."
           AND stl.userid IS NULL
    ";
    $sql .= $where;

    $hibuysql = $hibuyselect.$sql.$wtype;
    $losellsql = $losellselect.$sql.$fstype;
//  echo "<pre>".$hibuysql."</pre>";
//  echo "<pre>".$losellsql."</pre>";
    $rs = array();
    $rs["hibuy"]    = $page->db->get_field_query($hibuysql);
    $rs["losell"]   = $page->db->get_field_query($losellsql);

    return $rs;
}
?>