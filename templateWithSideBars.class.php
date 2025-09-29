<?php
require_once('template.class.php');

class templateWithSidebars extends template {

    public $leftsidebar;

    public function __construct($loginRequired = true, $showmsgs = true, $bypassMustReplyCheck = false) {

        parent::__construct($loginRequired, $showmsgs, $bypassMustReplyCheck);

    }

    public function displayLeftWidget() {

        echo $this->leftsidebar;

    }


}
?>