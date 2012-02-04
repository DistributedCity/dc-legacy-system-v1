<?php

// Show Box in Context
switch($content[context]){

  // Frontpage
 case "frontpage":

   if(!$app->cache->exists("older_stuff_box_frontpage") || $GLOBALS[config][cache] == "off"){
     $parameters[past_articles_url] = "/news/?action=older_articles_list";
     $parameters[box_label] = ucfirst($content[context]);
     $app->news->html->render_older_stuff_box("frontpage", $app->news->get_recent_older_article_subjects("frontpage"), $parameters);
   }

   echo $app->cache->get("older_stuff_box_frontpage");
   break;


  // Topic
 case "topic":

   // Get the topic id
   $topic_id = substr($HTTP_GET_VARS[topic_id],0,10);

   if($topic_id){
     if(!$app->cache->exists("older_stuff_box_topic_".$topic_id) || $GLOBALS[config][cache] == "off"){
       $parameters[past_articles_url] = "/news/topic/?action=older_articles_list&topic_id=".$topic_id;
       $parameters[box_label] = toolbox::get_topic_name($topic_id);
       $app->news->html->render_older_stuff_box("topic_".$topic_id, $app->news->get_recent_older_article_subjects("topic", $topic_id), $parameters);
     }
   }

   echo $app->cache->get("older_stuff_box_topic_".$topic_id);
   break;





  // Weblog
 case "weblog":

   // Get the weblog id
   $uid = substr($HTTP_GET_VARS[uid],0,10);

     if($uid){
       if(!$app->cache->exists("older_stuff_box_weblog_".$uid) || $GLOBALS[config][cache] == "off"){
         $parameters[past_articles_url] = "/news/weblogs/?action=older_articles_list&uid=".$uid;
         $parameters[box_label] = toolbox::get_username($uid);
         $app->news->html->render_older_stuff_box("weblog_".$uid, $app->news->get_recent_older_article_subjects("weblog", $uid), $parameters);
       }
     }

     echo $app->cache->get("older_stuff_box_weblog_".$uid);
   break;

}


?>