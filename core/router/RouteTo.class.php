<?php
/**
 * Examples:
 * RouteTo::PAGES(); /pages
 * RouteTo::NEW_PAGE(); /pages/new
 * RouteTo::EDIT_PAGE($page->id); /pages/:id/edit
 * RouteTo::PAGE($page->id); /pages/:id
 * RouteTo::PREVIEW_PAGE($page->id); /pages/:id/preview
 * 
 * RouteTo::NEW_NOTE('activities', $a->id); /:model_name/:id/notes/new
 */
class InvalidRouteException extends Exception { }
class RouteTo
{
    private static $_routes = array();
    
    public static function __callStatic($method, $args)
    {
        $broken = array_reverse(explode('_', $method));
        $c = count($broken);
        $c_args = count($args);
        $total_c = $c + $c_args;
        $routes = array_keys(self::$_routes);
        foreach($routes as $route)
        {
            $broken_route = explode(' ', $route);
            if($total_c == count($broken_route))
            {
                $winner = 0;
                for($a = 0; $a < $c; $a++)
                {
                    if(strpos($route, $broken[$a]) !== false)
                        $winner++;
                }
                if($winner == $c)
                {
                    $location = BASE_GLOBAL_URL;
                    $i = 0;
                    foreach($broken_route as $br)
                    {
                        if(strpos($br, ':') !== false)
                        {
                            $location .= $args[$i].'/';
                            $i++;
                        } else {
                            $location .= strtolower($br).'/';
                        }
                    }
                    return $location;
                }
            }
        }
        throw new InvalidRouteException("Route [{$method}] doesn't exist");
    }
    
    public static function _setDynamicRoute($term, $definition)
    {
        self::$_routes[$term] = $definition;
        //Log::write('Dyn Route: [%s] => [%s]', $term, $definition);
    }
}
?>