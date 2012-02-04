<?php

require_once('class_GenericError.php');

class GenericResult {
/* Data
 */
   var $error;
   var $success;
   var $result;

/* Public Methods
 */

   function GenericResult($success, $result, $error=null) {
      $this->success = $success;
      $this->result = $result;
      $this->error = $error;
   }

   function successful() {
      return $this->success;
   }

   function result() {
      return $this->result;
   }

   function error() {
      return $this->error;
   }

/* Pure Virtual (Protected) Methods
 */

};

?>
