<?php
$app->render_header($templateBase,"Services");

//$content[context] = "frontpage";
$content[left]    = "blocks/services/sidebar_left.php";
$content[center]  = "blocks/services/info.php"; 
$content[right]   = "blocks/services/sidebar_right.php";

include("blocks/master/content_3_column.php");

$app->render_footer($templateBase);
?>
