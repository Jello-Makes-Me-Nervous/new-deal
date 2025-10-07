<?php
// scanner.php - DealerNetX Scanner App Information Page
session_start();

// Page-specific settings
$page_title = "Scanner App";
$current_page = "scanner";

// Include the header
include 'includes/header.php';

// Define app store URLs
$GOOGLE_PLAY_URL = "https://play.google.com/store";
$APP_STORE_URL = "https://apps.apple.com/";
?>

<style>
/* Scanner App Page Styles */

/* Hero Section */
.scanner-hero {
    background: linear-gradient(135deg, #0066FF 0%, #003D99 100%);
    color: white;
    padding: 80px 20px;
    text-align: center;
    margin-bottom: 60px;
}

.scanner-hero h1 {
    font-size: 3rem;
    font-weight: 800;
    margin-bottom: 1rem;
}

.scanner-hero .tagline {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.scanner-hero p {
    font-size: 1.1rem;
    opacity: 0.95;
}

/* Download Section */
.download-section {
    margin-bottom: 80px;
}

.download-buttons {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.download-btn {
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 2rem;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
    gap: 0.75rem;
}

.download-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}

.download-btn svg {
    width: 32px;
    height: 32px;
}

.download-text {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.download-text .small {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.download-text .large {
    font-size: 1.25rem;
    font-weight: 600;
}

.google-play {
    background: #000;
    color: white;
}

.google-play:hover {
    background: #333;
    color: white;
}

.app-store {
    background: #000;
    color: white;
}

.app-store:hover {
    background: #333;
    color: white;
}

/* Features Section */
.scanner-features {
    background: #f8f9fa;
    padding: 80px 20px;
    margin-bottom: 80px;
}

.scanner-features h2 {
    text-align: center;
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 3rem;
    color: #2c3e50;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.feature-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.feature-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.feature-card h3 {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #2c3e50;
}

.feature-card p {
    color: #6c757d;
    line-height: 1.7;
}

/* Benefits Section */
.benefits {
    padding: 80px 20px;
    margin-bottom: 80px;
}

.benefits h2 {
    text-align: center;
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 3rem;
    color: #2c3e50;
}

.benefits-list {
    max-width: 900px;
    margin: 0 auto;
}

.benefit-item {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.benefit-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.benefit-check {
    font-size: 1.5rem;
    color: #10b981;
    font-weight: bold;
    flex-shrink: 0;
}

.benefit-item strong {
    color: #2c3e50;
}

.benefit-item div {
    line-height: 1.7;
    color: #495057;
}

/* How It Works Section */
.how-it-works {
    background: linear-gradient(135deg, #0066FF 0%, #003D99 100%);
    color: white;
    padding: 80px 20px;
}

.how-it-works h2 {
    text-align: center;
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 3rem;
}

.steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.step {
    text-align: center;
    padding: 2rem;
    background: rgba(255,255,255,0.1);
    border-radius: 12px;
    transition: all 0.3s ease;
}

.step:hover {
    background: rgba(255,255,255,0.15);
    transform: translateY(-5px);
}

.step-number {
    width: 60px;
    height: 60px;
    background: white;
    color: #0066FF;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    font-weight: 800;
    margin: 0 auto 1.5rem;
}

.step h3 {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.step p {
    opacity: 0.95;
    line-height: 1.7;
}

/* Responsive Design */
@media (max-width: 768px) {
    .scanner-hero h1 {
        font-size: 2rem;
    }
    
    .scanner-hero .tagline {
        font-size: 1.25rem;
    }
    
    .download-buttons {
        flex-direction: column;
        align-items: stretch;
    }
    
    .download-btn {
        justify-content: center;
    }
    
    .scanner-features h2,
    .benefits h2,
    .how-it-works h2 {
        font-size: 2rem;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
    }
    
    .steps {
        grid-template-columns: 1fr;
    }
}
</style>

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
        <a href="<?php echo $GOOGLE_PLAY_URL; ?>" 
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
        
        <a href="<?php echo $APP_STORE_URL; ?>" 
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
