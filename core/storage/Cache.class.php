<?php
class Cache
{
    private static $_cached_lookups = array();
    private static $_cached_values = array();
    private static $_pointer = 0;
    
    public function __construct()
    {
        
    }
    
    public static function IsCached($lookup)
    {
        return (in_array($lookup, self::$_cached_lookups));
    }
    
    public static function GetCache($lookup)
    {
        $pointer = array_search($lookup, self::$_cached_lookups);
        if(array_key_exists($pointer, self::$_cached_values))
            return self::$_cached_values[$pointer];
        trigger_error('No cache found for lookup ['.$lookup.']', E_USER_WARNING);
    }
    
    public static function Cache($lookup, $value)
    {
        Log::corewrite('Caching lookup [%s]', 1, __CLASS__, __FUNCTION__, array($lookup));
        self::$_pointer++;
        self::$_cached_lookups[self::$_pointer] = $lookup;
        self::$_cached_values[self::$_pointer] = $value;
    }
    
    public static function ForceExpire($lookup)
    {
        Log::corewrite('Expiring cache for lookup [%s]', 1, __CLASS__, __FUNCTION__, array($lookup));
        unset(self::$_cached_lookups[self::$_pointer]);
        unset(self::$_cached_values[self::$_pointer]);
    }
}
?>