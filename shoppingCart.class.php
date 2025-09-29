<?php

class shoppingcart {

    public $db;
    public $userId;

    public function __construct() {
        global $DB;

        $this->db = $DB;
    }

    public function getShoppingCartItem($userId, $shoppingCartId) {
        $item = null;

        if ($itemList = $this->getShoppingCart($userId, NULL, $shoppingCartId)) {
            $item = reset($itemList);
        }

        return $item;
    }

    public function getShoppingCart($userId, $type = NULL, $shoppingCartId = NULL) {
        $sql = "
            SELECT box.boxtypename,
                   cat.categoryname, cat.categorydescription, cat.categorytypeid,
                   crt.shoppingcartid, crt.listinguserid, crt.listingid, crt.originalqty, crt.originalminqty, cat.categoryid,
                   sub.subcategoryid, box.boxtypeid, lis.year, crt.currentdprice as unaltereddprice, crt.currentqty, crt.currentminqty,
                   lis.listingnotes, lis.minquantity, lis.picture, lis.quantity, lis.status, lis.type, lis.uom, lis.boxespercase, lis.picture,
                   sub.subcategoryname, sub.subcategorydescription,
                   usr.username, u.onvacation, u.returnondate, u.vacationtype, u.listinglogo,
                   CASE WHEN ar.userrightid IS NULL THEN 0 ELSE 1 END AS elitelisting,
                   CASE WHEN ar.userrightid IS NULL AND bar.userrightid IS NOT NULL THEN 1 ELSE 0 END AS bluestarlisting,
                   crt.originaldprice, crt.currentdprice, lis.dprice, crt.createdate as listingcreated,
                   CASE WHEN vdar.userrightid IS NULL THEN 0 ELSE 1 END AS verifiedlisting,
                   CASE WHEN eft.userid IS NULL THEN 0 ELSE 1 END AS eftlister,
                   lis.expireson, lis.deliverby, pu.upcs, p.variation,

                   CASE WHEN crt.originaldprice != lis.dprice
                        THEN lis.dprice
                         END  newdprice,

                   CASE WHEN crt.currentqty != lis.quantity
                        THEN lis.quantity
                         END newqty,

                   CASE WHEN crt.currentminqty != lis.minquantity
                        THEN lis.minquantity
                         END newminqty

              FROM shoppingcart             crt
              JOIN listings                 lis ON  lis.listingid       = crt.listingid
              JOIN categories               cat ON  cat.categoryid      = crt.categoryid
                                                AND cat.active          = 1
              JOIN subcategories            sub ON  sub.subcategoryid   = crt.subcategoryid
                                                AND sub.active          = 1
              JOIN boxtypes                 box ON  box.boxtypeid       = crt.boxtypeid
                                                AND box.active          = 1
              JOIN users                    usr ON  usr.userid          = crt.userId
              JOIN userinfo                 u   ON  u.userid            = crt.listinguserid
                                                AND u.userclassid       = ".USERCLASS_VENDOR."
              JOIN assignedrights           eur ON  eur.userid          = crt.listinguserid
                                                AND eur.userrightid     = ".USERRIGHT_ENABLED."
              LEFT JOIN products            p   ON  p.categoryid        = lis.categoryid
                                                AND p.subcategoryid     = lis.subcategoryid
                                                AND p.boxtypeid         = lis.boxtypeid
                                                AND isnull(p.year, '1') = isnull(lis.year, '1')
                                                AND p.active            = 1
              LEFT JOIN (
                SELECT u.productid, array_to_string(array_agg(upc), ', ') as upcs
                  FROM product_upc  u
                  JOIN products     p   ON  p.productid     = u.productid
                                        AND p.active        = 1
                GROUP BY u.productid
                        )                   pu  ON  pu.productid        = p.productid
              LEFT JOIN assignedrights      stl ON  stl.userid          = crt.listinguserid
                                                AND stl.userrightid     = ".USERRIGHT_STALE."
              LEFT JOIN assignedrights      ar  ON  ar.userid           = crt.listinguserid
                                                AND ar.userrightid      = ".USERRIGHT_ELITE."
              LEFT JOIN assignedrights      bar ON  bar.userid          = crt.listinguserid
                                                AND bar.userrightid     = ".USERRIGHT_BLUESTAR."
              LEFT JOIN assignedrights     vdar ON  vdar.userid         = crt.listinguserid
                                                AND vdar.userrightid    = ".USERRIGHT_VERIFIED."
              LEFT JOIN preferredpayment    eft ON  eft.userid          = crt.listinguserid
                                                AND eft.paymenttypeid   = ".PAYMENT_TYPE_ID_EFT."
                                                AND eft.transactiontype = lis.type
             WHERE crt.userId   = ".$userId."
               AND lis.status   ='OPEN'
               AND stl.userid IS NULL
        ";
        if (!empty($type)) {
            $sql .= "
               AND lis.type     = '".$type."'
            ";
        }
        if (!empty($shoppingCartId)) {
            $sql .= "
               AND crt.shoppingcartid = ".$shoppingCartId."
            ";
        }

        $sql .= "
             ORDER BY lis.type, crt.createdate desc, usr.username
        ";

//        echo "<pre>".$sql."</pre>";
        $info = $this->db->sql_query($sql);

        return $info;
    }

    public function syncCartUpdates($userId) {

        $isValid = false;

        if ($userId) {
            $sql = "
                UPDATE shoppingcart
                   SET originaldprice   = currentdprice,
                       originalqty      = currentqty,
                       originalminqty   = currentminqty
                WHERE userid = ".$userId;
            $result = $this->db->sql_execute($sql);
            if (isset($result)) {
                $sql = "
                    UPDATE shoppingcart
                       SET currentdprice    = listings.dprice,
                           currentqty       = listings.quantity,
                           currentminqty    = listings.minquantity
                    FROM listings
                    WHERE shoppingcart.userid   = ".$userId."
                      AND listings.listingid    = shoppingcart.listingid
                ";
                $result = $this->db->sql_execute($sql);
                if (isset($result)) {
                    $sql = "
                        DELETE FROM shoppingcart
                         WHERE shoppingcartid IN (
                                    SELECT crt.shoppingcartid
                                      FROM shoppingcart             crt
                                      JOIN listings                 lis ON  lis.listingid       = crt.listingid
                                      JOIN users                    usr ON  usr.userid          = lis.userid
                                      JOIN userinfo                 ui  ON  ui.userid           = usr.userid
                                      LEFT JOIN categories          cat ON  cat.categoryid      = crt.categoryid
                                                                        AND cat.active          = 1
                                      LEFT JOIN subcategories       sub ON  sub.subcategoryid   = crt.subcategoryid
                                                                        AND sub.active          = 1
                                      LEFT JOIN boxtypes            box ON  box.boxtypeid       = crt.boxtypeid
                                                                        AND box.active          = 1
                                      LEFT JOIN assignedrights      enu ON  enu.userid          = lis.userid
                                                                        AND enu.userrightid     = ".USERRIGHT_ENABLED."
                                      LEFT JOIN assignedrights      stl ON  stl.userid          = lis.userid
                                                                        AND stl.userrightid     = ".USERRIGHT_STALE."
                                     WHERE crt.userid   = ".$userId."
                                       AND (lis.status          = 'CLOSED'
                                            OR ui.userclassid   <> 3
                                            OR cat.categoryid IS NULL
                                            OR sub.subcategoryid IS NULL
                                            OR box.boxtypeid IS NULL
                                            OR enu.userid IS NULL
                                            OR stl.userid IS NOT NULL)
                               )
                    ";
                    $result = $this->db->sql_execute($sql);
                    if (isset($result)) {
                        $isValid = true;
                    } else {
                        echo "syncCartUpdates: error clearing inactive<br />\n";
                    }
                } else {
                    echo "syncCartUpdates: error updating current<br />\n";
                }
            } else {
                echo "syncCartUpdates: error clearing original<br />\n";
            }
        } else {
            echo "syncCartUpdates: No userid<br />\n";
        }
        return $isValid;
    }

    public function addToCart($listingId, $userId, $listingUserId, $originalPrice, $originalQty, $originalMinQty,
                              $categoryId, $subCategoryId, $boxTypeId, $notes, $year, $createdBy) {
        global $page;
        $result = null;

        $success = false;

        $sql = "SELECT count(*) AS incart FROM shoppingcart WHERE userid=".$userId." AND listingid=".$listingId;
        $incart = $page->db->get_field_query($sql);
        if ($incart > 0) {
            $page->messages->addErrorMsg('Item already in cart');
        } else {
            $sql = "
                INSERT INTO shoppingcart( listingid,  userid,  listinguserid,  originaldprice,  currentdprice,  originalqty,  currentqty,  originalminqty,  currentminqty,
                                          categoryid,  subcategoryid,  boxtypeid,  notes,  year,  createdby)
                                  VALUES(:listingId, :userId, :listingUserId, :originalPrice, :currentPrice, :originalQty, :currentQty, :originalMinQty, :currentMinqty,
                                         :categoryId, :subCategoryId, :boxTypeId, :notes, :year, :createdBy)
            ";

            $params['listingId']        = $listingId;
            $params['userId']           = $userId;
            $params['listingUserId']    = $listingUserId;
            $params['originalPrice']    = $originalPrice;
            $params['currentPrice']     = $originalPrice;
            $params['originalQty']      = $originalQty;
            $params['currentQty']       = $originalQty;
            $params['originalMinQty']   = $originalMinQty;
            $params['currentMinqty']    = $originalMinQty;
            $params['categoryId']       = $categoryId;
            $params['subCategoryId']    = $subCategoryId;
            $params['boxTypeId']        = $boxTypeId;
            $params['notes']            = $notes;
            $params['year']             = $year;
            $params['createdBy']        = $createdBy;

            try {
                $result = $this->db->sql_execute_params($sql, $params);
                if ($result > 0) {
                    $success = TRUE;
                    //$page->messages->addSuccessMsg('You have added new Items to your cart');
                } else {
                    $page->messages->addErrorMsg('Error?');
                }
            } catch (Exception $e) {
                $page->messages->addErrorMsg("Error: ".$e->getMessage()." [Add to cart]");
                $pagecontent = null;
            } finally {
            }
        }
        return $result;
    }

    public function cartUpdateOnView($dprice, $quantity, $minquantity, $shoppingcartid) {
        global $page;

        $success = "";
        $sql = "
            UPDATE shoppingcart SET currentdprice = :dprice, currentqty = :quantity, currentminqty = :minquantity
             WHERE shoppingcartid = :shoppingcartid
        ";

        $params = array();
        $params['dprice']            = $dprice;
        $params['quantity']         = $quantity;
        $params['minquantity']      = $minquantity;
        $params['shoppingcartid']   = $shoppingcartid;

        $result = $this->db->sql_execute_params($sql, $params);

    }

    public function cartView($wantedItems, $forSaleItems) {
        global $page;

        $hasItems = false;
        $hasWanted = false;
        $hasForSale = false;
        if (is_array($wantedItems) && (count($wantedItems)>0)) {
            $hasWanted = true;
            $hasItems = true;
        }
        if (is_array($forSaleItems) && (count($forSaleItems)>0)) {
            $hasForSale = true;
            $hasItems = true;
        }

        $output = "";

        if ($hasItems) {
            $output .= "  <header>\n";
            $output .= "    <h1>My Cart</h1>\n";
            $output .= "  </header>\n";

            if (($page->user->userclassid == USERCLASS_PREMIUM) || ($page->user->userclassid == USERCLASS_VENDOR)) {
                $output .= "  <ul>\n";
                $output .= "    <li>Counter offers can be made by clicking Buy From or Sell To below and changing quantity and price on the offer form.</li>\n";
                $output .= "    <li>Click Dealer Name / Logo to view profile for metrics, details and other listed products.</li>\n";
                $output .= "  </ul>\n";
            }

            $output .= "  <div class='watchlist-details'>\n";

            if ($hasWanted && $hasForSale) {
                $output .= "<table class='outer-table'>\n";
                $output .= "<theader>\n";
                $output .= "<tr><th>Sell To</th><th>Buy From</th></tr>\n";
                $output .= "<tbody>\n";
                $output .= "<tr style='vertical-align:top;'>\n";
                $output .= "<td>\n";
            }

            if ($hasWanted) {
                $output .= $this->cartInfoView($wantedItems);
            }

            if ($hasWanted && $hasForSale) {
                $output .= "</td><td>\n";
            }

            if ($hasForSale) {
                $output .= $this->cartInfoView($forSaleItems);
            }

            if ($hasWanted && $hasForSale) {
                $output .= "</td>\n";
                $output .= "</tr>\n";
                $output .= "</tbody>\n";
                $output .= "</table>\n";
            }

            $output .= "  </div> <!-- end watchlist details -->\n";
        } else {
            $output .= "Your cart is empty\n";
        }

        return $output;
    }

    public function cartInfoView($cartItems) {
        global $UTILITY, $page;

        $i = 1;
        $output = "";

        // Group listings by the listing user
        $group = array();
        foreach ($cartItems as $value) {
            $group[$value['listinguserid']][] = $value;
        }

        // Display each group
        $currentDT = time();
        foreach ($group as $grp => $gr) {
            $items = array();
            $panel = "";
            $paneltype = "";
            $vacationLock = false; // This is per row but applies at the user level
            $listingLogo = NULL;
            foreach ($gr as $g) {
                if ($g['listinglogo']) { // Listing logo is in dealers userinfo - it will be the same for all their joined listings
                    $listingLogo = $g['listinglogo'];
                }
                if ($g['onvacation'] && ($g['onvacation'] < $currentDT)) {
                    if ((!$g['returnondate']) || ($g['returnondate'] > $currentDT)) {
                        if (($g['vacationtype'] == 'Both')
                        || (($g['vacationtype'] == 'Sell') && ($g['type'] == 'For Sale'))
                        || (($g['vacationtype'] == 'Buy') && ($g['type'] == 'Wanted'))) {
                            $vacationLock = true;
                        }
                    }
                }
                $items[] = $g['shoppingcartid'];
                $dprice = $g['currentdprice'];
                $paneltype = $g['type'];
                $panel .= "<li><span title='Added ".date('m/d/Y H:i:s', $g['listingcreated'])."'>ID#".$g['shoppingcartid']."</span> - ";
                if ($g['picture']) {
                    if ($imgURL = $page->utility->getPrefixListingImageURL($g['picture'])) {
                        $panel .= "<a href='#' id='ModalLink'><i class='fa-regular fa-image'></i></a>\n";
                        $panel .= "<div id='myModal' class='modal'>\n";
                        $panel .= "  <!-- Modal content -->\n";
                        $panel .= "  <div class='modal-content'>\n";
                        $panel .= "      <span class='close'><i class='fa-solid fa-circle-xmark'></i></span>\n";
                        $panel .= "    <img src='".$imgURL."' />\n";
                        $panel .= "  </div>\n";
                        $panel .= "</div>\n";
                        $panel .= $this->getModalJs();
                    }
                }
                if ($g['categorytypeid'] == LISTING_TYPE_SUPPLY) {
                    $targetPage = "supplySummary.php";
                } else {
                    $targetPage = "listing.php";
                }
                $panel .= " - <a href='".$targetPage."?categoryid=".$g['categoryid']."&subcategoryid=".$g['subcategoryid']."&boxtypeid=".$g['boxtypeid']."&year=".$g['year']."' target='blank'>";
                $panel .= $g['year']." - ".$g['subcategorydescription']." - ".$g['categorydescription']." - ".$g['boxtypename'];
                $panel = (empty($g["variation"])) ? $panel : $panel." - ".$g["variation"];
                $panel .= "</a>";
                $panel .= " - ".$g['currentqty']." - ".floatToMoney($g['dprice'])." Per ".strtoupper($g['uom']);
                $panel .= (empty($g["upcs"])) ? "" : "<br><div style='display:inline;margin-left:75px;'><b>UPC:</b> ".$g["upcs"]."</div>\n";
                if ($g['status'] == "OPEN") {
                    $panel .= " <a class='fas fa-trash-alt' href='?remove=".$g['shoppingcartid']."' onclick=\"javascript: return confirm('Remove Cart Item #".$g['listingid']." ?')\"></a>\n";
                }
                if ($g['deliverby']) {
                    $panel .= "<p><strong>Delivery Required By ".date('m/d/y', $g['deliverby'])."</strong></p>\n";
                }
                if ($g['listingnotes']) {
                    $panel .= "<p><strong>Notes: </strong>".$page->utility->htmlFriendlyString($g['listingnotes'])."</p>\n";
                }
                if (($g['currentdprice'] != $g['originaldprice']) || ($g['currentminqty'] != $g['originalminqty']) || ($g['currentqty'] != $g['originalqty'])) {
                    $panel .= "<br /><strong>Note: listing was updated, previously ".$g['originalminqty']." - ".$g['originalqty']." ".floatToMoney($g['originaldprice'])." Per ".strtoupper($g['uom'])."</strong><br />\n";
                }
                $panel .= "</li>\n";
            }
            if (count($items) > 0) {
                if ($listingLogo) {
                    $displayDealerName = "<img style='vertical-align:middle;' src='".$page->utility->getPrefixMemberImageURL($listingLogo)."' title='".strtoupper($UTILITY->getDealersName($grp))."' width='75px' />";
                } else {
                    $displayDealerName = strtoupper($UTILITY->getDealersName($grp));
                }
                $output .= "<div class='watchlist-item'>\n";
                $output .= "  <div class='watchlist-item-header' style='display:flex; align-items:center;'>\n";
                $output .= "<a href='dealerProfile.php?dealerId=".$grp."' target='_blank' >".$displayDealerName."</a>&nbsp;&nbsp;&nbsp;";
                if ($g['elitelisting']) {
                    $output .= " <span title='Elite Dealer'><i class='fas fa-star'></i></span> ";
                }
                if ($g['bluestarlisting']) {
                    $output .= " <span title='Above Standard Dealer'><i class='fas fa-star' style='color: #00f;'></i></span> ";
                }
                if ($g['verifiedlisting']) {
                    $output .= " <span title='Verified Dealer'><i class='fas fa-check' style='color: #090;'></i></span> ";
                }
                if ($g['eftlister']) {
                    $output .= " <span title='EFT Accepted'><i class='fas fa-money-bill-wave'></i></span> ";
                }
                if ($vacationLock) {
                    $output .= "&nbsp;&nbsp;&nbsp;<span class='errormsg'>(Dealer on vacation, listings temporarily unavailable)</span>";
                } else {
                    $buttonLabel = ($paneltype == 'Wanted') ? "Sell To" : "Buy From";
                    $output .= "<a href='shoppingCartProcess.php?processitems=".implode(',',$items)."' title='".$buttonLabel."'><button>".$buttonLabel."</button></a>\n";
                }
                $output .= "  </div>\n";
                $output .= "<ul class='watchlist-items'>\n";
                $output .= $panel;
                $output .= "</ul>\n";
                $output .= "</div>\n";
            }
        }

        return $output;
    }

    public function getModalJs() {
        $output = "";
        // Get the modal
        $output ="<script>\n";
        $output .= "  var modal = document.getElementById('myModal');\n";

        // Get the link that opens the modal
        $output .= "  var link = document.getElementById('ModalLink');\n";

        // Get the <span> element that closes the modal
        $output .= "  var span = document.getElementsByClassName('close')[0];\n";

        // When the user clicks on the button, open the modal
        $output .= "  link.onclick = function() {\n";
        $output .= "  console.log(link);";
        $output .= "    modal.style.display = \"block\";\n";
        $output .= "  }\n";

        // When the user clicks on <span> (x), close the modal
        $output .= "  span.onclick = function() {\n";
        $output .= "    modal.style.display = \"none\";\n";
        $output .= "}\n";

        // When the user clicks anywhere outside of the modal, close it
        $output .= "  window.onclick = function(event) {\n";
        $output .= "    if (event.target == modal) {\n";
        $output .= "      modal.style.display = 'none';\n";
        $output .= "    }\n";
        $output .= "  }\n";
        $output .= "</script>\n";
        return $output;

    }

    public function ORIGcartInfoView($cartItems) {
        global $UTILITY;

        $i = 1;
        $output = "";

        // Group listings by the listing user
        $group = array();
        foreach ($cartItems as $value) {
            $group[$value['listinguserid']][] = $value;
        }

        // Display each group
        foreach ($group as $grp => $gr) {
            $output .= "<br /><strong> Offers To - ".strtoupper($UTILITY->getDealersName($grp))."</strong>\n";
            $output .= "<form name ='sub".$i."' style='margin-left:25px;' action='shoppingCartProcess.php' method='post'>\n";
            $output .= " <br />\n";
            foreach ($gr as $g) {
                $output .= " <input type='hidden' name='listingUserId' value='".$g['listinguserid']."'>\n";
                $output .= " <input type='hidden' name='offertype' value='".$g['type']."'>\n";

                $dprice = $g['currentdprice'] ;

                $output .= $g['type']." - ID#".$g['shoppingcartid']." - ".$g['picture']." -";
                $output .= "<a href='listing.php?categoryId=".$g['categoryid']."&subCategoryId=".$g['subcategoryid']."&boxTypeId=".$g['boxtypeid']."&year=".$g['year']."' target='blank'>";
                $output .= $g['year']." - ".$g['categoryname']." - ".$g['subcategoryname']." - ".$g['boxtypename']." - ";
                $output .= "</a>";
                $output .= " - ".$g['currentminqty']." - ".$g['currentqty']." - ".$g['currentdprice']." Per ".strtoupper($g['uom']."\n");
                if ($g['status'] == "OPEN") {
                    $output .= " <input name='offers[]' value='".$g['shoppingcartid']."' type='hidden'>\n";
                    $output .= " <a class='fas fa-trash-alt' href='?remove=".$g['shoppingcartid']."' onclick=\"javascript: return confirm('Remove Cart Item #".$g['listingid']." ?')\"></a>\n";
                }
                $output .= "<br />\n";
                if (($g['currentdprice'] != $g['originaldprice']) || ($g['currentminqty'] != $g['originalminqty']) || ($g['currentqty'] != $g['originalqty'])) {
                    $output .= "<strong>Note: listing was updated, previously ".$g['originalminqty']." - ".$g['originalqty']." ".$g['originaldprice']." Per ".strtoupper($g['uom'])."</strong><br />\n";
                }
            }
            $output .= "<input class='button' type='submit' name='process' value='Process Offer(s)'><br />\n";
            $i++;
            $output .= "</form>\n";
        }

        return $output;
    }

    public function removeItem($remove) {

        $sql = "
            DELETE
              FROM shoppingcart
             WHERE shoppingcartid = ".$remove."
        ";

        $result = $this->db->sql_execute_params($sql);

        header('location:shoppingCart.php');
        exit();
    }
}
?>