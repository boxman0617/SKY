<?php
//#############################################
// APP Defines @DoNotEdit
//#############################################
// #App Files
SkyDefines::Define('DIR_APP', SkyDefines::Call('APPROOT').'/app');
SkyDefines::Define('DIR_APP_CONTROLLERS', SkyDefines::Call('APPROOT').'/app/controllers');
SkyDefines::Define('DIR_APP_MAILERS', SkyDefines::Call('APPROOT').'/app/mailers');
SkyDefines::Define('DIR_APP_MODELS', SkyDefines::Call('APPROOT').'/app/models');
SkyDefines::Define('DIR_APP_SERVICES', SkyDefines::Call('APPROOT').'/app/services');
SkyDefines::Define('DIR_APP_VIEWS', SkyDefines::Call('APPROOT').'/app/views');

// #APP Configs Files
SkyDefines::Define('DIR_CONFIGS', SkyDefines::Call('APPROOT').'/configs');

// #APP Lib Files
SkyDefines::Define('DIR_LIB', SkyDefines::Call('APPROOT').'/lib');
SkyDefines::Define('DIR_LIB_PLUGINS', SkyDefines::Call('DIR_LIB').'/plugins');
SkyDefines::Define('DIR_LIB_TASKS', SkyDefines::Call('DIR_LIB').'/tasks');
SkyDefines::Define('DIR_LIB_OBJECTS', SkyDefines::Call('DIR_LIB').'/objects');
SkyDefines::Define('DIR_LIB_MIGRATIONS', SkyDefines::Call('DIR_LIB').'/migrations');
SkyDefines::Define('DIR_LIB_CACHE', SkyDefines::Call('DIR_LIB').'/tmp/cache');

// #APP Log Files
SkyDefines::Define('DIR_LOG', SkyDefines::Call('APPROOT').'/log');
SkyDefines::Define('APP_LOG', SkyDefines::Call('DIR_LOG').'/app.log');
SkyDefines::Define('CORE_LOG', SkyDefines::Call('DIR_LOG').'/core.log');

// #APP Public Files
SkyDefines::Define('DIR_PUBLIC', SkyDefines::Call('APPROOT').'/public');

// #APP Test Files
SkyDefines::Define('DIR_TEST', SkyDefines::Call('APPROOT').'/test');
SkyDefines::Define('DIR_FIXTURES', SkyDefines::Call('APPROOT').'/test/fixtures');

//#############################################
// SkyL::Import() import paths @DoNotEdit
//#############################################
SkyDefines::Define('IMPORT_PATHS', 
    SkyDefines::Call('DIR_APP_CONTROLLERS').';'.
    SkyDefines::Call('DIR_APP_MAILERS').';'.
    SkyDefines::Call('DIR_APP_MODELS').';'.
    SkyDefines::Call('DIR_APP_VIEWS')
);

SkyDefines::Define('LOG_LEVEL_HIGH', 1);
SkyDefines::Define('LOG_LEVEL_MID', 2);
SkyDefines::Define('LOG_LEVEL_LOW', 3);

class AppConfig
{
	private static $_database_envs = array(
		'DEV' => array(),
		'TEST' => array(),
		'PRO' => array()
	);

	public static function DatabaseENV($env, $settings)
	{
		self::$_database_envs[$env] = $settings;
	}

	public static function GetDatabaseSettings()
	{
		return self::$_database_envs[SkyDefines::GetEnv()];
	}

	// #################################################################

	private static $_files_cleanup = array(
		'ENABLED' => true
	);

	public static function EnableFileArrayCleanup($bool)
	{
		self::$_files_cleanup['ENABLED'] = $bool;
	}

	public static function IsFileArrayCleanupEnabled()
	{
		return self::$_files_cleanup['ENABLED'];
	}

	// #################################################################

	private static $_mysql_cache = array(
		'ENABLED' => true
	);

	public static function EnableMySQLQueryCache($bool)
	{
		self::$_mysql_cache['ENABLED'] = $bool;
	}

	public static function IsMySQLCacheEnabled()
	{
		return self::$_mysql_cache['ENABLED'];
	}

	// #################################################################

	private static $_logging = array(
		'LOGGING_ENABLED' => false,
		'LOG_LEVEL' => 1
	);

	public static function EnableLogging($bool)
	{
		self::$_logging['LOGGING_ENABLED'] = $bool;
	}

	public static function SetLoggingLevel($level)
	{
		self::$_logging['LOG_LEVEL'] = $level;
	}

	public static function IsLoggingEnabled()
	{
		return self::$_logging['LOGGING_ENABLED'];
	}

	public static function GetLoggingLevel()
	{
		return self::$_logging['LOG_LEVEL'];
	}

	// #################################################################

	private static $_auth = array(
		'AUTH_MODEL' => null,
		'AUTH_MODEL_USERNAME' => null,
		'AUTH_MODEL_PASSWORD' => null,
		'AUTH_SALT' => null
	);

	public static function AuthModel($value)
	{
		self::$_auth['AUTH_MODEL'] = $value;
	}

	public static function GetAuthModel()
	{
		return self::$_auth['AUTH_MODEL'];
	}

	public static function AuthModelUsername($value)
	{
		self::$_auth['AUTH_MODEL_USERNAME'] = $value;
	}

	public static function GetAuthModelUsername()
	{
		return self::$_auth['AUTH_MODEL_USERNAME'];
	}

	public static function AuthModelPassword($value)
	{
		self::$_auth['AUTH_MODEL_PASSWORD'] = $value;
	}

	public static function GetAuthModelPassword()
	{
		return self::$_auth['AUTH_MODEL_PASSWORD'];
	}

	public static function AuthSalt($value)
	{
		self::$_auth['AUTH_SALT'] = $value;
	}

	public static function GetAuthSalt()
	{
		return self::$_auth['AUTH_SALT'];
	}
}

SkyL::Import(SkyDefines::Call('APPROOT').'/configs/defines.php');
SkyL::Import(SkyDefines::Call('DIR_CONFIGS').'/configure.php');

// #Initialize SKYCORE Configures
SkyL::Import(SkyDefines::Call('SKYCORE_CONFIGS').'/loadcore.php');

if(SkyDefines::Call('ARTIFICIAL_LOAD') === false)
{
	// #initialize Sessions
	Session::getInstance();

	// #Initialize Router
	SkyL::Import(SkyDefines::Call('SKYCORE_CONFIGS').'/router_init.php');
}
?>