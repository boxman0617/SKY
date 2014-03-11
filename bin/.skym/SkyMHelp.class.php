<?php
class SkyMHelp implements SkyMCommand
{
	private $_cli; // For two-way communication

	public function __construct($cli)
	{
		$this->_cli = $cli;
	}

	public function GetShortHelp()
	{
		$help = "#\tskym help\n";
		$help .= "#\tskym help command";
		return $help;
	}

	public function GetLongHelp()
	{
		$help = "#\tskym help\n";
		$help .= "#\t - Shows this help prompt\n#\n";
		$help .= "#\tskym help command\n";
		$help .= "#\t - Shows help prompt for specific command";
		return $help;
	}

	public function Execute($args = array())
	{
		if(count($args) == 0)
			return $this->GeneralHelp();
		$this->HelpOn($args[0]);
	}

	private function HelpHeader()
	{
		$this->_cli->ShowBar();
		$this->_cli->PrintLn('# Skym Help');
		$this->_cli->ShowBar('=');
	}

	private function GeneralHelp()
	{
		$this->HelpHeader();

		$this->_cli->PrintLn('# Usage: skym command [argument [argument ...]]');
		$this->_cli->PrintLn('#');
		$commands = $this->_cli->GetCommands();
		foreach($commands as $name => $command)
		{
			$this->_cli->ShowBar('-');
			$this->_cli->PrintLn('# Command: '.$name);
			$this->_cli->PrintLn('#');
			$this->_cli->PrintLn($command->GetShortHelp());
		}
		$this->_cli->PrintLn('#');
		$this->_cli->ShowBar();
	}

	private function HelpOn($command)
	{
		$commands = $this->_cli->GetCommands();
		if(array_key_exists($command, $commands))
		{
			$this->HelpHeader();
			$this->_cli->PrintLn('# Help for ['.$command.']');
			$this->_cli->PrintLn('#');
			$this->_cli->PrintLn($commands[$command]->GetLongHelp());
			$this->_cli->PrintLn('#');
			$this->_cli->ShowBar();
		} else {
			$this->_cli->ShowError('Command ['.$this->_command.'] not found! (Run "skym help" for list of commands)');
		}
	}
}
?>