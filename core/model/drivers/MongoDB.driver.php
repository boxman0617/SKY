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
	private static $table_schema;
	private static $coll;
	private $collection_name;
	
	private $select = NULL;
	private $where = NULL;
	
	public function __construct()
	{
		if(!self::$db)
		{
			$m = new Mongo('mongodb://'.DB_USERNAME.':'.DB_PASSWORD.'@'.DB_SERVER.'/');
			self::$db = $m->selectDB(DB_DATABASE) ;
		}
	}
	
	public function setTableName($name)
	{
		self::$coll[$name] = self::$db->$name;
		$this->collection_name = $name;
	}
	
	public function setSchema()
	{
		$r = self::$coll[$this->collection_name]->findOne();
		foreach($r as $key => $value)
		{
			$k = "NULL";
			if($key == "_id")
			{
				$k = "PRI";
			}
			self::$table_schema[$this->collection_name][$key] = array(
				"Type" => gettype($value),
				"Null" => true,
				"Key" => $k,
				"Default" => "",
				"Extra" => ""
			);
		}
	}
	
	public function getSchema()
	{
		if(!isset(self::$table_schema[$this->collection_name]))
			$this->setSchema();
		return self::$table_schema[$this->collection_name];
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
			$c = self::$db->listCollections();	
			foreach($c as $v)
			{
				if(preg_match('/\.('.$table_name.')$/', $v) === 1)
				{
					$this->collection_name = $table_name;
					return true;
				}
			}
		}
	}
	
	private function autoIncrement()
	{
		$r = $db = self::$db->command(array(
			'findandmodify' => 'counters',
			'query' => array(
				'_id' => $this->collection_name.'_id'
			),
			'update' => array(
				'$inc' => array(
					'c' => 1
				)
			)
		));
		return $r['value']['c'];
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
	
	public function buildQuery($material)
	{
		var_dump($material);
		$query = array();
		if(!empty($material['select']))
		{
			$this->select = $material['select'];
		}
		if(!empty($material['where']))
		{
			
		}
	}
}
?>
