<?php

// Load all includes
//include($config[app_base]."config/app_includes.php");
include("config/app_includes.php");



// Parse SITE CONFIG
// Session ID found, attempt session
session_start();

$config = toolbox::parse_config( $_SERVER['DOCUMENT_ROOT'] . '/../conf/site_config.ini' );
$version = new GenericVersion(null);
$version->parseVersionFile( $config['app_base'] . '/VERSION' );

//toolbox::dprint($config);

// LOAD APP CONFIG (Configuration parameters common to all instances of application)
include_once( $_SERVER['DOCUMENT_ROOT'] . '/../config/app_config.cfg');

// FIX THIS: Template settings should come from raw config, no need to reassign here
// Set template base manually here...
$templateBase = $GLOBALS[config][app_base].'/templates/';

// Init Database Object
$str = "pgsql://" . $config[db_user] . ":" . $config[db_pass] . "@" . $config[db_host] ."/". $config[db_name];
$db = DB::connect( $str );

if ( !is_object($db) || get_class($db) !== 'db_pgsql') {
   // FIXME: proper error page here.  
   echo "fatal error: unable to connect to database.";
}


?>
