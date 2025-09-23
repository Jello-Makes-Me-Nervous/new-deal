<?php
require_once('templateAdmin.class.php');

$page = new templateAdmin(LOGIN, SHOWMSG);
$page->requireJS('scripts/validateCats.js');

$active                 = optional_param('active', NULL, PARAM_INT);
$addCategory            = optional_param('addcategory', 0, PARAM_INT);
$categoryId             = optional_param('categoryid', NULL, PARAM_INT);
$categoryDescription    = optional_param('categorydescription', NULL, PARAM_CLEANHTML);
$categoryName           = optional_param('categoryname', NULL, PARAM_TEXT);
$categoryTypeId         = optional_param('categorytypeid', NULL, PARAM_INT);
$commitEditCat          = optional_param('commiteditcat', 0, PARAM_INT);
$confirm                = optional_param('confirm', NULL, PARAM_INT);
$deleteCat              = optional_param('deletecat', 0, PARAM_INT);
$editCat                = optional_param('editcat', 0, PARAM_INT);
$newCat                 = optional_param('newcat', NULL, PARAM_INT);
$originalName           = optional_param('originalname', NULL, PARAM_TEXT);
$yearFormatTypeId       = optional_param('yearformattypeid', NULL, PARAM_TEXT);
$showonmenu             = optional_param('showonmenu', 0, PARAM_INT);

if ($page->user->isAdmin()) {
    if ($deleteCat == 1) {
        deleteCategories($categoryId);
    }
    if ($commitEditCat == 1) {
        EditCategories($categoryName, $categoryDescription, $active, $categoryTypeId,$categoryId, $originalName, $yearFormatTypeId, $showonmenu);
    }
    if ($addCategory == 1) {
        addCategoryegories($categoryName, $categoryDescription, $active, $categoryTypeId, $yearFormatTypeId, $showonmenu);
    }
}

echo $page->header('Categories');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $categoryId, $editCat, $categoryName, $newCat, $USER;

    $categories = getCategories();
    echo "<article>\n";
    echo "  <div class='entry-content'>\n";
    if (!isset($newCat)) {
        echo "    <div style='float: left; margin: 10px 5px 20px 5px;'><a class='button' href='/categories.php?newcat=1'>NEW</a></div>\n";
    }
    echo "    <form name = 'sub' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "      <table>\n";
    echo "        <thead>\n";
    echo "          <tr>\n";
    echo "            <th>ID</th>\n";
    echo "            <th>Name</th>\n";
    echo "            <th>Description</th>\n";
    echo "            <th>Category Type</th>\n";
    echo "            <th>Year Format</th>\n";
    echo "            <th>Show On Menu</th>\n";
    echo "            <th>Active</th>\n";
    echo "            <th>Action</th>\n";
    echo "          </tr>\n";
    echo "        </thead>\n";
    echo "        <tbody>\n";
    if ($newCat == 1) {
        echo "          <tr>\n";
        echo "            <td><input type='hidden' name='addcategory' id='addcategory' value='1'></td>\n";
        echo "            <td><input type='text' name='categoryname' id='categoryname' value=''></td>\n";
        echo "            <td><input type='text' name='categorydescription' id='categorydescription' value=''></td>\n";
        echo "            <td>".categoryTypeId()."</td>\n";
        echo "            <td>".yearFormatTypeId()."</td>\n";
        echo "            <td><input type='checkbox' name='showonmenu' id='showonmenu' value='1'/></td>\n";
        echo "            <td><input type='checkbox' name='active' id='active' value='1'/></td>\n";
        echo "            <td>\n";
        echo "              <a href='javascript: void(0);' onclick=\"javascript: if(validateCats()) {document.sub.submit();} else { return false;}\">Save </a>\n";
        echo "              <a href='".htmlentities($_SERVER['PHP_SELF'])."' />- Cancel\n";
        echo "            </td>\n";
        echo "          </tr>\n";

    }
    if (isset($categories)) {
        foreach ($categories as $row) {
            if ($editCat == $row['categoryid']) {
                echo "          <tr>\n";
                echo "            <td>".$row['categoryid']."\n";
                echo "              <input type='hidden' name='categoryid' id='categoryid' value='".$row['categoryid']."'>\n";
                echo "              <input type='hidden' name='originalname' id='originalname' value='".$row['categoryname']."'>\n";
                echo "            </td>\n";
                echo "            <td><input type='text' name='categoryname' id='categoryname' value='".$row['categoryname']."'></td>\n";
                echo "            <td><input type='text' name='categorydescription' id='categorydescription' value='".htmlspecialchars($row['categorydescription'], ENT_QUOTES, 'UTF-8')."'></td>\n";
                echo "            <td>".categoryTypeId($row['categorytypeid'])."</td>\n";
                echo "            <td>".yearFormatTypeId($row['yearformattypeid'])."</td>\n";
                echo "            <td><input type='checkbox' name='showonmenu' id='showonmenu' value='1' ".checked($row['showonmenu'])." ></td>\n";
                echo "            <td><input type='checkbox' name='active' id='active' value='1' ".checked($row['active'])." ></td>\n";
                echo "            <td>\n";
                echo "              <input type='hidden' name='commiteditcat' id='commiteditcat' value='1'>\n";
                echo "              <a href='javascript: void(0);' onclick=\"javascript: if(validateCats()) {document.sub.submit();} else { return false;}\">Save </a>\n";
                echo "              <a href='".htmlentities($_SERVER['PHP_SELF'])."' />- Cancel\n";
                echo "            </td>\n";
                echo "            </td>\n";
                echo "          </tr>\n";
            } else {
                echo "          <tr>\n";
                echo "            <td>".$row['categoryid']."</td>\n";
                echo "            <td>".stripslashes($row['categoryname'])."</td>\n";
                echo "            <td>".stripslashes($row['categorydescription'])."</td>\n";
                echo "            <td>".$row['categorytypename']."</td>\n";
                echo "            <td>".$row['yearformattype'].":  ".$row['yeartypedefinition']."</td>\n";
                echo "            <td><input type='checkbox'name='showonmenu' id='showonmenu' ".checked($row['showonmenu'])." readonly disabled></td>\n";
                echo "            <td><input type='checkbox'name='active' id='active' ".checked($row['active'])." readonly disabled></td>\n";
                echo "            <td>\n";
                echo "              <a href='/categories.php?editcat=".$row['categoryid']."#editA".$row['categoryid']."' class=' fas fa-edit'></a>\n";
                if (empty($row['catcount'])) {
                    echo "              <a href='/categories.php?deletecat=1&categoryid=".$row['categoryid']."'\n";
                    echo "                onclick=\"javascript: return confirm('Are you sure you want to permently delete the category - ".$row['categoryname']."')\"> - Delete</a>\n";
                }
                echo "            </td>\n";
                echo "          </tr>\n";
            }
        }
    }

    echo "        </tbody>\n";
    echo "      </table><br /><br />\n";
    echo "    </form>\n";
    echo "  </div>\n";
    echo "</article>\n";

}

function yearFormatTypeId($ROWyearformattypeid = NULL) {
    global $page;

    $output = "";
    if (isset($ROWyearformattypeid)) {
        $output .= getSelectDDM($page->utility->getYearFormatType(), "yearformattypeid", "yearformattypeid", "yearformattype", NULL, $ROWyearformattypeid, "Select");
    } else {
        $output .= getSelectDDM($page->utility->getYearFormatType(), "yearformattypeid", "yearformattypeid", "yearformattype", NULL, NULL, "Select");
    }

    return $output;

}

function categoryTypeId($ROWcategorytypeid = NULL) {
    global $page;

    $output = "";
    if (isset($ROWcategorytypeid)) {
        $output .= getSelectDDM($page->utility->getCategoryType($ROWcategorytypeid), "categorytypeid", "categorytypeid", "categorytypename", NULL, $ROWcategorytypeid, "Select");
    } else {
        $output .= getSelectDDM($page->utility->getCategoryType(), "categorytypeid", "categorytypeid", "categorytypename", NULL, NULL, "Select");
    }

    return $output;
}


function getCategories() {
    global $page;

    $sql = "
        SELECT cat.categoryid, cat.categoryname, cat.categorydescription, cat.active, cat.categorytypeid, cat.yearformattypeid,
               yft.yeartypedefinition, yft.yearformattype,
               typ.categorytypename, isnull(cnt.catcount, 0) AS catcount,
               cat.showonmenu
          FROM categories       cat
          LEFT JOIN (
                SELECT categoryid, COUNT(categoryid) AS catcount
                  FROM subcategories
                GROUP BY categoryId
                    )   cnt ON  cnt.categoryid = cat.categoryid
          JOIN yearformattype   yft ON  yft.yearformattypeid    = cat.yearformattypeid
          JOIN categorytypes    typ ON  typ.categorytypeid      = cat.categorytypeid
         ORDER BY cat.active DESC, cat.categoryname COLLATE \"POSIX\"
    ";

      $categories = $page->db->sql_query_params($sql);
      return $categories;
}

function checked($checked){
    if ($checked == 1) {
        $checked = "checked";
    } elseif ($checked == 0) {
        $checked = "";
    }
    return $checked;
}

function deleteCategories($categoryId) {
    global $page;

    $success = FALSE;
    $sql = "
        DELETE FROM categories WHERE categoryid = ".$categoryId."
    ";

    try {
        $page->db->sql_execute_params($sql);
        $page->messages->addSuccessMsg("Category deleted.");
        $success = TRUE;
    } catch (Exception $e) {
        $page->messages->addErrorMsg("ERROR: ".$e->getMessage()." [Unable to delete category.]");
        $success = FALSE;
    }

    return $success;

}

function EditCategories($categoryName, $categoryDescription, $active, $categoryTypeId, $categoryId, $originalName, $yearFormatTypeId, $showonmenu) {
    global $page;

    if ($page->utility->checkDuplicateName("categories", "categoryname", $categoryName, $originalName) == FALSE) {

        $success    = FALSE;
        $active     = (empty($active)) ?  0 : 1;
        $showonmenu = (empty($showonmenu)) ?  0 : 1;

        $sql = "
            UPDATE categories
               SET categoryname         = :categoryname,
                   categorydescription  = :categorydescription,
                   categorytypeid       = :categorytypeid,
                   yearformattypeid     = :yearformattypeid,
                   active               = :active,
                   showonmenu           = :showonmenu,
                   modifydate           = nowtoint(),
                   modifiedby           = :modifiedby
             WHERE categoryid           = :categoryid
        ";
        $params = array();
        $params['categoryid']               = $categoryId;
        $params['categoryname']             = $categoryName;
        $params['categorydescription']      = $categoryDescription;
        $params['categorytypeid']           = $categoryTypeId;
        $params['yearformattypeid']         = $yearFormatTypeId;
        $params['active']                   = $active;
        $params['showonmenu']               = $showonmenu;
        $params['modifiedby']               = $page->user->username;

        try {
            $page->db->sql_execute_params($sql, $params);
            $page->messages->addSuccessMsg("Category saved.");
            $success = TRUE;
        } catch (Exception $e) {
            $page->messages->addErrorMsg("ERROR: ".$e->getMessage()." [Unable to update category.]");
            $success = FALSE;
        }

    } else {
        $page->messages->addErrorMsg("The Category name (".$categoryName.") is already in use.");
        $success = FALSE;
    }

    return $success;
}

function addCategoryegories($categoryName, $categoryDescription, $active, $categoryTypeId, $yearFormatTypeId, $showonmenu) {
    global $page;

    $success    = FALSE;
    if ($page->utility->checkDuplicateName("categories", "categoryname", $categoryName) == FALSE) {
        $active     = (empty($active)) ?  0 : 1;
        $showonmenu = (empty($showonmenu)) ?  0 : 1;

        $sql = "
            INSERT INTO categories( categoryname,  categorydescription,  active,  categorytypeid,  yearformattypeid, showonmenu,  createdby)
                            VALUES(:categoryname, :categorydescription, :active, :categorytypeid, :yearformattypeid, :showonmenu, :createdby)
        ";
        $params = array();
        $params['categoryname']             = $categoryName;
        $params['categorydescription']      = $categoryDescription;
        $params['active']                   = $active;
        $params['showonmenu']               = $showonmenu;
        $params['categorytypeid']           = $categoryTypeId;
        $params['yearformattypeid']         = $yearFormatTypeId;
        $params['createdby']                = $page->user->username;

        try {
            $page->db->sql_execute_params($sql, $params);
            $page->messages->addSuccessMsg("Category created.");
            $success = TRUE;
        } catch (Exception $e) {
            $page->messages->addErrorMsg("ERROR: ".$e->getMessage()." [Unable to create category.]");
            $success = FALSE;
        }
    } else {
        $page->messages->addErrorMsg("The Category name (".stripslashes($categoryName).") is already in use.");
    }

    return $success;
}
?>