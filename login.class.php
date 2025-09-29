<?php
require_once('setup.php');
//change to login
class login {

    private $db;
    public $messages;
    private $out;
    private $user;
    private $utility;
    public $iMessage;
    public $queries;

    public function __construct() {
        global $DB, $MESSAGES, $USER, $UTILITY;

        $this->db           = $DB;
        $this->iMessage     = new internalMessage;
        $this->messages     = $MESSAGES;
        $this->queries      = new DBQueries("",$messages);
        $this->user         = $USER;
        $this->utility      = $UTILITY;

    }

    public function logout() {
        session_destroy();
        header('location:home.php');
        exit();
    }

    public function confirmLogin($userName, $userPass) {
        global $DB, $MESSAGES, $USER, $UTILITY;
        $success = FALSE;
        $ipdata = "";
        $mobile = 0;
        $ip = "";
        $ipResponse = "";

        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        if (
            preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$userAgent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($userAgent,0,4))) {
            $mobile = 1;
        }

        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }
        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];

        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        }
        elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }

        if ($mobile == 0) {
            $ipResponse = file_get_contents('http://ip-api.com/json/'.$ip);
            $data = json_decode($ipResponse);
            if ($data->status != "success") {
                $empty = array();
                $empty['city'] = "";
                $empty['region'] = "";
                $empty['country'] = "";

                $data = json_decode(json_encode($empty));
            }
        }

/* TODO: IP blocking is not currently being checked!!!
        $sql = "
            SELECT userid, blockeduserip
              FROM blockedips
             WHERE blockeduserip = '".$ip."'";
        $blocked = $this->db->sql_query($sql);
*/
        if (isset($blocked)) {
            $MESSAGES->addErrorMsg("This IP - ".$ip."has been blocked. Contact Admin for details.");
            $success = false;
        } else {
            $uName = strtolower($userName);
            /***
             * We noticed that when we brought over the passwords from B2B
             * that they were all capitalized.  We need to make it
             * case insensive for the time being.
             *
             * 04/06/2025 - JFS - Mike wants to make sure only premium, vendor and basic members can log in.
             ***/
            $uPass = strtoupper($userPass);
            $sql = "
                SELECT u.userid, u.lastlogin
                  FROM users            u
                  JOIN userinfo         ui  ON  ui.userid       = u.userid
                                            AND ui.userclassid IN (2,3,4,5) -- basic, vendor, suspended and premium
                  JOIN assignedrights   ar  ON  ar.userid       = u.userid
                                            AND ar.userrightid  = 1
                 WHERE LOWER(u.username) = '".$uName."'
                   AND u.userpass = crypt('".$uPass."', u.userpass)
            ";
            if ($result = $this->db->sql_query($sql)) {
                $x = reset($result);
                $_SESSION['userId']     = $x['userid'];
                $_SESSION['lastlogin']  = $x['lastlogin'];
                $success = true;
                $sql = "
                    INSERT INTO loginlog( userid,  logindate,  loginbrowser,  ipaddress,  loginreverse)
                                  VALUES(:userid, nowtoint(), :loginbrowser, :ipaddress, :loginreverse)
                ";
                $params = array();
                $params['userid']       = $_SESSION['userId'];
                $params['loginbrowser'] = $userAgent;
                $params['ipaddress']    = $ip;
                $params['loginreverse'] = gethostbyaddr($ip);

                $this->db->sql_execute_params($sql, $params);

                $sql = "
                    DELETE FROM assignedrights
                     WHERE userid       = ".$x['userid']."
                       AND userrightid  = 61  -- stale user
                ";
                $this->db->sql_execute($sql);

                $sql = "
                    UPDATE users SET lastlogin = nowtoint()
                     WHERE userid = ".$_SESSION['userId'];
                $this->db->sql_execute_params($sql);

                if ($this->getNotificationTriggers()) {
                    $_SESSION["notificationpageviews"] = NOTIFICATIONPAGEVIEWS;
                }
            } else {
                $url    = "/contactus_nologin.php";
                $link   = "<a href='".$url."'>Help Desk</a>";
                $MESSAGES->addErrorMsg("Invalid login; please try again or contact ".$link." for assistance.");
            }
        }

        return $success;
    }

    public function getNotificationTriggers() {
        global $page;

        $fromdate = $_SESSION['lastlogin'];
        $sql = "
            SELECT toid as id
              FROM messaging
             WHERE createdate > ".$fromdate."
               AND toid = ".$_SESSION['userId']."
            UNION
            SELECT offerto as id
              FROM offers
             WHERE modifydate   > ".$fromdate."
               AND offerstatus in ('PENDING', 'CANCELLED', 'VOID')
               AND offerto      = ".$_SESSION['userId']."
            UNION
            SELECT offerfrom as id
              FROM offers
             WHERE modifydate   > ".$fromdate."
               AND offerstatus in ('EXPIRED', 'DECLINED', 'ACCEPTED', 'VOID')
               AND offerfrom    = ".$_SESSION['userId']."
            UNION
            SELECT useraccountid as id
              FROM transactions
             WHERE createdate       > ".$fromdate."
               AND transtype = 'RECEIPT'
               AND useraccountid    = ".$_SESSION['userId']."
            LIMIT 1
        ";

//      echo "<pre>".$sql."</pre>";
        $bob = $this->db->get_field_query($sql);
        $retval = (empty($bob)) ? false : true;

        return $retval;
    }

    public function hasSMSNotification() {
        global $page;

        $sql = "
            SELECT preferenceid
              FROM notification_preferences
             WHERE notification_type    = 'SMS'
               AND userid               = ".$_SESSION['userId']."
               AND (validated_on IS NOT NULL
                    OR isactive = 0)
           LIMIT 1
        ";
//      echo "<pre>".$sql."</pre>";
        $bob = $this->db->get_field_query($sql);
        $retval = (empty($bob)) ? false : true;

        return $retval;
    }
}
?>