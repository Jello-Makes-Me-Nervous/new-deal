<?php
/*
 * PHP Pagination Class
 *
 * @author David Carr - dave@daveismyname.com - http://www.daveismyname.com
 * @version 1.0
 * @date October 20, 2012
 */
class Paginator {
    private $_perPage;
    private $_instance;
    private $_page;
    private $_limit;
    private $_totalRows = 0;
    private $_querystring = null;

    /**
     *  __construct
     *
     *  pass values when class is istantiated
     *
     * @param numeric  $_perPage  sets the number of iteems per page
     * @param numeric  $_instance sets the instance for the GET parameter
      */
    public function __construct($perPage, $instance){
        $this->_instance = $instance;
        $this->_perPage = $perPage;
        $this->set_instance();
    }

    /**
     * get_start
     * creates the starting point for limiting the dataset
     */
    public function get_start(){
        return ($this->_page * $this->_perPage) - $this->_perPage;
    }

    /**
     * set_instance
     * sets the instance parameter, if numeric value is 0 then set to 1
     */
    private function set_instance(){
        if (isset($_GET[$this->_instance])) {
            $this->_page = (int) $_GET[$this->_instance];
        } else {
            $this->_page = (int) (!isset($_POST[$this->_instance]) ? 1 : $_POST[$this->_instance]);
        }
        $this->_page = ($this->_page == 0 ? 1 : $this->_page);
    }

    /**
     * set_total
     * collect a numberic value and assigns it to the totalRows
     */
    public function set_total($_totalRows){
        $this->_totalRows = $_totalRows;
    }

    /**
     * get_limit
     * returns the limit for the data source, calling the get_start method and passing in the number of items perp page
     */
    public function get_limit(){
            return "LIMIT ".$this->get_start().",$this->_perPage";
    }

    /**
     * page_links
     * create the html links for navigating through the dataset
     */
    public function page_links($path='?',$ext=null) {
        $adjacents = "2";
        $prev = $this->_page - 1;
        $next = $this->_page + 1;
        $lastpage = ceil($this->_totalRows/$this->_perPage);
        $lpm1 = $lastpage - 1;
        $pagination = "";
        if($lastpage > 1) {
            $pagination .= "<ul>";
            if ($this->_page > 1) {
                $pagination.= "<li class='previous'><a href='".$path."$this->_instance=$prev"."$ext'>Back</a></li>";
            }
            if ($lastpage < 7 + ($adjacents * 2)) {
                for ($counter = 1; $counter <= $lastpage; $counter++) {
                    if ($counter == $this->_page) {
                        $pagination.= "<li class='selected'><a href='".$path."$this->_instance=$counter"."$ext'>$counter</a></li>";
                    } else {
                        $pagination.= "<li><a href='".$path."$this->_instance=$counter"."$ext'>$counter</a></li>";
                    }
                }
            } elseif($lastpage > 5 + ($adjacents * 2)) {
                if($this->_page < 1 + ($adjacents * 2)) {
                    for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++) {
                        if ($counter == $this->_page) {
                            $pagination.= "<li class='selected'><a href='".$path."$this->_instance=$counter"."$ext'>$counter</a></li>";
                        } else {
                            $pagination.= "<li><a href='".$path."$this->_instance=$counter"."$ext'>$counter</a></li>";
                        }
                    }
                    $pagination.= "...";
                    $pagination.= "<li><a href='".$path."$this->_instance=$lpm1"."$ext'>$lpm1</a></li>";
                    $pagination.= "<li><a href='".$path."$this->_instance=$lastpage"."$ext'>$lastpage</a></li>";
                } elseif($lastpage - ($adjacents * 2) > $this->_page && $this->_page > ($adjacents * 2)) {
                    $pagination.= "<li><a href='".$path."$this->_instance=1"."$ext'>1</a></li>";
                    $pagination.= "<li><a href='".$path."$this->_instance=2"."$ext'>2</a></li>";
                    $pagination.= "...";
                    for ($counter = $this->_page - $adjacents; $counter <= $this->_page + $adjacents; $counter++) {
                        if ($counter == $this->_page) {
                            $pagination.= "<li class='selected'><a href='".$path."$this->_instance=$counter"."$ext'>$counter</a></li>";
                        } else {
                            $pagination.= "<li><a href='".$path."$this->_instance=$counter"."$ext'>$counter</a></li>";
                        }
                    }
                    $pagination.= "..";
                    $pagination.= "<li><a href='".$path."$this->_instance=$lpm1"."$ext'>$lpm1</a></li>";
                    $pagination.= "<li><a href='".$path."$this->_instance=$lastpage"."$ext'>$lastpage</a></li>";
                } else {
                    $pagination.= "<li><a href='".$path."$this->_instance=1"."$ext'>1</a></li>";
                    $pagination.= "<li><a href='".$path."$this->_instance=2"."$ext'>2</a></li>";
                    $pagination.= "..";
                    for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++) {
                        if ($counter == $this->_page) {
                            $pagination.= "<li class='selected'><a href='".$path."$this->_instance=$counter"."$ext'>$counter</a></li>";
                        } else {
                            $pagination.= "<li><a href='".$path."$this->_instance=$counter"."$ext'>$counter</a></li>";
                        }
                    }
                }
            }
            if ($this->_page < $counter - 1) {
                $pagination.= "<li><a href='".$path."$this->_instance=$next"."$ext'>Next</a></li>";
            }
            $pagination.= "</ul>\n";
        }
        return $pagination;
    }

    /**
     * page_links
     * create the html links for navigating through the dataset
     */
    public function post_page_links($formid) {
        $adjacents = "2";
        $prev = $this->_page - 1;
        $next = $this->_page + 1;
        $lastpage = ceil($this->_totalRows/$this->_perPage);
        $lpm1 = $lastpage - 1;
        $pagination = "";
        $pagination .= "<input type='hidden' name='page' id='page' value='".$this->_page."' />\n";
        $pagination .= "<input type='hidden' name='perpage' id='perpage' value='".$this->_perPage."' />\n";
        $pagination .= "<input type='hidden' name='prevpage' id='prevpage' value='".$this->_page."' />\n";
        if($lastpage > 1) {
            $pagination .= "<ul>\n";
            if ($this->_page > 1) {
                $pagination.= "  <li class='previous'><a href='javascript: void(0);' onclick=\"javascript: document.getElementById('page').value=".$prev."; document.".$formid.".submit();\">Back</a></li>\n";
            }
            if ($lastpage < 7 + ($adjacents * 2)) {
                for ($counter = 1; $counter <= $lastpage; $counter++) {
                    if ($counter == $this->_page) {
                        $pagination.= "  <li class='selected'><a href='javascript: void(0);' onclick=\"javascript: document.getElementById('page').value=".$counter."; document.".$formid.".submit();\">".$counter."</a></li>\n";
                    } else {
                        $pagination.= "  <li><a href='javascript: void(0);' onclick=\"javascript: document.getElementById('page').value=".$counter."; document.".$formid.".submit();\">".$counter."</a></li>\n";
                    }
                }
            } elseif($lastpage > 5 + ($adjacents * 2)) {
                if($this->_page < 1 + ($adjacents * 2)) {
                    for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++) {
                        if ($counter == $this->_page) {
                            $pagination.= "  <li class='selected'><a href='javascript: void(0);' onclick=\"javascript: document.getElementById('page').value=".$counter."; document.".$formid.".submit();\">".$counter."</a></li>\n";
                        } else {
                            $pagination.= "  <li><a href='javascript: void(0);' onclick=\"javascript: document.getElementById('page').value=".$counter."; document.".$formid.".submit();\">".$counter."</a></li>\n";
                        }
                    }
                    $pagination.= "  ... \n";
                    $pagination.= "  <li><a href='javascript: void(0);' onclick=\"javascript: document.getElementById('page').value=".$lpm1."; document.".$formid.".submit();\">".$lpm1."</a></li>\n";
                    $pagination.= "  <li><a href='javascript: void(0);' onclick=\"javascript: document.getElementById('page').value=".$lastpage."; document.".$formid.".submit();\">".$lastpage."</a></li>\n";
                } elseif($lastpage - ($adjacents * 2) > $this->_page && $this->_page > ($adjacents * 2)) {
                    $pagination.= "  <li><a href='javascript: void(0);' onclick=\"javascript: document.getElementById('page').value=1; document.".$formid.".submit();\">1</a></li>\n";
                    $pagination.= "  <li><a href='javascript: void(0);' onclick=\"javascript: document.getElementById('page').value=2; document.".$formid.".submit();\">2</a></li>\n";
                    $pagination.= "  ... \n";
                    for ($counter = $this->_page - $adjacents; $counter <= $this->_page + $adjacents; $counter++) {
                        if ($counter == $this->_page) {
                            $pagination.= "  <li class='selected'><a href='javascript: void(0);' onclick=\"javascript: document.getElementById('page').value=".$counter."; document.".$formid.".submit();\">".$counter."</a></li>\n";
                        } else {
                            $pagination.= "  <li><a href='javascript: void(0);' onclick=\"javascript: document.getElementById('page').value=".$counter."; document.".$formid.".submit();\">".$counter."</a></li>\n";
                        }
                    }
                    $pagination.= "  ...  \n";
                    $pagination.= "  <li><a href='javascript: void(0);' onclick=\"javascript: document.getElementById('page').value=".$lpm1."; document.".$formid.".submit();\">".$lpm1."</a></li>\n";
                    $pagination.= "  <li><a href='javascript: void(0);' onclick=\"javascript: document.getElementById('page').value=".$lastpage."; document.".$formid.".submit();\">".$lastpage."</a></li>\n";
                } else {
                    $pagination.= "  <li><a href='javascript: void(0);' onclick=\"javascript: document.getElementById('page').value=1; document.".$formid.".submit();\">1</a></li>\n";
                    $pagination.= "  <li><a href='javascript: void(0);' onclick=\"javascript: document.getElementById('page').value=2; document.".$formid.".submit();\">2</a></li>\n";
                    $pagination.= " ... ";
                    for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++) {
                        if ($counter == $this->_page) {
                            $pagination.= "  <li class='selected'><a href='javascript: void(0);' onclick=\"javascript: document.getElementById('page').value=".$counter."; document.".$formid.".submit();\">".$counter."</a></li>\n";
                        } else {
                            $pagination.= "  <li><a href='javascript: void(0);' onclick=\"javascript: document.getElementById('page').value=".$counter."; document.".$formid.".submit();\">".$counter."</a></li>\n";
                        }
                    }
                }
            }
            if ($this->_page < $counter - 1) {
//                $pagination.= "  <li class='next'><a href='javascript: void(0);' onclick=\"javascript: document.getElementById('page').value=".$next."; document.".$formid.".submit();\">Next</a></li>\n";
                $pagination.= "  <li><a href='javascript: void(0);' onclick=\"javascript: document.getElementById('page').value=".$next."; document.".$formid.".submit();\">Next</a></li>\n";
            }
            $pagination.= "</ul>\n";
        }
        return $pagination;
    }

}
