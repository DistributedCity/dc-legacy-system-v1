<?php

/*
  Login/Logout Box
*/


$block = yats_define($templateBase."user/blogbox.html");

//yats_assign($block, array("username"    =>  $app->user->get_username()));

echo yats_getbuf($block);

?>

