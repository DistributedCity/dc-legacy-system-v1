<?php
$app->render_header($templateBase);

$content[context] = "section";
$content[left]    = "blocks/news/sidebar_left.php";
$content[center]  = "blocks/news/summaries.php"; 
$content[right]   = "blocks/news/sidebar_right.php";

include("blocks/master/content_3_column.php");

$app->render_footer($templateBase);
?>