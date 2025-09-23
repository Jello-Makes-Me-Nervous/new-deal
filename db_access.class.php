<?php

class DB_Access {

    public $dbErrorNum;
    public $dbErrorStr;
    public $dbRowsAffected;

    private $ConnectionStr;
    protected $oConn;

    public function __construct() {
        global $CFG;

        $this->ConnectionStr = "host=".$CFG->dbhost." dbname=".$CFG->dbname." user=".$CFG->dbuser." password=".$CFG->dbpass;
        $this->oConn = pg_connect($this->ConnectionStr);

    }

    public function sql_execute($sql) {
        global $CFG;

        $this->dbErrorStr = NULL;
        $this->dbErrorNum = 0;
        $this->dbRowsAffected = 0;
        
        $result = @pg_exec($this->oConn, $sql);
        if (!$result){
            $this->dbErrorStr = pg_last_error($this->oConn);
            $this->dbErrorNum = -1;
            if (($CFG->dberrmode == 'DISPLAY') || ($CFG->dberrmode == 'EXIT')) {
                echo "DB Error: $this->dbErrorStr<br />\n";
                if ($CFG->dberrmode == 'EXIT') {
                    exit;
                }
            }
        } else {
//var_dump($result);
            $this->dbRowsAffected = pg_affected_rows($result);
        }
        return $this->dbRowsAffected;
    }

    public function sql_query($sql) {
        global $CFG;

        $rs = NULL;

        $result = @pg_query($this->oConn, $sql);
        if (!$result){
            $this->dbErrorStr = pg_last_error($this->oConn);
            $this->dbErrorNum = -1;
            if (($CFG->dberrmode == 'DISPLAY') || ($CFG->dberrmode == 'EXIT')) {
                echo "DB Error: $this->dbErrorStr<br />\n";
                if ($CFG->dberrmode == 'EXIT') {
                    exit;
                }
            }
        } else {
            while ($row = pg_fetch_assoc($result)) {
                $rs[] = $row;
            }
        }
        return $rs;
    }
/////////////////////////////////////////////////////////////////////////
    public function sql_query_limit($sql, $limit = NULL, $offset =NULL) {
        global $CFG;

        $rs = NULL;
        if (!empty($limit)) {
            $sql = $sql." LIMIT ".$limit;
        }
        if (!empty($offset)) {
            $sql = $sql." OFFSET ".$offset;
        }
        //$sql = $sql." LIMIT ".$limit." OFFSET ".$offset;
        $result = @pg_query($this->oConn, $sql);
        if (!$result){
            $this->dbErrorStr = pg_last_error($this->oConn);
            $this->dbErrorNum = -1;
            if (($CFG->dberrmode == 'DISPLAY') || ($CFG->dberrmode == 'EXIT')) {
                echo "DB Error: $this->dbErrorStr<br />\n";
                if ($CFG->dberrmode == 'EXIT') {
                    exit;
                }
            }
        } else {
            while ($row = pg_fetch_assoc($result)) {
                $rs[] = $row;
            }
        }
        return $rs;
    }
/////////////////////////////////////////////////////////////////////////////
    public function sql_execute_params($sql, array $params = NULL) {
        global $CFG;
    
        $params = (array)$params; // make null array if needed
        $this->dbErrorStr = NULL;
        $this->dbErrorNum = 0;
        $this->dbRowsAffected = 0;
        
        //list is breaking out the pieces of the array to the fixed sql string and the array params
        list($sql, $params) = $this->fix_sql_params($sql, $params);
        $p = @pg_prepare($this->oConn, "", $sql);
        if ($p === false){
            $this->dbErrorStr = pg_last_error($this->oConn);
            $this->dbErrorNum = -1;
            if (($CFG->dberrmode == 'DISPLAY') || ($CFG->dberrmode == 'EXIT')) {
                echo "DB Error: $this->dbErrorStr<br />\n";
                if ($CFG->dberrmode == 'EXIT') {
                    exit;
                }
            }
        } else {
            $e = @pg_execute($this->oConn, "", $params);
            if ($e === false){
                //$this->dbErrorStr = pg_result_error($e);
                $this->dbErrorStr = pg_last_error($this->oConn);
                $this->dbErrorNum = -1;
                if (($CFG->dberrmode == 'DISPLAY') || ($CFG->dberrmode == 'EXIT')) {
                    echo "DB Error: $this->dbErrorStr<br />\n";
                    if ($CFG->dberrmode == 'EXIT') {
                        exit;
                    }
                }
            } else {
                $this->dbRowsAffected = pg_affected_rows($e);
            }
        }
    
        return $this->dbRowsAffected;
    }

    public function sql_query_params($sql, array $params = NULL, $limit = NULL, $offset =NULL) {
        global $CFG;

        $params = (array)$params; // make null array if needed
        $rs = NULL;
        if (!empty($limit)) {
            $sql = $sql." LIMIT ".$limit;
        }
        if (!empty($offset)) {
            $sql = $sql." OFFSET ".$offset;
        }
        
        //list is breaking out the pieces of the array to the fixed sql string and the array params
        list($sql, $params) = $this->fix_sql_params($sql, $params);
        $result = pg_query_params($this->oConn, $sql, $params);
        
        if (!$result){
            $this->dbErrorStr = pg_last_error($this->oConn);
            $this->dbErrorNum = -1;
            if (($CFG->dberrmode == 'DISPLAY') || ($CFG->dberrmode == 'EXIT')) {
                echo "DB Error: $this->dbErrorStr<br />\n";
                if ($CFG->dberrmode == 'EXIT') {
                exit;
                }
            }
        } else {
            while ($row = pg_fetch_assoc($result)) {
                $rs[] = $row;
            }
        }
        return $rs;
    }

    public function get_field_query($sql, array $params = NULL) {
        $field = NULL;
        $rs = $this->sql_query_params($sql, $params);
        if (!empty($rs)) {
            $row = reset($rs);
            $field = reset($row);
        }
        return $field;
    }

    private function fix_sql_params($sql, $params) {
        //finding the colon in the string
//$named_count = preg_match_all('/(?<!:):[a-z][a-z0-9_]*/', $sql, $named_matches);
        $named_count = preg_match_all('/(?<!:):[a-z A-Z][a-z A-Z 0-9_]*/', $sql, $named_matches);
        // for each colon changing to a number var
        $x=1;
        foreach ($named_matches[0] as $key) {
            $key2 = trim($key, ':');
            if (!array_key_exists($key2, $params)) {
                //error
            } else {
              // $sql = str_replace($key, '$'.$x, $sql, 1);
                $start = strpos($sql, $key);
                $length = strlen($key);
                $sql = substr_replace($sql, '$'.$x, $start, $length);
               $params[$x] = $params[$key2];
               unset($params[$key2]);

               $x++;
            }

        }
        //returning the fixed sql string and the array params
        return array($sql, $params);
    }

    public function sql_begin_trans() {
        $begin = "BEGIN;";
        $result = pg_query($begin);
    }

    public function sql_commit_trans() {
        $commit = "COMMIT;";
        $result = pg_query($commit);
    }

    public function sql_rollback_trans() {
        $rollback = "ROLLBACK;";
        $result = pg_query($rollback);
    }
    
    public function escape_string($string) {
        return pg_escape_string($this->oConn, $string);
    }

}
?>