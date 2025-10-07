<?php
// index.php - DealernetX Homepage
session_start();

// Set page-specific variables
$page_title = "Home";
$current_page = "home";

// Include the header
include 'includes/header.php';
?>

<style>
    /* Homepage Specific Styles */
    
    /* Hero Section */
    .hero {
        background: linear-gradient(135deg, #0066FF 0%, #003D99 100%);
        color: white;
        padding: 100px 0;
        text-align: center;
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
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        animation: float 20s ease-in-out infinite;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-20px); }
    }
    
    .hero-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 0 20px;
        position: relative;
        z-index: 1;
    }
    
    .hero h1 {
        font-size: 3.5rem;
        font-weight: 800;
        margin-bottom: 1.5rem;
        line-height: 1.2;
    }
    
    .hero p {
        font-size: 1.25rem;
        margin-bottom: 2.5rem;
        opacity: 0.95;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .hero-buttons {
        display: flex;
        justify-content: center;
        gap: 1rem;
    }
    
    .btn-hero {
        padding: 1rem 2rem;
        font-size: 1.1rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-block;
    }
    
    .btn-primary-hero {
        background: white;
        color: #0066FF;
    }
    
    .btn-primary-hero:hover {
        background: #f8f9fa;
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }
    
    .btn-secondary-hero {
        background: transparent;
        color: white;
        border: 2px solid white;
    }
    
    .btn-secondary-hero:hover {
        background: white;
        color: #0066FF;
        transform: translateY(-2px);
    }
    
    /* Corporate Sponsors Section */
    .corporate-sponsors {
        background: white;
        padding: 80px 0;
    }
    
    .section-header {
        text-align: center;
        margin-bottom: 3rem;
    }
    
    .section-title {
        font-size: 2.5rem;
        font-weight: 800;
        background: linear-gradient(135deg, #0066FF 0%, #003D99 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 1rem;
    }
    
    .section-subtitle {
        font-size: 1.1rem;
        color: #64748B;
    }
    
    /* Sponsors Slider */
    .sponsors-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
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
        background: #f8f9fa;
        border-radius: 12px;
        padding: 2rem 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        text-align: center;
        border: 1px solid #e5e7eb;
        height: 140px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    
    .sponsor-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        border-color: #0066FF;
    }
    
    .sponsor-card img {
        max-width: 100%;
        max-height: 60px;
        object-fit: contain;
    }
    
    .sponsor-name {
        margin-top: 1rem;
        font-size: 0.9rem;
        font-weight: 600;
        color: #333;
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
        background: #0066FF;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        pointer-events: all;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }
    
    .slider-btn:hover {
        background: #003D99;
        transform: scale(1.1);
    }
    
    /* Auto-scroll animation */
    @keyframes scroll {
        0% { transform: translateX(0); }
        100% { transform: translateX(calc(-232px * 7)); }
    }
    
    .slider-track.auto-scroll {
        animation: scroll 30s linear infinite;
    }
    
    .slider-wrapper:hover .slider-track.auto-scroll {
        animation-play-state: paused;
    }
    
    /* Features Section */
    .features-section {
        background: #f8fafc;
        padding: 80px 0;
    }
    
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 2rem;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    .feature-card {
        background: white;
        padding: 2.5rem;
        border-radius: 16px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    
    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .feature-icon {
        width: 60px;
        height: 60px;
        background: #0066FF;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        font-size: 1.5rem;
        color: white;
    }
    
    .feature-card h3 {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 1rem;
        color: #0F172A;
    }
    
    .feature-card p {
        color: #64748B;
        line-height: 1.7;
    }
    
    /* App Section */
    .app-section {
        background: linear-gradient(135deg, #0066FF 0%, #003D99 100%);
        color: white;
        padding: 80px 0;
    }
    
    .app-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
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
    
    .app-content > p {
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
        color: #0066FF;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-weight: bold;
    }
    
    .app-buttons {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .app-btn {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        border: 2px solid white;
        display: inline-block;
    }
    
    .app-btn-primary {
        background: white;
        color: #0066FF;
    }
    
    .app-btn-secondary {
        background: transparent;
        color: white;
    }
    
    .app-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
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
        box-shadow: 0 30px 60px rgba(0,0,0,0.3);
        position: relative;
        overflow: hidden;
    }
    
    .phone-screen {
        width: 100%;
        height: 100%;
        background: #f8fafc;
        border-radius: 20px;
        padding: 1rem;
        overflow: hidden;
    }
    
    .app-demo {
        background: white;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .demo-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
    }
    
    .demo-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: #0F172A;
    }
    
    .demo-badge {
        background: #10B981;
        color: white;
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .demo-badge.alert {
        background: #EF4444;
    }
    
    .demo-prices {
        display: flex;
        justify-content: space-between;
        color: #64748B;
        font-size: 0.8rem;
    }
    
    /* Partners Section */
    .partners-section {
        background: white;
        padding: 80px 0;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .hero h1 {
            font-size: 2.5rem;
        }
        
        .hero-buttons {
            flex-direction: column;
            align-items: center;
        }
        
        .features-grid {
            grid-template-columns: 1fr;
        }
        
        .app-container {
            flex-direction: column;
            text-align: center;
        }
        
        .app-mockup {
            display: none;
        }
    }
</style>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-container">
        <h1>The Stock Market for Sports Cards</h1>
        <p>Trade sealed boxes and cases with real-time bid-ask pricing. Join thousands of collectors on the most trusted platform since 2001.</p>
        <div class="hero-buttons">
            <a href="/register.php" class="btn-hero btn-primary-hero">Join Exchange</a>
            <a href="/demo.php" class="btn-hero btn-secondary-hero">Watch Demo</a>
        </div>
    </div>
</section>

<!-- Corporate Sponsors Section -->
<section class="corporate-sponsors">
    <div class="sponsors-container">
        <div class="section-header">
            <h2 class="section-title">Corporate Sponsors</h2>
            <p class="section-subtitle">Partnering with industry leaders worldwide</p>
        </div>
        <div class="slider-wrapper">
            <div class="slider-track auto-scroll" id="corporateSlider">
                <!-- Corporate Sponsors -->
                <div class="sponsor-card">
                    <span class="sponsor-name">Topps Official</span>
                </div>
                <div class="sponsor-card">
                    <span class="sponsor-name">Super Break Cards</span>
                </div>
                <div class="sponsor-card">
                    <span class="sponsor-name">Premium Card Shop</span>
                </div>
                <div class="sponsor-card">
                    <span class="sponsor-name">Elite Trading Cards</span>
                </div>
                <div class="sponsor-card">
                    <span class="sponsor-name">Card Collectors Hub</span>
                </div>
                <div class="sponsor-card">
                    <span class="sponsor-name">Southern Florida Baseball Cards</span>
                </div>
                <!-- Duplicate for continuous scroll -->
                <div class="sponsor-card">
                    <span class="sponsor-name">Topps Official</span>
                </div>
                <div class="sponsor-card">
                    <span class="sponsor-name">Super Break Cards</span>
                </div>
                <div class="sponsor-card">
                    <span class="sponsor-name">Premium Card Shop</span>
                </div>
                <div class="sponsor-card">
                    <span class="sponsor-name">Elite Trading Cards</span>
                </div>
                <div class="sponsor-card">
                    <span class="sponsor-name">Card Collectors Hub</span>
                </div>
                <div class="sponsor-card">
                    <span class="sponsor-name">Southern Florida Baseball Cards</span>
                </div>
            </div>
            <div class="slider-controls">
                <button class="slider-btn" onclick="slideLeft('corporateSlider')">‹</button>
                <button class="slider-btn" onclick="slideRight('corporateSlider')">›</button>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="section-header">
        <h2 class="section-title">Why Trade on DealernetX</h2>
        <p class="section-subtitle">The most advanced platform for serious collectors and investors</p>
    </div>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <h3>Real-Time Bid-Ask Pricing</h3>
            <p>Experience true market dynamics with live bid and ask prices, just like stock trading platforms. See exactly what buyers are willing to pay and sellers are asking.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">🔒</div>
            <h3>Secure Escrow Service</h3>
            <p>Trade with confidence. Our escrow system ensures both parties are protected, with funds and products secured until successful delivery confirmation.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">📱</div>
            <h3>Mobile Scanner App</h3>
            <p>Scan any box barcode to instantly see current market prices, place orders, and get price alerts. Your entire inventory at your fingertips.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">📈</div>
            <h3>Advanced Analytics</h3>
            <p>Make informed decisions with comprehensive price history, volume charts, and market trends. Track your portfolio performance over time.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">⚡</div>
            <h3>Instant Order Matching</h3>
            <p>Our sophisticated matching engine pairs buyers and sellers instantly when prices meet. No waiting, no hassle - just efficient trading.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">🌍</div>
            <h3>Global Marketplace</h3>
            <p>Access inventory from dealers and collectors worldwide. Expand your reach beyond local markets and find the best prices globally.</p>
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
                <a href="#" class="app-btn app-btn-primary">Download for iOS</a>
                <a href="#" class="app-btn app-btn-secondary">Download for Android</a>
            </div>
        </div>
        <div class="app-mockup">
            <div class="phone-frame">
                <div class="phone-screen">
                    <div class="app-demo">
                        <div class="demo-header">
                            <span class="demo-title">2024 Prizm Football Hobby</span>
                            <span class="demo-badge">LIVE</span>
                        </div>
                        <div class="demo-prices">
                            <span>BID: $425</span>
                            <span>ASK: $445</span>
                        </div>
                    </div>
                    <div class="app-demo">
                        <div class="demo-header">
                            <span class="demo-title">2024 Topps Chrome Baseball</span>
                            <span class="demo-badge">LIVE</span>
                        </div>
                        <div class="demo-prices">
                            <span>BID: $285</span>
                            <span>ASK: $299</span>
                        </div>
                    </div>
                    <div class="app-demo">
                        <div class="demo-header">
                            <span class="demo-title">Pokemon 151 Booster Box</span>
                            <span class="demo-badge alert">ALERT</span>
                        </div>
                        <div class="demo-prices">
                            <span>BID: $345</span>
                            <span>ASK: $365</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Partners Section -->
<section class="partners-section">
    <div class="sponsors-container">
        <div class="section-header">
            <h2 class="section-title">Featured Distributors & Partners</h2>
            <p class="section-subtitle">Trusted by the industry leading brands</p>
        </div>
        <div class="slider-wrapper">
            <div class="slider-track auto-scroll" id="partnersSlider">
                <!-- Partners -->
                <div class="sponsor-card">
                    <span class="sponsor-name">Sports Cards</span>
                </div>
                <div class="sponsor-card">
                    <span class="sponsor-name">Gold River</span>
                </div>
                <div class="sponsor-card">
                    <span class="sponsor-name">Pokemon</span>
                </div>
                <div class="sponsor-card">
                    <span class="sponsor-name">Yu-Gi-Oh!</span>
                </div>
                <div class="sponsor-card">
                    <span class="sponsor-name">Southern Hobby</span>
                </div>
                <div class="sponsor-card">
                    <span class="sponsor-name">Burbank Cards</span>
                </div>
                <!-- Duplicate for continuous scroll -->
                <div class="sponsor-card">
                    <span class="sponsor-name">Sports Cards</span>
                </div>
                <div class="sponsor-card">
                    <span class="sponsor-name">Gold River</span>
                </div>
                <div class="sponsor-card">
                    <span class="sponsor-name">Pokemon</span>
                </div>
                <div class="sponsor-card">
                    <span class="sponsor-name">Yu-Gi-Oh!</span>
                </div>
                <div class="sponsor-card">
                    <span class="sponsor-name">Southern Hobby</span>
                </div>
                <div class="sponsor-card">
                    <span class="sponsor-name">Burbank Cards</span>
                </div>
            </div>
            <div class="slider-controls">
                <button class="slider-btn" onclick="slideLeft('partnersSlider')">‹</button>
                <button class="slider-btn" onclick="slideRight('partnersSlider')">›</button>
            </div>
        </div>
    </div>
</section>

<script>
// Slider functionality
let sliderPositions = {};

function slideLeft(sliderId) {
    const slider = document.getElementById(sliderId);
    if (!sliderPositions[sliderId]) sliderPositions[sliderId] = 0;
    
    slider.classList.remove('auto-scroll');
    sliderPositions[sliderId] = Math.max(0, sliderPositions[sliderId] - 232);
    slider.style.transform = `translateX(-${sliderPositions[sliderId]}px)`;
}

function slideRight(sliderId) {
    const slider = document.getElementById(sliderId);
    if (!sliderPositions[sliderId]) sliderPositions[sliderId] = 0;
    
    slider.classList.remove('auto-scroll');
    const maxScroll = (slider.children.length - 5) * 232;
    sliderPositions[sliderId] = Math.min(maxScroll, sliderPositions[sliderId] + 232);
    slider.style.transform = `translateX(-${sliderPositions[sliderId]}px)`;
}

// Re-enable auto-scroll after manual interaction
let scrollTimeouts = {};

function resetAutoScroll(sliderId) {
    clearTimeout(scrollTimeouts[sliderId]);
    scrollTimeouts[sliderId] = setTimeout(() => {
        const slider = document.getElementById(sliderId);
        slider.classList.add('auto-scroll');
        slider.style.transform = '';
        sliderPositions[sliderId] = 0;
    }, 10000);
}

// Add event listeners
document.querySelectorAll('.slider-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const sliderId = btn.closest('.slider-wrapper').querySelector('.slider-track').id;
        resetAutoScroll(sliderId);
    });
});
</script>

<?php
// Include the footer
include 'includes/footer.php';
?>
