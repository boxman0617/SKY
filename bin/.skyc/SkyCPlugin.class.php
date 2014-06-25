<?php
SkyL::Import(SkyDefines::Call('PLUGIN_CLASS'));

class SkyCPlugin implements SkyCommand
{
	private $_cli; // For two-way communication

	public function __construct($cli)
	{
		$this->_cli = $cli;
	}

	public function GetShortHelp()
	{
		$help = "#\tsky plugin install pluginname\n";
		$help .= "#\tsky plugin load pluginname\n";
		$help .= "#\tsky plugin list\n";
		$help .= "#\tsky plugin remove pluginname\n";
		$help .= "#\tsky plugin destroy pluginname\n";
		$help .= "#\tsky plugin search pluginname\n";
		$help .= "#\tsky plugin publish";
		return $help;
	}

	public function GetLongHelp()
	{
		$help = "#\tsky plugin install pluginname\n";
		$help .= "#\t - Will install 'pluginname' into SKYCORE.\n#\n";

		$help .= "#\tsky plugin load pluginname\n";
		$help .= "#\t - Will install 'pluginname' into your app if it is\n";
		$help .= "#\t - already installed in your SKYCORE.\n#\n";

		$help .= "#\tsky plugin list\n";
		$help .= "#\t - Will show a list of all the currently installed plugins\n";
		$help .= "#\t - in your SKYCORE install.\n#\n";

		$help .= "#\tsky plugin remove pluginname\n";
		$help .= "#\t - Will remove an installed plugin from your app.\n#\n";

		$help .= "#\tsky plugin destroy pluginname\n";
		$help .= "#\t - Will remove an installed plugin from you SKYCORE\n";
		$help .= "#\t - install.\n#\n";

		$help .= "#\tsky plugin search pluginname\n";
		$help .= "#\t - Will search the repo of plugins.\n#\n";

		$help .= "#\tsky plugin publish\n";
		$help .= "#\t - Will publish the plugin to the codethesky.com plugin registry.";

		return $help;
	}

	public function Execute($args = array())
	{
		if(count($args) == 0)
			$this->_cli->ShowError('sky plugin requires more arguments! (Run "sky help plugin" for more information)');

		$command = $args[0];
		unset($args[0]);
		$args = array_values($args);

		call_user_func(array($this, 'Execute'.ucfirst($command)), $args);
	}

	private function Header($str)
	{
		$this->_cli->ShowBar();
		$this->_cli->PrintLn('# '.$str);
		$this->_cli->ShowBar('=');
	}

	private function ExecutePublish($args)
	{
		$cwd = getcwd();
		$this->Header('SkyApp Plugin Publish:');
		$this->_cli->Flush('# Checking for '.Plugin::PUBLISH_FILE.'...');
		$publish_file = $cwd.'/'.Plugin::PUBLISH_FILE;
		if(is_file($publish_file))
		{
			$this->_cli->PrintLn(" \033[0;32mOK!\033[0m");
			$this->_cli->Flush('# Reading '.Plugin::PUBLISH_FILE.'...');
			$publish_json = file_get_contents($publish_file);
			$publish = json_decode($publish_json, true);
			if($publish === null || $this->_ValidatePublishFile($publish) === false)
			{
				$this->_cli->PrintLn(" \033[0;31mFAIL!\033[0m");
				$this->_cli->ShowError('Unable to publish! There is somthing wrong with your '.Plugin::PUBLISH_FILE.'.');
			}

			$this->_cli->PrintLn(" \033[0;32mOK!\033[0m");

			$this->_cli->Flush('# Checking if plugin exists in registry...');
			$exists = Plugin::CheckIfExists($publish['name'], $publish['version']);
			if($exists === null)
			{
				$this->_cli->PrintLn(" \033[0;31mFAIL!\033[0m");
				$this->_cli->ShowError('There was a problem reaching the SKY Registry! Try again?');
			}

			if($exists === true)
			{
				$this->_cli->PrintLn(" \033[0;31mFAIL!\033[0m");
				$this->_cli->ShowError('Plugin already exists! Did you forget to bump the version?');
			}
			$this->_cli->PrintLn(" \033[0;32mOK!\033[0m");

			$this->_cli->Flush('# Packaging plugin...');
			$tmp = $cwd.'/.tmp';
			mkdir($tmp);
			$ignore = array('.', '..', '.tmp', '.git', '.gitignore');
			if(array_key_exists('include', $publish))
			{
				if($handle = opendir($cwd))
				{
			    while(false !== ($entry = readdir($handle)))
					{
		        if(!in_array($entry, $ignore))
						{
	            if(in_array($entry, $publish['include']))
							{
								if(is_dir($cwd.'/'.$entry))
									SKY::RCP($cwd.'/'.$entry, $tmp.'/'.$entry);
								else
									copy($cwd.'/'.$entry, $tmp.'/'.$entry);
							}
		        }
			    }
			    closedir($handle);
				}
			} elseif(array_key_exists('exclude', $publish)) {
				if($handle = opendir($cwd))
				{
					while(false !== ($entry = readdir($handle)))
					{
						if(!in_array($entry, $ignore))
						{
							if(!in_array($entry, $publish['exclude']))
							{
								if(is_dir($cwd.'/'.$entry))
									SKY::RCP($cwd.'/'.$entry, $tmp.'/'.$entry);
								else
									copy($cwd.'/'.$entry, $tmp.'/'.$entry);
							}
						}
					}
					closedir($handle);
				}
			} else {
				if($handle = opendir($cwd))
				{
					while(false !== ($entry = readdir($handle)))
					{
						if(!in_array($entry, $ignore))
						{
							if(is_dir($cwd.'/'.$entry))
								SKY::RCP($cwd.'/'.$entry, $tmp.'/'.$entry);
							else
								copy($cwd.'/'.$entry, $tmp.'/'.$entry);
						}
					}
					closedir($handle);
				}
			}

			$phar = new PharData('.tmp/plugin.tar.gz');
			$phar->buildFromDirectory($tmp);

			$this->_cli->PrintLn(" \033[0;32mOK!\033[0m");

			

		} else {
			$this->_cli->PrintLn(" \033[0;31mFAIL!\033[0m");
			$this->_cli->ShowError('Unable to publish! No '.Plugin::PUBLISH_FILE.' found in ['.$cwd.'].');
		}
	}

	private function _ValidatePublishFile($publish)
	{
		if(!array_key_exists('name', $publish))
			return false;
		if(!array_key_exists('version', $publish))
			return false;

		return true;
	}

	private function ExecuteRemove($args)
	{
		if(count($args) == 0)
			$this->_cli->ShowError('sky plugin remove requires more arguments! (Run "sky help plugin" for more information)');
		$this->Header('SkyApp Plugin Remover:');
		$plugin_name = $args[0];

		$plugin_home = SkyDefines::Call('DIR_LIB_PLUGINS').'/'.strtolower($plugin_name);
		if(is_dir($plugin_home))
		{
			$this->_cli->PrintLn('# Removing plugin ['.$plugin_name.']...');
			if(SKY::RRMDIR($plugin_home))
			{
				$this->_cli->ShowBar('=');
				$this->_cli->PrintLn('# Success! This plugin is now removed from your app.');
				$this->_cli->ShowBar();
				return true;
			}
			$this->_cli->ShowError('Unexpected error while removing this plugin...');
		}
		$this->_cli->ShowError('Seems as though this plugin is not installed in your app?');
	}

	private function ExecuteLoad($args)
	{
		if(count($args) == 0)
			$this->_cli->ShowError('sky plugin load requires more arguments! (Run "sky help plugin" for more information)');
		$this->Header('SkyApp Plugin Installation:');
		$plugin_name = $args[0];

		$plugin_dir = SkyDefines::Call('SKYCORE_LIB_PLUGINS').'/'.$plugin_name;
		$info_cnf = $plugin_dir.'/info.cnf';
		if(is_file($info_cnf))
		{
			$cnf = Plugin::ReadCNF($info_cnf);
			$this->_cli->PrintLn('# Installing plugin ['.$cnf['name'].']...');
			$install = $plugin_dir.'/install';
			if(!is_dir($install))
				$this->_cli->ShowError('Seems as though this plugin does not have an [install] directory. Maybe the plugin is broken?');
			SKY::RCP($install, SkyDefines::Call('DIR_LIB_PLUGINS').'/'.strtolower($plugin_name));
			$this->_cli->ShowBar('=');
			$this->_cli->PrintLn('# Success! Your plugin is now installed in your app.');
			$this->_cli->PrintLn('# You can find it under: [lib/plugins/'.strtolower($plugin_name).']');
			$this->_cli->PrintLn('#');
			$this->_cli->PrintLn('# (Note: Just because the plugin is now installed');
			$this->_cli->PrintLn('# does not mean the plugin is enabled. See [configs/plugins.php])');
			$this->_cli->ShowBar();
			return true;
		}
		$this->_cli->ShowError('Seems as though this plugin does not have an [info.cnf] file. Maybe the plugin is broken?');
	}

	private function ExecuteList($args)
	{
		$this->Header('List of installed plugins:');
		$real = SkyDefines::Call('SKYCORE_LIB_PLUGINS');
		if($handle = opendir($real))
		{
		    while(false !== ($entry = readdir($handle)))
		    {
		        if($entry != '.' && $entry != '..')
		        {
		        	$plugin_cnf = $real.'/'.$entry.'/info.cnf';
		        	if(is_file($plugin_cnf))
		        	{
		        		$cnf = Plugin::ReadCNF($plugin_cnf);
		        		if(array_key_exists('name', $cnf) && array_key_exists('version', $cnf))
		        		{
		        			$str = '#= '.$cnf['name'].':'.$cnf['version'];
		        			if(SKY::IsInApp())
		        			{
		        				if(is_dir(SkyDefines::Call('DIR_LIB_PLUGINS').'/'.$entry))
		        					$str .= ' *';
		        			}
							$this->_cli->PrintLn($str);
		        		} else {
		        			continue;
		        		}
		        	}
		        }
		    }

		    closedir($handle);
		}
		$this->_cli->ShowBar('=');
		$this->_cli->PrintLn('# (Note: * means the plugin is currently installed in your app)');
		$this->_cli->ShowBar();
	}
}
