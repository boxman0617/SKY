<?php
/**
 * Error Core Class
 *
 * The error class can take over the PHP error handler, and will log all errors,
 * both PHP level, or user level exceptions.
 *
 * LICENSE:
 * The MIT License (MIT)
 *
 * Copyright (c) 2014 DeeplogiK
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author      Alan Tirado <alan@deeplogik.com>
 * @copyright   2014 DeepLogik, All Rights Reserved
 * @license     MIT
 * @package     Core\Reporting\Error
 * @version     1.0.0
 */

if(!defined('E_USER_DEPRECATED'))
    define('E_USER_DEPRECATED', 16384);
if(!defined('E_DEPRECATED'))
    define('E_DEPRECATED', 8192);

/**
 * ErrorHandler class
 *
 * Handles errors created by code
 */
class Error extends Base
{
    /**
     * Singleton object placehold
     *
     * Holds the single instance of this class
     * @var mixed
     */
    public static $instance = null;

	/**
	 * Holds all errors that occur
	 *
	 * When an error occurs, it will eventually
	 * be appended to this array
	 * @var array[]
	 */
    protected static $_errors = array();

	/**
	 * Predefined error colors
	 *
	 * A predefined array of colors for when
	 * an error message is displayed in HTML
	 * @var string[]
	 */
    protected static $_colors = array(
        'notice'     => 'dbf0fc',
        'depricated' => 'eaa5ef',
        'warning'    => 'ffe900',
        'error'      => 'f48989',
        'E_ERROR'      => 'f48989',
        'exception'  => '949ce8',
        'parse'      => 'ce8ef2',
        'E_PARSE'      => 'ce8ef2'
    );

	/**
	 * Predefined error type map
	 *
	 * A predefined array of strings that
	 * map the error type with a string
	 * representation of it
	 * @var string[]
	 */
	private static $_error_type_map = array(
		E_NOTICE 			=> 'E_NOTICE',
        E_PARSE 			=> 'E_PARSE',
		E_USER_NOTICE 		=> 'E_USER_NOTICE',
		E_DEPRECATED 		=> 'E_DEPRECATED',
		E_USER_DEPRECATED 	=> 'E_USER_DEPRECATED',
		E_STRICT 			=> 'E_STRICT',
		E_WARNING 			=> 'E_WARNING',
		E_USER_WARNING 		=> 'E_USER_WARNING',
		E_USER_WARNING 		=> 'E_USER_WARNING',
		E_ERROR 			=> 'E_ERROR',
		E_USER_ERROR 		=> 'E_USER_ERROR',
		0 					=> 'EXCEPTION'
	);

	/**
	 * An array of error types to supress
	 *
	 * Using ::Supress() will append an error type
	 * into this property
	 * @var string[]
	 */
    private static $_supress = array();

	/**
	 * GetInstance
	 *
	 * Singleton entry point that will always return
	 * an instance of self
	 *
	 * @return Error
	 */
    public static function GetInstance()
    {
        if(is_null(self::$instance))
            self::$instance = new Error();
        return self::$instance;
    }

	/**
	 * __construct
	 *
	 * Sets up error handlingto use this class by
	 * calling set_error_handler, set_exception_handler, and
	 * register_shutdown_function
	 */
    public function __construct()
    {
        error_reporting(-1);
        set_error_handler(array('Error', 'HandleNormalErrors'), E_ALL | E_STRICT);
        set_exception_handler(array('Error', 'HandleExceptionErrors'));
        register_shutdown_function(array('Error', 'HandleShutdown'));
    }

	/**
	 * Supress
	 *
	 * Use this method to supress a type of error
	 * from being logged or displayed
	 *
	 * @param int $type Use constants to set this
	 */
    public static function Supress($type)
    {
        self::$_supress[] = $type;
    }

	/**
	 * HandleNormalErrors
	 *
	 * The method is called by set_error_handler and
	 * deligates to the correct internal method that handles that type
	 * of error
	 *
	 * @param int $no Error type
	 * @param string $str Error message
	 * @param string $file File path of where error occured
	 * @param int $line Line in file where error occured
	 */
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
        die();
    }

	/**
	 * Stringify
	 *
	 * This method maps the error type
	 * to a string
	 *
	 * @param int $no Error type
	 * @param string Returns the value of the error type map
	 */
    public static function Stringify($no)
    {
        if(array_key_exists($no, self::$_error_type_map))
        	return self::$_error_type_map[$no];
        return 'E_UNDEFINED';
    }

	/**
	 * LogError
	 *
	 * Logs the error in the core.log file with a 4 (error) level
	 *
	 * @param int $no Error type
	 * @param string $str Error message
	 * @param string $file File path of where error occured
	 * @param int $line Line in file where error occured
	 */
    public static function LogError($no, $str, $file, $line)
    {
        Log::corewrite('%s', 4, self::Stringify($no), $file.':'.$line, array(
            $str
        ));
    }

	/**
	 * BuildMessage
	 *
	 * This method builds a message that will be displayed
	 * in the enviroment it is in. In example, if this error
	 * occurs in the command line it will de displayed in a
	 * simple one line message where in the browser it will display
	 * in HTML
	 *
	 * @param int $no Error type
	 * @param string $str Error message
	 * @param string $file File path of where error occured
	 * @param int $line Line in file where error occured
	 * @param string $color HEX color for the error message
	 */
    public static function BuildMessage($no, $str, $file, $line, $color)
    {
        if(php_sapi_name() == 'cli')
        {
            $_no = self::Stringify($no);
            if(!is_int($no))
                $_no = $no;
            echo '['.$_no.'] '.$file.':'.$line.' => '.$str."\n";
        } else {
            $header = array(
              'type' => $no,
              'message' => $str
            );
            $trace = self::FilterTraceback();
            self::ShowPrettyErrorPage($header, $trace);
        }
    }

    private static function FilterTraceback()
    {
      $trace = debug_backtrace();
      $trace = array_filter($trace, function($v) {
        return array_key_exists('file', $v);
      });

      $trace = array_filter($trace, function($v) {
        return (strpos($v['file'], 'Error.class.php') === false);
      });

      $trace = array_filter($trace, function($v) {
        return (strpos($v['file'], SkyDefines::Call('SKYCORE')) === false);
      });

      $trace = array_filter($trace, function($v) {
        return (strpos($v['file'], 'configs/init.php') === false);
      });
      return $trace;
    }

	/**
	 * _HandleNotice
	 *
	 * If the current enviroment is NOT 'PRO',
	 * display the message using ::BuildMessage().
	 * This method also appends to the ::$_errors property
	 *
	 * @param int $no Error type
	 * @param string $str Error message
	 * @param string $file File path of where error occured
	 * @param int $line Line in file where error occured
	 */
    public static function _HandleNotice($no, $str, $file, $line)
    {
        if(SkyDefines::GetEnv() !== 'PRO') // Display Error
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
                $trace = str_replace(preg_replace('/(\/[a-z]+\/configs\/\.\.)$/', '', SkyDefines::Call('APPROOT')), '', $trace);
                $message .= '<b>Stack trace:</b><br><pre>'.$trace.'</pre><br><br>';
                $controller_params = array_merge($_POST, $_GET);
                $message .= '<b>Controller Params</b></br><pre>'.var_export($controller_params, true).'</pre><br><br>';
                self::$_errors[] = array('no' => $no, 'str' => $message, 'file' => $file, 'line' => $line, 'color' => self::$_colors['notice']);
                self::BuildMessage($no, $message, $file, $line, 'dbf0fc');
            }
        }
    }

	/**
	 * _HandleDeprecated
	 *
	 * If the current enviroment is NOT 'PRO',
	 * display the message using ::BuildMessage()
	 *
	 * @param int $no Error type
	 * @param string $str Error message
	 * @param string $file File path of where error occured
	 * @param int $line Line in file where error occured
	 */
    public static function _HandleDeprecated($no, $str, $file, $line)
    {
        if(SkyDefines::GetEnv() !== 'PRO') // Display Error
            self::BuildMessage($no, $str, $file, $line, 'eaa5ef');
    }

	/**
	 * _HandleWarning
	 *
	 * If the current enviroment is NOT 'PRO',
	 * display the message using ::BuildMessage()
	 *
	 * @param int $no Error type
	 * @param string $str Error message
	 * @param string $file File path of where error occured
	 * @param int $line Line in file where error occured
	 */
    public static function _HandleWarning($no, $str, $file, $line)
    {
        if(SkyDefines::GetEnv() !== 'PRO') // Display Error
            self::BuildMessage($no, $str, $file, $line, 'ffe900');
    }

	/**
	 * _HandleError
	 *
	 * If the current enviroment is NOT 'PRO',
	 * display the message using ::BuildMessage() while
	 * also building a stack trace to be included in the
	 * message
	 *
	 * @param int $no Error type
	 * @param string $str Error message
	 * @param string $file File path of where error occured
	 * @param int $line Line in file where error occured
	 */
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

        if(SkyDefines::GetEnv() !== 'PRO')
            self::BuildMessage($no, $message, $file, $line, 'f48989');
        exit();
    }

	/**
	 * IsThereErrors
	 *
	 * Checks to see if there are any errors in the
	 * stack. Uses ::ErrorCount() to get the number
	 * of errors
	 *
	 * @return boolean True if there are errors and false if not
	 */
    public static function IsThereErrors()
    {
        return (self::ErrorCount() > 0);
    }

	/**
	 * ErrorCount
	 *
	 * Counts the ::$_errors property
	 *
	 * @return int Number of errors
	 */
    public static function ErrorCount()
    {
        return count(self::$_errors);
    }

	/**
	 * Flush
	 *
	 * If there are any errors, iterate over them
	 * and send them to ::BuildMessage()
	 *
	 * @return boolean If there is any errors, return true else false
	 */
    public static function Flush()
    {
        if(self::IsThereErrors())
        {
            foreach(self::$_errors as $error)
                self::BuildMessage($error['no'], $error['str'], $error['file'], $error['line'], $error['color']);
            return true;
        }
        return false;
    }

	/**
	 * HandleExceptionErrors
	 *
	 * This method handles exceptions by extracting specific
	 * properties from the Exception to then send the message
	 * to ::BuildMessage of the enviroment is not 'PRO'. The
	 * Exception will always be logged
	 *
	 * @param Exception Exception object
	 */
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
        if(SkyDefines::GetEnv() !== 'PRO')
        {
            $message =  '<b>Date:</b> '.date('m-d-Y h:i:s A').'<br><br>';
            $message .= '<b>Message:</b> '.$e->getMessage().'<br><br>';
            $message .= '<b>Code:</b> '.$e->getCode().'<br><br>';
            $message .= '<b>Stack trace:</b><br><pre>'.$e->getTraceAsString().'</pre><br><br>';
            self::BuildMessage($NO, $message, $_file, $_line, '949ce8');
        }
    }

	/**
	 * HandleShutdown
	 *
	 * When a fatal error occurs, this is the last method
	 * that is called and it will log the error and display
	 * it through ::BuildMessage() if the enviroment is NOT
	 * 'PRO'
	 */
    public static function HandleShutdown()
    {
        $error = error_get_last();
        if($error)
        {
            self::LogError($error['type'], $error['message'], $error['file'], $error['line']);
            if(SkyDefines::GetEnv() !== 'PRO')
            {
                $header = array(
                    'type' => $error['type'],
                    'message' => $error['message']
                );
                $trace = array(
                    array(
                        'file' => $error['file'],
                        'line' => $error['line'],
                        'function' => '&lt;FATAL&gt;'
                    )
                );
                self::ShowPrettyErrorPage($header, $trace);
            }
        }
    }

    public static function ShowPrettyErrorPage($header, $trace)
    {
        $header['type'] = self::Stringify($header['type']);
        $trace = array_values($trace);

        $files = array();
        foreach($trace as $t)
        {
          $buffer = file($t['file']);
          $l = (((int)$t['line']) - 11);
          if($l < 0)
            $l = 0;
          $buffer = array_slice($buffer, $l, 22);
          $files[] = $buffer;
        }

        require_once(dirname(__FILE__).'/error_handler/error.view.php');
    }
}
