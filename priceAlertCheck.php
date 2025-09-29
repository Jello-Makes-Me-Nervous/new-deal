<?php
require_once('setup.php');

var_dump(getPriceAlerts());
// foreach userid send IM and EM
function getPriceAlerts() {
    global $DB;

    $sql = "
        SELECT pa.userid, pa.alertid, pa.status, pa.type, pa.categoryid, pa.subcategoryid, pa.year, pa.boxtypeid, pa.dprice
          FROM pricealerts pa
          JOIN listings li
            ON CASE
                WHEN li.type = 'Wanted'
                    THEN li.dprice >= pa.dprice
                WHEN li.type = 'For Sale'
                    THEN li.dprice <= pa.dprice
                END
           AND li.status         = pa.status
           AND li.type           = pa.type
           AND li.categoryid     = pa.categoryid
           AND li.subcategoryid  = pa.subcategoryid
           AND li.subcategoryid  = pa.subcategoryid
           AND li.year           = pa.year
           AND li.boxtypeid      = pa.boxtypeid
           AND li.type           = pa.type

    ";
    $data = $DB->sql_query_params($sql);

    return $data;

}

?>