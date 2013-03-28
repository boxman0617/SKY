<?php
/**
 * Model Core Class
 *
 * This class is the data abstraction of SKY. It allows 
 * data in a database to be abstracted in a Model type
 * object. The user can associate relations between 
 * Models with ease.
 *
 * LICENSE:
 *
 * This file may not be redistributed in whole or significant part, or
 * used on a web site without licensing of the enclosed code, and
 * software features.
 *
 * @author      Alan Tirado <root@deeplogik.com>
 * @copyright   2013 DeepLogik, All Rights Reserved
 * @license     http://www.codethesky.com/license
 * @link        http://www.codethesky.com/docs/modelclass
 * @package     Sky.Core
 */

/**
 * Model class
 * This class handles the data layer of your application
 * @package Sky.Core.Model
 */
abstract class Model implements Iterator
{
    /** PUBLIC PROPERTIES **/
    public $output_format = array();
    public $input_format = array();
    public $belongs_to = array();
    public $has_one = array();
    public $has_many = array();
    public $validate = array();
    public $table_name;
    public $encrypt_field = array();
    public $db_array = NULL;
    public $_skip_format_input = false;

    /** PUBLIC STATIC PROPERTIES **/
    public static $_static_info = array();

    /** PROTECTED PROPERTIES **/
    protected static $_table_schema = array();
    protected $_last_query;
    protected $_data = array();
    protected $_query_material = array(
        'select' => array(),
        'from' => array(),
        'joins' => array(),
        'where' => array(),
        'limit' => null,
        'orderby' => array(),
        'groupby' => array()
    );
    protected $_pre_data = array();
    protected $_child;
    protected $_result_count = 0;

    /** PROTECTED STATIC PROPERTIES **/
    protected static $_position = array();
    protected static $_array = array();

    /** PRIVATE PROPERTIES **/
    private $_object_id;

    //============================================================================//
    // Magic methods                                                              //
    //============================================================================//

    /**
     * Constructor sets up {@link $driver} and {@link $db}
     * @param array $hash Will set up model object with hash values
     */
    public function __construct($hash = array())
    {
        Log::corewrite('Starting Model [%s]', 3, __CLASS__, __FUNCTION__, array(get_class($this)));
        $this->_child = get_called_class();
        if(!isset(self::$_static_info[$this->_child])) self::$_static_info[$this->_child] = array();
        if(!isset(self::$_static_info[$this->_child]['db']))
        {
            self::$_static_info[$this->_child]['driver'] = MODEL_DRIVER.'Driver';
            if(is_file(SKYCORE_CORE_MODEL."/drivers/".MODEL_DRIVER.".driver.php"))
            {
                Log::corewrite('Found driver [%s]', 1, __CLASS__, __FUNCTION__, array(MODEL_DRIVER));
                import(SKYCORE_CORE_MODEL."/drivers/".MODEL_DRIVER.".driver.php");
                self::$_static_info[$this->_child]['db'] = new self::$_static_info[$this->_child]['driver']($this->db_array);
                if(!self::$_static_info[$this->_child]['db'] instanceof iDriver)
                    trigger_error('Driver loaded is not an instance of iDriver interface!', E_USER_ERROR);
                if(!isset($this->table_name))
                {
                    Log::corewrite('::$table_name is NOT set. Attempting to create name out of class', 1, __CLASS__, __FUNCTION__);
                    if(!self::$_static_info[$this->_child]['db']->doesTableExist(get_class($this)))
                        trigger_error('No table name specified. Please add property $table_name to model.', E_USER_ERROR);
                    else
                        $this->table_name = strtolower(get_class($this));
                }
                self::$_static_info[$this->_child]['db']->setTableName($this->table_name);
                self::$_static_info[$this->_child]['db']->setSchema();
                self::$_static_info[$this->_child]['table_name'] = $this->table_name;
                Log::corewrite('Model was set properly [%s]', 2, __CLASS__, __FUNCTION__, array(get_class($this)));
            } else {
                trigger_error('No driver found for model! Model: '.get_class($this).' | Driver: '.MODEL_DRIVER, E_USER_ERROR);
            }
        }
        
        self::$_table_schema[$this->_child] = self::$_static_info[$this->_child]['db']->getSchema();
        // Setting empty object
        foreach(self::$_table_schema[$this->_child] as $field => $i)
        {
            if(!empty($hash) && isset($hash[$field]))
                $this->_data[$field] = $hash[$field];
            else
            {
                $this->_data[$field] = NULL;
                if(isset($i['Default'])) $this->_data[$field] = $i['Default'];
            }
        }
        $this->_object_id = md5($this->_child.rand(0, 9999));
        Log::corewrite('At end of method...', 2, __CLASS__, __FUNCTION__);
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
        if(method_exists(self::$_static_info[$this->_child]['db'], $method))
        {
            call_user_func_array(array(self::$_static_info[$this->_child]['db'], $method), $args);
            return $this;
        }
        elseif(substr($method, 0, 7) == 'find_by')
        {
            $options = substr($method, 8);
            $conditions = $this->create_conditions_from_underscored_string($options, $args);
            $obj = call_user_func_array(array($this, 'where'), $conditions);
            return $obj->run();
        } else {
            trigger_error('No method name ['.$method.']', E_USER_ERROR);
        }
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
                    $bind = is_array($values[$j]) ? ' IN(?)' : ' = ?';
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
        if(!array_key_exists($name, $this->_data))
        {
            Log::corewrite('No data found. Checking associations...', 1, __CLASS__, __FUNCTION__);
            if(isset($this->belongs_to[$name])) return $this->_getBelongsTo($name);
            elseif(isset($this->has_one[$name])) return $this->_getHasOne($name);
            elseif(isset($this->has_many[$name])) return $this->_getHasMany($name);
            else
            {
                trigger_error(__CLASS__."::".__FUNCTION__." No field by the name [".$name."] in Model [".get_class($this)."]", E_USER_WARNING);
                return null;
            }
        }
        Log::corewrite('Found data. Checking format output...', 2, __CLASS__, __FUNCTION__);
        if(isset($this->output_format[$name]))
        {
            Log::corewrite('Calling formating', 1, __CLASS__, __FUNCTION__);
            if(is_array($this->output_format[$name]))
                return call_user_func(array($this, $this->output_format[$name]['custom']), $this->_data[$name]);
            else
                return sprintf($this->output_format[$name], $this->_data[$name]);
        }
        Log::corewrite('At the end of method', 2, __CLASS__, __FUNCTION__);
        return $this->_data[$name];
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
                $this->_data[$name] = call_user_func(array($this, $this->input_format[$name]['custom']), $value);
            else
                $this->_data[$name] = sprintf($this->input_format[$name], $value);
            }
        elseif(in_array($name, $this->encrypt_field))
        {
            Log::corewrite('Need to encrypt field. Executing...', 1, __CLASS__, __FUNCTION__);
            $this->_data[$name] = md5(AUTH_SALT.$value);
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
        return array_key_exists($name, $this->_data);
    }
    
    public function __unset( $name )
    {
            $this->_data[$name] = NULL;
    }

    public function get_raw($name)
    {
        if(!isset($this->_data[$name]))
        {
            trigger_error(__CLASS__."::".__FUNCTION__." No field by the name [".$name."]", E_USER_NOTICE);
            return null;
        }
        return $this->_data[$name];
    }



    //============================================================================//
    // Word plural/singular Methods                                               //
    //============================================================================//

    public function singularize($word)
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

    public function pluralize($word)
    {
        Log::corewrite('Pluralizing word [%s]', 1, __CLASS__, __FUNCTION__, array($word));
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
                Log::corewrite('Pluralized word [%s] 1', 1, __CLASS__, __FUNCTION__, array($word));
                return $word;
            }
        }

        foreach ($irregular as $_plural=> $_singular){
            if (preg_match('/('.$_plural.')$/i', $word, $arr)) {
                $word = preg_replace('/('.$_plural.')$/i', substr($arr[0],0,1).substr($_singular,1), $word);
                Log::corewrite('Pluralized word [%s] 2', 1, __CLASS__, __FUNCTION__, array($word));
                return $word;
            }
        }

        foreach ($plural as $rule => $replacement) {
            if (preg_match($rule, $word)) {
                $word = preg_replace($rule, $replacement, $word);
                Log::corewrite('Pluralized word [%s] Rule: [%s] Replace: [%s]', 1, __CLASS__, __FUNCTION__, array($word, $rule, $replacement));
                return $word;
            }
        }
        return false;
    }



    //============================================================================//
    // Association Methods                                                        //
    //============================================================================//
    
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
        if(isset($this->belongs_to[$name]['table']))
            $class = $this->FindModel($name, false, $this->belongs_to[$name]['table']);
        else
            $class = $this->FindModel($name);
        if($class !== null)
        {
            $this->table_name = self::$_static_info[$this->_child]['table_name'];
            $other = new $class();
                $ON = $name.'_id';
            if(isset($this->belongs_to[$name]['on']))
                $ON = $this->belongs_to[$name]['on'];
            $nameS = $this->pluralize($name);
            $thisPRI = $this->getPrimary();
            $this->_data[$name] = $other->from($this->table_name)
                ->joins('INNER JOIN `'.$nameS.'` ON 
                    (`'.$this->table_name.'`.`'.$ON.'` = `'.$nameS.'`.`'.$other->getPrimary().'`)')
                ->where('`'.$this->table_name.'`.`'.$thisPRI.'` = ?', $this->_data[$thisPRI])
                ->limit(1)
                ->run();
            return $this->_data[$name];
        } else {
            trigger_error(__CLASS__."::".__FUNCTION__." No Model by the name [".$name."]", E_USER_NOTICE);
            return null;
        }
    }
    
    private function _getHasOne($name)
    {
        Log::corewrite('Getting HasOne data [%s]', 3, __CLASS__, __FUNCTION__, array($name));
        return $this->__HasSomething($name, 'one');
    }
    
    private function _getHasMany($name)
    {
        Log::corewrite('Getting HasMany data [%s]', 3, __CLASS__, __FUNCTION__, array($name));
        return $this->__HasSomething($name, 'many');
    }

    private function __HasSomething($name, $size)
    {
        if(isset($this->has_many[$name]['table']))
            $class = $this->FindModel($name, false, $this->has_many[$name]['table']);
        else
            $class = $this->FindModel($name, false);
        if($class !== null)
        {
            $this->table_name = self::$_static_info[$this->_child]['table_name'];
            $other = new $class();
            $ON = $this->singularize($this->table_name).'_id';
            if(isset($this->has_many[$name]['on']))
                $ON = $this->has_many[$name]['on'];
            $thisPRI = $this->getPrimary();
            $r = $other->from($this->table_name);
            if(isset($this->has_many[$name]['through']))
            {
                $r = $r->joins('INNER JOIN `'.$this->has_many[$name]['through'].'` ON 
                    (`'.$this->table_name.'`.`'.$thisPRI.'` = `'.$this->has_many[$name]['through'].'`.`'.$ON.'`)');
                $r = $r->joins('INNER JOIN `'.$name.'` ON 
                    (`'.$this->has_many[$name]['through'].'`.`'.$this->singularize($name).'_id` = `'.$name.'`.`id`)');
            } else {
                $r = $r->joins('INNER JOIN `'.$name.'` ON
                    (`'.$this->table_name.'`.`'.$thisPRI.'` = `'.$name.'`.`'.$ON.'`)');
            }
            $q = $r->where('`'.$this->table_name.'`.`'.$thisPRI.'` = ?', $this->_data[$thisPRI]);
            if($size == 'one')
                $this->_data[$name] = $q->limit()->run();
            else
                $this->_data[$name] = $q->run();
            return $this->_data[$name];
        } else {
            trigger_error(__CLASS__."::".__FUNCTION__." No Model by the name [".$name."]", E_USER_NOTICE);
            return null;
        }
    }

    private function _deleteHasOne($name)
    {
        $r = $this->_getHasOne($name);
        if(!is_null($r))
        {
            foreach($r as $obj)
                $obj->delete();
        }
    }
    
    private function _deleteHasMany($name)
    {
        $r = $this->_getHasMany($name);
        if(!is_null($r))
        {
            foreach($r as $obj)
                $obj->delete();
        }
    }

    public function delete_set()
    {
        if(count(self::$_array[$this->_object_id]) > 0)
            foreach(self::$_array[$this->_object_id] as $obj) $obj->delete();
    }

    /**
     * Deletes current model from database
     * @access public
     * @return bool
     */
    public function delete()
    {
        Event::PublishActionHook('/Model/delete/start', array(&$this->_data));
        Log::corewrite('Deleting record', 3, __CLASS__, __FUNCTION__);
        $pri = $this->getPrimary();
        Event::PublishActionHook('/Model/delete/primarykey', array(&$pri));
        foreach($this->has_one as $model => $options)
            if(is_array($options) && isset($options['dependent'])) $this->_deleteHasOne($model);
        
        foreach($this->has_many as $model => $options)
            if(is_array($options) && isset($options['dependent'])) $this->_deleteHasMany($model);

        Log::corewrite('At the end of method', 2, __CLASS__, __FUNCTION__);
        Event::PublishActionHook('/Model/delete/before', array(&$this, $pri, $this->_data, $this->_child));
        $ret = self::$_static_info[$this->_child]['db']->delete($pri, $this->_data[$pri]);
        Event::PublishActionHook('/Model/delete/after', array(&$this, $ret));
        return $ret;
    }



    //============================================================================//
    // Query Macro Methods                                                        //
    //============================================================================//
    
    /**
     * Resets all [Query Builder] properties
     * @access public
     * @return $this
     */
    public function all()
    {
        $this->_query_material = array(
            'select' => array(),
            'from' => array(),
            'joins' => array(),
            'where' => array(),
            'limit' => null,
            'orderby' => array(),
            'groupby' => array()
        );
        return $this;
    }

    /**
     * Allows associative array to be passed to create query
     * @access public
     * @return $this
     */
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
            $args = func_get_arg(0);
            $obj = $this;
            foreach($args as $method => $params)
            {
                if(!is_array($params)) $params = array($params);
                $obj = call_user_func_array(array($this, $method), $params);
            }
            return $obj;
            }
        }

    public static function search()
    {
        $class = get_called_class();
        $n = new $class();
        $r = call_user_func_array(array($n, 'find'), func_get_args());
        return $r->run();
    }
	
    /**
     * Sets the query to only return the first result
     * @return object $this
     */
    public function first()
    {
        return $this->limit();
    }

    /**
     * Sets the query to only return the last result
     * @return object $this
     */
    public function last()
    {
        $pri = $this->getPrimary();
        $this->limit()->orderby($pri.' DESC');
        return $this;
    }



    //============================================================================//
    // Iterator Methods                                                           //
    //============================================================================//
    
        /**
     * Magic iterator method
     * Rewinds {@link $_position} to 0
     * @access public
     */
    public function rewind()
    {
        self::$_position[$this->_object_id] = 0;
    }
    
    /**
     * Magic iterator method
     * Returns currect {@link $_position} value
     * @access public
     * @return mixed
     */
    public function current()
    {
        return self::$_array[$this->_object_id][self::$_position[$this->_object_id]];
    }

    /**
     * Magic iterator method
     * Returns {@link $_position}
     * @access public
     * @return integer
     */
    public function key()
    {
        return self::$_position[$this->_object_id];
    }
    
    /**
     * Magic iterator method
     * Increases {@link $_position} by 1
     * @access public
     */
    public function next()
    {
        ++self::$_position[$this->_object_id];
    }
    
    /**
     * Magic iterator method
     * Checks if array[_position] is set
     * @access public
     * @return bool
     */
    public function valid()
    {
        return isset(self::$_array[$this->_object_id][self::$_position[$this->_object_id]]);
    }



    //============================================================================//
    // Conversion Methods                                                         //
    //============================================================================//
    
    /**
     * Dumps current {@link $data} values as an array
     * @return array $data
     */
    public function to_array($format = array())
    {
        Log::corewrite('Turning data into an array', 3, __CLASS__, __FUNCTION__);
        if(empty($this->output_format) && empty($format))
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
        foreach($format as $field => $function)
        {
            Log::corewrite('User formatted output [%s]', 1, __CLASS__, __FUNCTION__, array($field));
            $ret[$field] = $function($ret[$field]);
        }
        return $ret;
    }

    public function to_set($format = array())
    {
        Log::corewrite('Turning data into a set', 3, __CLASS__, __FUNCTION__);
        $ret = array();
        if(isset(self::$_array[$this->_object_id]))
        {
            foreach(self::$_array[$this->_object_id] as $i)
                $ret[] = $i->to_array($format);
        }
        return $ret;
    }



    //============================================================================//
    // Save Methods                                                               //
    //============================================================================//

    public function fill($data)
    {
        foreach($data as $field => $value)
            $this->$field = $value;
        return $this;
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
                    $tmp[$field] = $value;
            }
            $data = $tmp;
        }
        
        $ret = self::$_static_info[$this->_child]['db']->save($data);
        $this->_pre_data = $this->_data;
        Log::corewrite('At end of method', 2, __CLASS__, __FUNCTION__);
        
        if(is_numeric($ret))
        {
            foreach($this->has_one as $table => $options)
            {
                if(is_array($options) && isset($options['create']))
                    $this->_createHasOne($table, $ret);
            }
        }
        return $ret;
    }
    

    //============================================================================//
    // Query Methods                                                              //
    //============================================================================//

    /**
     * Runs query built by driver and executes it
     * @access public
     * @return $thi
     */
    public function run()
    {
        Log::corewrite('Running query...', 3, __CLASS__, __FUNCTION__);
        $query = self::$_static_info[$this->_child]['db']->buildQuery();
        $this->_last_query = $query;
        if($GLOBALS['ENV'] != 'PRO')
        {
            $f = fopen(DIR_LOG."/development.log", 'a');
            fwrite($f, "\033[36mSTART\033[0m: ".date('H:i:s')."\t".trim($query)."\n");
            fclose($f);
            $_start = microtime(true);
        }
        $results = self::$_static_info[$this->_child]['db']->runQuery($query);
        if($GLOBALS['ENV'] != 'PRO')
        {
            $_end = microtime(true);
            $f = fopen(DIR_LOG."/development.log", 'a');
            fwrite($f, "\033[35mEND\033[0m: ".date('H:i:s')."\t\033[1;36mResults\033[0m [".count($results)."] \033[1;32mTime\033[0m [".round($_end - $_start, 5)."]\n");
            fclose($f);
        }
        if(count($results) == 0)
            return $this;
        $this->_result_count = count($results);
        Log::corewrite('Results were found [%s]', 1, __CLASS__, __FUNCTION__, array(count($results)));
        for($i=0;$i<count($results);$i++)
        {
            foreach($results[$i] as $field => $value)
                $this->_data[$field] = $value;
            self::$_array[$this->_object_id][] = clone $this;
        }
        foreach($results[0] as $field => $value)
            $this->_data[$field] = $value;
        Log::corewrite('At the end of method...', 2, __CLASS__, __FUNCTION__);
        return $this;
    }

    public function getQueryMaterial()
    {
        return $this->_query_material;
    }

    /**
     * Gets query from driver and prints it to screen
     */
    public function printQuery()
    {
        Log::predebug(self::$_static_info[$this->_child]['db']->buildQuery());
		return $this;
    }
	
    /**
     * Figures out what the primary key of the table is and returns it
     * @return mixed $field
     */
    public function getPrimary()
    {
        Log::corewrite('Getting table primary key', 3, __CLASS__, __FUNCTION__);
        if(empty(self::$_table_schema[$this->_child]))
            self::$_table_schema[$this->_child] = self::$_static_info[$this->_child]['db']->getSchema();
        if(isset(self::$_table_schema[$this->_child]['id']) && self::$_table_schema[$this->_child]['id']['Key'] == 'PRI')
        {
            Log::corewrite('Found fast primary key [%s]', 1, __CLASS__, __FUNCTION__, array('id'));
            return 'id';
        }
        foreach(self::$_table_schema[$this->_child] as $field => $detail)
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

    public function getStaticSet()
    {
        return self::$_array[$this->_object_id];
    }

    public function getObjectId()
    {
        return $this->_object_id;
    }

    public function getCurrentPosition()
    {
        return self::$_position[$this->_object_id];
    }

    public static function getModelScope()
    {
        foreach(debug_backtrace(true) as $stack){
            if(isset($stack['class']) && $stack['class'] == 'Model' && isset($stack['object'])){
                return $stack['object'];
            }
        }
    }

    public function isEmpty()
    {
        if($this->_result_count == 0) return true;
        return false;
    }
}
?>
