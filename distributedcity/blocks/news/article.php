<?php

$block = yats_define($templateBase . "news/article.html");

// Get Article ID if exists in GET
if ($HTTP_GET_VARS['aid']) {
   $app->session->article_id = $HTTP_GET_VARS['aid'];
}




// Find out what to do
if ($HTTP_GET_VARS[action] == 'new_post') {

   if (!$HTTP_POST_VARS[cancel]) {
      $result = $app->news->process_new_incoming_post($HTTP_POST_VARS);

      // Check the result of the new post
      $post_status = $result[msg];


      // CACHE UPDATE THE FRONT PAGE
      $app->cache->clear("articles_frontpage");
      $app->cache->clear("articles_moderation");
      //  $app->news->html->render_summaries("articles_frontpage", $app->news->get_summaries("frontpage"));
      //  $app->news->html->render_summaries("articles_moderation", $app->news->get_summaries("moderation"), $params);

   }
   else {
      $post_status = "Comment post was cancelled.";
      $mode = 'new_comment_form';
   }

   yats_assign($block, array("post_status" => $post_status));


}
else if ($HTTP_GET_VARS[action] == 'preview') {

   if (!$HTTP_POST_VARS[cancel]) {
      $result = $app->news->preview_new_incoming_post($HTTP_POST_VARS);

      if ($result['err']) {
         $mode = 'new_comment_form';
      }
      else {
         $comment = $result['data'];
         $mode = 'preview';
      }

      // Check the result of the new post
      $post_status = $result[msg];

   }
   else {
      $post_status = "Comment post was cancelled.";
   }

   yats_assign($block, array("post_status" => $post_status));
}
else {
   // Hide new post comment status display since there was no attempt to post anything,
   // there is no status message to display
   yats_hide($block, "post_status", true);
}

$app->session->set_comment_mode($HTTP_POST_VARS[change_mode]);



// Comment?
if ($HTTP_POST_VARS[reply]) {

   // TODO: Validate incoming data
   $new_post[type]            = "A"; // 'A' = this is a reply to an article
   $new_post[parent_id]       = $HTTP_POST_VARS[new_post_parent_id];
   $new_post[subject_default] = $HTTP_POST_VARS[new_post_parent_subject];
   if ( strcasecmp(substr($new_post[subject_default], 0, 3), 're:')) {
      $new_post[subject_default] = 'Re: ' . $new_post[subject_default];
   }

   $mode = "new_comment_form";

}
else if ($HTTP_GET_VARS[reply]) {

   // TODO: Validate incoming data
   $new_post[type]            = "C"; // 'C' = this is a reply to a comment
   $new_post[parent_id]       = $HTTP_GET_VARS[parent];

   // Lookup the subject
   $new_post[subject_default] = $app->news->get_comment_subject($new_post[parent_id]);
   if ( strcasecmp(substr($new_post[subject_default], 0, 3), 're:')) {
      $new_post[subject_default] = 'Re: ' . $new_post[subject_default];
   }

   $mode = "new_comment_form";

}
else if ( $mode == 'continue_session') {
   $mode = "show_article";
}
else {
   $result = $app->news->preview_new_incoming_post($HTTP_POST_VARS);
   $new_post = $result['data'];
   $new_post['subject_default'] = $new_post['subject'];
   $new_post['type'] = $new_post['parent_type'];
}



switch ($mode) {

case 'preview':
   // Show Category / Forum Overview
   $block = yats_define($templateBase."master/preview_compose.html");

   $comment = $result['data'];

   // Prepare For Rendering and Render to html
   $comment_info['date']     = toolbox::make_folder_date( time() );
   $comment_info['subject']  = $app->html->dc_encode($comment['subject'],"subject");
   $comment_info['body']     = $app->html->dc_encode($comment['body'], "message_body");
   $comment_info['user_id']  = $comment['user_id'];
   $comment_info['user_name'] = toolbox::get_username( $comment_info['user_id'] );
   $comment_info['href']     = toolbox::get_user_image_src($comment_info['user_id']);
   $comment_info['body_raw'] = toolbox::html_hidden_field_escape($comment['body']);
   $comment_info['subj_raw'] = toolbox::html_hidden_field_escape($comment['subject']);

   yats_assign($block, array('comment_poster_id'        => $comment_info['user_id'],
                             'comment_poster_username'  => $comment_info['user_name'],
                             'user_image_src'           => $comment_info['href'],
                             'comment_id'               => $comment_info['id'],
                             'comment_subject'          => $comment_info['subject'],
                             'comment_body'             => $comment_info['body'],
                             'comment_date_time'        => $comment_info['date'],
                             'form_action'              => '?action=new_post',
                             'submit_button_text'       => 'Fire Away!' ));

   yats_assign($block, array('hidden_name'        => 'subject',
                             'hidden_value'       => $comment_info['subj_raw']) );
   yats_assign($block, array('hidden_name'        => 'message',
                             'hidden_value'       => $comment_info['body_raw']) );
   yats_assign($block, array('hidden_name'        => 'new_post_parent_type',
                             'hidden_value'       => $comment['parent_type']) );
   yats_assign($block, array('hidden_name'        => 'new_post_parent_id',
                             'hidden_value'       => $comment['parent_id']) );

   break;

case "new_comment_form":

   //   $block = yats_define($templateBase."master/new_comment_or_topic.html");
   $block = yats_define($templateBase."master/general_compose.html");
   $javascript = yats_define($templateBase."master/formatting_javascript.html");
   yats_assign($block, array('formatting_javascript'    => yats_getbuf($javascript)));

   yats_hide($block, "encryption", true);
   yats_hide($block, "title", true);
   yats_hide($block, "from", true);
   yats_hide($block, "to", true);
   yats_hide($block, "author", true);
   yats_hide($block, "crosspost", true);
   yats_hide($block, "forum_topic_prompt", true);
   yats_hide($block, "forum_comment_prompt", true);
   yats_hide($block, "message_page_prompt", true);

   // Assign vars to new comment form
   yats_assign($block, array('form_title_text'   => "Add New Comment",
                             'submit_button_text'=> "Preview this Comment",
                             'current_user'      => $app->user->get_username(),
                             'subject_content'   => toolbox::html_hidden_field_escape($new_post['subject_default']),
                             'message_content'   => toolbox::html_hidden_field_escape($new_post['body']),
                             'form_action'       => '?action=preview',
                             'sid' => session_id()));

   // Assign hidden form vars
   $hidden_vars = array("hidden_name"  => array("new_post_parent_id", "new_post_parent_type"),
   "hidden_value" => array($new_post[parent_id], $new_post[type]));
   yats_assign($block, $hidden_vars);


   break;


case "show_article":

   $article = $app->news->get_article($app->session->article_id);

   // Split the category name up, and get the first word for the icon name	 
   $tmp =  split('[, ]', $article[category_name]);
   $category_icon = $tmp[0];

   yats_assign($block, array('user_image_src'     => toolbox::get_user_image_src($article['poster_id']),
                             'category_icon'      => $category_icon, //$article['topic'],
                             'category_id'        => $article['category_id'],
                             'article_topic_id'   => $article['topic_id'],
                             'article_subject'    => $app->html->dc_encode($article['subject'], 'subject'),
                             'article_poster_username' =>  $article['poster_username'],
                             'article_poster_id'  =>  $article['poster_id'],
                             'article_date_time'  => toolbox::make_date($article['date']),
                             'article_leadin'     => $app->html->dc_encode($article['leadin'], 'message_body'),
                             'article_body'       => $app->html->dc_encode($article['body'], 'message_body'),
                             'article_comments'   => $article['comments'],
                             'article_id'         => $article['id'],
                             'comment_count_text' => toolbox::format_comment_count($article['comment_count']),
                             'sid'                => session_id()));

   if( $article['state'] == '5') {
      yats_hide($block, 'frontPageFeatured', false);
   }


   if ($app->session->comment_mode != "none") {

      // Get comments
      $comments = $app->news->get_comments($app->session->article_id, "A"); // Get comments for parent type A-article  

      if ($comments) {
         if($app->session->comment_mode == 'flat_desc') {
            $app->forums->flatten_comments($comments);
            $app->forums->sort_comments($comments, false);
         }
         if($app->session->comment_mode == 'flat_asc') {
            $app->forums->flatten_comments($comments);
            $app->forums->sort_comments($comments, true);
         }

         // Run through and render all the comments
         $comment_html_block = $app->html->render_comments($comments, $app->session->comment_mode, "A"); // Comment type A=Article
      }

   }
   else {

      // Set fineprint to off
      $hide_fineprint = true;
   }


   if ($hide_fineprint) {
      // hide the fine print notice above comments
      yats_hide($block, "fineprint", true);
   }


   yats_assign($block, array("comment_block" => $comment_html_block));

   yats_assign($block, $app->session->get_comment_mode_selected_state());


   break;

}

echo yats_getbuf($block);

?>