<?php
include_once('setup.php');
require_once('template.class.php');

$page = new template(LOGIN, SHOWMSG);
$page->requireJS('scripts/vacation.js');

$startVaca      = optional_param('startvaca', NULL, PARAM_TEXT);
$endVaca        = optional_param('endvaca', NULL, PARAM_TEXT);
$vacaType       = optional_param('vacationtype', 'Both', PARAM_TEXT);
$updateVaca     = optional_param('updatevaca', NULL, PARAM_TEXT);
$clearVaca     = optional_param('clearvaca', NULL, PARAM_TEXT);

$calendarJS = '
    $(function(){$("#startvaca").datepicker();});
    $(function(){$("#endvaca").datepicker();});
';
$page->jsInit($calendarJS);


if (isset($updateVaca)) {
    newVacation($startVaca, $endVaca, $vacaType);
} else {
    if (isset($clearVaca)) {
        $startVaca = NULL;
        $endVaca = NULL;
        $vacaType = 'Both';
        newVacation(NULL, NULL, 'Both');
    } else {
        if ($data = getDates()) {
            $startVaca = $data['onvacation'];
            $endVaca = $data['returnondate'];
            $vacaType = $data['vacationtype'];
        }
    }
}

echo $page->header('My Vacation');
echo mainContent();
echo $page->footer(true);


function mainContent() {
    global $page, $UTILITY, $startVaca, $endVaca, $vacaType;
    

    echo "<form name ='sub2' action='".htmlentities($_SERVER['PHP_SELF'])."' method='post' onsubmit='return validVaction()'>\n";
    echo "  <table>\n";
    echo "    <tbody>\n";
    echo "      <tr>\n";
    echo "        <td>Start Vacation</td>\n";
    echo "        <td><input type='text' name='startvaca' id='startvaca' value='".$startVaca."'> (mm/dd/yyyy)</td>\n";//add date picker - force a pattern
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>End Vacation</td>\n";
    echo "        <td><input type='text' name='endvaca' id='endvaca' value='".$endVaca."'> (mm/dd/yyyy)</td>\n";//add date picker - force a pattern
    echo "      </tr>\n";
    echo "      <tr>\n";
    echo "        <td>Vacation Applies To My Listings of Type</td>\n";
    echo "        <td>\n";
    echo "          <select name='vacationtype' id='vacationtype'>\n";
    echo "            ".vacationTypeOption('Both', $vacaType)."\n";
    echo "            ".vacationTypeOption('Buy', $vacaType)."\n";
    echo "            ".vacationTypeOption('Sell', $vacaType)."\n";
    echo "          </select>\n";
    echo "        </td>\n";
    echo "      </tr>\n";
    echo "    </tbody>\n";
    echo "  </table>\n";
    echo "  <input class='button' type='submit' name='updatevaca' value='Update Vacation'>\n";
    echo "  <input class='button' type='submit' name='clearvaca' value='Remove Vacation'>\n";
    echo "</form>\n";


}

function vacationTypeOption($thisType, $currentValue) {
    return "<option value='".$thisType."' ".(($thisType == $currentValue) ? "selected" : "").">".$thisType."</option>";
}

function checkDates($checkDate, $title) {
    global $page;
    
    $success = false;
    
    $lastYear = strtotime('-1 year');
    $nextYear = strtotime('+1 year');
    $dateStamp = strtotime($checkDate);
    $d = DateTime::createFromFormat('m/d/Y', $checkDate);
    if ($d) {
        $dateCheck = strtotime($d->format('m/d/Y'));
        
        if ($dateStamp == $dateCheck) {
            if (($lastYear < $dateStamp) && ($dateStamp < $nextYear)) {
                $success = true;
            } else {
                $page->messages->addErrorMsg($title." must be within one year of the current date");
            }
        } else {
            $page->messages->addErrorMsg("Invalid ".$title." must be mm/dd/yyyy");
        }
    } else {
        $page->messages->addErrorMsg("Invalid ".$title." must be mm/dd/yyyy");
    }

    return $success;
}    
function newVacation($startVaca, $endVaca, $vacaType) {
    global $page;
    
    $success = true;

    $sql = "";

    $params = array();
    $params['userid'] = $page->user->userId;
    
    if (empty($startVaca) && empty($endVaca)) {
        $sql = "UPDATE userinfo
            SET onvacation=NULL
                , returnondate=NULL
                , vacationtype='Both'
                , vacationbuy=0
                , vacationsell=0
            WHERE userid= :userid";
    } else {
        if (empty($startVaca)) {
            $page->messages->addErrorMsg("Start Vacation required");
            $success = false;
        } else {
            if (! checkDates($startVaca, 'Start Vacation')) {
                $success = false;
            }
        }
        
        if (empty($endVaca)) {
            $page->messages->addErrorMsg("End Vacation required");
            $success = false;
        } else {
            if (! checkDates($endVaca, 'End Vacation')) {
                $success = false;
            }
        }
        
        if ($success) {
            if (strtotime($startVaca) > strtotime($endVaca)) {
                $page->messages->addErrorMsg("Start Vacation must be before End Vacation required");
                $success = false;
            }
                
        }
        
        if (empty($vacaType)) {
            $page->messages->addErrorMsg("Vacation Type required");
            $success = false;
        }
        
        if ($success) {
            $vacationBuy = 0;
            $vacationSell = 0;
            $today = time();
            $startVacation = strtotime($startVaca);
            $endVacation = strtotime($endVaca." 23:59:59");
            if (($startVacation < $today) && ($today < $endVacation)) {
                switch ($vacaType) {
                    case 'Buy':
                        $vacationBuy = 1;
                        break;
                    case 'Sell':
                        $vacationSell = 1;
                        break;
                    case 'Both':
                        $vacationBuy = 1;
                        $vacationSell = 1;
                        break;
                }
                        
            }

            $sql = "UPDATE userinfo
                SET onvacation = :onvacation
                    , returnondate = :returnondate
                    , vacationtype=:vacationtype
                    , vacationbuy=:vacationbuy
                    , vacationsell=:vacationsell
                WHERE userid= :userid";
        
            $params['onvacation']       = $startVacation;
            $params['returnondate']     = $endVacation;
            $params['vacationtype']     = $vacaType;
            $params['vacationbuy']      = $vacationBuy;
            $params['vacationsell']     = $vacationSell;
        }
    }

    if ($success) {
        $result = $page->db->sql_query_params($sql, $params);

        if($result >= 0) {
            $page->messages->addSuccessMsg("Vacation updated");
        } else {
            $success = false;
            $page->messages->addErrorMsg("Error");
        }
    }

    return $success;
}
//Rework this if vacation date isset and return is not then onvacation if return is less than today then not on vacation
function getDates() {
    global $page;

    $sql = "SELECT onvacation, returnondate, vacationtype FROM userinfo WHERE userid = ".$page->user->userId;

    $data = $page->db->sql_query_params($sql);
    if (isset($data)) {
        $dat = reset($data);
        $startVaca = (isset($dat['onvacation'])) ? date('m/d/Y', $dat['onvacation']) : NULL;
        $endVaca = (isset($dat['returnondate'])) ? date('m/d/Y', $dat['returnondate']) : NULL;
        $vacaType = (isset($dat['vacationtype'])) ? $dat['vacationtype'] : NULL;
        $data = array('onvacation' => $startVaca, 'returnondate' => $endVaca, 'vacationtype' => $vacaType );
    }

    return $data;
}

?>