<?php
// header.php - Reusable header component for all pages
// Include this file at the top of each page using: <?php include 'header.php'; ?>
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DealerNetX - The original interactive marketplace for traders of sealed Sports, Gaming and Collectibles">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>DealerNetX</title>
    
    <!-- CSS Framework (Bootstrap 5) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #667eea;
            --accent-color: #764ba2;
            --text-light: #6c757d;
            --hover-bg: rgba(255,255,255,0.1);
        }
        
        /* Header Styles */
        .main-header {
            background: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white !important;
            text-decoration: none;
        }
        
        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.9) !important;
            padding: 0.5rem 1rem !important;
            margin: 0 0.25rem;
            border-radius: 4px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .navbar-nav .nav-link:hover {
            background: var(--hover-bg);
            color: white !important;
        }
        
        .navbar-nav .nav-link.active {
            background: var(--hover-bg);
            color: white !important;
        }
        
        .navbar-toggler {
            border-color: rgba(255,255,255,0.5);
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.9%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        /* User Menu Buttons */
        .btn-user {
            background: var(--hover-bg);
            color: white !important;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .btn-user:hover {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.5);
        }
        
        .btn-login {
            background: transparent;
            color: white !important;
            border: 1px solid rgba(255,255,255,0.5);
            padding: 0.5rem 1.25rem;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
            margin-right: 0.5rem;
        }
        
        .btn-login:hover {
            background: rgba(255,255,255,0.1);
            border-color: white;
        }
        
        .btn-signup {
            background: var(--secondary-color);
            color: white !important;
            border: 1px solid var(--secondary-color);
            padding: 0.5rem 1.25rem;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .btn-signup:hover {
            background: var(--accent-color);
            border-color: var(--accent-color);
            transform: translateY(-1px);
        }
        
        /* Dropdown Menu */
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 8px;
            margin-top: 0.5rem;
        }
        
        .dropdown-item {
            padding: 0.75rem 1.25rem;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background: var(--secondary-color);
            color: white;
        }
        
        .dropdown-item i {
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Area */
        .main-content {
            min-height: calc(100vh - 200px);
            padding-top: 2rem;
            padding-bottom: 2rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 991px) {
            .navbar-nav {
                padding: 1rem 0;
            }
            
            .navbar-nav .nav-link {
                margin: 0.25rem 0;
            }
            
            .btn-login, .btn-signup {
                width: 100%;
                margin: 0.25rem 0;
                text-align: center;
            }
        }
    </style>
    
    <?php
    // Allow pages to add custom CSS files
    if (isset($additional_css) && is_array($additional_css)) {
        foreach ($additional_css as $css_file) {
            echo "<link rel='stylesheet' href='$css_file'>\n";
        }
    }
    ?>
</head>
<body>
    <!-- Main Header -->
    <header class="main-header">
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <!-- Brand/Logo -->
                <a class="navbar-brand" href="index.php">
                    <i class="fas fa-chart-line me-2"></i>DealerNetX
                </a>
                
                <!-- Mobile toggle button -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <!-- Main navigation -->
                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (isset($current_page) && $current_page == 'marketplace') ? 'active' : ''; ?>" href="marketplace.php">
                                <i class="fas fa-store me-1"></i>Marketplace
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (isset($current_page) && $current_page == 'membership') ? 'active' : ''; ?>" href="membership.php">
                                <i class="fas fa-users me-1"></i>Membership
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (isset($current_page) && $current_page == 'scanner') ? 'active' : ''; ?>" href="scanner.php">
                                <i class="fas fa-barcode me-1"></i>Scanner App
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (isset($current_page) && $current_page == 'login') ? 'active' : ''; ?>" href="login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (isset($current_page) && $current_page == 'trading') ? 'active' : ''; ?>" href="trading.php">
                                <i class="fas fa-exchange-alt me-1"></i>Start Trading
                            </a>
                        </li>
                    </ul>
                    
                    <!-- Right side user menu -->
                    <div class="d-flex align-items-center">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <!-- Logged in user dropdown -->
                            <div class="dropdown">
                                <button class="btn btn-user dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i>
                                    <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User'; ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="dashboard.php">
                                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                    </a></li>
                                    <li><a class="dropdown-item" href="my-listings.php">
                                        <i class="fas fa-list me-2"></i>My Listings
                                    </a></li>
                                    <li><a class="dropdown-item" href="watchlist.php">
                                        <i class="fas fa-heart me-2"></i>Watchlist
                                    </a></li>
                                    <li><a class="dropdown-item" href="account.php">
                                        <i class="fas fa-cog me-2"></i>Account Settings
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <!-- Guest menu -->
                            <a href="login.php" class="btn btn-login">
                                <i class="fas fa-sign-in-alt me-1"></i> Login
                            </a>
                            <a href="register.php" class="btn btn-signup">
                                <i class="fas fa-user-plus me-1"></i> Sign Up
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- Main Content Container -->
    <main class="main-content">
        <div class="container-fluid">
ashley@162-213-123-153:/www/devdx/includes$ cd ..
ashley@162-213-123-153:/www/devdx$ cat scanner.php
<?php
// scanner.php - DealerNetX Scanner App Information Page
require_once 'config.php';

// Page-specific settings
$page_title = "Scanner App";
$current_page = "scanner";
$page_description = "DealerNetX Scanner App - Real-time bid/ask pricing for sports and gaming boxes at your fingertips";

// Additional CSS files for this page
$additional_css = [
    'scanner-styles.css'
];

// Include the header
include 'includes/header.php';
?>

<!-- Scanner Hero Section -->
<section class="scanner-hero">
    <div class="container">
        <h1>DealerNetX Scanner App</h1>
        <p class="tagline">Scan Smarter, Sell Faster</p>
        <p>Real-time bid/ask pricing for all sports & gaming boxes at your fingertips</p>
    </div>
</section>

<!-- Download Section -->
<section class="download-section container">
    <div class="download-buttons">
        <a href="<?php echo GOOGLE_PLAY_URL; ?>" 
           target="_blank" 
           rel="noopener noreferrer" 
           class="download-btn google-play">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M3,20.5V3.5C3,2.91 3.34,2.39 3.84,2.15L13.69,12L3.84,21.85C3.34,21.6 3,21.09 3,20.5M16.81,15.12L6.05,21.34L14.54,12.85L16.81,15.12M20.16,10.81C20.5,11.08 20.75,11.5 20.75,12C20.75,12.5 20.53,12.9 20.18,13.18L17.89,14.5L15.39,12L17.89,9.5L20.16,10.81M6.05,2.66L16.81,8.88L14.54,11.15L6.05,2.66Z"/>
            </svg>
            <div class="download-text">
                <span class="small">GET IT ON</span>
                <span class="large">Google Play</span>
            </div>
        </a>
        
        <a href="<?php echo APP_STORE_URL; ?>" 
           target="_blank" 
           rel="noopener noreferrer" 
           class="download-btn app-store">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M18.71,19.5C17.88,20.74 17,21.95 15.66,21.97C14.32,22 13.89,21.18 12.37,21.18C10.84,21.18 10.37,21.95 9.1,22C7.79,22.05 6.8,20.68 5.96,19.47C4.25,17 2.94,12.45 4.7,9.39C5.57,7.87 7.13,6.91 8.82,6.88C10.1,6.86 11.32,7.75 12.11,7.75C12.89,7.75 14.37,6.68 15.92,6.84C16.57,6.87 18.39,7.1 19.56,8.82C19.47,8.88 17.39,10.1 17.41,12.63C17.44,15.65 20.06,16.66 20.09,16.67C20.06,16.74 19.67,18.11 18.71,19.5M13,3.5C13.73,2.67 14.94,2.04 15.94,2C16.07,3.17 15.6,4.35 14.9,5.19C14.21,6.04 13.07,6.7 11.95,6.61C11.8,5.46 12.36,4.26 13,3.5Z"/>
            </svg>
            <div class="download-text">
                <span class="small">Download on the</span>
                <span class="large">App Store</span>
            </div>
        </a>
    </div>
    
    <p style="text-align: center; color: #666; margin-top: 1rem;">
        Available for iOS 15.1+ and Android devices
    </p>
</section>

<!-- Features Section -->
<section class="scanner-features">
    <div class="container">
        <h2>Key Features</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3>Instant UPC Scanning</h3>
                <p>Simply scan any UPC barcode using your phone's camera for immediate product identification</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">💰</div>
                <h3>Live Bid/Ask Data</h3>
                <p>Connect directly to real-time bid and ask pricing from the DealerNetX marketplace</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Accurate Comps</h3>
                <p>Get instant, accurate comparable prices to make informed buying and selling decisions</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🏪</div>
                <h3>Card Show Ready</h3>
                <p>Perfect for card shows, in-store shopping, and on-the-go pricing checks</p>
            </div>
        </div>
    </div>
</section>

<!-- Benefits Section -->
<section class="benefits">
    <div class="container">
        <h2>Why Choose DealerNetX Scanner?</h2>
        <div class="benefits-list">
            <div class="benefit-item">
                <span class="benefit-check">✓</span>
                <div>
                    <strong>No More Guessing:</strong> Make confident decisions backed by real-time marketplace data instead of relying on outdated pricing guides.
                </div>
            </div>
            
            <div class="benefit-item">
                <span class="benefit-check">✓</span>
                <div>
                    <strong>Save Time:</strong> Instantly check prices without manual searches or price guide lookups.
                </div>
            </div>
            
            <div class="benefit-item">
                <span class="benefit-check">✓</span>
                <div>
                    <strong>Better Deals:</strong> Know exactly what products are worth before buying or selling.
                </div>
            </div>
            
            <div class="benefit-item">
                <span class="benefit-check">✓</span>
                <div>
                    <strong>Stay Competitive:</strong> Keep up with market trends and price changes in real-time.
                </div>
            </div>
            
            <div class="benefit-item">
                <span class="benefit-check">✓</span>
                <div>
                    <strong>Professional Tool:</strong> Designed for serious collectors, dealers, and traders.
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="how-it-works">
    <div class="container">
        <h2>How It Works</h2>
        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <h3>Download the App</h3>
                <p>Get the free DealerNetX Scanner app from Google Play or the App Store</p>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <h3>Sign In</h3>
                <p>Use your DealerNetX account credentials to access the scanner</p>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <h3>Start Scanning</h3>
                <p>Point your camera at any UPC barcode to instantly get pricing data</p>
            </div>
            
            <div class="step">
                <div class="step-number">4</div>
                <h3>Make Smart Decisions</h3>
                <p>Use real-time bid/ask data to buy and sell with confidence</p>
            </div>
        </div>
    </div>
</section>

<?php
// Include the footer
include 'includes/footer.php';
?>
