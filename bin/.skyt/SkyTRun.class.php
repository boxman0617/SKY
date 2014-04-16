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
			$this->_cli->ShowError('skyt run needs more parameters! (Run "skyt help" for list of commands)');

		$this->tm = new TaskManager();
		$this->tm->Verbose($this->_cli);
		$this->tm->LoadTask($args[0]);
		if($c == 1)
			return $this->RunTask();
		elseif($c == 2)
			return $this->RunTaskMethod($args[1]);
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
?>