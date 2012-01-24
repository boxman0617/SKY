<?php
class ObjectFactory
{
    public static function Manufactor($type)
    {
        $ls = scandir(OBJECTS_DIR);
        if(in_array($type.'.object.php', $ls))
        {
            import(OBJECTS_DIR.'/'.$type.'.object.php');
            return new $type();
        }
        return false;
    }
}
?>