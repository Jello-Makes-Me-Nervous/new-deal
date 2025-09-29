<?php
require_once('templateMarket.class.php');

$page = new templateMarket(LOGIN, SHOWMSG);
$listing = new listing();

$listingId          = optional_param('listingId', NULL, PARAM_INT);
$lid                = optional_param('lid', NULL, PARAM_TEXT);
$listingIds         = optional_param('listingIds', NULL, PARAM_TEXT);
$minquantity        = optional_param('minquantity', NULL, PARAM_INT);
$dprice             = optional_param('dprice', NULL, PARAM_TEXT);
$quantity           = optional_param('quantity', NULL, PARAM_INT);
$status             = optional_param('status', NULL, PARAM_TEXT);
$update             = optional_param('update', NULL, PARAM_TEXT);

if (!empty($update)) {
    foreach ($lid as $lis => $lids) {
        updateListing($lids, $quantity[$lis], $dprice[$lis], $status[$lids]);
    }
}

echo $page->header('Update Listing');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $listing, $listingId, $listingIds;

    echo "<article>\n";
    echo "  <div class='entry-content'>\n";
//foreach after explode the form
    if (!empty($listingIds)) {
        $listingIdPart = explode(',' , $listingIds);
        echo "<form name='submit' id='submit' action='listingUpdate.php?listingId=".$listingId."' method='post'>\n";
        echo "  <table>\n";
        echo "    <thead>\n";
        echo "      <tr>\n";
        echo "        <th>Edit</th>\n";
        echo "        <th>Active</th>\n";
        echo "        <th>Trans</th>\n";
        echo "        <th>Unit</th>\n";
        echo "        <th>Qty</th>\n";
        echo "        <th>Last</th>\n";
        echo "      </tr>\n";
        echo "    </thead>\n";
        echo "    <tbody>\n";
        foreach ($listingIdPart as $lpId) {
            $listingInfo = $listing->getListing($lpId);
            $lID = reset($listingInfo);
            echo "      <tr>\n";
            echo "        <td>Categoory:</td>\n";
            echo "        <td>".$lID['year']."".$lID['categoryname']." ".$lID['subcategoryname']."</td>\n";
            echo "        <td>\n";

            echo "          <input type='hidden' name='lid[]' value='".$lID['listingid']."'>\n";

            echo "          <input type='radio' name='status[".$lID['listingid']."]' value='OPEN' ".checked("OPEN", $lID['status']).">Open<br />\n";
            echo "          <input type='radio' name='status[".$lID['listingid']."]' value='CLOSED' ".checked("CLOSED", $lID['status']).">Closed\n";
            echo "        </td>\n";
            $dprice = ltrim($lID['dprice'], '$');
            echo "        <td>$<input type='text' name='dprice[]' value='".$dprice."'></td>\n";
            echo "        <td><input type='text' name='quantity[]' value='".$lID['quantity']."'></td>\n";
            echo "      </tr>\n";

        }
        echo "      <tr>\n";
        echo "        <td colspan='6'><input type='submit' name='update' value='Update Listings'></td>\n";
        echo "      </tr>\n";
        echo "    </tbody>\n";
        echo "  </table>\n";
    }

    if (!empty($listingId)) {
        $listingInfo = $listing->getListing($listingId);
        $listingI = reset($listingInfo);
        echo "  <table>\n";
        echo "    <thead>\n";
        echo "      <tr>\n";
        echo "        <th></th>\n";
        echo "        <th></th>\n";
        echo "      </tr>\n";
        echo "    </thead>\n";
        echo "    <tbody>\n";
        echo "      <tr>\n";
        echo "        <td>Categoory:</td>\n";
        echo "        <td>".$listingI['categoryname']."</td>\n";
        echo "      </tr>\n";
        echo "      <tr>\n";
        echo "        <td>Subcategoory:</td>\n";
        echo "        <td>".$listingI['subcategoryname']."</td>\n";
        echo "      </tr>\n";
        echo "      <tr>\n";
        echo "        <td>Boxtype:</td>\n";
        echo "        <td>".$listingI['boxtypename']."</td>\n";
        echo "      </tr>\n";
        echo "      <tr>\n";
        echo "        <td>Status:</td>\n";
        echo "        <td>\n";
        echo "          <input type='radio' name='status' value='OPEN' ".checked("OPEN", $listingI['status']).">Open<br />\n";
        echo "          <input type='radio' name='status' value='CLOSED' ".checked("CLOSED", $listingI['status']).">Closed\n";
        echo "        </td>\n";
        echo "      </tr>\n";
        echo "      <tr>\n";
        echo "        <td>Price:</td>\n";
        $dprice = ltrim($listingI['dprice'], '$');
        echo "        <td>$<input type='text' name='dprice' value='".$dprice."'></td>\n";
        echo "      </tr>\n";
        echo "      <tr>\n";
        echo "        <td>Quantity:</td>\n";
        echo "        <td><input type='text' name='quantity' value='".$listingI['quantity']."'></td>\n";
        echo "      </tr>\n";
        echo "      <tr>\n";
        echo "        <td></td>\n";
        echo "        <td colspan='2'><input type='submit' name='update' value='Update Listing'></td>\n";
        echo "      </tr>\n";
        echo "    </tbody>\n";
        echo "  </table>\n";
    }
    echo "</form>\n";
    echo "  </div>\n";
    echo "</article>\n";

}

function checked($check, $checked) {
    if ($check == $checked) {
        $data = "checked='checked'";
    } else {
        $data = "";
    }

    return $data;
}

function updateListing($listingId, $quantity, $dprice, $status) {
echo $listingId."-".$quantity."-".$dprice."-".$status."<br />";
    global $page;
    $sql = "
        UPDATE listings SET quantity    = :quantity,
                            dprice      = :dprice,
                            status      = :status
         WHERE listingid = :listingid";

    $params = array();
    $params['listingid']        = $listingId;
    $params['quantity']         = $quantity;
    $params['dprice']           = $dprice;
    $params['status']           = $status;

    $result = $page->db->sql_execute_params($sql, $params);
    echo $result;
    if (!empty($result)) {
        //$page->messages->addSuccessMsg("Successfully updated your listing");
    }


}
?>