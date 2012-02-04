<?php


//Block Rendering Class
//$app->render->

class html {

   var $user_id;

   function html($user_id) {
      $this->user_id = $user_id;
   }   



   function render_user_directory_rows($users, $type) {

      // Get userlist
   #   $result = toolbox::get_profiles_directory($criteria);
   #   $users = $result[msg];

      if (is_array($users) && count($users)) {
         do {
            $html .= "<TR>";

            // Do 5 across
            for ($x=0;$x<5;$x++) {

               $user = current($users);

               $html .= '<td class="formRow" width="20%">';

               if (!empty($user[username])) {

                  if ($type == "profile") {
                     $html .= '<a href="/search/?profile=' . $user[user_id] . '">' . $user[username]. '</a>';

                  }elseif($type == "compose") {
                     $html .= '<a href="/messaging/?action=compose&uid=' . $user[user_id] . '">' . $user[username]. '</a>';

                  }elseif($type == "bloggers") {
                     $html .= '<a href="/news/weblogs/?uid=' . $user[user_id] . '">' . $user[username]. '</a>';
                  }

                  //http://distributedcity.com/news/weblogs/?uid=1850&SID=107c6a463859787c948a146755559cd4


               }
               else {
                  $html .= '&nbsp;';
               }

               $html .= '</td>';
               if ($x<4)
                  next($users);
            }

            $html .= "</TR>";

         }while (next($users));
      }
      return($html);




   }



   function render_comment(&$comment, $comment_mode, $comment_type, $state, $sub_level="off") {
      global $templateBase;

      if ($comment_type == "A") { // Use Article template
         $tpl = new TemplateMgr($templateBase . "news/");
         $tpl->define("comment", "comment_article.html");
      }elseif($comment_type == "F") {
         $tpl = new TemplateMgr($templateBase . "forums/"); 
         $tpl->define("comment", "comment_forum.html");
      }

      if ($comment_mode == "threaded" && $sub_level=="on") {

         $html .= '<LI>'. $comment[subject]  .'</LI>';

      }
      else {


         if (!empty($comment[subject])) {
            $tpl->assign("comment", array("comment_subject"   => $this->dc_encode($comment[subject], "subject")));
         }



         $tpl->assign("comment", array("user_image_src"     => toolbox::get_user_image_src($comment[poster_id]),
         "comment_id"        => $comment[id],
         "comment_parent"    => $comment[parent],
         "comment_poster_username" => $comment[poster_username],
         "comment_poster_id" => $comment[poster_id],
         "comment_body"      => $this->dc_encode($comment[body], "message_body"),
         "comment_date_time" => $comment_type == "F" ? toolbox::make_folder_date($comment[date]) : toolbox::make_date($comment[date]),
         "comment_id"        => $comment[id],
         "row_color"         => $state[row_color_toggle] == 1 ? "#CECECE" : "#DFDFDF"));

         $tpl->assign("comment", array("column_1_width"    => $state[column_1_width] . "%",
         "column_2_width"    => $state[column_2_width] . "%",
         "column_3_width"    => $state[column_3_width] . "%"));

         // Toggle Even/Odd Row
         $state[row_color_toggle] == 1 ? $state[row_color_toggle] = 0 : $state[row_color_toggle] = 1;


         $html .= $tpl->parse_to_string("comment");
      }



      if (!empty($comment[sub_comments])) {

         foreach($comment[sub_comments] as $sub_comment) {

            if ($comment_mode == "nested" || $comment_mode == "threaded") {
               $html .= "<UL>";

               // Increment Indent
               $state[column_2_width] = $state[column_2_width] + 3;
               $state[column_3_width] = $state[column_3_width] - 3;

            }

            if ($comment_mode == "threaded") {

               $data = $this->render_comment($sub_comment, $comment_mode, $comment_type, $state, "on");
               $html .= $data[html];
               $state = $data[state];

            }
            else {

               $data = $this->render_comment($sub_comment, $comment_mode, $comment_type, $state);
               $html .= $data[html];
               $state = $data[state];

            }

            if ($comment_mode == "nested" || $comment_mode == "threaded") {
               $state[column_2_width] = $state[column_2_width] - 3;
               $state[column_3_width] = $state[column_3_width] + 3;
               $html .= "</UL>";
            }
         }
      }

      return(array("html" => $html,
      "state" => $state));
   } /* end function render_comment */



   function render_comments(&$comments, $comment_mode, $comment_type) {

      $state[column_1_width] = 10;
      $state[column_2_width] = 0;
      $state[column_3_width] = 90;

      // Run through and render all the comments
      foreach($comments as $comment) {
         $data = $this->render_comment($comment, $comment_mode, $comment_type, $state);

         $comment_html_block .= $data[html];
         $state    = $data[state];
      }
      return($comment_html_block);
   }



   function dc_encode($data, $block_type="") {

      if (empty($data)) {
         return;
      }
      else {

         // TODO - Make recursive depth of array encode
         if (is_array($data)) {
            foreach( $data as $key => $val ) {
               $$key = $data[$key] = $this->_dc_encode( $val, $block_type ); 
            } 
         }
         else {
            $data = $this->_dc_encode($data, $block_type);
         }
         return $data;
      }
   }


   function make_display(&$text) {

      $allowed = array( '[b]' => '<b>',                     '[/b]' => '</b>', 
                        '[i]' => '<i>',                     '[/i]' => '</i>',
                        '[u]' => '<u>',                     '[/u]' => '</u>',
                        '[blockquote]' => '<blockquote>',   '[/blockquote]' => '</blockquote>',
                        '[quote]' => '<span class="quote">', '[/quote]' => '</span>',
                                                            '[/url]' => '</a>',
                        '[pre]' => '<pre>',                 '[/pre]' => '</pre>',
                      );

      $map = array();
      foreach( $allowed as $key => $val) {
         $map[$key] = $val;
         $map[strtoupper($key)] = $val;
      }

      $text = strtr($text, $map);


      $toked = explode('[url', $text);
      if( count( $toked ) > 1 ) {
         $text = '';
         foreach( $toked as $str) {
            $a_end = strpos( $str, '</a>' );

            if( $a_end ) {
               $url = null;
               switch( $str[0] ) {
               case '=':
                  $url_end = strpos( $str, ']');
                  $url = substr( $str, 1, $url_end - 1);
                  $name = substr( $str, $url_end + 1, $a_end - ($url_end + 1) );
                  break;
               case ']':
                  $url = substr( $str, 1, $a_end - 1 );
                  $name = $url;
                  break;
               }
               $url = urlencode( $this->_html_decode( $url ) );
               $text .= "<a href='/redirect.php?url=$url' target='_new' class='aUser'>$name";
               $text .= substr( $str, $a_end );
            }
            else {
               $text .= $str;
            }
         }
      }
   }

   function _html_encode($str) {
      return htmlentities($str, ENT_QUOTES, 'utf-8');
   }

   function _html_decode($str) {
      $trans = get_html_translation_table (HTML_ENTITIES, ENT_QUOTES );
      $trans = array_flip($trans);
      return strtr ($str, $trans);
   }

   // Display Format
   function _dc_encode($text, $block_type, $emot=true) {
      // echo "text: --- $text ---";

      if( $block_type != 'gpg_data' ) {
         $toked = explode('[php]', $text);
         if( count( $toked ) > 1 ) {
            $text = '';
            foreach( $toked as $str) {
               $end = strpos( $str, '[/php]' );

               if( $end ) {
                  $php_str = substr($str, 0, $end);

                  ob_start();
                  highlight_string( $php_str );
                  $text .= ob_get_contents();
                  ob_end_clean();

                  $text .= $this->_html_encode( substr( $str, $end + 7 ) );
               }
               else {
                  $text .= $this->_html_encode( $str );
               }
            }
         }
         else {
          $text = $this->_html_encode( $text );
         }
      }
      else {
         $text = $this->_html_encode($text);
         $emot = false;
         $pre = true;
      }

      if ($block_type == "message_body" || $block_type == "profile") {

         // Make safe and format [tags]
         $this->make_display($text);

         if ($block_type != "profile") {
            // Replace all \n\n with <P>
            $text = "<P>" . $text;
         }

         // this 
         $toked = explode('<pre>', $text);
         if( count( $toked ) > 1) {
            $text = '';
            foreach( $toked as $str ) {
               $end = strpos( $str, '</pre>');
               if( $end ) {
                  $pre_str = substr( $str, 0, $end);
                  $leftover = nl2br( substr( $str, $end + strlen('</pre>') ) );

                  $text .= '<pre>' . $pre_str . '</pre>' . $leftover;
               }
               else {
                  $text .= nl2br( $str );
               }
            }
         }
         else {
            $text = nl2br( $text );
         }
      }

      if ($emot) {
         // Parse and make emoticons
         $this->make_emoticons($text);
      }
      if( $pre ) {
         $text = "<pre>$text</pre>";
      }

      return $text;
   }



   function make_emoticons(&$text) {

      // Parse emoticons
      $emoticons_data = file($GLOBALS[config][config_dir]."emoticons.cfg");

      foreach($emoticons_data as $emoticon_data) {
         $data = explode("|", $emoticon_data);
         $emoticons[$data[0]] = array("image"       => $data[1],
         "description" => $data[2]);
      }
      foreach($emoticons as $icon_code => $icon_info) {
         $text = str_replace($icon_code, '<IMG SRC="/images/emoticons/'.$icon_info[image].'" border="0" align="abs_middle" width="15" height="15">', $text);
      }

   }


   // Use this to get/assign to template keyinfo for viewing
   // users key or others key, same code, so better for a function
   function assign_block_all_key_info(&$app, &$block, $keyid="") {

      // Get key info
      $result = $app->user->gpg->get_all_key_info($keyid);
      if ($result[err]) {

         // If error, then render the error message
         $error = $result[msg][0];

      }
      else {
         $all_key_info = $result[msg];
      }


      if ($error) {

         $result = $app->user->gpg->get_ce_status($app->user->get_user_id());
         if (!$result[err]) {
            $ce_status = $result[msg];
         }

         yats_assign($block, array("big_problem_message" => $error,
         "ce_status_keys_in_queue" => $ce_status["queue_total"],
         "ce_status_your_key_in_queue_flag" => $ce_status["key_found"],
         "ce_status_your_key_in_queue_position" => $ce_status["key_position"] ? $ce_status["key_position"] : "---"));

         yats_hide($block, "public_key", true);


      }
      else {

         // Format some things
         // HTML Display format for the userid
         $key_info['user-id']   = $this->_html_encode($all_key_info[pubkey]['user-id']);
         $key_info[fingerprint] = $all_key_info[pubkey][fingerprint];
         $key_info[keyid]       = $all_key_info[pubkey][keyid];

         $key_info[length]      = $all_key_info[sub_keys][0][length] ? $all_key_info[pubkey][length] ."/". $all_key_info[sub_keys][0][length] : $all_key_info[pubkey][length];
         $key_info[algorithm]   = $all_key_info[sub_keys][0][algorithm] ? $all_key_info[pubkey][algorithm] ."/". $all_key_info[sub_keys][0][algorithm] : $all_key_info[pubkey][algorithm]; 

         $key_info[expiration]  = $all_key_info[pubkey][expiration] ? $all_key_info[pubkey][expiration] : "---";
         $key_info[creation]    = $all_key_info[pubkey][creation];
         $key_info[keyblock]    = $all_key_info[keyblock];
         yats_assign($block, $key_info);
      }

      if ($error) {
         return false;
      }
      else {
         return true;
      }
   }


}
?>
