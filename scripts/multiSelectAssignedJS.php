<?php
require_once('../setup.php');

$selectAssigned = optional_param('selectAssigned', NULL, PARAM_TEXT);
$idnameB        = optional_param('idnameB', NULL, PARAM_TEXT);

header('Content-Type: text/javascript');


    echo "function ".$selectAssigned."() {\n";
    echo "  for (var i=0;i<".$idnameB.".options.length;i++) {\n";
    echo "    ".$idnameB.".options[i].selected = 'selected';\n";
    echo "  }\n";
//echo "console.log('HEY');\n";
    echo "}\n";

?>