<?php

if($HTTP_GET_VARS[profile]){

  include("blocks/search/profile.php");

}elseif($HTTP_GET_VARS[action] == "search_form"){
  $form = "search";

}elseif($HTTP_POST_VARS[search]){

  $pattern = substr($HTTP_POST_VARS["username"],0,50);

  $result = toolbox::get_users("MATCH", $pattern);
  $header_range_start = substr($range,0,1);
  $header_range_end = substr($range,-1);
  $header_pattern = "*".$pattern."*";

  if(!$result[err]){
    $users = $result[msg];
  }

  
  $form = "browse";


}elseif($HTTP_GET_VARS[browse]){

  // Get the range to browse
  $range = substr($HTTP_GET_VARS[browse],0,32);

  // Get the range
  if($range == "ALL") {
    // Get all users
    $result = toolbox::get_users("ALL");
    $header_range_start = "0";
    $header_range_end = "Z";
  }else{
    $result = toolbox::get_users("RANGE", $range);
    $header_range_start = substr($range,0,1);
    $header_range_end = substr($range,-1);
  }

  if(!$result[err]){
    $users = $result[msg];
  }

  $form = "browse";


}else{
  $form = "search";
}

switch($form){

 case "search":

   // Default Search System to General Form
   $block = yats_define($templateBase."search/search.html");

   yats_assign($block, array("sid"=> session_id()));
   echo yats_getbuf($block);
   break;

 case "browse":

   $block = yats_define($templateBase."search/browse.html");


   if(!count($users)){
     yats_hide($block, "user_listing_block", true);

   }else{
     yats_hide($block, "result_row_none", true);
     yats_assign($block, array("user_listing_block" => $app->html->render_user_directory_rows($users, "profile")));
   }

   if($header_range_start && $header_range_end)
     yats_assign($block, array("start_letter"=> $header_range_start, "end_letter"  => $header_range_end));


   if($header_pattern)
     yats_assign($block, array("pattern"=> $header_pattern));


   yats_assign($block, array("num_found"   => count($users), 
			     "sid"         => session_id()));


   echo yats_getbuf($block);
   break;


}




?>