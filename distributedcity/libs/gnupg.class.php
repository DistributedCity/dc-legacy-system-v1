<?php
// FIXME: This needs a lot of cleanup since there is a lot of stuff in here now
// that is completely irrelevant. In particular the config class and many settings defined
/*************************************************************************************************************
** Title.........: GPG Class
** Version.......: xxx (old 0.02.1)
** Author........: Derived and heavily modified from original work done by: Rodrigo Z. Armond <rodzadra@passagemdemariana.com.br>
** Filename......: gnugpg.class.php
**
**************************************************************************************************************/

define(GPG_BIN, "/usr/bin/gpg");					// This is the GNUpg Binary file
define(GPG_PARAMS, " --no-tty --no-secmem-warning --home ");		// The default parameters to use the GNUpg
define(GPG_PARAMS_PIPE, "  --no-secmem-warning --home ");		// The default parameters to use the GNUpg PIPE

define(GPG_USER_DIR, "/websites/www.distributedcity.com/gpg/keyrings/");// This is where the users dir will be created

define(GPG_PASS_LENGTH, 8);						// This is the minimum lenght accepted of the passphrase
define(FAIL_NO_RECIPIENT, 1);						// If one recipient, from the recipient list, do not exist...
									// ...this will return an erro else, if one dont exist and all...
									// ...the other exist this continues (see. function mount_recipients).
define(GEN_HTTP_LOG, 1);						// This generate or not logs in the HTTP log

class gnupg{
	var $username;			// the user name (owner of the keyrings)
	var $userEmail;			// the user email (owner email)
	var $subject;			// the subject of message
	var $message;			// the clean txt message to encript
	var $passphrase;		// the passphrase to decrypt the message
	var $encrypted_message;		// the returned message encrypted
	var $decrypted_message;		// the returned message decrypted
	var $gpg_path;			// the gpg base path to the private sub-dir of the user
	var $recipientName;		// the name of the recipient
	var $recipient_usernames;		// the recipient email
	var $keyArray;			// this will be filled with the keys on the keyrings
	var $public_key;		// this is the variable used to export the owner public key (export_key)
	var $encrypt_myself; 		// boolean to indicate if the message will be encrypted with the user owner key
	var $valid_keys;		// array with the list of recipients that are on the keyring
	var $not_valid_keys;		// array with a list of recipient that are not on the keyring

	// Misc Settings
	// Set these in the contructor
	var $utf_lib_dir;
	var $gpg_work_dir;
	var $gpg_keyring_dir;
	var $user_homedir;
 	var $user_pub_keyring_file;
 	var $user_sec_keyring_file;

function gnupg($username, $user_id=""){			// initialization of class variables

	// Misc Settings
	$this->utf_lib_dir     = $GLOBALS[config][lib_base];
	$this->gpg_work_dir    = $GLOBALS[config][gpg_work_dir];
	$this->gpg_keyring_dir = $GLOBALS[config][gpg_keyring_dir];

	$this->user_id = $user_id;
	$this->username = $username;
	$this->userEmail = strtolower($username . "@distributedcity.com");

	$this->user_homedir = $this->gpg_keyring_dir . strtolower($username) . "/";
 	$this->user_pub_keyring_file = $this->user_homedir . "pubring.gpg";
 	$this->user_sec_keyring_file = $this->user_homedir . "secring.gpg";





	$this->gpg_path = GPG_USER_DIR;
	$this->subject = $subject;
	$this->message = $message;
	$this->recipientEmail = $recipient_usernames;
	$this->recipientName = $recipientName;
	$this->passphrase = $passphrase;
	$this->encrypt_myself = $encrypt_myself;
	
	
  	//verifies that the GNUpg binary exists
	if(!file_exists(GPG_BIN)){
  		$this->error = "GNUpg binary file ".GPG_BIN." does not exist.\n";
		return(false);
	}
	
	//check that the GNUpg binary is executable
	if(!is_executable(GPG_BIN)){
  		$this->error = "GNUpg binary file ".GPG_BIN." is not executable.\n";
		return(false);
 	}
 
 }

 /*
  function check_private_dir()

  This function check if the private gnupg dir exist for the user $username
 */
 function check_private_dir(){
 	// clear the filesystem cache
	clearstatcache();
	
	// check if the user dir exists
	if(!is_dir($this->user_homedir)){
	  //die("Error: The user dir doesn't exist. (in function check_private_dir - 1)");
	  return(false);
	}
	
	return(true);

 } // end function check_private_dir


 /*
  function check_pubring()

  This function check if the pubring.gpg exists
*/
 function check_pubring(){
 	// clear the filesystem cache
	clearstatcache();

	// check if the user dir exists
	if(!file_exists($this->user_pub_keyring_file)){
	  //die("Error: The user pubring does not exists. Maybe the key was not be generated. (in function check_pubring - 1)");
	  return(false);
	}
	
	return(true);

 } // end function check_pubring
 



 
 /* 
  function check_all()

  This function check the private dir and the pubring
 */
 function check_all(){

   if(!$this->check_key_in_generation_queue() && !$this->check_pubring()){
     return(false);
   }else{
     return(true);
   }

 } // end function check_all

 /*
  function mount_recipients()

  This function return an array of valid recipients to receive the message encrypted
  (to be a valid recipient, the recipient must be on the keyring), and an array with 
  invalid recipient (that isn't on the key ring). 
  
  NOTE!!! IF FAIL_NO_RECIPIENT IS 0 AND ONE (OR MORE) RECIPIENTS ARE NOT IN THE KEYRING,
  THIS FUNCTION WILL RETURN FALSE (THIS IS THE DEFAULT). OTHERWISE, IF FAIL_NO_RECIPIENT 
  
  IS SET TO 1, THE FUNCTION WILL NOT RETURN AN ERROR MESSAGE AND WILL CONTINUES NORMALY.
  YOU CAN SET FAIL_NO_RECIPIENT TO 1 AND MAKE THE USE OF THE $this->not_valid_keys TO 
  FIND WHAT IS THE RECIPIENT THAT ARE NOT IN THE KEYRING.
 */
  function mount_recipients($recipients){

  	if(!$this->check_all()){
		return(false);
	}
  	
	// clear vars
	unset($this->valid_recipients, $this->unvalid_recipients);
	unset($keys, $valid_keys, $not_valid_keys);

	// Depreciated to class property $priv_path = $this->gpg_path.ereg_replace("[@]","_",$this->userEmail)."/.gnupg";

	// call the gpg to list the keys
	$tmp = explode(";",$recipients);	// create a temp array with all the recipients

	for($i=0; $i < count($tmp); $i++){
		// mount the command to list the keys
		$command = GPG_BIN.GPG_PARAMS.$this->user_homedir." --with-colons --list-key ".trim($tmp[$i]);
		if(GEN_HTTP_LOG){
			$command .= " 2>/dev/null";
		}

		// execute the list-key command for all recipients separeted
		exec($command, $keyArray, $errorcode); 
		
		if($errorcode){
			if(FAIL_NO_RECIPIENT) {
				$this->error = "Error: One or more recipients are not in the keyring. (in function mount_recipients - 1)";
				return(false);
			}
			$not_valid_keys .= trim($tmp[$i]).";";
		} else {
			
			for($j=0; $j < count($keyArray); $j+=2){
				$keys = array(explode(":",$keyArray[$j]));
				$valid_keys .= $keys[0][9].";";
			}
			unset($keyArray);
		}
	}
	
	$this->valid_keys = explode(";",$valid_keys);
	$this->not_valid_keys = explode(";",$not_valid_keys);
		
 	return(true); 
  } // end function mount_recipients


 /*
  function check_keyID()
  
  $keyID = the key(s) to check if exist.
  this can be a simple key or various keys separeded with ','
  
  check if exist the user dir exist and if the keyID is on the keyring.
  Returns false when failed, or true.
 */
 function check_keyID($keyID){

  	if(!$this->check_all()){
	  die("problem check_all");
		return(false);
	}
  	
	// Depreciated to class property $priv_path = $this->gpg_path.ereg_replace("[@]","_",$this->userEmail)."/.gnupg";

	// call the gpg to list the keys
	//$command = GPG_BIN.GPG_PARAMS.$priv_path;
	$command = GPG_BIN.GPG_PARAMS.$this->user_homedir;
	
	
	$tmp = explode(",",$keyID);
	   
	
	for($i=0; $i < count($tmp); $i++){					// list-key for every recipient
		$command .= " --list-key ".trim($tmp[$i]);	
	}

	if(GEN_HTTP_LOG){
		$command .= " 2>/dev/null";
	}


	exec($command, $keyArray, $errocode);



	if($errorcode){
	  $result[err] = 1;
	  $result[msg] = "Error: The keyID \"$keyID\" isn't on the keyring. (in function check_keyID - 3)";
	}

	if(count($keyArray) > 0) {
	  return true;
	  //$result[msg] = "OK: The KeyID \"$keyID\" was found on the keyring.";
	} else {
	  //	  $result[err] = 1;
	  //$result[msg] = "Error: The keyID \"$keyID\" isn't on the keyring. (in function check_keyID - 4)";
	  return false;
	}
	//	return($result);
 
 } // end function check_keyID
 

/* 
  function list_keys()

  List all the publics keys on the keyrings
  Return an array ($this->keyArray) with the keys.
  Returns false when failed. If failed, look at $this->error for the reason.
 */
 function list_keys($keyid=""){
	

   // If no keyid, then list ALL keys
   if($keyid)
     $keyid = escapeshellcmd(substr($keyid,0, 256));

	if(!$this->check_all()){
	  $result[err] = 1;
	  $result[msg] = "The GPG Public Keyring cannot be accessed. If you are a new user, your key is possibly still being generated. If you still cannot access your key 24-48 hours after you have registered please contact support.";
	  return $result;
	  //die("check_all problem in list_keyring");
	}
	
 	if (!$this->check_keyID($this->username)){

	  $result[err] = 1;
	  $result[msg] = "The GPG Public Keyring cannot be accessed. If you are a new user, your key is possibly still being generated. If you still cannot access your key 24-48 hours after you have registered please contact support.";
	  //	  die("check_keyID problem in list_keyring");

	  return $result;

	}
	
	// Depreciated to class property $priv_path = $this->gpg_path.ereg_replace("[@]","_",$this->userEmail)."/.gnupg";

	$command = GPG_BIN.GPG_PARAMS.$this->user_homedir." --list-key --fingerprint --with-colons ". $keyid;
	
	if(GEN_HTTP_LOG){
		$command .= " 2>/dev/null";
	}


	exec($command, $keyArray, $errorcode);
	

	if($errorcode){
		print "Error: Can't list the keys. (in function list_keys - 1)";
		die();
	}

	unset($this->keyArray);

/* DATA FORMAT
   Format of "---with-colons" listings
   ===================================
   
   sec::1024:17:6C7EE1B8621CC013:1998-07-07:0:::Werner Koch <werner.koch@guug.de>:
   ssb::1536:20:5CE086B5B5A18FF4:1998-07-07:0:::
   
 1. Field:  Type of record
   pub = public key
   sub = subkey (secondary key)
   sec = secret key
   ssb = secret subkey (secondary key)
   uid = user id (only field 10 is used).
   fpr = fingerprint: (fingerprint is in field 10)
   pkd = public key data (special field format, see below)

 2. Field:  A letter describing the calculated trust. This is a single
	    letter, but be prepared that additional information may follow
	    in some future versions. (not used for secret keys)
		o = Unknown (this key is new to the system)
		d = The key has been disabled
		r = The key has been revoked
		e = The key has expired
		q = Undefined (no value assigned)
		n = Don't trust this key at all
		m = There is marginal trust in this key
		f = The key is full trusted.
		u = The key is ultimately trusted; this is only used for
		    keys for which the secret key is also available.
 3. Field:  length of key in bits.
 4. Field:  Algorithm:	1 = RSA
		       16 = ElGamal (encrypt only)
		       17 = DSA (sometimes called DH, sign only)
		       20 = ElGamal (sign and encrypt)
	    (for other id's see include/cipher.h)
 5. Field:  KeyID
 6. Field:  Creation Date (in UTC)
 7. Field:  Key expiration date or empty if none.
 8. Field:  Local ID: record number of the dir record in the trustdb.
	    This value is only valid as long as the trustdb is not
	    deleted. You can use "#<local-id> as the user id when
	    specifying a key. This is needed because keyids may not be
	    unique - a program may use this number to access keys later.
 9. Field:  Ownertrust (primary public keys only)
	    This is a single letter, but be prepared that additional
	    information may follow in some future versions.
10. Field:  User-ID.  The value is quoted like a C string to avoid
	    control characters (the colon is quoted "\x3a").

More fields may be added later.

If field 1 has the tag "pkd", a listing looks like this:
pkd:0:1024:B665B1435F4C2 .... FF26ABB:
    !  !   !-- the value
    !  !------ for information number of bits in the value
    !--------- index (eg. DSA goes from 0 to 3: p,q,g,y)
*/


	// Parse the keyring data
	// Only Lose the useless header info and '---' lines
	// When we are not listing all keys

	if(!$keyid)
	  unset($keyArray[0], $keyArray[1]); 


	// Extract into separate key sections
	foreach($keyArray as $line){
	  if(substr($line,0,3)== "pub"){
	    $x = $x + 1;

	    $pub_key_data = explode(":", $line);	    

	    $data[$x][pubkey]['user-id'] = $pub_key_data[9]; 
	    $data[$x][pubkey]['keyid']   = $pub_key_data[4]; 
	    $data[$x][pubkey]['length']  = $pub_key_data[2]; 

	    $data[$x][pubkey]['creation']   = $pub_key_data[5]; 
	    $data[$x][pubkey]['expiration'] = $pub_key_data[6]; 



	    switch($pub_key_data[3]){
	    case "1":
	      $data[$x][pubkey]['algorithm']  = "RSA";
	      break;

	    case "16":
	      $data[$x][pubkey]['algorithm']  = "ElGamal (encrypt only)";
	      break;

	    case "17":
	      $data[$x][pubkey]['algorithm']  = "DSA (sometimes called DH, sign only)";
	      break;

	    case "20":
	      $data[$x][pubkey]['algorithm']  = "ElGamal (sign and encrypt)";
	      break;
	    }



	  }elseif(substr($line,0,3) == "fpr"){
	    $data[$x][pubkey][fingerprint] = current(array_slice(explode(":", $line),9,1));

	  }elseif(substr($line,0,3) == "sub"){
	    

	    $sub_key_array  = explode(":", $line);


	    $sub_key_data['length']  = $sub_key_array[2]; 
	    $sub_key_data['keyid']   = $sub_key_array[4]; 
	    $sub_key_data['creation']   = $sub_key_array[5]; 
	    $sub_key_data['expiration']   = $sub_key_array[6]; 




	    switch($sub_key_array[3]){
	    case "1":
	      $sub_key_data['algorithm']  = "RSA";
	      break;

	    case "16":
	      $sub_key_data['algorithm']  = "ElGamal (encrypt only)";
	      break;

	    case "17":
	      $sub_key_data['algorithm']  = "DSA (sometimes called DH, sign only)";
	      break;

	    case "20":
	      $sub_key_data['algorithm']  = "ElGamal (sign and encrypt)";
	      break;
	    }


	    $data[$x][sub_keys][] = $sub_key_data;

	  }elseif(substr($line,0,3) == "ssb"){
	    $data[$x][ssb_line][] = explode(":", $line);
	  
	  }elseif(substr($line,0,3) == "sec"){
	    $data[$x][sec_line][] = explode(":", $line);

	  }elseif(substr($line,0,3) == "uid"){
	    $data[$x][additional_user_ids][]['user-id'] = current(array_slice(explode(":", $line),9,1));
	  }


	}

	$result[msg] = $data;

	return $result;

	 
 } // end function list_keys

 

 /*
  function encrypt_message()
  
  Encrypt a clean txt message.
  Returns false when failed, or the encrypted message in the $this->encrypted_message, when (if) succeed.
  If failed, look at the $this->error for the reason.       
 */
 function encrypt_message($recipients, $message){

   unset($found_recipients);
   unset($missing_recipients);


   // IF $recipients is not an array, make it one
   if(!is_array($recipients)){
     $recipients = array($recipients);
   }

   // FIXME : do not just die, handle this
   //if(!$this->check_all()){
   //  die("encrypt_message check_all problem");//return(false);
   //}
   
   // first check if the key is on the keyring
   foreach($recipients as $recipient){
     if($this->check_keyID($recipient)){
       $found_recipients[] = $recipient;
     } else {
       $missing_recipients[] = trim($recipient);
     } 
   }
    




   // If there were some missing keys report that
   if(!empty($missing_recipients)) {


     foreach($missing_recipients as $missing_recipient){

       // Get the public key from the users keyring
       $user_gpg = new gnupg($missing_recipient);
       $result = $user_gpg->export_key();


       if(!$result[err]){
	 $users_public_key = $result[msg][keyblock];

	 // Now add this key to our keyring
	 if($this->import_key($users_public_key)) {
	   $crypto_result[msg] .= "Automatically imported the GPG Public Key for user: ".$missing_recipient."<BR>";
	 }else{
	   // Problem importing the key
	   $errors_import_keys[] = $missing_recipient;
	 }

       }else{

	 // Problem even getting the key
	 $errors_get_keys[] = $missing_recipient;

       }



     }// end foreach(missing_recipients as missing_recipient)
   }


   // If there were missing keys, valid recipients with no gpg key.
   // i.e. Maybe the recipient is a new user, and their key is still
   // in the keygen queue. Abort the process and notify the user
   // That the encrypt and send could not be completed.
   if($errors_import_keys || $errors_get_keys){

     $crypto_result[err] = 1;
   
     if($errors_import_keys){
     
       // See if the the problem is:
       // No valid keyring exists, because the key has not been generated yet
       if(!$this->check_pub_key() && $this->check_key_in_generation_queue()){
	 
	 $crypto_result[msg] .= "<br><b>Keyring Error:</b> There was a problem accessing your Keyring. A check of the Crypto_Engine shows your Key is still in the Queue and has not been generated yet.<BR>You must wait until you have a valid keyring before you can use the encryption/decryption functions of the system.<BR>You may check the status of your key in the Queue by going to: Top Tab Menu->Settings->OpenPGP";
	 
       }else{
	 // OR There is probably a problem with this key
	 $crypto_result[msg] .= "<br><b>Import Error:</b> There was a problem importing this Key into your Keyring. The Key was found, but there possibly is a problem with the Key for users: ". implode(",",$errors_import_keys);
	 
       }
     }  
     
     if($errors_get_keys)
       $crypto_result[msg] .= "<BR><B>Retrieve Error:</b> There was a problem obtaining this Key, the system could not find the GPG Key for users: ". implode(",",$errors_get_keys)."<br>". "They may be a newer user, still waiting for the Crypto_Engine to generate their key.";

     
     }else{

     // generate token for unique filenames
     $tmpToken = md5(uniqid(rand()));
     
     // create vars to hold paths and filenames
     $plainTxt   = $this->user_homedir . $tmpToken.".data";
     $cryptedTxt = $this->user_homedir . $tmpToken.".pgp";
     $statusTxt = $this->user_homedir . $tmpToken.".status";

     
     // open .data file and dump the plaintext contents into this
     $fd = @fopen($plainTxt, "w+");
     if(!$fd){
       $crypto_result[err] = 1;     
       $crypto_result[msg] .= "Problems accessing your user directory.";
     }else{
       @fputs($fd, $message);
       @fclose($fd);
     
       $this->encrypt_myself = true;
     
       // invoque the GNUgpg to encrypt the plaintext file
       $command = GPG_BIN.GPG_PARAMS.$this->user_homedir." --always-trust --armor";
       
       foreach($recipients as $recipient){
	 $command .= " --recipient '" . $recipient ."' ";
       }
       
              // Include the message to yourself
       if($this->encrypt_myself) {
	 $command .= " --recipient '$this->userEmail'";
       }
       
       $command .= " --output '$cryptedTxt' -e $plainTxt";
       
       if(GEN_HTTP_LOG){
	 $command .= " 2>".$statusTxt;
       }
     
       // execute the command
       system($command, $errorcode);
           
       if($errorcode){

	 $crypto_result[err] = 1;     
	 $crypto_result[msg] = "System Cannot encrypt the message.";
	 
	 // FIXME: Send this info to admin - big problem here
	 $fd = @fopen($statusTxt, "r");
	 $tmp = @fread($fd, filesize($statusTxt));
	 @fclose($fd);
	 @unlink($statusTxt);
	

       } else {

	 // open the crypted file and read contents into var
	 $fd = @fopen($cryptedTxt, "r");
	 if(!$fd){

	 $crypto_result[err] = 1;     
	 $crypto_result[msg] = "System cannot retrieve the ciphertext of the message.";

	 }else{

	   $tmp = @fread($fd, filesize($cryptedTxt));
	   @fclose($fd);
	   
	   // delete all the files
	   @unlink($plainTxt);
	   @unlink($cryptedTxt);
	   
	   // verifies the ciphertext is contains a pgp message
	   if(ereg("-----BEGIN PGP MESSAGE-----.*-----END PGP MESSAGE-----",$tmp)) {
	     
	     $crypto_result[ciphertext] = $tmp;
	     unset($tmp);
	     
	   } else {
	     $crypto_result[err] = 1;
	     $crypto_result[msg] = "The ciphertext does not appear to be a PGP message. Nothing done.";
	     unset($tmp);
	   }
	 }
       }
     }
   }

   return $crypto_result;


 } // end function encrypt_message()


 /*
  function decrypt_message()
  
  Decrypt the armored crypted message.
  Returns false when failed, or decrypted message in the $this->decrtypted_message, when (if) succeed.
  If failed, look at the $this->error for the reason.
 */
 function decrypt_message($message, $passphrase){

  	if(!$this->check_all()){
		return(false);
	}
	
 	// first check if the key is on the keyring
	if (!$this->check_keyID($this->recipientEmail)){
		return(false);
	} 
	
	// Depreciated to class property $priv_path = $this->gpg_path.ereg_replace("[@]","_",$this->userEmail)."/.gnupg";

	// check the header/footer of message to see if this is a valid PGP message
 	if(!ereg("-----BEGIN PGP MESSAGE-----.*-----END PGP MESSAGE-----",$message)) {
	  unset($passphrase);
	  $result[err] = 1;
	  $result[msg] = "Error: The header/footer of message not appear to be a valid PGP message. (in function decrypt_message - 1)";
	  return $result;
	} else {
	  
	  // generate token for unique filenames
	  $tmpToken = md5(uniqid(rand()));
	  
	  // create vars to hold paths and filenames
	  $plainTxt   = $this->user_homedir . $tmpToken . ".data";
	  $cryptedTxt = $this->user_homedir . $tmpToken . ".gpg";
	  
	  // create/open .pgp file and dump the crypted contents
	  $fd = @fopen($cryptedTxt, "w+");
	  if(!$fd){
	    $result[err] = 1;
	    $result[msg] = "Error: Can't create the .gpg file. Verify that you have write acces on the directory. (in function decrypt_message - 2)";
	    unset($passphrase);

	    return($result);
	  }
	  @fputs($fd, $message);
	  @fclose($fd);
	  
	  // create the command to execute
	  $command = "echo '$passphrase' | ".GPG_BIN.GPG_PARAMS.$this->user_homedir." --batch --passphrase-fd 0 -r '$this->username' -o $plainTxt --decrypt $cryptedTxt";
	  
		if(GEN_HTTP_LOG){
			$command .= " 2>/dev/null";
		}
		
		// execute the command to decrypt the file
		system($command, $errcode);

		unset($passphrase);
		
		// open the decrypted file and read contents into var
		$fd = @fopen($plainTxt, "r");
		if(!$fd){

		  $result[err] = 1;
		  $result[msg] = "Invalid passphrase.";
		  //"Error: Can't read the .asc file. Verify if you have entered the correct user/password. (in function decrypt_message - 3)";

		  @unlink($cryptedTxt);
		  return($result);
		}

		//$this->decrypted_message = @fread($fd, filesize($plainTxt));
		$result[msg] = @fread($fd, filesize($plainTxt));
		@fclose($fd);
		
		// delete all the files
		@unlink($plainTxt);
		@unlink($cryptedTxt);
	
	} 

	return $result;

 } // end function decrypt_message


 /*
  function import_key($key)
  
  Import public key to keyring. NOTE IT MUST BE IN ARMORED FORMAT (ASC).
  Returns false when failed.  If failed, look at the $this->error for the reason.
 */
 function import_key($key = ""){

  	if(!$this->check_all()){
		return(false);
	}
	
 	// first check if the key is on the keyring
	if (!$this->check_keyID($this->recipientEmail)){
		return(false);
	} 
	
	// Depreciated to class property $priv_path = $this->gpg_path.ereg_replace("[@]","_",$this->userEmail)."/.gnupg";

 	// check if the key to import isn't empty
	if($key == ""){
  		$this->error = "Error: No public key file specified. (in function import_key - 1)";
		return(false);
	}
	
	// Checks the header/footer to see if is a valid PGP PUBLIC KEY
	if(!ereg("-----BEGIN PGP PUBLIC KEY BLOCK-----.*-----END PGP PUBLIC KEY BLOCK-----",$key)) {
		$this->error = "Error: This not appear to be a valid PGP message. Error in header and/or footer. (in function import_key - 2)";

		return(false);
	} else {

	 	// generate token for unique filenames
		$tmpToken = md5(uniqid(rand()));
		
		// create vars to hold paths and filenames
		$tmpFile = $this->user_homedir."/".$tmpToken.".public.asc";
		  //$this->gpg_path.ereg_replace("[@]","_",$this->userEmail)."/".$tmpToken.".public.asc";
	
		// open file and dump in plaintext contents
		$fd = fopen($tmpFile, "w+");
		if (!$fd){
		  $this->error = "Error: Can't create .tmp file to add the key. Verify that you have write access in the dir. (in function import_key - 3): $tmpFile";

		  return(false);
		}
		@fputs($fd, $key);
		@fclose($fd);

		$command = GPG_BIN.GPG_PARAMS.$this->user_homedir." --import '$tmpFile'";

		if(GEN_HTTP_LOG){
		  //			$command .= " 2>/dev/null";
			$command .= " 2>/tmp/gpg.err";
		}

		system($command,$errorcode);



		if($errorcode){
			$this->error = "Error: Can't add the public key. (in function import_key - 4)";
			@unlink($tmpFile);
			return(false);
		} else {
			@unlink($tmpFile);
			return(true);
		}
	}
	
 } // end function import_key


/*
  function export_key(): 
  
  Export the owner public key in asc armored format.
  Returns false when failed.  If failed, look at the $this->error for the reason.
 */
 function export_key($keyid=""){			// TODO: option to make an file to attachment

   // If the keyid was empty then default to exporting MY PUBLIC KEY
   if(empty($keyid)){
     $keyid = $this->userEmail;
   }

   if(!$this->check_all()){
     $result[err] = 1;
     $result[msg] = "Error: Can't export the public key. (in function export_key - 1)";
     return $result;
   }
	
   // fIrst check if the key is on the keyring
   if (!$this->check_keyID($this->userEmail)){
     $result[err] = 1;
     $result[msg] = "Error: Can't export the public key. (in function export_key - 1)";
     return($result);
   } 
	

   $keyid = EscapeShellCmd($keyid);

   $command = GPG_BIN.GPG_PARAMS.$this->user_homedir." --batch --armor --export '".$keyid."'";
   
   
   if(GEN_HTTP_LOG){
     $command .= " 2>/dev/null";
   }
   

   exec($command, $gpg_result, $errorcode);
   
   if($errorcode){
     $result[err] = 1;
     $result[msg] = "There was a problem exporting the public key.";
   }else{
     $result[msg] = array("keyblock" => implode("\n",$gpg_result));
   }

   return($result);
	
 } // end function export_key



 function check_pub_key(){
   $result = $this->export_key();
   if($result[err]){
     return false;
   }else{
     return true;
   }
 }



 /*
  function remove_key():
  
  Remove a public key from keyring
  Returns false when failed.  If failed, look at the $this->error for the reason.
 */
 function remove_key($key = ""){

   	if(!$this->check_all()){
		return(false);
	}
	
 	// first check if the key is on the keyring
	if (!$this->check_keyID($this->recipientEmail)){
		return(false);
	} 
	
	// Depreciated to class property $priv_path = $this->gpg_path.ereg_replace("[@]","_",$this->userEmail)."/.gnupg";

  	if($key == ""){
  		$this->error = "Error: no specified public key to remove. (in function remove_key - 1)";
		return(false);
	}

	$command = GPG_BIN.GPG_PARAMS.$this->user_homedir." --batch --yes --delete-key '$key'";

	if(GEN_HTTP_LOG){
		$command .= " 2>/dev/null";
	}

	system($command,$errorcode);

	if($errorcode) {
		$this->error = "Error: Can't remove the key. (in function remove_key - 2) ";
		return(false);
	}
	return(true);
	
 } // end function remove_key




























 /* 
  function gen_key()

  Make the generation of keys controlled by a parameter file.
  This feature is not very well tested and is not very well documented.
  Just use this if you do not have how to generate the key in a secure machine.
 */
 function generate_key($username, $comment="", $userEmail, $passphrase){


	
   // the utf8.php includes is necessary, because to generate the key is needed to
   // enter the characters in the UTF-8 form :-/
   include_once("libs/utf8.php");
 
   // verify the variables
   if(empty($username)){
     $result[err] = 1;
     $result[msg] =  "Error: The username is empty. (in function gen_key - 1)";
     return($result);
   }
   if(empty($userEmail)){
     $result[err] = 1;
     $result[msg] = "Error: The email is empty. (in function gen_key - 2)";
     return($result);
   }
   if(empty($passphrase)){
     $result[err] = 1;
     $result[msg] = "Error: The passphrase is empty. (in function gen_key - 3)";
     return($result);
   }
   if(strlen(trim($passphrase)) < GPG_PASS_LENGTH){
     $result[err] = 1;
     $result[msg] = "Error: The passphrase is too short. (in function gen_key - 4)".count(trim($passphrase));
     return($result);
   }
   
   
   $utf = new utf;
   $utf->loadmap($this->utf_lib_dir . "8859-1.TXT","iso");
   

   // Generate GPG Script used to generate key
   unset($tmpConfig);

   // prepares the temporary config file
   $keyring_directory = $this->user_homedir;

   $tmpConfig = "Key-Type: DSA\r\n";
   $tmpConfig .= "Key-Length: 1024\r\n";

   $tmpConfig .= "Subkey-Type: ELG-E\r\n";
   $tmpConfig .= "Subkey-Length: ". $GLOBALS[config][gpg_key_size] ."\r\n";


   $name_real  = $utf->cp2utf($username,"iso");
   $tmpConfig .= "Name-Real: ".$name_real ."\r\n";

   // Comments are disabled right now. No decent reason for them at the moment
   //   if (!empty($comment))
   //     $tmpConfig .= "Name-Comment: ".$utf->cp2utf($comment)."\r\n";

   $tmpConfig .= "Name-Email: ".$userEmail."\r\nExpire-Date: 0\r\nPassphrase: ".$passphrase."\r\n";
   $tmpConfig .= "%commit\r\n";

   $tmpConfig = addslashes($tmpConfig);
   $time = time();

   // FIXME: Error Trap
   $sql = "INSERT into dc_gpg_keygen_queue (user_id, batch_data, date, keyring_directory, notification_recipient) values ('$this->user_id', '$tmpConfig', '$time', '$keyring_directory', '$name_real')";
   $dbresult = $GLOBALS[db]->getOne($sql);



 } // end function gen_key
 




// remove dirs recursivelly -- from commum_function (UebiMiau)
function RmdirR($userPath) {
	
	// just for a minimum of security
	if($this->gpg_path != GPG_USER_DIR or $this->gpg_path = "/"){
		return(false);
	}
	$location = $userPath;
	$all=opendir($location); 
        if (substr($location,-1) <> "/") $location = $location."/";
        $all=opendir($location);
        while ($file=readdir($all)) {
                if (is_dir($location.$file) && $file <> ".." && $file <> ".") {
                        $this->RmdirR($location.$file);
                        unset($file);
                } elseif (!is_dir($location.$file)) {
                        unlink($location.$file);
                        unset($file);
                }
        }
        closedir($all);
        unset($all);
        rmdir($location);
	
} // end function RmdirR










/* 
  function change_passphrase()

 */
 function change_passphrase($old_passphrase, $new_passphrase){

   $tmpToken = md5(uniqid(rand()));
   
   // create vars to hold paths and filenames
   $stdout_log = $this->user_homedir . $tmpToken.".stdout";

   $command = GPG_BIN.GPG_PARAMS.$this->user_homedir . " --command-fd=0  --status-fd=1  --edit-key '" . $this->userEmail ."' 1>".$stdout_log;


   $pipe = popen($command, "w");

   # Check to see if the passphrase is correct by passing it
   # to the pipe and recording the output to a file (see above 1>$stdout_log)
   # then looking into the output to see if BAD_PASSPHRASE or GOOD_PASSPHRASE is found
   fwrite($pipe, "passwd\n");
   fwrite($pipe, $old_passphrase."\n");
   fwrite($pipe, "quit\n");
   pclose($pipe);

   # Load the output and see if we got a correct/incorrect passphrase
   $fd = fopen($stdout_log, "r");
   $log_result = fread ($fd, filesize ($stdout_log));

   if(strstr($log_result, "BAD_PASSPHRASE")){        // User entered an incorrect passphrase, return error

     $result[err] = 1;
     $result[msg] = "Incorrect current passphrase.";     
     
   }elseif(strstr($log_result, "GOOD_PASSPHRASE")){  // Passphrase was correct, do full passphrase change

     $pipe = popen($command, "w");

     fwrite($pipe, "passwd\n");
     fwrite($pipe, $old_passphrase."\n");
     fwrite($pipe, $new_passphrase."\n");
     fwrite($pipe, "save\n");
     pclose($pipe);

     $result[err] = 0;
     $result[msg] = "Passphrase change successful.";     
   }

   return($result);

 }



 



/* 
  function list_key_info()

   Returns false when failed. If failed, look at $this->error for the reason.
 */
 function list_key_info($keyEmail){
	
	if(!$this->check_all()){
		return(false);
	}
	
 	if (!$this->check_keyID($this->username)){
		return(false);
	}
	
	// Depreciated to class property $priv_path = $this->gpg_path.ereg_replace("[@]","_",$this->userEmail)."/.gnupg";

	$command = GPG_BIN.GPG_PARAMS.$this->user_homedir." --list-key  --fingerprint --with-colons  '". $keyEmail  ."'";

	if(GEN_HTTP_LOG){
		$command .= " 2>/dev/null";
	}

	exec($command, $keyArray, $errorcode);
	
	if($errorcode){
	  //		$this->error = 
	  $result[err] = 1;
	  $result[msg] = "Error: Can't list the keys. (in function list_keys - 1)";

	}else{

	  // Get Pub Key Info
	  $tmp = explode(":",$keyArray[0]);
	  $info[type]     = $tmp[0];
	  $info[length]   = $tmp[2];

	  switch($tmp[3]){
	  case "1":
	    $info[algo] = "RSA";
	    break;
	    
	  case "16":
	    $info[algo] = "ElGamal (encrypt only)";
	    break;
	    
	  case "17":
	    $info[algo] = "DSA (sometimes called DH, sign only)";
	    break;

	  case "20":
	    $info[algo] = "ElGamal (sign and encrypt)";
	    break;

	  default:
	    $info[algo] = "---";
	    break;
	  }
	
	  $info[id]       = $tmp[4];
	  $info[creation] = $tmp[5];
	  $info[expiration] = $tmp[6] ? $tmp[6] : "---";
	  $info[user_id]  = $tmp[9];

	  // Get Fingerprint
	  $tmp = explode(":",$keyArray[1]);
	  $info[fingerprint] = $tmp[9];


	  // Get Sub key info
	  $tmp = explode(":",$keyArray[2]);
	  switch($tmp[3]){
	  case "1":
	    $info[algo] .= "/RSA";
	    break;
	    
	  case "16":
	    $info[algo] .= "/ElGamal (encrypt only)";
	    break;
	    
	  case "17":
	    $info[algo] .= "/DSA (sometimes called DH, sign only)";
	    break;

	  case "20":
	    $info[algo] .= "/ElGamal (sign and encrypt)";
	    break;

	  default:
	    $info[algo] = "---";
	    break;
	  }
	  $result[msg] = $info;
	}

	return($result);
	 
 } // end function list_key_info







 function get_all_key_info($keyid=""){

   // First get he key info
   $result = $this->list_keys($keyid);
   if($result[err]){
     $return_result[err] = 1;
     $return_result[msg][] = $result[msg];
   }else{
     $key_info = current($result[msg]);
   }




   // Next get the actual key itself
   $result = $this->export_key($keyid);
   if($result[err]){
     $return_result[err] = 1;
     $return_result[msg][] = $result[msg];
   }else{
     $key_block = $result[msg];
   }


   if(!$return_result[err]){
     $return_result[msg] = array_merge($key_info, $key_block);
   }
   return($return_result); 
 }


 function check_key_in_generation_queue(){


   $result = $this->get_ce_status($this->user_id);

   if($result[msg][key_found] == "NO"){
     return false;
   }elseif($result[msg][key_found] == "YES"){
     return true;
   }
 }



 function get_ce_status($user_id){

   // How many keys in queue total?
   $sql = "SELECT count(user_id) FROM dc_gpg_keygen_queue";
   $count = $GLOBALS[db]->getOne($sql);
   $ce_status["queue_total"] = $count ? $count : "None";

   // Is key in queue?
   $sql = "SELECT user_id FROM dc_gpg_keygen_queue WHERE user_id='$user_id'";
   $found = $GLOBALS[db]->getOne($sql);

   if(!$found){
     $ce_status["key_found"] = "NO";
   }else{
      $ce_status["key_found"] = "YES";

      /// What position in the queue?
      $sql = "SELECT user_id FROM dc_gpg_keygen_queue ORDER BY date ASC";
      $dbresult = $GLOBALS[db]->query($sql);
      
      while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
	$position_number++;
	if($entry[user_id] == $user_id)
	  break;
      }
      $ce_status["key_position"] = $position_number;      
   }

   if($ce_status){
     $result[msg] = $ce_status;
   }else{
     $result[err] = 1;
   }
   return $result;
 }



} // end class

?>
