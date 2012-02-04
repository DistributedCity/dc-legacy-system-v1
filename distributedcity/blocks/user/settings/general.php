<?php


if($HTTP_GET_VARS[action] == "change_image_form") {

  $mode = "change_image_form";

}elseif($HTTP_GET_VARS[action] == "change_image") {

      // Get incoming data
      $image = substr($HTTP_GET_VARS[image],0,1024);
      $group = substr($HTTP_GET_VARS[group],0,1024);

      $result = $app->user->set_user_image($image, $group);
      if(!$result[err]){
	echo $result[msg];
      }else{
	// handle
      }


 $mode = "general_settings";

}elseif($HTTP_POST_VARS[save_settings]){
  //    phpinfo();
  if($HTTP_POST_VARS[hide_presence] == "on"){
    $app->user->set_pref("hide_presence", "1");

    // If the user is requesting for their online presence to be hidden,
    // then lets delete any current or past entries in the online_status_db
    // So the online status is immediately reflected as hidden
    $dbfile = $GLOBALS[config][online_status_db];

    $result = toolbox::dba_delete($dbfile, $app->user->get_user_id() );

  }else{
    $app->user->set_pref("hide_presence", "0");
  }
  $mode = "general_settings";
}else{

  $mode = "general_settings";
}


switch($mode) {

 case "change_image_form":
   unset($block);
   $result = $app->user->html->render_user_image_selection_screen("settings");
   if(!$result[err]){
     echo $result[msg];
   }else{
     // handle
   }

   
   break;


 case "general_settings":
   $block = yats_define($templateBase."user/settings/general.html");

   if($change_avatar_status){
     yats_assign($block, array("change_avatar_status" => $change_avatar_status));
   }

   yats_assign($block, array("user_image_src"   => toolbox::get_user_image_src($app->user->get_user_id()),
			     "user_id" => $app->user->get_user_id(),
			     "session_id" => session_id(),
			     "hide_presence_status" => $app->user->get_pref("hide_presence") == 1 ? "CHECKED" : ""
			     ));

   break;
}

if($block)
     echo yats_getbuf($block);

?>