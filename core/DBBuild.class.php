<?php
/**
 * DBBuild Core Class
 *
 * @ToDo: Build description
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
 * @version 1.0
 * @package Sky.Core
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
                    'null' => true,
                    'type' => 'varchar(255)'
                );
                if(strpos($clm, "not_null"))
                    $tmp_column['null'] = false;
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
                            $tmp_column['type'] = $value[0]."(".$value[1].")";
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
        foreach($this->table["columns"] as $clm)
        {
            $create .= "`".$clm['name']."` ".$clm['type']." ";
            if(!$clm["null"])
                $create .= "NOT NULL";
            $create .= ", \n";
        }
        $create .= "`created_at` DATETIME NOT NULL,\n `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n PRIMARY KEY (`id`)\n)";
        $create .= "ENGINE=INNODB DEFAULT CHARSET=latin1";
        $db = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
        $db->query($create);

        // Building Model
        $class = "<?php
class ".ucfirst($this->table['name'])." extends Model
{

}
?>
";
        $f = fopen(MODEL_DIR."/".ucfirst($this->table['name']).".model.php", "w");
        fwrite($f, $class);
        fclose($f);
    }
}
?>