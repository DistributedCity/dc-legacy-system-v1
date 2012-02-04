<?php

$tmpl = yats_define("sample5.tmpl");

$thing = $GLOBALS[HTTP_GET_VARS][thing];
if(!$thing) {
   $thing = "there";
}

$colors = array("red", "blue", "green", "orange", "purple", "black", "mauve", "peach");
$flavors = array("peach", "mint", "chocolate", "vanilla", "coffee");

if($tmpl) {
   yats_assign($tmpl, array(color => $colors,
                            flavor => $flavors,
                            count_color => count($colors),
                            count_flavor => count($flavors),
                            person => "Joe"
                            ) );
   echo yats_getbuf($tmpl);
}
?>
