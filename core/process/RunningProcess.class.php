<?php
class RunningProcess
{
	private $_ID = null;
	private $_PID = null;
	private $_max_time = 0;
	private $_wait = 60; //seconds

	private $_cached_data = array();
	private $_data = array();
	private $_dirty = false;

	public function __construct($max_time = 0)
	{
		$this->_PID = getmypid();
		$this->_max_time = $max_time;

		$this->Register();
		$this->WaitForConnection();
	}

	private function WaitForConnection()
	{
		$s = 0;
		while($s < $this->_wait)
		{
			
			sleep(1);
		}
	}

	private function GetMyPID()
	{
		return $this->_PID;
	}

	private function Register()
	{
		$this->_ID = ProcessManager::Insert(array(
			'PID' => $this->PID,
			'status' => ProcessManager::$Status['INIT'],
			'status_code' => ProcessManager::PS_INIT,
			'max_time' => $this->_max_time
		));
	}

	public function Finish()
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
}
?>