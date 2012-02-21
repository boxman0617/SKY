<?php
/**
 * Model Driver Class for MySQL database
 *
 * This class translates SKY's model class into MySQL
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
 */

import(CORE_DIR."/Driver.interface.php");

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
    private static $db;
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

    /**
     * Sets up self::$db if not instantiated with mysqli object
     */
    public function __construct()
    {
        if(!self::$db)
        {
            self::$db = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
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
            $r = self::$db->query("DESCRIBE `".$this->table_name."`");
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
        preg_match_all('/[A-Z][^A-Z]*/', $class_name, $strings);
        $table_name = false;
        if(isset($strings[0]))
            $table_name = strtolower(implode('_', $strings[0]));
        else
            return false;

        if($table_name)
        {
            $r = self::$db->query("SHOW TABLES");
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
     * Escapes string value using mysqli's escape method
     * @param string $value
     * @return string
     */
    public function escape($value)
    {
        return self::$db->real_escape_string($value);
    }

    /**
     * Builds MySQL query from Model's material
     * @param array $material
     * @return string
     */
    public function buildQuery($material)
    {
        $query = "SELECT ";
        if(empty($material['select']))
        {
            $material['select'][] = $this->table_name.".*";
        }
        $query .= implode(',', $material['select']);
        $query .= " FROM ".$this->table_name." ";
        if(!empty($material['joins']))
        {
            foreach($material['joins'] as $value)
            {
                $query .= $value;
            }
        }
        if(!empty($material['where']))
        {
            $query .= " WHERE ";
            foreach($material['where'] as $where)
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
                    $query .= implode(' AND ', $where);
                    $query .= ' AND ';
                }
            }
            $query = substr($query, 0, -4);
        }
        if(!empty($material['groupby']))
        {
            $query .= " GROUP BY ";
            foreach($material['groupby'] as $value)
            {
                $query .= '`'.$value."`,";
            }
            $query = substr($query, 0, -1);
        }
        if(!empty($material['orderby']))
        {
            $query .= " ORDER BY ";
            foreach($material['orderby'] as $value)
            {
                $query .= $value.",";
            }
            $query = substr($query, 0, -1);
        }
        if(!empty($material['limit']))
        {
            $query .= " LIMIT ";
            if(!is_array($material['limit']))
            {
                $query .= $material['limit'];
            }
            else
            {
                $query .= $material['limit']["offset"].",".$material['limit']["limit"];
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
        $r = self::$db->query($query);
        $return = array();
        $i = 0;
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
        $where = "WHERE `".self::$db->real_escape_string($field)."` = '".self::$db->real_escape_string($value)."'";

        return $this->db->query($sql.$where);
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
            if($field != $pri && $field != 'updated_at' && $field != 'created_at')
            {
                $query .= "`".$field."` = '".self::$db->real_escape_string($value)."',";
            }
            elseif($field == 'created_at' && $data[$pri] === NULL)
            {
                $query .= "`created_at` = NOW(),";
            }
        }
        $query = substr($query,0,-1);
        return self::$db->query($query.$where);
    }
}
?>
