<?php
// faqs.php - DealernetX FAQ Page

// Output a minimal head section
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQs - DealerNetX</title>';

echo getFAQStyles();

echo '</head>
<body>';

// Include header navigation from includes folder
require_once('includes/header.php');

echo getFAQContent();

// Include footer from includes folder
require_once('includes/footer.php');

echo getFAQScripts();

echo '</body>
</html>';

function getFAQStyles() {
    return '<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0066FF;
            --primary-dark: #003D99;
            --secondary-color: #64748b;
            --accent-color: #00A3FF;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            --gradient: linear-gradient(135deg, #0066FF 0%, #003D99 100%);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background: #f9fafb;
            min-height: 100vh;
            position: relative;
            margin: 0;
            padding: 0;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0, 102, 255, 0.05) 0%, rgba(0, 163, 255, 0.05) 100%);
            pointer-events: none;
            z-index: -1;
        }

        /* Hide original template elements */
        body > table, body > center { display: none !important; }
        #header, .header, #navigation, .navigation { display: none !important; }
        #leftbar, #rightbar, .leftbar, .rightbar { display: none !important; }
        #sidebar, .sidebar, aside { display: none !important; }

        /* Navigation Styles */
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

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            color: var(--text-light);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 102, 255, 0.2);
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            padding-top: 100px;
        }

        /* Hero Section */
        .hero {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--gradient);
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
            border-radius: 20px;
        }

        .hero::before {
            content: "";
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(-50px, -50px) rotate(120deg); }
            66% { transform: translate(50px, -20px) rotate(240deg); }
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: white;
            position: relative;
            z-index: 1;
        }

        .hero p {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.9);
            max-width: 600px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        /* Search Bar */
        .search-container {
            max-width: 600px;
            margin: 2rem auto 3rem;
            position: relative;
        }

        .search-bar {
            width: 100%;
            padding: 1rem 3rem 1rem 1.25rem;
            font-size: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 50px;
            background: var(--bg-white);
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
        }

        .search-bar:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(0, 102, 255, 0.1), var(--shadow-lg);
            transform: translateY(-2px);
        }

        .search-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        /* Category Filter */
        .category-filter {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }

        .category-btn {
            padding: 0.75rem 1.5rem;
            border: 2px solid var(--border-color);
            background: var(--bg-white);
            border-radius: 50px;
            color: var(--text-dark);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .category-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            background: var(--gradient);
            color: white !important;
            border-color: transparent;
        }

        .category-btn.active {
            background: var(--gradient);
            color: white !important;
            border-color: transparent;
            box-shadow: var(--shadow-md);
        }

        /* FAQ Items */
        .faq-list {
            max-width: 900px;
            margin: 0 auto;
        }

        .faq-item {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 15px;
            margin-bottom: 1rem;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            backdrop-filter: blur(10px);
        }

        .faq-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .faq-question {
            padding: 1.5rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: var(--text-dark);
            transition: all 0.3s ease;
            position: relative;
        }

        .faq-question::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--gradient);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .faq-item.active .faq-question::before {
            opacity: 1;
        }

        .faq-question:hover {
            background: linear-gradient(90deg, rgba(0, 102, 255, 0.05) 0%, rgba(0, 163, 255, 0.05) 100%);
        }

        .faq-icon {
            font-size: 1.5rem;
            transition: transform 0.3s ease;
            color: var(--primary);
        }

        .faq-item.active .faq-icon {
            transform: rotate(45deg);
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease, padding 0.5s ease;
            padding: 0 1.5rem;
            color: var(--text-light);
            line-height: 1.8;
        }

        .faq-item.active .faq-answer {
            max-height: 500px;
            padding: 0 1.5rem 1.5rem;
        }

        /* Contact Section */
        .contact-section {
            margin-top: 4rem;
            margin-bottom: 4rem;
            padding: 3rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            text-align: center;
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-xl);
        }

        .contact-section h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .contact-btn {
            display: inline-block;
            padding: 1rem 2rem;
            background: var(--gradient);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            margin-top: 1rem;
        }

        .contact-btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }

        /* Footer Styles */
        footer.modern-footer {
            background: linear-gradient(180deg, #001F3F 0%, #000A1A 100%);
            color: white;
            padding: 3rem 2rem 1rem;
            margin-top: 0;
            position: relative;
            overflow: hidden;
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
        }

        .footer-bottom a:hover {
            color: #00A3FF;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .nav-links {
                display: none;
            }

            .container {
                padding: 1rem;
                padding-top: 80px;
            }

            .faq-question {
                padding: 1.25rem;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
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

        .faq-item {
            animation: fadeInUp 0.5s ease backwards;
        }

        .faq-item:nth-child(1) { animation-delay: 0.1s; }
        .faq-item:nth-child(2) { animation-delay: 0.2s; }
        .faq-item:nth-child(3) { animation-delay: 0.3s; }
        .faq-item:nth-child(4) { animation-delay: 0.4s; }
        .faq-item:nth-child(5) { animation-delay: 0.5s; }
    </style>';
}

function getFAQContent() {
    // FAQ Data Array
    $faqs = [
        // Payment FAQs
        [
            'category' => 'payment',
            'question' => 'What is the Dealernet EFT program?',
            'answer' => 'The Dealernet EFT (Electronic Funds Transfer) program is our automated payment system that allows for secure, direct bank-to-bank transfers. This program streamlines payment processing, reduces transaction times, and eliminates the need for manual payment handling. Members enrolled in the EFT program benefit from faster processing times and reduced transaction fees.'
        ],
        [
            'category' => 'payment',
            'question' => 'How are payments processed?',
            'answer' => 'Payments are processed through our secure payment gateway within 1-2 business days. Once a transaction is initiated, funds are verified and transferred electronically. You\'ll receive email confirmation at each stage of the process. For credit card payments, processing is typically instant, while ACH transfers may take 2-3 business days to complete.'
        ],
        [
            'category' => 'payment',
            'question' => 'What types of transaction incur fees?',
            'answer' => 'Transaction fees apply to: credit card payments (2.9% + $0.30), international wire transfers ($25), expedited ACH transfers ($10), and chargeback requests ($15). Standard ACH transfers and EFT program transactions have no fees. Premium members receive discounted rates on all transaction fees.'
        ],
        [
            'category' => 'payment',
            'question' => 'What forms of payment are accepted?',
            'answer' => 'We accept multiple payment methods including: major credit cards (Visa, MasterCard, American Express, Discover), ACH bank transfers, wire transfers, PayPal, and approved business checks. For transactions over $10,000, we also offer special payment arrangements and terms for qualified members.'
        ],
        [
            'category' => 'payment',
            'question' => 'How do I cancel a PayPal subscription?',
            'answer' => 'To cancel a PayPal subscription: Log into your PayPal account, go to Settings > Payments > Manage automatic payments, find the Dealernet subscription, click on it and select "Cancel." You can also cancel through your Dealernet account under Billing > Subscriptions. Cancellations take effect at the end of your current billing period.'
        ],
        [
            'category' => 'payment',
            'question' => 'What should I do if a payment is not received on time?',
            'answer' => 'If a payment isn\'t received on time, first check your payment confirmation email for the transaction ID. Log into your account to verify the payment status. If the issue persists, contact our support team immediately with your transaction details. We\'ll investigate and resolve payment delays within 24 hours. Late payments may affect your account standing, so prompt reporting is important.'
        ],
        
        // Membership FAQs
        [
            'category' => 'membership',
            'question' => 'What is Dealernet\'s Terms of Service (TOS)?',
            'answer' => 'Dealernet\'s Terms of Service outline the rules and regulations for using our platform. Key points include: maintaining accurate account information, conducting honest transactions, respecting intellectual property rights, and adhering to our code of conduct. The complete TOS is available in your account dashboard and is updated periodically to ensure compliance with industry regulations.'
        ],
        [
            'category' => 'membership',
            'question' => 'What are the rules/metric scores required for members?',
            'answer' => 'Members must maintain minimum metric scores: 95% positive feedback rating, less than 2% transaction dispute rate, 98% on-time payment rate, and active account usage (at least one transaction per quarter). Members falling below these thresholds receive warnings and support to improve. Continued non-compliance may result in account restrictions or suspension.'
        ],
        [
            'category' => 'membership',
            'question' => 'What are the different membership levels?',
            'answer' => 'Dealernet offers four membership levels: Basic (free) - limited to 10 transactions/month; Silver ($29/month) - 50 transactions, basic analytics; Gold ($99/month) - unlimited transactions, advanced analytics, priority support; Platinum ($299/month) - all Gold features plus API access, dedicated account manager, and custom integrations. Each level includes progressive benefits and tools.'
        ],
        [
            'category' => 'membership',
            'question' => 'What is elite (gold star) status?',
            'answer' => 'Elite (gold star) status is awarded to top-performing members who maintain 99%+ positive feedback, complete 100+ successful transactions annually, maintain zero disputes for 12 months, and demonstrate exceptional community engagement. Elite members receive exclusive benefits including: reduced fees, early access to new features, invitation-only events, and enhanced visibility in search results.'
        ],
        [
            'category' => 'membership',
            'question' => 'How are my vendor fees charged?',
            'answer' => 'Vendor fees are automatically calculated and charged based on your membership level and transaction volume. Fees are deducted from your account balance after each successful sale, or billed monthly if you prefer consolidated billing. Fee structure: Basic members pay 5% per transaction, Silver 3.5%, Gold 2.5%, and Platinum 1.5%. Volume discounts apply for transactions exceeding $50,000/month.'
        ],
        
        // Shipping & Orders FAQs
        [
            'category' => 'shipping',
            'question' => 'What is the difference between boxes and sealed cases?',
            'answer' => 'Boxes are individual product units that have been opened or are sold separately, allowing for single-item purchases and inspection before shipping. Sealed cases are manufacturer-sealed bulk packages containing multiple units (typically 6-24 items) that remain unopened until delivery. Sealed cases often offer better per-unit pricing but require purchasing in larger quantities.'
        ],
        [
            'category' => 'shipping',
            'question' => 'How does shipping work?',
            'answer' => 'Shipping is handled through our integrated logistics network. After order confirmation, items are packaged within 24 hours and shipped via your selected carrier (USPS, UPS, FedEx, or freight for large orders). You\'ll receive tracking information immediately upon shipment. Standard shipping takes 3-5 business days, expedited 1-2 days. We offer free shipping on orders over $500 for Gold and Platinum members.'
        ],
        [
            'category' => 'shipping',
            'question' => 'What should I do if I have an issue with my order?',
            'answer' => 'For order issues, immediately document the problem with photos if applicable. Log into your account and open a support ticket through the Order History section. Include your order number, description of the issue, and any supporting documentation. Our resolution team responds within 4 hours during business days. Most issues are resolved within 24-48 hours, with compensation or replacement provided when appropriate.'
        ],
        [
            'category' => 'shipping',
            'question' => 'Are transactions covered by Dealernet?',
            'answer' => 'Yes, all transactions processed through Dealernet are covered by our Buyer Protection Program. This includes: full refund for items not received, replacement for damaged items, mediation for items not as described, and fraud protection up to $10,000 per transaction. Coverage is automatic for all members and claims must be filed within 30 days of delivery.'
        ],
        [
            'category' => 'shipping',
            'question' => 'Where can I find my offers?',
            'answer' => 'All your offers are located in the "My Offers" section of your dashboard. Active offers are displayed at the top with status indicators (pending, accepted, declined, counter-offer). You can filter offers by date, status, or product category. Email notifications are sent for new offers, and push notifications are available through our mobile app. Offers expire after 48 hours unless otherwise specified.'
        ],
        
        // Buying & Selling FAQs
        [
            'category' => 'buying',
            'question' => 'How do I buy and sell at listed prices?',
            'answer' => 'To buy at listed prices, simply click "Buy Now" on any product page and complete checkout. For selling, list your items with a fixed price using the "Sell" button in your dashboard. Set your price, add product details and photos, then publish. Listed prices are firm and result in immediate transactions when accepted. You can also enable "Auto-Accept" for instant sales at your listed price.'
        ],
        [
            'category' => 'buying',
            'question' => 'How are prices set?',
            'answer' => 'Prices are determined by market dynamics and seller discretion. Our pricing algorithm suggests optimal prices based on: recent sale history, current market demand, competitor pricing, condition and rarity, and seasonal trends. Sellers can choose to use suggested pricing or set custom prices. The platform displays market averages and price history to help inform pricing decisions.'
        ],
        [
            'category' => 'buying',
            'question' => 'How do I set and negotiate prices?',
            'answer' => 'Set initial prices in your listing using our pricing tool or manually. Enable "Accept Offers" to allow negotiations. Buyers can submit offers below your asking price, which you can accept, decline, or counter. Use the messaging system to negotiate terms. Set your minimum acceptable price to auto-decline low offers. Best practice: price 5-10% above your target to leave room for negotiation.'
        ],
        [
            'category' => 'buying',
            'question' => 'How do I submit offers?',
            'answer' => 'To submit an offer, click "Make Offer" on any listing that accepts negotiations. Enter your offer amount, optional message to the seller, and offer expiration time (24-72 hours). You can include terms like bulk purchase discounts or shipping arrangements. Track all your submitted offers in the "My Offers" section. You can withdraw offers before seller response or modify them if countered.'
        ],
        [
            'category' => 'buying',
            'question' => 'What are expired offers?',
            'answer' => 'Expired offers are proposals that exceeded their validity period without acceptance. Standard offers expire after 48 hours, though custom durations (24-72 hours) can be set. Expired offers automatically void and cannot be accepted. Sellers can request offer renewal, or buyers can resubmit updated offers. Expired offer history remains visible for reference but doesn\'t affect your metrics or standing.'
        ],
        [
            'category' => 'buying',
            'question' => 'How do I add a product to the database?',
            'answer' => 'To add a new product, click "Add Product" in your seller dashboard. Provide required information: product name, category, manufacturer, model/SKU, detailed description, and high-quality photos. New products undergo quick verification (usually within 2 hours) to prevent duplicates and ensure accuracy. Once approved, your product becomes searchable and you can create listings. Frequent contributors earn "Product Pioneer" badges and fee discounts.'
        ]
    ];

    $html = '
    <!-- Main Content -->
    <div class="container">
        <!-- Hero Section -->
        <div class="hero">
            <h1>Frequently Asked Questions</h1>
            <p>Find answers to common questions about our services and solutions</p>
        </div>

        <!-- Search Bar -->
        <div class="search-container">
            <input type="text" class="search-bar" id="searchBar" placeholder="Search FAQs...">
            <span class="search-icon">🔍</span>
        </div>

        <!-- Category Filter -->
        <div class="category-filter">
            <button class="category-btn active" data-category="all">All</button>
            <button class="category-btn" data-category="payment">Payment</button>
            <button class="category-btn" data-category="membership">Membership</button>
            <button class="category-btn" data-category="shipping">Shipping & Orders</button>
            <button class="category-btn" data-category="buying">Buying & Selling</button>
        </div>

        <!-- FAQ List -->
        <div class="faq-list" id="faqList">';

    // Display FAQs
    foreach ($faqs as $faq) {
        $html .= '<div class="faq-item" data-category="' . htmlspecialchars($faq['category']) . '">';
        $html .= '<div class="faq-question">';
        $html .= '<span>' . htmlspecialchars($faq['question']) . '</span>';
        $html .= '<span class="faq-icon">+</span>';
        $html .= '</div>';
        $html .= '<div class="faq-answer">' . htmlspecialchars($faq['answer']) . '</div>';
        $html .= '</div>';
    }

    $html .= '
        </div>

        <!-- Contact Section -->
        <div class="contact-section">
            <h2>Still Have Questions?</h2>
            <p>Our support team is here to help you with any questions or concerns</p>
            <a href="contact.php" class="contact-btn">Contact Support</a>
        </div>
    </div>';

    return $html;
}

function getFAQScripts() {
    return '<script>
        // FAQ Accordion
        const faqItems = document.querySelectorAll(".faq-item");
        
        faqItems.forEach(item => {
            const question = item.querySelector(".faq-question");
            
            question.addEventListener("click", () => {
                // Close other items
                faqItems.forEach(otherItem => {
                    if (otherItem !== item && otherItem.classList.contains("active")) {
                        otherItem.classList.remove("active");
                    }
                });
                
                // Toggle current item
                item.classList.toggle("active");
            });
        });

        // Category Filter
        const categoryBtns = document.querySelectorAll(".category-btn");
        const allFaqItems = document.querySelectorAll(".faq-item");

        categoryBtns.forEach(btn => {
            btn.addEventListener("click", () => {
                // Update active button
                categoryBtns.forEach(b => b.classList.remove("active"));
                btn.classList.add("active");
                
                // Filter items
                const category = btn.dataset.category;
                
                allFaqItems.forEach(item => {
                    if (category === "all" || item.dataset.category === category) {
                        item.style.display = "block";
                        item.style.animation = "fadeInUp 0.5s ease";
                    } else {
                        item.style.display = "none";
                    }
                });
            });
        });

        // Search Functionality
        const searchBar = document.getElementById("searchBar");
        
        searchBar.addEventListener("input", (e) => {
            const searchTerm = e.target.value.toLowerCase();
            
            allFaqItems.forEach(item => {
                const question = item.querySelector(".faq-question span").textContent.toLowerCase();
                const answer = item.querySelector(".faq-answer").textContent.toLowerCase();
                
                if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                    item.style.display = "block";
                    item.style.animation = "fadeInUp 0.5s ease";
                    
                    // Highlight search term
                    if (searchTerm.length > 2) {
                        item.classList.add("highlight");
                    }
                } else {
                    item.style.display = "none";
                }
                
                if (searchTerm === "") {
                    item.classList.remove("highlight");
                    item.style.display = "block";
                }
            });
        });

        // Smooth scroll for anchor links
        document.querySelectorAll("a[href^=\'#\']").forEach(anchor => {
            anchor.addEventListener("click", function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute("href"));
                if (target) {
                    target.scrollIntoView({ behavior: "smooth", block: "start" });
                }
            });
        });

        // Add parallax effect on scroll
        window.addEventListener("scroll", () => {
            const scrolled = window.pageYOffset;
            const parallax = document.querySelector(".hero");
            if (parallax) {
                parallax.style.transform = `translateY(${scrolled * 0.3}px)`;
            }
        });
    </script>';
}
?>
