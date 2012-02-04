<?php

$tmpl = yats_define("sample2.tmpl");

$thing = $GLOBALS[HTTP_GET_VARS][thing];
if(!$thing) {
   $thing = "there";
}

if($tmpl) {
   yats_assign($tmpl, "color", array("red", "blue", "green", "orange", "purple", "black", "mauve", "peach"));
   echo yats_getbuf($tmpl);
}
?>
