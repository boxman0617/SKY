<?php
class SkyMShow implements SkyCommand
{
	private $_cli; // For two-way communication

	public function __construct($cli)
	{
		$this->_cli = $cli;
	}

	public function GetShortHelp()
	{
		$help = "#\tskym show ran\n";
		$help .= "#\tskym show list\n";
		$help .= "#\tskym show failed";
		return $help;
	}

	public function GetLongHelp()
	{
		$help = "#\tskym show ran\n";
		$help .= "#\t - Will show a list of all the migrations that where successfully ran.\n";
		$help .= "#\n";
		$help .= "#\tskym show list\n";
		$help .= "#\t - Will show a list of all migrations no matter what status.\n";
		$help .= "#\n";
		$help .= "#\tskym show failed\n";
		$help .= "#\t - Will show a list of all the migrations that failed.";
		return $help;
	}

	public function Execute($args = array())
	{
		SkyL::Import(SkyDefines::Call('MIGRATION_CLASS'));
		$num = count($args);
		if($num == 0)
			$this->_cli->ShowError('skym show requires 1 more argument! Please run skym help show for more info.');
		if(method_exists($this, 'Execute'.ucfirst($args[0])))
			call_user_func(array($this, 'Execute'.ucfirst($args[0])));
	}

	private function ExecuteList()
	{
		$this->_cli->ShowBar();
		$this->_cli->PrintLn('# List of all Migrations');
		$this->_cli->ShowBar('=');
		$migrations = $this->_cli->GetListOfMigrations();
		foreach($migrations as $migration)
		{
			$t = explode('_', $migration);
			$d = explode('.', $t[1]);
			$this->_cli->PrintLn('# ['.$d[0].'] '.$t[0]);
		}
	}

	private function ExecuteRan()
	{
		$this->_cli->ShowBar();
		$this->_cli->PrintLn('# List of all ran Migrations under ['.SkyDefines::GetEnv().']');
		$this->_cli->ShowBar('=');
		$log = $this->_cli->ReadFromMigrationLog();

		$this->ShowListOf('ran', '# No migrations have been ran under the current env.');
	}

	private function ExecuteFailed()
	{
		$this->_cli->ShowBar();
		$this->_cli->PrintLn('# List of all failed Migrations under ['.SkyDefines::GetEnv().']');
		$this->_cli->ShowBar('=');
		$log = $this->_cli->ReadFromMigrationLog();

		$this->ShowListOf('failed', '# No migrations have failed under the current env.');
	}

	private function ShowListOf($of, $failed)
	{
		$log = $this->_cli->ReadFromMigrationLog();
		
		if(array_key_exists(SkyDefines::GetEnv(), $log[$of]))
		{
			if(count($log[$of][SkyDefines::GetEnv()]) == 0)
			{
				$this->_cli->PrintLn($failed);
				return true;
			}
			foreach($log[$of][SkyDefines::GetEnv()] as $migration)
			{
				$t = explode('_', $migration);
				$d = explode('.', $t[1]);
				$this->_cli->PrintLn('# ['.$d[0].'] '.$t[0]);
			}
		} else {
			$this->_cli->PrintLn($failed);
		}
	}
}
?>