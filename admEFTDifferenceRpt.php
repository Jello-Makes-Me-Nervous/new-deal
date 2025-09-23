<?php
require_once('templateAdmin.class.php');
$page = new templateAdmin(LOGIN, SHOWMSG);

echo $page->header('EFT Houston, We Have A Problem Report');
echo mainContent();
echo $page->footer(true);

function mainContent() {

    $data = getData($asofdateTime);
    echo "<H3>EFT Balance By Date</H3>\n";
    echo "<article>\n";
    echo "  <div>\n";
    echo "    <table>\n";
    echo "      <thead>\n";
    echo "        <tr>\n";
    echo "          <th>Member</th>\n";
    echo "          <th>Available Amount</th>\n";
    echo "          <th>Total Gross</th>\n";
    echo "          <th>Total Fee</th>\n";
    echo "          <th>Difference</th>\n";
    echo "        </tr>\n";
    echo "      </thead>\n";
    echo "      <tbody>\n";
    if (!empty($data)) {
        foreach($data as $d) {
            echo "        <tr>\n";
            echo "          <td>".$x.") ".$d["username"]."</td>\n";
            echo "          <td style='number'>$".number_format($d["balance"], 2)."</td>\n";
            echo "        </tr>\n";
        }
    }
    echo "      </tbody>\n";
    echo "    </table>\n";
    echo "  </div>\n";
    echo "</article>\n";
    echo "<br />\n";

}

function getData($asofdate) {
    global $page;

    $sql = "
        SELECT username, available_amount, tot_trans_amt,  tot_fee_amt, (available_amount - tot_trans_amt - tot_fee_amt) as difference
          FROM users            u
          LEFT JOIN (
            select user_account_id,
                   sum(gross_amount)    as tot_trans_amt,
                   sum(fee_amount)      as tot_fee_amt
              from transactions
             where trans_status = 'ACCEPTED'
            group by user_account_id
               )                y   ON   y.user_account_id  = u.userid
          WHERE y.available_amount <> (y.tot_trans_amt + y.tot_fee_amt)
            AND y.tot_trans_amt IS NOT NULL
        ORDER BY (y.available_amount - y.tot_trans_amt - y.tot_fee_amt) desc, username
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