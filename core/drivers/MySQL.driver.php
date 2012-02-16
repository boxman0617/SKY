<?php
import(CORE_DIR."/Driver.interface.php");
class MySQLDriver implements iDriver
{
    private static $db;
    private static $table_schema;
    private $table_name;
    
    public function __construct()
    {
        if(!self::$db)
        {
            self::$db = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
        }
    }
    
    public function setTableName($name)
    {
        $this->table_name = $name;
    }
    
    public function getSchema()
    {
        if(!isset(self::$table_schema[$this->table_name]))
            $this->setSchema();
        return self::$table_schema[$this->table_name];
    }
    
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
    
    public function escape($value)
    {
        return self::$db->real_escape_string($value);
    }
    
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
            $query .= implode(' AND ', $material['where']);
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
                $query .= '`'.$value."`,";
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
        if($data[$field] === NULL)
        {
            $query = 'INSERT INTO `'.$this->table_name.'` SET ';
        } else {
            $query = 'UPDATE `'.$this->table_name.'` SET ';
            $where = ' WHERE `'.$pri.'` = "'.$data[$pri].'"';
        }
        
        foreach($data as $field => $value)
        {
            if($field != $pri)
            {
                $query .= "`".$field."` = '".self::$db->real_escape_string($value)."',";
            }
        }
        $query = substr($query,0,-1);
        return self::$db->query($query.$where);
    }
}
?>