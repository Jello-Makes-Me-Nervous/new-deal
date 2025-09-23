<?php
require_once('templateAdmin.class.php');
$page = new templateAdmin(LOGIN, SHOWMSG);

$asofdate    = optional_param('asofdate', NULL, PARAM_INT);

if (empty($asofdate)) {
    $data = getSummedData();
} else {
    $data = getData($asofdate);
}

echo $page->header('EFT Balance Report');
if (empty($asofdate)) {
    $data = getSummedData();
    echo displaySummedData();
} else {
    $data = getData($asofdate);
    echo displayData();
}
echo $page->footer(true);

function displaySummedData() {
    global $data;

    $output  = "";
    $output .=  "<H3>EFT Balances By Date</H3>\n";
    $output .=  "<article>\n";
    $output .=  "  <div>\n";
    $output .=  "    <table>\n";
    $output .=  "      <thead>\n";
    $output .=  "        <tr>\n";
    $output .=  "          <th>Date</th>\n";
    $output .=  "          <th>Balance</th>\n";
    $output .=  "          <th>Credit Used</th>\n";
    $output .=  "          <th>Available</th>\n";
    $output .=  "        </tr>\n";
    $output .=  "      </thead>\n";
    $output .=  "      <tbody>\n";
    if (!empty($data)) {
        $x = 0;
        $cnt = count($data);
        foreach($data as $d) {
            $output .=  "        <tr>\n";
            $url    = htmlentities($_SERVER['PHP_SELF'])."?asofdate=".$d["asofdate"];
            $link   = "<a href='".$url."'>".date("m/d/Y", $d["asofdate"])."</a>";
            $output .=  "          <td class='table-section-sep'>".$link."</td>\n";
            $output .=  "          <td class='number'>$".number_format($d["eft_blance"], 2)."</td>\n";
            $output .=  "          <td class='number'>$".number_format($d["eft_credit_used"], 2)."</td>\n";
            $output .=  "          <td class='number'>$".number_format($d["eft_available"], 2)."</td>\n";
            $output .=  "        </tr>\n";
        }
    }
    $output .=  "      </tbody>\n";
    $output .=  "    </table>\n";
    $output .=  "  </div>\n";
    $output .=  "</article>\n";
    $output .=  "<br />\n";

    return $output;

}

function displayData() {
    global $data;

    $output  = "";
    $output .=  "<H3>EFT Balance By Member</H3>\n";
    $output .=  "<article>\n";
    $output .=  "  <div>\n";
    $output .=  "    <table>\n";
    $output .=  "      <thead>\n";
    $output .=  "        <tr>\n";
    $output .=  "          <th>Member</th>\n";
    $output .=  "          <th>Balance</th>\n";
    $output .=  "          <th>Credit</th>\n";
    $output .=  "          <th>Available</th>\n";
    $output .=  "        </tr>\n";
    $output .=  "      </thead>\n";
    $output .=  "      <tbody>\n";
    if (!empty($data)) {
        $x = 0;
        $cnt = count($data);
        foreach($data as $d) {
            $output .=  "        <tr>\n";
            $output .=  "          <td>".$d["username"]."</td>\n";
            $output .=  "          <td class='number'>$".number_format($d["balance"], 2)."</td>\n";
            $output .=  "          <td class='number'>$".number_format($d["credit"], 2)."</td>\n";
            $output .=  "          <td class='number'>$".number_format($d["available"], 2)."</td>\n";
            $output .=  "        </tr>\n";
        }
    }
    $output .=  "      </tbody>\n";
    $output .=  "    </table>\n";
    $output .=  "  </div>\n";
    $output .=  "</article>\n";
    $output .=  "<br />\n";

    return $output;

}

function getSummedData() {
    global $page;

    $sql = "
        select asofdate, sum(available) as eft_available,
        sum(case when userid <> ".FEES_USERID."
                  and balance > 0 then balance end) as eft_blance,
        sum(case when balance < 0 then balance end) as eft_credit_used
          from eftbalances
        group by asofdate
        order by asofdate desc
    ";

    try {
        $result = $page->db->sql_query_params($sql);
    } catch (Exception $e) {
        $page->messages->addErrorMsg("Error: ".$e->getMessage()." [Retrieving report data]");
        $result = null;
    } finally {
    }

    return $result;
}

function getData($asofdate) {
    global $page;

    $sql = "
        select ui.userid, ui.username, eft.balance, eft.credit, eft.available
          from eftbalances  eft
          join users        ui  on  ui.userid   = eft.userid
         where eft.asofdate = ".$asofdate."
           and eft.available <> 0
        order by ui.username
    ";

    try {
        $result = $page->db->sql_query_params($sql);
    } catch (Exception $e) {
        $page->messages->addErrorMsg("Error: ".$e->getMessage()." [Retrieving report data]");
        $result = null;
    } finally {
    }

    return $result;
}

?>