<?php
/**
 * DBBuild Core Class
 *
 * This class allows building MySQL tables from within
 * the terminal.
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
 * @link        http://www.codethesky.com/docs/dbbuildclass
 * @package     Sky.Core
 */

/**
 * DBBuild class
 * @ToDo: Build description
 */
class DBBuild
{
    /**
     * Standard INput
     * @access private
     * @var string
     */
    private $STDIN;
    /**
     * Namespace for tasks
     * @access private
     * @var string
     */
    private $table = array();
    private $data_types = array(
        "char",
        "varchar",
        "tinytext",
        "text",
        "blob",
        "mediumtext",
        "mediumblob",
        "longtext",
        "longblob",
        "tinyint",
        "smallint",
        "mediumint",
        "int",
        "bigint",
        "float",
        "double",
        "decimal",
        "date",
        "datetime",
        "timestamp",
        "time",
        "enum"
    );

    /**
     * Constructor
     */
    public function __construct($params)
    {
        unset($params[0]);
        $this->STDIN = array_values($params);
    }

    public function HandleInput()
    {
        if(!empty($this->STDIN))
        {
            $this->table['name'] = $this->STDIN[0];
            unset($this->STDIN[0]);
            $columns = array_values($this->STDIN);
            foreach($columns as $clm)
            {
                $tmp = explode(':', $clm);
                $tmp_column = array(
                    'name' => $tmp[0],
                    'null' => false,
                    'type' => 'varchar(255)'
                );
                foreach ($this->data_types as $type)
                {
                    if($p1 = strpos($clm, $type))
                    {
                        $p2 = strpos($clm, ":", $p1);
                        $clm_string = "";
                        for($i=$p1;$i<(($p2) ? $p2 : strlen($clm));$i++)
                        {
                            $clm_string .= $clm[$i];
                        }
                        if(strpos($clm_string, "_"))
                        {
                            $value = explode("_", $clm_string);
                            if($value[0] == 'enum')
                            {
                                $tmp_column['type'] = $value[0].'(';
                                unset($value[0]);
                                foreach($value as $opts)
                                    $tmp_column['type'] .= "'".$opts."',";
                                $tmp_column['type'] = substr($tmp_column['type'], 0, -1).')';
                            } else {
                            $tmp_column['type'] = $value[0]."(".$value[1].")";
                            }
                        } else {
                            $tmp_column['type'] = $clm_string;
                        }
                    }
                }

                $this->table['columns'][] = $tmp_column;
            }
            $this->CreateTable();
        } else {
            die("Must pass arguments!\n");
        }
    }

    private function CreateTable()
    {
        $create = "CREATE TABLE `".$this->table['name']."`\n (`id` INT(11) NOT NULL AUTO_INCREMENT, \n";
        $columns = "";
        foreach($this->table["columns"] as $clm)
        {
            $columns .= sprintf("// %-20s %s\n", $clm['name'], $clm['type']);
            $create .= "`".$clm['name']."` ".$clm['type']." ";
            if(!$clm["null"])
                $create .= "NOT NULL";
            $create .= ", \n";
        }
        $create .= "`created_at` DATETIME NOT NULL,\n `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n PRIMARY KEY (`id`)\n)";
        $create .= "ENGINE=INNODB DEFAULT CHARSET=latin1";
        $db = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
        $db->query($create);

        $org_name = $this->table['name'];
        // Building Model
        if(strpos($this->table['name'], '_') !== false)
        {
            $MODEL_NAME = ucwords(str_replace('_', ' ', $this->table['name']));
            $this->table['name'] = str_replace(' ', '', $MODEL_NAME);
        } else {
            $this->table['name'] = ucfirst($this->table['name']);
        }
        $class = "<?php
// ####
// [".$org_name."]
".$columns."// ##

class ".$this->table['name']." extends Model
{

}
?>
";
        $f = fopen(DIR_APP_MODELS."/".$this->table['name'].".model.php", "w");
        fwrite($f, $class);
        fclose($f);
    }
}
?>