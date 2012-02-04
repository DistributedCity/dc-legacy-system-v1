<?php

/*
  Newsfeed RSS/RDF

  TODO: Remove hardcode and make dynamic

*/



if(!$newsfeed) {
  echo 'newsfeed disabled';
}else{

  // init
  unset($item);
  unset($items);
  unset($title);
  unset($link);
  
  require_once("rdf.class.php");
  
  
  $rdf = new fase4_rdf;
  $rdf->use_dynamic_display(true);
  $rdf->parse_RDF($feed[1]);
  
  $items = $rdf->get_array_item();
  
  $rdf->finish();
  
  do{
    $item = current($items);
    $title[] = $item[title];
    $link[]  = $item[link];
  }while(next($items));
}

$block = yats_define($templateBase."news/newsfeed.html");


yats_assign($block,  array("channel_title" => $feed[0]));


yats_assign($block, array("item_title" => $title,
			  "item_link"  => $link));
  

echo yats_getbuf($block);

?>

