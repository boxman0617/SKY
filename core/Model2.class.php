<?php
require_once(dirname(__FILE__).'/../configs/defines.php');
import(ERROR_CLASS);
import(CONFIGS_DIR.'/configure.php');
abstract class Model2 implements Iterator
{
    private $driver;
    private $error;
    private $db;
    protected $data = array();
    protected $table_name;
    protected $table_schema = array();
    protected $last_query;
    
    /**
     * Output formatting
     * @access public
     * @var array
     */
    public $output_format = array();
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
    
    public function __construct($hash = array())
    {
        $this->driver = MODEL_DRIVER."Driver";
        $this->error = ErrorHandler::Singleton(true);
        if(is_file(CORE_DIR."/drivers/".MODEL_DRIVER.".driver.php"))
        {
            import(CORE_DIR."/drivers/".MODEL_DRIVER.".driver.php");
            $this->db = new $this->driver();
            if(!$this->db instanceof iDriver)
                $this->error->Toss('Driver loaded is not an instance of iDriver interface!', E_USER_ERROR);
            if(isset($this->table_name))
            {
                $this->db->setTableName($this->table_name);
                $this->db->setSchema();
            } else {
                if(!$this->db->doesTableExist(get_class($this)))
                    $this->error->Toss('No table name specified. Please add property $table_name to model.', E_USER_ERROR);
                else
                {
                    preg_match_all('/[A-Z][^A-Z]*/', get_class($this), $strings);
                    if(isset($strings[0]))
                        $table_name = strtolower(implode('_', $strings[0]));
                    else
                        $this->error->Toss('Error handling class name as table name.', E_USER_ERROR);
                    $this->table_name = $table_name;
                    $this->db->setTableName($this->table_name);
                    $this->db->setSchema();
                }
            }
            $this->table_schema = $this->db->getSchema();
        } else {
            $this->error->Toss('No driver found for model! Model: '.get_class($this).' | Driver: '.MODEL_DRIVER, E_USER_ERROR);
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
        if(!isset($this->data[$name]))
        {
            $this->error->Toss(__CLASS__."::".__FUNCTION__." No field by the name [".$name."]", E_USER_NOTICE);
            return null;
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
    
    /**
     * Saves current model in database
     * @access public
     * @return bool
     */
    public function save()
    {
        return $this->db->save($this->data);
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
    
    public function from()
    {
        
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
        // @todo Need to figure out what to do with this
        //if(func_num_args() == 1 && is_numeric(func_get_arg(0)))
        //{
        //    $this->where[] = $this->table_name.".".$this->primary_key." = '".$this->db->real_escape_string(func_get_arg(0))."'";
        //}
        if(func_num_args() > 1 && is_string(func_get_arg(0)) && strpos(func_get_arg(0), ":") > -1)
        {
            
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
                    $where .= $broken[$i]."'".$this->db->escape(func_get_arg($i+1))."' ";
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
                        $where .= $key. " = '".$this->db->escape($value)."' AND ";
                    }
                }
            }
            $this->where[] = substr($where,0,-5);
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
}

class Zip extends Model2
{
    
}

$z = new Zip();

$r = $z->where('zip = ?', '92234')->run();
echo $r->city."\n-------------------------\n";
foreach($r as $v)
{
    $v->country = "USA";
    if($v->save())
        echo "YAY!!!\n";
}
?>