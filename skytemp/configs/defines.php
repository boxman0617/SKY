<?php
//#############################################
// Enviromet Variable @SafeToEdit
//#############################################
$GLOBALS['ENV'] = 'DEV';

//#############################################
// Base URL Definition @SafeToEdit
//#############################################
define('BASE_GLOBAL_URL', '/');

//#############################################
// APP Defines @DoNotEdit
//#############################################
// #App Files
define('DIR_APP', APPROOT.'/app');
define('DIR_APP_CONTROLLERS', APPROOT.'/app/controllers');
define('DIR_APP_MAILERS', APPROOT.'/app/mailers');
define('DIR_APP_MODELS', APPROOT.'/app/models');
define('DIR_APP_SERVICES', APPROOT.'/app/services');
define('DIR_APP_VIEWS', APPROOT.'/app/views');

// #APP Configs Files
define('DIR_CONFIGS', APPROOT.'/configs');

// #APP Lib Files
define('DIR_LIB', APPROOT.'/lib');
define('DIR_LIB_PLUGINS', DIR_LIB.'/plugins');
define('DIR_LIB_TASKS', DIR_LIB.'/tasks');
define('DIR_LIB_OBJECTS', DIR_LIB.'/objects');
define('DIR_LIB_CACHE', APPROOT.'/lib/tmp/cache');

// #APP Log Files
define('DIR_LOG', APPROOT.'/log');
define('APP_LOG', DIR_LOG.'/app.log');
define('CORE_LOG', DIR_LOG.'/core.log');

// #APP Public Files
define('DIR_PUBLIC', APPROOT.'/public');

// #APP Test Files
define('DIR_TEST', APPROOT.'/test');
define('DIR_FIXTURES', APPROOT.'/test/fixtures');

//#############################################
// import() import paths @DoNotEdit
//#############################################
define('IMPORT_PATHS', 
    DIR_APP_CONTROLLERS.';'.
    DIR_APP_MAILERS.';'.
    DIR_APP_MODELS.';'.
    DIR_APP_VIEWS
);

//#############################################
// USER Defines @SafeToEdit/Add
//#############################################
// If you have any local defines, 
// add them below.
//#############################################

// ## If you have any other IMPORT PATHS you want to add, uncomment the line below
// define('USER_IMPORT_PATHS', '/import_path1;/import_pahth2');

?>