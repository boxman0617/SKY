<?php
class SkyMRollback implements SkyCommand
{
	private $_cli; // For two-way communication

	public function __construct($cli)
	{
		$this->_cli = $cli;
	}

	public function GetShortHelp()
	{
		$help = "#\tskym rollback ENV\n";
		$help .= "#\tskym rollback ENV YYYYMMDDHHMMSS";
		$help .= "#\tskym rollback ENV ALL";
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
		$help .= "#\tskym rollback ENV ALL\n";
		$help .= "#\t - Will run all migrations in order of timestamp that have been ran\n";
		$help .= "#\t - no matter if they have been rolled back or not for the specific environment.\n";
		$help .= "#\n";

		$help .= "#\t(Note) The above commands will essentially call the migration's ::Down() method.";
		return $help;
	}

	public function Execute($args = array())
	{
		SkyL::Import(SkyDefines::Call('MIGRATION_CLASS'));
		$num = count($args);
		if($num == 1)
		{
			if($args[0] == 'ALL')
				$this->RunRollbacksForEnvALL();
			else
				$this->RunRollbacksForEnv($args[0]);
		}
		elseif($num == 2)
			$this->RunRollbacksForEnvAndTarget($args[0], $args[1]);
	}

	private function RunRollbacksForEnv($env)
	{
		$log = $this->_cli->ReadFromMigrationLog();
		$migrations = $this->_cli->GetListOfMigrations();
		$migrations = array_reverse($migrations);
		
		if(!array_key_exists($env, $log['ran']))
			$log['ran'][$env] = array();
		if(!array_key_exists($env, $log['rolled']))
			$log['rolled'][$env] = array();
		$need_to_roll = array();
		foreach($migrations as $migration)
		{
			if(in_array($migration, $log['ran'][$env]) && !in_array($migration, $log['rolled'][$env]))
				$need_to_roll[] = $migration;
		}

		$about_to_run = count($need_to_roll);
		if($about_to_run == 0)
		{
			$this->_cli->PrintLn('# No rollbacks to run...');
			exit();
		} else {
			$this->_cli->PrintLn('# About to roll back ['.$about_to_run.'] migrations(s)');
			$this->_cli->PrintLn('#');
		}

		$this->_cli->PrintLn('# Rolling back...');
		$ran_count = 0;
		foreach($need_to_roll as $migration)
		{
			SkyL::Import(SkyDefines::Call('DIR_LIB_MIGRATIONS').'/'.$migration);
			$this->_cli->PrintLn('#=> '.$migration);
			flush();
			$tmp = explode('_', $migration);
			$class = $tmp[0];

			$mObj = new $class($this->_cli->GetMySQLConnection($env));
			$mObj->Down();
			unset($log['ran'][$env][array_search($migration, $log['ran'][$env])]);
			$log['rolled'][$env][] = $migration;
			$this->_cli->PrintLn('#=== Complete!');
			$ran_count++;
		}
		$this->_cli->PrintLn('# Rolled back ['.$ran_count.'/'.$about_to_run.'] migration(s)!');
		$this->_cli->WriteToMigrationLog($log);
	}

	private function RunRollbacksForEnvAndTarget($env, $target)
	{
		
	}
}
?>