<?php

define("METRIC_SORT_TOTAL_OFFERS", "oc.totaloffers");
define("METRIC_SORT_DEALER_NAME", "oc.username");
define("METRIC_SORT_DEALER_ID", "oc.userid");
define("METRIC_SORT_ACCEPTED", "oc.acceptedoffers");
define("METRIC_SORT_REVISED", "oc.revisedbyme");
define("METRIC_SORT_DECLINED", "oc.declinedbyme");
define("METRIC_SORT_EXPIRED", "oc.expiredbyme");
define("METRIC_SORT_CANCELLED", "oc.cancelledbyme");
define("METRIC_SORT_VOIDED", "oc.voidedoffers");
define("METRIC_SORT_PENDING", "oc.pendingtome");
define("METRIC_SORT_ACCEPT_PCT", "oc.acceptedrate");
define("METRIC_SORT_DECLINE_PCT", "oc.declinedrate");
define("METRIC_SORT_EXPIRE_PCT", "oc.expiredrate");
define("METRIC_SORT_CANCEL_PCT", "oc.cancelledrate");
define("METRIC_SORT_TRACK_PCT", "oc.trackrate");
define("METRIC_SORT_DEALER_RATING", "oc.ratingavg");
define("METRIC_SORT_DEALER_RATE5_PCT", "oc.rating5rate");
define("METRIC_SORT_AVG_RESPONSE", "oc.avgresponse");
define("METRIC_SORT_ASSISTANCE", "oc.complaintcount");

define("METRIC_INTERVAL_ALL", 1);
define("METRIC_INTERVAL_1YEAR", 2);
define("METRIC_INTERVAL_6MONTH", 3);
define("METRIC_INTERVAL_90DAY", 4);
define("METRIC_INTERVAL_60DAY", 5);
define("METRIC_INTERVAL_30DAY", 6);
define("METRIC_INTERVAL_BLUESTAR", 99);
define("METRIC_INTERVAL_LIFETIME", 9999);

define("METRIC_FORMAT_NONE", 0);
define("METRIC_FORMAT_DECIMAL", 1);
define("METRIC_FORMAT_DECIMAL_PCT", 2);
define("METRIC_FORMAT_INTERVAL", 3);
define("METRIC_FORMAT_HIDDEN", 99);

define ("METRIC_DEALER_LEVEL_ALL", 0);
define ("METRIC_DEALER_LEVEL_ELITE", 1);
define ("METRIC_DEALER_LEVEL_BLUESTAR", 2);
define ("METRIC_DEALER_LEVEL_NEITHER", -1);

define("BLUESTAR_MEMBER_MONTHS", "minmembermonths");
define("BLUESTAR_LIFETIME_ACCEPTED", "minlifeacceptednum");
define("BLUESTAR_ACCEPTED_NUM", "minacceptednum");
define("BLUESTAR_ACCEPTED_RATE", "minacceptedrate");
define("BLUESTAR_EXPIRED_RATE", "minexpiredrate");
define("BLUESTAR_RESPONSE", "maxresponsehours");
define("BLUESTAR_RATING", "mindealerrating");
define("BLUESTAR_TRACKING_RATE", "mintrackingrate");
define("BLUESTAR_CANCELLED_RATE", "mincancelledrate");

class DealerMetrics {
    public $totalRows=0;
    public $intervals;
    public $profileColumns;
    
    public $lifetimeAccepted=0;

    public $bsGoalsActive;
    public $bsMinMemberMonths;
    public $bsMinLifeAcceptedNum;
    public $bsMinAcceptedNum;
    public $bsMinAcceptedRate;
    public $bsMaxExpiredRate;
    public $bsMaxCancelledRate;
    public $bsMinTrackingRate;
    public $bsMaxResponseHours;
    public $bsMinDealerRating;

    public function __construct() {
        $this->intervals = array(
            METRIC_INTERVAL_30DAY => array('name' => '30 Days', 'sql' => "floor(extract(epoch FROM (current_timestamp - interval '30 days')))")
            ,METRIC_INTERVAL_60DAY => array('name' => '60 Days', 'sql' => "floor(extract(epoch FROM (current_timestamp - interval '60 days')))")
            ,METRIC_INTERVAL_90DAY => array('name' => '90 Days', 'sql' => "floor(extract(epoch FROM (current_timestamp - interval '90 days')))")
            ,METRIC_INTERVAL_6MONTH => array('name' => '6 Months', 'sql' => "floor(extract(epoch FROM (current_timestamp - interval '6 months')))")
            ,METRIC_INTERVAL_1YEAR => array('name' => '1 Year', 'sql' => "floor(extract(epoch FROM (current_timestamp - interval '1 year')))")
            ,METRIC_INTERVAL_ALL => array('name' => 'All', 'sql' => "")
            ,METRIC_INTERVAL_BLUESTAR => array('name' => 'BlueStar', 'sql' => "floor(extract(epoch FROM (current_timestamp - interval '6 months')))")
        );

        $this->profileColumns = array(
            'acceptedoffers' => array('title' => 'Accepted Offers', 'format' => METRIC_FORMAT_DECIMAL) 
            ,'acceptedrate' => array('title' => 'Offer Acceptance Rate', 'format' => METRIC_FORMAT_DECIMAL_PCT) 
            //,'declinedrate' => array('title' => 'Offer Decline Rate', 'format' => METRIC_FORMAT_DECIMAL_PCT)
            ,'expiredrate' => array('title' => 'Offer Expiration Rate', 'format' => METRIC_FORMAT_DECIMAL_PCT)
            //,'cancelledrate' => array('title' => 'Offer Cancellation Rate', 'format' => METRIC_FORMAT_DECIMAL_PCT) 
            ,'avgresponse' => array('title' => 'Average Response', 'format' => METRIC_FORMAT_INTERVAL)
            ,'rating5rate' => array('title' => 'Dealer Rating', 'format' => METRIC_FORMAT_DECIMAL_PCT)
            ,'trackrate' => array('title' => 'Tracking Rate', 'format' => METRIC_FORMAT_DECIMAL_PCT)
        );

        $this->loadBlueStarGoals();
    }
    
    public function loadBlueStarGoals() {
        global $page;

        $this->bsGoalsActive = false;
        
        $sql = "select * from bluestar where active=1 limit 1";
        if ($result = $page->db->sql_query($sql)) {
            if (is_array($result) && (count($result) > 0)) {
                $goals = reset($result);
                $this->bsMinMemberMonths = $goals['minmembermonths'];
                $this->bsMinLifeAcceptedNum = $goals['minlifeacceptednum'];
                $this->bsMinAcceptedNum = $goals['minacceptednum'];
                $this->bsMinAcceptedRate = $goals['minacceptedrate'];
                $this->bsMaxExpiredRate = $goals['maxexpiredrate'];
                $this->bsMaxCancelledRate = $goals['maxcancelledrate'];
                $this->bsMinTrackingRate = $goals['mintrackingrate'];
                $this->bsMaxResponseHours = $goals['maxresponsehours'];
                $this->bsMinDealerRating = $goals['mindealerrating'];
                $this->bsGoalsActive = true;
            } else {
                $page->messages->addErrorMsg("No Bluestar Goals Found.");
            }
        } else {
            $page->messages->addErrorMsg("Error loading Bluestar Goals.");
        }
    }
    
    public function saveBlueStarGoals() {
        global $page;

        if ($this->bsGoalsActive) {
            $page->db->sql_begin_trans();
            
            $page->db->sql_execute("UPDATE bluestar SET active=0");
            
            $goals = array();
            $goals['active'] = true;
            $goals['minmembermonths'] = $this->bsMinMemberMonths;
            $goals['minlifeacceptednum'] = $this->bsMinLifeAcceptedNum;
            $goals['minacceptednum'] = $this->bsMinAcceptedNum;
            $goals['minacceptedrate'] = $this->bsMinAcceptedRate;
            $goals['maxexpiredrate'] = $this->bsMaxExpiredRate;
            $goals['maxcancelledrate'] = $this->bsMaxCancelledRate;
            $goals['mintrackingrate'] = $this->bsMinTrackingRate;
            $goals['maxresponsehours'] = $this->bsMaxResponseHours;
            $goals['mindealerrating'] = $this->bsMinDealerRating;
            $goals['createdby'] = $page->user->username;
            $goals['modifiedby'] = $page->user->username;
            
            $sql = "INSERT INTO bluestar
                        (active, minmembermonths, minlifeacceptednum
                        ,minacceptednum, minacceptedrate, maxexpiredrate, maxcancelledrate
                        ,mintrackingrate, maxresponsehours, mindealerrating
                        ,createdby, modifiedby)
                    VALUES
                        (:active, :minmembermonths, :minlifeacceptednum
                        ,:minacceptednum, :minacceptedrate, :maxexpiredrate, :maxcancelledrate
                        ,:mintrackingrate, :maxresponsehours, :mindealerrating
                        ,:createdby, :modifiedby)";
            
            if ($page->db->sql_execute_params($sql, $goals)) {
                $page->db->sql_commit_trans();
                $page->messages->addSuccessMsg("Blue Star configuration updated.");
            } else {
                $page->db->sql_rollback_trans();
                $page->messages->addErrorsMsg("Error updating Blue Star configuration.");
            }
        } else {
            $page->messages->addErrorsMsg("Error updating Blue Star configuration, not enabled");
        }
    }
    
    public function getIntervalNames() {
        $result = array();
        
        foreach($this->intervals as $intervalId => $intervalInfo) {
            $result[] = array('intervalid' => $intervalId, 'intervalname' => $intervalInfo['name']);
        }
        return $result;
    }
    
    public function getProfileColumnTitle($columnName) {
        return $this->profileColumns[$columnName]['title'];
    }
    
    public function formatProfileColumnData($value, $columnName) {
        if ($value) {
            $newValue = $value;
        } else {
            if ($this->profileColumns[$columnName]['format'] == METRIC_FORMAT_INTERVAL) {
                $newValue = "NA";
            } else {
                $newValue = 0;
            }
        }

        if ($this->profileColumns[$columnName]['format'] == METRIC_FORMAT_DECIMAL_PCT) {
            $newValue .= "%";
        }

        return $newValue;
    }
    
    public function styleProfileColumnData($columnName) {
        $newClass = "";
        switch ($this->profileColumns[$columnName]['format']) {
            CASE METRIC_FORMAT_DECIMAL:
            CASE METRIC_FORMAT_DECIMAL_PCT:
            CASE METRIC_FORMAT_INTERVAL:
                $newClass .= "number";
                break;
        }
        return $newClass;
    }
    
    public function getDealerMetricsAsOf($dealerId) {
        global $page;
        
        return $page->db->get_field_query("SELECT min(modifydate) FROM offercounts WHERE userid=".$dealerId);
    }
    
    public function getDealerMetricsMatrix($dealerId) {
        global $page;

        $matrix = null;

        $sql = "SELECT oc.acceptedoffers + isnull(uc.accepted, 0) AS lifetimeaccepted
            FROM offercounts oc
            LEFT JOIN usercounts uc ON uc.userid=oc.userid
            WHERE oc.userid=".$dealerId."
              AND oc.intervalid=".METRIC_INTERVAL_ALL;
        $this->lifetimeAccepted = $page->db->get_field_query($sql);
        
        $sql = "SELECT oc.intervalid, oc.userid, ".implode(', ',array_keys($this->profileColumns))."
            FROM offercounts oc
            WHERE oc.userid=".$dealerId."
            ORDER BY intervalid DESC";
        
        if ($metrics = $page->db->sql_query($sql)) {

            foreach ($this->profileColumns as $columnName => $column) {
                $matrix[$columnName] = array();
                foreach ($this->intervals as $intervalId => $intervalInfo) {
                    $matrix[$columnName][$intervalId] = null;
                }
            }

            $matrix = array();
            foreach ($metrics as $metric) {
                foreach ($this->profileColumns as $columnName => $column) {
                    $matrix[$columnName][$metric['intervalid']] = $metric[$columnName];
                }
            }
        }
        
        return $matrix;
    }
        
    public function getMetrics($intervalId, $dealerName=NULL, $dealerId=NULL, $dealerLevelId=NULL, $sortby=NULL, $pagenum = 1, $perpage = NULL) {
        global $page;
        
        $sqlWhere = " WHERE oc.intervalid=".$intervalId;
        if ($dealerId) {
            $sqlWhere .= " AND oc.userid=".$dealerId." ";
        }
        if ($dealerName) {
            $sqlWhere .= " AND oc.username ilike '%".$dealerName."%' ";
        }
        if ($dealerLevelId) {
            switch ($dealerLevelId) {
                CASE METRIC_DEALER_LEVEL_ELITE:
                    $sqlWhere .= " AND eu.userid IS NOT NULL ";
                    break;
                CASE METRIC_DEALER_LEVEL_BLUESTAR:
                    $sqlWhere .= " AND bu.userid IS NOT NULL ";
                    break;
                CASE METRIC_DEALER_LEVEL_NEITHER:
                    $sqlWhere .= " AND (eu.userid IS NULL AND bu.userid IS NULL) ";
                    break;
            }
        }

        $sqlSelect ="SELECT oc.*
            ,CASE WHEN eu.userid IS NOT NULL THEN 'Y' ELSE 'N' END AS eliteuser
            ,CASE WHEN bu.userid IS NOT NULL THEN 'Y' ELSE 'N' END AS bluestaruser
            ,oca.acceptedoffers + isnull(uc.accepted,0) AS acceptedall
            ";
        if ($this->bsGoalsActive) {
            $sqlSelect .="
                ,inttodatetime(ui.accountcreated) as accountcreated
                ,CASE WHEN ui.accountcreated < floor(extract(epoch FROM (current_timestamp - interval '".$this->bsMinMemberMonths." months'))) THEN 'Y' ELSE 'N' END AS bsmembership
                ,CASE WHEN (oca.acceptedoffers + isnull(uc.accepted, 0)) >= ".$this->bsMinLifeAcceptedNum." THEN 'Y' ELSE 'N' END AS bsacceptedall
                ,CASE WHEN oc.acceptedoffers >= ".$this->bsMinAcceptedNum." THEN 'Y' ELSE 'N' END AS bsacceptednum
                ,CASE WHEN oc.acceptedrate >= ".$this->bsMinAcceptedNum." THEN 'Y' ELSE 'N' END AS bsacceptedrate
                ,CASE WHEN (oc.expiredrate IS NULL OR (oc.expiredrate <= ".$this->bsMaxExpiredRate.")) THEN 'Y' ELSE 'N' END AS bsexpiredrate
                ,CASE WHEN (oc.cancelledrate IS NULL OR (oc.cancelledrate <= ".$this->bsMaxCancelledRate.")) THEN 'Y' ELSE 'N' END AS bscancelledrate
                ,CASE WHEN ((oc.trackablecount = 0) OR (oc.trackrate >= ".$this->bsMinTrackingRate.")) THEN 'Y' ELSE 'N' END AS bstrackrate
                ,CASE WHEN oc.rating5rate >= ".$this->bsMinDealerRating." THEN 'Y' ELSE 'N' END AS bsrating
                ,CASE WHEN oc.avgresponse <= interval '".$this->bsMaxResponseHours." hours' THEN 'Y' ELSE 'N' END AS bsresponse
                ";
        }
        $sqlFrom = "FROM offercounts oc
            JOIN userinfo ui ON ui.userid=oc.userid
            LEFT JOIN usercounts uc ON uc.userid=ui.userid
            LEFT JOIN offercounts oca ON oca.userid=oc.userid AND oca.intervalid=".METRIC_INTERVAL_ALL."
            LEFT JOIN assignedrights eu ON eu.userid=oc.userid AND eu.userrightid=".USERRIGHT_ELITE."
            LEFT JOIN assignedrights bu ON bu.userid=oc.userid AND bu.userrightid=".USERRIGHT_BLUESTAR;
        $sqlSortBy = empty($sortby) ? "oc.totaloffers, oc.username" : $sortby.",oc.username";
        $sqlPage = empty($perpage) ? "" : "
            ORDER BY ".$sqlSortBy."
            OFFSET ".($pagenum-1)*$perpage."
             LIMIT ".$perpage;
        $metrics = $page->db->sql_query($sqlSelect.$sqlFrom.$sqlWhere.$sqlPage);
        if ($metrics) {
            $this->totalRows = $page->db->get_field_query("SELECT count(*) AS cnt ".$sqlFrom.$sqlWhere);
        } else {
            $this->totalRows = 0;
            $page->messages->addErrorMsg("Error getting offer counts.");
        }
        
        return $metrics;
    }

    public function reloadMetrics($dealerId=NULL) {
        global $page;
        
        if ($page->user->isAdmin() || ($page->user->userId == $dealerId)) {
            //$rowCount = $page->db->get_field_query("SELECT count(*) as totalrows FROM offercounts");
            //echo "Before truncate:".$rowCount."<br />\n";
            $whereDealer = ($dealerId) ? "userid=".$dealerId : "1=1";
            $page->db->sql_execute("DELETE FROM offercounts WHERE ".$whereDealer);
            //$rowCount = $page->db->get_field_query("SELECT count(*) as totalrows FROM offercounts");
            //echo "After truncate:".$rowCount."<br />\n";
            
            foreach ($this->intervals as $intervalId => $intervalInfo) {
                $this->reloadInterval($intervalId, $dealerId);
                //echo "Reloaded Interval ".$intervalInfo['name']."<br />\n";
            }
        } else {
            $page->messages->addErrorMsg("Admin access required. No reload performed.");
        }
    }

    public function reloadInterval($intervalId, $dealerId=NULL) {
        global $page;

        if (! ($page->user->isAdmin() || ($page->user->userId == $dealerId))) {
            $page->messages->addErrorMsg("Admin access required. No reload performed.");
            return;
        }
        
        $intervalSql = (empty($this->intervals[$intervalId]['sql'])) ? "" : " AND (o.createdate > (".$this->intervals[$intervalId]['sql'].")) ";

        $andUser = ($dealerId) ? " AND u.userid=".$dealerId." " : "";
        
        $sqlSelect = "
            INSERT INTO offercounts
            SELECT ".$intervalId." AS intervalid
                ,uci.*
                ,'".$page->user->username."' as createdby, nowtoint() as createdate
                ,'".$page->user->username."' as modifiedby, nowtoint() as modifydate
            FROM (
                SELECT aot.userid, aot.username
                    ,aot.acceptedoffers
                    ,aot.revisedbyme
                    ,aot.declinedbyme
                    ,aot.expiredbyme
                    ,aot.cancelledbyme
                    ,aot.voidedoffers
                    ,aot.pendingtome
                    ,aot.totaloffers
                    ,aot.adedenominator
                    ,aot.cancelleddenominator
                    ,CASE WHEN aot.declinedbyme > 0 THEN ROUND((aot.declinedbyme::decimal/aot.adedenominator::decimal)*100,2) ELSE NULL END AS declinedrate
                    ,CASE WHEN aot.expiredbyme > 0 THEN ROUND((aot.expiredbyme::decimal/aot.adedenominator::decimal)*100,2) ELSE NULL END AS expiredrate
                    ,CASE WHEN aot.acceptedoffers > 0 THEN ROUND((aot.acceptedoffers::decimal/aot.adedenominator::decimal)*100,2) ELSE NULL END AS acceptedrate
                    ,CASE WHEN aot.cancelledbyme > 0 THEN ROUND((aot.cancelledbyme::decimal/aot.cancelleddenominator::decimal)*100,2) ELSE NULL END AS cancelledrate
                    ,aot.trackablecount
                    ,aot.trackedcount
                    ,CASE WHEN aot.trackablecount > 0 AND aot.trackedcount > 0 THEN ROUND((aot.trackedcount::decimal/aot.trackablecount::decimal)*100, 2) ELSE NULL END AS trackrate
                    ,aot.ratingtotal
                    ,aot.ratingcounts
                    ,aot.rating5counts
                    ,CASE WHEN aot.ratingcounts > 0 THEN ROUND(aot.ratingtotal::decimal/aot.ratingcounts::decimal, 2) ELSE NULL END AS ratingavg
                    ,CASE WHEN aot.rating5counts > 0 THEN ROUND((aot.rating5counts::decimal/aot.ratingcounts::decimal)*100, 2) ELSE NULL END AS rating5rate
                    ,aot.avgresponse
                    ,aot.complaintcount
                FROM (
                    SELECT ao.userid, ao.username
                        ,sum(ao.acceptedoffer) AS acceptedoffers
                        ,sum(ao.revisedbyme) AS revisedbyme
                        ,sum(ao.notbyme*ao.declinedoffer) AS declinedbyme
                        ,sum(ao.notbyme*ao.expiredoffer) AS expiredbyme
                        ,sum(ao.byme*ao.cancelledoffer) AS cancelledbyme
                        ,sum(ao.voidoffer) AS voidedoffers
                        ,sum(ao.notbyme*ao.pendingoffer) AS pendingtome
                        ,sum(ao.totaloffers) AS totaloffers
                        ,sum(ao.acceptedoffer)+sum(ao.notbyme*ao.declinedoffer)+sum(ao.notbyme*ao.expiredoffer) AS adedenominator
                        ,sum(ao.acceptedoffer)+sum(ao.notbyme*ao.declinedoffer)+sum(ao.notbyme*ao.expiredoffer)+sum(ao.byme*ao.cancelledoffer) AS cancelleddenominator
                        ,sum(ao.trackable) AS trackablecount
                        ,sum(ao.tracked) AS trackedcount
                        ,sum(ao.rating) as ratingtotal
                        ,sum(ao.ratingcount) as ratingcounts
                        ,sum(ao.rating5count) as rating5counts
                        ,date_trunc('seconds', (avg(ao.responsetime) || ' second')::interval) AS avgresponse
                        ,count(ao.complainedabout) AS complaintcount
                    FROM (
                        SELECT u.userid, u.username
                            ,1 AS totaloffers
                            ,CASE WHEN o.offerto=u.userid THEN 1 ELSE 0 END AS tome
                            ,CASE WHEN o.offerfrom=u.userid THEN 1 ELSE 0 END AS fromme
                            ,CASE WHEN o.offeredby=u.userid THEN 1 ELSE 0 END AS byme
                            ,CASE WHEN o.offeredby=u.userid THEN 0 ELSE 1 END AS notbyme
                            ,CASE WHEN o.offerstatus='PENDING' THEN 1 ELSE 0 END AS pendingoffer
                            ,CASE WHEN o.offerstatus='ACCEPTED' OR o.offerstatus='ARCHIVED' THEN 1 ELSE 0 END AS acceptedoffer
                            ,CASE WHEN o.offerstatus='DECLINED' AND o.countered=0 THEN 1 ELSE 0 END AS declinedoffer
                            ,CASE WHEN o.offerstatus='EXPIRED' AND o.countered=0 THEN 1 ELSE 0 END AS expiredoffer
                            ,CASE WHEN o.offerstatus='CANCELLED' THEN 1 ELSE 0 END AS cancelledoffer
                            ,CASE WHEN o.offerstatus='VOID' THEN 1 ELSE 0 END AS voidoffer
                            ,CASE WHEN rev.threadid IS NOT NULL THEN 1 ELSE 0 END AS revisedoffer
                            ,CASE WHEN rev.threadid IS NOT NULL AND o.offeredby=u.userid THEN 1 ELSE 0 END AS revisedbyme
                            ,CASE WHEN o.offerfrom=u.userid THEN
                                CASE WHEN o.transactiontype = 'Wanted' THEN
                                    CASE WHEN o.satisfiedbuy > 0 THEN o.satisfiedbuy ELSE 0 END
                                ELSE
                                    CASE WHEN o.satisfiedsell > 0 THEN o.satisfiedsell ELSE 0 END
                                END
                             ELSE
                                CASE WHEN o.transactiontype = 'Wanted' THEN
                                    CASE WHEN o.satisfiedsell > 0 THEN o.satisfiedsell ELSE 0 END
                                ELSE
                                    CASE WHEN o.satisfiedbuy > 0 THEN o.satisfiedbuy ELSE 0 END
                                END
                             END AS rating
                            ,CASE WHEN o.offerfrom=u.userid THEN
                                CASE WHEN o.transactiontype = 'Wanted' THEN
                                    CASE WHEN o.satisfiedbuy > 0 THEN 1 ELSE 0 END
                                ELSE
                                    CASE WHEN o.satisfiedsell > 0 THEN 1 ELSE 0 END
                                END
                             ELSE
                                CASE WHEN o.transactiontype = 'Wanted' THEN
                                    CASE WHEN o.satisfiedsell > 0 THEN 1 ELSE 0 END
                                ELSE
                                    CASE WHEN o.satisfiedbuy > 0 THEN 1 ELSE 0 END
                                END
                             END AS ratingcount
                            ,CASE WHEN o.offerfrom=u.userid THEN
                                CASE WHEN o.transactiontype = 'Wanted' THEN
                                    CASE WHEN o.satisfiedbuy = 5 THEN 1 ELSE 0 END
                                ELSE
                                    CASE WHEN o.satisfiedsell = 5 THEN 1 ELSE 0 END
                                END
                             ELSE
                                CASE WHEN o.transactiontype = 'Wanted' THEN
                                    CASE WHEN o.satisfiedsell = 5 THEN 1 ELSE 0 END
                                ELSE
                                    CASE WHEN o.satisfiedbuy = 5 THEN 1 ELSE 0 END
                                END
                             END AS rating5count
                            ,CASE
                                WHEN o.offeredby<>u.userid THEN
                                    CASE WHEN (o.offerstatus='ACCEPTED' OR o.offerstatus='ARCHIVED')
                                        THEN o.acceptedon - o.createdate
                                    WHEN o.offerstatus='REVISED'
                                        THEN o.modifydate-o.createdate
                                    WHEN o.offerstatus='EXPIRED'
                                        THEN o.modifydate-o.createdate
                                    WHEN o.offerstatus='DECLINED'
                                        THEN o.modifydate-o.createdate
                                    ELSE NULL END
                                ELSE
                                    NULL
                                END AS responsetime
                            ,CASE
                                WHEN (o.offerstatus='ACCEPTED' OR o.offerstatus='ARCHIVED')
                                 AND ((o.offerto=u.userid AND o.transactiontype='For Sale')
                                     OR (o.offerfrom=u.userid AND o.transactiontype='Wanted'))
                                 AND o.acceptedon < floor(extract(epoch FROM (current_timestamp - interval '1 days')))
                                THEN 1 ELSE 0 END
                                AS trackable
                            ,CASE
                                WHEN (o.offerstatus='ACCEPTED' OR o.offerstatus='ARCHIVED')
                                 AND ((o.offerto=u.userid AND o.transactiontype='For Sale')
                                     OR (o.offerfrom=u.userid AND o.transactiontype='Wanted'))
                                 AND o.acceptedon < floor(extract(epoch FROM (current_timestamp - interval '1 days')))
                                 AND length(o.tracking) > 0
                                THEN 1 ELSE 0 END
                                AS tracked
                            ,cmp.complainedabout
                        FROM users u
                        JOIN userinfo ui
                            ON ui.userid=u.userid
                            AND ui.userclassid IN (2,3,5)
                        JOIN assignedrights ear ON ear.userid=u.userid AND ear.userrightid=".USERRIGHT_ENABLED."
                        JOIN offers o ON (o.offerfrom=u.userid OR o.offerto=u.userid) ".$intervalSql."
                        LEFT JOIN (
                            SELECT threadid FROM offers WHERE offerstatus='REVISED' GROUP BY threadid
                        ) rev ON rev.threadid=o.threadid
                        LEFT JOIN (
                            SELECT o.offerid
                                ,CASE WHEN (m.toid=o.offerfrom OR m.fromid=o.offerfrom) THEN 
                                    o.offerto 
                                 ELSE o.offerfrom 
                                 END AS complainedabout
                            FROM offers o
                            JOIN messaging m ON m.offerid = o.offerid
                            WHERE m.messagetype = 'COMPLAINT'
                              AND m.parentid=0
                            GROUP BY 1,2
                        ) cmp ON cmp.offerid=o.offerid AND cmp.complainedabout = u.userid
                        WHERE o.offerstatus IN ('ACCEPTED','ARCHIVED','DECLINED','EXPIRED','CANCELLED','VOID')
                          ".$andUser."
                    ) ao
                    GROUP BY ao.userid, ao.username
                ) aot
            ) uci
        ";

//echo "ReloadMetrics:<pre>\n".$sqlSelect."</pre><br />\n";
//exit;
        $page->db->sql_query($sqlSelect);
        //$this->totalRows = $page->db->get_field_query("SELECT count(*) AS cnt FROM offercounts");
        //echo "Reloaded totalRows:".$this->totalRows."<br />\n";
    }
    
    public function promoteBlueStar() {
        global $page;
        
        if ($this->bsGoalsActive) {
            /*
               User is Vendor
               User is Enabled
               User is NOT Elite
               User is NOT already BlueStar
               User is NOT Stale
               Meets BlueStar Criteria
            */
            $sqlFrom = "
                FROM users u
                JOIN userinfo ui ON ui.userid=u.userid
                JOIN offercounts oc ON oc.userid=u.userid AND oc.intervalid=".METRIC_INTERVAL_6MONTH."
                JOIN offercounts oca ON oca.userid=oc.userid AND oca.intervalid=".METRIC_INTERVAL_ALL."
                LEFT JOIN assignedrights au ON au.userid=oc.userid AND au.userrightid=".USERRIGHT_ENABLED."
                LEFT JOIN assignedrights eu ON eu.userid=oc.userid AND eu.userrightid=".USERRIGHT_ELITE."
                LEFT JOIN assignedrights bu ON bu.userid=oc.userid AND bu.userrightid=".USERRIGHT_BLUESTAR."
                LEFT JOIN assignedrights su ON su.userid=oc.userid AND su.userrightid=".USERRIGHT_STALE."
                LEFT JOIN usercounts uc ON uc.userid=u.userid
                WHERE ui.userclassid = 3
                  AND ui.bluestarmodeid = -1
                  AND au.userid IS NOT NULL
                  AND eu.userid IS NULL
                  AND bu.userid IS NULL
                  AND su.userid IS NULL
                  AND ui.accountcreated <= floor(extract(epoch FROM (current_timestamp - interval '".$this->bsMinMemberMonths." months')))
                  AND (oca.acceptedoffers + isnull(uc.accepted,0)) >= ".$this->bsMinLifeAcceptedNum."
                  AND oc.acceptedoffers >= ".$this->bsMinAcceptedNum."
                  AND oc.acceptedrate >= ".$this->bsMinAcceptedRate."
                  AND (oc.expiredrate IS NULL OR (oc.expiredrate <= ".$this->bsMaxExpiredRate."))
                  AND (oc.cancelledrate IS NULL OR (oc.cancelledrate <= ".$this->bsMaxCancelledRate."))
                  AND ((oc.trackablecount = 0) OR (oc.trackrate >= ".$this->bsMinTrackingRate."))
                  AND oc.rating5rate >= ".$this->bsMinDealerRating."
                  AND oc.avgresponse <= interval '".$this->bsMaxResponseHours." hours'
                  ";
            $sql = "INSERT INTO bluestaraudit (
                      action,userid,userclassid
                      ,iselite,isbluestar,isenabled,isstale
                      ,membercreated,lifeacceptednum
                      ,acceptednum,acceptedrate,expiredrate,cancelledrate,trackingrate
                      ,dealerrating,responsehours,createdby,modifiedby)
                SELECT 1 AS action, u.userid, ui.userclassid
                    ,CASE WHEN eu.userid IS NULL THEN 0 ELSE 1 END AS iselite
                    ,CASE WHEN bu.userid IS NULL THEN 0 ELSE 1 END AS isbluestar
                    ,CASE WHEN au.userid IS NULL THEN 0 ELSE 1 END AS isenabled
                    ,CASE WHEN su.userid IS NULL THEN 0 ELSE 1 END AS isstale
                    ,ui.accountcreated AS membercreated
                    ,(oca.acceptedoffers + isnull(uc.accepted,0)) AS lifeacceptednum
                    ,oc.acceptedoffers AS acceptednum
                    ,oc.acceptedrate AS acceptedrate
                    ,oc.expiredrate AS expiredrate
                    ,oc.cancelledrate AS cancelledrate
                    ,oc.trackrate AS trackingrate
                    ,oc.rating5rate AS dealerrating
                    ,oc.avgresponse AS responsehours
                    ,'".$page->user->username."' AS createdby, '".$page->user->username."' AS modifiedby
                ".$sqlFrom;
            //echo "Audit PromoteSQL:<br />\n<pre>".$sql."</pre><br />\n";
            $results = $page->db->sql_execute($sql);
            $sql = "INSERT INTO assignedrights (userid, userrightid, createdby, modifiedby)
                SELECT u.userid, ".USERRIGHT_BLUESTAR." AS userrightid, '".$page->user->username."' AS createdby, '".$page->user->username."' AS modifiedby
                ".$sqlFrom;
            //echo "PromoteSQL:<br />\n<pre>".$sql."</pre><br />\n";
            $results = $page->db->sql_execute($sql);
        } else {
            $page->messages->addErrorMsg("Unable to promote BlueStar dealers, criteria not found.");
        }
    }
    
    public function demoteBlueStar() {
        global $page;
        
        if ($this->bsGoalsActive) {
            $sqlFrom = "FROM assignedrights bu
                    JOIN users u on u.userid=bu.userid
                    JOIN userinfo ui ON ui.userid=u.userid
                    JOIN offercounts oc ON oc.userid=u.userid AND oc.intervalid=".METRIC_INTERVAL_6MONTH."
                    JOIN offercounts oca ON oca.userid=oc.userid AND oca.intervalid=".METRIC_INTERVAL_ALL."
                    LEFT JOIN assignedrights au ON au.userid=oc.userid AND au.userrightid=".USERRIGHT_ENABLED."
                    LEFT JOIN assignedrights eu ON eu.userid=oc.userid AND eu.userrightid=".USERRIGHT_ELITE."
                    LEFT JOIN assignedrights su ON su.userid=oc.userid AND su.userrightid=".USERRIGHT_STALE."
                    LEFT JOIN usercounts uc ON uc.userid=u.userid
                    WHERE bu.userid=oc.userid AND bu.userrightid=".USERRIGHT_BLUESTAR."
                      AND ui.bluestarmodeid = -1
                      AND (
                            ui.userclassid <> 3
                            OR au.userid IS NULL
                            OR eu.userid IS NOT NULL
                            OR su.userid IS NOT NULL
                            OR ui.accountcreated > floor(extract(epoch FROM (current_timestamp - interval '".$this->bsMinMemberMonths." months')))
                            OR (oca.acceptedoffers + isnull(uc.accepted, 0)) < ".$this->bsMinLifeAcceptedNum."
                            OR oc.acceptedoffers < ".$this->bsMinAcceptedNum."
                            OR oc.acceptedrate < ".$this->bsMinAcceptedRate."
                            OR (oc.expiredrate IS NOT NULL AND (oc.expiredrate > ".$this->bsMaxExpiredRate."))
                            OR (oc.cancelledrate IS NOT NULL AND (oc.cancelledrate > ".$this->bsMaxCancelledRate."))
                            OR oc.trackrate < ".$this->bsMinTrackingRate."
                            OR oc.rating5rate < ".$this->bsMinDealerRating."
                            OR oc.avgresponse > interval '".$this->bsMaxResponseHours." hours'
                        )";

            $sql = "INSERT INTO bluestaraudit (
                      action,userid,userclassid
                      ,iselite,isbluestar,isenabled,isstale
                      ,membercreated,lifeacceptednum
                      ,acceptednum,acceptedrate,expiredrate,cancelledrate,trackingrate
                      ,dealerrating,responsehours,createdby,modifiedby)
                SELECT -1 AS action, u.userid, ui.userclassid
                    ,CASE WHEN eu.userid IS NULL THEN 0 ELSE 1 END AS iselite
                    ,CASE WHEN bu.userid IS NULL THEN 0 ELSE 1 END AS isbluestar
                    ,CASE WHEN au.userid IS NULL THEN 0 ELSE 1 END AS isenabled
                    ,CASE WHEN su.userid IS NULL THEN 0 ELSE 1 END AS isstale
                    ,ui.accountcreated AS membercreated
                    ,(oca.acceptedoffers + isnull(uc.accepted,0)) AS lifeacceptednum
                    ,oc.acceptedoffers AS acceptednum
                    ,oc.acceptedrate AS acceptedrate
                    ,oc.expiredrate AS expiredrate
                    ,oc.cancelledrate AS cancelledrate
                    ,oc.trackrate AS trackingrate
                    ,oc.rating5rate AS dealerrating
                    ,oc.avgresponse AS responsehours
                    ,'".$page->user->username."' AS createdby, '".$page->user->username."' AS modifiedby
                ".$sqlFrom;
            //echo "Audit DemoteSQL:<br />\n<pre>".$sql."</pre><br />\n";
            $results = $page->db->sql_execute($sql);
            
            
            $sql = "WITH demotes AS (
                    SELECT u.userid, ".USERRIGHT_BLUESTAR." AS userrightid
                    ".$sqlFrom."
                )
                DELETE FROM assignedrights AS ar
                    USING demotes AS dem
                    WHERE ar.userid=dem.userid AND ar.userrightid=dem.userrightid
                ";
            //echo "DemoteSQL:<br />\n<pre>".$sql."</pre><br />\n";
            $results = $page->db->sql_execute($sql);
        } else {
            $page->messages->addErrorMsg("Unable to demote BlueStar dealers, criteria not found.");
        }
    }
}
?>