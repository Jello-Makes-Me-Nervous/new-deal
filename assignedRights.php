<?php

require_once('templateAdmin.class.php');
$page = new templateAdmin(LOGIN, SHOWMSG);

$isAdmin = $page->user->hasUserRight("ADMIN");
if (!$isAdmin) {
    $page->messages->addErrorMsg("ERROR: You do not have access to view this page.");
}

echo $page->header('Assigned Rights Report');
if ($isAdmin) {
    echo mainContent();
}
echo $page->footer(true);

function mainContent() {
    global $page;

    $data = getUserRights();
    $assignedRights = null;
    $params = "";
    $ps = 0;
    $p = array();
    if (optional_param("go", NULL, PARAM_RAW)) {
        foreach ($data as $d) {
            $param = optional_param($d['userrightid'], NULL, PARAM_INT);
            if (!empty($param)) {
                $params .= (empty($params)) ? $param : ",".$param;
                $p[$param] = $param;
            }
        }

        $case = "";
        $ljoin = "";
        if (!empty($p)) {
            foreach ($p as $ur) {
                $case .= "           , CASE WHEN ar".$ur.".userid IS NULL THEN '' ELSE 'X' END as hasright".$ur." \n";
                $ljoin .= "
      JOIN assignedrights ar".$ur." ON ar".$ur.".userid         = u.userid
                                    AND ar".$ur.".userrightid   = ".$ur." \n";
            }

            $assignedRights = getSelectRights($case, $ljoin);
        }
    }

    echo "<article>\n";
    echo "  <div class='entry-content'>\n";
    echo "    <form name ='' action='/assignedRights.php' method='post'>\n";
    foreach ($data as $d) {
        if ($d['userrightid'] != 1) {
            $checked = (array_key_exists($d['userrightid'], $p)) ? "checked" : "";
            echo "      <div style='float: left; padding-right: 25px;'>\n";
            echo "        <input type='checkbox' name='".$d['userrightid']."' id='".$d['userrightid']."' value='".$d['userrightid']."' ".$checked.">\n";
            echo "        <label for='".$d['userrightid']."'>".$d['userrightname']."</label>\n";
            echo "      </div>\n";
        }
    }
    echo "      <div style='float: left; clear: left;'><input  class='' type='submit' name='go' id='go' value='GO'></div>\n";
    echo "    </form>\n";
    echo "<div style='padding-top: 25px;'>&nbsp;</div>\n";
    if (!empty(optional_param("go", NULL, PARAM_RAW))) {
        echo "    <div style='margin-top:50px;'>\n";
        echo "      <table border='1'>\n";
        echo "        <thead>\n";
        echo "          <tr>\n";
        echo "            <th>User</th>\n";
        $rnames = getRightsNames($params);
        if (!empty($rnames)) {
            foreach ($rnames as $rn) {
                echo "           <th>".$rn['userrightname']."</th>\n";
            }
        }
        echo "          </tr>\n";
        echo "        </thead>\n";
        echo "      <tbody>\n";
        $username = "";
        if (!empty($assignedRights)) {
            foreach ($assignedRights as $ar) {
                if ($username != $ar['username']) {
                    echo "          <tr>\n";
                    echo "            <td>".$ar['username']."</td>\n";
                    foreach ($p as $d) {
                        $var = "hasright".$d;
                        echo "           <td class='center'>".$ar[$var]."</td>\n";
                    }
                    echo "          </tr>\n";
                }
                $username = $ar['username'];
            }
        } else {
            echo "          <tr><td colspan='99'><b>No users found with selected user rights.</b></td></tr>\n";
        }
        echo "        </tbody>\n";
        echo "      </table>\n";
        echo "    </div>\n";
    }
    echo "  </div>\n";
    echo "</article>\n";

}

function getUserRights() {
    global $page;

    $sql = "
        SELECT userrightid, userrightname
          FROM userrights
         ORDER BY userrightid ASC
    ";

    $result = $page->db->sql_query_params($sql);

    return $result;

}

function Getrightsnames($ps) {
    global $page;

    $urids = (empty($ps)) ? 1 : $ps;
    $sql = "
        SELECT userrightname
          FROM userrights
         WHERE userrightid IN(".$urids.")
         ORDER BY userrightid
    ";

    $result = $page->db->sql_query_params($sql);

    return $result;

}

function getSelectRights($cases, $ljoins) {
    global $page;

    $result = array();

    $sql = "
    SELECT u.userid, u.username
           ".$cases."
      FROM users u
      JOIN assignedrights ar ON ar.userid       = u.userid
                             AND ar.userrightid = 1
      ".$ljoins."
      ORDER BY u.username
    ";

//    echo "<pre>".$sql."</pre>";
    $result = $page->db->sql_query_params($sql);

    return $result;

}

?>