<?php
class Cache
{
    private static $_cached_lookups = array();
    private static $_cached_values = array();
    private static $_pointer = 0;
    
    public function __construct() { }
    
    //##########################################
    // Page Caching
    //##########################################
    public static function IsPageCached($page_hash)
    {
        $files = scandir(SkyDefines::Call('DIR_LIB_CACHE'));
        foreach($files as $file)
        {
            if($file == '.' || $file == '..' || $file == 'README')
                continue;
            $e = explode('_', $file);
            $name_e = explode('_', $page_hash);
            if($e[0] == $name_e[0])
                return $file;
        }
        return false;
    }
    
    public static function GeneratePageHash($ri)
    {
        return md5($ri['layout'].$ri['method']).'_'.date('dmYHis');
    }
    
    public static function CachePage($page_hash, $contents)
    {
		$f = fopen(SkyDefines::Call('DIR_LIB_CACHE').'/'.$page_hash.'.cache', 'w');
		fwrite($f, $contents);
		fclose($f);
		return true;
    }
        
    public static function GetCachedPage($render_info, $page_hash)
    {
        $file = self::IsPageCached($page_hash);
        if($file !== false)
        {
            $date = explode('_', $file);
            $date = explode('.', $date[1]);
            $future = new DateTime(preg_replace('/(\d{2})(\d{2})(\d{4})(\d{2})(\d{2})(\d{2})/', '$1-$2-$3 $4:$5:$6', $date[0]));
            $future->add(new DateInterval('PT1H'));
            if(new DateTime() > $future)
            {
                unlink(SkyDefines::Call('DIR_LIB_CACHE').'/'.$file);
                return false;
            }
            echo file_get_contents(SkyDefines::Call('DIR_LIB_CACHE').'/'.$file);
            return true;
    }
        return false;
    }
    
    
    //##########################################
    // Model Caching
    //##########################################
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
        //Log::corewrite('Caching lookup [%s]', 1, __CLASS__, __FUNCTION__, array($lookup));
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