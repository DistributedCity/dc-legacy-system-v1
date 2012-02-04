<?php

/*
  Login/Logout Box
*/


$userbox_block = yats_define($templateBase."user/userbox.html");

// Hide the admin menu if user is not an administrator
if(!$app->user->is_admin()){
  yats_hide($userbox_block, "admin_menu", true);
}

yats_assign($userbox_block, array("username"             => $app->user->get_username(),
				  "id"                   => $app->user->get_user_id(),
				  "user_image_src"       => toolbox::get_user_image_src($app->user->get_user_id())
				  ));

echo yats_getbuf($userbox_block);

include("blocks/messaging/statusbox.php");

?>

