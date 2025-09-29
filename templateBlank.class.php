<?php

require_once('template.class.php');

define ("SHOWLOGO", TRUE);
define ("NOLOGO", FALSE);

class templateBlank extends template{

    public $showLogoBanner;

    public function __construct($loginRequired = true, $showmsgs = true, $bypassMustReplyCheck = false, $showlogo=false) {
        $this->showLogoBanner = $showlogo;
        parent::__construct($loginRequired, $showmsgs, $bypassMustReplyCheck);
    }

    public function displaySecondaryNav() {
    }

    public function displayPrimaryNav() {
        if ($this->showLogoBanner) {
            echo "          <div id='nav-primary' class='primary-menu'>\n";
            echo "            <div class='primary-menu-container not-logged-in font-inherit'>\n";
            echo "              <a class='custom-logo' href='home.php' alt='Site Home'><img src='images/dealernetX-logo-blue.png' alt='Logo'/></a>\n";
            echo "              <a href='javascript:void(0);' class='icon' onclick='responsiveMenu()'><i class='fa fa-bars'></i></a>\n";
            echo "            </div> <!-- primarymenucontainer-->\n";
            echo "          </div> <!--nav primary-->\n";
        }
    }

    public function displayBottomWidget() {
    }
}
?>