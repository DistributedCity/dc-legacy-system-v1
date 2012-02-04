<?php

require_once('class_GenericResult.php');

/* error codes returned by this class
 *  1:  unable to list keys
 *  2:  unable to import key
 *  3:  unable to encrypt to key
 *  4:  public key not found.
 */

class GPGWrapper {

   var $gpg_homedir;     //Location of the .gnupg/ directory, key rings, etc.
                         //  (ex. "/home/hubbins/.gnupg")

   function GPGWrapper($gpg_homedir) {
      $this->gpg_homedir    = $gpg_homedir;
   }


   function initKeyDB() {
      $error = null;
      $command = "rm {$this->gpg_homedir}/pubring.gpg";
      exec ($command, $output, $status);

      if ($status || !isset($output) || !is_array($output)) {
         $error = new GenericError(1, "rm failed with return status: $status", __FILE__, __LINE__);
      }

      return new GenericResult($error ? false : true, $output, $error);
   }

/****************************************************************************
    string list_keys()

    returns the output from the gpg command run. if there is no output, a 
    statement with the return code is returned. error checking for failure will 
    be implemented outside this function. 
*/

   function list_keys() {
      $error = null;
      $command = "gpg --homedir={$this->gpg_homedir} --list-keys";

      exec ($command, $output, $status);

      if (!isset($output) || !is_array($output)) {
         $error = new GenericError(1, "--list-keys failed with return status: $status", __FILE__, __LINE__);
      }

      return new GenericResult($error ? false : true, $output, $error);
   }    

/*****************************************************************************
    int import_public_key()

    error checking for failure will be implemented outside this 
    function. 
*/

   function import_public_key( $public_key ) {
      $error = null;
      $safe_public_key = $this->my_shell_escape($public_key);

      $command = "printf '%b' $safe_public_key | gpg --homedir={$this->gpg_homedir} --import 2>&1";

      exec ($command, $output, $status);

      $output = implode($output, "\n");

      /*  From GPG man page:
       *    The  program  returns  0  if  everything was fine, 1 if at
       *    least a signature was bad, and other error codes for fatal
       *    errors.
       */ 
      $success = ( $status == 0 || $status == 1 ) ? true : false;
      if (!$success) {
         $error = new GenericError(2, 'GPG unable to import key. output: $output', __FILE__, __LINE__);
      }
      return new GenericResult($success, $output, $error);
   }

   function import_public_key_from_file( $filename ) {
      $error = null;
      $safe_filename = $this->my_shell_escape($filename);

      $command = "gpg --homedir={$this->gpg_homedir} --import $safe_filename 2>&1";

      exec ($command, $output, $status);

      $output = implode($output, "\n");

      /*  From GPG man page:
       *    The  program  returns  0  if  everything was fine, 1 if at
       *    least a signature was bad, and other error codes for fatal
       *    errors.
       */ 
      $success = ( $status == 0 || $status == 1 ) ? true : false;
      if (!$success) {
         $error = new GenericError(2, "GPG unable to import key $safe_filename. output: $output", __FILE__, __LINE__);
      }
      return new GenericResult($success, $output, $error);
   }


/******************************************************************************
    string encrypt_to_key($message, $email_address)

    returns an $encrypted_message that has been encrypted $email_address. I 
    assume that the returned $encrypted_message string will be used later in 
    something like mail().

    encrypt_to_key() uses the given $email_address as the argument to 
    the --recipient option.

*/

   function encrypt_to_key($message, $email_address) {
      $error = null;
      /*
          The not-so-obvious arguments...
          --armor : Output in ASCII-armored format
          --batch : Do not prompt for warnings or validations
          --always-trust : Help smooth the auto-send process
          --no-secmem-warning : Supreess insecure memory warnings
      */

      $safe_message = $this->my_shell_escape($message);
      $safe_email_address = $this->my_shell_escape($email_address);

      $command = "printf '%b' $safe_message | gpg  --homedir={$this->gpg_homedir} --encrypt --armor " .
                 "--batch --always-trust --no-secmem-warning --recipient $safe_email_address 2>&1";

      exec ( $command, $output, $status );

      $output = implode($output, "\n");

      $success = ( $status == 0 ) ? true : false;
      if (!$success) {
         $code = 3;
         if(strstr($output, "public key not found")) {
            $code = 4;
         }
         $error = new GenericError($code, $output, __FILE__, __LINE__);
      }
      return new GenericResult($success, $output, $error);
   }

   function my_shell_escape($str) {
      return escapeshellarg($str);
   }
}
?>

