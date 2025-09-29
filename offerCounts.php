<?php
require_once("paginator.class.php");
require_once('templateAdmin.class.php');
require_once('metric.class.php');
$page = new templateAdmin(LOGIN, SHOWMSG);

$searchstring   = optional_param('searchstring', NULL, PARAM_RAW);
$sortby         = optional_param('sortby', METRIC_SORT_TOTAL_OFFERS." DESC", PARAM_RAW);
$pagenum        = optional_param('page', 1, PARAM_INT);
$prevpage       = optional_param('prevpage', 0, PARAM_INT);
$perpage        = optional_param('perpage', NULL, PARAM_INT);
$export         = optional_param('export', 0, PARAM_INT);
$doReload       = optional_param('doreload', 0, PARAM_INT);
$doPromote      = optional_param('dopromote', 0, PARAM_INT);
$doDemote       = optional_param('dodemote', 0, PARAM_INT);
$metricInterval = optional_param('intervalid', METRIC_INTERVAL_ALL, PARAM_INT);
$dealerLevelId  = optional_param('dealerlevelid', 0, PARAM_INT);

$dealerMetrics = new DealerMetrics();

if ($doReload) {
    reloadOfferCounts();
}

if ($doPromote) {
    promoteBlueStarDealers();
    $dealerLevelId = METRIC_DEALER_LEVEL_BLUESTAR;
    $metricInterval = METRIC_INTERVAL_BLUESTAR;
}

if ($doDemote) {
    demoteBlueStarDealers();
    $dealerLevelId = METRIC_DEALER_LEVEL_BLUESTAR;
    $metricInterval = METRIC_INTERVAL_BLUESTAR;
}
    
if (!empty($export)) {
    $x = export();
    if (empty($x)) {
        $page->messages->addErrorMsg("ERROR: Unable to export login information");
    }
}

$perpage = (isset($perpage)) ? $perpage : $page->cfg->PERPAGE;
$totalRows = 0;
$totalPages = 0;
$dealerName = (empty($searchstring)) ? NULL : $searchstring;
$dealerId = NULL;

$columnHeaders = array(
    array('display' => 'User', 'sort' => METRIC_SORT_DEALER_NAME, 'rev' => METRIC_SORT_DEALER_NAME." DESC"),
    array('display' => 'Revised', 'sort' => METRIC_SORT_REVISED." DESC", 'rev' => METRIC_SORT_REVISED),
    array('display' => 'Pending', 'sort' => METRIC_SORT_PENDING." DESC", 'rev' => METRIC_SORT_PENDING),
    array('display' => 'Cancelled', 'sort' => METRIC_SORT_CANCELLED." DESC", 'rev' => METRIC_SORT_CANCELLED),
    array('display' => 'Voided', 'sort' => METRIC_SORT_VOIDED." DESC", 'rev' => METRIC_SORT_VOIDED),
    array('display' => 'Accepted', 'sort' => METRIC_SORT_ACCEPTED." DESC", 'rev' => METRIC_SORT_ACCEPTED),
    array('display' => 'Declined', 'sort' => METRIC_SORT_DECLINED." DESC", 'rev' => METRIC_SORT_DECLINED),
    array('display' => 'Expired', 'sort' => METRIC_SORT_EXPIRED." DESC", 'rev' => METRIC_SORT_EXPIRED),
    array('display' => 'Total', 'sort' => METRIC_SORT_TOTAL_OFFERS." DESC", 'rev' => METRIC_SORT_TOTAL_OFFERS),
    array('display' => 'Accept Pct', 'sort' => METRIC_SORT_ACCEPT_PCT." DESC NULLS LAST", 'rev' => METRIC_SORT_ACCEPT_PCT),
    array('display' => 'Decline Pct', 'sort' => METRIC_SORT_DECLINE_PCT." DESC NULLS LAST", 'rev' => METRIC_SORT_ACCEPT_PCT),
    array('display' => 'Expire Pct', 'sort' => METRIC_SORT_EXPIRE_PCT." DESC NULLS LAST", 'rev' => METRIC_SORT_EXPIRE_PCT),
    array('display' => 'Tracking Pct', 'sort' => METRIC_SORT_TRACK_PCT." DESC NULLS LAST", 'rev' => METRIC_SORT_TRACK_PCT),
    array('display' => 'Cancel Pct', 'sort' => METRIC_SORT_CANCEL_PCT." DESC NULLS LAST", 'rev' => METRIC_SORT_CANCEL_PCT),
    array('display' => 'Response Time', 'sort' => METRIC_SORT_AVG_RESPONSE." DESC NULLS LAST", 'rev' => METRIC_SORT_AVG_RESPONSE),
    array('display' => 'Dealer Rating', 'sort' => METRIC_SORT_DEALER_RATING." DESC NULLS LAST", 'rev' => METRIC_SORT_DEALER_RATING),
    array('display' => 'Dealer 5 Pct', 'sort' => METRIC_SORT_DEALER_RATE5_PCT." DESC NULLS LAST", 'rev' => METRIC_SORT_DEALER_RATE5_PCT),
    array('display' => 'Assistance', 'sort' => METRIC_SORT_ASSISTANCE." DESC NULLS LAST", 'rev' => METRIC_SORT_ASSISTANCE)
);

echo $page->header('Offer Counts');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $searchstring, $sortby, $dealerName, $metricInterval, $dealerMetrics;
    global $totalRows, $totalPages, $pagenum, $perpage, $columnHeaders;

    $doBlueStar = ($metricInterval == METRIC_INTERVAL_BLUESTAR) ? 1 : 0;

    $data = getOfferCounts($pagenum, $perpage);
    $x = ($pagenum-1) * $perpage;

    if ($totalRows) {
        $pager = new Paginator($perpage, "page");
        $pager->set_total($totalRows);
    }

    echo "<h3>Offer Counts Report</h3>\n";
    echo "<article>\n";
    echo "  <div class='entry-content'>\n";
    echo "    <form name='searchform' id='searchform' method='post' action='offerCounts.php'>\n";
    echo "      <input type='hidden' name='export' id='export' value=''>\n";
    echo "      <input type='hidden' name='print' id='print' value=''>\n";
    echo "      <input type='hidden' name='sortby' id='sortby' value='".$sortby."'>\n";
    echo "      <table>\n";
    echo "        <tbody>\n";
    echo "          <tr>\n";
    echo "            <td>\n";
    echo "              <label for='searchstring'>Interval</label>";
    echo metricIntervalDDM();
    echo "            </td>\n";
    echo "            <td>\n";
    echo "              <label for='searchstring'>Dealer</label>";
    echo "              <input type='text' name='searchstring' id='searchstring' value='".$searchstring."' class='input' style='width:250px;'>\n";
    echo "            </td>\n";
    echo "            <td>\n";
    echo "              <label for='searchstring'>Level</label>";
    echo dealerLevelDDM();
    echo "            </td>\n";
    echo "            <td><input type='submit' name='searchbtn' value='Search' onclick='document.search.target=\"_self\"; document.search.export.value=\"\"; document.search.print.value=\"\"; '></td>\n";
    echo "            <td><a href='offerCounts.php?doreload=1' class='button'>Refresh All Data</a></td>\n";
    echo "            <td><a href='offerCounts.php?dopromote=1' class='button'>Promote BlueStar</a></td>\n";
    echo "            <td><a href='offerCounts.php?dodemote=1' class='button'>Demote BlueStar</a></td>\n";
    echo "          </tr>\n";
    echo "        </tbody>\n";
    echo "      </table>\n";
    echo "      <div>&nbsp;</div>\n";
    echo "      <table border='1'>\n";
    echo "        <caption>\n";
    echo "          <span style='float:left;font-weight:bold;'>Page ".$pagenum." of ".$totalPages."</span>";
    echo "          <a href='Javascript: document.searchform.target=\"_self\"; document.searchform.export.value=\"1\"; document.searchform.submit();' class='icon export'>Export</a>\n";
    echo "        </caption>\n";
    echo "        <thead>\n";
    echo "          <tr>\n";
    foreach ($columnHeaders as $columnHeader) {
        $sortIcon = "";
        $columnSort = "";
        if ($sortby == $columnHeader['sort']) {
            $columnSort = $columnHeader['rev'];
            $sortIcon = " <i class='fas fa-caret-down' aria-hidden='true'></i>";
        } else {
            $columnSort = $columnHeader['sort'];
            if ($sortby == $columnHeader['rev']) {
                $sortIcon = " <i class='fas fa-caret-up' aria-hidden='true'></i>";
            }
        }
        $link = "<a href='#' onClick=\"$('#sortby').val('".$columnSort."');$('#searchform').submit();\">".$columnHeader['display']."</a>".$sortIcon;
        echo "<th>".$link."</th>\n";
    }
    if ($doBlueStar) {
        echo "<th>Elite</th><th>Blue Star</th><th>Member Since</th><th>Accepted All</th><th>Accepted Num</th><th>Accepted Rate</th><th>Expired Rate</th><th>Cancelled Rate</th><th>Tracking Rate</th><th>Dealer Rating</th><th>Response Time</th>\n";
    }
    echo "          </tr>\n";
    echo "        </thead>\n";
    echo "        <tbody>\n";
    if (is_array($data) && (count($data) > 0)) {
        foreach ($data as $d) {
            $x++;
            echo "          <tr>\n";
            $url  = "/dealerProfile.php?dealerId=".$d["userid"];
            $title = "As of ".date('m/d/Y h:i:s', $d['modifydate']);
            $link = "<a href='".$url."' title='".$title."' target='_blank'>".$d['username']."</a>";
            if ($d['eliteuser'] == 'Y') {
                $link .= " <i class='fas fa-star'>";
            }
            if ($d['bluestaruser'] == 'Y') {
                $link .= " <i class='fas fa-star' style='color:#00f;'>";
            }
            echo "            <td>[".$x."]&nbsp;&nbsp;".$link."</td>\n";
            echo "            <td class='number'>".$d['revisedbyme']."</td>\n";
            echo "            <td class='number'>".$d['pendingtome']."</td>\n";
            echo "            <td class='number'>".$d['cancelledbyme']."</td>\n";
            echo "            <td class='number'>".$d['voidedoffers']."</td>\n";
            echo "            <td class='number'>".$d['acceptedoffers']."</td>\n";
            echo "            <td class='number'>".$d['declinedbyme']."</td>\n";
            echo "            <td class='number'>".$d['expiredbyme']."</td>\n";
            $title = $d['acceptedoffers']." + ".$d['declinedbyme']." + ".$d['expiredbyme'];
            echo "            <td class='number' title='".$title."'>".$d['adedenominator']."</td>\n";
            $title = $d['acceptedoffers']." / ".$d['adedenominator'];
            echo "            <td class='number' title='".$title."'>".$d['acceptedrate']."%</td>\n";
            $title = $d['declinedbyme']." / ".$d['adedenominator'];
            echo "            <td class='number' title='".$title."'>".$d['declinedrate']."%</td>\n";
            $title = $d['expiredbyme']." / ".$d['adedenominator'];
            echo "            <td class='number' title='".$title."'>".$d['expiredrate']."%</td>\n";
            $title = $d['trackedcount']." / ".$d['trackablecount'];
            echo "            <td class='number' title='".$title."'>".$d['trackrate']."%</td>\n";
            $title = $d['cancelledbyme']." / (".$d['cancelledbyme']." + ".$d['adedenominator'].")";
            echo "            <td class='number' title='".$title."'>".$d['cancelledrate']."%</td>\n";
            echo "            <td class='number'>".$d['avgresponse']."</td>\n";
            $title = $d['ratingtotal']." / ".$d['ratingcounts'];
            echo "            <td class='number' title='".$title."'>".$d['ratingavg']."</td>\n";
            $title = $d['rating5counts']." / ".$d['ratingcounts'];
            echo "            <td class='number' title='".$title."'>".$d['rating5rate']."</td>\n";
            $complaintLink = (empty($d['complaintcount'])) ? $d['complaintcount']
                : "<a href='offersadmincomplaint.php?dealerid=".$d['userid']."' target='_blank'>".$d['complaintcount']."</a>";
            echo "            <td class='number'>".$complaintLink."</td>\n";
            if ($doBlueStar) {
                echo "<td>".$d['eliteuser']."</td>\n";
                echo "<td>".$d['bluestaruser']."</td>\n";
                $statusBlueStar = displayBlueStarStatus(BLUESTAR_MEMBER_MONTHS, $d['bsmembership']);
                echo "<td title='".$d['accountcreated']."' ".$statusBlueStar.">".$d['bsmembership']."</td>\n";
                $statusBlueStar = displayBlueStarStatus(BLUESTAR_LIFETIME_ACCEPTED, $d['bsacceptedall']);
                echo "<td ".$statusBlueStar.">".$d['bsacceptedall']."</td>\n";
                $statusBlueStar = displayBlueStarStatus(BLUESTAR_ACCEPTED_NUM, $d['bsacceptednum']);
                echo "<td ".$statusBlueStar.">".$d['bsacceptednum']."</td>\n";
                $statusBlueStar = displayBlueStarStatus(BLUESTAR_ACCEPTED_RATE, $d['bsacceptedrate']);
                echo "<td ".$statusBlueStar.">".$d['bsacceptedrate']."</td>\n";
                $statusBlueStar = displayBlueStarStatus(BLUESTAR_EXPIRED_RATE, $d['bsexpiredrate']);
                echo "<td ".$statusBlueStar.">".$d['bsexpiredrate']."</td>\n";
                $statusBlueStar = displayBlueStarStatus(BLUESTAR_CANCELLED_RATE, $d['bscancelledrate']);
                echo "<td ".$statusBlueStar.">".$d['bscancelledrate']."</td>\n";
                $statusBlueStar = displayBlueStarStatus(BLUESTAR_TRACKING_RATE, $d['bstrackrate']);
                echo "<td ".$statusBlueStar.">".$d['bstrackrate']."</td>\n";
                $statusBlueStar = displayBlueStarStatus(BLUESTAR_RATING, $d['bsrating']);
                echo "<td ".$statusBlueStar.">".$d['bsrating']."</td>\n";
                $statusBlueStar = displayBlueStarStatus(BLUESTAR_RESPONSE, $d['bsresponse']);
                echo "<td ".$statusBlueStar.">".$d['bsresponse']."</td>\n";
            }
            echo "          </tr>\n";
        }
    }
    echo "        </tbody>\n";
    echo "        <tfoot>\n";
    echo "          <tr>\n";
    echo "            <td colspan='11'>\n";
    echo "              <div class='pagination'>\n";
    if ($totalRows) {
        $pager = new Paginator($perpage, "page");
        $pager->set_total($totalRows);
        echo "                <nav role='navigation' aria-label='Pagination Navigation' class='text-filter'>\n";
        echo $pager->post_page_links("searchform");
        echo "\n";
        echo "                </nav>\n";
    }
    echo "              </div>\n";
    echo "            </td>\n";
    echo "          </tr>\n";
    echo "        </tfoot>\n";
    echo "      </table>\n";
    echo "    </form>\n";
    echo "  </div>\n";
    echo "</article>\n";
    echo "<br />\n";
}

function displayBlueStarStatus($metricname, $metricvalue) {
    global $page, $metricInterval, $dealerMetrics;
    $display = "";
    
    if ($metricInterval == METRIC_INTERVAL_BLUESTAR) {
        if ($metricvalue == 'Y') {
            $display = "style='background-color: green;'";
        } else {
            $display = "style='background-color: red;'";
        }
    }
    return $display;
}

function metricIntervalDDM() {
    global $page, $dealerMetrics, $metricInterval;

    $output = ""; 
    $intervalNames = $dealerMetrics->getIntervalNames();

    if (is_array($intervalNames) && (count($intervalNames) > 1)) {
        $onChange = " onchange = \"document.searchform.submit();\"";
        $output .= getSelectDDM($intervalNames, "intervalid", "intervalid", "intervalname", NULL, $metricInterval, NULL, 0, NULL, NULL, $onChange);
    }
    
    return $output;
}

function dealerLevelDDM() {
    global $page, $dealerMetrics, $dealerLevelId;

    $output = ""; 
    
    $dealerLevelNames = array();
    $dealerLevelNames[] = array('dealerlevelid' => METRIC_DEALER_LEVEL_ELITE, 'dealerlevel' => 'Elite');
    $dealerLevelNames[] = array('dealerlevelid' => METRIC_DEALER_LEVEL_BLUESTAR, 'dealerlevel' => 'BlueStar');
    $dealerLevelNames[] = array('dealerlevelid' => METRIC_DEALER_LEVEL_NEITHER, 'dealerlevel' => 'Neither');
    $onChange = " onchange = \"document.searchform.submit();\"";
    $output .= getSelectDDM($dealerLevelNames, "dealerlevelid", "dealerlevelid", "dealerlevel", NULL, $dealerLevelId, "All", METRIC_DEALER_LEVEL_ALL, NULL, NULL, $onChange);
    
    return $output;
}

function reloadOfferCounts() {
    global $page, $dealerMetrics;
    
    $dealerMetrics->reloadMetrics();
}

function promoteBlueStarDealers() {
    global $page, $dealerMetrics;
    
    $dealerMetrics->promoteBlueStar();
}

function demoteBlueStarDealers() {
    global $page, $dealerMetrics;
    
    $dealerMetrics->demoteBlueStar();
}

function getOfferCounts($pagenum = 1, $perpage = NULL) {
    global $page, $dealerMetrics, $metricInterval, $dealerId, $dealerName, $dealerLevelId, $sortby, $totalRows, $totalPages;

    $metrics = $dealerMetrics->getMetrics($metricInterval, $dealerName, $dealerId, $dealerLevelId, $sortby, $pagenum, $perpage);
    $totalRows = $dealerMetrics->totalRows;
    if ($totalRows && $perpage) {
        $totalPages = floor($totalRows / $perpage)+1;
    } else {
        $totalPages = "";
    }
    return $metrics;
}

function export() {
    global $page;

    $data = getOfferCounts(1, 99999);
    $filename = "";
    if (!empty($data)) {
        $filename = date('Ymd_His')."_offerCounts.csv";
        $page->utility->export($data, $filename);
    }

    return $filename;
}
?>