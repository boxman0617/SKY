<?php
define('SKYCORE', getenv('SKYCORE'));
// #Defining ROOT dirs
define('SKYCORE_CORE', SKYCORE.'/core' );
define('SKYCORE_CONFIGS', SKYCORE.'/configs' );
define('SKYCORE_LIB', SKYCORE.'/lib' );
define('SKYCORE_BIN', SKYCORE.'/bin' );
define('SKYCORE_SCRIPTS', SKYCORE.'/scripts');
define('SKYCORE_TEST', SKYCORE.'/test');
define('SKYCORE_FIXTURES', SKYCORE.'/test/fixtures');

// #Defining ROOT/CORE dirs
define('SKYCORE_CORE_CONTROLLER', SKYCORE_CORE.'/controller');
define('SKYCORE_CORE_HTML', SKYCORE_CORE.'/html');
define('SKYCORE_CORE_MODEL', SKYCORE_CORE.'/model');
define('SKYCORE_CORE_PLUGIN', SKYCORE_CORE.'/plugin');
define('SKYCORE_CORE_REPORTING', SKYCORE_CORE.'/reporting');
define('SKYCORE_CORE_ROUTER', SKYCORE_CORE.'/router');
define('SKYCORE_CORE_STORAGE', SKYCORE_CORE.'/storage');
define('SKYCORE_CORE_UTILS', SKYCORE_CORE.'/utils');

// #Class Definer Function
function _ClassDefiner($path)
{
    $classes = scandir($path);
    foreach($classes as $class)
    {
        $m = explode('.', $class);
        if(isset($m[1]) && $m[1] == 'class') define(strtoupper($m[0]).'_CLASS', $path.'/'.$class);
    }
}
// #Defining CLASSES
_ClassDefiner(SKYCORE_CORE_CONTROLLER);
_ClassDefiner(SKYCORE_CORE_UTILS);
_ClassDefiner(SKYCORE_CORE_STORAGE);
_ClassDefiner(SKYCORE_CORE_MODEL);
_ClassDefiner(SKYCORE_CORE_ROUTER);
_ClassDefiner(SKYCORE_CORE_REPORTING);
_ClassDefiner(SKYCORE_CORE_PLUGIN);
_ClassDefiner(SKYCORE_CORE_HTML);


// #Define Helper Functions
$GLOBALS['IMPORTS'] = array();
function import($path)
{
    preg_match('/\/([a-zA-Z\.]+(?:\.php|\.task))/', $path, $match);
    if(!isset($GLOBALS['IMPORTS'][$match[1]]))
    {
        if(is_file($path))
        {
            $GLOBALS['IMPORTS'][$match[1]] = true;
            require_once($path);
        }
    }
}

if(!function_exists('date_diff'))
{
    function date_diff($date1, $date2)
    { 
        $current = $date1; 
        $datetime2 = date_create($date2); 
        $count = 0; 
        while(date_create($current) < $datetime2){ 
            $current = gmdate("Y-m-d", strtotime("+1 day", strtotime($current))); 
            $count++; 
        } 
        return $count; 
    } 
}

?>