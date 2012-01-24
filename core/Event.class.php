<?php
interface iEvent
{
    /**
     * Action CORE hooks
     */
    public static function SubscribeActionHook($hook, $callback);
    public static function UnSubscribeActionHook($hook);
    public static function PublishActionHook($hook, $args);
    
    /**
     * Filter OUTPUT hooks
     */
    //public static function SubstribeFilterHook($hook, $callback);
    //public static function UnSubstribeFilterHook($hook);
    //public static function PublishFilterHook($hook, $args);
}

class Event implements iEvent
{
    public static $hooks;
    
    public static function SubscribeActionHook($hook, $callback)
    {
        self::$hooks['action'][$hook] = $callback;
    }
    
    public static function UnSubscribeActionHook($hook)
    {
        if(isset(self::$hooks['action'][$hook]))
            unset(self::$hooks['action'][$hook]);
    }
    
    public static function PublishActionHook($hook, $args = array())
    {
        if(isset(self::$hooks['action'][$hook]))
        {
            if(is_array(self::$hooks['action'][$hook])) //Object
            {
                foreach(Plugin::$plugin as $name => $info)
                {
                    if(isset($info['class']) && $info['class'] == self::$hooks['action'][$hook][0])
                    {
                        import(Plugin::$plugin[$name]['dir'].'/'.Plugin::$plugin[$name]['file']);
                        $class = Plugin::$plugin[$name]['class'];
                        $obj = new $class();
                        return call_user_func_array(array($obj, self::$hooks['action'][$hook][1]), $args);
                    }
                }
            } else { //Function
                //foreach(Plugin::$plugin as $name => $info)
                //{
                //    import(Plugin::$plugin[$name]['dir'].'/'.Plugin::$plugin[$name]['file']);
                //    return call_user_func_array(array($obj, self::$hooks['action'][$hook][1]), $args);
                //}
            }
        }
    }
}
?>