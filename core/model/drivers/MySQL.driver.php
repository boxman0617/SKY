<?php
/**
 * MySQL Driver
 *
 * This driver interfaces the Model core class
 * to a MySQL server.
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
 * @link        http://www.codethesky.com/docs/mysqldriver
 * @package     Sky.Core
 */

import(SKYCORE_CORE_MODEL."/Driver.interface.php");

/**
 * MySQLDriver Driver Class Implements iDriver interface
 * This class talks MySQL
 * @package Sky.Driver
 * @subpackage MySQL
 */
class MySQLDriver implements iDriver
{
    /**
     * MySQLi's database instance
     * @access static private
     * @var object
     */
    private static $db = array();
    /**
     * Schema of current table
     * @access static private
     * @var array
     */
    private static $table_schema;
    /**
     * Model's table name
     * @access private
     * @var string
     */
    private $table_name;
    
    private $db_flag = false;
    private $db_array = array();
    private $server;

    protected $_query_material = array(
        'select' => array(),
        'from' => array(),
        'joins' => array(),
        'where' => array(),
        'limit' => null,
        'orderby' => array(),
        'groupby' => array()
    );

    /**
     * Sets up self::$db[$this->server] if not instantiated with mysqli object
     */
    public function __construct($db_array = NULL)
    {
        if(is_null($db_array))
        {
            $this->server = DB_SERVER;
            if(!isset(self::$db[$this->server]))
                self::$db[$this->server] = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
        }
        else
        {
            $this->server = $db_array['DB_SERVER'];
            $this->db_flag = true;
            $this->db_array = $db_array;
            if(!isset(self::$db[$this->server]))
                self::$db[$this->server] = new mysqli($db_array['DB_SERVER'], $db_array['DB_USERNAME'], $db_array['DB_PASSWORD'], $db_array['DB_DATABASE']);
        }
    }

    /**
     * Sets current table for object {@link $table_name}
     * @param string $name
     */
    public function setTableName($name)
    {
        $this->table_name = $name;
    }

    /**
     * Returns table's schema, if not set it will figure out the schema then return
     * @return array self::$table_schema[$this->table_name]
     */ 
    public function getSchema()
    {
        if(!isset(self::$table_schema[$this->table_name]))
            $this->setSchema();
        return self::$table_schema[$this->table_name];
    }
    
    /**
     * Figures out table's schema and sets it {@link self::$table_schema}
     * @return bool
     */
    public function setSchema()
    {
        if(!isset(self::$table_schema[$this->table_name]))
        {
            $r = self::$db[$this->server]->query("DESCRIBE `".$this->table_name."`");
            while($row = $r->fetch_assoc())
            {
                self::$table_schema[$this->table_name][$row['Field']] = array(
                    "Type" => $row['Type'],
                    "Null" => $row['Null'],
                    "Key" => $row['Key'],
                    "Default" => $row['Default'],
                    "Extra" => $row['Extra']
                );
            }
        }
        return true;
    }

    /**
     * Checks to see if table exists in database
     * @param string $class_name
     * @return bool
     */
    public function doesTableExist($class_name)
    {
        $table_name = strtolower($class_name);
        Log::corewrite('Checking if table exists [%s]', 1, __CLASS__, __FUNCTION__, array($table_name));
        if($table_name)
        {
            $r = self::$db[$this->server]->query("SHOW TABLES");
            while($row = $r->fetch_assoc())
            {
                if($row['Tables_in_'.(($this->db_flag) ? $this->db_array['DB_DATABASE'] : DB_DATABASE)] == $table_name)
                {
                    $this->table_name = $table_name;
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Escapes string value using mysqli's escape method
     * @param string $value
     * @return string
     */
    public function escape($value)
    {
        return self::$db[$this->server]->real_escape_string($value);
    }

    /**
     * Builds MySQL query from Model's material
     * @param array $material
     * @return string
     */
    public function buildQuery()
    {
        $query = "SELECT ";
        if(empty($this->_query_material['select']))
            $this->_query_material['select'][] = $this->table_name.".*";
        $query .= implode(',', $this->_query_material['select']);
        if(empty($this->_query_material['from']))
            $this->_query_material['from'] = $this->table_name;
        $query .= " FROM ".$this->_query_material['from']." ";
        if(!empty($this->_query_material['joins']))
        {
            foreach($this->_query_material['joins'] as $value)
            {
                $query .= $value;
            }
        }
        if(!empty($this->_query_material['where']))
        {
            $query .= " WHERE ";
            foreach($this->_query_material['where'] as $where)
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
        if(!empty($this->_query_material['groupby']))
        {
            $query .= " GROUP BY ";
            foreach($this->_query_material['groupby'] as $value)
            {
                $query .= '`'.$value."`,";
            }
            $query = substr($query, 0, -1);
        }
        if(!empty($this->_query_material['orderby']))
        {
            $query .= " ORDER BY ";
            foreach($this->_query_material['orderby'] as $value)
            {
                $query .= $value.",";
            }
            $query = substr($query, 0, -1);
        }
        if(!empty($this->_query_material['limit']))
        {
            $query .= " LIMIT ";
            if(!is_array($this->_query_material['limit']))
            {
                $query .= $this->_query_material['limit'];
            }
            else
            {
                $query .= $this->_query_material['limit']["offset"].",".$this->_query_material['limit']["limit"];
            }
        }
        return $query;
    }

    /**
     * Executes query on mysqli's query method
     * @param string $query
     * @return array
     */
    public function runQuery($query)
    {
        $r = self::$db[$this->server]->query($query);
        $return = array();
        $i = 0;
        if(!$r)
        {
            if(isset(self::$db[$this->server]->error)) trigger_error("[MySQL ERROR] => ".self::$db[$this->server]->error, E_USER_WARNING);
            return $return;
        }
        if($r === true)
            return true;
        while($row = $r->fetch_assoc())
        {
            foreach($row as $key => $value)
            {
                if(is_null($value))
                    $return[$i][$key] = "NULL";
                else
                    $return[$i][$key] = $value;
            }
            $i++;
        }
        return $return;
    }

    /**
     * Deletes current model from database
     * @access public
     * @return bool
     */
    public function delete($field, $value)
    {
        $sql = "DELETE FROM `".$this->table_name."` ";
        if(!is_array($value))
            $value = array($value);
        $where = "WHERE `".self::$db[$this->server]->real_escape_string($field)."` IN (";
        foreach($value as $v)
        {
            $where .= "'".self::$db[$this->server]->real_escape_string($v)."',";
        }
        $where = substr($where, 0, -1);
        $where .= ")";
        if($GLOBALS['ENV'] == 'DEV')
        {
            $f = fopen(DIR_LOG."/development.log", 'a');
            fwrite($f, "START: ".date('H:i:s')."\t".trim($sql.$where)."\n");
            fclose($f);
        }
        return self::$db[$this->server]->query($sql.$where);
    }

    /**
     * Saves current model's data to database
     * @param array $data
     * @return mixed
     */
    public function save($data)
    {
        $where = "";
        foreach(self::$table_schema[$this->table_name] as $field => $detail)
        {
            if($detail['Key'] == 'PRI')
            {
                $pri = $field;
                continue;
            }
        }
        if($data[$pri] === NULL)
        {
            $query = 'INSERT INTO `'.$this->table_name.'` SET ';
        } else {
            $query = 'UPDATE `'.$this->table_name.'` SET ';
            $where = ' WHERE `'.$pri.'` = "'.$data[$pri].'"';
        }

        foreach($data as $field => $value)
        {
            if($field != $pri && $field != 'updated_at' && $field != 'created_at' && isset(self::$table_schema[$this->table_name][$field]))
            {
                $query .= "`".$field."` = '".self::$db[$this->server]->real_escape_string($value)."',";
            }
            elseif($field == 'created_at' && $data[$pri] === NULL)
            {
                $query .= "`created_at` = NOW(),";
            }
        }
        $query = substr($query,0,-1);
        if($GLOBALS['ENV'] == 'DEV')
        {
            $f = fopen(DIR_LOG."/development.log", 'a');
            fwrite($f, "START: ".date('H:i:s')."\t".trim($query.$where)."\n");
            fclose($f);
        }
        if(self::$db[$this->server]->query($query.$where))
        {
            if(self::$db[$this->server]->insert_id !== 0)
                return self::$db[$this->server]->insert_id;
            else
                return true;
        } else {
            return false;
        }
    }

    //============================================================================//
    // Query Builder Methods                                                      //
    //============================================================================//


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
        $this->_query_material['select'] = array();
        for($i=0;$i<func_num_args();$i++)
            $this->_query_material['select'][] = func_get_arg($i);
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
        $this->_query_material['from'] = $from;
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
        Log::corewrite('Adding where clause to query', 2, __CLASS__, __FUNCTION__);

        // Regular string where clause. No extra work needed
        // PHP: "`name` = 'Alan'"
        // SQL: WHERE `name` = 'Alan'
        if(func_num_args() == 1 && is_string(func_get_arg(0)))
        {
            $this->_query_material['where'][] = func_get_arg(0);
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
                $this->_query_material['where'][] = array(
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
            $this->_query_material['where'][] = $tmp;
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
            $this->_query_material['where'][] = trim($where);
        }
        // Multiple Associative-Array based where clause.
        // Allows multiple arrays to be passed where
        // the key is the field and the value is the value
        // PHP: array('name' => 'Alan'), array('hobbies', => array('programming', 'music'))
        // SQL: WHERE name = 'Alan' AND hobbies IN ('programming', 'music')
        elseif(func_num_args() > 0 && is_array(func_get_arg(0)))
        {
            for($i=0;$i<func_num_args();$i++)
            {
                $arg = func_get_arg($i);
                if(!is_array($arg))
                {
                    trigger_error(__CLASS__."::".__FUNCTION__." Must be an array");
                }
                foreach($arg as $key => $value)
                {
                    $operator = '=';
                    if(is_array($value))
                        $operator = 'IN';
                    $this->_query_material['where'][] = array(
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
        if(is_string($join))
            $this->_query_material['joins'][] = $join;
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
        if(func_num_args() == 0)
            $this->_query_material['limit'] = 1;
        elseif(func_num_args() == 1)
            $this->_query_material['limit'] = func_get_arg(0);
        elseif(func_num_args() == 2)
        {
            $this->_query_material['limit'] = array(
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
        $this->_query_material['orderby'][] = $by;
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
        $this->_query_material['groupby'][] = $by;
    }
}
?>
