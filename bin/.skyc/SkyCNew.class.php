<?php
class SkyCNew implements SkyCommand
{
	private $_cli; // For two-way communication

	public function __construct($cli)
	{
		$this->_cli = $cli;
	}

	public function GetShortHelp()
	{
		$help = "#\tsky new appname";
		return $help;
	}

	public function GetLongHelp()
	{
		$help = "#\tsky new appname\n";
		$help .= "#\t - Will create a new app directory using the 'appname'\n";
		$help .= "#\t - as the name of the directory where all the required\n";
		$help .= "#\t - files and directories for your new app will live.";
		return $help;
	}

	public function Execute($args = array())
	{
		if(count($args) == 0)
			$this->_cli->ShowError('sky new requires the name of the new app! (Run "sky help new" for more information)');
		flush();
		$name = $args[0];

		$app = getcwd().'/'.$name;
		SKY::RCP(SkyDefines::Call('SKYCORE').'/skytemp', $app);
		$this->_cli->ShowBar();

		$dirs = scandir(SkyDefines::Call('SKYCORE').'/skytemp');
		$this->_cli->PrintLn('Creating new SKY app:');
		foreach($dirs as $d)
		{
			if(is_dir(SkyDefines::Call('SKYCORE').'/skytemp/'.$d) && $d != '.' && $d != '..')
			{
				$this->_cli->PrintLn(" => ".$d);
				$inner = scandir(SkyDefines::Call('SKYCORE').'/skytemp/'.$d);
				foreach ($inner as $inner_d) 
				{
					if(is_dir(SkyDefines::Call('SKYCORE').'/skytemp/'.$d.'/'.$inner_d) && $inner_d != '.' && $inner_d != '..')
						$this->_cli->PrintLn("\t => ".$d.'/'.$inner_d);
				}
			}
		}

		$htaccess = file_get_contents($app.'/.htaccess');
		$htaccess = preg_replace('/(SetEnv SKYCORE\s+)(.+)/', '$1'.SkyDefines::Call('SKYCORE'), $htaccess);
		file_put_contents($app.'/.htaccess', $htaccess);

		$f = fopen($app.'/.skycore', 'w');
		fwrite($f, 'Generated with Sky version ['.SKY::Version().']');
		fclose($f);
		$this->_cli->ShowBar('=');
		$this->_cli->PrintLn('# Success!');
		$this->_cli->ShowBar();
		chmod($app.'/log', 0777);
	}
}
?>