<?php
include_once('setup.php');
require_once('template.class.php');
require_once('metric.class.php');

$page = new template(LOGIN, SHOWMSG, REDIRECTSAFE, NOVERIFYPAYMENTS);

$dealerId       = optional_param('dealerId', $page->user->userId, PARAM_INT);
$action         = optional_param('action', NULL, PARAM_TEXT);
$wantedTypes    = optional_param('wantedtypes', NULL, PARAM_INT);
$forSaleTypes   = optional_param('forsaletypes', NULL, PARAM_INT);

$isMyProfile = ($dealerId == $page->user->userId) ? true : false;
$dealerIsStaff = $page->user->dealerHasRightOrAdmin($dealerId, USERRIGHT_STAFF);

$authorId = $page->user->userId;

if (($action == 'edit') || ($action == 'save')) {
    if (! $isMyProfile) {
        $action = NULL;
    }
}

if ($action == 'rmlogo') {
    if ($page->user->isAdmin()) {
        if ($dealerId) {
            deleteDealerLogo($dealerId);
        }
    }
    $action = NULL;
}

$listingTypeCounts = getListingTypeCounts();

$preferredPaymentData = getDealerPreferredPaymentData();

$dealerInfo = getDealerInfo();
if ($dealerInfo) {
    $counterMinimumTotal = $dealerInfo['counterminimumdtotal'];
    $bankInfo = $dealerInfo['bankinfo'];
    $paypalId = $dealerInfo['paypalid'];
    $membershipFee = $dealerInfo['membershipfee'];
    $listingFee = $dealerInfo['listingfee'];
}

if ($page->user->isAdmin()) {
    if (($action == "payupdate") || ($action == "shipupdate")) {
        applyAddressRequest($dealerId, $action);
    }
}
if ($page->user->isAdmin() || $isMyProfile) {
    if (($action == "paydelete") || ($action == "shipdelete")) {
        applyAddressRequest($dealerId, $action);
    }
}

if ($action == 'save') {
    if (saveDealerProfile($listingTypeCounts)) {
        $page->messages->addSuccessMsg("Profile updated");
    } else {
        $action = 'edit';
    }
} else {
    checkPaymentMethods($listingTypeCounts);
}

$dealerTransactions = getDealerTransactions($dealerId);
//echo "Transactions:<br />\n<pre>";var_dump($dealerTransactions);echo "</pre><br />\n";

$dealerMetrics = new DealerMetrics();
/*
if ($page->user->isAdmin() || $isMyProfile) {
    $dealerMetrics->reloadMetrics($dealerId);
}
*/
$metricMatrix = $dealerMetrics->getDealerMetricsMatrix($dealerId);
$metricMatrixAsOf = $dealerMetrics->getDealerMetricsAsOf($dealerId);
$pageTitle = ($isMyProfile) ? 'My Profile' : 'Dealer Profile';

refreshVacationStatus($dealerId);

if ($isMyProfile || $page->user->isAdmin()) {
    $dealerDisputes = getDealerDisputes($dealerId);
    if ($dealerDisputes && is_array($dealerDisputes) && (count($dealerDisputes) > 0)) {
        foreach ($dealerDisputes as $dispute) {
            if ($isMyProfile) {
                $page->messages->addErrorMsg("You have an open dispute on offer <a href='offer.php?offerid=".$dispute['offerid']."' target='_blank'>#".$dispute['offerid']."</a>");
            } else {
                $page->messages->addErrorMsg("Dealer has an open dispute on offer <a href='offeradmin.php?offerid=".$dispute['offerid']."' target='_blank'>#".$dispute['offerid']."</a>");
            }
        }
    }
}

echo $page->header($pageTitle);
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $UTILITY, $dealerId, $dealerInfo, $dealerTransactions, $dealerMetrics, $metricMatrix;
    global $dealerIsStaff, $isMyProfile, $action, $preferredPaymentData, $bankInfo, $paypalId, $membershipFee, $listingFee, $counterMinimumTotal, $counterMinimumLeast, $counterMinimumMost;

    echo "<div class='page-header'>\n";

    $haveCommands = false;

    if ($isMyProfile) {
        if ($action == 'edit') {
            echo "<form name ='sub' action='dealerProfile.php' method='post'>\n";
            echo "  <input type='hidden'  id='action' name='action' value='save' />\n";
        } else {
            echo "  <a href='dealerProfile.php?action=edit#editinfo' class='button'>Edit My Payment Options</a>\n";
            echo "  <a href='updatePassword.php?userid=".$dealerId."' class='button'>Change Password</a>\n";
            echo "  <a href='dealerCreditInfo.php' class='button'>CC for Membership Fees</a>\n";
            echo "  <a href='onVacation.php' class='button'>Vacation Status</a><br /><br />\n";
            $haveCommands = true;
        }
    } else {
        if ($page->user->isAdmin()) {
            echo "  <a href='updatePassword.php?userid=".$dealerId."' class='button'>Change Password</a>\n";
            $haveCommands = true;
        }
    }

    if ($isMyProfile) {
        $url = "assignPreferences.php?dealerId=".$dealerId;
        echo "  <a href='".$url."' class='button'>Preferences</a>\n";
        $url = "notificationPreferences.php?dealerId=".$dealerId;
        echo "  <a href='".$url."' class='button'>Notifications</a>\n";
        $haveCommands = true;
    } elseif ($page->user->isAdmin()) {
        $url = "notificationPreferences.php?dealerId=".$dealerId;
        echo "  <a href='".$url."' class='button'>Notifications</a>\n";
        $haveCommands = true;
    }
    $dealername = $UTILITY->getDealersName($dealerId);

    if ($isMyProfile || (! $dealerIsStaff)) {
        $url = "marketsnapshot.php?dealer=".$dealername."&type=W&sortby=cat&hourssince=0";
        echo "  <a href='".$url."' class='button'>View My Buys</a>\n";
        $url = "marketsnapshot.php?dealer=".$dealername."&type=FS&sortby=cat&hourssince=0";
        echo "  <a href='".$url."' class='button'>View My Sells</a>\n";
        $haveCommands = true;
    }

    if ($haveCommands) {
        echo "<br /><br />\n";
    }

    if ($dealerInfo['listinglogo']) {
        $deleteImg = ($page->user->isAdmin()) ? "&nbsp;&nbsp;<a href='dealerProfile.php?dealerId=".$dealerId."&action=rmlogo' title='Delete Logo' onClick=\"return confirm('Are you sure you want to delete this logo?');\"><i class='fa-solid fa-trash'></i></a>" : "";
        echo "<img src='".$page->utility->getPrefixMemberImageURL($dealerInfo['listinglogo'])."' style='max-width:200px; max-height:200px;' />".$deleteImg;
        echo "<br /><br />\n";
    }

    echo "  ".$dealername;
    if ($dealerInfo['elitemember']) {
        echo " <span title='Elite Member' style='margin-left:10px; margin-right:10px;'><i class='fas fa-star'></i></span>Elite Member";
    }
    if ($dealerInfo['bluestarmember']) {
        echo " <span title='Above Standard Member' style='margin-left:10px; margin-right:10px;'><i class='fas fa-star' style='color:#00f;'></i></span>Above Standard Member";
    }
    if ($dealerInfo['verifiedmember']) {
        echo " <span title='Verified Member' style='margin-left:10px; margin-right:10px;'><i class='fas fa-check' style='color:#090;'></i></span>Verified Member";
    }
    echo "<br />\n";

    if ($page->user->isStaff()) {
        echo "User Class: ".$dealerInfo['userclassname']."<br />\n";
    }
    echo "DealernetX Member Since: ".date('m/d/Y', $dealerInfo['accountcreated'])."<br />\n";

    displayAdminActions($dealerId);

    displayDealerTransactions($dealerTransactions);

    echo "</div>\n";

    if ($isMyProfile || (! $dealerIsStaff)) {
        echo "<table class='outer-table'>\n";
        if ($isMyProfile) {
            echo "  <caption><a href='addressChange.php'>Request Address Change</a></caption>\n";
        }
        echo "  <tbody>\n";
        echo "    <tr>\n";
        echo "      <td class='double-table'>\n";
        echo "        <table>\n";
        echo "          <thead>\n";
        echo "            <tr>\n";
        $addressasof = $UTILITY->getAddressAsOf($dealerId, ADDRESS_TYPE_PAY);
        echo "              <th>Pay To Address ".$addressasof."</th>\n";
        echo "            </tr>\n";
        echo "          </thead>\n";
        echo "          <tbody>\n";
        if ($isMyProfile || $page->user->isAdmin()) {
            echo "            <tr>\n";
            echo "              <td class='address' data-label='Pay To Address ".$addressasof."'>";
            $UTILITY->formatAddress($dealerId, ADDRESS_TYPE_PAY, $isMyProfile, true);
            echo "              </td>\n";
            echo "            </tr>\n";
            if ($addr = $UTILITY->getAddress($dealerId, ADDRESS_TYPE_REQUEST_PAY, $isMyProfile, true)) {
                echo "            <tr><td><strong>Pending Pay To Address ".$UTILITY->getAddressAsOf($dealerId, ADDRESS_TYPE_REQUEST_PAY)."</strong>";
                if ($page->user->isAdmin()) {
                    echo " <a href='dealerProfile.php?dealerId=".$dealerId."&action=payupdate'><button>Apply</button></a>";
                }
                if ($page->user->isAdmin() || $isMyProfile) {
                    echo " <a href='dealerProfile.php?dealerId=".$dealerId."&action=paydelete'><button>Delete</button></a>";
                }
                echo "</td></tr>\n";
                echo "            <tr>\n";
                echo "              <td>";
                $UTILITY->displayAddress($addr, $dealerId, ADDRESS_TYPE_REQUEST_PAY, $isMyProfile, true);
                echo "              </td>\n";
                echo "            </tr>\n";
            }
        } else {
            echo "            <tr>\n";
            echo "              <td class='address' data-label='Pay To Address ".$addressasof."'>";
            profileFormatAddress($dealerId, ADDRESS_TYPE_PAY, $isMyProfile, true);
            echo "              </td>\n";
            echo "            </tr>\n";
        }
        echo "          </tbody>\n";
        echo "        </table>\n";
        echo "      </td>\n";

        echo "      <td class='double-table'>\n";
        echo "        <table>\n";
        echo "          <thead>\n";
        echo "            <tr>\n";
        $addressasof = $UTILITY->getAddressAsOf($dealerId, ADDRESS_TYPE_SHIP);
        echo "              <th>Ship To Address ".$addressasof."</th>\n";
        echo "            </tr>\n";
        echo "          </thead>\n";
        echo "          <tbody>\n";
        if ($isMyProfile || $page->user->isAdmin()) {
            echo "            <tr>\n";
            echo "              <td class='address' data-label='Ship To Address ".$addressasof."'>\n";
            $UTILITY->formatAddress($dealerId, ADDRESS_TYPE_SHIP, $isMyProfile, true);
            echo "              </td>\n";
            echo "            </tr>\n";
            if ($addr = $UTILITY->getAddress($dealerId, ADDRESS_TYPE_REQUEST_SHIP, $isMyProfile, true)) {
                echo "            <tr><td><strong>Pending Ship To Address ".$UTILITY->getAddressAsOf($dealerId, ADDRESS_TYPE_REQUEST_SHIP)."</strong>";
                if ($page->user->isAdmin()) {
                    echo " <a href='dealerProfile.php?dealerId=".$dealerId."&action=shipupdate'><button>Apply</button></a>";
                }
                if ($page->user->isAdmin() || $isMyProfile) {
                    echo " <a href='dealerProfile.php?dealerId=".$dealerId."&action=shipdelete'><button>Delete</button></a>";
                }
                echo "</td></tr>\n";
                echo "            <tr>\n";
                echo "              <td>";
                $UTILITY->displayAddress($addr, $dealerId, ADDRESS_TYPE_REQUEST_SHIP, $isMyProfile, true);
                echo "              </td>\n";
                echo "            </tr>\n";
            }
        } else {
            echo "            <tr>\n";
            echo "              <td class='address' data-label='Ship To Address ".$addressasof."'>\n";
            profileFormatAddress($dealerId, ADDRESS_TYPE_SHIP, $isMyProfile, true);
            echo "              </td>\n";
            echo "            </tr>\n";
        }
        echo "          </tbody>\n";
        echo "        </table>\n";
        echo "      </td>\n";
        echo "    </tr>\n";
        echo "  </tbody>\n";
        echo "</table>\n";

        if ($dealerInfo) {
            if (!empty($dealerInfo['accountnote'])) {
                echo "        <table>\n";
                echo "          <thead>\n";
                echo "            <tr>\n";
                echo "              <th>Account Note</th>\n";
                echo "            </tr>\n";
                echo "          </thead>\n";
                echo "          <tbody>\n";
                echo "            <tr>\n";
                echo "              <td>".$page->utility->htmlFriendlyString($dealerInfo['accountnote'])."</td>\n";
                echo "            </tr>\n";
                echo "          </tbody>\n";
                echo "        </table>\n";
            }

            if ($isMyProfile || $page->user->isStaff()) {

                echo "        <table id='editinfo' name='editinfo'>\n";
                echo "          <thead>\n";
                echo "            <tr>\n";
                echo "              <th>Bank Info</th>\n";
                echo "              <th>EFT Withdraw Paypal ID</th>\n";
                if ($page->user->isStaff()) {
                    echo "              <th>Membership Fee</th>\n";
                    echo "              <th>Listing Fee</th>\n";
                }
                echo "            </tr>\n";
                echo "          </thead>\n";
                echo "          <tbody>\n";
                echo "            <tr>\n";
                echo "              <td data-label='Bank Info'>".$bankInfo."</td>\n";
                echo "              <td data-label='EFT Withdraw Paypal ID'>".$paypalId."</td>\n";
                if ($page->user->isStaff()) {
                    echo "              <td data-label='Membership Fee' class='number'>$".$membershipFee."</td>\n";
                    //echo "              <td align=right>".number_format(($listingFee * 100.00), 2)." %</td>\n";
                    echo "              <td data-label='Listing Fee' class='number'>".($listingFee*100)." %</td>\n";
                }
                echo "            </tr>\n";
                echo "          </tbody>\n";
                echo "        </table>\n";
            }

            if ($isMyProfile || $page->user->isStaff()) {
                if (isset($dealerInfo['counterminimumdtotal'])) {
                    if (($isMyProfile) && ($action == 'edit')) {
                        $counterMinimumTotalStr = "<input type='text' id='counterminimumdtotal' name='counterminimumdtotal' size='8' style='text-align:right;' value='".$counterMinimumTotal."' />";
                    } else {
                        $counterMinimumTotalStr = $counterMinimumTotal;
                    }
                    echo "        <table>\n";
                    echo "          <thead>\n";
                    echo "            <tr>\n";
                    echo "              <th>Counter Offer</th>\n";
                    echo "            </tr>\n";
                    echo "          </thead>\n";
                    echo "          <tbody>\n";
                    echo "            <tr>\n";
                    echo "              <td class='address' data-label='Counter Offer'>Minimum Order Total For Counter Offers: $".$counterMinimumTotalStr." ($".$counterMinimumLeast." to $".$counterMinimumMost.")</td>\n";
                    echo "            </tr>\n";
                    echo "          </tbody>\n";
                    echo "        </table>\n";
                }
            }
        }

        $preferredCaption = "";
        if (($isMyProfile) && ($action == 'edit')) {
            $preferredCaption = "<caption>* indicates additional info is required if option is selected</caption>\n";
            $preferredPayment = getEditDealerPreferredPayment();
        } else {
            $preferredPayment = getDealerPreferredPayment();
        }
        echo "<table class='outer-table'>\n";
        echo $preferredCaption;
        echo "  <tbody>\n";
        echo "    <tr>\n";
        echo "      <td class='double-table'>\n";
        echo "        <table>\n";
        echo "          <thead>\n";
        echo "            <tr>\n";
        echo "              <th>Buying Payment Options</th>\n";
        echo "            </tr>\n";
        echo "          </thead>\n";
        echo "          <tbody>\n";
        echo "            <tr>\n";
        echo "              <td class='address' data-label='Buying Payment Options'>\n";
        echo "               ".$preferredPayment['Wanted']."\n";
        echo "              </td>\n";
        echo "            </tr>\n";
        echo "          </tbody>\n";
        echo "        </table>\n";
        echo "      </td>\n";

        echo "      <td class='double-table'>\n";
        echo "        <table>\n";
        echo "          <thead>\n";
        echo "            <tr>\n";
        echo "              <th>Selling Payment Options</th>\n";
        echo "            </tr>\n";
        echo "          </thead>\n";
        echo "          <tbody>\n";
        echo "            <tr>\n";
        echo "              <td class='address' data-label='Selling Payment Options'>\n";
        echo "               ".$preferredPayment['For Sale']."\n";
        echo "              </td>\n";
        echo "            </tr>\n";
        echo "          </tbody>\n";
        echo "        </table>\n";
        echo "      </td>\n";
        echo "    </tr>\n";
        echo "  </tbody>\n";
        echo "</table>\n";
}

    if (! $isMyProfile) {
        echo "<table >\n";
        echo "  <thead>\n";
        echo "    <tr>\n";
        echo "      <th>My Dealer Notes (only visible to you)</th>\n";
        echo "    </tr>\n";
        echo "  </thead>\n";
        echo "  <tbody>\n";
        echo "    <tr>\n";
        echo "      <td class='address' data-label='My Dealer Notes (only visible to you)'>\n";
        echo "        <ul>\n";
        $dNotes = getDealerNotes();
        if (isset($dNotes)) {
            foreach ($dNotes as $dN) {
                echo "          <li>".$dN['dealernote']."</li>\n";
            }
        } else {
            echo "          <li>No notes</li>\n";

        }
        echo "        </ul>\n";
        echo "      </td>\n";
        echo "    </tr>\n";
        echo "  </tbody>\n";
        echo "</table>\n";

        echo "<a class='button' href='dealerNotes.php?dealerId=".$dealerId."'>Edit Dealer Notes</a>\n";
    }
    if ($action == 'edit') {
        echo "<input class='button' type='submit' name='savebtn' id='savebtn' value='Save'>\n";
        echo "<a class='button' href='dealerProfile.php'>Cancel</a>\n";
        echo "</form>\n";
    }
}

function displayMetricInterval($intervalId) {
    global $page, $isMyProfile;

    $showIt = false;

    switch ($intervalId) {
        CASE METRIC_INTERVAL_LIFETIME:
        CASE METRIC_INTERVAL_BLUESTAR:
            $showIt = false;
            break;
        CASE METRIC_INTERVAL_30DAY:
        CASE METRIC_INTERVAL_6MONTH:
        CASE METRIC_INTERVAL_1YEAR:
            $showIt = true;
            break;
        default:
            if ($page->user->isStaff() && (!$isMyProfile)) {
                $showIt = true;
            }
            break;
    }

    return $showIt;
}

function displayDealerTransactions($dealerTransactions) {
    global $page, $dealerIsStaff, $isMyProfile, $dealerMetrics, $metricMatrix, $metricMatrixAsOf;

    if ($isMyProfile || (! $dealerIsStaff)) {
        if ($dealerMetrics->lifetimeAccepted) {
            echo "<br />Lifetime Accepted Transactions: ".$dealerMetrics->lifetimeAccepted."<br />\n";
        } else {
            echo "<br />No Lifetime Accepted Transactions<br />\n";
        }

        if (is_array($metricMatrix)) {
            $matrixAsOf = ($metricMatrixAsOf) ? "As of ".date('m/d/Y h:i:s') : "&nbsp;";
            $intervalHeader =  "    <tr><th>&nbsp;</th>";
            $intervalColumns = 1;
            foreach ($dealerMetrics->intervals as $intervalId => $intervalInfo) {
                if (displayMetricInterval($intervalId)) {
                    $intervalHeader .= "<th>".$intervalInfo['name']."</th>";
                    $intervalColumns++;
                }
            }
            $intervalHeader .= "</tr>\n";

            echo "<table style=' margin:0px auto; width: auto;'>\n";
            echo "  <theader>\n";
            echo "    <tr><th colspan='".$intervalColumns."'>Dealer Metrics ".$matrixAsOf."</th></tr>\n";
            echo $intervalHeader;
            echo "  </theader>\n";
            echo "  <tbody>\n";
            foreach ($metricMatrix as $metricname => $metric) {
                echo "  <tr>\n";
                echo "    <th>".$dealerMetrics->getProfileColumnTitle($metricname)."</th>";
                $tdClass = $dealerMetrics->styleProfileColumnData($metricname);
                $tdString = ($tdClass) ? "<td class='".$tdClass."'>" : "<td>";
                foreach ($metric as $intervalId => $intervalValue) {
                    if (displayMetricInterval($intervalId)) {
                        echo $tdString.$dealerMetrics->formatProfileColumnData($intervalValue, $metricname)."</td>";
                    }
                }
                echo "  </tr>\n";
            }
            echo "  </tbody>\n";
            echo "</table>\n";
        }
    }
}
function displayAdminActions($dealerId) {
    global $page;

    if ($page->user->isAdmin()) {
        echo "<div>\n";
        echo "  <a class='fas fa-edit' title='Edit' href='userUpdate.php?userId=".$dealerId."'></a>\n";
        echo "  <a class='fas fa-credit-card' title='EFT Credit' href='EFTone.php?userId=".$dealerId."'></a>\n";
        echo "  <a class='fas fa-user-cog' title='Rights' href='assignUserRights.php?userId=".$dealerId."'></a>\n";
        echo "  <a class='fas fa-mask' title='Proxy' href='inProxy.php?proxiedId=".$dealerId."'></a>\n";
        echo "</div><br />\n";

    }
}

function dealerRating($dealerId, $authorId) {
    global $page;

    $sql = "
        SELECT dealerrating AS rating
          FROM dealerratings
         WHERE rateddealerid = ".$dealerId."
            AND rategivenid = ".$authorId."
    ";
    $data = $page->db->get_field_query($sql);

    return $data;
}


function getDealerInfo() {
    global $page, $dealerId;
    $row = null;

    $sql = "SELECT ui.userclassid, ucl.userclassname
            , ui.accountnote, ui.dcreditline, ui.bankinfo, ui.paypalid
            , ui.counterminimumdtotal, ui.membershipfee, ui.listingfee
            , ui.listinglogo, ui.accountcreated
            , CASE WHEN ar.userid IS NULL THEN 0 ELSE 1 END AS elitemember
            , CASE WHEN bar.userid IS NULL THEN 0 ELSE 1 END AS bluestarmember
            , CASE WHEN vuar.userid IS NULL THEN 0 ELSE 1 END AS verifiedmember
        FROM userinfo ui
        JOIN userclass ucl ON ucl.userclassid=ui.userclassid
        LEFT JOIN assignedrights ar ON ar.userid=ui.userid AND ar.userrightid=".USERRIGHT_ELITE."
        LEFT JOIN assignedrights bar ON bar.userid=ui.userid AND bar.userrightid=".USERRIGHT_BLUESTAR."
        LEFT JOIN assignedrights vuar ON vuar.userid=ui.userid AND vuar.userrightid=".USERRIGHT_VERIFIED."
        WHERE ui.userid = ".$dealerId;
    if ($data = $page->db->sql_query($sql)) {
        $row = reset($data);
    }

    return $row;
}
function getDealerDisputes($dealerId) {
    global $page;

    $dealerDisputes = null;

    $sql = "SELECT o.offerid
            FROM offers o
            WHERE (o.offerfrom=".$dealerId." AND o.disputefromopened IS NOT NULL AND o.disputefromclosed IS NULL)
               OR (o.offerto=".$dealerId." AND o.disputetoopened IS NOT NULL AND o.disputetoclosed IS NULL)";
    $dealerDisputes = $page->db->sql_query($sql);

    return($dealerDisputes);
}


function getDealerTransactions($dealerId) {
    global $page, $dealerIsStaff, $isMyProfile;

    $dealerTransactions = array();

    if ($isMyProfile || (! $dealerIsStaff)) {
        $sql = "SELECT count(*) AS numtrans FROM offers WHERE (offerfrom=".$dealerId." OR offerto=".$dealerId.") AND (offerstatus='ACCEPTED' OR offerstatus='ARCHIVED')";
        $dealerTransactions['totalaccepted'] = $page->db->get_field_query($sql);
        $sql = "SELECT accepted FROM usercounts WHERE userid=".$dealerId;
        $dealerTransactions['acceptedcounts'] = $page->db->get_field_query($sql);

        $lastNinety = strtotime('-90 days');
        $sql = "SELECT count(*) AS numtrans FROM offers WHERE (offerfrom=".$dealerId." OR offerto=".$dealerId.") AND (offerstatus='ACCEPTED' OR offerstatus='ARCHIVED') AND createdate > ".$lastNinety;
        $dealerTransactions['ninetyaccepted'] = $page->db->get_field_query($sql);

        $lastThirty = strtotime('-30 days');
        $sql = "SELECT count(*) AS numtrans FROM offers WHERE (offerfrom=".$dealerId." OR offerto=".$dealerId.") AND (offerstatus='ACCEPTED' OR offerstatus='ARCHIVED') AND createdate > ".$lastThirty;
        $dealerTransactions['thirtyaccepted'] = $page->db->get_field_query($sql);

        $lastSix = strtotime('-6 months');
        $sql = "SELECT count(*) AS numtrans FROM offers WHERE (offerfrom=".$dealerId." OR offerto=".$dealerId.") AND (offerstatus='ACCEPTED' OR offerstatus='ARCHIVED') AND createdate > ".$lastSix;
        $dealerTransactions['sixaccepted'] = $page->db->get_field_query($sql);

        $sql = "SELECT count(*) AS numtrans FROM offers WHERE (offerfrom=".$dealerId." OR offerto=".$dealerId.") AND offeredby<>".$dealerId." AND offerstatus='EXPIRED' AND createdate > ".$lastSix;
        $dealerTransactions['sixexpired'] = $page->db->get_field_query($sql);

        $sql = "SELECT count(*) AS numtrans FROM offers WHERE (offerfrom=".$dealerId." OR offerto=".$dealerId.") AND countered=0 AND offeredby<>".$dealerId." AND (offerstatus='EXPIRED' OR offerstatus='DECLINED') AND createdate > ".$lastSix;
        $dealerTransactions['sixnotaccepted'] = $page->db->get_field_query($sql);

        $dealerTransactions['sixcomplete'] = $dealerTransactions['sixnotaccepted'] + $dealerTransactions['sixaccepted'];

        if ($dealerTransactions['sixcomplete'] > 0) {
            $dealerTransactions['sixacceptpct'] = round(($dealerTransactions['sixaccepted'] / $dealerTransactions['sixcomplete']) * 100.00, 2);
            $dealerTransactions['sixexpirepct'] = round(($dealerTransactions['sixexpired'] / $dealerTransactions['sixcomplete']) * 100.00, 2);
        } else {
            $dealerTransactions['sixacceptpct'] = 0;
            $dealerTransactions['sixexpirepct'] = 0;
        }
        $sql = "SELECT date_trunc('seconds', (avgrt || ' second')::interval) AS avgresponse
            FROM (
                SELECT count(responsetime) as numrt, sum(responsetime) as totrt, avg(responsetime) as avgrt
                FROM (
                    SELECT CASE
                            WHEN (o.offerstatus='ACCEPTED' OR o.offerstatus='ARCHIVED')
                                THEN o.acceptedon - o.createdate
                            WHEN o.offerstatus='REVISED'
                                THEN o.modifydate-o.createdate
                            WHEN o.offerstatus='EXPIRED'
                                THEN o.modifydate-o.createdate
                            WHEN o.offerstatus='DECLINED'
                                THEN o.modifydate-o.createdate
                            ELSE NULL END AS responsetime
                    FROM offers o
                    WHERE (o.offerto=".$dealerId." or o.offerfrom=".$dealerId.")
                      AND o.offeredby<>".$dealerId."
                      AND o.createdate > ".$lastSix."
                ) rts
            ) tots";
        $dealerTransactions['sixresponse'] = $page->db->get_field_query($sql);

        $dealerTransactions['ratingtotal'] = 0;
        $dealerTransactions['ratingcount'] = 0;
        $dealerTransactions['ratingavg'] = null;
        $sql = "SELECT sum(ratings.rating) as ratingtotal, sum(ratings.transcount) as ratingcount
                FROM (
                    SELECT o.offerid, o.satisfiedbuy as rating, 1 as transcount
                    FROM offers o
                    WHERE o.offerfrom = ".$dealerId."
                      AND o.transactiontype = 'Wanted'
                      AND o.satisfiedbuy > 0
                    UNION ALL
                    SELECT o.offerid, o.satisfiedsell as rating, 1 as transcount
                    FROM offers o
                    WHERE o.offerfrom = ".$dealerId."
                      AND o.transactiontype = 'For Sale'
                      AND o.satisfiedsell > 0
                    UNION ALL
                    SELECT o.offerid, o.satisfiedbuy as rating, 1 as transcount
                    FROM offers o
                    WHERE o.offerto = ".$dealerId."
                      AND o.transactiontype = 'For Sale'
                      AND o.satisfiedbuy > 0
                    UNION ALL
                    SELECT o.offerid, o.satisfiedsell as rating, 1 as transcount
                    FROM offers o
                    WHERE o.offerto = ".$dealerId."
                      AND o.transactiontype = 'Wanted'
                      AND o.satisfiedsell > 0
                ) ratings";
        //echo "<pre>\n".$sql."</pre><br />\n";
        if ($rated = $page->db->sql_query($sql)) {
            $ratings = reset($rated);
            if ($ratings['ratingcount'] > 0) {
                $dealerTransactions['ratingtotal'] = $ratings['ratingtotal'];
                $dealerTransactions['ratingcount'] = $ratings['ratingcount'];
                $dealerTransactions['ratingavg'] = round($ratings['ratingtotal']/$ratings['ratingcount'], 2);
            }
        }
    }

    return $dealerTransactions;
}

function getDealerSignatures() {
    global $page, $dealerId;

    $sql = "
        SELECT internalsig, externalsig
          FROM userinfo
         WHERE userid = ".$dealerId."
    ";
     $data = $page->db->sql_query($sql);

      return $data;
}

function getDealerPreferredPayment() {
    global $page, $dealerId, $preferredPaymentData, $isMyProfile;

    $preferredPayment = array();
    $preferredPayment['Wanted'] = "";
    $preferredPayment['For Sale'] = "";

    if (is_array($preferredPaymentData['Wanted']) ) {
        $separator = "";
        foreach ($preferredPaymentData['Wanted'] as $ptid => $paymenttype) {
            if ($paymenttype['checked']) {
                $preferredPayment['Wanted'] .= $separator.$paymenttype['paymenttypename'];
                if ($isMyProfile) {
                    if ($paymenttype['extrainfo']) {
                        $preferredPayment['Wanted'] .= ' - '.$paymenttype['extrainfo'];
                    }
                }
                $separator = "<br />\n";
            }
        }
    }

    if (is_array($preferredPaymentData['For Sale']) ) {
        $separator = "";
        foreach ($preferredPaymentData['For Sale'] as $ptid => $paymenttype) {
            if ($paymenttype['checked']) {
                $preferredPayment['For Sale'] .= $separator.$paymenttype['paymenttypename'];
                if ($isMyProfile) {
                    if ($paymenttype['extrainfo']) {
                        $preferredPayment['For Sale'] .= ' - '.$paymenttype['extrainfo'];
                    }
                }
                $separator = "<br />\n";
            }
        }
    }

    return $preferredPayment;
}

function getEditDealerPreferredPayment() {
    global $page, $dealerId, $preferredPaymentData;

    $isElite = $page->db->get_field_query("SELECT 1 FROM assignedrights WHERE userid=".$page->user->userId." AND userrightid=".USERRIGHT_ELITE);

    $preferredPayment = array();
    $preferredPayment['Wanted'] = "";
    $preferredPayment['For Sale'] = "";

    $preferredPayment['Wanted'] .= "<table>\n";
    foreach ($preferredPaymentData['Wanted'] as $ptid => $paymenttype) {
        $disabledElite = (($ptid == 1) && $isElite) ? $disabledElite = " disabled " : "";
        $preferredPayment['Wanted'] .= "<tr>";
        $preferredPayment['Wanted'] .= "<td><input type=checkbox name='wantedtypes[]' ".$disabledElite." value='".$ptid."' ".$paymenttype['checked']." /> ".$paymenttype['paymenttypename']."</td>";
        $preferredPayment['Wanted'] .= "<td>";
        $required = "";
        switch ($paymenttype['allowinfo']) {
            case 'Yes':
                $required = "*";
            case 'Optional':
                $preferredPayment['Wanted'] .= "<input type'text' style='width:50ch;' id='wantinfo".$ptid."'  name='wantinfo".$ptid."' value='".$paymenttype['extrainfo']."' />".$required;
                break;
            default:
                $preferredPayment['Wanted'] .= "&nbsp;";
        }
        $preferredPayment['Wanted'] .= "</td>";
        $preferredPayment['Wanted'] .= "</tr>";

    }
    $preferredPayment['Wanted'] .= "</table>\n";

    $preferredPayment['For Sale'] .= "<table>\n";
    foreach ($preferredPaymentData['For Sale'] as $ptid => $paymenttype) {
        $disabledElite = (($ptid == 1) && $isElite) ? $disabledElite = " disabled " : "";
        $preferredPayment['For Sale'] .= "<tr>";
        $preferredPayment['For Sale'] .= "<td><input type=checkbox name='forsaletypes[]' ".$disabledElite." value='".$ptid."' ".$paymenttype['checked']." /> ".$paymenttype['paymenttypename']."</td>";
        $preferredPayment['For Sale'] .= "<td>";
        $required = "";
        switch ($paymenttype['allowinfo']) {
            case 'Yes':
                $required = "*";
            case 'Optional':
                $preferredPayment['For Sale'] .= "<input type'text' style='width:50ch;' id='saleinfo".$ptid."'  name='saleinfo".$ptid."' value='".$paymenttype['extrainfo']."' />".$required;
                break;
            default:
                $preferredPayment['For Sale'] .= "&nbsp;";
        }
        $preferredPayment['For Sale'] .= "</td>";
        $preferredPayment['For Sale'] .= "</tr>";

    }
    $preferredPayment['For Sale'] .= "</table>\n";

    return $preferredPayment;
}

function getDealerPreferredPaymentData() {
    global $page, $dealerId;

    $preferredPayment = array();
    $preferredPayment['Wanted'] = array();
    $preferredPayment['For Sale'] = array();

    $sql = "SELECT pt.paymenttypeid, pt.paymenttypename, pt.allowinfo
                    ,pp.preferredpaymentid, pp.extrainfo
                FROM paymenttypes pt
                    LEFT JOIN preferredpayment pp ON pp.paymenttypeid=pt.paymenttypeid
                        AND pp.userid=".$dealerId."
                        AND pp.transactiontype='Wanted'
                WHERE pt.active=1
                ORDER BY pt.paymenttypename";

    if ($wanted = $page->db->sql_query($sql)) {
        foreach ($wanted as $wantOne) {
            $preferredPayment['Wanted'][$wantOne['paymenttypeid']] = array();
            $preferredPayment['Wanted'][$wantOne['paymenttypeid']]['checked'] = (isset($wantOne['preferredpaymentid'])) ? "checked" : null;
            $preferredPayment['Wanted'][$wantOne['paymenttypeid']]['paymenttypename'] = $wantOne['paymenttypename'];
            $preferredPayment['Wanted'][$wantOne['paymenttypeid']]['allowinfo'] = $wantOne['allowinfo'];
            $preferredPayment['Wanted'][$wantOne['paymenttypeid']]['preferredpaymentid'] = $wantOne['preferredpaymentid'];
            $preferredPayment['Wanted'][$wantOne['paymenttypeid']]['extrainfo'] = $wantOne['extrainfo'];
        }
    }

    $sql = "SELECT pt.paymenttypeid, pt.paymenttypename, pt.allowinfo
                    ,pp.preferredpaymentid, pp.extrainfo
                FROM paymenttypes pt
                    LEFT JOIN preferredpayment pp ON pp.paymenttypeid=pt.paymenttypeid
                        AND pp.userid=".$dealerId."
                        AND pp.transactiontype='For Sale'
                WHERE pt.active=1
                ORDER BY pt.paymenttypename";

    if ($forSale = $page->db->sql_query($sql)) {
        foreach ($forSale as $forSaleOne) {
            $preferredPayment['For Sale'][$forSaleOne['paymenttypeid']] = array();
            $preferredPayment['For Sale'][$forSaleOne['paymenttypeid']]['checked'] = (isset($forSaleOne['preferredpaymentid'])) ? "checked" : null;
            $preferredPayment['For Sale'][$forSaleOne['paymenttypeid']]['paymenttypename'] = $forSaleOne['paymenttypename'];
            $preferredPayment['For Sale'][$forSaleOne['paymenttypeid']]['allowinfo'] = $forSaleOne['allowinfo'];
            $preferredPayment['For Sale'][$forSaleOne['paymenttypeid']]['preferredpaymentid'] = $forSaleOne['preferredpaymentid'];
            $preferredPayment['For Sale'][$forSaleOne['paymenttypeid']]['extrainfo'] = $forSaleOne['extrainfo'];
        }
    }

    return $preferredPayment;
}


function getDealerNotes() {
    global $page, $dealerId;

    $params = array();
    $params['dealerid'] = $dealerId;
    $params['authorid'] = $page->user->userId;
    $sql = "
        SELECT dealernote, dealernoteid
          FROM dealernotes
         WHERE dealerid = :dealerid
           AND authorid = :authorid
         ORDER BY createdate
     ";
    $data = $page->db->sql_query_params($sql, $params);

     return $data;
}

function getListingTypeCounts() {
    global $page;

    $listingCounts = array();
    $listingCounts['Wanted'] = 0;
    $listingCounts['For Sale'] = 0;

    $success = true;

    $sql = "SELECT l.type, count(*) as numlistings
            FROM listings l
            WHERE l.userid=".$page->user->userId."
              AND l.status='OPEN'
            GROUP BY l.type";
    if ($transactiontypes = $page->db->sql_query($sql)) {
        foreach ($transactiontypes as $transactiontype) {
            $listingCounts[$transactiontype['type']] = $transactiontype['numlistings'];
        }
    }

    return $listingCounts;
}

function checkPaymentMethods($listingCounts) {
    global $page;

    $success = true;

    foreach ($listingCounts as $transactiontype => $numlistings) {
        if ($numlistings > 0) {
            $sql = "SELECT count(pp.transactiontype) as numconfigged
                    FROM preferredpayment pp
                    JOIN paymenttypes pt on pt.paymenttypeid=pp.paymenttypeid AND pt.active=1
                    WHERE pp.userid=".$page->user->userId."
                      AND pp.transactiontype='".$transactiontype."'";
            $configged = $page->db->get_field_query($sql);
            if (!($configged > 0)) {
                $success = false;
                $page->messages->addErrorMsg("You must configure at least one Preferred Payment ".$transactiontype." or your existing listings will not display");
            }
        }
    }

    if ($success) {
        $sql = "SELECT pp.preferredpaymentid, pp.transactiontype, pt.paymenttypename
                FROM preferredpayment pp
                JOIN paymenttypes pt ON pt.paymenttypeid=pp.paymenttypeid AND pt.active=1
                WHERE pp.userid=".$page->user->userId."
                  AND pt.allowinfo='Yes'
                  AND pp.extrainfo  IS NULL";
        $missrequireds = $page->db->sql_query($sql);
        if ($missrequireds ) {
            $success = false;
            foreach($missrequireds as $missrequired) {
                $page->messages->addErrorMsg("Preferred Payment Type ".$missrequired['paymenttypename']." now requires additional information and your ".$missrequired['transactiontype']." info is empty. Edit you Payment Options to fix it.");
            }
        }
    }

    return $success;
}
function saveDealerProfile($listingCounts) {
    global $page, $preferredPaymentData, $wantedTypes, $forSaleTypes, $bankInfo, $paypalId, $counterMinimumTotal, $counterMinimumLeast, $counterMinimumMost;

    $isValid = true;
    $success = false;
    $hasWanted = false;
    $hasForSale = false;
    $insertValues = array();

    //$bankInfo = optional_param('bankinfo', NULL, PARAM_TEXT);

    $counterMinimumTotal = optional_param('counterminimumdtotal', NULL, PARAM_TEXT);
    if ((! isset($counterMinimumTotal)) || (! is_numeric($counterMinimumTotal))) {
        $page->messages->addErrorMsg("Minimum Order Total For Counter Offers must be a numeric value between $".$counterMinimumLeast." and $".$counterMinimumMost);
        $isValid = false;
    } else {
        if (($counterMinimumTotal < $counterMinimumLeast) || ($counterMinimumTotal > $counterMinimumMost)) {
            $page->messages->addErrorMsg("Minimum Order Total For Counter Offers must be a numeric value between $".$counterMinimumLeast." and $".$counterMinimumMost);
            $isValid = false;
        }
    }

    $isElite = $page->db->get_field_query("SELECT 1 FROM assignedrights WHERE userid=".$page->user->userId." AND userrightid=".USERRIGHT_ELITE);

    foreach($preferredPaymentData['Wanted'] as $ptid => $paymenttype) {
        $preferredPaymentData['Wanted'][$ptid]['extrainfo'] = optional_param('wantinfo'.$ptid, NULL, PARAM_TEXT);
        if (is_array($wantedTypes) && in_array($ptid, $wantedTypes)) {
            $preferredPaymentData['Wanted'][$ptid]['checked'] = "checked";
            if ($preferredPaymentData['Wanted'][$ptid]['allowinfo'] == 'Yes') {
                if (empty($preferredPaymentData['Wanted'][$ptid]['extrainfo'])) {
                    $page->messages->addErrorMsg("Additional info required for Wanted payment method ".$paymenttype['paymenttypename']);
                    $isValid = false;
                }
            }
            $hasWanted = true;
            $extrainfo = (empty($preferredPaymentData['Wanted'][$ptid]['extrainfo'])) ? 'null' : "'".$preferredPaymentData['Wanted'][$ptid]['extrainfo']."'";
            $insertValues[] = "(".$page->user->userId.",".$ptid.","."'Wanted',".$extrainfo.",'".$page->user->username."','".$page->user->username."')";
        } else {
            if ($isElite && $ptid == 1) {
                $preferredPaymentData['Wanted'][$ptid]['checked'] = "checked";
                $insertValues[] = "(".$page->user->userId.",".$ptid.","."'Wanted',null,'".$page->user->username."','".$page->user->username."')";
            } else {
                $preferredPaymentData['Wanted'][$ptid]['checked'] = null;
            }
        }
    }

    foreach($preferredPaymentData['For Sale'] as $ptid => $paymenttype) {
        $preferredPaymentData['For Sale'][$ptid]['extrainfo'] = optional_param('saleinfo'.$ptid, NULL, PARAM_TEXT);
        if (is_array($forSaleTypes) && in_array($ptid, $forSaleTypes)) {
            $preferredPaymentData['For Sale'][$ptid]['checked'] = "checked";
            if ($preferredPaymentData['For Sale'][$ptid]['allowinfo'] == 'Yes') {
                if (empty($preferredPaymentData['For Sale'][$ptid]['extrainfo'])) {
                    $page->messages->addErrorMsg("Additional info required for For Sale payment method ".$paymenttype['paymenttypename']);
                    $isValid = false;
                }
            }
            $hasForSale = true;
            $extrainfo = (empty($preferredPaymentData['For Sale'][$ptid]['extrainfo'])) ? 'null' : "'".$preferredPaymentData['For Sale'][$ptid]['extrainfo']."'";
            $insertValues[] = "(".$page->user->userId.",".$ptid.","."'For Sale',".$extrainfo.",'".$page->user->username."','".$page->user->username."')";
        } else {
            if ($isElite && $ptid == 1) {
                $preferredPaymentData['For Sale'][$ptid]['checked'] = "checked";
                $insertValues[] = "(".$page->user->userId.",".$ptid.","."'For Sale',null,'".$page->user->username."','".$page->user->username."')";
            } else {
                $preferredPaymentData['For Sale'][$ptid]['checked'] = null;
            }
        }
    }

    if (($listingCounts['Wanted'] > 0) && (!$hasWanted)) {
        $page->messages->addErrorMsg("You must configure at least one Preferred Payment Wanted or your existing listings will not display");
        $isValid = false;
    }

    if (($listingCounts['For Sale'] > 0) && (!$hasForSale)) {
        $page->messages->addErrorMsg("You must configure at least one Preferred Payment For Sale or your existing listings will not display");
        $isValid = false;
    }

    if ($isValid) {
        $success = true;
        $page->db->sql_begin_trans();
        /*
        $sql = "UPDATE userinfo
                SET bankinfo = :bankinfo,
                    counterminimumdtotal = :counterminimumdtotal
                WHERE userid = :userid";
        $params = array();
        $params['counterminimumdtotal'] = $counterMinimumTotal;
        $params['bankinfo'] = $bankInfo;
        $params['userid'] = $page->user->userId;
        */
        $sql = "UPDATE userinfo
                SET counterminimumdtotal = :counterminimumdtotal
                WHERE userid = :userid";
        $params = array();
        $params['counterminimumdtotal'] = $counterMinimumTotal;
        $params['userid'] = $page->user->userId;
        if ($page->db->sql_execute_params($sql, $params)) {
            $sql = "DELETE FROM preferredpayment WHERE userid=".$page->user->userId;
            $deleted = $page->db->sql_execute_params($sql);
            if (isset($deleted)) { // Will be set but 0 if no previous records
                if ($hasWanted || $hasForSale) {
                    $sql = "INSERT INTO preferredpayment (userid, paymenttypeid, transactiontype, extrainfo, createdby, modifiedby) VALUES ".implode(",",$insertValues);
                    if (! $page->db->sql_execute_params($sql)) {
                        $page->messages->addErrorMsg("Error adding new payment methods");
                        $success = false;
                    }
                }
            } else {
                $page->messages->addErrorMsg("Error removing old payment methods");
                $success = false;
            }
        } else {
            $page->messages->addErrorMsg("Error updating Minimum Order Total For Counter Offers");
            $success = false;
        }

        if ($success) {
            $page->db->sql_commit_trans();
        } else {
            $page->db->sql_rollback_trans();
            $page->messages->addErrorMsg("Dealer Profile NOT updated.");
        }
    } else {
        $page->messages->addErrorMsg("Dealer Profile NOT updated.");
    }

    return $success;
}

function refreshVacationStatus($dealerId) {
    global $page;

    $vacationMsg = NULL;

    $sql = "UPDATE userinfo SET vacationbuy=calcs.vacationbuy, vacationsell=calcs.vacationsell
            FROM (
                SELECT ui.userid, ui.vacationtype
                    ,CASE WHEN ui.onvacation IS NOT NULL
                        AND ui.onvacation < nowtoint()
                        AND (ui.returnondate IS NULL OR ui.returnondate > nowtoint())
                        AND (ui.vacationtype='Buy' OR ui.vacationtype='Both')
                        THEN 1 ELSE 0 END AS vacationbuy
                    ,CASE WHEN ui.onvacation IS NOT NULL
                        AND ui.onvacation < nowtoint()
                        AND (ui.returnondate IS NULL OR ui.returnondate > nowtoint())
                        AND (ui.vacationtype='Sell' OR ui.vacationtype='Both')
                        THEN 1 ELSE 0 END AS vacationsell
                FROM userinfo ui
                WHERE userid=".$dealerId."
            ) calcs
            WHERE calcs.userid=userinfo.userid";

    $page->db->sql_execute($sql);

    $sql = "SELECT CASE WHEN vacationtype='Both' THEN 'Buy and Sell' ELSE vacationtype END AS vacationtypestr, onvacation, returnondate, vacationbuy, vacationsell FROM userinfo WHERE userid=".$dealerId;
    if ($result = $page->db->sql_query($sql)) {
        $vacationInfo = reset($result);
        if ($vacationInfo['vacationbuy'] || $vacationInfo['vacationsell']) {
            $vacationMsg = "Dealer is currently on vacation ";
        } else {
            if ($vacationInfo['returnondate'] && ($vacationInfo['returnondate'] > time())) {
                $vacationMsg = "Dealer is scheduled for vacation ";
            }
        }

        if ($vacationMsg) {
            $vacationMsg .= "from ".date('m/d/Y', $vacationInfo['onvacation'])." thru ".date('m/d/Y', $vacationInfo['returnondate'])." for ".$vacationInfo['vacationtypestr'].".";
            $page->messages->addInfoMsg($vacationMsg);
        }
    }
}

function applyAddressRequest($dealerId, $action) {
    global $page;

    $success = false;

    $addressType = null;
    $currentId = null;
    $newId = null;
    $newTypeId = null;

    if (($action == "payupdate") || ($action == "shipupdate")) {
        $page->db->sql_begin_trans();
        if ($action == "payupdate") {
            $addressType = "Pay To";
            $currentId = $page->db->get_field_query("SELECT usercontactid FROM usercontactinfo WHERE userid=".$dealerId." AND addresstypeid=".ADDRESS_TYPE_PAY);
            $newId = $page->db->get_field_query("SELECT usercontactid FROM usercontactinfo WHERE userid=".$dealerId." AND addresstypeid=".ADDRESS_TYPE_REQUEST_PAY);
            $newTypeId = ADDRESS_TYPE_PAY;
        } else {
            $addressType = "Ship To";
            $currentId = $page->db->get_field_query("SELECT usercontactid FROM usercontactinfo WHERE userid=".$dealerId." AND addresstypeid=".ADDRESS_TYPE_SHIP);
            $newId = $page->db->get_field_query("SELECT usercontactid FROM usercontactinfo WHERE userid=".$dealerId." AND addresstypeid=".ADDRESS_TYPE_REQUEST_SHIP);
            $newTypeId = ADDRESS_TYPE_SHIP;
        }

        if ($currentId && $newId && $newTypeId) {
            $sql = "DELETE FROM usercontactinfo WHERE usercontactid=".$currentId;
            if ($page->db->sql_execute($sql)) {
                $page->messages->addInfoMsg("Deleted old ".$addressType." address");
                $sql = "UPDATE usercontactinfo
                    SET addresstypeid=".$newTypeId.", modifydate=nowtoint(), modifiedby='".$page->user->username."'
                    WHERE usercontactid=".$newId;
                if ($page->db->sql_execute($sql)) {
                    $page->messages->addInfoMsg("Applied new ".$addressType." address");
                    $success = true;
                } else {
                    $page->messages->addErrorMsg("Error applying new ".$addressType." address");
                }
            } else {
                $page->messages->addErrorMsg("Error removing old ".$addressType." address");
            }
        } else {
            $page->messages->addErrorMsg("Unable to apply ".$addressType." address request, current and new addresses required");
        }

        if ($success) {
            $page->db->sql_commit_trans();
            $page->messages->addInfoMsg("New ".$addressType." address applied");
        } else {
            $page->db->sql_rollback_trans();
        }
    }

    if (($action == "paydelete") || ($action == "shipdelete")) {
        if ($action == "paydelete") {
            $addressType = "Pay To";
            $newId = $page->db->get_field_query("SELECT usercontactid FROM usercontactinfo WHERE userid=".$dealerId." AND addresstypeid=".ADDRESS_TYPE_REQUEST_PAY);
            $newTypeId = ADDRESS_TYPE_REQUEST_PAY;
        } else {
            $addressType = "Ship To";
            $newId = $page->db->get_field_query("SELECT usercontactid FROM usercontactinfo WHERE userid=".$dealerId." AND addresstypeid=".ADDRESS_TYPE_REQUEST_SHIP);
            $newTypeId = ADDRESS_TYPE_REQUEST_SHIP;
        }
        if ($newId && $newTypeId) {
            $sql = "DELETE FROM usercontactinfo WHERE usercontactid=".$newId;
            if ($page->db->sql_execute($sql)) {
                $success = true;
                $page->messages->addInfoMsg("Deleted ".$addressType." address request");
            } else {
                $page->messages->addErrorMsg("Error deleting ".$addressType." address request");
            }
        } else {
            $page->messages->addErrorMsg("Unable to delete ".$addressType." address request, address request not found");
        }
    }

    return $success;
}

function deleteDealerLogo($dealerId) {
    global $page;

    $success = false;

    if ($logoFile = $page->db->get_field_query("SELECT listinglogo FROM userinfo WHERE userid=".$dealerId)) {
        $fullFileName = $page->cfg->dataroot.$page->cfg->memberLogosPath.$logoFile;
        if (file_exists($fullFileName)) {
            $page->db->sql_begin_trans();
            $sql = "UPDATE userinfo SET listinglogo=NULL, modifydate=nowtoint(), modifiedby='".$page->user->username."' WHERE userid=".$dealerId;
            if ($page->db->sql_execute($sql)) {
                if (unlink($fullFileName)) {
                    $page->messages->addSuccessMsg("Logo file deleted.");
                    $success = true;
                } else {
                    $page->messages->addErrorMsg("Error deleting logo file.");
                }
            } else {
                $page->messages->addErrorMsg("Error removing logo file from profile.");
            }
            if ($success) {
                $page->db->sql_commit_trans();
            } else {
                $page->db->sql_rollback_trans();
            }
        } else {
            $page->messages->addErrorMsg("Logo file not found on server.");
        }
    } else {
        $page->messages->addErrorMsg("No logo file configured for this dealer.");
    }

    return $success;
}


function profileFormatAddress($userId, $addressTypeId, $isMe=true, $showName=false) {
    global $page;

    $addr = $page->utility->getAddress($userId, $addressTypeId, $isMe, $showName);
    profileDisplayAddress($addr, $userId, $addressTypeId, $isMe, $showName);
}

function profileDisplayAddress($addr, $userId, $addressTypeId, $isMe=true, $showName=false) {
    global $page;

    if ($addr) {
        if (!(empty($addr['firstname']) && empty($addr['lastname']))) {
            if ($showName) {
                echo $page->utility->htmlFriendlyString($addr['firstname']." ".$addr['lastname'])."<br />\n";
            }
        }
        if (!empty($addr['companyname'])) {
            echo $page->utility->htmlFriendlyString($addr['companyname'])."<br />\n";
        }
        /*
        echo $this->htmlFriendlyString($addr['street'])."<br />\n";
        if (!empty($addr['street2'])) {
            echo $this->htmlFriendlyString($addr['street2'])."<br />\n";
        }
        */
        echo "".$addr['city'].", ".$addr['state']." ".$addr['zip']."<br />\n";
        if (!empty($addr['country'])) {
            echo $page->utility->htmlFriendlyString($addr['country'])."<br />\n";
        }
        if (!empty($addr['addressnote'])) {
            echo $page->utility->htmlFriendlyString($addr['addressnote'])."<br />\n";
        }
    } else {
        echo "No information avaliable.\n";
    }
}

?>