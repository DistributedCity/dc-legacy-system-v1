<?php

/* News Articles/Comments Class */

class news extends base {

  var $db;
  var $db_host;
  var $db_user;
  var $db_pass;
  var $db_name;



  function base()
  {
    // Init Database Settings
    $this->db_user = "root";
    $this->db_pass = "sellers";
    $this->db_host = "127.0.0.1";
    $this->db_name = "thread";

    // Init Database Object
    $this->db = DB::connect( "mysql://$this->db_user:$this->db_pass@$this->db_host/$this->db_name" );

    $this->_init_topics();

  }


  function add_new_comment($comment) {
    
    while( list( $key, $val ) = each( $comment )) { 
      $$key = $comment[$key] = addslashes( $val ); 
    } 
    
    mysql_connect( $this->db_host, $this->db_user, $this->db_pass );
    mysql_select_db( $this->db_name );

    // Check to see if this subject/body already exists, if so, don't add a duplicate
    $dbresult = mysql_query ( "select subject from comments where subject='$comment[subject]' and body='$comment[body]' and parent_id='$comment[parent_id]'" );

    if ( mysql_num_rows( $dbresult ) == 0 ) {
      $date = time();
      $dbresult = mysql_query ( "insert into comments (parent_id, parent_type, date, poster,subject,body) 
		     values ( '$comment[parent_id]', '$comment[parent_type]', '$date', '$comment[poster]', '$comment[subject]', '$comment[body]')" );
      
      //echo mysql_errno().": ".mysql_error()."<BR>";
      
      if ( !$dbresult ){

	$result[err] = 1;
	$result[msg] = 'Unknown error, could not add your comment.';

      }else{

	$result[err] = 0;
	$result[msg] = 'Your comment was successfully added.';

      }
    } else {
      // Duplicate found, reject this one
      $result[err] = 1;
      $result[msg] = 'Duplicate comment found. Your comment was rejected.';
    }

    return $result;
  }
  


  /*
    Get the data that shows on the front page,
    like you see on the front page of slashdot.com

    UID = USER ID (username) - This is optional. 
    If passed then only articles with the particular user id will be retrieved
  */
  function get_news_summaries($uid='')
  {

    if($uid) { // Only get articles for this particular user
      
      $sql = 'select * from news where poster="' .$uid . '" order by id desc limit 7';
      
    } else {
      
      $sql =  'select * from news order by id desc limit 7';
    }

    $result = $this->db->query($sql);
    
    while ($entry = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
      
      // Get the number of comments
      $countResult = $this->db->query('select count(*) as comments_count from comments where parent_id='.$entry['id']);
      $demoRow = $countResult->fetchRow(DB_FETCHMODE_ASSOC);
      $commentTotal = $demoRow['comments_count'];
      
      // Assign data to arrays
      $data[topic][]   = $entry['topic'] ? strip_tags( $entry['topic'], $allow_tags ) : "---";
      $data[subject][] = $entry['subject'] ? strip_tags( $entry['subject'], $allow_tags ) : "---";
      $data[poster][]  = $entry['poster'] ? strip_tags( $entry['poster'], $allow_tags ) : "---";
      $data[day][]     = $entry['date'] ? $entry['date'] : "---";
      $data[time][]    = $entry['time'] ? $entry['time'] : "---";
      $data[leadin][]  = $entry['leadin'] ? strip_tags( $entry['leadin'], $allow_tags ) : "---";
      $data[body][]    = $entry['body'] ? strip_tags( $entry['body'], $allow_tags ) : "---";
      $data[comments][] =$commentTotal;
      $data[id][]      = $entry['id'] ? $entry['id'] : "---";
    }

    return $data;
  }


  function _init_topics()
  {
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
  function get_article($id)
  {

    $sql = 'select * from news where id="' .$id.'"';

    $result = $this->db->query($sql);
    
    while ($entry = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
      
      // Get the number of comments
      $countResult = $this->db->query('select count(*) as comments_count from comments where parent_id='.$entry['id']);
      $demoRow = $countResult->fetchRow(DB_FETCHMODE_ASSOC);
      $commentTotal = $demoRow['comments_count'];
      
      // Assign data to arrays
      $data[topic][]   = $entry['topic'] ? strip_tags( $entry['topic'], $allow_tags ) : "---";
      $data[subject][] = $entry['subject'] ? strip_tags( $entry['subject'], $allow_tags ) : "---";
      $data[poster][]  = $entry['poster'] ? strip_tags( $entry['poster'], $allow_tags ) : "---";
      $data[day][]     = $entry['date'] ? $entry['date'] : "---";
      $data[time][]    = $entry['time'] ? $entry['time'] : "---";
      $data[leadin][]  = $entry['leadin'] ? strip_tags( $entry['leadin'], $allow_tags ) : "---";
      $data[body][]    = $entry['body'] ? strip_tags( $entry['body'], $allow_tags ) : "---";
      $data[comments][] =$commentTotal;
      $data[id][]      = $entry['id'] ? $entry['id'] : "---";
    }

    return $data;
  }







  /*
    Get all comments for an article
    id = ariticle id, it is required
  */
  function get_comments($id)
  {

    $sql = 'select * from comments where parent_id='.$id.' and parent_type="A" order by id';
    $result = $this->db->query($sql);
    
    while ($entry = $result->fetchRow(DB_FETCHMODE_ASSOC)) {

      $entry[sub_comments] =  $this->get_comment_comments($entry['id']);
      $data[] = $entry;

    $counter++;

    }
    return $data;
  }



  /*
    Get all comments for an article
    id = ariticle id, it is required
  */
  function get_comment_comments($id)
  {

    $sql = 'select * from comments where parent_id='.$id.' and parent_type="C" order by id';
    $result = $this->db->query($sql);
    
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
  function get_article_comment_count($id)
  {
    return($this->db->getOne('select COUNT(*) as comment_count from comments where parent_id='.$id.' and parent_type="A" order by id'));
  }

  /*
    Get the subject of a comment
    id = ariticle id, it is required
  */
  function get_comment_subject($id)
  {
    return( $this->db->getOne('select subject from comments where id='.$id) );
  }












}
?>
