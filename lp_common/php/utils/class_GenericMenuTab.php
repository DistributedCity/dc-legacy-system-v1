<?php

/* implements a generic menu tab object */

class GenericMenuTab extends GenericObject {
   
/* Public Methods
 */
   function GenericMenuTab($config, $id, $href, $title) {
      $this->GenericObject($config);

      $this->initAttr('id', $id);
      $this->initAttr('href', $href);
      $this->initAttr('title', $title);
      $this->initAttr('selected', false);
      $this->initAttr('shown', true);
   }

   function getID() {
      return $this->getAttr('id');
   }

   function getHref() {
      return $this->getAttr('href');
   }

   function setHref($href) {
      $this->setAttr('href', $href);
   }

   function getTitle() {
      return $this->getAttr('title');
   }

   function setTitle($title) {
      $this->setAttr('title', $title);
   }

   function isSelected() {
      return (bool)$this->getAttr('selected');
   }

   function setSelected($state=true) {
      $this->setAttr('selected', (bool)$state);
   }

   function isShown() {
      return (bool)$this->getAttr('shown');
   }

   function setShown($state=true) {
      $this->setAttr('shown', (bool)$state);
   }

/* Pure Virtual (Protected) Methods
 */

};

?>
