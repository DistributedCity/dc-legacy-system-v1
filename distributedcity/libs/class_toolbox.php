<?php

/* Misc Tools */
class toolbox {

   function hash($v) {

      /* Use pure sha1 PHP Code for now. */

      /* This returns an array of 5 32-bit integers. */
      $sha = new SHA;  
      $hasharray = $sha->hash_string($v);

      /* Converts the hash array to an uppercase hex string. */
      $hash = $sha->hash_to_string( $hasharray );

      return($hash);
   }


   function rfcdate () {
      // Translated from imap-4.7c/src/osdep/unix/env_unix.c 
      // env-unix.c is Copyright 2000 by the University of Washington 
      // localtime() not available in PHP3... 
      $tn = time(0); 
      $zone = gmdate("H", $tn) * 60 + gmdate("i", $tn); 
      $julian = gmdate("z", $tn); 
      $t = getdate($tn); 
      $zone = $t[hours] * 60 + $t[minutes] - $zone; 
      // 
      // julian can be one of: 
      //  36x  local time is December 31, UTC is January 1, offset -24 hours 
      //    1  local time is 1 day ahead of UTC, offset +24 hours 
      //    0  local time is same day as UTC, no offset 
      //   -1  local time is 1 day behind UTC, offset -24 hours 
      // -36x  local time is January 1, UTC is December 31, offset +24 hours 
      // 
      if ($julian = $t[yday] - $julian) {
         $zone += (($julian < 0) == (abs($julian) == 1)) ? -24*60 : 24*60; 
      }

      return date('D, d M Y H:i:s ', $tn) . sprintf("%03d%02d", $zone/60, abs($zone) % 60) . " (" . strftime("%Z") . ")"; 
   } 



   /* Session Related */
   function clean_sessions($lifetime="3600", $path="/websites/dc/sessions") {
      // FIX THIS!
      return;
      /* Cleanup, Logout, then Destroy  all session files which are older than the sessionlifetime */
      $expiredTime = time() - $lifetime; 
      $d = dir($path);

      while ($entry=$d->read()) {

         /* Operate on only session files */
         if (ereg("sess_", $entry)) {

            $filename = $path."/".$entry;

            /* Get the file timestamp */
            $timestamp = fileatime($filename);

            if ($timestamp < $expiredTime) {

               /* Kill Expired Sessions */
               //if(@unlink($filename)){
               //  if($this->debug) {
               print "Session file deleted: $filename <br>\n";
               $session_id = substr($entry, 5);
               print "Session ID:". $session_id ."<hr>";


               //	  include "Snoopy.class.inc";
               $snoopy = new Snoopy;

               $snoopy->user = "joe";
               $snoopy->pass = "bloe";

               if ($snoopy->fetch("http://distributedcity.com/logout/?logout=true&SID=".$session_id)) {
               }
//  	  echo "response code: ".$snoopy->response_code."<br>\n";
//  	  while(list($key,$val) = each($snoopy->headers))
//  	    echo $key.": ".$val."<br>\n";
//  	  echo "<p>\n";

//  	  //echo "<PRE><FONT COLOR=\"#FFFFFF\">".htmlspecialchars($snoopy->results)."</FONT></PRE>\n";

//  	  print "<HR>" . $snoopy->results;
//          }
//          else
//  	  echo "error fetching document: ".$snoopy->error."\n";


//  	flush();

               //  }
               //} else {
               //if($this->debug){
               // print "Could not delete session file.<br>\n";
               //}
               //  }
            }
         }
      }
      $d->close();

   }

   function get_random_hash() {
      return md5(toolbox::get_random_id());
   }


   function get_random_id() {
      srand((double)microtime()*1000000);    // Seed the number generator
      $token = uniqid (rand()); // better, difficult to guess
      return $token;
   }  


   function formatErrorList($errors) {
      return("<BR>-&nbsp;". implode($errors, "<BR>-&nbsp;"));
   }

   function formatStatusList($status) {
      return("<BR>-&nbsp;". implode($status, "<BR>-&nbsp;"));
   }

   function formatHiddenFormVars($name, $value) {
      $hiddenFormVars = "<input type=\"hidden\" name=\"". $name."\" value=\"".$value."\">\n";
      return($hiddenFormVars);
   }


   function findPGPKeys($data) {

      /* Get array of PGP Key Matches */
      preg_match_all("/-----BEGIN PGP PUBLIC KEY BLOCK-----.*-----END PGP PUBLIC KEY BLOCK-----/sU",$data,$matches);

      $result = new methodResult;

      if ($matches[0]) {

         $result->setData($matches[0]);

      }
      else {

         $result->setErrorCode(1);

      }

      return $result;
   }


   // Adds slashes to an associative array
   function slash($array) {

      while ( list( $key, $val ) = each( $array )) {
         $$key = $array[$key] = addslashes( $val ); 
      } 
      return $array;
   }

   function standard_dc_date( $timestamp, $is_local = true ) {

      $format = '%b %d %Y, %T %Z';

      return $is_local ? gmstrftime ( $format, $timestamp ) : strftime( $format, $timestamp );
   }


   function make_age( $timestamp ) {
      $currtime = time();

      $timediff = $currtime - $timestamp;

      $units = array( 'year' => 31536000,  // 60 * 60 * 24 * 365
                      'month' => 2592000,  // 60 * 60 * 24 * 30
                      'week'  => 604800,   // 60 * 60 * 24 * 7
                      'day' => 86400,      // 60 * 60 * 24
                      'hour' => 3600,      // 60 * 60
                      'minute' => 60,
                      'second' => 1);

      foreach( $units as $key => $val ) {
         if( $timediff >= $val ) {
            $diff = (int)($timediff / $val);
            $unit = $diff == 1 ? $key : $key . 's';
            break;
         }
      }

      return "$diff $unit";
   }

   function make_date($timestamp) {
      return toolbox::standard_dc_date( $timestamp );
   }

   function make_date_month_year($timestamp) {
      return toolbox::standard_dc_date( $timestamp );
   }


   function make_folder_date($timestamp) {
      return toolbox::standard_dc_date( $timestamp );
   }


   function make_comment_date($timestamp) {
      return toolbox::standard_dc_date( $timestamp );
   }

   function make_short_date($timestamp) {
      return toolbox::standard_dc_date( $timestamp );
   }

   function make_rough_date($timestamp) {
      return toolbox::standard_dc_date( $timestamp );
   }

   function format_comment_count($comment_count) {
      // Format comment count display
      if ($comment_count == 0) {
         $comment_count_text = "No sub-comments.";
         //      $comment_count = " ";
      }elseif ($comment_count == 1) {
         $comment_count_text = "<B>1</B> sub-comment.";
      } elseif ($comment_count > 1) {
         $comment_count_text = "<B>".$comment_count."</B> sub-comments";
      }
      return $comment_count_text;
   }


   function db_error($handle) {
      if (DB::isError($handle)) {
         print ($handle->getMessage);
         return true;
      }
      else {
         return false;
      }
   }

   function get_app_user() {
      return $GLOBALS[app]->user;
   }

   function get_user_id($username) {
      $sql = "SELECT id FROM dc_user WHERE username='$username'";
      $user_id = $GLOBALS[db]->getOne($sql);
      // TODO error check
      return $user_id;
   }

   function get_user_image_src($user_id) {
      static $cache;
      if( isset($cache[$user_id]) ) {
         return $cache[$user_id];
      }
      else {
         $sql = "SELECT user_image_src FROM dc_user WHERE id='$user_id'";
         $user_image_src = $GLOBALS[db]->getOne($sql);
         // TODO error check
         if (empty($user_image_src)) {
            return false;
         }
         else {
            return $GLOBALS[config][web_user_image_base].$user_image_src;
         }
      }
   }





   function get_users($type, $parameters="") {

      $parameters = addslashes($parameters);

      if ($type == "ALL") {

         $sql = "SELECT id as user_id, username FROM dc_user ORDER BY username";

      }elseif($type == "RANGE") {

         $values = explode("|", $parameters );

         if (count($values) > 1) {
            foreach($values as $value) {

               $where_params .= "username LIKE '" . strtolower($value)  ."%' OR ";
               $where_params .= "username LIKE '" . strtoupper($value)  ."%' OR ";

            }
            $where_params = substr($where_params, 0, -3);

         }
         else {
            $where_params .= "username LIKE '" . $value[0]  ."%'";
         }

         $sql = "SELECT id as user_id, username FROM dc_user WHERE $where_params order by username"; 
         //username LIKE 'W%' OR username LIKE 'A%';

      }elseif($type == "MATCH") {

         $sql = "SELECT id as user_id, username FROM dc_user WHERE username LIKE '%".$parameters."%' ORDER BY username";

      }elseif($type == "BLOGGERS") {

         $sql = "SELECT DISTINCT dc_user.id as user_id, dc_user.username as username FROM dc_user, dc_articles WHERE dc_user.id=dc_articles.user_id order by dc_user.username";
      }

      return toolbox::_get_users($sql);
   }



   /*
     Get all users usernames and user_id's
   */
   function _get_users($sql) {

      $dbresult = $GLOBALS[db]->query($sql);

      // TODO add error checking

      while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
         $data[] = $entry;
      }
      $result[msg] = $data;
      return $result;
   }



   /* Cleanup Related */

   function clean_online_status($lifetime="1", $path="/websites/www.distributedcity.com/online_status/") {

      $expiredTime = time() - $lifetime; 
      $d = dir($path);

      while ($entry=$d->read()) {
         if (strstr("_status", $entry)) {
            $filename = $path."/".$entry;

            /* Get the file timestamp */
            $timestamp = fileatime($filename);

            if ($timestamp < $expiredTime) {
               /* Kill Expired Sessions */
               unlink($filename);
            }
         }
      }

      $d->close();

   }




   function get_usernames($user_ids) {

      $user_ids = toolbox::slash($user_ids);

      // Format recipient user_ids for SQL IN statement
      if (count($user_ids) > 1) {
         $recipient_sql_list = "'".implode("', '", $user_ids)."'";
      }elseif(count($user_ids) == 1) {
         $recipient_sql_list = "'".$user_ids[0]."'";
      }

      // SQL: Sent TO this user FROM another user
      $sql = "SELECT username FROM dc_user WHERE id IN ($recipient_sql_list)";

      $dbresult = $GLOBALS[db]->query($sql);

      if ( toolbox::db_error(&$dbresult)) {
         $result[err] = 1;
         $result[msg] = 'Unknown error, could find the recipients usernames.';
      }
      else {

         if (!$dbresult->numRows()) {

            $result[err] = 1;
            $result[msg] = 'No recipients found.';

         }
         else {
            while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
               $data[] = $entry[username];
            }
            $result[msg] = $data;
         }
      }

      return($result);
   }






   function get_topic_name($topic_id) {
      $topic_id = addslashes($topic_id);
      $dbresult = $GLOBALS[db]->query("SELECT name  FROM dc_topics WHERE id='$topic_id'");
      if (!$dbresult->numRows()) {
         return false;
      }
      else {
         while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
            return $entry[name];
         }
      }
   }


   function get_category_info($category_id) {
      static $cache;
      if( isset( $cache[$category_id] ) ) {
         return $cache[$category_id];
      }
      else {
         $safe_category_id = addslashes($category_id);
         $dbresult = $GLOBALS[db]->query("SELECT id, name, description FROM dc_categories WHERE id='$safe_category_id'");
         if (!$dbresult->numRows()) {
            return false;
         }
         else {
            while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
               // Split the category name up, and get the first word for the icon name	 
               $tmp = split('[, ]', $entry[name]);
               $entry[icon] = $tmp[0];

               $cache[$category_id] = $entry;
               return $entry;
            }
         }
      }
   }


   function make_category_icon_name($category_name) {
      // Split the category name up, and get the first word for the icon name	 
      $tmp = split('[, ]', $category_name);
      $icon_name = $tmp[0];
      return $icon_name;
   }



   function get_username($user_id) {
      static $cache;
      if (isset($cache[$user_id]) ) {
         return $cache[$user_id];
      }
      else {
         $topic_id = addslashes($topic_id);
         $dbresult = $GLOBALS[db]->query("SELECT username  FROM dc_user WHERE id='$user_id'");

         if( is_object($dbresult) && get_class($dbresult) !== 'db_error') {
            if (!$dbresult->numRows()) {
               return false;
            }
            else {
               while ($entry = $dbresult->fetchRow(DB_FETCHMODE_ASSOC)) {
                  $cache[$user_id] = $entry[username];
                  return $entry[username];
               }
            }
         }
      }
   }




   function dprint($data) {
      print "<PRE><HR>";
      print_r($data);
      print "<HR></PRE>";

   }




   function parse_config($config_file) {

      if (!$fp = @fopen($config_file, "r")) {
         die( "unable to open site config file" );

         // If there is no config_dir somethings up, we should redirect here to login
         header ("Location: /");

      }
      else {
         while ($line = fgets($fp, 1024)) {
            $line = ereg_replace("#.*$", "", $line);
            list($name, $value) = explode ('=', $line);
            $name = trim($name);
            $value= trim($value);
            if (!empty($name))
               $config[$name] = $value;
         }
         return($config);
      }
   }

   function init_online_status_db() {

      $dbfile = $GLOBALS[config][online_status_db];
      $dbh = dba_open($dbfile, 'w', 'gdbm');
      if( $dbh ) {
         dba_insert('description', 'Distributed City User Online Status DB', $dbh);
	dba_close( $dbh );
      }
   }

   function dba_set($dbfile, $key, $val) {
      $dbh = dba_open($dbfile, 'w', 'gdbm');
      $retval = false;
      if( $dbh ) {
         $retval = dba_replace($key, $val, $dbh);
         dba_close( $dbh );
      }
      return $retval;
   }

   function dba_get($dbfile, $key) {
      $val = null;
      $dbh = dba_open($dbfile, 'w', 'gdbm');
      if( $dbh ) {
         $val = dba_fetch($key, $dbh);
         dba_close( $dbh );
      }
      return $val;
   }

   function dba_delete($dbfile, $key) {
      $retval = false;
      $dbh = dba_open($dbfile, 'w', 'gdbm');
      if( $dbh ) {
         $retval = dba_delete($key, $dbh);
         dba_close( $dbh );
      }
      return $retval;
   }

   function dba_exists($dbfile, $key) {
      $retval = false;
      $dbh = dba_open($dbfile, 'w', 'gdbm');
      if( $dbh ) {
         $retval = dba_exists($key, $dbh);
         dba_close( $dbh );
      }
      return $retval;
   }

   function html_hidden_field_escape( $buf ) {
      // translate newlines and carriage returns into html equivs.
      // it's a nicer way to store in hidden fields.
      $trans = array( "\n" => '&#10;',
                      "\r" => '&#13;');

      return strtr( html::_html_encode( $buf ), $trans);
   }

}

function logout_screen($mode="") {

   $block = yats_define($GLOBALS[templateBase]."master/logout.html");
   if ($mode == "expired") {
      yats_assign($block, array("logout_message" => "Your session has expired. Please login again."));
   }

   echo yats_getbuf($block);
   exit();
}


?>
