<?php
/**
 * Log Core Class
 *
 * This system handles the writing of logs from CORE level and APP level
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
 * @link        http://www.codethesky.com/docs/logclass
 * @package     Sky.Core
 */

/**
 * Log class
 * This class handles APP and CORE level logging
 * @package Sky.Core.Log
 */
class Log
{
    public static $app_format = "%s > %s\n";
    public static $core_format = "\033[1;33m%s \033[0m[\033[1;36m%s\033[0m][\033[1;31m%s::%s\033[0m] \033[1;32m>\033[0m ";
    public static $app_count = 0;
    public static $core_count = 0;
    public static $companies = array(
        'att' => 'txt.att.net',
        'verizon' => 'vtext.com',
        'sprint' => 'messaging.sprintpcs.com',
        'virgin' => 'vmobl.com',
        'tmobile' => 'tomomail.net'
    );

    public static function predebug()
    {
        if($GLOBALS['ENV'] !== 'PROD')
        {
            $echo = "<pre>";
            $vars = func_get_args();
            foreach($vars as $v)
                $echo .= var_export($v, true)."\n";
            echo $echo."</pre>";
        }
    }

    /**
     * Static app level write method
     * @access public
     * Requires at least 1 argument. If 2 or more, will run sprintf
     */
    public static function write()
    {
        if(LOGGING_ENABLED)
        {
            $f = fopen(APP_LOG, 'a');
            self::$app_count++;
            if(self::$app_count == 1)
                fwrite($f, ">========APP=LOG===========> ".date('m-d-Y H:i:s')."\n");
            if(func_num_args() == 1)
            {
                fwrite($f, sprintf(self::$app_format, date('H:i:s'), func_get_arg(0)));
            }
            elseif(func_num_args() > 1)
            {
                $args = func_get_args();
                $msg = $args[0];
                unset($args[0]);
                $args = array_values($args);
                $args = array_reverse($args);
                $args[] = date('H:i:s');
                $args[] = "%s > ".$msg."\n";
                $args = array_reverse($args);
                $r = call_user_func_array('sprintf', $args);
                fwrite($f, $r);
            }
            fclose($f);
            chmod(APP_LOG, 0777);
        }
    }

    public static function stringify($level)
    {
        if($level == 1)
            return 'MASCORE';
        elseif($level == 2)
            return 'MIDCORE';
        elseif($level == 3)
            return 'INFCORE';
        elseif($level == 4)
            return 'ERROR';
        return '???';
    }

    public static function textmessage($msg, $level, $group = "default")
    {
        if(TXT_MSG_ENABLED)
        {
            global $txt_groups;
            $group = $txt_groups[$group];
            foreach($group as $number => $company)
            {
                mail($number.'@'.self::$companies[$company], self::stringify($level), $msg, "From: sky@txtmsg.com\r\n");
            }
        }
    }

    /**
     * Static core level write method
     * @access public
     * @param string $msg - Regular string output OR printf format string
     * @param int $level - One of the three levels of output
     * @param string $class - Name of class
     * @param string $method - Name of method
     * @param array $args - Args for printf format string is using one
     */
    public static function corewrite($msg, $level, $class, $method, $args = array())
    {
        if(LOGGING_ENABLED && $GLOBALS['ENV'] != 'TEST')
        {
            if($level >= LOG_LEVEL)
            {
                $f = fopen(CORE_LOG, 'a');
                self::$core_count++;
                if(self::$core_count == 1)
                    fwrite($f, ">========CORE=LOG===========> ".date('m-d-Y H:i:s')."\n");
                $args = array_reverse($args);
                $args[] = $method;
                $args[] = $class;
                $args[] = self::stringify($level);
                $args[] = date('H:i:s');
                $args[] = self::$core_format.$msg."\n";
                $args = array_values(array_reverse($args));
                $r = call_user_func_array('sprintf', $args);
                fwrite($f, $r);
                fclose($f);
            }
        }
    }
}
?>