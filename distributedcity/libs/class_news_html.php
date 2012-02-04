<?php

class news_html {


   function render_summaries($key, $articles, $parameters='') {

      $cache_key = $key;

      $block_main = yats_define($GLOBALS[templateBase] . 'news/summaries.html');

      // If not blog admin context, hide the blog admin menu
      if (!$parameters[blog_admin]) {
         yats_hide($block_main, 'blog_admin_menu', true);
      }

      // See if there are any articles
      if ($articles[err]) {
         // Assign error message
         $parameters[ok] = $articles[msg];
      }


      if ($key == 'articles_blog') {
         yats_assign($block_main, array('user_image_src' => toolbox::get_user_image_src($parameters[user_id]),
                                        'blog_username' => toolbox::get_username($parameters[user_id]),
                                        'blog_user_id'  => $parameters[user_id]));
         $cache_key = $key.'_'.$parameters[user_id];
      }


      if ($key == 'articles_frontpage') {
         yats_assign($block_main, array('header_frontpage_name' => 'Frontpage'));
      }


      if ($key == 'articles_topic') {

         yats_assign($block_main, array('header_topic_id'   => $parameters[topic_id],
                                        'header_topic_name' => toolbox::get_topic_name($parameters[topic_id])));
         $cache_key = $key.'_'.$parameters[topic_id];
      }


      if ($key == 'articles_category') {
         $c_info = toolbox::get_category_info($parameters[category_id]);

         yats_assign($block_main, array('header_category_id'   => $c_info[id],
                                        'header_category_name' => $c_info[name],
                                        'header_category_icon' => $c_info[icon],
                                        'header_category_description' => $c_info[description]));

         $cache_key = $key.'_'.$parameters[category_id];
      }

      if (!$articles[err]) {

         $articles = $articles[msg];

         reset($articles);
         $summaries_content = '';
         foreach($articles as $article) {

            // If not moderation context, hide the moderation menu
            if ($key == 'articles_moderation') {
               $block = yats_define($GLOBALS[templateBase] . 'news/summaries_moderation.html');
            }
            else {
               $block = yats_define($GLOBALS[templateBase] . 'news/summaries_standard.html');

               if( $article['state'] == '5' && $key == 'articles_blog') {
                  yats_hide($block, 'frontPageFeatured', false);
               }
            }


            switch ($article[state]) {
            case '1':
               $state_color = '#FF3333'; // USER
               break;

            case '2':
               $state_color = '#333333'; // SUBMITTED
               break;

            case '3':
               $state_color = '#006666'; // PASSED OVER
               break;

            case '4':
               $state_color = '#006666'; // SECTION
               break;

            case '5':
               $state_color = '#006666'; // FRONT PAGE
               break;
            }

            yats_assign($block, array('user_image_src' => toolbox::get_user_image_src($article[poster_id]),
                                      'state_color'    => $state_color, 
                                      'category_icon'  => $article[category_icon],
                                      'category_id'    => $article[category_id],
                                      'category_name'  => $article[category_name],
                                      'topic_name'     => $article[topic_name],
                                      'subject'        => $GLOBALS[app]->html->dc_encode($article[subject], 'subject'),
                                      'poster_username'=> $article[poster_username],
                                      'poster_id'      => $article[poster_id],
                                      'date_time'      => toolbox::make_date($article[date]),
                                      'leadin'         => $key == 'articles_moderation' ? $article[leadin] : $GLOBALS[app]->html->dc_encode($article[leadin], 'message_body'),
                                      'body'           => $GLOBALS[app]->html->dc_encode($article[body], 'message_body'),
                                      'comment_count_text' => toolbox::format_comment_count($article[comment_count]), 
                                      'id'             => $article[id],
                                      'sid'            => session_id()));

            $summaries_content .= trim(yats_getbuf($block));
         }
         yats_assign($block_main, array('summaries_content' => $summaries_content));
         $html = yats_getbuf($block_main);
      }
      else {
         // No articles, hide the block
         $html = '';

      }

      $GLOBALS[app]->cache->put($cache_key, $html);
   }


   function render_preview( $article) {

      $block = yats_define($GLOBALS[templateBase] . "news/preview_post.html");

      yats_assign($block, 
                  array("user_image_src" => toolbox::get_user_image_src($article[user_id]),
                        "topic_name"     => toolbox::get_topic_name( $article[topic_id] ),
                        "subject"        => $GLOBALS[app]->html->dc_encode($article[subject], "subject"),
                        "poster_username"=> toolbox::get_username( $article[user_id] ),
                        "poster_id"      => $article[user_id],
                        "date_time"      => toolbox::make_date( time() ),
                        "leadin"         => $GLOBALS[app]->html->dc_encode($article[leadin], "message_body"),
                        "body"           => $GLOBALS[app]->html->dc_encode($article[body], "message_body") ));

      $hidden_vars = array('new_post_topic_id' => toolbox::html_hidden_field_escape($article['topic_id']),
                           'new_post_subject' => toolbox::html_hidden_field_escape($article['subject']),
                           'message' => toolbox::html_hidden_field_escape($article['leadin']),
                           'new_post_body' => toolbox::html_hidden_field_escape($article['body']),
                           'main_page_candidate' => $article['state_id'] == '2' ? 'true' : '' );

      foreach($hidden_vars as $key => $val) {
         yats_assign($block, array('hidden_name' => $key,
                                   'hidden_value' => $val));
      }

      $preview = yats_getbuf($block);

      yats_assign($block_main, array('blog_preview' => $preview));
      $html = yats_getbuf($block);

      return $html;
   }

   function render_older_stuff_box($key, $articles_data, $parameters="") {

      $block = yats_define($GLOBALS[templateBase]."news/older_stuff_box.html");

      if (!$articles_data[err]) {
         $articles = $articles_data[msg];

         if( count( $articles ) ) {

            yats_hide($block, 'older_articles_none_row', true);

            foreach($articles as $article) {
               $ids[]      = $article[id];

               // Max 30 chars for sidebox subject
               if (strlen($article[subject]) >40) {
                  $article[subject] = substr($article[subject],0,40) . "...";
               }

               $subjects[] = $article[comment_count]  ? $GLOBALS[app]->html->dc_encode($article[subject]) . "(". $article[comment_count] .")" : $GLOBALS[app]->html->dc_encode($article[subject]);

            }

            yats_assign($block, array("article_id"        => $ids,
            "article_subject"   => $subjects));

            yats_assign($block, array("box_label"         => $parameters[box_label],
            "past_articles_url" => $parameters[past_articles_url]));
         }
      }
      else {
         yats_hide($block, "articles", true);
         yats_hide($block, "older_articles_link_row", true);
         yats_assign($block, array("status_message" => "No older articles found."));
      }

      $GLOBALS[app]->cache->put("older_stuff_box_".$key, yats_getbuf($block));

   }




   function render_older_articles_list($key, $articles_data, $parameters="") {

      $block = yats_define($GLOBALS[templateBase] . "news/older_articles_list.html");

      if (!$articles_data[err]) {

         $articles = $articles_data[msg];
         foreach($articles as $article) {
            $article_data[ids][]           = $article[id];
            $article_data[subjects][]      = $GLOBALS[app]->html->dc_encode($article[subject]);
            $article_data[dates][]         = toolbox::make_date($article[date]);
            $article_data[comment_count][] = (string)$article[comment_count];
         }

         yats_assign($block, array("article_id"      => $article_data[ids],
         "article_subject" => $article_data[subjects],
         "article_date"    => $article_data[dates],
         "article_posts"   => $article_data[comment_count]));

         // Assign header information
         switch ($key) {
         case "frontpage":
            yats_assign($block, array("header_frontpage_name" => "Frontpage"));
            break;


         case "weblog":
            $key = $key ."_".$parameters[user_id];
            yats_assign($block, array("header_weblog_username" => toolbox::get_username($parameters[user_id]),
            "header_weblog_user_id"  => $parameters[user_id],
            "user_image_src"         => toolbox::get_user_image_src($parameters[user_id])));
            break;


         case "topic":
            yats_assign($block, array("header_topic_name" => toolbox::get_topic_name($parameters[topic_id])));
            $key = $key ."_".$parameters[topic_id];
            break;

         case "section":
            break;
         }
         yats_hide($block, "articles_none_row", true);

      }
      else {
         yats_assign($block, array("error_message"      => "No older articles found."));
      }

      $GLOBALS[app]->cache->put("older_articles_list_".$key, yats_getbuf($block));
   }

}

?>