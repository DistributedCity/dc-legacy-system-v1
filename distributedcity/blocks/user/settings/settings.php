<?php

/*
  User settings block
*/

$mode = substr($HTTP_GET_VARS[mode],0,20);

switch($mode){

 case "general":
   include("blocks/user/settings/general.php");
   break;

 case "profile":
   include("blocks/user/settings/profile.php");
   break;

 case "recommendations":
   include("blocks/user/settings/recommendations.php");
   break;

 case "security":
   include("blocks/user/settings/security.php");
   break;

 case "gpg":
   include("blocks/user/settings/gpg.php");
   break;

 case "buddy_list":
   include("blocks/user/settings/buddy_list.php");
   break;

 default:
   include("blocks/user/settings/general.php");
   break;
}


?>