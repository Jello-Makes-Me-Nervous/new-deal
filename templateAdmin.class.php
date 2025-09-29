<?php

require_once('templateStaff.class.php');

class templateAdmin extends templateStaff{


    public function __construct($loginRequired = true, $showmsgs = true, $bypassMustReplyCheck = false) {
        parent::__construct($loginRequired, $showmsgs, $bypassMustReplyCheck);

        if ($this->user->hasUserRight('ADMIN') == false) {
            header('location:home.php?pgemsg=The%20requested%20page%20requires%20ADMIN%20access');
            exit();
        }
    }

    public function displayLeftWidget() {
        echo "           <div id='primary-widget' class='no-left-sidebar shadow font-roboto-condensed'><!--PRIMARY WIDGET NARROW LEFT---->\n";
        echo "             LEFT Home WIDGET\n";
        echo "           </div><!----primary-widget narrow left-->\n";
    }


    public function displayRightWidget() {

    }

    public function footer() {
        echo "          </div> <!-- content -->\n";

        $this->displayRightWidget();

        echo "        </div> <!----/#container-inside, #container -->\n";
        echo "      </div> <!----container -->\n";
        echo "      <footer>\n";
        echo "        <div>\n";
        echo "          <aside id='footer-menu'>\n";
        echo "            <ul class='menu-items xs-font-size'>\n";
        echo "              <li><a href='#'>Mail</a></li>\n";
        echo "              <li><a href='#'>My B2B</a></li>\n";
        echo "              <li><a href='#'>Message Area</a></li>\n";
        echo "            </ul>\n";
        echo "          </aside>\n";
        echo "        </div> <!--divFoot-->\n";
        echo "      </footer>\n";
        echo "    </div> <!-- page-wrap -->\n";

        if ($this->jsinit) {
            echo "    <SCRIPT language='JavaScript'>\n";
            foreach($this->jsinit as $j) {
                echo "      ".$j."\n";
            }
            echo "    </SCRIPT>\n";
        }
        echo "  </body>\n";
        echo "</html>\n";

    }


    public function getAdvert() {
        global $page;

        $sql = "
            SELECT advertclassid, originalpath, classpath, advertname
              FROM advertmanage
             WHERE active = 1
        ";
        $data = $page->db->sql_query_params($sql);

        return $data;
    }

}
?>