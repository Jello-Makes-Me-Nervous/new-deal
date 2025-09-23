<?php
require_once('templateAdmin.class.php');

$page = new templateAdmin(LOGIN, SHOWMSG);

echo $page->header('ADMIN');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page;

    echo "<article>\n";
    echo "  <div class='entry-content'>\n";

    echo "    <div style='float:left;width:350px;padding-left:25px;'>\n";
    echo "      <fieldset style='padding: 3px;'>\n";
    echo "        <legend>configuration</legend>\n";
    echo "        <ul>\n";
    echo "          <li><a href='userRolodex.php'>Users</a></li>\n";
    echo "          <li><a href='userRolodex.php?addrrequest=1'>Address Requests</a></li>\n";
    echo "          <li><a href='adminCreditInfoList.php'>Membership Billing Updates</a></li>\n";
    echo "          <li><a href='categories.php'>Categories</a></li>\n";
    echo "          <li><a href='subCategories.php'>Subcategories</a></li>\n";
    echo "          <li><a href='boxType.php'>Box Types</a></li>\n";
    echo "          <li><a href='paymenttype.php'>Payment Types</a></li>\n";
    echo "          <li><a href='productSKUs.php'>Product UPCs</a></li>\n";
    echo "          <li><a href='blockedIPs.php'>Block IPs</a></li>\n";
    echo "          <li><a href='bluestarconfig.php'>Blue Star Criteria</a></li>\n";
    echo "        </ul>\n";
    echo "      </fieldset>\n";
    echo "    </div>\n";

    echo "    <div style='float:left;width:350px;padding-left:25px;'>\n";
    echo "      <fieldset style='padding: 3px;'>\n";
    echo "        <legend>admin pages</legend>\n";
    echo "        <ul>\n";
    echo "          <li><a href='adminmultiedit.php?pagename=aboutUs&findbtn=1'>About Us</a></li>\n";
    echo "          <li><a href='adminmultiedit.php?pagename=adminFAQ&findbtn=1'>FAQs</a></li>\n";
    echo "          <li><a href='adminmultiedit.php?pagename=homepage&findbtn=1'>Home page</a></li>\n";
    echo "          <li><a href='adminmultiedit.php?pagename=membership&findbtn=1'>Membership</a></li>\n";
    echo "          <li><a href='adminmultiedit.php?pagename=siteannouncements&findbtn=1'>Site Announcements</a></li>\n";
    echo "          <li><a href='adminmultiedit.php?pagename=productcalendar&findbtn=1'>Product Calendar</a></li>\n";
    echo "          <li><a href='adminmultiedit.php?pagename=newmembers&findbtn=1'>New Members (Welcome)</a></li>\n";
    echo "          <li><a href='adminmultiedit.php?pagename=newmembers_email&findbtn=1'>New Members Email</a></li>\n";
    echo "        </ul>\n";
    echo "        <ul>\n";
    echo "          <li><a href='adminmultimgr.php'>Admin Multi Manager</a></li>\n";
    echo "          <li><a href='adminmultiedit.php'>Admin Multi Page Edit</a></li>\n";
    echo "          <li><a href='adminimagemgr.php'>Admin Image Manager</a></li>\n";
    echo "          <li><a href='adminimageupload.php'>Admin Image Upload</a></li>\n";
    echo "        </ul>\n";
    echo "      </fieldset>\n";
    echo "    </div>\n";

    echo "    <div style='float:left;width:350px;padding-left:25px;'>\n";
    echo "      <fieldset style='padding: 3px;'>\n";
    echo "        <legend>sponsorship</legend>\n";
    echo "        <ul>\n";
    echo "          <li><a href='advertManage.php'>Manage Advertising</a></li>\n";
    echo "        </ul>\n";
    echo "      </fieldset>\n";
    echo "    </div>\n";

    echo "    <div style='float:left;width:350px;padding-left:25px;'>\n";
    echo "      <fieldset style='padding: 3px;'>\n";
    echo "        <legend>offers</legend>\n";
    echo "        <ul>\n";
    echo "          <li><a href='offersadmin.php'>Find Offer</a></li>\n";
    echo "          <li><a href='adminoffertotals.php'>Offer Totals Report</a></li>\n";
    echo "        </ul>\n";
    echo "      </fieldset>\n";
    echo "    </div>\n";

    echo "    <div style='float:left;width:350px;padding-left:25px;'>\n";
    echo "      <fieldset style='padding: 3px;'>\n";
    echo "        <legend>EFT Reports</legend>\n";
    echo "        <ul>\n";
    echo "          <li><a href='/admEFTStats.php'>EFT Stats</a></li>\n";
    echo "          <li><a href='/admEFTBalance.php'>EFT Balance</a></li>\n";
    echo "          <li><a href='admEFTCreditBalance.php'>EFT Credit Balance</a></li>\n";
    echo "          <li><a href='/admEFTDailyBalance.php'>EFT Daily Balance</a></li>\n";
    echo "          <li><a href='/admEFTBalanceByDate.php'>EFT Balance x Date</a></li>\n";
    echo "        </ul>\n";
    echo "      </fieldset>\n";
    echo "    </div>\n";

    echo "    <div style='float:left;width:350px;padding-left:25px;'>\n";
    echo "      <fieldset style='padding: 3px;'>\n";
    echo "        <legend>Misc Reports</legend>\n";
    echo "        <ul>\n";
    echo "          <li><a href='lastLogin.php'>Last Login</a></li>\n";
    echo "          <li><a href='admloginreport.php'>Login Report (IP Addresses)</a></li>\n";
    echo "          <li><a href='assignedRights.php'>Assigned Rights</a></li>\n";
    echo "          <li><a href='listingCounts.php'>Listing Counts</a></li>\n";
    echo "          <li><a href='offerCounts.php'>Offer Counts</a></li>\n";
    echo "          <li><a href='javascript:void(0);'>Credit Card</a></li>\n";
    echo "          <li><a href='billing.php'>Auto Management Fee</a></li>\n";
    echo "        </ul>\n";
    echo "      </fieldset>\n";
    echo "    </div>\n";

    echo "  </div>\n";
    echo "</article>\n";

}

?>