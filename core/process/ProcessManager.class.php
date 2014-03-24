<?php
class ProcessManager
{
	public static $StartupWaitCycles = 60;
	public static $ProcessListTableName = 'processes';
	
	private static $_DB = null;

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

	public static function Fork($script)
	{
		$s = self::DoesScriptExists($script);
		if($s === false)
			throw new NoScriptFoundException($script);

		$desc = array(
			array('pipe', 'r'),
			array('pipe', 'w'),
			array('pipe', 'w')
		);

		$process = proc_open(
			'exec '.$s.' > /dev/null & echo $!',
			$desc, $pipes
		);

		if(is_resource($process))
		{
			usleep(100000); // wait .05 seconds for startup errors.
			fclose($pipes[0]);
			stream_set_blocking($pipes[1], 0);
			$PID = trim(fread($pipes[1], 64));
			fclose($pipes[1]);

			stream_set_timeout($pipes[2], 0, 500);
			stream_set_blocking($pipes[2], 0);
			$STDERR = fread($pipes[2], 4096);
			fclose($pipes[2]);

			if($STDERR != '' && !in_array($PID, self::GetPIDs()))
			{
				return Process::Init(array(
					'script' => $script,
					'error' => $STDERR
				));
			}
			elseif($STDERR == '' && $PLID = self::IsChildActive($PID))
			{
				$process = Process::Get($PLID);
				$process->script = $script;
				return $process;
			}
			elseif($PLID = self::IsInProcessList($PID))
			{
				$process = Process::Get($PLID);
				$process->script = $script;
				return $process;
			}
			else
			{
				return Process::Init(array(
					'script' => $script,
					'error' => 'Unknown startup error occured!'
				));
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

	public static function IsInProcessList($PID)
	{
		$sql = 'SELECT `id` FROM `'.self::$ProcessListTableName.' WHERE ';
		$sql .= '`PID` = "'.$PID.'"';
		if($r = self::RunQuery($sql))
		{
			$row = $r->fetch_assoc();
			return $row['id'];
		}
		return false;
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
?>