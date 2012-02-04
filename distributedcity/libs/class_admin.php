<?php

class admin {

  var $db;
  var $db_host;
  var $db_user;
  var $db_pass;
  var $db_name;


  function admin()
  {
  }


  function check_password($userinfo)
  {
    // Make vars safe for sql
    $userinfo = toolbox::slash($userinfo);

    // Lowercase the username and then MD5 hash it
    $username_hash = md5(strtolower($userinfo[username]));

    // Hash the password
    $password_hash = md5($userinfo[password]);

    if($user_id = $GLOBALS[db]->getOne("SELECT id FROM dc_user where username_hash='$username_hash' and password='$password_hash'")) {
      // TODO error check
      $result[msg] = $user_id;
    } else {
      $result[err] = 1;
      $result[msg] = "Access Denied.";
    }
    return $result;
  }


  function create_user($userinfo, $access_level="1")
  {
    // TODO Validate Username
    $username = trim($userinfo[username]); // Kill any leading trailing whitespace
    $password = $userinfo[password]; // Allow whitespace

    // Validate Username
    // Length max = 20
    $username_max_length = 20;
    if(strlen($username) > $username_max_length){
      $error[] = "Username must be less than $username_max_length characters.";
    }

    // Length min = 5
    $username_min_length = 5;
    if(strlen($username) < $username_min_length){
      $error[] = "Username must be more than $username_min_length characters.";
    }

    // Valid characters
    if(!empty($username)){
      if(preg_match("/[^a-z0-9_]/i", $username)){  
         $error[] = "Username must contain 0-9 A-Z or _ characters only";
      }
    }

    
    // Validate Password
    // Length min = 8
    $password_min_length = 8;
    if(strlen($password) < $password_min_length){
      $error[] = "Password must be more than $password_min_length characters. Please enter twice for confirmation.";
    }


    if(!$error){

      // Create username_hash by lowecasing the username and then md5 hashing it
      // Why? Because this allows us to store the username for display like this: Bugs_BunnY
      // ... yet still allow for any case of login to happen: bugs_bunny, BuGs_BuNnY are valid usernames for login.
      // ... then after the user is logged in, the display name will always be the username.
      // ... - This also allows the user to change their display format in the future (we must compare lowercase md5 of the new
      // ... display username to make sure that only the case has changed in some of the letters, and not actual characters
      // ... because a user can never change their username. They can only change the display upper/lowercase of the username for display.
      $username_hash = md5(strtolower($userinfo[username]));
      $password_hash = md5($password);
            
      $sql ="INSERT INTO dc_user (username, username_hash, password, access_level) VALUES ('$username', '$username_hash', '$password_hash', '$access_level')";
      $insert_result = $GLOBALS[db]->query($sql);
      
      // Check for errors
      if(toolbox::db_error(&$insert_result)){
print_r( $insert_result );	
	$result[err] = 1;
	$result[msg] = 'Sorry, but the username: <i>'. $username .'</i> is not available.';
	
      }else{
	
	// No error on account create - get the new user_id
	$user_id = $GLOBALS[db]->getOne("SELECT id FROM dc_user WHERE username='$username'");
	
	
	// Set Public Information "user_since" property
	// Do not put in the datestamp, we do now want to record the exact time in the database.
	// So render the time right now as MONTH/YEAR, pretty generic, and save in the public_info
	// database table field of 'user_since'
	$sql = "INSERT into dc_user_public_info (user_id, user_since) values ('$user_id', '". toolbox::make_rough_date(time()) ."')";
	
	$dbresult = $GLOBALS[db]->getOne($sql);

	$result[err] = 0;
	$result[msg] = $user_id;
      
      }
    }else{

      $result[err] = 1;
      $result[msg] = $error;
    }

    return($result);
  }







}




?>
