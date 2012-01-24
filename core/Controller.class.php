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
 * @package Sky.Core
 */

import(SMARTY_CLASS);
import(MODEL_CLASS);

/**
 * Constant RENDER_NONE, tells controller not to render anything
 */
define('RENDER_NONE', 0);
/**
 * Constant RENDER_HTML, tells controller to render HTML Smarty Template
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

interface iController
{
    public function __construct();
    public function HandleRequest($method, $pass = null);
}

/**
 * Controller class
 * Handles what to do with models and data then displays it to view or JSON
 * @package Sky.Core.Controller
 */
abstract class Controller implements iController
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
     * Variables to pass to Smarty
     * @access protected
     * @var array
     */
    public $smarty_assign = array();
    /**
     * Layout name
     * @access protected
     * @var string
     */
    protected $layout = 'layout/layout.view.sky';
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
    
    /**
     * Constructor sets up {@link $error} and {@link $params}
     */
    public function __construct()
    {
        Event::PublishActionHook('/Controller/before/__construct/', array($this));
        $this->error = ErrorHandler::Singleton(true);
        foreach($_POST as $key => $value)
        {
            $this->params[$key] = $value;
        }
        Event::PublishActionHook('/Controller/after/__construct/', array($this));
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
                        if(in_array($this->method, $options['only']))
                        {
                            return call_user_func(array($this, $filter));
                        }
                    } else {
                        if(strtolower($this->method) == strtolower($options['only']))
                        {
                            return call_user_func(array($this, $filter));
                        }
                    }
                }
            } else { //Run before filter [No options]
                return call_user_func(array($this, $filter));
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
     * Renders data using Smarty
     * @access protected
     */
    protected function RenderHTML()
    {
        $s_tpl = $this->StartSmarty();
        Event::PublishActionHook('/Controller/before/RenderHTML/', array($this, $s_tpl));
        $s_tpl->assign('MAIN_DIR', strtolower(get_class($this)));
        $s_tpl->assign('MAIN_PAGE', strtolower($this->method));
        $s_tpl->assign('secure_post', Route::CreateHash(Route::GetSalt()));
        $session = Session::getInstance();
        if(isset($session->flash))
            $s_tpl->assign('flash', $session->flash);
        foreach($this->smarty_assign as $name => $value)
        {
            $s_tpl->assign($name, $value);
        }
        
        $s_tpl->display($this->layout);
        $this->render_status = RENDERED;
        Event::PublishActionHook('/Controller/after/RenderHTML/', array($this, $s_tpl));
    }
    
    /**
     * Sets up {@link $smarty_assign}
     * @access public
     */
    public function Assign($name, $value)
    {
        Event::PublishActionHook('/Controller/before/Assign/', array($this));
        $this->smarty_assign[$name] = $value;
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
        if(is_array($url))
        {
            if(isset($url['action'])) // Use this controller and fire action
            {
                $params = array();
                if(isset($url['params']))
                    $params = $url['params'];
                $this->HandleRequest($url['action'], $params);
            }
        } else {
            header('Location: '.$this->GetPageURL().$url);
        }
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
    
    /**
     * Initializes Smarty class
     * @access protected
     * @return object Smarty instance
     */
    protected function StartSmarty()
    {
        $smarty = new Smarty();
        
        $smarty->template_dir = SMARTY_TEMPLATE_DIR;
        $smarty->compile_dir = SMARTY_COMPILE_DIR;
        $smarty->config_dir = SMARTY_CONFIG_DIR;
        $smarty->cache_dir = SMARTY_CACHE_DIR;
        
        return $smarty;
    }
}
?>