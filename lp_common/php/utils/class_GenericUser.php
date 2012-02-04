<?php

class GenericUser extends GenericObject {

/* Public Methods
 */
   function GenericUser($config) {
      $this->GenericObject($config);

      $this->initAttr('isLoggedIn', false);
      $this->initAttr('username', false);
      $this->initAttr('password', false);
      $this->initAttr('form', false);
   }

   function getUserName() {
      
      return $this->getAttr('username');
   }

   function setUserName($name) {
      return $this->setAttr('username', $name);
   }

   function getPassword() {
      return $this->getAttr('password');
   }

   function changePassword($oldpass, $newpass) {
      $success = false;
      if($oldpass == $this->getPassword() ) {
         $this->setAttr(password, $newpass);
         $success = true;
      }
      return new GenericResult($success, null, null);
   }

   function login($user, $pass) {
      if(!$this->isLoggedIn()) {
         $result = $this->authenticate($user, $pass);
         if($result->successful() ) {
            $this->setAttr('username', $user);
            $this->setAttr('password', $pass);
            $this->setAttr('isLoggedIn', true);
         }
      }
      else {
         $result = new GenericResult(false, null, new GenericError(0, "already logged in", 
                                                                   __FILE__, __LINE__) );
      }
      return $result;
   }

   function isLoggedIn() {
      return $this->getAttr('isLoggedIn');
   }

   function logout() {
      $this->setAttr('username', false);
      $this->setAttr('password', false);
      $this->setAttr('isLoggedIn', false);
   }

   // useful for setting/getting persistent single submit forms.
   function setForm(&$formObject) {
      return $this->setAttr('form', $formObject);
   }

   function& getForm() {
      return $this->getAttr('form');
   }


/* Pure Virtual (Protected) Methods
 */

   // override this with your own authentication
   function authenticate($user, $pass) {
      return new GenericResult(false, null, new GenericError(0, "authentication not implemented", 
                                                             __FILE__, __LINE__) );
   }

}

?>
