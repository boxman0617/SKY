<?php
/**
 * TestMaster Core Class
 *
 * This class allows unit testing of other classes and/or
 * apps.
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
 * @link        http://www.codethesky.com/docs/testmasterclass
 * @package     Sky.Core
 */

SkyL::Import(SkyDefines::Call('FIXTURE_CLASS'));

class TestMaster
{
    public static $score = array(
        'pass' => 0,
        'fail' => 0
    );
    
    public function RunTestClass($class)
    {
        $method = null;
        if(strpos($class, ':'))
        {
            $tmp = explode(':', $class);
            $class = $tmp[0];
            $method = $tmp[1];
        }
        $files = scandir(SkyDefines::Call('DIR_TEST'));
        $found = false;
        foreach($files as $file)
        {
            $file_name = explode('.', $file);
            if(strtolower($file_name[0]) == strtolower($class))
            {
                $found = true;
                SkyL::Import(SkyDefines::Call('DIR_TEST').'/'.$file);
                break;
            }
        }
        if(!$found)
        {
            $files = scandir(SkyDefines::Call('SKYCORE_TEST'));
            foreach($files as $file)
            {
                $file_name = explode('.', $file);
                if(strtolower($file_name[0]) == strtolower($class))
                {
                    $found = true;
                    SkyL::Import(SkyDefines::Call('SKYCORE_TEST').'/'.$file);
                    break;
                }
            }
        }
        if(!$found) die('No test found!');
        $obj = new $class();
        if(is_null($method))
        {
            $methods = get_class_methods($obj);
            foreach($methods as $method)
            {
                if($method == '__construct') continue;
                if($method == '__destruct') continue;
                echo $this->to_s($method).": \n";
                $_start = microtime(true);
                $obj->$method();
                $_end = microtime(true);
                echo "Elapsed time: ".round($_end - $_start, 5)."S\n\n";
            }
        } else {
            echo $this->to_s($method).": \n";
            $_start = microtime(true);
            $obj->$method();
            $_end = microtime(true);
            echo "Elapsed time: ".round($_end - $_start, 5)."S\n\n";
        }
        self::_OutputTotal();
    }
    
    private function to_s($method)
    {
        $pieces = preg_split('/(?=[A-Z])/',$method);
        $string = "";
        for($i=0;$i<count($pieces);$i++)
        {
            if($pieces[$i] == "") continue;
            $string .= $pieces[$i].' ';
        }
        return substr($string, 0, -1);
    }
    
    public static function _IncreaseCount($bool, $type, $msg)
    {
        if($bool)
        {
            echo "\tAssertion type [".$type."]: Pass\n";
            self::$score['pass']++;
        }
        else
        {
            echo "\tAssertion type [".$type."]: Fail\n";
            self::$score['fail']++;
            self::_Message($msg);
        }
    }
    
    public static function _Message($msg)
    {
        if(!is_null($msg))
            echo "\t\t=> ".$msg."\n";
    }
    
    public static function _OutputTotal()
    {
        $result = ((self::$score['fail'] > 0) ? 'FAIL' : 'PASS');
        if(php_sapi_name() == 'cli')
    {
            echo "==============================================\n";
            echo "[".$result."] Pass: ".self::$score['pass']." Fail: ".self::$score['fail']." Total: ".array_sum(self::$score)."\n";
        } else {
            $h = new HTML();
            echo $h->div(
                $h->div(
                    $h->div('['.$result.']', array('style' => 'float: left;padding-right:5px;font-weight:bold;')).
                    $h->div('Pass: '.self::$score['pass'].' Fail: '.self::$score['fail'].' Total: '.array_sum(self::$score), array('style' => 'padding-left:5px;')), 
                    array('style' => 'padding: 5px;border-bottom:1px solid #000;background:#b0e5f2;')).
                $h->div('', array('style' => 'padding:5px;')), 
                array('style' => 'width:95%; border:1px solid #000;margin:5px auto;')
            );
        }
    }
        
    public static function Assert($var, $msg = null)
    {
        self::_IncreaseCount($var === true, 'True', $msg);
    }
    
    public static function AssertEqual($var1, $var2, $msg = null)
    {
        self::_IncreaseCount($var1 == $var2, 'Equal', $msg);
    }

    public static function AssertNotEqual($var1, $var2, $msg = null)
        {
        self::_IncreaseCount($var1 != $var2, 'NotEqual', $msg);
        }

    public static function AssertSame($var1, $var2, $msg = null)
    {
        self::_IncreaseCount($var1 === $var2, 'Same', $msg);
    }
    
    public static function AssertNotSame($var1, $var2, $msg = null)
    {
        self::_IncreaseCount($var1 !== $var2, 'NotSame', $msg);
    }

    public static function AssertNull($var, $msg = null)
        {
        self::_IncreaseCount(is_null($var), 'Null', $msg);
        }

    public static function AssertNotNull($var, $msg = null)
    {
        self::_IncreaseCount(!is_null($var), 'NotNull', $msg);
    }

    public static function AssertIsSet($var, $msg = null)
    {
        self::_IncreaseCount(isset($var), 'IsSet', $msg);
    }

    public static function AssertIsNotSet($var, $msg = null)
    {
        self::_IncreaseCount(!isset($var), 'IsNotSet', $msg);
    }
}
?>