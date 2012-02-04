<?php

// HTTP Web Chat

class chat {

  var $active_room_name;

  function get_public_rooms(){
    {
      $sql = "SELECT DISTINCT id, name, topic, state FROM dc_chat_room";

      $result = $GLOBALS[db]->query($sql);

      // TODO add error checking
      
      while ($entry = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
	$data[] = $entry;
      }
      return $data;
    }

  }

  function create_room($name, $hidden="no"){
  }

  function send_private_message($username, $message){
  }



  /*
    Get a messages for a room
    room_id is required
  */
  function get_messages($room_id){
    //user_id date message
    $entry = $this->get_active_room_entry();
    $sql = "select distinct dc_chat_room_messages.user_id as avatar, dc_user.username as username, dc_chat_room_messages.date as date, dc_chat_room_messages.message as text from dc_user, dc_chat_room_messages WHERE dc_chat_room_messages.date > '$entry' AND dc_chat_room_messages.chat_room_id='$room_id' AND  dc_chat_room_messages.user_id=dc_user.id ORDER BY date DESC LIMIT 25";
    $dbresult = $GLOBALS[db]->query($sql);

    if ( toolbox::db_error(&$dbresult) || $dbresult->numRows() == 0) {
      
      $result[err] = 1;
      $result[msg] = 'No messages.';
      return($result);
    }else{

      while($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)){
	$data[] = $entry;
      }
      return array_reverse($data);
    }

  }




  function send_new_message($message) {

    $message = toolbox::slash($message);
    $date = time();

    $dbresult = $GLOBALS[db]->query ( "insert into dc_chat_room_messages (chat_room_id, user_id, date, message) 
          values ( '$message[chat_room_id]','$message[user_id]', '$date', '$message[message]')" );
    
    // TODO error checking
    
    if ( toolbox::db_error(&$dbresult) ){
      
      $result[err] = 1;
      $result[msg] = 'Unknown error, could not add send your message.';
      
    }else{
      
      $result[err] = 0;
      $result[msg] = 'Your message was successfully added.';
      
    }
    return $result;
  }



  function get_active_room_name(){
    return $this->active_room_info[name];
  }

  function get_active_room_id(){
    return $this->active_room_info[id];
  }

  function get_active_room_topic(){
    return $this->active_room_info[topic];
  }

  function get_active_room_entry(){
    return $this->active_room_info[entry];
  }




  function set_active_room($user_id, $room_id){

    // Get new room information
    $sql = "SELECT id, name, topic, state FROM dc_chat_room WHERE id='$room_id'";

    $result = $GLOBALS[db]->query($sql);

    // TODO Add Error Checking
    $entry = $result->fetchRow(DB_FETCHMODE_ASSOC);
    $this->active_room_info = $entry;

    // Set the time of entry
    $this->active_room_info[entry] = time();

    // Mark the user as being in a room currently
    $this->user_touch_room($user_id, $room_id, $this->active_room_info[entry]);
  }


  function set_active_room_entry(){
    $this->active_room_info[entry] = time();
  }

  function user_touch_room($user_id, $room_id, $time=""){

    if(!$time){
      $time = time();
    }

    // Mark this user as being active in a specific room.
    $result = $GLOBALS[db]->getOne( "insert into dc_chat_users (chat_room_id, user_id, date) values ('$room_id', '$user_id', '$time')");

    // Check if error, if so, attempt an update
    if ( toolbox::db_error(&$result) ){
      $result = $GLOBALS[db]->getOne( "update dc_chat_users SET date='$time', chat_room_id='$room_id'  WHERE user_id='$user_id'");
    }


  }




  function get_room_users($room_id){

    $room_timeout = time() - 60; // 1 minute timeout
    $sql = "select distinct dc_chat_users.user_id as id, dc_user.username as username FROM dc_chat_users WHERE dc_user.id=dc_chat_users.user_id AND dc_chat_users.chat_room_id='$room_id' AND dc_chat_users.date>'$room_timeout'";

    $result = $GLOBALS[db]->query($sql);

    //TODO Check for errors

    while($entry = $result->fetchRow(DB_FETCHMODE_ASSOC)){

      $users[] = $entry;

    }
      return ($users);
  }








}

?>