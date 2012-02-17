<?php
// Enviroment
define('ENV', 'TEST');

// Development Enviroment [DEV]
$db_dev = array(
    'MODEL_DRIVER' => 'MySQL',
    'DB_SERVER' => '10.10.0.6',
    'DB_USERNAME' => 'root',
    'DB_PASSWORD' => '',
    'DB_DATABASE' => 'ces'
);
// Testing Enviroment [TEST]
$db_test = array(
    'MODEL_DRIVER' => 'MongoDB',
    'DB_SERVER' => 'localhost',
    'DB_USERNAME' => 'root',
    'DB_PASSWORD' => 'vision',
    'DB_DATABASE' => 'test'
);
// Production Enviroment [PRO]
$db_pro = array(
    'MODEL_DRIVER' => 'MySQL',
    'DB_SERVER' => '',
    'DB_USERNAME' => '',
    'DB_PASSWORD' => '',
    'DB_DATABASE' => ''
);

switch (ENV)
{
    case 'DEV':
        define('DB_SERVER', $db_dev['DB_SERVER']);
        define('DB_USERNAME', $db_dev['DB_USERNAME']);
        define('DB_PASSWORD', $db_dev['DB_PASSWORD']);
        define('DB_DATABASE', $db_dev['DB_DATABASE']);
        define('MODEL_DRIVER', $db_dev['MODEL_DRIVER']);
        
        define('ERROR_REPORT_LEVEL', E_ALL);
        define('ERROR_LOG_LEVEL', E_ALL);
        define('ERROR_CRASH_LEVEL', E_ALL ^ E_NOTICE ^ E_WARNING ^ E_USER_NOTICE ^ E_USER_WARNING ^ E_DEPRECATED ^ E_USER_DEPRECATED);
        define('ERROR_PRINT_LEVEL', E_ALL);
        break;
    case 'TEST':
        define('DB_SERVER', $db_test['DB_SERVER']);
        define('DB_USERNAME', $db_test['DB_USERNAME']);
        define('DB_PASSWORD', $db_test['DB_PASSWORD']);
        define('DB_DATABASE', $db_test['DB_DATABASE']);
        define('MODEL_DRIVER', $db_test['MODEL_DRIVER']);
        
        define('ERROR_REPORT_LEVEL', E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_USER_NOTICE);
        define('ERROR_LOG_LEVEL', E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_USER_NOTICE);
        define('ERROR_CRASH_LEVEL', E_ALL ^ E_NOTICE ^ E_WARNING ^ E_USER_NOTICE ^ E_USER_WARNING ^ E_DEPRECATED ^ E_USER_DEPRECATED);
        define('ERROR_PRINT_LEVEL', E_ALL ^ E_NOTICE ^ E_WARNING ^ E_USER_NOTICE ^ E_USER_WARNING ^ E_DEPRECATED ^ E_USER_DEPRECATED);
        break;
    case 'PRO':
        define('DB_SERVER', $db_pro['DB_SERVER']);
        define('DB_USERNAME', $db_pro['DB_USERNAME']);
        define('DB_PASSWORD', $db_pro['DB_PASSWORD']);
        define('DB_DATABASE', $db_pro['DB_DATABASE']);
        define('MODEL_DRIVER', $db_pro['MODEL_DRIVER']);
        
        define('ERROR_REPORT_LEVEL', E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_USER_NOTICE);
        define('ERROR_LOG_LEVEL', E_ALL ^ E_NOTICE ^ E_WARNING ^ E_STRICT);
        define('ERROR_CRASH_LEVEL', E_ALL ^ E_NOTICE ^ E_WARNING ^ E_USER_NOTICE ^ E_USER_WARNING ^ E_DEPRECATED ^ E_USER_DEPRECATED);
        define('ERROR_PRINT_LEVEL', 0);
        break;
}
?>
