<?php
require_once('template.class.php');

$page = new template(LOGIN, SHOWMSG);
$page->requireJS('scripts/validateAdvertClass.js');

$target = $CFG->advert;

$active                 = optional_param('active', NULL, PARAM_INT);
$addAdvertClassName     = optional_param('addAdvertClassName', NULL, PARAM_ALPHA);
$advertClassId          = optional_param('advertClassId', NULL, PARAM_INT);
$advertClassName        = optional_param('advertClassName', NULL, PARAM_TEXT);
$commiteditAdvertClass  = optional_param('commiteditAdvertClass', NULL, PARAM_INT);
$confirm                = optional_param('confirm', NULL, PARAM_INT);
$deleteAdvertClass      = optional_param('deleteAdvertClass', NULL, PARAM_INT);
$editAdvertClass        = optional_param('editAdvertClass', 0, PARAM_INT);
$maxHeight              = optional_param('maxHeight', NULL, PARAM_INT);
$maxWidth               = optional_param('maxWidth', NULL, PARAM_INT);
$newAdvertClass         = optional_param('newAdvertClass', NULL, PARAM_INT);
$originalName           = optional_param('originalName', NULL, PARAM_TEXT);

if ($deleteAdvertClass == 1) {
    deleteAdvertClass($advertClassName, $advertClassId, $target);
}
if (isset($commiteditAdvertClass)) {
    editAdvertClass($advertClassName, $maxHeight, $maxWidth, $active, $advertClassId, $originalName, $target);
}
if (isset($addAdvertClassName)) {
    addAdvertClassName($advertClassName, $maxHeight, $maxWidth, $active, $target);
}

echo $page->header('Advert Classes');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $addAdvertClassName, $advertClassId, $advertClassName, $editAdvertClass, $newAdvertClass, $UTILITY;

    if (!isset($newAdvertClass)) {
        echo "<div style='float: right; margin: 0px 500px 5px 5px;'><a href='?newAdvertClass=1'>NEW</a></div>\n";
    }
    $advertClass = getAdvertClass();
    echo "<form name = 'sub' action='".htmlentities($_SERVER['PHP_SELF'])."#A".$advertClassId."' method='post' onsubmit='return validateAdvertClass()'>\n";///added anchor?
    echo "  <table>\n";
    echo "    <thead>\n";
    echo "      <tr>\n";
    echo "        <th>ID</th>\n";
    echo "        <th>Name</th>\n";
//echo "        <th>Max Height</th>\n";
//echo "        <th>Max Width</th>\n";
    echo "        <th>Active</th>\n";
    echo "        <th>Action</th>\n";
    echo "      </tr>\n";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    if ($newAdvertClass == 1) {
        echo "      <tr>\n";
        echo "        <td></td>\n";
        echo "        <td><input type='text' name='advertClassName' id='advertClassName' value=''/></td>\n";
//echo "        <td><input type='tel' name='maxHeight' id='maxHeight' value=''/></td>\n";
//echo "        <td><input type='tel' name='maxWidth' id='maxWidth' value=''/></td>\n";
        echo "        <td>\n";
        echo "          <input type='checkbox' name='active' value='1'/>\n";
        echo "          <input type='hidden' name='addAdvertClassName' value='Add New'/>\n";
        echo "        </td>\n";
        echo "        <td>\n";
        echo "          <a href='javascript: void(0);' onclick=\"javascript: document.sub.submit();\">Save </a>\n";
//echo "          <a href='javascript: void(0);' onclick=\"javascript: if(validateAdvertClass()) {document.sub.submit();} else { return false;}\">Save </a>\n";
        echo "          <a href='".htmlentities($_SERVER['PHP_SELF'])."' />- Cancel</td>\n";
        echo "        </td>\n";
        echo "      </tr>\n";
    }
    if (isset($advertClass)) {
        foreach ($advertClass as $row) {
            if ($editAdvertClass == $row['advertclassid']) {
                echo "      <tr style='background-color: #CCC;' id='editA".$row['advertclassid']."'>\n";
                echo "        <td>".$row['advertclassid']."\n";
                echo "          <input type='hidden' name='advertClassId' value='".$row['advertclassid']."'/>\n";
                echo "          <input type='hidden' name='originalName' value='".$row['advertclassname']."'/>\n";
                echo "        </td>\n";
                echo "        <td><input type='text' name='advertClassName' id='advertClassName' value='".$row['advertclassname']."'/></td>\n";
//echo "        <td><input type='tel' name='maxHeight' id='maxHeight' value='".($row['maxheight'])."' /></td>\n";
//echo "        <td><input type='tel' name='maxWidth' id='maxWidth' value='".$UTILITY->checked($row['maxwidth'])."' /></td>\n";
                echo "        <td><input type='checkbox' name='active' value='1' ".$UTILITY->checked($row['active'])." /></td>\n";
                echo "        <td>\n";
                echo "          <input type='hidden' name='commiteditAdvertClass' value=Commit Edit/>\n";
                echo "          <a href='javascript: void(0);' onclick=\"javascript: if(validateAdvertClass()) {document.sub.submit();} else { return false;}\">Save </a>\n";
                echo "          <a href='".htmlentities($_SERVER['PHP_SELF'])."' />- Cancel</td>\n";
                echo "        </td>\n";
                echo "      </tr>\n";
            } else {
        ///////////////////////////Anchor and background//////////////////////////////////////////
                if (isset($advertClassId) && $advertClassId == $row['advertclassid'] || $advertClassName == $row['advertclassname']) {
                    echo "      <tr id='A".$advertClassId."'>\n";
                } else {
                    echo "      <tr>\n";
                }
                echo "         <td>".$row['advertclassid']."</td>\n";
                echo "         <td>".$row['advertclassname']."</td>\n";
//echo "         <td>".($row['maxheight'])."</td>\n";
//echo "         <td>".($row['maxwidth'])."</td>\n";
                echo "         <td><input type='checkbox'name='active' ".$UTILITY->checked($row['active'])." readonly disabled /></td>\n";
                echo "         <td>\n";
                echo "           <a href='?editAdvertClass=".$row['advertclassid']."' class='fa-edit'>Edit</a> - \n";
                echo "           <a href='?deleteAdvertClass=1&advertClassName=".$row['advertclassname']."&advertClassId=".$row['advertclassid']."'\n";
                echo "             onclick=\"javascript: return confirm('Are you sure you want to permently delete the AdvertClass - ".$row['advertclassname']."')\">Delete</a>\n";
                echo "         </td>\n";
                echo "      </tr>\n";
            }
        }
    }

    echo "    </tbody>\n";
    echo "  </table><br /><br />\n";
    echo "</form>\n";
    //JAVASCRIPT///////////////////////////////////////////////////////////////////////
    echo "<script src='scripts/validateAdvertClass.js'></script>\n";//required
    ////////////////////////////////////////////////////////////////javascript//////
}

function getAdvertClass() {
    global $page;

    $sql = "
        SELECT advertClassName, advertClassId, maxHeight, maxWidth, active
          FROM AdvertClass
         ORDER BY active DESC, advertClassName
    ";

    $advertClass = $page->db->sql_query_params($sql);

    return $advertClass;
}

function deleteAdvertClass($advertClassName, $advertClassId, $target) {
    global $page;
    $success = FALSE;

    $files = glob($target.$advertClassName. '/*');
//var_dump($files);
//unlinks not working on my pc//////////////
    foreach($files as $file) {
        unlink($file);
    }
    unlink($target.$advertClassName. '/');

    $sql = "DELETE FROM AdvertClass WHERE advertClassId = $advertClassId";

    $delete = $page->db->sql_execute_params($sql);

    if ($delete > 0) {
        $success = TRUE;
        $page->messages->addSuccessMsg('You have successfully deleted '.$advertClassName);
    } else {
        $page->messages->addErrorMsg('Error deletion was not completed?');
    }

    return $success;

}


function editAdvertClass($advertClassName, $maxHeight, $maxWidth, $active, $advertClassId, $originalName, $target) {
    global $page;
    global $USER;
    global $UTILITY;
    $success = FALSE;

    if ($UTILITY->checkDuplicateName('AdvertClass', 'advertClassName', $advertClassName, $originalName) == FALSE) {

        ($active == 0 ? $active = 0 : $active = 1);

        $sql = "
            UPDATE AdvertClass
               SET advertClassName  = :advertClassName,
                   maxHeight        = :maxHeight,
                   maxWidth         = :maxWidth,
                   active           = :active,
                   modifiedBy       = :modifiedBy
             WHERE advertClassId    = :advertClassId
        ";
        $params = array();
        $params['advertClassId']    = $advertClassId;
        $params['advertClassName']  = $advertClassName;
        $params['maxHeight']        = $maxHeight;
        $params['maxWidth']         = $maxWidth;
        $params['active']           = $active;
        $params['modifiedBy']       = $USER->userId;

        $result = $page->db->sql_execute_params($sql, $params);

        if ($result > 0) {
            rename($target.$originalName.'/',$target.$advertClassName.'/');
            $success = TRUE;
            $page->messages->addSuccessMsg('You have edited the the box type- '.$advertClassName);
        } else {
            $page->messages->addErrorMsg('Error?');
        }

        return $success;

    } else {
        $page->messages->addErrorMsg("The Advert Class name (".$advertClassName.") is already in use.");
    }
}

function addAdvertClassName($advertClassName, $maxHeight, $maxWidth, $active, $target) {
    global $page;
    global $USER;
    global $UTILITY;

    $success = FALSE;
    if ($UTILITY->checkDuplicateName('AdvertClass', 'advertClassName', $advertClassName) == FALSE) {

        ($active == 0 ? $active = 0 : $active = 1);
        $advertClassPath = $target.$advertClassName."/";

        $sql = "
            INSERT INTO AdvertClass( advertClassName,  maxHeight,  maxWidth,  active,  createdBy,  advertClassPath)
                             VALUES(:advertClassName, :maxHeight, :maxWidth, :active, :createdBy, :advertClassPath)
        ";
        $params = array();
        $params['advertClassName']  = $advertClassName;
        $params['maxHeight']        = $maxHeight;
        $params['maxWidth']         = $maxWidth;
        $params['active']           = $active;
        $params['createdBy']        = $page->user->userId;
        $params['advertClassPath']  = $advertClassPath;

        $result = $page->db->sql_execute_params($sql, $params);
        if ($result > 0) {
            $success = TRUE;
            mkdir($advertClassPath,0777,TRUE);
            mkdir($advertClassPath."/thumbs",0777,TRUE);
            mkdir($advertClassPath."/originals",0777,TRUE);
            $page->messages->addSuccessMsg('You have added the Advert Class - '.$advertClassName);
        } else {
            $page->messages->addErrorMsg('Error?');
        }

        return $success;

    } else {
        $page->messages->addErrorMsg("The Advert Class name (".$advertClassName.") is already in use.");
    }

}
////////////////////////////////////////////////////////////////////////////////
?>