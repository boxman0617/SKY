<?php
class SKY
{
	public static function Version()
	{
		return trim(file_get_contents(SKYCORE.'/version.info'));
	}

	public static function RCP($src, $dst) 
	{ 
	    $dir = opendir($src); 
	    @mkdir($dst); 
	    while(false !== ($file = readdir($dir)))
	    { 
	        if(($file != '.' ) && ( $file != '..' ))
	        { 
	            if(is_dir($src . '/' . $file))
	                self::RCP($src . '/' . $file,$dst . '/' . $file);
	            else
	                copy($src . '/' . $file,$dst . '/' . $file);
	        }
	    }
	    closedir($dir);
	}

	public static function IsCurl()
	{
		return function_exists('curl_version');
	}

	public static function DownloadFile($file_source, $file_target) 
	{
	    $rh = fopen($file_source, 'rb');
	    $wh = fopen($file_target, 'w+b');
	    if (!$rh || !$wh)
	        return false;

	    echo '[';
	    while (!feof($rh)) 
	    {
	        if (fwrite($wh, fread($rh, 4096)) === FALSE)
	            return false;
	        echo '=';
	        flush();
	    }
	    echo "]\n";

	    fclose($rh);
	    fclose($wh);

	    return true;
	}

	public static function LoadCore($ENV = 'DEV')
	{
		require_once(getenv('SKYCORE').'/configs/defines.php');
		define('APPROOT', getcwd());

		require_once(APPROOT.'/configs/defines.php');
		require_once(DIR_CONFIGS.'/configure.php');

		$GLOBALS['ENV'] = $ENV;

		require_once(SKYCORE_CONFIGS.'/configure.php');
		require_once(SKYCORE_CONFIGS.'/loadcore.php');
	}
}
?>