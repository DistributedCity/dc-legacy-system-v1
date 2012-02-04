<?php

/*
  Startup the system. This file is first every hit
  1) Check session ? Load session : Login
  2) Setup vars/misc things if they need it
  3) Render the header
  4) Let the app continue to wherever it wanted to go
*/

// Include all necessary files
require_once('config/init_config.php');

session_start();
             
// Allow the certain scripts to pass thru and run, no session management or authentication.
if ($SCRIPT_NAME == '/images/proxy_image.php' || $SCRIPT_NAME ==  '/xmlrpc/index.php' || $SCRIPT_NAME ==  '/redirect.php' || $SCRIPT_NAME == '/404.html') {

   $mode = "passthru";

}
else if ($HTTP_GET_VARS[action] == "login") {

   if ($HTTP_POST_VARS[registration]) {
      $mode = "redirect_registration_form";
   }
   else {

      // Destroy old app
      if (session_id())
         session_destroy();

      // Get incoming vars
      $username = substr($HTTP_POST_VARS[username], 0, 1024);
      $password = substr($HTTP_POST_VARS[password], 0, 1024);

      // Instantiate new admin object to check authentication
      $admin = new admin;

      // Check authentication
      $result = $admin->check_password(array("username" => $username, "password" => $password));
      if ($result[err]) {
         $error = $result[msg];
         $mode = "login_form";

      }
      else {

         // Authentication OK: Start a new app
         $app = new app($result[msg]);

         session_register('app');

         // Set config directory in session for exact location on all hits
         $app->site_config_file = $GLOBALS[config][app_base] . "conf/site_config.ini";

         // Ok, New session created - Do some checks first
         // We need to check if the user has a gpg keyring dir or not.
         // Because they may have canceled keygen during registration
         // We are not going to check for a key, because the key, may be
         // still in the queue. We are going to check for a keyring dir.
         // If they have chosen a gpg passphrase, then the keyring dir exists
         // and we know we can let them continue.
         if (!$app->user->gpg->check_pub_key() && !$app->user->gpg->check_key_in_generation_queue()) { // Check for a keyring directory

            $mode = "redirect_registration_gpg_form";

         }
         else if (!$app->user->has_user_image()) {            // Check for the users image

            $mode = "redirect_registration_image_form";

         }
         else {

            $mode = "redirect_frontpage";
         }
      }
   }

}
else if ($HTTP_GET_VARS[action] == "registration") {

session_start();
   if ($HTTP_POST_VARS[register_now]) {

      if(session_id() ) {
         session_destroy();
      }

      // Get incoming data
      $username = substr($HTTP_POST_VARS[username],0,1024);
      $password = substr($HTTP_POST_VARS[password],0,1024);
      $password2 = substr($HTTP_POST_VARS[password_confirm],0,1024);

      if( $password != $password2 ) {
         $error = "Passwords do not match";
         $mode = "registration_user_pass_form";
      }

      else {
         // Instantiate new admin object to check authentication
         $admin = new admin;

         // Check authentication
         $result = $admin->create_user(array("username" => $username, "password" => $password));

         if ($result[err]) {
            $error = $result[msg];
            $mode = "registration_user_pass_form";

         }
         else {
            // Authentication OK: Start a new app
            $app = new app($result[msg]);
            session_register('app');

            // Set config directory in session for exact location on all hits
            $app->site_config_file = $GLOBALS[config][app_base] . "conf/site_config.ini";

         session_write_close();
   //         exit();
            $mode = "redirect_registration_gpg_form";      

         }
      }

   }
   else if ($HTTP_POST_VARS[cancel]) {

      if (session_id())
         session_destroy();

      $mode = "redirect_login_form";

   }
   else {

      $mode = "registration_user_pass_form";
   }  



}
else if ($HTTP_GET_VARS[action] == "registration_gpg") {

   session_start();

   if ($HTTP_POST_VARS[register_gpg]) {

      if ($app) {

         // Get incoming data
         $passphrase = substr($HTTP_POST_VARS[passphrase],0,1024);
         $passphrase_confirm = substr($HTTP_POST_VARS[passphrase_confirm],0,1024);

         // Instantiate new admin object to check authentication
         $admin = new admin;

         // Check authentication
         $result = $app->user->generate_gpg_key(array("passphrase" => $passphrase, "passphrase_confirm" => $passphrase_confirm));

         if ($result[err]) {
            $error = $result[msg];
            $mode = "registration_gpg_form";

         }
         else {

            $gpg_status_message = $result[msg];
            // Authentication OK: Start a new app
            session_register('app');

            $mode = "redirect_registration_image_form";
         }
      } // end if($app)
      else {
         die( "no app found!" );
      }

   }
   else {
      $mode = "registration_gpg_form";

   } // end else HTTP_POST_VARS[register_gpg




}
else if ($HTTP_GET_VARS[action] == "registration_image") {

   session_start();

   if ($HTTP_GET_VARS[image]) {

      if ($app) {

         // Get incoming data
         $image = substr($HTTP_GET_VARS[image],0,1024);
         $group = substr($HTTP_GET_VARS[group],0,1024);


         $result = $app->user->set_user_image($image, $group);
         if (!$result[err]) {
            echo $result[msg];
         }
         else {
            // handle
         }



         if ($result[err]) {
            $error = $result[msg];
            $mode = "registration_image_form";

         }
         else {

            $gpg_status_message = $result[msg];
            // Authentication OK: Start a new app
            session_register('app');
            $mode = "registration_welcome";
         }


      } // end if($app)

   }
   else {

      $mode = "registration_image_form";

   } // end else HTTP_POST_VARS[register_image



}
else {

   // See if there is an existing app already
   session_start();

   if (!$app) {
      session_destroy();
      $mode = "login_form";

   }
   else {
      // Check session if OK: resets session timeout || Error: destroys session
      if ( $app->session->check() ) {
         $mode = "continue_session";
      }
      else {
         if( $app->session->is_stale() ) {
             // too stale to show logged out screen.  just show front page again.
             $mode = 'redirect_frontpage';
         }
         else {
             //$error = "Session is invalid or has expired. Please login again."; 
             logout_screen("expired");
         }
      }
   }
}

$SID = 'SID=' . session_id();

switch ($mode) {


case "redirect_frontpage":
   session_write_close();
   header ("Location: /news/?".$SID);
   break;


case "redirect_login_form":
   header ("Location: /?".$SID);
   break;


case "redirect_registration_form":
   header ("Location: /?action=registration&".$SID);
   break;

case "redirect_registration_gpg_form":
   header ("Location: /?action=registration_gpg&".$SID);
   break;

case "redirect_registration_image_form":
   header ("Location: /?action=registration_image&".$SID);
   break;


case "login_form":

   $block = yats_define($templateBase."master/login_form.html");


   // Assign error if it exists
   if ($error) {
      yats_assign($block, array("error_text" => $error,
      "username"   => $username));
   }

   // Assign hidden vars to the form
   yats_assign($block, array("sid" => session_id()));

   echo yats_getbuf($block);
   exit();
   break;



case "registration_user_pass_form":

   $block = yats_define($templateBase."master/registration_user_pass_form.html");

   if (session_id())
      session_destroy();

   // Assign error if it exists
   if ($error) {
      yats_assign($block, array("error_text" => $error));
   }

   // Assign hidden vars to the form
   yats_assign($block, array("username"     => $username));

   echo yats_getbuf($block);
   exit();
   break;


case "registration_gpg_form":

   $block = yats_define($templateBase."master/registration_gpg_form.html");

   // Assign error if it exists
   if ($error) {
      yats_assign($block, array("error_text" => $error));
   }

   // Assign hidden vars to the form
   yats_assign($block, array("sid"      => session_id(),
   "username" => $username));

   echo yats_getbuf($block);
   exit();
   break;



case "registration_image_form":

   $result = $app->user->html->render_user_image_selection_screen("registration");
   if (!$result[err]) {
      echo $result[msg];
   }
   else {
      // handle
   }


   exit();
   break;



case "registration_welcome":

   $block = yats_define($templateBase."master/registration_welcome.html");
   // Assign hidden vars to the form
   yats_assign($block, array("gpg_status_message" => $gpg_status_message,
   "username" => $app->user->get_username(),
   "sid" => session_id()));

   echo yats_getbuf($block);
   exit();
   break;


case "passthru":
   break;


case "continue_session":
   // Set me as online if my prefs allow it
   $app->user->i_am_online();
   break;


}

?>
