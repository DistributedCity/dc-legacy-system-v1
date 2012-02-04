<?php

/*
  Shutdown the system. This file is last every hit
  1) Render the footer

*/

// Close out any comms to db
$GLOBALS[db]->disconnect();
if($app)
  session_register('app');



?>