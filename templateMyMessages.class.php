<?php

require_once('template.class.php');

class templateMyMessages extends template{


    public function __construct($loginRequired = true, $showmsgs = true, $bypassMustReplyCheck = false) {
        parent::__construct($loginRequired, $showmsgs, $bypassMustReplyCheck);

    }

    public function displayRightWidget() {
        echo "          <div id='secondary-widget' class='standard-left-sidebar shadow font-roboto-condensed'> <!-- SECONDARY WIDGET right side -->\n";
        $this->displayAds();
        echo "            <div>&nbsp;</div>\n";
        $this->displayBlasts();
        echo "          </div> <!-- secondary-widget right side -->\n";
    }

    private function getRightHandAds() {
        $sql = "
            SELECT classpath || originalpath as imagepath, url
              FROM advertmanage
             WHERE active = 1
               AND classpath = 'MyMessages/'
            ORDER BY random()
        ";

        if ($rs = $this->db->sql_query($sql)) {
        } else {
            $rs = array();
        }

        return $rs;
    }

    private function displayAds() {
        $ads = $this->getRightHandAds();
        if (count($ads) > 0) {
            $a = reset($ads);
            if (empty($a["url"])) {
                $img = "<img class='align-center' src='".$this->utility->getPrefixAdvertImageURL($a["imagepath"])."'>";
            } else {
                $img = "<a href='".$a["url"]."' target='_new'>";
                $img .= "<img class='align-center' src='".$this->utility->getPrefixAdvertImageURL($a["imagepath"])."'>";
                $img .= "</a>";
            }
            echo "            <aside>".$img."</aside>\n";
        }
    }

    private function getBlastAdList() {
        global $page;

        $returnData = null;

        $sql = "
            SELECT l.userid, u.username, ui.listinglogo, max(l.createdate) as lastupdate, count(l.listingid) as blastcount
              FROM listings         l
              JOIN categories       c   ON  c.categoryid        = l.categoryid
                                        AND c.categorytypeid    = ".LISTING_TYPE_BLAST."
              JOIN users            u   ON  u.userid            = l.userid
              JOIN userinfo         ui  ON  ui.userid           = l.userid
              JOIN assignedrights   ar  ON  ar.userid           = l.userid
                                        AND ar.userrightid in (".USERRIGHT_LIMITED_BLAST.",".USERRIGHT_UNLIMITED_BLAST.")
             WHERE l.status = 'OPEN'
               AND inttodatetime(l.modifydate)::TIMESTAMP + interval '7' day > now()
            GROUP BY l.userid, u.username, ui.listinglogo
            ORDER BY 4 DESC
                ";

        //echo "<pre>".$sql."</pre>";
        $rs = $page->db->sql_query($sql);

        return $rs;
    }

    private function displayBlasts() {
        global $page;

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

    }

}


?>