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
        $this->Scope($controller, array(
            '/' => array(ucfirst($controller).'#Index'),
            '/new' => array(ucfirst($controller).'#NewItem'),
            '/' => array(ucfirst($controller).'#Create', 'POST'),
            '/:id' => array(ucfirst($controller).'#Show'),
            '/:id/edit' => array(ucfirst($controller).'#Edit'),
            '/:id' => array(ucfirst($controller).'#Update', 'PUT'),
            '/:id' => array(ucfirst($controller).'#Destroy', 'DELETE')
        ));
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
        if($url[0] === '/')
            $url = substr($url, 1);
        
        $this->match_index[count(explode('/', $url))][] = $request_method.'_'.$url;
        $this->matches[$request_method.'_'.$url]['CONTROLLER_ACTION'] = $controller_action;
    }

    /**
     * Adds URL match to {@link $matches} based on $base_url
     * @access public
     * @param string $base_url '/base'
     * @param array $matches array('/test' => array('Base#Test', 'POST'))
     */
    public function Scope($base_url, $matches)
    {
        if($base_url[strlen($base_url)-1] === '/')
            $base_url = substr($base_url, 0, -1);

        foreach($matches as $url => $match)
        {
            if($url[0] !== '/')
                $url = '/'.$url;
            if(!isset($match[1]))
                $match[1] = 'GET';
            $this->Match($base_url.$url, $match[0], $match[1]);
        }
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
                //$this->error->Toss("Not Authorized! [No token past]", E_USER_WARNING);
            }
            if($_POST['token'] != Route::CreateHash(Route::GetSalt()))
            {
                Log::corewrite('Authorized token incorrect [%s] = [%s]', 1, __CLASS__, __FUNCTION__, array($_POST['token'], Route::CreateHash(Route::GetSalt())));
                $session = Session::getInstance();
                $id = $session->getSessionId();
                Log::corewrite('Getting stats [%s] [%s]', 1, __CLASS__, __FUNCTION__, array($id, self::$salt));
                //$this->error->Toss('Not Authorized! [Incorrect token]', E_USER_ERROR);
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
        import(CONTROLLER_DIR.'/'.strtolower($tmp[0]).'.controller.php');
        $obj = new $class();
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
    public function Follow()
    {
        Log::corewrite('Following routes', 3, __CLASS__, __FUNCTION__);
        Event::PublishActionHook('/Route/before/Follow/');
        $this->status = STATUS_NOTFOUND;
        //$this->CreateRouteAliases();
        $query = rtrim($_REQUEST['query'], '/');
        Log::corewrite('Trimming query string [%s]', 1, __CLASS__, __FUNCTION__, array($query));
        if($query == "") //Home#Index
        {
            Log::corewrite('Empty query, accesing Home#Index', 1, __CLASS__, __FUNCTION__);
            $this->RunFollow($this->home, 'HomeRequest');
        } else {
            if(isset($this->matches[$this->REQUEST_METHOD.'_'.$query])) //Direct match
            {
                Log::corewrite('Found direct route match', 1, __CLASS__, __FUNCTION__);
                $this->RunFollow($this->matches[$this->REQUEST_METHOD.'_'.$query]['CONTROLLER_ACTION'], 'DirectMatchRequest');
            } else { //Indirect match (conversion needed)
                Log::corewrite('Converting indirect route match', 1, __CLASS__, __FUNCTION__);
                $tmp = explode('/', $query);
                Log::corewrite('Looking for match index [%s]',1 , __CLASS__, __FUNCTION__, array(count($tmp)));
                if(!isset($this->match_index[count($tmp)]))
                {
                    $this->status = STATUS_NOTFOUND;
                    $pos_matches = array();
                }
                else
                {
                    $pos_matches = $this->match_index[count($tmp)];
                }
                $closest = array();
                $q_tmp = $this->REQUEST_METHOD.'_'.$query;
                foreach($pos_matches as $url)
                {
                    for($i = 0; $i < strlen($q_tmp); $i++)
                    {
                        if($q_tmp[$i] == $url[$i])
                            $closest[$url] = $i;
                        else
                            break;
                    }
                }
                $key = array_keys($closest, max($closest));
                $url = $key[0];
                Log::corewrite('Matched [%s]',1 , __CLASS__, __FUNCTION__, array($url));
                if(strpos($url, ':'))
                {
                    Log::corewrite('Found : in url',1 , __CLASS__, __FUNCTION__);
                    $this->SecurePOST($this->REQUEST_METHOD);
                    $info = $this->matches[$url];
                    $CA = explode('#', $info['CONTROLLER_ACTION']);
                    $class = strtolower(ucfirst($CA[0]));
                    import(CONTROLLER_DIR.'/'.strtolower($CA[0]).'.controller.php');
                    $obj = new $class();
                    Event::PublishActionHook('/Route/before/Follow/IndirectMatchRequest/', array($obj));
                    $obj->HandleRequest(strtolower(ucfirst($CA[1])), $tmp[count($tmp) - 1]);
                    Event::PublishActionHook('/Route/after/Follow/IndirectMatchRequest/', array($obj));
                    $this->status = STATUS_FOUND;
                    Log::corewrite('At the end of method', 2, __CLASS__, __FUNCTION__);
                    return true;
                }
            }
            Log::corewrite('At the end of method', 2, __CLASS__, __FUNCTION__);
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