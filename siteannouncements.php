<?php
require_once('templateHome.class.php');

$page = new templateHome(LOGIN, SHOWMSG, REDIRECTSAFE);

if (! $page->verifyPaymentMethods()) {
    header('Location:dealerProfile.php');
    exit();
}

if (isset($CFG->adminmulti)) {
    $directory  = $CFG->adminmulti;
} else {
    $directory  = $CFG->dataroot."adminmulti/";
}

$filename = $directory."siteannouncements.inc";

// Check if user is admin - accessed from backend with ?edit=true parameter
$isAdmin = isset($_GET['edit']) && $_GET['edit'] === 'true';
// For production, use your actual admin check from backend:
// $isAdmin = isset($_SESSION['admin']) && $_SESSION['admin'] === true;

// Handle form submission if admin is saving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin && isset($_POST['announcement_content'])) {
    $contentToSave = $_POST['announcement_content'];
    
    try {
        // Create directory if it doesn't exist
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // Save the content
        $fp = fopen($filename, 'w');
        if ($fp) {
            fwrite($fp, $contentToSave);
            fclose($fp);
            $page->messages->addSuccessMsg("Announcements have been successfully updated!");
            // Redirect to remove POST data
            header('Location: siteannouncements.php?saved=true');
            exit();
        }
    } catch (Exception $e) {
        $page->messages->addErrorMsg("Error saving announcements: ".$e->getMessage());
    }
}

$content = null;
if (file_exists($filename)) {
    try {
        $fp = fopen($filename,'r');
        $content = fread($fp, filesize($filename));
        fclose($fp);
    } catch (Exception $e) {
        $page->messages->addErrorMsg("Error: ".$e->getMessage()." [Reading file]");
        $content = null;
    }
}

// Get corporate sponsors data (you can move this to a separate include file)
if (!function_exists('getCorporateSponsorsData')) {
    function getCorporateSponsorsData() {
        return [
            ['name' => 'Southern Florida Baseball Cards', 'image' => '/images/corporate/sfbc.jpg', 'link' => '#'],
            ['name' => 'Burbank Sportscards', 'image' => '/images/corporate/burbank.jpg', 'link' => '#'],
            ['name' => 'Pulse Card Exchange', 'image' => '/images/corporate/pulse.jpg', 'link' => '#'],
            ['name' => 'Historic Autographs', 'image' => '/images/corporate/historic.jpg', 'link' => '#'],
            ['name' => 'The Zon Stand', 'image' => '/images/corporate/zonstand.jpg', 'link' => '#'],
            ['name' => 'Topps Official', 'image' => '/images/corporate/topps.jpg', 'link' => '#'],
            ['name' => 'Super Break Cards', 'image' => '/images/corporate/superbreak.jpg', 'link' => '#'],
            ['name' => 'Premium Card Shop', 'image' => '/images/corporate/premium.jpg', 'link' => '#'],
            ['name' => 'Elite Trading Cards', 'image' => '/images/corporate/elite.jpg', 'link' => '#'],
            ['name' => 'Card Collectors Hub', 'image' => '/images/corporate/collectors.jpg', 'link' => '#'],
        ];
    }
}

$corporateSponsors = getCorporateSponsorsData();
$currentYear = date('Y');

// Output buffer to capture and modify the header
ob_start();
echo $page->header('Site Announcements');
$headerContent = ob_get_clean();

// Remove everything before </head> and add our modern styles
$headPos = strpos($headerContent, '</head>');
if ($headPos !== false) {
    echo substr($headerContent, 0, $headPos);
}

echo getModernStyles(); // Add modern styles

// Add TinyMCE if admin
if ($isAdmin) {
    echo '
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: "#announcement_editor",
            height: 500,
            menubar: true,
            plugins: [
                "advlist", "autolink", "lists", "link", "image", "charmap", "preview",
                "anchor", "searchreplace", "visualblocks", "code", "fullscreen",
                "insertdatetime", "media", "table", "help", "wordcount"
            ],
            toolbar: "undo redo | blocks | " +
                "bold italic forecolor backcolor | alignleft aligncenter " +
                "alignright alignjustify | bullist numlist outdent indent | " +
                "removeformat | link image media | code preview fullscreen | help",
            content_style: "body { font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif; font-size: 14px; line-height: 1.6; }",
            setup: function (editor) {
                editor.on("submit", function (e) {
                    editor.save();
                });
            }
        });
        
        // Ensure TinyMCE content is saved before form submission
        document.addEventListener("DOMContentLoaded", function() {
            var form = document.getElementById("announcement-form");
            if (form) {
                form.addEventListener("submit", function(e) {
                    tinymce.triggerSave();
                });
            }
        });
    </script>';
    }
}

echo '</head><body class="modern-dealernetx">';
echo mainContent();
echo getCorporateSponsorsSection();
echo getModernFooter();
echo getModernScripts();
echo $page->footer(true); // Use true to suppress default footer HTML

if (!function_exists('mainContent')) {
    function mainContent() {
        global $content, $isAdmin, $filename;
    
    $html = '
    <!-- Clean wrapper for modern content -->
    <div id="modern-wrapper" style="position: relative; z-index: 9999; background: white; margin-top: -20px;">
    
    <!-- Modern Navigation -->
    <nav class="modern-nav" id="navbar">
        <div class="nav-container">
            <a href="/index.php" class="logo">DealernetX</a>
            <div class="nav-links">
                <a href="/marketplace.php" class="nav-link">Marketplace</a>
                <a href="/membership.php" class="nav-link">Membership</a>
                <a href="/scanner.php" class="nav-link">Scanner App</a>
                <a href="/about.php" class="nav-link">About</a>
                <a href="/login.php" class="nav-link">Login</a>
                <a href="/register.php" class="btn-primary">Start Trading</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <h1>📢 Site Announcements</h1>
            <p>Stay updated with the latest news, updates, and important information</p>
        </div>
    </section>

    <!-- Main Content Area -->
    <div class="announcements-container">';
    
    // Show success message if saved
    if (isset($_GET['saved']) && $_GET['saved'] === 'true') {
        $html .= '
        <div class="success-message">
            Announcements have been successfully updated!
        </div>';
    }
    
    if ($isAdmin) {
        // Admin edit mode
        $html .= '
        <!-- Admin Controls -->
        <div class="admin-controls">
            <div class="edit-mode-indicator">
                <span class="admin-badge">ADMIN MODE</span>
                <span>You are currently in edit mode</span>
            </div>
            <a href="siteannouncements.php" class="btn btn-secondary">View as User</a>
        </div>

        <!-- Editor Form -->
        <form method="POST" action="siteannouncements.php?edit=true" id="announcement-form">
            <div class="editor-container">
                <div class="editor-header">
                    <h2>Edit Announcements</h2>
                </div>
                
                <textarea id="announcement_editor" name="announcement_content">' . 
                    htmlspecialchars($content ?: '') . '
                </textarea>
                
                <div class="action-buttons">
                    <button type="submit" class="btn btn-success" onclick="tinymce.triggerSave();">💾 Save Changes</button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href=\'siteannouncements.php\'">Cancel</button>
                </div>
            </div>
        </form>

        <!-- Preview Section -->
        <div class="editor-container" style="margin-top: 2rem;">
            <div class="editor-header">
                <h2>Preview</h2>
            </div>
            <div class="announcement-display">';
        
        if (!empty($content)) {
            $html .= $content;
        } else {
            $html .= '<p>No content to preview. Start typing in the editor above.</p>';
        }
        
        $html .= '
            </div>
        </div>';
        
    } else {
        // Regular user view - no admin button
        $html .= '
        <article>
            <div class="entry-content announcement-display">';
        
        // Display the content from the file, or a default message if empty
        if (!empty($content)) {
            $html .= $content;
        } else {
            $html .= '
                <div class="announcement-card">
                    <h3>No Announcements</h3>
                    <p>There are currently no announcements. Please check back later for updates.</p>
                </div>';
        }
        
        $html .= '
            </div> <!-- entry-content -->
        </article>';
    }
    
    $html .= '
    </div>'; // End announcements-container
    
    return $html;
}

if (!function_exists('getCorporateSponsorsSection')) {
    function getCorporateSponsorsSection() {
    global $corporateSponsors;
    
    $html = '
    <!-- Corporate Sponsors Slider Section -->
    <section class="sponsors-slider">
        <div class="sponsors-container">
            <div class="sponsors-header">
                <h2 class="sponsors-title">Corporate Sponsors</h2>
                <p class="sponsors-subtitle">Partnering with industry leaders worldwide</p>
            </div>
            <div class="slider-wrapper">
                <div class="slider-track auto-scroll" id="corporateSlider">';
    
    // Add corporate sponsor cards - duplicate for continuous scrolling
    $allCorporate = array_merge($corporateSponsors, $corporateSponsors);
    foreach ($allCorporate as $corp) {
        // Create a base64 placeholder image
        $corpPlaceholder = base64_encode('<svg width="200" height="120" xmlns="http://www.w3.org/2000/svg"><rect width="200" height="120" fill="#333"/><text text-anchor="middle" x="100" y="60" font-family="Arial" font-size="14" fill="#666">' . htmlspecialchars($corp['name']) . '</text></svg>');
        
        $html .= '
                    <div class="sponsor-card">
                        <a href="' . htmlspecialchars($corp['link']) . '" target="_blank" style="text-decoration: none;">
                            <img src="' . htmlspecialchars($corp['image']) . '" 
                                 alt="' . htmlspecialchars($corp['name']) . '" 
                                 onerror="this.src=\'data:image/svg+xml;base64,' . $corpPlaceholder . '\'">
                            <div class="sponsor-name">' . htmlspecialchars($corp['name']) . '</div>
                        </a>
                    </div>';
    }
    
    $html .= '
                </div>
                <div class="slider-controls">
                    <button class="slider-btn" id="corpPrevBtn" onclick="slideLeftCorp()">‹</button>
                    <button class="slider-btn" id="corpNextBtn" onclick="slideRightCorp()">›</button>
                </div>
            </div>
        </div>
    </section>';
    
    return $html;
    }
}

if (!function_exists('getModernFooter')) {
    function getModernFooter() {
    global $currentYear;
    
    return '
    <!-- Modern Footer -->
    <footer class="modern-footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <h3>DealernetX</h3>
                    <p>The original bid-ask marketplace for sports cards, gaming, and collectibles. Trusted by collectors and dealers worldwide since 2001.</p>
                </div>
                <div class="footer-column">
                    <h4>Platform</h4>
                    <div class="footer-links">
                        <a href="/marketplace.php" class="footer-link">Marketplace</a>
                        <a href="/scanner.php" class="footer-link">Scanner App</a>
                        <a href="/pricing.php" class="footer-link">Pricing</a>
                        <a href="/api.php" class="footer-link">API</a>
                    </div>
                </div>
                <div class="footer-column">
                    <h4>Company</h4>
                    <div class="footer-links">
                        <a href="/about.php" class="footer-link">About Us</a>
                        <a href="/contact.php" class="footer-link">Contact</a>
                        <a href="/careers.php" class="footer-link">Careers</a>
                        <a href="/press.php" class="footer-link">Press</a>
                    </div>
                </div>
                <div class="footer-column">
                    <h4>Support</h4>
                    <div class="footer-links">
                        <a href="/help.php" class="footer-link">Help Center</a>
                        <a href="/faqs.php" class="footer-link">FAQs</a>
                        <a href="/status.php" class="footer-link">System Status</a>
                        <a href="/security.php" class="footer-link">Security</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; ' . $currentYear . ' DealernetX. All rights reserved. | <a href="/privacy.php" style="color: inherit;">Privacy Policy</a> | <a href="/terms.php" style="color: inherit;">Terms of Service</a></p>
            </div>
        </div>
    </footer>
    
    </div> <!-- End modern-wrapper -->';
    }
}

if (!function_exists('getModernStyles')) {
    function getModernStyles() {
    return '<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0066FF;
            --primary-dark: #0052CC;
            --success: #10B981;
            --danger: #EF4444;
            --dark: #0F172A;
            --gray: #64748B;
            --light: #F8FAFC;
            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background: #FFFFFF;
            overflow-x: hidden;
        }

        /* Hide original template elements and sidebars */
        .original-header, .original-nav { display: none; }
        body > table, body > center { display: none !important; }
        #header, .header, #navigation, .navigation { display: none !important; }
        #leftbar, #rightbar, .leftbar, .rightbar { display: none !important; }
        #sidebar, .sidebar, aside { display: none !important; }
        body > table[width="100%"] { display: none !important; }
        td[width="130"], td[width="140"], td[valign="top"][width] { display: none !important; }
        form[action*="search"], .search-form { display: none !important; }
        
        body {
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Navigation */
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

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            cursor: pointer;
            text-decoration: none;
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

        .nav-link:hover {
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

        .nav-link:hover::after {
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

        /* Hero Section */
        .hero {
            margin-top: 80px;
            padding: 4rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .hero-container {
            max-width: 900px;
            margin: 0 auto;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1rem;
            line-height: 1.2;
            animation: fadeInUp 0.8s ease;
        }

        .hero p {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.9);
            animation: fadeInUp 0.8s ease 0.2s both;
            max-width: 700px;
            margin: 0 auto;
        }

        /* Main Content Container */
        .announcements-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 2rem;
            min-height: 500px;
        }

        /* Admin Controls */
        .admin-controls {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-badge {
            background: #ffc107;
            color: #000;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .edit-mode-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #856404;
        }

        /* Editor Container */
        .editor-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .editor-header {
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }

        .editor-header h2 {
            color: #1e293b;
            font-size: 1.5rem;
            margin: 0;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            font-size: 1rem;
        }

        .btn-secondary {
            background: var(--gray);
            color: white;
        }

        .btn-secondary:hover {
            background: #475569;
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        /* Success Message */
        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .success-message::before {
            content: "✅";
            font-size: 1.2rem;
        }

        /* Entry Content (from original) */
        .entry-content {
            font-size: 1rem;
            line-height: 1.8;
            color: var(--dark);
        }

        /* Announcement Display */
        .announcement-display {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            min-height: 200px;
        }

        .announcement-display h1, 
        .announcement-display h2, 
        .announcement-display h3 {
            color: #1e293b;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }

        .announcement-display h1:first-child,
        .announcement-display h2:first-child,
        .announcement-display h3:first-child {
            margin-top: 0;
        }

        .announcement-display p {
            color: #475569;
            line-height: 1.8;
            margin-bottom: 1rem;
        }

        .announcement-display ul, 
        .announcement-display ol {
            margin-left: 1.5rem;
            margin-bottom: 1rem;
            color: #475569;
        }

        .announcement-display li {
            margin-bottom: 0.5rem;
            line-height: 1.6;
        }

        .announcement-display strong {
            color: #1e293b;
            font-weight: 600;
        }

        .announcement-display a {
            color: var(--primary);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .announcement-display a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .announcement-display blockquote {
            border-left: 4px solid #6366f1;
            padding-left: 1rem;
            margin: 1rem 0;
            color: #64748b;
            font-style: italic;
        }

        /* Announcement Cards (if content uses this class) */
        .announcement-card {
            background: #f8f9fa;
            border-left: 4px solid #6366f1;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .announcement-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .announcement-card h3 {
            color: #1e293b;
            margin-top: 0;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        .announcement-card.high-priority {
            border-left-color: #ef4444;
            background: #fef2f2;
        }

        .announcement-card .date {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        /* Corporate Sponsors Slider Section */
        .sponsors-slider {
            padding: 4rem 2rem;
            background: #1a1a1a;
            overflow: hidden;
        }

        .sponsors-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .sponsors-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .sponsors-title {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }

        .sponsors-subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.1rem;
        }

        .slider-wrapper {
            position: relative;
            overflow: hidden;
            padding: 0 3rem;
        }

        .slider-track {
            display: flex;
            transition: transform 0.5s ease;
            gap: 2rem;
        }

        .sponsor-card {
            flex: 0 0 200px;
            background: #2a2a2a;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            text-align: center;
        }

        .sponsor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }

        .sponsor-card img {
            width: 100%;
            height: 120px;
            object-fit: contain;
            margin-bottom: 0.5rem;
            border-radius: 8px;
        }

        .sponsor-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            margin-top: 0.5rem;
        }

        .slider-controls {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 100%;
            display: flex;
            justify-content: space-between;
            pointer-events: none;
            padding: 0 1rem;
        }

        .slider-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gradient);
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            pointer-events: all;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .slider-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        }

        .slider-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Auto-scroll animation */
        @keyframes scroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(calc(-220px * 10)); }
        }

        .slider-track.auto-scroll {
            animation: scroll 30s linear infinite;
        }

        .slider-wrapper:hover .slider-track.auto-scroll {
            animation-play-state: paused;
        }

        /* Modern Footer */
        footer.modern-footer {
            background: var(--dark);
            color: white;
            padding: 3rem 2rem 1rem;
            margin-top: 0;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
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
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .footer-brand p {
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.7;
        }

        .footer-column h4 {
            margin-bottom: 1rem;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .footer-link {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-link:hover {
            color: white;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 2rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }
    </style>';
    }
}

if (!function_exists('getModernScripts')) {
    function getModernScripts() {
    global $corporateSponsors;
    $corporateCount = count($corporateSponsors);
    
    return '<script>
        // Smooth scrolling for navigation
        document.querySelectorAll(\'a[href^="#"]\').forEach(anchor => {
            anchor.addEventListener("click", function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute("href"));
                if (target) {
                    target.scrollIntoView({
                        behavior: "smooth"
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

        // Corporate Slider Controls
        let currentCorpSlide = 0;
        const corpSlider = document.getElementById("corporateSlider");
        const slideWidth = 220; // 200px card + 20px gap
        const totalCorpSlides = ' . $corporateCount . ';
        
        function slideLeftCorp() {
            if (corpSlider) {
                corpSlider.classList.remove("auto-scroll");
                currentCorpSlide = Math.max(0, currentCorpSlide - 1);
                corpSlider.style.transform = `translateX(-${currentCorpSlide * slideWidth}px)`;
            }
        }
        
        function slideRightCorp() {
            if (corpSlider) {
                corpSlider.classList.remove("auto-scroll");
                const maxSlide = Math.max(0, totalCorpSlides - 5);
                currentCorpSlide = Math.min(maxSlide, currentCorpSlide + 1);
                corpSlider.style.transform = `translateX(-${currentCorpSlide * slideWidth}px)`;
            }
        }
        
        // Re-enable auto-scroll after manual interaction
        let corpScrollTimeout;
        
        function resetCorpAutoScroll() {
            clearTimeout(corpScrollTimeout);
            corpScrollTimeout = setTimeout(() => {
                if (corpSlider) {
                    corpSlider.classList.add("auto-scroll");
                    corpSlider.style.transform = "";
                    currentCorpSlide = 0;
                }
            }, 10000);
        }
        
        // Add event listeners for manual controls
        document.getElementById("corpPrevBtn")?.addEventListener("click", resetCorpAutoScroll);
        document.getElementById("corpNextBtn")?.addEventListener("click", resetCorpAutoScroll);
        
        // Touch/swipe support for mobile
        let corpTouchStartX = 0;
        let corpTouchEndX = 0;
        
        if (corpSlider) {
            corpSlider.addEventListener("touchstart", (e) => {
                corpTouchStartX = e.changedTouches[0].screenX;
                corpSlider.classList.remove("auto-scroll");
            });
            
            corpSlider.addEventListener("touchend", (e) => {
                corpTouchEndX = e.changedTouches[0].screenX;
                handleCorpSwipe();
                resetCorpAutoScroll();
            });
        }
        
        function handleCorpSwipe() {
            if (corpTouchEndX < corpTouchStartX - 50) slideRightCorp();
            if (corpTouchEndX > corpTouchStartX + 50) slideLeftCorp();
        }

        // Add animation to announcement cards
        const observerOptions = {
            threshold: 0.1,
            rootMargin: "0px"
        };

        const fadeObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = "fadeInUp 0.6s ease forwards";
                    fadeObserver.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll(".announcement-card").forEach(element => {
            element.style.opacity = "0";
            fadeObserver.observe(element);
        });
    </script>';
}
?>