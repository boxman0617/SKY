<?php
class SKY
{
	public static function Version()
	{
		return trim(file_get_contents(SKYCORE.'/version.info'));
	}

	public static function RCP($src, $dst) 
	{ 
		$dir = opendir($src); 
		@mkdir($dst); 
		while(false !== ($file = readdir($dir)))
		{ 
			if(($file != '.' ) && ( $file != '..' ))
			{ 
				if(is_dir($src . '/' . $file))
					self::RCP($src . '/' . $file,$dst . '/' . $file);
				else
					copy($src . '/' . $file,$dst . '/' . $file);
			}
		}
		closedir($dir);
	}

	public static function IsCurl()
	{
		return function_exists('curl_version');
	}

	public static function DownloadFile($file_source, $file_target) 
	{
		$rh = fopen($file_source, 'rb');
		$wh = fopen($file_target, 'w+b');
		if (!$rh || !$wh)
			return false;

		echo '[';
		while (!feof($rh)) 
		{
			if (fwrite($wh, fread($rh, 4096)) === FALSE)
				return false;
			echo '=';
			flush();
		}
		echo "]\n";

		fclose($rh);
		fclose($wh);

		return true;
	}

	public static function LoadCore($ENV = 'DEV')
	{
		require_once(getenv('SKYCORE').'/configs/defines.php');
		define('APPROOT', getcwd());

		require_once(APPROOT.'/configs/defines.php');
		require_once(DIR_CONFIGS.'/configure.php');

		$GLOBALS['ENV'] = $ENV;

		require_once(SKYCORE_CONFIGS.'/configure.php');
		require_once(SKYCORE_CONFIGS.'/loadcore.php');
	}

	//############################################################
	//# Word plural/singular Methods
	//############################################################
	
	public static function singularize($word)
  {
	  $singular = array (
		'/(quiz)zes$/i' => '$1',
		'/(matr)ices$/i' => '$1ix',
		'/(vert|ind)ices$/i' => '$1ex',
		'/^(ox)en/i' => '$1',
		'/(alias|status)es$/i' => '$1',
		'/([octop|vir])i$/i' => '$1us',
		'/(cris|ax|test)es$/i' => '$1is',
		'/(shoe)s$/i' => '$1',
		'/(o)es$/i' => '$1',
		'/(bus)es$/i' => '$1',
		'/([m|l])ice$/i' => '$1ouse',
		'/(x|ch|ss|sh)es$/i' => '$1',
		'/(m)ovies$/i' => '$1ovie',
		'/(s)eries$/i' => '$1eries',
		'/([^aeiouy]|qu)ies$/i' => '$1y',
		'/([lr])ves$/i' => '$1f',
		'/(tive)s$/i' => '$1',
		'/(hive)s$/i' => '$1',
		'/([^f])ves$/i' => '$1fe',
		'/(^analy)ses$/i' => '$1sis',
		'/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '$1$2sis',
		'/([ti])a$/i' => '$1um',
		'/(n)ews$/i' => '$1ews',
		'/s$/i' => ''
	  );

	  $uncountable = array('equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep');

	  $irregular = array(
	  'person' => 'people',
	  'man' => 'men',
	  'child' => 'children',
	  'sex' => 'sexes',
	  'move' => 'moves');

	  $lowercased_word = strtolower($word);
	  foreach ($uncountable as $_uncountable){
		  if(substr($lowercased_word,(-1*strlen($_uncountable))) == $_uncountable){
			  return $word;
		  }
	  }

	  foreach ($irregular as $_plural=> $_singular){
		  if (preg_match('/('.$_singular.')$/i', $word, $arr)) {
			  return preg_replace('/('.$_singular.')$/i', substr($arr[0],0,1).substr($_plural,1), $word);
		  }
	  }

	  foreach ($singular as $rule => $replacement) {
		  if (preg_match($rule, $word)) {
			  return preg_replace($rule, $replacement, $word);
		  }
	  }

	  return false;
  }

	public static function pluralize($word)
	{
		$plural = array(
			'/(quiz)$/i' => '$1zes',
			'/^(ox)$/i' => '$1en',
			'/([m|l])ouse$/i' => '$1ice',
			'/(matr|vert|ind)ix|ex$/i' => '$1ices',
			'/(x|ch|ss|sh)$/i' => '$1es',
			'/([^aeiouy]|qu)ies$/i' => '$1y',
			'/([^aeiouy]|qu)y$/i' => '$1ies',
			'/(hive)$/i' => '$1s',
			'/(?:([^f])fe|([lr])f)$/i' => '$1$2ves',
			'/sis$/i' => 'ses',
			'/([ti])um$/i' => '$1a',
			'/(buffal|tomat)o$/i' => '$1oes',
			'/(bu)s$/i' => '$1ses',
			'/(alias|status)/i'=> '$1es',
			'/(octop|vir)us$/i'=> '$1i',
			'/(ax|test)is$/i'=> '$1es',
			'/s$/i'=> 's',
			'/$/'=> 's'
		);

		$uncountable = array('equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep');

		$irregular = array(
		'person' => 'people',
		'man' => 'men',
		'child' => 'children',
		'sex' => 'sexes',
		'move' => 'moves');

		$lowercased_word = strtolower($word);

		foreach ($uncountable as $_uncountable){
			if(substr($lowercased_word,(-1*strlen($_uncountable))) == $_uncountable){
				return $word;
			}
		}

		foreach ($irregular as $_plural=> $_singular){
			if (preg_match('/('.$_plural.')$/i', $word, $arr)) {
				return preg_replace('/('.$_plural.')$/i', substr($arr[0],0,1).substr($_singular,1), $word);
			}
		}

		foreach ($plural as $rule => $replacement) {
			if (preg_match($rule, $word)) {
				return preg_replace($rule, $replacement, $word);
			}
		}
		return false;
	}

	public static function UnderscoreToUpper($word)
	{
		$tmp = explode('_', $word);
		$return = "";
		foreach($tmp as $sub)
			$return .= ucfirst($sub);
		return $return;
	}
}
?>
