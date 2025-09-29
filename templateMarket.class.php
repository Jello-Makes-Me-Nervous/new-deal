<?php
//TODO change price and qty to REVISED insert revised when making an offer
require_once('template.class.php');

class templateMarket extends template{

    public $addToCart;
    public $addToCartBTN;
    public $boxTypeId;
    public $categoryId;
    public $dealerId;
    public $dealerName;
    public $go;
    public $keyword;
    public $listingId;
    public $listingSince;
    public $search;
    public $sort;
    public $subCategoryId;
    public $type;
    public $year;
    public $display_RightWidget = false;
    public $blockCantOffer = true;
    public $blockBelowStandard = true;
    public $enforceSMSSetup     = true;


    public function __construct($loginRequired = true, $showmsgs = true, $bypassMustReplyCheck = false, $forcePaymentMethodVerification = true, $enforceBlockCantOffer = true, $enforceBelowStandard = true, $enforceSMSSetup = true) {
        $this->blockCantOffer = $enforceBlockCantOffer;
        if (!isset($this->user)) {
            $_SESSION["gotoOnLogin"] = $_SERVER["REQUEST_URI"];
        }
        parent::__construct($loginRequired, $showmsgs, $bypassMustReplyCheck, $forcePaymentMethodVerification);

        if ($this->user->isLoggedIn()) {
            if (! $this->user->canOffer()) {
                if ($this->blockCantOffer) {
                    $helpDeskLink = "<a href='sendmessage.php?dept=1'>Contact Help Desk</a>";
                    header('location:home.php?pgemsg='.URLEncode("Market Place access is denied. Your account is Inactive or Suspended. ".$helpDeskLink));
                    exit();
                }
            } elseif ($enforceSMSSetup && !$this->hasSMSNotification()) {
                $link = "<a href='/notificationPreferences.php?dealerId=".$this->user->userId."'>here</a>";
                $msg  = "<p>Please make sure to setup SMS notification before proceeding to the marketplace.</p>";
                $msg .= "<p><b>You can setup these notifications by going to Account > Profile > Notifications or by clicking ".$link.".</b></p>";
                $this->messages->addErrorMsg($msg);
                header('location:smsask.php');
                exit();
            } else {
                if ($this->hasPendingOffers()) {
                    if ($this->user->isBelowStandard()) {
                        if ($enforceBelowStandard) {
                            header('location:offers.php?offerfilter=PENDINGIN#results');
                            exit();
                        } else {
                            $this->messages->addErrorMsg("Please make sure to respond to all pending offers before proceeding to the marketplace.");
                        }
                    }
                }
            }
            $this->requireJS("https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js");
        } else {
            header('location:home.php?pgsmsg=Login%20required');
            exit();
        }
    }

    public function paramInit() {

        $this->addToCart      = optional_param('addToCart', NULL, PARAM_TEXT);
        $this->addToCartBTN   = optional_param('addToCartBTN', NULL, PARAM_TEXT);
        $this->boxTypeId      = optional_param('boxtypeid', NULL, PARAM_INT);
        $this->categoryId     = optional_param('categoryid', NULL, PARAM_INT);
        $this->dealerId       = optional_param('dealerId', NULL, PARAM_INT);
        $this->dealerName     = optional_param('dealerName', NULL, PARAM_TEXT);
        $this->go             = optional_param('go', NULL, PARAM_TEXT);
        $this->keyword        = optional_param('keyword', NULL, PARAM_TEXT);
        $this->listingId      = optional_param('listingId', NULL, PARAM_TEXT);
        $this->listingSince   = optional_param('listingSince', NULL, PARAM_INT);
        $this->search         = optional_param('search', NULL, PARAM_TEXT);//array
        $this->sort           = optional_param('sort', NULL, PARAM_TEXT);
        $this->subCategoryId  = optional_param('subcategoryid', NULL, PARAM_INT);
        $this->boxTypeId      = optional_param('boxtypeid', NULL, PARAM_INT);
        $this->type           = optional_param('type', "both", PARAM_TEXT);
        $this->year           = optional_param('year', NULL, PARAM_TEXT);

    }

    private function hasPendingOffers() {
        global $DB, $USER;

        $sql = "SELECT count(*) AS numoffers FROM offers WHERE (offerto=".$USER->userId." OR offerto=".$USER->userId.") AND offeredby <> ".$USER->userId." AND offerstatus='PENDING'";

        $numOffers = $DB->get_field_query($sql);

        return $numOffers;
    }

    public function addToCartBTN() {
          echo "<input class='button' type='submit' name='addToCart' value='Add To Cart'>\n";
    }

    function getBlastAdList() {
        global $page;

        $returnData = null;

        $sql = "SELECT l.userid, u.username, ui.listinglogo, max(l.createdate) as lastupdate, count(l.listingid) as blastcount
                FROM listings l
                    JOIN categories c ON  c.categoryid = l.categoryid AND c.categorytypeid=".LISTING_TYPE_BLAST."
                    JOIN users u ON u.userid=l.userid
                    JOIN userinfo ui on ui.userid=l.userid
                    JOIN assignedrights ar ON ar.userid=l.userid AND ar.userrightid in (".USERRIGHT_LIMITED_BLAST.",".USERRIGHT_UNLIMITED_BLAST.")
                    WHERE l.status = 'OPEN'
                    GROUP BY l.userid, u.username, ui.listinglogo
                    ORDER BY 4 DESC
                ";

        //echo "<pre>".$sql."</pre>";
        $returnData = $page->db->sql_query($sql);

        return $returnData;
    }

    public function displayLeftWidget() {
        global $page;

        echo "          <div id='primary-widget' class='narrow-left-sidebar shadow font-roboto-condensed'><!--PRIMARY WIDGET NARROW LEFT---->\n";

        $blastList = $this->getBlastAdList();
        if ($blastList && is_array($blastList) && (count($blastList) > 0)) {
            echo "            <aside>\n";
            echo "              <header>\n";
            echo "                <h3>Latest Blasts</h3>\n";
            echo "              </header>\n";
            echo "              <div class='side-menu'> <!-- side menu container -->\n";
            echo "                <div class='side-menu-container font-inherit'> <!-- side menu container -->\n";
            echo "                  <ul class='menu-items images'>\n";
            foreach ($blastList as $blaster) {
                $logo = ($blaster['listinglogo']) ? $page->utility->getPrefixMemberImageURL($blaster['listinglogo']) : "/images/nologo.gif";
                echo "                    <li>\n";
                echo "                      <a href='blasts.php?blasterid=".$blaster['userid']."&blastad=1' title='Click blast specials from ".$blaster['username']."'>\n";
                echo "                        <img src='".$logo."'>\n";
                echo "                      </a>\n";
                echo "                    </li>\n";
            }
            echo "                  </ul>\n";
            echo "                </div><!-- side menu container -->\n";
            echo "              </div><!-- side menu -->\n";
            echo "            </aside>\n";
        }

        echo "          </div><!-- primary-widget -->\n";
    }

    public function displayRightWidget() {

        if ($this->display_RightWidget) {
            echo "          <div id='secondary-widget' class='narrow-left-sidebar shadow font-roboto-condensed'><!--SECONDARY WIDGET right side---->\n";

            $recent = null;
            $last30 = null;
            if (!empty($this->categoryId)) {
                $info = $this->boxesTraded($this->categoryId, $this->subCategoryId, $this->boxTypeId, $this->year);
                $priceHistory = $this->historicalPrice($this->categoryId, $this->subCategoryId, $this->boxTypeId, $this->year);
                $recent = $this->recentTrades($this->categoryId, $this->subCategoryId, $this->boxTypeId, $this->year);
                $last30 = $this->last30DaysAverage($this->categoryId, $this->subCategoryId, $this->boxTypeId, $this->year);
            }
            if (is_array($last30) && (count($last30) > 0)) {
                echo "            <aside>\n";
                echo "              <canvas id='historychart' style='height:200px;width:90%;'></canvas><br>\n";
                $hidata = "";
                $lodata = "";
                $factorydata = "";
                $min = 0;
                $max = 0;
                $factory = 0;
                $offerCnt = 0;
                foreach ($last30 as $r) {
                    $offerCnt++;
                    if (empty($offer)) {
                        $offer = "{x: '".date('m/d/Y', $r["transactiondate"])."', y: ".$r["dailyweightedaverage"]."}";
                    } else {
                        $offer .= ", {x: '".date('m/d/Y', $r["transactiondate"])."', y: ".$r["dailyweightedaverage"]."}";
                    }
                    if (isset($priceHistory) && (count($priceHistory) > 0)) {
                        if (isset($priceHistory['0']['highprice']) && !empty($priceHistory['0']['highprice'])) {
                            if (empty($hidata)) {
                                $hidata = "{x: '".date('m/d/Y', $r["transactiondate"])."', y: ".$priceHistory['0']['highprice']."}";
                            } else {
                                $hidata .= ", {x: '".date('m/d/Y', $r["transactiondate"])."', y: ".$priceHistory['0']['highprice']."}";
                            }
                            $max = $priceHistory['0']['highprice'];
                        }
                        if (isset($priceHistory['0']['lowprice']) && !empty($priceHistory['0']['lowprice'])) {
                            if (empty($lodata)) {
                                $lodata = "{x: '".date('m/d/Y', $r["transactiondate"])."', y: ".$priceHistory['0']['lowprice']."}";
                            } else {
                                $lodata .= ", {x: '".date('m/d/Y', $r["transactiondate"])."', y: ".$priceHistory['0']['lowprice']."}";
                            }
                            $min = $priceHistory['0']['lowprice'];
                        }
                        if (isset($priceHistory['0']['maxfactory']) && !empty($priceHistory['0']['maxfactory'])) {
                            if (empty($factorydata)) {
                                $factorydata = "{x: '".date('m/d/Y', $r["transactiondate"])."', y: ".$priceHistory['0']['maxfactory']."}";
                            } else {
                                $factorydata .= ", {x: '".date('m/d/Y', $r["transactiondate"])."', y: ".$priceHistory['0']['maxfactory']."}";
                            }
                            $factory = $priceHistory['0']['maxfactory'];
                        }
                    }
                    $min = floor(min($min, $factory));
                    $max = ceil(max($max, $factory));
                }
                if ($offerCnt == 1) {
                    // make a duplicate so there is a trend line
                    $daybefore = strtotime(date("m/d/Y", $last30[0]["transactiondate"]) ." -1 day");
                    $dayafter = strtotime(date("m/d/Y", $last30[0]["transactiondate"]) ." +1 day");
                    $offer = "{x: '', y: ".$last30[0]["dailyweightedaverage"]."}, ".$offer;
                    if (isset($priceHistory['0']['maxfactory']) && !empty($priceHistory['0']['maxfactory'])) {
                        $factorydata .= ", {x: '', y: ".$priceHistory['0']['maxfactory']."}, ";
                    }
                }
                echo $this->displayHistoryChart($offer, $hidata, $lodata, $factorydata, $min, $max, $offerCnt);
                echo "            </aside>\n";
            }
            if(isset($priceHistory)) {
                echo "            <aside>\n";
                echo "              <header>\n";
                echo "                <h3>Product Info</h3>\n";
                echo "              </header>\n";
                echo "              <div>\n";
                echo "                <p># Boxes Traded: ".$info['0']['allboxes']."</p>\n";
                echo "                <p># Boxes Traded Last 30 Days: ".$info['0']['boxes30']."</p>\n";
                //echo "                <p>Average Sale Price: $".number_format((float)$priceHistory['0']['avg'], 2)."</p>\n";
                echo "                <p>Historical High: $".$priceHistory['0']['highprice']."</p>\n";
                echo "                <p>Historical Low: $".$priceHistory['0']['lowprice']."</p>\n";
                if (($priceHistory['0']['lowprice']) && ($priceHistory['0']['maxfactory'])) {
                    $pricePremium = (($priceHistory['0']['lowprice'] - $priceHistory['0']['maxfactory'])/ $priceHistory['0']['maxfactory']) * 100;
                    echo "                <p>Factory: $".$priceHistory['0']['maxfactory']."</p>\n";
                    echo "                <p><span title='CPI = ((Low Sell - Factory Cost)/ Factory Cost) * 100' class='indictionary'>CPI:</span> ".number_format($pricePremium, 2)." %</p>\n";
                }
                echo "              </div>\n";
                echo "            </aside>\n";
                if (is_array($recent) && (count($recent) > 0)) {
                    echo "            <aside>\n";
                    echo "              <header>\n";
                    echo "                <h3>Recent Trades</h3>\n";
                    echo "              </header>\n";
                    echo "              <div>\n";
                    echo "                <table class='recent-trades'>\n";
                    foreach ($recent as $r) {
                        echo "                  <tr>\n";
                        echo "                    <td data-label='date'>".date('m/d/Y', $r['transactiondate'])."</td>\n";
                        echo "                    <td data-label='price' title='box price'>".floatToMoney($r['boxprice'])."</td>\n";
                        echo "                    <td data-label='quantity' title='boxes'>".$r['boxquantity']."</td>\n";
                        //echo "                    <td data-label='Wanted/For Sale' ".$this->wfsTitle( $r['type'])."</td>\n";
                        echo "                  </tr>\n";
                    }
                    echo "                </table>\n";
                    echo "              </div>\n";
                    echo "            </aside>\n";
                }
            } else {
                echo "            <aside>\n";
                echo "              Need Product Info + Recent Trades\n";
                echo "            </aside>\n";
            }
            echo "          </div><!----secondary-widget right side-->\n";
        } else {
            // Dont't display the right widget
        }
    }

    public function boxesTraded($categoryId, $subCategoryId, $boxTypeId, $year = NULL) {
        global $DB;

        $last30 =  strtotime('-30 days');

        $andYear = (!empty($year)) ? " AND year   = '".$year."'" : "";

        $sql = "
            SELECT sum(tot.boxquantity) AS allboxes, sum(tot.boxquantity * tot.last30) AS boxes30
            FROM (
                SELECT boxquantity, CASE WHEN oh.transactiondate > ".$last30." THEN 1 ELSE 0 END AS last30
                FROM offer_history oh
                WHERE oh.quantity>0
                  AND oh.categoryid     = ".$categoryId."
                  AND oh.subcategoryid  = ".$subCategoryId."
                  AND oh.boxtypeid      = ".$boxTypeId."
                  ".$andYear."
            ) tot";

//        echo "<pre>".$sql."</pre>";
        $data = $DB->sql_query_params($sql);

        return $data;
    }

    public function historicalPrice($categoryId, $subCategoryId, $boxTypeId, $year = NULL) {
        global $DB;

        $andYear = (!empty($year)) ? " AND oh.year   = '".$year."'" : "";

        $sql = "
            SELECT max(boxprice)::numeric(12,2) as highprice, min(boxprice)::numeric(12,2) AS lowprice, max(factorycost)::numeric(12,2) AS maxfactory
            FROM (
                SELECT oh.boxprice, f.dprice as factorycost
                  FROM offer_history oh
                  LEFT JOIN listings f
                    ON  f.userid=".FACTORYCOSTID."
                    AND f.categoryid        = oh.categoryid
                    AND f.subcategoryid     = oh.subcategoryid
                    AND f.boxtypeid         = oh.boxtypeid
                    AND isnull(f.year, '1') = isnull(oh.year, '1')
                    AND f.status            = 'OPEN'
                    AND f.uom               = 'box'
                 WHERE oh.quantity       > 0
                   AND oh.categoryid     = ".$categoryId."
                   AND oh.subcategoryid  = ".$subCategoryId."
                   AND oh.boxtypeid      = ".$boxTypeId."
                   ".$andYear."
            ) tot";

//        echo "<pre>".$sql."</pre>";
        $data = $DB->sql_query_params($sql);

        return $data;
    }

// NOT CURRENTLY USED
    public function historyInfo($categoryId, $subCategoryId, $year = NULL) {
        global $DB;

        $previousYear =  time() - 1597489474;

        $sql = "
            SELECT COUNT(offerqty) AS count, MAX(lstdprice) AS high, MIN(lstdprice) AS low, AVG(lstdprice) AS avg, accepteddate
              FROM offeritems
             WHERE lstcatid     = ".$categoryId."
               AND lstsubcatid  = ".$subCategoryId."
               AND modifydate   > ".$previousYear;
        if (!empty($year)) {
            $sql .= "
               AND lstyear   = '".$year."'";
        }
        $sql .= "
            GROUP BY accepteddate";

//        echo "<pre>".$sql."</pre>";
        $data = $DB->sql_query_params($sql);

        return $data;
    }

    public function recentTrades($categoryId, $subCategoryId, $boxTypeId, $year = NULL) {
        global $DB;

        $andYear = (!empty($year)) ? " AND year   = '".$year."'" : "";

        $sql = "
            SELECT oh.transactiondate, oh.boxprice, oh.type, oh.boxquantity
              FROM offer_history oh
             WHERE oh.quantity      > 0
               AND oh.categoryid    = ".$categoryId."
               AND oh.subcategoryid = ".$subCategoryId."
               AND oh.boxtypeid     = ".$boxTypeId."
               ".$andYear."
             ORDER by oh.transactiondate DESC
             LIMIT 12";

//        echo "<pre>".$sql."</pre>";
        $data = $DB->sql_query_params($sql);

        return $data;
    }

    public function last30DaysAverage($categoryId, $subCategoryId, $boxTypeId, $year = NULL) {
        global $DB;

        $andYear = (!empty($year)) ? "AND oh.year = '".$year."'" : "";

        $sql = "
            SELECT startdatetime(oh.transactiondate) as transactiondate,
                   (sum(oh.boxprice * oh.boxquantity) / sum(oh.boxquantity))::INTEGER as dailyweightedaverage
              FROM offer_history        oh
              JOIN categories           c   ON  c.categoryid        = oh.categoryid
                                            AND c.categoryid        = ".$categoryId."
              JOIN subcategories        sc  ON  sc.subcategoryid    = oh.subcategoryid
                                            AND sc.subcategoryid    = ".$subCategoryId."
              JOIN boxtypes             bt  ON  bt.boxtypeid        = oh.boxtypeid
                                            AND bt.boxtypeid        = ".$boxTypeId."
             WHERE oh.quantity > 0
               ".$andYear."
               AND inttodate(oh.transactiondate)::TIMESTAMP > CURRENT_DATE - interval '30 day'
            GROUP BY startdatetime(oh.transactiondate)
            ORDER BY startdatetime(oh.transactiondate)
        ";

//        echo "<pre>".$sql."</pre>";
        $data = $DB->sql_query_params($sql);

        return $data;
    }

    public function wfsTitle($type) {
        $output = "";

        if ($type == 'Wanted') {
            $output = "title='Wanted'>W";
        }
        if ($type == 'For SAle') {
            $output = "title='For Sale'>FS";
        }

        return $output;

    }

    private function displayHistoryChart($offers, $hi, $lo, $factory, $min, $max, $offerCnt) {
        $output = "";
        $output .= "<script>\n";
        $output .= "var canvas = document.getElementById('historychart');\n";
        $output .= "new Chart(canvas, {\n";
        $output .= "  type: 'line',\n";
        $output .= "  data: {\n";
        $output .= "    datasets: [\n";
        $output .= "      {\n";
        $output .= "        label: 'Offers',\n";
        $output .= "        lineTension: 0,\n";
        $output .= "        borderColor: '#3A6F9E',\n";
        $output .= "        data: [".$offers."]\n";
        $output .= "      }, \n";
        if (!empty($hi) && $offerCnt > 1) {
            $output .= "      {\n";
            $output .= "        label: 'High',\n";
            $output .= "        lineTension: .8,\n";
            $output .= "        borderColor: '#50B93A',\n";
            $output .= "        data: [".$hi."]\n";
            $output .= "      }, \n";
            $output .= "      {\n";
            $output .= "        label: 'Low',\n";
            $output .= "        lineTension: .8,\n";
            $output .= "        borderColor: '#D90444',\n";
            $output .= "        data: [".$lo."]\n";
            $output .= "      }, \n";
        }
        if (!empty($factory)) {
            $output .= "      {\n";
            $output .= "        label: 'Factory',\n";
            $output .= "        lineTension: .8,\n";
            $output .= "        borderColor: '#ffbf00',\n";
            $output .= "        data: [".$factory."]\n";
            $output .= "      }, \n";
        }
        $output .= "   ]\n";
        $output .= "  },  // data\n";
        $output .= "  options: {\n";
        $output .= "    responsive: true,\n";
        if (!empty($min) && $min < $max) {
            $output .= "    scales: {\n";
            $output .= "      y: {\n";
            $output .= "          min: ".$min.",\n";
            $output .= "          max: ".$max."\n";
            $output .= "      }\n";
            $output .= "    },\n";
        }
        $output .= "    transitions: {\n";
        $output .= "      show: {\n";
        $output .= "        animations: {\n";
        $output .= "          x: {\n";
        $output .= "            from: 0\n";
        $output .= "          },\n";
        $output .= "          y: {\n";
        $output .= "            from: 0\n";
        $output .= "          },\n";
        $output .= "        },\n";
        $output .= "      },\n";
        $output .= "      hide: {\n";
        $output .= "        animations: {\n";
        $output .= "          x: {\n";
        $output .= "            to: 0\n";
        $output .= "          },\n";
        $output .= "          y: {\n";
        $output .= "            to: 0\n";
        $output .= "          },\n";
        $output .= "        },\n";
        $output .= "      },\n";
        $output .= "    },\n";
        $output .= "    plugins: {\n";
        $output .= "      legend: {\n";
        $output .= "        labels: {\n";
        $output .= "          boxWidth: 10\n";
        $output .= "        },\n";
        $output .= "      },\n";
        $output .= "      title: {\n";
        $output .= "        display: true,\n";
        $output .= "        position: 'bottom',\n";
        $output .= "        text: '30 Day Daily Average'\n";
        $output .= "      },\n";
        $output .= "    },\n";
        $output .= "    elements: {\n";
        $output .= "      point: {\n";
        $output .= "        radius: 0\n";
        $output .= "      },\n";
        $output .= "      line: {\n";
        $output .= "        borderWidth: 3\n";
        $output .= "      }\n";
        $output .= "    },\n";
        $output .= "    scales: {\n";
        $output .= "      x: {\n";
        $output .= "        ticks: {\n";
        $output .= "          display: false\n";
        $output .= "        },\n";
        $output .= "      },\n";
        $output .= "    }\n";
        $output .= "  }\n";
        $output .= "});  // new Chart\n";
        $output .= "</script>\n";

        return $output;
    }

    public function hasSMSNotification() {
        global $page;

        $sql = "
            SELECT preferenceid
              FROM notification_preferences
             WHERE notification_type    = 'SMS'
               AND userid               = ".$_SESSION['userId']."
               AND (validated_on IS NOT NULL
                    OR isactive = 0)
           LIMIT 1
        ";
//      echo "<pre>".$sql."</pre>";
        $bob = $this->db->get_field_query($sql);
        $retval = (empty($bob)) ? false : true;

        return $retval;
    }
}

?>