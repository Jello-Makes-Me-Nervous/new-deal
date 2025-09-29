<?php
require_once('templateAdmin.class.php');

$page = new templateAdmin(LOGIN, SHOWMSG);

$addSubCategory             = optional_param('addSubCategory', NULL, PARAM_TEXT);
$active                     = optional_param('active', NULL, PARAM_INT);
$categoryId                 = optional_param('categoryId', NULL, PARAM_INT);
$categoryName               = optional_param('categoryName', NULL, PARAM_INT);
$commitEditSubCat           = optional_param('commitEditSubCat', NULL, PARAM_INT);
$confirm                    = optional_param('confirm', NULL, PARAM_INT);
$deleteSubCat               = optional_param('deleteSubCat', 0, PARAM_INT);
$editCategoryName           = optional_param('editCategoryName', 0, PARAM_INT);
$editSubCat                 = optional_param('editSubCat', 0, PARAM_INT);
$newSubCat                  = optional_param('newSubCat', NULL, PARAM_INT);
$originalName               = optional_param('originalName', NULL, PARAM_TEXT);
$subCategoryDescription     = optional_param('subCategoryDescription', NULL, PARAM_CLEANHTML);
$subCategoryId              = optional_param('subCategoryId', NULL, PARAM_INT);
$subCategoryName            = optional_param('subCategoryName', NULL, PARAM_TEXT);
$isSecondary                = optional_param('secondary', 0, PARAM_INT);
$subcatStatus               = optional_param('subcatstatus', "", PARAM_TEXT);
$secondaryType              = optional_param('secondarytype', "", PARAM_TEXT);
$updateSubCats              = optional_param('updatesubcats', 0, PARAM_INT);

$createdBy  = $USER->username;
$modifiedBy = $USER->username;

if ($categoryId) {
    $categoryName = $page->db->get_field_query("SELECT categoryname FROM categories WHERE categoryid=".$categoryId);
}

if ($page->user->isAdmin()) {
    if ($deleteSubCat == 1) {
        deleteSubCategories($subCategoryId, $subCategoryName, $categoryName);
    }

    if (isset($commitEditSubCat)) {
        editSubCategories($editCategoryName, $subCategoryId, $subCategoryName, $subCategoryDescription, $isSecondary, $active, $modifiedBy, $originalName, $categoryId);
    }

    if (isset($addSubCategory)) {
        addSubCategory($categoryId, $subCategoryName, $subCategoryDescription, $isSecondary, $active, $createdBy);
    } else {
        if ($updateSubCats) {
            updateSubCategories();
        }
    }
}

if ($newSubCat == 1 || !empty($editSubCat)){
    $editAddSelect= getSelectDDM($page->utility->getcategories(), "editCategoryname", "categoryid", "categoryname", NULL, $categoryId, "Select");
}

echo $page->header('Sub Categories');
echo mainContent();
echo $page->footer(true);


function mainContent() {
    global $categoryId, $subcatStatus, $secondaryType, $categoryName, $editAddSelect, $editSubCat, $MESSAGES, $newSubCat, $subCategoryId, $subCategoryName;
    global $page;
    
    $filterParams = "";
    if ($subcatStatus) {
        $filterParams .= "&subcatstatus=".$subcatStatus;
    }
    if ($secondaryType) {
        $filterParams .= "&secondarytype=".$secondaryType;
    }

    echo "             <article>\n";
    echo "               <div class='entry-content'>\n";
    echo "                 <form value='1' action='subCategories.php' method='post'>\n";
    echo categoryDDM($categoryId);
    echo "&nbsp;&nbsp;";
    echo activeDDM($subcatStatus);
    echo "&nbsp;&nbsp;";
    echo secondaryDDM($secondaryType);
    echo "                 <a class='button' href='subCategories.php?newSubCat=1&categoryId=".$categoryId.$filterParams."'>NEW</a>";
    echo "                 </form>\n";
    if ($categoryId){
        $subcategories = SubCategories($categoryId, $subcatStatus, $secondaryType);
        echo "               <form name='sub' id='sub' action='subCategories.php' method='post'>\n";
        if (!($newSubCat || $editSubCat)) {
            echo "                  <input type='hidden' name='updatesubcats' id='updatesubcats' value='1' />\n";
            echo "                  <input type='hidden' name='categoryId' id='categoryId' value='".$categoryId."' />\n";
        }
            
        if ($subcatStatus) {
            echo "                  <input type='hidden' name='subcatstatus' id='subcatstatus' value='".$subcatStatus."' />\n";
        }
        if ($secondaryType) {
            echo "                  <input type='hidden' name='secondarytype' id='secondarytype' value='".$secondaryType."' />\n";
        }
        echo "                  <table border='1'>\n";
        if (!($newSubCat || $editSubCat)) {
            echo "                    <caption>\n";
            echo "                 &nbsp;&nbsp;<a href='#' onClick='document.sub.submit();'><button>Update Subcategories</button></a>";
            echo "                    </caption>\n";
        }
        echo "                    <thead>\n";
        echo "                      <tr>\n";
        echo "                        <th>ID</th>\n";
        echo "                        <th>Category</th>\n";
        echo "                        <th>Sub Category</th>\n";
        echo "                        <th>Description</th>\n";
        echo "                        <th>Secondary</th>\n";
        echo "                        <th>Active</th>\n";
        echo "                        <th>In Use</th>\n";
        echo "                        <th>Action</th>\n";
        echo "                      </tr>\n";
        echo "                    </thead>\n";
        echo "                    <tbody>\n";
        if ($newSubCat == 1) {
            echo "                      <tr>\n";
            echo "                        <td></td>\n";
            echo "                        <td>".$editAddSelect."\n";
            echo "                          <input type='hidden' name='categoryId' value='".$categoryId."'/>\n";
            echo "                          <input type='hidden' name='categoryName' value='".$categoryName."'/>\n";
            echo "                        </td>\n";
            echo "                        <td><input type='text' name='subCategoryName' value=''/></td>\n";
            echo "                        <td><input type='text' name='subCategoryDescription' value=''/></td>\n";
            echo "                        <td><input type='checkbox' name='secondary' value='1'/></td>\n";
            echo "                        <td>\n";
            echo "                          <input type='checkbox' name='active' value='1'/>\n";
            echo "                          <input type='hidden' name='addSubCategory' value='Add New'/>\n";
            echo "                        </td>\n";
            echo "                        <td>&nbsp;</td>\n";
            echo "                        <td><a href='' onclick=\"javascript:document.sub.submit();return false;\">Save</a> - <a href='?categoryId=".$categoryId.$filterParams."' /> Cancel</td>\n";
            echo "                      </tr>\n";
        }
        if (isset($subcategories) > 0) {
            foreach ($subcategories as $row) {
                if ($editSubCat == $row['subcategoryid']) {
                    echo "                      <tr id='editA".$editSubCat."'  style='background-color: #CCC;'>\n";
                    echo "                        <td>\n";
                    echo "                          ".$row['subcategoryid'];
                    echo "                          <input type='hidden' name='subCategoryId' value='".$row['subcategoryid']."'/>\n";
                    echo "                          <input type='hidden' name='originalName' value='".$row['subcategoryname']."'/>\n";
                    echo "                          <input type='hidden' name='categoryName' value='".$row['categoryname']."'/>\n";
                    echo "                          <input type='hidden' name='categoryId' value='".$row['categoryid']."'/>\n";
                    echo "                        </td>\n";
                    echo "                        <td>\n";
                    echo "                          ".$row["categoryname"]."\n";
                    echo "                          <input type='hidden' name='editCategoryname' value='".$row['categoryid']."'/>\n";
                    echo "                        </td>\n";

                    echo "                        <td><input type='text' name='subCategoryName' value='".$row['subcategoryname']."' autofocus /></td>\n";
                    echo "                        <td><input type='text' name='subCategoryDescription' value='".htmlspecialchars($row['subcategorydescription'], ENT_QUOTES, 'UTF-8')."'/></td>\n";
                    echo "                        <td><input type='checkbox' name='secondary' value='1' ".$page->utility->checked($row['secondary'])." /></td>\n";
                    echo "                        <td><input type='checkbox' name='active' value='1' ".$page->utility->checked($row['active'])." /></td>\n";
                    echo "                        <td>".$row["inuse"]."</td>\n";
                    echo "                        <td>\n";
                    echo "                          <input type='hidden' name='commitEditSubCat' value='Commit Edit'/>\n";
                    echo "                          <a href='' onclick=\"javascript:document.sub.submit();return false;\">Save</a> - \n";
                    echo "                          <a href='subCategories.php?categoryId=".$categoryId.$filterParams."' /> Cancel\n";
                    echo "                        </td>\n";
                    echo "                      </tr>\n";
                } else {
                    if (isset($subCategoryId) && $subCategoryId == $row['subcategoryid'] || $subCategoryName== $row['subcategoryname']) {
                        echo "                      <tr id='A".$subCategoryId."' style='background-color: #C8F5E0'>\n";
                    } else {
                        echo "                      <tr>\n";
                    }
                    echo "                            <td>".$row['subcategoryid']."</td>\n";
                    echo "                            <td>".$row['categoryname']."</td>\n";
                    echo "                            <td>".$row['subcategoryname']."</td>\n";
                    echo "                            <td>".$row['subcategorydescription']."</td>\n";
                    $disableBulk = ($newSubCat || $editSubCat) ? " disabled " : "";
                    echo "                            <td><input type='checkbox' name='secondary_".$row['subcategoryid']."' id='secondary_".$row['subcategoryid']."' value='1' ".$page->utility->checked($row['secondary'])." ".$disableBulk." /></td>\n";
                    echo "                            <td><input type='checkbox' name='active_".$row['subcategoryid']."' id='active_".$row['subcategoryid']."' value='1' ".$page->utility->checked($row['active'])." ".$disableBulk." /></td>\n";
                    echo "                            <td>".$row["inuse"]."</td>\n";
                    echo "                            <td>\n";
                    if ($newSubCat || $editSubCat) {
                        echo "&nbsp;";
                    } else {
                        echo "                              <a href='subCategories.php?editSubCat=".$row['subcategoryid']."&categoryId=".$row['categoryid']."&categoryName=".URLEncode($row['categoryname']).$filterParams."' class='fas fa-edit'></a>\n";
                        if (empty($row["inuse"])) {
                            echo "                              <a href='subCategories.php?deleteSubCat=1&subCategoryName=".$row['subcategoryname']."&subCategoryId=".$row['subcategoryid']."&categoryName=".URLEncode($row['categoryname'])."&categoryId=".$categoryId.$filterParams."'\n";
                            echo "                                onclick=\"javascript: return confirm('Are you sure you want to permently delete the Sub Category - ".$row['subcategoryname']."')\">Delete</a>\n";
                        }
                    }
                    echo "                            </td>\n";
                    echo "                          </tr>\n";
                }
            }
        }
        echo "                   </tbody>\n";
        echo "                 </table>\n";
        echo "               </form>\n";
        echo "             </div>\n";
        echo "           </article>\n";
    }
}


function categoryDDM($categoryId) {
    global $page;

    $output = "";
    $output = "<label label-for='categoryId'><strong>Category:</strong></label>";
    $onChangeScript = "onChange=\"javascript: this.form.submit();\"";
    $output .= $mySelect= getSelectDDM($page->utility->getcategories(), "categoryId", "categoryid", "categoryname", NULL, $categoryId, "Select", NULL, NULL, NULL, $onChangeScript);

    return $output;

}


function activeDDM($activeStatus) {
    global $page;

    $output = "";
    $output .= " <label label-for='subcatstatus'><strong>Status:</strong></label>";
    $output .= "<select name='subcatstatus' id='subcatstatus' onchange='this.form.submit();'>\n";
    $output .= "<option value='' ".$page->utility->selected($activeStatus, "")." >All</option>\n";
    $output .= "<option value='Active' ".$page->utility->selected($activeStatus, "Active")." >Active</option>\n";
    $output .= "<option value='Inactive' ".$page->utility->selected($activeStatus, "Inactive")." >Inactive</option>\n";
    $output .= "</select>\n";

    return $output;

}


function secondaryDDM($secondaryType) {
    global $page;

    $output = "";
    $output .= " <label label-for='secondarytype'><strong>Type:</strong></label>";
    $output .= "<select name='secondarytype' id='secondarytype' onchange='this.form.submit();'>\n";
    $output .= "<option value='' ".$page->utility->selected($secondaryType, "")." >All</option>\n";
    $output .= "<option value='Primary' ".$page->utility->selected($secondaryType, "Primary")." >Primary</option>\n";
    $output .= "<option value='Secondary' ".$page->utility->selected($secondaryType, "Secondary")." >Secondary</option>\n";
    $output .= "</select>\n";

    return $output;

}

function SubCategories($categoryId, $subcatStatus, $secondaryType) {
    global $page;

    
    $sql = "
        SELECT cat.categoryname, cat.categoryid, sub.subcategoryname, sub.subcategoryid, subcategorydescription, sub.active, sub.secondary, l.inuse
          FROM subcategories    sub
          JOIN categories       cat ON cat.categoryid = sub.categoryid
          LEFT JOIN (
                SELECT subcategoryid, count(1) as inuse
                  FROM listings
                 WHERE categoryid = ".$categoryId."
                GROUP BY subcategoryid
                ORDER BY subcategoryid
               )                l   ON  l.subcategoryid = sub.subcategoryid
         WHERE cat.categoryid = ".$categoryId."
         ";
    if ($subcatStatus) {
        $sql .= "AND sub.active=".(($subcatStatus=='Active') ? 1 : 0)."
         ";
    }
    if ($secondaryType) {
        $sql .= "AND sub.secondary=".(($secondaryType=='Secondary') ? 1 : 0)."
         ";
    }
    
    //$sql .= "ORDER BY sub.active DESC, sub.secondary, sub.subcategoryname COLLATE \"POSIX\" ";
    $sql .= "ORDER BY sub.subcategoryname COLLATE \"POSIX\" ";
    //echo "SubCategories SQL:<br />\n<pre>".$sql."</pre><br />\n";
    $categories = $page->db->sql_query_params($sql);

    return $categories;
}

function deleteSubCategories($subCategoryId, $subCategoryName, $categoryName) {
    global $page;

    $success = FALSE;

    $sql = "
        DELETE FROM subcategories
         WHERE subcategoryid = ".$subCategoryId;

    try {
        $delete = $page->db->sql_execute_params($sql);
        $success = TRUE;
        $page->messages->addSuccessMsg('You have successfully deleted '.$subCategoryName);
    } catch (Exception $e) {
        $page->messages->addErrorMsg("ERROR: ".$e->getMessage());
    } finally {
    }

    return $success;

}

function editSubCategories($editCategoryName, $subCategoryId, $subCategoryName, $subCategoryDescription, $isSecondary, $active, $modifiedBy, $originalName, $categoryId) {
    global $page;

    $success = FALSE;

    if ($page->utility->checkDupNameSubCat($subCategoryName, $editCategoryName, $categoryId, $originalName) == FALSE) {

        $active = ($active == 0) ? 0 : 1;

        $sql = "
            update subcategories
               set subcategoryname        = :subcategoryname,
                   subcategorydescription = :subcategorydescription,
                   secondary              = :secondary,
                   active                 = :active,
                   modifiedby             = :modifiedby,
                   modifydate             = nowtoint()
             where subcategoryid = :subcategoryid
        ";
        $params = array();
        $params['subcategoryname']          = $subCategoryName;
        $params['subcategorydescription']	= $subCategoryDescription;
        $params['secondary']                = $isSecondary;
        $params['active']                   = $active;
        $params['modifiedby']               = $modifiedBy;
        $params['subcategoryid']            = $subCategoryId;

        try {
            //echo "editSubcategory SQL:<br />\n<pre>$sql\n";var_dump($params);echo "</pre><br />\n";
            $result = $page->db->sql_execute_params($sql, $params);
            $success = TRUE;
            $page->messages->addSuccessMsg('You have edited the subcategory - '.$subCategoryName);
        } catch (Exception $e) {
            $page->messages->addErrorMsg("ERROR: ".$e->getMessage());
        } finally {
        }
    } else {
        $page->messages->addErrorMsg("The Subcategory name (".$subCategoryName.") is already in use in the choosen catergory.");
    }

    return $success;
}

function addSubCategory($categoryId, $subCategoryName, $subCategoryDescription, $isSecondary, $active, $createdBy) {
    global $page;

    $success = FALSE;

    $active = ($active == 0) ? 0 : 1;

    if ($page->utility->checkDupNameSubCat($subCategoryName, $categoryId) == FALSE) {
        $sql = "
            INSERT INTO subcategories( categoryId,  subCategoryName,  subCategoryDescription,  secondary,  active,  createdBy )
                               VALUES(:categoryId, :subCategoryName, :subCategoryDescription, :secondary, :active, :createdby)
        ";
        $params = array();
        $params['categoryId']               = $categoryId;
        $params['subCategoryName']		    = $subCategoryName;
        $params['subCategoryDescription']	= $subCategoryDescription;
        $params['secondary']                = $isSecondary;
        $params['active']                   = $active;
        $params['createdby']                = $createdBy;

        try {
            $result = $page->db->sql_execute_params($sql, $params);
            $success = TRUE;
            $page->messages->addSuccessMsg('You have added the subcategory- '.$subCategoryName);
        } catch (Exception $e) {
            $page->messages->addErrorMsg("ERROR: ".$e->getMessage());
        } finally {
        }
    } else {
        $page->messages->addErrorMsg("The Subcategory name (".$subCategoryName.") is already in use.");
    }
    return $success;

}

function updateSubCategories() {
    global $page, $categoryId, $subcatStatus, $secondaryType;

    $success = true;
    $numUpdates = 0;

    if ($subcategories = SubCategories($categoryId, $subcatStatus, $secondaryType)) {
        $page->db->sql_begin_trans();
        foreach($subcategories as $subcat) {
            if ($success) {
                $newActive = optional_param('active'.'_'.$subcat['subcategoryid'], 0, PARAM_INT);
                $newSecondary = optional_param('secondary'.'_'.$subcat['subcategoryid'], 0, PARAM_INT);
                //echo "SubCatId: ".$subcat['subcategoryid']." Active:".$newActive."/".$subcat['active']." Secondary:".$newSecondary."/".$subcat['secondary']."<br />\n";
                if (($newActive != $subcat['active']) || ($newSecondary != $subcat['secondary'])) {
                    $sql = "UPDATE subcategories SET active=".$newActive.",secondary=".$newSecondary." WHERE subcategoryid=".$subcat['subcategoryid'];
                    if ($page->db->sql_execute($sql)) {
                        $page->messages->addSuccessMsg("Updated subcategory ".$subcat['subcategoryid'].": ".$subcat['subcategoryname']);
                        $numUpdates++;
                    } else {
                        $page->messages->addErrorMsg("Error updating subcategory ".$subcat['subcategoryid']);
                        $success = false;
                    }
                }
            }
        }

        if ($success) {
            $page->db->sql_commit_trans();
            if ($numUpdates) {
                $page->messages->addSuccessMsg("Updated ".$numUpdates." subcategories.");
            } else {
                $page->messages->addWarningMsg("No subcategory updates specified.");
            }
        } else {
            $page->db->sql_rollback_trans();
            $page->messages->addErrorMsg("Error updating subcategories. All changes reverted.");
        }
    } else {
        $page->messages->addWarningMsg("No subcategories specified.");
    }
}

?>