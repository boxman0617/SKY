<?php
/**
 * Controller Core Class
 *
 * This class is the C in the MVC model. It allows for the user
 * to construct a logic tie between the Views and the Models. 
 * Separating the design logic from the HTML renderings
 * and the business data logic from the Data models.
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
 * @link        http://www.codethesky.com/docs/controllerclass
 * @package     Sky.Core
 */

import(MODEL_CLASS);
import(RENDER_CLASS);

define('RENDER_NONE', 'RenderNONE');
define('RENDER_HTML', 'RenderHTML');
define('RENDER_JSON', 'RenderJSON');
define('RENDER_XML', 'RenderXML');

define('RENDERED', true);
define('NOT_RENDERED', false);

define('SUBVIEW_BEFORE', 'before');
define('SUBVIEW_AFTER', 'after');

/**
 * Controller class
 * Handles what to do with models and data then displays it to view or JSON
 * @package Sky.Core.Controller
 */
abstract class Controller
{
    protected $before_filter = array();
    protected $after_filter = array();

    protected $render_info = array(
        'layout' => 'layout/layout.view.php',
        'method' => null,
        'status' => NOT_RENDERED,
        'render' => RENDER_HTML
    );
    public static $_subview_info = array(
        'dir' => null,
        'view' => null
    );

    public $params = array();
    public static $_variables = array();
    public static $_subview_queue = array(
        'before' => array(),
        'after' => array()
    );

    /**
     * Constructor method. Gets called at object initialization.
     */
    public function __construct($params = array())
    {
        Log::corewrite('Opening controller [%s]', 3, __CLASS__, __FUNCTION__, array(get_class($this)));
        Event::PublishActionHook('/Controller/before/__construct/', array($this));
        Log::corewrite('Merging $_POST and ::$params', 1, __CLASS__, __FUNCTION__);
        $this->params = array_merge($_POST, $this->params, $_GET, $params);
        unset($this->params['_query']);
        Event::PublishActionHook('/Controller/after/__construct/', array($this));
        Log::corewrite('At the end of method', 2, __CLASS__, __FUNCTION__);
    }

    /**
     * Adds user level control of how data is rendered.
     *
     * By passing an array of options, the way data
     * is rendered can be controlled.
     * Options:
     *   'action' => 'NewPage'
     *   'action' => 'NewPage', 'params' => array('param1' => 'foo', ...)
     *   'flag' => RENDER_NONE
     *   'flag' => RENDER_JSON, 'info' => array('param1' => 'foo', ...)
     *   'view' => 'NewView'
     *   'file' => 'FileName.pdf'
     *
     * @param array $params Array of options.
     */
    protected function Render($params)
    {
        if(isset($params['action']))
        {
            if(isset($params['params']))
                $this->RedirectTo(array('action' => $params['action'], 'params' => $params['params']));
            else
                $this->RedirectTo(array('action' => $params['action']));
        }
        elseif(isset($params['flag']))
        {
            $this->render_info['render'] = $params['flag'];
            if(isset($params['info'])) $this->render_info['info'] = $params['info'];
        }
		elseif(isset($params['view']))
		{
			$this->Assign('MAIN_PAGE', $params['view']);
		}
        elseif(isset($params['file']))
        {
            $this->GetFile($params['file']);
        }
        elseif(isset($params['subview']))
        {
            self::$_subview_queue[$params['subview']][] = array(
                'view_page' => strtolower($this->render_info['method']),
                'view_dir' => strtolower(get_called_class())
            );
            $this->render_info['render'] = RENDER_NONE;
        }
    }

    protected function JSON($info)
    {
        $this->Render(array(
            'flag' => RENDER_JSON,
            'info' => $info
        ));
    }

    /**
     * Forces file download.
     *
     * Allows a file to be downloaded.
     * After file is downloaded, nothing else is rendered.
     *
     * @param string $filename Path to file.
     */
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
        $this->render_info['render'] = RENDER_NONE;
    }

    /**
     * Sets up Layout view.
     *
     * Allows for user to use a different Layout view then
     * the default one.
     *
     * @param string $layout_name Name of Layout view.
     */
    protected function SetLayout($layout_name)
    {
        $this->render_info['layout'] = $layout_name;
    }

    /**
     * Will handle any before filters applied to action.
     *
     * Will run any filter methods before the main action
     * is ran.
     */
    protected function HandleBeforeFilters()
    {
        $this->DRYRunFilter($this->before_filter);
    }

    /**
     * DRY filter method
     *
     * Calls filter method by calling call_user_func.
     * Options:
     *  array(
     *      'FilterMethod' => true, //Will always run
     *      'FilterMethod2' => array(
     *          'only' => 'IndexMethod' //Will ONLY run when IndexMethod method is called
     *      )
     *  )
     * @access private
     * @param array $filter Array of methods to be called.
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
                        $options['only'] = array_map('strtolower', $options['only']);
                        if(in_array(strtolower($this->render_info['method']), $options['only']))
                        {
                            call_user_func(array($this, $filter));
                        }
                    } else {
                        if(strtolower($this->render_info['method']) == strtolower($options['only']))
                        {
                            call_user_func(array($this, $filter));
                        }
                    }
                } elseif (isset($options['exclude'])) {
                    if(is_array($options['exclude']))
                    {
                        $options['exclude'] = array_map('strtolower', $options['exclude']);
                        if(!in_array(strtolower($this->render_info['method']), $options['exclude']))
                        {
                            call_user_func(array($this, $filter));
                        }
                    } else {
                        if(strtolower($this->render_info['method']) != strtolower($options['exclude']))
                        {
                            call_user_func(array($this, $filter));
                        }
                    }
                }
            } else { //No options
                call_user_func(array($this, $filter));
            }
        }
    }

    /**
     * Will handle any after filters applied to action.
     *
     * Will run any filter methods after the main action
     * is ran.
     */
    protected function HandleAfterFilters()
    {
        $this->DRYRunFilter($this->after_filter);
    }

    /**
     * Decides how to render controller and runs child method
     *
     * When a request comes in, it is first handled by the Router class.
     * Then it passes what method should be ran to this method. It attempts
     * to find the method in the child class and run it. After, it finds
     * if it needs to render anything outside of this class and delegates
     * that task to it's correct method.
     *
     * @param string $method Name of child method to run.
     * @param mixed $pass default null Array of parameters to pass to child method.
     */
    public function HandleRequest($method)
    {
        Log::corewrite('Handling request by [%s]', 3, __CLASS__, __FUNCTION__, array($method));
        Event::PublishActionHook('/Controller/before/HandleRequest/', array($this));
        $this->render_info['method'] = $method;
        self::$_subview_info['dir'] = strtolower(get_class($this));
        self::$_subview_info['view'] = strtolower($this->render_info['method']);
        unset($method);

        // Run Action Determined by Router
        $this->HandleBeforeFilters();
        call_user_func(array($this, $this->render_info['method']));
        $this->HandleAfterFilters();

        // Render
        if($this->render_info['render'] != RENDER_NONE)
        {
            $class = $this->render_info['render'];
            $obj = new $class();
            $obj->Render($this->render_info);
        }

        Event::PublishActionHook('/Controller/after/HandleRequest/', array($this));
        Log::corewrite('At the end of method', 2, __CLASS__, __FUNCTION__);
    }

    /**
     * Sets flash message in Session instance
     *
     * @param string $msg Text that will be shown in Flash message.
     */
    protected function SetFlash($msg)
    {
        $session = Session::getInstance();
        $session->flash = $msg;
    }

    /**
     * Includes action view inside Layout view.
     *
     * This static method allows the view of the action to be included
     * inside the current Layout view.
     */
    public static function RenderSubView()
    {
        extract(self::$_variables);
        foreach(self::$_subview_queue['before'] as $subview)
        {
            Log::corewrite('Opening subviewed page: [%s/%s]', 1, __CLASS__, __FUNCTION__, array($subview['view_dir'], $subview['view_page']));
            include_once(DIR_APP_VIEWS.'/'.$subview['view_dir'].'/'.$subview['view_page'].'.view.php');
        }
        Log::corewrite('Opening page: [%s/%s]', 1, __CLASS__, __FUNCTION__, array(self::$_subview_info['dir'], self::$_subview_info['view']));
        include_once(DIR_APP_VIEWS.'/'.self::$_subview_info['dir'].'/'.self::$_subview_info['view'].'.view.php');
        foreach(self::$_subview_queue['after'] as $subview)
        {
            Log::corewrite('Opening subviewed page: [%s/%s]', 1, __CLASS__, __FUNCTION__, array($subview['view_dir'], $subview['view_page']));
            include_once(DIR_APP_VIEWS.'/'.$subview['view_dir'].'/'.$subview['view_page'].'.view.php');
        }
    }

    /**
     * Returns a secret hash to enable secure posting.
     *
     * If index '_secure_post' is found in the $_variables property, it will be returned.
     * Else it will return a new hash created by the Router class.
     *
     * @return String
     */
    public static function SecurePost()
    {
        if(isset(self::$_variables['_secure_post']))
            return self::$_variables['_secure_post'];
        else
            return Router::CreateHash(Router::GetSalt());
    }

    /**
     * Outputs to the current view an HTML flash notice.
     */
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
            echo $flash;
        }
    }

    /**
     * Outputs to current view what is needed for a secure post.
     */
    public static function MethodPut()
    {
        $div = '<div style="display: none">';
        $div .= '<input type="hidden" value="PUT" name="REQUEST_METHOD"/>';
        $div .= '<input type="hidden" value="'.Router::CreateHash(Router::GetSalt()).'" name="token"/>';
        $div .= '</div>';
        return $div;
    }

    /**
     * Appends a variable to the $_variables property.
     */
    public function Assign($name, $value)
    {
        Event::PublishActionHook('/Controller/before/Assign/', array($this));
        self::$_variables[$name] = $value;
        Event::PublishActionHook('/Controller/after/Assign/', array($this));
    }

    /**
     * Redirects controller to other page or controller action.
     *
     * Redirects controller to other page or controller action.
     * if $url is string => Redirect to page
     * if #url is array => $url['action'] $url['params']
     *
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
            exit();
        }
        Log::corewrite('At end of method...', 2, __CLASS__, __FUNCTION__);
    }

    /**
     * Returns page URL
     *
     * @return string
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
     *
     * @return string
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
     * Hack-ish way of running Tasks.
     *
     * @see Task::__construct()
     *
     * @param string $task   Name of task to run.
     * @param array  $params Array of parameters to use for task.
     */
    protected function RunTask($task, $params = array())
    {
        //Hack-ish way of running Command line Task
        $params = array_reverse($params);
        $params[] = 'dont';
        $params[] = 'need';
        $params = array_values(array_reverse($params));
        $t = new Task($params, false);
        $t->HandleInput($task);
    }

    public static function TruncateString($string, $chars = 100)
    {
        preg_match('/^.{0,' . $chars. '}(?:.*?)\b/iu', $string, $matches);
        $new_string = $matches[0];
        return ($new_string === $string) ? $string : $new_string . '&hellip;';
    }
}
?>