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

	public function _GetMatches()
	{
		return $this->matches;
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