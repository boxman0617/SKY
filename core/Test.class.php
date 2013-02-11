<?php
require_once(dirname(__FILE__).'/../configs/defines.php');
import(PREIMPORTS);
class TestMaster
{
    //public static $ENV_HOLD = ENV;
    public static $score = array(
        'pass' => 0,
        'fail' => 0
    );
    
    public function RunTestClass($class)
    {
        $obj = new $class();
        $methods = get_class_methods($obj);
        foreach($methods as $method)
        {
            echo "Running ".$method.": \n";
            $obj->$method();
        }
        self::_OutputTotal();
    }
    
    public static function _IncreaseCount($bool, $type)
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
        }
    }
    
    public static function _Message($msg)
    {
        if(!is_null($msg))
            echo $msg;
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
        self::_IncreaseCount($var === true, 'True');
        self::_Message($msg);
    }
    
    public static function AssertEqual($var1, $var2, $msg = null)
    {
        self::_IncreaseCount($var1 == $var2, 'Equal');
        self::_Message($msg);
    }

    public static function AssertNotEqual($var1, $var2, $msg = null)
        {
        self::_IncreaseCount($var1 != $var2, 'NotEqual');
        self::_Message($msg);
        }

    public static function AssertSame($var1, $var2, $msg = null)
    {
        self::_IncreaseCount($var1 === $var2, 'Same');
        self::_Message($msg);
    }
    
    public static function AssertNotSame($var1, $var2, $msg = null)
    {
        self::_IncreaseCount($var1 !== $var2, 'NotSame');
        self::_Message($msg);
    }

    public static function AssertNull($var, $msg = null)
        {
        self::_IncreaseCount(is_null($var), 'Null');
        self::_Message($msg);
        }

    public static function AssertNotNull($var, $msg = null)
    {
        self::_IncreaseCount(!is_null($var), 'NotNull');
        self::_Message($msg);
    }
}

class MyTest
{
    public function Test1()
    {
        TestMaster::Assert(true);
    }
    
    public function Test2()
    {
        TestMaster::AssertEqual(5, '5');
    }
    
    public function Test3()
    {
        TestMaster::AssertNotEqual(5, 5);
        TestMaster::AssertSame(5, '5');
    }
}

$tm = new TestMaster();
$tm->RunTestClass('MyTest');
?>