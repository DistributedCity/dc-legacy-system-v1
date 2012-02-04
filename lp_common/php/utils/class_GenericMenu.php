<?php

require_once('class_GenericMenuTab.php');

/* implements a generic menu object */

class GenericMenu extends GenericObject {

/* Public Methods
 */
   function GenericMenu($config, $id='menubar') {
      $this->GenericObject($config);

      $this->initAttr('tabs', array());
      $this->initAttr('selected');
      $this->initAttr('id', $id);
   }

   function addTab($id, $href, $title=null) {
      $tabs =& $this->getAttr('tabs');

      if(!$tabs[$id]) {
         $tabs[$id] = new GenericMenuTab($this->config, $id, $href, $title);
      }
   }

   function setMenuID($id) {
      $this->setAttr('id', $id);
   }

   function getMenuID() {
      return $this->getAttr('id');
   }

   function selectTab($id) {
      $selected =& $this->getAttr('selected');
      $tabs =& $this->getAttr('tabs');

      if($tabs[$id]) {
         if($selected) {
            $tabs[$selected]->setSelected(false);
         }
         $tabs[$id]->setSelected(true);
         $this->setAttr('selected', $id);
      }
   }

   function getSelectedTabId() {
      return $this->getAttr('selected');
   }

   function getSelectedTab() {
      $selected =& $this->getAttr('selected');
      $tabs =& $this->getAttr('tabs');

      return $tabs[$selected];
   }

   function setDefaultTabId($id) {
      $this->setAttr('defsel', $id);
   }

   function getDefaultTabId() {
      return $this->getAttr('defsel');
   }

   function showTab($id, $state=true) {
      $tabs =& $this->getAttr('tabs');
      $tab = $tabs[$id];

      if($tab) {
         $tab->setShown($state);
      }
   }

   function getTabList() {
      return $this->getAttr('tabs');
   }


/* Pure Virtual (Protected) Methods
 */

};

?>
