<?php
require_once('templateAdmin.class.php');
$page = new templateAdmin(LOGIN, SHOWMSG);

echo $page->header('EFT Stats Report');
echo mainContent();
echo $page->footer(true);

function mainContent() {

    $data = getData();
    $summary = getSummaryData();
    echo "<H3>EFT Stats</H3>\n";
    echo "<article>\n";
    echo "  <div>\n";
    if (!empty($summary)) {
        $s = reset($summary);
        echo "    <table>\n";
        echo "      <tbody>\n";
        echo "        <tr>\n";
        echo "          <td>\n";
        echo "            <span><b>Balances: </b></span>\n";
        echo "            $".number_format($s["balances"], 2)."\n";
        echo "          </td>\n";
        echo "          <td>\n";
        echo "            <span><b>Deposits: </b></span>\n";
        echo "            $".number_format($s["deposits"], 2)."\n";
        echo "          </td>\n";
        echo "          <td>\n";
        echo "            <span><b>Withdrawals: </b></span>\n";
        echo "            $".number_format($s["withdrawals"], 2)."\n";
        echo "          </td>\n";
        echo "          <td>\n";
        echo "            <span><b>Fees: </b></span>\n";
        echo "            $".number_format($s["fees"], 2)."\n";
        echo "          </td>\n";
        echo "          <td>\n";
        echo "            <span><b>@Risk: </b></span>\n";
        $atRisk = $s["balances"] + $s["deposits"] + $s["fees"] + $s["withdrawals"];
        echo "            $".number_format($atRisk, 2)."\n";
        echo "          </td>\n";
        echo "        <tr>\n";
        echo "      </tbody>\n";
        echo "    </table>\n";
    }
    echo "    <table>\n";
    echo "      <thead>\n";
    echo "        <tr>\n";
    echo "          <th>YYYY/MM</th>\n";
    echo "          <th>Transaction Type</th>\n";
    echo "          <th>Count</th>\n";
    echo "          <th>Gross</th>\n";
    echo "          <th>Fees</th>\n";
    echo "        </tr>\n";
    echo "      </thead>\n";
    echo "      <tbody>\n";
    if (!empty($data)) {
        foreach($data as $d) {
            echo "        <tr>\n";
            echo "          <th>".$d["year_month"]."</th>\n";
            echo "          <td>".$d["trans_type"]."</td>\n";
            echo "          <td class='number'>".$d["count"]."</td>\n";
            echo "          <td class='number'>$".number_format($d["gross"], 2)."</td>\n";
            echo "          <td class='number'>$".number_format($d["fees"], 2)."</td>\n";
            echo "        </tr>\n";
        }
    }
    echo "      </tbody>\n";
    echo "    </table>\n";
    echo "  </div>\n";
    echo "</article>\n";
    echo "<br />\n";

}

function getSummaryData() {
    global $page;

    $sql = "
        select d.deposits, w.withdrawals, f.fees, b.balances
          from (
            select sum(dgrossamount) as deposits
              from transactions
             where transtype = 'DEPOSIT'
                ) d
          cross join (
            select sum(dgrossamount) as withdrawals
              from transactions
             where transtype = 'CASHOUT'
                ) w
          cross join (
            select sum(dgrossamount) as balances
              from transactions
             where transtype = 'BALANCE'
                ) b
          cross join (
            select sum(fee_amount) as fees
              from (
                  select dgrossamount as fee_amount
                    from transactions
                   where dgrossamount > 0
                     and transtype in ('FEE')
                  union
                  select dgrossamount as fee_amount
                    from transactions
                   where dgrossamount > 0
                     and transtype = 'PAYMENT'
                     and strpos(transdesc, '>Listing fee</A>') > 0
                    ) x
                ) f
    ";

    try {
        $result = $page->db->sql_query_params($sql);
    } catch (Exception $e) {
        $page->messages->addErrorMsg("Error: ".$e->getMessage()." [Retrieving summary data]");
        $result = null;
    } finally {
    }

    return $result;

}

function getData() {
    global $page;

    $sql = "
        select t.year_month, t.trans_type, count(transactionid) as count, sum(sumg) as gross , sum(fee_amount) as fees
        from (
            select transactionid, isnull(transtype,'NULL') as trans_type, to_char(inttodate(createdate)::TIMESTAMP,'YYYY/MM') as year_month,
                   dgrossamount as sumg, 0 as fee_amount
              from transactions
             where dgrossamount > 0
            UNION
            select transactionid, 'Listing Fee' as trans_type, to_char(inttodate(createdate)::TIMESTAMP,'YYYY/MM') as year_month,
                   0 as sumg, dgrossamount as fee_amount
              from transactions
             where dgrossamount > 0
               and transtype = 'Payment'
               and strpos(transdesc, '>Listing fee</A>') > 0
             ) t
        group by t.year_month, t.trans_type
        order by t.year_month desc, t.trans_type
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