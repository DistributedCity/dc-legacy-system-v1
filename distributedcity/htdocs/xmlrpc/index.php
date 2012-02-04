<?php
$request_xml = $HTTP_RAW_POST_DATA;
/*
$request_xml = <<< END
<?xml version="1.0"?>
<methodCall>
<methodName>greeting</methodName>
<params>
<param>
<value><string>Dan</string></value>
</param>
</params>
</methodCall>
END;
*/

// ensure extension is loaded.
xu_load_extension();



function greeting_func($method_name, $params, $app_data) {
  $name = $params[0];
  return array("hello $name. How are you today?");
}


function fdump($data){
  $fd = fopen("/tmp/ttt.log","w");
  foreach($data as $key => $value){
    fputs($fd, $key ." => " .$value. "\n");
  }
  fclose($fd);
}


function send_message_func($method_name, $params, $app_data) {

  fdump($params);
  
  $username = $params[0];
  $password = $params[1];



  $admin = new admin;
  
  // Check authentication
  $result = $admin->check_password(array("username" => $username, "password" => $password));
  if($result[err]){
    return array("0", "Access Denied");
  }else{

    // Authentication OK: Start a new app
    $app = new app($result[msg]);
    session_register('app');
    
    // TODO Validate incoming and required data
    //  $new_message_to               = $HTTP_POST_VARS[new_message_to];
    //  $new_message[encrypt_subject] = $HTTP_POST_VARS[encrypt_subject];
    //  $new_message[subject]         = $HTTP_POST_VARS[new_message_subject];
    //  $new_message[body]            = $HTTP_POST_VARS[new_message_body];

    $new_message_to        = $params[2];
    $new_message[subject]  = $params[3];
    $new_message[body]     = $params[4];
    
    // Lookup user_id's from incoming username recipient list
    $recipient_usernames = explode(",", $new_message_to);
    
    $result = $app->user->im->get_user_ids($recipient_usernames);
    
    if($result[err]){
      // ERROR: Problem finding all the user_ids
      $compose_error = $result[msg];
      $mode = "compose";
      
      return(array("0", $compose_error));
      
    } else {
      // OK: Get the recipient ids found
      $recipient_user_ids = $result[msg];
      

      $result = $app->user->im->send_new_message($new_message, $recipient_user_ids);
      fdump($result);
      if($result[err]){
	$compose_error = $result[msg];
	return(array("1", $compose_error));
      }else{
	$compose_message = "Message successfully sent.";
	$mode = "folder";
	
	return(array("1", $compose_message));
      }
      
    }

    //  return array("1", "OK: Message 'Subject: ".$subject."|Body: ".$body."' sent to user: ".$user_id);
  }

  return "sdfsdf";


}


// create server
$xmlrpc_server = xmlrpc_server_create();

if($xmlrpc_server) {
    // register methods

    if(!xmlrpc_server_register_method($xmlrpc_server, "greeting", "greeting_func")) {
      die("<h2>method registration failed. 2</h2>");
    }

    if(!xmlrpc_server_register_method($xmlrpc_server, "send_message", "send_message_func")) {
      die("<h2>method registration failed. 1</h2>");
    }

    $foo = xmlrpc_server_call_method($xmlrpc_server, $request_xml, $response, array(output_type => "xml"));
    //echo "<h1>XML Response</h1><xmp>$foo</xmp>\n";
    echo $foo;
    // free server resources
    $success = xmlrpc_server_destroy($xmlrpc_server);
    
}
?>