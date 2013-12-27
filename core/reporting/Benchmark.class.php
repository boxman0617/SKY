<?php
class Benchmark extends Base
{
    private static $RunTime = 0.0;
    private static $StartTime = 0.0;
    private static $EndTime = 0.0;
    private static $Markers = array();
    
    public static function Start()
    {
        self::$StartTime = microtime(true);
    }
    
    public static function Mark($label)
    {
        self::$Markers[$label] = microtime(true);
    }
    
    public static function End()
    {
        self::$EndTime = microtime(true);
        self::$RunTime = self::$EndTime - self::$StartTime;
    }
    
    public static function ElapsedTime($start = null, $end = null)
    {
        $START = self::$StartTime;
        $END = self::$EndTime;
        if(!is_null($start))
            $START = self::$Markers[$start];
        if(!is_null($end))
            $END = self::$Markers[$end];
        return round($END - $START, 4);
    }
}
?>