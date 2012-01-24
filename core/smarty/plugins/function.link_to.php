<?php
require_once(dirname(__FILE__).'/../../../configs/defines.php');
require_once(ROUTES_CLASS);
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage PluginsFunction
 */

/**
 * Smarty - Sky {link_to} function plugin
 *
 * Type:     function<br>
 * Name:     link_to<br>
 * Purpose:  print out a link to page according with routes.php
 * @author Alan Tirado <atirado0617 at gmail dot com>
 * @param array parameters
 * @param Smarty
 * @param object $template template object
 */
function smarty_function_link_to($params, $template)
{
    $routes = file_get_contents(ROUTES);
    preg_match_all("/\n.routes->Home\('(.+)'\);/", $routes, $home_matches);
    preg_match_all("/\n.routes->NotFound\('(.+)'\);/", $routes, $not_found_matches);
    preg_match_all("/\n.routes->Match\('(.+)'\);/", $routes, $matches);
    preg_match_all("/\n.routes->Resource\('(.+)'\);/", $routes, $resource_matches);
    
    $r = new Route();
    if(isset($home_matches[1]))
    {
        foreach($home_matches[1] as $v)
        {
            $r->Home($v);
        }
    }
    if(isset($not_found_matches[1]))
    {
        foreach($not_found_matches[1] as $v)
        {
            $r->NotFound($v);
        }
    }
    if(isset($resource_matches[1]))
    {
        foreach($resource_matches[1] as $v)
        {
            $r->Resource($v);
        }
    }
    if(isset($matches[1]))
    {
        foreach($matches[1] as $v)
        {
            $myparams = explode(',', $v);
            foreach($myparams as $p)
            {
                $tmp[] = trim(trim($p), "'");
            }
            call_user_func_array(array($r, 'Match'), $tmp);
        }
    }
    $r->CreateRouteAliases();
    if(isset($params['link_name']))
        $link_name = $params['link_name'];
    else
        $link_name = 'Link';
    echo '<a href="';
    eval('echo '.$params['name'].';');
    echo '">'.$link_name.'</a>';
}

?>