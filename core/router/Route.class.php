<?php
/**
 * Route Core Abstraction Class
 *
 * This class allows the creation of routes for the
 * Router class to parse and create links to the
 * Controller class.
 * 
 * LICENSE:
 * The MIT License (MIT)
 * 
 * Copyright (c) 2014 DeeplogiK
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * 
 * @author      Alan Tirado <alan@deeplogik.com>
 * @copyright   2014 DeepLogik, All Rights Reserved
 * @license     MIT
 * @package     Core\router\Route
 * @version     1.0.0
 */

/**
 * Base class for APP routes
 */
abstract class Route
{
	private $matches = array();
	private $api_matches = array();

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
	                $DEFINITION = SkyDefines::Call('BASE_GLOBAL_URL').$URL;
	                if($URL == '_') // Match => Home
	                {
	                    $TERM .= 'HOME';
	                    $DEFINITION = SkyDefines::Call('BASE_GLOBAL_URL');
	                }
	                elseif($URL == '_notfound') // Match => NotFound (404) #@ToDo: Make this so other "error" pages will show EX: 500 error
	                {
	                    $TERM .= 'PAGE_NOT_FOUND';
	                    $DEFINITION = SkyDefines::Call('BASE_GLOBAL_URL').'404';
	                } else {
	                    if(strpos($URL, ':') === false)
	                    {
	                        $TERM .= strtoupper(str_replace('-', '_', str_replace('/', '_', $URL)));
	                    } else {
	                        RouteTo::_setDynamicRoute(strtoupper(str_replace('-', '_', str_replace('/', ' ', $URL))), $DEFINITION);
	                        continue;
	                    }
	                }
	                if(SkyDefines::GetEnv() == 'DEV')
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
