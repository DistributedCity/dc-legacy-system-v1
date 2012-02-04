<?php

$mode = $content[context];

$user_id = $app->user->get_user_id();

if ($HTTP_GET_VARS[uid]) {
   $user_id = substr($HTTP_GET_VARS[uid],0,10);

}
else if ($mode == "blog_admin") {

   $user_id = $app->user->get_user_id();
   $mode = "blog";

}
else {

   unset($user_id);

}

unset($post);
$post[topic_id] = $HTTP_POST_VARS[new_post_topic_id];
$post[subject]  = $HTTP_POST_VARS[new_post_subject];
$post[leadin]   = $HTTP_POST_VARS[message];
$post[body]     = $HTTP_POST_VARS[new_post_body];

// Only allow state ids of: 1:user or 2:submitted
if ($HTTP_POST_VARS[main_page_candidate] == "true") {
   $post[state_id] = "2"; //  2:submitted as a main page candidate 
}
else {
   $post[state_id] = "1"; // 1:user weblog only
}


// Display new post form?
if ($HTTP_GET_VARS[action] == "new_post_form") {

   $mode = "new_post_form";


}
else if ($HTTP_POST_VARS[new_post]) {         // Add a new post?

   // TODO Validate incoming and required data
   $post[user_id]  = $app->user->get_user_id();
#  $post[state_id] = substr($HTTP_POST_VARS[new_post_state_id],0,5);

   $result = $app->news->add_new_blog_post($post);

   if ($result[err]) {

      $error_message = $result[msg];
      $mode = "new_post_form";

   }
   else {

      $ok = $result[msg];

      $user_id = $app->user->get_user_id();  
      $mode = "blog";

   }


}

else if ($HTTP_POST_VARS[preview_post]) {         // preview new post?

   // TODO Validate incoming and required data
   $post[user_id]  = $app->user->get_user_id();

   // Only allow state ids of: 1:user or 2:submitted
   if ($HTTP_POST_VARS[main_page_candidate] == "true") {
      $post[state_id] = "2"; //  2:submitted as a main page candidate 
   }
   else {
      $post[state_id] = "1"; // 1:user weblog only
   }

   $result = $app->news->pre_post_blog($post);

   if ($result[err]) {
      $error_message = $result[msg];
      $mode = "new_post_form";
   }
   else {
      $ok = $result[msg];

      $user_id = $app->user->get_user_id();  
      $mode = "preview";
   }
}
else if( $HTTP_POST_VARS[cancel]) {
   if($HTTP_GET_VARS['action'] == 'new_post') {
      $mode = 'new_post_form';
   }
}






// Get Articles in Context
switch ($mode) {

case "moderation":

   if ($HTTP_POST_VARS[action] == "moderate" && $app->user->is_moderator()) {

      // Get post info to moderate
      $moderation_paramaters[leadin]     = $HTTP_POST_VARS[leadin];
      $moderation_paramaters[topic_id]   = $HTTP_POST_VARS[topic_id];
      $moderation_paramaters[article_id] = substr($HTTP_POST_VARS[article_id],0,100);
      
      // Mod To "FRONT PAGE" or "PASS"?
      if ($HTTP_POST_VARS[moderate_front_page]) {
         $moderation_paramaters[state_id]   = 5; // 5 is the id code for front page status
      }
      else if ($HTTP_POST_VARS[moderate_pass]) {
         $moderation_paramaters[state_id]   = 3; // 3 is the id code for "passing"
      }


      $result = $app->news->moderate_article($moderation_paramaters);
      
      if ($result[err]) {
         $params[error] = $result[msg];
      }
      else {
         $params[ok] = $moderation_paramaters[state_id] == 5 ? "The item was successfully moderated: Front Page" : "The item was successfully moderated: Passed Over"; 
         $result[msg];
      }

      echo $result['msg'];

      // Update Cache for Frontpage and Moderation
      $app->cache->clear("articles_frontpage");
      $app->cache->clear("articles_moderation");
      $app->cache->clear("older_stuff_box_frontpage");
      $app->cache->clear("older_stuff_box_topic_".$moderation_parameters[topic_id]);
   }


   // If the data is not in the cache, render it
   if (!$app->cache->exists("articles_moderation") || $GLOBALS[config][cache] == "off" || $app->session->cache_reset[articles_moderation]) {
      $app->news->html->render_summaries("articles_moderation", $app->news->get_summaries("moderation"), $params);
   }

   echo $app->cache->get("articles_moderation");


   break;


case 'frontpage':

   // Get Older news subjects
   if ($HTTP_GET_VARS[action] == 'older_articles_list') {

      // If the data is not in the cache, render it
      if (!$app->cache->exists('older_articles_list_frontpage') || $GLOBALS[config][cache] == 'off')
         $app->news->html->render_older_articles_list('frontpage', $app->news->get_all_older_article_subjects('frontpage'));

      echo $app->cache->get('older_articles_list_frontpage');
   }
   else {

      // If the data is not in the cache, render it
      if (!$app->cache->exists('articles_frontpage') || $GLOBALS[config][cache] == 'off')
         $app->news->html->render_summaries('articles_frontpage', $app->news->get_summaries('frontpage'));

      echo $app->cache->get('articles_frontpage');

   }

   break;


case "section":
   // Show Section Articles
   if ($HTTP_GET_VARS[section_id]) {

      // TODO: Validate section_id
      $section_id = $HTTP_GET_VARS[section_id];
      $articles = $app->news->get_summaries("section", $section_id);
      $mode = "articles";

   }
   else {
      // Show Section Directory
      $mode = "section_directory";
   }
   break;


case "category":

   // Show Section Articles
   if ($HTTP_GET_VARS[category_id]) {

      // TODO: Validate section_id
      $topic_id = $HTTP_GET_VARS[category_id];


      if ($HTTP_GET_VARS[action] == "older_articles_list") {

         // If the data is not in the cache, render it
         //if(!$app->cache->exists("older_articles_list_topic_".$topic_id) || $GLOBALS[config][cache] == "off")
         $app->news->html->render_older_articles_list("category", $app->news->get_all_older_article_subjects("category", $category_id), array("category_id" =>$category_id));

         echo $app->cache->get("older_articles_list_topic_".$category_id);

      }
      else {

         //    toolbox::dprint($app->news->get_summaries("category", $category_id));
         // If the data is not in the cache, render it
         //if(!$app->cache->exists("articles_topic_".$topic_id) || $GLOBALS[config][cache] == "off")
         $app->news->html->render_summaries("articles_category", $app->news->get_summaries("category", $category_id), array("category_id"=>$category_id));

         echo $app->cache->get("articles_category_".$category_id);
      }


   }
   else {
      // Show Topic Directory
      $mode = "category_directory";
   }
   break;


case "weblog":
case "blog":

   // Show Users Blog
   if ($user_id) {

      // TODO: Validate uid
      //$user_id = substr($HTTP_GET_VARS[uid],0,10);

      // Get Older news subjects?
      //     if($HTTP_GET_VARS[mode] == "older_articles_list"){
      //       $result = $app->news->get_all_older_article_subjects("blog", $user_id);
      //       $mode = "older_articles_list";
      //     }else{
      //       $articles = $app->news->get_summaries("blog", $user_id);
      //       $mode = "articles";
      //     }

      if ($HTTP_GET_VARS[action] == 'older_articles_list') {

         // If the data is not in the cache, render it
         if (!$app->cache->exists('ss'.$topic_id) || $GLOBALS[config][cache] == 'off')
            //$app->news->get_all_older_article_subjects("weblog", $user_id);
            $app->news->html->render_older_articles_list('weblog', $app->news->get_all_older_article_subjects('weblog', $user_id), array('user_id' =>$user_id));

         echo $app->cache->get('older_articles_list_weblog_'.$user_id);

      }
      else {

         // If the data is not in the cache, render it
         if (!$app->cache->exists('articles_blog_'.$user_id) || $GLOBALS[config][cache] == 'off') {

            // Is blog admin ?
            $blog_admin = $user_id == $app->user->get_user_id() ? 1 : 0;

            $app->news->html->render_summaries('articles_blog', 
                                               $app->news->get_summaries('blog', $user_id),
                                               array('user_id'=>$user_id, 
                                                     'blog_admin' => $blog_admin));
         }

         echo $app->cache->get('articles_blog_'.$user_id);
      }



   }
   else {

      // Show Blog Directory
      $mode = 'blog_directory';
   }

   break;


case 'preview':

   echo $app->news->html->render_preview( $post );

   break;

}



switch ($mode) {

case "blog_directory":

   $block = yats_define($templateBase . "news/blogger_directory.html");

   $result = toolbox::get_users("BLOGGERS");
   if (!$result[err]) {
      $users = $result[msg];
   }

   if (count($users)) {
      yats_hide($block, "no_user_weblogs", true);
      yats_assign($block, array("user_listing_block" => $app->html->render_user_directory_rows($users, "bloggers")));
   }
   else {
      yats_hide($block, "user_directory_items", true);
   }
   echo yats_getbuf($block);
   break;


case "category_directory":
   $block = yats_define($templateBase . "news/categories_directory.html");

   $categories = $app->news->get_categories();
   foreach($categories as $category) {
      $c_info[category_id][] = $category[id];
      $c_info[category_name][] = $category[name];
      $c_info[category_icon][] = $category[icon];
      $c_info[category_description][] = $category[description] ? $category[description] : "(none)"; 
   }

   yats_assign($block, $c_info);
   echo yats_getbuf($block);
   break;



case "new_post_form":
   $block = yats_define($templateBase . "news/new_post_form.html");

   $javascript = yats_define($templateBase."master/formatting_javascript.html");
   yats_assign($block, array('formatting_javascript'    => yats_getbuf($javascript)));

   // Errors?
   if ($error_message)
      yats_assign($block, array("error_message" => $error_message));

   // If there was a post error, show the message and fill in the form with the
   // values from the previous submission attempt.
   yats_assign($block, 
               array("new_post_subject" => $post[subject] ? $post[subject] : "",
                     "new_post_leadin"  => $post[leadin]  ? $post[leadin] : "",
                     "new_post_body"    => $post[body]    ? $post[body] : ""));

   // Make the main page candidate button show the previous
   // selected state if this is a redisplay of a post attempt
   if ($post[state_id] == "2") {
      $checked = "CHECKED";
   }
   else {
      $checked = " ";
   }     
   yats_assign($block, array("main_page_candidate_checked_flag" => $checked));


   $result = $app->forums->get_cross_post_options();
   $cross_post_options = $result[msg];
   // Parse result for yats assignment
   foreach($cross_post_options as $category => $forums) {
      foreach($forums as $forum_id => $forum_name) {
         $cross_post_parent_id[] = (string)$forum_id;
         $cross_post_label[] = $category.": ". $forum_name;
         $selected[] = $forum_id == $post['topic_id'] ? 'selected' : '';
      }
   }

   yats_assign($block, 
               array("topic_id"    => $cross_post_parent_id,
                     "topic_label" => $cross_post_label,
                     'topic_selected_state' => $selected));

   yats_assign($block, 
               array("username" => $app->user->get_username(),
                     "sid" => session_id()));
   echo yats_getbuf($block);
   break;

}



?>
