<?php

// Instant Messaging

class im {

   var $user_id;
   var $inbox_folder_id = 1;
   var $saved_folder_id = 2;
   var $sent_folder_id  = 3;
   var $trash_folder_id = 4;
   var $addresses;


   function im($user_id) {
      $this->user_id = $user_id;
      $this->_reload_buddy_list();
      $this->_load_addresses();
   }


   function get_message_headers($folder_id="1", $from_user_id="") {

      $folder_id = addslashes($folder_id);

      switch ($folder_id) {
      case $this->inbox_folder_id:
         // SQL: Sent TO this user FROM another user
         $sql = "SELECT distinct dc_im_messages.user_id_from as from_user_id, dc_user.username as from_username, date, subject, read_flag AS read, dc_im_messages.id as message_id FROM dc_im_messages, dc_user WHERE dc_im_messages.user_id_owner='$this->user_id' AND dc_im_messages.folder_id='$this->inbox_folder_id' AND dc_im_messages.user_id_from=dc_user.id ORDER BY date DESC";
         break;


      case $this->saved_folder_id:
         // SQL: Messages SAVED TO this user FROM another user
         $sql = "SELECT distinct dc_im_messages.user_id_from as from_user_id, dc_user.username as from_username, date, subject, read_flag AS read, dc_im_messages.id as message_id FROM dc_im_messages, dc_user WHERE dc_im_messages.user_id_owner='$this->user_id' AND dc_im_messages.user_id_from!=dc_im_messages.user_id_owner AND dc_im_messages.folder_id='$this->saved_folder_id' AND dc_im_messages.user_id_from=dc_user.id ORDER BY date DESC";
         break;


      case $this->sent_folder_id:
         // SQL: Messages SENT FROM this user TO another user
         $sql = "SELECT distinct dc_im_messages.user_id_to as to_user_ids, date, subject, read_flag AS read, dc_im_messages.id as message_id FROM dc_im_messages, dc_user WHERE dc_im_messages.user_id_owner='$this->user_id' AND dc_im_messages.user_id_from=dc_im_messages.user_id_owner AND dc_im_messages.folder_id='$this->sent_folder_id' AND dc_im_messages.user_id_from=dc_user.id ORDER BY date DESC";
         break;


      case $this->trash_folder_id:
         // SQL: All messages sent to Trash
         $sql = "SELECT distinct dc_im_messages.user_id_from as from_user_id, dc_user.username as from_username, date, subject, read_flag AS read, dc_im_messages.id as message_id FROM dc_im_messages, dc_user WHERE dc_im_messages.user_id_owner='$this->user_id' AND dc_im_messages.user_id_from!=dc_im_messages.user_id_owner AND dc_im_messages.folder_id='$this->trash_folder_id' AND dc_im_messages.user_id_from=dc_user.id ORDER BY date DESC";
         break;

      }


      $dbresult = $GLOBALS[db]->query($sql);

      if ( toolbox::db_error(&$dbresult)) {
         $result[err] = 1;
         $result[msg] = 'Unknown error get message headers.';
      }
      else {

         if (!$dbresult->numRows()) {

            $result[err] = 1;
            $result[msg] = 'No messages found.';

         }
         else {
            while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {

               if ($folder_id == $this->sent_folder_id) {
                  // Get username from user id
                  $entry[to_user_ids] = explode("|", $entry[to_user_ids]);
                  $result_x = $this->get_usernames($entry[to_user_ids]);
                  if (!$result_x[err]) {
                     $entry[to_usernames] = $result_x[msg];
                  }
               }

               $result[msg][] = $entry;
            }
         }
      }

      return($result);
   }


   function get_pm_from_queue($user_id_from) {
      $user_id_from = addslashes($user_id_from);

      $sql = "SELECT distinct date, dc_user.username AS from_username, dc_user.id AS from_user_id,  subject, read_flag AS read, dc_im_messages.id as message_id, dc_im_messages.body FROM dc_im_messages, dc_user WHERE dc_im_messages.user_id_owner='$this->user_id' AND dc_im_messages.user_id_from=dc_user.id AND dc_im_messages.user_id_from='$user_id_from' AND dc_im_messages.read_flag ISNULL ORDER BY date ASC LIMIT 1";

      return $this->_get_message($sql);
   }


   function get_message($message_id) {
      $message_id = addslashes($message_id);

      $sql = "SELECT distinct date, dc_user.username AS from_username, dc_user.id AS from_user_id,  subject, read_flag AS read, dc_im_messages.id as message_id, dc_im_messages.body FROM dc_im_messages, dc_user WHERE dc_im_messages.user_id_owner='$this->user_id' AND dc_im_messages.user_id_from=dc_user.id and dc_im_messages.id='$message_id'";

      return $this->_get_message($sql);
   }

   function _get_message($sql) {


      $dbresult = $GLOBALS[db]->query($sql);

      if ( toolbox::db_error(&$dbresult)) {
         $result[err] = 1;
         $result[msg] = 'Error, could not retrieve your message.';
      }
      else {

         if (!$dbresult->numRows()) {

            $result[err] = 1;
            $result[msg] = 'No messages found.';

         }
         else {
            while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
               $result[msg] = $entry;
            }
         }
      }
      return($result);
   }


   function mark_message_read($message_id) {
      // Mark this message as read 
      $sql = "UPDATE dc_im_messages SET read_flag='yes'  WHERE id='$message_id'";
      $GLOBALS[db]->getOne($sql);
   }






   function get_usernames($recipients) {
      $recipients = is_array($recipients) ? $recipients : array($recipients);
      return toolbox::get_usernames($recipients); 
   }





   function get_folder_name($folder_id) {
      switch ($folder_id) {
      
      case $this->inbox_folder_id:
         $folder_name = "INBOX";
         break;

      case $this->saved_folder_id:
         $folder_name = "Saved";
         break;

      case $this->sent_folder_id:
         $folder_name = "Sent";
         break;

      case $this->trash_folder_id:
         $folder_name = "Deleted";
         break;

      default:
         $folder_name = "Unknown";
         break;
      }
      return($folder_name);
   }





   function send_new_message($message, $recipient_user_ids) {

      $message = toolbox::slash($message);
      $recipient_user_ids = toolbox::slash($recipient_user_ids);

      $date = time();

      // FIRST Send the message to each of the recipient usernames
      foreach($recipient_user_ids as $recipient_user_id) {

         $sql = "INSERT INTO dc_im_messages (date, user_id_owner, user_id_to, user_id_from, folder_id, subject, body) 
             values ('$date', '$recipient_user_id', '$recipient_user_id', '$this->user_id', '$this->inbox_folder_id', '$message[subject]', '$message[body]')";

         $dbresult = $GLOBALS[db]->query($sql);

         if ( toolbox::db_error(&$dbresult) || !$GLOBALS[db]->affectedRows() ) {
            $result[err] = 1;
            $result[msg] = 'Unknown error, could not add send your message.';
         }
         else {
            $result[err] = 0;
            $result[msg] = 'Your message was successfully added.';

            // Send the user a notification of a new message
            $this->notify_new_message($recipient_user_id);
         }

      }



      // NEXT Save copies of sent messages into the SENT FOLDER
      //    reset($recipient_user_ids);
      //foreach($recipient_user_ids as $recipient_user_id){

      // Format recipient list for the DB using delimiter |
      $recipients = implode("|", $recipient_user_ids);

      $sql = "INSERT INTO dc_im_messages (date, user_id_owner, user_id_to, user_id_from, folder_id, subject, body, read_flag) 
             values ('$date', '$this->user_id', '$recipients', '$this->user_id', '$this->sent_folder_id', '$message[subject]', '$message[body]', 'yes')";
      //    print $sql;
      $dbresult = $GLOBALS[db]->query($sql);

      //}

      return $result;
   }












   function get_user_ids($usernames) {
      // No need to ADDSLASHES, since we are hashing the value before we send to db querey anyway.

      if (!is_array($usernames))
         $usernames = array($usernames);


      do {

         $username = current($usernames);

         $username_hash = md5(strtolower(trim($username)));

         // SQL: Sent TO this user FROM another user
         $sql = "SELECT id FROM dc_user WHERE username_hash='$username_hash'";


         $dbresult = $GLOBALS[db]->query($sql);

         if ( toolbox::db_error(&$dbresult)) {
            $result[err] = 1;
            $result[msg] = 'Unknown error, could find the user_ids.';

         }
         else {

            if (!$dbresult->numRows()) {

               $usernames_not_found[] = $username;
               $error = 1;

            }
            else {
               while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
                  $data[] = $entry[id];
               }
               $result[msg] = $data;
            }
         }
      }while (next($usernames));

      if ($error) {
         $result[err] = 1;
         $result[msg] = "Could not find users: ". implode(", ", $usernames_not_found);

      }


      return($result);
   }


   function save_message($message_id) {
      return $this->move_message($message_id, "2");
   }



   function delete_message($message_id) {
      return $this->move_message($message_id, "4");
   }

   function move_message($message_id, $folder_id) {

      $message_id = addslashes($message_id);
      $folder_id  = addslashes($folder_id);
      $sql = "UPDATE dc_im_messages SET folder_id='$folder_id'  WHERE id='$message_id' AND user_id_owner='$this->user_id'";

      // "SAVED" folder has id of 2
      $dbresult = $GLOBALS[db]->query($sql);

      return($dbresult);
   }



   function empty_trash() {

      // "DELETED" folder has id of 4 - KILL EVERYTHING IN THIS FOLDER OWNED BY THIS USER
      $dbresult = $GLOBALS[db]->query( "DELETE FROM dc_im_messages  WHERE folder_id='4' AND user_id_owner='$this->user_id'");
   }


   function notify_new_message($user_id) {
      $user_id = addslashes($user_id);
      $result = $GLOBALS[db]->getOne( "insert into dc_im_notify (user_id, user_id_from) values ('$user_id', '$this->user_id')");
   }



   function get_message_notifications() {

      $sql = "SELECT distinct count(*) as message_count, dc_im_messages.user_id_from as user_id, dc_user.username as username FROM dc_im_messages, dc_user WHERE dc_im_messages.user_id_owner='$this->user_id' AND dc_im_messages.user_id_from!=dc_im_messages.user_id_owner AND dc_im_messages.folder_id='1' AND dc_im_messages.user_id_from=dc_user.id AND dc_im_messages.read_flag ISNULL  GROUP BY user_id_from, dc_user.username";

      $dbresult = $GLOBALS[db]->query($sql);

      if ( toolbox::db_error(&$dbresult) || $dbresult->numRows() == 0) {

         $result[err] = 1;
         $result[msg] = 'No messages.';

      }
      else {

         while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {

            // Set Notifications ID's
            $notifications[$entry[user_id]] = array("username"      => $entry[username],
            "message_count" => $entry[message_count]);

            //$data[]$entry[user_id] = $entry[message_count];

            // Set Notifications Data
            //	$data[data][$entry[user_id]] = $entry[username];

            //	$notifications[] = $data;

         }
         if (!empty($notifications)) {
            $result[msg] = $notifications;
         }
         else {
            $result[err] = 1;
            $result[msg] = 'No messages.';
         }
      }
      return($result);
   }


   function remove_buddy($user_id) {

      $user_id = addslashes($user_id);

      // Remove this buddy from the buddy list
      $dbresult = $GLOBALS[db]->query( "DELETE FROM dc_im_buddy_list  WHERE user_id='$this->user_id' AND user_id_buddy='$user_id'");

      if ( toolbox::db_error(&$dbresult) ) {
         $result[err] = 1;
         $result[msg] = "There was an problem removing the User to your Buddy List.";
      }
      else {
         $this->_reload_buddy_list();
         $result[msg] = "The User was successfully removed to your Buddy List.";
      }


      return $result;
   }



   function add_buddy($user_id) {
      $user_id = addslashes($user_id);

      // Check that the user is not already a buddy
      $dbresult = $GLOBALS[db]->getOne( "SELECT count(id) FROM dc_im_buddy_list WHERE user_id='$this->user_id' AND user_id_buddy='$user_id'");

      if ($dbresult == "0") { // User is not already a buddy, go ahead and add the buddy to the users list

         $dbresult = $GLOBALS[db]->query( "insert into dc_im_buddy_list (user_id, user_id_buddy) values ('$this->user_id', '$user_id')");

         if ( toolbox::db_error(&$dbresult) ) {
            $result[err] = 1;
            $result[msg] = "There was an problem adding this User to your Buddy List.";
         }
         else {
            $result[msg] = "The User was successfully added to your Buddy List.";
         }

         $this->_reload_buddy_list();

      }
      else {

         $result[err] = 1;
         $result[msg] = "There user appears to already be in your Buddy List!";
      }

      return $result;
   }



   function _reload_buddy_list() {

      // FIXME: CLEAN THIS STUFF UP!

      $sql = "select dc_im_buddy_list.user_id_buddy as user_id, dc_user.username FROM dc_im_buddy_list, dc_user WHERE dc_user.id=dc_im_buddy_list.user_id_buddy AND dc_im_buddy_list.user_id='$this->user_id'";

      $dbresult = $GLOBALS[db]->query($sql);

      if ( toolbox::db_error(&$dbresult)) {
         $result[err] = 1;
         $result[msg] = 'Unknown error, could find buddies.';

      }
      else {

         if ($dbresult->numRows()) {
            while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
               $data[$entry[user_id]] = array("username"     => $entry[username],
               "online_status" => $this->is_online($entry[user_id]));
            }
            $this->buddy_list =  $data;
         }
         else {
            unset($this->buddy_list);
            $result[err] = 1;
            $result[msg] = 'Unknown error, could not find buddies.';
         }
      }
   }



   function is_online($user_id) {
      $timeout = time() - (60 * 15); // 15 minute timeout

      $dbfile = $GLOBALS[config][online_status_db];

      $last_seen = toolbox::dba_get($dbfile, $user_id);

      if ($last_seen) { // Ok, found an last seen entry for this userid
         if ($last_seen > $timeout) { // Ok, looks like the user is online
            return "1";
         }
         else { // Looks like the last seen date for this user is expired
            // Delete this entry, keeps the db clean	
            toolbox::dba_delete($dbfile, $user_id);
            return "0";
         }

      }
      else {
         return "0";
      }
   }



   function delete_address($address_entry_id) {

      print $address_entry_id;

      if ($this->addresses[$address_entry_id]) {
         unset($this->addresses[$address_entry_id]);
         $result[msg] = "Entry deleted.";
      }
      else {
         $result[err] = 1;
         $result[msg] = "No address with that entry id.";
      }

      return $result;
   }


   function add_address($address_string) {

      // Get addresss user ids
      $address_usernames = explode(",", $address_string);

      // Lookup user_id's from incoming username recipient list
      $result = $this->get_user_ids($address_usernames);

      if ($result[err]) {
         // ERROR: Problem finding all the user_ids
         $add_result[err] = 1;
         $add_result[msg] = $result[msg];

      }
      else {
         // OK: Get the recipient ids found
         $entry_data = implode("|", $result[msg]);
         $entry_id   = md5($entry_data);
         $this->addresses[$entry_id] = $entry_data;
         $this->_save_addresses();
         $add_result[msg] = "Entry added ok.";
      }

      return($add_result);

   }



   function _load_addresses() {
      $dbresult = $GLOBALS[db]->query( "SELECT addresses FROM dc_im_addresses WHERE user_id='$this->user_id'");

   }

   function _save_addresses() {

      // Serialize Data
      $data = serialize($this->addresses);

      // Mark this user as being active in a specific room.
      $dbresult = $GLOBALS[db]->getOne( " insert into dc_im_addresses (user_id, addresses) values ('$this->user_id', '$data') ");

      // Check if error, if so, attempt an update
      if ( toolbox::db_error(&$dbresult) ) {
         $dbresult = $GLOBALS[db]->getOne( "update dc_im_addresses SET addresses='$data' WHERE user_id='$this->user_id'");

         if ( toolbox::db_error(&$dbresult) ) {
            $result[err] = 1;
            $result[msg] = "There was an problem saving your addresses.";
         }
         else {
            $result[msg] = "Your addresses were successfully saved.";
         }
         return($result);
      }
   }


}

?>