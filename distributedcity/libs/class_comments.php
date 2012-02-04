<?php

/* Comments Class */

class comments {
   var $html;

   function comments() {
      $this->html = new comments_html;

   }



   /*
     Get comment count - recursively counts sub comments
   */
   function get_comments_count($parent_id, $parent_type) {

      $sql = "SELECT id  FROM dc_comments WHERE parent_id='$parent_id' AND parent_type='$parent_type' UNION SELECT id  FROM dc_comments WHERE cross_post_parent_id='$parent_id' AND parent_type='$parent_type'";

      $result = $GLOBALS[db]->query($sql);

      if ( toolbox::db_error(&$dbresult) ) {

         $comment_count = 0;

      }
      else {

         $comment_count = 0;
         while ($entry = $result->fetchRow(DB_FETCHMODE_ASSOC)) {

            $comment_count++;
            $sub_comment_count =  $this->get_comments_count($entry['id'], "C");
            $comment_count = $comment_count + $sub_comment_count;
         }
      }
      return $comment_count;
   }




   function get_comments_summary($parent_id, $parent_type) {

      $parent_id = addslashes($parent_id);
      $parent_type = addslashes($parent_type);

      $sql = "SELECT DISTINCT dc_comments.id, dc_comments.date, dc_comments.subject, dc_comments.state, dc_user.username as poster_username, dc_user.id as poster_id, dc_comments.view_count FROM dc_comments, dc_user WHERE dc_comments.user_id=dc_user.id AND dc_comments.parent_id='$parent_id' AND dc_comments.parent_type='$parent_type' UNION SELECT DISTINCT dc_comments.id, dc_comments.date, dc_comments.subject, dc_comments.state, dc_user.username as poster_username, dc_user.id as poster_id, dc_comments.view_count FROM dc_comments, dc_user WHERE dc_comments.user_id=dc_user.id AND dc_comments.cross_post_parent_id='$parent_id' AND dc_comments.parent_type='$parent_type'";

      $dbresult = $GLOBALS[db]->query($sql);



      // TODO add error checking

      while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {

         // Get the comment count for natural parent
         $entry[replies] =  $this->get_comments_count($entry['id'], "C");

         $data[$entry[date]] = $entry;
      }

      // Sort by date descending (We assigned this above as the key value for each post
      if (!empty($data))
         krsort($data);

      if ($data) {
         $result[msg] = $data;

      }
      else {
         $result[err] = 1;
      }


      return $result;
   }












   /*
     Get all comments for an article
     id = ariticle id, it is required
 
     // Parent Types:
     // C = Comment
     // A = Article
     // T = Forum Topic
     // P = Poll
     // R = Review
 
   */
   function get_comments($parent_id, $parent_type) {
      $parent_id = addslashes($parent_id);
      $parent_type = addslashes($parent_type);

      $sql = "SELECT DISTINCT dc_comments.id, dc_comments.date, dc_comments.icon, dc_comments.subject, dc_comments.body, dc_comments.state, dc_user.username as poster_username, dc_user.id as poster_id FROM dc_comments, dc_user WHERE dc_comments.user_id=dc_user.id AND dc_comments.parent_id='$parent_id' AND dc_comments.parent_type='$parent_type' ORDER BY dc_comments.date";

      $dbresult = $GLOBALS[db]->query($sql);

      // TODO add error checking

      while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
         $entry[sub_comments] =  $this->get_comments($entry['id'], "C", $ascending);
         $data[] = $entry;
      }

      if ($data) {
         return $data;
      }
      else {
         return false;
      }
   }



   /*
     Get the number of comments for a parent
     id = ariticle id, it is required
   */
   function get_article_comment_count($parent_id) {
      return($GLOBALS[db]->getOne("select COUNT(*) as comment_count from dc_comments where parent_id='$parent_id' and parent_type='A' order by id"));
   }


   /*
     Get the subject of a comment
     id = ariticle id, it is required
   */
   function get_comment_subject($id) {
      return( $GLOBALS[db]->getOne("select subject from dc_comments where id='$id'") );
   }

   function get_comment_body($id) {
      return( $GLOBALS[db]->getOne("select body from dc_comments where id='$id'") );
   }

   function get_topic_id_for_parent($parent_id, $parent_type) {
      $row = $sql = null;

      if( $parent_type === 'F' ) {
         return $parent_id;
      }
      else {
         $table = $parent_type === 'A' ? 'dc_articles' : 'dc_comments';
         $sql = "select topic_id from $table where id = '$parent_id'";

         $dbresult = $GLOBALS[db]->query ( $sql );
         if( !toolbox::db_error(&$dbresult)) {
            $row = $dbresult->fetchRow(DB_FETCHMODE_ASSOC);
         }

         return $row ? $row['topic_id'] : null;
      }
   }

   function pre_process_post( $post_data ) {

      // Check Max Size Body
      if ( strlen($post_data[body]) > $GLOBALS[config][comments][max_size_body] ) {
         $result[err] = 1;
         $result[msg] = 'Your body has exceeded the maximum size of: ' . number_format($GLOBALS[config][comments][max_size_body]) ." characters.";
      }

      // Check Max Size Topic/Subject
      else if ( strlen($post_data[subject]) > $GLOBALS[config][posts][max_size_topic_subject] ) {
         $result[err] = 1;
         $result[msg] = 'Your subject/title has exceeded the maximum size of: ' . number_format($GLOBALS[config][posts][max_size_topic_subject]) ." characters.";
      }

      // Check that at LEAST a subject OR a body is being submitted
      else if (empty($post_data[subject]) && empty($post_data[body])) {
         $result[err] = 1;
         $result[msg] = 'Your post was blank. You must enter in at least a Subject or a Body for any submission.';
      }

      else if (empty($post_data['parent_id']) || empty($post_data['parent_type']) || 
               empty($post_data['user_id']) ) {
         $result[err] = 1;
         $result[msg] = 'Internal error.  missing required data.';
      }
      
      else {
         $result['err'] = null;
         $result['data'] = $post_data;
      }
      return $result;
   }

   function add_new_post($post_data) {
      $result = $this->pre_process_post( $post_data );

      if (!$result[err]) {
         $result['data'] = toolbox::slash($post_data);
         $post_data = $result['data'];

         $topic_id = $this->get_topic_id_for_parent( $post_data['parent_id'], $post_data['parent_type'] );

         if (!$GLOBALS[db]->getOne( "select count(id) from dc_comments where subject='{$post_data[subject]}' and body='{$post_data[body]}' and parent_id='{$post_data[parent_id]}' and topic_id='$topic_id'" )) {

            $post_data[id] = toolbox::get_random_hash();

            $date = time();

            $sql = "insert into dc_comments (id, parent_id, parent_type, date,user_id, subject, body, icon, state, cross_post_parent_id, view_count, topic_id) 
             values ( '$post_data[id]', '$post_data[parent_id]','$post_data[parent_type]', '$date', '$post_data[user_id]', '$post_data[subject]', '$post_data[body]', '$post_data[icon]', '$post_data[state]', '$post_data[cross_post_parent_id]', 0, '$topic_id' )";

            $dbresult = $GLOBALS[db]->query ( $sql );

            if (toolbox::db_error(&$dbresult) ) {
               $result[err] = 1;
               $result[msg] = 'Unknown error, could not add your post.';
            }
            else {
               $result[err] = 0;
               $result[msg] = 'Your post was successfully added.';
            }
         }
         else {
            $result[err] = 1;
            $result[msg] = 'This appears to be a duplicate. Perhaps you submitted the form twice?  Post NOT added.';
         }
      }

      return $result;
   }

   function get_incoming_post_vars(&$HTTP_POST_VARS) {
      // TODO Validate incoming and required data
      $new_post['parent_id']   = $HTTP_POST_VARS['new_post_parent_id'];
      $new_post['parent_type'] = $HTTP_POST_VARS['new_post_parent_type']; // A=article C=comment
      $new_post['user_id']     = $this->user_id;
      $new_post['subject']     = $HTTP_POST_VARS['subject'];
      $new_post['body']        = $HTTP_POST_VARS['message'];
      $new_post['state']       = 1; //Default to state 1 for a new comment

      // If this is a new topic post, look for a cross post selection
      if ($new_post['parent_type'] == "F") {
         $new_post['cross_post_parent_id'] = $HTTP_POST_VARS['cross_post_parent_id'];
      }

      return $new_post;
   }

   function process_new_incoming_post(&$HTTP_POST_VARS) {
      $new_post = $this->get_incoming_post_vars($HTTP_POST_VARS);
      return $this->add_new_post($new_post);
   }

   function preview_new_incoming_post(&$HTTP_POST_VARS) {
      $new_post = $this->get_incoming_post_vars($HTTP_POST_VARS);
      return $this->pre_process_post( $new_post );
   }

   // flattening and sorting really should be done in the DB
   function _flatten_comments($comments, &$flat_list) {
      foreach( $comments as $comment ) {
         $sub = $comment['sub_comments'];

         $comment['sub_comments'] = null;
         $flat_list[] = $comment;

         if( $sub ) {
            $this->_flatten_comments( $sub, $flat_list );
         }
      }
   }

   function flatten_comments( &$comments ) {
      $flat_list = array();
      $this->_flatten_comments( $comments, $flat_list );
      $comments = $flat_list;
   }

   function sort_comments(&$comments, $asc) {
      $func = $asc ? 'cmp_comments_asc' : 'cmp_comments_desc';
      usort ($comments, $func);
   }

}

function cmp_comments_desc($a, $b) {
    if ($a['date'] == $b['date']) return 0;
    return ($a['date'] > $b['date']) ? -1 : 1;
}

function cmp_comments_asc($a, $b) {
    if ($a['date'] == $b['date']) return 0;
    return ($a['date'] < $b['date']) ? -1 : 1;
}


?>