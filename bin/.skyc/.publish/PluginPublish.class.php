<?php
class PluginPublish
{
	private $_cwd = false;
	private $_ignore = array('.', '..', '.tmp', '.git', '.gitignore');
	private $_name = false;

	const PUBLISH_URL = 'http://codethesky.com/plugins/';
  const QUERY_SEARCH = 'search';
  const QUERY_EXISTS = 'exists';
  const QUERY_PUSH_JSON = 'publish/json';
  const QUERY_UPLOAD = 'upload';
  const QUERY_DOWNLOAD = 'download';

	public function __construct($cwd)
	{
		$this->_cwd = $cwd;
	}

	public function Start()
	{
		$this->CheckForPluginJSONFile();
	}

	public function InstallPlugin($plugin_name, $version = 'latest')
	{
		mkdir(SkyDefines::Call('SKYCORE_LIB_PLUGINS').'/.tmp');
		$opts = array(
			'http' => array(
				'method' => 'GET',
				'header' => "Accept-language: en\r\n"
			)
		);
		$context = stream_context_create($opts);
		stream_context_set_params($context, array(
			'notification' => 'PluginPublish::stream_notification_callback'
		));
		$file = file_get_contents(PluginPublish::PUBLISH_URL.'/'.PluginPublish::QUERY_DOWNLOAD.'/'.$plugin_name.'/'.$version, false, $context);
		$tar_file = SkyDefines::Call('SKYCORE_LIB_PLUGINS').'/.tmp/plugin.tar.gz';
		file_put_contents($tar_file, $file);

		$this->OKMessage();
		$this->UntarPlugin($tar_file);
	}

	private function UntarPlugin($file)
	{
		SkyCLI::Flush('# Unpackaging Plugin File...');
		try {
			$phar = new PharData($file);
			$phar->decompress();
			$phar = new PharData(str_replace('.gz', '', $file));
	    $phar->extractTo(dirname($file));
		} catch(Exception $e) {
			$tmp = SkyDefines::Call('SKYCORE_LIB_PLUGINS').'/.tmp';
			$this->CleanInstallTMP($tmp);
			$this->Fail($e->getMessage());
		}

		$this->OKMessage();
		$this->ActualInstall();
	}

	private function ActualInstall()
	{
		SkyCLI::Flush('# Installing...');
		$tmp = SkyDefines::Call('SKYCORE_LIB_PLUGINS').'/.tmp';
		$json = json_decode(file_get_contents($tmp.'/'.Plugin::PUBLISH_FILE), true);
		$plugin = SkyDefines::Call('SKYCORE_LIB_PLUGINS').'/'.$json['name'];

		@unlink($tmp.'/plugin.tar.gz');
		@unlink($tmp.'/plugin.tar');

		SKY::RCP($tmp, $plugin);

		$this->OKMessage();
		$this->CleanInstallTMP($tmp);
		$this->InstallDependencies($json);
	}

	private function InstallDependencies($json)
	{
		if(array_key_exists('dependencies', $json))
		{
			SkyCLI::PrintLn('# Installing dependencies...');
			foreach($json['dependencies'] as $dependency => $version)
				$this->InstallPlugin($dependency, $version);
		}
	}

	private function CleanInstallTMP($dir)
	{
		SKY::RRMDIR($dir);
	}

	public function stream_notification_callback($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max)
	{
		switch($notification_code)
		{
			case STREAM_NOTIFY_CONNECT:
				SkyCLI::Flush('# Downloading: ');
				break;
			case STREAM_NOTIFY_FILE_SIZE_IS:
        $filesize = $bytes_max;
        break;

			case STREAM_NOTIFY_PROGRESS:
				if($bytes_transferred > 0)
				{
          if(!isset($filesize))
              printf("\r# Downloading: %2dkb...", $bytes_transferred/1024);
          else {
            $length = (int)(($bytes_transferred/$filesize)*100);
            printf("\r[%-100s] %d%% (%2d/%2d kb)", str_repeat("=", $length). ">", $length, ($bytes_transferred/1024), $filesize/1024);
          }
        }
				break;
		}
	}

	private function CheckForPluginJSONFile()
	{
		SkyCLI::Flush('# Checking for '.Plugin::PUBLISH_FILE.'...');
		$publish_file = $this->_cwd.'/'.Plugin::PUBLISH_FILE;
		if(is_file($publish_file))
		{
			$this->OKMessage();
			$this->CheckIfPluginJSONIsCorrect($publish_file);
			return true;
		}

		$this->Fail('Unable to publish! No '.Plugin::PUBLISH_FILE.' found in ['.$this->_cwd.'].');
	}

	private function CheckIfPluginJSONIsCorrect($publish_file)
	{
		SkyCLI::Flush('# Reading '.Plugin::PUBLISH_FILE.'...');
		$publish_json = file_get_contents($publish_file);
		if($publish_json === false)
			$this->Fail('Unable to publish! Was unable to read the '.Plugin::PUBLISH_FILE);

		$publish = json_decode($publish_json, true);
		if($publish === null || $this->ValidatePublishFile($publish) === false)
			$this->Fail('Unable to publish! There is something wrong with your '.Plugin::PUBLISH_FILE.'.');

		$this->OKMessage();
		$this->CheckIfPluginExistsInRegistry($publish);
	}

	private function CheckIfPluginExistsInRegistry($publish)
	{
		SkyCLI::Flush('# Checking if plugin exists in registry...');
		$exists = self::CheckIfExists($publish['name'], $publish['version']);
		if($exists === null)
			$this->Fail('There was a problem reaching the SKY Registry! Try again?');

		if($exists === true)
			$this->Fail('Plugin already exists! Did you forget to bump the version?');

		$this->_name = $publish['name'];
		$this->OKMessage();
		$this->PackagingPlugin($publish);
	}

	private function PackagingPlugin($publish)
	{
		SkyCLI::Flush('# Packaging plugin...');
		$tmp = $this->_cwd.'/.tmp';
		mkdir($tmp);
		if(array_key_exists('include', $publish))
			$this->PackageWithInclude($publish, $tmp);
		elseif(array_key_exists('exclude', $publish))
			$this->PackageWithExclude($publish, $tmp);
		else
			$this->Package($publish, $tmp);

		$phar = new PharData('.tmp/plugin.tar.gz');
		$phar->buildFromDirectory($tmp);

		$this->OKMessage();
		$this->PushPluginJSON();
	}

	private function PackageWithInclude($publish, $tmp)
	{
		$this->CopyFiles($tmp, $publish, function($entry, $publish) {
			return in_array($entry, $publish['include']);
		});
	}

	private function PackageWithExclude($publish, $tmp)
	{
		$this->CopyFiles($tmp, $publish, function($entry, $publish) {
			return !in_array($entry, $publish['exclude']);
		});
	}

	private function Package($publish, $tmp)
	{
		$this->CopyFiles($tmp, $publish, function($entry, $publish) {
			return true;
		});
	}

	private function CopyFiles($tmp, $publish, $check_function)
	{
		if($handle = opendir($this->_cwd))
		{
		    while(false !== ($entry = readdir($handle)))
			{
	        	if(!in_array($entry, $this->_ignore))
				{
            		if($check_function($entry, $publish))
					{
						if(is_dir($this->_cwd.'/'.$entry))
							SKY::RCP($this->_cwd.'/'.$entry, $tmp.'/'.$entry);
						else
							copy($this->_cwd.'/'.$entry, $tmp.'/'.$entry);
					}
	        	}
		    }
		    closedir($handle);
		}
	}

	private function PushPluginJSON()
	{
		SkyCLI::Flush('# Syncing Plugin with registry...');
		$publish_file = $this->_cwd.'/'.Plugin::PUBLISH_FILE;

		$target_url = self::PUBLISH_URL.self::QUERY_PUSH_JSON;

	    $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $target_url);
		$this->curl_custom_postfields($ch, array(), array('plugin_json' => $publish_file));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$r = curl_exec($ch);
		$result = json_decode($r);
		curl_close($ch);

		if($result === false)
			$this->Fail('Unable to sync with the registry. Try again? Have you tried turning it off then on again?');

		$this->OKMessage();
		$this->PushPluginTAR($result);
	}

	private function PushPluginTAR($ID)
	{
		SkyCLI::Flush('# Pushing Plugin to registry...');

		$target_url = self::PUBLISH_URL.self::QUERY_UPLOAD.'/'.$ID;

	  $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $target_url);
		$this->curl_custom_postfields($ch, array(), array('plugin' => $this->_cwd.'/.tmp/plugin.tar.gz'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = json_decode(curl_exec($ch));
		curl_close($ch);

		if($result === false)
			$this->Fail('Noooooooo! Unable to push plugin. I guess you can try again...');

		$this->OKMessage();
		$this->CleanTMP($this->_cwd.'/.tmp');
	}

	private function CleanTMP($loc)
	{
		SkyCLI::Flush('# Cleaning up...');
		SKY::RRMDIR($loc);
		$this->OKMessage();
		SkyCLI::PrintLn('=> Yay!!! You did it! Your plugin is now up there. Woohoo!');
		SkyCLI::PrintLn('=> You can now install it with: sky plugin install '.$this->_name);
	}

	// ## Helpers
	public static function CheckIfExists($name, $version)
    {
    	$json = file_get_contents(self::PUBLISH_URL.self::QUERY_EXISTS.'/'.$name.'/'.$version);
		return json_decode($json);
    }

	private function ValidatePublishFile($publish)
	{
		if(!array_key_exists('name', $publish))
			return false;
		if(!array_key_exists('version', $publish))
			return false;
		if(strtolower($publish['name']) !== $publish['name'])
			$this->Fail('You package name is wrong! All wrong... Try something like this: my-plugin');
		if(strpos($publish['name'], ' ') !== false)
			$this->Fail('You package name is wrong! All wrong... Try something like this: my-plugin');

		return true;
	}

	private function Fail($msg)
	{
		$this->FailMessage();
		SkyCLI::ShowError($msg);
	}

	private function OKMessage()
	{
		SkyCLI::PrintLn(" \033[0;32mOK!\033[0m");
	}

	private function FAILMessage()
	{
		SkyCLI::PrintLn(" \033[0;31mFAIL!\033[0m");
	}

	/**
	* For safe multipart POST request for PHP5.3 ~ PHP 5.4.
	*
	* @param resource $ch cURL resource
	* @param array $assoc "name => value"
	* @param array $files "name => path"
	* @return bool
	*/
	private function curl_custom_postfields($ch, array $assoc = array(), array $files = array()) {

	    // invalid characters for "name" and "filename"
	    static $disallow = array("\0", "\"", "\r", "\n");

	    // build normal parameters
	    foreach ($assoc as $k => $v) {
	        $k = str_replace($disallow, "_", $k);
	        $body[] = implode("\r\n", array(
	            "Content-Disposition: form-data; name=\"{$k}\"",
	            "",
	            $v,
	        ));
	    }

	    // build file parameters
	    foreach ($files as $k => $v) {
	        switch (true) {
	            case !is_file($v):
	            case !is_readable($v):
	                continue; // or return false, throw new InvalidArgumentException
	        }
	        if(basename($v) === Plugin::PUBLISH_FILE)
	        	$content_type = 'application/json';
	        elseif(basename($v) === 'plugin.tar.gz')
	        	$content_type = 'application/zip';
	        else
	        	$content_type = 'application/octet-stream';
	        $data = file_get_contents($v);
	        $v = call_user_func("end", explode(DIRECTORY_SEPARATOR, $v));
	        $k = str_replace($disallow, "_", $k);
	        $v = str_replace($disallow, "_", $v);
	        $body[] = implode("\r\n", array(
	            "Content-Disposition: form-data; name=\"{$k}\"; filename=\"{$v}\"",
	            "Content-Type: ".$content_type,
	            "",
	            $data,
	        ));
	    }

	    // generate safe boundary
	    do {
	        $boundary = "---------------------" . md5(mt_rand() . microtime());
	    } while (preg_grep("/{$boundary}/", $body));

	    // add boundary for each parameters
	    array_walk($body, function (&$part) use ($boundary) {
	        $part = "--{$boundary}\r\n{$part}";
	    });

	    // add final boundary
	    $body[] = "--{$boundary}--";
	    $body[] = "";

	    // set options
	    return @curl_setopt_array($ch, array(
	        CURLOPT_POST       => true,
	        CURLOPT_POSTFIELDS => implode("\r\n", $body),
	        CURLOPT_HTTPHEADER => array(
	            "Expect: 100-continue",
	            "Content-Type: multipart/form-data; boundary={$boundary}", // change Content-Type
	        ),
	    ));
	}
}
