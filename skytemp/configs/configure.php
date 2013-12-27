<?php
// Auth Table/Model
define('AUTH_MODEL', 'users');
define('AUTH_MODEL_USERNAME', 'username');
define('AUTH_MODEL_PASSWORD', 'password');
define('AUTH_SALT', 'SKY');
// Log system options
define('LOGGING_ENABLED', true);
define('TXT_MSG_ENABLED', true);
$GLOBALS['TXT_GROUPS'] = array(
    'default' => array(
        '1231231234' => 'att'
    )
);
// Logging levels
// @level 1 - Most info. Main core mechanics
// @level 2 - Info at major parts of the core
// @level 3 - Startup info for methods
define('LOG_LEVEL', 1);
// Internal Request Caching
define('CACHE_ENABLED', true);
define('PAGE_CACHE_ENABLED', true);

// $_FILES Auto Clean up
define('FILES_CLEANUP_ENABLED', true);

// Development Enviroment [DEV]
$DB['DEV'] = array(
    'MODEL_DRIVER' => 'MySQL',
    'DB_SERVER' => 'localhost',
    'DB_USERNAME' => 'root',
    'DB_PASSWORD' => '',
    'DB_DATABASE' => 'solveit_dev'
);
// Testing Enviroment [TEST]
$DB['TEST'] = array(
    'MODEL_DRIVER' => 'MySQL',
    'DB_SERVER' => 'localhost',
    'DB_USERNAME' => 'root',
    'DB_PASSWORD' => '',
    'DB_DATABASE' => 'sky_test'
);
// Production Enviroment [PRO]
$DB['PRO'] = array(
    'MODEL_DRIVER' => 'MySQL',
    'DB_SERVER' => '',
    'DB_USERNAME' => '',
    'DB_PASSWORD' => '',
    'DB_DATABASE' => 'sky'
);
?>
