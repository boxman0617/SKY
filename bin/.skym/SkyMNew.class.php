<?php
class SkyMNew implements SkyCommand
{
	private $_cli; // For two-way communication

	public function __construct($cli)
	{
		$this->_cli = $cli;
	}

	public function GetShortHelp()
	{
		$help = "#\tskym new MyMigrationName";
		return $help;
	}

	public function GetLongHelp()
	{
		$help = "#\tskym new MyMigrationName\n";
		$help .= "#\t - Will create a new migration file using the 'MyMigrationName'\n";
		$help .= "#\t - as the name of the class. The file name will compose of\n";
		$help .= "#\t - the migration name, and a timestamp formated as so: YYYYMMDDHHMMSS\n";
		$help .= "#\t - So the full name of the migration file will be:\n";
		$help .= "#\t - ".$this->CreateFileName('MyMigrationName');
		return $help;
	}

	private function CreateFileName($name)
	{
		return $name.'_'.date('YmdHis').'.migration.php';
	}

	public function Execute($args = array())
	{
		if(count($args) == 0)
			$this->_cli->ShowError('skym new requires the name of the migration! (Run "skym help new" for more information)');
		$this->_cli->PrintLn('Generating new migration...');
		flush();
		$name = $args[0];
		if(is_dir(SkyDefines::Call('DIR_LIB_MIGRATIONS')))
		{
			$f = fopen(SkyDefines::Call('DIR_LIB_MIGRATIONS').'/'.$this->CreateFileName($name), 'w');
			fwrite($f, "<?php
class ".$name." extends Migration
{
	public function Up()
	{

	}

	public function Down()
	{

	}
}
?>");
			fclose($f);
		}
		$this->_cli->PrintLn('Done!');
	}
}
?>