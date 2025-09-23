<?php
require_once('templateAdmin.class.php');
require_once('templateBlank.class.php');

$printpreview   = optional_param('print', 0, PARAM_INT);
if (empty($printpreview)) {
    $page = new templateAdmin(LOGIN, SHOWMSG);
} else {
    $page = new templateBlank(LOGIN, SHOWMSG);
}

$export         = optional_param('export', 0, PARAM_INT);
if (!empty($export)) {
    $x = export();
    if (empty($x)) {
        $page->messages->addErrorMsg("ERROR: Unable to export credit balance information");
    }
}


echo $page->header('Credit Balance Report');
if (empty($printpreview)) {
    echo mainContent();
} else {
    printpreview();
}
echo $page->footer(true);

function mainContent() {
    global $printpreview, $export;

    $data = getData();
    echo "<H3>EFT Credit Balance Report</H3>\n";
    echo "<article>\n";
    echo "  <div>\n";
    echo "    <form name='balancerpt' id='balancerpt' method='post' action='".$_SERVER['PHP_SELF']."'>\n";
    echo "      <input type='hidden' name='export' id='export' value=''>\n";
    echo "      <input type='hidden' name='print' id='print' value=''>\n";
    echo "    </form>\n";
    echo "    <table>\n";
    if (empty($printpreview)) {
        echo "      <caption>\n";
        echo "        <a href=\"Javascript: $('#balancerpt').attr('target', '_blank'); $('#print').val(1); $('#balancerpt').submit();\" class='icon print'>Print</a>&nbsp;&nbsp;\n";
        echo "        <a href=\"Javascript: $('#balancerpt').attr('target', '_self'); $('#export').val(1); $('#balancerpt').submit();\" class='icon export'>Export</a>\n";
        echo "      </caption>\n";
    }
    echo "      <thead>\n";
    echo "        <tr>\n";
    echo "          <th colspan='2'>Member ID</th>\n";
    echo "          <th>Member</th>\n";
    echo "          <th>Date of Last Positive Balance</th>\n";
    echo "          <th>Current Balance</th>\n";
    echo "          <th>Deposits</th>\n";
    echo "          <th>Number of Deposits</th>\n";
    echo "          <th>Credit Line</th>\n";
    echo "        </tr>\n";
    echo "      </thead>\n";
    echo "      <tbody>\n";
    if (!empty($data)) {
        $x = 0;
        $totalbalance = 0;
        foreach($data as $d) {
            $x++;
            echo "        <tr>\n";
            echo "          <td class='number'>".$x.".</td>\n";
            $url = "/dealerProfile.php?dealerId=".$d["userid"];
            $link = "<a href='".$url."' target='_blank'>".$d["username"]."</a>";
            echo "          <td class='center'>".$d["userid"]."</td>\n";
            echo "          <td>".$link."</td>\n";
            echo "          <td class='date'>".$d["last_positive_balance_date"]."</td>\n";
            echo "          <td class='number'>$".number_format($d["balance"], 2)."</td>\n";
            echo "          <td class='number'>$".number_format($d["deposits"], 2)."</td>\n";
            echo "          <td class='center'>".$d["number_of_deposits"]."</td>\n";
            echo "          <td class='number'>$".number_format($d["credit"], 2)."</td>\n";
            echo "        </tr>\n";
            $totalbalance += $d["balance"];
        }
    }
    echo "        <tr>\n";
    echo "          <td colspan='4'></td>\n";
    echo "          <td class='number'>".number_format($totalbalance, 2)."</td>\n";
    echo "          <td colspan='3'></td>\n";
    echo "        </tr>\n";
    echo "      </tbody>\n";
    echo "    </table>\n";
    echo "  </div>\n";
    echo "</article>\n";
    echo "<br />\n";

}

function getData() {
    global $page;

    $sql = "DROP TABLE IF EXISTS neg_balances";
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS last_pos_balance;";
    $page->queries->AddQuery($sql);
    $sql = "DROP TABLE IF EXISTS total_deposts;";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE neg_balances AS
        SELECT u.userid, u.username,
               eft.balance, eft.credit, eft.available, eft.asofdate
          FROM eftbalances      eft
          JOIN users            u   ON  u.userid = eft.userid
         WHERE asofdate = todaytoint()
           AND balance < 0
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE last_pos_balance AS
        SELECT nb.userid, max(eft.asofdate) as last_positive
          FROM eftbalances      eft
          JOIN neg_balances     nb  ON  nb.userid = eft.userid
         WHERE eft.balance >= 0
        GROUP BY nb.userid
    ";
    $page->queries->AddQuery($sql);

    $sql = "
        CREATE TEMPORARY TABLE total_deposts AS
        SELECT t.useraccountid as userid, sum(t.dgrossamount) as deposits,
               count(1) as number_of_deposits
          FROM transactions     t
          JOIN neg_balances     nb  ON  nb.userid   = t.useraccountid
         WHERE to_timestamp(t.transdate) > (NOW() - interval ' 6 months')
           AND t.dgrossamount   > 0
           AND t.refaccountid   = 321
        GROUP BY t.useraccountid
    ";
    $page->queries->AddQuery($sql);

    $page->queries->ProcessQueries();

    $sql = "
        SELECT nb.userid, nb.username,
               inttodate(lpb.last_positive) as last_positive_balance_date,
               nb.balance, td.deposits, td.number_of_deposits, nb.credit
          FROM neg_balances             nb
          LEFT JOIN last_pos_balance    lpb ON  lpb.userid  = nb.userid
          LEFT JOIN total_deposts       td  ON  td.userid   = nb.userid
        ORDER BY lpb.last_positive, nb.username
    ";

    try {
        $result = $page->db->sql_query_params($sql);
    } catch (Exception $e) {
        $page->messages->addErrorMsg("Error: ".$e->getMessage()." [Retrieving report data]");
        $result = null;
    } finally {
        unset($page->queries);
        $page->queries  = new DBQueries("", $page->messages);
        $sql = "DROP TABLE IF EXISTS neg_balances";
        $page->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS last_pos_balance;";
        $page->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS total_deposts;";
        $page->queries->AddQuery($sql);
        $page->queries->ProcessQueries();
    }

    return $result;

}

function export() {
    global $page;

    $data = getData();
    $filename = "";
    if (!empty($data)) {
        $filename = date('Ymd_His')."_creditbalancerpt.csv";
        $page->utility->export($data, $filename);
    }

    return $filename;
}

function printpreview() {
    global $page;

    echo "<h3>Dealernet Credit Balance Report</h3>\n";
    $data = getData();
    if (!empty($data)) {
        $page->utility->printpreview($data);
    }
}
?>