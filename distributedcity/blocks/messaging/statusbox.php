<?php
/*
*/

function cmp_buddies($a, $b) {
   return strcasecmp($a['username'], $b['username']);
}

$app->user->im->_reload_buddy_list();

$messaging_status_box = yats_define($templateBase."messaging/statusbox.html");

$buddy_list = $app->user->im->buddy_list;

$result = $app->user->im->get_message_notifications();

if(!$result[err]){

  $notifications = $result[msg];
}







// Do BUDDY USERS
if( is_array($buddy_list) ){

  uksort( $buddy_list, 'cmp_buddies' );

  // Note: We are going to loop through each buddy on the buddy list and look to see which
  //       buddies are online, if they are, we will then see if the user has any notifications
  //       for new messages from that user. Then we will assign this buddy and info to the ONLINE BUDDIES
  //       
  foreach($buddy_list as $buddy_user_id => $buddy_data){
    
    if($buddy_data[online_status] == "1"){
      $online_status = "online";
    }else{
      $online_status = "offline";
    }
    
    $buddies[$online_status][user_image_src][]   = toolbox::get_user_image_src($buddy_user_id);
    $buddies[$online_status][user_id][]   = (string)$buddy_user_id;
    $buddies[$online_status][username][]  = $notifications[$buddy_user_id][message_count] ? $buddy_data[username] . "&nbsp;(".$notifications[$buddy_user_id][message_count].")" : $buddy_data[username];      
    
    $buddies[$online_status][icon][] = $notifications[$buddy_user_id][message_count] ? '<a href="/messaging/?action=read&folder=1&uidf=' . $buddy_user_id .'"><img src="/images/new_message.gif"  border="0" width="12" height="9"></a>' : "";
  }
}




// Do OTHER USERS (Users that have sent a new message that are not on the buddy list)
if($notifications){

  // NOTE: We are looping through each notification, and looking to see if the notification_user_id
  //       is found in the buddy list. If it is NOT, then we assign this notification information to the OTHERS
  foreach($notifications as $notification_user_id => $notification_data){
    if(!$buddy_list[$notification_user_id]){
      $users[other][user_image_src][]  = toolbox::get_user_image_src($notification_user_id);
      $users[other][user_id][]  = (string)$notification_user_id;
      $users[other][username][] = $notification_data[username];
      $users[other][icon][]     = '<a href="/messaging/?action=read&folder=1&uidf=' .$notification_user_id . '"><img src="/images/new_message.gif" border="0" width="12" height="9"></a>';
    }
  }
}






if(!empty($buddies[online])){
  yats_assign($messaging_status_box, array("buddies_online_user_image_src"=> $buddies[online][user_image_src],
					   "buddies_online_username"      => $buddies[online][username],
					   "buddies_online_user_id"       => $buddies[online][user_id],
					   "buddies_online_message_count" => $buddies[online][message_count],
					   "buddies_online_icon"          => $buddies[online][icon]));
}else{
  yats_hide($messaging_status_box, "online", true);
}

if(!empty($buddies[offline])){
  yats_assign($messaging_status_box, array("buddies_offline_user_image_src"=> $buddies[offline][user_image_src],
					   "buddies_offline_username"      => $buddies[offline][username],
					   "buddies_offline_user_id"       => $buddies[offline][user_id],
					   "buddies_offline_message_count" => $buddies[offline][message_count],
					   "buddies_offline_icon"          => $buddies[offline][icon]));
}else{
  yats_hide($messaging_status_box, "offline", true);
}



if(!empty($users[other])){

  yats_assign($messaging_status_box, array("other_user_image_src"=> $users[other][user_image_src],
					   "other_username"      => $users[other][username],
					   "other_user_id"       => $users[other][user_id],
					   "other_message_count" => $users[other][message_count],
					   "other_icon"          => $users[other][icon]));
}else{
  
  yats_hide($messaging_status_box, "other_users", true);
}



  if(!$result[err]){

  }else{
    // Show "no messages" section
    yats_assign($messaging_status_box, array("notification_status" => "No messages."));
  }


echo yats_getbuf($messaging_status_box);  

?>