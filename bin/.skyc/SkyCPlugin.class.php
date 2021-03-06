<?php
SkyL::Import(SkyDefines::Call('PLUGIN_CLASS'));
SkyL::Import(dirname(__FILE__).'/.publish/PluginPublish.class.php');

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
		$help .= "#\tsky plugin use pluginname\n";
		$help .= "#\tsky plugin list\n";
		$help .= "#\tsky plugin remove pluginname\n";
		$help .= "#\tsky plugin uninstall pluginname\n";
		$help .= "#\tsky plugin search pluginname\n";
		$help .= "#\tsky plugin publish";
		return $help;
	}

	public function GetLongHelp()
	{
		$help = "#\tsky plugin install pluginname\n";
		$help .= "#\t - Will install 'pluginname' into SKYCORE.\n#\n";

		$help .= "#\tsky plugin use pluginname\n";
		$help .= "#\t - Will install 'pluginname' into your app. If it is\n";
		$help .= "#\t - not installed in your SKYCORE, it will install there first.\n#\n";

		$help .= "#\tsky plugin list\n";
		$help .= "#\t - Will show a list of all the currently installed plugins\n";
		$help .= "#\t - in your SKYCORE install.\n#\n";

		$help .= "#\tsky plugin remove pluginname\n";
		$help .= "#\t - Will remove an installed plugin from your app.\n#\n";

		$help .= "#\tsky plugin uninstall pluginname\n";
		$help .= "#\t - Will remove an installed plugin from SKYCORE\n";
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
			SkyCLI::ShowError('sky plugin requires more arguments! (Run "sky help plugin" for more information)');

		$command = $args[0];
		unset($args[0]);
		$args = array_values($args);

		call_user_func(array($this, 'Execute'.ucfirst($command)), $args);
	}

	private function Header($str)
	{
		SkyCLI::ShowBar();
		SkyCLI::PrintLn('# '.$str);
		SkyCLI::ShowBar('=');
	}

	private function ExecutePublish($args)
	{
		$this->Header('SkyApp Plugin Publish:');
		$p = new PluginPublish(getcwd());
		$p->Start();
	}

	private function ExecuteRemove($args)
	{
		if(count($args) == 0)
			SkyCLI::ShowError('sky plugin remove requires more arguments! (Run "sky help plugin" for more information)');
		$this->Header('SkyApp Plugin Remover:');
		$plugin_name = $args[0];

		$plugin_home = SkyDefines::Call('DIR_LIB_PLUGINS').'/'.strtolower($plugin_name);

		SkyCLI::PrintLn('# Removing plugin ['.$plugin_name.']...');
		$plugins_file = SkyDefines::Call('DIR_CONFIGS').'/'.Plugin::PLUGINS_FILE;
		$json = json_decode(file_get_contents($plugins_file), true);
		if(!array_key_exists($plugin_name, $json))
			SkyCLI::ShowError('Seems as though this plugin is not installed in your app?');

		$rm = true;
		if(is_dir($plugin_home))
			$rm = SKY::RRMDIR($plugin_home);

		if($rm === false)
			SkyCLI::ShowError('Unable to delete plugin home directory.');
		unset($json[$plugin_name]);
		file_put_contents($plugins_file, json_encode($json));
		SkyCLI::ShowBar('=');
		SkyCLI::PrintLn('# Success! This plugin is now removed from your app.');
		SkyCLI::ShowBar();
		return true;
	}

	private function ExecuteUninstall($args)
	{
		if(count($args) == 0)
			SkyCLI::ShowError('sky plugin uninstall requires more arguments! (Run "sky help plugin" for more information)');
		$this->Header('SkyApp Plugin Uninstaller:');
		$plugin_name = $args[0];

		$plugin_home = Plugin::GetPluginDir($plugin_name);
		if(is_dir($plugin_home))
		{
			SkyCLI::PrintLn('# Uninstalling plugin ['.$plugin_name.']...');
			if(SKY::RRMDIR($plugin_home))
			{
				SkyCLI::ShowBar('=');
				SkyCLI::PrintLn('# Success! This plugin is now uninstalled.');
				SkyCLI::ShowBar();
				return true;
			}
			SkyCLI::ShowError('Unexpected error while uninstalling this plugin...');
		}
		SkyCLI::ShowError('Seems as though this plugin is not installed?');
	}

	private function ExecuteUse($args)
	{
		if(count($args) == 0)
			SkyCLI::ShowError('sky plugin use requires more arguments! (Run "sky help plugin" for more information)');
		$this->Header('SkyApp Plugin Installation:');
		$plugin_name = $args[0];
		$plugin_dir = SkyDefines::Call('SKYCORE_LIB_PLUGINS').'/'.$plugin_name;

		if(!is_dir($plugin_dir))
		{
			SkyCLI::PrintLn('# Installing app...');
			$p = new PluginPublish(getcwd());
			$p->InstallPlugin($plugin_name);
		}

		SkyCLI::Flush('# Checking for dups...');

		$plugins = json_decode(file_get_contents(SkyDefines::Call('DIR_CONFIGS').'/'.Plugin::PLUGINS_FILE), true);

		if(array_key_exists($plugin_name, $plugins))
			SkyCLI::Fail('Plugin is already installed in app!');

		SkyCLI::OKMessage();

		SkyCLI::Flush('# Installing...');
		$json = Plugin::ReadJSON($plugin_name);
		$plugins[$plugin_name] = array(
			'version' => $json['version']
		);

		if(array_key_exists('onLoad', $json))
			$plugins[$plugin_name]['onLoad'] = $json['onLoad'];
		if(array_key_exists('getID', $json))
			$plugins[$plugin_name]['getID'] = $json['getID'];

		if(array_key_exists('localInstall', $json))
		{
			$local = SkyDefines::Call('DIR_LIB_PLUGINS').'/'.$json['name'];
			SKY::RCP($plugin_dir.'/'.$json['localInstall'], $local);
		}

		if(array_key_exists('onUse', $json))
		{
			$local = SkyDefines::Call('DIR_LIB_PLUGINS').'/'.$json['name'];
			require_once(Plugin::GetPluginDir($plugin_name).'/'.$json['onUse']);
		}

		file_put_contents(SkyDefines::Call('DIR_CONFIGS').'/'.Plugin::PLUGINS_FILE, json_encode($plugins));

		SkyCLI::OKMessage();
		SkyCLI::PrintLn('=> Yay!!! You did it!');
	}

	private function ExecuteInstall($args)
	{
		if(count($args) == 0)
			SkyCLI::ShowError('sky plugin install requires more arguments! (Run "sky help plugin" for more information)');
		$this->Header('SkyApp Plugin Installation:');
		$plugin_name = $args[0];

		$plugin_dir = SkyDefines::Call('SKYCORE_LIB_PLUGINS').'/'.$plugin_name;
		if(!is_dir($plugin_dir))
		{
			$p = new PluginPublish(getcwd());
			$p->InstallPlugin($plugin_name);
		}
	}

	private function ExecuteLoad($args)
	{
		if(count($args) == 0)
			SkyCLI::ShowError('sky plugin load requires more arguments! (Run "sky help plugin" for more information)');
		$this->Header('SkyApp Plugin Installation:');
		$plugin_name = $args[0];

		$plugin_dir = SkyDefines::Call('SKYCORE_LIB_PLUGINS').'/'.$plugin_name;
		$info_cnf = $plugin_dir.'/info.cnf';
		if(is_file($info_cnf))
		{
			$cnf = Plugin::ReadCNF($info_cnf);
			SkyCLI::PrintLn('# Installing plugin ['.$cnf['name'].']...');
			$install = $plugin_dir.'/install';
			if(!is_dir($install))
				SkyCLI::ShowError('Seems as though this plugin does not have an [install] directory. Maybe the plugin is broken?');
			SKY::RCP($install, SkyDefines::Call('DIR_LIB_PLUGINS').'/'.strtolower($plugin_name));
			SkyCLI::ShowBar('=');
			SkyCLI::PrintLn('# Success! Your plugin is now installed in your app.');
			SkyCLI::PrintLn('# You can find it under: [lib/plugins/'.strtolower($plugin_name).']');
			SkyCLI::PrintLn('#');
			SkyCLI::PrintLn('# (Note: Just because the plugin is now installed');
			SkyCLI::PrintLn('# does not mean the plugin is enabled. See [configs/plugins.php])');
			SkyCLI::ShowBar();
			return true;
		}
		SkyCLI::ShowError('Seems as though this plugin does not have an [info.cnf] file. Maybe the plugin is broken?');
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
									SkyCLI::PrintLn($str);
		        		} else {
		        			continue;
		        		}
		        	}
		        }
		    }

		    closedir($handle);
		}
		SkyCLI::ShowBar('=');
		SkyCLI::PrintLn('# (Note: * means the plugin is currently installed in your app)');
		SkyCLI::ShowBar();
	}
}
