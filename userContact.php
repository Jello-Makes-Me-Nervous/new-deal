<?php
include('setup.php');

$messages = new Messages();
/*
 *assuming this info is collected on creation
*/
///////////////////////////////////////////////////////////////////
//till we add a user the id is 1
$userId = 9;
///////////////////////////////////////

$addressTypeId           = optional_param_array('addresstypeid', NULL, PARAM_RAW);//not int because of the _
$altphone                = optional_param('altphone', NULL, PARAM_RAW);
$city                    = optional_param('city', NULL, PARAM_RAW);
$commitEditContactInfo   = optional_param('commitEditContactInfo', NULL, PARAM_RAW);
$companyName             = optional_param('companyName', NULL, PARAM_RAW);
$confirm                 = optional_param('confirm', NULL, PARAM_INT);
$country                 = optional_param('country', NULL, PARAM_RAW);
$deleteContactInfo       = optional_param('deleteContactInfo', NULL, PARAM_RAW);
$edit                    = optional_param('edit', NULL, PARAM_INT);
$email                   = optional_param('email', NULL, PARAM_RAW);
$fax                     = optional_param('fax', NULL, PARAM_RAW);
$phone                   = optional_param('phone', NULL, PARAM_RAW);
$state                   = optional_param('state', NULL, PARAM_RAW);
$street                  = optional_param('street', NULL, PARAM_RAW);
$street2                 = optional_param('street2', NULL, PARAM_RAW);
$typeId                  = optional_param('typeId', NULL, PARAM_RAW);
$zip                     = optional_param('zip', NULL, PARAM_RAW);
$userContactId           = optional_param('userContactId', NULL, PARAM_INT);

$createdBy = "user";
$modifiedBy = "user";

if (isset($commitEditContactInfo)) {

queries($addressTypeId, $companyName, $phone, $altphone, $fax, $email, $street, $street2, $city, $state, $zip, $country, $userId, $createdBy);
}
/////////////////////////////////////////////
if ($deleteContactInfo == 1) {
    deleteUserContactInfo($userContactId);
}
echo $messages->displayMessages();

echo "<form id='sub' name ='subx' action='".htmlentities($_SERVER['PHP_SELF'])."#A".$userContactId."' method='post'>\n";
if ($edit == 1) {
    $row = getContactInfo($userId, $userContactId, $typeId);
    if (!empty($row)) {
        $row = reset($row);
    }
    echo "  <table border='1' style='width: 80%;'>\n";
    echo "    <thead>\n";
    echo "      <th colspan='2'>".strtoupper($row['addresstypename'])."\n";
    echo "      <div style='float: right;padding-right: 50;'>\n";
    echo "         <a href='' onclick=\"javascript:document.subx.submit();return false;\">SAVE</a>&nbsp - &nbsp\n";
    echo "         <a href='?'/>CANCEL</a>\n";
    echo "        </div>\n";
    echo "      </th>\n";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    echo "      <tr>\n";
    echo "        <td>Company Name: </td>\n";
    echo "        <td>\n";
    echo "          <input type='text' name='companyName' value='".ucfirst($row['companyname'])."'/>\n";
    echo "          <input type='hidden' name='commitEditContactInfo' value='1'/>\n";
    echo "          <input type='hidden' name='userContactId' value='".$row['usercontactid']."'/>\n";
    echo "        </td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Phone #: </td>\n";
    echo "        <td><input type='text' name='phone' value='".$row['phone']."'/></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Aletrnate Phone #: </td>\n";
    echo "        <td><input type='text' name='altphone' value='".$row['altphone']."'/></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Fax #: </td>\n";
    echo "        <td><input type='text' name='fax' value='".$row['fax']."'/></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Email: </td>\n";
    echo "        <td><input type='text' name='email' value='".$row['email']."'/></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Street: </td>\n";
    echo "        <td><input type='text' name='street' value='".$row['street']."'/></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Street 2: </td>\n";
    echo "        <td><input type='text' name='street2' value='".$row['street2']."'/></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>City: </td>\n";
    echo "        <td><input type='text' name='city' value='".$row['city']."'/></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>State: </td>\n";
    echo "        <td><input type='text' name='state' value='".$row['state']."'/></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Zip: </td>\n";
    echo "        <td><input type='text' name='zip' value='".$row['zip']."'/></td>\n";
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Country: </td>\n";
    echo "        <td><input type='text' name='country' value='".$row['country']."'/></td>\n";
    echo "      </tr>\n";
    echo "    </tbody>\n";
    echo "  </table><br />\n";
    echo "   Select all categories where you would like to save this info\n";
    $data = getAddressTypes($row['addresstypeid'], $userId);
    echo getCheckBox($data, 'addresstypeid', "at_uc_id", "addresstypename", "checked", $typeId);
}
echo "</form>\n";

if ($edit < 1) {
    $row = getContactInfo($userId);
    foreach ($row as $row) {
        echo "<table border='1' style='width: 80%;'>\n";
        echo "  <thead>\n";
        echo "    <th colspan='2'>".strtoupper($row['addresstypename'])."\n";
        echo "      <div style='float: right;padding-right: 50;'>\n";
        echo "        <a href='?edit=1&userContactId=".$row['usercontactid']."&typeId=".$row['typeid']."' />EDIT</a> - \n";
        echo "        <a href='?deleteContactInfo=1&userContactId=".$row['usercontactid']."'\n";
        echo "          onclick=\"javascript: return confirm('Are you sure you want to permently delete the information for - ".ucfirst($row['addresstypename'])."')\">DELETE</a>\n";
        echo "      </div>\n";
        echo "    </th>\n";
        echo "  </thead>\n";
        echo "  <tbody>\n";
        echo "    <tr>\n";
        echo "      <td>Company Name: </td>\n";
        echo "      <td>".ucfirst($row['companyname'])."</td>\n";
        echo "    </tr>\n";
        echo "    <tr>\n";
        echo "      <td>Phone #: </td>\n";
        echo "      <td>".$row['phone']."</td>\n";
        echo "    </tr>\n";
        echo "    <tr>\n";
        echo "      <td>Alternate Phone #: </td>\n";
        echo "      <td>".$row['altphone']."</td>\n";
        echo "    </tr>\n";
        echo "    <tr>\n";
        echo "      <td>Fax #: </td>\n";
        echo "      <td>".$row['fax']."</td>\n";
        echo "    </tr>\n";
        echo "    <tr>\n";
        echo "      <td>Email: </td>\n";
        echo "      <td>".$row['email']."</td>\n";
        echo "    </tr>\n";
        echo "    <tr>\n";
        echo "      <td valign='top'>Address: </td>\n";
        echo "      <td>".$row['street']." ".$row['street2']."\n";
        echo "        <br />".$row['city'].", ".$row['state']." ".$row['zip']."\n";
        echo "        <br />".$row['country']."</td>\n";
        echo "    </tr>\n";
        echo "  </tbody>\n";
        echo "</table><br /><br />\n";
    }
}

function getAddressTypes($atid = NULL, $userId) {
    global $DB;

    $addresstypeid = (!is_null($atid)) ? $atid : -1;

    $sql ="
        SELECT at.addresstypeid AS typeid, at.addresstypename, uc.usercontactid,
                CASE WHEN at.addresstypeid = ".$addresstypeid." THEN 1
                    ELSE 0 END AS checked,
                CASE WHEN uc.usercontactid IS NULL THEN at.addresstypeid::VARCHAR
                     ELSE at.addresstypeid || '_' || uc.usercontactid
                 END AS at_uc_id
          FROM addresstype at
          LEFT JOIN usercontactinfo uc ON   uc.addresstypeid    = at.addresstypeid
                                       AND  uc.userid           = ".$userId."
           ORDER BY at.sort
        ";
    $data = $DB->sql_query_params($sql);

    return $data;
}

function getContactInfo($userId, $userContactId = NULL, $typeId = NULL) {
    global $DB;

    $sql = "
       SELECT typ.addresstypeid AS typeId, typ.addresstypename,
              con.userid, con.usercontactid, con.companyName, con.phone, con.altphone, con.fax, con.email,
              con.addresstypeid, con.street, con.street2, con.city, con.state, con.zip, con.country
         FROM addresstype      typ
         LEFT JOIN usercontactinfo  con ON     con.addresstypeid   = typ.addresstypeid
                                        AND    con.userid          = ".$userId."
    ";
    if (!isset($typeId)) {
        $sql .= "
        ORDER BY typ.sort";
    }
    if ($typeId > 0) {
        $sql .= "
        WHERE typ.addresstypeId = ".$typeId."
        ORDER BY typ.sort";
    }
    $row = $DB->sql_query_params($sql);

    return $row;
}

function queries($addressTypeId, $companyName, $phone, $altphone = NULL, $fax = NULL, $email, $street, $street2 = NULL, $city, $state, $zip, $country, $userId, $createdBy) {
    global $DB;
    global $messages;
    $success = FALSE;
    $add = new DBQueries();

//$phone = preg_replace('/[^0-9]/', '', $phone);
//$altphone = preg_replace('/[^0-9]/', '', $altphone);

    foreach($addressTypeId as $atId) {
        $split = explode('_', $atId);
        $atId = $split['0'];
        if (isset($split['1'])) {
            $conId = $split['1'];
            $sql = "
                UPDATE userContactInfo
                   SET companyName      = :companyName,
                       phone            = :phone,
                       altphone         = :altphone,
                       fax              = :fax,
                       email            = :email,
                       street           = :street,
                       street2          = :street2,
                       city             = :city,
                       state            = :state,
                       zip              = :zip,
                       country          = :country
                 WHERE userContactId    = :userContactId
            ";
            $params = array();
            $params['companyName']   = $companyName;
            $params['phone']         = $phone;
            $params['altphone']      = $altphone;
            $params['fax']           = $fax;
            $params['email']         = $email;
            $params['street']        = $street;
            $params['street2']       = $street2;
            $params['city']          = $city;
            $params['state']         = $state;
            $params['zip']           = $zip;
            $params['country']       = $country;
            $params['userContactId'] = $conId;
            $add->AddQuery($sql, $params, "add");
            unset($params);
            unset($sql);

        } else {
            $sql = "
                INSERT INTO userContactInfo(companyName, phone, altphone, fax, email, addressTypeId, street, street2, city, state, zip, country, userId, createdBy)
                     VALUES(:companyName, :phone, :altphone, :fax, :email, :addressTypeId, :street, :street2, :city, :state, :zip, :country, :userId, :createdby)
            ";
            $params = array();
            $params['companyName']   = $companyName;
            $params['phone']         = $phone;
            $params['altphone']      = $altphone;
            $params['fax']           = $fax;
            $params['email']         = $email;
            $params['addressTypeId'] = $atId;
            $params['street']        = $street;
            $params['street2']       = $street2;
            $params['city']          = $city;
            $params['state']         = $state;
            $params['zip']           = $zip;
            $params['country']       = $country;
            $params['userId']        = $userId;
            $params['createdby']     = $createdBy;
            $add->AddQuery($sql, $params, "add");
            unset($params);
            unset($sql);
        }
    }

    if ($add->ProcessQueries() == TRUE) {
        $result = TRUE;
        $success = TRUE;
        $messages->addSuccessMsg('You have updated your contact info');
    } else {
        $messages->addErrorMsg('Error?');
    }

    return $success;

}

function deleteUserContactInfo($userContactId) {
    global $DB;
    $success = FALSE;

    $sql = "
        DELETE FROM userContactInfo WHERE usercontactid = ".$userContactId
    ;

    $result = $DB->sql_execute_params($sql);

    if ($result > 0) {
        $success = TRUE;
        $messages->addSuccessMsg('You have deleted - ');
    } else {
        $message->addErrorMsg('Error?');
    }

    return $success;
}
//////////////////////////////////////////////////////////////////////////////
?>