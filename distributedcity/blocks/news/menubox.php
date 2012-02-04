<?php

/*

*/


$block = yats_define($templateBase."news/menubox.html");

//yats_assign($block, array("username"    =>  $app->user->get_username()));

echo yats_getbuf($block);

?>

