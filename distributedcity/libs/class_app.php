<?php

class app {

  var $session;
  var $user;
  var $news;
  var $chat;
  var $html;
  
  function app($user_id) {
    // Start a new session
    session_id(toolbox::get_random_hash());
    session_start();

    $this->session = new session($user_id);
    $this->user = new user($user_id);
    $this->news = new news($user_id);
    $this->forums = new forums($user_id);
    $this->chat = new chat($user_id);
    $this->html = new html($user_id);
    $this->cache = new cache($user_id);

  }
  
  function quit() {

    // Destroy the session
    session_destroy();
  }



  function render_header($templateBase, $menu_section=""){

    $block = yats_define($templateBase."master/header.html");
    echo yats_getbuf($block); 
    include("blocks/master/menu_top.php");
  }


  function render_footer($templateBase){

    $block = yats_define($templateBase."master/footer.html");    
    echo yats_getbuf($block);
  }



}

?>