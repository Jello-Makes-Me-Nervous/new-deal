<?php

require_once('template.class.php');

class templateCommon extends template{


    public function __construct($loginRequired = true, $showmsgs = true, $bypassMustReplyCheck = false) {
        parent::__construct($loginRequired, $showmsgs, $bypassMustReplyCheck);

    }

}


?>