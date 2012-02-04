<?php

/* News Articles/Comments Class */

class forum {



   function forumn() {



   }


   function get_article_summaries($type, $id='') {

      switch ($type) {

      // Get pending articles
      case "moderation":
         $sql ="SELECT DISTINCT dc_articles.id, dc_articles.subject,  dc_articles.state_id as state, dc_topics.id as topic_id, dc_topics.name as topic, dc_sections.name as section, dc_articles.date as date, dc_articles.leadin, dc_articles.body, dc_user.username as poster_username, dc_user.id as poster_id  FROM dc_topics, dc_sections, dc_articles, dc_user WHERE dc_articles.user_id=dc_user.id AND dc_articles.topic_id=dc_topics.id AND dc_articles.section_id=dc_sections.id AND dc_articles.state_id='2' ORDER BY date DESC LIMIT {$this->summaries_per_page}"; 
         break;

         // Get the latest frontpage articles
      case "frontpage":
         $sql ="SELECT DISTINCT dc_articles.id, dc_articles.subject,  dc_articles.state_id as state, dc_topics.id as topic_id, dc_topics.name as topic, dc_sections.name as section, dc_articles.date as date, dc_articles.leadin, dc_articles.body, dc_user.username as poster_username, dc_user.id as poster_id  FROM dc_topics, dc_sections, dc_articles, dc_user WHERE dc_articles.user_id=dc_user.id AND dc_articles.topic_id=dc_topics.id AND dc_articles.section_id=dc_sections.id AND dc_articles.state_id='5' ORDER BY date DESC LIMIT {$this->summaries_per_page}";
         break;

         // Get the latest blog articles for a specific user
      case "blog":
         $sql ="SELECT DISTINCT dc_articles.id, dc_articles.subject, dc_articles.state_id as state, dc_topics.id as topic_id, dc_topics.name as topic, dc_sections.name as section, dc_articles.date, dc_articles.leadin, dc_articles.body, dc_user.username as poster_username, dc_user.id as poster_id  FROM dc_topics, dc_sections, dc_articles, dc_user WHERE dc_articles.user_id=dc_user.id AND dc_articles.topic_id=dc_topics.id AND dc_articles.section_id=dc_sections.id AND dc_articles.user_id=$id  ORDER BY date DESC LIMIT {$this->summaries_per_page}"; 
         break;

         // Get the latest articles for a specific section
      case "section":
         $sql ="SELECT DISTINCT dc_articles.id, dc_articles.subject, dc_articles.state_id as state, dc_topics.id as topic_id, dc_topics.name as topic, dc_sections.name as section, dc_articles.date, dc_articles.leadin, dc_articles.body, dc_user.username as poster_username, dc_user.id as poster_id  FROM dc_topics, dc_sections, dc_articles, dc_user WHERE dc_articles.user_id=dc_user.id AND dc_articles.topic_id=dc_topics.id AND dc_articles.section_id=dc_sections.id AND dc_articles.section_id='$id'  ORDER BY date DESC LIMIT {$this->summaries_per_page}"; 
         break;


         // Get the latest articles for a specific topic
      case "topic":
         $sql ="SELECT DISTINCT dc_articles.id, dc_articles.subject, dc_articles.state_id as state, dc_topics.id as topic_id, dc_topics.name as topic, dc_sections.name as section, dc_articles.date, dc_articles.leadin, dc_articles.body, dc_user.username as poster_username, dc_user.id as poster_id  FROM dc_topics, dc_sections, dc_articles, dc_user WHERE dc_articles.user_id=dc_user.id AND dc_articles.topic_id=dc_topics.id AND dc_articles.section_id=dc_sections.id AND dc_articles.topic_id='$id' AND dc_articles.state_id='5' ORDER BY date DESC LIMIT {$this->summaries_per_page}"; 
         break;

         return $this->_get_article_summaries($sql);
      }




      /*
        Get the data that shows on the front page,
        like you see on the front page of slashdot.com
    
        UID = USER ID (username) - This is optional. 
        If passed then only articles with the particular user id will be retrieved
      */
      function _get_article_summaries($sql) {
         $dbresult = $GLOBALS[db]->query($sql);

         if ( toolbox::db_error(&$dbresult) ) {
            $result[err] = 1;
            $result[msg] = 'Unknown error.';

         }
         else {

            while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {

               // Get the number of comments
               $entry[comment_count] = $this->get_comments_count($entry['id']);

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








      function get_all_older_article_subjects($type) {

         switch ($type) {
         // Get the 20 frontpage articles starting after the most recent 10
         case "frontpage":
            $sql ="SELECT  dc_articles.id, dc_articles.subject, dc_articles.date  FROM  dc_articles WHERE dc_articles.state_id='5' ORDER BY date DESC LIMIT 1000, 10";
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
         case "blog":
            $sql ="SELECT DISTINCT dc_articles.id, dc_articles.subject, dc_user.username as poster_username, dc_user.id as poster_id, date  FROM dc_articles, dc_user WHERE dc_articles.user_id=dc_user.id AND dc_articles.user_id='$id'  ORDER BY date DESC LIMIT $limit, $start"; 
            break;

            // 
         case "topic":
            $sql ="SELECT dc_articles.id, dc_articles.subject, dc_topics.name as topic FROM dc_articles, dc_topics WHERE dc_articles.topic_id='$id' AND dc_articles.topic_id=dc_topics.id ORDER BY date DESC LIMIT $limit, $start";
            break;
         }

         return $this->_get_article_summaries($sql);
      }


      function _get_older_article_subjects($sql) {

         $dbresult = $GLOBALS[db]->query($sql);

         if ( toolbox::db_error(&$dbresult) ) {
            $result[err] = 1;
            $result[msg] = 'Unknown error.';
         }
         else {
            while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {

               $entry[comment_count] = $this->get_comments_count($entry['id']);

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
         //    $sql = "select * from dc_articles where id='$id'";

         $sql ="SELECT DISTINCT dc_articles.id, dc_articles.subject, dc_topics.id as topic_id, dc_topics.name as topic, dc_sections.name as section, dc_articles.date, dc_articles.leadin, dc_articles.body, dc_user.username as poster_username, dc_user.id as poster_id FROM dc_topics, dc_sections, dc_articles, dc_user WHERE dc_articles.user_id=dc_user.id AND dc_articles.topic_id=dc_topics.id AND dc_articles.section_id=dc_sections.id AND dc_articles.id=$id";

         $result = $GLOBALS[db]->query($sql);

         //TODO Check for errors

         $entry = $result->fetchRow(DB_FETCHMODE_ASSOC);

         // Get the number of comments
         // TODO this could be much more efficient and elegant
         $entry[comment_count] = $this->get_comments_count($id);


         return $entry;
      }









      /*
        Get comment count for an article
        id = ariticle id, it is required
      */
      function get_comments_count($id) {
         $sql = "SELECT id  FROM dc_comments  WHERE parent_id='$id'";

         $result = $GLOBALS[db]->query($sql);

         // TODO add error checking

         while ($entry = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
            $comment_count++;
            $sub_comment_count =  $this->get_comment_comments_count($entry['id']);
            $comment_count = $comment_count + $sub_comment_count;
         }
         return $comment_count;
      }




      /*
        Get comment comments count
        id = ariticle id, it is required
      */
      function get_comment_comments_count($id) {
         $sql = "select id  from dc_comments where parent_id='$id' and parent_type='C' order by id";
         $result = $GLOBALS[db]->query($sql);

         while ($entry = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
            $comment_count++;
            $sub_comment_count =  $this->get_comment_comments_count($entry['id']);
            $comment_count = $comment_count + $sub_comment_count;
         }
         return $comment_count;
      }























      /*
        Get all comments for an article
        id = ariticle id, it is required
      */
      function get_comments($id, $ascending = true) {
         $direction = $ascending ? 'ascending' : 'descending';
         $sql = "SELECT DISTINCT dc_comments.id, dc_comments.date, dc_comments.subject, dc_comments.body, dc_comments.state, dc_user.username as poster_username, dc_user.id as poster_id FROM dc_comments, dc_user WHERE dc_comments.user_id=dc_user.id AND dc_comments.parent_id='$id' order by dc_commends.id $direction";

         $result = $GLOBALS[db]->query($sql);

         // TODO add error checking

         while ($entry = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
            $entry[sub_comments] =  $this->get_comment_comments($entry['id']);
            $data[] = $entry;
         }
         return $data;
      }



      /*
        Get all comments for an article
        id = ariticle id, it is required
      */
      function get_comment_comments($id, $ascending) {

         //    $sql = "select * from dc_comments where parent_id='$id' and parent_type='C' order by id";
         $direction = $ascending ? 'ascending' : 'descending';

         $sql = "SELECT DISTINCT dc_comments.id, dc_comments.date, dc_comments.subject, dc_comments.body, dc_comments.state, dc_user.username as poster_username, dc_user.id as poster_id FROM dc_comments, dc_user WHERE dc_comments.user_id=dc_user.id AND dc_comments.parent_id='$id' AND parent_type='C' order by id $direction";


         $result = $GLOBALS[db]->query($sql);

         while ($entry = $result->fetchRow(DB_FETCHMODE_ASSOC)) {

            $entry[sub_comments] =  $this->get_comment_comments($entry['id']);
            $data[] = $entry;

         }

         return $data;
      }



      /*
        Get the number of comments for a parent
        id = ariticle id, it is required
      */
      function get_article_comment_count($id) {
         return($GLOBALS[db]->getOne("select COUNT(*) as comment_count from dc_comments where parent_id='$id' and parent_type='A' order by id"));
      }

      /*
        Get the subject of a comment
        id = ariticle id, it is required
      */
      function get_comment_subject($id) {
         return( $GLOBALS[db]->getOne('select subject from dc_comments where id='.$id) );
      }







      /*
        Get all users who have blogs
      */
      function get_bloggers() {
         $sql = "SELECT DISTINCT dc_user.id as user_id, dc_user.username as username FROM dc_user, dc_articles WHERE dc_user.id=dc_articles.user_id";

         $result = $GLOBALS[db]->query($sql);

         // TODO add error checking

         while ($entry = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
            $data[] = $entry;
         }
         return $data;
      }



      /*
        Get all topics
      */
      function get_topics() {
         $sql = "SELECT DISTINCT dc_topics.id as id, dc_topics.name as name, dc_topics.description as description FROM dc_topics";

         $result = $GLOBALS[db]->query($sql);

         // TODO add error checking

         while ($entry = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
            $topics[topic_id][]          = $entry[id];
            $topics[topic_name][]        = $entry[name];
            $topics[topic_description][] = $entry[description];
         }
         return $topics;
      }




      function add_new_blog_post($post) {

         $post = toolbox::slash($post);

         // TODO - fix section id, for now it is disabled
         $section_id = 1;

         //Check to see if this subject/body already exists, if so, don't add a duplicate
         //if(!$GLOBALS[db]->getOne( "select count(id) from dc_comments where subject='$comment[subject]' and body='$comment[body]' and parent_id='$comment[parent_id]'" )){
         $date = time();
         $dbresult = $GLOBALS[db]->query ( "insert into dc_articles (date, section_id, topic_id, user_id, subject, leadin, body, state_id) 
          values ('$date', '$section_id', '$post[topic_id]','$post[user_id]','$post[subject]','$post[leadin]','$post[body]','$post[state_id]')" );

         // TODO error checking

         if ( toolbox::db_error(&$dbresult) ) {
            print_r($dbresult);
            $result[err] = 1;
            $result[msg] = 'Unknown error, could not add your post.';

         }
         else {

            $result[err] = 0;
            $result[msg] = 'Your comment was successfully added.';

         }
         //} else {
         //// Duplicate found, reject this one
         //$result[err] = 1;
         //$result[msg] = 'Duplicate comment found. Your comment was rejected.';
         //}

         return $result;
      }






      function moderate_article($article_data) {

         $dbresult = $GLOBALS[db]->getOne ( "UPDATE dc_articles SET state_id='$article_data[state_id]' WHERE id='$article_data[article_id]'" );
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








   }
   ?>
   
