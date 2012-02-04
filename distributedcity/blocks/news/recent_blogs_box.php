<?php

/*
  Show recent updated.
*/

$result = $app->news->get_recent_updated_blogs();

if(!$result[err]){

  $recent_bloggers = $result[msg];

  $block = yats_define($templateBase."news/recent_blogs_box.html");

  foreach($recent_bloggers as $blogger){
    $data[username][] = $blogger[username];
    $data['user_id'][] = $blogger[user_id];


    // Max 30 chars for sidebox subject
    if(strlen($blogger[subject]) >40){
      $blogger[subject] = substr($blogger[subject],0,40) . "...";
    }


    $data[subject][] = $app->html->dc_encode($blogger[subject], 'subject');


    $data[date][] = toolbox::make_short_date($blogger[date]);
  }

  yats_assign($block, $data);
  
  echo yats_getbuf($block);
}

?>

