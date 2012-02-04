<?php

// url was previously encoded into 'url' get var.
$redirect_url = $HTTP_GET_VARS[url];

header("Location: $redirect_url");

//toolbox::dprint($redirect_url);

?>
