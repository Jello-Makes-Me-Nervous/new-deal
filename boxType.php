<?php
require_once('templateAdmin.class.php');

$page = new templateAdmin(LOGIN, SHOWMSG);
$page->requireJS('scripts/validateBoxTypes.js');

$active             = optional_param('active', NULL, PARAM_INT);
$addBoxType         = optional_param('addBoxType', NULL, PARAM_ALPHA);
$boxTypeId          = optional_param('boxTypeId', NULL, PARAM_INT);
$boxTypeName        = optional_param('boxTypeName', NULL, PARAM_TEXT);
$categoryTypeId     = optional_param('categoryTypeId', NULL, PARAM_INT);
$commiteditBoxType  = optional_param('commiteditBoxType', NULL, PARAM_INT);
$confirm            = optional_param('confirm', NULL, PARAM_INT);
$deleteBoxType      = optional_param('deleteBoxType', NULL, PARAM_INT);
$editBoxType        = optional_param('editBoxType', 0, PARAM_INT);
$isGaming           = optional_param('isGaming', NULL, PARAM_INT);
$newBoxType         = optional_param('newBoxType', NULL, PARAM_INT);
$originalName       = optional_param('originalName', NULL, PARAM_TEXT);

if (isset($deleteBoxType)) {
    deleteBoxType($boxTypeName, $boxTypeId);
}
if (isset($commiteditBoxType)) {
    editBoxType($boxTypeName, $isGaming, $active, $boxTypeId, $originalName, $categoryTypeId);

}
if (isset($addBoxType)) {
    addBoxType($boxTypeName, $categoryTypeId, $active);
}

echo $page->header('Boxtypes');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $addBoxType, $boxTypeId, $boxTypeName, $categoryTypeName, $editBoxType, $newBoxType, $UTILITY;

    $boxTypes = getboxTypes();
    if (!isset($newBoxType)) {
        echo "<div style='float: right; margin: 10px 500px 10px 5px;'><a class='button' href='?newBoxType=1'>NEW</a></div>\n";
    }
    echo "<form name ='sub' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "<table>\n";
    echo "  <thead>\n";
    echo "    <tr>\n";
    echo "      <th>Name</th>\n";
    echo "      <th>Category Type</th>\n";
    echo "      <th>Active</th>\n";
    echo "      <th>Action</th>\n";
    echo "    </tr>\n";
    echo "  </thead>\n";
    echo "  <tbody>\n";
    if ($newBoxType == 1) {
        echo "    <tr>\n";
        echo "      <td><input type='text' name='boxTypeName' id='boxTypeName' value=''/></td>\n";
        echo "      ".categoryType()."\n";
        echo "      <td>\n";
        echo "        <input type='checkbox' name='active' id='active' value='1'/>\n";
        echo "        <input type='hidden' name='addBoxType' value='Add New'/>\n";
        echo "      </td>\n";
        echo "      <td>\n";
        echo "        <a href='javascript: void(0);' onclick=\"javascript: if(validateBoxTypes()) {document.sub.submit();} else { return false;}\">Save </a>\n";
        echo "        <a href='".htmlentities($_SERVER['PHP_SELF'])."' />- Cancel\n";
        echo "      </td>\n";
        echo "    </tr>\n";

    }
    if (isset($boxTypes)) {
        foreach ($boxTypes as $row) {
            if ($editBoxType == $row['boxtypeid']) {
                echo "    <tr>\n";
                echo "        <input type='hidden' name='boxTypeId' value='".$row['boxtypeid']."'/>\n";
                echo "        <input type='hidden' name='originalName' value='".$row['boxtypename']."'/>\n";
                echo "      <td><input type='text' name='boxTypeName' id='boxTypeName' value='".$row['boxtypename']."'/></td>\n";
                echo "      ".categoryType()."\n";
                echo "      <td><input type='checkbox' name='active' id='active' value='1' ".$UTILITY->checked($row['active'])." /></td>\n";
                echo "      <td>\n";
                echo "        <input type='hidden' name='commiteditBoxType' value='Commit Edit'/>\n";
                echo "        <a href='javascript: void(0);' onclick=\"javascript: if(validateBoxTypes()) {document.sub.submit();} else { return false;}\">Save </a>\n";
                echo "        <a href='".htmlentities($_SERVER['PHP_SELF'])."' />- Cancel\n";
                echo "      </td>\n";
                echo "    </tr>\n";
            } else {
                if (isset($boxTypeId) && $boxTypeId == $row['boxtypeid'] || $boxTypeName == $row['boxtypename']) {
                    echo "    <tr>\n";
                } else {
                    echo "    <tr>\n";
                }
                echo "      <td>".$row['boxtypename']."</td>\n";
                echo "      <td>".$row['categorytypename']."</td>\n";
                echo "      <td><input type='checkbox'name='active' ".$UTILITY->checked($row['active'])." readonly disabled /></td>\n";
                echo "      <td>\n";
                echo "        <a href='?editBoxType=".$row['boxtypeid']."#editA".$row['boxtypeid']."'>Edit</a> - \n";
                echo "        <a href='?deleteBoxType=1&boxTypeName=".$row['boxtypename']."&boxTypeId=".$row['boxtypeid']."'\n";
                echo "          onclick=\"javascript: return confirm('Are you sure you want to permently delete the boxtype - ".$row['boxtypename']."')\">Delete</a>\n";
                echo "      </td>\n";
                echo "    </tr>\n";
            }
        }
    }

    echo "  </tbody>\n";
    echo "</table><br /><br />\n";
    echo "</form>\n";
    }


function categoryType() {
    global $UTILITY;

    echo "      <td>".getSelectDDM($UTILITY->getCategoryType(), "categoryTypeId", "categorytypeid", "categorytypename", NULL, NULL, "Select")."</td>\n";
}

function getboxTypes() {
    global $page;

    $sql = "
        SELECT bx.boxTypeName, bx.boxTypeId, bx.active,
               typ.categorytypename
          FROM boxTypes bx
          LEFT JOIN categorytypes typ ON typ.categorytypeid = bx.categorytypeid
         ORDER BY active DESC, boxTypeName COLLATE \"POSIX\"
    ";

      $boxTypes = $page->db->sql_query_params($sql);

      return $boxTypes;
}

function deleteBoxType($boxTypeName, $boxTypeId) {
    global $page;

    $success = FALSE;
    $sql = "
        DELETE FROM boxTypes WHERE boxTypeId = $boxTypeId
    ";
    $delete = $page->db->sql_execute_params($sql);
    if ($delete > 0) {
        $success = TRUE;
        $page->messages->addSuccessMsg('You have successfully deleted '.$boxTypeName);
    } else {
        $page->messages->addErrorMsg('Can not delete the box type- '.$boxTypeName);
    }

    return $success;

}

function editBoxType($boxTypeName, $isGaming, $active, $boxTypeId, $originalName, $categoryTypeId) {
    global $page, $USER, $UTILITY;

    if ($UTILITY->checkDuplicateName('boxTypes', 'boxTypeName', $boxTypeName, $originalName) == FALSE) {

        $success = FALSE;
        ($active == 0 ? $active = 0 : $active = 1);
        ($isGaming == 0 ? $isGaming = 0 : $isGaming = 1);

        $sql = "UPDATE boxTypes
                   SET boxTypeName      = :boxTypeName,
                       categoryTypeId   = :categoryTypeId,
                       active           = :active,
                       modifiedBy       = :modifiedBy
                 WHERE boxTypeId        = :boxTypeId
        ";
        $params = array();
        $params['boxTypeId']        = $boxTypeId;
        $params['boxTypeName']      = $boxTypeName;
        $params['categoryTypeId']   = $categoryTypeId;
        $params['active']           = $active;
        $params['modifiedBy']       = $USER->userId;

        $result = $page->db->sql_execute_params($sql, $params);
        if ($result > 0) {
            $success = TRUE;
            $page->messages->addSuccessMsg('You have successfully edited '.$boxTypeName);
        } else {
        $page->messages->addErrorMsg('Error?- '.$boxTypeName);
        }

        return $success;
    } else {
        $page->messages->addErrorMsg("The Boxtype name (".$boxTypeName.") is already in use.");
    }

}

function addBoxType($boxTypeName, $categoryTypeId, $active) {
    global $page;
    global $USER;
    global $UTILITY;

    if ($UTILITY->checkDuplicateName('boxTypes', 'boxTypeName', $boxTypeName) == FALSE) {
        $success = FALSE;
        ($active == 0 ? $active = 0 : $active = 1);

        $sql = "INSERT INTO boxTypes( boxTypeName,  categoryTypeId,  active , createdBy)
                              VALUES(:boxTypeName, :categoryTypeId, :active, :createdBy)
        ";
        $params = array();
        $params['boxTypeName']      = $boxTypeName;
        $params['categoryTypeId']   = $categoryTypeId;
        $params['active']           = $active;
        $params['createdBy']        = $USER->userId;

        $result = $page->db->sql_execute_params($sql, $params);
        if ($result > 0) {
            $success = TRUE;
            $page->messages->addSuccessMsg('You have successfully added '.$boxTypeName);
        } else {
        $MESSAGES->addErrorMsg('Error?- '.$boxTypeName);
        }
        return $result;
    } else {
        $page->messages->addErrorMsg("The Boxtype name (".$boxTypeName.") is already in use.");
    }

}
?>