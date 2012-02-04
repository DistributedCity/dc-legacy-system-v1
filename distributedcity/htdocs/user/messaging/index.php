<?php
include("news/menu_top.php");
?>

<table border="1" width="100%">
<TR valign="top">
  <TD width="100">
   <?php include("user/userbox.php"); ?>
   <?php //include("news/sidebar_left.php"); ?>
  </TD>


  <TD>
   <?php 
    $context = "blog";
    include("news/news.php"); 
   ?>
  </TD>

  <TD width="100"><?php //include("news/sidebar_right.php"); ?></TD>
</TR>

<TR>
  <TD>----------</TD>
  <TD>----------</TD>
  <TD>----------</TD>  
</TR>
</TABLE>
