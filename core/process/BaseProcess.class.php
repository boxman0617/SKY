<?php
abstract class BaseProcess
{
	private $_data;
	protected $_ID;

	public function __construct()
	{
		$this->_data = new ProcessData($this);
	}

	protected function Sync()
	{
		$this->_data->Sync();
	}

	public function GetID()
	{
		return $this->_ID;
	}

	final public function __set($name, $value)
	{
		$this->_data->$name = $value;
	}

	final public function __get($name)
	{
		return $this->_data->$name;
	}
}

class ProcessData
{
	private $_cached_data = null;
	private $_data = array();
	private $_dirty = false;
	private $_base = null;

	public function __construct(BaseProcess $base)
	{
		$this->_base = $base;
	}

	public function __set($name, $value)
	{
		$this->Pull();
		if($this->_cached_data[$name] != $value)
			$this->_dirty = true;

		$this->_data[$name] = $value;
		if($name == 'status_code')
			$this->TriggerStatusCode($value);
		$this->Sync();
	}

	private function TriggerStatusCode($code)
	{
		$status = null;
		switch($code)
		{
			case ProcessManager::PS_CREATED:
				$status = ProcessManager::$Status['CREATED'];
				break;
			case ProcessManager::PS_WAITING:
				$status = ProcessManager::$Status['WAIT'];
				break;
			case ProcessManager::PS_RUNNING:
				$status = ProcessManager::$Status['RUNNING'];
				break;
			case ProcessManager::PS_KILLED:
				$status = ProcessManager::$Status['KILLED'];
				break;
			case ProcessManager::PS_DONE:
				$status = ProcessManager::$Status['DONE'];
				$this->_data['progress'] = 100;
				break;
		}
		if(!is_null($status))
			$this->_data['status'] = $status;
	}

	public function __get($name)
	{
		$this->Pull();
		return $this->_data[$name];
	}

	private function Pull()
	{
		$query = 'SELECT * FROM `'.ProcessManager::$ProcessListTableName.'` ';
		$query .= 'WHERE `id` = '.$this->_base->GetID();

		$r = ProcessManager::RunQuery($query);
		if($r !== false)
		{
			$row = $r->fetch_assoc();
			$this->SetData($row);
			return true;
		}
		return false;
	}

	private function Push()
	{
		$query = 'UPDATE `'.ProcessManager::$ProcessListTableName.'` SET ';
		foreach($this->_data as $column => $value)
		{
			if(in_array($column, array('id', 'created_at', 'updated_at')))
				continue;
			if($this->_cached_data[$column] == $this->_data[$column])
				continue;
			$query .= '`'.$column.'` = ';
			if(is_string($value))
				$query .= '"'.$value.'", ';
			else
				$query .= $value.', ';
		}
		$query = substr($query, 0, -2);
		$query .= ' WHERE `id` = '.$this->_base->GetID();

		$r = ProcessManager::RunQuery($query);
		if($r === false)
		{
			throw new ProcessDisconnectException();
		}
	}

	public function Sync()
	{
		if($this->_dirty)
			$this->Push();
		else
			$this->Pull();
	}

	private function SetData($data = array())
	{
		$this->_data = $data;
		if(is_null($this->_cached_data))
			$this->_cached_data = $this->_data;

		if($this->_data != $this->_cached_data)
			$this->_dirty = true;
	}

	public function IsDirty()
	{
		return $this->_dirty;
	}
}
?>