<?php
/**
 * Model Core Class
 *
 * This class handles Database and Data models
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
 * @version 1.0 Initial Build
 * @version 1.1 Adding ability to be driven by Data and File aside from Database
 * @package Sky.Core
 */

import(dirname(__FILE__)."/../configs/configure.php");
import(ERROR_CLASS);
import(PRELOADER);

/**
 * Constant M_TYPE_DB, tells Model class to behave as a Database model
 */
define('M_TYPE_DB', 0);
/**
 * Constant M_TYPE_DATA, tells Model class to behave as a Data model
 */
define('M_TYPE_DATA', 1);
/**
 * Constant M_TYPE_DATA, tells Model class to behave as a Data model
 */
define('M_TYPE_FILE', 2);

/**
 * DatabaseSingleton class
 * Creates a singleton object of the PHP mysqli class
 * @package Sky.Core.DatabaseSingleton
 */
class DatabaseSingleton
{
    private static $m_pInstance;
    
    public static function getInstance()
    {
        if(!self::$m_pInstance)
        {
            self::$m_pInstance = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
        }
        return self::$m_pInstance;
    }
}

/**
 * Model class implements Iterator
 * This class handles Database data models
 * @package Sky.Core.Model
 *
 * @method object find_by...($arg[, ...]); Creates query from underscored method name
 */
abstract class Model implements Iterator
{
    /**
     * SQLite3 tmp dir
     * @access protected
     * @var string
     */
    protected $tmp_dir;
    /**
     * SQLite3 Database instance
     * @access protected
     * @var object
     */
    protected $sqlite;
    /**
     * Table Name
     * @access protected
     * @var string
     */
    protected $table_name;
    /**
     * Database instance
     * @access protected
     * @var object
     */
    protected $db;
    /**
     * Magic setter/getter property
     * @access protected
     * @var array
     */
    protected $data;
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
     * Static property to hold table schemas
     * @access protected
     * @var array
     */
    protected static $table_schema = array();
    /**
     * Error Class Object
     * @access private
     * @var object
     */
    private $error;
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
     * Holds current model id
     * @access private
     * @var integer
     */
    private $id;
    /**
     * Holds primary key field of model
     * @access private
     * @var string
     */
    private $primary_key;
    /**
     * Sets type of model
     * @access protected
     * @var integer
     */
    protected $model_type = M_TYPE_DB;
    
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
    /**
     * Data driven array
     * @access protected
     * @var array
     */
    protected $data_info = array();
    
    /**
     * Constructor sets up {@link $error} and {@link $db}
     * Then it gets the schema of the table
     * @access public
     * @param integer $id default false
     * @return object If $id is passed
     */
    public function __construct($id = false)
    {
        $this->error = ErrorHandler::Singleton(true);
        $this->tmp_dir = LIBS_DIR.'/tmp/';
        if($this->model_type === M_TYPE_DB)
        {
            $this->db = DatabaseSingleton::getInstance();
            if(isset($this->table_name))
            {
                $this->getSchema();
            } else {
                if(!$this->doesTableExist())
                    $this->error->Toss('No table name specified. Please add property $table_name to model.', E_USER_ERROR);
                else
                    $this->getSchema();
            }
        }
        if($this->model_type === M_TYPE_DATA)
        {
            if(!isset($this->table_name))
                $this->table_name = strtolower(get_class($this));
            if(!isset(self::$table_schema[$this->table_name]))
                $this->error->Toss('Data driven models require a self::$table_schema array. Please add one.', E_USER_ERROR);
                
            $this->SQLiteStartUp();
        }
        
        if($id !== false)
        {
            $this->id = $id;
            return $this->where($id)->run();
        }
    }
    
    private function SQLiteStartUp()
    {
        if(is_file($this->tmp_dir.$this->table_name.'.db'))
            unlink($this->tmp_dir.$this->table_name.'.db');
        $this->sqlite = new SQLite3($this->tmp_dir.$this->table_name.'.db');
        
        //Create SQLite table
        $sql = 'CREATE TABLE '.$this->table_name.' (';
        $fields = '(';
        foreach(self::$table_schema[$this->table_name] as $field => $info)
        {
            $sql .= $field.' '.$info['Type'].',';
            $fields .= $field.',';
        }
        $sql = substr($sql, 0, -1).')';
        $fields = substr($fields, 0, -1).')';
        $this->sqlite->exec($sql);
        
        //Fill SQLite table
        foreach($this->data_info as $value)
        {
            $sql = 'INSERT INTO '.$this->table_name.' '.$fields.' VALUES (';
            foreach($value as $v)
            {
                $sql .= "'".$v."',";
            }
            $sql = substr($sql, 0, -1).')';
            $this->sqlite->exec($sql);
        }
    }
    
    /**
     * Checks if table exists
     * @access private
     * @return bool
     */
    private function doesTableExist()
    {
        $class_name = get_class($this);
        
        preg_match_all('/[A-Z][^A-Z]*/', $class_name, $strings);
        $table_name = false;
        if(isset($strings[0]))
            $table_name = strtolower(implode('_', $strings[0]));
        else
            return false;
        
        if($table_name)
        {
            $r = $this->db->query("SHOW TABLES");
            while($row = $r->fetch_assoc())
            {
                if($row['Tables_in_'.DB_DATABASE] == $table_name)
                {
                    $this->table_name = $table_name;
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * DRY helper function for {@link getSchema}
     * @access private
     * @param array $data
     */
    private function drySetTableSchema($data)
    {
        if($data['Key'] == "PRI")
        {
            $this->primary_key = $data['Field'];
        }
        self::$table_schema[$this->table_name][$data['Field']] = array(
            "Type" => $data['Type'],
            "Null" => $data['Null'],
            "Key" => $data['Key'],
            "Default" => $data['Default'],
            "Extra" => $data['Extra']
        );
    }
    
    /**
     * Gets schema for table
     * Stores result in static variable {@link $table_schema}
     * Sets up {@link $primary_key}
     */
    private function getSchema()
    {
        if(!isset(self::$table_schema[$this->table_name]))
        {
            if($this->model_type === M_TYPE_DB)
            {
                $r = $this->db->query("DESCRIBE `".$this->table_name."`");
                while($row = $r->fetch_assoc())
                {
                    $this->drySetTableSchema($row);
                }
                return true;
            }
        } else {
            foreach(self::$table_schema[$this->table_name] as $field => $value)
            {
                if($value['Key'] == "PRI")
                {
                    $this->primary_key = $field;
                }
            }
        }
    }

////////////////////////////////
// Magic Methods
////////////////////////////////

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
            $this->error->Toss('No method name ['.$method.']');
        }
    }
    
    /**
     * Dumps {@link $data}
     * @access public
     * @return array
     */
    public function dumpData()
    {
        return $this->data;
    }
    
    /**
     * Magic setter sets up {@link $data}
     * @access public
     */
    public function __set( $name, $value )
    {
        $this->data[$name] = $value;
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
        if($name == "primaryKey")
        {
            return $this->primary_key;
        }
        if($name == "table_name")
        {
            return $this->table_name;
        }
        if($name == "all")
        {
            return $this->data;
        }
        if(!isset($this->data[$name]))
        {
            $this->error->Toss(__CLASS__."::".__FUNCTION__." No field by the name [".$name."]", E_USER_NOTICE);
            return null;
        }
        if(is_object($this->data[$name]))
        {
            //@ToDo: Do something here
        }
        if(isset($this->output_format[$name]))
        {
            if(is_array($this->output_format[$name]))
            {
                return call_user_func(array($this, $this->output_format[$name]['custom']), $this->data[$name]);
            } else {
                return sprintf($this->output_format[$name], $this->data[$name]);
            }
        }
        return $this->data[$name];
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

////////////////////////////////
// Query Methods
////////////////////////////////
    
    /**
     * Starts a new model
     * @access public
     * @param array $keyValue default false
     * @return $this
     */
    public function newItem($keyValue = false)
    {
        if($keyValue === false)
        {
            foreach(self::$table_schema[$this->table_name] as $Field => $Info)
            {
                if($Info['Default'] != NULL)
                {
                    $this->data[$Field] = $Info['Default'];
                }
            }
        } else {
            if(!is_array($keyValue))
            {
                $this->error->Toss(__CLASS__."::".__FUNCTION__." - Argument must be an array");
            }
            foreach($keyValue as $key => $value)
            {
                $this->data[$key] = $value;
            }
            return $this;
        }
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
                if(!isset($this->data[$field]))
                    return false;
            }
            if(isset($params['must_be'])) // Check for type
            {
                if(isset($this->data[$field]))
                {
                    switch($params['must_be'])
                    {
                        case 'integer':
                            if(!is_integer($this->data[$field]))
                                return false;
                            break;
                        case 'bool':
                            if(!is_bool($this->data[$field]))
                                return false;
                            break;
                        case 'string':
                            if(!is_string($this->data[$field]))
                                return false;
                            break;
                        case 'float':
                            if(!is_float($this->data[$field]))
                                return false;
                            break;
                    }
                }
            }
            if(isset($params['custom']))
            {
                if(method_exists($this, $params['custom']))
                {
                    if(!call_user_func(array($this, $params['custom']), $this->data[$field]))
                        return false;
                }
            }
        }
        return true;
    }
    
    /**
     * Deletes current model from database
     * @access public
     * @return bool
     */
    public function delete()
    {
        $where = "";
        $sql = "DELETE FROM `".$this->table_name."` ";
        if(isset($this->data[$this->primary_key]))
        {
            $where = "WHERE `".$this->primary_key."` = '".$this->data[$this->primary_key]."'";
        } else {
            $where = "WHERE ";
            foreach($this->data as $k => $v)
            {
                if(isset(self::$table_schema[$this->table_name][$k]))
                {
                    if($this->model_type === M_TYPE_DB)
                        $where .= "`".$k."` = '".$this->db->real_escape_string($v)."' AND ";
                    if($this->model_type === M_TYPE_DATA)
                        $where .= "`".$k."` = '".$this->sqlite->escapeString($v)."' AND ";
                }
            }
            $where = substr($where, 0, -4);
        }
        if($this->model_type === M_TYPE_DB)
            return $this->db->query($sql.$where);
        if($this->model_type === M_TYPE_DATA)
            return $this->sqlite->query($sql.$where);
    }
    
    /**
     * Saves current model in database
     * @access public
     * @return bool
     */
    public function save()
    {
        $where = "";
        if(isset($this->data[$this->primary_key]))
        {
            $sql = "UPDATE `".$this->table_name."` SET ";
            $where = " WHERE `".$this->primary_key."` = '".$this->data[$this->primary_key]."' ";
        } else {
            $sql = "INSERT INTO `".$this->table_name."` SET ";
        }
        if(!$this->validate())
            $this->error->Toss('Validation fail', E_ERROR);
        
        foreach($this->data as $field => $value)
        {
            if($field != $this->primary_key)
            {
                if($this->model_type === M_TYPE_DB)
                    $sql .= "`".$field."` = '".$this->db->real_escape_string($value)."',";
                if($this->model_type === M_TYPE_DATA)
                    $sql .= "`".$field."` = '".$this->sqlite->escapeString($value)."',";
            }
        }
        $sql = substr($sql,0,-1);
        if($this->model_type === M_TYPE_DB)
            return $this->db->query($sql.$where);
        if($this->model_type === M_TYPE_DATA)
            return $this->sqlite->query($sql.$where);
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
    
    /**
     * Adds a join to {@link $join}
     * @param string $join
     * @access public
     * @return $this
     */
    public function joins($join)
    {
        if(is_string($join))
        {
            $this->joins[] = $join;
        }
        if(is_array($join))
        {
            foreach($join as $value)
            {
                if(in_array($value, $this->has_one))
                {
                    $obj = new $value();
                    $this->joins[] = "INNER JOIN ".$obj->table_name." ON (".$obj->table_name.".".$obj->primary_key." = ".$this->table_name.".".$obj->primaryKey.")";
                }
                if(in_array($value, $this->has_many))
                {
                    $obj = new $value();
                    $this->joins[] = "INNER JOIN ".$obj->table_name." ON (".$obj->table_name.".".$this->primary_key." = ".$this->table_name.".".$this->primaryKey.")";
                }
            }
        }
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
        if(func_num_args() == 1 && is_numeric(func_get_arg(0)))
        {
            if($this->model_type === M_TYPE_DB)
                $this->where[] = $this->table_name.".".$this->primary_key." = '".$this->db->real_escape_string(func_get_arg(0))."'";
            if($this->model_type === M_TYPE_DATA)
                $this->where[] = $this->table_name.".".$this->primary_key." = '".$this->sqlite->escapeString(func_get_arg(0))."'";
        }
        if(func_num_args() > 1 && is_string(func_get_arg(0)) && strpos(func_get_arg(0), "?") > -1)
        {
            $tmp = func_get_arg(0);
            $count = substr_count($tmp, "?");
            $broken = explode("?", $tmp);
            $where = "";
            for($i=0;$i<$count;$i++)
            {
                if($broken[$i] != "")
                {
                    if($this->model_type === M_TYPE_DB)
                        $where .= $broken[$i]."'".$this->db->real_escape_string(func_get_arg($i+1))."' ";
                    if($this->model_type === M_TYPE_DATA)
                        $where .= $broken[$i]."'".$this->sqlite->escapeString(func_get_arg($i+1))."' ";
                }
            }
            $this->where[] = $where;
        }
        if(func_num_args() > 0 && is_array(func_get_arg(0)))
        {
            $where = "";
            for($i=0;$i<func_num_args();$i++)
            {
                $arg = func_get_arg($i);
                if(!is_array($arg))
                {
                    $this->error->Toss(__CLASS__."::".__FUNCTION__." Must be an array");
                }
                foreach($arg as $key => $value)
                {
                    if(is_array($value))
                    {
                        $where .= $key. " IN ('".implode("','", $value)."') AND ";
                    } else {
                        if($this->model_type === M_TYPE_DB)
                            $where .= $key. " = '".$this->db->real_escape_string($value)."' AND ";
                        if($this->model_type === M_TYPE_DATA)
                            $where .= $key. " = '".$this->sqlite->escapeString($value)."' AND ";
                    }
                }
            }
            $this->where[] = substr($where,0,-5);
        }
        return $this;
    }
    
    /**
     * Sets up {@link $limit} to 1
     * @access public
     * @return $this
     */
    public function first()
    {
        $this->limit = 1;
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
        if(func_num_args() == 1)
        {
            $this->limit = func_get_arg(0);
        }
        if(func_num_args() == 2)
        {
            $this->limit = array(
                "offset" => func_get_arg(0),
                "limit" => func_get_arg(1)
            );
        }
        return $this;
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
     * Checks {@link $model_type} and runs according to it
     * @access public
     * @return $thi
     */
    public function run()
    {
        $query = $this->buildQuery();
        //@ToDo: Check for errors
        if($this->model_type === M_TYPE_DB) // Database driven results
        {
            return $this->DatabaseDriver($query);
        }
        if($this->model_type === M_TYPE_DATA) // Data driven results
        {
            return $this->DataDriver($query);
        }
        if($this->model_type === M_TYPE_FILE) // File driven results
        {
            return $this->FileDriver($query);
        }
    }
    
    /**
     * Data driven results
     * @access private
     * @param string $query
     * @return object $this
     */
    private function DataDriver($query)
    {
        $r = $this->sqlite->query($query);
        while($row = $r->fetchArray())
        {
            foreach($row as $key => $value)
            {
                if(!is_numeric($key))
                {
                    if(is_null($value))
                        $this->$key = "NULL";
                    else
                        $this->$key = $value;
                }
            }
            $this->array[] = clone $this;
        }
        return $this;
    }
    
    /**
     * File driven results
     * @access private
     * @param string $query
     * @return object $this
     */
    private function FileDriver($query)
    {
        
    }
    
    /**
     * Database driven results
     * @access private
     * @param string $query
     * @return object $this
     */
    private function DatabaseDriver($query)
    {
        $r = $this->db->query($query);
        while($row = $r->fetch_assoc())
        {
            foreach($row as $key => $value)
            {
                if(is_null($value))
                    $this->$key = "NULL";
                else
                    $this->$key = $value;
            }
            $this->array[] = clone $this;
        }
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
        unset($this->limit);
        return $this->run();
    }
    
    /**
     * Prints out built query
     * @access public
     * @return $this
     */
    public function printQuery()
    {
        echo $this->buildQuery();
        return $this;
    }
    
    /**
     * Builds query from [Query Builder] properties
     * @access private
     * @return string
     */
    private function buildQuery()
    {
        $query = "SELECT ";
        if(empty($this->select))
        {
            $this->select[] = $this->table_name.".*";
        }
        $query .= implode(',', $this->select);
        $query .= " FROM ".$this->table_name." ";
        if(!empty($this->joins))
        {
            foreach($this->joins as $value)
            {
                $query .= $value;
            }
        }
        if(!empty($this->where))
        {
            $query .= " WHERE ";
            $query .= implode(' AND ', $this->where);
        }
        if(!empty($this->groupby))
        {
            $query .= " GROUP BY ";
            foreach($this->groupby as $key => $value)
            {
                $query .= $key." ".$value.",";
            }
            $query = substr($query, 0, -1);
        }
        if(!empty($this->orderby))
        {
            $query .= " ORDER BY ";
            foreach($this->orderby as $value)
            {
                $query .= $value.",";
            }
            $query = substr($query, 0, -1);
        }
        if(!empty($this->limit))
        {
            $query .= " LIMIT ";
            if(!is_array($this->limit))
            {
                $query .= $this->limit;
            }
            else
            {
                $query .= $this->limit["offset"].",".$this->limit["limit"];
            }
        }
        return $query;
    }
}
?>