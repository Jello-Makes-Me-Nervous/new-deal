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
