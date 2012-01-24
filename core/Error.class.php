<?php
/**
 * Error Core Class
 *
 * The error class can take over the PHP error handler, and will log all errors,
 * both PHP level, or user level exceptions.
 *
 * LICENSE:
 *
 * This file may not be redistributed in whole or significant part, or
 * used on a web site without licensing of the enclosed code, and
 * software features.
 * 
 * @author Alan Tirado <root@deeplogik.com>
 * @copyright 2012 DeepLogiK, All Rights Reserved
 * @license http://www.deeplogik.com/sky/legal/license
 * @link http://www.deeplogik.com/sky/index
 * @version 1.0 Initial build
 * @package Sky.Core
 */

if (!defined('E_STRICT'))
{
    define('E_STRICT', 2048);
}
if (!defined('E_RECOVERABLE_ERROR'))
{
    define('E_RECOVERABLE_ERROR' , 4096);
}
if (!defined('E_DEPRECATED'))
{
    define('E_DEPRECATED', 8192);
}
if (!defined('E_USER_DEPRECATED'))
{
    define('E_USER_DEPRECATED', 16384);
}

class ErrorHandler
{
    private static $instance;
    private $report = true;
    private $log_level;
    private $mail_level;
    private $print_level;
    private $crash_level;
    private $level;
    private $core_error;
    private $error_log = ERROR_LOG_DIR;
    private $message_mask = "[%s] {File: %s | Line: %d | Time: %s} %s\n";
    public static $prefix = 'sky';
    public $garbage_collector_days = 5;
    private $errorTypes = array (
        E_PARSE => 'Parsing Error',
        E_ALL => 'All errors occured at once',
        E_WARNING => 'Run-Time Warning',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_WARNING => 'User Warning',
        E_ERROR => 'Fatal Run-Time Error',
        E_CORE_ERROR => 'Core Error',
        E_COMPILE_ERROR => 'Compile Error',
        E_USER_ERROR => 'User Error',
        E_DEPRECATED => 'Deprecated code detected',
        E_USER_DEPRECATED => 'Deprecated code detected',
        E_RECOVERABLE_ERROR => 'Recoverable error',
        E_NOTICE => 'Notice',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Error',
    );
    
    public function __construct()
    {
        $this->log_level = ERROR_LOG_LEVEL;
        $this->mail_level = ERROR_REPORT_LEVEL;
        $this->print_level = ERROR_PRINT_LEVEL;
        $this->crash_level = ERROR_CRASH_LEVEL;
        
        $this->level = error_reporting();
        set_error_handler(array($this, 'HandleError'));
        register_shutdown_function(array($this, 'ShutDownFunction'));
    }
    
    public static function Singleton()
    {
        if (!isset(self::$instance))
        {
            $c = __CLASS__;
            self::$instance = new $c();
        }
        return self::$instance;
    }
    
    public function __destruct()
    {
            restore_error_handler();
    }
    
    public function Toss($error, $level = E_USER_ERROR)
    {
        $d = debug_backtrace();
        foreach ($d as $traceline)
        {
            if (isset($traceline['file']))
            {
                $out = $traceline;
            }
        }
        $this->HandleError($level, $error, $out['file'], $out['line'], $d);
    }
    
    public function HandleError($error_level, $error_message, $error_file = '', $error_line = 0, $error_context = '')
    {
        if($this->report)
        {
		global $_IMPORT;
		if(($error_level & $this->log_level) != 0)
		{
		    $this->LogError($error_level, $error_message, $error_file, $error_line);
		}
		if(($error_level & $this->mail_level) != 0)
		{
		    $this->EmailError($error_level, $error_message, $error_file, $error_line, $error_context);
		}
		if(($error_level & $this->print_level) != 0)
		{
		    $this->PrintError($error_level, $error_message, $error_file, $error_line, $error_context);
		}
		if(($error_level & $this->crash_level) != 0)
		{
		    die(1);
		}
        }
    }
    
    private function LogError($level, $message, $file, $line)
    {
        $f = fopen($this->error_log.self::$prefix."_error_".date('mdY').".log", 'a');
        $formatted_message = sprintf($this->message_mask, $this->ToString($level), $file, $line, date('H:i:s'), $message);
        fwrite($f, $formatted_message);
        fclose($f);
    }
    
    private function EmailError($level, $message, $file, $line, $context)
    {
        
    }
    
    private function PrintError($level, $message, $file, $line, $context)
    {
        if ($file == '' && $line == '')
        {
            $d = $this->ParentTrace();
            if (isset($d[0]['file']))
            {
                $file = $d[0]['file'];
            }
            if (isset($d[0]['line']))
            {
                $line = $d[0]['line'];
            }
        }
        
        printf($this->message_mask, $this->ToString($level), $file, $line, date('H:i:s'), $message);
        
        if (function_exists('xdebug_is_enabled') && xdebug_is_enabled())
        {
                ini_set('xdebug.collect_vars', 'on');
                ini_set('xdebug.collect_params', '4');
                ini_set('xdebug.dump_globals', 'on');
                ini_set('xdebug.dump.SERVER', 'REQUEST_URI');
                xdebug_print_function_stack( $this->ToString($level)." - ".$message."\n" );
        }
    }
    
    private function ParentTrace()
    {
        $trace = debug_backtrace();
        
        // filter out error class stuff. 
        if (count($trace) > 2)
        {
            $trace2 = array();
            foreach ($trace as $item)
            {
                if (isset($item['file']) && stristr($item['file'], 'Error.class.php')===FALSE)
                {
                    // it's not the error class, so add it.
                    $trace2[] = $item;
                }
            }
            $trace = $trace2;
        }
        
        if (!isset($trace[0]['file']))
        {
            $trace[0]['file'] = __FILE__;
        }
        if (!isset($trace[0]['line']))
        {
            $trace[0]['line'] = __LINE__;
        }
        return $trace;
    }
    
    private function ToString($error_level)
    {
        if(isset($this->errorTypes[$error_level]))
        {
            return $this->errorTypes[$error_level];
        }
        return null;
    }
    
    public function ShutDownFunction()
    {
	if(round(rand(0, 10)) < 5)
		return false;
        $files = scandir($this->error_log);
	foreach($files as $file)
	{
		if($file != '.' && $file != '..')
		{
			preg_match('/'.self::$prefix.'_error_(\d+).log/', $file, $date);
			preg_match('/(\d{2})(\d{2})(\d{4})/', $date[1], $mdy);
			$today = date('Y-m-d');
			$end = date('Y-m-d', strtotime($mdy[3].'-'.$mdy[1].'-'.$mdy[2]));
			$d_today = new DateTime($today);
			$d_end = new DateTime($end);
			$diff = $d_today->diff($d_end);
			if($diff->format('%d') >= $this->garbage_collector_days)
				unlink($this->error_log.'/'.$file);
		}
	}
	return true;
    }
}
?>