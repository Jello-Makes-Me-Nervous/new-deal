<?php
require_once('templateHome.class.php');

$page = new templateHome(NOLOGIN, SHOWMSG);

if (isset($CFG->adminmulti)) {
    $directory  = $CFG->adminmulti;
} else {
    $directory  = $CFG->dataroot."adminmulti/";
}

// Output buffer to capture and modify the header
ob_start();
echo $page->header('DealernetX - The Stock Market for Sports Cards');
$headerContent = ob_get_clean();

// Remove everything before </head> and add our clean structure
$headPos = strpos($headerContent, '</head>');
if ($headPos !== false) {
    echo substr($headerContent, 0, $headPos);
}

echo getModernStyles(); // Add modern styles
echo '</head><body class="modern-dealernetx">';
echo mainContent();
echo getModernScripts(); // Add modern scripts
echo $page->footer(true);

function mainContent() {
    // Return the modern HTML content directly
    return getModernHomepage();
}

function getMarketData() {
    // TODO: Replace this with actual database query
    // Example: $db->query("SELECT product, bid, ask FROM market_prices ORDER BY volume DESC LIMIT 4");
    return [
        ['product' => '2024 Prizm Football Hobby', 'bid' => 425, 'ask' => 445],
        ['product' => '2024 Topps Chrome Baseball', 'bid' => 285, 'ask' => 299],
        ['product' => 'Pokemon 151 Booster Box', 'bid' => 345, 'ask' => 365],
        ['product' => '2024 National Treasures NBA', 'bid' => 4250, 'ask' => 4450],
    ];
}

function getFeatures() {
    return [
        [
            'icon' => '📊',
            'title' => 'Real-Time Bid-Ask Pricing',
            'description' => 'Experience true market dynamics with live bid and ask prices, just like stock trading platforms. See exactly what buyers are willing to pay and sellers are asking.'
        ],
        [
            'icon' => '🔒',
            'title' => 'Secure Escrow Service',
            'description' => 'Trade with confidence. Our escrow system ensures both parties are protected, with funds and products secured until successful delivery confirmation.'
        ],
        [
            'icon' => '📱',
            'title' => 'Mobile Scanner App',
            'description' => 'Scan any box barcode to instantly see current market prices, place orders, and get price alerts. Your entire inventory at your fingertips.'
        ],
        [
            'icon' => '📈',
            'title' => 'Advanced Analytics',
            'description' => 'Make informed decisions with comprehensive price history, volume charts, and market trends. Track your portfolio performance over time.'
        ],
        [
            'icon' => '⚡',
            'title' => 'Instant Order Matching',
            'description' => 'Our sophisticated matching engine pairs buyers and sellers instantly when prices meet. No waiting, no hassle - just efficient trading.'
        ],
        [
            'icon' => '🌍',
            'title' => 'Global Marketplace',
            'description' => 'Access inventory from dealers and collectors worldwide. Expand your reach beyond local markets and find the best prices globally.'
        ]
    ];
}

function getSponsorsData() {
    // Add your actual sponsor/distributor images here
    // These would typically come from your database or existing sidebar content
    return [
        ['name' => 'GTS Distribution', 'image' => '/images/sponsors/gts.jpg', 'link' => '#'],
        ['name' => 'Upper Deck', 'image' => '/images/sponsors/upperdeck.jpg', 'link' => '#'],
        ['name' => 'Panini', 'image' => '/images/sponsors/panini.jpg', 'link' => '#'],
        ['name' => 'Topps', 'image' => '/images/sponsors/topps.jpg', 'link' => '#'],
        ['name' => 'Sports Cards', 'image' => '/images/sponsors/sportscards.jpg', 'link' => '#'],
        ['name' => 'Gold River', 'image' => '/images/sponsors/goldriver.jpg', 'link' => '#'],
        ['name' => 'Pokemon', 'image' => '/images/sponsors/pokemon.jpg', 'link' => '#'],
        ['name' => 'Yu-Gi-Oh!', 'image' => '/images/sponsors/yugioh.jpg', 'link' => '#'],
        ['name' => 'Southern Hobby', 'image' => '/images/sponsors/southernhobby.jpg', 'link' => '#'],
        ['name' => 'Burbank Cards', 'image' => '/images/sponsors/burbank.jpg', 'link' => '#'],
        ['name' => 'Historic Autographs', 'image' => '/images/sponsors/historic.jpg', 'link' => '#'],
        ['name' => 'Pulse', 'image' => '/images/sponsors/pulse.jpg', 'link' => '#'],
        ['name' => 'The Zon Stand', 'image' => '/images/sponsors/zonstand.jpg', 'link' => '#'],
        ['name' => 'Super Break', 'image' => '/images/sponsors/superbreak.jpg', 'link' => '#'],
    ];
}

function getCorporateSponsorsData() {
    // Add your actual corporate sponsor images here
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

function formatPrice($price) {
    return $price >= 1000 ? number_format($price) : $price;
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
        
        /* Hide the old DealernetX header and navigation */
        body > table, body > center { display: none !important; }
        #header, .header, #navigation, .navigation { display: none !important; }
        
        /* Hide sidebars */
        #leftbar, #rightbar, .leftbar, .rightbar { display: none !important; }
        #sidebar, .sidebar, aside { display: none !important; }
        
        /* Hide any table-based layout elements */
        body > table[width="100%"] { display: none !important; }
        td[width="130"], td[width="140"], td[valign="top"][width] { display: none !important; }
        
        /* Hide the search bar area */
        form[action*="search"], .search-form { display: none !important; }
        
        /* Clean up body margins */
        body {
            margin: 0 !important;
            padding: 0 !important;
        }
        
        /* Ensure modern content takes full width */
        .modern-nav, .hero, .stats, .features, .app-section, .modern-footer {
            width: 100% !important;
            max-width: none;
            margin-left: 0 !important;
            margin-right: 0 !important;
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
            padding: 5rem 2rem;
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

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            animation: fadeInUp 0.8s ease;
        }

        .hero-content p {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            animation: fadeInUp 0.8s ease 0.2s both;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            animation: fadeInUp 0.8s ease 0.4s both;
        }

        .btn-hero {
            padding: 1rem 2rem;
            font-size: 1.1rem;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid white;
            display: inline-block;
        }

        .btn-secondary:hover {
            background: white;
            color: var(--primary);
            transform: translateY(-2px);
        }

        /* Features Section */
        .features {
            padding: 5rem 2rem;
            background: var(--light);
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .section-subtitle {
            font-size: 1.25rem;
            color: var(--gray);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .feature-card:hover::before {
            transform: scaleX(1);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }

        .feature-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .feature-description {
            color: var(--gray);
            line-height: 1.7;
        }

        /* Stats Section */
        .stats {
            padding: 4rem 2rem;
            background: white;
        }

        .stats-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .stat-card {
            text-align: center;
            padding: 2rem;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--gray);
            font-size: 1.1rem;
        }

        /* App Section - Horizontal Layout */
        .app-section {
            padding: 5rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .app-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 4rem;
        }

        .app-content {
            flex: 1;
            max-width: 500px;
        }

        .app-content h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }

        .app-content p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }

        .app-features {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .app-feature {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .check-icon {
            width: 24px;
            height: 24px;
            background: white;
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .app-buttons {
            display: flex;
            gap: 1rem;
        }

        .app-mockup {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .phone-frame {
            width: 300px;
            height: 600px;
            background: white;
            border-radius: 30px;
            padding: 1rem;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .phone-screen {
            width: 100%;
            height: 100%;
            background: var(--light);
            border-radius: 20px;
            padding: 1rem;
            overflow: hidden;
        }

        .app-demo {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .demo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .demo-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--dark);
        }

        .demo-badge {
            background: var(--success);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .demo-prices {
            display: flex;
            justify-content: space-between;
            color: #64748B;
            font-size: 0.8rem;
        }

        /* Sponsors Slider Section */
        .sponsors-slider {
            padding: 4rem 2rem;
            background: white;
            overflow: hidden;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .sponsors-slider.light-bg {
            background: var(--light);
        }

        .sponsors-slider.dark-bg {
            background: #1a1a1a;
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
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .sponsors-slider.dark-bg .sponsors-title {
            color: white;
        }

        .sponsors-subtitle {
            color: var(--gray);
            font-size: 1.1rem;
        }

        .sponsors-slider.dark-bg .sponsors-subtitle {
            color: rgba(255, 255, 255, 0.7);
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
            background: white;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            text-align: center;
        }

        .sponsors-slider.dark-bg .sponsor-card {
            background: #2a2a2a;
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
            color: var(--dark);
            margin-top: 0.5rem;
        }

        .sponsors-slider.dark-bg .sponsor-name {
            color: rgba(255, 255, 255, 0.9);
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
            100% { transform: translateX(calc(-220px * 8)); }
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

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-buttons {
                justify-content: center;
            }

            .app-container {
                flex-direction: column;
                text-align: center;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .stats-container {
                grid-template-columns: 1fr 1fr;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }
    </style>';
}

function getModernHomepage() {
    $marketData = getMarketData();
    $features = getFeatures();
    
    // Get sponsors data
    $sponsors = [];
    if (function_exists('getSponsorsData')) {
        $sponsors = getSponsorsData();
    } else {
        // Fallback sponsor data if function doesn't exist
        $sponsors = [
            ['name' => 'Partner 1', 'image' => '/images/partner1.jpg', 'link' => '#'],
            ['name' => 'Partner 2', 'image' => '/images/partner2.jpg', 'link' => '#'],
            ['name' => 'Partner 3', 'image' => '/images/partner3.jpg', 'link' => '#'],
        ];
    }
    
    // Get corporate sponsors data
    $corporateSponsors = [];
    if (function_exists('getCorporateSponsorsData')) {
        $corporateSponsors = getCorporateSponsorsData();
    } else {
        // Fallback corporate sponsor data
        $corporateSponsors = [
            ['name' => 'Corporate 1', 'image' => '/images/corp1.jpg', 'link' => '#'],
            ['name' => 'Corporate 2', 'image' => '/images/corp2.jpg', 'link' => '#'],
            ['name' => 'Corporate 3', 'image' => '/images/corp3.jpg', 'link' => '#'],
        ];
    }
    
    $currentYear = date('Y');
    $yearsInService = $currentYear - 2001;
    
    // Add a wrapper div to ensure proper containment
    $html = '
    <!-- Clean wrapper for modern content -->
    <div id="modern-wrapper" style="position: relative; z-index: 9999; background: white; margin-top: -20px;">
    
    <!-- Modern Navigation -->
    <nav class="modern-nav" id="navbar" style="position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important;">
        <div class="nav-container">
            <div class="logo">DealernetX</div>
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

    <!-- Hero Section - Centered without widget -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1>The Stock Market for Sports Cards</h1>
                <p>Trade sealed boxes and cases with real-time bid-ask pricing. Join thousands of collectors on the most trusted platform since 2001.</p>
                <div class="hero-buttons">
                    <a href="/register.php" class="btn-primary btn-hero">Join Exchange</a>
                    <a href="/demo.php" class="btn-secondary">Watch Demo</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number" data-target="' . $yearsInService . '">' . $yearsInService . '+</div>
                <div class="stat-label">Years of Service</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" data-target="50000">50K+</div>
                <div class="stat-label">Active Traders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" data-target="10000">10K+</div>
                <div class="stat-label">Products Listed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" data-target="500">$500M+</div>
                <div class="stat-label">Volume Traded</div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="features-container">
            <div class="section-header">
                <h2 class="section-title">Why Trade on DealernetX</h2>
                <p class="section-subtitle">The most advanced platform for serious collectors and investors</p>
            </div>
            <div class="features-grid">';
    
    foreach ($features as $feature) {
        $html .= '
                <div class="feature-card">
                    <div class="feature-icon">' . $feature['icon'] . '</div>
                    <h3 class="feature-title">' . htmlspecialchars($feature['title']) . '</h3>
                    <p class="feature-description">' . htmlspecialchars($feature['description']) . '</p>
                </div>';
    }
    
    $html .= '
            </div>
        </div>
    </section>

    <!-- App Section -->
    <section class="app-section">
        <div class="app-container">
            <div class="app-content">
                <h2>Trade Anywhere with Our Mobile App</h2>
                <p>Never miss a trading opportunity with the DealernetX Scanner App</p>
                <div class="app-features">
                    <div class="app-feature">
                        <div class="check-icon">✓</div>
                        <span>Instant barcode scanning for price checks</span>
                    </div>
                    <div class="app-feature">
                        <div class="check-icon">✓</div>
                        <span>Real-time price alerts and notifications</span>
                    </div>
                    <div class="app-feature">
                        <div class="check-icon">✓</div>
                        <span>Manage your inventory and watchlist</span>
                    </div>
                    <div class="app-feature">
                        <div class="check-icon">✓</div>
                        <span>Place and track orders on the go</span>
                    </div>
                </div>
                <div class="app-buttons">
                    <a href="/download/ios" class="btn-primary btn-hero">Download for iOS</a>
                    <a href="/download/android" class="btn-secondary">Download for Android</a>
                </div>
            </div>
            <div class="app-mockup">
                <div class="phone-frame">
                    <div class="phone-screen">';
    
    // Show first 3 items in phone mockup
    $appItems = array_slice($marketData, 0, 3);
    foreach ($appItems as $index => $item) {
        $badge = $index == 2 ? 'ALERT' : 'LIVE';
        $html .= '
                        <div class="app-demo">
                            <div class="demo-header">
                                <span class="demo-title">' . htmlspecialchars($item['product']) . '</span>
                                <span class="demo-badge">' . $badge . '</span>
                            </div>
                            <div class="demo-prices">
                                <span>BID: $' . formatPrice($item['bid']) . '</span>
                                <span>ASK: $' . formatPrice($item['ask']) . '</span>
                            </div>
                        </div>';
    }
    
    $html .= '
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Distributors & Partners Slider Section - Above Footer with Light Background -->
    <section class="sponsors-slider light-bg">
        <div class="sponsors-container">
            <div class="sponsors-header">
                <h2 class="sponsors-title">Featured Distributors & Partners</h2>
                <p class="sponsors-subtitle">Trusted by the industry leading brands</p>
            </div>
            <div class="slider-wrapper">
                <div class="slider-track auto-scroll" id="sponsorSlider">';
    
    // Add sponsor cards - duplicate the array for continuous scrolling
    $allSponsors = array_merge($sponsors, $sponsors); // Duplicate for seamless loop
    foreach ($allSponsors as $sponsor) {
        // Create a base64 placeholder image with the sponsor name
        $placeholderSvg = base64_encode('<svg width="200" height="120" xmlns="http://www.w3.org/2000/svg"><rect width="200" height="120" fill="#eee"/><text text-anchor="middle" x="100" y="60" font-family="Arial" font-size="14" fill="#999">' . htmlspecialchars($sponsor['name']) . '</text></svg>');
        
        $html .= '
                    <div class="sponsor-card">
                        <a href="' . htmlspecialchars($sponsor['link']) . '" target="_blank" style="text-decoration: none;">
                            <img src="' . htmlspecialchars($sponsor['image']) . '" 
                                 alt="' . htmlspecialchars($sponsor['name']) . '" 
                                 onerror="this.src=\'data:image/svg+xml;base64,' . $placeholderSvg . '\'">
                            <div class="sponsor-name">' . htmlspecialchars($sponsor['name']) . '</div>
                        </a>
                    </div>';
    }
    
    $html .= '
                </div>
                <div class="slider-controls">
                    <button class="slider-btn" id="prevBtn" onclick="slideLeft()">‹</button>
                    <button class="slider-btn" id="nextBtn" onclick="slideRight()">›</button>
                </div>
            </div>
        </div>
    </section>

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
                    </div>
                </div>
                <div class="footer-column">
                    <h4>Company</h4>
                    <div class="footer-links">
                        <a href="/aboutus.php" class="footer-link">About Us</a>
                        <a href="/contact.php" class="footer-link">Contact</a>
                    </div>
                </div>
                <div class="footer-column">
                    <h4>Support</h4>
                    <div class="footer-links">
                        <a href="/help.php" class="footer-link">Help Center</a>
                        <a href="/faq.php" class="footer-link">FAQ</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; ' . $currentYear . ' DealernetX. All rights reserved. | <a href="/privacy.php" style="color: inherit;">Privacy Policy</a> | <a href="/terms.php" style="color: inherit;">Terms of Service</a></p>
            </div>
        </div>
    </footer>

    <!-- Corporate Sponsors Slider Section - Below Footer with Dark Background -->
    <section class="sponsors-slider dark-bg">
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
    </section>

    </div> <!-- End modern-wrapper -->';
    
    return $html;
}

function getModernScripts() {
    // Check if sponsors data exists
    $sponsorCount = 14; // Default count
    $corporateCount = 10; // Default count
    if (function_exists('getSponsorsData')) {
        $sponsors = getSponsorsData();
        $sponsorCount = count($sponsors);
    }
    if (function_exists('getCorporateSponsorsData')) {
        $corporateSponsors = getCorporateSponsorsData();
        $corporateCount = count($corporateSponsors);
    }
    
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

        // Animate stats on scroll
        const observerOptions = {
            threshold: 0.5,
            rootMargin: "0px"
        };

        const animateValue = (element, start, end, duration) => {
            let startTimestamp = null;
            const isPrice = element.textContent.includes("$");
            const suffix = element.textContent.includes("+") ? "+" : "";
            const prefix = isPrice ? "$" : "";
            const isMillion = element.textContent.includes("M");
            const isThousand = element.textContent.includes("K");
            
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                
                let current = Math.floor(progress * (end - start) + start);
                
                if (isMillion) {
                    element.textContent = prefix + current + "M" + suffix;
                } else if (isThousand) {
                    element.textContent = current + "K" + suffix;
                } else {
                    element.textContent = current + suffix;
                }
                
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        };

        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !entry.target.animated) {
                    const target = parseInt(entry.target.dataset.target);
                    animateValue(entry.target, 0, target, 2000);
                    entry.target.animated = true;
                }
            });
        }, observerOptions);

        document.querySelectorAll(".stat-number").forEach(stat => {
            statsObserver.observe(stat);
        });

        // Add hover effects to feature cards
        const featureCards = document.querySelectorAll(".feature-card");
        
        featureCards.forEach(card => {
            card.addEventListener("mouseenter", function() {
                this.style.transform = "translateY(-5px)";
            });
            
            card.addEventListener("mouseleave", function() {
                this.style.transform = "translateY(0)";
            });
        });

        // Intersection Observer for fade-in animations
        const fadeElements = document.querySelectorAll(".feature-card, .stat-card");
        
        const fadeObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = "fadeInUp 0.6s ease forwards";
                    fadeObserver.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1
        });

        fadeElements.forEach(element => {
            element.style.opacity = "0";
            fadeObserver.observe(element);
        });

        // Distributor Slider Controls
        let currentSlide = 0;
        const slider = document.getElementById("sponsorSlider");
        const slideWidth = 220; // 200px card + 20px gap
        const totalSlides = ' . $sponsorCount . ';
        
        function slideLeft() {
            if (slider) {
                slider.classList.remove("auto-scroll");
                currentSlide = Math.max(0, currentSlide - 1);
                slider.style.transform = `translateX(-${currentSlide * slideWidth}px)`;
            }
        }
        
        function slideRight() {
            if (slider) {
                slider.classList.remove("auto-scroll");
                const maxSlide = Math.max(0, totalSlides - 5); // Show 5 at a time
                currentSlide = Math.min(maxSlide, currentSlide + 1);
                slider.style.transform = `translateX(-${currentSlide * slideWidth}px)`;
            }
        }
        
        // Corporate Slider Controls
        let currentCorpSlide = 0;
        const corpSlider = document.getElementById("corporateSlider");
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
        
        // Optional: Re-enable auto-scroll after manual interaction
        let scrollTimeout, corpScrollTimeout;
        
        function resetAutoScroll() {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                if (slider) {
                    slider.classList.add("auto-scroll");
                    slider.style.transform = "";
                    currentSlide = 0;
                }
            }, 10000);
        }
        
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
        document.getElementById("prevBtn")?.addEventListener("click", resetAutoScroll);
        document.getElementById("nextBtn")?.addEventListener("click", resetAutoScroll);
        document.getElementById("corpPrevBtn")?.addEventListener("click", resetCorpAutoScroll);
        document.getElementById("corpNextBtn")?.addEventListener("click", resetCorpAutoScroll);
        
        // Touch/swipe support for mobile - Distributors
        let touchStartX = 0;
        let touchEndX = 0;
        
        if (slider) {
            slider.addEventListener("touchstart", (e) => {
                touchStartX = e.changedTouches[0].screenX;
                slider.classList.remove("auto-scroll");
            });
            
            slider.addEventListener("touchend", (e) => {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
                resetAutoScroll();
            });
        }
        
        function handleSwipe() {
            if (touchEndX < touchStartX - 50) slideRight();
            if (touchEndX > touchStartX + 50) slideLeft();
        }
        
        // Touch/swipe support for mobile - Corporate
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
    </script>';
}
?>