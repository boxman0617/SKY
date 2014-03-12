<?php
class SkyMMigrate implements SkyCommand
{
	private $_cli; // For two-way communication

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

	private function RunMigrationsForEnv($env)
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
		$migrations = array_values($migrations);

		$this->_cli->PrintLn('# Running migrations...');
		foreach($migrations as $migration)
		{
			SkyL::Import(SkyDefines::Call('DIR_LIB_MIGRATIONS').'/'.$migration);
			$this->_cli->PrintLn('#=> '.$migration);
			flush();
			$tmp = explode('_', $migration);
			$class = $tmp[0];

			$mObj = new $class($this->_cli->GetMySQLConnection($env));
			$mObj->Up();
			$log['ran'][$env][] = $migration;
			$this->_cli->PrintLn('#=== Complete!');
		}
		$this->_cli->WriteToMigrationLog($log);
	}

	private function RunMigrationsForEnvAndTarget($env, $target)
	{
		
	}
}
?>