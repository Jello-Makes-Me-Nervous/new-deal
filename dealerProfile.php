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

$dealerMetrics = new DealerMetrics();
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

// Output buffer to capture and modify the header
ob_start();
echo $page->header('DealernetX - '.$pageTitle);
$headerContent = ob_get_clean();

// Remove everything before </head> and add our clean structure
$headPos = strpos($headerContent, '</head>');
if ($headPos !== false) {
    echo substr($headerContent, 0, $headPos);
}

echo getModernStyles(); // Add modern styles
echo '</head><body class="modern-dealernetx">';
echo getModernNavigation(); // Add modern navigation
echo mainContent();
echo getModernFooter(); // Add modern footer
echo getModernScripts(); // Add modern scripts
echo $page->footer(true);

function mainContent() {
    global $page, $UTILITY, $dealerId, $dealerInfo, $dealerTransactions, $dealerMetrics, $metricMatrix, $metricMatrixAsOf;
    global $dealerIsStaff, $isMyProfile, $action, $preferredPaymentData, $bankInfo, $paypalId, $membershipFee, $listingFee, $counterMinimumTotal, $counterMinimumLeast, $counterMinimumMost;

    $dealername = $UTILITY->getDealersName($dealerId);

    $html = '
    <!-- Main Content Wrapper -->
    <div class="main-content-wrapper">
        <div class="container">';

    // Profile Header with Logo and Basic Info
    $html .= '
            <div class="profile-header-card">
                <div class="profile-header-content">';
    
    if ($dealerInfo['listinglogo']) {
        $deleteImg = ($page->user->isAdmin()) ? '<button class="logo-delete-btn" onclick="if(confirm(\'Are you sure you want to delete this logo?\')) window.location.href=\'dealerProfile.php?dealerId='.$dealerId.'&action=rmlogo\'"><i class="fa-solid fa-trash"></i></button>' : '';
        $html .= '
                    <div class="dealer-logo-wrapper">
                        <img src="'.$page->utility->getPrefixMemberImageURL($dealerInfo['listinglogo']).'" class="dealer-logo" alt="Dealer Logo">
                        '.$deleteImg.'
                    </div>';
    }
    
    $html .= '
                    <div class="dealer-info">
                        <h1 class="dealer-name">'.$dealername.'</h1>
                        <div class="dealer-badges">';
    
    if ($dealerInfo['elitemember']) {
        $html .= '<span class="badge badge-elite"><i class="fas fa-star"></i> Elite Member</span>';
    }
    if ($dealerInfo['bluestarmember']) {
        $html .= '<span class="badge badge-bluestar"><i class="fas fa-star"></i> Above Standard Member</span>';
    }
    if ($dealerInfo['verifiedmember']) {
        $html .= '<span class="badge badge-verified"><i class="fas fa-check"></i> Verified Member</span>';
    }
    
    $html .= '
                        </div>
                        <div class="dealer-meta">';
    if ($page->user->isStaff()) {
        $html .= '<span class="meta-item">User Class: '.$dealerInfo['userclassname'].'</span>';
    }
    $html .= '
                            <span class="meta-item">Member Since: '.date('m/d/Y', $dealerInfo['accountcreated']).'</span>
                        </div>';
    
    // Admin Actions
    if ($page->user->isAdmin()) {
        $html .= '
                        <div class="admin-actions">
                            <a href="userUpdate.php?userId='.$dealerId.'" class="admin-btn" title="Edit"><i class="fas fa-edit"></i></a>
                            <a href="EFTone.php?userId='.$dealerId.'" class="admin-btn" title="EFT Credit"><i class="fas fa-credit-card"></i></a>
                            <a href="assignUserRights.php?userId='.$dealerId.'" class="admin-btn" title="Rights"><i class="fas fa-user-cog"></i></a>
                            <a href="inProxy.php?proxiedId='.$dealerId.'" class="admin-btn" title="Proxy"><i class="fas fa-mask"></i></a>
                        </div>';
    }
    
    $html .= '
                    </div>
                </div>';
    
    // Action Buttons
    $html .= '
                <div class="profile-actions">';
    
    if ($isMyProfile) {
        if ($action == 'edit') {
            // Form starts here for editing
            $html = '<form name="sub" action="dealerProfile.php" method="post">
                <input type="hidden" id="action" name="action" value="save">
                <div class="main-content-wrapper">
                    <div class="container">' . $html;
        } else {
            $html .= '
                    <a href="dealerProfile.php?action=edit#editinfo" class="btn-action">Edit Payment Options</a>
                    <a href="updatePassword.php?userid='.$dealerId.'" class="btn-action">Change Password</a>
                    <a href="dealerCreditInfo.php" class="btn-action">CC for Membership</a>
                    <a href="onVacation.php" class="btn-action">Vacation Status</a>';
        }
        $html .= '
                    <a href="assignPreferences.php?dealerId='.$dealerId.'" class="btn-action">Preferences</a>
                    <a href="notificationPreferences.php?dealerId='.$dealerId.'" class="btn-action">Notifications</a>';
    } elseif ($page->user->isAdmin()) {
        $html .= '
                    <a href="updatePassword.php?userid='.$dealerId.'" class="btn-action">Change Password</a>
                    <a href="notificationPreferences.php?dealerId='.$dealerId.'" class="btn-action">Notifications</a>';
    }
    
    if ($isMyProfile || (!$dealerIsStaff)) {
        $html .= '
                    <a href="marketsnapshot.php?dealer='.$dealername.'&type=W&sortby=cat&hourssince=0" class="btn-action">View My Buys</a>
                    <a href="marketsnapshot.php?dealer='.$dealername.'&type=FS&sortby=cat&hourssince=0" class="btn-action">View My Sells</a>';
    }
    
    $html .= '
                </div>
            </div>';

    // Dealer Metrics
    if (($isMyProfile || (!$dealerIsStaff)) && $dealerMetrics && is_array($metricMatrix)) {
        $html .= '
            <div class="metrics-card">
                <h2 class="section-title">Performance Metrics</h2>
                <p class="metrics-info">Lifetime Accepted Transactions: '.($dealerMetrics->lifetimeAccepted ?: 'None').'</p>';
        
        if (is_array($metricMatrix)) {
            $matrixAsOf = ($metricMatrixAsOf) ? "As of ".date('m/d/Y h:i:s') : "";
            
            $html .= '
                <div class="metrics-table-wrapper">
                    <table class="metrics-table">
                        <thead>
                            <tr>
                                <th></th>';
            
            foreach ($dealerMetrics->intervals as $intervalId => $intervalInfo) {
                if (displayMetricInterval($intervalId)) {
                    $html .= '<th>'.$intervalInfo['name'].'</th>';
                }
            }
            
            $html .= '
                            </tr>
                        </thead>
                        <tbody>';
            
            foreach ($metricMatrix as $metricname => $metric) {
                $html .= '
                            <tr>
                                <th>'.$dealerMetrics->getProfileColumnTitle($metricname).'</th>';
                
                $tdClass = $dealerMetrics->styleProfileColumnData($metricname);
                foreach ($metric as $intervalId => $intervalValue) {
                    if (displayMetricInterval($intervalId)) {
                        $classStr = $tdClass ? ' class="'.$tdClass.'"' : '';
                        $html .= '<td'.$classStr.'>'.$dealerMetrics->formatProfileColumnData($intervalValue, $metricname).'</td>';
                    }
                }
                
                $html .= '
                            </tr>';
            }
            
            $html .= '
                        </tbody>
                    </table>
                </div>
                <p class="metrics-date">'.$matrixAsOf.'</p>';
        }
        
        $html .= '
            </div>';
    }

    // Addresses Section
    if ($isMyProfile || (!$dealerIsStaff)) {
        $payAddressAsOf = $UTILITY->getAddressAsOf($dealerId, ADDRESS_TYPE_PAY);
        $shipAddressAsOf = $UTILITY->getAddressAsOf($dealerId, ADDRESS_TYPE_SHIP);
        
        $html .= '
            <div class="addresses-section">
                <div class="section-header-row">
                    <h2 class="section-title">Addresses</h2>';
        
        if ($isMyProfile) {
            $html .= '<a href="addressChange.php" class="btn-link">Request Address Change</a>';
        }
        
        $html .= '
                </div>
                <div class="addresses-grid">
                    <div class="address-card">
                        <h3 class="address-title">Pay To Address '.$payAddressAsOf.'</h3>
                        <div class="address-content">';
        
        ob_start();
        if ($isMyProfile || $page->user->isAdmin()) {
            $UTILITY->formatAddress($dealerId, ADDRESS_TYPE_PAY, $isMyProfile, true);
            
            if ($addr = $UTILITY->getAddress($dealerId, ADDRESS_TYPE_REQUEST_PAY, $isMyProfile, true)) {
                echo '<div class="pending-address">';
                echo '<strong>Pending Pay To Address '.$UTILITY->getAddressAsOf($dealerId, ADDRESS_TYPE_REQUEST_PAY).'</strong>';
                if ($page->user->isAdmin()) {
                    echo ' <button onclick="window.location.href=\'dealerProfile.php?dealerId='.$dealerId.'&action=payupdate\'" class="btn-small">Apply</button>';
                }
                if ($page->user->isAdmin() || $isMyProfile) {
                    echo ' <button onclick="window.location.href=\'dealerProfile.php?dealerId='.$dealerId.'&action=paydelete\'" class="btn-small btn-danger">Delete</button>';
                }
                echo '</div>';
                $UTILITY->displayAddress($addr, $dealerId, ADDRESS_TYPE_REQUEST_PAY, $isMyProfile, true);
            }
        } else {
            profileFormatAddress($dealerId, ADDRESS_TYPE_PAY, $isMyProfile, true);
        }
        $addressHtml = ob_get_clean();
        
        $html .= $addressHtml;
        $html .= '
                        </div>
                    </div>
                    
                    <div class="address-card">
                        <h3 class="address-title">Ship To Address '.$shipAddressAsOf.'</h3>
                        <div class="address-content">';
        
        ob_start();
        if ($isMyProfile || $page->user->isAdmin()) {
            $UTILITY->formatAddress($dealerId, ADDRESS_TYPE_SHIP, $isMyProfile, true);
            
            if ($addr = $UTILITY->getAddress($dealerId, ADDRESS_TYPE_REQUEST_SHIP, $isMyProfile, true)) {
                echo '<div class="pending-address">';
                echo '<strong>Pending Ship To Address '.$UTILITY->getAddressAsOf($dealerId, ADDRESS_TYPE_REQUEST_SHIP).'</strong>';
                if ($page->user->isAdmin()) {
                    echo ' <button onclick="window.location.href=\'dealerProfile.php?dealerId='.$dealerId.'&action=shipupdate\'" class="btn-small">Apply</button>';
                }
                if ($page->user->isAdmin() || $isMyProfile) {
                    echo ' <button onclick="window.location.href=\'dealerProfile.php?dealerId='.$dealerId.'&action=shipdelete\'" class="btn-small btn-danger">Delete</button>';
                }
                echo '</div>';
                $UTILITY->displayAddress($addr, $dealerId, ADDRESS_TYPE_REQUEST_SHIP, $isMyProfile, true);
            }
        } else {
            profileFormatAddress($dealerId, ADDRESS_TYPE_SHIP, $isMyProfile, true);
        }
        $addressHtml = ob_get_clean();
        
        $html .= $addressHtml;
        $html .= '
                        </div>
                    </div>
                </div>
            </div>';
    }

    // Account Note
    if ($dealerInfo && !empty($dealerInfo['accountnote'])) {
        $html .= '
            <div class="info-card">
                <h3 class="card-title">Account Note</h3>
                <div class="card-content">
                    '.$page->utility->htmlFriendlyString($dealerInfo['accountnote']).'
                </div>
            </div>';
    }

    // Bank/Payment Info (Staff Only)
    if ($isMyProfile || $page->user->isStaff()) {
        $html .= '
            <div class="payment-info-card" id="editinfo">
                <h3 class="card-title">Payment Information</h3>
                <div class="payment-info-grid">
                    <div class="info-item">
                        <label>Bank Info</label>
                        <span>'.$bankInfo.'</span>
                    </div>
                    <div class="info-item">
                        <label>EFT Withdraw Paypal ID</label>
                        <span>'.$paypalId.'</span>
                    </div>';
        
        if ($page->user->isStaff()) {
            $html .= '
                    <div class="info-item">
                        <label>Membership Fee</label>
                        <span>$'.$membershipFee.'</span>
                    </div>
                    <div class="info-item">
                        <label>Listing Fee</label>
                        <span>'.($listingFee*100).'%</span>
                    </div>';
        }
        
        $html .= '
                </div>
            </div>';
    }

    // Counter Offer Minimum
    if (($isMyProfile || $page->user->isStaff()) && isset($dealerInfo['counterminimumdtotal'])) {
        $counterMinimumTotalStr = ($isMyProfile && $action == 'edit')
            ? '<input type="text" id="counterminimumdtotal" name="counterminimumdtotal" class="input-inline" value="'.$counterMinimumTotal.'">'
            : $counterMinimumTotal;
        
        $html .= '
            <div class="info-card">
                <h3 class="card-title">Counter Offer Settings</h3>
                <div class="card-content">
                    Minimum Order Total For Counter Offers: $'.$counterMinimumTotalStr.' 
                    <span class="hint">($'.$counterMinimumLeast.' to $'.$counterMinimumMost.')</span>
                </div>
            </div>';
    }

    // Payment Options
    $preferredPayment = ($isMyProfile && $action == 'edit') 
        ? getEditDealerPreferredPayment() 
        : getDealerPreferredPayment();
    
    $html .= '
            <div class="payment-options-section">
                <h2 class="section-title">Payment Options</h2>';
    
    if ($isMyProfile && $action == 'edit') {
        $html .= '<p class="section-note">* indicates additional info is required if option is selected</p>';
    }
    
    $html .= '
                <div class="payment-options-grid">
                    <div class="payment-option-card">
                        <h3 class="card-title">Buying Payment Options</h3>
                        <div class="payment-options-content">
                            '.$preferredPayment['Wanted'].'
                        </div>
                    </div>
                    <div class="payment-option-card">
                        <h3 class="card-title">Selling Payment Options</h3>
                        <div class="payment-options-content">
                            '.$preferredPayment['For Sale'].'
                        </div>
                    </div>
                </div>
            </div>';

    // Dealer Notes (for other dealers)
    if (!$isMyProfile) {
        $dNotes = getDealerNotes();
        $notesHtml = '';
        if (isset($dNotes) && count($dNotes) > 0) {
            foreach ($dNotes as $dN) {
                $notesHtml .= '<li>'.$dN['dealernote'].'</li>';
            }
        } else {
            $notesHtml = '<li>No notes</li>';
        }
        
        $html .= '
            <div class="dealer-notes-card">
                <h3 class="card-title">My Dealer Notes <span class="subtitle">(only visible to you)</span></h3>
                <div class="card-content">
                    <ul class="notes-list">
                        '.$notesHtml.'
                    </ul>
                </div>
                <a href="dealerNotes.php?dealerId='.$dealerId.'" class="btn-action">Edit Dealer Notes</a>
            </div>';
    }

    // Form Actions
    if ($action == 'edit') {
        $html .= '
            <div class="form-actions">
                <button type="submit" name="savebtn" id="savebtn" class="btn-save">Save Changes</button>
                <a href="dealerProfile.php" class="btn-cancel">Cancel</a>
            </div>
        </form>';
    }

    $html .= '
        </div>
    </div>';

    return $html;
}

function getModernStyles() {
    return '<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0066FF;
            --primary-dark: #003D99;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
            --info: #3B82F6;
            --dark: #0F172A;
            --gray: #64748B;
            --light: #F8FAFC;
            --gradient: linear-gradient(135deg, #0066FF 0%, #003D99 100%);
            --navy: #001F3F;
            --gold: #FFD700;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background: var(--light);
            overflow-x: hidden;
        }

        /* Hide original template elements */
        body > table:last-of-type, body > center:last-of-type { display: none !important; }
        .modern-footer ~ * { display: none !important; }
        .original-header, .original-nav { display: none; }
        body > table, body > center { display: none !important; }
        #header, .header, #navigation, .navigation { display: none !important; }
        .page-header:not(.profile-header-card) { display: none !important; }

        /* Modern Navigation */
        nav.modern-nav {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-link {
            display: inline-block;
            height: 40px;
            text-decoration: none;
        }

        .logo-img {
            height: 40px;
            width: auto;
            display: block;
            transition: opacity 0.3s ease;
        }

        .logo-img:hover {
            opacity: 0.8;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            color: var(--gray);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary);
        }

        .nav-link::after {
            content: "";
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after, .nav-link.active::after {
            width: 100%;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-block;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 102, 255, 0.2);
        }

        /* Main Content */
        .main-content-wrapper {
            margin-top: 80px;
            padding: 2rem 0;
            min-height: calc(100vh - 80px);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Profile Header Card */
        .profile-header-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .profile-header-content {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
        }

        .dealer-logo-wrapper {
            position: relative;
            flex-shrink: 0;
        }

        .dealer-logo {
            width: 150px;
            height: 150px;
            object-fit: contain;
            border-radius: 12px;
            background: var(--light);
            padding: 0.5rem;
        }

        .logo-delete-btn {
            position: absolute;
            top: -10px;
            right: -10px;
            background: var(--danger);
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .logo-delete-btn:hover {
            transform: scale(1.1);
        }

        .dealer-info {
            flex: 1;
        }

        .dealer-name {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .dealer-badges {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .badge-elite {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: white;
        }

        .badge-bluestar {
            background: linear-gradient(135deg, #0066FF, #003D99);
            color: white;
        }

        .badge-verified {
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
        }

        .dealer-meta {
            display: flex;
            gap: 2rem;
            color: var(--gray);
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
        }

        .admin-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .admin-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--light);
            color: var(--gray);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .admin-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        /* Profile Actions */
        .profile-actions {
            display: flex;
            flex-wrap: nowrap;
            gap: 0.75rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
            overflow-x: auto;
        }

        .btn-action {
            background: var(--primary);
            color: white;
            padding: 0.625rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            display: inline-block;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .btn-action:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Metrics Card */
        .metrics-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .metrics-info {
            color: var(--gray);
            margin-bottom: 1.5rem;
        }

        .metrics-table-wrapper {
            overflow-x: auto;
        }

        .metrics-table {
            width: 100%;
            border-collapse: collapse;
        }

        .metrics-table thead {
            background: var(--light);
        }

        .metrics-table th {
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--dark);
            border-bottom: 2px solid #e5e7eb;
        }

        .metrics-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .metrics-table tbody tr:hover {
            background: #f8fafc;
        }

        .metrics-date {
            text-align: right;
            color: var(--gray);
            font-size: 0.85rem;
            margin-top: 1rem;
        }

        /* Addresses Section */
        .addresses-section {
            margin-bottom: 2rem;
        }

        .section-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .btn-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .btn-link:hover {
            color: var(--primary-dark);
        }

        .addresses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .address-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .address-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .address-content {
            color: var(--gray);
            line-height: 1.8;
        }

        .pending-address {
            background: #FEF3C7;
            padding: 0.75rem;
            border-radius: 6px;
            margin: 1rem 0;
        }

        .pending-address strong {
            color: #92400E;
        }

        /* Info Cards */
        .info-card, .payment-info-card, .payment-option-card, .dealer-notes-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .card-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .card-title .subtitle {
            color: var(--gray);
            font-weight: 400;
            font-size: 0.85rem;
        }

        .card-content {
            color: var(--gray);
            line-height: 1.7;
        }

        /* Payment Info Grid */
        .payment-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-item label {
            font-size: 0.85rem;
            color: var(--gray);
            margin-bottom: 0.25rem;
        }

        .info-item span {
            font-weight: 600;
            color: var(--dark);
        }

        /* Payment Options */
        .payment-options-section {
            margin-bottom: 2rem;
        }

        .section-note {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .payment-options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .payment-options-content {
            line-height: 1.8;
        }

        .payment-options-content table {
            width: 100%;
        }

        .payment-options-content td {
            padding: 0.5rem 0;
        }

        .payment-options-content input[type="text"] {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .payment-options-content input[type="checkbox"] {
            margin-right: 0.5rem;
        }

        /* Dealer Notes */
        .notes-list {
            list-style: none;
            padding-left: 0;
        }

        .notes-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .notes-list li:last-child {
            border-bottom: none;
        }

        /* Form Elements */
        .input-inline {
            padding: 0.375rem 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 0.95rem;
            width: auto;
            display: inline-block;
        }

        .hint {
            color: var(--gray);
            font-size: 0.85rem;
            margin-left: 0.5rem;
        }

        .btn-small {
            padding: 0.25rem 0.75rem;
            font-size: 0.85rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-small:hover {
            background: var(--primary-dark);
        }

        .btn-danger {
            background: var(--danger);
        }

        .btn-danger:hover {
            background: #DC2626;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .btn-save {
            background: var(--success);
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: var(--gray);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: #475569;
        }

        /* Modern Footer */
        footer.modern-footer {
            background: linear-gradient(180deg, #001F3F 0%, #000A1A 100%);
            color: white;
            padding: 3rem 2rem 1rem;
            margin-top: 0;
            position: relative;
            overflow: hidden;
        }

        footer.modern-footer::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, #0066FF, transparent);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }

        .footer-container {
            max-width: 100%;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 3rem;
            margin-bottom: 2rem;
        }

        .footer-brand h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #0066FF 0%, #00A3FF 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
        }

        .footer-brand p {
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.7;
            font-size: 0.95rem;
        }

        .footer-column h4 {
            margin-bottom: 1rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #0066FF;
            font-weight: 600;
        }

        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .footer-link {
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            position: relative;
            padding-left: 0;
        }

        .footer-link:hover {
            color: #00A3FF;
            padding-left: 5px;
        }

        .footer-bottom {
            border-top: 1px solid rgba(0, 102, 255, 0.2);
            padding-top: 2rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        .footer-bottom a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-bottom a:hover {
            color: #00A3FF;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .profile-header-content {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .dealer-meta {
                flex-direction: column;
                gap: 0.5rem;
            }

            .profile-actions {
                justify-content: flex-start;
            }

            .addresses-grid,
            .payment-options-grid {
                grid-template-columns: 1fr;
            }

            .payment-info-grid {
                grid-template-columns: 1fr;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }
    </style>';
}

function getModernNavigation() {
    return '
    <!-- Modern Navigation -->
    <nav class="modern-nav" id="navbar">
        <div class="nav-container">
            <a href="/index.php" class="logo-link">
                <img src="/images/dealernetx-logo.png" alt="DealernetX" class="logo-img" onerror="this.style.display=\'none\'; this.parentElement.innerHTML=\'<span style=\\\'font-size: 1.5rem; font-weight: 800; background: linear-gradient(135deg, #0066FF 0%, #003D99 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;\\\'>DealernetX</span>\';">
            </a>
            <div class="nav-links">
                <a href="/listings.php" class="nav-link">Marketplace</a>
                <a href="/offers.php" class="nav-link">My Offers</a>
                <a href="/dealerProfile.php" class="nav-link active">Profile</a>
                <a href="/scanner.php" class="nav-link">Scanner App</a>
                <a href="/login.php" class="nav-link">Login</a>
                <a href="/register.php" class="btn-primary">Start Trading</a>
            </div>
        </div>
    </nav>';
}

function getModernFooter() {
    $currentYear = date('Y');
    return '
    <!-- Modern Footer -->
    <footer class="modern-footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <h3>DealernetX</h3>
                    <p>The original bid-ask marketplace for sports cards, gaming, and collectibles.</p>
                    <p>Trusted by collectors and dealers worldwide since 2001.</p>
                </div>
                <div class="footer-column">
                    <h4>Platform</h4>
                    <div class="footer-links">
                        <a href="/listings.php" class="footer-link">Marketplace</a>
                        <a href="/offers.php" class="footer-link">My Offers</a>
                        <a href="/scanner.php" class="footer-link">Scanner App</a>
                    </div>
                </div>
                <div class="footer-column">
                    <h4>Company</h4>
                    <div class="footer-links">
                        <a href="/contact.php" class="footer-link">Contact</a>
                    </div>
                </div>
                <div class="footer-column">
                    <h4>Support</h4>
                    <div class="footer-links">
                        <a href="/help.php" class="footer-link">Help Center</a>
                        <a href="/faqs.php" class="footer-link">FAQs</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; ' . $currentYear . ' DealernetX. All rights reserved. | <a href="/privacy.php">Privacy Policy</a> | <a href="/terms.php">Terms of Service</a></p>
            </div>
        </div>
    </footer>';
}

function getModernScripts() {
    return '<script>
        // Smooth scrolling for navigation
        document.querySelectorAll(\'a[href^="#"]\').forEach(anchor => {
            anchor.addEventListener("click", function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute("href"));
                if (target) {
                    target.scrollIntoView({
                        behavior: "smooth",
                        block: "start"
                    });
                }
            });
        });

        // Navbar scroll effect
        window.addEventListener("scroll", function() {
            const navbar = document.getElementById("navbar");
            if (navbar && window.scrollY > 50) {
                navbar.style.boxShadow = "0 2px 20px rgba(0, 0, 0, 0.1)";
            } else if (navbar) {
                navbar.style.boxShadow = "none";
            }
        });
    </script>';
}

// Keep all existing backend functions unchanged
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
                $preferredPayment['Wanted'] .= "<input type='text' style='width:50ch;' id='wantinfo".$ptid."'  name='wantinfo".$ptid."' value='".$paymenttype['extrainfo']."' />".$required;
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
                $preferredPayment['For Sale'] .= "<input type='text' style='width:50ch;' id='saleinfo".$ptid."'  name='saleinfo".$ptid."' value='".$paymenttype['extrainfo']."' />".$required;
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
        
        $sql = "UPDATE userinfo
                SET counterminimumdtotal = :counterminimumdtotal
                WHERE userid = :userid";
        $params = array();
        $params['counterminimumdtotal'] = $counterMinimumTotal;
        $params['userid'] = $page->user->userId;
        if ($page->db->sql_execute_params($sql, $params)) {
            $sql = "DELETE FROM preferredpayment WHERE userid=".$page->user->userId;
            $deleted = $page->db->sql_execute_params($sql);
            if (isset($deleted)) {
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
        
        echo "".$addr['city'].", ".$addr['state']." ".$addr['zip']."<br />\n";
        if (!empty($addr['country'])) {
            echo $page->utility->htmlFriendlyString($addr['country'])."<br />\n";
        }
        if (!empty($addr['addressnote'])) {
            echo $page->utility->htmlFriendlyString($addr['addressnote'])."<br />\n";
        }
    } else {
        echo "No information available.\n";
    }
}
?>
