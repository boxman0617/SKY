<?php
SkyL::Import(SkyDefines::Call('PROCESS_CLASS'));
class ProcessManager
{
	public static $StartupWaitCycles = 60;
	public static $ProcessListTableName = 'processes';

	const PS_INIT 		= 0;
	const PS_CREATED	= 1;
	const PS_RUNNING	= 2;
	const PS_KILLED 	= 3;
	const PS_ERROR 		= 4;
	const PS_DONE 		= 5;
	const PS_WAITING	= 7;

	public static $Status = array(
		'INIT'    => 'Initializing',
		'WAIT'    => 'Waiting...',
		'CREATED' => 'Process has been created',
		'RUNNING' => 'Running...',
		'KILLED'  => 'Process has been killed',
		'KILLING' => 'Attempting to kill process',
		'ERROR'   => 'Encountered an error',
		'DONE'    => 'Process is done running'
	);
	
	private static $_DB = null;
	private static $_tmpScriptHold;

	public static function SetScriptName($name)
	{
		self::$_tmpScriptHold = $name;
	}

	public static function GetScriptName()
	{
		return self::$_tmpScriptHold;
	}

	public static function GetDatabaseInstance()
	{
		if(is_null(self::$_DB))
		{
			$s = AppConfig::GetDatabaseSettings();
			self::$_DB = new mysqli($s[':server'], $s[':username'], $s[':password'], $s[':database']);
		}
		return self::$_DB;
	}

	public static function RunQuery($query)
	{
		$db = self::GetDatabaseInstance();
		return $db->query($query);
	}

	public static function Insert($values = array())
	{
		$query = 'INSERT INTO `'.self::$ProcessListTableName.'` (';
		$columns = array_keys($values);
		$values = array_values($values);
		foreach($columns as $column)
			$query .= '`'.$column.'`, ';
		$query .= '`created_at`) VALUES (';
		foreach($values as $value)
		{
			if(is_string($value))
				$query .= '"'.$value.'", ';
			else
				$query .= $value.', ';
		}
		$query .= 'NOW())';

		$ID = self::RunQuery($query);
		if($ID !== false)
			return self::GetDatabaseInstance()->insert_id;
		return false;
	}

	public static function Fork($script)
	{
		Log::corewrite('Forking script [%s]', 3, __CLASS__, __FUNCTION__, array($script));
		if(self::DoesScriptExists($script) === false)
			throw new NoScriptFoundException($script);

		$desc = array(
			array('pipe', 'r'),
			array('pipe', 'w'),
			array('pipe', 'w')
		);

		$PHP = PHP_BINARY;
		if($PHP == '')
		{
			if(is_file(PHP_BINDIR.'/php'))
				$PHP = PHP_BINDIR.'/php';
			else
				$PHP = SkyDefines::Call('PHP_BIN');
		}

		$exec = 'exec '.$PHP.' '.SkyDefines::Call('SKYCORE_CORE_PROCESS').'/Run.php '.$script.' '.realpath(SkyDefines::Call('APPROOT')).' > /dev/null & echo $!';
		Log::corewrite('Exec string [%s]', 1, __CLASS__, __FUNCTION__, array($exec));
		$process = proc_open($exec, $desc, $pipes);

		if(is_resource($process))
		{
			Log::corewrite('Script is a resource [%s]', 2, __CLASS__, __FUNCTION__, array($script));
			usleep(100000); // wait .05 seconds for startup errors.
			fclose($pipes[0]);
			stream_set_blocking($pipes[1], 0);
			$PID = trim(fread($pipes[1], 64));
			fclose($pipes[1]);

			stream_set_timeout($pipes[2], 0, 500);
			stream_set_blocking($pipes[2], 0);
			$STDERR = fread($pipes[2], 4096);
			fclose($pipes[2]);

			Log::corewrite('Script STRERR [%s]', 2, __CLASS__, __FUNCTION__, array($STDERR));
			if(!empty($STDERR))
				return Process::InitError($PID, $script, $STDERR);
			else {
				$s = 0;
				while($s < self::$StartupWaitCycles)
				{
					if($ID = self::IsInProcessList($PID))
					{
						$process = Process::Get($ID);
						if($process->status_code == self::PS_WAITING)
						{
							$process->status_code = self::PS_CREATED;
							return $process;
						}
					}
					$s++;
					sleep(1);
				}
				return Process::InitError($PID, $script, 'Unknown startup error occured!');
			}
		}

		throw new ForkException();
	}

	public static function IsChildActive($PID)
	{
		for($i = 0; $i < self::$StartupWaitCycles; $i++)
		{
			if(in_array($PID, self::GetPIDs()))
			{
				if($PLID = self::IsInProcessList($PID))
					return $PLID;
			} else
				return false;
			sleep(1);
		}
		return false;
	}

	public static function GC()
	{
		$r = rand(0, 1000);
		if($r >= 500 && $r <= 552)
		{
			$sql = 'DELETE FROM `'.self::$ProcessListTableName.'` WHERE `status_code` IN ('.implode(',', array(
				self::PS_DONE, self::PS_KILLED
			)).')';
			self::RunQuery($sql);
		}
	}

	private static $PLCache = null;

	public static function GetProcessList()
	{
		self::GC();
		return self::_GetProcessList();
	}

	private static function _GetProcessList()
	{
		if(is_null(self::$PLCache))
		{
			$sql = 'SELECT * FROM `'.self::$ProcessListTableName;
			if($r = self::RunQuery($sql))
			{
				$list = array();
				while ($row = $r->fetch_assoc())
			        $list[] = $row;
			    self::$PLCache = $list;
				return $list;
			}
			throw new ProcessDisconnectException();
		} else {
			return self::$PLCache;
		}
	}

	private static function ProcessListQuery($column, $value)
	{
		$sql = 'SELECT `id` FROM `'.self::$ProcessListTableName.'` WHERE ';
		$sql .= '`'.$column.'` = ';
		if(is_string($value))
			$sql .= '"'.$value.'"';
		else
			$sql .= $value;

		if($r = self::RunQuery($sql))
		{
			$row = $r->fetch_assoc();
			return $row['id'];
		}
		throw new ProcessDisconnectException();
	}

	public static function CheckFor($ID)
	{
		try {
			self::ProcessListQuery('id', $ID);
			return true;
		} catch(ProcessDisconnectException $e) {
			return false;
		}
	}

	public static function IsInProcessList($PID)
	{
		try {
			$ID = self::ProcessListQuery('PID', $PID);
			return $ID;
		} catch(ProcessDisconnectException $e) {
			return false;
		}
	}

	public static function GetPIDs()
	{
		exec("ps aux | awk '{ print $2 }'", $out);
		array_shift($out);
		return $out;
	}

	// Check for false. If file exists, it will return the full path
	public static function DoesScriptExists($script)
	{
		$script = SkyDefines::Call('DIR_LIB_SCRIPTS').'/'.$script.'.script.php';
		if(is_file($script))
			return $script;
		return false;
	}
}

// class CreateProcessList extends Migration
// {
// 	public function Up()
// 	{
// 		$t = Table::Create('processes');
// 		$t->AddColumn('PID', 'mediumint', array('null' => false, 'unsigned' => true));
// 		$t->AddColumn('name', 'varchar', array('null' => false, 'length' => 255));
// 		$t->AddColumn('progress', 'tinyint', array('null' => false, 'unsigned' => true, 'default' => 0));
// 		$t->AddColumn('status', 'varchar', array('null' => false, 'length' => 255));
// 		$t->AddColumn('status_code', 'tinyint', array('null' => false, 'unsigned' => true, 'length' => 1));
// 		$t->AddColumn('error', 'text', array('null' => false));
// 		$t->AddColumn('max_time', 'smallint', array('null' => false, 'unsigned' => true, 'default' => 0, 'comment' => 'By seconds'));

// 		$t->AddIndex(array('PID'));
// 		$t->AddIndex(array('status_code'));

// 		$t->Create();
// 	}

// 	public function Down()
// 	{
// 		$this->DropTable('processes');
// 	}
// }
