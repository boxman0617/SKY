<?php
/**
 * MongoDB Driver
 *
 * This driver interfaces the Model core class
 * to a MongoDB server. It is still incomplete...
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
 * @link        http://www.codethesky.com/docs/mongodbdriver
 * @package     Sky.Core
 */

import(SKYCORE_CORE_MODEL."/Driver.interface.php");
class MongoDBDriver implements iDriver
{
	private static $db;
	private static $collection;
	private static $table_schema;
	private $server;
	
	public function __construct($db_array = NULL)
	{
		$this->server = DB_SERVER;
		if(!is_null($db_array) && isset($db_array['DB_SERVER'])) $this->server = $db_array['DB_SERVER'];
        $db = array(
        	'DB_SERVER' => DB_SERVER,
        	'DB_USERNAME' => DB_USERNAME,
        	'DB_PASSWORD' => DB_PASSWORD,
        	'DB_DATABASE' => DB_DATABASE
        );
        if(!is_null($db_array)) $db = $db_array;
        if(!isset(self::$db[$this->server]))
        {
        	$m = new MongoClient('mongodb://'.$db['DB_USERNAME'].':'.$db['DB_PASSWORD'].'@'.$db['DB_SERVER'].'/');
        	self::$db[$this->server] = $m->$db['DB_DATABASE'];
        }

	}
	
	public function setTableName($name)
	{
		self::$collection = self::$db[$this->server]->$name;
	}
	
	public function setSchema()
	{
		return true;
	}
	
	public function getSchema()
	{
		return array();
	}
	
	public function doesTableExist($class_name)
	{
		return true;
	}
	
	public function runQuery($query)
	{
		
	}
	
	public function save($data)
	{
		
	}
	
	public function escape($value)
	{
		return addslashes(rtrim($value));
	}
	
	public function buildQuery()
	{
		
	}
}
?>
