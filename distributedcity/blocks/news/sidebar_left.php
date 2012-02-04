<?php


if($content[context] == "weblog"){
  include("blocks/news/top_blogs_box.php");

  if($HTTP_GET_VARS[uid]){
    include("blocks/news/older_stuff_box.php");
  }else{
    include("blocks/news/recent_blogs_box.php");
  }
  
}else{
  include("blocks/news/menubox.php");
  include("blocks/news/older_stuff_box.php");
}
?>