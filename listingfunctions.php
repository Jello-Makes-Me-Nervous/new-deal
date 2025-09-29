<?php

function categoryDDM($categoryId = NULL) {
    global $page, $listingTypeId;

    $categories = $page->utility->getListingCategories($listingTypeId);

    $onChange = " onchange = \"$('#subcategoryid').val('');$('#uom').val('');$('#boxtypeid').val('');submit();\"";

    $output = "        ".getSelectDDM($categories, "categoryid", "categoryid", "categorydescription", NULL, $categoryId, "Select", 0, NULL, NULL, $onChange)."\n";

    return $output;
}

function boxTypeDDM($categoryId = null, $boxTypeId = NULL) {
    global $page;

    if (!empty($categoryId)) {
        $rs = $page->utility->getListingBoxTypes($categoryId);
        $onChange = " onchange = \"$('#subcategoryid').val('');$('#uom').val('');submit();\"";
        $output = "          ".getSelectDDM($rs, "boxtypeid", "boxtypeid", "boxtypename", NULL, $boxTypeId, "Select", 0, NULL, NULL, $onChange)."\n";
    } else {
        $output = "<input type='hidden' name='boxtypeid' id='boxtypeid' value='' />\n";
    }

    return $output;
}

function filterGetBoxTypes($categoryId, $subCategoryId, $year) {
    global $page, $DB;

    $typeWhere = ($page->user->canSell()) ? "" : " AND l.type='For Sale'";
    $sql = "SELECT box.boxtypeid, box.active, concat(box.boxtypename||' ('||count(l.listingid)||')') as boxtypename
            FROM boxtypes box
            JOIN listings l ON l.status='OPEN' AND l.boxtypeid=box.boxtypeid AND l.userid<>".FACTORYCOSTID."
            JOIN assignedrights ar on ar.userid=l.userid AND ar.userrightid=1
            JOIN userinfo ui
                ON ui.userid=l.userid
                AND ui.userclassid=3
                AND (
                    (l.type='For Sale' AND ui.vacationsell=0)
                    OR
                    (l.type='Wanted' AND ui.vacationbuy=0)
                )
            ";

    $sql .= " WHERE box.active=1 ".$typeWhere." ";
            
    if (isset($categoryId)) {
        $sql .= " AND l.categoryid=".$categoryId;
    }

    if (isset($subCategoryId)) {
        $sql .= " AND l.subcategoryid=".$subCategoryId;
    }

    if (isset($year)) {
        $sql .= " AND l.year='".$year."'";
    }
    
    $sql .= " GROUP BY box.boxtypeid, box.boxtypename, box.active ORDER BY box.active, box.boxtypename COLLATE \"POSIX\"";
    
    //echo "BoxTypes SQL:<br />\n<pre>".$sql."</pre><br />\n";
    $boxTypesData = $DB->sql_query($sql);
    
    return $boxTypesData;
}
function filterBoxTypeDDM($categoryId, $subCategoryId, $year, $boxTypeId = NULL) {
    global $page;

    if ((!empty($categoryId)) && (!empty($subCategoryId)) && (!empty($year))){
        $rs = filterGetBoxTypes($categoryId, $subCategoryId, $year);
        $onChange = " onchange = \"$('#uomid').val('');submit();\"";
        $output = "          ".getSelectDDM($rs, "boxtypeid", "boxtypeid", "boxtypename", NULL, $boxTypeId, NULL, 0, NULL, NULL, $onChange)."\n";
    } else {
        $output = "<input type='hidden' name='boxtypeid' id='boxtypeid' value='' />\n";
    }

    return $output;
}

function yearDDM($categoryId, $boxTypeId, $year) {
    global $page;

    if ((!empty($categoryId)) && (!empty($boxTypeId))) {
        $rs = $page->utility->getListingYears($categoryId, $boxTypeId);
        $onChange = " onchange = \"$('#subcategoryid').val('');submit();\"";
        $output = "          ".getSelectDDM($rs, "year", "year", "yearname", NULL, $year, "Select", 0, NULL, NULL, $onChange)."\n";
    } else {
        $output = "<input type='hidden' name='year' id='year' value='' />\n";
    }

    return $output;
}

function subCatDDM($categoryId, $boxTypeId, $year, $subCatId) {
    global $page, $listingTypeId;

    if ((!empty($categoryId)) && (!empty($boxTypeId))) {
        if (($listingTypeId == LISTING_TYPE_GAMING) || (!empty($year))) {
            $rs = $page->utility->getListingSubCategories($categoryId, $boxTypeId, $year);
            $onChange = " onchange = 'submit();'";
            $output = "          ".getSelectDDM($rs, "subcategoryid", "subcategoryid", "subcategoryname", NULL, $subCatId, "Select", 0, NULL, NULL, $onChange)."\n";
        } else {
            $output = "<input type='hidden' name='subcategoryid' id='subcategoryid' value='' />\n";
        }
    } else {
        $output = "<input type='hidden' name='subcategoryid' id='subcategoryid' value='' />\n";
    }

    return $output;
}

function uomDDM($categoryId, $boxTypeId, $year, $subCatId, $uomId) {
    global $page, $listingTypeId;

    if ((!empty($categoryId)) && (!empty($boxTypeId)) && (!empty($subCatId)) && (($listingTypeId == LISTING_TYPE_GAMING) || (!empty($year)))) {
        $rs = $page->utility->getListingUOMs($categoryId, $boxTypeId, $year, $subCatId);
        $onChange = " onchange = 'submit();'";
        $output = "          ".getSelectDDM($rs, "uomid", "uomid", "uomname", NULL, $uomId, "All", NULL, NULL, NULL, $onChange)."\n";
    } else {
        $output = "<input type='hidden' name='uomid' id='uomid' value='' />\n";
    }

    return $output;
}

?>