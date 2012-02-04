<?php


class user_html {

   var $user_id;

   function user_html($user_id) {

      $this->user_id = $user_id;

   }


   function render_user_image_selection_screen($selection_type) {

      $block = yats_define($GLOBALS[config][template_base]."master/registration_image_form.html");

      // Selection type is either:
      // Initial Registration or Settings

      if ($selection_type == "registration") {

         yats_hide($block, "SettingsTab", true);    

      }elseif($selection_type == "settings") {

         yats_hide($block, "new_user_setup", true);
         yats_hide($block, "RegistrationTab", true);

      }


      // Assign error if it exists
      if ($error) {
         yats_assign($block, array("error_text" => $error));
      }

      // Assign hidden vars to the form
      yats_assign($block, array("sid"      => session_id()));


      yats_assign($block, array("user_id" => $this->user_id ));


      // Get list of all Groups available
      $avatar_collection_directory = $GLOBALS[config][images_base]."user/";
      $d = dir($avatar_collection_directory);
      while ($entry=$d->read()) {
         if (strstr($entry, "group_")) {
            $avatar_groups[] = $entry;
         }
      }
      $d->close();




      // For each group, split up into sections of max 100
      foreach($avatar_groups as $avatar_group) {

         $section_id = 0;
         $x = 0;

         // Get list of all available avatars
         $avatar_directory = $avatar_collection_directory.$avatar_group;
         $d = dir($avatar_directory);
         while ($entry=$d->read()) {
            if (strstr($entry, "gif")) {

               $x++; // Increment section total
               if ($x % 100 == 1)
                  $section_id++;

               $avatars[$avatar_group][$section_id][] = $entry;
            }
         }

         $d->close();
      }


      if ($GLOBALS[HTTP_GET_VARS][show_group]) {
         $show_group   = $GLOBALS[HTTP_GET_VARS][show_group];
         $show_section = $GLOBALS[HTTP_GET_VARS][show_section] ? $GLOBALS[HTTP_GET_VARS][show_section] : "1";
      }
      else {

         // Pick a random group if user has not specifically set a group to show
         foreach($avatars as $group => $sections) {
            foreach($sections as $section_id => $section_data) {
               $choices[] = array("group"      => $group,
               "section_id" => (string)$section_id);
            }
         }



         // seed with microseconds since last "whole" second
         srand ((double) microtime() * 1000000);
         $random_pick = rand(0, count($choices)-1);
         $show_group = $choices[$random_pick][group];
         $show_section = $choices[$random_pick][section_id];

         //$show_group   = $groups[rand(0, count($groups)-1)];
         //$show_section = rand(1, count($avatars[$show_group]));

      }




      $avatars_section = $avatars[$show_group][$show_section];

      if ($selection_type == "registration") {
         $avatar_column = yats_define($GLOBALS[config][template_base]."master/registration_image_column.html");

      }elseif($selection_type == "settings") {

         $avatar_column = yats_define($GLOBALS[config][template_base]."user/settings/image_column.html");
      }




      $icons_per_column = 10;
      for ($x=0; $x<count($avatars_section); $x += $icons_per_column) {

         $avatar_slice =  array_slice ($avatars_section, $x, $icons_per_column);




         foreach($avatar_slice as $avatar_item) {

            $icon_name[] = str_replace(".gif", "", $avatar_item);
            $icon_src[]  = "/images/user/".$show_group."/".$avatar_item;
            $icon_group[]  = $show_group;
         }


         //yats_assign($block, array("icon_name" => $icon_name,
         yats_assign($avatar_column, array('icon_name' => $icon_name,
                                           'icon_src'  => $icon_src,
                                           'icon_group'=> $icon_group));



         $avatar_rows[]= yats_getbuf($avatar_column);

         unset($avatar_slice, $icon_name, $icon_src);
      }

      yats_assign($block, array("avatar_rows" => $avatar_rows));



      //toolbox::dprint($avatars);

      foreach($avatars as $group => $sections) {

         foreach($sections as $section_id => $elements) {

            $menu[show_group][]   = $group;
            $menu[show_section][] = (string)$section_id;
            $menu[show_section_label][] = ucfirst(str_replace("group_", "", $group))."&nbsp;".$section_id;
            if ($show_group == $group && $show_section == $section_id) {
               $menu[tab_state][] = "On";
            }
            else {
               $menu[tab_state][] = "Off";
            }

         }
      }



      // Render Collection Menu
      yats_assign($block, array('show_group'  => $menu['show_group'],
                                'show_section'=> $menu['show_section'],
                                'show_section_label' => $menu['show_section_label'],
                                'tab_state'   => $menu['tab_state']));

      $result[msg] = yats_getbuf($block);
      return($result);

   }

}

?>