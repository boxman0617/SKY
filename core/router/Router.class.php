<?php
/**
 * Router Core Class
 *
 * This class acts like a router for all incoming requests.
 * It uses the Route class to determine where the requests
 * should go. Once the request matches up with one of the
 * routes in the Route class, it will create an instance
 * of the controller defined within the route definition.
 * Then the method that should be ran will be.
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
 * @link        http://www.codethesky.com/docs/routerclass
 * @package     Sky.Core
 */

/**
 * Constant STATUS_NOTFOUND, tells route that it did not find a valid route to route request to
 */
define('STATUS_NOTFOUND', 404);
/**
 * Constant STATUS_FOUND, tells route that is found a valid route to route request to
 */
define('STATUS_FOUND', 200);

/**
 * Router class
 * Handles requests from server and sends them to the proper controller
 * @package Sky.Core.Route
 */
class Router
{
    private $home;
    private $notfound;
    private $aliases;
    private static $salt = 'SKY';
    private $query_string;
    private $status = STATUS_NOTFOUND;
    private $REQUEST_METHOD;
    
    /**
     * Constructor sets up {@link $error}
     */
    public function __construct()
    {
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
        Event::PublishActionHook('/Route/after/__construct/');
        Log::corewrite('At the end of method', 2, __CLASS__, __FUNCTION__);
    }
    
    /**
     * Returns static auth {@link $salt}
     * @access public
     * @return string $salt
     */
    public static function GetSalt()
    {
        return self::$salt;
    }
    
    /**
     * Creates secure MD5 hash from session id and static auth {@link $salt}
     * @access public
     * @param string $salt
     * @return string
     */
    public static function CreateHash($salt)
    {
        $session = Session::getInstance();
        $id = $session->getSessionId();
        Log::corewrite("Creating Hash [%s] [%s]", 1, __CLASS__, __FUNCTION__, array($id, $salt));
        return md5($id.$salt);
    }
    
    /**
     * Creates constants for routes to be used in links
     * @todo Need to add this and inegrate with MVC classes
     * @access public
     */
    public function CreateRouteAliases()
    {
        // @todo
    }
    
    /**
     * Prints out routes
     * @todo Need to add this and integrate with CML tools
     * @access public
     */
    public function ShowRouteAliases()
    {
        // @todo
    }
    
    /**
     * Checks auth token hash when a secure POST request comes in
     * @access private
     * @param string $request_method
     * @return bool
     */
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

    private function RunFollow($controller_action, $hook)
    {
        $this->SecurePOST($this->REQUEST_METHOD);
        $tmp = explode('#', $controller_action);
        $class = strtolower(ucfirst($tmp[0]));
        import(DIR_APP_CONTROLLERS.'/'.strtolower($tmp[0]).'.controller.php');
        $obj = new $class();
        Log::write('Query: [%s]', rtrim($_REQUEST['_query'], '/'));
        Event::PublishActionHook('/Route/before/Follow/'.$hook.'/', array($obj));
        $obj->HandleRequest(strtolower(ucfirst($tmp[1])));
        Event::PublishActionHook('/Route/after/Follow/'.$hook.'/', array($obj));
        $this->status = STATUS_FOUND;
    }

    /**
     * Follow URL match
     * @access public
     * @return bool
     */
    public function Follow($routes)
    {
        Log::corewrite('Following routes', 3, __CLASS__, __FUNCTION__);
        Event::PublishActionHook('/Route/before/Follow/');
        $query = rtrim($_REQUEST['_query'], '/');
        if($query == '')
            $query = '_';

        Log::corewrite('Following query [%s][%s]', 1, __CLASS__, __FUNCTION__, array($this->REQUEST_METHOD, $query));

        $e_tmp = explode('/', $query);
        if(isset($routes[$this->REQUEST_METHOD][count($e_tmp)]))
            $tmp = $routes[$this->REQUEST_METHOD][count($e_tmp)];
        else
            $tmp = array();

        if(isset($tmp[$query])) //Direct match
        {
            Log::corewrite('Direct Match', 1, __CLASS__, __FUNCTION__);
            $class = ucfirst(strtolower($tmp[$query]['controller']));
            import(DIR_APP_CONTROLLERS.'/'.strtolower($class).'.controller.php');
            $obj = new $class();
            $obj->HandleRequest(ucfirst(strtolower($tmp[$query]['action'])));
            $this->status = STATUS_FOUND;
            return true;
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
                }
            }

            if(!is_null($winner))
            {
                $class = ucfirst(strtolower($indirect_matches[$winner]['controller']));
                import(DIR_APP_CONTROLLERS.'/'.strtolower($class).'.controller.php');
                $obj = new $class($indirect_matches[$winner]['params']);
                $obj->HandleRequest(ucfirst(strtolower($winner)));
                $this->status = STATUS_FOUND;
                return true;
            }
        }

        $this->status = STATUS_NOTFOUND;
        $class = ucfirst(strtolower($routes['GET'][1]['_notfound']['controller']));
        import(DIR_APP_CONTROLLERS.'/'.strtolower($class).'.controller.php');
        $obj = new $class();
        $obj->HandleRequest(ucfirst(strtolower($routes['GET'][1]['_notfound']['action'])));
        return false;
    }
    
    /**
     * Fetches files from the public directory
     * @access private
     * @param string $file_loc Path to file and file name
     * @deprecated Do Not Use
     */
    private function FetchPublicFile($file_loc)
    {
        $file = file_get_contents(dirname(__FILE__).'/../public/'.$file_loc);
        ob_start("ob_ghandler");
        $ext = explode('.', $file_loc);
        switch ($ext[(count($ext) - 1)])
        {
            case "pdf": $ctype="application/pdf"; break; 
            case "exe": $ctype="application/octet-stream"; break; 
            case "zip": $ctype="application/zip"; break; 
            case "doc": $ctype="application/msword"; break; 
            case "xls": $ctype="application/vnd.ms-excel"; break; 
            case "ppt": $ctype="application/vnd.ms-powerpoint"; break; 
            case "gif": $ctype="image/gif"; break; 
            case "png": $ctype="image/png"; break; 
            case "jpeg": 
            case "jpg": $ctype="image/jpg"; break;
            case "css": $ctype="text/css"; break;
            case "js": $ctype="text/javascript"; break;
            default: $ctype="application/force-download";
        }
        header("content-type: ".$ctype."; charset: UTF-8");
        header("cache-control: must-revalidate");
        $offset = 60 * 60;
        $expire = "expire: ".gmdate("D, d M Y H:i:s", time() + $offset)." GMT";
        header($expire);
        if($GLOBALS['ENV'] == "PRO")
        {
            $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $file);
            $file = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer);
        }
        echo $file;
        ob_end_flush();
    }
}
?>