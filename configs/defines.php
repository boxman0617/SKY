<?php
class SkyDefines
{
    private static $_defines = array();
    private static $_ENV = null;
    private static $_core_dirs = array();

    public static function AddCoreDir($name, $path)
    {
        self::$_core_dirs[$name] = $path;
    }

    public static function SetEnv($env)
    {
        self::$_ENV = $env;
    }

    public static function GetEnv()
    {
        if(is_null(self::$_ENV))
            throw new Exception('No enviroment has been set yet. SkyCore Error.');
        return self::$_ENV;
    }

    public static function Define($name, $value)
    {
        if(array_key_exists($name, self::$_defines))
            throw new Exception('Cannot overwrite a definition with ::Define(). Please use ::Overwrite() instead.');
        self::$_defines[$name] = $value;
    }

    public static function Overwrite($name, $value)
    {
        if(!array_key_exists($name, self::$_defines))
            throw new Exception('SkyCore has no defined value by the name of ['.$name.'] to overwrite. Please use ::Define() if trying to define a new value.');
        self::$_defines[$name] = $value;
    }

    public static function Call($name)
    {
        if(!array_key_exists($name, self::$_defines))
            throw new Exception('SkyCore has no defined value by the name of ['.$name.']');
        return self::$_defines[$name];
    }
}

SkyDefines::Define('SKYCORE', getenv('SKYCORE'));
require_once(SKYCORE.'/core/main/Base.class.php');
require_once(SKYCORE.'/core/reporting/Benchmark.class.php');
Benchmark::Start();
// #Defining ROOT dirs
SkyDefines::Define('SKYCORE_CORE', SKYCORE.'/core' );
SkyDefines::Define('SKYCORE_CONFIGS', SKYCORE.'/configs' );
SkyDefines::Define('SKYCORE_LIB', SKYCORE.'/lib' );
SkyDefines::Define('SKYCORE_BIN', SKYCORE.'/bin' );
SkyDefines::Define('SKYCORE_SCRIPTS', SKYCORE.'/scripts');
SkyDefines::Define('SKYCORE_TEST', SKYCORE.'/test');
SkyDefines::Define('SKYCORE_FIXTURES', SKYCORE.'/test/fixtures');

// #Defining ROOT/CORE dirs
SkyDefines::Define('SKYCORE_CORE_CONTROLLER', SKYCORE_CORE.'/controller');
SkyDefines::Define('SKYCORE_CORE_DEPLOY', SKYCORE_CORE.'/deploy');
SkyDefines::Define('SKYCORE_CORE_HTML', SKYCORE_CORE.'/html');
SkyDefines::Define('SKYCORE_CORE_MODEL', SKYCORE_CORE.'/model');
SkyDefines::Define('SKYCORE_CORE_PLUGIN', SKYCORE_CORE.'/plugin');
SkyDefines::Define('SKYCORE_CORE_REPORTING', SKYCORE_CORE.'/reporting');
SkyDefines::Define('SKYCORE_CORE_ROUTER', SKYCORE_CORE.'/router');
SkyDefines::Define('SKYCORE_CORE_SERVICES', SKYCORE_CORE.'/services');
SkyDefines::Define('SKYCORE_CORE_STORAGE', SKYCORE_CORE.'/storage');
SkyDefines::Define('SKYCORE_CORE_UTILS', SKYCORE_CORE.'/utils');
SkyDefines::Define('SKYCORE_CORE_IMAGES', SKYCORE_CORE.'/images');
SkyDefines::Define('SKYCORE_CORE_OBJECTS', SKYCORE_CORE.'/objects');

// #Class Definer Function
function _ClassDefiner($path)
{
    $classes = scandir($path);
    foreach($classes as $class)
    {
        $m = explode('.', $class);
        if(isset($m[1]) && $m[1] == 'class') SkyDefines::Define(strtoupper($m[0]).'_CLASS', $path.'/'.$class);
    }
}
// #Defining CLASSES
_ClassDefiner(SKYCORE_CORE_CONTROLLER);
_ClassDefiner(SKYCORE_CORE_DEPLOY);
_ClassDefiner(SKYCORE_CORE_UTILS);
_ClassDefiner(SKYCORE_CORE_STORAGE);
_ClassDefiner(SKYCORE_CORE_MODEL);
_ClassDefiner(SKYCORE_CORE_ROUTER);
_ClassDefiner(SKYCORE_CORE_SERVICES);
_ClassDefiner(SKYCORE_CORE_REPORTING);
_ClassDefiner(SKYCORE_CORE_PLUGIN);
_ClassDefiner(SKYCORE_CORE_HTML);


// #Define Helper Functions
function _import_by_array($myfile, $paths)
{
    foreach($paths as $dir)
    {
        if(is_dir($dir)) 
        {
            if($dh = opendir($dir)) 
            {
                while(($file = readdir($dh)) !== false) 
                {
                    $INFO = pathinfo($file);
                    if($INFO['filename'] == $myfile)
                    {
                        require_once($dir.'/'.$myfile.'.'.$INFO['extension']);
                        return true;
                    }
                }
                closedir($dh);
            }
        }
    }
    return false;
}

$GLOBALS['IMPORTS'] = array();
function import($path)
{
    preg_match('/\/([a-zA-Z\.]+(?:\.php|\.task))/', $path, $match);
    if(array_key_exists(1, $match) && !isset($GLOBALS['IMPORTS'][$match[1]]))
    {
        if(is_file($path))
        {
            $GLOBALS['IMPORTS'][$match[1]] = true;
            require_once($path);
            return true;
        }
    } elseif(strpos($path, 'service.') !== false) {
        $broken = explode('.', $path);
        $base = DIR_APP_SERVICES.'/'.$broken[0].'.'.$broken[1];
        if(is_dir($base))
        {
            $src_dir = $base.'/src/'.SKY::pluralize($broken[2]);
            if(is_dir($src_dir))
            {
                $file = $src_dir.'/'.$broken[3].'.'.$broken[2].'.php';
                if(is_file($file))
                {
                    require_once($file);
                    return true;
                }
            }
        }
    } else {
        $paths = explode(';', IMPORT_PATHS);
        if(_import_by_array($path, $paths) === false)
        {
            if(SkyDefines::Define('USER_IMPORT_PATHS'))
            {
                $paths = explode(';', USER_IMPORT_PATHS);
                return _import_by_array($path, $paths);
            }
        }
        return true;
    }
    return false;
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
Benchmark::Mark('END_SKYCORE_DEFINES');
?>