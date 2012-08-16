<?php
/**
 * Controller Core Class
 *
 * This class handles controller actions by getting info
 * from models and displaying them to a view
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
 * @version 1.1 Added ability to change main layout by child controller or controller action
 * @version 1.1.2 Fixed DRYRunFilter method
 * @version 2.0.0 Removed SMARTY
 * @package Sky.Core
 */

import(MODEL_CLASS);

/**
 * Constant RENDER_NONE, tells controller not to render anything
 */
define('RENDER_NONE', 0);
/**
 * Constant RENDER_HTML, tells controller to render HTML page
 */
define('RENDER_HTML', 1);
/**
 * Constant RENDER_JSON, tells controller to render JSON code
 */
define('RENDER_JSON', 2);
/**
 * Constant RENDERD, tells controller that it has been redered
 */
define('RENDERED', true);
/**
 * Constant NOT_RENDERD, tells controller that it has not been rendered yet
 */
define('NOT_RENDERED', false);

/**
 * Controller class
 * Handles what to do with models and data then displays it to view or JSON
 * @package Sky.Core.Controller
 */
abstract class Controller
{
    /**
     * Error Class Object
     * @access private
     * @var object
     */
    private $error;
    /**
     * What to render flag
     * @access private
     * @var integer
     */
    private $render = RENDER_HTML;
    /**
     * Rendered Status
     * @access private
     * @var bool
     */
    private $render_status = NOT_RENDERED;
    /**
     * Reditect directive
     * @access process
     * @var string
     */
    protected $redirect = null;
    /**
     * Data to pass to JSON render method
     * @access private
     * @var mixed
     */
    private $render_info = null;
    /**
     * Method to render
     * @access private
     * @var string
     */
    private $method;
    /**
     * Variables to pass to HTML page
     * @access protected
     * @var array
     */
    public $variables = array();
    /**
     * Layout name
     * @access protected
     * @var string
     */
    protected $layout = 'layout/layout.view.php';
    /**
     * $_POST params
     * @access public
     * @var array
     */
    public $params = array();
    /**
     * A filter applied before a controller action
     * @access protected
     * @var array
     */
    protected $before_filter = array();
    /**
     * A filter applied around a controller action
     * @access protected
     * @todo Not sure if this will be fully implemented
     * @var array
     */
    protected $around_filter = array();
    /**
     * A filter applied after a controller action
     * @access protected
     * @var array
     */
    protected $after_filter = array();
    public static $_variables = array();
    
    /**
     * Constructor sets up {@link $error} and {@link $params}
     */
    public function __construct()
    {
        Log::corewrite('Opening controller [%s]', 3, __CLASS__, __FUNCTION__, array(get_class($this)));
        Event::PublishActionHook('/Controller/before/__construct/', array($this));
        $this->error = ErrorHandler::Singleton(true);
        Log::corewrite('Merging $_POST and ::$params', 1, __CLASS__, __FUNCTION__);
        $this->params = array_merge($_POST, $this->params);
        Event::PublishActionHook('/Controller/after/__construct/', array($this));
        Log::corewrite('At the end of method', 2, __CLASS__, __FUNCTION__);
    }
    
    /**
     * Sets up {@link $render} and {@link $render_info} is not null
     * @access protected
     * @param integer $render
     * @param mixed $render_info
     */
    protected function Render($params)
    {
        if(isset($params['action']))
        {
            if(isset($params['params']))
                $this->RedirectTo(array('action' => $params['action'], 'params' => $params['params']));
            else
                $this->RedirectTo(array('action' => $params['action']));
            return true;
        }
        if(isset($params['flag']))
        {
            $this->render = $params['flag'];
            if(isset($params['info']))
            {
                $this->render_info = $params['info'];
            }
            return true;
        }
		if(isset($params['view']))
		{
			$this->Assign('MAIN_PAGE', $params['view']);
			return true;
		}
        if(isset($params['file']))
        {
            $this->GetFile($params['file']);
        }
    }

    protected function GetFile($filename)
    {
        // required for IE, otherwise Content-disposition is ignored
        if(ini_get('zlib.output_compression'))
            ini_set('zlib.output_compression', 'Off');
        $file_extension = strtolower(substr(strrchr($filename,"."),1));

        switch( $file_extension )
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
          default: $ctype="application/force-download";
        }
        header("Pragma: public"); // required
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private",false); // required for certain browsers
        header("Content-Type: $ctype");
        // change, added quotes to allow spaces in filenames, by Rajkumar Singh
        header("Content-Disposition: attachment; filename=\"".basename($filename)."\";" );
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".filesize($filename));
        readfile("$filename");
        $this->render = RENDER_NONE;
    }

    /**
     * Sets up {@link $layout}
     * @access protected
     * @param string $layout_name
     */
    protected function SetLayout($layout_name)
    {
        $this->layout = $layout_name;
    }
    
    /**
     * Will handle any before filters applied to action
     * @access protected
     * @return bool
     */
    protected function HandleBeforeFilters()
    {
        $this->DRYRunFilter($this->before_filter);
    }
    
    /**
     * DRY filter method
     * @access private
     * @param array $filter
     */
    private function DRYRunFilter($filters)
    {
        foreach($filters as $filter => $options)
        {
            if(is_array($options)) //Look for special options
            {
                if(isset($options['only'])) //Run ONLY for these actions
                {
                    if(is_array($options['only']))
                    {
                        function Lower($v)
                        {
                            return strtolower($v);
                        }
                        $options['only'] = array_map('Lower', $options['only']);
                        if(in_array($this->method, $options['only']))
                        {
                            call_user_func(array($this, $filter));
                        }
                    } else {
                        if(strtolower($this->method) == strtolower($options['only']))
                        {
                            call_user_func(array($this, $filter));
                        }
                    }
                }
            } else { //Run before filter [No options]
                call_user_func(array($this, $filter));
            }
        }
        return true;
    }
    
    /**
     * Will handle any after filters applied to action
     * @access protected
     * @return bool
     */
    protected function HandleAfterFilters()
    {
        return $this->DRYRunFilter($this->after_filter);
    }
    
    /**
     * Decides how to render controller and runs child method
     * @access public
     * @param string $method
     * @param mixed $pass default null
     */
    public function HandleRequest($method, $pass = null)
    {
        Log::corewrite('Handling request by [%s]', 3, __CLASS__, __FUNCTION__, array($method));
        Event::PublishActionHook('/Controller/before/HandleRequest/', array($this));
        $this->method = $method;
        $this->HandleBeforeFilters();
        call_user_func(array($this, $method), $pass);
        $this->HandleAfterFilters();
        switch($this->render)
        {
            case RENDER_HTML:
                if(!$this->render_status)
                {
                    $this->RenderHTML();
                }
                break;
            case RENDER_JSON:
                $this->RenderJSON();
                break;
            case RENDER_NONE:
                break;
        }
        Event::PublishActionHook('/Controller/after/HandleRequest/', array($this));
        Log::corewrite('At the end of method', 2, __CLASS__, __FUNCTION__);
    }
    
    /**
     * Renders data in JSON format
     * @access protected
     */
    protected function RenderJSON()
    {
        Event::PublishActionHook('/Controller/before/RenderJSON/', array($this->render_info));
        if(!is_null($this->render_info))
        {
            echo json_encode($this->render_info);
            Event::PublishActionHook('/Controller/after/RenderJSON/', array($this));
            return true;
        }
        echo json_encode(array());
        Event::PublishActionHook('/Controller/after/RenderJSON/', array($this));
        return false;
    }
    
    /**
     * Sets flash message in Session instance
     * @param string $msg
     * @access protected
     */
    protected function SetFlash($msg)
    {
        $session = Session::getInstance();
        $session->flash = $msg;
    }
    
    /**
     * Renders data HTML page
     * @access protected
     */
    protected function RenderHTML()
    {
        Event::PublishActionHook('/Controller/before/RenderHTML/', array($this));
        foreach(self::$_variables as $name => $value)
        {
            $$name = $value;
        }
        self::$_variables['_MAIN_DIR'] = strtolower(get_class($this));
        self::$_variables['_MAIN_PAGE'] = strtolower($this->method);
        self::$_variables['_secure_post'] = Route::CreateHash(Route::GetSalt());
        include_once(VIEW_DIR.'/'.$this->layout);
        $this->render_status = RENDERED;
        Event::PublishActionHook('/Controller/after/RenderHTML/', array($this));
    }

    public static function yield()
    {
        foreach(self::$_variables as $name => $value)
        {
            $$name = $value;
        }
        include_once(VIEW_DIR.'/'.self::$_variables['_MAIN_DIR'].'/'.self::$_variables['_MAIN_PAGE'].'.view.php');
    }

    public static function SecurePost()
    {
        if(isset(self::$_variables['_secure_post']))
            return self::$_variables['_secure_post'];
        else
            return Route::CreateHash(Route::GetSalt());
    }

    public static function DisplayFlash()
    {
        $session = Session::getInstance();
        if(isset($session->flash))
        {
            if(!isset($params['type']))
                $params['type'] = 'info';

            switch($params['type'])
            {
                case 'info':
                    $flash = '<div style="background-image: url(\'/core/images/info.png\'); background-repeat: no-repeat; background-position: 10px center; font-weight: bold; background-color: #BDE5F8; border: 1px solid #00529B; padding:15px 10px 15px 50px; color: #00529B; margin: 10px 0px; font-family:Arial, Helvetica, sans-serif; font-size:13px;">';
                    break;
                case 'warning':
                    $flash = '<div style="background-image: url(\'/core/images/warning.png\'); background-repeat: no-repeat; background-position: 10px center; font-weight: bold; background-color: #FEEFB3; border: 1px solid #9F6000; padding:15px 10px 15px 50px; color: #9F6000; margin: 10px 0px; font-family:Arial, Helvetica, sans-serif; font-size:13px;">';
                    break;
                case 'success':
                    $flash = '<div style="background-image: url(\'/core/images/success.png\'); background-repeat: no-repeat; background-position: 10px center; font-weight: bold; background-color: #DFF2BF; border: 1px solid #4F8A10; padding:15px 10px 15px 50px; color: #4F8A10; margin: 10px 0px; font-family:Arial, Helvetica, sans-serif; font-size:13px;">';
                    break;
                case 'error':
                    $flash = '<div style="background-image: url(\'/core/images/error.png\'); background-repeat: no-repeat; background-position: 10px center; font-weight: bold; background-color: #FFBABA; border: 1px solid #D8000C; padding:15px 10px 15px 50px; color: #D8000C; margin: 10px 0px; font-family:Arial, Helvetica, sans-serif; font-size:13px;">';
                    break;
                default:
                    $flash = '<div style="background-image: url(\'/core/images/info.png\'); background-repeat: no-repeat; background-position: 10px center; font-weight: bold; background-color: #BDE5F8; border: 1px solid #00529B; padding:15px 10px 15px 50px; color: #00529B; margin: 10px 0px; font-family:Arial, Helvetica, sans-serif; font-size:13px;">';
            }
            $flash .= $session->flash;
            $flash .= '</div>';
            return $flash;
        }
    }

    public static function MethodPut()
    {
        $div = '<div style="display: none">';
        $div .= '<input type="hidden" value="PUT" name="REQUEST_METHOD"/>';
        $div .= '<input type="hidden" value="'.Route::CreateHash(Route::GetSalt()).'" name="token"/>';
        $div .= '</div>';
        return $div;
    }

    /**
     * Sets up {@link $_variables}
     * @access public
     */
    public function Assign($name, $value)
    {
        Event::PublishActionHook('/Controller/before/Assign/', array($this));
        self::$_variables[$name] = $value;
        Event::PublishActionHook('/Controller/after/Assign/', array($this));
    }
    
    /**
     * Redirects controller to other page or controller action
     * if $url is string => Redirect to page
     * if #url is array => $url['action'] $url['params']
     *
     * @access protected
     * @param mixed $url String to redirect to page or array for controller action
     */
    protected function RedirectTo($url)
    {
        Log::corewrite('Redirecting page', 3, __CLASS__, __FUNCTION__);
        if(is_array($url))
        {
            if(isset($url['action'])) // Use this controller and fire action
            {
                Log::corewrite('Actions passed: [%s]', 1, __CLASS__, __FUNCTION__, array($url['action']));
                $params = array();
                if(isset($url['params']))
                    $params = $url['params'];
                $this->HandleRequest($url['action'], $params);
            }
        } else {
            Log::corewrite('URL passed: [%s]', 1, __CLASS__, __FUNCTION__, array($url));
            header('Location: '.$this->GetPageURL().$url);
        }
        Log::corewrite('At end of method...', 2, __CLASS__, __FUNCTION__);
    }
    
    /**
     * Returns page url
     * @access private
     * @return string Page URL
     */
    private function GetPageURL()
    {
        $pageURL = 'http';
        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
            $pageURL .= 's';
        $pageURL .= '://';
        if($_SERVER['SERVER_PORT'] != '80')
            $pageURL .= $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'];
        else
            $pageURL .= $_SERVER['SERVER_NAME'];
        return $pageURL;
    }
    
    /**
     * Gets subdomain from URL
     * @access protected
     * @return string Subdomain of URL
     */
    protected function GetSubDomain()
    {
        $domain = explode('.', $_SERVER['SERVER_NAME']);
        if(count($domain) <= 2)
        {
            return 'www';
        }
        return $domain[0];
    }

    protected function RunTask($task, $params = array())
    {
        $params = array_reverse($params);
        $params[] = 'a';
        $params[] = 'a';
        $params = array_values(array_reverse($params));
        $t = new Task($params, false);
        $t->HandleInput($task);
    }
}
?>