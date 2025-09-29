<?php
require_once('templateAdmin.class.php');

$page = new templateAdmin(LOGIN, SHOWMSG);
$page->requireJS('scripts/validateVersions.js');

$active                = optional_param('active', NULL, PARAM_INT);
$addVersion            = optional_param('addversion', 0, PARAM_INT);
$versionId             = optional_param('productversionid', NULL, PARAM_INT);
$versionName           = optional_param('versionname', NULL, PARAM_TEXT);
$originalName          = optional_param('originalname', NULL, PARAM_TEXT);
$versionDescription    = optional_param('versiondescription', NULL, PARAM_CLEANHTML);
$versionNotes          = optional_param('versionnote', NULL, PARAM_CLEANHTML);
$commitEditVersion     = optional_param('commiteditversion', 0, PARAM_INT);
$confirm               = optional_param('confirm', NULL, PARAM_INT);
$deleteVersion         = optional_param('deleteversion', 0, PARAM_INT);
$editVersion           = optional_param('editversion', 0, PARAM_INT);
$newVersion            = optional_param('newversion', NULL, PARAM_INT);

if ($page->user->isAdmin()) {
    if ($deleteVersion == 1) {
        deleteVersion($versionId);
    }
    if ($commitEditVersion == 1) {
        editVersion($versionId, $versionName, $versionDescription, $versionNotes, $active, $originalName);
    }
    if ($addVersion == 1) {
        addVersion($versionName, $versionDescription, $versionNotes, $active);
    }
}

echo $page->header('Product Versions');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $page, $productVersionId, $editVersion, $versionName, $newVersion, $USER;

    $versions = getVersions();
    echo "<article>\n";
    echo "  <div class='entry-content'>\n";
    if (!isset($newVersion)) {
        echo "    <div style='float: left; margin: 10px 5px 20px 5px;'><a class='button' href='/productVersions.php?newversion=1'>NEW</a></div>\n";
    }
    echo "    <form name = 'sub' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "      <table>\n";
    echo "        <thead>\n";
    echo "          <tr>\n";
    echo "            <th>ID</th>\n";
    echo "            <th>Name</th>\n";
    echo "            <th>Description</th>\n";
    echo "            <th>Notes</th>\n";
    echo "            <th>Active</th>\n";
    echo "            <th>References</th>\n";
    echo "            <th>Action</th>\n";
    echo "          </tr>\n";
    echo "        </thead>\n";
    echo "        <tbody>\n";
    if ($newVersion == 1) {
        echo "          <tr>\n";
        echo "            <td><input type='hidden' name='addversion' id='addversion' value='1'>Add</td>\n";
        echo "            <td><input type='text' name='versionname' id='versionname' value=''></td>\n";
        echo "            <td><input type='text' name='versiondescription' id='versiondescription' value=''></td>\n";
        echo "            <td><input type='text' name='versionnote' id='versionnote' value=''></td>\n";
        echo "            <td><input type='checkbox' name='active' id='active' value='1' checked /></td>\n";
        echo "            <td class='number'>0</td>\n";
        echo "            <td>\n";
        echo "              <a href='javascript: void(0);' onclick=\"javascript: if(validateVersions()) {document.sub.submit();} else { return false;}\">Save </a>\n";
        echo "              <a href='".htmlentities($_SERVER['PHP_SELF'])."' />- Cancel\n";
        echo "            </td>\n";
        echo "          </tr>\n";
    }
    if (isset($versions)) {
        foreach ($versions as $row) {
            if ($editVersion == $row['productversionid']) {
                echo "          <tr>\n";
                echo "            <td>".$row['productversionid']."\n";
                echo "              <input type='hidden' name='productversionid' id='productversionid' value='".$row['productversionid']."'>\n";
                echo "              <input type='hidden' name='originalname' id='originalname' value='".$row['versionname']."'>\n";
                echo "            </td>\n";
                echo "            <td><input type='text' name='versionname' id='versionname' value='".$row['versionname']."'></td>\n";
                echo "            <td><input type='text' name='versiondescription' id='versiondescription' value='".htmlspecialchars($row['versiondescription'], ENT_QUOTES, 'UTF-8')."'></td>\n";
                echo "            <td><input type='text' name='versionnote' id='versionnote' value='".htmlspecialchars($row['versionnote'], ENT_QUOTES, 'UTF-8')."'></td>\n";
                echo "            <td><input type='checkbox' name='active' id='active' value='1' ".$page->utility->isChecked($row['active'], 1, "checked")." ></td>\n";
                echo "            <td class='number'>".$row['numproducts']."</td>\n";
                echo "            <td>\n";
                echo "              <input type='hidden' name='commiteditversion' id='commiteditversion' value='1'>\n";
                echo "              <a href='javascript: void(0);' onclick=\"javascript: if(validateVersions()) {document.sub.submit();} else { return false;}\">Save </a>\n";
                echo "              <a href='".htmlentities($_SERVER['PHP_SELF'])."' />- Cancel\n";
                echo "            </td>\n";
                echo "            </td>\n";
                echo "          </tr>\n";
            } else {
                echo "          <tr>\n";
                echo "            <td class='number'>".$row['productversionid']."</td>\n";
                echo "            <td>".stripslashes($row['versionname'])."</td>\n";
                echo "            <td>".stripslashes($row['versiondescription'])."</td>\n";
                echo "            <td>".stripslashes($row['versionnote'])."</td>\n";
                echo "            <td><input type='checkbox'name='active' id='active' ".$page->utility->isChecked($row['active'], 1, "checked")." readonly disabled></td>\n";
                echo "            <td class='number'>".$row['numproducts']."</td>\n";
                echo "            <td>\n";
                echo "              <a href='/productVersions.php?editversion=".$row['productversionid']."#editA".$row['productversionid']."' class=' fas fa-edit'></a>\n";
                if (empty($row['numproducts'])) {
                    echo "              <a href='/productVersions.php?deleteversion=1&productversionid=".$row['productversionid']."'\n";
                    echo "                onclick=\"javascript: return confirm('Are you sure you want to permently delete the version - ".$row['versionname']."')\" class=' fas fa-trash'></a>\n";
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

function getVersions() {
    global $page;

    $sql = "
        SELECT pv.productversionid, pv.versionname, pv.versiondescription, pv.active, pv.versionnote, count(p.productid) AS numproducts
        FROM productversions pv
        LEFT JOIN products p ON p.productversionid=pv.productversionid
        GROUP BY pv.productversionid, pv.versionname, pv.versiondescription, pv.active, pv.versionnote
        ORDER BY pv.active DESC, pv.versionname COLLATE \"POSIX\"
    ";

      $versions = $page->db->sql_query_params($sql);
      return $versions;
}

function deleteVersion($versionId) {
    global $page;

    $success = FALSE;
    $sql = "
        DELETE FROM productversions WHERE productversionid = ".$versionId."
    ";

    try {
        $page->db->sql_execute_params($sql);
        $page->messages->addSuccessMsg("Product Version deleted.");
        $success = TRUE;
    } catch (Exception $e) {
        $page->messages->addErrorMsg("ERROR: ".$e->getMessage()." [Unable to delete product version.]");
        $success = FALSE;
    }

    return $success;

}

function editVersion($versionId, $versionName, $versionDescription, $versionNotes, $active, $originalName) {
    global $page;

    if ($page->utility->checkDuplicateName("productversions", "versionname", $versionName, $originalName) == FALSE) {

        $success    = FALSE;
        $active     = (empty($active)) ?  0 : 1;

        $sql = "
            UPDATE productversions
               SET versionname          = :versionname,
                   versiondescription   = :versiondescription,
                   versionnote         = :versionnote,
                   active               = :active,
                   modifydate           = nowtoint(),
                   modifiedby           = :modifiedby
             WHERE productversionid     = :productversionid
        ";
        $params = array();
        $params['productversionid']        = $versionId;
        $params['versionname']             = $versionName;
        $params['versiondescription']      = $versionDescription;
        $params['versionnote']            = $versionNotes;
        $params['active']                  = $active;
        $params['modifiedby']              = $page->user->username;

        try {
            $page->db->sql_execute_params($sql, $params);
            $page->messages->addSuccessMsg("Product version saved.");
            $success = TRUE;
        } catch (Exception $e) {
            $page->messages->addErrorMsg("ERROR: ".$e->getMessage()." [Unable to update product version.]");
            $success = FALSE;
        }

    } else {
        $page->messages->addErrorMsg("The Product Version name (".$versionName.") is already in use.");
        $success = FALSE;
    }

    return $success;
}

function addVersion($versionName, $versionDescription, $versionNotes, $active) {
    global $page;

    $success    = FALSE;
    if ($page->utility->checkDuplicateName("productversions", "versionname", $versionName) == FALSE) {
        $active     = (empty($active)) ?  0 : 1;

        $sql = "
            INSERT INTO productversions( versionname,  versiondescription, versionnote,  active,  createdby,  modifiedby)
                            VALUES(:versionname, :versiondescription, :versionnote, :active, :createdby, :modifiedby)
        ";
        $params = array();
        $params['versionname']             = $versionName;
        $params['versiondescription']      = $versionDescription;
        $params['versionnote']            = $versionNotes;
        $params['active']                  = $active;
        $params['createdby']               = $page->user->username;
        $params['modifiedby']              = $page->user->username;

        try {
            $page->db->sql_execute_params($sql, $params);
            $page->messages->addSuccessMsg("Product Version created.");
            $success = TRUE;
        } catch (Exception $e) {
            $page->messages->addErrorMsg("ERROR: ".$e->getMessage()." [Unable to create product version.]");
            $success = FALSE;
        }
    } else {
        $page->messages->addErrorMsg("The Product Version name (".stripslashes($versionName).") is already in use.");
    }

    return $success;
}
?>