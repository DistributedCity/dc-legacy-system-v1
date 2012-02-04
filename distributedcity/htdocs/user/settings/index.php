<?php

$app->render_header($templateBase, "Settings");

//$content[context] = "news";
//$content[context] = "settings";

$content[left]    = "blocks/user/settings/sidebar_left.php";
$content[center]  = "blocks/user/settings/settings.php"; 
$content[right]   = "blocks/user/sidebar_right.php";

include("blocks/master/content_3_column.php");

$app->render_footer($templateBase);
?>
