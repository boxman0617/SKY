<?php
class SkyDefines
{
    private static $_defines = array();
    private static $_ENV = null;
    private static $_core_dirs = array();

    public static function AddCoreDir($name, $path)
    {
        self::$_core_dirs[$name] = $path;
        self::Define($name, $path);
    }

    public static function DefineClasses()
    {
        foreach(self::$_core_dirs as $path)
        {
            $classes = scandir($path);
            foreach($classes as $class)
            {
                $m = explode('.', $class);
                if(isset($m[1]) && $m[1] == 'class')
                    SkyDefines::Define(strtoupper($m[0]).'_CLASS', $path.'/'.$class);
            }
        }
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
require_once(SkyDefines::Call('SKYCORE').'/core/main/Base.class.php');
require_once(SkyDefines::Call('SKYCORE').'/core/reporting/Benchmark.class.php');
Benchmark::Start();
// #Defining ROOT dirs
SkyDefines::Define('SKYCORE_CORE', SkyDefines::Call('SKYCORE').'/core' );
SkyDefines::Define('SKYCORE_CONFIGS', SkyDefines::Call('SKYCORE').'/configs' );
SkyDefines::Define('SKYCORE_LIB', SkyDefines::Call('SKYCORE').'/lib' );
SkyDefines::Define('SKYCORE_LIB_PLUGINS', SkyDefines::Call('SKYCORE_LIB').'/plugins' );
SkyDefines::Define('SKYCORE_BIN', SkyDefines::Call('SKYCORE').'/bin' );
SkyDefines::Define('SKYCORE_SCRIPTS', SkyDefines::Call('SKYCORE').'/scripts');
SkyDefines::Define('SKYCORE_TEST', SkyDefines::Call('SKYCORE').'/test');
SkyDefines::Define('SKYCORE_FIXTURES', SkyDefines::Call('SKYCORE').'/test/fixtures');

// #Defining ROOT/CORE dirs
if($handle = opendir(SkyDefines::Call('SKYCORE_CORE')))
{
    while(false !== ($entry = readdir($handle)))
    {
        if($entry != "." && $entry != "..")
            SkyDefines::AddCoreDir('SKYCORE_CORE_'.strtoupper($entry), SkyDefines::Call('SKYCORE_CORE').'/'.$entry);
    }
    closedir($handle);
}
SkyDefines::DefineClasses();

class SkyL
{
    private static $_imported = array();

    public static function Import($path)
    {
        if(!in_array($path, self::$_imported))
        {
            if(is_file($path))
                return self::_Import($path);

            if(strpos($path, 'service.') !== false)
                return self::_ImportService($path);

            if(self::_ImportFromUserPaths($path) === false)
              throw new ImportException('Unable to import ['.$path.']');
        }
    }

    private static function _Import($file)
    {
        require_once($file);
        self::$_imported[] = $file;
        return true;
    }

    private static function _ImportService($name)
    {
        $broken = explode('.', $name);
        $base = SkyDefines::Call('DIR_APP_SERVICES').'/'.$broken[0].'.'.$broken[1];
        if(is_dir($base))
        {
            $src_dir = $base.'/src/'.SKY::pluralize($broken[2]);
            if(is_dir($src_dir))
            {
                $file = $src_dir.'/'.$broken[3].'.'.$broken[2].'.php';
                if(is_file($file))
                {
                    return self::_Import($file);
                }
            }
        }
    }

    private static function _ImportUsingPathArray($paths, $name)
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
                        if($INFO['filename'] == $name)
                        {
                            closedir($dh);
                            return self::_Import($dir.'/'.$name.'.'.$INFO['extension']);
                        }
                    }
                    closedir($dh);
                }
            }
        }
        return false;
    }

    private static function _ImportFromUserPaths($name)
    {
        $paths = explode(';', SkyDefines::Call('IMPORT_PATHS'));
        if(self::_ImportUsingPathArray($paths, $name) === false)
        {
            try {
                $paths = explode(';', SkyDefines::Call('USER_IMPORT_PATHS'));
                return self::_ImportFromUserPaths($paths, $name);
            } catch(Exception $e) {
                return false;
            }
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
SkyDefines::Define('ARTIFICIAL_LOAD', false);
Benchmark::Mark('END_SKYCORE_DEFINES');
