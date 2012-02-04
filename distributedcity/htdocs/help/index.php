<?php
$app->render_header($templateBase, "Help");

//$content[context] = "frontpage";
$content[left]    = "blocks/help/sidebar_left.php";
$content[center]  = "blocks/help/faq.php"; 
$content[right]   = "blocks/help/sidebar_right.php";

include("blocks/master/content_3_column.php");

$app->render_footer($templateBase);
?>
