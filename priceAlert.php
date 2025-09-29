<?php
require_once('templateMarket.class.php');

$page = new templateMarket(LOGIN, SHOWMSG, REDIRECTSAFE);
$page->requireJS('scripts/populateSubcatBoxtype.js');
$page->requireJS('scripts/listingPage.js');
$page->requireJS('scripts/priceAlerts.js');

$addalert       = optional_param('addalert', NULL, PARAM_RAW);
$alertid        = optional_param('aid', 0, PARAM_INT);

$categoryid     = optional_param('categoryid', NULL, PARAM_INT);
$subcategoryid  = optional_param('subcategoryid', NULL, PARAM_INT);
$boxtypeid      = optional_param('boxtypeid', NULL, PARAM_INT);
$year           = optional_param('year', NULL, PARAM_RAW);
$dprice         = optional_param('dprice', NULL, PARAM_RAW);
$status         = optional_param('status', "OPEN", PARAM_RAW);
$type           = optional_param('type', NULL, PARAM_RAW);
$uom            = optional_param('uom', "box", PARAM_RAW);
$listingtypeid  = optional_param('listingtypeid', NULL, PARAM_INT);
$hibuy          = str_replace("$", "", optional_param('hibuy', NULL, PARAM_RAW));
$losell         = str_replace("$", "", optional_param('losell', NULL, PARAM_RAW));

$oNames = new ListingReferral();

$alert = array();
$alert["alertid"]       = $alertid;
$alert["categoryid"]    = $categoryid;
$alert["category"]      = $oNames->referCategoryName;
$alert["subcategoryid"] = $subcategoryid;
$alert["subcategory"]   = $oNames->referSubCategoryName;
$alert["boxtypeid"]     = $boxtypeid;
$alert["boxtype"]       = ucwords($oNames->referBoxTypeName);
$alert["year"]          = $year;
$alert["type"]          = $type;
$alert["status"]        = $status;
$alert["uom"]           = $uom;
$alert["listingtypeid"] = $listingtypeid;
$alert["dprice"]        = $dprice;

if (!empty($alertid)) {
    deleteAlert($alert);
} elseif (!empty($addalert) && !empty($type) && !empty($dprice)) {
    if ($type == 'Wanted' && !empty($hibuy) && $dprice <= $hibuy) {
        $page->messages->addWarningMsg("Price alert NOT added as the price (".$dprice.") is equal to or lower than the high buy (".$hibuy.")");
    } elseif ($type == 'For Sale' && !empty($losell) && $dprice >= $losell) {
        $page->messages->addWarningMsg("Price alert NOT added as the price (".$dprice.") is equal to or higher than the low sell (".$losell.")");
    } else {
        insertAlert($alert);
    }
}

echo $page->header("Price Alerts");
echo mainContent();
echo $page->footer();


function mainContent() {
    global $page, $alert;

    echo "<h3>Add Price Alert</h3>\n";
    if ($alert) {
        echo "<form name ='addAlert' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
        echo "<table>\n";
        echo "  <thead>\n";
        echo "    <tr>\n";
        echo "      <th>Product</th>\n";
        echo "      <th>Type</th>\n";
        echo "      <th>Price</th>\n";
        echo "      <th>High Buy / Low Sell</th>\n";
        echo "      <th></th>\n";
        echo "    </tr>\n";
        echo "  <thead>\n";
        echo "  <tbody>\n";
        echo "    <tr>\n";
        $label = $alert['boxtype']." ".$alert['subcategory']." ".$alert['category'];
        $label = (empty($alert["year"])) ? $label : $alert['year']." ".$label;
        echo "      <td>".$label."</td>\n";
        echo "      <td class='center'>\n";
        echo "        <input type='radio' name='type' id='type' value='Wanted' ".$page->utility->isChecked($alert['type'], 'Wanted')." required>Wanted  &nbsp;\n";
        echo "        <input type='radio' name='type' id='type' value='For Sale' ".$page->utility->isChecked($alert['type'], 'For Sale').">For Sale\n";
        echo "      </td>\n";
        echo "      <td>\n";
        echo "        <input type='text' name='dprice' id='dprice' size='4' value='".$alert['dprice']."' required>\n";
        echo "        <input type='hidden' name='categoryid' id='categoryid' value='".$alert['categoryid']."'>\n";
        echo "        <input type='hidden' name='subcategoryid' id='subcategoryid' value='".$alert['subcategoryid']."'>\n";
        echo "        <input type='hidden' name='boxtypeid' id='boxtypeid' value='".$alert['boxtypeid']."'>\n";
        echo "        <input type='hidden' name='year' id='year' value='".$alert['year']."'>\n";
        echo "        <input type='hidden' name='uom' id='uom' value='box'>\n";
        echo "      </td>\n";
        $hilo = getHiBuyLoSell($alert);
        $hibuy  = (empty($hilo["hibuy"])) ? "" : "$".number_format($hilo["hibuy"], 2);
        $losell = (empty($hilo["losell"])) ? "" : "$".number_format($hilo["losell"], 2);
        echo "      <td class='center'>".$hibuy." / ".$losell."</td>\n";
        echo "      <td class='center'>\n";
        echo "        <input type='submit' id='addalert' name='addalert' value='Add'>\n";
        $link = "listing.php?categoryid=".$alert['categoryid']."&subcategoryid=".$alert['subcategoryid']."&boxtypeid=".$alert['boxtypeid']."&listingtypeid=".$alert['listingtypeid']."&year=".$alert['year'];
        echo "        <input type='button' value='Cancel' onclick='javascript: window.location.href=\"".$link."\"'>\n";
        echo "      </td>\n";
        echo "    </tr>\n";
        echo "  </tbody>\n";
        echo "</table>\n";
        echo "<input type='hidden' name='hibuy' id='hibuy' value='".$hibuy."'>\n";
        echo "<input type='hidden' name='losell' id='losell' value='".$losell."'>\n";
        echo "</form>\n";
        echo "<div>&nbsp;</div>\n";
        echo "<h3>Associated Price Alerts</h3>\n";
        echo "<form name ='deleteAlert' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
        echo "<table>\n";
        echo "  <thead>\n";
        echo "    <tr>\n";
        echo "      <th>Product</th>\n";
        echo "      <th>Type</th>\n";
        echo "      <th>Price</th>\n";
        echo "      <th>Status</th>\n";
        echo "      <th>&nbsp;</th>\n";
        echo "    </tr>\n";
        echo "  <thead>\n";
        echo "  <tbody>\n";
        if ($alerts = getMatchingPriceAlerts($alert)) {
            foreach($alerts as $a) {
                echo "    <tr>\n";
                $label = $alert['boxtype']." ".$alert['subcategory']." ".$alert['category'];
                $label = (empty($alert["year"])) ? $label : $alert['year']." ".$label;
                echo "      <td>".$label."</td>\n";
                echo "      <td class='center'>".$a["type"]."</td>\n";
                echo "      <td class='number'>$".number_format($a["dprice"], 2)."</td>\n";
                $status = ($a["status"] == "Triggered") ? $a["status"]." on ".date('m/d/Y',$a["modifydate"]) : $a["status"];
                echo "      <td class='center'>".$status."</td>\n";
                echo "      <td class='fa-action-items'>\n";
                $confirm  = "Are you sure you want to delete this alert?";
                $confirm .= "\\n    ".$a["type"].": ".$label." @ $".number_format($a["dprice"], 2);
                echo "        <a class='fas fa-trash-alt' title='Delete ".$label."' href='javascript:void(0);' onclick=\"javascript: if (confirm('".$confirm."')) { document.deleteAlert.aid.value=".$a["alertid"]."; document.deleteAlert.submit(); }\"></a>\n";
                echo "      </td>\n";
                echo "    </tr>\n";
            }
        } else {
        echo "      <tr><td class='center' colspan='5'>No associated alerts found.</td></tr>\n";
        }

        echo "  </tbody>\n";
        echo "</table>\n";
        echo "<input type='hidden' name='aid' id='aid' value=''>\n";
        echo "<input type='hidden' name='categoryid' id='categoryid' value='".$alert['categoryid']."'>\n";
        echo "<input type='hidden' name='subcategoryid' id='subcategoryid' value='".$alert['subcategoryid']."'>\n";
        echo "<input type='hidden' name='boxtypeid' id='boxtypeid' value='".$alert['boxtypeid']."'>\n";
        echo "<input type='hidden' name='year' id='year' value='".$alert['year']."'>\n";
        echo "<input type='hidden' name='listingtypeid' id='listingtypeid' value='".$alert['listingtypeid']."'>\n";
        echo "<input type='hidden' name='uom' id='uom' value='box'>\n";
        echo "</form>\n";
    }
}


function insertAlert($alert) {
    global $page;

     $success = "";
     $sql = "
        INSERT INTO pricealerts( userid,  status,  type,  categoryid,  subcategoryid,  year,  boxtypeid,  dprice,  uom,  createdby)
                         VALUES(:userid, :status, :type, :categoryid, :subcategoryid, :year, :boxtypeid, :dprice, :uom, :createdby)
    ";
    $params = array();
    $params['userid']           = $page->user->userId;
    $params['status']           = $alert["status"];
    $params['type']             = $alert["type"];
    $params['categoryid']       = $alert["categoryid"];
    $params['subcategoryid']    = $alert["subcategoryid"];
    $params['year']             = $alert["year"];
    $params['boxtypeid']        = $alert["boxtypeid"];
    $params['dprice']           = $alert["dprice"];
    $params['uom']              = $alert["uom"];
    $params['createdby']        = $page->user->username;

    try {
        $page->db->sql_execute_params($sql, $params);
        $label = $alert['boxtype']." ".$alert['subcategory']." ".$alert['category'];
        $label = (empty($alert["year"])) ? $label : $alert['year']." ".$label;
        $page->messages->addSuccessMsg("Price alert added for ".$label);
    } catch (Exception $e) {
        $page->messages->addErrorMsg("ERROR: ".$e->getMessage());
    } finally {
        unset($params);
    }

}

function deleteAlert($alert) {
    global $page;

     $success = "";
     $sql = "
        DELETE FROM pricealerts
         WHERE userid   = ".$page->user->userId."
           AND alertid  = ".$alert["alertid"];

    try {
        $page->db->sql_execute($sql);
        $label = $alert['boxtype']." ".$alert['subcategory']." ".$alert['category'];
        $label = (empty($alert["year"])) ? $label : $alert['year']." ".$label;
        $page->messages->addSuccessMsg("Price alert deleted for ".$label);
    } catch (Exception $e) {
        $page->messages->addErrorMsg("ERROR: ".$e->getMessage());
    } finally {
        unset($params);
    }

}

function getMatchingPriceAlerts($alert) {
    global $page;

    $where = "
         WHERE pa.userid=".$page->user->userId;
    if (isset($alert["year"]) && !empty($alert["year"])) {
        $where ."
           AND pa.year = '".$alert["year"]."'";
    }
    $sql = "
        SELECT pa.alertid, pa.status, pa.type, pa.categoryid, pa.subcategoryid, pa.year, pa.boxtypeid, pa.dprice, pa.uom,
               c.categoryname, sc.subcategoryname, b.boxtypename, pa.modifydate
          FROM pricealerts      pa
          JOIN categories       c   ON  c.categoryid        = pa.categoryid
                                    AND c.categoryid        = ".$alert["categoryid"]."
          JOIN subcategories    sc  ON  sc.subcategoryid    = pa.subcategoryid
                                    AND sc.subcategoryid    = ".$alert["subcategoryid"]."
          JOIN boxtypes         b   ON  b.boxtypeid         = pa.boxtypeid
                                    AND b.boxtypeid         = ".$alert["boxtypeid"];
    $sql .= $where;
    $sql .= "
        ORDER BY pa.status, pa.type DESC, pa.dprice
    ";

//  echo "<pre>".$sql."</pre>";
    $rs = $page->db->sql_query_params($sql);

    return $rs;
}

function getHiBuyLoSell($alert) {
    global $page;

    $factoryCostID = $page->utility->getDealerId(FACTORYCOSTNAME);
    $hibuyselect = "
        SELECT max(l.boxprice) as hibuy";
    $losellselect = "
        SELECT min(l.boxprice) as losell";
    $fstype = "
           AND l.type           = 'For Sale'";
    $wtype = "
           AND l.type           = 'Wanted'";
    $where = "";
    if (isset($alert["year"]) && !empty($alert["year"])) {
        $where .= "
           AND year             = '".$alert["year"]."'";
    }
    $sql = "
          FROM listings             l
          JOIN userinfo             u   ON  u.userid            = l.userid
                                        AND u.userclassid       = 3
                                        AND u.vacationsell      = 0
          LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                        AND stl.userrightid     = ".USERRIGHT_STALE."
         WHERE l.categoryid     = ".$alert["categoryid"]."
           AND l.subcategoryid  = ".$alert["subcategoryid"]."
           AND l.boxtypeid      = ".$alert["boxtypeid"]."
           AND l.status         = 'OPEN'
           AND l.uom IN ('box', 'case')
           AND stl.userid IS NULL
           AND l.userid           <> ".$factoryCostID;
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