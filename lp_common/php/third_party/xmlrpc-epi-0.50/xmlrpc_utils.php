<?php

// This file has moved. keeping below for legacy compat reasons.

include_once("utils/utils.php");

function xi_format($value) {
   include_once("utils/introspect.php");
   return format_describe_methods_result($value);
}

?>
