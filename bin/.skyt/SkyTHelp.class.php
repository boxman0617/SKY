<?php
class SkyTHelp implements SkyCommand
{
	private $_cli; // For two-way communication

	public function __construct($cli)
	{
		$this->_cli = $cli;
	}

	public function GetShortHelp()
	{
		$help = "#\tskyt help\n";
		$help .= "#\tskyt help command";
		return $help;
	}

	public function GetLongHelp()
	{
		$help = "#\tskyt help\n";
		$help .= "#\t - Shows this help prompt\n#\n";
		$help .= "#\tskyt help command\n";
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
		$this->_cli->PrintLn('# SkyT Help');
		$this->_cli->ShowBar('=');
	}

	private function GeneralHelp()
	{
		$this->HelpHeader();

		$this->_cli->PrintLn('# Usage: skyt command [argument [argument ...]]');
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
			$this->_cli->ShowError('Command ['.$this->_command.'] not found! (Run "skyt help" for list of commands)');
		}
	}
}
?>