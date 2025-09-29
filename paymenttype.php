<?php
require_once('templateAdmin.class.php');

$page = new templateAdmin(LOGIN, SHOWMSG);
//$page->requireJS('scripts/validateBoxTypes.js');

$active                 = optional_param('active', NULL, PARAM_INT);
$addPaymentType         = optional_param('addpaymenttype', NULL, PARAM_ALPHA);
$paymentTypeId          = optional_param('paymenttypeid', NULL, PARAM_INT);
$paymentTypeName        = optional_param('paymenttypename', NULL, PARAM_TEXT);
$commitEditPaymentType  = optional_param('commiteditpaymenttype', NULL, PARAM_INT);
$paymentAllowInfo       = optional_param('allowinfo', 'Optional', PARAM_TEXT);
$confirm                = optional_param('confirm', NULL, PARAM_INT);
$deletePaymentType      = optional_param('deletepaymenttype', NULL, PARAM_INT);
$editPaymentType        = optional_param('editpaymenttype', 0, PARAM_INT);
$newPaymentType         = optional_param('newpaymenttype', NULL, PARAM_INT);
$originalName           = optional_param('originalname', NULL, PARAM_TEXT);

if (isset($deletePaymentType)) {
    deletePaymentType($paymentTypeName, $paymentTypeId);
}
if (isset($commitEditPaymentType)) {
    editPaymentType($paymentTypeId, $paymentTypeName, $paymentAllowInfo, $active, $originalName);

}
if (isset($addPaymentType)) {
    addPaymentType($paymentTypeName, $paymentAllowInfo, $active);
}

echo $page->header('Payment Types');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $addPaymentType, $paymentTypeId, $paymentTypeName, $paymentAllowInfo, $editPaymentType, $newPaymentType, $UTILITY;

    $paymentTypes = getPaymentTypes();
    if (!isset($newBoxType)) {
        echo "<div style='float: right; margin: 10px 500px 10px 5px;'><a class='button' href='?newpaymenttype=1'>NEW</a></div>\n";
    }
    echo "<form name ='sub' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post'>\n";
    echo "<table>\n";
    echo "  <thead>\n";
    echo "    <tr>\n";
    echo "      <th>Name</th>\n";
    echo "      <th>Require Info</th>\n";
    echo "      <th>Active</th>\n";
    echo "      <th>Action</th>\n";
    echo "    </tr>\n";
    echo "  </thead>\n";
    echo "  <tbody>\n";
    if ($newPaymentType == 1) {
        echo "    <tr>\n";
        echo "      <td><input type='text' name='paymenttypename' id='paymenttypename' value=''/><input type='hidden' name='addpaymenttype' value='Add New'/></td>\n";
        echo "      <td>".getAllowInfoDDM($paymentAllowInfo)."</td>\n";
        echo "      <td><input type='checkbox' name='active' id='active' value='1'/></td>\n";
        echo "      <td>\n";
        echo "        <a href='javascript: void(0);' onclick=\"javascript: document.sub.submit();\">Save </a>\n";
        echo "        <a href='".htmlentities($_SERVER['PHP_SELF'])."' />- Cancel\n";
        echo "      </td>\n";
        echo "    </tr>\n";

    }
    if (isset($paymentTypes)) {
        foreach ($paymentTypes as $row) {
            if ($editPaymentType == $row['paymenttypeid']) {
                echo "    <tr>\n";
                echo "        <input type='hidden' name='paymenttypeid' value='".$row['paymenttypeid']."'/>\n";
                echo "        <input type='hidden' name='originalname' value='".$row['paymenttypename']."'/>\n";
                echo "      <td><input type='text' name='paymenttypename' id='paymenttypename' value='".$row['paymenttypename']."'/></td>\n";
                echo "      <td>".getAllowInfoDDM($row['allowinfo'])."</td>\n";
                echo "      <td><input type='checkbox' name='active' id='active' value='1' ".$UTILITY->checked($row['active'])." /></td>\n";
                echo "      <td>\n";
                echo "        <input type='hidden' name='commiteditpaymenttype' value='Commit Edit'/>\n";
                echo "        <a href='javascript: void(0);' onclick=\"javascript: document.sub.submit()\">Save </a>\n";
                echo "        <a href='".htmlentities($_SERVER['PHP_SELF'])."' />- Cancel\n";
                echo "      </td>\n";
                echo "    </tr>\n";
            } else {
                if ((isset($paymentTypeId) && $paymentTypeId == $row['paymenttypeid']) || ($paymentTypeName == $row['paymenttypename'])) {
                    echo "    <tr>\n";
                } else {
                    echo "    <tr>\n";
                }
                echo "      <td>".$row['paymenttypename']."</td>\n";
                echo "      <td>".$row['allowinfo']."</td>\n";
                echo "      <td><input type='checkbox'name='active' ".$UTILITY->checked($row['active'])." readonly disabled /></td>\n";
                echo "      <td>\n";
                if ($editPaymentType != 0) {
                    echo "&nbsp;\n";
                } else {
                    echo "        <a href='?editpaymenttype=".$row['paymenttypeid']."'>Edit</a>\n";
                    //echo "        - <a href='?deletepaymenttype=1&paymenttypename=".$row['paymenttypename']."&paymenttypeid=".$row['paymenttypeid']."'\n";
                    //echo "          onclick=\"javascript: return confirm('Are you sure you want to permently delete the payment type - ".$row['paymenttypename']."')\">Delete</a>\n";
                }
                echo "      </td>\n";
                echo "    </tr>\n";
            }
        }
    }

    echo "  </tbody>\n";
    echo "</table><br /><br />\n";
    echo "</form>\n";
}

function getAllowInfoDDM($paymentAllowInfo) {
    $spacers = "        ";
    $allowDDM = $spacers."<select id='allowinfo' name='allowinfo'>\n";
    $allowDDM .= $spacers."  <option value='No'".(($paymentAllowInfo=="No") ? " selected " : "").">No</option>\n";
    $allowDDM .= $spacers."  <option value='Yes'".(($paymentAllowInfo=="Yes") ? " selected " : "").">Yes</option>\n";
    $allowDDM .= $spacers."  <option value='Optional'".(($paymentAllowInfo=="Optional") ? " selected " : "").">Optional</option>\n";
    $allowDDM .= $spacers."</select>";
    return $allowDDM;
}

function getPaymentTypes() {
    global $page;

    $sql = "SELECT paymenttypeid, paymenttypename, allowinfo, active
        FROM paymenttypes
        ORDER BY active DESC, paymenttypename";

    $paymentTypes = $page->db->sql_query_params($sql);
    
    return $paymentTypes;
}

function deletePaymentType($paymentTypeName, $paymentTypeId) {
    global $page;

    $success = FALSE;
    $sql = "DELETE FROM paymenttypes WHERE paymenttypeId=".$paymentTypeId;
    $delete = $page->db->sql_execute_params($sql);
    if ($delete > 0) {
        $success = TRUE;
        $page->messages->addSuccessMsg('You have successfully deleted '.$paymentTypeName);
    } else {
        $page->messages->addErrorMsg('Can not delete the payment type - '.$paymentTypeName);
    }

    return $success;
}

function editPaymentType($paymentTypeId, $paymentTypeName, $paymentAllowInfo, $active, $originalName) {
    global $page, $USER, $UTILITY;

    if ($UTILITY->checkDuplicateName('paymenttypes', 'paymenttypename', $paymentTypeName, $originalName) == FALSE) {

        $success = FALSE;
        ($active == 0 ? $active = 0 : $active = 1);

        $sql = "UPDATE paymenttypes
                   SET paymenttypename  = :paymenttypename,
                       allowinfo        = :allowinfo,
                       active           = :active,
                       modifiedby       = :modifiedby
                 WHERE paymenttypeid    = :paymenttypeid
        ";
        $params = array();
        $params['paymenttypeid']    = $paymentTypeId;
        $params['paymenttypename']  = $paymentTypeName;
        $params['allowinfo']        = $paymentAllowInfo;
        $params['active']           = $active;
        $params['modifiedby']       = $USER->username;

        $result = $page->db->sql_execute_params($sql, $params);
        if ($result > 0) {
            $success = TRUE;
            $page->messages->addSuccessMsg('You have successfully edited '.$paymentTypeName);
        } else {
            $page->messages->addErrorMsg('Error editing payment type - '.$paymentTypeName);
        }

        return $success;
    } else {
        $page->messages->addErrorMsg("The Payment Type name (".$paymentTypeName.") is already in use.");
    }

}

function addPaymentType($paymentTypeName, $paymentAllowInfo, $active) {
    global $page;
    global $USER;
    global $UTILITY;

    if ($UTILITY->checkDuplicateName('paymenttypes', 'paymenttypename', $paymentTypeName) == FALSE) {
        $success = FALSE;
        ($active == 0 ? $active = 0 : $active = 1);

        $sql = "INSERT INTO paymenttypes( paymenttypename, allowinfo,  active, createdby, modifiedby)
                              VALUES(:paymenttypename, :allowinfo, :active, :createdby, :modifiedby)
        ";
        $params = array();
        $params['paymenttypename']  = $paymentTypeName;
        $params['allowinfo']        = $paymentAllowInfo;
        $params['active']           = $active;
        $params['createdby']        = $USER->username;
        $params['modifiedby']       = $USER->username;

        $result = $page->db->sql_execute_params($sql, $params);
        if ($result > 0) {
            $success = TRUE;
            $page->messages->addSuccessMsg('You have successfully added '.$paymentTypeName);
        } else {
            $page->messages->addErrorMsg('Error adding payment type - '.$paymentTypeName);
        }
        return $result;
    } else {
        $page->messages->addErrorMsg("The Payment Type name (".$paymentTypeName.") is already in use.");
    }
}
?>