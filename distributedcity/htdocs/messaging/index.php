<?php
$app->render_header($templateBase, "Messaging");

//$content[context] = "folder";
$content[left]    = "blocks/messaging/sidebar_left.php";
$content[center]  = "blocks/messaging/messaging.php"; 
$content[right]   = "blocks/messaging/sidebar_right.php";

include("blocks/master/content_3_column.php");

$app->render_footer($templateBase);
?>
