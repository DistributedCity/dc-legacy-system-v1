<?php

function my_cmp_buddies($a, $b) {
   return strcasecmp($a['username'], $b['username']);
}


if ($HTTP_GET_VARS[action] == "add_buddy" && $HTTP_GET_VARS[uid]) {

   // Get incoming vars
   $user_id = substr($HTTP_GET_VARS[uid],0,100);

   $result = $app->user->im->add_buddy($user_id);

   if ($result[err]) {
      $error_message = $result[msg];  
   }
   else {
      $status_message = $result[msg];
   }

   $mode = "buddy_list_display";

}
else if ($HTTP_GET_VARS[action] == "remove_buddy" && $HTTP_GET_VARS[uid]) {

   // Get incoming vars
   $user_id = substr($HTTP_GET_VARS[uid],0,100);

   $result = $app->user->im->remove_buddy($user_id);

   if ($result[err]) {
      $error_message = $result[msg];  
   }
   else {
      $status_message = $result[msg];
   }

   $mode = "buddy_list_display";

}
else {

   $mode = "buddy_list_display";

}


switch ($mode) {

case "buddy_list_display":
   $block = yats_define($templateBase."user/settings/buddy_list.html");

   if (empty($app->user->im->buddy_list)) {
      yats_hide($block, "buddy_item", true);

   }
   else {

      $blist = $app->user->im->buddy_list;
      uksort( $blist, 'my_cmp_buddies' );

      yats_hide($block, "buddy_row_none", true);
      foreach($blist as $buddy_user_id => $buddy_data) {
         $buddies[buddy_user_id][] = (string)$buddy_user_id;
         $buddies[buddy_username][] = $buddy_data[username];
         $buddies[user_image_src][] = toolbox::get_user_image_src($buddy_user_id);
      }
      yats_assign($block, $buddies);
   }

   if ($error_message) {
      yats_assign($block, array("error_message" => $error_message));
   }

   // Status Message? Assign
   if ($status_message) {
      yats_assign($block, array("status_message" => $status_message));
   }


   yats_assign($block, array("user_id" => $app->user->get_user_id(),
   "sid" => session_id()));
   break;
}
echo yats_getbuf($block);

?>