<?php

/*
  Other Services Screens
*/


$screen = substr($HTTP_GET_VARS[screen],0,25);

// Set default page to dc project

if(!$screen)
     $screen = "dcp";

switch($screen){
   
 case "lp":
   $tpl = "lp";
   break;

 case "books":
   $tpl = "books";
   break;

 case "dcp":
   $tpl = "dcp";
   break;
   
 case "dmt":
   $tpl = "dmt";
   break;
   
 case "int":
   $tpl = "int";
   break;
   
}

$block = yats_define($templateBase."services/".$screen.".html");

if ($screen == 'dcp') {
yats_assign($block, array('app_version_string' => $version->getFullVersion(),
                          'app_version_major'  => $version->getMajor(),
                          'app_version_minor'  => $version->getMinor(),
                          'app_version_date'   => toolbox::make_date($version->getTimeStamp()),
                          'app_version_cvs_tag'=> 'release_' . str_replace('.', '_', $version->getFullVersion()),
                          ));

   $uri_base = $config['app_base'] . '/htdocs/downloads/release/';
   if ($dir = @opendir($uri_base)) {
     while (($file = readdir($dir)) !== false) {
        if( strstr($file, '.tar.gz')) {
           $file_list[] = $file;
        }
     }
     closedir($dir);
     usort($file_list, 'file_cmp');
     yats_assign($block, array('release_ver' => $file_list));
   }
   
   $uri_base = $config['app_base'] . '/htdocs/downloads/nightly/';
   if ($dir = @opendir($uri_base)) {
      while (($file = readdir($dir)) !== false) {
         if( strstr($file, '.tar.gz')) {
            $file_list[] = $file;
         }
      }
      closedir($dir);
      usort($file_list, 'file_cmp');
      yats_assign($block, array('nightly_ver' => $file_list));
   }
}

//yats_assign($block, array("username"    =>  $app->user->get_username()));

echo yats_getbuf($block);

function file_cmp($a, $b) {
   $parts_a = explode('.', $a);
   $parts_b = explode('.', $b);

   if($parts_a[2] == $parts_b[2]) {
      return 0;
   }
   return $parts_a[2] < $parts_b[2] ? 1 : -1;
}

?>