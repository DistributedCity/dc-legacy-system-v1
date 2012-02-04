<?php

$app->render_header($templateBase, "News");

$content[context] = "blog_admin";
$content[left]    = "blocks/user/sidebar_left.php";
$content[center]  = "blocks/news/summaries.php"; 
$content[right]   = "blocks/user/sidebar_right.php";

include("blocks/master/content_3_column.php");

$app->render_footer($templateBase);
?>
