<?php
SkyL::Import(SkyDefines::Call('BASEPROCESS_CLASS'));

class RunningProcess extends BaseProcess
{
	private $_PID = null;
	private $_max_time = 0;
	private $_wait = 60; //seconds

	public function __construct($max_time = 0)
	{
		parent::__construct();
		$this->_PID = getmypid();
		$this->_max_time = $max_time;

		$this->Register();
		$this->WaitForConnection();
	}

	private function WaitForConnection()
	{
		$this->status_code = ProcessManager::PS_WAITING;
		$s = 0;
		while($s < $this->_wait)
		{
			if($this->status_code == ProcessManager::PS_CREATED)
			{
				$this->status_code = ProcessManager::PS_RUNNING;
				return true;
			}
			$s++;
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
			'PID' => $this->_PID,
			'name' => ProcessManager::GetScriptName(),
			'status' => ProcessManager::$Status['INIT'],
			'status_code' => ProcessManager::PS_INIT,
			'max_time' => $this->_max_time
		));
		$this->Sync();
	}

	public function Finish()
	{
		$this->status_code = ProcessManager::PS_DONE;
		exit();
	}
}
?>