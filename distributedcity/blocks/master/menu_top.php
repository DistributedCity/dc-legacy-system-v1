<?php

// Menu Top 
$block = yats_define($templateBase."master/menu_top.html");

// Hide inactive menus
$sections = array("News", "Forums", "Weblogs", "Messaging", "Chat", "Services", "Search", "Settings", "Help");
foreach($sections as $section){
  if($menu_section != $section){
    yats_hide($block, "tabMenu".$section, true);
  }
}
echo yats_getbuf($block);
?>