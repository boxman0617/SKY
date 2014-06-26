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
		SkyDefines::SetEnv($args[0]);

		$num = count($args);
		if($num == 1)
			$this->RunMigrationsForEnv();
		elseif($num == 2)
			$this->RunMigrationsForEnvAndTarget($args[1]);
	}

	private function RunMigrations($migrations)
	{
		$ran_count = 0;
		foreach($migrations as $migration)
		{
			SkyL::Import(SkyDefines::Call('DIR_LIB_MIGRATIONS').'/'.$migration);
			SkyCLI::PrintLn('#=> '.$migration);
			flush();
			$tmp = explode('_', $migration);
			$class = $tmp[0];

			$mObj = new $class(SkyCLI::GetMySQLConnection(SkyDefines::GetEnv()));
			try {
				$mObj->Up();
			} catch(Exception $e) {
				SkyCLI::ShowError('#!!! Was unable to complete migrations due to unexpected error.');
			}
			
			MigrationLog::MarkAsMigrated($migration);
			SkyCLI::PrintLn('#=== Complete!');
			$ran_count++;
		}
		return $ran_count;
	}

	private function LoadMigrationsToRun(callable $filter)
	{
		$migrations = array_values(array_filter(MigrationLog::GetUnmigrated(), $filter));

		$about_to_run = count($migrations);
		if($about_to_run == 0)
		{
			SkyCLI::PrintLn('# No migrations to run...');
			exit();
		} else {
			SkyCLI::PrintLn('# About to run ['.$about_to_run.'] migrations(s)');
			SkyCLI::PrintLn('#');
		}

		SkyCLI::PrintLn('# Running migrations...');
		$ran_count = $this->RunMigrations($migrations);

		SkyCLI::PrintLn('# Ran ['.$ran_count.'/'.$about_to_run.'] migration(s)!');
	}

	private function RunMigrationsForEnv()
	{
		$this->LoadMigrationsToRun(function($m){
			return true;
		});
	}

	private function RunMigrationsForEnvAndTarget($target)
	{
		self::$target = $target;
		$this->LoadMigrationsToRun(function($m){
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
