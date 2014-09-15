<?php
define('TEXT_FIELD', '_field_text');
define('TEXTAREA_FIELD', '_field_textarea');
define('SELECT_FIELD', '_field_select');
define('CHECKBOX_FIELD', '_field_checkbox');
define('RADIO_FIELD', '_field_radio');
define('PASSWORD_FIELD', '_field_password');
define('FILE_FIELD', '_field_file');

//SkyL::Import(VALIDATION_CLASS);

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
    protected $ValidationErrorMessages = array(
        'ABSENT'        => 'This field is required!',
        'REGEX_FAILED'  => 'The input you have given seems to be invalid.',
        'TOO_SHORT'     => 'The input you have given seems to be too short.',
        'TOO_LONG'      => 'The input you have given seems to be too long.',
        'NOT_UNIQUE'    => 'Input must be unique!',
        'NOT_CONFIRMED' => 'Input does not match!'
    );
    protected $ValidationCustomMessages = array();
	protected $TableName 			= null;
	protected $PrimaryKey 			= null;
	protected $OutputFormat			= array();
	protected $InputFormat			= array();
	protected $EncryptField			= array();
    protected $SerializeField  		= array();
	protected $OnActionCallbacks	= array();
    protected $Validate             = array();
	//# Association Properties
	protected $BelongsTo			= array();
	protected $HasOne				= array();
	protected $HasMany				= array();
	protected $HasAndBelongsToMany	= array();
	//# Model Display Properties
    protected $DisplayAsTableViews  = array();

	//############################################################
	//# Magic Methods
	//############################################################
	
	public function __construct()
	{
        if(method_exists($this, 'preinit'))
            $this->preinit();
		$this->_child = get_called_class();
		if(!isset(self::$_static_info[$this->_child])) self::$_static_info[$this->_child] = array();

		// # If driver for this Child Model is not set, instantiate!
		if(!isset(self::$_static_info[$this->_child]['driver']))
		{
            $db = AppConfig::GetDatabaseSettings();
			$_DB = array(
				'DB_SERVER'		=> $db[':server'],
				'DB_USERNAME' 	=> $db[':username'],
				'DB_PASSWORD' 	=> $db[':password'],
				'DB_DATABASE' 	=> $db[':database'],
				'MODEL_DRIVER' 	=> $db[':driver']
			);
			if(isset($this->DatabaseOverwrite['MODEL_DRIVER'])) 
				$_DB = $this->DatabaseOverwrite;
			if(is_file(SkyDefines::Call('SKYCORE_CORE_MODEL')."/drivers/".$_DB['MODEL_DRIVER'].".driver.php"))
			{
				SkyL::Import(SkyDefines::Call('SKYCORE_CORE_MODEL')."/drivers/".$_DB['MODEL_DRIVER'].".driver.php");
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
        if(method_exists($this, 'init'))
            $this->init();
	}
	
	public static function __set_state($an_array)
	{
		return $this->_iterator_data;
	}
	
	public function __debugInfo()
	{
		return $this->_iterator_data;
	}

    public function __toString()
    {
        if($this->count() > 0)
        {
            $PRI = $this->getPrimaryKey();
            return call_user_func('RouteTo::'.strtoupper(SKY::singularize($this->_child)), $this->$PRI);
        }
        return $this->_child;
    }
    
    public static function __callStatic($method, $args)
    {
        Log::corewrite('Static method call for [%s]', 2, __CLASS__, __FUNCTION__, array($method));
        if(substr($method, 0, 6) === 'FindBy' || substr($method, 0, 9) === 'FindOneBy')
        {
            $rest = substr($method, 6);
            if(substr($method, 0, 9) === 'FindOneBy')
                $rest = substr($method, 9);
            $parts = explode('And', $rest);
            $QUERY = array();
            foreach($parts as $k => $p)
            {
                $FIELD = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $p));
                $QUERY[$FIELD] = $args[$k];
            }
            $CHILD = get_called_class();
            $OBJ = new $CHILD();
            if(substr($method, 0, 9) === 'FindOneBy')
                return $OBJ->search($QUERY)->limit(1)->run();
            return $OBJ->search($QUERY)->run();
        }
        elseif(substr($method, 0, 6) === 'Search')
        {
            $CHILD = get_called_class();
            $OBJ = new $CHILD();
            return $OBJ->search((array)$args[0])->run();
        }
    }

	public function __call($method, $args)
	{
		if(method_exists(self::$_static_info[$this->_child]['driver'], $method))
		{
			call_user_func_array(array(self::$_static_info[$this->_child]['driver'], $method), $args);
			return $this;
		}
        else if(substr($method, 0, 9) === "CreateNew") 
        {
            $what = substr($method, 9);
            $what = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $what));
            if(array_key_exists(SKY::pluralize($what), $this->HasMany) || array_key_exists($what, $this->HasOne))
            {
                if(empty($args))
                    $args = array(array());
                return $this->_CreateNewAssociatedModel($this->_GetModel($what), $args[0]);
            } else {
                throw new ModelAssociationException("No association to Model [".$what."]. Unable to create associated new model.");
                return false;
            }
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
		    throw new ModelIOException('No field by the name ['.$name.']');
			return null;
		}
		return $this->_iterator_data[$this->_iterator_position][$key];
	}
	
	public function get_preupdate_data($key)
	{
	    if(array_key_exists($key, $this->_unaltered_data[$this->_iterator_position]))
	    {
	        return $this->_unaltered_data[$this->_iterator_position][$key];
	    }
	}

	public function __get($key)
	{
        if($this->_iterator_position != 0 && $this->_iterator_position >= count($this->_iterator_data))
            throw new ModelIOException('The Iterator Position is out of range in the Model. /* Tip: try ::rewind() */ ['.$this->_child.'::'.$key.']['.$this->_iterator_position.']['.count($this->_iterator_data).']');
		if(!array_key_exists($this->_iterator_position, $this->_iterator_data))
            throw new ModelIOException('No data is present in Model. ['.$this->_child.'::'.$key.']['.$this->_iterator_position.']');
		if(!array_key_exists($key, $this->_iterator_data[$this->_iterator_position]))
		{
            if(method_exists($this, 'Get'.ucfirst($key)))
            {
                return call_user_func(array($this, 'Get'.ucfirst($key)));
            }
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
            
            $_associations = array(
                '_BelongsTo' => $this->BelongsTo,
                '_HasOne' => $this->HasOne,
                '_HasMany' => $this->HasMany,
                '_HasAndBelongsToMany' => $this->HasAndBelongsToMany
            );
            foreach($_associations as $_type => $_association)
            {
                if(array_key_exists($key, $_association))
                {
                    if(call_user_func(array($this, $_type), $key))
                        return $this->_iterator_data[$this->_iterator_position][$key];
                }
            }
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
	
	public function responds_to($name)
	{
	    if(array_key_exists($name, $this->_iterator_data[$this->_iterator_position]))
	        return true;
	    return method_exists($this, $name);
	}

	public function __unset($key)
	{
		unset($this->_iterator_data[$this->_iterator_position][$key]);
	}

    public function get_child()
    {
        return $this->_child;
    }

    //############################################################
    //# Validation Methods
	//############################################################

    // public function is_valid($skip_confirm = false)
    // {
    //     $validation = Validation::Validate($this->_iterator_data[$this->_iterator_position], $this->Validate, $this->_child);
    //     return $validation->Status;
    // }

    public function does_exists($field)
    {
        return (array_key_exists($field, $this->_iterator_data[$this->_iterator_position]) && !empty($this->_iterator_data[$this->_iterator_position][$field]));
    }

    public function is_required($field)
    {
        return (array_key_exists($field, $this->Validate) && array_key_exists(':required', $this->Validate[$field]));
    }

    public function is_valid($skip_confirm = false)
    {
        $VALID = true;
        foreach($this->Validate as $field => $options)
        {
            $REQUIRED_PASS = true;
            $IS_REQUIRED = false;
            foreach($options as $type => $value)
            {
                if($type == ':messages') continue;
                if($type == ':required')
                {
                    $IS_REQUIRED = true;
                    if(!array_key_exists($field, $this->_iterator_data[$this->_iterator_position]))
                    {
                        if(array_key_exists(':messages', $options) && array_key_exists('ABSENT', $options[':messages']))
                            $this->_ValidationError($field, 'ABSENT', $options[':messages']['ABSENT']);
                        else
                            $this->_ValidationError($field, 'ABSENT');
                        
                        $REQUIRED_PASS = false;
                        $VALID = false;
                    } else {
                        $field_value = $this->_iterator_data[$this->_iterator_position][$field];
                        if(in_array($field, $this->EncryptField) && array_key_exists($this->_iterator_position, $this->_unencrypted_data))
                            $field_value = $this->_unencrypted_data[$this->_iterator_position][$field];
                        if(!array_key_exists($field, $this->_iterator_data[$this->_iterator_position]) || empty($field_value))
                        {
                            if(array_key_exists(':messages', $options) && array_key_exists('ABSENT', $options[':messages']))
                                $this->_ValidationError($field, 'ABSENT', $options[':messages']['ABSENT']);
                            else
                                $this->_ValidationError($field, 'ABSENT');
                            $REQUIRED_PASS = false;
                            $VALID = false;
                        }
                    }
                }
                if($type == ':regex')
                {
                    if(!$IS_REQUIRED && !$this->does_exists($field))
                        continue;
                    elseif($IS_REQUIRED && !$REQUIRED_PASS)
                        continue;
                    else {
                        $field_value = $this->_iterator_data[$this->_iterator_position][$field];
                        if(in_array($field, $this->EncryptField))
                            $field_value = $this->_unencrypted_data[$this->_iterator_position][$field];
                        if(!(bool)preg_match($value, $field_value))
                        {
                            if(array_key_exists(':messages', $options) && array_key_exists('REGEX_FAILED', $options[':messages']))
                                $this->_ValidationError($field, 'REGEX_FAILED', $options[':messages']['REGEX_FAILED']);
                            else
                                $this->_ValidationError($field, 'REGEX_FAILED');
                            $VALID = false;
                        }
                    }
                }
                if($type == ':min')
                {

                    if(!$IS_REQUIRED && !$this->does_exists($field))
                        continue;
                    elseif($IS_REQUIRED && !$REQUIRED_PASS)
                        continue;
                    else {
                        $field_value = $this->_iterator_data[$this->_iterator_position][$field];
                        if(in_array($field, $this->EncryptField))
                            $field_value = $this->_unencrypted_data[$this->_iterator_position][$field];
                        if(strlen($field_value) < $value)
                        {
                            if(array_key_exists(':messages', $options) && array_key_exists('TOO_SHORT', $options[':messages']))
                                $this->_ValidationError($field, 'TOO_SHORT', $options[':messages']['TOO_SHORT']);
                            else
                                $this->_ValidationError($field, 'TOO_SHORT');
                            $VALID = false;
                        }
                    }
                }
                if($REQUIRED_PASS && $type == ':max')
                {
                    if(!$IS_REQUIRED && !$this->does_exists($field))
                        continue;
                    elseif($IS_REQUIRED && !$REQUIRED_PASS)
                        continue;
                    else {
                        $field_value = $this->_iterator_data[$this->_iterator_position][$field];
                        if(in_array($field, $this->EncryptField))
                            $field_value = $this->_unencrypted_data[$this->_iterator_position][$field];
                        if(strlen($field_value) > $value)
                        {
                            if(array_key_exists(':messages', $options) && array_key_exists('TOO_LONG', $options[':messages']))
                                $this->_ValidationError($field, 'TOO_LONG', $options[':messages']['TOO_LONG']);
                            else
                                $this->_ValidationError($field, 'TOO_LONG');
                            $VALID = false;
                        }
                    }
                }
                if($REQUIRED_PASS && $type == ':unique')
                {
                    if(!$IS_REQUIRED && !$this->does_exists($field))
                        continue;
                    elseif($IS_REQUIRED && !$REQUIRED_PASS)
                        continue;
                    else {
                        $CHILD = $this->_child;
                        $obj = new $CHILD();
                        $r = $obj->search(array(
                            $field => $this->_iterator_data[$this->_iterator_position][$field]
                        ))->run();
                        $PRI = $r->getPrimaryKey();
                        if(count($r) > 0)
                        {
                            // If TRUE, this is NOT a new Model
                            if(array_key_exists($PRI, $this->_iterator_data[$this->_iterator_position]))
                            {
                                if($this->$PRI != $r->$PRI)
                                {
                                    if(array_key_exists(':messages', $options) && array_key_exists('NOT_UNIQUE', $options[':messages']))
                                        $this->_ValidationError($field, 'NOT_UNIQUE', $options[':messages']['NOT_UNIQUE']);
                                    else
                                        $this->_ValidationError($field, 'NOT_UNIQUE');
                                    $VALID = false;
                                }
                            } else {
                                if(array_key_exists(':messages', $options) && array_key_exists('NOT_UNIQUE', $options[':messages']))
                                    $this->_ValidationError($field, 'NOT_UNIQUE', $options[':messages']['NOT_UNIQUE']);
                                else
                                    $this->_ValidationError($field, 'NOT_UNIQUE');
                                $VALID = false;
                            }
                        }
                    }
                }
                if($REQUIRED_PASS && $type == ':confirm')
                {
                    if(!$IS_REQUIRED && !$this->does_exists($field))
                        continue;
                    elseif($IS_REQUIRED && !$REQUIRED_PASS)
                        continue;
                    else {
                        if($skip_confirm)
                            continue;
                        if(array_key_exists($value, $this->_iterator_data[$this->_iterator_position]))
                        {
                            $compare = $this->_iterator_data[$this->_iterator_position][$value];
                            if(in_array($field, $this->EncryptField))
                                $compare = $this->Encrypt($compare);
                            if($this->_iterator_data[$this->_iterator_position][$field] != $compare)
                            {
                                if(array_key_exists(':messages', $options) && array_key_exists('NOT_CONFIRMED', $options[':messages']))
                                    $this->_ValidationError($field, 'NOT_CONFIRMED', $options[':messages']['NOT_CONFIRMED']);
                                else
                                    $this->_ValidationError($field, 'NOT_CONFIRMED');
                                $VALID = false;
                            }
                        } else {
                            if(array_key_exists(':messages', $options) && array_key_exists('NOT_CONFIRMED', $options[':messages']))
                                $this->_ValidationError($field, 'NOT_CONFIRMED', $options[':messages']['NOT_CONFIRMED']);
                            else
                                $this->_ValidationError($field, 'NOT_CONFIRMED');
                            $VALID = false;
                        }
                    }
                }
            }
        }
        if($VALID === false)
        {
            foreach($this->EncryptField as $field)
                unset($this->$field);
        }
        return $VALID;
    }
    
    private function _ValidationError($on, $type, $message = null)
    {
        if(!isset($this->_validation_errors[$on]))
            $this->_validation_errors[$on] = array();
        $this->_validation_errors[$on][] = $type;
        if(!is_null($message))
            $this->ValidationCustomMessages[$on] = array($type => $message);
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
    
    private function _CreateNewAssociatedModel($obj, $hash)
    {
        foreach($hash as $field => $value)
            $obj->$field = $value;
        $foreign_key = SKY::singularize(strtolower($this->_child)).'_id';
        $PRI = $this->getPrimaryKey();
        $obj->$foreign_key = $this->$PRI;
        return $obj;
    }

	private function _GetModel($model_name)
	{
		if(strpos($model_name, '_') !== false) $model_name = SKY::UnderscoreToUpper($model_name);
		//Log::corewrite('Is $model_name singular? [%s][%s]', 2, __CLASS__, __FUNCTION__, array($model_name, (SKY::singularize($model_name)) ? 'true' : 'false'));
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
			$this->_iterator_data[$this->_iterator_position][$model_name] = $r;
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
            $explode = explode('_', preg_replace('/\B([A-Z])/', '_$1', get_class($this)));
            $explode[count($explode)-1] = SKY::singularize($explode[count($explode)-1]);
            $f = implode('_', $explode);
			$FOREIGN_KEY = strtolower($f.'_id');
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
                $explode = explode('_', $model_name);
                $explode[count($explode)-1] = SKY::singularize($explode[count($explode)-1]);
                $mid = implode('_', $explode);
				$MID_FOREIGN_KEY = strtolower($mid.'_id');
				$MID_obj = $this->_GetModel($OPTIONS[':through']);
                if(array_key_exists(':through_conditions', $OPTIONS))
                {
                    $TCONDITIONS = &$OPTIONS[':through_conditions'];
                    $COUNT = count($TCONDITIONS);
                    for($i=0; $i<$COUNT; $i++)
                        $SEARCH = array_merge($SEARCH, $TCONDITIONS[$i]);
                }
				$r = $MID_obj->search($SEARCH, array($MID_FOREIGN_KEY))->run();
                $IDs = array();
                foreach($r as $rs)
                    $IDs[] = $rs->$MID_FOREIGN_KEY;
				$SEARCH = array(
					$obj->getPrimaryKey() => $IDs
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
			$RETURN = call_user_func(array($this, $callback));
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
    
    public function OnRun($callback)
    {
		$this->OnAction('run', $callback);
	}

	//############################################################
	//# Run Methods
	//############################################################
    
    public static function all()
    {
        $CHILD = get_called_class();
        $OBJ = new $CHILD();
        return $OBJ->run();
    }
    
    public static function first()
    {
        $CHILD = get_called_class();
        $OBJ = new $CHILD();
        return $OBJ->limit(1)->run();
    }
	
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
        $this->ExecuteActions('run');
		return $this;
	}

	//############################################################
	//# Save Methods
	//############################################################
	
	public function setToReadOnly()
	{
		$this->_readonly = true;
	}
    
    public static function build($hash = array())
    {
        $CHILD = get_called_class();
        $OBJ = new $CHILD();
        return $OBJ->create($hash);
    }
    
    public function fill($hash = array())
    {
        if(isset($this->_iterator_data[$this->_iterator_position]))
        {
            foreach($hash as $field => $value)
            {
                if(array_key_exists($field, $this->_iterator_data[$this->_iterator_position]) && $this->_iterator_data[$this->_iterator_position][$field] !== $value)
                    $this->$field = $value;
            }
        } else {
            throw new ModelIOException('Unable to fill empty Model. Use [static]::build() or [public]::create() instead.');
        }
        return $this;
    }

	public function create($hash = array())
	{
		$PRI = $this->getPrimaryKey();
		if(isset($this->_iterator_data[$this->_iterator_position]) && array_key_exists($PRI, $this->_iterator_data[$this->_iterator_position]))
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
    
    public function GetOldValue($field)
    {
        return $this->_unaltered_data[$this->_iterator_position][$field];
    }
    
    public function HasChanged($field)
    {
        return ($this->_unaltered_data[$this->_iterator_position][$field] != $this->_iterator_data[$this->_iterator_position][$field]);
    }
    
    public function is_altered()
    {
        return ($this->_unaltered_data[$this->_iterator_position] !== $this->_iterator_data[$this->_iterator_position]);
    }
	
	public function save()
	{
		if($this->_readonly)
		    throw new ModelReadOnlyException();
		//# Update Record
		if(isset($this->_iterator_data[$this->_iterator_position][$this->PrimaryKey]))
		{
            Log::corewrite('Updating Model object.', 2, __CLASS__, __FUNCTION__);
			$this->ExecuteActions('update');
            $this->SerializeThis(true);
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
            // if(!$this->is_valid())
            // {
            //     Log::corewrite('Model object failed validation!', 1, __CLASS__, __FUNCTION__);
            //     // Not sure if an error is the correct action here...
            //     return false;
            // }
            Log::corewrite('Saving new Model object.', 2, __CLASS__, __FUNCTION__);
			$this->ExecuteActions('save');
            $this->SerializeThis();
			$DOCUMENT = self::$_static_info[$this->_child]['driver']->savenew(
				$this->_iterator_data[$this->_iterator_position]
			);
			$this->_iterator_data[$this->_iterator_position][$this->PrimaryKey] = $DOCUMENT['pri'];
            $this->UnserializeThis();
            $this->ExecuteActions('after_save');
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
            $this->_DeleteAssociations();
			return self::$_static_info[$this->_child]['driver']->delete($this->_iterator_data[$this->_iterator_position][$this->PrimaryKey]);
		}
		return false;
	}
    
    private function _DeleteAssociations()
    {
        foreach($this->HasMany as $MODEL => $OPTIONS)
        {
        	if(!is_array($OPTIONS))
        		$OPTIONS = array();
            if(array_key_exists(':dependent', $OPTIONS))
            {
                if($OPTIONS[':dependent'] === ':delete')
                {
                    foreach($this->$MODEL as $M)
                        $M->delete();
                }
            }
        }
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
    //# Model Render Methods
	//############################################################

	protected $FormFieldDir   = 'form_model';
	protected $FormFieldViews = array(
	    'text'      => null,
        'textarea'  => null,
	    'select'    => null,
	    'checkbox'  => null,
	    'radio'     => null,
	    'password'  => null,
	    'file'      => null
	);
	
	public function get_form_field($field, $type = TEXT_FIELD, $options = array())
	{
	    Log::corewrite('Getting form field', 3, __CLASS__, __FUNCTION__);
        $FIELD_TYPE = substr($type, 7, strlen($type));
        if(is_null($this->FormFieldViews[$FIELD_TYPE]))
            $this->FormFieldViews[$FIELD_TYPE] = $FIELD_TYPE.'field';
        if(method_exists($this, $type))
            call_user_func_array(array($this, $type), array($field, &$options));
        include(SkyDefines::Call('DIR_APP_VIEWS').'/'.$this->FormFieldDir.'/'.$this->FormFieldViews[$FIELD_TYPE].'.part.php');
        
	    //throw new UninitializedChildPropertyException('Property ::FormFieldViews['.$FIELD_TYPE.'] is null. Assign value to continue.');
	}
    
    private function _field_checkbox($field, $options)
    {
        
    }
    
    private function _field_radio($field, $options)
    {
        
    }
    
    private function _field_password($field, $options)
    {
        
    }

	//############################################################
	//# Output Format Methods
	//############################################################
	
	public function Encrypt($value)
	{
		return md5(AppConfig::GetAuthSalt().$value);
	}
    
    //############################################################
    //# Model Display Methods
    //############################################################
    
    public function to_table($view, $params = array())
    {
        if(!empty($this->DisplayAsTableViews))
        {
            if(array_key_exists($view, $this->DisplayAsTableViews))
            {
                extract($params);
                include_once(SkyDefines::Call('DIR_APP_VIEWS').'/'.$this->DisplayAsTableViews[$view]);
            } else {
                throw new ModelIOException('DisplayAsTableView property has no view by the name ['.$view.']!');
            }
        } else {
            throw new UninitializedChildPropertyException('DisplayAsTableView property is empty! Please assign a/some view(s) to this model to be able to use this feature.');
        }
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

    public function is_empty()
    {
        return empty($this->_iterator_data);
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
		return array_key_exists($this->_iterator_position, $this->_iterator_data);
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