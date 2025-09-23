<?php
require_once('templateAdmin.class.php');
$page = new templateAdmin(LOGIN, SHOWMSG);

echo $page->header('EFT Balance Report');
echo mainContent();
echo $page->footer(true);

function mainContent() {

    $data = getData();
    echo "<H3>EFT Balance</H3>\n";
    echo "<article>\n";
    echo "  <div>\n";
    echo "    <table>\n";
    echo "      <thead>\n";
    echo "        <tr>\n";
    echo "          <th>Member</th>\n";
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
        foreach($data as $d) {
            $x++;
            if ($x % 3 == 1) {
                echo "        <tr>\n";
            }
            echo "          <td class='table-section-sep'>".$x.") ".$d["username"]."</td>\n";
            echo "          <td>$".number_format($d["balance"], 2)."</td>\n";
            if ($x % 3 == 0 || $x == $cnt) {
                echo "        </tr>\n";
            }
        }
    }
    echo "      </tbody>\n";
    echo "    </table>\n";
    echo "  </div>\n";
    echo "</article>\n";
    echo "<br />\n";

}

function getData() {
    global $page;

    $sql = "
        SELECT x.username, x.balance
          FROM (
            SELECT u.username, COALESCE(SUM(t.dgrossamount), 0) as balance
              FROM transactions t
              JOIN users        u   ON  u.userid = t.useraccountid
            GROUP BY u.username
               ) x
         WHERE x.balance <> 0
        ORDER BY x.balance DESC
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