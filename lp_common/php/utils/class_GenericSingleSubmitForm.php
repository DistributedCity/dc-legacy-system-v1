<?php

require_once('class_GenericObject.php');

class GenericSingleSubmitForm extends GenericObject {

/* Public Methods
 */
   function GenericSingleSubmitForm(&$app, &$po) {
      $this->varname = 'formID';

      parent::GenericObject($app->getConfig());
      $this->initAttr('formId', $this->generateFormId() );
      $this->initAttr('lastSubmittedFormId', false );

      $this->assignTmpl($po);
   }

   function getFormId() {
      return $this->getAttr('formId');
   }

   function getLastSubmittedFormId() {
      return $this->getAttr('lastSubmittedFormId');
   }

   function newFormId() {
      $this->setAttr('formId', $this->generateFormId() );
   }

   function assignTmpl(&$po) {
      $po->assignHiddenInput( $this->varname, $this->getFormId() );
   }

   function isDoubleSubmitted() {
      $bDouble = false;
      $id = $this->HTTP_GET_VARS[$this->varname];
      if(!$id) {
         $id = $this->HTTP_POST_VARS[$this->varname];
      }
      if($id) {
         $bDouble = ($id == $this->getLastSubmittedFormId()) ? true : false;
         $this->setAttr('lastSubmittedFormId', $id);
      }
      return $bDouble;
   }

/* Pure Virtual (Protected) Methods
 */
   function _ignoredVars() {
      $ignored = parent::_ignoredVars();
      return $ignored;
   }

   function generateFormId() {
      $mt = microtime();
      srand(time());
      return rand(10000, 1000000);
   }

}

?>
