<?php

/*
  User Profile 
*/

// Show Profile Directory?




if(!empty($HTTP_GET_VARS[profile])){  // Show specific Profile?

  // Get the user_id
  $user_id = (int)substr($HTTP_GET_VARS[profile],0,100);

  // check if user_id is int
  if(!is_int($user_id)){

    $error_message = "User not found.";
    $mode = "profile_error";

  } else {

    unset($profile);
    $profile = new public_info($user_id);
    
    $result = $profile->get_data();
    
    if($result[err]){
      $error_message = $result[msg];
      $mode = "profile_error";
      
    }else{
      $public_info = $result[msg];
      $mode = "profile_display";

    }
  }


}

switch($mode){

 case "search":
   $block = yats_define($templateBase."user/search.html");
   yats_assign($block, array("sid"=> session_id()));
   break;

 case "profile_error":
   $block = yats_define($templateBase."search/profile.html");
   yats_hide($block, "profile_display", true);
   yats_hide($block, "profile_directory", true);
   yats_assign($block, array("error_message"=> $error_message));
   break;


 case "profile_display":
   $block = yats_define($templateBase."search/profile.html");
   yats_hide($block, "profile_directory", true);

   // DC Encode all non-array items
   $profile = $public_info[profile];
   $recommended_blogs = $public_info[blog_recommendations];

   $profile = $app->html->dc_encode($profile, "profile");

   // Render profile data
   yats_assign($block, $profile);


   
   // Render recommended blogs
   if(!empty($recommended_blogs)){
     yats_assign($block, $recommended_blogs);
   }else{
     yats_hide($block, "blog_recommendations", true);
   }

   // Display users public key
   break;

}
echo yats_getbuf($block);
?>

