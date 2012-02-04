<?php

require_once('class_GenericObject.php');

class GenericPresentationObject extends GenericObject {
   var $tplId = null;

/* Public Methods
 */
   function GenericPresentationObject(&$app) {
      $this->app =& $app;
      parent::GenericObject($app->getConfig());
   }

   function setTmplId($id) {
      $this->tplId = $id;
   }

   function process($command) {
      return new GenericResult(true, null, null);
   }

   function assign($varArray, $global=false) {
      $tpl =& $this->app->getTemplates();

      if( $this->tplId && !$global ) {
         $tpl->assign($this->tplId, $varArray);
      }
      else {
         $tpl->global_assign($varArray);
      }
   }

   function showSection($sectionId, $show=true, $rows=null, $global=false) {
      $tpl =& $this->app->getTemplates();

      if( $this->tplId && !$global ) {
         if($show) {
            $tpl->show_section($this->tplId, $sectionId, $rows);
         }
         else {
            $tpl->hide_section($this->tplId, $sectionId, $rows);
         }
      }
      else {
         if($show) {
            $tpl->global_show_section($sectionId, $rows);
         }
         else {
            $tpl->global_hide_section($sectionId, $rows);
         }
      }
   }

   function preFillForm($vars=null) {
      $vars = $vars ? $vars : $this->getQueryVars();
      $this->assign($vars);
   }

   function preFillHidden($vars=null, $keys=null, $htmlescape=true) {
      $vars = $vars ? $vars : $this->getQueryVars();
      foreach($vars as $key => $val) {
         if(!$keys || in_array($key, $keys)) {
            $this->assignHiddenInput($key, $val, $htmlescape);
         }
      }
   }

   function assignHiddenInput($key, $val, $htmlescape=true) {
      if($htmlescape) {
         $val = htmlentities($val, ENT_QUOTES);
      }
      $this->assign(array(hiddenName => $key,
                          hiddenValue => $val),
                    true);
   }


   /* this method assumes that the form has one or more
    * submit buttons and that they are named according
    * to this scheme:
    *
    * <input type="submit" name="submit:command" value="some value">
    *
    * This is useful for identifying which submit button actually
    * submitted the form.
    */
   function findSubmitButton() {
      // search post vars, then get vars.
      $queries = array($this->HTTP_POST_VARS, $this->HTTP_GET_VARS);

      foreach($queries as $query) { 
         foreach($query as $key => $value) {
            $newvar = explode(':', $key, 2);

            if($newvar[0] === 'submit') {
               $val = $newvar[1];
               
               // input type=image stupidly appends _x and _y.
               if( substr($val, strlen($val) - 2) === '_x' ) {
                  $val = substr($val, 0, strlen($val) - 2);
               }
               return $val ;
            }
         }
         if(!$command) {
            $command = $query_vars[command];
         }
      }
      return $command;
   }


/* Pure Virtual (Protected) Methods
 */
}

?>
