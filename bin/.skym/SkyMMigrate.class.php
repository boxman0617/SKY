<?php
class SkyMMigrate implements SkyCommand
{
	private $_cli; // For two-way communication

	private static $target;

	public function __construct($cli)
	{
		$this->_cli = $cli;
	}

	public function GetShortHelp()
	{
		$help = "#\tskym migrate ENV\n";
		$help .= "#\tskym migrate ENV YYYYMMDDHHMMSS";
		return $help;
	}

	public function GetLongHelp()
	{
		$help = "#\tskym migrate ENV\n";
		$help .= "#\t - Will run all migrations in order of timestamp that have not yet been ran\n";
		$help .= "#\t - for the specified environment.\n#\n";
		$help .= "#\tskym migrate ENV YYYYMMDDHHMMSS\n";
		$help .= "#\t - Will run all migrations in order of timestamp that have not yet been ran\n";
		$help .= "#\t - up to the point specified for the specified environment.\n#\n";
		$help .= "#\t(Note) The above commands will essentially call the migration's ::Up() method.";
		return $help;
	}

	public function Execute($args = array())
	{
		SkyL::Import(SkyDefines::Call('MIGRATION_CLASS'));
		$num = count($args);
		if($num == 1)
			$this->RunMigrationsForEnv($args[0]);
		elseif($num == 2)
			$this->RunMigrationsForEnvAndTarget($args[0], $args[1]);
	}

	private function RunMigrations($migrations, $env, $log)
	{
		$ran_count = 0;
		foreach($migrations as $migration)
		{
			SkyL::Import(SkyDefines::Call('DIR_LIB_MIGRATIONS').'/'.$migration);
			$this->_cli->PrintLn('#=> '.$migration);
			flush();
			$tmp = explode('_', $migration);
			$class = $tmp[0];

			$mObj = new $class($this->_cli->GetMySQLConnection($env));
			try {
				$mObj->Up();
			} catch(Exception $e) {
				$this->_cli->PrintLn('#!!! Was unable to complete migrations. SKipping...');
				continue;
			}
			
			$log['ran'][$env][] = $migration;
			if(array_key_exists($env, $log['rolled']) && in_array($migration, $log['rolled'][$env]))
				unset($log['rolled'][$env][array_search($migration, $log['rolled'][$env])]);
			$this->_cli->PrintLn('#=== Complete!');
			$ran_count++;
			$this->_cli->WriteToMigrationLog($log);
			$log = $this->_cli->ReadFromMigrationLog();
		}
		return $ran_count;
	}

	private function LoadMigrationsToRun($env, callable $filter)
	{
		$log = $this->_cli->ReadFromMigrationLog();
		$migrations = $this->_cli->GetListOfMigrations();
		if(!array_key_exists($env, $log['ran']))
			$log['ran'][$env] = array();
		foreach($migrations as $key => $migration)
		{
			if(in_array($migration, $log['ran'][$env]))
				unset($migrations[$key]);
		}
		$migrations = array_filter($migrations, $filter);
		$migrations = array_values($migrations);
		$about_to_run = count($migrations);
		if($about_to_run == 0)
		{
			$this->_cli->PrintLn('# No migrations to run...');
			exit();
		} else {
			$this->_cli->PrintLn('# About to run ['.$about_to_run.'] migrations(s)');
			$this->_cli->PrintLn('#');
		}

		$this->_cli->PrintLn('# Running migrations...');
		$ran_count = $this->RunMigrations($migrations, $env, $log);

		$this->_cli->PrintLn('# Ran ['.$ran_count.'/'.$about_to_run.'] migration(s)!');
	}

	private function RunMigrationsForEnv($env)
	{
		$this->LoadMigrationsToRun($env, function($m){
			return true;
		});
	}

	private function RunMigrationsForEnvAndTarget($env, $target)
	{
		self::$target = $target;
		$this->LoadMigrationsToRun($env, function($m){
			return SkyMMigrate::CheckIfGreaterThenTarget($m);
		});
	}

	public static function CheckIfGreaterThenTarget($m)
	{
		$t = explode('_', $m);
		$m = explode('.', $t[1]);
		$target = strtotime(self::$target);
		$migration = strtotime($m[0]);
		return ($migration <= $target);
	}
}
?>