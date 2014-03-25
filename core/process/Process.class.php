<?php
class Process
{
	private $_ID = null;
	private $_cached_data = array();
	private $_data = array();
	private $_dirty = false;

	public function __construct($id)
	{
		$this->_ID = $id;
		$this->Sync();
	}

	public function __set($key, $value)
	{
		if(array_key_exists($key, $this->_cached_data))
		{
			if($this->_cached_data[$key] != $value)
			{
				$this->_data[$key] = $value;
				$this->_dirty = true;
			}
		}

		$this->Sync();
	}

	public static function Get($id)
	{
		return new Process($id);
	}

	public static function InitError($PID, $script, $error)
	{
		$id = ProcessManager::Insert(array(
			'PID' => $PID,
			'status' => ProcessManager::$Status['ERROR'],
			'status_code' => ProcessManager::PS_ERROR
		));
		return Process::Get($id);
	}

	public function Set($params = array())
	{

	}

	private function Sync()
	{
		if($this->_dirty)
		{
			
		} else {
			$query = 'SELECT * FROM `'.ProcessManager::$ProcessListTableName.' WHERE ';
			$query .= '`id` = '.$this->_ID;
			if($r = ProcessManager::RunQuery($query))
			{
				$row = $r->fetch_assoc();
				$this->_data = $row;
				$this->CacheData();
			}
		}
	}

	private function CacheData()
	{
		$this->_cached_data = $this->_data;
	}
}
?>