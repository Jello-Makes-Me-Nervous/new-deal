<?php

require_once('setup.php');

define ("LOGIN",                    TRUE);
define ("NOLOGIN",                  FALSE);
define ("SHOWMSG",                  TRUE);
define ("NOSHOWMSG",                FALSE);
define ("REDIRECTSAFE",             TRUE);
define ("REDIRECT",                 FALSE);
define ("MAXMEGAMENUITEMS",         8);
define ("VERIFYPAYMENTS",           TRUE);
define ("NOVERIFYPAYMENTS",         FALSE);
define ("NOTIFICATIONPAGEVIEWS",    5);

class template {

    private $showmsgs = false;
    private $topNav = false;
    public $db;
    public $cfg;
    public $log;
    public $iMessage;
    public $messages;
    public $jsincludes = array();
    public $jsinit = array();
    public $stylesheets = array();
    public $pageStyles = array();
    public $queries;
    public $user;
    public $utility;
    public $display_BottomWidget = true;
    public $display_RightWidget = false;
    public $display_StandardLeftWidget = false;
    public $bypassMustReplyCheck = false;

    public $templateTimestamps= array();
    public $forceNoCache = false;
    public $showTimestamps = false;
    public $forcePaymentMethodVerification = false;
    public $hasExistingOffers = 0;
    public $allowBlockedIPs = false;

    public function __construct($loginRequired = true, $showmsgs = true, $bypassMustReplyCheck = false, $forcePaymentMethodVerification = false) {
        global $CFG, $DB, $MESSAGES, $USER, $UTILITY;

        $this->showTimestamps = optional_param('showtstamps', $CFG->SHOW_PAGE_TIMESTAMPS, PARAM_INT);

        $this->setTimestamp("Construct");

        $this->paramInit();
        $this->setTimestamp("paramInit");

        $UTILITY->checkBlockedIP();
        $this->setTimestamp("checkBlocked");

        $this->cfg                  = $CFG;
        $this->db                   = $DB;
        $this->messages             = $MESSAGES;
        $this->showmsgs             = $showmsgs;///
        $this->user                 = $USER;
        $this->utility              = $UTILITY;
        $this->log                  = new login();
        $this->queries              = new DBQueries("", $this->messages);
        $this->iMessage             = new internalMessage();
        $this->bypassMustReplyCheck = $bypassMustReplyCheck;
        $this->forcePaymentMethodVerification = $forcePaymentMethodVerification;
        $this->forceNoCache         = false;
        $this->setTimestamp("createObjects");

        /***
         * Check for blocked IPs
         ***/
         if (!$this->allowBlockedIPs) {
            $client  = @$_SERVER['HTTP_CLIENT_IP'];
            $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
            $remote  = @$_SERVER['REMOTE_ADDR'];

            if (filter_var($client, FILTER_VALIDATE_IP)) {
                $ip = $client;
            } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
                $ip = $forward;
            } else {
                $ip = $remote;
            }

            if ($this->isIPBlocked($ip)) {
                session_destroy();
                header('location:blockedip.php');
                exit();
            }
        }

        if ($loginRequired) {
            if (!$this->user->isLoggedIn()) {
                header('location:home.php?pgsmsg=Login%20required');
                exit();
            } else {
                if ($this->user->onvacation) {
                }
            }
            if ($this->user->isLoggedIn()      &&
                !$this->bypassMustReplyCheck  &&
                $this->iMessage->hasAdminMsgsRequiringReply($this->user->userId)) {
                header('location:mymessages.php');
                exit();
            }
            if ($this->user->isLoggedIn()) {
                if (isset($_SERVER['HTTP_USER_AGENT']) && (!empty(trim($_SERVER['HTTP_USER_AGENT'])))) {
                    $userAgent = $_SERVER['HTTP_USER_AGENT'];
                    if (!preg_match('/(Mozilla|Chrome|Safari|Firefox|Opera)/i', $userAgent) &&
                        preg_match('/(bot|spider|crawler|curl|wget|guzzle|scrapy|python|symfony|httpful)/i', $userAgent)) {
                        // Block or redirect or send msg

                    } elseif (preg_match('/(guzzle|scrapy|python|symfony|httpful)/i', $userAgent)) {
                        // Block or redirect or send msg

                    }
                } else {
                        // Block or redirect or send msg

                }
            }
        }
        if ($this->user->isLoggedIn()) {
            if ($this->forcePaymentMethodVerification) {
                if (! $this->verifyPaymentMethods()) {
                    header('Location:dealerProfile.php');
                    exit();
                //} else {
                //    $this->messages->addInfoMsg("Payment methods are valid.");
                }
            }
            $this->hasExistingOffers = $this->hasOffers();

            if (isset($_SESSION["notificationpageviews"]) && !empty($_SESSION["notificationpageviews"])) {
                $_SESSION["notificationpageviews"] --;
                $url  = "/activitiessincelastlogin.php";
                $link = "<a href='".$url."'>Click here</a>";
                $this->messages->addinfoMsg("You had activities since your last login on ".date("m/d/Y", $_SESSION['lastlogin']).". ".$link." to view them (My Mail > Activities).");

            }
        }
        $this->setTimestamp("checkLogin");

        $this->requireStyle("https://fonts.googleapis.com/css2?family=Alegreya+Sans&family=Roboto+Condensed&display=swap");
        $this->requireStyle("/fonts/css/all.css' type='text/css' media='all'");
        $this->requireStyle("/styles/style.css' type='text/css' media='all'");
        $this->requireStyle("/styles/jquery-ui.css' type='text/css' media='all'");
        $this->requireStyle("/styles/paginator.css' type='text/css' media='all'");

        $this->requireJS("/scripts/jquery-3.6.0.js");
        $this->requireJS("/scripts/jquery-ui-1.21.1.js");
        $this->requireJS("/scripts/batman.js");
        $this->requireJS("/scripts/tz.js");
        $this->requireJS("/scripts/template.js");

        if (!isset($_SESSION["pba_index"])) {
            $this->loadPageBottomAds2Session();
        }
        $this->setTimestamp("loadBottom Ads");

        if (!$this->user->isLoggedIn()) {
            $js = "
            const togglePassword = document.querySelector('#togglePassword');
            const password = document.querySelector('#id_password');

              togglePassword.addEventListener('click', function (e) {
                // toggle the type attribute
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                // toggle the eye slash icon
                this.classList.toggle('fa-eye-slash');
            });
            ";
            $this->jsInit($js);
        }
        $this->setTimestamp("jsInit");
    }

    public function setTimestamp($title) {
        $timestamp = time();
        $this->templateTimestamps[] = array($title => $timestamp);
    }

    public function dumpTimestamps() {
        $firstTime = NULL;
        $lastTime = NULL;
        $diff = 0;

        if ($this->showTimestamps) {
            $showListingEdit = false;
            $itemEditDisplayMode = ($showListingEdit) ? "" : "style='display:none'";
            $itemHideDisplayMode = ($showListingEdit) ? "style='display:none'" : "";
            echo "<a class='fas fa-plus' title='Show Timestamps' name='showiedit' id='showiedit' href='#' onclick=\"$(this).hide();$('#hideiedit').show();$('#templatetimestamps').show();return false;\" ".$itemHideDisplayMode."></a>";
            echo "<a class='fas fa-minus' title='Hide Timestamps' name='hideiedit' id='hideiedit' href='#' onclick=\"$(this).hide();$('#showiedit').show();;$('#templatetimestamps').hide();return false;\"  ".$itemEditDisplayMode."></a>";
            echo "<div id='templatetimestamps' name='templatetimestamps' ".$itemEditDisplayMode.">\n";
            foreach ($this->templateTimestamps as $item) {
                foreach ($item as $title => $timestamp) {
                    if (isset($lastTime)) {
                        $diff = $timestamp - $lastTime;
                    } else {
                        $diff = 0;
                        $firstTime = $timestamp;
                    }
                    echo $diff." to ".$title."<br />\n";
                    $lastTime = $timestamp;
                }
            }
            echo "From:".date('m-d-Y h:i:s',$firstTime)." To:".date('m-d-Y h:i:s',$lastTime)." Elapsed:".($lastTime-$firstTime)."<br />\n";
            echo "</div><br />\n";
        }
    }

    public function paramInit() {
//foreach ($_POST as $idx=>$p) {
//  echo "<br>".$idx.": ".$p;
//}
    }

    public function requireStyle($stylesheet) {
        $this->stylesheets[] = $stylesheet;
    }

    public function pageStyle($styleEntry) {
        $this->pageStyles[] = $styleEntry;
    }

    public function requireJS($js) {
        $this->jsincludes[] = $js;
    }

    public function jsInit($jsscript) {
        $this->jsinit[] = $jsscript;
    }

    public function header($title) {
        $this->setTimestamp("Start Header");

        echo "<!DOCTYPE html>\n";
        echo "<html lang='en-US'>\n";
        echo "  <head>\n";
        echo "    <meta charset='UTF-8'/>\n";
        echo "    <meta name='viewport' content='width=device-width,initial-scale=1.0' />\n";
        echo "    <meta name='google-site-verification' content='gNHjkfi5FZD3NXZcrj_YTGy4mKcgtM8JaX3rA7Jc71E' />\n";
        if ($this->forceNoCache) {
            echo "    <meta http-equiv='Cache-Control' content='no-cache, no-store, must-revalidate' />\n";
            echo "    <meta http-equiv='Pragma' content='no-cache' />\n";
            echo "    <meta http-equiv='Expires' content='0' />\n";
        }
        echo "    <title>".$title."</title>\n";
        foreach($this->stylesheets as $stylesheet) {
            echo "    <link rel='stylesheet' href='".$stylesheet."'>\n";
        }
        if (count($this->pageStyles) > 0) {
            echo "<style>\n";
            foreach($this->pageStyles as $styleEntry) {
                echo $styleEntry."\n";
            }
            echo "</style>\n";
        }
        foreach($this->jsincludes as $jsinclude) {
            echo "    <script src='".$jsinclude."'></script>\n";
        }
        echo "    <link rel='apple-touch-icon' sizes='180x180' href='/apple-touch-icon.png'>\n";
        echo "    <link rel='icon' type='image/png' sizes='32x32' href='/favicon-32x32.png'>\n";
        echo "    <link rel='icon' type=;image/png' sizes='16x16' href='/favicon-16x16.png'>\n";
        echo "    <link rel='manifest' href='/site.webmanifest'>\n";
        echo "  </head>\n";
        echo "  <body>\n";
        echo "    <div id='page-wrap'>\n";
        echo "      <div id='header' class='font-inherit'> <!-- HEADER -->\n";
        echo "        <div id='header-inside' class='block-inside'> <!-- HEADER-INSIDE -->\n";

        $this->displaySecondaryNav();

        $this->displayPrimaryNav();

        echo "        </div> <!-- header-inside class -->\n";
        echo "      </div> <!-- header class -->\n";
        echo "\n";
        echo "      <div id='container' class='container font-inherit'> <!-- CONTAINER -->\n";
        echo "        <div id='container-inside'> <!-- CONTAINER-INSIDE -->\n";
        $this->displayLeftWidget();
        if ($this->display_RightWidget) {
            echo "          <div id='content' role='main' class='secondary-sidebar font-inherit'> <!-- CONTENT -->\n";
        } else {
            if ($this->display_StandardLeftWidget) {
                echo "          <div id='content' role='main' class='standard-left-sidebar font-inherit'> <!-- CONTENT -->\n";
            } else {
                echo "          <div id='content' role='main' class='narrow-left-sidebar font-inherit'> <!-- CONTENT -->\n";
            }
        }

        if ($this->messages->hasMsgs() && $this->showmsgs) {
            echo $this->messages->displayMessages();
        }
        $this->setTimestamp("Done Header");
    }

    public function displaySecondaryNav() {
        global $USER, $UTILITY;
        echo "          <div id='nav-secondary'> <!-- NAV-SECONDARY--top of page -->\n";
        echo "            <div class='xs-font-size font-inherit'>\n";
        echo "              <ul id='menu-secondary' class='menu-items'>\n";

        if ($this->user->isLoggedIn()) {
            if ($this->user->isProxied()) {
                echo "                <li><a href='inProxy.php?unproxy=1' title='MemberID: ".$_SESSION['userId']." - Click to unproxy'>".$this->user->username."</a></li>\n";
            } else {
                echo "                <li><span style='font-weight:bold;' title='MemberID: ".$_SESSION['userId']."'>".$this->user->username."</span></li>\n";
            }
            echo "                <li><a href='logout.php'>Log Out</a></li>\n";
        }
        echo "                <li><a href='/faqs.php'>FAQs</a></li>\n";
        echo "                <li><a href='/membership.php'>Membership</a></li>\n";
        echo "                <li><a href='/aboutus.php'>About Us</a></li>\n";
        if ($this->user->isLoggedIn()) {
            echo "                <li><a href='/sendmessage.php?dept=1'>Help Desk</a></li>\n";
            echo "                <li><a href='/siteannouncements.php'>Site Announcements</a></li>\n";
        } else {
            echo "                <li><a href='/contactus_nologin.php'>Contact Us</a></li>\n";
            echo "                <li><a href='/register.php'>Register</a></li>\n";
        }
        echo "              </ul>\n";
        echo "            </div> <!-- XS font-->\n";
        echo "          </div> <!-- nav-secondary -->\n";
    }

    public function displayPrimaryNav() {

        if ($this->user->isLoggedIn()) {
            $this->displayPrimaryNav_loggedIn();
        } else {
            $this->displayPrimaryNav_notloggedIn();
        }

    }

    public function displayPrimaryNav_notloggedIn() {
        global $user;

        echo "          <div id='nav-primary' class='primary-menu'>\n";
        echo "            <div class='primary-menu-container not-logged-in font-inherit'>\n";
        echo "              <a class='custom-logo' href='home.php' alt='Site Home'><img src='images/dealernetX-logo-blue.png' alt='Logo'/></a>\n";
        echo "              <a href='javascript:void(0);' class='icon' onclick='responsiveMenu()'><i class='fa fa-bars'></i></a>\n";
        echo "              <div class='login'>\n";
        echo "                <form name = 'log' action='login.php' method='post'>\n";
        echo "                  <span class='username'>\n";
        echo "                    <label>Username:</label>\n";
        echo "                    <input type='text' name='userName' id='userName' value=''/>\n";
        echo "                  </span>\n";
        echo "                  <span class='password'>\n";
        echo "                    <label>Password:</label>\n";
        echo "                    <input type='password' class='password' name='userPass' id='id_password' value=''/>\n";
        echo "                    <i class='far fa-eye' id='togglePassword'></i>\n";
        echo "                  </span>\n";
        echo "                  <input type='hidden' name='tz' id='tz' value=''>\n";
        echo "                  <input class='logingo' type='submit' name='loginBtn' value='Go'>\n";
        echo "                </form>\n";
        echo "              </div> <!-- login -->\n";
        echo "              <div style='float left;padding-left:10px;'>\n";
        echo "                <a href='/register.php'>Register</a><br />\n";
        echo "                <a href='/resetpassword_sms.php'>Forgot Password</a>\n";
        echo "              </div>\n";
        echo "            </div> <!-- primarymenucontainer-->\n";
        echo "          </div> <!--nav primary-->\n";
    }

    public function displayPrimaryNav_loggedIn() {
        global $USER;

        echo "          <div id='nav-primary' class='primary-menu'> <!-- PRIMARY-MENU -->\n";
        echo "            <div class='primary-menu-container font-inherit'> <!-- PRIMARY-MENU-CONTAINER -->\n";
        echo "              <a class='custom-logo' href='home.php' alt='Site Home'><img src='images/dealernetX-logo-blue.png' alt='logo'/></a>\n";

        if ($this->user->hasUserRight("ADMIN")) {
            echo "                <a href='admin.php' class='single-menu-item'>Admin</a>\n";
        }

        // MARKETPLACE
        if ($this->user->canOffer()) {
            $mpitems = $this->getMarketplaceMenu();
            $category_types = array_count_values(array_column($mpitems, 'categorytypeid'));
            echo "              <div class='dropdown'>\n";
            echo "                <button class='dd-button'><a href='#'>Marketplace<i class='fas fa-caret-down' aria-hidden='true'></i></a></button>\n";
            echo "                <div class='dd-content'>\n";
            echo "                  <div class='dd_row'>\n";
            $ads = $this->getMarketplaceMenuAds();
            if ($ads) {
                echo "                    <div class='dd_column_large'>\n";
                $a = reset($ads);
                if (empty($a["url"])) {
                    $img = "<img class='align-center' src='".$this->utility->getPrefixAdvertImageURL($a["imagepath"])."'>";
                } else {
                    $img = "<a href='".$a["url"]."' target='_new'>";
                    $img .= "<img class='align-center' src='".$this->utility->getPrefixAdvertImageURL($a["imagepath"])."'>";
                    $img .= "</a>";
                }
                echo "                      ".$img;
                echo "                    </div>\n";
            }
            if (array_key_exists('1',$category_types) && ($category_types['1'] > MAXMEGAMENUITEMS))  {
                echo "                    <div class='dd_column two-column'>\n";
            } else {
                echo "                    <div class='dd_column'>\n";
            }
            echo "                      <h3><a href='/listings.php?listingtypeid=1'>Sports</a></h3>\n";
            foreach($mpitems as $i) {
                if ($i["categorytypeid"] == 1) {
                    $url = "/listings.php?listingtypeid=".$i["categorytypeid"]."&categoryid=".$i["categoryid"];
                    echo "                      <a href='".$url."'>".$i["categorydescription"]."</a>\n";
                }
            }
            echo "                    </div></a>\n";

            if (array_key_exists('2',$category_types) && ($category_types['2'] > MAXMEGAMENUITEMS))  {
                echo "                    <div class='dd_column two-column'>\n";
            } else {
                echo "                    <div class='dd_column'>\n";
            }
            echo "                      <h3><a href='/listings.php?listingtypeid=2&amp;categoryid=141'>Gaming</a></h3>\n";
            foreach($mpitems as $i) {
                if ($i["categorytypeid"] == 2) {
                    $url = "/listings.php?listingtypeid=".$i["categorytypeid"]."&categoryid=".$i["categoryid"];
                    echo "                      <a href='".$url."'>".$i["categorydescription"]."</a>\n";
                }
            }
            echo "                    </div>\n";

            if (array_key_exists('3',$category_types) && ($category_types['3'] > MAXMEGAMENUITEMS))  {
                echo "                    <div class='dd_column two-column'>\n";
            } else {
                echo "                    <div class='dd_column'>\n";
            }
            echo "                      <h3><a href='/supplies.php'>Supplies</a></h3>\n";
            foreach($mpitems as $i) {
                if ($i["categorytypeid"] == 3) {
                    $url = "/supplySummary.php?categoryid=".$i["categoryid"];
                    echo "                      <a href='".$url."'>".$i["categorydescription"]."</a>\n";
                }
            }
            echo "                    </div>\n";

            echo "                    <div class='dd_column'>\n";
            echo "                      <h3>Other</h3>\n";
            echo "                      <a href='/blasts.php'>Blasts</a>\n";
            echo "                      <a href='/marketsnapshot.php'>Market Snapshot</a>\n";
            echo "                      <a href='/hotlist_sports.php'>Market Hot List</a>\n";
            echo "                      <a href='/offerhistory.php'>Offer History</a>\n";
            echo "                      <a href='/priceguide.php'>Price Guide</a>\n";
            echo "                      <a href='/pricecomparison.php'>Price Comparison</a>\n";
            echo "                      <a href='/productcalendar.php'>Product Calendar</a>\n";
            echo "                      <a href='/activeBuys.php'>Active Buys</a>\n";
            echo "                      <a href='/activeSells.php'>Active Sells</a>\n";
            echo "                    </div>\n";
            echo "                  </div> <!-- row -->\n";
            echo "                </div> <!--dd-content -->\n";
            echo "              </div> <!--dropdown menu-->\n";
        }

        // MY MAIL
        echo "              <div class='submenu'>\n";
        if ($this->iMessage->hasUnreadMessages($USER->userId)) {
            echo "                <button class='subbtn'><a href='#'><i class='fa-solid fa-envelope fa-beat email-alert fa-xs'></i>My Mail <i class='fas fa-caret-down' aria-hidden='true'></i></a></button>\n";
        } else {
            echo "                <button class='subbtn'><a href='#'>My Mail <i class='fas fa-caret-down' aria-hidden='true'></i></a></button>\n";
        }
        echo "                <div class='submenu-content'>\n";
        echo "                  <a href='sendmessage.php'>Send Message</a>\n";
        echo "                  <a href='mymessages.php'>Inbox</a>\n";
        echo "                  <a href='mysentmessages.php'>Outbox</a>\n";
        echo "                  <a href='myb2barchive.php'>B2B Archive</a>\n";
        echo "                  <a href='activitiessincelastlogin.php'>Activities since</a>\n";
        echo "                </div> <!--submenu content-->\n";
        echo "              </div> <!--submenu-->\n";

        // ACCOUNT
        echo "              <div class='submenu'>\n";
        echo "                <button class='subbtn'><a href='#'>Account<i class='fas fa-caret-down' aria-hidden='true'></i></a></button>\n";
        echo "                <div class='submenu-content'>\n";
        echo "                  <a href='dealerProfile.php'>My Profile</a>\n";
        echo "                  <a href='myEFTaccount.php'>EFT Account Summary</a>\n";
        echo "                  <a href='myEFTaccountTotals.php'>EFT Totals Report</a>\n";
        if ($this->user->isVendor()) {
            echo "                  <a href='mylistingcats.php'>My Listings</a>\n";
            echo "                  <a href='priceAlerts.php'>My Price Alerts</a>\n";
        }
        if ($this->user->hasUserRight('Email Blast Limited') || $this->user->hasUserRight('Email Blast Unlimited')) {
            echo "                  <a href='blasts.php?dealerid=".$this->user->userId."'>My Blasts</a>\n";
        }
        echo "                  <a href='blockmember.php'>Block Members</a>\n";
        echo "                </div> <!--submenu-content-->\n";
        echo "              </div> <!--submenu-->\n";

        // OFFERS
        if ($this->hasExistingOffers) {
            echo "              <div class='submenu'>\n";
            echo "                <button class='subbtn'><a href='#'>Offers<i class='fas fa-caret-down' aria-hidden='true'></i></a></button>\n";
            echo "                <div class='submenu-content'>\n";
            echo "                  <a href='offers.php?offerfilter=SALES#results'>Sales - Last 14 Days</a>\n";
            echo "                  <a href='offers.php?offerfilter=SALESALL#results'>Sales - All</a>\n";
            echo "                  <a href='offers.php?offerfilter=PURCHASES#results'>Purchases - Last 14 Days</a>\n";
            echo "                  <a href='offers.php?offerfilter=PURCHASESALL#results' title='Include rated offers'>Purchases - All</a>\n";
            echo "                  <a href='offers.php?offerfilter=PENDINGIN#results'>Pending Incoming</a>\n";
            echo "                  <a href='offers.php?offerfilter=PENDINGOUT#results'>Pending Outgoing</a>\n";
            echo "                  <a href='offers.php?offerfilter=DECLINED#results'>Declined</a>\n";
            echo "                  <a href='offers.php?offerfilter=EXPIRED#results'>Expired</a>\n";
            echo "                  <a href='offers.php?offerfilter=CANCELLED#results'>Cancelled</a>\n";
            echo "                  <a href='offers.php?offerfilter=VOID#results'>Void</a>\n";
            echo "                </div> <!--submenu-content-->\n";
            echo "              </div> <!--submenu-->\n";
        }

        // RECENT ACTIVITY
        if ($this->user->canOffer()) {
            if ($cartCounts = $this->getCartCounts()) {
                if ($cartCounts['numincart'] > 0) {
                    echo "              <a href='/shoppingCart.php' class='single-menu-item' title='".$cartCounts['numwanted']." Sell To / ".$cartCounts['numforsale']." Buy From'><span class='fa-stack fa-sm'><i class='fas fa-shopping-cart fa-stack-2x'></i><i class='fas fa-stack-1x fa-sm fa-inverse shopping'>".$cartCounts['numincart']."</i></span></a>\n";
                }
            }
        }

        if ($this->hasExistingOffers) {
            if ($offers = $this->getOfferCounts()) {
                $display = false;
                echo "              <div style='display:inline-block; text-align: center;'> <!-- Offer links -->\n";
                foreach ($offers as $o) {
                    $style = ($o["status"] == "OTHER") ? "style='display:none;'" : "";
                    $label = $o["status"]."(".$o["cnt"].")";
                    echo "                <a href='/offers.php?offerfilter=".$o["statusfilter"]."#results' class='single-menu-item action-req' ".$style." title='Exclude rated offers'>".$label."</a>\n";
                    $display = ((!$display) && empty($style)) ? true : false;
                }
                if ($display) {
                    echo "                <br><span class='fa-sm' style='color: #AAA;font-weight:bold;white-space: nowrap;'>Review recent activity</span>\n";
                }
                echo "              </div> <!-- Offer links -->\n";
            }
        }

        if ($this->user->isStaff()) {
            echo "              <div class='submenu'>\n";
            echo "                <button class='subbtn'><a href='/dealerRolodex.php'>Rolodex <i class='fas fa-caret-down' aria-hidden='true'></i></a></button>\n";
            echo "                <div class='submenu-content'>\n";
            echo "                  <a href='/dealerRolodex.php'>Search</a>\n";
            echo "                  <a href='#'>Master Distributor</a>\n";
            echo "                  <a href='/dealerRolodex.php?onvacation=Yes'>Vacationers</a>\n";
            echo "                  <a href='#'>New Members</a>\n";
            echo "                  <a href='#'>Elite Members</a>\n";
            echo "                  <a href='#'>Manufacturers</a>\n";
            echo "                  <a href='#'>Store Fronts</a>\n";
            echo "                </div> <!--submenu-content-->\n";
            echo "              </div> <!--submenu-->\n";
        }

        if ($this->user->canOffer()) {
            echo "              <div style='float:right; display:inline-block; padding-right:5px; margin-top:5px;'> <!-- search -->\n";
            echo "                <form id='searchForm' method='POST' action='search.php'>\n";
            echo "                  <input type='search' value='' name='keywordsearch' id='keywordsearch' style='float:left; height:35px;width:250px;padding: 0 0 0 5px; border: 2px solid #3568b2; border-radius:10px 0 0 10px;' placeholder='Product search ...' >\n";
            $onclick = "$(\"#searchForm\").submit();";
            echo "                  <a style='height:35px;padding: 10px 10px 5px 10px; background-color:#3568b2; color:#DDD;border-top-right-radius: 25%;border-bottom-right-radius:25%;' onclick='".$onclick."'><i class='fa fa-search' aria-hidden='true'></i></a><br>\n";
            echo "                </form>\n";
            echo "              </div> <!-- search -->\n";
        }

        echo "              <a href='javascript:void(0);' class='icon' onclick='responsiveMenu()'><i class='fa fa-bars'></i></a>\n";

        echo "              <span class='menu-search'>&nbsp;</span>\n\n"; // Menu bottom border goes bad without this
        echo "            </div> <!-- primary-menu-container -->\n";
        echo "          </div> <!-- primary-menu -->\n";

    }

    public function displayLeftWidget() {
//        echo "           <div id='primary-widget' class='narrow-left-sidebar shadow font-roboto-condensed'><!-- PRIMARY WIDGET NARROW LEFT -->\n";
        echo "           <div id='primary-widget'><!-- PRIMARY WIDGET NARROW LEFT -->\n";
        echo "           </div><!-- primary-widget narrow left -->\n";
    }

    public function displayBottomWidget() {
        echo "            <div id='bottom-widget' class='align-center'> <!-- BOTTOM WIDGET -->\n";
        echo "              <aside>\n";
        if ($this->display_BottomWidget) {
            if (isset($_SESSION["pba_index"]) && isset($_SESSION["pba_maxindex"])) {
                if (empty($_SESSION["pba_url_".$_SESSION["pba_index"]])) {
                    echo "                <img src='".$this->utility->getPrefixAdvertImageURL($_SESSION["pba_".$_SESSION["pba_index"]])."' alt='page bottom ads' class='align-center'>\n";
                } else {
                    echo "                <a href='".$_SESSION["pba_url_".$_SESSION["pba_index"]]."' target='_new'>\n";
                    echo "                  <img src='".$this->utility->getPrefixAdvertImageURL($_SESSION["pba_".$_SESSION["pba_index"]])."' alt='page bottom ads' class='align-center'>\n";
                    echo "                </a>\n";
                }
                $_SESSION["pba_index"] = ($_SESSION["pba_index"] == $_SESSION["pba_maxindex"]) ? 1 : $_SESSION["pba_index"] + 1;
            }
        } else {
            echo "                <img src='/images/spacer.gif' style='height:1px; width:100%;' alt='spacer for width'>\n";
        }
        echo "              </aside>\n";
        echo "            </div> <!-- bottom-widget -->\n";
    }

    public function displayRightWidget() {

//        echo "          <div id='secondary-widget' class='standard-left-sidebar shadow font-roboto-condensed'> <!-- SECONDARY WIDGET right side -->\n";
        echo "          <div id='secondary-widget'> <!-- SECONDARY WIDGET right side -->\n";
        echo "          </div> <!-- secondary-widget right side -->\n";

    }

    public function footer() {
        $this->setTimestamp("Start Footer");

        $this->displayBottomWidget();
        $this->setTimestamp("displayBottomWidget");
        echo "          </div> <!-- content -->\n";

        $this->displayRightWidget();
        $this->setTimestamp("displayRightWidget");

        echo "        </div> <!-- container-inside -->\n";
        echo "      </div> <!-- container -->\n";
        echo "      <footer>\n";
        echo "        <div>\n";
        /*
        echo "          <aside id='footer-menu'>\n";
        echo "            <ul class='menu-items xs-font-size'>\n";
        echo "              <li><a href='#'>Mail</a></li>\n";
        echo "              <li><a href='#'>My B2B</a></li>\n";
        echo "              <li><a href='#'>Message Area</a></li>\n";
        echo "            </ul>\n";
        echo "          </aside>\n";
        */
        echo "          <div id='site-ig-wrap'>\n";
        echo "            <span id='site-info'>\n";
        echo "              &copy;".date('Y', time())." - <a href='home.php' title='DealernetX' rel='home'>DealernetX</a>\n";
        echo "            </span> <!-- #site-info -->\n";
        echo "          </div> <!-- #site-ig-wrap -->\n";
        echo "        </div> <!--divFoot-->\n";
        echo "      </footer>\n";
        echo "    </div> <!-- page-wrap -->\n";
        $this->setTimestamp("displayFooter");

        if ($this->jsinit) {
            echo "    <SCRIPT language='JavaScript'>\n";
            foreach($this->jsinit as $j) {
                echo "      ".$j."\n";
            }
            echo "    </SCRIPT>\n";
        }
        $this->setTimestamp("add jsInit");
        $this->setTimestamp("Done Footer");
        $this->dumpTimestamps();
        echo "  </body>\n";
        echo "</html>\n";
    }

    private function loadPageBottomAds2Session() {
        $sql = "
            SELECT classpath || originalpath as imagepath, url
              FROM advertmanage
             WHERE active = 1
               AND classpath = 'PageBottom/'
            ORDER BY createdate
        ";

        if ($rs = $this->db->sql_query($sql)) {
            $x = 1;
            foreach($rs as $i) {
                $_SESSION["pba_".$x] = $i["imagepath"];
                $_SESSION["pba_url_".$x] = $i["url"];
                $x++;
            }
            $_SESSION["pba_index"] = 1;
            $_SESSION["pba_maxindex"] = count($rs);
        }
    }

    private function hasCartItems() {
        $hasItems = NULL;
        $sql = "
            SELECT count(*) as numincart
              FROM shoppingcart
             WHERE userid = ".$this->user->userId."
        ";

        $hasItems = $this->db->get_field_query($sql);

        return $hasItems;
    }

    private function getCartCounts() {
        $cartCounts = NULL;
        $sql = "
            SELECT count(*) as numincart, count(iswanted) as numwanted, count(isforsale) as numforsale
            FROM (
                SELECT s.shoppingcartid
                    , CASE WHEN l.type = 'Wanted' THEN 1 ELSE NULL END AS iswanted
                    , CASE WHEN l.type = 'For Sale' THEN 1 ELSE NULL END AS isforsale
                  FROM shoppingcart s
                  JOIN listings l on l.listingid=s.listingid AND l.status='OPEN'
                  JOIN categories        cat ON cat.categoryid       = l.categoryid AND cat.active=1
                  JOIN subcategories     sub ON sub.subcategoryid    = l.subcategoryid AND sub.active=1
                  JOIN boxtypes          box ON box.boxtypeid        = l.boxtypeid AND box.active=1
                  JOIN userinfo           ui ON ui.userid            = s.listinguserid AND ui.userclassid=".USERCLASS_VENDOR."
                  JOIN assignedrights    eur ON eur.userid           = s.listinguserid AND eur.userrightid=".USERRIGHT_ENABLED."
                  LEFT JOIN assignedrights    stl ON stl.userid      = s.listinguserid AND stl.userrightid=".USERRIGHT_STALE."
                 WHERE s.userid = ".$this->user->userId."
                   AND stl.userid IS NULL
             ) items
        ";

        if ($results = $this->db->sql_query($sql)) {
            $cartCounts = reset($results);
        }

        return $cartCounts;
    }

    private function getMarketplaceMenu() {
        global $page;

        $items = NULL;

        $sql = "
            SELECT c.categoryid, c.categorydescription, ct.categorytypeid, ct.categorytypename, ct.sort
              FROM categories           c
              JOIN categorytypes        ct  ON  c.categorytypeid    = ct.categorytypeid
             WHERE c.active     = 1
               AND c.showonmenu = 1
            GROUP BY c.categoryid, c.categorydescription, ct.categorytypeid, ct.categorytypename, ct.sort
            ORDER BY ct.sort, c.categorydescription COLLATE \"POSIX\"
        ";
        $items = $this->db->sql_query($sql);

        return $items;
    }

    private function getMarketplaceMenuAds() {
        $sql = "
            SELECT classpath || originalpath as imagepath, url
              FROM advertmanage
             WHERE active = 1
               AND classpath = 'MarketplaceMenu/'
            ORDER BY random()
        ";

        if ($rs = $this->db->sql_query($sql)) {
        } else {
            $rs = array();
        }

        return $rs;
    }

    private function hasOffers() {
        global $DB, $USER;

        $sql = "SELECT count(*) AS numoffers FROM offers WHERE offerfrom=".$USER->userId." OR offerto=".$USER->userId;

        $numOffers = $this->db->get_field_query($sql);

        return $numOffers;
    }

    private function getOfferCounts() {
        global $DB, $USER;

        $sql = "
            SELECT status, statusfilter, count(1) AS cnt
              FROM (
                SELECT CASE WHEN offerstatus = 'PENDING'  AND offeredby = ".$USER->userId."  THEN 'Pending Out'
                            WHEN offerstatus = 'PENDING'  AND offeredby <> ".$USER->userId." THEN 'Pending In'
                            WHEN offerstatus = 'ACCEPTED' AND transactiontype='For Sale' AND offerto = ".$USER->userId."   THEN 'Sales'
                            WHEN offerstatus = 'ACCEPTED' AND transactiontype='Wanted'   AND offerfrom = ".$USER->userId." THEN 'Sales'
                            WHEN offerstatus = 'ACCEPTED' AND transactiontype='For Sale' AND offerfrom = ".$USER->userId." THEN 'Purchases'
                            WHEN offerstatus = 'ACCEPTED' AND transactiontype='Wanted'   AND offerto = ".$USER->userId."   THEN 'Purchases'
                            ELSE 'OTHER' END AS status,
                       CASE WHEN offerstatus = 'PENDING'  AND offeredby = ".$USER->userId."  THEN 'PENDINGOUT'
                            WHEN offerstatus = 'PENDING'  AND offeredby <> ".$USER->userId." THEN 'PENDINGIN'
                            WHEN offerstatus = 'ACCEPTED' AND transactiontype='For Sale' AND offerto = ".$USER->userId."   THEN 'SALESUNRATED'
                            WHEN offerstatus = 'ACCEPTED' AND transactiontype='Wanted'   AND offerfrom = ".$USER->userId." THEN 'SALESUNRATED'
                            WHEN offerstatus = 'ACCEPTED' AND transactiontype='For Sale' AND offerfrom = ".$USER->userId." THEN 'PURCHASESUNRATED'
                            WHEN offerstatus = 'ACCEPTED' AND transactiontype='Wanted'   AND offerto = ".$USER->userId."   THEN 'PURCHASESUNRATED'
                            ELSE 'OTHER' END AS statusfilter
                  FROM offers
                 WHERE (     offerstatus = 'PENDING'
                        AND (offerto = ".$USER->userId." OR offerfrom = ".$USER->userId."))
                    OR (     offerstatus   = 'ACCEPTED'
                        AND (satisfiedsell = 0
                             AND (    (transactiontype = 'For Sale' AND offerto = ".$USER->userId.")
                                   OR (transactiontype = 'Wanted' AND offerfrom = ".$USER->userId.")))
                         OR (satisfiedbuy = 0
                             AND (    (transactiontype = 'For Sale' AND offerfrom = ".$USER->userId.")
                                   OR (transactiontype = 'Wanted' AND offerto = ".$USER->userId."))))
                    ) x
            GROUP BY status, statusfilter
            ORDER BY status
        ";

        $rs = $this->db->sql_query($sql);

        return $rs;
    }

    function verifyPaymentMethods() {
        $success = true;

        if ($this->user->userId) {
            $sql = "SELECT distinct l.type
                    FROM listings l
                    WHERE l.userid=".$this->user->userId."
                    AND l.status='OPEN'";
            if ($transactiontypes = $this->db->sql_query($sql)) {
                foreach ($transactiontypes as $transactiontype) {
                    $sql = "SELECT count(pp.transactiontype) as numconfigged
                            FROM preferredpayment pp
                            JOIN paymenttypes pt ON pt.paymenttypeid=pp.paymenttypeid AND pt.active=1
                            WHERE pp.userid=".$this->user->userId."
                              AND pp.transactiontype='".$transactiontype['type']."'";
                    $configged = $this->db->get_field_query($sql);
                    if (!($configged > 0)) {
                        $success = false;
                        break;
                    }
                }
            }

            if ($success) {
                $sql = "SELECT count(*) as missrequired
                        FROM preferredpayment pp
                        JOIN paymenttypes pt ON pt.paymenttypeid=pp.paymenttypeid AND pt.active=1
                        WHERE pp.userid=".$this->user->userId."
                          AND pt.allowinfo='Yes'
                          AND pp.extrainfo  IS NULL";
                $missrequired = $this->db->get_field_query($sql);
                if ($missrequired > 0) {
                    $success = false;
                }
            }
        }
        return $success;
    }

    public function isIPBlocked($ipaddress) {
        $isblocked = false;
        if (!empty($ipaddress)) {
            $x = $this->db->get_field_query("SELECT ipaddress FROM blocked_ips where trim(ipaddress) = trim('".$ipaddress."')");
            if (!empty($x)) {
                $isblocked = true;
            }
        }

        return $isblocked;
    }
}


?>