<?php

DEFINE ('MSG_TYPE_ERROR',   'errormsg');
DEFINE ('MSG_TYPE_INFO',    'infomsg');
DEFINE ('MSG_TYPE_SUCCESS', 'successmsg');
DEFINE ('MSG_TYPE_WARNING', 'warningmsg');

class Messages {

    public $msgs;

    function __construct() {
        $this->msgs = array();
        if ($msg = optional_param('pgemsg', NULL, PARAM_RAW)) {
            $this->addErrorMsg($msg);
        }
        if ($msg = optional_param('pgimsg', NULL, PARAM_RAW)) {
            $this->addInfoMsg($msg);
        }
        if ($msg = optional_param('pgsmsg', NULL, PARAM_RAW)) {
            $this->addSuccessMsg($msg);
        }
        if ($msg = optional_param('pgwmsg', NULL, PARAM_RAW)) {
            $this->addWarningMsg($msg);
        }

    }

    private function addMsg($msgtext, $msgtype = NULL) {
        $msg = array();
        $type = (empty($msgtype)) ? MSG_TYPE_INFO : $msgtype;
        if(!empty($msgtext)) {
            $msg['msgtext'] = $msgtext;
            $msg['msgtype'] = $type;
            $this->msgs[] = $msg;
        }
    }

    public function addErrorMsg($msgtext) {
        $this->addMsg($msgtext, MSG_TYPE_ERROR);
    }

    public function addInfoMsg($msgtext) {
        $this->addMsg($msgtext, MSG_TYPE_INFO);
    }

    public function addSuccessMsg($msgtext) {
        $this->addMsg($msgtext, MSG_TYPE_SUCCESS);
    }

    public function addWarningMsg($msgtext) {
        $this->addMsg($msgtext, MSG_TYPE_WARNING);
    }

    public function hasMsgs() {
        return (is_array($this->msgs) && count($this->msgs) > 0);
    }
//P
    public function recentMsgs($msgs) {
        reset($msgs);
    }
//P
    public function clearMsgs($msgs) {
        unset($msgs);
    }
//P
    public function parseMsg($msg, &$msgtype) {
        $msgtype = NULL;
        $msgtext = NULL;
        if(is_array($msg) && isset($msg['msgtype']) && isset($msg['msgtext'])) {
            $msgtype = $msg['msgtype'];
            $msgtext = $msg['msgtext'];
        }
        return $msgtext;
    }

    public function getMsg(&$msgtype) {
        $currentmsgtext = NULL;
        if(is_array($this->msgs)) {
            if($currentmsgtext = $this->parseMsg(current($this->msgs), $msgtype)) {
                $nxt = next($this->msgs);
            }
        }
        return $currentmsgtext;
    }

    public function displayMessages() {
        $output = "\n";
        if($this->hasMsgs()) {
            $output .= "<div>\n";
            foreach($this->msgs as $msg) {
                $msgtext = $this->parseMsg($msg, $class);
                $output .= "  <p class='".$class."'>".$msgtext."</p>\n";
            }
            $output .= "</div>\n";
        }
        return $output;
    }

    public function showMessage($msgtext, $class=MSG_TYPE_INFO) {
        $output = "<div><p class='".$class."'>".$msgtext."</p></div>\n";
        return $output;
    }
}
?>