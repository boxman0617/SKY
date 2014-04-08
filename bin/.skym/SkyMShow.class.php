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
		$help = "#\tskym show migrated\n";
		$help .= "#\tskym show list\n";
		$help .= "#\tskym show rolled";
		return $help;
	}

	public function GetLongHelp()
	{
		$help = "#\tskym show migrated\n";
		$help .= "#\t - Will show a list of all the migrations that where successfully migrated.\n";
		$help .= "#\n";
		$help .= "#\tskym show list\n";
		$help .= "#\t - Will show a list of all migrations no matter what status.\n";
		$help .= "#\n";
		$help .= "#\tskym show rolled\n";
		$help .= "#\t - Will show a list of all the migrations that where successfully rolled back.";
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
			$this->DisplayMigration($migration);
	}

	private function ExecuteMigrated()
	{
		$this->_cli->ShowBar();
		$this->_cli->PrintLn('# List of all migrated Migrations under ['.SkyDefines::GetEnv().']');
		$this->_cli->ShowBar('=');
		$log = $this->_cli->ReadFromMigrationLog();

		$this->ShowListOf('migrated', '# No migrations have been migrated under the current env.');
	}

	private function ExecuteRolled()
	{
		$this->_cli->ShowBar();
		$this->_cli->PrintLn('# List of all rolled back Migrations under ['.SkyDefines::GetEnv().']');
		$this->_cli->ShowBar('=');
		$log = $this->_cli->ReadFromMigrationLog();

		$this->ShowListOf('rolled', '# No migrations have been rolled back under the current env.');
	}

	private function ShowListOf($of, $failed)
	{
		$migrations = call_user_func('MigrationLog::Get'.ucfirst($of));
		if(empty($migrations))
		{
			$this->_cli->PrintLn($failed);
			return false;
		}

		foreach($migrations as $migration)
			$this->DisplayMigration($migration);
	}

	private function DisplayMigration($migration)
	{
		$t = explode('_', $migration);
		$d = explode('.', $t[1]);
		$this->_cli->PrintLn('# ['.$d[0].'] ('.date('F j, Y g:i A', strtotime($d[0])).') '.$t[0]);
	}
}
?>