<?php
/**
 * Router Class
 *
 * This class acts like a router for all incoming requests.
 * It uses the Route class to determine where the requests
 * should go. Once the request matches up with one of the
 * routes in the Route class, it will create an instance
 * of the controller defined within the route definition.
 * Then the method that should be ran will be.
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
 * @package     Core\router\Router
 * @version     1.0.0
 */

define('STATUS_NOTFOUND', 404);
define('STATUS_FOUND', 200);

// ####
// Router Class
// @desc Handles requests from server and sends them to the proper controller.
// @package SKY.Core.Router
// ##
class Router extends Base
{
    private $home;
    private $notfound;
    private $aliases;
    private static $salt = 'SKY';
    private $query_string;
    private $status = STATUS_NOTFOUND;
    private $REQUEST_METHOD;
    public static $_request_method;
    public static $_current_location;
    public static $_route;
    public static $_controller;
    public static $_action;

    // ####
    // __construct
    // @desc Constructor method. Gets called at object initialization.
    // - Sets the request method.
    // @public
    // @core
    // ##
    public function __construct()
    {
        self::$_current_location = rtrim($_REQUEST['_query'], '/');
        Log::corewrite('Opening routes', 3, __CLASS__, __FUNCTION__);
        Event::PublishActionHook('/Route/before/__construct/');
        if(isset($_REQUEST['REQUEST_METHOD']))
        {
            Log::corewrite('Conveting DRY method', 1, __CLASS__, __FUNCTION__);
            switch($_REQUEST['REQUEST_METHOD'])
            {
                case 'PUT':
                    $this->REQUEST_METHOD = 'PUT';
                    break;
                case 'DELETE':
                    $this->REQUEST_METHOD = 'DELETE';
                    break;
            }
            unset($_REQUEST['REQUEST_METHOD']);
        } else {
            Log::corewrite('Assigning DRY method', 1, __CLASS__, __FUNCTION__);
            $this->REQUEST_METHOD = $_SERVER['REQUEST_METHOD'];
        }
        self::$_request_method = $this->REQUEST_METHOD;
        Log::corewrite('Method [%s]', 3, __CLASS__, __FUNCTION__, array($this->REQUEST_METHOD));
        Event::PublishActionHook('/Route/after/__construct/');
        Log::corewrite('At the end of method', 2, __CLASS__, __FUNCTION__);
    }

    // ####
    // GetSalt
    // @desc Returns static $salt
    // @return String
    // @public
    // @static
    // @app
    // ##
    public static function GetSalt()
    {
        return self::$salt;
    }

    // ####
    // CreateHash
    // @desc Creates secure MD5 hash from session id and $salt
    // @args String $salt
    // - Random salt for md5 hashing. Example: SkYR0ck5!
    // @return String
    // @public
    // @static
    // @app
    // ##
    public static function CreateHash($salt)
    {
        $session = Session::getInstance();
        $id = $session->getSessionId();
        Log::corewrite("Creating Hash [%s] [%s]", 1, __CLASS__, __FUNCTION__, array($id, $salt));
        return md5($id.$salt);
    }

    // ####
    // CreateHash
    // @desc Checks auth token hash when a secure POST request comes in
    // @args String $request_method
    // - Method of request comming in. Should be POST || PUT
    // @return Bool
    // @private
    // @core
    // ##
    private function SecurePOST($request_method)
    {
        Event::PublishActionHook('/Route/before/SecurePOST/');
        if($request_method == 'POST' || $request_method == 'PUT')
        {
            if(!isset($_POST['token'])) trigger_error("Not Authorized! [No token passed]", E_USER_WARNING);
            if($_POST['token'] != Route::CreateHash(Route::GetSalt()))
            {
                Log::corewrite('Authorized token incorrect [%s] = [%s]', 1, __CLASS__, __FUNCTION__, array($_POST['token'], Route::CreateHash(Route::GetSalt())));
                $session = Session::getInstance();
                $id = $session->getSessionId();
                Log::corewrite('Getting stats [%s] [%s]', 1, __CLASS__, __FUNCTION__, array($id, self::$salt));
                trigger_error('Not Authorized! [Incorrect token]', E_USER_ERROR);
            }
        }
        Event::PublishActionHook('/Route/after/SecurePOST/');
        return true;
    }

    private function ControllerInit($class, $query, $action, $params = array())
    {
        self::$_controller = $class;
        self::$_action = $action;
        $controller = new $class($params);
        if(!($controller instanceof Controller))
            throw new Exception('['.$class.'] is not a Controller');
        Event::PublishActionHook('/Router/before/ControllerInit/', array(array(
            'class' => $class,
            'query' => $query,
            'action' => $action,
            'params' => $params,
            'controller' => $controller
        )));
        $controller->SetRouterSpecs(array(
            'query' => $query,
            'method' => $this->REQUEST_METHOD
        ));
        Log::corewrite('Running Controller::Action [%s::%s]', 1, __CLASS__, __FUNCTION__, array($class, $action));
        $controller->HandleRequest($action);
        $this->status = STATUS_FOUND;
        Event::PublishActionHook('/Router/after/ControllerInit/', array(array(
            'class' => $class,
            'query' => $query,
            'action' => $action,
            'params' => $params,
            'controller' => $controller
        )));
    }

    /**
     * Follow URL match
     * @access public
     * @return bool
     */
    public function Follow($routes)
    {
        Log::corewrite('Following query [%s]', 3, __CLASS__, __FUNCTION__, array($_REQUEST['_query']));
        $query = rtrim($_REQUEST['_query'], '/');
        if($query == '')
            $query = '_';
            
        Event::PublishActionHook('/Route/before/Follow/', array(
            $query, $this->REQUEST_METHOD
        ));

        self::$_route = $query;

        Log::corewrite('Following query [%s][%s]', 1, __CLASS__, __FUNCTION__, array($this->REQUEST_METHOD, $query));

        $e_tmp = explode('/', $query);
        if(isset($routes[$this->REQUEST_METHOD][count($e_tmp)]))
            $tmp = $routes[$this->REQUEST_METHOD][count($e_tmp)];
        else
            $tmp = array();

        self::$_share['router']['query'] = $query;
        self::$_share['router']['METHOD'] = $this->REQUEST_METHOD;

        Event::PublishActionHook('/Route/query/ready/before/', array($query, $this->REQUEST_METHOD));

        if(isset($tmp[$query])) //Direct match
        {
            Log::corewrite('Direct Match', 1, __CLASS__, __FUNCTION__);
            $class = ucfirst(strtolower($tmp[$query]['controller']));
            SkyL::Import(SkyDefines::Call('DIR_APP_CONTROLLERS').'/'.strtolower($class).'.controller.php');
            if(class_exists($class))
            {
                $this->ControllerInit($class, $query, $tmp[$query]['action']);
                return true;
            }
            throw new Exception('Seems as though the following Controller has not yet been defined: ['.$class.'] Define it in the following directory to fix this issue: ['.SkyDefines::Call('DIR_APP_CONTROLLERS').']');
            return false;
        }
        else if(!isset($tmp[$query]))
        {
            Log::corewrite('Indirect Match', 1, __CLASS__, __FUNCTION__);
            $matches = array_keys($tmp);
            $indirect_match = false;
            $indirect_matches = array();
            for($i=0;$i<count($matches);$i++)
            {
                $params = array();
                $e = explode('/', $matches[$i]);
                $check_tmp = array();
                for($n=0;$n<count($e);$n++)
                {
                    if(strpos($e[$n], ':') !== false)
                    {
                        $params[ltrim($e[$n], ':')] = $e_tmp[$n];
                        $check_tmp[$n] = $e_tmp[$n];
                    }
                    else
                        $check_tmp[$n] = $e[$n];
                }
                $check_query = implode('/', $check_tmp);
                if($query == $check_query)
                {
                    Log::corewrite('Matched [%s] to [%s]', 1, __CLASS__, __FUNCTION__, array($query, $check_query));
                    $indirect_match = $tmp[$matches[$i]];
                    $indirect_match['url'] = $matches[$i];
                    $indirect_matches[$indirect_match['action']] = $indirect_match;
                    $indirect_matches[$indirect_match['action']]['params'] = $params;
                }
            }
            $winner = null;
            $num_win = 999999;
            foreach($indirect_matches as $action => $info)
            {
                if(count($info['params']) < $num_win)
                {
                    $num_win = count($info['params']);
                    $winner = $action;
                    Log::corewrite('Found a winner [%s]', 1, __CLASS__, __FUNCTION__, array($winner));
                }
            }

            if(!is_null($winner))
            {
                $class = ucfirst(strtolower($indirect_matches[$winner]['controller']));
                SkyL::Import(SkyDefines::Call('DIR_APP_CONTROLLERS').'/'.strtolower($class).'.controller.php');
                if(class_exists($class))
                {
                    $this->ControllerInit(
                        $class,
                        $query,
                        ucfirst(strtolower($winner)),
                        $indirect_matches[$winner]['params']
                    );
                    return true;
                }
                throw new Exception('Seems as though the following Controller has not yet been defined: ['.$class.'] Define it in the following directory to fix this issue: ['.SkyDefines::Call('DIR_APP_CONTROLLERS').']');
                return false;
            }
        }

        $this->status = STATUS_NOTFOUND;
        $class = ucfirst(strtolower($routes['GET'][1]['_notfound']['controller']));
        SkyL::Import(SkyDefines::Call('DIR_APP_CONTROLLERS').'/'.strtolower($class).'.controller.php');
        $obj = new $class();
        $obj->HandleRequest(ucfirst(strtolower($routes['GET'][1]['_notfound']['action'])));
        return false;
    }
}
