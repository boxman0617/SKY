<?php
class CommandLine
{
	private $arguments = array();
	private $delegates = array();

	public function __construct($args)
	{
		unset($args[0]);
		$arguments = array_values($args);
		foreach($arguments as $key => $arg)
		{
			if(strpos($arg, '=') !== false)
			{
				$explode = explode('=', $arg, 2);
				$this->arguments[$explode[0]] = $explode[1];
			} else {
				$this->arguments[$key] = $arg;
			}
		}
	}

	public function Run()
	{
		foreach($this->arguments as $key => $value)
		{
			if(isset($this->delegates[$key])) 
			{
				if(is_array($this->delegates[$key][$value]))
				{
					if(function_exists($this->delegates[$key][$value][0]))
					{
						if(!isset($this->delegates[$key][$value][1]))
						{
							call_user_func(
								$this->delegates[$key][$value][0]
							);
						} else {
							call_user_func(
								$this->delegates[$key][$value][0], 
								$this->arguments[$this->delegates[$key][$value][1]]
							);
						}
					} else {
						self::PrintError('Invalid argument ['.$value.']');
						if(isset($this->delegates[0]['help']) && function_exists($this->delegates[0]['help']))
							call_user_func($this->delegates[0]['help']);
					}
				} else {
					call_user_func($this->delegates[$key][$value]);
				}
			}
		}

	}

	public function Delegate($commands)
	{
		$this->delegates = $commands;
	}

	public static function Question($question, $options = array('Y', 'N'))
	{
		echo $question."\n";
		echo "[".implode('/', $options)."] > ";
		return trim(fgets(STDIN));
	}

	public static function End($msg)
	{
		self::Puts($msg);
		exit();
	}

	public static function PrintError($msg)
	{
		self::Puts('[ERROR] > '.$msg);
	}

	public static function HeaderBar($fill = '#')
	{
		self::Puts(self::LJust('', 35, $fill));
	}

	public static function Puts($string)
	{
		echo $string."\n";
	}

	public static function LJust($string, $size, $fill = ' ')
	{
		printf("%'".$fill[0].'-'.$size."s", $string);
	}

	public static function RJust($string, $size, $fill = ' ')
	{
		printf("%'".$fill[0].$size."s", $string);
	}

	public function DumpArguments()
	{
		var_dump($this->arguments);
	}
}
