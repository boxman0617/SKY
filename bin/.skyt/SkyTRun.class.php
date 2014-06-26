<?php
SkyL::Import(SkyDefines::Call('TASK_CLASS'));

class SkyTRun implements SkyCommand
{
	private $_cli; // For two-way communication
	private $tm;

	public function __construct($cli)
	{
		$this->_cli = $cli;
	}

	public function GetShortHelp()
	{
		$help = "#\tskyt run TaskName [MethodName]";
		return $help;
	}

	public function GetLongHelp()
	{
		$help = "#\tskyt run TaskName [MethodName]\n";
		$help .= "#\t - This will run the task \"TaskName\", if a\n";
		$help .= "#\t - second parameter is passed that will be the\n";
		$help .- "#\t - individual method in the task that will be called.\n";
		$help .= "#\t - When running a task, all of the associated dependencies\n";
		$help .= "#\t - will run as well.";
		return $help;
	}

	public function Execute($args = array())
	{
		$c = count($args);

		if($c == 0)
			SkyCLI::ShowError('skyt run needs more parameters! (Run "skyt help" for list of commands)');

		if(strpos($args[0], ':') !== false)
		{
			$actions = explode(':', $args[0]);
			unset($args[0]);
		} else {
			$actions = array($args[0]);
			unset($args[0]);
		}

		$this->tm = new TaskManager();
		$this->tm->Verbose($this->_cli);

		if(count($args) > 0)
			$this->tm->Options($args);
		$this->tm->LoadTask($actions[0]);
		if(count($actions) == 1)
			return $this->RunTask();
		elseif(count($actions) == 2)
			return $this->RunTaskMethod($actions[1]);
	}

	private function RunTask()
	{
		$this->tm->Run();
	}

	private function RunTaskMethod($method)
	{
		$this->tm->Run($method);
	}
}
