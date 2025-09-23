<?php
require_once('template.class.php');

$page = new template(LOGIN, SHOWMSG);

echo $page->header('Dealer active buys');
echo mainContent();
echo $page->footer(true);

function mainContent() {

    $data = getBuys();
    $dealerCnt = getDealerCnt();
    $sellCnt = number_format(getListingCnt());
    $header = "<h3>Dealer Active Buys - ".$dealerCnt." Dealers with ".$sellCnt." active buy listings</h3>\n";
    $i = 0;
    echo $header;
    echo "<article>\n";
    echo "  <div>\n";
    foreach ($data as $d) {
        if (($i % 50 == 0) && ($i % 100 != 0)) {
            echo "    <div style='float: left; margin-bottom: 14px; padding: 10px; text-align: left;'>\n";
        }
        if ($i % 100 == 0) {
            echo "    <div style='float: left; margin-bottom: 14px; padding: 10px; text-align: left; background-color: #ddd;'>\n";
        }
        $url  = "marketsnapshot.php?dealer=".$d['username']."&type=W&sortby=cat&hourssince=0";
        $eliteUser = ($d['iselite']) ? " <span title='Elite Dealer'><i class='fas fa-star'></span>" : "";
        echo "      <a href='".$url."'>".$d['username']."</a> (".$d['buys'].")\n";
        if ($i % 50 != 49) {
            echo "      <br />\n";
        }
        if ($i % 50 == 49) {
            echo "    </div>\n";
        }
        $i++;
    }
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</article>\n";

}

function getBuys() {
    global $page;

    $sql = "
        SELECT x.username, x.buys,
               CASE WHEN ar.userid IS NOT NULL THEN 1
                    ELSE 0 END as iselite
          FROM (
            SELECT u.userid, u.username, COUNT(l.listingid) AS buys
              FROM listings             l
              JOIN users                u   ON  u.userid            = l.userid
              JOIN userinfo             ui  ON  ui.userid           = u.userid
                                            AND ui.userclassid      = 3 -- vendors
              JOIN categories           c   ON  c.categoryid        = l.categoryid
                                            AND c.active            = 1
              JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                            AND sc.active           = 1
              JOIN boxtypes             b   ON  b.boxtypeid         = l.boxtypeid
                                            AND b.active            = 1
              LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                            AND stl.userrightid     = ".USERRIGHT_STALE."
             WHERE l.type   = 'Wanted'
               AND l.status = 'OPEN'
               AND stl.userid IS NULL
             GROUP BY u.userid, u.username
                )                   x
          LEFT JOIN assignedrights  ar  ON  ar.userid           = x.userid
                                        AND ar.userrightid      = 15 -- Elite
         ORDER BY x.username
    ";

    $result = $page->db->sql_query_params($sql);

    return $result;
}

function getDealerCnt() {
    global $page;

    $sql = "
        SELECT count(1) as activedealers
          FROM (
            SELECT u.username
              FROM listings             l
              JOIN users                u   ON  u.userid            = l.userid
              JOIN userinfo             ui  ON  ui.userid           = u.userid
                                            AND ui.userclassid      = 3 -- vendors
              JOIN categories           c   ON  c.categoryid        = l.categoryid
                                            AND c.active            = 1
              JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                            AND sc.active           = 1
              JOIN boxtypes             b   ON  b.boxtypeid         = l.boxtypeid
                                            AND b.active            = 1
              LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                            AND stl.userrightid     = ".USERRIGHT_STALE."
             WHERE l.type   = 'Wanted'
               AND l.status = 'OPEN'
               AND stl.userid IS NULL
             GROUP BY u.username
                ) x
    ";

    $activedealers = $page->db->get_field_query($sql);

    return $activedealers;
}

function getListingCnt() {
    global $page;

    $sql = "
        SELECT COUNT(1) AS listingcount
          FROM listings             l
          JOIN users                u   ON  u.userid            = l.userid
          JOIN userinfo             ui  ON  ui.userid           = u.userid
                                        AND ui.userclassid      = 3 -- vendors
          JOIN categories           c   ON  c.categoryid        = l.categoryid
                                        AND c.active            = 1
          JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                        AND sc.active           = 1
          JOIN boxtypes             b   ON  b.boxtypeid         = l.boxtypeid
                                        AND b.active            = 1
          LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                        AND stl.userrightid     = ".USERRIGHT_STALE."

         WHERE l.type   = 'Wanted'
           AND l.status = 'OPEN'
           AND stl.userid IS NULL
    ";

    $listingcount = $page->db->get_field_query($sql);

    return $listingcount;
}
?>