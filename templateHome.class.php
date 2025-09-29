<?php

require_once('template.class.php');

class templateHome extends template{


    public function __construct($loginRequired = true, $showmsgs = true, $bypassMustReplyCheck = false) {
        $this->allowBlockedIPs = true;
        parent::__construct($loginRequired, $showmsgs, $bypassMustReplyCheck);

        $this->display_BottomWidget = false;
    }

    public function displayLeftWidget() {

        $ads = $this->getLeftHandAds();
        if (count($ads) > 0) {
            echo "           <div id='primary-widget' class='narrow-left-sidebar shadow font-roboto-condensed'><!-- PRIMARY WIDGET NARROW LEFT -->\n";
            echo "            <header>\n";
            echo "              <h3>Featured Hobby Distributors</h3>\n";
            echo "            </header>\n";
            foreach($ads as $a) {
                if (empty($a["url"])) {
                    $img = "<img class='align-center' src='".$this->utility->getPrefixAdvertImageURL($a["imagepath"])."'>";
                } else {
                    $img = "<a href='".$a["url"]."' target='_new'>";
                    $img .= "<img class='align-center' src='".$this->utility->getPrefixAdvertImageURL($a["imagepath"])."'>";
                    $img .= "</a>";
                }
                echo "            <aside>".$img."</aside>\n";
            }
            echo "           </div><!-- primary-widget narrow left -->\n";
        }
    }

    public function displayRightWidget() {

        $ads = $this->getRightHandAds();
        if (count($ads) > 0) {
            echo "          <div id='secondary-widget' class='standard-left-sidebar shadow font-roboto-condensed'> <!-- SECONDARY WIDGET right side -->\n";
            echo "            <header>\n";
            echo "              <h3>Corporate Sponsors</h3>\n";
            echo "            </header>\n";
            foreach($ads as $a) {
                if (empty($a["url"])) {
                    $img = "<img class='align-center' src='".$this->utility->getPrefixAdvertImageURL($a["imagepath"])."'>";
                } else {
                    $img = "<a href='".$a["url"]."' target='_new'>";
                    $img .= "<img class='align-center' src='".$this->utility->getPrefixAdvertImageURL($a["imagepath"])."'>";
                    $img .= "</a>";
                }

                echo "            <aside>".$img."</aside>\n";
            }
        echo "          </div> <!-- secondary-widget right side -->\n";
        }

    }

    private function getLeftHandAds() {
        $sql = "
            SELECT classpath || originalpath as imagepath, url
              FROM advertmanage
             WHERE active = 1
               AND classpath = 'HomeLeft/'
            ORDER BY random()
        ";

        if ($rs = $this->db->sql_query($sql)) {
        } else {
            $rs = array();
        }

        return $rs;
    }

    private function getRightHandAds() {
        $sql = "
            SELECT classpath || originalpath as imagepath, url
              FROM advertmanage
             WHERE active = 1
               AND classpath = 'HomeRight/'
            ORDER BY random()
        ";

        if ($rs = $this->db->sql_query($sql)) {
        } else {
            $rs = array();
        }

        return $rs;
    }


}


?>