<?php

class comments_html{


   function new_post_form($post_type){
     global $templateBase, $app, $HTTP_POST_VARS, $new_topic, $new_post;
     
     $block = yats_define($templateBase.'master/general_compose.html');
     $javascript = yats_define($templateBase.'master/formatting_javascript.html');

     // this is so miserable.
     $new_post['parent_id'] = $new_post['parent_id'] ? $new_post['parent_id'] : $HTTP_POST_VARS['new_post_parent_id'];
     $new_post['type'] = $new_post['type'] ? $new_post['type'] : $HTTP_POST_VARS['new_post_parent_type'];
     $new_post['cross_post_parent_id'] = $new_post['cross_post_parent_id'] ? $new_post['cross_post_parent_id'] : $HTTP_POST_VARS['cross_post_parent_id'];
     $new_post['topic_id'] = $new_post['topic_id'] ? $new_post['topic_id'] : $HTTP_POST_VARS['topic_id'];
     $new_post['forum_id'] = $new_post['forum_id'] ? $new_post['forum_id'] : $HTTP_POST_VARS['forum_id'];

     $parent_body = comments::get_comment_body($new_post['parent_id']);

     $subject = '';
     if( $HTTP_POST_VARS[subject]) {
        $subject = $HTTP_POST_VARS[subject];
     }
     else if( $new_post['parent_id'] ) {
        $subject = comments::get_comment_subject($new_post['parent_id']);
        if( $subject && strtolower(substr($subject, 0, 3)) != 're:') {
           $subject = "Re: $subject";
        }
     }


     switch($post_type){
       

      case 'topic':

         $this->render_location_bar($block, 'new_topic', $app->forums->get_forum_location($app->session->forum_id));

         yats_hide($block, 'encryption', true);
         yats_hide($block, 'subject', true);
         yats_hide($block, 'from', true);
         yats_hide($block, 'to', true);
         yats_hide($block, 'forum_comment_prompt', true);
         yats_hide($block, 'article_comment_prompt', true);
         yats_hide($block, 'message_page_prompt', true);

         // Assign vars to new comment form
         yats_assign($block, array('form_title_text'       => 'Add New Topic',
                                   'submit_button_text'    => 'Preview Post',
                                   'new_post_parent_top'   => $new_post[parent_top],
                                   'subject_content'       => $GLOBALS[app]->html->dc_encode($subject, 'subject'),
                                   'message_content'       => $HTTP_POST_VARS[message] ? $HTTP_POST_VARS[message] : '',
                                   'parent_body'           => $GLOBALS[app]->html->dc_encode($parent_body, 'message_body') ));

         // Assign hidden form vars
         $hidden_vars = array('hidden_name'  => array('new_post_parent_id', 'new_post_parent_type', 'topic_id', 'forum_id'),
                              'hidden_value' => array($new_post[parent_id], 'F', $new_post['topic_id'], $new_post['forum_id'])); 

         yats_assign($block, $hidden_vars);

         $result = $app->forums->get_cross_post_options();

         $cross_post_options = $result[msg];
         // Parse result for yats assignment
         foreach($cross_post_options as $category => $forums) {
            foreach($forums as $forum_id => $forum_name) {
               $cross_post_parent_id[] = (string)$forum_id;
               $cross_post_label[] = $category.': '. $forum_name;
               $cross_post_selected[] = $forum_id == $new_post['cross_post_parent_id'] ? 'selected' : '';
            }
         }

         yats_assign($block, array('cross_post_parent_id' => $cross_post_parent_id,
                                   'cross_post_label'     => $cross_post_label, 
                                   'cross_post_selected'  => $cross_post_selected));
         break;
       
       

       
     case 'comment':
       
       $this->render_location_bar($block, 'new_comment', $app->forums->get_forum_location($app->session->forum_id));
       
       yats_hide($block, 'encryption', true);
       yats_hide($block, 'title', true);
       yats_hide($block, 'from', true);
       yats_hide($block, 'to', true);
       yats_hide($block, 'crosspost', true);
       yats_hide($block, 'forum_topic_prompt', true);
       yats_hide($block, 'article_comment_prompt', true);
       yats_hide($block, 'message_page_prompt', true);
       

       // Assign vars to new comment form
       yats_assign($block, array('form_title_text'          => 'Add New Comment',
                                 'submit_button_text'       => 'Preview Post',
                                 'subject_content'       => $GLOBALS[app]->html->dc_encode($subject, 'subject'),
                                 'message_content'       => $HTTP_POST_VARS[message] ? $HTTP_POST_VARS[message] : '',
                                 'parent_body'           => $GLOBALS[app]->html->dc_encode($parent_body, 'message_body') ));

     
       // Assign hidden form vars
       $hidden_vars = array('hidden_name'  => array('new_post_parent_id', 'new_post_parent_type', 'topic_id', 'forum_id'),     
                            'hidden_value' => array($new_post[parent_id], $new_post[type], $new_post['topic_id'], $new_post['forum_id'])); 
       yats_assign($block, $hidden_vars);
       
       
       
       break;
     }
     
     
     // Assign vars to new post form
     yats_assign($block, array('current_user'          => $app->user->get_username(),
                               'formatting_javascript' => yats_getbuf($javascript),
                               'form_action'           => '?action=preview_post',
                               'sid'                   => session_id()));

     
     if($new_post[error_message])
       yats_assign($block, array('error_message' => $new_post[error_message]));  
     
     echo yats_getbuf($block);  
     
   }

   




   // Assign 'you are here' location bar at the top
   function render_location_bar(&$block, $type, $location=null){
      if( is_array( $location ) ) {
        $location_bar = yats_define($GLOBALS[templateBase].'forums/location_bar_'.$type.'.html');   
        yats_assign($location_bar, $location);
        yats_assign($block, array('location_bar' => yats_getbuf($location_bar)));
      }
   }
   
}

?>