<?php

/*
  IM (Instant Messaging) Block
*/

unset($mode);
unset($data);

// Save a message?
if ($HTTP_POST_VARS[save_message]) {
   // Get the message id
   $save_message_id = substr($HTTP_POST_VARS[mid],0,100);
   $result = $app->user->im->save_message($save_message_id);
   // TODO - Show "Message was saved" message
   if ($result) {
   }
   else {
   }
}



// Delete a message?
if ($HTTP_POST_VARS[delete_message]) {
   // Get the message id
   $delete_message_id = substr($HTTP_POST_VARS[mid],0,100);
   $result = $app->user->im->delete_message($delete_message_id);
   // TODO - Show "Message was saved" message
   if ($result) {
   }
   else {
   }
}



// TODO Validate incoming and required data
$new_message_to               = $HTTP_POST_VARS[new_message_to];
$new_message[encrypt_subject] = $HTTP_POST_VARS[encrypt_subject];
$new_message[encrypt_message] = $HTTP_POST_VARS[encrypt_message];
$new_message[subject]         = $HTTP_POST_VARS[subject];
$new_message[body]            = $HTTP_POST_VARS[message];
$new_message[enable_ico]      = $HTTP_POST_VARS['enable_ico'];



// Mode?
if ($HTTP_GET_VARS[action] == "addresses") {


   // Add addresses to addresses???
   if ($HTTP_POST_VARS[add_new_address]) {

      // Get the address string
      $address_string = substr($HTTP_POST_VARS[new_address],0, 1024);

      $result = $app->user->im->add_address($address_string);

      if ($result[err]) {
         // ERROR: Problem finding all the user_ids
         $error_message = $result[msg];

      }
      else {
         $status_message = $result[msg];
      }
   }





   // Delete an address entry?
   if ($HTTP_GET_VARS[delete_address]) {
      // Get the address string
      $address_entry_id = substr($HTTP_GET_VARS[delete_address],0, 41);

      $result = $app->user->im->delete_address($address_entry_id);

      if ($result[err]) {
         $error_message = $result[msg];
      }
      else {
         $status_message = $result[msg];
      }

   }

   $mode = "addresses";

}
else if ($HTTP_GET_VARS[action] == "directory") {
   $mode = "directory";

}
else if ($HTTP_POST_VARS["empty_deleted_box"]) {

   $mode = "empty_deleted_box_confirm";

}
else if ($HTTP_POST_VARS[empty_deleted_box_confirm]) {

   $result = $app->user->im->empty_trash();
   $mode = "folder";



}
else if ($HTTP_GET_VARS[action] == "compose") {

   // See if there are any incoming recipient requests
   unset($recipients);
   if ($HTTP_GET_VARS[uid]) {
      $recipients_string = substr($HTTP_GET_VARS[uid],0,100);
      $recipients = explode("|", $recipients_string);

      // Get the recipients by user id
      $result = $app->user->im->get_usernames($recipients);
      if (!$result[err]) {
         $recipient_usernames = $result[msg];
      }


   }
   $mode = "compose";




}
else if ($HTTP_GET_VARS[action] == 'read' || $HTTP_POST_VARS[decrypt_message]) {

   // Read the oldest unread message from a specific user?
   if ($HTTP_GET_VARS[uidf]) {
      $user_id_from = substr($HTTP_GET_VARS[uidf],0,100);
      $result = $app->user->im->get_pm_from_queue($user_id_from);
      $read_mode_action = "read&uidf=".$user_id_from;

   }
   else {

      // Read a specific Message
      $message_id = substr($HTTP_GET_VARS[mid],0,100);
      if (empty($message_id)) {
         $message_id = substr($HTTP_POST_VARS[mid],0,100);
      }

      // Attempt to retrieve the message
      $result = $app->user->im->get_message($message_id);
      $read_mode_action = 'folder';
   }

   if ($result[err]) {
      $error = 'error getting message';
      $mode = 'folder';

   }
   else {

      $message = $result['msg'];
      $mode = 'read';

      // If message is unread, mark as read
      if ($message['read'] == null) {
         // Mark as read

         $app->user->im->mark_message_read($message[message_id]);
         // Set read flag for message display, no need to re-retrieve the message
         $message['read'] = 't';
      }


      // Decrypt Message?
      if ($HTTP_POST_VARS['decrypt_message'] || strstr($message['body'], '-----BEGIN PGP MESSAGE-----')) {

         // Attempt to get any new incoming passphrase
         if ($HTTP_POST_VARS['passphrase']) {

            $passphrase = substr($HTTP_POST_VARS['passphrase'],0,1024);

            // Cache passphrase?
            if ($HTTP_POST_VARS['cache_passphrase']) {
               $app->session->passphrase_cache = $passphrase;
            }

         }
         else if (empty($app->session->passphrase_cache)) {
            // Problem, there was no incoming passphrase, neither is there one in the cache
            // Tell the user we need a passphrase
            $error = 'Missing Passphrase, please enter the correct passphrase below.';
         }
         else if (!empty($app->session->passphrase_cache)) {
            $passphrase = $app->session->passphrase_cache;
         }


         if (!$error) {
            $result = $app->user->gpg->decrypt_message($message['body'], $passphrase);

            if ($result[err]) {
               // Dump passphrase from cache
               if ( isset($app->session->passphrase_cache) ) {
                  unset($app->session->passphrase_cache);
               }

               $error = $result['msg'];
            }
            else {
               // Replace the ciphertext with the plaintext
               $message[body] = $result['msg'];
            }
         }//end if no error
      }// end if post vars decrypt message


   }


}

else if ($HTTP_GET_VARS[action] == 'preview' && !$HTTP_POST_VARS['cancel']) {

   // Turn recipient list into an array
   $recipient_usernames = explode(",", $new_message_to);

   // Lookup user_id's from incoming username recipient list
   $result = $app->user->im->get_user_ids($recipient_usernames);

   if ($result[err]) {
      // ERROR: Problem finding all the user_ids
      $compose_error = $result[msg];
      $mode = 'compose';

   }
   else {
      $new_message['users'] = implode($result[msg], ',');
      $mode = 'preview';
   }
}

else if ($HTTP_GET_VARS[action] == 'send' && !$HTTP_POST_VARS['cancel']) {


   // TODO Validate incoming and required data
   $new_message_to               = $HTTP_POST_VARS['new_message_to'];
   $new_message['encrypt_subject'] = $HTTP_POST_VARS['encrypt_subject'];
   $new_message['subject']         = $HTTP_POST_VARS['subject'];
   $new_message['enable_ico']    = $HTTP_POST_VARS['enable_ico'];
   $new_message['body']            = $HTTP_POST_VARS['message'];

   // Turn recipient list into an array
   $recipient_usernames = explode(',', $new_message_to);

   // Lookup user_id's from incoming username recipient list
   $result = $app->user->im->get_user_ids($recipient_usernames);

   if ($result[err]) {
      // ERROR: Problem finding all the user_ids
      $compose_error = $result[msg];
      $mode = 'compose';

   }
   else {
      // OK: Get the recipient ids found
      $recipient_user_ids = $result[msg];


      // ENCRYPT THE MESSAGE TO ALL THE RECIPIENTS?
      if ($HTTP_POST_VARS[encrypt_message]) {

         $text_to_encrypt =  $new_message[body];

         // Encrypt the subject also?
         if ($HTTP_POST_VARS[encrypt_subject]) {

            // Add the subject to the body with the prefix text of Subject:
            $text_to_encrypt = 'Encrypted Subject: ' . $new_message[subject] . "\n\n" .$text_to_encrypt;

            // Set the subject to "Encrypted Subject"
            $new_message[subject] = 'Encrypted Subject.';
         }


         $result = $app->user->gpg->encrypt_message($recipient_usernames, $text_to_encrypt);


         if ($result[err]) {
            $compose_error = $result[msg];
         }


         if (!$compose_error) {
            // Replace the old plaintext body with the new ciphertext
            $new_message[body] = $result[ciphertext];
            $encryption_status_message = $result[msg];
         }
      }

      if (!$compose_error) {

         // Attempt to send the message now
         $result = $app->user->im->send_new_message($new_message, $recipient_user_ids);

         if ($result[err]) {
            $compose_error = $result[msg];
            $mode = "compose";
         }
         else {
            $compose_message = "Message successfully sent.";
            $compose_message .= "<BR>".$encryption_status_message;
            $mode = "folder";
         }

      }
      else {
         // Error - go back to compose
         $mode = "compose";
      }

      //} // else encrypt message error

   }// else lookup userids error


}
else if( $HTTP_GET_VARS[action] == 'send' && $HTTP_POST_VARS['cancel'] ) {
   $mode = 'compose';
}
else {
   $mode = 'folder';
}


switch ($mode) {

case 'preview':
   $block = yats_define($GLOBALS[templateBase] . "messaging/preview_post.html");

   $user = toolbox::get_app_user();

   yats_assign($block, 
               array('user_image_src' => toolbox::get_user_image_src($user->user_id),
                     'subject'        => $GLOBALS[app]->html->dc_encode($new_message[subject], 'subject'),
                     'poster_username'=> $user->username,
                     'poster_id'      => $user->user_id,
                     'date_time'      => toolbox::make_date( time() ),
                     'recipients'      => $GLOBALS[app]->html->dc_encode($new_message[users], 'recipients'),
                     'body'           => $GLOBALS[app]->html->dc_encode($new_message[body], 'message_body') ));

   $hidden_vars = array('encrypt_subject' => toolbox::html_hidden_field_escape($new_message['encrypt_subject']),
                        'encrypt_message' => toolbox::html_hidden_field_escape($new_message['encrypt_message']),
                        'subject' => toolbox::html_hidden_field_escape($new_message['subject']),
                        'new_message_to' => toolbox::html_hidden_field_escape($HTTP_POST_VARS[new_message_to]),
                        'message' => toolbox::html_hidden_field_escape($new_message['body']) );

   foreach($hidden_vars as $key => $val) {
      yats_assign($block, array('hidden_name' => $key,
                                'hidden_value' => $val));
   }

   $preview = yats_getbuf($block);

   yats_assign($block_main, array('blog_preview' => $preview));

   echo yats_getbuf($block);

   break;

case "folder":

   $block = yats_define($templateBase."messaging/folder.html");

   if ($compose_message) {
      yats_assign($block, "status_message", $compose_message);
   }


   if ($HTTP_GET_VARS[folder]) {
      $app->session->active_folder_id = substr($HTTP_GET_VARS[folder],0,10);
   }
   else if (empty($app->session->active_folder_id)) {
      $app->session->active_folder_id = "1";
   }

   $result = $app->user->im->get_message_headers($app->session->active_folder_id);


   if ($result[err]) {

      yats_hide($block, "message_headers", true);

   }
   else {

      $messages = $result[msg];
      do {
         $message = current($messages);
         $data[to_from_username][] = $app->session->active_folder_id == 3 ? implode(", ", $message[to_usernames]) : $message[from_username];

         $to_from_user_id = $app->session->active_folder_id == 3 ? $message[to_user_ids][0] : $message[from_user_id];
         $data[to_from_user_id][] = $to_from_user_id;

         $data[to_from_user_image_src][] = toolbox::get_user_image_src($to_from_user_id);

         $data[read][]          = $message[read] == "t" ? "eee": "rrr";
         $data[date][]          = toolbox::make_folder_date($message[date]);

         $data[message_id][]    = $message[message_id];

         // Missing subject?
         $message[subject] = $message[subject] ? $app->html->dc_encode($message[subject]) : "No Subject.";

         // Format read/unread message
         $data[subject][]       = $message[read] == "t" ? $message[subject] : "<B>".$message[subject]."</B>";

         $data[body][]          = $message[body] ? $app->html->dc_encode($message[body], "message_body") : "No Body.";

      }while (next($messages));



      yats_assign($block, array("message_to_from_user_image_src" => $data[to_from_user_image_src],
      'message_id'            => $data[message_id],
      'message_date'          => $data[date],
      'message_read'          => $data[read],
      'message_to_from_user_id'  => $data[to_from_user_id],
      'message_to_from_username' => $data[to_from_username],
      'message_subject'       => $data[subject],
      'message_body'          => $data[body]
      ));

      yats_hide($block, "message_headers_none", true);

   }
   // Assign From/To Column Label (3=Sent Box show To: | all others show From:)
   yats_assign($block, array('from_to_column_label' => $app->session->active_folder_id == 3 ? 'To' : 'From',
                             'folder_name'          => $app->user->im->get_folder_name($app->session->active_folder_id)));

   // Show "EMPTY TRASH OPTION" on Deleted Screen
   if ($app->session->active_folder_id != 4) {
      yats_hide($block, 'empty_deleted_box', true);
   }

   yats_assign($block, array('sid' => session_id())); 


   echo yats_getbuf($block);

   break;




case 'compose':
   $block = yats_define($templateBase.'master/general_compose.html');
   $javascript = yats_define($templateBase.'master/formatting_javascript.html');
   yats_assign($block, array('formatting_javascript'    => yats_getbuf($javascript)));


   yats_hide($block, 'forum_page_prompt', true);
   yats_hide($block, 'author', true);
   yats_hide($block, 'title', true);
   yats_hide($block, 'crosspost', true);



   yats_hide($block, 'forum_topic_prompt', true);
   yats_hide($block, 'article_comment_prompt', true);
   yats_hide($block, 'forum_comment_prompt', true);



   // If recipients, format for To: display
   if (!empty($recipients)) {
      $new_message_to = implode(", ", $recipient_usernames);
   }


   if ($compose_error) {
      yats_assign($block, array("error_message"   => $compose_error));
   }

   yats_assign($block, array('submit_button_text' => 'Preview Message',
                             'username'           => $app->user->get_username(),
                             'new_message_to'     => toolbox::html_hidden_field_escape($new_message_to),
                             'subject_content'    => toolbox::html_hidden_field_escape($new_message['subject']),
                             'message_content'    => toolbox::html_hidden_field_escape($new_message['body']),
                             'sid'                => session_id(),
                             'encrypt_subject_checked_flag' => $app->user->get_pref('gpg_encrypt_subject') == 'on' ? 'CHECKED' : '',
                             'encrypt_body_checked_flag'    => $app->user->get_pref('gpg_encrypt_body') == 'off' ? '' : 'CHECKED',
                             'form_action'                  => '?action=preview'));

   echo yats_getbuf($block);
   break;




case 'read':
   $block = yats_define($templateBase.'messaging/read.html');

   $javascript = yats_define($templateBase.'master/formatting_javascript.html');
   yats_assign($block, array('formatting_javascript'    => yats_getbuf($javascript)));

   $data[from_username] = $message[from_username];
   $data[from_user_id]  = $message[from_user_id];


   $data[date]          = toolbox::make_folder_date($message[date]);

   $data[message_id]    = $message[message_id];

   // Missing subject?
   $data[subject] = $message[subject] ?  $app->html->dc_encode($message[subject]) : 'No Subject.';

   // Format read/unread message
   //$data[subject]       = $message[read] == 't' ? $message[subject] : '<B>'.$message[subject].'</B>';

   if (strstr($message[body], '-----BEGIN PGP MESSAGE-----')) {
      $data[body] = '<PRE>' . htmlentities($message[body]) . '</PRE>';
   }
   else {

      $hide_passphrase_box = true; // Never show the passphrase box regardless, if there is no pgp data
      $data[body]          = $message[body] ?  $app->html->dc_encode($message[body], 'message_body') : 'No Body.';

   }

   // Format body for reply
   if (!empty($message[body])) {
      $reply_body = "On ". date("l, F d Y, g:ia", $message[date]) .", {$data[from_username]} scribbled:\n" . $message[body];
      $reply_body =  str_replace("\n", "\n> ", $reply_body) . "\n\n";
   }

   $subject = (strcasecmp( substr($message['subject'], 0, 3), 're:') == 0) ? 
                  $message['subject'] : 
                  'Re: ' . $message['subject'];

   yats_assign($block, array('user_image_src'        => toolbox::get_user_image_src($data[from_user_id]),
                             'message_id'            => $data[message_id],
                             'message_date'          => $data[date],
                             'message_read'          => $data[read],
                             'message_from_user_id'  => $data[from_user_id],
                             'message_from_username' => $data[from_username],
                             'message_subject'       => $data[subject],
                             'message_body'       => $data[body],
                             'new_message_to'        => $data[from_username],
                             'new_message_subject'   => $subject,
                             'new_message_body'      => $reply_body,
                             'sid'                   => session_id(),
                             'encrypt_subject_checked_flag' => $app->user->get_pref('gpg_encrypt_subject') == 'on' ? 'CHECKED' : '',
                             'encrypt_body_checked_flag'    => $app->user->get_pref('gpg_encrypt_body') == 'off' ? '' : 'CHECKED',
                             'action'                => $read_mode_action,
                             'form_action'              => '?action=preview'));

   yats_assign($block,
               array('hidden_name' => 'new_message_to',
                     'hidden_value' => $data['from_username']));


   // Display any error messages
   if ($error) {
      yats_assign($block, array('error_message'            => $error));
   }

   // Passphrase is cache'd do not show the passphrase box
   if (!empty($app->session->passphrase_cache)) {
      $hide_passphrase_box = true;
   }

   // Hide the passphrase box?
   if ($hide_passphrase_box) {
      yats_hide($block, 'passphrase_box', true);
   }

   // Generate Reply Form
   //$compose_form_block = yats_define($templateBase."messaging/read.html");

   //yats_assign($block, array("reply_compose_form_block" => yats_getbuf($compose_form_block)));

   echo yats_getbuf($block);
   break;


case 'directory':

   $block = yats_define($templateBase.'messaging/directory.html');
   // OLD yats_assign($block, array('user_listing_block' => $app->html->render_user_directory('compose')));

   $result = toolbox::get_users('ALL');
   if (!$result[err]) {
      $users = $result[msg];
   }

   yats_assign($block, array("user_listing_block" => $app->html->render_user_directory_rows($users, "compose")));

   echo yats_getbuf($block);
   break;


case "empty_deleted_box_confirm":

   $block = yats_define($templateBase."messaging/empty_deleted_box_confirm.html");
   yats_assign($block, array("sid" => session_id())); 
   echo yats_getbuf($block);
   break;


case "addresses":


   $block = yats_define($templateBase."messaging/addresses.html");  

   if ($app->user->im->addresses) {
      foreach($app->user->im->addresses as $id => $data) {

         $address_data[address_entry_uid][] = $data;

         // Get usernames by userid
         $result = $app->user->im->get_usernames(explode("|",$data));

         if (!$result[err]) {
            $address_data[address_entry_label][] = implode(", ", $result[msg]);
            $address_data[address_entry_id][] = $id;
         }
      }

      yats_assign($block, $address_data);
   }
   else {

      $status_message = "No addresses found.";
   }



   if ($error_message)
      yats_assign($block, array("error_message" => $error_message, "new_address"=>$address_string));

   if ($status_message)
      yats_assign($block, array("status_message" => $status_message));

   yats_assign($block, array("sid" => session_id())); 
   echo yats_getbuf($block);
   break;

}

?>