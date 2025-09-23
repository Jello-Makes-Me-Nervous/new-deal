<?php

DEFINE("DEFAULTHRS",        24);
DEFINE("BYPASSDEFAULTHRS",  500);
DEFINE("DEFAULTMATCHES",    50);
DEFINE("MAXHRSSINCE",       1680);
DEFINE("MAXBYPASSHRSSINCE", 1800000);

require_once('templateMarket.class.php');


class hostlist extends templateMarket {

    public  $tabs = array();
    public  $selectedcats;
    public  $boxtypeid;
    public  $subcategoryid;
    public  $year;
    public  $keywordsearch;
    public  $type;
    public  $hourssince;
    public  $matches;
    public  $sortby;
    public  $searchbtn;
    public  $selectedtab;
    public  $doingAjax;
    public  $proceed;
    public  $bypass;
    public  $data;


    public function __construct($loginRequired = true, $showmsgs = true, $bypassMustReplyCheck = false) {
        parent::__construct($loginRequired, $showmsgs, $bypassMustReplyCheck);

        $this->requireJS("/scripts/jquery.multi-select.js");
        $this->requireStyle("/styles/multi-select.css");

        $this->proceed = true;
        if (empty($this->bypass)) {
            if (empty($this->categoryids) && empty($this->keywordsearch)) {
                if (empty($this->hourssince)) {
                    $this->messages->addErrorMsg("A category, search criteria or hours since must be entered.");
                    $this->proceed = false;
                } elseif ($this->hourssince > MAXHRSSINCE) {
                    $this->messages->addErrorMsg("Without a category, search criteria selected; hours since must be a reasonable number (1-".MAXHRSSINCE.").");
                    $this->proceed = false;
                }
            }
        }

        $this->initData();

        if (empty($this->selectedcats) && empty($this->boxtypeid) && empty($this->keywordsearch) && $this->sortby == 't' &&
            $this->hourssince == DEFAULTHRS && $this->matches == DEFAULTMATCHES && $this->selectedtab == 'sports') {
            $this->doingAjax = true;
            $this->requireJS("/scripts/ajax.js");
            $url = "/hotlist_ajax.php";
            $imgurl = "/images/busy.gif";
            $waitmsg = "<div class=\"center\"><img src=\"".$imgurl."\" width=\"32\" height=\"32\" alt=\"Please wait\"><br>loading latest information.</div>";
            $ajaxcall = "ajax_getcontent('".$url."', 'hotlist_goes_here', '".$waitmsg."');";

            $this->jsInit($ajaxcall);
        }

        if ($this->selectedtab == "sports") {
            $this->data = $this->getSportsData();
        } else {
            $this->data = $this->getGamingData();
        }
        if (isset($this->data) && (count($this->data) == 1) && !empty($this->bypass)) {
            $d   = reset($this->data);
            $url = "listing.php?categoryid=".$d["categoryid"]."&subcategoryid=".$d["subcategoryid"]."&boxtypeid=".$d["boxtypeid"]."&listingtypeid=".$d["categorytypeid"]."&year=".$d["year"];
            header("Location: ".$url);
        }
    }

    public function paramInit() {
        parent::paramInit();

        $this->selectedcats       = optional_param('selectedcats', NULL, PARAM_RAW);
        $this->boxtypeid          = optional_param('boxtypeid', NULL, PARAM_INT);
        $this->subcategoryid      = optional_param('subcategoryid', NULL, PARAM_INT);
        $this->year               = optional_param('year', NULL, PARAM_RAW);
        $this->keywordsearch      = optional_param('keywordsearch', NULL, PARAM_RAW);
        $this->type               = optional_param('type', NULL, PARAM_RAW);
        $this->matches            = optional_param('matches', DEFAULTMATCHES, PARAM_INT);
        $this->sortby             = optional_param('sortby', "t", PARAM_RAW);
        $this->searchbtn          = optional_param("searchbtn", "Search", PARAM_RAW);
        $this->selectedtab        = optional_param("selectedtab", "sports", PARAM_RAW);
        $this->bypass             = optional_param('bph', 0, PARAM_INT);
        if (empty($this->bypass)) {
            $this->hourssince         = optional_param('hourssince', DEFAULTHRS, PARAM_INT);
        } else {
            $this->hourssince         = optional_param('hourssince', BYPASSDEFAULTHRS, PARAM_INT);
        }

        $this->categoryids = trim(str_replace("][", ",", $this->selectedcats), "[]");
    }

    public function initData() {

        $this->doingAjax = false;
        $this->tabs["sports"] = array("label"=>"Sports", "link"=>"/hotlist_sports.php?selectedtab=sports");
        $this->tabs["gaming"] = array("label"=>"Gaming", "link"=>"/hotlist_gaming.php?selectedtab=gaming");

    }

    public function displayPage() {
        echo "<h3>Hotlist</h3>\n";
        echo "<article>\n";
        echo "  <div class='entry-content'>\n";
        $this->displayTabs($this->selectedtab);
        echo "    <div class='tabcontent' style='display:block;'>\n";
        switch ($this->selectedtab) {
            case "sports":  $this->displayForm(LISTING_TYPE_SPORTS, "sports");
                            break;
            case "gaming":  $this->displayForm(LISTING_TYPE_GAMING, "gaming");
                            break;
            default:
                            break;
        }
        echo "      <div id='hotlist_goes_here'>\n";
        if (!$this->doingAjax) {
            switch ($this->selectedtab) {
                case "sports":  $this->displaySports();
                                break;
                case "gaming":  $this->displayGaming();
                                break;
                default:
                                break;
            }
        }
        echo "      </div> <!-- hotlist_goes_here -->\n";
        echo "    </div>\n";
        echo "  </div>\n";
        echo "</article>\n";
    }

    private function displayTabs($selectedTab) {
        echo "    <div class='tab'>\n";
        foreach($this->tabs as $tabId=>$tab) {
            $isActive = ($tabId == $selectedTab) ? " active" : "";
            $link = "<a href='".$tab["link"]."'>".$tab["label"]."</a>";
            echo "      <button class='tablinks".$isActive."' >".$link."</button>\n";
        }
        echo "    </div>\n";
    }

    private function displayForm($cattypeid, $selectedtab) {
        echo "      <form name ='search' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
        echo "        <table class='table-condensed'>\n";
        echo "          <tbody>\n";
        echo "            <tr>\n";
        echo "              <td rowspan='3'>\n";
        $cats = $this->getCategories($this->categoryids, $cattypeid);
        echo $this->getMultiSelect($cats, "categoryid", "categoryid", "categorydescription");
        echo "              </td>\n";
        echo "              <td colspan='2'>\n";
        echo "                <label for='keywordsearch'>Keyword Search</label><br>\n";
        echo "                <input type='text' name='keywordsearch' id='keywordsearch' value='".$this->keywordsearch."' class='input'>\n";
        echo "              </td>\n";
        echo "              <td>\n";
        echo "                <label for='boxtypeid'>Box Type</label><br>\n";
        $boxes = $this->getBoxtypes($this->categoryids, $cattypeid);
        echo getSelectDDM($boxes, "boxtypeid", "boxtypeid", "boxtypename", NULL, $this->boxtypeid, "Select", 0);
        echo "              </td>\n";
        echo "            </tr>\n";
        echo "            <tr>\n";
        echo "              <td>\n";
        echo "                <div style='display:inline;white-space: nowrap;'>\n";
        echo "                <label for='hourssince'>Hours since</label> \n";
        echo "                <input type='text' name='hourssince' id='hourssince' size='4' value='".$this->hourssince."' class='input' style='width:75px;'>\n";
        echo "                 (hrs ago)\n";
        echo "                </div>\n";
        echo "              </td>\n";
        echo "              <td>\n";
        echo "                <div style='display:inline;white-space: nowrap;'>\n";
        echo "                <label for='matches'># of Matches</label> \n";
        echo "                <input type='text' name='matches' id='matches' size='3' value='".$this->matches."' class='input' style='width:50px;'>\n";
        echo "                </div>\n";
        echo "              </td>\n";
        echo "              <td rowspan='2'>\n";
        echo "                <label for='sortby_w'>Sort By:</label><br>\n";
        $checked = ($this->sortby == "d") ? "CHECKED" : "";
        echo "                <div style='width: 75px;display:inline;white-space: nowrap;'>\n";
        echo "                  &nbsp;&nbsp;<input type='radio' name='sortby' id='sortby_w' value='d' class='input' ".$checked.">\n";
        echo "                  <label for='sortby_w'>Date</label>\n";
        echo "                </div><br>\n";
        $checked = ($this->sortby == "ycscbt") ? "CHECKED" : "";
        echo "                <div style='width: 75px;display:inline;white-space: nowrap;'>\n";
        echo "                  &nbsp;&nbsp;<input type='radio' name='sortby' id='sortby_both' value='ycscbt' class='input' ".$checked.">\n";
        echo "                  <label for='sortby_both'>Year, Cat, Subcat, Boxtype</label>\n";
        echo "                </div><br>\n";
        $checked = (empty($this->sortby) || $this->sortby == "t") ? "CHECKED" : "";
        echo "                <div style='width: 75px;display:inline;white-space: nowrap;'>\n";
        echo "                  &nbsp;&nbsp;<input type='radio' name='sortby' id='sortby_both' value='t' class='input' ".$checked.">\n";
        echo "                  <label for='sortby_both'>Totals (buy+sells)</label>\n";
        echo "                </div>\n";
        echo "              </td>\n";
        echo "            </tr>\n";
        echo "            <tr>\n";
        echo "              <td class='center' colspan='2'>\n";
        echo "                <input type='submit' name='searchbtn' id='searchbtn' value='Search'>\n";
        echo "              </td>\n";
        echo "            </tr>\n";
        echo "          </tbody>\n";
        echo "        </table>\n";
        echo "        <input type='hidden' name='selectedtab' id='selectedtab' value='".$selectedtab."'>\n";
        echo "      </form>\n";

    }

    public function displaySports() {
        if (!empty($this->searchbtn)) {  //$proceed
            echo "      <table class='table-condensed'>\n";
            echo "        <thead>\n";
            echo "          <tr>\n";
            echo "            <th scope='col' colspan='2'> Listing</th>\n";
            echo "            <th scope='col'>Variation</th>\n";
            echo "            <th scope='col'>UPC</th>\n";
            echo "            <th scope='col'>Factory</th>\n";
            echo "            <th scope='col'>Trend</th>\n";
            echo "            <th scope='col'>High Buy / Low Sell (case)</th>\n";
            echo "            <th scope='col'>High Buy / Low Sell (box)</th>\n";
            echo "            <th scope='col'>Buys / Sells / Total</th>\n";
            echo "          </tr>\n";
            echo "        </thead>\n";
            echo "        <tbody>\n";
            if (empty($this->data)) {
                echo "          <tr><td colspan='7'>No matching listings found.</td></tr>\n";
            } else {
                $x = 0;
                $inSecondary = false;
                foreach($this->data as $d) {
                    $x++;
                    $rowClass = "";
                    $rowStyle = "";
                    $secondaryClassPrefix= "secondary";
                    if ($d['secondary']) {
                        $secondaryOnly = false;
                        $rowClass = " class='".$secondaryClassPrefix."sc' ";
                        if (!$secondaryOnly) {
                            $rowStyle = " style='display:none;' ";
                        }
                        if (! $inSecondary) {
                            echo "<tr>";
                            echo "<td colspan='7'>";
                            if (! $secondaryOnly) {
                                echo "<a title='Show secondary subcategories' href='#' onClick='$(\".".$secondaryClassPrefix."sc\").show();$(\".".$secondaryClassPrefix."toggle\").hide(); return(false);' class='".$secondaryClassPrefix."toggle' ><i class='fa-solid fa-plus'></i></a>";
                                echo "<a title='Hide secondary subcategories' href='#' onClick='$(\".".$secondaryClassPrefix."sc\").hide();$(\".".$secondaryClassPrefix."toggle\").show(); return(false);' class='".$secondaryClassPrefix."sc' ".$rowStyle."><i class='fa-solid fa-minus'></i></a>";
                            }
                            echo " <strong>Secondary Subcategories</strong>";
                            echo "</td>";
                            echo "</tr>\n";
                            $inSecondary = true;
                        }
                    }
                    echo "    <tr ".$rowClass." ".$rowStyle.">\n";
                    echo "            <td data-label='#' class='number'>".$x.".</td>\n";
                    $label = $d['year']." ".$d['subcategoryname']." ".$d['categorydescription']." ".ucwords($d['boxtypename']);
                    $url    = "listing.php?categoryid=".$d["categoryid"]."&subcategoryid=".$d["subcategoryid"]."&boxtypeid=".$d["boxtypeid"]."&listingtypeid=".$d["categorytypeid"]."&year=".$d["year"];
                    $link   = "<a href='".$url."' target='_blank'>".$label."</a>";
                    $title  = "Modified: ".date("m/d/Y h:i:sA", $d["modifydate"]);
                    echo "            <td title='".$title."'>".$link."</td>\n";
                    echo "            <td data-label='Variation' class='center'>".$d["variation"]."</td>\n";
                    $upcs = (empty($d["upcs"])) ? "" : str_replace(",", "<br>", $d["upcs"]);
                    echo "            <td data-label='UPC' class='center'>".$upcs."</td>\n";
                    $fc  = (empty($d['factory_cost'])) ? "" : "$".number_format($d['factory_cost'], 2);
                    echo "            <td data-label='Factory' class='center'>".$fc."</td>\n";
                    echo "            <td data-label='Trend' class='center'>".$this->trendIndicator($d["trend"])."</td>\n";
                    $hibuy  = (empty($d['highbuy_case'])) ? "" : "$".number_format($d['highbuy_case'], 2);
                    $losell = (empty($d['lowsell_case'])) ? "" : "$".number_format($d['lowsell_case'], 2);
                    echo "            <td data-label='Hi Buy / Lo Sell (case)' class='center'>".$hibuy." / ".$losell."</td>\n";
                    $hibuy  = (empty($d['highbuy_box'])) ? "" : "$".number_format($d['highbuy_box'], 2);
                    $losell = (empty($d['lowsell_box'])) ? "" : "$".number_format($d['lowsell_box'], 2);
                    echo "            <td data-label='Hi Buy / Lo Sell (box)' class='center'>".$hibuy." / ".$losell."</td>\n";
                    $total      = $d["buycnt"] + $d["sellcnt"];
                    echo "            <td data-label='Buy / Sell Counts' class='center'>".$d["buycnt"]." / ".$d["sellcnt"]." / ".$total."</td>\n";
                    echo "          </tr>\n";
                }
            }
            echo "        </tbody>\n";
            echo "      </table>\n";
        }
    }

    public   function displayGaming() {
        if (!empty($this->searchbtn)) {  //$proceed
            echo "      <table class='table-condensed'>\n";
            echo "        <thead>\n";
            echo "          <tr>\n";
            echo "            <th scope='col' colspan='2'> Listing</th>\n";
            echo "            <th scope='col'>Variation</th>\n";
            echo "            <th scope='col'>UPC</th>\n";
            echo "            <th scope='col'>Factory</th>\n";
            echo "            <th scope='col'>Trend</th>\n";
            echo "            <th scope='col'>High Buy / Low Sell (case)</th>\n";
            echo "            <th scope='col'>High Buy / Low Sell (box)</th>\n";
            echo "            <th scope='col'>Buys / Sells / Total</th>\n";
            echo "          </tr>\n";
            echo "        </thead>\n";
            echo "        <tbody>\n";
            if (empty($this->data)) {
                echo "          <tr><td colspan='7'>No matching listings found.</td></tr>\n";
            } else {
                $x = 0;
                foreach($this->data as $d) {
                    $x++;
                    $inSecondary = false;
                    $rowClass = "";
                    $rowStyle = "";
                    $secondaryClassPrefix= "secondary";
                    if ($d['secondary']) {
                        $secondaryOnly = false;
                        $rowClass = " class='".$secondaryClassPrefix."sc' ";
                        if (!$secondaryOnly) {
                            $rowStyle = " style='display:none;' ";
                        }
                        if (! $inSecondary) {
                            echo "<tr>";
                            echo "<td colspan='7'>";
                            if (! $secondaryOnly) {
                                echo "<a title='Show secondary subcategories' href='#' onClick='$(\".".$secondaryClassPrefix."sc\").show();$(\".".$secondaryClassPrefix."toggle\").hide(); return(false);' class='".$secondaryClassPrefix."toggle' ><i class='fa-solid fa-plus'></i></a>";
                                echo "<a title='Hide secondary subcategories' href='#' onClick='$(\".".$secondaryClassPrefix."sc\").hide();$(\".".$secondaryClassPrefix."toggle\").show(); return(false);' class='".$secondaryClassPrefix."sc' ".$rowStyle."><i class='fa-solid fa-minus'></i></a>";
                            }
                            echo " <strong>Secondary Subcategories</strong>";
                            echo "</td>";
                            echo "</tr>\n";
                            $inSecondary = true;
                        }
                    }
                    echo "    <tr ".$rowClass." ".$rowStyle.">\n";
                    echo "            <td data-label='#' class='number'>".$x.".</td>\n";
                    $label = $d['year']." ".$d['subcategoryname']." ".$d['categorydescription']." ".ucwords($d['boxtypename']);
                    $url    = "listing.php?categoryid=".$d["categoryid"]."&subcategoryid=".$d["subcategoryid"]."&boxtypeid=".$d["boxtypeid"]."&listingtypeid=".$d["categorytypeid"]."&year=".$d["year"];
                    $link   = "<a href='".$url."' target='_blank'>".$label."</a>";
                    $title  = "Modified: ".date("m/d/Y h:i:sA", $d["modifydate"]);
                    echo "            <td title='".$title."'>".$link."</td>\n";
                    echo "            <td data-label='UPC' class='center'>".$d["variation"]."</td>\n";
                    echo "            <td data-label='UPC' class='center'>".$d["upcs"]."</td>\n";
                    $fc  = (empty($d['factory_cost'])) ? "" : "$".number_format($d['factory_cost'], 2);
                    echo "            <td data-label='Factory' class='center'>".$fc."</td>\n";
                    echo "            <td data-label='Trend' class='center'>".$this->trendIndicator($d["trend"])."</td>\n";
                    $hibuy  = (empty($d['highbuy_case'])) ? "" : "$".number_format($d['highbuy_case'], 2);
                    $losell = (empty($d['lowsell_case'])) ? "" : "$".number_format($d['lowsell_case'], 2);
                    echo "            <td data-label='Hi Buy / Lo Sell (case)' class='center'>".$hibuy." / ".$losell."</td>\n";
                    $hibuy  = (empty($d['highbuy_box'])) ? "" : "$".number_format($d['highbuy_box'], 2);
                    $losell = (empty($d['lowsell_box'])) ? "" : "$".number_format($d['lowsell_box'], 2);
                    echo "            <td data-label='Hi Buy / Lo Sell (box)' class='center'>".$hibuy." / ".$losell."</td>\n";
                    $total      = $d["buycnt"] + $d["sellcnt"];
                    echo "            <td data-label='Buy / Sell Counts' class='center'>".$d["buycnt"]." / ".$d["sellcnt"]." / ".$total."</td>\n";
                    echo "          </tr>\n";
                }
            }
            echo "        </tbody>\n";
            echo "      </table>\n";
        }
    }

    private function getCategories($selectedids, $cattypeid) {
        global $page;

        $catids = (empty($selectedids)) ? "0" : $selectedids;
        $sql = "
            SELECT c.categorydescription, c.categoryid,
                    case when sel.categoryid is not null then 1
                         else 0 end as selected
              FROM categories       c
              JOIN categorytypes    ct  ON  ct.categorytypeid   = c.categorytypeid
                                        AND ct.categorytypeid in (".$cattypeid.")
              LEFT JOIN categories  sel ON  sel.categoryid      = c.categoryid
                                        AND sel.categoryid in (".$catids.")
             WHERE c.active = 1
             ORDER BY c.categorytypeid, c.categorydescription COLLATE \"POSIX\"
        ";

        $rs = $page->db->sql_query_params($sql);

        return $rs;
    }

    private function getMultiSelect($data, $idname, $valuefield, $displayfield) {
        global $page;

        $catheader = "<div class='ms_header'>Categories</div>";
        $selectedcatheader = "<div class='ms_header'>Selected Categories</div>";
        $options  = "{";
        $options .= "selectableHeader:\"".$catheader."\",";
        $options .= "selectionHeader: \"".$selectedcatheader."\",";
        $options .= "afterSelect: function(values){ $('#selectedcats').val($('#selectedcats').val() + '[' + values + ']'); },";
        $options .= "afterDeselect: function(values){ $('#selectedcats').val($('#selectedcats').val().replace('[' + values + ']', '')); }";
        $options .= "}";
        $js  = "
            $(\"#".$idname."\").multiSelect(".$options.");
        ";
        $page->jsInit($js);

        $output =  "<select id='".$idname."' multiple='multiple'/>\n";
        $selectedcats = "";
        foreach ($data as $d) {
            $selected = (isset($d["selected"]) && !empty($d["selected"])) ? "selected" : "";
            $output .= "  <option value='".$d[$valuefield]."' ".$selected.">".$d[$displayfield]."</option>\n";
            $selectedcats .= (!empty($selected)) ? "[".$d[$valuefield]."]" : "";
        }
        $output .= "</select>\n";
        $output .= "<input type='hidden' name='selectedcats' id='selectedcats' value='".$selectedcats."'>\n";

        return $output;
    }

    private function getSubcategories($categoryid, $boxtypeid = NULL, $year = NULL) {
        global $page;

        $sql = "
            SELECT DISTINCT(s.subcategoryname), s.subcategoryid
              FROM listings         l
              JOIN subcategories    s   ON  s.subcategoryid     = l.subcategoryid
                                        AND s.active            = 1
             WHERE l.categoryid = ".$categoryid."
            ";
            if (!empty($boxtypeid)) {
                $sql .= "   AND l.boxtypeid = ".$boxtypeid."
                ";
            }
            if (!empty($year)) {
                $sql .= "   AND l.year = '".$year."'
                ";
            }
        $sql .= "
            ORDER BY s.subcategoryname
        ";

    //    echo "<pre>".$sql."</pre>";
        $result = $page->db->sql_query_params($sql);

        return $result;
    }

    private function getBoxtypes($categoryids, $cattypeid) {
        global $page;

        $sql = "
            SELECT b.boxtypeid, b.boxtypename
              FROM listings         l
              JOIN boxtypes         b   ON  b.boxtypeid     = l.boxtypeid
                                        AND b.active        = 1
                                        AND b.categorytypeid in (".$cattypeid.")
            GROUP BY b.boxtypeid, b.boxtypename
            ORDER BY b.boxtypename  COLLATE \"POSIX\"
        ";

    //  echo "<pre>".$sql."</pre>";
        $rs = $page->db->sql_query_params($sql);

        return $rs;
    }

    private function getYears($categoryid, $subcategoryid = NULL, $boxtypeid = NULL) {
        global $page;
        $sql = "";
        $sql .= "
            SELECT DISTINCT(year)
              FROM listings
             WHERE categoryid  = ".$categoryid."
        ";
        if (!empty($subcategoryid)) {
            $sql .= "       AND subcategoryid = ".$subcategoryid."
            ";
        }
        if (!empty($boxtypeid)) {
           $sql .= "   AND boxtypeid = ".$boxtypeid."
           ";
        }
        $sql .= "
             ORDER BY year DESC
        ";

    //    echo "<pre>".$sql."</pre>";
        $result = $page->db->sql_query_params($sql);

        return $result;

    }

    private function getSportsData() {

        $factoryCostID = $this->utility->getDealerId(FACTORYCOSTNAME);
        $pgdate = $this->db->get_field_query("SELECT max(pgdate) FROM price_guide");
        $catid  = (empty($this->categoryids)) ? "" : "AND c.categoryid IN (".$this->categoryids.")";
        $btid   = (empty($this->boxtypeid))   ? "" : "AND bt.boxtypeid = ".$this->boxtypeid;

        $hrssince = "";
        if (empty($this->bypass)) {
            $hourssince = $this->hourssince;
            if (empty($this->hourssince)) {
                $hourssince = DEFAULTHRS;
            } elseif ($this->hourssince > MAXHRSSINCE) {
                $hourssince = MAXHRSSINCE;
            }
            $hoursago   = $hourssince * 60*60;
            $hrssince   = "AND l.modifydate > (nowtoint() - ".$hoursago.")";
        } else {
            $hrssince   = "AND l.modifydate > (nowtoint() - ".MAXBYPASSHRSSINCE.")";
        }

        $keyword    = "";
        if (!empty($this->keywordsearch)) {
            $searchstring = strtolower(trim($this->keywordsearch));
            $searcharray = explode(" ",$searchstring);
            foreach($searcharray as $sa) {
                $keyword .= " AND (lower(l.year) LIKE '%".$sa."%'\n";
                $keyword .=  "     OR lower(c.categorydescription) LIKE '%".$sa."%'\n";
                $keyword .=  "     OR lower(sc.subcategoryname) LIKE '%".$sa."%'\n";
                $keyword .=  "     OR lower(bt.boxtypename) LIKE '%".$sa."%'\n";
                $keyword .=  "     OR lower(upc.upcs) LIKE '%".$sa."%'\n";
                $keyword .=  "     OR lower(upc.variation) LIKE '%".$sa."%')\n";
            }
        }

        SWITCH ($this->sortby) {
            CASE "d":       $sortby = "m.modifydate DESC";
                            break;
            CASE "ycscbt":  $sortby = "l.year DESC, c.categorydescription, sc.subcategoryname, bt.boxtypename";
                            break;
            CASE "t":       $sortby = "totalcnt DESC";
                            break;
            DEFAULT:        $sortby = "totalcnt DESC";
        }


        $random = rand();
        $sql = "DROP TABLE IF EXISTS tmp_high_buy_box_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_low_sell_box_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_high_buy_case_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_low_sell_case_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_buy_cnt_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_sell_cnt_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_factory_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_upcs_".$random;
        $this->queries->AddQuery($sql);

        $sql = "
            CREATE TEMPORARY TABLE tmp_high_buy_box_".$random." AS
            SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year,
                   MAX(l.boxprice) AS highbuy_box
              FROM listings             l
              JOIN categories           c   ON  c.categoryid        = l.categoryid
                                            AND c.categorytypeid    = ".LISTING_TYPE_SPORTS."
                                            AND c.active            = 1
              JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                            AND sc.active           = 1
              JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                            AND bt.active           = 1
              JOIN assignedrights       a   ON  a.userid            = l.userid
                                            AND a.userrightid       = 1
              JOIN userinfo             u   ON  u.userid            = l.userid
                                            AND u.userclassid       = 3
                                            AND u.vacationbuy       = 0
              LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                            AND stl.userrightid     = ".USERRIGHT_STALE."
             WHERE l.type               = 'Wanted'
               AND l.status             = 'OPEN'
               AND l.uom IN ('box')
               ".$catid."
               ".$btid."
               AND l.userid             <> ".$factoryCostID."
               AND stl.userid IS NULL
            GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
            ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
        ";
        $this->queries->AddQuery($sql);

        $sql = "CREATE INDEX idx_highbuybox_cidscidbtid ON tmp_high_buy_box_".$random." USING btree (categoryid, subcategoryid, boxtypeid)";
        $this->queries->AddQuery($sql);

        $sql = "
            CREATE TEMPORARY TABLE tmp_low_sell_box_".$random." AS
            SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year,
                   MIN(l.boxprice) AS lowsell_box
              FROM listings             l
              JOIN categories           c   ON  c.categoryid        = l.categoryid
                                            AND c.active            = 1
                                            AND c.categorytypeid    = ".LISTING_TYPE_SPORTS."
              JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                            AND sc.active           = 1
              JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                            AND bt.active           = 1
              JOIN assignedrights       a   ON  a.userid            = l.userid
                                            AND a.userrightid       = 1
              JOIN userinfo             u   ON  u.userid            = l.userid
                                            AND u.userclassid       = 3
                                            AND u.vacationsell      = 0
              LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                            AND stl.userrightid     = ".USERRIGHT_STALE."
             WHERE l.type               = 'For Sale'
               AND l.status             = 'OPEN'
               AND l.uom IN ('box')
               ".$catid."
               ".$btid."
               AND l.userid             <> ".$factoryCostID."
               AND stl.userid IS NULL
            GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
            ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
        ";
        $this->queries->AddQuery($sql);

        $sql = "CREATE INDEX idx_lowsellbox_cidscidbtid ON tmp_low_sell_box_".$random." USING btree (categoryid, subcategoryid, boxtypeid)";
        $this->queries->AddQuery($sql);

        $sql = "
            CREATE TEMPORARY TABLE tmp_high_buy_case_".$random." AS
            SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year,
                   MAX(l.boxprice) AS highbuy_case
              FROM listings             l
              JOIN categories           c   ON  c.categoryid        = l.categoryid
                                            AND c.categorytypeid    = ".LISTING_TYPE_SPORTS."
                                            AND c.active            = 1
              JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                            AND sc.active           = 1
              JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                            AND bt.active           = 1
              JOIN assignedrights       a   ON  a.userid            = l.userid
                                            AND a.userrightid       = 1
              JOIN userinfo             u   ON  u.userid            = l.userid
                                            AND u.userclassid       = 3
                                            AND u.vacationbuy       = 0
              LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                            AND stl.userrightid     = ".USERRIGHT_STALE."
             WHERE l.type               = 'Wanted'
               AND l.status             = 'OPEN'
               AND l.uom IN ('case')
               ".$catid."
               ".$btid."
               AND l.userid             <> ".$factoryCostID."
               AND stl.userid IS NULL
            GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
            ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
        ";
        $this->queries->AddQuery($sql);

        $sql = "CREATE INDEX idx_highbuycase_cidscidbtid ON tmp_high_buy_case_".$random." USING btree (categoryid, subcategoryid, boxtypeid)";
        $this->queries->AddQuery($sql);

        $sql = "
            CREATE TEMPORARY TABLE tmp_low_sell_case_".$random." AS
            SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year,
                   MIN(l.boxprice) AS lowsell_case
              FROM listings             l
              JOIN categories           c   ON  c.categoryid        = l.categoryid
                                            AND c.categorytypeid    = ".LISTING_TYPE_SPORTS."
                                            AND c.active            = 1
              JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                            AND sc.active           = 1
              JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                            AND bt.active           = 1
              JOIN assignedrights       a   ON  a.userid            = l.userid
                                            AND a.userrightid       = 1
              JOIN userinfo             u   ON  u.userid            = l.userid
                                            AND u.userclassid       = 3
                                            AND u.vacationsell      = 0
              LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                            AND stl.userrightid     = ".USERRIGHT_STALE."
             WHERE l.type               = 'For Sale'
               AND l.status             = 'OPEN'
               AND l.uom IN ('case')
               ".$catid."
               ".$btid."
               AND l.userid             <> ".$factoryCostID."
               AND stl.userid IS NULL
            GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
            ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
        ";
        $this->queries->AddQuery($sql);

        $sql = "CREATE INDEX idx_lowsellcase_cidscidbtid ON tmp_low_sell_case_".$random." USING btree (categoryid, subcategoryid, boxtypeid)";
        $this->queries->AddQuery($sql);

        $sql = "
            CREATE TEMPORARY TABLE tmp_buy_cnt_".$random." AS
            SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year,
                   count(1) as buycnt
              FROM listings             l
              JOIN categories           c   ON  c.categoryid        = l.categoryid
                                            AND c.categorytypeid    = ".LISTING_TYPE_SPORTS."
                                            AND c.active            = 1
              JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                            AND sc.active           = 1
              JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                            AND bt.active           = 1
              JOIN assignedrights       a   ON  a.userid            = l.userid
                                            AND a.userrightid       = 1
              JOIN userinfo             u   ON  u.userid            = l.userid
                                            AND u.userclassid       = 3
                                            AND u.vacationbuy       = 0
              LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                            AND stl.userrightid     = ".USERRIGHT_STALE."
             WHERE l.type               = 'Wanted'
               AND l.status             = 'OPEN'
               AND l.uom IN ('box', 'case')
               ".$catid."
               ".$btid."
               ".$hrssince."
               AND l.userid             <> ".$factoryCostID."
               AND stl.userid IS NULL
            GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
            ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
        ";
        $this->queries->AddQuery($sql);

        $sql = "CREATE INDEX idx_buycnt_cidscidbtid ON tmp_buy_cnt_".$random." USING btree (categoryid, subcategoryid, boxtypeid)";
        $this->queries->AddQuery($sql);

        $sql = "
            CREATE TEMPORARY TABLE tmp_sell_cnt_".$random." AS
            SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year,
                   count(1) as sellcnt
              FROM listings             l
              JOIN categories           c   ON  c.categoryid        = l.categoryid
                                            AND c.active            = 1
                                            AND c.categorytypeid    = ".LISTING_TYPE_SPORTS."
              JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                            AND sc.active           = 1
              JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                            AND bt.active           = 1
              JOIN assignedrights       a   ON  a.userid            = l.userid
                                            AND a.userrightid       = 1
              JOIN userinfo             u   ON  u.userid            = l.userid
                                            AND u.userclassid       = 3
                                            AND u.vacationsell      = 0
              LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                            AND stl.userrightid     = ".USERRIGHT_STALE."
             WHERE l.type               = 'For Sale'
               AND l.status             = 'OPEN'
               AND l.uom IN ('box', 'case')
               ".$catid."
               ".$btid."
               ".$hrssince."
               AND l.userid             <> ".$factoryCostID."
               AND stl.userid IS NULL
            GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
            ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
        ";
        $this->queries->AddQuery($sql);

        $sql = "CREATE INDEX idx_sellcnt_cidscidbtid ON tmp_sell_cnt_".$random." USING btree (categoryid, subcategoryid, boxtypeid)";
        $this->queries->AddQuery($sql);

        $sql = "
            CREATE TEMPORARY TABLE tmp_factory_".$random." AS
            SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year, l.boxprice
              FROM listings             l
              JOIN categories           c   ON  c.categoryid        = l.categoryid
                                            AND c.categorytypeid    = ".LISTING_TYPE_SPORTS."
                                            AND c.active            = 1
              JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                            AND sc.active           = 1
              JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                            AND bt.active           = 1
             WHERE l.userid     = ".$factoryCostID."
               AND l.status     = 'OPEN'
               AND l.type       = 'For Sale'
               AND l.uom        = 'box'
               ".$catid."
               ".$btid."
            ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
        ";
        $this->queries->AddQuery($sql);

        $sql = "CREATE INDEX idx_factory_cidscidbtid ON tmp_factory_".$random." USING btree (categoryid, subcategoryid, boxtypeid)";
        $this->queries->AddQuery($sql);

        $typeWhere = ($this->user->canSell()) ? "" : " AND l.type='For Sale'";

        $sql = "
            CREATE TEMPORARY TABLE tmp_upcs_".$random." AS
            SELECT p.categoryid, p.subcategoryid, p.boxtypeid, p.year, pu.upcs, p.variation
              FROM products             p
              JOIN (
                    SELECT u.productid, array_to_string(array_agg(upc), ',') as upcs
                      FROM product_upc  u
                      JOIN products     p   ON  p.productid     = u.productid
                                            AND p.active        = 1
                    GROUP BY u.productid
                   )                    pu  ON  pu.productid        = p.productid
              JOIN categories           c   ON  c.categoryid        = p.categoryid
                                            AND c.categorytypeid    = ".LISTING_TYPE_SPORTS."
                                            AND c.active            = 1
              JOIN subcategories        sc  ON  sc.subcategoryid    = p.subcategoryid
                                            AND sc.active           = 1
              JOIN boxtypes             bt  ON  bt.boxtypeid        = p.boxtypeid
                                            AND bt.active           = 1
             WHERE p.active = 1
            ORDER BY p.categoryid, p.subcategoryid, p.boxtypeid, p.year
        ";
        $this->queries->AddQuery($sql);

        $sql = "CREATE INDEX idx_upc_cidscidbtid ON tmp_upcs_".$random." USING btree (categoryid, subcategoryid, boxtypeid)";
        $this->queries->AddQuery($sql);


//      foreach($page->queries->sqls as $sql) {
//          echo "<pre>".$sql.";</pre>";
//      }

        $this->queries->ProcessQueries();

        $sql = "
            SELECT DISTINCT l.categoryid, c.categorydescription, c.categorytypeid,
                            l.subcategoryid, sc.subcategoryname, sc.secondary,
                            l.boxtypeid, bt.boxtypename,
                            l.year, m.modifydate,
                            hbb.highbuy_box, lsb.lowsell_box,
                            hbc.highbuy_case, lsc.lowsell_case,
                            isnull(bcnt.buycnt, 0) as buycnt, isnull(scnt.sellcnt, 0) as sellcnt,
                            (isnull(bcnt.buycnt, 0) + isnull(scnt.sellcnt, 0)) as totalcnt,
                            f.boxprice as factory_cost,
                            case when pg.selltrend <> 'N' then pg.selltrend
                                 else pg.buytrend end as trend,
                            upc.upcs,
                            upc.variation
              FROM listings                             l
              JOIN categories                           c   ON  c.categoryid            = l.categoryid
                                                            AND c.categorytypeid        = ".LISTING_TYPE_SPORTS."
                                                            AND c.active                = 1
              JOIN subcategories                        sc  ON  sc.subcategoryid        = l.subcategoryid
                                                            AND sc.active               = 1
                                                            AND sc.secondary            = 0
              JOIN boxtypes                             bt  ON  bt.boxtypeid            = l.boxtypeid
                                                            AND bt.active               = 1
              JOIN (
                    SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.year, max(l.modifydate) as modifydate
                      FROM listings                             l
                      JOIN categories                           c   ON  c.categoryid            = l.categoryid
                                                                    AND c.categorytypeid        = ".LISTING_TYPE_SPORTS."
                                                                    AND c.active                = 1
                      JOIN subcategories                        sc  ON  sc.subcategoryid        = l.subcategoryid
                                                                    AND sc.active               = 1
                      JOIN boxtypes                             bt  ON  bt.boxtypeid            = l.boxtypeid
                                                                    AND bt.active               = 1
                     WHERE l.status = 'OPEN'
                    GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
                    )                                   m   ON  m.categoryid            = c.categoryid
                                                            AND m.subcategoryid         = sc.subcategoryid
                                                            AND m.boxtypeid             = bt.boxtypeid
                                                            AND m.year                  = l.year
              LEFT JOIN price_guide                     pg  ON  pg.categoryid           = c.categoryid
                                                            AND pg.subcategoryid        = sc.subcategoryid
                                                            AND pg.boxtypeid            = bt.boxtypeid
                                                            AND pg.year                 = l.year
                                                            AND pg.pgdate               = ".$pgdate."
              LEFT JOIN tmp_high_buy_box_".$random."    hbb ON  hbb.categoryid          = c.categoryid
                                                            AND hbb.subcategoryid       = sc.subcategoryid
                                                            AND hbb.boxtypeid           = bt.boxtypeid
                                                            AND hbb.year                = l.year
              LEFT JOIN tmp_low_sell_box_".$random."    lsb ON  lsb.categoryid          = c.categoryid
                                                            AND lsb.subcategoryid       = sc.subcategoryid
                                                            AND lsb.boxtypeid           = bt.boxtypeid
                                                            AND lsb.year                = l.year
              LEFT JOIN tmp_high_buy_case_".$random."   hbc ON  hbc.categoryid          = c.categoryid
                                                            AND hbc.subcategoryid       = sc.subcategoryid
                                                            AND hbc.boxtypeid           = bt.boxtypeid
                                                            AND hbc.year                = l.year
              LEFT JOIN tmp_low_sell_case_".$random."   lsc ON  lsc.categoryid          = c.categoryid
                                                            AND lsc.subcategoryid       = sc.subcategoryid
                                                            AND lsc.boxtypeid           = bt.boxtypeid
                                                            AND lsc.year                = l.year
              LEFT JOIN tmp_buy_cnt_".$random."        bcnt ON  bcnt.categoryid         = c.categoryid
                                                            AND bcnt.subcategoryid      = sc.subcategoryid
                                                            AND bcnt.boxtypeid          = bt.boxtypeid
                                                            AND bcnt.year               = l.year
              LEFT JOIN tmp_sell_cnt_".$random."       scnt ON  scnt.categoryid         = c.categoryid
                                                            AND scnt.subcategoryid      = sc.subcategoryid
                                                            AND scnt.boxtypeid          = bt.boxtypeid
                                                            AND scnt.year               = l.year
              LEFT JOIN tmp_factory_".$random."         f   ON  f.categoryid            = c.categoryid
                                                            AND f.subcategoryid         = sc.subcategoryid
                                                            AND f.boxtypeid             = bt.boxtypeid
                                                            AND f.year                  = l.year
              LEFT JOIN tmp_upcs_".$random."            upc ON  upc.categoryid          = c.categoryid
                                                            AND upc.subcategoryid       = sc.subcategoryid
                                                            AND upc.boxtypeid           = bt.boxtypeid
                                                            AND upc.year                = l.year
             WHERE l.status = 'OPEN'
               ".$catid."
               ".$btid."
               ".$keyword."
               ".$hrssince."
            ORDER BY sc.secondary, ".$sortby."
            LIMIT ".$this->matches."
        ";

//      echo "<pre>".$sql."</pre>";
        $rs = $this->db->sql_query_params($sql);

        $sql = "DROP TABLE IF EXISTS tmp_high_buy_box_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_low_sell_box_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_high_buy_case_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_low_sell_case_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_buy_cnt_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_sell_cnt_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_factory_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_upcs_".$random;
        $this->queries->AddQuery($sql);
        $this->queries->ProcessQueries();

        return $rs;
    }

    private function getGamingData() {

        $factoryCostID = $this->utility->getDealerId(FACTORYCOSTNAME);
        $pgdate = $this->db->get_field_query("SELECT max(pgdate) FROM price_guide");
        $catid  = (empty($this->categoryids)) ? "" : "AND c.categoryid IN (".$this->categoryids.")";
        $btid   = (empty($this->boxtypeid))   ? "" : "AND bt.boxtypeid = ".$this->boxtypeid;

        $hrssince = "";
        if (empty($this->bypass)) {
            $hourssince = $this->hourssince;
            if (empty($this->hourssince)) {
                $hourssince = DEFAULTHRS;
            } elseif ($this->hourssince > MAXHRSSINCE) {
                $hourssince = MAXHRSSINCE;
            }
            $hoursago   = $hourssince * 60*60;
            $hrssince   = "AND l.modifydate > (nowtoint() - ".$hoursago.")";
        } else {
            $hrssince   = "AND l.modifydate > (nowtoint() - ".MAXBYPASSHRSSINCE.")";
        }

        $keyword    = "";
        if (!empty($this->keywordsearch)) {
            $searchstring = strtolower(trim($this->keywordsearch));
            $searcharray = explode(" ",$searchstring);
            foreach($searcharray as $sa) {
                $keyword .= " AND (lower(l.year) LIKE '%".$sa."%'\n";
                $keyword .=  "     OR lower(c.categorydescription) LIKE '%".$sa."%'\n";
                $keyword .=  "     OR lower(sc.subcategoryname) LIKE '%".$sa."%'\n";
                $keyword .=  "     OR lower(bt.boxtypename) LIKE '%".$sa."%'";
                $keyword .=  "     OR lower(upc.upcs) LIKE '%".$sa."%'\n";
                $keyword .=  "     OR lower(upc.variation) LIKE '%".$sa."%')\n";
            }
        }

        SWITCH ($this->sortby) {
            CASE "d":       $sortby = "m.modifydate DESC";
                            break;
            CASE "ycscbt":  $sortby = "l.year DESC, c.categorydescription, sc.subcategoryname, bt.boxtypename";
                            break;
            CASE "t":       $sortby = "totalcnt DESC";
                            break;
            DEFAULT:        $sortby = "totalcnt DESC";
        }


        $random = rand();
        $sql = "DROP TABLE IF EXISTS tmp_high_buy_box_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_low_sell_box_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_high_buy_case_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_low_sell_case_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_buy_cnt_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_sell_cnt_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_factory_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_upcs_".$random;
        $this->queries->AddQuery($sql);

        $sql = "
            CREATE TEMPORARY TABLE tmp_high_buy_box_".$random." AS
            SELECT l.categoryid, l.subcategoryid, l.boxtypeid,
                   MAX(l.boxprice) AS highbuy_box
              FROM listings             l
              JOIN categories           c   ON  c.categoryid        = l.categoryid
                                            AND c.categorytypeid    = ".LISTING_TYPE_GAMING."
                                            AND c.active            = 1
              JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                            AND sc.active           = 1
              JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                            AND bt.active           = 1
              JOIN assignedrights       a   ON  a.userid            = l.userid
                                            AND a.userrightid       = 1
              JOIN userinfo             u   ON  u.userid            = l.userid
                                            AND u.userclassid       = 3
                                            AND u.vacationbuy       = 0
              LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                            AND stl.userrightid     = ".USERRIGHT_STALE."
             WHERE l.type               = 'Wanted'
               AND l.status             = 'OPEN'
               AND l.uom IN ('box')
               ".$catid."
               ".$btid."
               AND l.userid             <> ".$factoryCostID."
               AND stl.userid IS NULL
            GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid
            ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid
        ";
        $this->queries->AddQuery($sql);

        $sql = "CREATE INDEX idx_highbuybox_cidscidbtid ON tmp_high_buy_box_".$random." USING btree (categoryid, subcategoryid, boxtypeid)";
        $this->queries->AddQuery($sql);

        $sql = "
            CREATE TEMPORARY TABLE tmp_low_sell_box_".$random." AS
            SELECT l.categoryid, l.subcategoryid, l.boxtypeid,
                   MIN(l.boxprice) AS lowsell_box
              FROM listings             l
              JOIN categories           c   ON  c.categoryid        = l.categoryid
                                            AND c.active            = 1
                                            AND c.categorytypeid    = ".LISTING_TYPE_GAMING."
              JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                            AND sc.active           = 1
              JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                            AND bt.active           = 1
              JOIN assignedrights       a   ON  a.userid            = l.userid
                                            AND a.userrightid       = 1
              JOIN userinfo             u   ON  u.userid            = l.userid
                                            AND u.userclassid       = 3
                                            AND u.vacationsell      = 0
              LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                            AND stl.userrightid     = ".USERRIGHT_STALE."
             WHERE l.type               = 'For Sale'
               AND l.status             = 'OPEN'
               AND l.uom IN ('box')
               ".$catid."
               ".$btid."
               AND l.userid             <> ".$factoryCostID."
               AND stl.userid IS NULL
            GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid
            ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid
        ";
        $this->queries->AddQuery($sql);

        $sql = "CREATE INDEX idx_lowsellbox_cidscidbtid ON tmp_low_sell_box_".$random." USING btree (categoryid, subcategoryid, boxtypeid)";
        $this->queries->AddQuery($sql);

        $sql = "
            CREATE TEMPORARY TABLE tmp_high_buy_case_".$random." AS
            SELECT l.categoryid, l.subcategoryid, l.boxtypeid,
                   MAX(l.boxprice) AS highbuy_case
              FROM listings             l
              JOIN categories           c   ON  c.categoryid        = l.categoryid
                                            AND c.categorytypeid    = ".LISTING_TYPE_GAMING."
                                            AND c.active            = 1
              JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                            AND sc.active           = 1
              JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                            AND bt.active           = 1
              JOIN assignedrights       a   ON  a.userid            = l.userid
                                            AND a.userrightid       = 1
              JOIN userinfo             u   ON  u.userid            = l.userid
                                            AND u.userclassid       = 3
                                            AND u.vacationbuy       = 0
              LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                            AND stl.userrightid     = ".USERRIGHT_STALE."
             WHERE l.type               = 'Wanted'
               AND l.status             = 'OPEN'
               AND l.uom IN ('case')
               ".$catid."
               ".$btid."
               AND l.userid             <> ".$factoryCostID."
               AND stl.userid IS NULL
            GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid
            ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid
        ";
        $this->queries->AddQuery($sql);

        $sql = "CREATE INDEX idx_highbuycase_cidscidbtid ON tmp_high_buy_case_".$random." USING btree (categoryid, subcategoryid, boxtypeid)";
        $this->queries->AddQuery($sql);

        $sql = "
            CREATE TEMPORARY TABLE tmp_low_sell_case_".$random." AS
            SELECT l.categoryid, l.subcategoryid, l.boxtypeid,
                   MIN(l.boxprice) AS lowsell_case
              FROM listings             l
              JOIN categories           c   ON  c.categoryid        = l.categoryid
                                            AND c.categorytypeid    = ".LISTING_TYPE_GAMING."
                                            AND c.active            = 1
              JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                            AND sc.active           = 1
              JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                            AND bt.active           = 1
              JOIN assignedrights       a   ON  a.userid            = l.userid
                                            AND a.userrightid       = 1
              JOIN userinfo             u   ON  u.userid            = l.userid
                                            AND u.userclassid       = 3
                                            AND u.vacationsell      = 0
              LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                            AND stl.userrightid     = ".USERRIGHT_STALE."
             WHERE l.type               = 'For Sale'
               AND l.status             = 'OPEN'
               AND l.uom IN ('case')
               ".$catid."
               ".$btid."
               AND l.userid             <> ".$factoryCostID."
               AND stl.userid IS NULL
            GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid
            ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid
        ";
        $this->queries->AddQuery($sql);

        $sql = "CREATE INDEX idx_lowsellcase_cidscidbtid ON tmp_low_sell_case_".$random." USING btree (categoryid, subcategoryid, boxtypeid)";
        $this->queries->AddQuery($sql);

        $sql = "
            CREATE TEMPORARY TABLE tmp_buy_cnt_".$random." AS
            SELECT l.categoryid, l.subcategoryid, l.boxtypeid,
                   count(1) as buycnt
              FROM listings             l
              JOIN categories           c   ON  c.categoryid        = l.categoryid
                                            AND c.categorytypeid    = ".LISTING_TYPE_GAMING."
                                            AND c.active            = 1
              JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                            AND sc.active           = 1
              JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                            AND bt.active           = 1
              JOIN assignedrights       a   ON  a.userid            = l.userid
                                            AND a.userrightid       = 1
              JOIN userinfo             u   ON  u.userid            = l.userid
                                            AND u.userclassid       = 3
                                            AND u.vacationbuy       = 0
              LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                            AND stl.userrightid     = ".USERRIGHT_STALE."
             WHERE l.type               = 'Wanted'
               AND l.status             = 'OPEN'
               AND l.uom IN ('box', 'case')
               ".$catid."
               ".$btid."
               ".$hrssince."
               AND l.userid             <> ".$factoryCostID."
               AND stl.userid IS NULL
            GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
            ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
        ";
        $this->queries->AddQuery($sql);

        $sql = "CREATE INDEX idx_buycnt_cidscidbtid ON tmp_buy_cnt_".$random." USING btree (categoryid, subcategoryid, boxtypeid)";
        $this->queries->AddQuery($sql);

        $sql = "
            CREATE TEMPORARY TABLE tmp_sell_cnt_".$random." AS
            SELECT l.categoryid, l.subcategoryid, l.boxtypeid,
                   count(1) as sellcnt
              FROM listings             l
              JOIN categories           c   ON  c.categoryid        = l.categoryid
                                            AND c.active            = 1
                                            AND c.categorytypeid    = ".LISTING_TYPE_GAMING."
              JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                            AND sc.active           = 1
              JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                            AND bt.active           = 1
              JOIN assignedrights       a   ON  a.userid            = l.userid
                                            AND a.userrightid       = 1
              JOIN userinfo             u   ON  u.userid            = l.userid
                                            AND u.userclassid       = 3
                                            AND u.vacationsell      = 0
              LEFT JOIN assignedrights  stl ON  stl.userid          = l.userid
                                            AND stl.userrightid     = ".USERRIGHT_STALE."
             WHERE l.type               = 'For Sale'
               AND l.status             = 'OPEN'
               AND l.uom IN ('box', 'case')
               ".$catid."
               ".$btid."
               ".$hrssince."
               AND l.userid             <> ".$factoryCostID."
               AND stl.userid IS NULL
            GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
            ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid, l.year
        ";
        $this->queries->AddQuery($sql);

        $sql = "CREATE INDEX idx_sellcnt_cidscidbtid ON tmp_sell_cnt_".$random." USING btree (categoryid, subcategoryid, boxtypeid)";
        $this->queries->AddQuery($sql);

        $sql = "
            CREATE TEMPORARY TABLE tmp_factory_".$random." AS
            SELECT l.categoryid, l.subcategoryid, l.boxtypeid, l.boxprice
              FROM listings             l
              JOIN categories           c   ON  c.categoryid        = l.categoryid
                                            AND c.categorytypeid    = ".LISTING_TYPE_GAMING."
                                            AND c.active            = 1
              JOIN subcategories        sc  ON  sc.subcategoryid    = l.subcategoryid
                                            AND sc.active           = 1
              JOIN boxtypes             bt  ON  bt.boxtypeid        = l.boxtypeid
                                            AND bt.active           = 1
             WHERE l.userid     = ".$factoryCostID."
               AND l.status     = 'OPEN'
               AND l.type       = 'For Sale'
               AND l.uom        = 'box'
               ".$catid."
               ".$btid."
            ORDER BY l.categoryid, l.subcategoryid, l.boxtypeid
        ";
        $this->queries->AddQuery($sql);

        $sql = "CREATE INDEX idx_factory_cidscidbtid ON tmp_factory_".$random." USING btree (categoryid, subcategoryid, boxtypeid)";
        $this->queries->AddQuery($sql);

        $typeWhere = ($this->user->canSell()) ? "" : " AND l.type='For Sale'";

        $sql = "
            CREATE TEMPORARY TABLE tmp_upcs_".$random." AS
            SELECT p.categoryid, p.subcategoryid, p.boxtypeid, p.year, pu.upcs, p.variation
              FROM products             p
              JOIN (
                    SELECT u.productid, array_to_string(array_agg(upc), ',') as upcs
                      FROM product_upc  u
                      JOIN products     p   ON  p.productid     = u.productid
                                            AND p.active        = 1
                    GROUP BY u.productid
                   )                    pu  ON  pu.productid        = p.productid
              JOIN categories           c   ON  c.categoryid        = p.categoryid
                                            AND c.categorytypeid    = ".LISTING_TYPE_GAMING."
                                            AND c.active            = 1
              JOIN subcategories        sc  ON  sc.subcategoryid    = p.subcategoryid
                                            AND sc.active           = 1
              JOIN boxtypes             bt  ON  bt.boxtypeid        = p.boxtypeid
                                            AND bt.active           = 1
             WHERE p.active = 1
            ORDER BY p.categoryid, p.subcategoryid, p.boxtypeid, p.year
        ";
        $this->queries->AddQuery($sql);

        $sql = "CREATE INDEX idx_upc_cidscidbtid ON tmp_upcs_".$random." USING btree (categoryid, subcategoryid, boxtypeid)";
        $this->queries->AddQuery($sql);

    //foreach($page->queries->sqls as $sql) {
    //    echo "<pre>".$sql.";</pre>";
    //}

        $this->queries->ProcessQueries();

        $sql = "
            SELECT DISTINCT l.categoryid, c.categorydescription, c.categorytypeid,
                            l.subcategoryid, sc.subcategoryname, sc.secondary,
                            l.boxtypeid, bt.boxtypename,
                            l.year, m.modifydate,
                            hbb.highbuy_box, lsb.lowsell_box,
                            hbc.highbuy_case, lsc.lowsell_case,
                            isnull(bcnt.buycnt, 0) as buycnt, isnull(scnt.sellcnt, 0) as sellcnt,
                            (isnull(bcnt.buycnt, 0) + isnull(scnt.sellcnt, 0)) as totalcnt,
                            f.boxprice as factory_cost,
                            case when pg.selltrend <> 'N' then pg.selltrend
                                 else pg.buytrend end as trend,
                            upc.upcs,
                            upc.variation
              FROM listings                             l
              JOIN categories                           c   ON  c.categoryid            = l.categoryid
                                                            AND c.categorytypeid        = ".LISTING_TYPE_GAMING."
                                                            AND c.active                = 1
              JOIN subcategories                        sc  ON  sc.subcategoryid        = l.subcategoryid
                                                            AND sc.active               = 1
                                                            AND sc.secondary            = 0
              JOIN boxtypes                             bt  ON  bt.boxtypeid            = l.boxtypeid
                                                            AND bt.active               = 1
              JOIN (
                    SELECT l.categoryid, l.subcategoryid, l.boxtypeid, max(l.modifydate) as modifydate
                      FROM listings                             l
                      JOIN categories                           c   ON  c.categoryid            = l.categoryid
                                                                    AND c.categorytypeid        = ".LISTING_TYPE_GAMING."
                                                                    AND c.active                = 1
                      JOIN subcategories                        sc  ON  sc.subcategoryid        = l.subcategoryid
                                                                    AND sc.active               = 1
                      JOIN boxtypes                             bt  ON  bt.boxtypeid            = l.boxtypeid
                                                                    AND bt.active               = 1
                     WHERE l.status = 'OPEN'
                    GROUP BY l.categoryid, l.subcategoryid, l.boxtypeid
                    )                                   m   ON  m.categoryid            = c.categoryid
                                                            AND m.subcategoryid         = sc.subcategoryid
                                                            AND m.boxtypeid             = bt.boxtypeid
              LEFT JOIN price_guide                     pg  ON  pg.categoryid           = c.categoryid
                                                            AND pg.subcategoryid        = sc.subcategoryid
                                                            AND pg.boxtypeid            = bt.boxtypeid
                                                            AND pg.pgdate               = ".$pgdate."
              LEFT JOIN tmp_high_buy_box_".$random."    hbb ON  hbb.categoryid          = c.categoryid
                                                            AND hbb.subcategoryid       = sc.subcategoryid
                                                            AND hbb.boxtypeid           = bt.boxtypeid
              LEFT JOIN tmp_low_sell_box_".$random."    lsb ON  lsb.categoryid          = c.categoryid
                                                            AND lsb.subcategoryid       = sc.subcategoryid
                                                            AND lsb.boxtypeid           = bt.boxtypeid
              LEFT JOIN tmp_high_buy_case_".$random."   hbc ON  hbc.categoryid          = c.categoryid
                                                            AND hbc.subcategoryid       = sc.subcategoryid
                                                            AND hbc.boxtypeid           = bt.boxtypeid
              LEFT JOIN tmp_low_sell_case_".$random."   lsc ON  lsc.categoryid          = c.categoryid
                                                            AND lsc.subcategoryid       = sc.subcategoryid
                                                            AND lsc.boxtypeid           = bt.boxtypeid
              LEFT JOIN tmp_buy_cnt_".$random."        bcnt ON  bcnt.categoryid         = c.categoryid
                                                            AND bcnt.subcategoryid      = sc.subcategoryid
                                                            AND bcnt.boxtypeid          = bt.boxtypeid
              LEFT JOIN tmp_sell_cnt_".$random."       scnt ON  scnt.categoryid         = c.categoryid
                                                            AND scnt.subcategoryid      = sc.subcategoryid
                                                            AND scnt.boxtypeid          = bt.boxtypeid
              LEFT JOIN tmp_factory_".$random."         f   ON  f.categoryid            = c.categoryid
                                                            AND f.subcategoryid         = sc.subcategoryid
                                                            AND f.boxtypeid             = bt.boxtypeid
              LEFT JOIN tmp_upcs_".$random."            upc ON  upc.categoryid          = c.categoryid
                                                            AND upc.subcategoryid       = sc.subcategoryid
                                                            AND upc.boxtypeid           = bt.boxtypeid
             WHERE l.status = 'OPEN'
               ".$catid."
               ".$btid."
               ".$keyword."
               ".$hrssince."
            ORDER BY sc.secondary,".$sortby."
            LIMIT ".$this->matches."
        ";

    //  echo "<pre>".$sql."</pre>";
        $rs = $this->db->sql_query_params($sql);

        $sql = "DROP TABLE IF EXISTS tmp_high_buy_box_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_low_sell_box_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_high_buy_case_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_low_sell_case_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_buy_cnt_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_sell_cnt_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_factory_".$random;
        $this->queries->AddQuery($sql);
        $sql = "DROP TABLE IF EXISTS tmp_upcs_".$random;
        $this->queries->AddQuery($sql);
        $this->queries->ProcessQueries();

        return $rs;
    }

    private function trendIndicator($trendDirection) {
        $indicator = "";

        switch ($trendDirection) {
            case 'U':
                $indicator =  "<i class='fa-solid fa-arrow-up' style='color: #00ff00;'></i>";
                break;
            case 'D':
                $indicator =  "<i class='fa-solid fa-arrow-down' style='color: #ff0000;'></i>";
                break;
            default:
                $indicator =  "<i class='fa-solid fa-dash fa-sm' style='color: #0000ff;'></i>";
                break;
        }

        return $indicator;
    }
}
?>
