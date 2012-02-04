<?php
$app->render_header($templateBase, "Chat");

$content[context] = "frontpage";
$content[left]    = "blocks/chat/sidebar_left.php";
$content[center]  = "blocks/chat/chat_info.php"; 
$content[right]   = "blocks/chat/sidebar_right.php";

include("blocks/master/content_3_column.php");

$app->render_footer($templateBase);
?>
