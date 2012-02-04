<?php

/* News Articles/Comments Class */

class news extends comments {

   var $summaries_per_page = 10;
   var $user_id;
   var $html;

   function news($user_id) {
      $this->user_id = $user_id;
      $this->_init_topics();
      $this->html = new news_html;
   }




   function get_summaries($type, $id='') {

      switch ($type) {

      // Get pending articles
      case "moderation":
         $sql = "SELECT DISTINCT dc_articles.id, dc_articles.subject, dc_articles.state_id as state, dc_topics.id as topic_id, dc_topics.name as topic_name,  dc_articles.date as date, dc_articles.leadin, dc_user.username as poster_username, dc_user.id as poster_id, dc_categories.name as category_name, dc_categories.id as category_id FROM dc_categories, dc_topics, dc_articles, dc_user WHERE dc_articles.user_id=dc_user.id AND dc_articles.topic_id=dc_topics.id AND dc_articles.state_id='2' AND dc_topics.category_id=dc_categories.id ORDER BY date DESC LIMIT $this->summaries_per_page"; 
         break;


      case "frontpage":
         $sql = "SELECT DISTINCT dc_articles.id, dc_articles.subject, dc_articles.state_id as state, dc_topics.id as topic_id, dc_topics.name as topic_name, dc_articles.date as date, dc_articles.leadin, dc_user.username as poster_username, dc_user.id as poster_id, dc_categories.name as category_name, dc_categories.id as category_id  FROM dc_categories, dc_topics, dc_articles, dc_user WHERE dc_articles.user_id=dc_user.id AND dc_articles.topic_id=dc_topics.id AND dc_topics.category_id=dc_categories.id AND dc_articles.state_id='5' ORDER BY date DESC LIMIT 10";
         break;

         // Get the latest blog articles for a specific user
      case "blog":

         $sql ="SELECT DISTINCT dc_articles.id, dc_articles.subject, dc_articles.state_id as state, dc_topics.id as topic_id, dc_topics.name as topic_name,  dc_articles.date, dc_articles.leadin, dc_user.username as poster_username, dc_user.id as poster_id, dc_categories.name as category_name, dc_categories.id as category_id FROM dc_categories, dc_topics,  dc_articles, dc_user WHERE dc_articles.user_id=dc_user.id AND dc_articles.topic_id=dc_topics.id AND  dc_articles.user_id=$id AND dc_topics.category_id=dc_categories.id ORDER BY date DESC LIMIT $this->summaries_per_page"; 

         break;


         // Get the latest articles for a specific category
      case "category":
         $sql ="SELECT DISTINCT dc_articles.id, dc_articles.subject, dc_articles.state_id as state, dc_topics.id as topic_id, dc_topics.name as topic_name, dc_articles.date, dc_articles.leadin, dc_user.username as poster_username, dc_user.id as poster_id, dc_categories.name as category_name, dc_categories.id as category_id  FROM dc_categories, dc_topics, dc_articles, dc_user WHERE dc_articles.user_id=dc_user.id AND dc_articles.topic_id=dc_topics.id AND dc_categories.id='$id' AND dc_articles.state_id='5' AND dc_topics.category_id=dc_categories.id ORDER BY date DESC LIMIT $this->summaries_per_page"; 
         break;

      }

      return $this->_get_summaries($sql);
   }




   /*
     Get the data that shows on the front page,
     like you see on the front page of slashdot.com
 
     UID = USER ID (username) - This is optional. 
     If passed then only articles with the particular user id will be retrieved
   */
   function _get_summaries($sql) {

      $dbresult = $GLOBALS[db]->query($sql);

      if ( toolbox::db_error(&$dbresult) ) {
         $result[err] = 1;
         $result[msg] = 'Unknown error.';

      }
      else {

         while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {

            // Get the number of comments
            $entry[comment_count] = $this->get_comments_count($entry['id'], "A"); // A = article comments

            $entry[category_icon] = toolbox::make_category_icon_name($entry[category_name]);

            $articles[] = $entry;
         }

         if (!count($articles)) {
            $result[err] = 1;
            $result[msg] = "There were no articles found.";
         }
         else {
            $result[msg] = $articles;
         }

      }

      return $result;
   }








   function get_all_older_article_subjects($type, $id="") {
      if ($id) {
         $id = addslashes($id);
      }
      switch ($type) {
      // Get the 20 frontpage articles starting after the most recent 10
      case "frontpage":
         $sql ="SELECT  dc_articles.id, dc_articles.subject, dc_articles.date  FROM  dc_articles WHERE dc_articles.state_id='5' ORDER BY date DESC LIMIT 1000, 0";
         break;

      case "topic":
         $sql ="SELECT  dc_articles.id, dc_articles.subject, dc_articles.date  FROM  dc_articles WHERE dc_articles.topic_id='$id' ORDER BY date DESC LIMIT 1000, 0";
         break;

      case "weblog":
         $sql ="SELECT  dc_articles.id, dc_articles.subject, dc_articles.date  FROM  dc_articles WHERE dc_articles.user_id='$id' ORDER BY date DESC LIMIT 1000, 0";
         break;
      }

      return $this->_get_older_article_subjects($sql);
   }


   function get_recent_older_article_subjects($type, $id="") {

      $limit = 20;
      $start = 10;

      if ($id) {
         $id = addslashes($id);
      }

      switch ($type) {
      // Get the 20 frontpage articles starting after the most recent 10
      case "frontpage":
         $sql ="SELECT  dc_articles.id, dc_articles.subject FROM  dc_articles WHERE dc_articles.state_id='5' ORDER BY date DESC LIMIT $limit, $start";
         break;

         // 
      case "weblog":
         $sql ="SELECT DISTINCT dc_articles.id, dc_articles.subject, dc_user.username as poster_username, dc_user.id as poster_id, date  FROM dc_articles, dc_user WHERE dc_articles.user_id=dc_user.id AND dc_articles.user_id='$id'  ORDER BY date DESC LIMIT $limit, $start"; 
         break;

         // 
      case "topic":
         $sql ="SELECT dc_topics.name, dc_articles.id, dc_articles.subject, dc_topics.name as topic FROM dc_articles, dc_topics WHERE dc_articles.topic_id='$id' AND dc_articles.topic_id=dc_topics.id ORDER BY date DESC LIMIT $limit, $start";
         break;
      }


      return $this->_get_older_article_subjects($sql);
   }


   function _get_older_article_subjects($sql) {
      $dbresult = $GLOBALS[db]->query($sql);

      if ( toolbox::db_error(&$dbresult) ) {
         $result[err] = 1;
         $result[msg] = 'Unknown error.';
      }
      else {
         while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {

            $entry[comment_count] = $this->get_comments_count($entry['id'], "A");
            $articles[]           = $entry;
         }

         if (!count($articles)) {
            $result[err] = 1;
            $result[msg] = "There were no articles found.";
         }
         else {
            $result[msg] = $articles;
         }
      }
      return $result;
   }




   function _init_topics() {
      $this->topics = array('USA',
      'apple',
      'censorship',
      'encryption',
      'humor',
      'internet',
      'law',
      'links',
      'linux',
      'media',
      'microsoft',
      'money',
      'news',
      'privacy',
      'programming',
      'security',
      'technology');
   }






   /*
     Get a *single* news article
     id = ariticle id, it is required
   */
   function get_article($id) {

      $sql ="SELECT DISTINCT dc_articles.id, dc_articles.subject, dc_topics.id as topic_id, dc_topics.name as topic, dc_articles.date, dc_articles.leadin, dc_articles.body, dc_user.username as poster_username, dc_user.id as poster_id,dc_categories.name as category_name, dc_categories.id as category_id, dc_articles.state_id as state  FROM dc_topics, dc_articles, dc_user WHERE dc_articles.user_id=dc_user.id AND dc_articles.topic_id=dc_topics.id AND dc_articles.id='$id' AND dc_topics.category_id=dc_categories.id ";


      $result = $GLOBALS[db]->query($sql);

      //TODO Check for errors

      $entry = $result->fetchRow(DB_FETCHMODE_ASSOC);

      // Get the number of comments
      // TODO this could be much more efficient and elegant
      $entry[comment_count] = $this->get_comments_count($id, "A");

      return $entry;
   }





   /*
     Get all topics
   */
   function get_topics() {
      $sql = "SELECT DISTINCT dc_topics.id as id, dc_topics.name as name, dc_topics.description as description FROM dc_topics";

      $result = $GLOBALS[db]->query($sql);

      // TODO add error checking

      while ($entry = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
         $topics[] = $entry;
      }
      return $topics;
   }





   /*
     Get all categories
   */
   function get_categories() {
      $sql = "SELECT DISTINCT dc_categories.id as id, dc_categories.name as name, dc_categories.description as description FROM dc_categories";

      $result = $GLOBALS[db]->query($sql);

      // TODO add error checking

      while ($entry = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
         $entry[icon] = toolbox::make_category_icon_name($entry[name]);
         $categories[] = $entry;
      }
      return $categories;
   }

   function pre_post_blog( $post ) {

      // Do not allow empty subject or empty lead in
      if (empty($post['subject']) || empty($post['leadin'])) {
         $result[err] = 1;
         $result[msg][] = 'You must always provide a Subject and Lead-In on all new posts.';
      }

      if ( strlen($post[leadin]) > $GLOBALS[config][weblog][max_size_leadin]   ) {          // Check Max size leadin
         $result[err] = 1;
         $result[msg][] = 'Your leadin has exceeded the maximum size of: ' . number_format($GLOBALS[config][weblog][max_size_leadin]) . " characters.";
      }


      if ( strlen($post[body]) > $GLOBALS[config][weblog][max_size_body] ) {          // Check Max size leadin
         $result[err] = 1;
         $result[msg][] = 'Your body has exceeded the maximum size of: ' . number_format($GLOBALS[config][weblog][max_size_body]) ." characters.";
      }


      // Do not allow empty topic
      if ($post[topic_id] == 'None') {
         $result[err] = 1;
         $result[msg][] = 'You must always select a Topic from the pulldown menu below.';
      }

      if ( !$result['err'] ) {
         $result['data'] = $post;
      }

      return $result;
   }


   function add_new_blog_post($post) {

      $result = $this->pre_post_blog( $post );

      if (!$result[err]) {

         $post = toolbox::slash($post);

         // Generate Article ID based on Hash of the Subject, UserID, and Leadin
         $post[id] = md5($post[subject] . $post[leadin]);

         // TODO - fix section id, for now it is disabled
         $section_id = 1;

         //Check to see if this subject/body already exists, if so, don't add a duplicate
         if ($GLOBALS[db]->getOne( "select count(id) from dc_comments where id='$post[id]'") == 0) {

            $date = time();

            $post[user_id] = $this->user_id;

            $sql = "insert into dc_articles (id, date, topic_id, user_id, subject, leadin, body, state_id) values ('$post[id]', '$date', '$post[topic_id]','$post[user_id]','$post[subject]','$post[leadin]','$post[body]','$post[state_id]')";
            $dbresult = $GLOBALS[db]->query($sql);


            // TODO error checking

            if ( toolbox::db_error(&$dbresult) ) {

               $result[err] = 1;
               $result[msg] = 'Unknown error, could not add your post.';

            }
            else {

               $result[msg] = 'Your comment was successfully added.';
               $result[post_id] = $post[id];
            }
         }
         else {
            // Duplicate found, reject this one
            $result[err] = 1;
            $result[msg] = 'Duplicate post found. Your post was rejected.';
         }
      }

      return $result;
   }


   function moderate_article($article_data) {

      $article_data = toolbox::slash($article_data);

      // Reset the submission date since it is being submitted to the front page to NOW
      $time = time();
      $dbresult = $GLOBALS[db]->getOne ( "UPDATE dc_articles SET date='$time', state_id='$article_data[state_id]', leadin='$article_data[leadin]' WHERE id='$article_data[article_id]'" );


      if ( toolbox::db_error(&$dbresult) ) {
         $result[err] = 1;
         $result[msg] = 'Unknown error, could not moderate the article.';
      }
      else {
         $result[err] = 0;
         $result[msg] = 'The article was successfully moderated.';
      }
      return $result;
   }



   function get_recent_updated_blogs() {


      $sql = "select dc_user.id as user_id, dc_user.username, dc_articles.subject, dc_articles.date FROM dc_articles, dc_user WHERE dc_user.id=dc_articles.user_id ORDER BY date DESC LIMIT 10";

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
         $result[msg] = "Unknown error.";
      }
      return $result;
   }






}
?>
