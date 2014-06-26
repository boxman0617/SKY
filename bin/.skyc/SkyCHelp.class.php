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
		SkyCLI::ShowBar();
		SkyCLI::PrintLn('# SKY Version '.SKY::Version());
		SkyCLI::ShowBar('=');
	}

	private function GeneralHelp()
	{
		$this->HelpHeader();

		SkyCLI::PrintLn('# Usage: sky command [argument [argument ...]]');
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
			SkyCLI::ShowError('Command ['.$this->_command.'] not found! (Run "sky help" for list of commands)');
		}
	}
}
