<?php

/* WOW!  This is convoluted and nasty, and BEGGING for a re-write */


/*
  Main Forum Page
*/

function getVarFromAnywhere($app, $key) {
   if($_GET[$key]) {
      return $_GET[$key];
   }
   if($_POST[$key]) {
      return $_POST[$key];
   }
   return $app->session->$key;
}

$global_topic_id = getVarFromAnywhere($app, 'topic_id');
$global_forum_id = getVarFromAnywhere($app, 'forum_id');
$global_category_id = getVarFromAnywhere($app, 'category_id');
$global_action = getVarFromAnywhere($app, 'action');

$post_info['topic_id'] = $global_topic_id;
$post_info['forum_id'] = $global_forum_id;

// Get Topic ID if exists in GET
if ($HTTP_GET_VARS['topic_id']) {
   $app->session->topic_id = $HTTP_GET_VARS['topic_id'];
}

// Get Forum ID if exists in GET
if ($HTTP_GET_VARS['forum_id']) {
   $app->session->forum_id = $HTTP_GET_VARS['forum_id'];
}


// Get Category ID if exists in GET
if ($HTTP_GET_VARS['category_id']) {
   $app->session->category_id = $HTTP_GET_VARS['category_id'];
}

// Get Action if it exists
if ($HTTP_GET_VARS['action']) {
   $app->session->action = $HTTP_GET_VARS['action'];
}

// Cancel out of a new topic add
if ($app->session->action == "new_topic" && $HTTP_POST_VARS['cancel_new_post']) {
   $app->session->action = "forum";
}





// Add a new comment/topic post before we continue?
if ($HTTP_GET_VARS[action] == 'new_post') {

   // FIXME: do better validation
   if ($HTTP_POST_VARS[cancel]) {
      $status_message = "New post cancelled.";

      // Reset the current action
      if ($HTTP_POST_VARS[new_post_parent_type] == "F") {
         $app->session->action = "topic";
         $mode = "new_topic_form";

      }
      else if ($HTTP_POST_VARS[new_post_parent_type] == "C") {
         $app->session->action = "forum";
         $mode = "new_comment_form";
      }


   }
   else {
      if (!$error) {

         $result = $app->forums->process_new_incoming_post($HTTP_POST_VARS);

         if ($result[err]) {

            // Check the result of the new post
            $new_post[error_message] = $result[msg];
            $new_post[parent_id]       = $HTTP_POST_VARS[new_post_parent_id];   

            // Reset the current action
            if ($HTTP_POST_VARS[new_post_parent_type] == "F") {
               $new_post[type]            = "F"; // 'F' = this is a new topic post to a forum
               $mode = "new_topic_form";
            }
            else if ($HTTP_POST_VARS[new_post_parent_type] == "C") {
               // TODO: Validate incoming data
               $new_post[type]            = "C"; // 'C' = this is a reply to a topic/comment
               $mode = "new_comment_form";
            }
         }
         else {

            // Reset the current action
            if ($HTTP_POST_VARS[new_post_parent_type] == "C") {
               $app->session->action = "topic";
               $mode = "topic";

            }
            else if ($HTTP_POST_VARS[new_post_parent_type] == "F") {
               $app->session->action = "forum";
               $mode = "forum";
            }


         }   
      }
   }
}

// preview a post?
else if ($HTTP_GET_VARS[action] == 'preview_post') {

   if ($HTTP_POST_VARS[cancel]) {
      $status_message = "New post cancelled.";

      // Reset the current action
      if ($HTTP_POST_VARS[new_post_parent_type] == "C") {
         $app->session->action = "topic";
         $mode = "topic";

      }
      else if ($HTTP_POST_VARS[new_post_parent_type] == "F") {
         $app->session->action = "forum";
         $mode = "forum";
      }
   }

   else if (!$error) {
      $result = $app->forums->preview_new_incoming_post($HTTP_POST_VARS);

      if (!$result || $result[err]) {
         // Check the result of the new post
         $new_post[error_message] = $result[msg];
         $new_post[parent_id]       = $HTTP_POST_VARS[new_post_parent_id];

         // Reset the current action
         if ($HTTP_POST_VARS[new_post_parent_type] == "F") {
            $new_post[type]            = "F"; // 'F' = this is a new topic post to a forum
            $mode = "new_topic_form";
         }
         else if ($HTTP_POST_VARS[new_post_parent_type] == "C") {
            // TODO: Validate incoming data
            $new_post[type]            = "C"; // 'C' = this is a reply to a topic/comment
            $mode = "new_comment_form";
         }
      }
      else {
         $mode = 'preview';
      }
   }
}


else if ($HTTP_POST_VARS[reply] || $HTTP_GET_VARS[reply]) { // Comment?

   // TODO: Validate incoming data
   $new_post[type]            = "C"; // 'C' = this is a reply to a topic/comment

   if ($HTTP_GET_VARS[new_post_parent_id]) {
      $new_post[parent_id]       = $HTTP_GET_VARS[new_post_parent_id];
   }
   else {
      $new_post[parent_id]       = $HTTP_POST_VARS[new_post_parent_id];
   }

   $new_post[subject_default] = ''; // Chan$HTTP_POST_VARS[new_post_parent_subject];
   $new_post[regarding] = $HTTP_POST_VARS[new_post_regarding];

   $mode = "new_comment_form";


}
else if ($global_action == "new_topic" ) { // New Topic?

   // TODO: Validate incoming data
   $new_topic[type]            = "F"; // 'F' = this is a new topic post to a forum

   if ($HTTP_GET_VARS[parent_id]) {
      $new_post[parent_id]       = $HTTP_GET_VARS[parent_id];
   }
   else {
      $new_post[parent_id]       = $HTTP_POST_VARS[parent_id];
   }

   // No default subject, as this is a new topic post

   $mode = "new_topic_form";


}
else if ($global_action == "recent_overview") {
   $mode = "recent_overview";
}
else if ($global_action == "global_overview") {
   $mode = "global_overview";
}
else if ($global_action == "category_overview") {
   $mode = "category_overview";
}
else if ($global_action == "forum") {
   $mode = "forum";
}
else if ($global_action == "topic") {
   $mode = "topic";
}
else if ($global_action == "new_topic_add") {
   $mode = "new_topic_add";
}
else {
   // Default to Category Overview if no implicit action requested
   $mode = "global_overview";
}



$app->session->set_comment_mode($HTTP_POST_VARS[change_mode]);


switch ($mode) {

case 'recent_overview':

   // Recent replies to my comments
   $block = yats_define($templateBase."forums/recent_comments.html");

   // Get Forum threads Top Level Summary
   $result = $app->forums->get_global_recent_self_replies();

   if (!$result[err]) {
      $threads = $result[msg];

      foreach($threads as $thread) {
         yats_assign($block, array('replies_thread_id'      => $thread['id'],
                                   'replies_thread_subject' => $thread[subject] ? $app->html->dc_encode($thread[subject],'subject') : 'No subject.',
                                   'replies_thread_category' => $thread['cat_name'],
                                   'replies_thread_date'    => toolbox::make_age($thread[date]),
                                   'replies_thread_poster_username'  => $thread['poster_username'],
                                   'replies_thread_poster_user_id'   => $thread['poster_id']));
      }
   }
   else {
      yats_assign($block, array('replies_status_message'  => 'No replies found.'));
   }

   // Recently started Forum threads (threads)

   
   unset( $thread_info );

   // Get Forum threads Top Level Summary
   $result = $app->forums->get_global_recent_threads();

   if (!$result[err]) {
      $threads = $result[msg];

      foreach($threads as $thread) {
         yats_assign($block, array('threads_thread_id'      => $thread['id'],
                                   'threads_thread_subject' => $thread['subject'] ? $app->html->dc_encode($thread['subject'],'subject') : 'No subject.',
                                   'threads_thread_category' => $thread['cat_name'],
                                   'threads_thread_date'    => toolbox::make_age( $thread['date'] ),
                                   'threads_thread_poster_username'  => $thread['poster_username'],
                                   'threads_thread_poster_user_id'   => $thread['poster_id']));
      }
   }
   else {
      yats_assign($block, array('threads_status_message'  => 'No threads found.'));
   }


   unset( $thread_info );

   // Get Forum threads Top Level Summary
   $result = $app->forums->get_global_recent_comments();

   if (!$result[err]) {
      $threads = $result[msg];

      foreach($threads as $thread) {
         yats_assign($block, array('comments_thread_id'      => $thread['id'],
                                   'comments_thread_subject' => $thread[subject] ? $app->html->dc_encode($thread[subject],"subject") : "No subject.",
                                   'comments_thread_category' => $thread['cat_name'],
                                   'comments_thread_date'    => toolbox::make_age($thread[date]),
                                   'comments_thread_poster_username'  => $thread[poster_username],
                                   'comments_thread_poster_user_id'   => $thread[poster_id] ));
      }
   }
   else {
      yats_assign($block, array('comments_status_message'  => 'No comments found.'));
   }


   echo yats_getbuf($block);  

   break;


case "global_overview":

   // Show Category / Forum Overview
   $block = yats_define($templateBase."forums/categories.html");

   $result = $app->forums->get_categories_info();
   $categories = $result[msg];

   // Prepare For Rendering and Render to html
   foreach($categories as $category) {



      $category_info[id]          = $category[id];
      $category_info[name]        = $category[name];
      $category_info[description] = $category[description];

      $block2 = yats_define($templateBase."forums/category.html");

      yats_assign($block2, array('category_id'          => $category_info[id],
                                 'category_name'        => $category_info[name],
                                 'category_description' => $category_info[description],
                                 'category_icon'        => toolbox::make_category_icon_name( $category_info[name]),
      ));

      // Do Any Forums of this category
      if ($category[forums]) {
         foreach($category[forums] as $forum) {
            $category_info[forum_id][]          = $forum[id];
            $category_info[forum_name][]        = $forum[name];
            $category_info[forum_description][] = $forum[description] ? $forum[description] : "&nbsp;";
            $category_info[forum_topic_count][] = $forum[topic_count] ? $forum[topic_count] : "-";
            $category_info[forum_post_count][]  = $forum[post_count] ? $forum[post_count] : "-";

         }

         yats_assign($block2, array('forum_id'          => $category_info[forum_id],
                                    'forum_name'        => $category_info[forum_name],
                                    'forum_description' => $category_info[forum_description],
                                    'forum_topic_count' => $category_info[forum_topic_count],
                                    'forum_post_count' => $category_info[forum_post_count]));
      }
      else {

         //yats_assign($block2, array("status_message"  => "---"));

      }

      $sub_block_html .= yats_getbuf($block2);
      unset($category_info);
   }

   yats_assign($block, array("category_forum_block" => $sub_block_html));

   echo yats_getbuf($block);
   break;



case "category_overview":

   // Show Category / Forum Overview
   $block = yats_define($templateBase."forums/category.html");

   // Render location bar
   $app->forums->html->render_location_bar($block, "category", $app->forums->get_category_info($global_category_id));

   $result = $app->forums->get_categories_info($global_category_id);

   if (!$result[err]) {
      $category = $result[msg];

      // Prepare For Rendering and Render to html
      $category_info[id]          = $category[id];
      $category_info[name]        = $category[name];
      $category_info[description] = $category[description];
      $category_info['category_icon'] = toolbox::make_category_icon_name( $category['name']);

      yats_assign($block, 
                  array('category_id'          => $category_info[id],
                        'category_name'        => $category_info[name],
                        'category_description' => $category_info[description],
                        'category_icon'        => toolbox::make_category_icon_name( $category_info[name])));

      yats_hide($block, 'cat_name', true);

      // Do Any Forums of this category
      if ($category[forums]) {
         foreach($category[forums] as $forum) {
            $category_info[forum_id][]          = $forum[id];
            $category_info[forum_name][]        = $forum[name];
            $category_info[forum_description][] = $forum[description] ? $forum[description] : '---';
            $category_info[forum_topic_count][] = $forum[topic_count] ? $forum[topic_count] : '-';
            $category_info[forum_post_count][] = $forum[post_count] ? $forum[post_count] : '-';
         }

         yats_assign($block, array('forum_id'          => $category_info[forum_id],
                                   'forum_name'        => $category_info[forum_name],
                                   'forum_description' => $category_info[forum_description],
                                   'forum_post_count'  => $category_info[forum_post_count],
                                   'forum_topic_count' => $category_info[forum_topic_count]));
      }
      else {
         yats_assign($block, array('status_message'  => 'No forums.'));
      }

   }
   else {
      // Some type of error getting category overview
   }

   echo yats_getbuf($block);  

   break;



case 'forum':

   // Show Category / Forum Overview
   $block = yats_define($templateBase.'forums/forum.html');

   // Render location bar
   $loc = $app->forums->get_forum_location( $global_forum_id );
   $app->forums->html->render_location_bar($block, 'forum', $loc);

   yats_assign($block, array('forum_id' => $global_forum_id,
   'sid' => session_id()));

   // Get Forum Topics Top Level Summary
   $result = $app->forums->get_topics($global_forum_id);



   if (!$result[err]) {
      $topics = $result[msg];

      foreach($topics as $topic) {

         // Prepare For Rendering and Render to html
         $topic_info[id][]       = $topic[id];
         $topic_info[date][]     = toolbox::make_date($topic[date]);
         $topic_info[subject][]  = $topic[subject] ? $app->html->dc_encode($topic[subject],"subject") : "No subject.";
         $topic_info[replies][]  = $topic[replies] ? (string)$topic[replies] : "-";
         $topic_info[username][] = $topic[poster_username];
         $topic_info[user_id][]  = $topic[poster_id];
         $topic_info[views][]    = $topic[view_count];
      }

      yats_assign($block, array('topic_id'      => $topic_info[id],
                                'topic_subject' => $topic_info[subject],
                                'topic_replies' => $topic_info[replies],
                                'topic_date'    => $topic_info[date],
                                'topic_views'   => $topic_info[views],
                                'topic_poster_username'  => $topic_info[username],
                                'topic_poster_user_id'   => $topic_info[user_id]));
   }
   else {
      yats_assign($block, array("status_message"  => "No threads found. Start a new one if you like."));
   }

   yats_assign($block, array('hidden_name'        => 'topic_id',
                             'hidden_value'       => $global_topic_id) );
   yats_assign($block, array('hidden_name'        => 'forum_id',
                             'hidden_value'       => $global_forum_id) );


   echo yats_getbuf($block);  

   break;


case 'topic':

   $block = yats_define($templateBase.'forums/topic.html');

   // Render location bar
   $loc = $app->forums->get_topic_location($global_topic_id );

   $app->forums->html->render_location_bar($block, 'thread',  $loc);

   // Assign any new post status if it exists
   if ($post_status) {
      yats_assign($block, array('post_status' => $post_status));
   }


   // Get Forum Topics Top Level Summary
   // See if this topic_id has been read this session?
   if ($app->session->topics_read) {
      if (in_array($global_topic_id, $app->session->topics_read)) {
         $increment_view_count = 'no';
      }
      else {
         $increment_view_counter = 'yes';
      }
   }
   else {
      $increment_view_counter = 'yes';
   }

   if ($increment_view_counter == 'yes') {
      $app->session->topics_read[] = $global_topic_id;
   }


                                            
   $result = $app->forums->get_topic($global_topic_id, $increment_view_counter);

   if (!$result[err]) {
      $topic = $result[msg];

      // FIX ME CLEAN THIS UP! 2 assignments are not necessary here

      // Prepare For Rendering and Render to html
      $topic_info[id]       = $topic[id];
      //     $topic_info[icon][]     = $topic[icon] ? $app->html->dc_encode($topic[icon]) : "&nbsp;";
      $topic_info[date]     = toolbox::make_folder_date($topic[date]);
      $topic_info[subject]  = $app->html->dc_encode($topic[subject],"subject");
      $topic_info[body]     = $app->html->dc_encode($topic[body], "message_body");
      $topic_info[replies]  = (string)$topic[replies];
      $topic_info[username] = $topic[poster_username];
      $topic_info[user_id]  = $topic[poster_user_id];
      $topic_info[parent_id]  = $topic[parent_id];
      $topic_info[parent_type]  = $topic[parent_type];
      $topic_info[user_image_src] = toolbox::get_user_image_src($topic[poster_user_id]);
      $topic_info[comment_count] = $topic[comment_count];
      $topic_info[view_count] = $topic[view_count];
   }

   // Render location topic subject
   $location_topic_subject = $topic_info[subject];
   $location_topic_subject = strlen($location_topic_subject) > 32 ? substr($location_topic_subject,0,32)."..." : $location_topic_subject;

   //"location_topic_subject" => $location_topic_subject,

   $parent_type = trim($topic_info['parent_type']);
   if( $parent_type  == 'C') {
      $parent_href = "/forums/?action=topic&topic_id={$topic_info['parent_id']}";
   }
   else if( $parent_type == 'F') {
      $parent_href = "/forums/?action=forum&forum_id={$topic_info['parent_id']}";
   }
   else if( $parent_type == 'A') {
      $parent_href = "/news/weblogs/article/?aid={$topic_info['parent_id']}";
   }


   yats_assign($block, array('user_image_src'         => $topic_info['user_image_src'],
                             'topic_id'               => $topic_info['id'],
                             'topic_parent_id'        => $topic_info['parent_id'],
                             'topic_parent_href'      => $parent_href,
                             'topic_subject'          => $topic_info['subject'],
                             'topic_body'             => $topic_info['body'],
                             'topic_replies'          => $topic_info['replies'],
                             'topic_date_time'        => $topic_info['date'],
                             'topic_poster_username'  => $topic_info['username'],
                             'topic_poster_user_id'   => $topic_info['user_id'],
                             'topic_view_count'       => $topic_info['view_count'],         
                             'sid'                    => session_id(),
                             'comment_count_text'     => toolbox::format_comment_count($topic_info['comment_count'])));

   yats_assign($block, array('hidden_name'        => 'topic_id',
                             'hidden_value'       => $global_topic_id) );
   yats_assign($block, array('hidden_name'        => 'forum_id',
                             'hidden_value'       => $global_forum_id) );


   if ($app->session->comment_mode != "none") {
      // Get comments
      $comments = $app->forums->get_comments($global_topic_id, "C"); // Get comments for parent type A-article  
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
         $comment_html_block = $app->html->render_comments($comments, &$app->session->comment_mode, "F"); // Forum template format
      }
   }
   else {
      // Set fineprint to off
      $hide_fineprint = true;
   }

   yats_assign($block, array("comment_block" => $comment_html_block));

   // Assign the current comment_mode to the dropdown
   yats_assign($block, $app->session->get_comment_mode_selected_state());
   echo yats_getbuf($block);  
   break;


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
                             'submit_button_text'       => 'Post It!'
                              ));

   yats_assign($block, array('hidden_name'        => 'subject',
                             'hidden_value'       => $comment_info['subj_raw']) );
   yats_assign($block, array('hidden_name'        => 'message',
                             'hidden_value'       => $comment_info['body_raw']) );
   yats_assign($block, array('hidden_name'        => 'new_post_parent_type',
                             'hidden_value'       => $comment['parent_type']) );
   yats_assign($block, array('hidden_name'        => 'new_post_parent_id',
                             'hidden_value'       => $comment['parent_id']) );
   yats_assign($block, array('hidden_name'        => 'cross_post_parent_id',
                             'hidden_value'       => $comment['cross_post_parent_id']) );
   yats_assign($block, array('hidden_name'        => 'topic_id',
                             'hidden_value'       => $global_topic_id) );
   yats_assign($block, array('hidden_name'        => 'forum_id',
                             'hidden_value'       => $global_forum_id) );

   echo yats_getbuf($block);  
   break;

case "new_topic_form":
   $app->forums->html->new_post_form("topic");
   break;


case "new_comment_form":

   $app->forums->html->new_post_form("comment");

   break;


}

?>

