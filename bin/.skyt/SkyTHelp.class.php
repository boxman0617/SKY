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
		SkyCLI::ShowBar();
		SkyCLI::PrintLn('# SkyT Help');
		SkyCLI::ShowBar('=');
	}

	private function GeneralHelp()
	{
		$this->HelpHeader();

		SkyCLI::PrintLn('# Usage: skyt command [argument [argument ...]]');
		SkyCLI::PrintLn('#');
		$commands = $this->_cli->GetCommands();
		foreach($commands as $name => $command)
		{
			SkyCLI::ShowBar('-');
			SkyCLI::PrintLn('# Command: '.$name);
			SkyCLI::PrintLn('#');
			SkyCLI::PrintLn($command->GetShortHelp());
		}
		SkyCLI::PrintLn('#');
		SkyCLI::ShowBar();
	}

	private function HelpOn($command)
	{
		$commands = $this->_cli->GetCommands();
		if(array_key_exists($command, $commands))
		{
			$this->HelpHeader();
			SkyCLI::PrintLn('# Help for ['.$command.']');
			SkyCLI::PrintLn('#');
			SkyCLI::PrintLn($commands[$command]->GetLongHelp());
			SkyCLI::PrintLn('#');
			SkyCLI::ShowBar();
		} else {
			SkyCLI::ShowError('Command ['.$this->_command.'] not found! (Run "skyt help" for list of commands)');
		}
	}
}
