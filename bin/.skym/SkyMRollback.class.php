<?php
class SkyMRollback implements SkyCommand
{
	private $_cli; // For two-way communication

	private static $target;

	public function __construct($cli)
	{
		$this->_cli = $cli;
	}

	public function GetShortHelp()
	{
		$help = "#\tskym rollback ENV\n";
		$help .= "#\tskym rollback ENV YYYYMMDDHHMMSS";
		return $help;
	}

	public function GetLongHelp()
	{
		$help = "#\tskym rollback ENV\n";
		$help .= "#\t - Will run all migrations in order of timestamp that have been ran\n";
		$help .= "#\t - and that have not yet been rolled back for the specific environment\n";
		$help .= "#\n";
		$help .= "#\tskym rollback ENV YYYYMMDDHHMMSS\n";
		$help .= "#\t - Will run all migrations in order of timestamp that have been ran\n";
		$help .= "#\t - up to the point specified and that have not yet been rolled back\n";
		$help .= "#\t - for the specific environment.\n";
		$help .= "#\n";

		$help .= "#\t(Note) The above commands will essentially call the migration's ::Down() method.";
		return $help;
	}

	public function Execute($args = array())
	{
		SkyL::Import(SkyDefines::Call('MIGRATION_CLASS'));
		SkyDefines::SetEnv($args[0]);

		$num = count($args);
		if($num == 1)
			$this->RunRollbacksForEnv();
		elseif($num == 2)
			$this->RunRollbacksForEnvAndTarget($args[1]);
	}

	private function RunRollbacks($migrations)
	{
		$ran_count = 0;
		foreach($migrations as $migration)
		{
			SkyL::Import(SkyDefines::Call('DIR_LIB_MIGRATIONS').'/'.$migration);
			$this->_cli->PrintLn('#=> '.$migration);
			flush();
			$tmp = explode('_', $migration);
			$class = $tmp[0];

			$mObj = new $class($this->_cli->GetMySQLConnection(SkyDefines::GetEnv()));
			try {
				$mObj->Down();
			} catch(Exception $e) {
				$this->_cli->ShowError('#!!! Was unable to complete migrations due to unexpected error.');
			}

			MigrationLog::MarkAsRolled($migration);
			$this->_cli->PrintLn('#=== Complete!');
			$ran_count++;
		}
		return $ran_count;
	}

	private function LoadRollbacksToRun(callable $filter)
	{
		$migrations = array_values(array_filter(MigrationLog::GetMigrated(), $filter));
		
		$about_to_run = count($migrations);
		if($about_to_run == 0)
		{
			$this->_cli->PrintLn('# No rollbacks to run...');
			exit();
		} else {
			$this->_cli->PrintLn('# About to roll back ['.$about_to_run.'] migrations(s)');
			$this->_cli->PrintLn('#');
		}

		$this->_cli->PrintLn('# Rolling back...');
		$ran_count = $this->RunRollbacks($migrations);

		$this->_cli->PrintLn('# Rolled back ['.$ran_count.'/'.$about_to_run.'] migration(s)!');
	}

	private function RunRollbacksForEnv()
	{
		$this->LoadRollbacksToRun(function($m){
			return true;
		});
	}

	private function RunRollbacksForEnvAndTarget($target)
	{
		self::$target = $target;
		$this->LoadRollbacksToRun(function($m){
			return SkyMRollback::CheckIfGreaterThenTarget($m);
		});
	}

	public static function CheckIfGreaterThenTarget($m)
	{
		$t = explode('_', $m);
		$m = explode('.', $t[1]);
		$target = strtotime(self::$target);
		$migration = strtotime($m[0]);
		return ($migration >= $target);
	}
}
