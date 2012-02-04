<?php

/*
  Information about the chat systems
*/

switch($HTTP_GET_VARS[action]){

 default:
   $file = "iip_overview.html";
   break;
   
 case "installation":
   $file = "iip_installation.html";
   break;
   
 case "using":
   $file = "iip_using.html";
   break;

case "ssl_interface":
   $file = "iip_ssl.html";
   break;

 case "web_interface":
   $file = "iip_web_interface.html";
   break;

 case "channels":
   $file = "iip_channels.html";
   break;

 case "credit":
   $file = "iip_credit.html";
   break;

}

$block = yats_define($templateBase."chat/".$file);

//yats_assign($block, array("username"    =>  $app->user->get_username()));

echo yats_getbuf($block);

?>

