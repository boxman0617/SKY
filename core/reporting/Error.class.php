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
 * @author      Alan Tirado <root@deeplogik.com>
 * @copyright   2013 DeepLogik, All Rights Reserved
 * @license     http://www.codethesky.com/license
 * @link        http://www.codethesky.com/docs/errorclass
 * @package     Sky.Core
 */

/**
 * ErrorHandler class
 * Handles errors created by code
 * @package Sky.Core.Error
 */
 
if(!defined('E_USER_DEPRECATED'))
    define('E_USER_DEPRECATED', 16384);
if(!defined('E_DEPRECATED'))
    define('E_DEPRECATED', 8192); 

class Error
{
    public static $instance = null;
    protected static $_errors = array();
    protected static $_colors = array(
        'notice' => 'dbf0fc',
        'depricated' => 'eaa5ef',
        'warning' => 'ffe900',
        'error' => 'f48989',
        'exception' => '949ce8'
    );
    private static $_supress = array();

    public static function GetInstance()
    {
        if(is_null(self::$instance))
            self::$instance = new Error();
        else
            return self::$instance;
    }

    public function __construct()
    {
        if($GLOBALS['ENV'] !== 'PRO')
            ini_set('display_errors', 1);
        else
            ini_set('display_errors', 0);
        error_reporting(-1);
        set_error_handler( array( 'Error', 'HandleNormalErrors' ) );
        set_exception_handler( array( 'Error', 'HandleExceptionErrors' ) );
        register_shutdown_function( array( 'Error', 'HandleShutdown' ) );
    }
    
    public static function Supress($type)
    {
        self::$_supress[] = $type;
    }

    public static function HandleNormalErrors($no, $str, $file, $line)
    {
        self::LogError($no, $str, $file, $line);
        if(!in_array($no, self::$_supress))
        {
            switch($no)
            {
                case E_NOTICE:
                case E_USER_NOTICE:
                    self::_HandleNotice($no, $str, $file, $line);
                    break;
                case E_DEPRECATED:
                case E_USER_DEPRECATED:
                case E_STRICT:
                    self::_HandleDeprecated($no, $str, $file, $line);
                    self::$_errors[] = array('no' => $no, 'str' => $str, 'file' => $file, 'line' => $line, 'color' => self::$_colors['depricated']);
                    break;
                case E_WARNING:
                case E_USER_WARNING:
                    self::_HandleWarning($no, $str, $file, $line);
                    self::$_errors[] = array('no' => $no, 'str' => $str, 'file' => $file, 'line' => $line, 'color' => self::$_colors['warning']);
                    break;
                case E_ERROR:
                case E_USER_ERROR:
                    self::_HandleError($no, $str, $file, $line);
                    self::$_errors[] = array('no' => $no, 'str' => $str, 'file' => $file, 'line' => $line, 'color' => self::$_colors['error']);
                    break;
                default:
                    
            }
        }
    }

    public static function Stringify($no)
    {
        switch($no)
        {
            case E_NOTICE:
                return "E_NOTICE";
            case E_USER_NOTICE:
                return "E_USER_NOTICE";
            case E_DEPRECATED:
                return "E_DEPRECATED";
            case E_USER_DEPRECATED:
                return "E_USER_DEPRECATED";
            case E_STRICT:
                return "E_STRICT";
            case E_WARNING:
                return "E_WARNING";
            case E_USER_WARNING:
                return "E_USER_WARNING";
            case E_USER_WARNING:
                return "E_USER_WARNING";
            case E_ERROR:
                return "E_ERROR";
            case E_USER_ERROR:
                return "E_USER_ERROR";
            case 0:
                return "EXCEPTION";
            default:
                return "E_UNDEFINED";
        }
    }

    public static function LogError($no, $str, $file, $line)
    {
        $_no = self::Stringify($no);
        if(!is_int($no))
            $_no = $no;
        Log::corewrite('%s', 4, $_no, $file.':'.$line, array(
            $str
        ));
    }

    public static function BuildMessage($no, $str, $file, $line, $color)
    {
        $_no = self::Stringify($no);
        if(!is_int($no))
            $_no = $no;
        if(php_sapi_name() == 'cli')
        {
            echo '['.$_no.'] '.$file.':'.$line.' => '.$str."\n";
        } else {
            $h = new HTML();
            echo $h->div(
                $h->div(
                    $h->div('['.$_no.']', array('style' => 'float: left;padding-right:5px;font-weight:bold;')).
                    $h->div($file.':'.$line, array('style' => 'padding-left:5px;')), 
                    array('style' => 'padding: 5px;border-bottom:1px solid #000;background:#'.$color.';')).
                $h->div($str, array('style' => 'padding:5px;background:#FFFFFF;')), 
                array('style' => 'width:95%; border:1px solid #000;margin:5px auto;color:#000000;font-family:"Courier New";font-size:14px;')
            );
        }
    }
    
    public static function _HandleNotice($no, $str, $file, $line)
    {
        if($GLOBALS['ENV'] !== 'PRO') // Display Error
        {
            if(php_sapi_name() == 'cli')
            {
                self::$_errors[] = array('no' => $no, 'str' => $str, 'file' => $file, 'line' => $line, 'color' => self::$_colors['notice']);
                self::BuildMessage($no, $str, $file, $line, 'dbf0fc');
            }
            else
            {
                $message =  '<b>Date:</b> '.date('m-d-Y h:i:s A').'<br><br>';
                $message .= '<b>Message:</b> '.$str.'<br><br>';
                ob_start();
                debug_print_backtrace();
                $trace = ob_get_contents();
                ob_end_clean();
                $trace = str_replace(preg_replace('/(\/[a-z]+\/configs\/\.\.)$/', '', APPROOT), '', $trace);
                $message .= '<b>Stack trace:</b><br><pre>'.$trace.'</pre><br><br>';
                self::$_errors[] = array('no' => $no, 'str' => $message, 'file' => $file, 'line' => $line, 'color' => self::$_colors['notice']);
                self::BuildMessage($no, $message, $file, $line, 'dbf0fc');
            }
        }
    }

    public static function _HandleDeprecated($no, $str, $file, $line)
    {
        if($GLOBALS['ENV'] !== 'PRO') // Display Error
            self::BuildMessage($no, $str, $file, $line, 'eaa5ef');
    }

    public static function _HandleWarning($no, $str, $file, $line)
    {
        if($GLOBALS['ENV'] !== 'PRO') // Display Error
            self::BuildMessage($no, $str, $file, $line, 'ffe900');
    }

    public static function _HandleError($no, $str, $file, $line)
    {
        $backtrace = debug_backtrace();
        $trace = null;
        foreach($backtrace as $step_number => $step)
        {
            if(isset($step['file']) && $step['file'] == $file)
            {
                $trace[] = $step;
                $trace[] = $backtrace[$step_number+1];
                break;
            }
        }
        
        $message = $str."<h3>Traceback:</h3><pre>".var_export($trace, true)."</pre>";

        if($GLOBALS['ENV'] !== 'PRO')
            self::BuildMessage($no, $message, $file, $line, 'f48989');
        exit();
    }
    
    public static function IsThereErrors()
    {
        return (count(self::$_errors) > 0);
    }
    
    public static function ErrorCount()
    {
        return count(self::$_errors);
    }
    
    public static function Flush()
    {
        if(count(self::$_errors) > 0)
        {
            foreach(self::$_errors as $error)
                self::BuildMessage($error['no'], $error['str'], $error['file'], $error['line'], $error['color']);
            return true;
        } else {
            return false;
        }
    }

    public static function HandleExceptionErrors($e)
    {
        $_file = $e->getFile();
        $_line = $e->getLine();
        $trace = $e->getTrace();
        foreach($trace as $t)
        {
            if(isset($t['file']))
            {
                $_file = $t['file'];
                $_line = $t['line'];
                break;
            }
        }
        $NO = strtoupper(get_class($e));
        self::$_errors[] = array('no' => $NO, 'str' => $e->getMessage(), 'file' => $_file, 'line' => $_line, 'color' => self::$_colors['exception']);
        if(ob_get_level() > 1)
            ob_end_clean();
        self::LogError($NO, $e->getMessage(), $_file, $_line);
        if($GLOBALS['ENV'] !== 'PRO')
        {
            $message =  '<b>Date:</b> '.date('m-d-Y h:i:s A').'<br><br>';
            $message .= '<b>Message:</b> '.$e->getMessage().'<br><br>';
            $message .= '<b>Code:</b> '.$e->getCode().'<br><br>';
            $message .= '<b>Stack trace:</b><br><pre>'.$e->getTraceAsString().'</pre><br><br>';
            self::BuildMessage($NO, $message, $_file, $_line, '949ce8');
        }
    }

    public static function HandleShutdown()
    {
        $error = error_get_last();
        if($error)
        {
            if($GLOBALS['ENV'] !== 'PRO')
            {
                //ob_end_clean( );
                self::LogError($error['type'], $error['message'], $error['file'], $error['line']);
                self::BuildMessage($error['type'], $error['message'], $error['file'], $error['line'], 'f48989');
            }
        }
    }
}
?>