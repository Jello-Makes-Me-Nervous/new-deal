<?php
/*
MSIS Queries
*/
class DBQueries {
    public $sqls;//
    public $params;//WHERE
    public $querytitles;
    public $actiontitle;
    public $msgs;
    protected $db;//CONNECT
    function __construct($actiontitle=null, &$msgs=null) {
        GLOBAL $DB;
        $this->db = &$DB;
        $this->sqls = array();
        $this->params = array();
        $this->querytitles = array();
        $this->actiontitle = $actiontitle;
        $this->msgs = &$msgs;
    }

    function AddQuery($sql, $param=null, $querytitle=null) {
        array_push($this->sqls, $sql);
        // checking params for string nulls
        if (is_array($param) && (count($param) > 0)) {
            foreach ($param as $pindex => $myparam) {
                if (gettype($myparam == 'string') && $this->notempty($myparam) && (strtolower($myparam) === 'null')) {
                    $param[$pindex] = NULL;
                }
            }
        }
        array_push($this->params, $param);
        array_push($this->querytitles, $querytitle);
    }

    function GetParam($idx) {
        if (array_key_exists($idx, $this->params)) {
            return $this->params[$idx];
        } else {
            return null;
        }
    }

    function GetQueryTitle($idx) {
        if (array_key_exists($idx, $this->querytitles)) {
            return $this->querytitles[$idx];
        } else {
            return null;
        }
    }

    function HasQueries() {
        $result = (count($this->sqls) > 0) ? true : false;
        return $result;
    }

    function ProcessQueries() {
        $success = true;
        if ($this->HasQueries()) {
            foreach($this->sqls as $idx => $sql) {
                $params = $this->GetParam($idx);
                try {
                    $rowsaffected = $this->db->sql_execute_params($sql, $params);
                }
                catch(Exception $e) {
                    if ($this->msgs) {
                        $this->msgs->add_error_msg("Database Error:  Your data may not have saved.  Please contact the PALCSchool Administrator");
                        $adminmessage = "Debug info: ".$e->debuginfo."\n";
                        $adminmessage .= "Error code: ".$e->errorcode."\n";
                        $adminmessage .= "Stack Trace: \n".format_backtrace($e->getTrace(), true);
                        $this->msgs->add_msg($adminmessage, "admin");
                    } else {
                        throw $e;
                    }
                    $success = false;
                }
            }
        } else {
            if (is_object($this->msgs)) {
                $this->msgs->add_info_msg($this->actiontitle.' no changes');
            }
        }

        return ($success);
    }

    function notempty($checkvar) {
        if (empty($checkvar)) {
            return false;
        } else {
            return true;
        }
    }
}