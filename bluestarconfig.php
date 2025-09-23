<?php
require_once('templateAdmin.class.php');
require_once('metric.class.php');
$page = new templateAdmin(LOGIN, SHOWMSG);

$doSave = optional_param('save', NULL, PARAM_TEXT);

$dealerMetrics = new DealerMetrics();

if ($doSave) {
    scrapeBlueStarConfig();
    validateBlueStarConfig();
    if ($dealerMetrics->bsGoalsActive) {
        $dealerMetrics->saveBlueStarGoals();
    }
}

echo $page->header("Blue Star Config");
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $dealerMetrics;

    echo "<h3>Blue Star Configuration</h3>\n";

    echo "<article>\n";
    echo "  <div class='entry-content'>\n";
    echo "<form name='sub' id='sub' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post' enctype='multipart/form-data'>\n";
    echo "  <table>\n";
    echo "    <tr>\n";
    echo "      <th>Minimum Membership:</th>\n";
    echo "      <td style='white-space:nowrap;'><input class='number' type='text' name='minmembermonths' id='minmembermonths' size='10' value='".$dealerMetrics->bsMinMemberMonths."' /> months</td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <th>Minimum Lifetime Accepted:</th>\n";
    echo "      <td style='white-space:nowrap;'><input class='number' type='text' name='minlifeacceptednum' id='minlifeacceptednum' size='10' value='".$dealerMetrics->bsMinLifeAcceptedNum."' /> offers</td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <th>Minimum 6 Month Accepted Number:</th>\n";
    echo "      <td style='white-space:nowrap;'><input class='number' type='text' name='minacceptednum' id='minacceptednum' size='10' value='".$dealerMetrics->bsMinAcceptedNum."' /> offers</td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <th>Minimum 6 Month Accepted Rate:</th>\n";
    echo "      <td style='white-space:nowrap;'><input class='number' type='text' name='minacceptedrate' id='minacceptedrate' size='10' value='".$dealerMetrics->bsMinAcceptedRate."' /> %</td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <th>Maximum 6 Month Expired Rate:</th>\n";
    echo "      <td style='white-space:nowrap;'><input class='number' type='text' name='maxexpiredrate' id='maxexpiredrate' size='10' value='".$dealerMetrics->bsMaxExpiredRate."' /> %</td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <th>Maximum 6 Month Cancelled Rate:</th>\n";
    echo "      <td style='white-space:nowrap;'><input class='number' type='text' name='maxcancelledrate' id='maxcancelledrate' size='10' value='".$dealerMetrics->bsMaxCancelledRate."' /> %</td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <th>Minimum 6 Month Tracking Rate:</th>\n";
    echo "      <td style='white-space:nowrap;'><input class='number' type='text' name='mintrackingrate' id='mintrackingrate' size='10' value='".$dealerMetrics->bsMinTrackingRate."' /> %</td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <th>Minimum 6 Month Dealer Rating:</th>\n";
    echo "      <td style='white-space:nowrap;'><input class='number' type='text' name='mindealerrating' id='mindealerrating' size='10' value='".$dealerMetrics->bsMinDealerRating."' /> %</td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <th>Maximum 6 Month Response Time:</th>\n";
    echo "      <td style='white-space:nowrap;'><input class='number' type='text' name='maxresponsehours' id='maxresponsehours' size='10' value='".$dealerMetrics->bsMaxResponseHours."' /> hours</td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td colspan='2' align='center'>\n";
    echo "        <input type='submit' name='save' id='save' value='SAVE' />\n";
    echo "        <a href='bluestarconfig.php'>CANCEL</a>\n";
    echo "      </td>\n";
    echo "    </tr>\n";
    echo "  </table>\n";
    echo "</form>\n";

    echo "  </div>\n";
    echo "</article>\n";
}

function scrapeBlueStarConfig() {
    global $page, $dealerMetrics;

    $dealerMetrics->bsMinMemberMonths = optional_param('minmembermonths', 0 , PARAM_INT);
    $dealerMetrics->bsMinLifeAcceptedNum = optional_param('minlifeacceptednum', 0 , PARAM_INT);
    $dealerMetrics->bsMinAcceptedNum = optional_param('minacceptednum', 0 , PARAM_INT);
    $dealerMetrics->bsMinAcceptedRate = optional_param('minacceptedrate', 0 , PARAM_FLOAT);
    $dealerMetrics->bsMaxExpiredRate = optional_param('maxexpiredrate', 0 , PARAM_FLOAT);
    $dealerMetrics->bsMaxCancelledRate = optional_param('maxcancelledrate', 0 , PARAM_FLOAT);
    $dealerMetrics->bsMinTrackingRate = optional_param('mintrackingrate', 0 , PARAM_FLOAT);
    $dealerMetrics->bsMaxResponseHours = optional_param('maxresponsehours', 0 , PARAM_INT);
    $dealerMetrics->bsMinDealerRating = optional_param('mindealerrating', 0 , PARAM_FLOAT);
    $dealerMetrics->bsGoalsActive = true;
}

function validateBlueStarConfig() {
    global $page, $dealerMetrics;

    $success = true;

    if (($dealerMetrics->bsMinMemberMonths < 0) || ($dealerMetrics->bsMinMemberMonths > 100)) {
        $success = false;
        $page->messages->addErrorMsg("Invalid bsMinMemberMonths - updated cancelled.");
        $dealerMetrics->bsGoalsActive = false;
    }

    if (($dealerMetrics->bsMinLifeAcceptedNum < 0) || ($dealerMetrics->bsMinLifeAcceptedNum > 100)) {
        $success = false;
        $page->messages->addErrorMsg("Invalid bsMinLifeAcceptedNum - updated cancelled.");
        $dealerMetrics->bsGoalsActive = false;
    }

    if (($dealerMetrics->bsMinAcceptedNum < 0) || ($dealerMetrics->bsMinAcceptedNum > 100)) {
        $success = false;
        $page->messages->addErrorMsg("Invalid bsMinAcceptedNum - updated cancelled.");
        $dealerMetrics->bsGoalsActive = false;
    }

    if (($dealerMetrics->bsMinAcceptedRate < 0) || ($dealerMetrics->bsMinAcceptedRate > 100)) {
        $success = false;
        $page->messages->addErrorMsg("Invalid bsMinAcceptedRate - updated cancelled.");
        $dealerMetrics->bsGoalsActive = false;
    }

    if (($dealerMetrics->bsMaxExpiredRate < 0) || ($dealerMetrics->bsMaxExpiredRate > 100)) {
        $success = false;
        $page->messages->addErrorMsg("Invalid bsMaxExpiredRate - updated cancelled.");
        $dealerMetrics->bsGoalsActive = false;
    }

    if (($dealerMetrics->bsMaxCancelledRate < 0) || ($dealerMetrics->bsMaxCancelledRate > 100)) {
        $success = false;
        $page->messages->addErrorMsg("Invalid bsMaxCancelledRate - updated cancelled.");
        $dealerMetrics->bsGoalsActive = false;
    }

    if (($dealerMetrics->bsMinTrackingRate < 0) || ($dealerMetrics->bsMinTrackingRate > 100)) {
        $success = false;
        $page->messages->addErrorMsg("Invalid bsMinTrackingRate - updated cancelled.");
        $dealerMetrics->bsGoalsActive = false;
    }

    if (($dealerMetrics->bsMaxResponseHours < 0) || ($dealerMetrics->bsMaxResponseHours > 100)) {
        $success = false;
        $page->messages->addErrorMsg("Invalid bsMaxResponseHours - updated cancelled.");
        $dealerMetrics->bsGoalsActive = false;
    }

    if (($dealerMetrics->bsMinDealerRating < 0) || ($dealerMetrics->bsMinDealerRating > 100)) {
        $success = false;
        $page->messages->addErrorMsg("Invalid bsMinDealerRating - updated cancelled.");
        $dealerMetrics->bsGoalsActive = false;
    }

    return $success;
}
?>