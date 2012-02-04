<?php

/*

*/


$block = yats_define($templateBase."forums/menubox.html");
$result = $app->forums->get_categories();
if(!$result[err]){
  $categories = $result[msg];
  yats_assign($block, $categories);

echo yats_getbuf($block);

}


?>

