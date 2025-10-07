<?php
// Define site root for included files
define('SITE_ROOT', dirname(__FILE__));

// Page configuration
$pageTitle = 'DealernetX - Terms of Service';
$pageDescription = 'Terms of Service for DealernetX - Please read these terms carefully before using the platform.';
$lastUpdated = date('F d, Y');

// Include header
require_once('header.php');
?>

<div class="legal-page">
    <!-- Hero Section -->
    <section class="legal-hero">
        <div class="hero-container">
            <h1>Terms of Service</h1>
            <p>Please read these terms carefully before using the DealernetX platform.</p>
            <div class="last-updated">Last Updated: <?php echo $lastUpdated; ?></div>
        </div>
    </section>

    <!-- Terms Content -->
    <div class="legal-container">
        <div class="legal-content">
            <!-- Important Notice -->
            <div class="notice-card important">
                <div class="notice-icon">⚠️</div>
                <div class="notice-content">
                    <h3>Important Legal Notice</h3>
                    <p>By accessing or using DealernetX, you agree to be bound by these Terms of Service. If you do not agree to all terms, you must not use our services.</p>
                </div>
            </div>

            <!-- Table of Contents -->
            <div class="toc-card">
                <h3>Table of Contents</h3>
                <ul class="toc-list">
                    <li><a href="#acceptance">1. Acceptance of Terms</a></li>
                    <li><a href="#description">2. Description of Service</a></li>
                    <li><a href="#eligibility">3. Eligibility and Registration</a></li>
                    <li><a href="#membership">4. Membership and Fees</a></li>
                    <li><a href="#marketplace">5. Marketplace Rules</a></li>
                    <li><a href="#transactions">6. Transactions and Payments</a></li>
                    <li><a href="#conduct">7. User Conduct</a></li>
                    <li><a href="#content">8. User Content</a></li>
                    <li><a href="#intellectual">9. Intellectual Property</a></li>
                    <li><a href="#disputes">10. Dispute Resolution</a></li>
                    <li><a href="#liability">11. Limitation of Liability</a></li>
                    <li><a href="#indemnification">12. Indemnification</a></li>
                    <li><a href="#termination">13. Termination</a></li>
                    <li><a href="#general">14. General Provisions</a></li>
                </ul>
            </div>

            <!-- Section 1: Acceptance -->
            <section id="acceptance" class="policy-section">
                <h2>1. Acceptance of Terms</h2>
                
                <p>These Terms of Service ("Terms") constitute a legally binding agreement between you and Dealernet Inc. ("DealernetX," "we," "our," or "us") governing your use of the DealernetX platform, website, mobile applications, and related services (collectively, the "Services").</p>
                
                <p>By accessing or using our Services, you acknowledge that you have read, understood, and agree to be bound by these Terms and our Privacy Policy. If you do not agree to these Terms, you must not access or use our Services.</p>
                
                <p>We reserve the right to modify these Terms at any time. We will notify you of material changes by posting the updated Terms on our website and updating the "Last Updated" date. Your continued use of the Services after any changes constitutes acceptance of the modified Terms.</p>
            </section>

            <!-- Section 2: Description of Service -->
            <section id="description" class="policy-section">
                <h2>2. Description of Service</h2>
                
                <h3>2.1 Platform Role</h3>
                <p>DealernetX operates as a peer-to-peer marketplace that facilitates transactions between buyers and sellers of sealed sports cards, gaming cards, and collectibles. We provide the platform and tools for members to connect and trade, but we are not a party to transactions between users.</p>
                
                <h3>2.2 No Inventory or Direct Sales</h3>
                <p>We do not hold inventory, take possession of products, or act as a seller. All transactions occur directly between registered members. We are not responsible for the quality, safety, legality, or delivery of items traded on our platform.</p>
                
                <h3>2.3 Bid-Ask Trading System</h3>
                <p>Our platform uses a bid-ask trading model similar to stock markets, where buyers post bids and sellers post asks. When a bid and ask match or are accepted, a binding transaction is created between the parties.</p>
            </section>

            <!-- Section 3: Eligibility -->
            <section id="eligibility" class="policy-section">
                <h2>3. Eligibility and Registration</h2>
                
                <h3>3.1 Age Requirement</h3>
                <p>You must be at least 18 years old to use our Services. By registering, you represent and warrant that you meet this age requirement.</p>
                
                <h3>3.2 Account Registration</h3>
                <p>To access certain features, you must create an account by providing accurate, complete, and current information. You are responsible for:</p>
                <ul>
                    <li>Maintaining the confidentiality of your account credentials</li>
                    <li>All activities that occur under your account</li>
                    <li>Immediately notifying us of any unauthorized use</li>
                    <li>Ensuring your account information remains accurate and up-to-date</li>
                </ul>
                
                <h3>3.3 Identity Verification</h3>
                <p>For Above Standard and Elite membership tiers, we require identity verification. You agree to provide valid government-issued identification and authorize us to verify your identity through third-party services.</p>
                
                <h3>3.4 Account Restrictions</h3>
                <p>You may only maintain one account. Creating multiple accounts or using false information may result in immediate termination of all accounts.</p>
            </section>

            <!-- Section 4: Membership -->
            <section id="membership" class="policy-section">
                <h2>4. Membership and Fees</h2>
                
                <h3>4.1 Membership Tiers</h3>
                <p>We offer several membership tiers with different features and pricing:</p>
                
                <div class="tier-grid">
                    <div class="tier-card">
                        <h4>Basic ($19/month)</h4>
                        <ul>
                            <li>View marketplace prices</li>
                            <li>Trade at listed prices</li>
                            <li>No transaction fees for accepting prices</li>
                            <li>Cannot post own listings</li>
                        </ul>
                    </div>
                    <div class="tier-card">
                        <h4>Vendor ($35/month)</h4>
                        <ul>
                            <li>Post buy/sell listings</li>
                            <li>Negotiate prices</li>
                            <li>3% transaction fee on posted offers</li>
                            <li>Access to EFT credit system</li>
                        </ul>
                    </div>
                    <div class="tier-card">
                        <h4>Premium ($65/month)</h4>
                        <ul>
                            <li>All Vendor features</li>
                            <li>2% transaction fee</li>
                            <li>Market analytics tools</li>
                        </ul>
                    </div>
                    <div class="tier-card">
                        <h4>Executive ($249/month)</h4>
                        <ul>
                            <li>1% transaction fee</li>
                            <li>Full analytics suite</li>
                            <li>Custom advertising</li>
                        </ul>
                    </div>
                </div>
                
                <h3>4.2 Billing and Payments</h3>
                <p>Membership fees are billed monthly through PayPal. By subscribing, you authorize recurring monthly charges. You may cancel at any time, but must do so at least 3 days before your next billing date to avoid charges.</p>
                
                <h3>4.3 Transaction Fees</h3>
                <p>Transaction fees apply only when you:</p>
                <ul>
                    <li>Post a listing that gets accepted</li>
                    <li>Send or accept a counter-offer</li>
                </ul>
                <p>No fees apply when buying or selling at another member's posted price.</p>
                
                <h3>4.4 Refund Policy</h3>
                <p>Membership fees are non-refundable. Transaction fees are final once a trade is completed.</p>
            </section>

            <!-- Section 5: Marketplace Rules -->
            <section id="marketplace" class="policy-section">
                <h2>5. Marketplace Rules</h2>
                
                <h3>5.1 Permitted Products</h3>
                <p>Only sealed, unopened boxes and cases of the following are permitted:</p>
                <ul>
                    <li>Sports cards (Topps, Panini, Upper Deck, etc.)</li>
                    <li>Gaming cards (Pokémon, Yu-Gi-Oh!, Magic: The Gathering, etc.)</li>
                    <li>Authorized collectibles (Labubu, etc.)</li>
                </ul>
                
                <h3>5.2 Prohibited Items</h3>
                <p>The following are strictly prohibited:</p>
                <ul>
                    <li>Opened products or single cards</li>
                    <li>Counterfeit or replica items</li>
                    <li>Stolen goods</li>
                    <li>Items that violate any laws or regulations</li>
                </ul>
                
                <h3>5.3 Listing Requirements</h3>
                <p>All listings must:</p>
                <ul>
                    <li>Accurately describe the product</li>
                    <li>Include correct quantities</li>
                    <li>State clear pricing</li>
                    <li>Be for items you actually possess or can deliver</li>
                </ul>
                
                <h3>5.4 Binding Offers</h3>
                <p><strong>When you accept an offer or your offer is accepted, it creates a legally binding agreement to complete the transaction.</strong> Failure to complete agreed transactions may result in account penalties or termination.</p>
            </section>

            <!-- Section 6: Transactions -->
            <section id="transactions" class="policy-section">
                <h2>6. Transactions and Payments</h2>
                
                <h3>6.1 Payment Methods</h3>
                <p>Members arrange payments directly using their chosen methods, including:</p>
                <ul>
                    <li>PayPal</li>
                    <li>Credit/Debit Cards</li>
                    <li>Venmo</li>
                    <li>Bank transfers (ACH/Wire)</li>
                    <li>Zelle</li>
                    <li>DealernetX EFT Credits</li>
                    <li>Checks (expedited/tracked only)</li>
                </ul>
                
                <h3>6.2 Payment Terms</h3>
                <p>Standard payment terms require upfront payment before shipment. Elite members may pay upon receipt (due within 2 business days).</p>
                
                <h3>6.3 Third-Party Payment Processors</h3>
                <p>We do not process payments directly. When using third-party payment services, you are subject to their terms and policies. We are not responsible for their actions or security.</p>
                
                <h3>6.4 EFT Credit System</h3>
                <p>Our proprietary EFT credit system allows internal transfers between members. <strong>EFT transfers are IRREVOCABLE</strong> and cannot be reversed once sent.</p>
                
                <h3>6.5 Shipping Requirements</h3>
                <p>Sellers must:</p>
                <ul>
                    <li>Ship items within agreed timeframes</li>
                    <li>Provide valid tracking numbers</li>
                    <li>Use appropriate packaging to prevent damage</li>
                    <li>Ship to the address provided by the buyer</li>
                </ul>
            </section>

            <!-- Section 7: User Conduct -->
            <section id="conduct" class="policy-section">
                <h2>7. User Conduct</h2>
                
                <h3>7.1 Prohibited Conduct</h3>
                <p>You agree NOT to:</p>
                <ul>
                    <li>Provide false or misleading information</li>
                    <li>Manipulate prices or engage in fraudulent bidding</li>
                    <li>Harass, threaten, or abuse other users</li>
                    <li>Take transactions off-platform to avoid fees</li>
                    <li>Contact users outside the platform regarding listings</li>
                    <li>Violate any applicable laws or regulations</li>
                    <li>Use automated systems or bots</li>
                    <li>Attempt to circumvent security measures</li>
                    <li>Interfere with platform operations</li>
                    <li>Impersonate others or misrepresent affiliations</li>
                </ul>
                
                <h3>7.2 Performance Standards</h3>
                <p>Members must maintain minimum performance metrics:</p>
                
                <div class="standards-grid">
                    <div class="standard-card">
                        <h4>Elite (Gold Star) Requirements</h4>
                        <ul>
                            <li>95%+ positive ratings</li>
                            <li>95%+ tracking provided</li>
                            <li>&lt;10% cancellation rate</li>
                            <li>100+ lifetime transactions</li>
                            <li>ID verification completed</li>
                        </ul>
                    </div>
                    <div class="standard-card">
                        <h4>Above Standard (Blue Star)</h4>
                        <ul>
                            <li>85%+ positive ratings</li>
                            <li>85%+ tracking provided</li>
                            <li>&lt;20% cancellation rate</li>
                            <li>20+ lifetime transactions</li>
                            <li>ID verification required by Dec 31, 2025</li>
                        </ul>
                    </div>
                </div>
                
                <h3>7.3 Communication Standards</h3>
                <p>All communications must remain professional and on-platform. Sharing personal contact information for transaction purposes is prohibited.</p>
            </section>

            <!-- Section 8: User Content -->
            <section id="content" class="policy-section">
                <h2>8. User Content</h2>
                
                <h3>8.1 License Grant</h3>
                <p>By posting content on our platform, you grant DealernetX a worldwide, non-exclusive, royalty-free, perpetual, transferable license to use, reproduce, modify, distribute, and display such content in connection with operating and promoting our Services.</p>
                
                <h3>8.2 Content Responsibility</h3>
                <p>You are solely responsible for content you post and represent that:</p>
                <ul>
                    <li>You own or have rights to use the content</li>
                    <li>Your content does not infringe any third-party rights</li>
                    <li>Your content is accurate and not misleading</li>
                    <li>Your content complies with all applicable laws</li>
                </ul>
                
                <h3>8.3 Content Removal</h3>
                <p>We reserve the right to remove any content that violates these Terms or is otherwise objectionable, without notice and at our sole discretion.</p>
            </section>

            <!-- Section 9: Intellectual Property -->
            <section id="intellectual" class="policy-section">
                <h2>9. Intellectual Property</h2>
                
                <h3>9.1 Platform Ownership</h3>
                <p>All rights, title, and interest in the DealernetX platform, including all software, designs, text, graphics, and other content, are owned by Dealernet Inc. or our licensors.</p>
                
                <h3>9.2 Trademark Rights</h3>
                <p>"DealernetX" and our logos are trademarks of Dealernet Inc. You may not use our trademarks without written permission.</p>
                
                <h3>9.3 Copyright Infringement</h3>
                <p>We respect intellectual property rights. If you believe content on our platform infringes your copyright, please contact us with:</p>
                <ul>
                    <li>Description of the copyrighted work</li>
                    <li>Location of the infringing material</li>
                    <li>Your contact information</li>
                    <li>Statement of good faith belief</li>
                    <li>Statement of accuracy under penalty of perjury</li>
                </ul>
            </section>

            <!-- Section 10: Disputes -->
            <section id="disputes" class="policy-section">
                <h2>10. Dispute Resolution</h2>
                
                <h3>10.1 Member Disputes</h3>
                <p>DealernetX provides a platform for transactions but is not responsible for resolving disputes between members. We offer non-binding mediation assistance but make no determinations on dispute validity.</p>
                
                <h3>10.2 Dispute Procedures</h3>
                <p>For transaction issues:</p>
                <ul>
                    <li>Contact the other party within 48 hours of issue discovery</li>
                    <li>Attempt resolution through platform messaging</li>
                    <li>File an Offer Assistance report within 14 days if needed</li>
                    <li>Cooperate with any mediation efforts</li>
                </ul>
                
                <h3>10.3 Default Coverage</h3>
                <p>Elite members receive up to $10,000 in default coverage; Above Standard members receive up to $5,000. Coverage terms and claims are determined at our sole discretion.</p>
                
                <h3>10.4 Arbitration Agreement</h3>
                <p><strong>Any dispute with DealernetX shall be resolved through binding arbitration</strong> under the rules of the American Arbitration Association. You waive your right to jury trial and class actions.</p>
            </section>

            <!-- Section 11: Liability -->
            <section id="liability" class="policy-section">
                <h2>11. Limitation of Liability</h2>
                
                <h3>11.1 Service Disclaimer</h3>
                <p>THE SERVICES ARE PROVIDED "AS IS" WITHOUT WARRANTIES OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, OR NON-INFRINGEMENT.</p>
                
                <h3>11.2 Limitation of Damages</h3>
                <p>TO THE MAXIMUM EXTENT PERMITTED BY LAW, DEALERNETX SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, OR ANY LOSS OF PROFITS OR REVENUES.</p>
                
                <h3>11.3 Liability Cap</h3>
                <p>OUR TOTAL LIABILITY SHALL NOT EXCEED THE GREATER OF $100 OR THE AMOUNT YOU PAID US IN THE 12 MONTHS BEFORE THE EVENT GIVING RISE TO LIABILITY.</p>
                
                <h3>11.4 User Transactions</h3>
                <p>We are not liable for any issues arising from transactions between users, including but not limited to:</p>
                <ul>
                    <li>Item quality or authenticity</li>
                    <li>Delivery failures or delays</li>
                    <li>Payment disputes</li>
                    <li>Breach of agreements between users</li>
                </ul>
            </section>

            <!-- Section 12: Indemnification -->
            <section id="indemnification" class="policy-section">
                <h2>12. Indemnification</h2>
                
                <p>You agree to indemnify, defend, and hold harmless DealernetX, its officers, directors, employees, agents, and affiliates from any claims, damages, losses, liabilities, costs, and expenses (including attorney fees) arising from:</p>
                <ul>
                    <li>Your use of the Services</li>
                    <li>Your violation of these Terms</li>
                    <li>Your violation of any rights of another party</li>
                    <li>Your transactions with other users</li>
                    <li>Content you post on the platform</li>
                </ul>
            </section>

            <!-- Section 13: Termination -->
            <section id="termination" class="policy-section">
                <h2>13. Termination</h2>
                
                <h3>13.1 Termination by You</h3>
                <p>You may terminate your account at any time through your account settings. Termination does not relieve you of obligations for pending transactions.</p>
                
                <h3>13.2 Termination by Us</h3>
                <p>We may suspend or terminate your account immediately, without notice, for:</p>
                <ul>
                    <li>Violation of these Terms</li>
                    <li>Fraudulent or illegal activity</li>
                    <li>Repeated performance failures</li>
                    <li>Creating risk or legal exposure for us</li>
                    <li>Extended account inactivity</li>
                    <li>Any reason at our sole discretion</li>
                </ul>
                
                <h3>13.3 Effect of Termination</h3>
                <p>Upon termination:</p>
                <ul>
                    <li>Your access to the Services ends immediately</li>
                    <li>We may delete your account and content</li>
                    <li>You remain liable for completed transactions</li>
                    <li>Provisions that should survive termination will remain in effect</li>
                </ul>
            </section>

            <!-- Section 14: General -->
            <section id="general" class="policy-section">
                <h2>14. General Provisions</h2>
                
                <h3>14.1 Governing Law</h3>
                <p>These Terms are governed by the laws of the State of Florida, without regard to conflict of law principles.</p>
                
                <h3>14.2 Entire Agreement</h3>
                <p>These Terms and our Privacy Policy constitute the entire agreement between you and DealernetX regarding the Services.</p>
                
                <h3>14.3 Severability</h3>
                <p>If any provision is found unenforceable, the remaining provisions will continue in full effect.</p>
                
                <h3>14.4 Waiver</h3>
                <p>Our failure to enforce any provision does not waive our right to enforce it later.</p>
                
                <h3>14.5 Assignment</h3>
                <p>You may not assign these Terms. We may assign our rights and obligations without restriction.</p>
                
                <h3>14.6 Contact Information</h3>
                <div class="contact-info">
                    <p><strong>Dealernet Inc.</strong><br>
                    12226 Corporate Blvd Suite 142-338<br>
                    Orlando, FL 32817<br>
                    United States</p>
                    
                    <p><strong>Email:</strong> legal@dealernetx.com</p>
                </div>
            </section>

            <!-- Agreement Actions -->
            <div class="agreement-actions">
                <h3>By using DealernetX, you agree to these Terms of Service</h3>
                <div class="action-buttons">
                    <a href="/register.php" class="action-btn primary">
                        <span class="action-icon">✅</span>
                        I Agree - Create Account
                    </a>
                    <a href="/" class="action-btn secondary">
                        <span class="action-icon">←</span>
                        Return to Homepage
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once('footer.php');
?>
