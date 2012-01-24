<?php
define('DIR_ROOT', dirname(__FILE__));
define('CONTROLLER_DIR', DIR_ROOT.'/../app/controllers');
define('VIEW_DIR', DIR_ROOT.'/../app/views');
define('MODEL_DIR', DIR_ROOT.'/../app/models');
define('MAILER_DIR', DIR_ROOT.'/../app/mailers');

define('CORE_DIR', DIR_ROOT.'/../core');
define('LIBS_DIR', DIR_ROOT.'/../lib');
define('CONFIGS_DIR', DIR_ROOT.'/../configs');
define('OBJECTS_DIR', CORE_DIR.'/objects');
define('PLUGINS_DIR', LIBS_DIR.'/plugins');
define('PRELOADER', CONFIGS_DIR.'/preloader.php');
define('PREIMPORTS', CONFIGS_DIR.'/preimports.php');
define('ROUTES', CONFIGS_DIR.'/routes.php');
define('CONTROLLER_CLASS', CORE_DIR.'/Controller.class.php');
define('MODEL_CLASS', CORE_DIR.'/Model.class.php');
define('FACTORY_CLASS', CORE_DIR.'/Factory.class.php');
define('EVENT_CLASS', CORE_DIR.'/Event.class.php');
define('PLUGINS_CLASS', CORE_DIR.'/Plugin.class.php');
define('ROUTES_CLASS', CORE_DIR.'/Route.class.php');
define('ERROR_CLASS', CORE_DIR.'/Error.class.php');
define('MAILER_CLASS', CORE_DIR.'/Mailer.class.php');
define('SESSION_CLASS', CORE_DIR.'/Session.class.php');
define('TASKS_DIR', LIBS_DIR.'/tasks');

define('SMARTY_LOC', DIR_ROOT.'/../core/smarty');
define('SMARTY_CLASS', SMARTY_LOC.'/Smarty.class.php');
define("SMARTY_TEMPLATE_DIR", VIEW_DIR);
define("SMARTY_COMPILE_DIR", '/tmp/');
define("SMARTY_CONFIG_DIR", CONFIGS_DIR);
define("SMARTY_CACHE_DIR", '/tmp/');

define('LOG_DIR', DIR_ROOT.'/../log');
define('ERROR_LOG_DIR', DIR_ROOT.'/../log/error/');
$_IMPORTS = array();
$f = fopen(LOG_DIR.'/imports.log', 'w');
fclose($f);

function import($path = "")
{
    global $_IMPORTS;
    preg_match('/\/([a-zA-Z\.]+(?:\.php|\.task|\.sky))/', $path, $match);
    if(!isset($_IMPORTS[$match[1]]))
    {
        if(is_file($path))
        {
            $_IMPORTS[$match[1]] = true;
            $f = fopen(LOG_DIR.'/imports.log', 'a');
            fwrite($f, "Included [".microtime(true)."]: ".$match[1]."\n");
            fclose($f);
            require_once($path);
        }
    }
}
?>