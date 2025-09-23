<?php

DEFINE("CASHOUT", "CashOut");
DEFINE("DEPOSIT", "Deposit");
DEFINE("PAYMENT", "Transfer To Member");
DEFINE("REVERSE", "Reverse");
DEFINE("TRANSFER", "Transfer To Member");
DEFINE("WD_REQUEST", "Withdraw Request");
DEFINE("WITHDRAWAL", "Withdrawal");

DEFINE("EFT_ACTION_CASHIN","CASHIN");
DEFINE("EFT_ACTION_CASHOUT","CASHOUT");
DEFINE("EFT_ACTION_DEPOSIT","DEPOSIT");
DEFINE("EFT_ACTION_WITHDRAWAL","WITHDRAWAL");
DEFINE("EFT_ACTION_PAYMENT","PAYMENT");
DEFINE("EFT_ACTION_TRANSFER","TRANSFER");

DEFINE("EFT_TRAN_TYPE_CASHOUT","CASHOUT");
DEFINE("EFT_TRAN_TYPE_WITHDRAWAL","WITHDRAWAL");
DEFINE("EFT_TRAN_TYPE_PAYMENT","PAYMENT");
DEFINE("EFT_TRAN_TYPE_WD_REQUEST","WD-REQUEST");
DEFINE("EFT_TRAN_TYPE_DEPOSIT","DEPOSIT");
DEFINE("EFT_TRAN_TYPE_FEE","FEE");
DEFINE("EFT_TRAN_TYPE_CREDIT_FEE","CREDIT FEE");
DEFINE("EFT_TRAN_TYPE_MEMBERSHIP_FEE",  "MEMBERSHIP FEE");
DEFINE("EFT_TRAN_TYPE_RECEIPT","RECEIPT");
DEFINE("EFT_TRAN_TYPE_REVERSE","REVERSE");
DEFINE("EFT_TRAN_TYPE_TXFR-IN","TXFR-IN");
DEFINE("EFT_TRAN_TYPE_TXFR-OUT","TXFR-OUT");
DEFINE("EFT_TRAN_TYPE_BALANCE","BALANCE");

class electronicFundsTransfer {

    public $messages;
    public $amount;
    public $dealerName;
    public $description;
    public $fee;
    public $fromId;
    public $linkId;
    public $mon;
    public $offerId;
    public $toId;
    public $transType;
    public $year;
    public $ledgerBalance;
    public $creditLine;
    public $bankInfo;
    public $paypalId;
    public $initialBalance;
    private $adminId;
    private $adminName;
    private $feeId;
    private $feeName;
    private $recordTransactionBtn;
    private $confirmTransaction;
    private $payDealerId;
    private $payDealerName;

    public function __construct() {
        global $page;

        $this->messages = new Messages();
        $this->paramInit();
//        $this->year = date('Y', strtotime("+".$this->mon." months"));
        if (!empty($this->recordTransactionBtn)) {
            switch ($this->transType) {
                 case EFT_ACTION_CASHIN:
                    if ($page->user->isAdmin()) {
                        $this->makeCashIn();
                    } else {
                        $page->messages->addErrorMsg("Admin access is required");
                    }
                    break;

                 case EFT_ACTION_CASHOUT:
                    if ($page->user->isAdmin()) {
                        $this->makeCashOut();
                    } else {
                        $page->messages->addErrorMsg("Admin access is required");
                    }
                    break;

                 case EFT_ACTION_WITHDRAWAL:
                    $this->makeWithdrawal();
                    break;

                 case EFT_ACTION_PAYMENT:
                    $this->makePayment();
                    break;

                 case EFT_ACTION_TRANSFER:
                    $this->makeTransfer();
                    break;

                 case EFT_ACTION_DEPOSIT:
                    $this->makeDeposit();
                    break;
/*
                 case REVERSE:
                    $this->reverse();
                    break;

                 case WD_REQUEST:
                    $this->WDrequest();
                    break;
*/
                 default:
                    break;
            }
        } else {
            //echo "No transaction submitted<br />\n";
        }

    }

    public function paramInit() {
        global $page;
        global $UTILITY;

        $this->amount               = optional_param('amount', NULL, PARAM_TEXT);
        $this->dealerName           = optional_param('dealername', NULL, PARAM_TEXT);
        $this->description          = optional_param('description', NULL, PARAM_TEXT);
        $this->fee                  = optional_param('fee', 0, PARAM_INT);
        $this->fromId               = optional_param('fromid', 0, PARAM_INT);
        $this->linkId               = optional_param('linkid', 0, PARAM_TEXT);
        $this->offerId              = optional_param('offerid', NULL, PARAM_INT);
        $this->toId                 = optional_param('toid', 0, PARAM_INT);
        $this->transType            = optional_param('transtype', NULL, PARAM_TEXT);
        $this->recordTransactionBtn = optional_param('recordtransactionbtn', NULL, PARAM_RAW);
        $this->confirmTransaction   = optional_param('confirmtransaction', NULL, PARAM_RAW);
        $this->mon                  = optional_param('mon', date('m'), PARAM_INT);
        $this->payDealerId          = optional_param('paydealerid', NULL, PARAM_INT);
        $this->payDealerName        = optional_param('paydealername', NULL, PARAM_TEXT);
        $this->year                 = optional_param('year', date('Y'), PARAM_INT);
        $this->adminName            = USERNAME_ADMIN;
        $this->adminId              = $UTILITY->getUserId($this->adminName);
        $this->feeName              = USERNAME_FEES;
        $this->feeId                = $UTILITY->getUserId($this->feeName);
        $this->ledgerBalance = $this->getLedgerBalance();
        $this->creditLine = $page->db->get_field_query("select dcreditline from userinfo where userid=".$page->user->userId);
        $this->bankInfo = $page->db->get_field_query("select bankinfo from userinfo where userid=".$page->user->userId);
        $this->paypalId = $page->db->get_field_query("select paypalid from userinfo where userid=".$page->user->userId);
        $this->initialBalance = $this->getInitialBalanceInfo();

        if (empty($this->payDealerName) && (!empty($this->payDealerId))) {
            $this->payDealerName = $UTILITY->getUserName($this->payDealerId);
        }
        if (empty($this->payDealerId) && (!empty($this->payDealerName))) {
            $this->payDealerId = $UTILITY->getUserId($this->payDealerName);
        }
    }

    public function getLedgerBalance() {
        global $DB;
        global $USER;

        $sql = "
            SELECT COALESCE(SUM(dgrossamount), 0) as balance
              FROM transactions
             WHERE transstatus = 'ACCEPTED'
               AND useraccountid = ".$USER->userId."
        ";

        $result = $DB->get_field_query($sql);

        return $result;

    }

    public function displayEFTCashOutForm() {
        global $page;

        $output = "";

        $output .= "<h1 class='page-title'>Admin Cash Out</h1>\n";
        $output .= "<div class='entry-content'>\n";
        if ($page->user->isAdmin()) {
            if ($this->ledgerBalance > 0) {
                $availableBalance = $this->ledgerBalance;
                $output .="  <form name ='trans' action='myEFTaction.php' method='post'>\n";
                $output .="  <div class='eft-form'>\n";
                $output .="    <input type='hidden' name='recordtransactionbtn'id='recordtransactionbtn' value='1' />\n";
                $output .="    <input type='hidden' name='action' id='action' value='cashout' />\n";
                $output .="    <input type='hidden' name='transtype' id='transtype' value='".EFT_ACTION_CASHOUT."' />\n";
                $output .="    <input type='hidden' name='availablebalance' id='availablebalance' value='".$availableBalance."' />\n";
                $output .="    <input type='hidden' name='fee' id='fee' value='0' />\n";
                $output .="    <div class='block'>\n";
                $output .="        <label for='amount'>Amount: $<input type='text' name='amount' id='amount' value='".$this->amount."' size='10' /></label>\n";
                $output .="        <label for='description'>Description: <input type='text' name='description' id='description' size='30' value='".$this->description."' /></label>\n";
                $output .="        <br />\n";
                $output .="        <strong>Available Balance:</strong> ".floatToMoney($availableBalance)."<br />\n";
                $output .="    </div>\n";
                $output .="  </div>\n";
                $output .="  <div class='button-box'>\n";
                $output .="    <input type='submit' name='submit' id='submit' value='Submit' onclick=\"return validateEFTCashOut();\" />\n";
                $output .="    <a href='myEFTaccount.php' class='cancel-button'>Cancel</a>\n";
                $output .="  </div>\n";
                $output .="</form>\n";
            } else {
                $output .= $page->messages->showMessage("You do not have funds available to cash out");
            }
        } else {
            $output .= $page->messages->showMessage("Admin access is required for this function", MSG_TYPE_ERROR);
        }
        $output .="</div>\n";

        return $output;
    }

    public function displayEFTCashInForm() {
        global $page;

        $output = "";

        $output .= "<h1 class='page-title'>Admin Cash In</h1>\n";
        $output .= "<div class='entry-content'>\n";
        if ($page->user->isAdmin()) {
            if ($this->confirmTransaction) {
                $output .= "<strong>Are you sure you want to perform the Cash In below?</strong><br />\n";
            }
            $output .="  <form name ='trans' action='myEFTaction.php' method='post'>\n";
            if ($this->confirmTransaction) {
                $output .="    <input type='hidden' name='recordtransactionbtn' id='recordtransactionbtn' value='1' />\n";
            } else {
                $output .="    <input type='hidden' name='confirmtransaction' id='confirmtransaction' value='1' />\n";
            }
            $output .="    <input type='hidden' name='action' id='action' value='cashin' />\n";
            $output .="    <input type='hidden' name='transtype' id='transtype' value='".EFT_ACTION_CASHIN."' />\n";
            $output .="    <div class='eft-form'>\n";
            if ($this->confirmTransaction) {
                $output .= "      <div class='medium-blocks'>\n";
                $output .= "        <div class='block'>\n";
                $output .= "          <strong>Cash In Amount:</strong> ".(($this->amount) ? floatToMoney($this->amount) : "")."<br />\n";
                $output .= "          <input type='hidden' name='amount' id='amount' value='".$this->amount."' />\n";
                $output .= "          <strong>Transfer Amount:</strong> ".(($this->amount) ? floatToMoney($this->amount) : "")."<br />\n";
                $output .= "          <strong>Fee Amount:</strong> ".(($this->fee) ? floatToMoney($this->fee) : "")."<br />\n";
                $output .= "          <strong>Description:</strong> ".$this->description."\n";
                $output .= "          <input type='hidden' name='description' id='description' value='".$this->description."' />\n";
                $output .= "        </div>\n";
                $output .= "        <div class='block'>\n";
                $output .= "          <strong>Transfer To Dealer:</strong> ".$this->payDealerName."(".$this->payDealerId.")<br />\n";
                $output .= "          <input type='hidden' name='paydealername' id='paydealername' value='".$this->payDealerName."' />\n";
                if ($this->payDealerId) {
                    $cashInUser = new user($this->payDealerId);
                    $output .= $cashInUser->formatAddress(BILLING);
                }
                $output .= "        </div>\n";
                $output .= "      </div>\n";
            } else {
                $output .= "      <div class='block'>\n";
                $output .= "        <label for='amount'>Amount: $ <input type='text' name='amount' id='amount' value='".$this->amount."' size='10' /></label>\n";
                $output .= "        <label for='paydealername'>Dealer: <input type='text' name='paydealername' id='paydealername' value='".$this->payDealerName."' size='30' /></label>\n";
                $output .= "        <label for='description'>Description: <input type='text' size='30' name='description' id='description' value='".$this->description."' /></label>\n";
                $output .= "        <input type=hidden name='tapaydealerid' id='tapaydealerid' value='' />\n";
                $output .= "      </div>\n";
            }
            $output .="    </div>\n";
            $output .="    <div class='button-box'>\n";
            $output .="      <input type='submit' name='submit' id='submit' value='Submit' />\n";
            $output .="      <a href='myEFTaccount.php' class='cancel-button'>Cancel</a>\n";
            $output .="    </div>\n";
            $output .="  </form>\n";
        } else {
            $output .= $page->messages->showMessage("Admin access is required for this function", MSG_TYPE_ERROR);
        }
        $output .="</div>";
        return $output;
    }

    public function displayEFTDepositForm() {
        global $page;
        $output = "";

        $output .= "<h1 class='page-title'>Deposit</h1>\n";

        $output .= "<div class='entry-content'>\n";
        $output .= "  <ul>\n";
        $output .= "    <li><b>Deposit:</b> ".floatToMoney($page->cfg->EFT_MAX_DEPOSIT)." max per calendar month</li>\n";
        $output .= "    <li>You can download the ACH Authorization form <A href='docs/dealernetach_authorization.pdf' target='_blank'>here</A> to add or update banking info.</li>\n";
        $output .= "  </ul>\n";
        $output .="<p><strong>Ledger Balance:</strong> ".floatToMoney($this->ledgerBalance);
        if ($this->creditLine > 0) {
            $creditRemain = ($this->ledgerBalance < 0.00) ? "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Available:</strong> ".floatToMoney($this->creditLine + $this->ledgerBalance) : "";
            $output .="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Credit Line:</strong> ".floatToMoney($this->creditLine).$creditRemain;
        }
        $output .="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Bank Info:</strong> ".$this->bankInfo."</p>\n";

        if (!empty($this->bankInfo)) {
            $output .="<form name ='trans' action='myEFTaction.php' method='post'>\n";
            $output .="<div class='single-column'>\n";
            $output .="  <input type='hidden' name='action' value='deposit' />\n";
            $output .="  <input type='hidden' name='recordtransactionbtn'id='recordtransactionbtn' value='1' />\n";
            $output .="  <input type='hidden' name='transtype' value='".EFT_ACTION_DEPOSIT."' />\n";
            $output .="  <DIV>";
            $output .="    <P>I authorize Dealernet Inc. to charge my bank account previously provided in the amount of $";
            $output .="       <select class='form-copy' size='1' name='amount' id='amount'>";
            $output .="         <option value='500' ".$this->isChecked($this->amount, 500).">500.00</option>";
            $output .="         <option value='1000' ".$this->isChecked($this->amount, 1000).">1,000.00</option>";
            $output .="         <option value='1500' ".$this->isChecked($this->amount, 1500).">1,500.00</option>";
            $output .="         <option value='2000' ".$this->isChecked($this->amount, 2000).">2,000.00</option>";
            $output .="         <option value='2500' ".$this->isChecked($this->amount, 2500).">2,500.00</option>";
            $output .="         <option value='3000' ".$this->isChecked($this->amount, 3000).">3,000.00</option>";
            $output .="       </select>";
            $output .="    <P>I fully understand that these funds will be posted directly to my Dealernet EFT trading account and I agree not to hold Dealernet liable for any subsequent fund transfers that I may make to other members.</P>";
            $output .="    <P>I understand that charges declined by the financial institution which maintains this bank account will constitute grounds for cancellation of service and that all charges incurred by Dealernet Inc plus any bank charges incurred will be subject to collection procedures.</P>";
            $output .="    <P>I understand that because this is an electronic transaction, these funds may be withdrawn from my account as soon as today's date. In the case of the payment being rejected for Non-Sufficient Funds (NSF) I understand that Dealernet may, at its discretion, attempt to process the charge again within 3 days, and I agree to an additional 35.00 charge for each attempt returned NSF, which will be initiated as a separate transaction from the authorized payment. I acknowledge that the origination of ACH transactions to my account must comply with the provisions of U.S. law. I will not dispute Dealernet's billing with my bank so long as the transaction corresponds to the terms indicated in this agreement.</P>";
            $output .="  </DIV>";
            $output .="  <div class='button-box'>\n";
            $output .="    <input type='submit' name='submit' id='submit' value='Submit' />\n";
            $output .="    <a href='myEFTaccount.php' class='cancel-button'>Cancel</a>\n";
            $output .="  </div>\n";
            $output .="</div>\n";
            $output .="</form>\n";
        } else {
            $output .="<div><span class='errormsg'>You must submit the ACH Authorization form above to provide banking information before depositing funds.</span></div>\n";
        }
        $output .="</div>\n"; // entry-content

        return $output;
    }

    public function displayEFTRedeemForm() {
        global $page;
        $output = "";

        $isPA = $page->user->isProxiedAdmin();
        $proxiedNote = ($isPA) ? "<span title='Proxy override active' style='font-weight:bold; color:red;'> *** </span>" : "";

        $billEFT = 0;
        $acceptEFT = 0;
        if ($meetWaiver = $this->getRedeemWaiver()) {
            if ($meetWaiver['billeft']) {
                $billEFT = 1;
            }
            if ($meetWaiver['accepteft']) {
                $acceptEFT = 1;
            }
        }
        $fullFee = ($billEFT && $acceptEFT) ? 0 : $page->cfg->EFT_REDEEM_FEE;

        if (($lastRedeem = $this->getLastRedeem()) && (! $isPA)) {
            $output .= $this->messages->showMessage("Limit one redeem per ".$page->cfg->EFT_REDEEM_DAYS." day period. Your last redeem was ".$lastRedeem['transdt'],MSG_TYPE_ERROR);
            $output .= "<a href='myEFTaccount.php'>Return to EFT Summary</a><br />\n";
        } else {
            $output .= "<h1 class='page-title'>Withdraw</h1>\n";

            $output .= "<div class='entry-content'>\n";
            $output .= "  <ul>\n";
            $output .= "    <li><b>Withdraw:</b> One withdraw per ".$page->cfg->EFT_REDEEM_DAYS." day period with a max amount of ".floatToMoney($page->cfg->EFT_MAX_REDEEM_AMOUNT)." per request with a ".floatToMoney($page->cfg->EFT_REDEEM_FEE)." fee.".$proxiedNote."</li>\n";
            $output .= "    <li><b>Fee:</b> $10 flat fee. Waived if you accept EFT selling payment option (<strong>".(($acceptEFT) ? "Y" : "N")."</strong>) and have enabled AUTO-EFT membership billing (<strong>".(($billEFT) ? "Y" : "N")."</strong>).</li>\n";
            //$output .= "    <li>You can download the ACH Authorization form <A href='docs/dealernetach_authorization.pdf' target='_blank'>here</A> to add or update banking info.</li>\n";
            $output .= "    <li>All withdraws will be deposited to your Paypal account after Admin review.</li>\n";
            $output .= "    <li>You can download the Paypal Authorization form <A href='/imageviewer.php?img=attachments/513848.pdf' target='_blank'>here</A> to add or update your EFT Withdraw Paypal account info.</li>\n";
            $output .= "  </ul>\n";

            if (!empty($this->paypalId)) {
                if (($this->ledgerBalance > 0) || $isPA) {
                    $availableBalance = min($this->ledgerBalance, ($page->cfg->EFT_MAX_REDEEM_AMOUNT+$fullFee));
                    $displayAvailableBalance = min($this->ledgerBalance, ($page->cfg->EFT_MAX_REDEEM_AMOUNT));
                    $output .="  <form name ='trans' action='myEFTaction.php' method='post'>\n";
                    $output .="    <input type='hidden' name='recordtransactionbtn'id='recordtransactionbtn' value='1' />\n";
                    $output .="    <input type='hidden' name='action' id='action' value='redeem' />\n";
                    $output .="    <input type='hidden' name='transtype' id='transtype' value='".EFT_ACTION_WITHDRAWAL."' />\n";
                    $output .="    <input type='hidden' name='availablebalance' id='availablebalance' value='".$availableBalance."' />\n";
                    $output .="    <input type='hidden' name='fee' id='fee' value='".$fullFee."' />\n";
                    $output .="    <div class='eft-form'>\n";
                    $output .="      Amount: $<input type='text' name='amount' id='amount' size='10' value='".$this->amount."' /><br />\n";
                    $output .="      <strong>Fee:</strong> ".floatToMoney($fullFee);
                    $output .="      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Available Balance:</strong> ".floatToMoney($displayAvailableBalance).$proxiedNote;
                    $output .="      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>PaypalId:</strong> ".$this->paypalId."<br />\n";
                    $output .="    </div>\n";
                    $output .="    <div class='button-box'>\n";
                    $output .="      <input type='submit' name='submit' id='submit' value='Submit' onclick=\"return validateEFTRedeem(".$isPA.");\" />\n";
                    $output .="      <a href='myEFTaccount.php' class='cancel-button'>Cancel</a>\n";
                    $output .="    </div>\n";
                    $output .="  </form>\n";
                } else {
                    $output .="<div style='color: red; margin: auto; padding-left: 50px;'>You do not have funds available to redeem</div><br />\n";
                }
            } else {
                $output .="<div><span class='errormsg'>You must supply Paypal account information before withdrawing funds.</span></div>\n";
            }
            $output .="</div>\n"; // entry-content
        }

        return $output;
    }

    public function displayEFTTransferForm() {
        $output = "";

        $output .= "<h1 class='page-title'>Transfer Funds To Member</h1>\n";

        $output .= "<div class='entry-content'>\n";
        $output .= "  <strong>Transfer funds to other members with no fees and no limits</strong><br />\n";

        $output .="  <form name ='trans' action='myEFTaction.php' method='post'>\n";
        $output .="  <div class='eft-form'>\n";
        if ($this->confirmTransaction) {
            $output .="    <input type='hidden' name='recordtransactionbtn' id='recordtransactionbtn' value='1' />\n";
        } else {
            $output .="    <input type='hidden' name='confirmtransaction' id='confirmtransaction' value='1' />\n";
        }
        $output .="    <input type='hidden' name='action' id='action' value='transfer' />\n";
        $output .="    <input type='hidden' name='transtype' id='transtype' value='".EFT_ACTION_TRANSFER."' />\n";
        if ($this->confirmTransaction) {
            $output .= "<strong>Are you sure you want to perform the Transfer below?</strong><br />\n";
        }
        if ($this->confirmTransaction) {
            $output .= "      <div class='medium-blocks'>\n";
            $output .= "      <div class='block'>\n";
            $output .= "          <strong>Dealer:</strong> ".$this->payDealerName."(".$this->payDealerId.") <br />\n";
            if ($this->payDealerId) {
                $cashInUser = new user($this->payDealerId);
                $output .= $cashInUser->formatAddress(BILLING);
            }
            $output .= "          <input type='hidden' name='paydealername' id='paydealername' value='".$this->payDealerName."' /><br />\n";
            $output .= "      </div>\n";
            $output .= "      <div class='block'>\n";
            $output .= "          <strong>Amount:</strong> ".(($this->amount) ? floatToMoney($this->amount) : "")." ";
            $output .= "          <input type='hidden' name='amount' id='amount' value='".$this->amount."' /><br />\n";
            $output .= "          <strong>Description:</strong> ".$this->description."\n";
            $output .= "          <input type='hidden' name='description' id='description' value='".$this->description."' /><br />\n";
            $output .= "      </div>\n";
            $output .= "      </div>\n";
        } else {
            $output .="      <div class='block'>\n";
            $output .="        <label for='dealer'>Dealer: \n";
            $output .="          <input type='text' name='paydealername' id='paydealername' value='".$this->payDealerName."' size='10'>\n";
            $output .="        </label><input type='hidden' name='tapaydealerid' id='tapaydealerid' value='' />\n";
            $output .="        <label for='amount'>Amount: \n";
            $output .="          <input type='text' name='amount' id='amount' value='".$this->amount."' size='10'>\n";
            $output .="        </label> \n";
            $output .="        <label for='description'>Description: \n";
            $output .="          <input type='text' size='25' name='description' id=description' value='".$this->description."' />\n";
            $output .="        </label>\n";
            $output .= "      </div>\n";
        }
        $output .="    <div class='block'>\n";
        $output .="        <p><strong>Ledger Balance:</strong> ".floatToMoney($this->ledgerBalance);
        if ($this->creditLine > 0) {
            $creditRemain = ($this->ledgerBalance < 0.00) ? "&nbsp;&nbsp;&nbsp;&nbsp;<strong>Available:</strong> ".floatToMoney($this->creditLine + $this->ledgerBalance) : "";
            $output .="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Credit Line:</strong> ".floatToMoney($this->creditLine).$creditRemain;
        }
        $output .="</p>\n";
        $output .="    </div>\n";
        $output .="  </div>\n"; // eft-form
        $output .="  <div class='button-box'>\n";
        $output .="    <input type='submit' name='submit' id='submit' value='Submit' />\n";
        $output .="    <a href='myEFTaccount.php' class='cancel-button'>Cancel</a>\n";
        $output .="  </div>\n";
        $output .="</form>\n";
        $output .="</div>\n";  // entry-content

        return $output;
    }

    public function displayEFTOfferForm() {
        global $page;

        $output = "";

        $output .= "<h1>Pay For Purchase</h1>\n";
        $output .= "<strong>Transfer funds to other members with no fees and no limits</strong><br />\n";

        $availableBalance = $this->ledgerBalance;
        if (!empty($this->offerId)) {
            $offer = $this->getOffer($this->offerId);
            $off = (!empty($offer)) ? reset($offer) : "" ;

            if ($off) {
                $output .="<form name ='trans' action='myEFTaction.php' method='post'>\n";
                $output .="  <div class='eft-form'>\n";
                $output .="    <input type='hidden' name='recordtransactionbtn'id='recordtransactionbtn' value='1' />\n";
                $output .="    <input type='hidden' name='action' id='action' value='pay' />\n";
                $output .="    <input type='hidden' name='transtype' id='transtype' value='".EFT_ACTION_PAYMENT."' />\n";
                $output .="    <input type='hidden' name='offerid' id='offerid' value='".$this->offerId."' />\n";
                $output .="    <input type='hidden' name='paydealername' id='paydealername' value='".$off['paydealername']."' />\n";
                $output .= "   <div class='block'>\n";
                $output .="      <strong>Offer #:</strong> ".$off['offerid']."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Dealer:</strong> ".$off['paydealername']."<br />\n";
                $output .="      Amount: $ <input type=text name='amount' id='amount' width=10 value='".$off['offerdsubtotal']."' /><br />\n";
                $output .="      <strong>Ledger Balance:</strong> ".floatToMoney($this->ledgerBalance);
                if ($this->creditLine > 0) {
                    $creditRemain = ($this->ledgerBalance < 0.00) ? "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Available:</strong> ".floatToMoney($this->creditLine + $this->ledgerBalance) : "";
                    $output .="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Credit Line:</strong> ".floatToMoney($this->creditLine).$creditRemain;
                }
                $output .="    </div>\n";
                $output .="  </div>\n";
                $output .="  <div class='button-box'>\n";
                $output .="    <input type='submit' name='submit' id='submit' value='Submit' onclick=\"return validateEFTTransfer();\" />\n";
                $output .="    <a href='myEFTaccount.php' class='cancel-button'>Cancel</a>\n";
                $output .="  </div>\n";
                $output .="</form>\n";
            } else {
                $page->messages->showMessage("Offer not found",MSG_TYPE_ERROR);
            }
        } else {
            $page->messages->showMessage("No offer specified",MSG_TYPE_ERROR);
        }

        return $output;
    }
    public function displayMonthlySelector() {
        global $MESSAGES;

        $from = $this->getBeginOfMonth();
        $to = $this->getEndOfMonth();

        $output = "";

 //       $output = $this->monthYearRotate();

        if ($this->initialBalance) {
            //echo "To:".$to." ".date('m/d/Y h:i:s', $to)." IBDate:".$initialBalance['transdate']." IBAmt:".floatToMoney($initialBalance['dgrossamount'])."<br />\n";
            if ($this->initialBalance['transdate'] && ($this->initialBalance['transdate'] > $to)) {
                $output .= $MESSAGES->showMessage("Transactions prior to ".date('m/d/Y', $this->initialBalance['transdate']+1)." have been archived.");
                $output .= $MESSAGES->showMessage("Balance as of ".date('m/d/Y', $this->initialBalance['transdate']+1)." was ".floatToMoney($this->initialBalance['dgrossamount']));
            }
        }

        return $output;
    }

    public function displayEFTtotalsHorizontal() {
        global $DB;
        global $MESSAGES;
        global $USER;
        global $UTILITY;

        $output = "";
        $output .= "<div class='single-column'>\n";
        $output .= "  <table>\n";
        $output .= "    <tr>\n";
        $output .= "      <th>Balances</th>\n";
        $output .= "      <td><strong>Ledger: </strong>".floatToMoney($this->ledgerBalance)."</td>\n";
        $output .= "      <td><strong>Credit Line: </strong>".floatToMoney($this->creditLine)."</td>\n";
        $output .= "      <td><strong>Available: </strong>".floatToMoney($this->ledgerBalance + $this->creditLine)."</td>\n";
        $output .= "      <td>&nbsp;</td>\n";
        $output .= "    </tr>\n";
        $output .= "    <tr>\n";
        $output .= "      <th>Totals for ".$this->year."</th>\n";
        $output .= "      <td><strong>Payments: </strong>".floatToMoney($this->getPaymentAmount())."</td>\n";
        $output .= "      <td><strong>Receipts: </strong>".floatToMoney($this->getReceiptAmount())."</td>\n";
        $output .= "      <td><strong>Withdrawals: </strong>".floatToMoney($this->getWithdrawalAmount())."</td>\n";
        $output .= "      <td><strong>Listing Fees: </strong>".floatToMoney($this->getFeeAmount())."</td>\n";
        $output .= "    </tr>\n";
        $output .= "    <tr>\n";
        $output .= "      <th>Transactions</th>\n";
        $output .= "      <td><strong>Payment Count: </strong>".$this->getPaymentCount()."</td>\n";
        $output .= "      <td><strong>Receipt Count: </strong>".$this->getReceiptCount()."</td>\n";
        $output .= "      <td><strong>Withdrawal Count: </strong>".$this->getWithdrawalCount()."</td>\n";
        $output .= "      <td><strong>Listing Fee Count: </strong>".$this->getFeeCount()."</td>\n";
        $output .= "    </tr>\n";
        $output .= "  </table>\n";
        $output .= "</div>\n";

        return $output;
    }

    public function displayMonthlyTransactions() {
        global $DB;
        global $MESSAGES;
        global $USER;
        global $UTILITY;

        $beginningBalance = $this->getBeginOfMonthTotal();
        $endingBalance = $this->getEndOfMonthTotal();
        $data = $this->getTransactionInfoMonth();

        $to = $this->getEndOfMonth();

        $output = "";
        if (!($this->initialBalance['transdate'] && ($this->initialBalance['transdate'] > $to))) {
            $output .= "<div class='single-column'>\n";
            $output .= "  <table>\n";
            $output .= "    <thead>\n";
            $output .= "      <th>Date</th>\n";
            $output .= "      <th>Account</th>\n";
            $output .= "      <th>Description</th>\n";
            $output .= "      <th>Debit</th>\n";
            $output .= "      <th>Credit</th>\n";
            $output .= "      <th>Balance</th>\n";
            $output .= "    </thead>\n";
            $output .= "    <tbody>\n";

            $output .= "        <tr class='balances'><td class='number' colspan='5'>Ending Balance</td><td class='number'>".floatToMoney($endingBalance)."</td></tr>\n";
            $balance = $endingBalance;
            if (isset($data)) {
                foreach ($data as $key) {
                    if ($key['dgrossamount'][0] == '-') {
                        $credit = "";
                        $debit = $key['dgrossamount'];
                    } else {
                        $credit = $key['dgrossamount'];
                        $debit = "";
                    }
                    $account = "";
                    $output .= "        <tr>\n";
                    $output .= "          <td data-label='Date'>".$key['day']."</td>\n";
    //refaccountid is empty sometimes
                    if (!empty($key['refaccountid'])) {
                        $username=$UTILITY->getUserName($key['refaccountid']);
                    } else {
                        $username = "";
                    }
                    $output .= "          <td data-label='Account'>".$username."</td>\n";
    //if payment only show account ref id?
                    if (($USER->username == $this->adminName) && ($key['transtype'] == "Deposit")) {
                        $output .= "          <td data-label='Description'>".$key['transtype']."-".$USER->username."-".$key['transdesc']."</td>\n";
                    } else {
                        if (($USER->username == $this->adminName) && ($key['transtype'] == "Payment")) {
                            $output .= "          <td data-label='Description'><a href='?'>Ref# ".$key['crossrefid']."-</a>".$key['transdesc']."</td>\n";
                        } else {
                            if ($key['offerid']) {
                                $output .= "          <td data-label='Description'><a href='offer.php?offerid=".$key['offerid']."' target=_blank>".$key['transdesc']."(#".$key['offerid'].")</a></td>\n";
                            } else {
                                $output .= "          <td data-label='Description'>".$key['transdesc']."</td>\n";
                            }
                        }
                    }
                    $output .= "          <td data-label='Debit' class='debit number'>".$debit."</td>\n";
                    $output .= "          <td data-label='Credit' class='number'>".$credit."</td>\n";
                    if ($balance < 0) {
                        $output .= "          <td data-label='Balance' style='color: RED;' align='right'>\n";
                    } else {
                        $output .= "          <td data-label='Balance' align='right'>\n";
                    }
                    $output .= "          ".floatToMoney($balance)."</td>\n";
                    $output .=  "        </tr>\n";
                    //balance = ledger - trans (goins backwards)
                    $balance -= $key['dgrossamount'];

                }
            } else {
                $output .= "        <tr><td colspan='6'>No transactions for the specified time period.</td>\n";
            }
            $output .= "        <tr class='balances'><td class='number' colspan='5'>Beginning Balance</td><td class='number'>".floatToMoney($beginningBalance)."</td></tr>\n";
            $output .= "      </tbody>\n";
            $output .= "    </table>\n";
            $output .= "</div>\n";
        }

        return $output;

    }

    public function confirmPayment($fromId = NULL, $toId = NULL, $linkId = NULL) {
        global $DB;
        global $MESSAGES;
        global $USER;
        global $UTILITY;

        $success = true;
        $toDealerNmae = NULL;

        $from = $USER->username;
        $fromId = $USER->userId;

        if (empty($toId)) {
            if (empty($this->dealerName)) {
                $success = false;
                $MESSAGES->addErrorMsg("Dealer required");
            } else {
                $toId = $UTILITY->getUserId($this->dealerName);
                if (empty($toId)) {
                    $success = false;
                    $MESSAGES->addErrorMsg('Dealer '.$this->dealerName.' does not exist.');
                } else {
                    $toDealerName = $this->dealerName;
                }
            }
        } else {
            $toDealerName = $UTILITY->getDealersName($toId);
            if (empty($toDealerName)) {
                $success = false;
                $MESSAGES->addErrorMsg('Invalid Dealer Id');
            }
        }

        if (empty($this->amount)) {
            $success = false;
            $MESSAGES->addErrorMsg("Amount required");
        } else {
            if ($this->amount <= 0) {
                $success = false;
                $MESSAGES->addErrorMsg("Invalid amount");
            } else {
                $acceptTransaction = $this->checkFunds($this->amount, $fromId);
                if (! $acceptTransaction) {
                    $success = false;
                    $MESSAGES->addErrorMsg("Insufficient funds");
                }
            }
        }

        $description = ($linkId == "Fees") ? "Fees" : $this->description;

        if ($success) {
            $MESSAGES->addInfoMsg("Transfer ".floatToMoney($this->amount)." to Dealer ".$toDealerName." for ".$description);
        }

    }

    public function makePayment() {
        global $page, $UTILITY;

        $inTransaction = false;
        $success = true;
        $offer = NULL;
        $crossRefId = NULL;

        if ($this->offerId) {
            if ($offers = $this->getOffer($this->offerId)) {
                $offer = reset($offers);
                $page->messages->addSuccessMsg("Offer found");
            } else {
                $success = false;
                $page->messages->addErrorMsg("Offer not found. No payment made.");
            }
        } else {
            $success = false;
            $page->messages->addErrorMsg("Missing Offer Id. No payment made.");
        }

        if ($this->amount > 0) {
            if ($this->amount > $this->ledgerBalance) {
                if ($page->user->dcreditline > 0) {
                    $available = $this->ledgerBalance + $page->user->dcreditline;
                    if ($this->amount > $available) {
                        $page->messages->addErrorMsg("Insufficient funds (".floatToMoney($available).") to pay ".floatToMoney($this->amount));
                    }
                } else {
                    $page->messages->addErrorMsg("Insufficient funds (".floatToMoney($this->ledgerBalance).") to pay ".floatToMoney($this->amount));
                    $success = false;
                }
            }
        } else {
            $page->messages->addErrorMsg("Amount to pay must be positive");
            $success = false;
        }

        if ($success) {
            $page->db->sql_begin_trans();
            $inTransaction = true;

            $crossRefId = $this->getCrossRefId();

            $sql = "
                INSERT INTO transactions( crossrefid,  useraccountid,  refaccountid,  transtype, transstatus,  dgrossamount,  accountname,  transdesc, offerid,  createdby, modifiedby)
                                  VALUES(:crossrefid, :useraccountid, :refaccountid, :transtype, :transstatus, :dgrossamount, :accountname, :transdesc, :offerid, :createdby, :modifiedby)
            ";
            $params = array();
            $params['crossrefid']       = $crossRefId;
            $params['useraccountid']    = $offer['paydealerid'];
            $params['refaccountid']     = $page->user->userId;
            $params['transtype']        = EFT_TRAN_TYPE_RECEIPT;
            $params['transstatus']      = "ACCEPTED";
            $params['dgrossamount']     = $this->amount;
            $params['accountname']      = $page->user->username;
            $params['transdesc']        = "Receipt for offer ".$this->offerId;
            $params['offerid']          = $this->offerId;
            $params['createdby']        = $page->user->username;
            $params['modifiedby']       = $page->user->username;

            if ($page->db->sql_execute_params($sql, $params)) {
                $page->messages->addSuccessMsg("Added dealer receipt");
            } else {
                $page->messages->addErrorMsg("Error adding dealer receipt");
                $success = false;
            }
        }

        if ($success) {
            $sql = "
                INSERT INTO transactions( crossrefid,  useraccountid,  refaccountid,  transtype,  transstatus,  dgrossamount,  accountname,  transdesc, offerid,  createdby, modifiedby)
                                  VALUES(:crossrefid, :useraccountid, :refaccountid, :transtype, :transstatus, :dgrossamount, :accountname, :transdesc, :offerid, :createdby, :modifiedby)
            ";
            $params = array();
            $params['crossrefid']       = $crossRefId;
            $params['useraccountid']    = $page->user->userId;
            $params['refaccountid']     = $offer['paydealerid'];
            $params['transtype']        = EFT_TRAN_TYPE_PAYMENT;
            $params['transstatus']      = "ACCEPTED";
            $params['dgrossamount']     = 0 - $this->amount;
            $params['accountname']      = $offer['paydealername'];
            $params['transdesc']        = "Payment for offer ".$this->offerId;
            $params['offerid']          = $this->offerId;
            $params['createdby']        = $page->user->username;
            $params['modifiedby']       = $page->user->username;

            if ($page->db->sql_execute_params($sql, $params)) {
                $page->messages->addSuccessMsg("Added user payment");
            } else {
                $page->messages->addErrorMsg("Error adding user payment");
                $success = false;
            }
        }

        if ($inTransaction) {
            if ($success) {
                $page->db->sql_commit_trans();
                header("location:myEFTaccount.php?pgsmsg=".urlencode("Payment completed for offer ".$this->offerId));
                $msgSubject = "Payment completed";
                $msgBody = "You have received a payment of ".floatToMoney($this->amount)." from ".$page->user->username." for offer #".$this->offerId.".";
                $msgId = $page->iMessage->insertSystemMessage($page, $this->payDealerId, $this->payDealerName, $msgSubject, $msgBody, EMAIL);
                $this->amount = NULL;
            } else {
                $page->db->sql_rollback_trans();
            }
        }
    }

    public function makeInitialBalance($userId, $userName, $asOfDate) {
        global $page;

        $success = true;

        if ($success) {
            $sql = "
                INSERT INTO transactions( crossrefid,  useraccountid,  refaccountid,  transtype, transstatus, transdate,  dgrossamount,  accountname,  transdesc, offerid,  createdby, createdate, modifiedby, modifydate)
                                  VALUES(:crossrefid, :useraccountid, :refaccountid, :transtype, :transstatus, :transdate, :dgrossamount, :accountname, :transdesc, :offerid, :createdby, :createdate, :modifiedby, :modifydate)
            ";
            $params = array();
            $params['crossrefid']       = -1;
            $params['useraccountid']    = $userId;
            $params['refaccountid']     = NULL;
            $params['transtype']        = EFT_TRAN_TYPE_BALANCE;
            $params['transstatus']      = "ACCEPTED";
            $params['transdate']        = $asOfDate;
            $params['dgrossamount']     = 0.00;
            $params['accountname']      = $userName;
            $params['transdesc']        = "Initial Balance as of ".date('m/d/Y H:i:s', $asOfDate);
            $params['offerid']          = NULL;
            $params['createdby']        = $page->user->username;
            $params['createdate']       = $asOfDate;
            $params['modifiedby']       = $page->user->username;
            $params['modifydate']       = $asOfDate;

            if ($page->db->sql_execute_params($sql, $params)) {
                $page->messages->addSuccessMsg("Added initial balance");
            } else {
                $page->messages->addErrorMsg("Error adding initial balance");
                $success = false;
            }
        }

        return $success;
    }

    public function makeTransfer() {
        global $page, $UTILITY;

        $success = true;
        $offer = NULL;
        $crossRefId = NULL;

        $success = true;

        if ($this->amount > 0) {
            if ($this->amount > $this->ledgerBalance) {
                if ($this->creditLine > 0) {
                    if ($this->amount > ($this->ledgerBalance + $this->creditLine)) {
                        $page->messages->addErrorMsg("Transfer amount of ".floatToMoney($this->amount)." exceeds available credit limit");
                        $success = false;
                    }
                } else {
                    $page->messages->addErrorMsg("Insufficient funds to transfer ".floatToMoney($this->amount));
                    $success = false;
                }
            }
        } else {
            $page->messages->addErrorMsg("Amount to transfer must be positive");
            $success = false;
        }

        if ($this->payDealerName) {
            if (! $this->payDealerId) {
                $page->messages->addErrorMsg("Invalid Dealer Name");
                $success = false;
            }
        } else {
            $page->messages->addErrorMsg("Dealer Name is required");
            $success = false;
        }

        if ($success) {
            $page->db->sql_begin_trans();

            $crossRefId = $this->getCrossRefId();

            $sql = "
                INSERT INTO transactions( crossrefid,  useraccountid,  refaccountid,  transtype, transstatus,  dgrossamount,  accountname,  transdesc, offerid,  createdby, modifiedby)
                                  VALUES(:crossrefid, :useraccountid, :refaccountid, :transtype, :transstatus, :dgrossamount, :accountname, :transdesc, :offerid, :createdby, :modifiedby)
            ";
            $params = array();
            $params['crossrefid']       = $crossRefId;
            $params['useraccountid']    = $this->payDealerId;
            $params['refaccountid']     = $page->user->userId;
            $params['transtype']        = EFT_TRAN_TYPE_RECEIPT;
            $params['transstatus']      = "ACCEPTED";
            $params['dgrossamount']     = $this->amount;
            $params['accountname']      = $page->user->username;
            $params['transdesc']        = (empty($this->description)) ? "Transfer Receipt " : "Transfer Receipt - ".substr($this->description, 0, 250);
            $params['offerid']          = NULL;
            $params['createdby']        = $page->user->username;
            $params['modifiedby']       = $page->user->username;

            if ($page->db->sql_execute_params($sql, $params)) {
                $page->messages->addSuccessMsg("Added dealer receipt");
            } else {
                $page->messages->addErrorMsg("Error adding dealer receipt");
                $success = false;
            }
        }

        if ($success) {
            $sql = "
                INSERT INTO transactions( crossrefid,  useraccountid,  refaccountid,  transtype,  transstatus,  dgrossamount,  accountname,  transdesc, offerid,  createdby, modifiedby)
                                  VALUES(:crossrefid, :useraccountid, :refaccountid, :transtype, :transstatus, :dgrossamount, :accountname, :transdesc, :offerid, :createdby, :modifiedby)
            ";
            $params = array();
            $params['crossrefid']       = $crossRefId;
            $params['useraccountid']    = $page->user->userId;
            $params['refaccountid']     = $this->payDealerId;
            $params['transtype']        = EFT_TRAN_TYPE_PAYMENT;
            $params['transstatus']      = "ACCEPTED";
            $params['dgrossamount']     = 0 - $this->amount;
            $params['accountname']      = $this->payDealerName;
            $params['transdesc']        = (empty($this->description)) ? "Transfer Payment " : "Transfer Receipt - ".substr($this->description, 0, 250);
            $params['offerid']          = NULL;
            $params['createdby']        = $page->user->username;
            $params['modifiedby']       = $page->user->username;

            if ($page->db->sql_execute_params($sql, $params)) {
                $page->messages->addSuccessMsg("Added user payment");
            } else {
                $page->messages->addErrorMsg("Error user payment");
                $success = false;
            }
        }

        if ($success) {
            $page->db->sql_commit_trans();
            $msgSubject = "Transfer completed";
            $msgBody = "You have received a transfer of ".floatToMoney($this->amount)." from ".$page->user->username.".";
            $msgId = $page->iMessage->insertSystemMessage($page, $this->payDealerId, $this->payDealerName, $msgSubject, $msgBody, EMAIL);
            header("location:myEFTaccount.php?pgsmsg=".urlencode("Transferred ".floatToMoney($amount)." to ".$this->payDealerName));
            $this->amount = NULL;
        } else {
            $page->db->sql_rollback_trans();
        }
    }

    public function makeDeposit() {
        global $page;

        $success = true;

        $amount = $this->amount;
        if (($amount < $page->cfg->EFT_MIN_DEPOSIT) || ($amount > $page->cfg->EFT_MAX_DEPOSIT)) {
            $page->messages->addErrorMsg("Amount must be between ".floatToMoney($page->cfg->EFT_MIN_DEPOSIT)." and ".floatToMoney($page->cfg->EFT_MAX_DEPOSIT));
            $success = false;
        }

        if ($success) {
            $msgSubject = "Deposit request from ".$page->user->username." (".$page->user->userId.")";
            $cashInURL = "<a href='myEFTaction.php?action=cashin&amount=".urlencode($amount)."&paydealername=".$page->user->username."&description=".urlencode("Deposit request from ".$page->user->username." for ".$amount)."' target=_blank>Cash In Link</a>";
            $msgBody = $page->user->username." (".$page->user->userId.") is requesting a deposit of ".floatToMoney($amount)." \n".$cashInURL;
            $msgId = $page->iMessage->insertSystemMessage($page, ADMINUSERID, ADMINUSERNAME, $msgSubject, $msgBody, EMAIL);
            if ($msgId) {
                header("location:myEFTaccount.php?pgsmsg=".urlencode("Credit purchase request sent for ".floatToMoney($amount)."(id:".$msgId.")"));
            } else {
                $page->messages->addErrorMsg("Error sending purchase request");
                $success = false;
            }
        }
    }

    public function makeWithdrawal() {
        global $page;

        $isPA = $page->user->isProxiedAdmin();

        $success = true;

        $billEFT = 0;
        $acceptEFT = 0;
        if ($meetWaiver = $this->getRedeemWaiver()) {
            if ($meetWaiver['billeft']) {
                $billEFT = 1;
            }
            if ($meetWaiver['accepteft']) {
                $acceptEFT = 1;
            }
        }
        $fullFee = ($billEFT && $acceptEFT) ? 0 : $page->cfg->EFT_REDEEM_FEE;

        $amount = $this->amount;
        $amountTotal = $amount + $fullFee;
        $amountNet = $amount - $fullFee;
        $availableBalance = ($this->ledgerBalance > 0) ? min($this->ledgerBalance, $page->cfg->EFT_MAX_REDEEM_AMOUNT+$fullFee) : 0;
        $minimumAmount = max(1, $fullFee);

        if (($lastRedeem = $this->getLastRedeem()) && (! $isPA)) {
            $page->messages->addErrorMsg("Limit one redeem per ".$page->cfg->EFT_REDEEM_DAYS." day period. Your last redeem was ".$lastRedeem['transdt']);
            $success = false;
        }

        if ((($amountTotal <= $minimumAmount) || ($amountTotal > $availableBalance)) && (! $isPA)) {
            $page->messages->addErrorMsg("Amount with fee must be between ".floatToMoney($minimumAmount)." and ".floatToMoney($availableBalance));
            $success = false;
        }

        // Dealer transactions
        if ($success) {
            $page->db->sql_begin_trans();

            $crossRefId = $this->getCrossRefId();

            // User transactions
            $sql = "
                    INSERT INTO transactions( crossrefid,  useraccountid,  refaccountid,  transtype, transstatus,  dgrossamount,  accountname,  transdesc,  createdby,  modifiedby)
                                      VALUES(:crossrefid, :useraccountid, :refaccountid, :transtype, :transstatus, :dgrossamount, :accountname, :transdesc, :createdby, :modifiedby)
            ";
            $params = array();
            $params['crossrefid']       = $crossRefId;
            $params['useraccountid']    = $page->user->userId;
            $params['refaccountid']     = $this->adminId;
            $params['transtype']        = EFT_TRAN_TYPE_WITHDRAWAL;
            $params['transstatus']      = "ACCEPTED";
            $params['dgrossamount']     = "-".$amount;
            $params['accountname']      = "ADMIN";
            $params['transdesc']        = "Withdrawal";
            $params['createdby']        = $page->user->username;
            $params['modifiedby']       = $page->user->username;

            if ($page->db->sql_execute_params($sql, $params)) {
                $page->messages->addSuccessMsg("Added dealer withdrawal request");
            } else {
                $page->messages->addErrorMsg("Error adding dealer withdrawal request");
                $success = false;
            }
        }

        // Admin transactions
        if ($success) {
            $sql = "
                    INSERT INTO transactions( crossrefid,  useraccountid,  refaccountid,  transtype, transstatus,  dgrossamount,  accountname,  transdesc,  createdby,  modifiedby)
                                      VALUES(:crossrefid, :useraccountid, :refaccountid, :transtype, :transstatus, :dgrossamount, :accountname, :transdesc, :createdby, :modifiedby)
            ";
            $params = array();
            $params['crossrefid']       = $crossRefId;
            $params['useraccountid']    = $this->adminId;
            $params['refaccountid']     = $page->user->userId;
            $params['transtype']        = EFT_TRAN_TYPE_WD_REQUEST;
            $params['transstatus']      = "ACCEPTED";
            $params['dgrossamount']      = $amount;
            $params['accountname']      = $page->user->username;;
            $params['transdesc']        = "Withdraw Request";
            $params['createdby']        = $page->user->username;
            $params['modifiedby']       = $page->user->username;

            if ($page->db->sql_execute_params($sql, $params)) {
                $page->messages->addSuccessMsg("Added system withdrawal request");
            } else {
                $page->messages->addErrorMsg("Error adding system withdrawal request");
                $success = false;
            }
        }

        // User Fees
        if ($success && $fullFee) {
            $sql = "
                INSERT INTO transactions( crossrefid,  useraccountid,  refaccountid,  transtype, transstatus,  dgrossamount,  accountname,  transdesc,  createdby,  modifiedby)
                                  VALUES(:crossrefid, :useraccountid, :refaccountid, :transtype, :transstatus, :dgrossamount, :accountname, :transdesc, :createdby, :modifiedby)
            ";
            $params = array();
            $params['crossrefid']       = $crossRefId;
            $params['useraccountid']    = $page->user->userId;
            $params['refaccountid']     = $this->feeId;
            $params['transtype']        = EFT_TRAN_TYPE_FEE;
            $params['transstatus']      = "ACCEPTED";
            $params['dgrossamount']      = "-".$fullFee;
            $params['accountname']      = $this->feeName;
            $params['transdesc']        = "WITHDRAWAL FEE DEBIT ".$page->user->username;
            $params['createdby']        = $page->user->username;
            $params['modifiedby']       = $page->user->username;
//echo "SQL:".$sql."<br />\nParams:<br />\n<pre>"; var_dump($params); echo "</pre><br />\n";
            if ($page->db->sql_execute_params($sql, $params)) {
                $page->messages->addSuccessMsg("Added system withdrawal fee");
            } else {
                $page->messages->addErrorMsg("Error adding system withdrawal fee");
                $success = false;
            }
        }

        // Fees Account
        if ($success && $fullFee) {
            $sql = "
                INSERT INTO transactions( crossrefid,  useraccountid,  refaccountid,  transtype, transstatus,  dgrossamount,  accountname,  transdesc,  createdby,  modifiedby)
                                  VALUES(:crossrefid, :useraccountid, :refaccountid, :transtype, :transstatus, :dgrossamount, :accountname, :transdesc, :createdby, :modifiedby)
            ";
            $params = array();
            $params['crossrefid']       = $crossRefId;
            $params['useraccountid']    = $this->feeId;
            $params['refaccountid']     = $page->user->userId;
            $params['transtype']        = EFT_TRAN_TYPE_FEE;
            $params['transstatus']      = "ACCEPTED";
            $params['dgrossamount']     = $fullFee;
            $params['accountname']      = $page->user->username;
            $params['transdesc']        = "WITHDRAWAL FEE CREDIT ".$page->user->username;
            $params['createdby']        = $page->user->username;
            $params['modifiedby']       = $page->user->username;
            if ($page->db->sql_execute_params($sql, $params)) {
                $page->messages->addSuccessMsg("Added withdrawal fee");
            } else {
                $page->messages->addErrorMsg("Error adding withdrawal fee");
                $success = false;
            }
        }

        // Admin Cash Out
        if ($success) {
            $cashOutAmount = $amount;

            $sql = "
                INSERT INTO transactions( crossrefid,  useraccountid,  transtype, transstatus,  dgrossamount,  accountname,  transdesc,  createdby,  modifiedby)
                                  VALUES(:crossrefid, :useraccountid, :transtype, :transstatus, :dgrossamount, :accountname, :transdesc, :createdby, :modifiedby)
            ";
            $params = array();
            $params['crossrefid']       = $crossRefId;
            $params['useraccountid']    = $this->adminId;
            $params['transtype']        = EFT_TRAN_TYPE_CASHOUT;
            $params['transstatus']      = "ACCEPTED";
            $params['dgrossamount']      = "-".$cashOutAmount;
            $params['accountname']      = $this->adminName;
            $params['transdesc']        = "WITHDRAWAL CASH OUT ".$page->user->username;
            $params['createdby']        = $page->user->username;
            $params['modifiedby']       = $page->user->username;
            if ($page->db->sql_execute_params($sql, $params)) {
                $page->messages->addSuccessMsg("Added system withdrawal cash out");
            } else {
                $page->messages->addErrorMsg("Error adding system withdrawal cash out");
                $success = false;
            }
        }

        if ($success) {
            $page->db->sql_commit_trans();
            $paypalId = $page->db->get_field_query("SELECT paypalid FROM userinfo WHERE userid=".$page->user->userId);
            $msgSubject = "Withdraw Requested";
            $accountURL = "<a href='myEFTaccount.php'>Your Account Summary</a>";
            $msgBody = $page->user->username." (".$page->user->userId.") is requesting a withdraw of ".floatToMoney($amount)." and paid fees of ".floatToMoney($fullFee)."\nEFT Withdraw PaypalID: ".$paypalId." \n".$accountURL;
            $msgId = $page->iMessage->insertMessage($page, ADMINUSERID, ADMINUSERNAME, $msgSubject, $msgBody, EMAIL);
            header("location:myEFTaccount.php?pgsmsg=".urlencode("Withdrawal completed for ".floatToMoney($this->amount+$fullFee)." minus fee ".floatToMoney($fullFee)." for net proceeds of ".floatToMoney($this->amount)));
        } else {
            $page->db->sql_rollback_trans();
        }
    }

    public function makeCashOut() {
        global $page;

        $success = true;

        $availableBalance = $this->ledgerBalance;
        $fee = 0;
        $amount = $this->amount;

        if (($amount <= 0) || ($amount > $availableBalance)) {
            $page->messages->addErrorMsg("Amount must be between $0.00 and ".floatToMoney($availableBalance));
            $success = false;
        }

        if ($success) {
            $page->db->sql_begin_trans();

            $crossRefId = $this->getCrossRefId();

            $sql = "
                INSERT INTO transactions( crossrefid,  useraccountid,  transtype,  transstatus,  dgrossamount,  accountname,  transdesc,  createdby,  modifiedby)
                                  VALUES(:crossrefid, :useraccountid, :transtype, :transstatus, :dgrossamount, :accountname, :transdesc, :createdby, :modifiedby)
            ";
            $params = array();
            $params['crossrefid']       = $crossRefId;
            $params['useraccountid']    = $page->user->userId;
            $params['transtype']        = EFT_TRAN_TYPE_CASHOUT;
            $params['transstatus']      = "ACCEPTED";
            $params['dgrossamount']     = "-".$amount;
            $params['accountname']      = $this->adminName;
            $params['transdesc']        = "CASHOUT-ADMIN ".$this->description;
            $params['createdby']        = $page->user->username;
            $params['modifiedby']       = $page->user->username;

            if ($page->db->sql_execute_params($sql, $params)) {
                $page->messages->addSuccessMsg("Added cash out");
            } else {
                $page->messages->addErrorMsg("Error adding cash out");
                $success = false;
            }
        }

        if ($success) {
            $page->db->sql_commit_trans();
            header("location:myEFTaccount.php?pgsmsg=".urlencode("Cash Out completed for ".floatToMoney($this->amount)));
            $this->amount = NULL;
        } else {
            $page->db->sql_rollback_trans();
        }
    }

    public function makeCashIn() {
        global $page, $UTILITY;

        $success = true;
        $inTransaction = false;
        $crossRefId = null;

        $availableBalance = $this->ledgerBalance;
        $fee = 0;
        $amount = $this->amount;

        if ($amount <= 0) {
            $page->messages->addErrorMsg("Amount must be positive");
            $success = false;
        }

        if ($success) {
            $page->db->sql_begin_trans();
            $inTransaction = true;
            $crossRefId = $this->getCrossRefId();
        }

        if ($success) {
            $sql = "
                INSERT INTO transactions( crossrefid,  useraccountid,  transtype,  transstatus,  dgrossamount,  accountname,  transdesc,  createdby,  modifiedby)
                                  VALUES(:crossrefid, :useraccountid, :transtype, :transstatus, :dgrossamount, :accountname, :transdesc, :createdby, :modifiedby)
            ";
            $params = array();
            $params['crossrefid']       = $crossRefId;
            $params['useraccountid']    = $page->user->userId;
            $params['transtype']        = EFT_TRAN_TYPE_DEPOSIT;
            $params['transstatus']      = "ACCEPTED";
            $params['dgrossamount']     = $amount;
            $params['accountname']      = $this->adminName;
            $params['transdesc']        = "DEPOSIT-ADMIN ".$this->description;
            $params['createdby']        = $page->user->username;
            $params['modifiedby']       = $page->user->username;

            if ($page->db->sql_execute_params($sql, $params)) {
                $page->messages->addSuccessMsg("Added Cash In");
            } else {
                $page->messages->addErrorMsg("Error adding Cash In");
                $success = false;
            }
        }
        if ($success && $this->payDealerId) {
            $sql = "
                INSERT INTO transactions( crossrefid,  useraccountid,  refaccountid,  transtype, transstatus,  dgrossamount,  accountname,  transdesc, offerid,  createdby, modifiedby)
                                  VALUES(:crossrefid, :useraccountid, :refaccountid, :transtype, :transstatus, :dgrossamount, :accountname, :transdesc, :offerid, :createdby, :modifiedby)
            ";
            $params = array();
            $params['crossrefid']       = $crossRefId;
            $params['useraccountid']    = $this->payDealerId;
            $params['refaccountid']     = $page->user->userId;
            $params['transtype']        = EFT_TRAN_TYPE_RECEIPT;
            $params['transstatus']      = "ACCEPTED";
            $params['dgrossamount']     = $amount;
            $params['accountname']      = $page->user->username;
            $params['transdesc']        = "Transfer Receipt";
            $params['offerid']          = NULL;
            $params['createdby']        = $page->user->username;
            $params['modifiedby']       = $page->user->username;

            if ($page->db->sql_execute_params($sql, $params)) {
                $page->messages->addSuccessMsg("Added dealer receipt");
            } else {
                $page->messages->addErrorMsg("Error adding dealer receipt");
                $success = false;
            }
        }

        if ($success && $this->payDealerId) {
            $sql = "
                INSERT INTO transactions( crossrefid,  useraccountid,  refaccountid,  transtype,  transstatus,  dgrossamount,  accountname,  transdesc, offerid,  createdby, modifiedby)
                                  VALUES(:crossrefid, :useraccountid, :refaccountid, :transtype, :transstatus, :dgrossamount, :accountname, :transdesc, :offerid, :createdby, :modifiedby)
            ";
            $params = array();
            $params['crossrefid']       = $crossRefId;
            $params['useraccountid']    = $page->user->userId;
            $params['refaccountid']     = $this->payDealerId;
            $params['transtype']        = EFT_TRAN_TYPE_PAYMENT;
            $params['transstatus']      = "ACCEPTED";
            $params['dgrossamount']     = 0 - $amount;
            $params['accountname']      = $this->payDealerName;
            $params['transdesc']        = "Transfer Payment";
            $params['offerid']          = NULL;
            $params['createdby']        = $page->user->username;
            $params['modifiedby']       = $page->user->username;

            if ($page->db->sql_execute_params($sql, $params)) {
                $page->messages->addSuccessMsg("Added user payment");
            } else {
                $page->messages->addErrorMsg("Error adding user payment");
                $success = false;
            }
        }

        if ($success) {
            $page->db->sql_commit_trans();
            $msgSubject = "Deposit completed";
            $msgBody = "Your deposit of ".floatToMoney($amount)." has been completed.";
            $msgId = $page->iMessage->insertSystemMessage($page, $this->payDealerId, $this->payDealerName, $msgSubject, $msgBody, EMAIL);
            header("location:myEFTaccount.php?pgsmsg=".urlencode("Cash In completed for ".floatToMoney($this->amount)));
            $this->amount = NULL;
        } else {
            if ($inTransaction) {
                $page->db->sql_rollback_trans();
            }
        }
    }

    function makeAcceptFees($offerInfo, $doTransaction=true) {
        global $page;

        $success = true;

        $offerSubtotal = $offerInfo['offerdsubtotal'];

        $listingFee = $offerInfo['listingfee'];
        $listingFeeAmount = 0.00;
        if ($listingFee && $offerSubtotal) {
            $listingFeeAmount = ($listingFee * $offerSubtotal) * 0.01;
        } else {
            $page->messages->addInfoMsg("No listing Fees");
        }

        $counterFeeAmount = 0.00;
        if ($offerInfo['countered'] && ($offerInfo['counterfee'] > 0.00) && $offerSubtotal) {
            $counterFeeAmount = ($offerInfo['counterfee'] * $offerSubtotal) * 0.01;
        } else {
            $page->messages->addInfoMsg("No counter Fees");
        }

        if ($listingFeeAmount || $counterFeeAmount) {
            if ($doTransaction) {
                $page->db->sql_begin_trans();
            }
            $crossRefId = $this->getCrossRefId();
        }

        if ($listingFeeAmount) {
            // Fees Account
            if ($success) {
                $sql = "
                    INSERT INTO transactions( crossrefid,  useraccountid,  refaccountid, offerid,  transtype, transstatus,  dgrossamount,  accountname,  transdesc,  createdby,  modifiedby)
                                      VALUES(:crossrefid, :useraccountid, :refaccountid, :offerid, :transtype, :transstatus, :dgrossamount, :accountname, :transdesc, :createdby, :modifiedby)
                ";
                $params = array();
                $params['crossrefid']       = $crossRefId;
                $params['useraccountid']    = $this->feeId;
                $params['refaccountid']     = $offerInfo['offerto'];
                $params['offerid']          = $offerInfo['offerid'];
                $params['transtype']        = EFT_TRAN_TYPE_FEE;
                $params['transstatus']      = "ACCEPTED";
                $params['dgrossamount']     = $listingFeeAmount;
                $params['accountname']      = $offerInfo['offertoname'];
                $params['transdesc']        = "LISTING FEE CREDIT ".$offerInfo['offertoname'];
                $params['createdby']        = $page->user->username;
                $params['modifiedby']       = $page->user->username;
                if ($page->db->sql_execute_params($sql, $params)) {
                    $page->messages->addInfoMsg("Credited listing fee");
                } else {
                    $page->messages->addErrorMsg("Error crediting listing fee");
                    $success = false;
                }
            }
            // Listing Dealer Account
            if ($success) {
                $sql = "
                    INSERT INTO transactions( crossrefid,  useraccountid,  refaccountid, offerid,  transtype, transstatus,  dgrossamount,  accountname,  transdesc,  createdby,  modifiedby)
                                      VALUES(:crossrefid, :useraccountid, :refaccountid, :offerid, :transtype, :transstatus, :dgrossamount, :accountname, :transdesc, :createdby, :modifiedby)
                ";
                $params = array();
                $params['crossrefid']       = $crossRefId;
                $params['useraccountid']    = $offerInfo['offerto'];
                $params['refaccountid']     = $this->feeId;
                $params['offerid']          = $offerInfo['offerid'];
                $params['transtype']        = EFT_TRAN_TYPE_FEE;
                $params['transstatus']      = "ACCEPTED";
                $params['dgrossamount']     = (0 - $listingFeeAmount);
                $params['accountname']      = $this->feeName;
                $params['transdesc']        = "LISTING FEE DEBIT";
                $params['createdby']        = $page->user->username;
                $params['modifiedby']       = $page->user->username;
                if ($page->db->sql_execute_params($sql, $params)) {
                    $page->messages->addInfoMsg("Debited listing fee");
                } else {
                    $page->messages->addErrorMsg("Error debiting listing fee");
                    $success = false;
                }
            }
        } else {
            $page->messages->addInfoMsg("No listing Fees");
        }

        if ($counterFeeAmount) {
            // Fees Account
            if ($success) {
                $sql = "
                    INSERT INTO transactions( crossrefid,  useraccountid,  refaccountid, offerid,  transtype, transstatus,  dgrossamount,  accountname,  transdesc,  createdby,  modifiedby)
                                      VALUES(:crossrefid, :useraccountid, :refaccountid, :offerid, :transtype, :transstatus, :dgrossamount, :accountname, :transdesc, :createdby, :modifiedby)
                ";
                $params = array();
                $params['crossrefid']       = $crossRefId;
                $params['useraccountid']    = $this->feeId;
                $params['refaccountid']     = $offerInfo['offerfrom'];
                $params['offerid']          = $offerInfo['offerid'];
                $params['transtype']        = EFT_TRAN_TYPE_FEE;
                $params['transstatus']      = "ACCEPTED";
                $params['dgrossamount']     = $counterFeeAmount;
                $params['accountname']      = $offerInfo['offerfromname'];
                $params['transdesc']        = "COUNTER FEE CREDIT ".$offerInfo['offerfromname'];
                $params['createdby']        = $page->user->username;
                $params['modifiedby']       = $page->user->username;
                if ($page->db->sql_execute_params($sql, $params)) {
                    $page->messages->addInfoMsg("Credited counter fee");
                } else {
                    $page->messages->addErrorMsg("Error crediting counter fee");
                    $success = false;
                }
            }
            // Counter Dealer Account
            if ($success) {
                $sql = "
                    INSERT INTO transactions( crossrefid,  useraccountid,  refaccountid, offerid,  transtype, transstatus,  dgrossamount,  accountname,  transdesc,  createdby,  modifiedby)
                                      VALUES(:crossrefid, :useraccountid, :refaccountid, :offerid, :transtype, :transstatus, :dgrossamount, :accountname, :transdesc, :createdby, :modifiedby)
                ";
                $params = array();
                $params['crossrefid']       = $crossRefId;
                $params['useraccountid']    = $offerInfo['offerfrom'];
                $params['refaccountid']     = $this->feeId;
                $params['offerid']          = $offerInfo['offerid'];
                $params['transtype']        = EFT_TRAN_TYPE_FEE;
                $params['transstatus']      = "ACCEPTED";
                $params['dgrossamount']     = (0 - $counterFeeAmount);
                $params['accountname']      = $this->feeName;
                $params['transdesc']        = "COUNTER FEE DEBIT";
                $params['createdby']        = $page->user->username;
                $params['modifiedby']       = $page->user->username;
                if ($page->db->sql_execute_params($sql, $params)) {
                    $page->messages->addInfoMsg("Debited counter fee");
                } else {
                    $page->messages->addErrorMsg("Error debiting counter fee");
                    $success = false;
                }
            }
        } else {
            $page->messages->addInfoMsg("No counter Fees");
        }

        if ($listingFeeAmount || $counterFeeAmount) {
            if ($success) {
                if ($doTransaction) {
                    $page->db->sql_commit_trans();
                    $page->messages->addInfoMsg("Committed Acceptance Fees");
                }
            } else {
                if ($doTransaction) {
                    $page->db->sql_rollback_trans();
                    $page->messages->addErrorMsg("Rolled Back Acceptance Fees");
                }
            }
        }

        return($success);
    }

    private function getCrossRefId() {
        global $DB;

        $sql = "
            SELECT nextval('transactions_crossrefid_seq')
        ";
        $crossRefId = $DB->get_field_query($sql);

        return $crossRefId;
    }
//get amount from ledger and compare to payment/withdrawal amount
    private function checkFunds($amount, $userId) {
        global $DB;

        $sql = "SELECT sum(dgrossamount) as available FROM transactions WHERE useraccountid=".$userId;
        $availableFunds = $DB->get_field_query($sql);

        if ($availableFunds >= $amount) {
            $acceptTransaction = TRUE;
        } else {
            $acceptTransaction = FALSE;
        }

        return $acceptTransaction;
    }

    private function getTransTypeDDM($payment = NULL) {
        global $USER;

        $types = array();
        if ($USER->isSuperAdmin() == TRUE) {
            $select = "Select";
            $types[] = array("id" => CASHOUT, "value" => CASHOUT);
            $types[] = array("id" => DEPOSIT, "value" => DEPOSIT);
            $types[] = array("id" => PAYMENT, "value" => PAYMENT);
            $types[] = array("id" => REVERSE, "value" => REVERSE);
        } elseif (!empty($payment)) {
            $select = NULL;
            $types[] = array("id" => PAYMENT, "value" => PAYMENT);
        } else {
            $select = "Select";
            $types[] = array("id" => PAYMENT, "value" => PAYMENT);
            $types[] = array("id" => WITHDRAWAL, "value" => WITHDRAWAL);
        }

        return getSelectDDM($types, "transType",  "id", "value", NULL, NULL, $select);
    }

    private function getAdminId() {
        global $DB;

        $sql = "
            SELECT userid FROM users WHERE username = 'ADMIN'
        ";
        $adminId = $DB->get_field_query($sql);

        return $adminId;
    }

    private function getTotalAmountMonth() {
        global $DB;
        global $USER;
        global $UTILITY;

        $sql = "
            SELECT SUM(dgrossamount)
              FROM transactions
             WHERE useraccountid = ".$USER->userId."
               AND transstatus = 'ACCEPTED'
               AND transdate BETWEEN ".$UTILITY->getBeginOfMonth()." AND ".$UTILITY->getEndOfMonth()."
        ";
        $totalMonth = $DB->get_field_query($sql);

        return $totalMonth;
    }

    private function getTotalAmountToEndOfMonth($userId) {
        global $DB;
        global $USER;
        global $UTILITY;

        $sql = "
            SELECT SUM(dgrossamount)
              FROM transactions
             WHERE useraccountid = ".$userId."
               AND transstatus = 'ACCEPTED'
               AND transdate < ".$UTILITY->getEndOfMonth()."
        ";
        $totalEnd = $DB->get_field_query($sql);

        return $totalEnd;
    }

    public function getRedeemWaiver() {
        global $page;

        $rows = null;
        $data = null;

        $sql = "SELECT u.userid
                , CASE WHEN ar.userid IS NULL THEN 0 ELSE 1 END AS billeft
                , CASE WHEN pp.userid IS NULL THEN 0 ELSE 1 END AS accepteft
            FROM users u
            LEFT JOIN assignedrights ar ON ar.userid=u.userid and ar.userrightid=".USERRIGHT_EFT_MEMBERSHIP."
            LEFT JOIN preferredpayment pp ON pp.userid=u.userid AND pp.paymenttypeid=1 AND pp.transactiontype='For Sale'
            WHERE u.userid=".$page->user->userId;
        //echo "getRedeemWaiver SQL:<pre>".$sql."</pre><br />\n";
        if ($rows = $page->db->sql_query_params($sql)) {
            $data = reset($rows);
        }

        return $data;
    }

    public function getLastRedeem() {
        global $page;
        global $DB;
        global $USER;
        global $UTILITY;

        $data = NULL;

        $sql = "
            SELECT
                transactionid, transdate, inttommddyyyy_slash(transdate) as transdt, transtype, transstatus
            FROM transactions
            WHERE useraccountid = ".$USER->userId."
                AND transtype = '".EFT_TRAN_TYPE_WITHDRAWAL."'
                AND transstatus = 'ACCEPTED'
                AND transdate >= datetoint(CURRENT_DATE - ".$page->cfg->EFT_REDEEM_DAYS.")
            ORDER BY transdate DESC
            LIMIT 1
        ";
        //echo "getLastRedeem SQL:".$sql."<br />\n";
        if ($rows = $DB->sql_query_params($sql)) {
            $data = reset($rows);
        }

        return $data;
    }


    private function getTransactionInfoMonth() {
        global $DB;
        global $USER;
        global $UTILITY;

        $sql = "
            SELECT inttommyy(transdate) AS date, inttommddyyyy_slash(transdate) AS day, transtype, dgrossamount, transdesc, refaccountid, crossrefid, offerid
              FROM transactions
             WHERE useraccountid = ".$USER->userId."
               AND transdate BETWEEN ".$this->getBeginOfMonth()." AND ".$this->getEndOfMonth()."
               AND transstatus = 'ACCEPTED'
             ORDER BY transdate DESC
        ";
        //echo "getTransactionInfoMonth SQL:".$sql."<br />\n";
        $data = $DB->sql_query_params($sql);

        return $data;
    }

    private function getPaymentCount() {
        global $DB;
        global $USER;
        global $UTILITY;

        $sql = "
                SELECT COUNT(*)
                  FROM transactions
                 WHERE useraccountid = ".$USER->userId."
                   AND transstatus = 'ACCEPTED'
                   AND transdate BETWEEN ".$this->getBeginYear()." AND ".$this->getEndYear()."
                   AND (transtype = 'PAYMENT' OR transtype = 'Payment')
        ";
        $totalPaymentsCount = $DB->get_field_query($sql);

        return $totalPaymentsCount;
    }

    private function getPaymentAmount() {
        global $DB;
        global $USER;
        global $UTILITY;

        $sql = "
            SELECT SUM(dgrossamount)
              FROM transactions
             WHERE useraccountid = ".$USER->userId."
               AND transstatus = 'ACCEPTED'
               AND transdate BETWEEN ".$this->getBeginYear()." AND ".$this->getEndYear()."
               AND transtype = 'PAYMENT'
                OR transtype = 'Payment'
        ";
        $totalPaymentsAmount = $DB->get_field_query($sql);

        return $totalPaymentsAmount;
    }

    private function getReceiptCount() {
        global $DB;
        global $USER;
        global $UTILITY;

        $sql = "
            SELECT COUNT(*)
              FROM transactions
             WHERE useraccountid = ".$USER->userId."
               AND transstatus = 'ACCEPTED'
               AND transdate BETWEEN ".$this->getBeginYear()." AND ".$this->getEndYear()."
               AND transtype = 'RECEIPT'
        ";
        $totalReceiptsCount = $DB->get_field_query($sql);

        return $totalReceiptsCount;

    }

    private function getReceiptAmount() {
        global $DB;
        global $USER;
        global $UTILITY;

        $sql = "
            SELECT SUM(dgrossamount)
              FROM transactions
             WHERE useraccountid = ".$USER->userId."
               AND transstatus = 'ACCEPTED'
               AND transdate BETWEEN ".$this->getBeginYear()." AND ".$this->getEndYear()."
               AND transtype = 'RECEIPT'
        ";
        $totalReceiptsAmount = $DB->get_field_query($sql);

        return $totalReceiptsAmount;
    }

    private function getWithdrawalCount() {
        global $DB;
        global $USER;
        global $UTILITY;

        $sql = "
            SELECT COUNT(*)
              FROM transactions
             WHERE useraccountid = ".$USER->userId."
               AND transstatus = 'ACCEPTED'
               AND transdate BETWEEN ".$this->getBeginYear()." AND ".$this->getEndYear()."
               AND transtype = 'WITHDRAWAL'
        ";
        $totalWithdrawalsCount = $DB->get_field_query($sql);

        return $totalWithdrawalsCount;

    }

    private function getWithdrawalAmount() {
        global $DB;
        global $USER;
        global $UTILITY;

        $sql = "
            SELECT SUM(dgrossamount)
              FROM transactions
             WHERE useraccountid = ".$USER->userId."
               AND transtype = 'WITHDRAWAL'
               AND transstatus = 'ACCEPTED'
               AND transdate BETWEEN ".$this->getBeginYear()." AND ".$this->getEndYear()."
        ";
        $totalWithdrawalsAmount = $DB->get_field_query($sql);

        return $totalWithdrawalsAmount;
    }

    private function getFeeCount() {
        global $DB;
        global $USER;
        global $UTILITY;

        $sql = "
            SELECT COUNT(*)
              FROM transactions
             WHERE useraccountid = ".$USER->userId."
               AND refaccountid = ".FEES_USERID."
               AND transstatus = 'ACCEPTED'
               AND transtype IN ('".EFT_TRAN_TYPE_PAYMENT."', '".EFT_TRAN_TYPE_RECEIPT."')
               AND transdate BETWEEN ".$this->getBeginYear()." AND ".$this->getEndYear()."
        ";
        //echo "getFeeCount SQL:<pre>".$sql."</pre><br />\n";
        $totalFeesCount = $DB->get_field_query($sql);

        return $totalFeesCount;

    }

    private function getFeeAmount() {
        global $DB;
        global $USER;
        global $UTILITY;

        $sql = "
            SELECT SUM(dgrossamount)
              FROM transactions
             WHERE useraccountid = ".$USER->userId."
               AND refaccountid = ".FEES_USERID."
               AND transstatus = 'ACCEPTED'
               AND transtype IN ('".EFT_TRAN_TYPE_PAYMENT."', '".EFT_TRAN_TYPE_RECEIPT."')
               AND transdate BETWEEN ".$this->getBeginYear()." AND ".$this->getEndYear()."
        ";
        $totalFeesAmount = $DB->get_field_query($sql);

        return $totalFeesAmount;
    }

    public function monthYearRotate() {

        $output = "";

        $nowTS = strtotime(date('Y-m',time()) . '-01 00:00:00');
        $baseTS = strtotime("+".$this->mon." months", $nowTS);
        $baseMonth = date('M', $baseTS);
        $previousMonth = date('M', strtotime("-1 months", $baseTS));
        $nextMonth = date('M', strtotime("+1 months", $baseTS));
        //echo " mon:".$this->mon." baseMonth:".$baseMonth." previousMonth:".$previousMonth." nextMonth:".$nextMonth."<br />\n";
        $output .= "<div class='button-box'>\n";
        $output .= "  <a href='?mon=".($this->mon-1)."' class='button'>".$previousMonth."</a> \n";
        $output .= "  ".$baseMonth."-".$this->year."\n";
        $output .= "  <a href='?mon=".($this->mon+1)."' class='button'>".$nextMonth."</a> \n";
        $output .= "</div>\n";

        return $output;
    }

    public function getBeginYear() {
        $thisyear   =  date($this->year);
        $fromdate   = new DateTime('1/1/'.$thisyear);
        $from       = strtotime($fromdate->format('m/d/Y H:i:s'));

        return $from;
    }

    public function getEndYear() {

        $thisyear = date($this->year);
        $todate = new DateTime('12/31/'.$thisyear);
        $todate->modify('-1 second');
        $to = strtotime($todate->format('m/d/Y H:i:s'));

        return $to;
    }

    public function getBeginOfMonth() {

        $month = date('m');
        $year = date('Y');
        $fromdate = new DateTime($month.'/1/'.$year);
        date_date_set($fromdate, $this->year,$this->mon,01);
//        $fromdate->modify($this->mon.' month');
        $from = strtotime($fromdate->format('m/d/Y H:i:s'));
//        echo "Month: ".$month." Year ".$year." BeginOfMonth:".$fromdate->format('m/d/Y H:i:s')."(".$from.")<br />\n";

        return $from;
    }

    public function getEndOfMonth() {

        $month = date('m');
        $year = date('Y');
        $todate = new DateTime($month.'/1/'.$year);
        date_date_set($todate, $this->year,$this->mon+1,01);
//        $todate->modify(($this->mon +1).' month');
        $todate->modify('-1 second');

        $to = strtotime($todate->format('m/d/Y H:i:s'));
//        echo "EndOfMonth:".$todate->format('m/d/Y H:i:s')."(".$to.")<br />\n";

        return $to;
    }

    public function getInitialBalanceInfo() {
        global $DB;
        global $USER;

        $info = null;
        $sql = "SELECT * FROM transactions WHERE useraccountid=".$USER->userId." AND transtype='".EFT_TRAN_TYPE_BALANCE."' ORDER BY transdate LIMIT 1";

        if ($result = $DB->sql_query($sql)) {
            $info = reset($result);
        }

        return $info;
    }

    public function getBeginOfMonthTotal() {
        global $DB;
        global $USER;

        $begin = $this->getBeginOfMonth();

        $sql = "
            SELECT COALESCE(SUM(dgrossamount), 0) as balance
              FROM transactions
             WHERE transdate < ".$begin."
               AND transstatus = 'ACCEPTED'
               AND useraccountid = ".$USER->userId."
        ";

        $result = $DB->get_field_query($sql);

        return $result;

    }

    public function getEndOfMonthTotal() {
        global $DB;
        global $USER;

        $end = $this->getEndOfMonth();

        $sql = "
            SELECT COALESCE(SUM(dgrossamount), 0) as balance
              FROM transactions
             WHERE transdate <= ".$end."
               AND transstatus = 'ACCEPTED'
               AND useraccountid = ".$USER->userId."
        ";

        $result = $DB->get_field_query($sql);

        return $result;

    }


    public function usedMonthly() {
        global $DB;
        global $USER;

        $begin = $this->getBeginOfMonth();
        $end = $this->getEndOfMonth();

        $sql = "
            SELECT SUM(dgrossamount)::NUMERIC
              FROM transactions
             WHERE useraccountid = ".$USER->userId."
               AND transstatus = 'ACCEPTED'
               AND transdate BETWEEN ".$begin." AND ".$end."
        ";
        $result = $DB->get_field_query($sql);

        return $result;

    }

    public function dailyAverage() {
//Daily average spent for the month
        global $UTILITY;

        $days = date('t');
        $total = $this->usedMonthly();
        $average = $total / $days;
        $dailyAverage = number_format($average, 2, '.', '');

        return $dailyAverage;
    }
//////////////////////////////////////////////////////////////////////////////////
    public function getOffer($offerId) {
        global $page;

        $sql = "
            SELECT itm.offeritemid,
                   ofr.offerid, ofr.offerdsubtotal, ofr.unaltsubtotal, 0 AS fee,
                   ofr.offerexpiration, ofr.offernotes, ofr.offerto, ofr.offerfrom, inttommddyyyy_slash(ofr.createdate),
                   u.username,
                   CASE WHEN ofr.transactiontype='For Sale' THEN ofr.offerto ELSE ofr.offerfrom END AS paydealerid,
                   CASE WHEN ofr.transactiontype='For Sale' THEN uto.username ELSE ufrom.username END AS paydealername
              FROM offeritems itm
              JOIN offers ofr       ON ofr.offerid  = itm. offerid
              JOIN users u          ON u.userid     = itm.touserid
              JOIN users uto        ON uto.userid   = ofr.offerto
              JOIN users ufrom      ON ufrom.userid = ofr.offerfrom
             WHERE ofr.offerid      = ".$offerId."
             ORDER BY ofr.offerfrom, ofr.createdate
        ";
        //echo "SQL:".$sql."<br />\n";
        $data = $page->db->sql_query($sql);

        return $data;
    }

    function isChecked($check, $checked) {

        if ($check == $checked) {
            $data = " selected";
        } else {
            $data = "";
        }

        return $data;
    }
}

?>