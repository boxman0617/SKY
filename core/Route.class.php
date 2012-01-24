<?php
/**
 * Route Core Class
 *
 * This class handles requests from server and sends them to the proper controller
 *
 * LICENSE:
 *
 * This file may not be redistributed in whole or significant part, or
 * used on a web site without licensing of the enclosed code, and
 * software features.
 * 
 * @author Alan Tirado <root@deeplogik.com>
 * @copyright 2012 DeepLogiK, All Rights Reserved
 * @license http://www.deeplogik.com/sky/legal/license
 * @link http://www.deeplogik.com/sky/index
 * @version 1.0 Initial build
 * @version 1.1 Fixed ambiguous Follow matching and depricated Route::FetchPublicFile()
 * @version 1.2 Ability to simulate PUT and DELETE request methods
 * @package Sky.Core
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
 * Route class
 * Handles requests from server and sends them to the proper controller
 * @package Sky.Core.Route
 */
class Route
{
    /**
     * Error Class Object
     * @access private
     * @var object
     */
    private $error;
    /**
     * Route match indexing array
     * @access private
     * @var array
     */
    private $match_index;
    /**
     * URL match route
     * @access private
     * @var array
     */
    private $matches;
    /**
     * Home page URL match
     * @access private
     * @var array
     */
    private $home;
    /**
     * 404 page URL match
     * @access private
     * @var array
     */
    private $notfound;
    /**
     * URL aliases
     * @access private
     * @var array
     */
    private $aliases;
    /**
     * Static auth salt
     * @access private
     * @var string
     */
    private static $salt = 'SKY';
    /**
     * Actual query string passed in
     * @access private
     * @var string
     */
    private $query_string;
    /**
     * Final match status
     * @access private
     * @var integer
     */
    private $status = STATUS_NOTFOUND;
    /**
     * Final request method
     * @access private
     * @var int
     */
    private $REQUEST_METHOD;
    
    /**
     * Constructor sets up {@link $error}
     */
    public function __construct()
    {
        Event::PublishActionHook('/Route/before/__construct/');
        $this->error = ErrorHandler::Singleton(true);
        if(isset($_REQUEST['REQUEST_METHOD']))
        {
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
            $this->REQUEST_METHOD = $_SERVER['REQUEST_METHOD'];
        }
        Event::PublishActionHook('/Route/after/__construct/');
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
        return md5($id.$salt);
    }
    
    /**
     * Adds URL to {@link $home}
     * @param string $url
     * @access public
     */
    public function Home($url)
    {
        $tmp = explode('#', $url);
        $this->home = ucfirst($tmp[0]).'#'.ucfirst($tmp[1]);
        $this->matches['GET_'.strtolower($tmp[0]).'/'.strtolower($tmp[1])]['CONTROLLER_ACTION'] = ucfirst($tmp[0]).'#'.ucfirst($tmp[1]);
    }
    
    /**
     * Adds URL to {@link $notfound}
     * @param string $url
     * @access public
     */
    public function NotFound($url)
    {
        $tmp = explode('#', $url);
        $this->notfound = ucfirst($tmp[0]).'#'.ucfirst($tmp[1]);
        $this->matches['GET_'.strtolower($tmp[0]).'/'.strtolower($tmp[1])]['CONTROLLER_ACTION'] = ucfirst($tmp[0]).'#'.ucfirst($tmp[1]);
    }
    
    /**
     * Creates RESTful URL matches
     * @access public
     * @param string $controller
     */
    public function Resource($controller)
    {
        $this->Match($controller, ucfirst($controller).'#Index', 'GET');
        $this->Match($controller.'/new', ucfirst($controller).'#NewItem', 'GET');
        $this->Match($controller, ucfirst($controller).'#Create', 'POST');
        $this->Match($controller.'/:id', ucfirst($controller).'#Show', 'GET');
        $this->Match($controller.'/:id/edit', ucfirst($controller).'#Edit', 'GET');
        $this->Match($controller.'/:id', ucfirst($controller).'#Update', 'PUT');
        $this->Match($controller.'/:id', ucfirst($controller).'#Destroy', 'DELETE');
    }
    
    /**
     * Adds URL match to {@link $matches} and creates index to {@link $match_index}
     * @access public
     * @param string $url '/home/index'
     * @param string $controller_action 'Home#Index'
     * @param string $request_method default 'GET'
     */
    public function Match($url, $controller_action, $request_method = 'GET')
    {
        if(strpos($url, '/') === 0)
            $url = substr($url, 1);
        
        $this->match_index[count(explode('/', $url))][] = $request_method.'_'.$url;
        $this->matches[$request_method.'_'.$url]['CONTROLLER_ACTION'] = $controller_action;
    }
    
    /**
     * Create {@link $aliases} from {@link $match_index}
     * @access public
     */
    public function CreateRouteAliases()
    {
        foreach($this->match_index as $k => $value)
        {
            if($k == 1)
            {
                foreach($value as $url)
                {
                    $tmp = explode('_', $url);
                    if(!defined($tmp[1].'_path'))
                        define($tmp[1].'_path', '/'.$tmp[1]);
                }
            }
            if($k > 1)
            {
                foreach($value as $url)
                {
                    $tmp = explode('_', $url);
                    $tmp2 = explode('/', $tmp[1]);
                    $echo = '';
                    $params = '';
                    foreach($tmp2 as $v)
                    {
                        if(strpos($v, ':') === 0)
                        {
                            $params .= $v.', ';
                        } else {
                            $echo .= $v.'_';
                        }
                    }
                    $echo .= 'path';
                    if(!defined($echo))
                        define($echo, '/'.$tmp[1]);
                }
            }
        }
    }
    
    /**
     * Prints out route aliases
     * @access public
     */
    public function ShowRouteAliases()
    {
        foreach($this->match_index as $k => $value)
        {
            if($k == 1)
            {
                foreach($value as $url)
                {
                    $tmp = explode('_', $url);
                    echo $tmp[1].'_path => '.$this->matches[$url]['CONTROLLER_ACTION'].', :via => '.$tmp[0].'<br>';
                }
            }
            if($k > 1)
            {
                foreach($value as $url)
                {
                    $tmp = explode('_', $url);
                    $tmp2 = explode('/', $tmp[1]);
                    $echo = '';
                    $params = '';
                    foreach($tmp2 as $v)
                    {
                        if(strpos($v, ':') === 0)
                        {
                            $params .= $v.', ';
                        } else {
                            $echo .= $v.'_';
                        }
                    }
                    $echo .= 'path';
                    if($params != '')
                    {
                        $echo .= '('.substr($params, 0, -2).')';
                    }
                    echo $echo.' => '.$this->matches[$url]['CONTROLLER_ACTION'].', :via => '.$tmp[0].'<br>';
                }
            }
        }
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
            if(!isset($_POST['token']))
            {
                $this->error->Toss("Not Authorized! [No token past]", E_USER_WARNING);
            }
            if($_POST['token'] != Route::CreateHash(Route::GetSalt()))
            {
                $this->error->Toss('Not Authorized! [Incorrect token]', E_USER_ERROR);
            }
        }
        Event::PublishActionHook('/Route/after/SecurePOST/');
        return true;
    }
    
    /**
     * Follow URL match
     * @access public
     * @return bool
     */
    public function Follow()
    {
        Event::PublishActionHook('/Route/before/Follow/');
        $this->status = STATUS_NOTFOUND;
        //$this->CreateRouteAliases();
        $query = rtrim($_REQUEST['query'], '/');
        if($query == "") //Home#Index
        {
            $tmp = explode('#', $this->home);
            $class = strtolower(ucfirst($tmp[0]));
            import(CONTROLLER_DIR.'/'.strtolower($tmp[0]).'.controller.php');
            $obj = new $class();
            Event::PublishActionHook('/Route/before/Follow/HomeRequest/', array($obj));
            $obj->HandleRequest(strtolower(ucfirst($tmp[1])));
            Event::PublishActionHook('/Route/after/Follow/HomeRequest/', array($obj));
            $this->status = STATUS_FOUND;
        } else {
            if(isset($this->matches[$this->REQUEST_METHOD.'_'.$query])) //Direct match
            {
                $this->SecurePOST($this->REQUEST_METHOD);
                $tmp = explode('#', $this->matches[$this->REQUEST_METHOD.'_'.$query]['CONTROLLER_ACTION']);
                $class = strtolower(ucfirst($tmp[0]));
                import(CONTROLLER_DIR.'/'.strtolower($tmp[0]).'.controller.php');
                $obj = new $class();
                Event::PublishActionHook('/Route/before/Follow/DirectMatchRequest/', array($obj));
                $obj->HandleRequest(strtolower(ucfirst($tmp[1])));
                Event::PublishActionHook('/Route/after/Follow/DirectMatchRequest/', array($obj));
                $this->status = STATUS_FOUND;
            } else { //Indirect match (conversion needed)
                $tmp = explode('/', $query);
                if(!isset($this->match_index[count($tmp)]))
                {
                    $this->status = STATUS_NOTFOUND;
                    $pos_matches = array();
                }
                else
                {
                    $pos_matches = $this->match_index[count($tmp)];
                }
                foreach($pos_matches as $url)
                {
                    if(preg_match('/'.$this->REQUEST_METHOD.'_'.$tmp[0].'/', $url))
                    {
                        if(strpos($url, ':'))
                        {
                            $this->SecurePOST($this->REQUEST_METHOD);
                            $info = $this->matches[$url];
                            $CA = explode('#', $info['CONTROLLER_ACTION']);
                            $class = strtolower(ucfirst($CA[0]));
                            import(CONTROLLER_DIR.'/'.strtolower($CA[0]).'.controller.php');
                            $obj = new $class();
                            Event::PublishActionHook('/Route/before/Follow/IndirectMatchRequest/', array($obj));
                            $obj->HandleRequest(strtolower(ucfirst($CA[1])), $tmp[1]);
                            Event::PublishActionHook('/Route/after/Follow/IndirectMatchRequest/', array($obj));
                            $this->status = STATUS_FOUND;
                            return true;
                        }
                    }
                }
            }
        }
        
        if($this->status == STATUS_NOTFOUND)
        {
            $tmp = explode('#', $this->notfound);
            $class = strtolower(ucfirst($tmp[0]));
            import(CONTROLLER_DIR.'/'.strtolower($tmp[0]).'.controller.php');
            $obj = new $class();
            Event::PublishActionHook('/Route/before/Follow/NotFoundRequest/', array($obj));
            $obj->HandleRequest(strtolower(ucfirst($tmp[1])));
            Event::PublishActionHook('/Route/after/Follow/NotFoundRequest/', array($obj));
        }
        return true;
    }
    
    /**
     * Fetches files from the public directory
     * @access private
     * @param string $file_loc Path to file and file name
     * @deprecated in version 1.1
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
        if(ENV == "PRO")
        {
            $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $file);
            $file = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer);
        }
        echo $file;
        ob_end_flush();
    }
}
?>