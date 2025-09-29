<?php
require_once('templateMarket.class.php');

$page = new templateMarket(LOGIN, SHOWMSG);

$offer  = optional_param('offer', NULL, PARAM_INT);
$remove = optional_param('remove', NULL, PARAM_INT);


$cart = new shoppingcart($page->user->userId);

if (isset($remove)) {
    $cart->removeItem($remove);
}

$cart->syncCartUpdates($page->user->userId);
$wanted = $cart->getShoppingCart($page->user->userId, "Wanted");
$forsale = $cart->getShoppingCart($page->user->userId, "For Sale");

echo $page->header('Shopping Cart');
echo mainContent($cart, $wanted, $forsale);
echo $page->footer(true);
//should we add check boxes to enable multiple deletes??

function mainContent($cart, $wanted, $forsale) {

    $noListings = true;
    
    echo $cart->cartView($wanted, $forsale);
}


?>