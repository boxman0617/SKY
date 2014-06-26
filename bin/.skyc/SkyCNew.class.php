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
		$help .= "#\tsky new appname --grunt-less";
		$help .= "#\t - Will do the same as above and also add some node.js tools\n";
		$help .= "#\t - like grunt and less for less development.";
		return $help;
	}

	public function Execute($args = array())
	{
		if(count($args) == 0)
			SkyCLI::ShowError('sky new requires the name of the new app! (Run "sky help new" for more information)');
		flush();
		$name = $args[0];

		$app = getcwd().'/'.$name;
		SKY::RCP(SkyDefines::Call('SKYCORE').'/skytemp', $app);
		SkyCLI::ShowBar();

		$dirs = scandir(SkyDefines::Call('SKYCORE').'/skytemp');
		SkyCLI::PrintLn('Creating new SKY app:');
		foreach($dirs as $d)
		{
			if(is_dir(SkyDefines::Call('SKYCORE').'/skytemp/'.$d) && $d !== '.' && $d !== '..' && $d[0] !== '.')
			{
				SkyCLI::PrintLn(" => ".$d);
				$inner = scandir(SkyDefines::Call('SKYCORE').'/skytemp/'.$d);
				foreach ($inner as $inner_d)
				{
					if(is_dir(SkyDefines::Call('SKYCORE').'/skytemp/'.$d.'/'.$inner_d) && $inner_d != '.' && $inner_d != '..')
						SkyCLI::PrintLn("\t => ".$d.'/'.$inner_d);
				}
			}
		}

		$htaccess = file_get_contents($app.'/.htaccess');
		$htaccess = preg_replace('/(SetEnv SKYCORE\s+)(.+)/', '$1'.SkyDefines::Call('SKYCORE'), $htaccess);
		file_put_contents($app.'/.htaccess', $htaccess);

		if(isset($args[1]))
		{
			// If grunt-less options is set, create node.js dev tools for less
			if($args[1] === '--grunt-less')
			{
				$this->GruntLess($app, $name);
			}
		}

		$f = fopen($app.'/.skycore', 'w');
		fwrite($f, 'Generated with Sky version ['.SKY::Version().']');
		fclose($f);
		SkyCLI::ShowBar('=');
		SkyCLI::PrintLn('# Success!');
		SkyCLI::ShowBar();
		chmod($app.'/log', 0777);
	}

	private function GruntLess($app, $name)
	{
		SkyCLI::ShowBar('+');
		SkyCLI::PrintLn('# + Building GruntLESS\n#');
		$base = dirname(__FILE__).'/../../skytemp/.grunt-less';
		$package = file_get_contents($base.'/package.json');
		$package = str_replace('{{NAME}}', $name, $package);
		SkyCLI::PrintLn('# Creating package.json');
		file_put_contents($app.'/package.json', $package);
		SkyCLI::PrintLn('# Installing Gruntfile.js');
		copy($base.'/Gruntfile.js', $app.'/Gruntfile.js');
		SkyCLI::PrintLn('# Installing Dev directories');
		mkdir($app.'/dev');
		mkdir($app.'/dev/less');
		SkyCLI::ShowBar('>');
		SkyCLI::PrintLn('#!!! now run: sudo npm install');
		SkyCLI::PrintLn('#!!! (Make sure you have nodejs and npm installed!)');
		SkyCLI::ShowBar('>');
	}
}
