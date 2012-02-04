<?php

/*
  Show most recommended blogs.
*/

$public_info = new public_info;
$result = $public_info->get_top_recommended_blogs();

if(!$result[err]){

  $block = yats_define($templateBase."news/top_blogs_box.html");

  $top_picks = $result[msg];
    
  yats_assign($block, $top_picks);
  
  echo yats_getbuf($block);
}

?>

