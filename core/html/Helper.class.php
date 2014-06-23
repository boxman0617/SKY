<?php
class Helper
{
    public static function link_to($label, $path, $attr = array())
    {
        $link = '<a href="'.$path.'" ';
        foreach($attr as $name => $value)
            $link .= $name.'="'.$value.'" ';
        $link .= '>'.$label.'</a>';
        return $link;
    }
    
    private static function create_path(Model $model, $end = '')
    {
        if($model->responds_to('id'))
        {
            return SkyDefines::Call('BASE_GLOBAL_URL').strtolower(get_class($model)).'/'.$model->id.$end;
        }
        trigger_error('Model has not been loaded. Unable to generate path.', E_USER_NOTICE);
        return '';
    }
    
    public static function __callStatic($method, $args)
    {
        if(substr($method, 0, 7) === 'path_to')
        {
            $e = explode('_', $method);
            if(count($e) > 2)
            {
                return self::create_path($args[0], '/'.$e[2]);
            }
            return self::create_path($args[0]);
        }
    }
}
