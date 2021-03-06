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
			SkyCLI::ShowError('skym show requires 1 more argument! Please run skym help show for more info.');
		if(method_exists($this, 'Execute'.ucfirst($args[0])))
			call_user_func(array($this, 'Execute'.ucfirst($args[0])));
	}

	private function ExecuteList()
	{
		SkyCLI::ShowBar();
		SkyCLI::PrintLn('# List of all Migrations');
		SkyCLI::ShowBar('=');
		$migrations = SkyM::GetListOfMigrations();
		foreach($migrations as $migration)
			$this->DisplayMigration($migration);
	}

	private function ExecuteMigrated()
	{
		SkyCLI::ShowBar();
		SkyCLI::PrintLn('# List of all migrated Migrations under ['.SkyDefines::GetEnv().']');
		SkyCLI::ShowBar('=');

		$this->ShowListOf('migrated', '# No migrations have been migrated under the current env.');
	}

	private function ExecuteRolled()
	{
		SkyCLI::ShowBar();
		SkyCLI::PrintLn('# List of all rolled back Migrations under ['.SkyDefines::GetEnv().']');
		SkyCLI::ShowBar('=');

		$this->ShowListOf('rolled', '# No migrations have been rolled back under the current env.');
	}

	private function ShowListOf($of, $failed)
	{
		$migrations = call_user_func('MigrationLog::Get'.ucfirst($of));
		if(empty($migrations))
		{
			SkyCLI::PrintLn($failed);
			return false;
		}

		foreach($migrations as $migration)
			$this->DisplayMigration($migration);
	}

	private function DisplayMigration($migration)
	{
		$t = explode('_', $migration);
		$d = explode('.', $t[1]);
		SkyCLI::PrintLn('# ['.$d[0].'] ('.date('F j, Y g:i A', strtotime($d[0])).') '.$t[0]);
	}
}
