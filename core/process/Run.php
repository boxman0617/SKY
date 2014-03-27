<?php
require_once(getenv('SKYCORE').'/core/utils/SKY.class.php');
SKY::LoadCore();
SkyL::Import(SkyDefines::Call('PROCESSMANAGER_CLASS'));
SkyL::Import(SkyDefines::Call('RUNNINGPROCESS_CLASS'));
$script = $argv[1];

$s = ProcessManager::DoesScriptExists($script);
ProcessManager::SetScriptName($script);

class ProcessError extends Error
{
	public static function HandleNormalErrors($no, $str, $file, $line)
    {
    	Log::debug('Logging normal error');
        parent::HandleNormalErrors($no, $str, $file, $line);

        Process::InitError(getmypid(), ProcessManager::GetScriptName(), '['.$no.'] '.$str.' - File:Line['.$file.':'.$line.']');
    }

    public static function HandleExceptionErrors($e)
    {
    	Log::debug('Logging exception error');
        parent::HandleExceptionErrors($e);

        Process::InitError(getmypid(), ProcessManager::GetScriptName(), '[EXCEPTION] '.$e->getFile().':'.$e->getLine());
    }

    public static function HandleShutdown()
    {
    	Log::debug('Logging shutdown error');
        parent::HandleShutdown();

        $error = error_get_last();
        if($error)
        	Process::InitError(getmypid(), ProcessManager::GetScriptName(), '[ERROR] '.$error['message']);
    }

    public static function GetInstance()
    {
    	Log::debug('Getting error instance');
        if(is_null(self::$instance))
            self::$instance = new ProcessError();
        else
        {
        	Log::debug('Class [%s]', get_class(self::$instance));
        	if(get_class(self::$instance) == 'Error')
        		self::$instance = new ProcessError();
            return self::$instance;
        }
    }
}

$_errorHandler = ProcessError::GetInstance();

SkyL::Import($s);
?>