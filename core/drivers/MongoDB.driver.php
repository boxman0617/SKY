<?php
import(CORE_DIR."/Driver.interface.php");
class MongoDBDriver implements iDriver
{
	private static $db;
    private static $table_schema;
    private $table_name;
	
	public function __construct()
	{
		if(!self::$db)
        {
            self::$db = new Mongo('mongodb://'.DB_USERNAME.':'.DB_PASSWORD.'@'.DB_SERVER.'/');
			self::$db = self::$db->DB_DATABASE;
        }
	}
    public function setTableName($name)
	{
		$this->table_name = $name;
	}
    public function setSchema()
	{

	}
    public function getSchema()
	{

	}
    public function doesTableExist($class_name)
	{

	}
    public function runQuery($query)
	{

	}
    public function save($data)
	{

	}

    public function escape($value)
	{

	}
    public function buildQuery($material)
	{

	}
}
?>
