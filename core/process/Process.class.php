<?php
SkyL::Import(SkyDefines::Call('BASEPROCESS_CLASS'));

class Process extends BaseProcess
{
	private $_cached_data = array();
	private $_data = array();
	private $_dirty = false;

	public function __construct($id)
	{
		parent::__construct();
		$this->_ID = $id;
		$this->Sync();
	}

	public static function Get($id)
	{
		if(ProcessManager::CheckFor($id))
			return new Process($id);
		return false;
	}

	public static function InitError($PID, $script, $error)
	{
		$id = ProcessManager::Insert(array(
			'PID' => $PID,
			'name' => $script,
			'status' => ProcessManager::$Status['ERROR'],
			'status_code' => ProcessManager::PS_ERROR,
			'error' => $error
		));
		return Process::Get($id);
	}
}
?>