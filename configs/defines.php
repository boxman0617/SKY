<?php
// Enviroment
$GLOBALS['ENV'] = 'DEV';
define('DIR_ROOT', dirname(__FILE__));
define('CONTROLLER_DIR', DIR_ROOT.'/../app/controllers');
define('VIEW_DIR', DIR_ROOT.'/../app/views');
define('MODEL_DIR', DIR_ROOT.'/../app/models');
define('MAILER_DIR', DIR_ROOT.'/../app/mailers');
define('CORE_DIR', DIR_ROOT.'/../core');
define('LIBS_DIR', DIR_ROOT.'/../lib');
define('TESTS_DIR', DIR_ROOT.'/../test');
define('CONFIGS_DIR', DIR_ROOT.'/../configs');
define('OBJECTS_DIR', CORE_DIR.'/objects');
define('PLUGINS_DIR', LIBS_DIR.'/plugins');
define('TASKS_DIR', LIBS_DIR.'/tasks');

define('PRELOADER', CONFIGS_DIR.'/preloader.php');
define('PREIMPORTS', CONFIGS_DIR.'/preimports.php');
define('ROUTES', CONFIGS_DIR.'/routes.php');

$core_classes = scandir(CORE_DIR);
foreach($core_classes as $class)
{
    $m = explode('.', $class);
    if(isset($m[1]) && $m[1] == 'class') define(strtoupper($m[0]).'_CLASS', CORE_DIR.'/'.$class);
}

define('LOG_DIR', DIR_ROOT.'/../log');
define('ERROR_LOG_DIR', DIR_ROOT.'/../log/error/');
$GLOBALS['IMPORTS'] = array();
$f = fopen(LOG_DIR.'/imports.log', 'w');
fclose($f);

function import($path = "")
{
    preg_match('/\/([a-zA-Z\.]+(?:\.php|\.task|\.sky))/', $path, $match);
    if(!isset($GLOBALS['IMPORTS'][$match[1]]))
    {
        if(is_file($path))
        {
            $GLOBALS['IMPORTS'][$match[1]] = true;
            $f = fopen(LOG_DIR.'/imports.log', 'a');
            fwrite($f, "Included [".microtime(true)."]: ".$match[1]."\n");
            fclose($f);
            require_once($path);
        }
    }
}
?>