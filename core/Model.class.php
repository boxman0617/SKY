<?php
/**
 * Model Core Class
 *
 * This class handles the data layer of your application.
 * It allows for different back ends like MySQL and others.
 *
 * LICENSE:
 *
 * This file may not be redistributed in whole or significant part, or
 * used on a web site without licensing of the enclosed code, and
 * software features.
 * 
 * @author Alan Tirado <root@deeplogik.com>
 * @copyright 2012 DeepLogiK, All Rights Reserved
 * @license http://www.deeplogik.com/sky/legal/license
 * @link http://www.deeplogik.com/sky/index
 * @version 1.0 Initial build
 * @version 1.1 Bug fixes
 * @version 2.0 Logic upgrade and added drivers
 * @package Sky.Core
 * @todo: Simplyfy the find methods. Example: find() find_by_%() find_all()
 */

/**
 * Model class
 * This class handles the data layer of your application
 * @package Sky.Core.Model
 */
abstract class Model implements Iterator
{
    /**
     * Driver that will be used with this object
     * @access private
     * @var string
     */
    private $driver;
    /**
     * Error Class Object
     * @access private
     * @var object
     */
    private $_error;
    /**
     * Driver Class Object
     * @access private
     * @var object
     */
    private $db;
    /**
     * Data for model
     * @access protected
     * @var array
     */
    protected $_data = array();
    /**
     * Name of table
     * @access protected
     * @var string
     */
    protected $table_name;
    /**
     * Schema of current table
     * @access protected
     * @var array
     */
    protected $table_schema = array();
    /**
     * Last query ran
     * @access protected
     * @var string
     */
    protected $last_query;
    /**
     * Query to be ran at Model::__get()
     * @access private
     * @var array
     */
    private $run_this = array();
    
    protected $belongs_to = array();
    /**
     * Relational property [this model has one on one relationship with]
     * @access protected
     * @var array
     */
    protected $has_one = array();
    /**
     * Relational property [this model has one on many relationship with]
     * @access protected
     * @var array
     */
    protected $has_many = array();
    /**
     * Fields to validate on save
     * @access protected
     * @var array
     */
    protected $validate = array();
    /**
     * Output formatting
     * @access public
     * @var array
     */
    public $output_format = array();
    /**
     * Input formatting
     * @access public
     * @var array
     */
    public $input_format = array();
    /**
     * Holds internal iterator position
     * @access private
     * @var integer
     */
    private $position = 0;
    /**
     * Holds internal iterator place
     * @access private
     * @var array
     */
    private $array = array();
    /**
     * [Query Builder] Holds select
     * @access protected
     * @var array
     */
    protected $select = array();
    /**
     * [Query Builder] Holds from
     * @access protected
     * @var array
     */
    protected $from = array();
    /**
     * [Query Builder] Holds joins
     * @access protected
     * @var array
     */
    protected $joins = array();
    /**
     * [Query Builder] Holds where
     * @access protected
     * @var array
     */
    protected $where = array();
    /**
     * [Query Builder] Holds limit
     * @access protected
     * @var array
     */
    protected $limit;
    /**
     * [Query Builder] Holds order by
     * @access protected
     * @var array
     */
    protected $orderby = array();
    /**
     * [Query Builder] Holds group by
     * @access protected
     * @var array
     */
    protected $groupby = array();
    protected $db_array = NULL;
    protected $_pre_data = array();
    protected $_skip_format_input = false;
    protected $encrypt_field = array();

    /**
     * Constructor sets up {@link $driver}, {@link $error}, and {@link $db}
     * @param array $hash Will set up model object with hash values
     */
    public function __construct($hash = array())
    {
        Log::corewrite('Starting Model [%s]', 3, __CLASS__, __FUNCTION__, array(get_class($this)));
        $this->driver = MODEL_DRIVER."Driver";
        $this->_error = ErrorHandler::Singleton(true);
        if(is_file(CORE_DIR."/drivers/".MODEL_DRIVER.".driver.php"))
        {
            Log::corewrite('Found driver [%s]', 1, __CLASS__, __FUNCTION__, array(MODEL_DRIVER));
            import(CORE_DIR."/drivers/".MODEL_DRIVER.".driver.php");
                    $this->db = new $this->driver($this->db_array);
            if(!$this->db instanceof iDriver)
                $this->_error->Toss('Driver loaded is not an instance of iDriver interface!', E_USER_ERROR);
            if(isset($this->table_name))
            {
                Log::corewrite('::$table_name is set [%s]', 1, __CLASS__, __FUNCTION__, array($this->table_name));
                $this->db->setTableName($this->table_name);
                $this->db->setSchema();
            } else {
                Log::corewrite('::$table_name is NOT set. Attempting to create name out of class', 1, __CLASS__, __FUNCTION__);
                if(!$this->db->doesTableExist(get_class($this)))
                    $this->_error->Toss('No table name specified. Please add property $table_name to model.', E_USER_ERROR);
                else
                {
                    $table_name = strtolower(get_class($this));
                    $this->table_name = $table_name;
                    $this->db->setTableName($this->table_name);
                    $this->db->setSchema();
                }
            }
            $this->table_schema = $this->db->getSchema();
            Log::corewrite('Model was set properly [%s]', 2, __CLASS__, __FUNCTION__, array(get_class($this)));
        } else {
            $this->_error->Toss('No driver found for model! Model: '.get_class($this).' | Driver: '.MODEL_DRIVER, E_USER_ERROR);
        }
        
        // Setting empty object
        if(empty($hash))
        {
            Log::corewrite('Creating empty Model object', 1, __CLASS__, __FUNCTION__);
            foreach($this->table_schema as $field => $i)
            {
                if(isset($i['Default']))
                    $this->_data[$field] = $i['Default'];
                else
                    $this->_data[$field] = NULL;
            }
        } else {
            Log::corewrite('Hash was passed in. Filling Model...', 1, __CLASS__, __FUNCTION__);
            foreach($this->table_schema as $field => $i)
            {
                if(isset($hash[$field]))
                    $this->_data[$field] = $hash[$field];
                else
                {
                    if(isset($i['Default']))
                        $this->_data[$field] = $i['Default'];
                    else
                        $this->_data[$field] = NULL;
                }
            }
        }
        Log::corewrite('At end of method...', 2, __CLASS__, __FUNCTION__);
    }

    /**
     * Creates where conditions from underscored string
     * @access private
     * @param string $name
     * @param array &$values default array()
     * @return string
     */
    private function create_conditions_from_underscored_string($name, &$values=array())
    {
        if (!$name)
                return null;

        $parts = preg_split('/(_and_|_or_)/i',$name,-1,PREG_SPLIT_DELIM_CAPTURE);
        $num_values = count($values);
        $conditions = array('');

        for ($i=0,$j=0,$n=count($parts); $i<$n; $i+=2,++$j)
        {
            if ($i >= 2)
                $conditions[0] .= preg_replace(array('/_and_/i','/_or_/i'),array(' AND ',' OR '),$parts[$i-1]);
            if ($j < $num_values)
            {
                if (!is_null($values[$j]))
                {
                    $bind = is_array($values[$j]) ? ' IN(?)' : '=?';
                    $conditions[] = $values[$j];
                }
                else
                    $bind = ' IS NULL';
            }
            else
                    $bind = ' IS NULL';
            // map to correct name if $map was supplied
            $name = $parts[$i];

            $conditions[0] .= $name . $bind;
        }
        return $conditions;
    }

    /**
     * Magic __call method
     * @access public
     * @param string $method
     * @param mixed $args
     *
     * If method is called on this object and it is not found
     * this method will be called.
     * If the method name starts with 'find_by' it will create a
     * Query using the rest of the method name.
     * @example http://www.deeplogik.com/sky/docs/examples/model
     */
    public function __call($method, $args)
    {
        if(substr($method, 0, 7) == 'find_by')
        {
            $options = substr($method, 8);
            //$fields = array_keys(self::$table_schema[$this->table_name]);
            $conditions = $this->create_conditions_from_underscored_string($options, $args);
            $obj = call_user_func_array(array($this, 'where'), $conditions);
            return $obj->run();
        } else {
            $this->_error->Toss('No method name ['.$method.']');
        }
    }

    /**
     * Magic setter sets up {@link $data}
     * @access public
     */
    public function __set( $name, $value )
    {
        Log::corewrite('Setting Model data [%s]', 3, __CLASS__, __FUNCTION__, array($name));
        if(isset($this->input_format[$name]) && !$this->_skip_format_input)
        {
            Log::corewrite('Has input format. Executing...', 1, __CLASS__, __FUNCTION__);
            if(is_array($this->input_format[$name]))
            {
                $this->_data[$name] = call_user_func(array($this, $this->input_format[$name]['custom']), $value);
            } else {
                $this->_data[$name] = sprintf($this->input_format[$name], $value);
            }
        }
        elseif(in_array($name, $this->encrypt_field))
        {
            Log::corewrite('Need to encrypt field. Executing...', 1, __CLASS__, __FUNCTION__);
            $this->_data[$name] = md5($value);
        }
        else
        {
            $this->_data[$name] = $value;
            Log::corewrite('Data was set normally...', 1, __CLASS__, __FUNCTION__);
        }
        Log::corewrite('At the end of method...', 2, __CLASS__, __FUNCTION__);
    }
	
    public function __isset( $name )
    {
            return (isset($this->_data[$name]) && $this->_data[$name] !== NULL);
    }
    
    public function __unset( $name )
    {
            $this->_data[$name] = NULL;
    }

    private function singularize($word)
    {
        $singular = array (
        '/(quiz)zes$/i' => '\1',
        '/(matr)ices$/i' => '\1ix',
        '/(vert|ind)ices$/i' => '\1ex',
        '/^(ox)en/i' => '\1',
        '/(alias|status)es$/i' => '\1',
        '/([octop|vir])i$/i' => '\1us',
        '/(cris|ax|test)es$/i' => '\1is',
        '/(shoe)s$/i' => '\1',
        '/(o)es$/i' => '\1',
        '/(bus)es$/i' => '\1',
        '/([m|l])ice$/i' => '\1ouse',
        '/(x|ch|ss|sh)es$/i' => '\1',
        '/(m)ovies$/i' => '\1ovie',
        '/(s)eries$/i' => '\1eries',
        '/([^aeiouy]|qu)ies$/i' => '\1y',
        '/([lr])ves$/i' => '\1f',
        '/(tive)s$/i' => '\1',
        '/(hive)s$/i' => '\1',
        '/([^f])ves$/i' => '\1fe',
        '/(^analy)ses$/i' => '\1sis',
        '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
        '/([ti])a$/i' => '\1um',
        '/(n)ews$/i' => '\1ews',
        '/s$/i' => '',
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

        return $word;
    }

    private function pluralize($word)
    {
        $plural = array(
        '/(quiz)$/i' => '1zes',
        '/^(ox)$/i' => '1en',
        '/([m|l])ouse$/i' => '1ice',
        '/(matr|vert|ind)ix|ex$/i' => '1ices',
        '/(x|ch|ss|sh)$/i' => '1es',
        '/([^aeiouy]|qu)ies$/i' => '1y',
        '/([^aeiouy]|qu)y$/i' => '1ies',
        '/(hive)$/i' => '1s',
        '/(?:([^f])fe|([lr])f)$/i' => '12ves',
        '/sis$/i' => 'ses',
        '/([ti])um$/i' => '1a',
        '/(buffal|tomat)o$/i' => '1oes',
        '/(bu)s$/i' => '1ses',
        '/(alias|status)/i'=> '1es',
        '/(octop|vir)us$/i'=> '1i',
        '/(ax|test)is$/i'=> '1es',
        '/s$/i'=> 's',
        '/$/'=> 's');

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
    
    private function FindModel($name, $plural = true, $overwrite = null)
    {
        if($plural)
            $name = $this->pluralize($name);

        if(!is_null($overwrite))
            $name = $overwrite;
        Log::corewrite('Finding Model out of field [%s]', 3, __CLASS__, __FUNCTION__, array($name));
        $file_list = implode(scandir(MODEL_DIR), '^');
        Log::corewrite('File list: [%s]', 3, __CLASS__, __FUNCTION__, array(strtolower($file_list)));
        $class = null;
        Log::corewrite('Looking for [%s] in list [%s]', 3, __CLASS__, __FUNCTION__, array(
            $name.'.model.php',
            var_export(strpos(strtolower($file_list), $name.'.model.php'), true)
        ));
        if(strpos(strtolower($file_list), $name.'.model.php'))
            $class = ucfirst($name);

        Log::corewrite('At the end of method... [%s]', 2, __CLASS__, __FUNCTION__, array(var_export($class, true)));
        return $class;
    }
    
    private function _getBelongsTo($name)
    {
        Log::corewrite('Getting BelongsTo data [%s]', 3, __CLASS__, __FUNCTION__, array($name));
        if(isset($this->belongs_to[$name]['table']))
            $class = $this->FindModel($name, false, $this->belongs_to[$name]['table']);
        else
            $class = $this->FindModel($name);
        if($class != null)
        {
            $PRI = $this->getPrimary();
            $obj = new $class();
            if(is_array($this->belongs_to[$name]))
                $ON = (isset($this->belongs_to[$name]['on']) ? $this->belongs_to[$name]['on'] : $name.'_id');
            else
                $ON = $name.'_id';
            Log::corewrite('At the end of method...', 2, __CLASS__, __FUNCTION__, array($name));
            return $obj->joins('LEFT JOIN `'.$this->table_name.'` ON (`'.$this->table_name.'`.`'.$ON.'` = `'.((isset($this->belongs_to[$name]['table'])) ? $this->belongs_to[$name]['table'] : $name.'s').'`.`id`)')
                ->where('`'.$this->table_name.'`.`'.$PRI.'` = ?', $this->_data[$PRI])
                ->run();
        } else {
            $this->_error->Toss(__CLASS__."::".__FUNCTION__." No Model by the name [".$name."]", E_USER_NOTICE);
            return null;
        }
    }
    
    private function _getHasOne($name)
    {
        Log::corewrite('Getting HasOne data [%s]', 3, __CLASS__, __FUNCTION__, array($name));
        if(isset($this->has_one[$name]['table']))
            $class = $this->FindModel($name, false, $this->has_one[$name]['table']);
        else
            $class = $this->FindModel($name);
        if($class != null)
        {
            $PRI = $this->getPrimary();
            $obj = new $class();
            if(is_array($this->has_one[$name]))
                $ON = (isset($this->has_one[$name]['on']) ? $this->has_one[$name]['on'] : $this->singularize($this->table_name).'_id');
            else
                $ON = $this->singularize($this->table_name).'_id';
            Log::corewrite('At the end of method...', 2, __CLASS__, __FUNCTION__, array($name));
            return $obj->where('`'.$ON.'` = ?', $this->_data[$PRI])->run();
        } else {
            $this->_error->Toss(__CLASS__."::".__FUNCTION__." No Model by the name [".$name."]", E_USER_NOTICE);
            return null;
        }
    }
    
    private function _getHasMany($name)
    {
        Log::corewrite('Getting HasMany data [%s]', 3, __CLASS__, __FUNCTION__, array($name));
        if(isset($this->has_many[$name]['table']))
            $class = $this->FindModel($name, false, $this->has_many[$name]['table']);
        else
            $class = $this->FindModel($name, false);
        if($class != null)
        {
            $PRI = $this->getPrimary();
            $obj = new $class();
            if(is_array($this->has_many[$name]))
                $ON = (isset($this->has_many[$name]['on']) ? $this->has_many[$name]['on'] : $this->singularize($this->table_name).'_id');
            else
                $ON = $this->singularize($this->table_name).'_id';
            Log::corewrite('At the end of method...', 2, __CLASS__, __FUNCTION__, array($name));
            return $obj->where('`'.$ON.'` = ?', $this->_data[$PRI])->run();
        } else {
            $this->_error->Toss(__CLASS__."::".__FUNCTION__." No Model by the name [".$name."]", E_USER_NOTICE);
            return null;
        }
    }

    /** Magic getter
     * - gets {@link $data}
     * - gets {@link $table_name}
     * - gets {@link $primary_key}
     * @access public
     * @return mixed
     */
    public function __get( $name )
    {
        Log::corewrite('Getting data from Model [%s]', 3, __CLASS__, __FUNCTION__, array($name));
        if(!isset($this->_data[$name]))
        {
            Log::corewrite('No data found. Checking associations...', 1, __CLASS__, __FUNCTION__);
            if(isset($this->belongs_to[$name]))
            {
                return $this->_getBelongsTo($name);
            }
            elseif(isset($this->has_one[$name]))
            {
                return $this->_getHasOne($name);
            }
            elseif(isset($this->has_many[$name]))
            {
                return $this->_getHasMany($name);
            }
            else
            {
                $this->_error->Toss(__CLASS__."::".__FUNCTION__." No field by the name [".$name."] in Model [".get_class($this)."]", E_USER_NOTICE);
                return null;
            }
        }
        Log::corewrite('Found data. Checking format output...', 2, __CLASS__, __FUNCTION__);
        if(isset($this->output_format[$name]))
        {
            Log::corewrite('Calling formating', 1, __CLASS__, __FUNCTION__);
            if(is_array($this->output_format[$name]))
            {
                return call_user_func(array($this, $this->output_format[$name]['custom']), $this->_data[$name]);
            } else {
                return sprintf($this->output_format[$name], $this->_data[$name]);
            }
        }
        Log::corewrite('At the end of method', 2, __CLASS__, __FUNCTION__);
        return $this->_data[$name];
    }
	
    public function get_raw($name)
    {
            if(!isset($this->_data[$name]))
    {
        $this->_error->Toss(__CLASS__."::".__FUNCTION__." No field by the name [".$name."]", E_USER_NOTICE);
        return null;
    }
            return $this->_data[$name];
    }
    
    public function find()
    {
        if(func_num_args() == 1 && is_numeric(func_get_arg(0)))
        {
            $pri = $this->getPrimary();
            $arg = func_get_arg(0);
            return $this->where($pri.' = ?', $arg);
        }
        elseif(func_num_args() == 1 && is_array(func_get_arg(0)))
        {
            $arg = func_get_arg(0);
            $obj = $this;
            if(isset($arg['where']))
                $obj = $obj->where($arg['where']);
            if(isset($arg['limit']))
                $obj = $obj->limit($arg['limit']);
            return $obj;
        }
    }
    
    public function find_all()
    {
        return $obj->all()->run();
    }
    
    public function fill($data)
    {
        foreach($data as $field => $value)
        {
            $this->$field = $value;
        }
        return $this;
    }
    
    /**
     * Dumps current {@link $data} values as an array
     * @return array $data
     */
    public function to_array()
    {
        Log::corewrite('Turning data into an array', 3, __CLASS__, __FUNCTION__);
        if(empty($this->output_format))
        {
            Log::corewrite('Returning fast data', 1, __CLASS__, __FUNCTION__);
            return $this->_data;
        }
        $ret = $this->_data;
        Log::corewrite('Returning slow data', 2, __CLASS__, __FUNCTION__);
        foreach($this->output_format as $field => $value)
        {
            Log::corewrite('Formatted output [%s]', 1, __CLASS__, __FUNCTION__, array($field));
            $ret[$field] = $this->$field;
        }
        return $ret;
    }

    public function to_set()
    {
        Log::corewrite('Turning data into a set', 3, __CLASS__, __FUNCTION__);
        $ret = array();
        foreach($this->array as $i)
        {
            $ret[] = $i->to_array();
        }
        return $ret;
    }

    /**
     * Validates before save using {@link $validate}
     * @access private
     * @return bool
     */
    private function validate()
    {
        foreach($this->validate as $field => $params)
        {
            if(isset($params['required']) && $params['required']) // Check if required
            {
                if(!isset($this->_data[$field]))
                    return false;
            }
            if(isset($params['must_be'])) // Check for type
            {
                if(isset($this->_data[$field]))
                {
                    switch($params['must_be'])
                    {
                        case 'integer':
                            if(!is_integer($this->_data[$field]))
                                return false;
                            break;
                        case 'bool':
                            if(!is_bool($this->_data[$field]))
                                return false;
                            break;
                        case 'string':
                            if(!is_string($this->_data[$field]))
                                return false;
                            break;
                        case 'float':
                            if(!is_float($this->_data[$field]))
                                return false;
                            break;
                    }
                }
            }
            if(isset($params['custom']))
            {
                if(method_exists($this, $params['custom']))
                {
                    if(!call_user_func(array($this, $params['custom']), $this->_data[$field]))
                        return false;
                }
            }
        }
        return true;
    }

    /**
     * Magic iterator method
     * Rewinds {@link $position} to 0
     * @access public
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Magic iterator method
     * Returns currect {@link $position} value
     * @access public
     * @return mixed
     */
    public function current()
    {
        return $this->array[$this->position];
    }

    /**
     * Magic iterator method
     * Returns {@link $position}
     * @access public
     * @return integer
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Magic iterator method
     * Increases {@link $position} by 1
     * @access public
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * Magic iterator method
     * Checks if array[position] is set
     * @access public
     * @return bool
     */
    public function valid()
    {
        return isset($this->array[$this->position]);
    }

    /**
     * Deletes current model from database
     * @access public
     * @return bool
     */
    public function delete()
    {
        Log::corewrite('Deleting record', 3, __CLASS__, __FUNCTION__);
        $pri = $this->getPrimary();
        foreach($this->has_one as $model => $options)
        {
            if(is_array($options) && isset($options['dependent']))
            {
                $this->_deleteHasOne($model);
            }
        }
        
        foreach($this->has_many as $model => $options)
        {
            if(is_array($options) && isset($options['dependent']))
            {
                $this->_deleteHasMany($model);
            }
        }
        Log::corewrite('At the end of method', 2, __CLASS__, __FUNCTION__);
        $ret = $this->db->delete($pri, $this->_data[$pri]);
        return $ret;
    }
    
    private function _deleteHasOne($name)
    {
        $class = $this->FindModel($name);
        if($class != null)
        {
            $PRI = $this->getPrimary();
            $obj = new $class();
            if(is_array($this->has_one[$name]))
                $ON = (isset($this->has_one[$name]['on']) ? $this->has_one[$name]['on'] : $this->singularize($this->table_name).'_id');
            else
                $ON = $this->singularize($this->table_name).'_id';
            $r = $obj->where('`'.$ON.'` = ?', $this->$PRI)->run();
            return $r->delete();
        } else {
            $this->_error->Toss(__CLASS__."::".__FUNCTION__." No Model by the name [".$name."]", E_USER_NOTICE);
            return null;
        }
    }
    
    private function _deleteHasMany($name)
    {
        $class = $this->FindModel($name, false);
        if($class != null)
        {
            $PRI = $this->getPrimary();
            $obj = new $class();
            if(is_array($this->has_many[$name]))
                $ON = (isset($this->has_many[$name]['on']) ? $this->has_many[$name]['on'] : $this->singularize($this->table_name).'_id');
            else
                $ON = $this->singularize($this->table_name).'_id';
            $r = $obj->where('`'.$ON.'` = ?', $this->$PRI)->run();
            if(isset($r->$ON))
                $r->delete_set();
        } else {
            $this->_error->Toss(__CLASS__."::".__FUNCTION__." No Model by the name [".$name."]", E_USER_NOTICE);
            return null;
        }
    }

    /**
     * Saves current model in database
     * @access public
     * @return bool
     */
    public function save()
    {
        Log::corewrite('Saving record', 3, __CLASS__, __FUNCTION__);
        $pri = $this->getPrimary();
        $data = $this->_data;
        if(!empty($this->_pre_data))
        {
                $tmp = array();
                foreach($data as $field => $value)
                {
                        if($field != 'updated_at' && $field != 'created_at' && $field != $pri && $value != $this->_pre_data[$field])
                        {
                                $tmp[$field] = $value;
                        }
                }
                $data = $tmp;
        }
        
        $ret = $this->db->save($data);
        $this->_pre_data = $this->_data;
        Log::corewrite('At end of method', 2, __CLASS__, __FUNCTION__);
        
        if(is_numeric($ret))
        {
            foreach($this->has_one as $table => $options)
            {
                if(is_array($options) && isset($options['create']))
                {
                    $this->_createHasOne($table, $ret);
                }
            }
            
        }
        return $ret;
    }
    
    private function _createHasOne($name, $id)
    {
        $class = $this->FindModel($name);
        if($class != null)
        {
            $obj = new $class();
            $FK = strtolower($this->singularize(get_class($this)).'_id');
            $obj->$FK = $id;
            return $obj->save();
        } else {
            $this->_error->Toss(__CLASS__."::".__FUNCTION__." No Model by the name [".$name."]", E_USER_NOTICE);
            return false;
        }
    }

    /**
     * Resets all [Query Builder] properties and runs query
     * @access public
     * @return object
     */
    public function all()
    {
        $this->select = array();
        $this->where = array();
        $this->groupby = array();
        $this->orderby = array();
        $this->limit = array();
        return $this;
    }

    /**
     * Adds to {@link $select}
     * @access public
     * @return $this
     */
    public function select()
    {
        $this->select = array();
        for($i=0;$i<func_num_args();$i++)
        {
            $this->select[] = func_get_arg($i);
        }
        return $this;
    }
    
    /**
     * Sets {@link $from}
     * @return object $this
     * @todo Decide whether to keep this or not
     */
    public function from($from)
    {
        $this->from = $from;
        return $this;
    }

    /**
     * Adds a where to {@link $where}
     * @example http://www.deeplogik.com/sky/docs/examples/model
     * @access public
     * @return $this
     */
    public function where()
    {
        Log::corewrite('Adding where clause to query', 2, __CLASS__, __FUNCTION__);
        if(func_num_args() == 1 && is_string(func_get_arg(0)))
        {
            $this->where[] = func_get_arg(0);
        }
        elseif(func_num_args() == 1 && is_array(func_get_arg(0)))
        {
            foreach(func_get_arg(0) as $key => $value)
            {
                $operator = '=';
                if(is_array($value))
                    $operator = 'IN';
                $this->where[] = array(
                    'field' => $this->db->escape($key),
                    'operator' => $operator,
                    'value' => $value
                );
            }
        }
        elseif(func_num_args() > 1 && is_string(func_get_arg(0)) && is_array(func_get_arg(1)) && strpos(func_get_arg(0), ":") > -1)
        {
            $tmp = func_get_arg(0);
            $data = func_get_arg(1);
            preg_match_all('/\:([a-zA-Z0-9]+)/', $tmp, $matches);
            foreach($matches[1] as $field)
            {
                $tmp = preg_replace('/(\:'.$field.')/', "'".$this->db->escape($data[$field])."'", $tmp);
            }
            $this->where[] = $tmp;
        }
        elseif(func_num_args() > 1 && is_string(func_get_arg(0)) && strpos(func_get_arg(0), "?") > -1)
        {
            $tmp = func_get_arg(0);
            Log::corewrite('Passed ? where clause [%s]', 1, __CLASS__, __FUNCTION__, array($tmp));
            $count = substr_count($tmp, "?");
            $broken = explode("?", $tmp);
            $where = "";
            for($i=0;$i<$count;$i++)
            {
                if($broken[$i] != "")
                {
                    $where .= $broken[$i]."'".$this->db->escape(func_get_arg($i+1))."' ";
                }
            }
            $this->where[] = $where;
        }
        elseif(func_num_args() > 0 && is_array(func_get_arg(0)))
        {
            for($i=0;$i<func_num_args();$i++)
            {
                $arg = func_get_arg($i);
                if(!is_array($arg))
                {
                    $this->_error->Toss(__CLASS__."::".__FUNCTION__." Must be an array");
                }
                foreach($arg as $key => $value)
                {
                    $operator = '=';
                    if(is_array($value))
                        $operator = 'IN';
                    $this->where[] = array(
                        'field' => $this->db->escape($key),
                        'operator' => $operator,
                        'value' => $value
                    );
                }
            }
        }
        return $this;
    }

    /**
     * Adds a join to {@link $join}
     * @param string $join
     * @access public
     * @return $this
     * @todo Need to figure out primary key thing...
     */
    public function joins($join)
    {
        if(is_string($join))
        {
            $this->joins[] = $join;
        }
        return $this;
    }

    /**
     * Sets up {@link $limit}
     * @example http://www.deeplogik.com/sky/docs/examples/model
     * @access public
     * @return $this
     */
    public function limit()
    {
        if(func_num_args() == 0)
        {
            $this->limit = 1;
        }
        elseif(func_num_args() == 1)
        {
            $this->limit = func_get_arg(0);
        }
        elseif(func_num_args() == 2)
        {
            $this->limit = array(
                "offset" => func_get_arg(0),
                "limit" => func_get_arg(1)
            );
        }
        return $this;
    }

    /**
     * Adds an order by to {@link $orderby}
     * @param string $by
     * @access public
     * @return $this
     */
    public function orderby($by)
    {
        $this->orderby[] = $by;
        return $this;
    }

    public function groupby($by)
    {
        $this->groupby[] = $by;
        return $this;
    }

    /**
     * Runs query built by driver and executes it
     * @access public
     * @return $thi
     */
    public function run()
    {
        Log::corewrite('Running query...', 3, __CLASS__, __FUNCTION__);
        $query = $this->db->buildQuery(array(
            'select' => $this->select,
            'from' => $this->from,
            'where' => $this->where,
            'joins' => $this->joins,
            'limit' => $this->limit,
            'orderby' => $this->orderby,
            'groupby' => $this->groupby
        ));
        $this->last_query = $query;
        if(ENV == 'DEV')
        {
            $f = fopen(LOG_DIR."/development.log", 'a');
            fwrite($f, "START: ".date('H:i:s')."\t".trim($query)."\n");
            fclose($f);
        }
        $results = $this->db->runQuery($query);
        if(count($results) == 0)
            return $this;
        Log::corewrite('Results were found [%s]', 1, __CLASS__, __FUNCTION__, array(count($results)));
        for($i=0;$i<count($results);$i++)
        {
            foreach($results[$i] as $field => $value)
            {
                $this->_data[$field] = $value;
            }
            $this->array[] = clone $this;
        }
        foreach($results[0] as $field => $value)
        {
            $this->_data[$field] = $value;
        }
        Log::corewrite('At the end of method...', 2, __CLASS__, __FUNCTION__);
        return $this;
    }

    /**
     * Sets the query to only return the first result
     * @return object $this
     */
    public function first()
    {
        $this->limit(1);
        return $this;
    }

    /**
     * Sets the query to only return the last result
     * @return object $this
     */
    public function last()
    {
        $pri = $this->getPrimary();
        $this->limit(1)->orderby($pri.' DESC');
        return $this;
    }

    /**
     * Gets query from driver and prints it to screen
     */
    public function printQuery()
    {
        echo $this->db->buildQuery(array(
            'select' => $this->select,
            'from' => $this->from,
            'where' => $this->where,
            'joins' => $this->joins,
            'limit' => $this->limit,
            'orderby' => $this->orderby,
            'groupby' => $this->groupby
        ));
		return $this;
    }
	
	public function delete_set()
	{
		if(count($this->array) > 0)
		{
			$pri = $this->getPrimary();
			$ids = array();
			foreach($this->array as $obj)
			{
				$ids[] = $obj->$pri;
			}
			$this->db->delete($pri, $ids);
		}
	}

    /**
     * Figures out what the primary key of the table is and returns it
     * @return mixed $field
     */
    protected function getPrimary()
    {
        Log::corewrite('Getting table primary key', 3, __CLASS__, __FUNCTION__);
        if(isset($this->table_schema['id']) && $this->table_schema['id']['Key'] == 'PRI')
        {
            Log::corewrite('Found fast primary key [%s]', 1, __CLASS__, __FUNCTION__, array('id'));
            return 'id';
        }
        foreach($this->table_schema as $field => $detail)
        {
            Log::corewrite('Field [%s]', 1, __CLASS__, __FUNCTION__, array($field));
            if($detail['Key'] == 'PRI')
            {
                Log::corewrite('Found primary key [%s]', 1, __CLASS__, __FUNCTION__, array($field));
                return $field;
            }
        }
        Log::corewrite('No primary key found', 2, __CLASS__, __FUNCTION__);
        return NULL;
    }
}
?>
