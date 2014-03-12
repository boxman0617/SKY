<?php
class DataCache
{
	protected static $CacheDir = '/cache';

	public static function Cache($key, $data, $expire = '1 day')
	{
		$unix_time = time();

		$cacheCreated = false;

		$file = self::GetKeyFile($key);
		if($file !== false)
		{
			if(self::IsCacheExpired($file, $expire))
			{
				self::ClearCache($file);
    			self::CreateCacheFile($key, $data);
    			$cacheCreated = true;
			} else {
				$cacheCreated = true;
			}
		}

		if($cacheCreated === false)
			self::CreateCacheFile($key, $data);
	}

	protected static function IsCacheExpired($file, $expire = '1 day')
	{
		$keyFile = self::ParseKeyFileName($file);

		$expTime = new DateTime();
		$expTime->setTimestamp($keyFile['unixtime']);
		$expTime->add(DateInterval::createFromDateString($expire));

		$now = new DateTime();

		if($now < $expTime)
			return false;
		return true;
	}

	public static function HasCache($key, $expire = '1 day')
	{
		$file = self::GetKeyFile($key);
		if($file === false)
			return false;
		if(self::IsCacheExpired($file, $expire))
		{
			self::ClearCache($file);
			return false;
		}
		return true;
	}

	public static function GetCache($key)
	{
		$file = self::GetKeyFile($key);
		if($file !== false)
			return unserialize(file_get_contents(DIR_APP.self::$CacheDir.'/'.$file));
		throw new Exception('No cache key of that name found ['.$key.']');
	}

	protected static function GetKeyFile($key)
	{
		if($handle = opendir(DIR_APP.self::$CacheDir))
		{
			while(false !== ($entry = readdir($handle)))
			{
			    if($entry != '.' && $entry != '..')
			    {
			    	if(strpos($entry, 'k_'.$key.'.') !== false)
			    	{
			    		closedir($handle);
			    		return $entry;
			    	}
			    }
			}
		}

		closedir($handle);
		return false;
	}

	private static function GetNewFileName($key)
	{
		return 'k_'.$key.'.'.time().'.cache';
	}

	protected static function CreateCacheFile($key, $data)
	{
		file_put_contents(DIR_APP.self::$CacheDir.'/'.self::GetNewFileName($key), serialize($data));
	}

	protected static function ClearCache($keyFile)
	{
		@unlink(DIR_APP.self::$CacheDir.'/'.$keyFile);
	}

	protected static function ParseKeyFileName($keyFile)
	{
		$parts = array();
		preg_match('/k_(.+)\.(\d{10})\.cache/', $keyFile, $matches);
		if(array_key_exists(1, $matches))
			$parts['key'] = $matches[1];
		else
			throw new Exception('No key found in keyfile ['.$keyFile.']');
		if(array_key_exists(2, $matches))
			$parts['unixtime'] = $matches[2];
		else
			throw new Exception('No unixtime found in keyfile ['.$keyFile.']');

		return $parts;
	}
}
?>