<?php
require_once('templateCommon.class.php');

$page = new templateCommon(NOLOGIN, SHOWMSG);
$page->requireJS("/scripts/formValidation.js");
$page->requireJS("https://www.google.com/recaptcha/api.js");

$firstname      = optional_param('firstname', NULL, PARAM_RAW);
$lastname       = optional_param('lastname', NULL, PARAM_RAW);
$companyname    = optional_param('companyname', NULL, PARAM_RAW);
$street         = optional_param('street', NULL, PARAM_RAW);
$street2        = optional_param('street2', NULL, PARAM_RAW);
$city           = optional_param('city', NULL, PARAM_RAW);
$state          = optional_param('state', NULL, PARAM_RAW);
$zip            = optional_param('zip', NULL, PARAM_RAW);
$country        = optional_param('country', NULL, PARAM_RAW);
$phone          = optional_param('phone', NULL, PARAM_RAW);
$email          = optional_param('email', NULL, PARAM_RAW);
$password       = optional_param('password', NULL, PARAM_RAW);
$hint           = optional_param('hint', "x", PARAM_RAW);
$answer         = optional_param('answer', "x", PARAM_RAW);
$referral       = optional_param('referral', NULL, PARAM_RAW);
$ebayid         = optional_param('ebayid', NULL, PARAM_RAW);
$paypalid       = optional_param('paypalid', NULL, PARAM_RAW);
$submitbtn      = optional_param('submitbtn', NULL, PARAM_RAW);

$honeypot       = optional_param('dow', NULL, PARAM_RAW);
$recaptcha      = optional_param('g-recaptcha-response', NULL, PARAM_RAW);

if (!empty($recaptcha) && empty($honeypot)) {
    if (!empty($submitbtn)) {
        if (!doesEmailExist($email)) {
            if (!empty($firstname) && !empty($lastname) && !empty($street) && !empty($city) &&
                !empty($state) && !empty($state) && !empty($zip) && !empty($country) &&
                !empty($phone) && !empty($email) && !empty($password) && !empty($hint) && !empty($answer)) {
                createUser($firstname, $lastname, $companyname, $street, $street2, $city, $state, $zip,
                           $country, $phone, $email, $password, $hint, $answer, $referral, $ebayid, $paypalid);
            } else {
                $page->messages->addErrorMsg("ERROR: Missing fields. Please try agian or contact Dealernet admin for assistance.");
            }
        } else {
            $url  = "/contactus_nologin.php";
            $link = "<a href='".$url."'>contact Dealernet Admin</a>";
            $page->messages->addErrorMsg("ERROR: User with this email already exists. Please ".$link." for assistance.");
            $subject = "New User Registration - Possible Dup / eMail Exists";
            sendInternalMsg(NULL, $subject);
        }
    }
} else {
    $page->messages->addWarningMsg("You need to complete the recaptcha to register for access.");
}

$js = "
const toggleRegistrationPassword = document.querySelector('#toggleRegistrationPassword');
const registrationPassword = document.querySelector('#id_registrationpassword');

if (toggleRegistrationPassword) {
    toggleRegistrationPassword.addEventListener('click', function (e) {
        // toggle the type attribute
        const type = registrationPassword.getAttribute('type') === 'password' ? 'text' : 'password';
        registrationPassword.setAttribute('type', type);
        // toggle the eye slash icon
        this.classList.toggle('fa-eye-slash');
    });
}
";
$page->jsInit($js);

// Output buffer to capture and modify the header
ob_start();
echo $page->header('DealernetX - Register');
$headerContent = ob_get_clean();

// Remove everything before </head> and add our clean structure
$headPos = strpos($headerContent, '</head>');
if ($headPos !== false) {
    echo substr($headerContent, 0, $headPos);
}

echo getModernStyles(); // Add modern styles
echo '</head><body class="modern-dealernetx">';
require_once('includes/header.php'); // Include navigation from includes folder
echo mainContent();
require_once('includes/footer.php'); // Include footer + scripts from includes folder

function mainContent() {
    global $CFG;

    $html = '
    <!-- Main Content Wrapper -->
    <div class="main-content-wrapper">
        <div class="container">
            <div class="registration-container">
                <div class="registration-header">
                    <h1>Create Your Account</h1>
                    <p>Join thousands of collectors and dealers on the most trusted platform since 2001</p>
                </div>
                
                <div class="spam-notice">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Important Email Notice</strong>
                        <p>Some emails from DealernetX may be marked as spam. Please add <strong>dealernetx.com</strong> to your safe senders list and add <strong>admin@dealernetx.com</strong> to your address book.</p>
                    </div>
                </div>

                <form name="RegisterForm" action="register.php" onsubmit="return VerifyFields(this)" method="POST" class="modern-form">
                    <div class="form-sections">
                        <!-- Personal Information Section -->
                        <div class="form-section">
                            <h2 class="section-title">Personal Information</h2>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="firstname">First Name <span class="required">*</span></label>
                                    <input type="text" id="firstname" name="firstname" maxlength="30" required>
                                </div>
                                <div class="form-group">
                                    <label for="lastname">Last Name <span class="required">*</span></label>
                                    <input type="text" id="lastname" name="lastname" maxlength="30" required>
                                </div>
                                <div class="form-group full-width">
                                    <label for="companyname">Company Name</label>
                                    <input type="text" id="companyname" name="companyname" maxlength="30">
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information Section -->
                        <div class="form-section">
                            <h2 class="section-title">Contact Information</h2>
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label for="street">Address <span class="required">*</span></label>
                                    <input type="text" id="street" name="street" maxlength="30" required>
                                </div>
                                <div class="form-group full-width">
                                    <label for="street2">Address Line 2</label>
                                    <input type="text" id="street2" name="street2" maxlength="30">
                                </div>
                                <div class="form-group">
                                    <label for="city">City <span class="required">*</span></label>
                                    <input type="text" id="city" name="city" maxlength="30" required>
                                </div>
                                <div class="form-group small">
                                    <label for="state">State <span class="required">*</span></label>
                                    <input type="text" id="state" name="state" maxlength="2" required>
                                </div>
                                <div class="form-group">
                                    <label for="zip">Zip Code <span class="required">*</span></label>
                                    <input type="text" id="zip" name="zip" maxlength="10" required>
                                </div>
                                <div class="form-group">
                                    <label for="country">Country <span class="required">*</span></label>
                                    <input type="text" id="country" name="country" maxlength="30" required>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Mobile Phone <span class="required">*</span></label>
                                    <input type="text" id="phone" name="phone" maxlength="15" required>
                                </div>
                            </div>
                        </div>

                        <!-- Account Information Section -->
                        <div class="form-section">
                            <h2 class="section-title">Account Information</h2>
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label for="email">Email Address (Username) <span class="required">*</span></label>
                                    <input type="email" id="email" name="email" maxlength="100" required>
                                </div>
                                <div class="form-group full-width">
                                    <label for="id_registrationpassword">Password <span class="required">*</span></label>
                                    <div class="password-input-wrapper">
                                        <input type="password" id="id_registrationpassword" name="password" maxlength="100" required>
                                        <i class="far fa-eye" id="toggleRegistrationPassword"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information Section -->
                        <div class="form-section">
                            <h2 class="section-title">Additional Information</h2>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="ein">EIN / Tax ID #</label>
                                    <input type="text" id="ein" name="ein" maxlength="11">
                                </div>
                                <div class="form-group">
                                    <label for="referral">Referral <span class="required">*</span></label>
                                    <input type="text" id="referral" name="referral" maxlength="30" required>
                                    <span class="field-hint">If none, enter N/A</span>
                                </div>
                                <div class="form-group">
                                    <label for="ebayid">eBay ID</label>
                                    <input type="text" id="ebayid" name="ebayid" maxlength="30">
                                </div>
                                <div class="form-group">
                                    <label for="paypalid">PayPal ID</label>
                                    <input type="text" id="paypalid" name="paypalid" maxlength="30">
                                </div>
                            </div>
                        </div>
                    </div>';

    if (isset($CFG->reCAPTCHA_SiteKey)) {
        $html .= '
                    <div class="recaptcha-wrapper">
                        <div class="g-recaptcha" data-sitekey="'.$CFG->reCAPTCHA_SiteKey.'"></div>
                    </div>';
    }

    $html .= '
                    <!-- Honeypot field for bot protection -->
                    <input type="text" name="dow" id="dow" class="ohnohoney">
                    
                    <div class="form-footer">
                        <p class="required-note"><span class="required">*</span> Required fields</p>
                        <button type="submit" name="submitbtn" id="submitbtn" class="btn-submit">
                            Create Account
                        </button>
                        <p class="terms-note">By creating an account, you agree to our Terms and Conditions of Use</p>
                    </div>
                </form>
            </div>
        </div>
    </div>';

    // Include the original JavaScript validation
    $html .= '
    <script type="text/javascript">
    function VerifyFields(f) {
        var a = [
            [/^firstname$/,   "First Name",      "text",    true,   50],
            [/^lastname$/,    "Last Name",       "text",    true,   50],
            [/^companyname$/, "Company Name",    "text",    false,  30],
            [/^street$/,      "Address",         "text",    true,   100],
            [/^street2$/,     "Address 2",       "text",    false,  100],
            [/^city$/,        "City",            "text",    true,   50],
            [/^state$/,       "State",           "text",    true,   2],
            [/^zip$/,         "Zip Code",        "text",    true,   10],
            [/^country$/,     "Country",         "text",    true,   30],
            [/^phone$/,       "Phone",           "text",    true,   20],
            [/^email$/,       "E-mail address",  "email",   true,   100],
            [/^password$/,    "Password",        "text",    true,   100],
            [/^ein$/,         "EIN / Tax ID #",  "text",    false,  11],
            [/^referral$/,    "Referral",        "text",    true,   100]
        ];

        var m = "";
        for (i = 0; i < f.elements.length; i++) {
            for (j = 0; j < a.length; j++) {
                if (f.elements[i].name.match(a[j][0])) {
                    m = m + VerifyTextField(f.elements[i], a[j][1], a[j][2], a[j][3], a[j][4]);
                    break;
                }
            }
        }

        if (m != "") {
            alert("The following fields contain values that are not permitted or are missing values:\\n\\n" + m);
            return false;
        } else {
            return true;
        }
    }
    </script>';

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
            --dark: #0F172A;
            --gray: #64748B;
            --light: #F8FAFC;
            --gradient: linear-gradient(135deg, #0066FF 0%, #003D99 100%);
            --navy: #001F3F;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background: var(--light);
            overflow-x: hidden;
        }

        /* Hide original template elements */
        .original-header, .original-nav { display: none; }
        body > table, body > center { display: none !important; }
        #header, .header, #navigation, .navigation { display: none !important; }
        #leftbar, #rightbar, .leftbar, .rightbar { display: none !important; }
        #sidebar, .sidebar, aside { display: none !important; }
        body > table[width="100%"] { display: none !important; }

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
            padding: 3rem 0;
            min-height: calc(100vh - 80px);
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Registration Container */
        .registration-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .registration-header {
            background: var(--gradient);
            color: white;
            padding: 3rem;
            text-align: center;
        }

        .registration-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .registration-header p {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        /* Spam Notice */
        .spam-notice {
            background: #FFF3CD;
            border: 1px solid #FFC107;
            border-radius: 8px;
            padding: 1rem 1.5rem;
            margin: 2rem 3rem;
            display: flex;
            gap: 1rem;
            align-items: start;
        }

        .spam-notice i {
            color: #F59E0B;
            font-size: 1.25rem;
            margin-top: 0.25rem;
        }

        .spam-notice strong {
            display: block;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .spam-notice p {
            color: #856404;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        /* Modern Form */
        .modern-form {
            padding: 0 3rem 3rem;
        }

        .form-sections {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .form-section {
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 2rem;
        }

        .form-section:last-child {
            border-bottom: none;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title::before {
            content: "";
            display: inline-block;
            width: 4px;
            height: 24px;
            background: var(--gradient);
            border-radius: 2px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group.small {
            grid-column: span 1;
            max-width: 100px;
        }

        .form-group label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-group input {
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.1);
        }

        .form-group input:hover {
            border-color: #cbd5e1;
        }

        .required {
            color: var(--danger);
            font-weight: normal;
        }

        .field-hint {
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 0.25rem;
        }

        /* Password Input Wrapper */
        .password-input-wrapper {
            position: relative;
        }

        .password-input-wrapper input {
            padding-right: 3rem;
            width: 100%;
        }

        .password-input-wrapper i {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--gray);
            transition: color 0.3s ease;
        }

        .password-input-wrapper i:hover {
            color: var(--primary);
        }

        /* reCAPTCHA Wrapper */
        .recaptcha-wrapper {
            display: flex;
            justify-content: center;
            margin: 2rem 0;
        }

        /* Honeypot Field */
        .ohnohoney {
            position: absolute;
            left: -9999px;
            width: 1px;
            height: 1px;
            overflow: hidden;
        }

        /* Form Footer */
        .form-footer {
            margin-top: 2rem;
            text-align: center;
        }

        .required-note {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 1.5rem;
        }

        .btn-submit {
            background: var(--gradient);
            color: white;
            padding: 1rem 3rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 102, 255, 0.2);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 102, 255, 0.3);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .terms-note {
            margin-top: 1rem;
            font-size: 0.9rem;
            color: var(--gray);
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

        /* Success/Error Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin: 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #D1FAE5;
            border: 1px solid #10B981;
            color: #065F46;
        }

        .alert-error {
            background: #FEE2E2;
            border: 1px solid #EF4444;
            color: #991B1B;
        }

        .alert-warning {
            background: #FEF3C7;
            border: 1px solid #F59E0B;
            color: #92400E;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .container {
                padding: 0 1rem;
            }

            .registration-header {
                padding: 2rem 1.5rem;
            }

            .registration-header h1 {
                font-size: 2rem;
            }

            .modern-form {
                padding: 0 1.5rem 2rem;
            }

            .spam-notice {
                margin: 1.5rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.small {
                max-width: none;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }
    </style>';
}

// Keep all the existing backend functions unchanged
function createUser($firstname, $lastname, $companyname, $street, $street2, $city, $state, $zip,
                    $country, $phone, $email, $password, $hint, $answer, $referral, $ebayid, $paypalid) {
    global $page;

    $success = false;
    $exists = $page->db->get_field_query("SELECT username FROM users WHERE lower(username) = lower('email')");
    if ($exists) {
        $page->messages->addErrorMsg("ERROR: User with this email already exists. Please contact Dealernet Admin for assistance.");
    } else {
        $addresstypes   = $page->db->sql_query("SELECT addresstypeid FROM addresstype");
        $new            = $page->db->get_field_query("SELECT userclassid FROM userclass WHERE lower(userclassname) = 'inactive'");
        $rights         = $page->db->sql_query("SELECT userrightid FROM userrights WHERE lower(userrightname) IN ('enabled', 'eft enabled')");
        $preferences    = $page->db->sql_query("SELECT preferenceid FROM userpreferences WHERE lower(preference) IN ('inbox blasts', 'auto-inactivate for sale', 'auto-inactivate wanted')");

        $userid = $page->utility->nextval('users_userid_seq');

        if (empty($new) || empty($userid) || empty($rights) || empty($preferences) || empty($addresstypes)) {
            $page->messages->addErrorMsg("ERROR: unable to get user class / right / preference / id. Please contact Dealernet admin for assistance.");
        } else {
            $sql = "
                INSERT INTO users( userid,  username,  userpass,  createdby)
                           VALUES(:userid, :username, crypt(:userpass, gen_salt('bf')), :createdby)
            ";

            $params = array();
            $params['userid']       = $userid;
            $params['username']     = $email;
            $params['userpass']     = strtoupper($password);
            $params['createdby']    = $email;

            $page->queries->AddQuery($sql, $params);
            unset($sql);
            unset($params);


            if (!empty($addresstypes)) {
                $sql = "
                    INSERT INTO usercontactinfo ( userid,  addresstypeid,  companyname,  street,  street2,  city,  state,  zip, country,  phone,  email,  createdby)
                                         values (:userid, :addresstypeid, :companyname, :street, :street2, :city, :state, :zip, :country, :phone, :email, :createdby)
                ";

                $params = array();
                $params['userid']           = $userid;
                $params['companyname']      = (empty($companyname)) ? null : $companyname;
                $params['street']           = $street;
                $params['street2']          = (empty($street2)) ? null : $street2;
                $params['city']             = $city;
                $params['state']            = $state;
                $params['zip']              = $zip;
                $params['country']          = $country;
                $params['phone']            = $phone;
                $params['email']            = $email;
                $params['createdby']        = $email;

                foreach($addresstypes as $at) {
                    $params['addresstypeid']    = $at["addresstypeid"];
                    $page->queries->AddQuery($sql, $params);
                }
                unset($sql);
                unset($params);
            }


            $sql = "
                INSERT INTO userinfo ( userid,  firstname,  userclassid,  lastname,  eintaxid,  ebayid,  paypalid, createdby)
                              values (:userid, :firstname, :userclassid, :lastname, :eintaxid, :ebayid, :paypalid, :createdby)
            ";

            $params = array();
            $params['userid']       = $userid;
            $params['firstname']    = $firstname;
            $params['lastname']     = $lastname;
            $params['userclassid']  = $new;
            $params['eintaxid']     = (empty($eintaxid)) ? null : $eintaxid;
            $params['ebayid']       = (empty($ebayid)) ? null : $ebayid;
            $params['paypalid']     = (empty($paypalid)) ? null : $paypalid;
            $params['createdby']    = $email;

            $page->queries->AddQuery($sql, $params);
            unset($sql);
            unset($params);

            $sql = "
                INSERT INTO transactions( crossrefid,  useraccountid,  refaccountid,  transtype, transstatus, transdate,  dgrossamount,  accountname,  transdesc, offerid,  createdby, createdate, modifiedby, modifydate)
                                  VALUES(:crossrefid, :useraccountid, :refaccountid, :transtype, :transstatus, :transdate, :dgrossamount, :accountname, :transdesc, :offerid, :createdby, :createdate, :modifiedby, :modifydate)
            ";
            $asOfDate = time();
            $asOfStr = date('m/d/Y H:i:s', $asOfDate);
            $params = array();
            $params['crossrefid']       = -1;
            $params['useraccountid']    = $userid;
            $params['refaccountid']     = NULL;
            $params['transtype']        = EFT_TRAN_TYPE_BALANCE;
            $params['transstatus']      = "ACCEPTED";
            $params['transdate']        = $asOfDate;
            $params['dgrossamount']     = 0.00;
            $params['accountname']      = $email;
            $params['transdesc']        = "Initial Balance as of ".$asOfStr;
            $params['offerid']          = NULL;
            $params['createdby']        = $email;
            $params['createdate']       = $asOfDate;
            $params['modifiedby']       = $email;
            $params['modifydate']       = $asOfDate;

            $page->queries->AddQuery($sql, $params);
            unset($sql);
            unset($params);

            $sql = "
                INSERT INTO assignedrights( userid,  userrightid,  createdby)
                                    values(:userid, :userrightid, :createdby)
            ";
            $params = array();
            $params['userid']       = $userid;
            $params['createdby']    = $email;

            foreach($rights as $r) {
                $params['userrightid']  = $r["userrightid"];
                $page->queries->AddQuery($sql, $params);
            }
            unset($sql);
            unset($params);


            $sql = "
                INSERT INTO assignedpreferences( userid,  preferenceid, value,  createdby)
                                    values(:userid, :preferenceid, :value, :createdby)
            ";
            $params = array();
            $params['userid']       = $userid;
            $params['createdby']    = $email;

            foreach($preferences as $r) {
                $params['preferenceid'] = $r["preferenceid"];
                $params['value']        = $r["preferenceid"];
                $page->queries->AddQuery($sql, $params);
            }
            unset($sql);
            unset($params);

            try {
                $page->db->sql_begin_trans();
                $page->queries->ProcessQueries();
                $page->messages->addSuccessMsg('You have created the user - '.$email);
                $success = true;
                sendInternalMsg($userid);
            } catch (Exception $e) {
                $page->db->sql_rollback_trans();
                $page->messages->addErrorMsg("ERROR: ".$e->getMessage()." [Unable to create user.]");
                $success = false;
            } finally {
                if ($success) {
                    $page->db->sql_commit_trans();
                }
            }
        }
    }

    return $success;
}

function sendInternalMsg($userid, $subject = "New User Registration") {
    global $page;

    $msg = "<div class='filters'>\n";
    foreach($_POST as $name=>$value) {
        if ($name == "dow") {
            $msg .= "  <label style='color:#D30000;'>".$name.":</label>&nbsp;&nbsp;".$value."<br/>\n";
        } else {
            $msg .= ($name == "g-recaptcha-response") ? "" : "  <label>".$name.":</label>&nbsp;&nbsp;".$value."<br/>\n";
        }
    }
    $msg .= "</div>\n";
    $msg2 = "<hr>\n";
    $msg2 .= "<div class='filters'>\n";
    $msg2 .= "  <label style='color:#D30000;'>*** If dow has a value then the form was not completed by a human. ***</label><br/>\n";
    $msg2 .= "  <label>Browser</label>&nbsp;&nbsp;".$_SERVER["HTTP_SEC_CH_UA"]."<br/>\n";
    $msg2 .= "  <label>Platform</label>&nbsp;&nbsp;".$_SERVER["HTTP_SEC_CH_UA_PLATFORM"]."<br/>\n";
    $msg2 .= "  <label>User Agent</label>&nbsp;&nbsp;".$_SERVER["HTTP_USER_AGENT"]."<br/>\n";
    $msg2 .= "  <label>IP Address</label>&nbsp;&nbsp;".$_SERVER["REMOTE_ADDR"]."<br/>\n";
    $msg2 .= "  <label>Network</label>&nbsp;&nbsp;".gethostbyaddr($_SERVER["REMOTE_ADDR"])."<br/>\n";
    $msg2 .= "  <label>Language</label>&nbsp;&nbsp;".$_SERVER["HTTP_ACCEPT_LANGUAGE"]."<br/>\n";
    if (isset($_POST["g-recaptcha-response"])) {
        $msg2 .= "  <label>g-recaptcha-response</label>&nbsp;&nbsp;".$_POST["g-recaptcha-response"]."<br/>\n";
    }
    $msg2 .= "</div>\n";

    $page->iMessage->insertSystemMessage($page, ADMINUSERID, ADMINUSERNAME, $subject, $msg.$msg2, EMAIL);
    if (!empty($userid)) {
        $subjectText = "DealernetX: ".$subject;
        $messageText = "<p>You have supplied the following information to DealernetX:</p>".$msg;
        $page->iMessage->sendExternalEmail($userid, $subjectText, $messageText);
    }
}

function doesEmailExist($email) {
    global $page;

    $sql = "
        SELECT case when strpos(lower(u.username), lower(:username)) > 0 then 1
                    when b.userid IS NOT NULL AND strpos(lower(b.email), lower(:billingemail)) > 0 then 1
                    when s.userid IS NOT NULL AND strpos(lower(s.email), lower(:shippingemail)) > 0 then 1
                    else 0 end as email_exists
          FROM users                    u
          LEFT JOIN usercontactinfo     b   ON  b.userid        = u.userid
                                            AND b.addresstypeid = 1
          LEFT JOIN usercontactinfo     s   ON  s.userid        = b.userid
                                            AND s.addresstypeid = 3
         WHERE strpos(lower(u.username), lower(:username2)) > 0
        ORDER BY 1 DESC
        LIMIT 1
    ";

    $params = array();
    $params["username"]         = strtolower($email);
    $params["username2"]        = strtolower($email);
    $params["billingemail"]     = strtolower($email);
    $params["shippingemail"]    = strtolower($email);

    $exists = $page->db->get_field_query($sql, $params);

    return $exists;
}
?>
