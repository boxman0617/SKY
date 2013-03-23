<?php
abstract class Model implements Iterator, ArrayAccess
{
	private $_unaltered_data		= array();
	private $_iterator_data 		= array();
	private $_iterator_position 	= 0;

	private $_child;
	private $_object_id;
	private $_driver_info 			= array();
	private static $_static_info 	= array();

	protected $DatabaseOverwrite 	= array(
		'DB_SERVER' 	=> null,
		'DB_USERNAME' 	=> null,
		'DB_PASSWORD' 	=> null,
		'DB_DATABASE' 	=> null,
		'MODEL_DRIVER' 	=> null
	);
	protected $TableName 			= null;
	protected $PrimaryKey 			= null;
	protected $OutputFormat			= array();
	protected $InputFormat			= array();
	protected $EncryptField			= array();

	//############################################################
	//# Magic Methods
	//############################################################
	
	public function __construct()
	{
		$this->_child = get_called_class();
		if(!isset(self::$_static_info[$this->_child])) self::$_static_info[$this->_child] = array();

		// # If driver for this Child Model is not set, instantiate!
		if(!isset(self::$_static_info[$this->_child]['driver']))
		{
			$_DB = array(
				'DB_SERVER'		=> DB_SERVER,
				'DB_USERNAME' 	=> DB_USERNAME,
				'DB_PASSWORD' 	=> DB_PASSWORD,
				'DB_DATABASE' 	=> DB_DATABASE,
				'MODEL_DRIVER' 	=> MODEL_DRIVER
			);
			if(isset($this->DatabaseOverwrite['MODEL_DRIVER'])) 
				$_DB = $this->DatabaseOverwrite;
			if(is_file(SKYCORE_CORE_MODEL."/drivers/".$_DB['MODEL_DRIVER'].".driver.php"))
			{
				import(SKYCORE_CORE_MODEL."/drivers/".$_DB['MODEL_DRIVER'].".driver.php");
				$_DRIVER_CLASS = $_DB['MODEL_DRIVER'].'Driver';
				self::$_static_info[$this->_child]['driver'] = new $_DRIVER_CLASS($_DB);
				if(is_null($this->TableName))
					$this->TableName = strtolower(get_class($this));
				self::$_static_info[$this->_child]['driver']->setTableName($this->TableName);
				self::$_static_info[$this->_child]['driver']->setPrimaryKey($this->PrimaryKey);
			}
		}

		$this->PrimaryKey = self::$_static_info[$this->_child]['driver']->getPrimaryKey();
		$this->_object_id = md5($this->_child.rand(0, 9999));
		self::$_static_info[$this->_child]['driver']->buildModelInfo($this);
	}

	public function __call($method, $args)
	{
		if(method_exists(self::$_static_info[$this->_child]['driver'], $method))
		{
			call_user_func_array(array(self::$_static_info[$this->_child]['driver'], $method), $args);
			return $this;
		}
	}

	public function &__GetDriverInfo($hash_key, $default = array())
	{
		if(!isset($this->_driver_info[$hash_key])) $this->_driver_info[$hash_key] = $default;
		return $this->_driver_info[$hash_key];
	}

	public function __get($key)
	{
		if(!array_key_exists($this->_iterator_position, $this->_iterator_data))
			return null;
		if(!array_key_exists($key, $this->_iterator_data[$this->_iterator_position]))
		{
			// @ToDo: Put association code here...
			return null;
		}
		if(array_key_exists($key, $this->OutputFormat))
		{
			// @ToDo: Put output format code here...
		}
		return $this->_iterator_data[$this->_iterator_position][$key];
	}

	public function __set($key, $value)
	{
		if(isset($this->_iterator_data[$this->_iterator_position]) && array_key_exists($key, $this->_iterator_data[$this->_iterator_position]))
		{
			$this->_unaltered_data[$this->_iterator_position] = $this->_iterator_data[$this->_iterator_position];
		}

		if(array_key_exists($key, $this->InputFormat))
		{
			// @ToDo: Put input format code here...
		}
		elseif(in_array($key, $this->EncryptField))
		{
			// @ToDo: Put encryption format code here...
		}
		else
		{
			$this->_iterator_data[$this->_iterator_position][$key] = $value;
		}
	}

	//############################################################
	//# Run Methods
	//############################################################
	
	public function run()
	{
		$this->_iterator_data = self::$_static_info[$this->_child]['driver']->run();
		return $this;
	}

	//############################################################
	//# Save Methods
	//############################################################
	
	public function save()
	{
		//# Update Record
		if(isset($this->_iterator_data[$this->_iterator_position][$this->PrimaryKey]))
		{
			$UPDATED = self::$_static_info[$this->_child]['driver']->update(
				$this->_unaltered_data, 
				$this->_iterator_data[$this->_iterator_position],
				$this->_iterator_position
			);
			$this->_iterator_data[$this->_iterator_position] = $UPDATED['updated'];
			return $UPDATED['status'];
		//# Save New Record
		} else {
			$DOCUMENT = self::$_static_info[$this->_child]['driver']->savenew(
				$this->_iterator_data[$this->_iterator_position]
			);
			$this->_iterator_data[$this->_iterator_position][$this->PrimaryKey] = $DOCUMENT['data'];
			return $DOCUMENT['pri'];
		}
	}

	public function save_all()
	{

	}

	//############################################################
	//# To_ Methods
	//############################################################
	
	public function to_array()
	{
		return $this->_iterator_data[$this->_iterator_position];
	}

	public function to_set()
	{
		return $this->_iterator_data;
	}

	//############################################################
	//# Iterator Methods
	//############################################################

	public function current()
	{
		return $this;
	}

	public function key()
	{
		return $this->_iterator_position;
	}

	public function next()
	{
		++$this->_iterator_position;
	}

	public function rewind()
	{
		$this->_iterator_position = 0;
	}

	public function valid()
	{
		return isset($this->_iterator_data[$this->_iterator_position]);
	}

	//############################################################
	//# ArrayAccess Methods
	//############################################################
	
	public function offsetExists($offset)
	{
		return isset($this->_iterator_data[$offset]);
	}

	public function offsetGet($offset)
	{
		if(isset($this->_iterator_data[$offset]))
		{
			$this->_iterator_position = $offset;
			return $this->current();
		}
		return null;
	}

	public function offsetSet($offset, $value)
	{
		if(is_null($offset))
			$this->_iterator_data[] = $value;
		else
			$this->_iterator_data[$offset] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->_iterator_data[$offset]);
	}

	public function ResultCount()
	{
		return count($this->_iterator_data);
	}
}
?>