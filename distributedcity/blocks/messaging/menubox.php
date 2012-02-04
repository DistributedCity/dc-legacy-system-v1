<?php

/*
  Just throws up the menus for:
  - Compose/AddressBook/User Directory
  and
  - Folders: Incoming, Saved, Sent, Deleted
*/

$block = yats_define($templateBase."messaging/menubox.html");


echo yats_getbuf($block);

?>

