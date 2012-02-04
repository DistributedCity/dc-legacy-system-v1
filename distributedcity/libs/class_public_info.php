<?php


class public_info {

   var $user_id;
   var $data;

   function public_info($user_id="0") {
      $this->user_id = $user_id;

      if (!empty($user_id)) {
         $this->_load();
      }
   }

   function get_data() {

      $data = array("profile" => array_merge($this->data_persistent, $this->data_dynamic),
      "blog_recommendations" => $this->data[blog_recommendations]);

      if (!empty($data)) {
         $result[msg] = $data;
      }
      else {
         $result[err] = 1;
         $result[msg] = "User not found.";
      }
      return $result;
   }

   function init_public_info() {
      $profile_data[user_since] = toolbox::make_date_month_year(time());
      $this->set_data($profile_data);
   }



   function _load() {

      // Get User Persistent Public Info and Username
      $sql = "SELECT dc_user.username, email, www, specialties, company_association, dmt_usd_claim_number, quote, comments, user_since FROM dc_user_public_info, dc_user WHERE dc_user_public_info.user_id='$this->user_id' AND dc_user.id='$this->user_id'";
      $dbresult = $GLOBALS[db]->query($sql);

      // No User Or No Public Information Found?
      if (!$dbresult->numRows()) {

         //Try and just get the username
         $dbresult = $GLOBALS[db]->query( "SELECT username FROM dc_user WHERE id='$this->user_id'");

         // No user name found?
         if (!$dbresult->numRows()) {

            $error = 1;

         }
         else {
            while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
               $this->data_dynamic[username] = $entry[username];
            }
         }

      }
      else {

         // TODO add error checking
         while ($data = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
            $this->data_persistent = $data;
            $this->data_dynamic[username] = $data[username];
         }
      }


      if (!$error) {

         // Check if user_since is known
         if (empty($this->data_persistent[user_since])) {
            $this->data_persistent[user_since] = "Unknown";
         }

         // Set the user id
         $this->data_dynamic[user_id] = $this->user_id;

         // Get Last Post Information
         $sql = "SELECT dc_articles.id as most_recent_post_id, dc_articles.date as most_recent_post_date, dc_articles.subject as most_recent_post_subject FROM dc_articles WHERE dc_articles.user_id='$this->user_id' GROUP BY dc_articles.subject, dc_user.username, dc_articles.id, dc_articles.date ORDER BY dc_articles.date DESC LIMIT 1";
         $dbresult = $GLOBALS[db]->query($sql);
         // TODO add error checking
         while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
            $this->data_dynamic[most_recent_post_id]      = $entry[most_recent_post_id];
            $this->data_dynamic[most_recent_post_date]    = toolbox::make_date($entry[most_recent_post_date]);
            $this->data_dynamic[most_recent_post_subject] = $entry[most_recent_post_subject];
         }


         // Get Total Posts Information
         $sql = "SELECT count(id) as number_of_posts FROM dc_articles WHERE user_id='$this->user_id'";
         $dbresult = $GLOBALS[db]->query($sql);
         // TODO add error checking
         while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
            if ($entry[number_of_posts] != "0") {
               $this->data_dynamic[number_of_posts] = $entry[number_of_posts];
            }
         }

         // Get Total Posts Information
         $sql = "SELECT count(id) as number_of_comments FROM dc_comments WHERE user_id='$this->user_id'";
         $dbresult = $GLOBALS[db]->query($sql);
         // TODO add error checking
         while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
            if ($entry[number_of_comments] != "0") {
               $this->data_dynamic[number_of_comments] = $entry[number_of_comments];
            }
         }

         // Get user image
         $this->data_dynamic[user_image_src] = toolbox::get_user_image_src($this->user_id);

         // Load Blog Reccomendations
         $this->_load_blog_recommendations();

      }

      return($result);
   }



   function _load_blog_recommendations() {
      // Get Blog Reccomendations
      $sql = "SELECT dc_user.id as user_id, dc_user.username FROM dc_blog_recommendations, dc_user WHERE dc_blog_recommendations.user_id='$this->user_id' AND dc_blog_recommendations.user_id_blog=dc_user.id";
      $dbresult = $GLOBALS[db]->query($sql);

      if ($dbresult->numRows()) {
         while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
            $blog_usernames[] = $entry[username];
            $blog_user_ids[]  = $entry[user_id];
            $blog_user_images[] = toolbox::get_user_image_src($entry[user_id]);
         }
         //      "user_image_src" => toolbox::get_user_image_src($parameters[user_id]),
         $this->data[blog_recommendations][recommended_blog_username] = $blog_usernames;
         $this->data[blog_recommendations][recommended_blog_user_id]  = $blog_user_ids;
         $this->data[blog_recommendations][recommended_blog_user_image_src]  = $blog_user_images;
      }
   }



   function get_blog_recommendations() {
      $this->_load_blog_recommendations();
      if (!empty($this->data[blog_recommendations][recommended_blog_username])) {
         $result[msg] = array("recommended_blog_username" => $this->data[blog_recommendations][recommended_blog_username],
         "recommended_blog_user_id"  => $this->data[blog_recommendations][recommended_blog_user_id],
         "user_image_src"            => $this->data[blog_recommendations][recommended_blog_user_image_src]);
      }
      else {
         $result[msg] = "No Blog recommendations found.";
      }
      return($result);
   }




   function remove_blog_recommendation($blog_id) {

      $blog_id = addslashes($blog_id);

      $dbresult = $GLOBALS[db]->getOne( "DELETE FROM dc_blog_recommendations WHERE user_id='$this->user_id' AND user_id_blog='$blog_id'");

      // Check if error
      if ( toolbox::db_error(&$dbresult) ) {
         $result[err] = 1;
         $result[msg] = "There was an problem removing the blog recommendation.";
      }
      else {
         $result[msg] = "Your blog recommendation was successfully removed.";
      }
      return($result);
   }


   function add_blog_recommendation($blog_id) {

      $blog_id = addslashes($blog_id);

      // Check that the blog recommendation is not already in the list of recommendations
      $dbresult = $GLOBALS[db]->getOne( "SELECT count(id) FROM dc_blog_recommendations  WHERE user_id='$this->user_id' AND user_id_blog='$blog_id'");

      if ($dbresult == "0") { // Recommendation is does not exist, go ahead and add it

         // This check really should be implemented in the DB via referential integrity... sheesh.
         $dbresult = $GLOBALS[db]->getOne( "SELECT count(id) FROM dc_articles  WHERE user_id='$blog_id'");

         if( toolbox::db_error(&$dbresult) || $dbresult < 1)  {
            $result[err] = 1;
            $result[msg] = "The user does not have a blog.  Thus, it can't very well be recommended.";
         }
         else {
            $dbresult = $GLOBALS[db]->getOne( "INSERT INTO dc_blog_recommendations ( user_id, user_id_blog) VALUES ('$this->user_id', '$blog_id')" );

            // Check if error
            if ( toolbox::db_error(&$dbresult) ) {
               $result[err] = 1;
               $result[msg] = "There was an problem adding this Blog recommendation. It may already be in your recommendation list.";
            }
            else {
               $result[msg] = "Your blog recommendation was successfully added.";
            }
         }
      }
      else {
         $result[err] = 1;
         $result[msg] = "There was an problem adding this Blog recommendation. It may already be in your recommendation list.";
      }
      return($result);
   }


   function get_top_recommended_blogs() {

      $sql = "select dc_user.id as user_id, dc_user.username, count(dc_blog_recommendations.user_id_blog) FROM dc_blog_recommendations, dc_user WHERE dc_user.id=dc_blog_recommendations.user_id_blog GROUP BY dc_blog_recommendations.user_id_blog, dc_user.username, dc_user.id  ORDER BY count DESC, dc_user.username ASC LIMIT 10";

      $dbresult = $GLOBALS[db]->query($sql);
      // TODO add error checking
      while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
         $data[user_id][]  = $entry[user_id];
         $data[username][] = $entry[username];
         $data[count][]    = $entry[count];
      }

      if ($data) {
         $result[msg] = $data;
      }
      else {
         $result[err] = 1;
         $result[msg] = "Unknown error.";
      }
      return $result;
   }

}
?>