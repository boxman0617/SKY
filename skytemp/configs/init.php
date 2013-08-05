<?php
//#############################################
// Start File @DoNotEdit
//#############################################
// #Initialize SKYCORE
require_once(getenv('SKYCORE').'/configs/defines.php');

// #Initialize APP
define('APPROOT', dirname(__FILE__).'/..');
require_once(APPROOT.'/configs/defines.php');
require_once(DIR_CONFIGS.'/configure.php'); 

// #Initialize SKYCORE Configures
require_once(SKYCORE_CONFIGS.'/configure.php');
require_once(SKYCORE_CONFIGS.'/loadcore.php');

// #initialize Sessions
Session::getInstance();

// #Initialize Router
require_once(SKYCORE_CONFIGS.'/router_init.php');
?>