<?php
abstract class Model implements Iterator, ArrayAccess, Countable
{
	private $_unaltered_data		= array();
	private $_iterator_data 		= array();
	private $_iterator_position 	= 0;
    private $_unencrypted_data      = array();

	private $_readonly				= false;
	private $_association_key		= array();
	private $_child;
	private $_object_id;
	private $_driver_info 			= array();
    private $_validation_errors     = array();
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
<<<<<<< HEAD
	protected $OnActionCallbacks	= array();
=======
    protected $SerializeField  		= array();
	protected $OnActionCallbacks	= array();
    protected $Validate             = array();
>>>>>>> Version 0.0.4
	//# Association Properties
	protected $BelongsTo			= array();
	protected $HasOne				= array();
	protected $HasMany				= array();
	protected $HasAndBelongsToMany	= array();
	

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
				{
					$TABLE_NAME = get_class($this);
					$TABLE_NAME = preg_replace('/\B([A-Z])/', '_$1', $TABLE_NAME);
					$this->TableName = strtolower($TABLE_NAME);
				}
				self::$_static_info[$this->_child]['driver']->setTableName($this->TableName);
				self::$_static_info[$this->_child]['driver']->setPrimaryKey($this->PrimaryKey);
			}
		}

		$this->PrimaryKey = self::$_static_info[$this->_child]['driver']->getPrimaryKey();
		$this->_object_id = md5($this->_child.rand(0, 9999).date('YmdHis'));
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

	public function get_raw($key)
	{
		if(!array_key_exists($key, $this->_iterator_data[$this->_iterator_position]))
		{
			trigger_error(__CLASS__."::".__FUNCTION__." No field by the name [".$name."]", E_USER_NOTICE);
			return null;
		}
		return $this->_iterator_data[$this->_iterator_position][$key];
	}

	public function __get($key)
	{
		if(!array_key_exists($this->_iterator_position, $this->_iterator_data))
			return null;
		if(!array_key_exists($key, $this->_iterator_data[$this->_iterator_position]))
		{
			$SUCCESS = false;
			if(SKY::singularize($key) === false) // Key is Singular
			{
				if(array_key_exists($key, $this->BelongsTo))
				{
					$SUCCESS = $this->_BelongsTo($key);
				}
				if(array_key_exists($key, $this->HasOne))
				{
					$SUCCESS = $this->_HasOne($key);
				}
			} else { // Key is plural
				if(array_key_exists($key, $this->HasMany))
				{
					$SUCCESS = $this->_HasMany($key);
				}
				if(array_key_exists($key, $this->HasAndBelongsToMany))
				{
					$SUCCESS = $this->_HasAndBelongsToMany($key);
				}
			}
			if($SUCCESS)
				return $this->_iterator_data[$this->_iterator_position][$key];
			return null;
		}
		if(array_key_exists($key, $this->OutputFormat))
		{
			if(method_exists($this, $this->OutputFormat[$key]))
			{
				return call_user_func(array($this, $this->OutputFormat[$key]), $this->_iterator_data[$this->_iterator_position][$key]);
			} else {
				return sprintf($this->OutputFormat[$key], $this->_iterator_data[$this->_iterator_position][$key]);
			}
		}
		return $this->_iterator_data[$this->_iterator_position][$key];
	}

	public function __set($key, $value)
	{
		if(!isset($this->_unaltered_data[$this->_iterator_position]) && isset($this->_iterator_data[$this->_iterator_position]) && array_key_exists($key, $this->_iterator_data[$this->_iterator_position]))
		{
			$this->_unaltered_data[$this->_iterator_position] = $this->_iterator_data[$this->_iterator_position];
		}

		if(array_key_exists($key, $this->InputFormat))
		{
			if(method_exists($this, $this->InputFormat[$key]))
			{
				$this->_iterator_data[$this->_iterator_position][$key] = call_user_func(array($this, $this->InputFormat[$key]), $value);
			} else {
				$this->_iterator_data[$this->_iterator_position][$key] = sprintf($this->InputFormat[$key], $value);
			}
		}
		elseif(in_array($key, $this->EncryptField))
		{
            if(!isset($this->_unencrypted_data[$this->_iterator_position]))
                $this->_unencrypted_data[$this->_iterator_position] = array();
            $this->_unencrypted_data[$this->_iterator_position][$key] = $value;
			$this->_iterator_data[$this->_iterator_position][$key] = $this->Encrypt($value);
		}
		else
		{
			$this->_iterator_data[$this->_iterator_position][$key] = $value;
		}
	}

	public function __isset($key)
	{
		return isset($this->_iterator_data[$this->_iterator_position][$key]);
	}

	public function __unset($key)
	{
		unset($this->_iterator_data[$this->_iterator_position][$key]);
	}

    //############################################################
    //# Validation Methods
	//############################################################

    public function is_valid()
    {
        $VALID = true;
        foreach($this->Validate as $field => $options)
        {
            $REQUIRED_PASS = true;
            foreach($options as $type => $value)
            {
                if($type == ':required')
                {
                    $field_value = $this->_iterator_data[$this->_iterator_position][$field];
                    if(in_array($field, $this->EncryptField))
                        $field_value = $this->_unencrypted_data[$this->_iterator_position][$field];
                    if(!array_key_exists($field, $this->_iterator_data[$this->_iterator_position]) || empty($field_value))
                    {
                        $this->_ValidationError($field, 'ABSENT');
                        $REQUIRED_PASS = false;
                        $VALID = false;
                    }
                }
                if($REQUIRED_PASS && $type == ':regex')
                {
                    $field_value = $this->_iterator_data[$this->_iterator_position][$field];
                    if(in_array($field, $this->EncryptField))
                        $field_value = $this->_unencrypted_data[$this->_iterator_position][$field];
                    if(!(bool)preg_match($value, $field_value))
                    {
                        $this->_ValidationError($field, 'REGEX_FAILED');
                        $VALID = false;
                    }
                }
                if($REQUIRED_PASS && $type == ':min')
                {
                    $field_value = $this->_iterator_data[$this->_iterator_position][$field];
                    if(in_array($field, $this->EncryptField))
                        $field_value = $this->_unencrypted_data[$this->_iterator_position][$field];
                    if(strlen($field_value) < $value)
                    {
                        $this->_ValidationError($field, 'TOO_SHORT');
                        $VALID = false;
                    }
                }
                if($REQUIRED_PASS && $type == ':max')
                {
                    $field_value = $this->_iterator_data[$this->_iterator_position][$field];
                    if(in_array($field, $this->EncryptField))
                        $field_value = $this->_unencrypted_data[$this->_iterator_position][$field];
                    if(strlen($field_value) > $value)
                    {
                        $this->_ValidationError($field, 'TOO_LONG');
                        $VALID = false;
                    }
                }
                if($REQUIRED_PASS && $type == ':unique')
                {
                    $CHILD = $this->_child;
                    $obj = new $CHILD();
                    $r = $obj->search(array($field => $this->_iterator_data[$this->_iterator_position][$field]))->run();
                    $SEARCH_PRI = $r->getPrimaryKey();
                    $MY_PRI = $this->getPrimaryKey();
                    if(($r->$SEARCH_PRI != $this->$MY_PRI) && count($r) > 0)
                    {
                        $this->_ValidationError($field, 'NOT_UNIQUE');
                        $VALID = false;
                    }
                }
                if($REQUIRED_PASS && $type == ':confirm')
                {
                    if(array_key_exists($value, $this->_iterator_data[$this->_iterator_position]))
                    {
                        $compare = $this->_iterator_data[$this->_iterator_position][$value];
                        if(in_array($field, $this->EncryptField))
                            $compare = $this->Encrypt($compare);
                        if($this->_iterator_data[$this->_iterator_position][$field] != $compare)
                        {
                            $this->_ValidationError($field, 'NOT_CONFIRMED');
                            $VALID = false;
                        }
                    } else {
                        $this->_ValidationError($field, 'NOT_CONFIRMED');
                        $VALID = false;
                    }
                }
            }
        }
        return $VALID;
    }
    
    private function _ValidationError($on, $type)
    {
        if(!isset($this->_validation_errors[$on]))
            $this->_validation_errors[$on] = array();
        $this->_validation_errors[$on][] = $type;
    }
    
    public function GetValidationErrors()
    {
        return $this->_validation_errors;
    }

	//############################################################
	//# Association Methods
	//############################################################

	public function setAssociationKey($key_value)
	{
		$this->_association_key = array(
			'key' => $key_value['key'],
			'value' => $key_value['value']
		);
	}

	public function getPrimaryKey()
	{
		return $this->PrimaryKey;
	}

	private function _GetModel($model_name)
	{
		if(strpos($model_name, '_') !== false) $model_name = SKY::UnderscoreToUpper($model_name);
		if(SKY::singularize($model_name) === false) $model_name = SKY::pluralize($model_name);
		$model_name = ucfirst($model_name);
		return new $model_name();
	}

	private function _BelongsToPolymorphic($model_name)
	{
		$PARENT_ID	 = $this->_iterator_data[$this->_iterator_position][strtolower($model_name.'_id')];
		$PARENT_TYPE = $this->_iterator_data[$this->_iterator_position][strtolower($model_name.'_type')];
		$obj = $this->_GetModel($PARENT_TYPE);
		if($obj instanceof Model)
		{
			$SEARCH = array(
				$obj->getPrimaryKey() => $PARENT_ID
			);
			$r = $obj->findOne($SEARCH)->run();
			$this->_iterator_data[$this->_iterator_position][$original_name] = $r;
			return true;
		}
		return false;
	}
	
	private function _BelongsTo($model_name)
	{
		$original_name = $model_name;
		$OPTIONS = $this->BelongsTo[$original_name];
		if(!is_array($OPTIONS)) $OPTIONS = array();
		if(array_key_exists(':polymorphic', $OPTIONS))
			return $this->_BelongsToPolymorphic($original_name);
		if(array_key_exists(':model_name', $OPTIONS)) $model_name = $OPTIONS[':model_name'];
		$obj = $this->_GetModel($model_name);
		if($obj instanceof Model)
		{
			$FOREIGN_KEY = $original_name.'_id';
			if(array_key_exists(':foreign_key', $OPTIONS)) $FOREIGN_KEY = $OPTIONS[':foreign_key'];
			$SEARCH = array(
				$obj->getPrimaryKey() => $this->_iterator_data[$this->_iterator_position][$FOREIGN_KEY]
			);
			if(array_key_exists(':conditions', $OPTIONS))
			{
				$CONDITIONS = &$OPTIONS[':conditions'];
				$COUNT = count($CONDITIONS);
				for($i=0; $i<$COUNT; $i++)
					$SEARCH = array_merge($SEARCH, $CONDITIONS[$i]);
			}
			$r = $obj->findOne($SEARCH)->run();
			if(array_key_exists(':readonly', $OPTIONS)) $r->setToReadOnly();
			$this->_iterator_data[$this->_iterator_position][$original_name] = $r;
			return true;
		}
		return false;
	}

	private function _HasOne($model_name)
	{
		$original_name = $model_name;
		$OPTIONS = $this->HasOne[$original_name];
		if(!is_array($OPTIONS)) $OPTIONS = array();
		if(array_key_exists(':model_name', $OPTIONS)) $model_name = $OPTIONS[':model_name'];
		$obj = $this->_GetModel($model_name);
		if($obj instanceof Model)
		{
			$FOREIGN_KEY = SKY::singularize($this->_child).'_id';
			if(array_key_exists(':foreign_key', $OPTIONS)) $FOREIGN_KEY = $OPTIONS[':foreign_key'];
			$SEARCH = array(
				strtolower($FOREIGN_KEY) => $this->_iterator_data[$this->_iterator_position][$this->getPrimaryKey()]
			);
			if(array_key_exists(':through', $OPTIONS))
			{
				$MID_FOREIGN_KEY = strtolower($OPTIONS[':through'].'_id');
				$MID_obj = $this->_GetModel($OPTIONS[':through']);
				$PRI = $MID_obj->getPrimaryKey();
				$r = $MID_obj->search($SEARCH, array($MID_obj->getPrimaryKey()))->run();
				$SEARCH = array(
					$MID_FOREIGN_KEY => $r->$PRI
				);
			}
			if(array_key_exists(':conditions', $OPTIONS))
			{
				$CONDITIONS = &$OPTIONS[':conditions'];
				$COUNT = count($CONDITIONS);
				for($i=0; $i<$COUNT; $i++)
					$SEARCH = array_merge($SEARCH, $CONDITIONS[$i]);
			}
			$r = $obj->findOne($SEARCH)->run();
			$r->setAssociationKey(array(
				'key' => strtolower($FOREIGN_KEY),
				'value' => $this->_iterator_data[$this->_iterator_position][$this->getPrimaryKey()]
			));
			if(array_key_exists(':readonly', $OPTIONS)) $r->setToReadOnly();
			$this->_iterator_data[$this->_iterator_position][$original_name] = $r;
			return true;
		}
		return false;
	}

	private function _HasMany($model_name)
	{
		$original_name = $model_name;
		$OPTIONS = $this->HasMany[$original_name];
		if(!is_array($OPTIONS)) $OPTIONS = array();
		if(array_key_exists(':model_name', $OPTIONS)) $model_name = $OPTIONS[':model_name'];
		$obj = $this->_GetModel($model_name);
		if($obj instanceof Model)
		{
			$FOREIGN_KEY = strtolower(SKY::singularize($this->_child).'_id');
			if(array_key_exists(':as', $OPTIONS))
			{
				$FOREIGN_KEY = $OPTIONS[':as'].'_id';
			}
			else
			{
				if(array_key_exists(':foreign_key', $OPTIONS)) 
					$FOREIGN_KEY = strtolower($OPTIONS[':foreign_key']);
			}
			$SEARCH = array(
				$FOREIGN_KEY => $this->_iterator_data[$this->_iterator_position][$this->getPrimaryKey()]
			);
			if(array_key_exists(':as', $OPTIONS))
				$SEARCH[$OPTIONS[':as'].'_type'] = strtolower(SKY::singularize($this->_child));
			if(array_key_exists(':through', $OPTIONS))
			{
				$MID_FOREIGN_KEY = strtolower(SKY::singularize($model_name).'_id');
				$MID_obj = $this->_GetModel($OPTIONS[':through']);
				$r = $MID_obj->search($SEARCH, array($MID_FOREIGN_KEY))->run();
				$SEARCH = array(
					$obj->getPrimaryKey() => $r->$MID_FOREIGN_KEY
				);
			}
			if(array_key_exists(':conditions', $OPTIONS))
			{
				$CONDITIONS = &$OPTIONS[':conditions'];
				$COUNT = count($CONDITIONS);
				for($i=0; $i<$COUNT; $i++)
					$SEARCH = array_merge($SEARCH, $CONDITIONS[$i]);
			}
			$r = $obj->search($SEARCH)->run();
			$r->setAssociationKey(array(
				'key' => $FOREIGN_KEY, 
				'value' => $this->_iterator_data[$this->_iterator_position][$this->getPrimaryKey()]
			));
			if(array_key_exists(':readonly', $OPTIONS)) $r->setToReadOnly();
			$this->_iterator_data[$this->_iterator_position][$original_name] = $r;
			return true;
		}
		return false;
	}

	private function _HasAndBelongsToMany($model_name)
	{
		$original_name = $model_name; //Assemblies
		$OPTIONS = $this->HasAndBelongsToMany[$original_name];
		if(!is_array($OPTIONS)) $OPTIONS = array();
		if(array_key_exists(':model_name', $OPTIONS)) $model_name = $OPTIONS[':model_name'];
		$obj = $this->_GetModel($model_name);
		if($obj instanceof Model)
		{
			$TABLES = array(strtolower($this->_child), strtolower($model_name));
			sort($TABLES);
			$JOIN_TABLE = implode('_', $TABLES);
			$SEARCH = array(
				SKY::singularize(strtolower($this->_child)).'_id' => $this->_iterator_data[$this->_iterator_position][$this->getPrimaryKey()]
			);
			$JOIN_OBJ = $this->_GetModel($JOIN_TABLE);
			$MODEL_JOIN_KEY = strtolower(SKY::singularize($model_name).'_id');
			$r = $JOIN_OBJ->search($SEARCH, array($MODEL_JOIN_KEY))->run();

			$SEARCH = array(
				$obj->getPrimaryKey() => $r->$MODEL_JOIN_KEY
			);
			if(array_key_exists(':conditions', $OPTIONS))
			{
				$CONDITIONS = &$OPTIONS[':conditions'];
				$COUNT = count($CONDITIONS);
				for($i=0; $i<$COUNT; $i++)
					$SEARCH = array_merge($SEARCH, $CONDITIONS[$i]);
			}
			$r = $obj->search($SEARCH)->run();
			if(array_key_exists(':readonly', $OPTIONS)) $r->setToReadOnly();
			$this->_iterator_data[$this->_iterator_position][$original_name] = $r;
			return true;
		}
		return false;
	}

	//############################################################
	//# OnAction Methods
	//############################################################

	private function ExecuteActions($action)
	{
		if(!isset($this->OnActionCallbacks[$action]))
			return false;
		$STATUS = true;
		foreach($this->OnActionCallbacks[$action] as $callback)
		{
<<<<<<< HEAD
			$RETURN = call_user_func($callback);
=======
			$RETURN = call_user_func(array($this, $callback));
>>>>>>> Version 0.0.4
			if($STATUS === true && $RETURN === false)
				$STATUS = $RETURN;
		}
		return $STATUS;
	}

	private function OnAction($action, $callback)
	{
		if(!isset($this->OnActionCallbacks[$action]))
			$this->OnActionCallbacks[$action] = array();
		$this->OnActionCallbacks[$action][] = $callback;
	}

	public function OnDelete($callback)
	{
		$this->OnAction('delete', $callback);
	}

	public function OnDestroy($callback)
	{
		$this->OnAction('destroy', $callback);
	}

	public function OnSave($callback)
	{
		$this->OnAction('save', $callback);
	}

	public function OnUpdate($callback)
	{
		$this->OnAction('update', $callback);
	}

	//############################################################
	//# Run Methods
	//############################################################
	
	public function run()
	{
        if(!empty($this->SerializeField))
        {
            $tmp = self::$_static_info[$this->_child]['driver']->run();
            $c = count($tmp);
            $c_s = count($this->SerializeField);
            for($i=0;$i<$c;$i++)
            {
                for($x=0;$x<$c_s;$x++)
                {
                    $tmp[$i][$this->SerializeField[$x]] = unserialize($tmp[$i][$this->SerializeField[$x]]);
                }
            }
            $this->_iterator_data = $tmp;
        } else {
		    $this->_iterator_data = self::$_static_info[$this->_child]['driver']->run();
        }
        $this->_unaltered_data = $this->_iterator_data;
		return $this;
	}

	//############################################################
	//# Save Methods
	//############################################################
	
	public function setToReadOnly()
	{
		$this->_readonly = true;
	}

	public function create($hash = array())
	{
		$PRI = $this->getPrimaryKey();
<<<<<<< HEAD
		if(array_key_exists($PRI, $this->_iterator_data[$this->_iterator_position]))
=======
		if(isset($this->_iterator_data[$this->_iterator_position]) && array_key_exists($PRI, $this->_iterator_data[$this->_iterator_position]))
>>>>>>> Version 0.0.4
		{
			$this->_iterator_data[] = array();
			$this->_iterator_position = count($this->_iterator_data)-1;
		}
		if(isset($this->_association_key['key']))
		{
			$KEY = $this->_association_key['key'];
			$this->$KEY = $this->_association_key['value'];
		}
		foreach($hash as $key => $value)
			$this->$key = $value;
		return $this;
	}
    
    public function HasChanged($field)
    {
        return ($this->_unaltered_data[$this->_iterator_position][$field] != $this->_iterator_data[$this->_iterator_position][$field]);
    }
	
	public function save()
	{
		if($this->_readonly)
		{
			trigger_error('This record is set to ReadOnly mode!', E_USER_WARNING);
			return false;
		}
        if(!$this->is_valid())
        {
            Log::corewrite('Model object failed validation!', 1, __CLASS__, __FUNCTION__);
            // Not sure if an error is the correct action here...
            return false;
        }
		//# Update Record
		if(isset($this->_iterator_data[$this->_iterator_position][$this->PrimaryKey]))
		{
<<<<<<< HEAD
			$this->ExecuteActions('update');
=======
            Log::corewrite('Updating Model object.', 2, __CLASS__, __FUNCTION__);
			$this->ExecuteActions('update');
            $this->SerializeThis(true);
>>>>>>> Version 0.0.4
			$UPDATED = self::$_static_info[$this->_child]['driver']->update(
				$this->_unaltered_data, 
				$this->_iterator_data[$this->_iterator_position],
				$this->_iterator_position
			);
			$this->_iterator_data[$this->_iterator_position] = $UPDATED['updated'];
            $this->UnserializeThis();
            Log::corewrite('Ran update method on Driver [%s].', 2, __CLASS__, __FUNCTION__, array($UPDATED['status']));
			return $UPDATED['status'];
		//# Save New Record
		} else {
<<<<<<< HEAD
			$this->ExecuteActions('save');
=======
            Log::corewrite('Saving new Model object.', 2, __CLASS__, __FUNCTION__);
			$this->ExecuteActions('save');
            $this->SerializeThis();
>>>>>>> Version 0.0.4
			$DOCUMENT = self::$_static_info[$this->_child]['driver']->savenew(
				$this->_iterator_data[$this->_iterator_position]
			);
			$this->_iterator_data[$this->_iterator_position][$this->PrimaryKey] = $DOCUMENT['data'];
            $this->UnserializeThis();
            Log::corewrite('Ran save method on Driver [%s].', 2, __CLASS__, __FUNCTION__, array($DOCUMENT['pri']));
			return $DOCUMENT['pri'];
		}
	}
    
    private function SerializeThis($update = false)
    {
        if(!empty($this->SerializeField))
        {
            $c = count($this->SerializeField);
            for($x=0;$x<$c;$x++)
            {
                $this->_iterator_data[$this->_iterator_position][$this->SerializeField[$x]] = serialize($this->_iterator_data[$this->_iterator_position][$this->SerializeField[$x]]);
                if($update)
                    $this->_unaltered_data[$this->_iterator_position][$this->SerializeField[$x]] = serialize($this->_unaltered_data[$this->_iterator_position][$this->SerializeField[$x]]);
            }
        }
    }
    
    private function UnserializeThis()
    {
        if(!empty($this->SerializeField))
        {
            $c = count($this->SerializeField);
            for($x=0;$x<$c;$x++)
            {
                $this->_iterator_data[$this->_iterator_position][$this->SerializeField[$x]] = unserialize($this->_iterator_data[$this->_iterator_position][$this->SerializeField[$x]]);
            }
        }
    }

	public function save_all()
	{
		$RETURN = true;
		for($i = 0; $i < count($this->_iterator_data); $i++)
		{
			$this->_iterator_position = $i;
			$STATUS = $this->save();
			if((bool)$STATUS === false) $RETURN = false;
		}
		return $RETURN;
	}


	//############################################################
	//# Delete Methods
	//############################################################

	public function destroy()
	{
		if($this->delete())
		{
			$this->ExecuteActions('destroy');
			unset($this->_iterator_data[$this->_iterator_position]);
		}
	}
	
	public function delete()
	{
		if(isset($this->_iterator_data[$this->_iterator_position][$this->PrimaryKey]))
		{
			$this->ExecuteActions('delete');
			return self::$_static_info[$this->_child]['driver']->delete($this->_iterator_data[$this->_iterator_position][$this->PrimaryKey]);
		}
		return false;
	}

	public function delete_all()
	{
		$RETURN = true;
		for($i = 0; $i < count($this->_iterator_data); $i++)
		{
			$this->_iterator_position = $i;
			$STATUS = $this->delete();
			if((bool)$STATUS === false) $RETURN = false;
		}
		return $RETURN;
	}

	//############################################################
	//# Output Format Methods
	//############################################################
	
	public function Encrypt($value)
	{
		return md5(AUTH_SALT.$value);
	}

	//############################################################
	//# To_ Methods
	//############################################################
	
	public function to_array()
	{
        $array = array();
        foreach($this->_iterator_data[$this->_iterator_position] as $key => $value)
            $array[$key] = $this->$key;
		return $array;
	}

	public function to_set()
	{
        $c = count($this->_iterator_data);
        $set = array();
        for($i = 0; $i < $c; $i++)
        {
            $this->_iterator_position = $i;
            $set[] = $this->to_array();
        }
        $this->_iterator_position = 0;
		return $set;
	}

	public function is_null()
	{
		return empty($this->_iterator_data[$this->_iterator_position]);
	}

	//############################################################
	//# Countable Methods
	//############################################################
	
	public function count()
	{
		return count($this->_iterator_data);
	}
    
    public function size()
    {
        return count($this->_iterator_data[$this->_iterator_position]);
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
		if(is_null($offset))
		{
			++$this->_iterator_position;
			return $this->current();
		}
		$this->_iterator_position = $offset;
		return $this->current();
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
}
?>
