<?php

/* Forum Class */

class forums extends comments {

   var $user_id;

   function forums($user_id) {
      $this->user_id = $user_id;
      $this->html = new forums_html;
   }

   function get_categories() {

      $sql = "SELECT id AS category_id, name AS category_name FROM dc_categories ORDER BY display_order";

      $dbresult = $GLOBALS[db]->query($sql);

      if ( toolbox::db_error(&$dbresult) ) {
         $result[err] = 1;
         $result[msg] = 'Unknown error.';
      }
      else {

         while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
            $categories[category_id][]   = $entry['category_id'];
            $categories[category_name][] = $entry['category_name'];
         }

         $result[msg] = $categories;
      }
      return $result;
   }





   function get_categories_info($category_id="") {

      // If no category_id is given, get all categories
      if ($category_id) {
         $category_id = addslashes($category_id);
         $sql = "SELECT id, name, description FROM dc_categories WHERE id='$category_id' ORDER BY display_order";
      }
      else {
         $sql = "SELECT id, name, description FROM dc_categories ORDER BY display_order";
      }



      $dbresult = $GLOBALS[db]->query($sql);
      if ( toolbox::db_error(&$dbresult) ) {
         $result[err] = 1;
         $result[msg] = 'Unknown error.';
      }
      else {
         while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {

            // Get info for each category
            if ($category_id) { // Only return this element in an array

               $info = array('id'          => $entry['id'],
                             'name'        => $entry['name'],
                             'description' => $entry['description'],
                             'forums' => $this->_list_forums_info($entry['id']));

            }
            else { // Return multiple array of elements

               $info[] = array('id'        => $entry['id'],
                               'name'        => $entry['name'],
                               'description' => $entry['description'],
                               'forums' => $this->_list_forums_info($entry['id']));
            }
         }

         $result[msg] = $info;
      }
      return $result;
   }



   function _list_forums_info($category_id) {
      $sql = "SELECT id, name, description FROM dc_topics WHERE category_id='$category_id' ORDER BY display_order";
      $dbresult = $GLOBALS[db]->query($sql);

      while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
         // Get info for each forum
         $primary_count = $GLOBALS[db]->getOne("SELECT count(id) FROM dc_comments WHERE parent_id='$entry[id]' AND parent_type='F'");
         $cross_post_count = $GLOBALS[db]->getOne("SELECT count(id) FROM dc_comments WHERE cross_post_parent_id='$entry[id]' AND parent_type='F'");

         $topic_count = $primary_count + $cross_post_count;
         $comment_count = $this->get_comments_count($entry[id], 'F');
         $post_count = $comment_count - $topic_count;

         // sanity check.
         $entry['post_count'] = (string)( $post_count >= 0 ? $post_count : 0 );
         $entry['topic_count'] = (string)$topic_count;

         $info[] = $entry;
      }
      return $info;
   }




   function get_topics($forum_id) {
      return $this->get_comments_summary($forum_id, 'F');
   }

   function get_global_recent_threads($max=10) {
      return $this->_get_global_recent_items(array('F'), $max);
   }

   function get_global_recent_comments($max=50) {
      return $this->_get_global_recent_items(array('C', 'A'), $max);
   }

   function get_global_recent_self_replies($max=50) {
      return $this->_get_global_recent_self_replies(null, $max);
   }

   function _get_global_recent_items($parent_types, $max) {
      $parent_id = addslashes($parent_id);

      if(is_array( $parent_types) ) {
         $count = 0;
         $parent_clause = 'AND (';
         foreach( $parent_types as $p ) {
            if( $count++ != 0 ) {
               $parent_clause .= 'OR';
            }
            $p = addslashes( $p );
            $parent_clause .= " c.parent_type='$p'";
         }
         $parent_clause .= ')';
      }

      $sql = "SELECT DISTINCT c.id, c.date, c.subject, c.state, u.username as poster_username, u.id as poster_id, c.view_count, t.name as topic_name, cat.name as cat_name FROM dc_comments as c, dc_user as u, dc_topics as t, dc_categories as cat WHERE c.user_id=u.id $parent_clause and c.topic_id = t.id and t.category_id = cat.id order by c.date desc limit $max";

      $dbresult = $GLOBALS[db]->query($sql);

      // TODO add error checking

      while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
         $data[] = $entry;
      }

      if ($data) {
         $result[msg] = $data;
      }
      else {
         $result[err] = 1;
      }

      return $result;
   }

   function _get_global_recent_self_replies($parent_type, $max) {
      $parent_id = addslashes($parent_id);
      $parent_type = addslashes($parent_type);

      if($parent_type) {
         $parent_clause = "AND dc_comments.parent_type='$parent_type'";
      }

      $sql = "SELECT DISTINCT dcr.id, dcr.date, dcr.subject, dcr.state, dcu.username as poster_username, dcu.id as poster_id, dcr.view_count, t.name as topic_name, cat.name as cat_name FROM dc_comments as dcr, dc_comments as dcf, dc_user as dcu, dc_topics as t, dc_categories as cat WHERE dcr.parent_id=dcf.id and dcf.user_id='{$this->user_id}' AND dcr.user_id=dcu.id and dcr.topic_id = t.id and t.category_id = cat.id $parent_clause order by dcr.date desc limit $max";

      $dbresult = $GLOBALS[db]->query($sql);

      // TODO add error checking

      while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
         $data[] = $entry;
      }

      if ($data) {
         $result[msg] = $data;
      }
      else {
         $result[err] = 1;
      }

      return $result;
   }


   function get_topic_location($topic_id) {
      $retval = null;
      $topic_id = addslashes($topic_id);
      $sql = "SELECT parent_id, parent_type, subject FROM dc_comments WHERE id='$topic_id'";

      $dbresult = $GLOBALS[db]->query($sql);
      $topic_data = $dbresult->fetchRow(DB_FETCHMODE_ASSOC);

      $ptype = trim($topic_data['parent_type']);
      if( $ptype == 'C' ) {
         $retval = $this->get_topic_location( $topic_data['parent_id'] );
      }
      else {
         if ($ptype == 'F') {
            $forum_location_info = $this->get_forum_location($topic_data['parent_id']);
         }
         else if ($ptype == 'A') {
            $forum_location_info = $this->get_forum_location_by_article($topic_data['parent_id']);
         }

         if( $forum_location_info ) {
            $cutoff_point = 40;
            if (strlen($topic_data[subject]) > $cutoff_point) {
               $location_topic_subject[location_topic_subject] = substr($topic_data[subject],0,$cutoff_point)."...";
            }

            $retval = array_merge($forum_location_info, $location_topic_subject);
         }
      }

      return $retval;
   }

   function get_forum_location_by_article( $article_id ) {

      $info = news::get_article( $article_id );
      return $this->get_forum_location( $info['topic_id'] );

   }

   function get_forum_location($forum_id) {

      $forum_id = addslashes($forum_id);
      $dbresult = $GLOBALS[db]->query("SELECT name, category_id FROM dc_topics WHERE id='$forum_id'");
      if ( toolbox::db_error(&$dbresult) || !$dbresult->numRows()) {
         return false;
      }
      else {
         while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
            $data[location_forum_id] = $forum_id;
            $data[location_forum_name] = $entry[name];
            $data[location_category_id] = $entry[category_id];
            $data[location_category_name] = $this->get_category_name($entry[category_id]);
         }
         return($data);
      }
   }

   function get_category_info($category_id) {
      $data[location_category_id] = $category_id;
      $data[location_category_name] = $this->get_category_name($category_id);
      return $data;
   }

   function get_category_name($category_id) {
      $category_id = addslashes($category_id);
      $dbresult = $GLOBALS[db]->query("SELECT name FROM dc_categories WHERE id='$category_id'");
      if (!$dbresult->numRows()) {
         return false;
      }
      else {
         while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
            return $entry[name];
         }
      }
   }


   function increment_topic_view_counter($topic_id, $current_count) {

      $current_count++;
      $sql = "update dc_comments SET view_count='$current_count' WHERE id='$topic_id'";

      // Try an update first, if an error where the record is no there, then insert a new one
      $dbresult = $GLOBALS[db]->query($sql);
   }


   /*
   */
   function get_topic($topic_id, $increment_view_counter="no") {

      $topic_id = addslashes($topic_id);

      $sql = "SELECT DISTINCT dc_comments.id, dc_comments.parent_id, dc_comments.parent_type, dc_comments.icon, dc_comments.subject, dc_comments.date,  dc_comments.body, dc_user.username as poster_username, dc_user.id as poster_user_id, dc_comments.view_count as view_count FROM dc_comments, dc_user WHERE dc_comments.user_id=dc_user.id AND dc_comments.id='$topic_id'";


      $dbresult = $GLOBALS[db]->query($sql);


      //TODO Check for errors

      $entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC);


      // Get the number of comments
      // TODO this could be much more efficient and elegant
      $entry[comment_count] = (string)$this->get_comments_count($topic_id, "C");

      if ($increment_view_counter == "yes")
         $entry[view_count] = (string)$this->increment_topic_view_counter($topic_id, $entry[view_count]);

      if ($entry) {
         $result[msg] = $entry;
      }
      else {
         $result[err] = 1;
      }
      return $result;
   }









   function list_forums($category_id) {
   }

   function get_forum_info($forum_id) {
   }

   function add_category($category_name) {
   }

   function delete_category($category_id) {
   }

   function add_forum($forum_name, $category_id) {
   }

   function delete_forum($forum_id) {
   }











   function get_cross_post_options() {

      $sql = "SELECT id, name, description FROM dc_categories ORDER BY display_order";

      $dbresult = $GLOBALS[db]->query($sql);
      if ( toolbox::db_error(&$dbresult) ) {
         $result[err] = 1;
         $result[msg] = 'Unknown error.';
      }
      else {
         while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {

            $info[$entry[name]] = $this->get_short_forums_info($entry['id']);
         }

         $result[msg] = $info;
      }
      return $result;
   }



   function get_short_forums_info($category_id) {
      $sql = "SELECT id, name FROM dc_topics WHERE category_id='$category_id' ORDER BY display_order";
      $dbresult = $GLOBALS[db]->query($sql);

      while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
         // Get info for each forum
         //$entry[topic_count] = (string)$GLOBALS[db]->getOne("SELECT count(id) FROM dc_comments WHERE parent_id='$entry[id]' AND parent_type='F'");
         //$entry[post_count] = (string)$this->get_comments_count($entry[id], 'F');
         $info[$entry[id]] = $entry[name];

      }

      return $info;
   }



}

?>