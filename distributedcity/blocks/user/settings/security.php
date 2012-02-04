<?php

$block = yats_define($templateBase."user/settings/security.html");

// Password change request?
if($HTTP_POST_VARS[change_password]){

  // Get password data
  $data[password_current]     = substr($HTTP_POST_VARS[password_current],0,1024);
  $data[password_new]         = substr($HTTP_POST_VARS[password_new],0,1024);
  $data[password_new_confirm] = substr($HTTP_POST_VARS[password_new_confirm],0,1024);

  $result = $app->user->change_password($data);

  if($result[err]){
    yats_assign($block, array("password_change_error" => $result[msg]));
  }else{
    yats_assign($block, array("password_change_ok" => $result[msg]));
  }

}






yats_assign($block, array("sid"    =>  session_id()));
echo yats_getbuf($block);

?>