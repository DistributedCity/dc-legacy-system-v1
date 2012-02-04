<?php

$app->render_header($templateBase, "Search");

#$content[context] = "blog_admin";
$content[left]    = "blocks/search/sidebar_left.php";
$content[center]  = "blocks/search/main.php"; 
$content[right]   = "blocks/search/sidebar_right.php";

include("blocks/master/content_3_column.php");

$app->render_footer($templateBase);
?>
