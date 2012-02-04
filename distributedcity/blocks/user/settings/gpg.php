<?php

// Render Submenu
function render_gpg_submenu(&$block, $tab) {
   $gpg_submenu = yats_define($GLOBALS[templateBase]."user/settings/gpg_submenu.html");

   // Hide menus
   $sections = array("General", "Keyring", "Test");
   foreach($sections as $section) {
      if ($tab != $section) {
         yats_hide($gpg_submenu, "gpgTabMenu".$section, true);
      }
   }

   yats_assign($block, array("gpg_submenu" => yats_getbuf($gpg_submenu)));
}


if ($HTTP_POST_VARS[save_changes]) { // Save encryption settings

   // Subject encryption is dependent on body decryption,
   // so if body encryption is OFF, subject encryption will
   // also be turned off.

   if ($HTTP_POST_VARS[encrypt_body] == "yes") {
      $app->user->set_pref("gpg_encrypt_body", "on");
   }
   else {
      $app->user->set_pref("gpg_encrypt_body", "off");
      $app->user->set_pref("gpg_encrypt_subject", "off");
   }


   if ($HTTP_POST_VARS[encrypt_subject] == "yes" &&   $HTTP_POST_VARS[encrypt_body] == "yes") {
      $app->user->set_pref("gpg_encrypt_subject", "on");

   }
   else if ($HTTP_POST_VARS[encrypt_subject] == "no" &&   $HTTP_POST_VARS[encrypt_body] == "yes") {

      $app->user->set_pref("gpg_encrypt_subject", "off");
   }







   $form = "main";


}
else if ($HTTP_POST_VARS[change_passphrase]) {     // Passphrase change request?

   // Get passphrase data
   $passphrase_old         = substr($HTTP_POST_VARS[passphrase_old],0,1024);
   $passphrase_new         = substr($HTTP_POST_VARS[passphrase_new],0,1024);
   $passphrase_new_confirm = substr($HTTP_POST_VARS[passphrase_new_confirm],0,1024);

   if (empty($passphrase_old )) {
      $passphrase_change_error[] = "Current passphrase was empty.";
   }

   // Check that both new passphrases match
   if ($passphrase_new != $passphrase_new_confirm) {
      $passphrase_change_error[] = "New passphrases do not match.";
   }

   if (empty($passphrase_new) || empty($passphrase_new) ) {
      $passphrase_change_error[] = "New passphrases were empty.";
   }


   // Validate Password
   // Length min = 8
   $password_min_length = 8;
   if (strlen($password) < $password_min_length) {
      $error[] = "Password must be more than $password_min_length characters. Please enter twice for confirmation.";
   }




   if (!$passphrase_change_error) {
      $result = $app->user->gpg->change_passphrase($passphrase_old, $passphrase_new);

      // Clear the passphrase cache
      if ($app->session->passphrase_cache)
         unset($app->session->passphrase_cache);

      if ($result[err]) {
         $passphrase_change_error = $result[msg];
      }
      else {
         $passphrase_change_ok = $result[msg];
      }

   }


   $form = "main";

}
else if ($HTTP_POST_VARS[encrypt_test]) {
   // Get plaintext
   $message_plaintext = $HTTP_POST_VARS[message_plaintext];

   $result = $app->user->gpg->encrypt_message($app->user->get_username() ."@distributedcity.com", $message_plaintext);
   if ($result[err]) {
      $error[] = $result[msg];
   }
   else {
      $message_ciphertext = $result[ciphertext];
   }

   $form = "encrypt_test";


}
else if ($HTTP_POST_VARS[decrypt_test]) {

   // Get plaintext
   $message_ciphertext = $HTTP_POST_VARS[message_ciphertext];
   $passphrase = $HTTP_POST_VARS[passphrase];

   $result = $app->user->gpg->decrypt_message($message_ciphertext, $passphrase);

   if ($result[err]) {
      $error[] = $result[msg];
      $form = "encrypt_test";    
   }
   else {
      $message_plaintext = $result[msg];
      $form = "decrypt_test";
   }


}
else if ($HTTP_GET_VARS[action] == "init_encrypt_decrypt_test") {

   $form = "init_encrypt_decrypt_test";



}
else if ($HTTP_GET_VARS[action] == "keyring") {

   $form = "keyring";



}
else if ($HTTP_GET_VARS[action] == "view_key") {

   // Get incoming KEYID of key to view
   $keyid = substr($HTTP_GET_VARS[keyid],0,16);
   $form = "view_key";

}
else {

   $form = "main";
}





switch ($form) {

case "main":

   $block = yats_define($templateBase."user/settings/gpg.html");

   yats_assign($block, array("encrypt_subject_checked_flag" => $app->user->get_pref("gpg_encrypt_subject") == "on" ? "CHECKED" : "",
   "encrypt_body_checked_flag" => $app->user->get_pref("gpg_encrypt_body") == "off" ? "" : "CHECKED"));


   $block_public_key_info = yats_define($templateBase."user/settings/gpg_view_key.html");

   if (!$app->html->assign_block_all_key_info($app, $block_public_key_info, $app->user->get_username()."@distributedcity.com")) {
      // Problem, hide the rest of the gpg settings
      yats_hide($block, "additional_settings", true);
   }

   render_gpg_submenu($block_public_key_info, "General");
   yats_assign($block, array("public_key_info_block" => yats_getbuf($block_public_key_info)));


   if ($passphrase_change_error) {
      yats_assign($block, array("passphrase_change_error" => $passphrase_change_error));
   }
   else if ($passphrase_change_ok) {
      yats_assign($block, array("passphrase_change_ok" => $passphrase_change_ok));
   }

   break;



case "keyring":

   $block = yats_define($templateBase."user/settings/gpg_keyring.html");
   $block_pub = yats_define($templateBase."user/settings/gpg_keyring_pubkey.html");
   $block_sub = yats_define($templateBase."user/settings/gpg_keyring_subkey.html");
   $block_uid = yats_define($templateBase."user/settings/gpg_keyring_uid.html");

   // Get keyring info
   $result = $app->user->gpg->list_keys(); // Do not pass any arguments, so we get ALL Public keys on keyring

   if (!$result) {
      $error = $result[msg];
   }
   else {
      $keyring_data = $result[msg];
   }

   foreach($keyring_data as $user_key) {
      unset($pubkey_data);

      // Do Pubkey data block

      // HTML encode the user id for display
      $user_key[pubkey]['user-id'] = htmlentities($user_key[pubkey]['user-id']);

      // fix for SF bug 571560, elgamal length not displayed.
      if( isset($user_key['sub_keys'][0]['length'] )) {
         $user_key['pubkey']['length'] .= '/' . $user_key['sub_keys'][0]['length'];
      }

      yats_assign($block_pub, $user_key[pubkey]);
      $pubkey_data .= yats_getbuf($block_pub);

      // Do Subkey data blocks
      if ($user_key[sub_keys]) {
         foreach($user_key[sub_keys] as $subkey) {
            yats_assign($block_sub, $subkey);
            $pubkey_data .= yats_getbuf($block_sub);
         }
      }

      // Do additional Userid data blocks
      if ($user_key[additional_user_ids]) {
         foreach($user_key[additional_user_ids] as $userid) {

            // HTML encode the user id for display
            $userid['user_id'] = htmlentities($userid);

            yats_assign($block_uid, $userid);
            $pubkey_data .= yats_getbuf($block_uid);
         }
      }
   }

   render_gpg_submenu($block, "Keyring");

   yats_assign($block, array("pubkey_block" => $pubkey_data));
   break;




case "encrypt_test":
   $block = yats_define($templateBase."user/settings/gpg_encrypt_decrypt_form.html");
#   yats_hide($block, "test_decrypt_form", true);
   render_gpg_submenu($block, "Test");
   yats_assign($block, array("title" => "Encrypted Message:",
   "instructions" => "You have completed the GPG Encrypt/Decrypt test. The original text you entered should be displayed below, decrypted. More dedicated GPG testers can continue testing to their heart's content.",
   "message_ciphertext" => $message_ciphertext,
   "username" => $app->user->get_username()));
   break;


case "decrypt_test":
   $block = yats_define($templateBase."user/settings/gpg_encrypt_decrypt_form.html");
#   yats_hide($block, "test_encrypt_form", true);
   render_gpg_submenu($block, "Test");
   yats_assign($block, array("title" => "Decrypted Message:",
   "instructions" => "You have completed the GPG Encrypt/Decrypt test. The original text you entered should be displayed below, decrypted. More dedicated GPG testers can continue testing to their heart's content.",
   "message_plaintext" => $message_plaintext,
   "username" => $app->user->get_username()));


   break;


case "init_encrypt_decrypt_test":
   $block = yats_define($templateBase."user/settings/gpg_encrypt_decrypt_form.html");
   render_gpg_submenu($block, "Test");
   yats_assign($block, array("title" => "Encryption/Decryption Test:",
   "instructions" => "This test allows you to run the GPG system through its paces. Enter some text in the box below and press 'Encrypt Message' to continue the test.",
   "message_plaintext" => "Please enter some text here to test.",
   "username" => $app->user->get_username()));
   break;



case "view_key":

   $block = yats_define($templateBase."user/settings/gpg_view_key.html");
   render_gpg_submenu($block, "Keyring");
   $app->html->assign_block_all_key_info($app, $block, $keyid);

   break;


}



if ($error) {
   yats_assign($block, array("error_message" => $error));
}

yats_assign($block, array("sid"    =>  session_id()));
echo yats_getbuf($block);



?>