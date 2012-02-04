<?php
$app->render_header($templateBase, "Forums");

//$content[context] = "frontpage";
$content[left]    = "blocks/forums/sidebar_left.php";
$content[center]  = "blocks/forums/main.php"; 
$content[right]   = "blocks/forums/sidebar_right.php";

include("blocks/master/content_3_column.php");

$app->render_footer($templateBase);
?>
