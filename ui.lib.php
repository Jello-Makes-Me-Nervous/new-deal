<?php

function getSelectDDM($data, $idname, $valuefield, $displayfield, $defaultitem = NULL, $selecteditem = NULL, $none = NULL, $nonevalue = 0, $ddmClass = NULL, $ddmStyle = NULL, $onChangeScript = NULL, $ddmattribute = NULL) {
    $output = "";

    $output = "no choices found";
    $output .= "<INPUT TYPE='hidden' id='".$idname."' name='".$idname."' value=''>";
    if (!empty($data)) {

        if (!is_null($ddmattribute)) {
            $attribute = $ddmattribute;
        } else {
            $attribute = "";
        }
        if (!is_null($ddmClass)) {
            $class = "class='".$ddmClass."'";
        } else {
            $class = "";
        }
        if (!is_null($ddmStyle)) {
            $style = "style='".$ddmStyle."'";
        } else {
            $style = "";
        }
        if (!is_null($onChangeScript)) {
            $change = $onChangeScript;
        } else {
            $change = "";
        }

//(!is_null($disabled)) ? $disabled : $disabled = "";

        $output =  "<select ".$attribute." ".$class." ".$style." id='".$idname."' name='".$idname."' ".$change."/>\n";

        if (!empty($none)) {
            $output .= "  <option value='".$nonevalue."'>".$none."</option>\n";
        }

        foreach ($data as $row) {
            if ($row[$valuefield] == $selecteditem) {
                $selected = "selected";
            } else {
                if (isset($row[$defaultitem]) && (!empty($row[$defaultitem]))) {
                    $selected = "selected";
                } else {
                    $selected = "";
                }
            }

            $output .= "  <option value='".$row[$valuefield]."' ".$selected.">".$row[$displayfield]."</option>\n";
        }
        $output .= "</select>\n";
    }

    return $output;
}

function getCheckBox($data, $name, $valuefield, $displayfield,  $checkField = NULL, $readOnly = NULL, $cols = 1, $cbClass = NULL, $cbStyle = NULL, $cbAttribute = NULL) {

    $width = intval(90 / $cols);
    $class = (!empty($cbClass)) ? "class ='".$cbClass."'" : "";
    $style = (!empty($cbStyle)) ? "style ='float: left; width: ".$width."%; white-space: nowrap;".$cbStyle."'" : "style='float: left; width: ".$width."%; white-space: nowrap;'";

    echo "<div style='clear: both; margin: 2px;'>\n";
    foreach ($data as $row) {
        $checked = "";
        if (!empty($checkField) && isset($row[$checkField])) {
            $checked = (!empty($row[$checkField])) ? "checked" : "";
        }
        $read = "";
        if (!empty($row[$readOnly]) && isset($row[$checkField])) {
            $read = (!empty($row[$checkField])) ? "onclick='return false'" : "";
        }

        echo "<div ".$class." ".$style.">\n";
        echo "  <input type='checkbox' id='".$name."_".$row[$valuefield]."' name='".$name."[]' value='".$row[$valuefield]."' ".$checked." ".$read." ".$cbAttribute.">\n";
        echo "  <label for='".$name."_".$row[$valuefield]."'>".ucfirst($row[$displayfield])."<label/>\n";
        echo "</div>\n";
    }
    echo "</div>\n";
}

function getRadioButton($data, $name, $group, $valuefield, $displayfield, $radioquestion,  $checkField = NULL, $readOnly = NULL,  $cols = 1, $rbClass = NULL, $rbStyle = NULL, $rbAttribute = NULL) {

    $width = intval(90 / $cols);
    $class = (!empty($rbClass)) ? "class ='".$rbClass."'" : "";
    $style = (!empty($rbStyle)) ? "style ='float: left; width: ".$width."%; white-space: nowrap;".$rbClass."'" : "style='float: left; width: ".$width."%; white-space: nowrap;'";
    echo "<div style='clear: both; margin: 2px;'>\n";
    foreach ($data as $row) {
        $checked = "";
        if (!empty($checkField) && isset($row[$checkField])) {
            $checked = (!empty($row[$checkField])) ? "checked" : "";
        }
        $read = "";
        if (!empty($row[$readOnly]) && isset($row[$checkField])) {
            $read = (!empty($row[$checkField])) ? "onclick='return false'" : "";
        }
        echo "<div ".$class." ".$style.">\n";
if ($row[$radioquestion] == 1) {
    echo $choice = $row[$displayfield];
} elseif ($row[$radioquestion] != 1) {
        echo "  <input type='radio' id='".$name."_".$row[$valuefield]."' name='".$name."[".$row[$group]."]' value='".$row[$valuefield]."' ".$checked." ".$read." ".$rbAttribute.">\n";
        echo "  <label for='".$name."_".$row[$valuefield]."'>".ucfirst($row[$displayfield])."<label/>\n";
}
        echo "</div>\n";
    }
    echo "</div>\n";
}

function getMultiSelectBoxes($dataA, $dataB, $idnameA, $idnameB, $valuefield, $displayfield, $title, $rightTitle, $leftTitle, $defaultitem = NULL, $selecteditem = NULL, $none = NULL, $nonevalue = 0, $ddmClass = NULL, $ddmStyle = NULL) {

    $output = "";

    //$output .= "no choices found";
    //$output .= "<INPUT TYPE='hidden' id='".$idname."' name='".$idname."' value=''>";
    if (!empty($dataA)) {

        if (is_null($ddmClass)) {
            $ddmClass = "";
        } else {
            $ddmClass = "class='".$ddmClass."'";
        }
        if (is_null($ddmStyle)) {
            $ddmStyle = "";
        } else {
            $ddmStyle = "style='".$ddmStyle."'";
        }

        $onChangeScriptA = "onclick=\"javascript: var op = new Option(this.options[this.selectedIndex].text, this.value); var oA = document.getElementById('".$idnameB."'); oA.add(op); sortSelectByValue(oA, this.value); this.remove(this.selectedIndex);\"";
        $onChangeScriptB = "onclick=\"javascript: var op = new Option(this.options[this.selectedIndex].text, this.value); var oA = document.getElementById('".$idnameA."'); oA.add(op); sortSelectByValue(oA, this.value); this.remove(this.selectedIndex);\"";

        $output .= "\n";
        $output .= "<table>\n";
        $output .= "  <thead>\n";
        $output .= "    <tr>\n";
        $output .= "      <th colspan='2'>".$title." <br />&#8678; &#8680;</th>\n";
        $output .= "    </tr>\n";
        $output .= "  </thead>\n";
        $output .= "  <tbody>\n";


        $output .= "    <tr>\n";
        $output .= "      <td align='center'>\n";
        $output .= "        <select multiple='multiple' ".$ddmClass." ".$ddmStyle." id='".$idnameA."' name='".$idnameA."[]' ".$onChangeScriptA."/>\n";


        if (!empty($none)) {
            $output .= "  <option value='".$nonevalue."'>".$none."</option>\n";
        }

        foreach ($dataA as $row) {
            if ($row[$valuefield] == $selecteditem) {
                $selected = "selected";
            } else {
                $selected = "";
            }

            $output .= "  <option value='".$row[$valuefield]."' ".$selected.">".$row[$displayfield]."</option>\n";
        }
        $output .= "        </select>\n";
        $output .= "      </td>\n";
        $output .= "      <td align='center'>\n";

        $output .= "<select multiple='multiple' ".$ddmClass." ".$ddmStyle." id='".$idnameB."' name='".$idnameB."[]' ".$onChangeScriptB."/>\n";

        if (!empty($dataB)) {
            foreach ($dataB as $row) {
                if ($row[$valuefield] == $selecteditem) {
                    $selected = "selected";
                } else {
                    $selected = "";
                }

                $output .= "  <option value='".$row[$valuefield]."' ".$selected.">".$row[$displayfield]."</option>\n";
            }
        }

        $output .= "        </select>\n";
        $output .= "      </td>\n";

        $output .= "    </tr>\n";
        $output .= "  </tbody>\n";
        $output .= "</table>\n";
    }

    return $output;
}

function offsetAnchor($name) {
    return "<a id='".$name."' name='".$name."' class='offsetanchor'></a>";
}
?>