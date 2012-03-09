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
     * Flag to see if query should be ran at Model::__get()
     * @access private
     * @var bool
     */
    private $run_at_get_flag = false;
    /**
     * Query to be ran at Model::__get()
     * @access private
     * @var array
     */
    private $run_this = array();
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
	protected $db_array = null;
	protected $_pre_data = array();

    /**
     * Constructor sets up {@link $driver}, {@link $error}, and {@link $db}
     * @param array $hash Will set up model object with hash values
     * @param mixed $runthis Will set {@link $run_at_get_flag} to true and {@link $run_this}
     */
    public function __construct($hash = array(), $runthis = false)
    {
        $this->driver = MODEL_DRIVER."Driver";
        $this->_error = ErrorHandler::Singleton(true);
        if(is_file(CORE_DIR."/drivers/".MODEL_DRIVER.".driver.php"))
        {
            import(CORE_DIR."/drivers/".MODEL_DRIVER.".driver.php");
			if(is_null($this->db_array))
				$this->db = new $this->driver();
			else
				$this->db = new $this->driver($this->db_array);
            if(!$this->db instanceof iDriver)
                $this->_error->Toss('Driver loaded is not an instance of iDriver interface!', E_USER_ERROR);
            if(isset($this->table_name))
            {
                $this->db->setTableName($this->table_name);
                $this->db->setSchema();
            } else {
                if(!$this->db->doesTableExist(get_class($this)))
                    $this->_error->Toss('No table name specified. Please add property $table_name to model.', E_USER_ERROR);
                else
                {
                    preg_match_all('/[A-Z][^A-Z]*/', get_class($this), $strings);
                    if(isset($strings[0]))
                        $table_name = strtolower(implode('_', $strings[0]));
                    else
                        $this->_error->Toss('Error handling class name as table name.', E_USER_ERROR);
                    $this->table_name = $table_name;
                    $this->db->setTableName($this->table_name);
                    $this->db->setSchema();
                }
            }
            $this->table_schema = $this->db->getSchema();
        } else {
            $this->_error->Toss('No driver found for model! Model: '.get_class($this).' | Driver: '.MODEL_DRIVER, E_USER_ERROR);
        }

        // Setting empty object
        if(empty($hash))
        {
            foreach($this->table_schema as $field => $i)
            {
                if(isset($i['Default']))
                    $this->$field = $i['Default'];
                else
                    $this->$field = NULL;
            }
        } else {
            foreach($this->table_schema as $field => $i)
            {
                if(isset($hash[$field]))
                    $this->$field = $hash[$field];
                else
                {
                    if(isset($i['Default']))
                        $this->$field = $i['Default'];
                    else
                        $this->$field = NULL;
                }
            }
        }

        if($runthis !== false)
        {
            $this->run_at_get_flag = true;
            $this->run_this = $runthis;
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
            $fields = array_keys(self::$table_schema[$this->table_name]);
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
        if(isset($this->input_format[$name]))
        {
            if(is_array($this->input_format[$name]))
            {
                $this->_data[$name] = call_user_func(array($this, $this->input_format[$name]['custom']), $value);
            } else {
                $this->_data[$name] = sprintf($this->input_format[$name], $value);
            }
        } else {
            $this->_data[$name] = $value;
        }
    }
	
	public function __isset( $name )
	{
		return (isset($this->_data[$name]) && $this->_data[$name] !== NULL);
	}
	
	public function __unset( $name )
	{
		$this->_data[$name] = NULL;
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
        if($this->run_at_get_flag === true)
        {
            $r = $this;
            foreach($this->run_this as $method => $args)
            {
                $r = $r->$method($args);
            }
            $n = $r->run();
            $array = $n->to_array();
            foreach($array as $key => $value)
            {
                $this->$key = $value;
            }
            $this->run_at_get_flag = false;
        }
        if(!isset($this->_data[$name]))
        {
            $this->_error->Toss(__CLASS__."::".__FUNCTION__." No field by the name [".$name."]", E_USER_NOTICE);
            return null;
        }
        if(isset($this->output_format[$name]))
        {
            if(is_array($this->output_format[$name]))
            {
                return call_user_func(array($this, $this->output_format[$name]['custom']), $this->_data[$name]);
            } else {
                return sprintf($this->output_format[$name], $this->_data[$name]);
            }
        }
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
    
    /**
     * Dumps current {@link $data} values as an array
     * @return array $data
     */
    public function to_array()
    {
		$ret = array();
		foreach($this->_data as $key => $v)
        {
			$ret[$key] = $this->$key;
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
        $pri = $this->getPrimary();
        return $this->db->delete($pri, $this->_data[$pri]);
    }

    /**
     * Saves current model in database
     * @access public
     * @return bool
     */
    public function save()
    {
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
		return $ret;
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
        //if(is_array($join))
        //{
        //    foreach($join as $value)
        //    {
        //        if(in_array($value, $this->has_one))
        //        {
        //            $obj = new $value();
        //            $this->joins[] = "INNER JOIN ".$obj->table_name." ON (".$obj->table_name.".".$obj->primary_key." = ".$this->table_name.".".$obj->primaryKey.")";
        //        }
        //        if(in_array($value, $this->has_many))
        //        {
        //            $obj = new $value();
        //            $this->joins[] = "INNER JOIN ".$obj->table_name." ON (".$obj->table_name.".".$this->primary_key." = ".$this->table_name.".".$this->primaryKey.")";
        //        }
        //    }
        //}
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

        $results = $this->db->runQuery($query);
        if(count($results) == 0)
            return $this;
        for($i=0;$i<count($results);$i++)
        {
            foreach($results[$i] as $field => $value)
            {
                if(strpos($field, '_id'))
                {
                    $name = substr($field, 0, -3);
                    if(isset($this->has_many[$name]))
                    {
                        $obj = new $name(array(), array(
                            'where' => array(
                                $this->has_many[$name] => $value
                            )
                        ));
                        $this->$name = $obj;
                    }
                    elseif(isset($this->has_one[$name]))
                    {
                        $obj = new $name(array(), array(
                            'where' => array(
                                $this->has_one[$name] => $value
                            )
                        ));
                        $this->$name = $obj;
                    }
                    elseif(in_array($name, $this->has_many) || in_array($name, $this->has_one))
                    {
                        $obj = new $name(array(), array(
                            'where' => array(
                                'id' => $value
                            )
                        ));
                        $this->$name = $obj;
                    }
                }
                $this->$field = $value;
            }
            $this->array[] = clone $this;
        }
        foreach($results[0] as $field => $value)
        {
            $this->$field = $value;
        }
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
        foreach($this->table_schema as $field => $detail)
        {
            if($detail['Key'] == 'PRI')
            {
                return $field;
            }
        }
        return NULL;
    }
}
?>
