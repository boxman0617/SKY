<?php
class SkyCHelp implements SkyCommand
{
	private $_cli; // For two-way communication

	public function __construct($cli)
	{
		$this->_cli = $cli;
	}

	public function GetShortHelp()
	{
		$help = "#\tsky help\n";
		$help .= "#\tsky help command";
		return $help;
	}

	public function GetLongHelp()
	{
		$help = "#\tsky help\n";
		$help .= "#\t - Shows this help prompt\n#\n";
		$help .= "#\tsky help command\n";
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
		$this->_cli->PrintLn('# SKY Version '.SKY::Version());
		$this->_cli->ShowBar('=');
	}

	private function GeneralHelp()
	{
		$this->HelpHeader();

		$this->_cli->PrintLn('# Usage: sky command [argument [argument ...]]');
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
			$this->_cli->ShowError('Command ['.$this->_command.'] not found! (Run "sky help" for list of commands)');
		}
	}
}
?>