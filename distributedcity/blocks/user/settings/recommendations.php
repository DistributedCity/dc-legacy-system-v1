<?php


if($HTTP_GET_VARS[action] == "add_blog" && $HTTP_GET_VARS[blog_id]){

  // Get incoming vars
  $blog_id = substr($HTTP_GET_VARS[blog_id],0,100);
  
  unset($profile);
  $profile = new public_info($app->user->get_user_id());
  
  $result = $profile->add_blog_recommendation($blog_id);
  
  if($result[err]){
    $error_message = $result[msg];  
  }else{
    $status_message = $result[msg];
  }

  $mode = "recommendations_display";

}elseif($HTTP_GET_VARS[action] == "remove_blog" && $HTTP_GET_VARS[blog_id]){

  // Get incoming vars
  $blog_id = substr($HTTP_GET_VARS[blog_id],0,100);
  
  unset($profile);
  $profile = new public_info($app->user->get_user_id());
  
  $result = $profile->remove_blog_recommendation($blog_id);
  
  if($result[err]){
    $error_message = $result[msg];  
  }else{
    $status_message = $result[msg];
  }

  $mode = "recommendations_display";

}else{

  $mode = "recommendations_display";

}


switch($mode) {

 case "recommendations_display":
   $block = yats_define($templateBase."user/settings/recommendations.html");


   // Get recommendations_data
   unset($profile);
   $public_info = new public_info($app->user->get_user_id());

   $result = $public_info->get_blog_recommendations();

   if($result[err]){
     $error_message = $result[msg];      
     yats_hide($block, "blog_recommendations", true);

   }elseif(!is_array($result[msg])){

     yats_assign($block, array("status_message" => $result[msg]));

   }else{

    yats_hide($block, "blog_recommendations_none_row", true);
    $blog_recommendations = $result[msg];
    yats_assign($block, $blog_recommendations);

   }


    if($error_message){
      yats_assign($block, array("error_message" => $error_message));
    }
    
    // Status Message? Assign
    if($status_message){
      yats_assign($block, array("status_message" => $status_message));
    }
    
    
    yats_assign($block, array("user_id" => $app->user->get_user_id(),
			      "sid" => session_id()));
    break;
}
echo yats_getbuf($block);

?>