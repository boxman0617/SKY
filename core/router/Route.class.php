<?php
/**
 * Route Core Abstraction Class
 *
 * This class allows the creation of routes for the
 * Router class to parse and create links to the
 * Controller class.
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
 * @link        http://www.codethesky.com/docs/routeclass
 * @package     Sky.Core
 */

abstract class Route
{
	private $matches = array();

	abstract public function AppRoutes();
	
	private function _DefineRoutes()
	{
	    foreach($this->matches as $REQUEST_TYPE => $DATA)
	    {
	        foreach($DATA as $SIZE => $URL_ARRAY)
	        {
	            foreach($URL_ARRAY as $URL => $ControllerAction)
	            {
	                $TERM = $REQUEST_TYPE.'_';
	                $DEFINITION = BASE_GLOBAL_URL.$URL;
	                if($URL == '_') // Match => Home
	                {
	                    $TERM .= 'HOME';
	                    $DEFINITION = BASE_GLOBAL_URL;
	                }
	                elseif($URL == '_notfound') // Match => NotFound (404) #@ToDo: Make this so other "error" pages will show EX: 500 error
	                {
	                    $TERM .= 'PAGE_NOT_FOUND';
	                    $DEFINITION = BASE_GLOBAL_URL.'404';
	                } else {
	                    if(strpos($URL, ':') === false)
	                    {
	                        $TERM .= strtoupper(str_replace('-', '_', str_replace('/', '_', $URL)));
	                    } else {
	                        RouteTo::_setDynamicRoute(strtoupper(str_replace('-', '_', str_replace('/', ' ', $URL))), $DEFINITION);
	                        continue;
	                    }
	                }
	                if($GLOBALS['ENV'] == 'DEV')
	                    Log::write('Route Def: %s => %s [%s#%s]', $TERM, $DEFINITION, $ControllerAction['controller'], $ControllerAction['action']);
	                define($TERM, $DEFINITION);
	            }
	        }
	    }
	}

	public function _GetMatches()
	{
	    $this->_DefineRoutes();
		return $this->matches;
	}
	
	protected function RemoveMatch($url, $request_method = 'GET')
	{
	    if($url[0] === '/')
            if(!$url = substr($url, 1))
                $url = "/";
        if(array_key_exists($url, $this->matches[strtoupper($request_method)][count(explode('/', $url))]))
            unset($this->matches[strtoupper($request_method)][count(explode('/', $url))][$url]);
	}

	protected function Match($url, $controller_action, $request_method = 'GET')
    {
        if($url[0] === '/')
            if(!$url = substr($url, 1))
                $url = "/";
        
        $ca = explode('#', $controller_action);
        $this->matches[strtoupper($request_method)][count(explode('/', $url))][$url] = array(
            'controller' => $ca[0],
            'action' => $ca[1]
        );
    }

    protected function Scope($base_url, $matches)
    {
        if($base_url[strlen($base_url)-1] === '/')
            $base_url = substr($base_url, 0, -1);

        foreach($matches as $match)
        {
            if($match[0][0] !== '/')
                $match[0] = '/'.$match[0];
            if(strlen($match[0]) == 1 && $match[0] == '/')
                $match[0] = '';
            if(!isset($match[2]))
                $match[2] = 'GET';
            $this->Match($base_url.$match[0], $match[1], $match[2]);
        }
    }

    protected function Resource($controller)
    {
        $this->Scope($controller, array(
            array('/', ucfirst($controller).'#Index'),
            array('/new', ucfirst($controller).'#NewItem'),
            array('/', ucfirst($controller).'#Create', 'POST'),
            array('/:id',ucfirst($controller).'#Show'),
            array('/:id/edit', ucfirst($controller).'#Edit'),
            array('/:id', ucfirst($controller).'#Update', 'PUT'),
            array('/:id', ucfirst($controller).'#Destroy', 'DELETE')
        ));
    }

    protected function Home($controller_action)
    {
        $this->Match('_', $controller_action);
    }

    protected function NotFound($controller_action)
    {
        $this->Match('/_notfound', $controller_action);
    }
}
?>