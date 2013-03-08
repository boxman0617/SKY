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
 * @version 2.0 Complete rework
 * @package Sky.Core
 */

/**
 * ErrorHandler class
 * Handles errors created by code
 * @package Sky.Core.Error
 */
class Error
{
    public static $instance = null;

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

    public static function HandleNormalErrors($no, $str, $file, $line)
    {
        self::LogError($no, $str, $file, $line);
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
                break;
            case E_WARNING:
            case E_USER_WARNING:
                self::_HandleWarning($no, $str, $file, $line);
                break;
            case E_ERROR:
            case E_USER_ERROR:
                self::_HandleError($no, $str, $file, $line);
                break;
            default:

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
        Log::corewrite('%s', 4, self::Stringify($no), $file.':'.$line, array(
            $str
        ));
    }

    public static function _HandleNotice($no, $str, $file, $line)
    {
        if($GLOBALS['ENV'] !== 'PRO') // Display Error
            self::BuildMessage($no, $str, $file, $line, 'dbf0fc');
    }

    public static function BuildMessage($no, $str, $file, $line, $color)
    {
        if(php_sapi_name() == 'cli')
        {
            echo '['.self::Stringify($no).'] '.$file.':'.$line.' => '.$str."\n";
        } else {
        $h = new HTML();
        echo $h->div(
            $h->div(
                $h->div('['.self::Stringify($no).']', array('style' => 'float: left;padding-right:5px;font-weight:bold;')).
                $h->div($file.':'.$line, array('style' => 'padding-left:5px;')), 
                array('style' => 'padding: 5px;border-bottom:1px solid #000;background:#'.$color.';')).
            $h->div($str, array('style' => 'padding:5px;')), 
            array('style' => 'width:95%; border:1px solid #000;margin:5px auto;')
        );
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

    public static function HandleExceptionErrors($e)
    {
        self::LogError($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
        if($GLOBALS['ENV'] !== 'PRO')
            self::BuildMessage($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine(), '949ce8');
    }

    public static function HandleShutdown()
    {
        $error = error_get_last();
        if($error)
        {
            if($GLOBALS['ENV'] !== 'PRO')
            {
                ob_end_clean( );
                self::LogError($error['type'], $error['message'], $error['file'], $error['line']);
                self::BuildMessage($error['type'], $error['message'], $error['file'], $error['line'].'['.phpversion().']', 'f48989');
            }
        }
    }
}
?>