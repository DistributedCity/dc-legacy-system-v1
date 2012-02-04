<?php

class GenericError {

/* Data
 */
   var $code;
   var $string;
   var $file;
   var $line;

/* Public Methods
 */

   function GenericError($code, $string, $file, $line) {
      $this->code = $code;
      $this->string = $string;
      $this->file = $file;
      $this->line = $line;
   }

   function getCode() {
      return $this->code;
   }

   function getString() {
      return $this->string;
   }

   function getFile() {
      return $this->file;
   }

   function getLine() {
      return $this->line;
   }

/* Pure Virtual (Protected) Methods
 */

}

?>
