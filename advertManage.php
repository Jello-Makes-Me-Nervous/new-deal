<?php
require_once('templateAdmin.class.php');

$page = new templateAdmin(LOGIN, SHOWMSG);

$active             = optional_param('active', NULL, PARAM_INT);
$addNew             = optional_param('addNew', NULL, PARAM_INT);
$advertClassId      = optional_param('advertClassId', NULL, PARAM_INT);
$advertClassName    = optional_param('advertClassName', NULL, PARAM_TEXT);
$advertId           = optional_param('advertId', NULL, PARAM_INT);
$advertName         = optional_param('advertName', NULL, PARAM_TEXT);
$commitEditAdvertId = optional_param('commitEditAdvertId', NULL, PARAM_INT);
$confirm            = optional_param('confirm', NULL, PARAM_INT);
$deleteAdvert       = optional_param('deleteAdvert', NULL, PARAM_INT);
$editAdvertId       = optional_param('editAdvertId', NULL, PARAM_INT);
$getAdvertId        = optional_param('getAdvertId', 0, PARAM_INT);
$getNew             = optional_param('getNew', NULL, PARAM_INT);
$imageUp            = optional_param('imageUp', NULL, PARAM_FILE);
$selectName         = optional_param('selectName', NULL, PARAM_TEXT);
$url                = optional_param('url', NULL, PARAM_TEXT);

$advertClassDDMdata = getAdvertClassdata();
$target = $CFG->advertPath;

if ($deleteAdvert == 1) {
    deleteAdvert($advertId, $advertClassName, $advertName);
}
if (isset($commitEditAdvertId)) {
    editAdvert($advertId, $active, $advertName);
}
if ($getNew == 1) {
    $imageUp = $_FILES["imageUp"];
    addNew($getAdvertId, $advertName, $active, $target, $imageUp, $url);
}
///only get the select if needed
if ($addNew == 1 || !empty($editAdvertId)){
    $editAddSelect= getSelectDDM($advertClassDDMdata, "getAdvertId", "advertclassid", "advertclassname", NULL, $getAdvertId, "Select");
}

echo $page->header('Advert Manage');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $CFG, $page, $addNew, $advertClassDDMdata, $advertClassName, $commitEditAdvertId, $deleteAdvert, $editAdvertId, $editAddSelect, $getAdvertId;


    $onChangeScript = "onChange=\"javascript: this.form.submit();\"";
    $mySelect= getSelectDDM($advertClassDDMdata, "getAdvertId", "advertclassid", "advertclassname", NULL, $getAdvertId, "Select", NULL, NULL, NULL, $onChangeScript);

    echo "<form action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "<strong>Choose An Area To Manage:</strong> ".$mySelect;
    echo "<input type='hidden' name='test' value='YO!'/>\n";
    echo "</form>\n";
    if ($getAdvertId){
        $subategories = getAdvertManage($getAdvertId);
        echo "<form name='sub' action='".htmlentities($_SERVER['PHP_SELF'])."#A' method='post' enctype='multipart/form-data'>\n";
        if (!isset($addNew)) {
            echo "<div style='float: right; margin: 0px 500px 10px 5px;'>\n";
            echo "  <a class='button' href='?addNew=1&getAdvertId=".$getAdvertId."'>NEW</a>\n";
            echo "</div>\n";
        }
        echo "<table>\n";
        echo "  <thead>\n";
        echo "    <tr>\n";
        echo "      <th>ID</th>\n";
        echo "      <th>Class Name</th>\n";
        echo "      <th>Advert Name</th>\n";
        echo "      <th>URL</th>\n";
        echo "      <th>Advert Image</th>\n";
        if ($addNew) {
            echo "      <th>Choose File</th>\n";
        }
        echo "      <th>active</th>\n";
        echo "      <th>Action</th>\n";
        echo "    </tr>\n";
        echo "  </thead>\n";
        echo "  <tbody>\n";
        if ($addNew == 1) {
            echo "    <tr>\n";
            echo "      <td></td>\n";
            echo "      <td>".$advertClassName."\n";
            echo "        <input type='hidden' name='getAdvertId' value='".$getAdvertId."'/>\n";
            echo "      </td>\n";
            echo "      <td><input type='text' name='advertName' value=''/></td>\n";
            echo "      <td><input type='text' name='url' value=''/></td>\n";
            echo "      <td colspan='2'><input type='file' name='imageUp' id='imageUp'><br />(gif & jpg only - Max:".(round(($CFG->IMG_MAX_UPLOAD/1000000),2))."MB)</td>\n";
            echo "      <td>\n";
            echo "        <input type='checkbox' name='active' value='1' checked/>\n";
            echo "        <input type='hidden' name='getNew' value='1'/>\n";
            echo "      </td>\n";
            echo "      <td class='fa-action-items'><a class='fas fa-check-circle' title='Save' href='' onclick=\"javascript:document.sub.submit();return false;\"></a><a class='fas fa-times-circle' title='Cancel' href='?getAdvertId=".$getAdvertId."' /></td>\n";
            echo "    </tr>\n";
        }
        if (isset($subategories) > 0) {
            foreach ($subategories as $row) {
                if (isset($advertId) && $advertId == $row['advertid'] || isset($advertName) && $advertName == $row['advertname'] ) {
                    echo "<tr id='A".$advertId."' style='background-color: #C8F5E0'>\n";
                } else {
                    echo "<tr>\n";
                }
                echo "      <td>".$row['advertid']."</td>\n";
                echo "      <td>".$row['advertclassname']."</td>\n";
                echo "      <td>".$row['advertname']."</td>\n";
                echo "      <td>".$row['url']."</td>\n";
                echo "      <td><img src='".$page->utility->getPrefixAdvertImageURL($row['originalpath'])."'></td>\n";
                echo "      <td><input type='checkbox' ".checked($row['active'])." disabled /></td>\n";
                echo "      <td class='fa-action-items'>\n";
                if ($row['active'] > 0) {
                    echo "<a class='fas fa-toggle-off' title='Deactivate' href='/advertManage.php?commitEditAdvertId=1&active=0&getAdvertId=".$getAdvertId."&advertId=".$row['advertid']."&advertName=".$row['advertname']."#editA".$row['advertid']."'></a> - \n";
                } else {
                    echo "<a class='fas fa-toggle-on' title='Activate' href='/advertManage.php?commitEditAdvertId=1&active=1&getAdvertId=".$getAdvertId."&advertId=".$row['advertid']."&advertName=".$row['advertname']."#editA".$row['advertid']."'></a> - \n";
                }
                echo "        <a  class='fas fa-trash-alt' title='Delete' href='/advertManage.php?deleteAdvert=1&getAdvertId=".$getAdvertId."&advertClassName=".$row['advertclassname']."&advertId=".$row['advertid']."&advertName=".$row['advertname']."'\n";
                echo "          onclick=\"javascript: return confirm('Are you sure you want to permanently delete - ".$row['advertname']." and all assocciated files')\"></a>\n";
                echo "      </td>\n";
                echo "    </tr>\n";
            }
        }
        echo "  </tbody>\n";
        echo "</table><br /><br />\n";
        echo "</form>\n";
    }
}


function getAdvertClassdata() {
    global $page;

    $sql = "
        SELECT advertClassId, advertClassName
          FROM advertClass
         WHERE active = 1
         ORDER BY advertClassName
    ";
    $rs = $page->db->sql_query_params($sql);

    return $rs;
}

function getAdvertManage($getAdvertId) {
    global $page;

    $sql = "
        SELECT cls.advertclassname, cls.advertclassid, am.advertname,
               am.classpath || am.originalpath as originalpath,
               am.advertid, am.active, am.url
          FROM advertManage         am
          JOIN advertclass          cls on cls.advertclassid = am.advertclassid
         WHERE cls.advertClassId = ".$getAdvertId."
         ORDER BY am.active DESC, am.advertname
    ";
    $categories = $page->db->sql_query_params($sql);

    return $categories;
}

function deleteAdvert($advertId, $advertClassName, $advertName) {
    global $page;
    $success = FALSE;

    $sql = "
        DELETE FROM advertManage
         WHERE advertid = ".$advertId;

    $result = $page->db->sql_execute_params($sql);
    if ($result > 0) {
        $success = TRUE;
        $page->messages->addSuccessMsg('You have deleted - '.$advertName);
    } else {
        $page->messages->addErrorMsg('Error?');
    }

    return $success;
}

function checked($checked){
    if ($checked == 1) {
        $checked = "checked";
    } elseif ($checked == 0) {
        $checked = "";
    }
    return $checked;
}

function editAdvert($advertId, $active, $advertName) {
    global $page;

    $success = NULL;

    $active     = (empty($active)) ?  0 : 1;
    $activated  = (empty($active)) ? "Deactivated" : "Activated";

    $sql = "
        UPDATE advertManage
           SET active       = ".$active."
               ,modifiedby   = '".$page->user->username."'
               ,modifydate   = nowtoint()
         WHERE advertId = ".$advertId;

    $result = $page->db->sql_execute_params($sql);
    if ($result > 0) {
        $success = TRUE;
        $page->messages->addSuccessMsg('You have '.$activated.' - '.$advertName);
    } else {
        $page->messages->addErrorMsg('Error: updating db record.');
    }

    return $success;
}

function addNew($advertClassId, $advertName, $active, $target, array $imageUp, $url) {
    global $page;

    $success = NULL;

    //get the name for the path
    $sql = "
        SELECT advertClassName
          FROM advertClass
         WHERE advertClassId = ".$advertClassId;
    $row = $page->db->sql_query_params($sql);
    $advertClassName = $row[0]['advertclassname'];

    $sql = "SELECT nextval('advertmanage_advertid_seq')";
    $advertId = $page->db->get_field_query($sql);

    //make the path
    $path = $target.$advertClassName."/";

    //upload the image
    $img = prefixImgUp($imageUp, $advertId, $path, $page);

    $sql = "
        INSERT INTO advertmanage(advertclassid, advertname, classpath, originalpath, active, url, createdBy )
                          VALUES(:advertClassId, :advertName, :classPath, :originalPath, :active, :url, :createdBy)
    ";
    $params = array();
    $params['advertClassId']    = $advertClassId;
    $params['advertName']       = $advertName;
    $params['classPath']        = $advertClassName."/";
    $params['originalPath']     = $img;
    $params['active']           = 1;
    $params['url']              = $url;
    $params['createdBy']        = $page->user->username;

    $result = $page->db->sql_execute_params($sql, $params);

    if ($result > 0) {
        $success = TRUE;
        $page->messages->addSuccessMsg('You have added - '.$advertName);
    } else {
        $page->messages->addErrorMsg('Error: unable to add advertising record.');
    }

    return  $success;
}

?>