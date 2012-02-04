<?php

require_once('class_GenericObject.php');
require_once('class_GenericUser.php');
require_once('class_GenericSession.php');
require_once('class_GenericSingleSubmitForm.php');
require_once('class.TemplateMgr.php');

class GenericApp extends GenericObject {

/* Public Methods
 */
   function GenericApp($config) {
      $this->GenericObject($config);

      $this->resetTemplates();
      $this->initAttr('sess', new GenericSession() );

      $this->user =& $this->getUser();
      $this->initUser();
   }

   function run() {
      $tpl =& $this->getTemplates();

      $this->processQuery();
      return $tpl->global_parse_to_string( $this->ignoredTemplates() );
   }

   // override this if you need to ignore any template handles during parsing
   function ignoredTemplates() {
      return array();
   }


/* Pure Virtual (Protected) Methods
 */
   function setTemplateRoot($path) {
      $tpl =& $this->getAttr('tpl');
      $tpl->set_root($path);
   }

   function resetTemplates() {
      $this->initAttr('tpl', new TemplateMgr() );
   }

   function &getTemplates() {
      return $this->getAttr('tpl');
   }

   function setSession($session) {
      $this->initAttr('sess', $session);
   }

   function setUser(&$user) {
      $sess =& $this->getAttr('sess');
      $sess->setAttr('user', $user);
      $this->user =& $sess->getAttr('user');
   }

   function& getUser() {
      $sess =& $this->getAttr('sess');
      $user =& $sess->getAttr('user');
      if(!$user) {
         $user = $this->newUser();
         $this->setUser($user);
         $sess->setAttr('user', $user);
      }
      return $user;
   }

   function logoutUser() {
      $this->user->logout();
      $sess = $this->getAttr('sess');
      $sess->end();
   }

   function& newUser() {
      $user =& new GenericUser($this->getConfig());
      return $user;
   }

   function initUser() {
      $this->user->setConfig($this->config);
   }

   function processQuery() {

   }

   function doLogin() {
   }

};

?>
