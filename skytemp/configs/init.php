<?php
//#############################################
// Start File @DoNotEdit
//#############################################
// #Initialize SKYCORE
require_once(getenv('SKYCORE').'/configs/defines.php');

// #Initialize APP
SkyDefines::Define('APPROOT', realpath(dirname(__FILE__).'/..'));
SkyL::Import(SkyDefines::Call('SKYCORE_CONFIGS').'/app_defines.php');
