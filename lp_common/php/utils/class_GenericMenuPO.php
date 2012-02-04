<?php

require_once('class_GenericMenu.php');
require_once('class_GenericPresentationObject.php');

/* Implements a generic menu presentation object */

/* The class assumes a template containing tab sections
 * that look like this:
 *
 * note that the prefix (eg tabHelp) must be used for
 * all vars, and the suffixes (On, Off, Href, Title)
 * are important. The prefix == tab Id.
 *
 *  {{section:tabHelpOn autohide="on"}}
 *     <td bgcolor="#FFFFFF">
 *        <b>{{tabHelpTitle}}</b>
 *     </td>
 *  {{/section:tabHelpOn}}
 *
 *  {{section:tabHelpOff autohide="on"}}
 *     <td>
 *        <a href="{{tabHelpHref}}"><b>{{tabHelpTitle}}</b></a>
 *     </td>
 *  {{/section:tabHelpOff}}
 */


class GenericMenuPO extends GenericPresentationObject {
/* Public Methods
 */

   function GenericMenuPO(&$app) {
      parent::GenericPresentationObject($app);

      $this->initAttr('menu');
      $this->setupMenu();
   }

   function& getMenu() {
      return $this->getAttr('menu');
   }

   function appendQueryVar($href, $key, $val) {
      $sym = strstr($href, '?') ? '&' : '?';
      return "$href$sym$key=$val";
   }

   function process($command) {
      $menu = $this->getMenu();
      
      $selected = $menu->getSelectedTabId();
      if( !$selected ) {
         $selected = $this->getQueryVar( $menu->getMenuID() );
         if(!$selected) {
            $selected = $menu->getDefaultTabId();
         }
      }
      if($selected) {
         $menu->selectTab( $selected );
         $this->assignHiddenInput($menu->getMenuID(), $selected);
      }

      $tabs = $menu->getTabList();

      foreach($tabs as $tab) {

         $hrefVar = $tab->getID() . 'Href';
         $titleVar = $tab->getID() . 'Title';
         $onVar = $tab->getID() . 'On';
         $offVar = $tab->getID() . 'Off';

         $tabHref = $this->appendQueryVar($tab->getHref(), $menu->getMenuID(), $tab->getID() );

         $this->assign(array($hrefVar => $tabHref,
                             $titleVar => $tab->getTitle()) );

         if( !$tab->isShown() ) {
            $this->showSection($onVar, false);
            $this->showSection($offVar, false);
         }
         else {
            $this->showSection($offVar, !$tab->isSelected() );
            $this->showSection($onVar, $tab->isSelected());
         }
      }
      return parent::process($command);
   }


/* Pure Virtual (Protected) Methods
 */
   function setupMenu() {
      $menu = new GenericMenu( $this->config );
      $this->setAttr( 'menu', $menu );

      /* derived class should call menu->addTab(), showTab(), etc */
   }

}

?>
