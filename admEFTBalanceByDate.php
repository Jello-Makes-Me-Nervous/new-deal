<?php
require_once('templateAdmin.class.php');
$page = new templateAdmin(LOGIN, SHOWMSG);

$calendarJS = '
    $(function(){$("#asofdate").datepicker();});
';
$page->jsInit($calendarJS);

echo $page->header('EFT Balance By Date Report');
echo mainContent();
echo $page->footer(true);

function mainContent() {

    $asofdate   = optional_param('asofdate', NULL, PARAM_RAW);
    if (!empty($asofdate)) {
        $asofdateTime = strtotime($asofdate);
        if (!$asofdateTime) {
            $page->messages->addErrorMsg("Invalid As of Date");
        }
    } else {
        $asofdate = date("m/d/Y");
        $asofdateTime = strtotime($asofdate);
    }
    $data = getData($asofdateTime);
    echo "<H3>EFT Balance By Date</H3>\n";
    echo "<article>\n";
    echo "  <div>\n";
    echo "    <div class='entry-content'>\n";
    echo "      <form name ='search' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "        <p>\n";
    echo "          <span><b>As of Date: </b></span>\n";
    echo "          <input type='text' name='asofdate' id='asofdate' value='".date("m/d/Y", $asofdateTime)."'>\n";
    echo "          &nbsp;&nbsp;<input type='submit' name='search' value='Search'\n";
    echo "        </p>\n";
    echo "      </form>\n";
    echo "    </div>\n";
    echo "    <table>\n";
    echo "      <thead>\n";
    echo "        <tr>\n";
    echo "          <th>Member</th>\n";
    echo "          <th>Balance</th>\n";
    echo "          <th class='table-section-sep'>Member</th>\n";
    echo "          <th>Balance</th>\n";
    echo "          <th class='table-section-sep'>Member</th>\n";
    echo "          <th>Balance</th>\n";
    echo "          <th class='table-section-sep'>Member</th>\n";
    echo "          <th>Balance</th>\n";
    echo "        </tr>\n";
    echo "      </thead>\n";
    echo "      <tbody>\n";
    if (!empty($data)) {
        $x = 0;
        $cnt = count($data);
        $debitBal = 0;
        $creditBal = 0;
        foreach($data as $d) {
            $x++;
            if ($x % 4 == 1) {
                echo "        <tr>\n";
            }
            echo "          <td class='table-section-sep'>".$x.") ".$d["username"]."</td>\n";
            echo "          <td>$".number_format($d["balance"], 2)."</td>\n";
            if ($x % 4 == 0 || $x == $cnt) {
                echo "        </tr>\n";
            }
            if ($d["balance"] > 0) {
                $debitBal += $d["balance"];
            } else {
                $creditBal += $d["balance"];
            }
        }
    }
    echo "      </tbody>\n";
    echo "      <tfoot>\n";
    echo "        <tr>\n";
    echo "        </tr>\n";
    echo "          <td colspan='8'>&nbsp;</td>\n";
    echo "        <tr>\n";
    echo "          <td colspan='2'>\n";
    echo "            <span><b>Credits: </b></span>\n";
    echo "            $".number_format($creditBal, 2)."\n";
    echo "          </td>\n";
    echo "          <td class='table-section-sep' colspan='2'>\n";
    echo "            <span><b>Debits: </b></span>\n";
    echo "            $".number_format($debitBal, 2)."\n";
    echo "          </td>\n";
    echo "          <td class='table-section-sep' colspan='4'>\n";
    echo "            <span><b>Total: </b></span>\n";
    echo "            $".number_format($creditBal + $debitBal, 2)."\n";
    echo "          </td>\n";
    echo "        </tr>\n";
    echo "      </tfoot>\n";
    echo "    </table>\n";
    echo "  </div>\n";
    echo "</article>\n";
    echo "<br />\n";

}

function getData($asofdate) {
    global $page;

    $sql = "
        SELECT x.username, x.balance
          FROM (
            SELECT u.username, COALESCE(SUM(t.dgrossamount), 0) as balance
              FROM transactions t
              JOIN users        u   ON  u.userid = t.useraccountid
             WHERE t.transtype in ('Payment', 'PAYMENT', 'RECEIPT', 'WITHDRAWL', 'TXFR-OUT', 'CREDIT FEE', 'FEE')
               AND t.transdate <= enddatetime(".$asofdate.")
            GROUP BY u.username
               ) x
         WHERE x.balance <> 0
        ORDER BY x.username
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