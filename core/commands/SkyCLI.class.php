<?php
abstract class SkyCLI
{
	private $_commands = array();

	private $_command = null;
	private $_command_args = array();

	private $tool;

	public function __construct($arguments)
	{
		$this->tool = strtolower(get_called_class());
		$this->BootstrapCommands();

		unset($arguments[0]);
		$arguments = array_values($arguments);
		$this->_command = $arguments[0];
		
		if(array_key_exists($this->_command, $this->_commands))
		{
			array_shift($arguments);
			if(count($arguments) > 0)
				$this->_command_args = $arguments;
			$this->ExecuteCommand();
		} else {
			$this->ShowError('Command ['.$this->_command.'] not found! (Run "'.$this->tool.' help" for list of commands)');
		}
	}

	private function ExecuteCommand()
	{
		$this->_commands[$this->_command]->Execute($this->_command_args);
	}

	private function BootstrapCommands()
	{
		$real = SkyDefines::Call('SKYCORE_BIN').'/.'.$this->tool;
		if($handle = opendir($real))
		{
		    while(false !== ($entry = readdir($handle))) 
		    {
		        if($entry != '.' && $entry != '..')
		        {
		        	$class = str_replace('.class.php', '', $entry);
		        	$name = strtolower(str_replace(get_called_class(), '', $class));

		        	SkyL::Import($real.'/'.$entry);
		        	$this->_commands[$name] = new $class($this);
		        }
		    }

		    closedir($handle);
		}
	}

	// ## Helper Methods
	public function GetCommands()
	{
		return $this->_commands;
	}

	public function ShowError($error)
	{
		$this->PrintLn('[ERROR]:: '.$error);
		exit();
	}

	public function ShowBar($char = '#', $multi = 50)
	{
		$this->PrintLn(str_repeat($char, $multi));
	}

	public function PrintLn($str)
	{
		echo $str."\n";
	}
}
?>