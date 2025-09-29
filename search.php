<?php
require_once('templateMarket.class.php');

$page = new templateMarket(NOLOGIN, SHOWMSG);

$keyword = optional_param('keywordsearch', NULL, PARAM_TEXT);
$verbose = optional_param('verbose', 0, PARAM_INT);

echo $page->header('Product Search');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    GLOBAL $keyword, $page;

    echo "<h3>Product Search Results</h3>\n";
    echo "<article>\n";

    echo "  <table class='table-condensed'>\n";
    echo "    <thead>\n";
    echo "      <tr>\n";
    echo "        <th scope='col'></th>\n";
    echo "        <th scope='col'>UPC</th>\n";
    echo "        <th scope='col' colspan='2'>Listing</th>\n";
    echo "        <th scope='col'>Variation</th>\n";
    echo "        <th scope='col'>Release Date</th>\n";
    echo "        <th scope='col'>Factory</th>\n";
    echo "      </tr>\n";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    $data = getProductInfoKeyword($keyword);
    if (empty($data)) {
        echo "      <tr><td colspan='6'>No matching listings found.</td></tr>\n";
    } else {
        $x = 0;
        foreach($data as $d) {
            $x++;
            $rowClass = "";
            $rowStyle = "";
            echo "      <tr ".$rowClass." ".$rowStyle.">\n";
            echo "        <td data-label='#' class='number'>".$x.".</td>\n";
            $upc = (empty($d["upc"])) ? "" : str_replace(",", "<br>", $d["upc"]);
            echo "        <td data-label='UPC' class='center'>".$upc."</td>\n";
            $link = null;
            if (!empty($d["picture"])) {
                $picURL = $page->utility->Getprefixpublicimageurl($d["picture"], THUMB100);
            } else {
                $picURL = "/images/spacer.gif";
            }
            $img = "<img src='".$picURL."' alt='product image' width='100' height='100'>";
            echo "      <td data-label='Picture' class='center'>".$img."</td>\n";
            $label = $d['year']." ".$d['subcategoryname']." ".$d['categorydescription']." ".ucwords($d['boxtypename']);
            $url   = "listing.php?categoryid=".$d["categoryid"]."&subcategoryid=".$d["subcategoryid"]."&boxtypeid=".$d["boxtypeid"]."&listingtypeid=".$d["categorytypeid"]."&year=".$d["year"];
            $link  = "<a href='".$url."' target='_blank'>".$label."</a>";
            echo "        <td>".$link."</td>\n";
            echo "        <td data-label='Variation' class='center'>".$d["variation"]."</td>\n";
            $releasedate = (empty($d['releasedate'])) ? NULL : date("m/d/Y", $d['releasedate']);
            echo "        <td data-label='Release Date' class='date'>".$releasedate."</td>\n";
            $fc    = (empty($d['factorycost']) || $d['factorycost'] == 0.00) ? "" : "$".number_format($d['factorycost'], 2);
            echo "        <td data-label='Factory' class='center'>".$fc."</td>\n";
            echo "      </tr>\n";
        }
    }
    echo "    </tbody>\n";
    echo "  </table>\n";
    echo "</article>\n";
}



function getProductInfoKeyword($keywordsearch) {
    global $page;

    $keyword    = "";
    if (!empty($keywordsearch)) {
        $searchstring = strtolower(trim($keywordsearch));
        $searcharray = explode(" ",$searchstring);
        foreach($searcharray as $sa) {
            $keyword .= " AND (lower(p.year) LIKE '%".$sa."%'\n";
            $keyword .=  "     OR lower(c.categorydescription) LIKE '%".$sa."%'\n";
            $keyword .=  "     OR lower(sc.subcategoryname) LIKE '%".$sa."%'\n";
            $keyword .=  "     OR lower(bt.boxtypename) LIKE '%".$sa."%'\n";
            $keyword .=  "     OR lower(pu.upc) LIKE '%".$sa."%'\n";
            $keyword .=  "     OR lower(p.variation) LIKE '%".$sa."%')\n";
        }
    }

    $sql = "
        SELECT DISTINCT p.categoryid, c.categorydescription, c.categorytypeid,
                        p.subcategoryid, sc.subcategoryname,
                        p.boxtypeid, bt.boxtypename,
                        p.year, p.releasedate, pu.upc, p.variation,
                        p.picture, p.factorycost
          FROM products         p
          JOIN categories       c   ON  c.categoryid            = p.categoryid
                                    AND c.active                = 1
          JOIN subcategories    sc  ON  sc.subcategoryid        = p.subcategoryid
                                    AND sc.active               = 1
          JOIN boxtypes         bt  ON  bt.boxtypeid            = p.boxtypeid
                                    AND bt.active               = 1
          LEFT JOIN (
                SELECT u.productid, array_to_string(array_agg(u.upc), ',') as upc
                  FROM product_upc  u
                  JOIN products     pr  ON  pr.productid        = u.productid
                                        AND pr.active           = 1
                GROUP BY u.productid
                    )           pu  ON  pu.productid        = p.productid

         WHERE 1 = 1
           ".$keyword."
        ORDER BY c.categorydescription, sc.subcategoryname, bt.boxtypename, p.year
    ";

//  echo "<pre>";print_r($sql);echo "</pre>";
    $rs = $page->db->sql_query_params($sql);


    return $rs;
}


?>