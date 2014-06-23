<?php
class Fixtures
{
	public static function Start($fixture)
	{
		if(file_exists(SkyDefines::Call('SKYCORE_FIXTURES').'/'.$fixture.'.fixture.php'))
		{
			SkyL::Import(SkyDefines::Call('SKYCORE_FIXTURES').'/'.$fixture.'.fixture.php');
		}
		elseif(file_exists(SkyDefines::Call('DIR_FIXTURES').'/'.$fixture.'.fixture.php'))
		{
			SkyL::Import(SkyDefines::Call('DIR_FIXTURES').'/'.$fixture.'.fixture.php');
		}
		else
		{
			trigger_error('No fixture found!', E_USER_ERROR);
			return false;
		}
		echo "# Building Fixtures...\n";
		Fixture::Build();
		echo "# Done! Starting tests...\n\n";
	}
}

class Fixture
{
	private static $Models  = array();
	private static $Rows	= array();

	public static function CreateModels($driver, $models = array())
	{
		foreach($models as $model => $fields)
		{
			self::$Models[$model] = array(
				'fields' 		=> $fields,
				'driver' 		=> $driver,
				'associations' 	=> false
			);
		}
	}

	public static function CreateAssociations($associations = array())
	{
		foreach($associations as $model => $association)
		{
			if(strpos($model, '_') !== false)
			{
				$tmp = explode('_', $model);
				$COUNT = count($tmp);
				$tmp[$COUNT-1] = SKY::pluralize($tmp[$COUNT-1]);
				$model = implode('_', $tmp);
			} else {
				$model = SKY::pluralize($model);
			}
			self::$Models[$model]['associations'] = $association;
		}
	}

	public static function AddRow($model_name, $data = array())
	{
		if(!isset(self::$Rows[$model_name])) self::$Rows[$model_name] = array();
		self::$Rows[$model_name][] = $data;
		SkyL::Import(SkyDefines::Call('SKYCORE_CORE_MODEL').'/drivers/'.self::$Models[$model_name]['driver'].'.driver.php');
		$DRIVER = self::$Models[$model_name]['driver'].'Driver';
		$data[$DRIVER::$DefaultPrimaryKey] = count(self::$Rows[$model_name]);
		$data['created_at'] = $DRIVER::created_at();
		$data['updated_at'] = $DRIVER::updated_at();
		return new FixtureRow($data);
	}

	public static function Build()
	{
		foreach(self::$Models as $name => $info)
		{
			SkyL::Import(SkyDefines::Call('SKYCORE_CORE_MODEL').'/drivers/'.$info['driver'].'.driver.php');
			$DRIVER = $info['driver'].'Driver';
			$DRIVER::DropTable($name);
			$DRIVER::CreateTable($name, $info['fields']);
			$MODEL = ucfirst($name);
			if(strpos($name, '_') !== false) $MODEL = SKY::UnderscoreToUpper($name);
			$class = "<?php
class ".$MODEL." extends Model
{
";
			if($info['associations'] !== false)
			{
				foreach($info['associations'] as $type => $models)
				{
					$class .= "protected $".SKY::UnderscoreToUpper($type)." = array(";
					foreach($models as $model => $options)
						$class .= "'".$model."' => ".var_export($options, true).",";
					$class = substr($class, 0, -1);
					$class .= ");\n";
				}
			}
			$class .= "}

";
			if(file_exists(SkyDefines::Call('DIR_APP_MODELS')."/".$MODEL.".model.php")) unlink(SkyDefines::Call('DIR_APP_MODELS')."/".$MODEL.".model.php");
	        $f = fopen(SkyDefines::Call('DIR_APP_MODELS')."/".$MODEL.".model.php", "w");
	        fwrite($f, $class);
	        fclose($f);

	        $m = new $MODEL();
	        $COUNT = count(self::$Rows[$name]);
	        for($i=0; $i<$COUNT; $i++)
	        {
	        	$ROW = self::$Rows[$name][$i];
	        	foreach($ROW as $key => $value)
	        		$m[$i]->$key = $value;
	        }
	        $m->save_all();
		}
	}

	public static function Debug()
	{
		var_dump(self::$Models);
		var_dump(self::$Rows);
	}
}

class FixtureRow
{
	private $data = array();

	public function __construct($data = array())
	{
		$this->data = $data;
	}

	public function __set($key, $value)
	{
		$this->data[$key] = $value;
	}

	public function __get($key)
	{
		return $this->data[$key];
	}
}
