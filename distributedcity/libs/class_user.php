<?php

/* User Class */

class user {

   var $_username;

   var $db;
   var $db_host;
   var $db_user;
   var $db_pass;
   var $db_thread;

   var $gpg;
   var $im;
   var $html;

   function user($user_id) {

      // Load User Info
      $sql = "SELECT id, username, username_hash, access_level, prefs FROM dc_user WHERE id='$user_id'";
      $dbresult = $GLOBALS[db]->query($sql);

      if ( toolbox::db_error(&$dbresult) ) {

      }

      while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
         $this->user_id      =  $entry[id];
         $this->username     =  $entry[username];
         $this->access_level =  $entry[access_level];
         $this->prefs        =  unserialize($entry[prefs]);
      }

    #$this->gpg = new gnupg($this->get_username(), strtolower($this->get_username())."@distributedcity.com");
      $this->gpg = new gnupg($this->get_username(), $this->get_user_id());
      $this->im = new im($this->get_user_id());
      $this->html = new user_html($this->get_user_id());
   }

   function save_prefs() {
      $prefs = serialize($this->prefs);
      $sql = "UPDATE dc_user SET prefs='$prefs' WHERE id='$this->user_id'";
      $res = $GLOBALS[db]->query($sql);

      // TODO add error checking

   }




   function change_password($password_info) {

      // See if password current  password is empty
      if (empty($password_info[password_current])) {
         $error[] = "Please enter your Current password in top box below.";
      }

      // See if passwords are empty
      if (empty($password_info[password_new]) || empty($password_info[password_new_confirm])) {

         $error[] = "Please enter your new password twice in the two lower boxes below.";

         // See if new passwords match
      }elseif($password_info[password_new] != $password_info[password_new_confirm]) {
         $error[] = "New passwords do not match, please enter your new password twice in the boxes below.";
      }

      // Validate Password
      // Length min = 8
      $password_min_length = 8;
      if (strlen($password_info[password_new]) < $password_min_length) {
         $error[] = "Password must be more than $password_min_length characters. Please enter your new password twice in the boxes below.";
      }


      if (!$error) {

         // Try and set the new password  

         // Hash the passwords
         $password_current     = md5($password_info[password_current]);
         $password_new         = md5($password_info[password_new]);

         $dbresult = $GLOBALS[db]->query("UPDATE dc_user SET password='$password_new' WHERE id='$this->user_id' AND password='$password_current'");

         if ( toolbox::db_error(&$dbresult) || !$GLOBALS[db]->affectedRows()) {
            $result[err] = 1;
            $result[msg] = "Invalid Current password.";
         }
         else {
            $result[err] = 0;
            $result[msg] = "Password change successsful.";
         }

      }
      else {

         $result[err] = 1;
         $result[msg] = $error;

      }

      return $result;
   }







   function get_username() {
      return $this->username;
   }

   function get_avatar() {
      return $this->avatar;
   }


   function get_user_id() {
      return $this->user_id;
   }


   function is_admin() {
      if ($this->access_level == "10") {
         return true;
      }
      else {
         return false;
      }
   }


   function is_moderator() {
      if ($this->access_level == "10") {
         return true;
      }
      else {
         return false;
      }
   }



   //  function has_gpg_keyring_directory(){
   //    return $this->gpg->check_private_dir();
   //  }



   function has_gpg_key() {
      return $this->gpg->check_all();
   }


   function generate_gpg_key($passphrase_info) {
      $passphrase         = $passphrase_info[passphrase];
      $passphrase_confirm = $passphrase_info[passphrase_confirm];

      // Validate Passphrase

      // Length min = 8
      $passphrase_min_length = 8;
      if (strlen($passphrase) < $passphrase_min_length) {
         $error[] = "Passphrase must be more than $passphrase_min_length characters.";
      }

      // Check if passphrase entries match
      if ($passphrase != $passphrase_confirm) {
         $error[] = "Passphrases do not match. Please enter the Passphrase in both boxes for confirmation.";
      }

      if (!$error) {
         set_time_limit(0);
         $result = $this->gpg->generate_key($this->get_username(), "", strtolower($this->get_username())."@distributedcity.com", $passphrase);
         if ($result[err]) {
            $error = $result[msg];
         }
      }

      if ($error) {
         $result[err] = 1;
         $result[msg] = $error;
      }

      return($result);
   }


   function i_am_online() {
      if ($this->get_pref(hide_presence) != "1") {

         $dbfile = $GLOBALS[config][online_status_db];

         // Check if db exists, if not, create it
         if (!is_file($dbfile)) {
            toolbox::init_online_status_db();
         }

         // Set the user as online:
         // Set the key as the user_id and the value as the timestamp
         toolbox::dba_set($dbfile, $this->get_user_id(), time() );
      }
   }


   function _save_prefs() {
      // Serialize the preferences
      $serialized_prefs     = serialize($this->prefs);

      // Save to the db
      $dbresult = $GLOBALS[db]->getOne("UPDATE dc_user SET prefs='$serialized_prefs' WHERE id='$this->user_id'");

      // Add Error Checking
   }


   function set_pref($prefs_element, $value) {
      $this->prefs[$prefs_element] = $value;
      $this->_save_prefs();
   }

   function get_pref($prefs_element) {
      return $this->prefs[$prefs_element];
   }

   function has_user_image() {
      if (!toolbox::get_user_image_src($this->get_user_id())) {
         return false;
      }
      else {
         return true;
      }
   }



   function set_user_image($image, $group) {

      $user_image_src = addslashes($group."/".$image.".gif");

      // Save to the db
      $dbresult = $GLOBALS[db]->getOne("UPDATE dc_user SET user_image_src='$user_image_src' WHERE id='$this->user_id'");

      $change_avatar_status = 'Your user image was successfully changed. If you still see your old user image on the screen Click Reload on your browser to refresh the the graphics.';      

      $result[msg] = "";
      return($result);
   }


   function set_profile($data) {

      // Generate SQL from data
      foreach($data as $field => $value) {
         $sql_fields .= addslashes($field) . ", ";
         $sql_values .= "'" . addslashes($value) . "', ";
      }
      // Strip of trailing chars
      $sql_fields = substr($sql_fields, 0, -2);
      $sql_values = substr($sql_values, 0, -2);


      // Mark this user as being active in a specific room.
      $sql = "INSERT into dc_user_public_info (user_id, ". $sql_fields .") values ('$this->user_id', ". $sql_values .")";


      $dbresult = $GLOBALS[db]->getOne($sql);

      // Check if error, if so, attempt an update
      if ( toolbox::db_error(&$dbresult) ) {


         // Generate SQL from data
         foreach($data as $field => $value) {
            $sql_update_string .= addslashes($field) . "='". addslashes($value)."', ";
         }

         // Strip of trailing chars
         $sql_update_string = substr($sql_update_string, 0, -2);

         $sql = "update dc_user_public_info SET ". $sql_update_string ." WHERE user_id='$this->user_id'";
         $dbresult = $GLOBALS[db]->getOne($sql);


         if ( toolbox::db_error(&$dbresult) ) {
            $result[err] = 1;
            $result[msg] = "There was an problem saving your profile information.";
         }

      }

      if (!$result[err])
         $result[msg] = "Your profile information was successfully saved.";


      return($result);
   }



}
?>
