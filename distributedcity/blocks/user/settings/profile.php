<?php


if ($HTTP_POST_VARS[save_profile]) {

   // Get Incoming Profile Vars
   // Property Name => Max Length,
   $vars = array('email'        => 100,
                 'www'          => 100,
                 'specialties' => 256,
                 'company_association'      => 100,
                 'dmt_usd_claim_number' => 1024,
                 'quote'        => 256,
                 'comments'    => 10240);

   foreach($vars as $property => $max_length) {
      $newval = $HTTP_POST_VARS[$property] ? $HTTP_POST_VARS[$property] : '';
      $profile_data[$property] = substr($newval, 0, $max_length);
   }

   $result = $app->user->set_profile($profile_data);
   if ($result[err]) {
      $error_message = $result[msg];
   }
   else {
      $status_message = $result[msg];
   }

   $mode = "profile_form";

}
else {

   $mode = "profile_form";
}


switch ($mode) {

case "profile_form":
   $block = yats_define($templateBase."user/settings/profile.html");

   // Get profile data
   unset($profile);
   $public_info = new public_info($app->user->get_user_id());

   $result = $public_info->get_data();

   if ($result[err]) {
      $error_message = $result[msg];
      $mode = "profile_error";
   }
   else {
      $profile = $result[msg][profile];

      // HTMLize just in case there are double quotes and the like, so it will not
      // screw up the form name="values"
      foreach($profile as $name => $value)
      $formatted_profile[$name] = htmlentities($value);

      // Do not DC Encode - this is not for display, but for form editing
      yats_assign($block, $formatted_profile);  
   }

   // Error_Message? Assign
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