<?php
SkyL::Import(SKYCORE_CORE_MODEL."/Driver.interface.php");

class Operator
{
    public $operator = null;
    public $value = null;
    
    public function __construct($operator = '!=', $value)
    {
        $this->operator = $operator;
        $this->value = $value;
    }
    
    public static function Not($value)
    {
        return new Operator('!=', $value);
    }
    
    public static function NotIn($value)
    {
        return new Operator('NOT IN', $value);
    }
    
    public static function IsNot($value)
    {
        return new Operator('IS NOT', $value);
    }
    
    public static function IsNotNull()
    {
        return new Operator('IS NOT NULL', null);
    }
    
    public static function IsNull()
    {
        return new Operator('IS NULL', null);
    }
    
    public static function LessThan($value)
    {
        return new Operator('<', $value);
    }
    
    public static function LessThanOrEqualTo($value)
    {
        return new Operator('<=', $value);
    }
    
    public static function GreaterThan($value)
    {
        return new Operator('>', $value);
    }
    
    public static function GreaterThanOrEqualTo($value)
    {
        return new Operator('>=', $value);
    }
    
    public static function Like($value)
    {
        throw new Exception('This is not yet supported!');
    }
}

class MySQLDriver implements iDriver
{
	private $TableName;
	private $PrimaryKey;
	private $Server;
    private static $_server;
	private static $DB;
	private $Model;
    public static $DefaultPrimaryKey = 'id';
    private static $_log_count = 0;

	public function __construct($db)
	{
		$this->Server = $db['DB_SERVER'];
        self::$_server = $this->Server;
		Log::corewrite('MySQLi ["%s"@"%s" on "%s"]', 1, __CLASS__, __FUNCTION__, array($db['DB_USERNAME'], $db['DB_SERVER'], $db['DB_DATABASE']));
		self::$DB[$this->Server] = new mysqli($db['DB_SERVER'], $db['DB_USERNAME'], $db['DB_PASSWORD'], $db['DB_DATABASE']);
		if(self::$DB[$this->Server]->connect_error)
		{
		    throw new Exception('Connection Error: ('.self::$DB[$this->Server]->connect_errno.') '.self::$DB[$this->Server]->connect_error);
		}
	}

    public static function GetDBInstance()
    {
        return self::$DB[self::$_server];
    }

	public function buildModelInfo(&$model)
	{
		$this->Model = $model;
		$driver_info = &$this->Model->__GetDriverInfo('query_material');
		$driver_info = array(
			'select' 	=> array(),
	        'from' 		=> array(),
	        'joins' 	=> array(),
	        'where' 	=> array(),
	        'limit' 	=> null,
	        'orderby' 	=> array(),
	        'groupby' 	=> array()
		);
	}

    public function setTableName($name)
    {
    	$this->TableName = $name;
    }

    public function setPrimaryKey(&$key)
    {
        if(is_null($key)) $key = self::$DefaultPrimaryKey;
        $this->PrimaryKey = $key;
    }

    public function getPrimaryKey()
    {
        return $this->PrimaryKey;
    }

    public function escape($value)
    {
    	return self::$DB[$this->Server]->real_escape_string($value);
    }

    public function run()
    {
        $QUERY = $this->buildQuery();
        if(AppConfig::IsMySQLCacheEnabled())
        {
            if(Cache::IsCached($QUERY))
            {
                //Log::corewrite('Getting cached value [%s]', 1, __CLASS__, __FUNCTION__, array($QUERY));
                return Cache::GetCache($QUERY);
            }
        }
        
        $_START = $this->LogBeforeAction('RUN', $QUERY);
        
        $RESULTS = self::$DB[$this->Server]->query($QUERY);
        
        $this->LogAfterAction($_START, $RESULTS, array('RESULTS' => $RESULTS->num_rows));
        
        if($RESULTS === true)
            return $RESULTS;
        $RETURN = array();
        while($ROW = $RESULTS->fetch_assoc())
            $RETURN[] = $this->enum_to_bool($ROW);
        if(AppConfig::IsMySQLCacheEnabled())
            Cache::Cache($QUERY, $RETURN);
        return $RETURN;
    }
    
    private function diff($array1, $array2)
    {
        $diff = array();
        foreach($array1 as $key => $value)
        {
            if(is_object($value))
                continue;
            if((string)$value !== (string)$array2[$key])
                $diff[$key] = $value;
        }
        return $diff;
    }

    public function update(&$unaltered, &$data, $position)
    {
        if(isset($unaltered[$position]))
        {
            $CHANGES = $this->diff($data, $unaltered[$position]);
            if(empty($CHANGES))
                return array(
                    'status' => true,
                    'updated' => $data
                );
            $QUERY   = 'UPDATE `'.$this->TableName.'` SET ';

            if(isset($CHANGES['created_at'])) unset($CHANGES['created_at']);
            if(isset($CHANGES['updated_at'])) unset($CHANGES['updated_at']);

            $COLUMNS = $this->ShowColumns();
            $this->bool_to_string($CHANGES);
            foreach($CHANGES as $FIELD => $VALUE)
            {
                if(in_array($FIELD, $COLUMNS))
                    $QUERY .= "`".$FIELD."` = '".self::$DB[$this->Server]->real_escape_string($VALUE)."',";
            }
            $QUERY = substr($QUERY, 0, -1)." WHERE `".$this->PrimaryKey."` = '".$data[$this->PrimaryKey]."'";
                $_START = $this->LogBeforeAction('UPDATE', $QUERY);
            $STATUS = self::$DB[$this->Server]->query($QUERY);
                $this->LogAfterAction($_START, $STATUS);
            return array(
                'status' => $STATUS,
                'updated' => array_merge($CHANGES, $data)
            );
        }
    }

    public static function created_at()
    {
        return date('Y-m-d H:i:s');
    }

    public static function updated_at()
    {
        return date('Y-m-d H:i:s');
    }

    public function savenew(&$data)
    {
        $QUERY = 'INSERT INTO `'.$this->TableName.'` SET ';
        $NOW = self::created_at();
        $data['created_at'] = $NOW;
        $data['updated_at'] = $NOW;
        $COLUMNS = $this->ShowColumns();
        $this->bool_to_string($data);
        foreach($data as $FIELD => $VALUE)
        {
            if(in_array($FIELD, $COLUMNS))
                $QUERY .= ' `'.$FIELD.'` = "'.$this->escape($VALUE).'",';
        }
        $QUERY = substr($QUERY, 0, -1);
            $_START = $this->LogBeforeAction('INSERT', $QUERY);
        $ID = self::$DB[$this->Server]->query($QUERY);
            $this->LogAfterAction($_START, $ID);
        if($ID) $ID = self::$DB[$this->Server]->insert_id;
        return array(
            'pri' => $ID,
            'data' => $data
        );
    }

    public function delete(&$ID)
    {
        $QUERY = 'DELETE FROM `'.$this->TableName.'` WHERE `'.$this->PrimaryKey.'` = "'.$this->escape($ID).'"';
            $_START = $this->LogBeforeAction('DELETE', $QUERY);
        $RETURN = self::$DB[$this->Server]->query($QUERY);
            $this->LogAfterAction($_START, $RETURN);
        return $RETURN;
    }

    //============================================================================//
    // Log Method                                                                 //
    //============================================================================//

    private function LogBeforeAction($action_name, $action)
    {
        if(SkyDefines::GetEnv() != 'PRO')
        {
            $LOG = fopen(DIR_LOG."/development.log", 'a');
            if(self::$_log_count == 0)
                fwrite($LOG, ">========DEV=LOG===========> ".date('m-d-Y H:i:s')."\n");
            fwrite($LOG, "\033[36mSTART\033[0m: ".date('H:i:s')."\t".$action_name.": ".trim($action)."\n");
            fclose($LOG);
            self::$_log_count++;
        }
        return microtime(true);
    }

    private function LogAfterAction(&$_START, $STATUS, $EXTRA = array())
    {
        if(SkyDefines::GetEnv() != 'PRO')
        {
            $_END = microtime(true);
            $LOG = fopen(DIR_LOG."/development.log", 'a');
            if($STATUS === false && isset(self::$DB[$this->Server]->error))
            {
                fwrite($LOG, "\033[35mERROR\033[0m: ".date('H:i:s')."\tMSG:\033[0m [".self::$DB[$this->Server]->error."\n");
                trigger_error("[MySQL ERROR] => ".self::$DB[$this->Server]->error, E_USER_WARNING);
            }
            $EXTRA_INFO = "";
            foreach($EXTRA as $KEY => $VALUE)
                $EXTRA_INFO .= $KEY." [".$VALUE."] ";
            fwrite($LOG, "\033[35mEND\033[0m: ".date('H:i:s')."\tTime\033[0m [".round($_END - $_START, 5)."] ".$EXTRA_INFO."\n");
            fclose($LOG);
        }
    }

    //============================================================================//
    // Create Table Methods                                                       //
    //============================================================================//

    public static function DropTable($name)
    {
        $db = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
        return $db->query("DROP TABLE `".$name."`");
    }

    public static function CreateTable($name, $fields)
    {
        $CREATE_STATEMENT = "CREATE TABLE `".$name."`\n (`".self::$DefaultPrimaryKey."` INT(11) NOT NULL AUTO_INCREMENT, \n";
        foreach($fields as $name => $type)
        {
            $tmp = explode('_', $type);
            $data_type = $tmp[0].'('.$tmp[1].')';
            $CREATE_STATEMENT .= "`".$name."` ".$data_type." NOT NULL, \n";
        }
        $CREATE_STATEMENT .= "`created_at` DATETIME NOT NULL,\n `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n PRIMARY KEY (`".self::$DefaultPrimaryKey."`)\n)";
        $CREATE_STATEMENT .= "ENGINE=INNODB DEFAULT CHARSET=latin1";
        $db = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
        return $db->query($CREATE_STATEMENT);
    }
    
    public function ShowColumns()
    {
        $QUERY = "SHOW COLUMNS FROM `".$this->TableName."`";
        $RESULTS = self::$DB[$this->Server]->query($QUERY);
        $COLUMNS = array();
        while($ROW = $RESULTS->fetch_assoc())
            $COLUMNS[] = $ROW['Field'];
        return $COLUMNS;
    }

    //============================================================================//
    // Query Builder Methods                                                      //
    //============================================================================//
    
    private function bool_to_string(&$where)
    {
        foreach($where as $field => $value)
        {
            if(is_bool($value))
            {
                if($value === true)
                    $where[$field] = 'Y';
                else
                    $where[$field] = 'N';
            }
        }
    }
    
    private function enum_to_bool($row)
    {
        foreach($row as $field => $value)
        {
            if($value == 'Y' || $value == 'N')
            {
                if($value === 'Y')
                    $row[$field] = true;
                else
                    $row[$field] = false;
            }
        }
        return $row;
    }
    
    public function search($where = array(), $select = array())
    {
        if(!empty($select)) call_user_func_array(array($this, 'select'), $select);
        $this->bool_to_string($where);
        $this->where($where);
    }

    public function findOne($where = array())
    {
        $this->bool_to_string($where);
        $this->where($where);
        $this->limit(1);
    }

    /**
     * Builds MySQL query from Model's material
     * @param array $material
     * @return string
     */
    public function buildQuery()
    {
        $driver_info = $this->Model->__GetDriverInfo('query_material');
        $query = "SELECT ";
        if(empty($driver_info['select']))
            $driver_info['select'][] = $this->TableName.".*";
        $query .= implode(',', $driver_info['select']);
        if(empty($driver_info['from']))
            $driver_info['from'] = $this->TableName;
        $query .= " FROM ".$driver_info['from']." ";
        if(!empty($driver_info['joins']))
        {
            foreach($driver_info['joins'] as $value)
            {
                $query .= $value;
            }
        }
        if(!empty($driver_info['where']))
        {
            $query .= " WHERE ";
            foreach($driver_info['where'] as $where)
            {
                if(is_array($where))
                {
                    $query .= "`".$where['field']."` ".$where['operator'];
                    if(is_array($where['value']))
                    {
                        $query .= " ('".implode("','", $where['value'])."') ";
                    } else {
                        $query .= " '".$this->escape($where['value'])."'";
                    }
                    $query .= ' AND ';
                } else {
                    $query .= $where.' AND ';
                }
            }
            $query = substr($query, 0, -4);
        }
        if(!empty($driver_info['groupby']))
        {
            $query .= " GROUP BY ";
            foreach($driver_info['groupby'] as $value)
            {
                $query .= '`'.$value."`,";
            }
            $query = substr($query, 0, -1);
        }
        if(!empty($driver_info['orderby']))
        {
            $query .= " ORDER BY ";
            foreach($driver_info['orderby'] as $value)
            {
                $query .= $value.",";
            }
            $query = substr($query, 0, -1);
        }
        if(!empty($driver_info['limit']))
        {
            $query .= " LIMIT ";
            if(!is_array($driver_info['limit']))
            {
                $query .= $driver_info['limit'];
            }
            else
            {
                $query .= $driver_info['limit']["offset"].",".$driver_info['limit']["limit"];
            }
        }
        return $query;
    }


    /**
     * Allows selection of specific fields in table
     *
     * @example
     * <?php
     * $users = new Users();
     * $users->select('name', 'number')->run();
     * ?>
     * 
     * @access public
     * @return $this
     */
    public function select()
    {
        $driver_info = &$this->Model->__GetDriverInfo('query_material');
        $driver_info['select'] = array();
        for($i=0;$i<func_num_args();$i++)
            $driver_info['select'][] = func_get_arg($i);
    }

    /**
     * Allows user to overwrite what table query will be selecting from.
     * Usually used internally by Association Methods
     *
     * @example
     * <?php
     * $users = new Users();
     * $users->from('adminusers')->run();
     * ?>
     * 
     * @access public
     * @return $this
     */
    public function from($from)
    {
        $driver_info = &$this->Model->__GetDriverInfo('query_material');
        $driver_info['from'] = $from;
    }

    /**
     * Gives query a where clause. Allows different ways of
     * determining how the clause will be constructed.
     *
     * Allows the following parameters:
     * // Regular string based //////////////////////////////////////////////////////////////////////
     * ::where(String)                                                                          
     *     PHP: "`name` = 'Alan'"
     *     SQL: WHERE `name` = 'Alan'
     * // Array based ///////////////////////////////////////////////////////////////////////////////
     * ::where(Array)                                                                           
     *     PHP: array(1, 2, 3, 4, 5, 6)
     *     SQL: WHERE `field` IN (1, 2, 3, 4, 5, 6)
     * // Associative substitution based ////////////////////////////////////////////////////////////
     * ::where(String $query, Array $sub)                                                       
     *     PHP: "`name` = :name", array('name' => 'Alan')
     *     SQL: WHERE `name` = 'Alan'
     * // Ordered substitution based ////////////////////////////////////////////////////////////////
     * ::where(String $query, String $sub [, String $...])                                      
     *     PHP: "`name` = ? AND `age` = ?", 'Alan', 2
     *     SQL: WHERE `name` = 'Alan' AND `age` = 23
     * // Multiple Associative-Array based //////////////////////////////////////////////////////////
     * ::where(String $query, Array $sub [, Array $...])                                        
     *     PHP: array('name' => 'Alan'), array('hobbies', => array('programming', 'music'))
     *     SQL: WHERE name = 'Alan' AND hobbies IN ('programming', 'music')
     * 
     * @access public
     * @return $this
     */
    public function where()
    {
        $driver_info = &$this->Model->__GetDriverInfo('query_material');

        // Regular string where clause. No extra work needed
        // PHP: "`name` = 'Alan'"
        // SQL: WHERE `name` = 'Alan'
        if(func_num_args() == 1 && is_string(func_get_arg(0)))
        {
            $driver_info['where'][] = func_get_arg(0);
        }
        // Array based where clause. Turning array into IN() where clause:
        // PHP: array('id' => array(1, 2, 3, 4, 5, 6))
        // SQL: WHERE `id` IN (1, 2, 3, 4, 5, 6)
        elseif(func_num_args() == 1 && is_array(func_get_arg(0)))
        {
            foreach(func_get_arg(0) as $key => $value)
            {
                $operator = '=';
                if(is_array($value))
                    $operator = 'IN';
                elseif(is_object($value) && $value instanceof Operator)
                {
                    $operator = $value->operator;
                    $value = $value->value;
                }
                $driver_info['where'][] = array(
                    'field' => $this->escape($key),
                    'operator' => $operator,
                    'value' => $value
                );
            }
        }
        // Associative substitution based where clause. 
        // Substitution of symbol like patterns (:symbol) in first parameter
        // PHP: "`name` = :name", array('name' => 'Alan')
        // SQL: WHERE `name` = 'Alan'
        elseif(func_num_args() == 2 && is_string(func_get_arg(0)) && is_array(func_get_arg(1)) && strpos(func_get_arg(0), ":") > -1)
        {
            $tmp = func_get_arg(0);
            $data = func_get_arg(1);
            preg_match_all('/\:([a-zA-Z0-9]+)/', $tmp, $matches);
            foreach($matches[1] as $field)
            {
                $tmp = preg_replace('/(\:'.$field.')/', "'".$this->escape($data[$field])."'", $tmp);
            }
            $driver_info['where'][] = $tmp;
        }
        // Ordered substitution based where clause.
        // Substitution of ? characters in first parameter with remaining parameters
        // PHP: "`name` = ? AND `age` = ?", 'Alan', 23
        // SQL: WHERE `name` = 'Alan' AND `age` = 23
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
                    $where .= trim($broken[$i])." '".$this->escape(func_get_arg($i+1))."' ";
            }
            if(isset($broken[$i]))
                $where .= trim($broken[$i]);
            $driver_info['where'][] = trim($where);
        }
        // Multiple Associative-Array based where clause.
        // Allows multiple arrays to be passed where
        // the key is the field and the value is the value
        // PHP: array('name' => 'Alan'), array('hobbies', => array('programming', 'music'))
        // SQL: WHERE name = 'Alan' AND hobbies IN ('programming', 'music')
        elseif(func_num_args() > 0 && is_array(func_get_arg(0)))
        {
            $count = func_num_args();
            for($i=0;$i<$count;$i++)
            {
                $arg = func_get_arg($i);
                if(!is_array($arg))
                    trigger_error(__CLASS__."::".__FUNCTION__." Must be an array");
                foreach($arg as $key => $value)
                {
                    $operator = '=';
                    if(is_array($value))
                        $operator = 'IN';
                    $driver_info['where'][] = array(
                        'field' => $this->escape($key),
                        'operator' => $operator,
                        'value' => $value
                    );
                }
            }
        }
    }

    /**
     * Allows the joining of other tables
     *
     * @example
     * <?php
     * $users = new Users();
     * $users->joins('addresses ON (users.id = addresses.user_id)')->run();
     * ?>
     *
     * @idea Could allow the passing of objects to then allow this method to
     *       find the associations?
     * @access public
     * @return $this
     */
    public function joins($join)
    {
        $driver_info = &$this->Model->__GetDriverInfo('query_material');
        if(is_string($join))
            $driver_info['joins'][] = $join;
    }

    /**
     * Allows limiting and offsetting of results set
     *
     * Allows the following parameters:
     * // Default Limit //////////////////////////////////////////////////////////////////////
     * ::limit()
     *     SQL: LIMIT 1
     * // Regular Limit //////////////////////////////////////////////////////////////////////
     * ::limit(Integer $limit)
     *     PHP: 10
     *     SQL: LIMIT 10
     * // Offset Limit ///////////////////////////////////////////////////////////////////////
     * ::limit(Integer $offset, Integer $limit)
     *     PHP: 10, 100
     *     SQL: LIMIT 10, 100
     * 
     * @example
     * <?php
     * $users = new Users();
     * $users->limit(10)->run();
     * ?>
     *
     * @idea Could allow the passing of objects to then allow this method to
     *       find the associations?
     * @access public
     * @return $this
     */
    public function limit()
    {
        $driver_info = &$this->Model->__GetDriverInfo('query_material');
        if(func_num_args() == 0)
            $driver_info['limit'] = 1;
        elseif(func_num_args() == 1)
            $driver_info['limit'] = func_get_arg(0);
        elseif(func_num_args() == 2)
        {
            $driver_info['limit'] = array(
                "offset" => func_get_arg(0),
                "limit" => func_get_arg(1)
            );
        }
    }

    /**
     * Allows the sorting clause 'order by'
     *
     * @example
     * <?php
     * $users = new Users();
     * $users->orderby('age')->run();
     * ?>
     * 
     * @access public
     * @return $this
     */
    public function orderby($by)
    {
        $driver_info = &$this->Model->__GetDriverInfo('query_material');
        $driver_info['orderby'][] = $by;
    }

    /**
     * Allows the grouping clause 'group by'
     *
     * @example
     * <?php
     * $users = new Users();
     * $users->groupby('age')->run();
     * ?>
     * 
     * @access public
     * @return $this
     */
    public function groupby($by)
    {
        $driver_info = &$this->Model->__GetDriverInfo('query_material');
        $driver_info['groupby'][] = $by;
    }
}
?>