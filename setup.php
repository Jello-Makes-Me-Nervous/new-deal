<?php

DEFINE('FACTORYCOSTNAME', 'COST');
DEFINE('FACTORYCOSTID',3101);

if(session_status() == PHP_SESSION_NONE) {
    session_start();
}
//debug_print_backtrace();
require_once('advert.lib.php');

//require_once('Amessage.class.php');
//require_once('B2B_messages.class.php');
require_once('config.php');
require_once('db_access.class.php');
require_once('db_queries.class.php');
require_once('debugdisplay.class.php');
require_once('eft.class.php');
require_once('internalMessage.class.php');
require_once('listing.class.php');
require_once('listingPage.class.php');
require_once('login.class.php');
require_once('messages.class.php');
require_once('params.lib.php');
require_once('sendgrid_email.php');
require_once('shoppingCart.class.php');
require_once('ui.lib.php');
require_once('user.class.php');
require_once('utility.class.php');
require_once('web.lib.php');

if(!isset($_SESSION['userId']) || empty($_SESSION['userId'])) {
    $_SESSION['userId'] = 0;
}

$DB = new DB_Access();
$MESSAGES = new Messages();
$USER = new user($_SESSION['userId']);
$UTILITY = new utility();

global $DB, $MESSAGES, $USER, $UTILITY;


?>