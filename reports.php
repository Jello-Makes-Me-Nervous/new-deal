<?php

//TODO
// all these need work just basic right now
//average sale price
$sql = "
    SELECT
    ROUND(AVG(lstprice::NUMERIC), 2) as average
    FROM offeritems
    WHERE createdate > 1609477200
";
//last 5 sales
$sql = "
    SELECT inttommddyyyy_slash(createdate) AS date, lstqty, lstprice
      FROM offeritems
     WHERE accepteddate < nowtoint()
     LIMIT 5
";
/*
 * SELECT
--MAX(oi.lstprice)  AS maxi
ROUND(AVG(oi.lstprice::NUMERIC), 2) as average
FROM offers o
JOIN offeritems oi ON oi.offerid = o.offerid
WHERE oi.createdate > 1609477200
GROUP BY oi.lstqty
 *
 */


?>