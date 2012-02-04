<?php

/*

*/


$block = yats_define($templateBase."help/faq.html");

yats_assign($block, array('app_version_string' => $version->getFullVersion(),
                          'app_version_major'  => $version->getMajor(),
                          'app_version_minor'  => $version->getMinor(),
                          'app_version_date'   => toolbox::make_date($version->getTimeStamp())
                          ));

//yats_assign($block, array("username"    =>  $app->user->get_username()));

echo yats_getbuf($block);

?>

