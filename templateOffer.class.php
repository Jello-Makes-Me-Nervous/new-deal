<?php
//TODO change price and qty to REVISED insert revised when making an offer
require_once('templateMarket.class.php');

class templateOffer extends templateMarket{

    public function __construct($loginRequired = true, $showmsgs = true, $bypassMustReplyCheck = true, $forcePaymentMethodVerification = true) {
        parent::__construct($loginRequired, $showmsgs, $bypassMustReplyCheck, $forcePaymentMethodVerification, false, false);
        $this->display_BottomWidget = false;
    }
}

?>