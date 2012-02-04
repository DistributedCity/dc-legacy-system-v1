<?php

require_once('class_GenericResult.php');

class GenericVar {}

class GenericObject {
   var $config;            // application specific config
   var $HTTP_GET_VARS;
   var $HTTP_POST_VARS;
   var $HTTP_COOKIE_VARS;

   var $vars;
   var $error;

/* Public Methods
 */
   function GenericObject($config) {
      $this->vars = new GenericVar;
      $this->config = $config;
      $this->init();
   }

   // init only inits generic / per request data
   function init() {
      $this->HTTP_GET_VARS = $_GET ? $_GET : $GLOBALS[HTTP_GET_VARS];
      $this->HTTP_POST_VARS = $_POST ? $_POST : $GLOBALS[HTTP_POST_VARS];
      $this->HTTP_COOKIE_VARS = $_COOKIE ? $_COOKIE : $GLOBALS[HTTP_COOKIE_VARS];
      $this->error = null;
   }

   function getQueryVar($key) {
      $val = $this->HTTP_GET_VARS[$key];
      if(!$val) {
         $val = $this->HTTP_POST_VARS[$key];
      }
      return $val;
   }

   function getQueryVars() {
      return array_merge($this->HTTP_POST_VARS, $this->HTTP_GET_VARS);
   }

   function getConfig() {
      return $this->config;
   }

   function setConfig($config) {
      $this->config = $config;
   }

   function vardump($thing=null) {
      $thing = $thing ? $thing : $this;
      echo "<xmp>\n"; var_dump($thing); echo "\n</xmp>";
   }

   function print_r($thing=null) {
      $thing = $thing ? $thing : $this;
      echo "<xmp>\n"; print_r($thing); echo "\n</xmp>";
   }

   /******************************************************
   * A handy function to iterate over an array and print *
   * it as an html list.  recurses if necessary          *
   ******************************************************/
   function getvar($val, $key=null, $depth=0) {
      if($depth > 15) {
         $buf = "<UL><LI>Exceeded Max Depth 15</UL>\n";
      }
      else {
         if (is_array($val) || is_object($val)) {
            if($key === null) {
               $key = '<LI> root';
            }
            $buf .= "$key => $val\n";

            $buf .= "<UL>\n";

            if(is_object($val) && method_exists($val, '_ignoredVars')) {
               $ignore = $val->_ignoredVars();
            }

            foreach($val as $key => $iter) {
               if(is_array($ignore) && in_array($key, $ignore)) {
                  $buf .= "<LI>" . "$key => ($type) *IGNORED*";
               }
               else {
                  $buf .= "<LI>" . $this->getvar($iter, $key, $depth+1);
               }
            }
            $buf .= "</UL>\n";
         }
         else {
            $type = substr(gettype($val), 0, 1);
            $buf .= "$key => [$type] $val\n";
         }
      }
      
      return $buf;
   }

   /********************************
   * Prints out result of getvar   *
   ********************************/
   function echovar($list) {
      echo $this->getvar($list);
   }

   function dump() {
      $this->echovar($this );
   }

/* Private Methods
 */

   /* returns a list of variables that should not be
    * printed or serialized. Inherited objects should
    * override this as necessary.
    */
   function _ignoredVars() {
      return array('config', 
                   'HTTP_GET_VARS', 
                   'HTTP_POST_VARS',
                   'HTTP_COOKIE_VARS',
                   'error');
   }

   /* called automagically by php when object is about to be serialized.
    * in php4.0.5 this method causes php to crash unless one is extremely
    * careful.  Do not change this func unless you _really_ know what you
    * are doing.
    */
   function __sleep() {
      $ignored = $this->_ignoredVars();

      foreach($ignored as $value) {
         unset($this->$value);
      }

      foreach($this as $key=>$value) {
         $serial[] = $key;
      }

      return $serial;
   }
   
   // called automagically by php when object has been de-serialized
   function __wakeup() {
      $this->init(null);
   }

/* Pure Virtual (Protected) Methods
 */

   // get/init/setAttr should only be called by classes
   // that inherit from GenericObject
   function &getAttr($name) {
      $this->error = null;
      if (!isset($this->vars->$name)) {
         $this->error = new GenericError(1, "value '$name' not set.", 
         __FILE__, __LINE__  );
         $attr = null;
      }
      return $this->vars->$name;
   }

   function initAttr($attr, $value=false) {
      $this->error = null;
      $this->vars->$attr = $value;
      return new GenericResult(true, null, null );
   }

   function setAttr($attr, $value) {
      $this->error = null;
      if (!isset($this->vars->$attr) && $this->vars->$attr !== null ) {
         $this->error = new GenericError(1, "value '$attr' doesn't exist.", 
         __FILE__, __LINE__  );
         return null;
      }
      $this->vars->$attr = $value;
      return true;
   }

};

?>
