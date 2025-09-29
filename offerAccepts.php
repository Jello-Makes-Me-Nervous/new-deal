<?php

require_once('templateADMIN.class.php');
$page = new templateAdmin(LOGIN, SHOWMSG);

$boxtypeid      = optional_param('boxtypeid', NULL, PARAM_INT);
$categoryid     = optional_param('categoryid', NULL, PARAM_INT);
$subcategoryid  = optional_param('subcategoryid', NULL, PARAM_INT);
$year           = optional_param('year', NULL, PARAM_TEXT);
$data = getCounts();

echo $page->header('Listing Counts');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $data;

    echo "<article>\n";
    echo "  <div class='entry-content'>\n";
    echo "    <table border='1'>\n";
    echo "      <thead>\n";
    echo "        <tr>\n";
    echo "          <th>User</th>\n";
    echo "          <th>Accepted</th>\n";
    echo "          <th>Declined</th>\n";
    echo "          <th>Revised</th>\n";
    echo "          <th>Archived</th>\n";
    echo "          <th>Total</th>\n";
    echo "          <th>Percentage</th>\n";
    echo "        </tr>\n";
    echo "     	</thead>\n";
    echo "     	<tbody>\n";
    foreach ($data as $d) {
        $total = $d['accepted'] + $d['declined'] + $d['revised'] + $d['archived'];
        $ptotal = $d['accepted'] + $d['declined'] + $d['revised'];
        if ($d['accepted'] == 0) {
            $percent = 0;
        } else {
            $percent = ($d['accepted'] / $ptotal) * 100; 
        }  
        echo "        <tr>\n";
        echo "          <td>".$d['username']."</td>\n";
        echo "          <td>".$d['accepted']."</td>\n";
        echo "          <td>".$d['declined']."</td>\n";
        echo "          <td>".$d['revised']."</td>\n";
        echo "          <td>".$d['archived']."</td>\n";
        echo "          <td>".$total."</td>\n";
        echo "          <td>".round($percent, 2)."%</td>\n";
        echo "        </tr>\n";
    }
    echo "      </tbody>\n";
    echo "    </table>\n";
    echo "  </div>\n";
    echo "</article>\n";
    echo "<br />\n";
}

function getCounts() {
    global $page;

    $sql = "
        SELECT u.username, COUNT(a.offerstatus) AS accepted, COUNT(c.offerstatus) AS cancelled, 
               COUNT(d.offerstatus) AS declined, COUNT(r.offerstatus) AS revised, COUNT(ar.offerstatus) AS archived
          FROM offers o 
          LEFT JOIN offers a ON a.offerid = o.offerid AND a.offerstatus = 'ACCEPTED'
          LEFT JOIN offers c ON c.offerid = o.offerid AND c.offerstatus = 'CANCELLED'
          LEFT JOIN offers d ON d.offerid = o.offerid AND d.offerstatus = 'DECLINED'
          LEFT JOIN offers r ON r.offerid = o.offerid AND r.offerstatus = 'REVISED'
          LEFT JOIN offers ar ON ar.offerid = o.offerid AND ar.offerstatus = 'ARCHIVED' 
          LEFT JOIN offers t ON t.offerid = o.offerid 
          JOIN users u ON u.userid = o.offerto OR u.userid = o.offerfrom
         GROUP BY u.username
    ";

    $result = $page->db->sql_query_params($sql);

    return $result;
}

?>