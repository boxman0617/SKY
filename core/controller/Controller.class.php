<?php
// ####
// Controller Class
// 
// This class is the C in the MVC model. It allows for the user
// to construct a logic tie between the Views and the Models. 
// Separating the design logic from the HTML renderings
// and the business data logic from the Data models.
//
// @license
// - This file may not be redistributed in whole or significant part, or
// - used on a web site without licensing of the enclosed code, and
// - software features.
//
// @author      Alan Tirado <alan@deeplogik.com>
// @copyright   2013 DeepLogik, All Rights Reserved
//
// @version     0.0.7 Starting from here
// ##

import(MODEL_CLASS);
import(RENDER_CLASS);
import(TASK_CLASS);
import(FILE_CLASS);

define('RENDER_NONE', 'RenderNONE');
define('RENDER_HTML', 'RenderHTML');
define('RENDER_JSON', 'RenderJSON');
define('RENDER_XML', 'RenderXML');

define('RENDERED', true);
define('NOT_RENDERED', false);

define('SUBVIEW_BEFORE', 'before');
define('SUBVIEW_AFTER', 'after');

interface iController
{
    public function __toString();
    public function __construct($params = array());
    public function SetRouterSpecs($specs = array());
    
    public static function CSS($file_path);
    public static function JS($file_path);
    public static function IMG_LOC($file_path);
    
    public static function SecurePost();
    public static function DisplayFlash();
    public static function MethodPut();
    public static function MethodDelete();
    
    public function Render($params);
    public function JSON($info);
    public function HandleRequest($method);
    public function Assign($name, $value);
    public function GetFile($filename);
    public function SetLayout($layout_name);
    public function SetFlash($msg, $type);
    public function RedirectTo($url);
    
    public static function GetPageURL();
    public static function GetSubDomain();
    public static function GetClientIP();
    public static function TruncateString($string, $chars = 100);
}

// ####
// Controller Class
// @desc Handles how a request from the Router Class should be ran.
// @abstract
// @package SKY.Core.Controller
// ##
abstract class Controller extends Base implements iController
{
    protected $before_filter = array();
    protected $after_filter = array();
    protected static $flash_display = array();
    protected $public_location = '/public';
    protected $locations = array(
        'css' => 'stylesheet',
        'js' => 'javascript',
        'img' => 'images'
    );

    private static $_parts = array();
    private static $_parts_pos = 0;
    private static $_parts_tree = array();

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
    public $files = array();
    public static $_variables = array();
    public static $_subview_queue = array(
        'before' => array(),
        'after' => array()
    );
    public $_router_specs = array();
    
    // ####
    // __toString
    // @desc Magic method to convert class to string
    // @return String
    // - Returns the name of the called class
    // @reference [http://php.net/manual/en/function.get-called-class.php]
    // @public
    // @app
    // ##
    public function __toString()
    {
        return get_called_class();
    }

    // ####
    // __construct
    // @desc Constructor method. Gets called at object initialization.
    // @args Array $params @default array()
    // - An array of parameters to be merged with $_POST, $this->params and $_GET
    // @public
    // @app
    // ##
    public function __construct($params = array())
    {
        Log::corewrite('Opening controller [%s]', 3, __CLASS__, __FUNCTION__, array(get_class($this)));
        Event::PublishActionHook('/Controller/before/__construct/', array($this));
        Log::corewrite('Merging $_POST and ::$params', 1, __CLASS__, __FUNCTION__);
        $this->params = array_merge($_POST, $this->params, $_GET, $params);
        $PAYLOAD = file_get_contents('php://input');
        if(!empty($PAYLOAD))
        {
            $JSON = json_decode($PAYLOAD, true);
            if(!is_null($JSON))
                $this->params = array_merge($this->params, $JSON);
        }
        if(FILES_CLEANUP_ENABLED && !empty($_FILES))
        {
            $this->files = File::FilesCleanUp();
            foreach($this->files as $name => $file)
            {
                if(is_array($file))
                {
                    foreach($file as $loc => $data)
                        File::RegisterFile($name.'['.$loc.']', new SingleFile($name.'['.$loc.']', $data));
                } else {
                    File::RegisterFile($name, new SingleFile($name.'['.$loc.']', $file));
                }
            }
        }
        unset($this->params['_query']);
        Event::PublishActionHook('/Controller/after/__construct/', array($this));
        Log::corewrite('At the end of method', 2, __CLASS__, __FUNCTION__);
    }

    // ####
    // __set
    // @desc Getter for assign properties
    // @args Mixed $name
    // - Name of the variable
    // @args Mixed $value
    // - Value of the variable
    // @public
    // @app
    // ##
    public function __set($name, $value)
    {
        $this->Assign($name, $value);
    }

    // ####
    // RenderViewPart
    // @desc Renders ViewParts found under views
    // @args String $view_part
    // - The name of the ViewPart to render
    // - Example: 'header' => '_header.part.php'
    // - Example: 'shared/header' => 'shared/_header.part.php'
    // @return String
    // @static
    // @public
    // @app
    // ##
    public static function RenderViewPart($view_part)
    {
        extract(self::$_variables);
        $file = '';
        if(strpos('/', $view_part) !== false)
        {
            $file = self::$_subview_info['dir'].'/_'.$view_part.'.part.php';
        } else {
            $name = explode('/', $view_part);
            $name[count($name)-1] = '_'.$name[count($name)-1].'.part.php';
            $file = implode('/', $name);
        }
        if(file_exists(DIR_APP_VIEWS.'/'.$file))
            require_once(DIR_APP_VIEWS.'/'.$file);
    }

    // ####
    // RenderPart
    // @desc Renders request specific snippets that can be found in their respective
    // - view
    // @args String $part
    // - The index of the part to be rendered
    // - Example: ':javascript'
    // @return String
    // @static
    // @public
    // @app
    // ##
    public static function RenderPart($part)
    {
        if(array_key_exists($part, self::$_parts))
        {
            foreach(self::$_parts[$part] as $chunk)
                echo $chunk;
        }
    }

    // ####
    // Part
    // @desc Starts the capture of a "Part".
    // @args String $name
    // - Name of the index of the part to be rendered
    // - Example: ':javascript'
    // @static
    // @public
    // @app
    // ##
    public static function Part($name)
    {
        if(!array_key_exists($name, self::$_parts))
            self::$_parts[$name] = array();
        self::$_parts_tree[self::$_parts_pos] = $name;
        self::$_parts_pos++;
        ob_start();
    }

    // ####
    // EndPart
    // @desc Ends the capture of a "Part".
    // @static
    // @public
    // @app
    // ##
    public static function EndPart()
    {
        self::$_parts[self::$_parts_tree[self::$_parts_pos-1]][] = ob_get_clean();
        self::$_parts_pos--;
    }
    
    // ####
    // SetRouterSpecs
    // @desc Assigned the $specs array from the Router instance to the Controller
    // @args Array $specs @default array()
    // - An array of parameters from the Router
    // @public
    // @core
    // ##
    public function SetRouterSpecs($specs = array())
    {
        $this->_router_specs = $specs;
    }
    
    // ####
    // CSS
    // @desc Create a CSS link to a file according to the path locations set in the Controller child
    // @args String $file_path
    // - The path after the base path set by the app in the Controller to a CSS file
    // - Example: 'myscript.css'
    // @return String
    // - Example: <link href="/blah/public/css/styles.css" rel="stylesheet">
    // @static
    // @public
    // @app
    // ##
    public static function CSS($file_path)
    {
        return '<link href="'.self::CSS_LOC($file_path).'" rel="stylesheet">';
    }
    
    // ####
    // CSS_LOC
    // @desc Create a CSS link to a file according to the path locations set in the Controller child
    // @args String $file_path
    // - The path after the base path set by the app in the Controller to a CSS file
    // - Example: 'myscript.css'
    // @return String
    // - Example: /blah/public/css/styles.css
    // @static
    // @public
    // @app
    // ##
    public static function CSS_LOC($file_path)
    {
        $class = get_called_class();
        $loc = self::CachedLocation($class);
        return str_replace('//', '/', BASE_GLOBAL_URL.$loc['public_location'].'/'.$loc['locations']['css'].'/'.$file_path);
    }
    
    // ####
    // CachedLocation
    // @desc This will create a cache in the Base::$_share property if not found and return the
    // - correct array of locations
    // @args String $class_name
    // @return Array
    // @static
    // @private
    // @app
    // ##
    private static function CachedLocation($class_name)
    {
        if(!array_key_exists('LOC_CLASSES', self::$_share))
            self::$_share['LOC_CLASSES'] = array();
        if(!array_key_exists($class_name, self::$_share['LOC_CLASSES']))
        {
            $obj = new $class_name();
            self::$_share['LOC_CLASSES'][$class_name] = array(
                'locations' => $obj->locations,
                'public_location' => $obj->public_location
            );
        }
        return self::$_share['LOC_CLASSES'][$class_name];
    }
    
    // ####
    // JS
    // @desc Create a JS script to a file according to the path locations set in the Controller child
    // @args String $file_path
    // - The path after the base path set by the app in the Controller to a JS file
    // - Example: 'myscript.js'
    // @return String
    // - Example: <script src="/blah/public/js/myscript.js" type="text/javascript" charset="utf-8"></script>
    // @static
    // @public
    // @app
    // ##
    public static function JS($file_path)
    {
        return '<script src="'.self::JS_LOC($file_path).'" type="text/javascript" charset="utf-8"></script>';
    }
    
    // ####
    // JS_LOC
    // @desc Get JS location according to the path locations set in the COntroller child
    // @args String $file_path
    // - The path after the base path set by the app in the Controller to a JS file
    // - Example: 'myscript.js'
    // @return String
    // - Example: /blah/public/js/myscript.js
    // @static
    // @public
    // @app
    // ##
    public static function JS_LOC($file_path)
    {
        $class = get_called_class();
        $loc = self::CachedLocation($class);
        return str_replace('//', '/', BASE_GLOBAL_URL.$loc['public_location'].'/'.$loc['locations']['js'].'/'.$file_path);
    }
    
    // ####
    // IMG_LOC
    // @desc Gets an image path according to the location of where images are, set in the Controller child
    // @args String $file_path
    // - The path after the base path set by the app in the Controller to an image file
    // - Example: 'myimage.png'
    // @return String
    // - Example: /blah/public/images/myimage.png
    // @static
    // @public
    // @app
    // ##
    public static function IMG_LOC($file_path)
    {
        $class = get_called_class();
        $loc = self::CachedLocation($class);
        return str_replace('//', '/', BASE_GLOBAL_URL.$loc['public_location'].'/'.$loc['locations']['img'].'/'.$file_path);
    }

    // ####
    // Render
    // @desc Adds app level control of how data is rendered.
    // @args Array $params
    // - By passing an array of options, the way data is rendered can be controlled.
    // - Examples:
    // - 'action' => 'NewPage'
    // - 'action' => 'NewPage', 'params' => array('param1' => 'foo', ...)
    // - 'flag' => RENDER_NONE
    // - 'flag' => RENDER_JSON, 'info' => array('param1' => 'foo', ...)
    // - 'view' => 'NewView'
    // - 'file' => 'FileName.pdf'
    // @public
    // @app
    // ##
    public function Render($params)
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
            self::$_subview_info['view'] = $params['view'];
            if(isset($params['dir']))
                self::$_subview_info['dir'] = $params['dir'];
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
    
    // ####
    // JSON
    // @desc Shorthand method for Render(RENDER_JSON). Echos $info in a JSON format.
    // @args Mixed $info
    // - $info argument gets turned to JSON using json_encode function
    // @reference [http://php.net/manual/en/function.json-encode.php]
    // @public
    // @app
    // @dependent ::Render
    // ##
    public function JSON($info)
    {
        $this->Render(array(
            'flag' => RENDER_JSON,
            'info' => $info
        ));
    }

    // ####
    // GetFile
    // @desc Forces a file download to be downloaded.
    // @args String $filename
    // - $filename Name of (with path) of file that must be downloaded.
    // @public
    // @app
    // ##
    public function GetFile($filename)
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

    // ####
    // SetLayout
    // @desc Allows for user to use a different Layout view then the default one.
    // @args String $layout_name
    // - Name of the layout file that should be used.
    // @public
    // @lookat ::$render_info['layout']
    // @app
    // ##
    public function SetLayout($layout_name)
    {
        $this->render_info['layout'] = $layout_name;
    }

    // ####
    // HandleBeforeFilters
    // @desc Will run any filter methods before the main action is ran.
    // @lookat ::$before_filter
    // @dependent ::DRYRunFilter
    // @protected
    // @core
    // ##
    protected function HandleBeforeFilters()
    {
        $this->DRYRunFilter($this->before_filter);
    }

    // ####
    // DRYRunFilter
    // @desc Calls filter method by calling call_user_func.
    // @args Array $filters
    // - array(
    // -     'FilterMethod' => true, // Will run on all actions
    // -     'FilterMethod2' => array(
    // -         'only' => 'IndexMethod' // Will ONLY run when action IndexMethod is called
    // -     )
    // - )
    // - Options:
    // - - only => Will run filter ONLY when action specified is called
    // - - exclude => Will run on all actions, EXCEPT the ones defined undet this array
    // - - => Can run in ALL actions if left blank
    // @private
    // @core
    // ##
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

    // ####
    // HandleAfterFilters
    // @desc Will run any filter methods after the main action is ran.
    // @lookat ::$after_filter
    // @dependent ::DRYRunFilter
    // @protected
    // @core
    // ##
    protected function HandleAfterFilters()
    {
        $this->DRYRunFilter($this->after_filter);
    }

    // ####
    // HandleRequest
    // @desc When a request comes in, it is first handled by the Router class.
    // - Then it passes what method should be ran to this method. It attempts
    // - to find the method in the child class and run it. After, it finds
    // - if it needs to render anything outside of this class and delegates
    // - that task to it's correct method.
    // @args String $method
    // - Name of what method to run in child object
    // @calledby Router::RunFollow
    // @public
    // @core
    // ##
    public function HandleRequest($method)
    {
        Log::corewrite('Handling request by [%s]', 3, __CLASS__, __FUNCTION__, array($method));
        Event::PublishActionHook('/Controller/before/HandleRequest/', array($this));
        $this->render_info['method']    = $method;
        self::$_subview_info['dir']     = strtolower(get_class($this));
        self::$_subview_info['view']    = strtolower($this->render_info['method']);
        $this->Assign('_METHOD_', strtolower($method));
        unset($method);

        // Run Action Determined by Router
        $this->HandleBeforeFilters();
        call_user_func(array($this, $this->render_info['method']));
        $this->HandleAfterFilters();

        // Render
        if($this->render_info['render'] != RENDER_NONE)
        {
            $class = $this->render_info['render'];
            $obj = new $class($this);
            $obj->Render($this->render_info);
        }
        
        Event::PublishActionHook('/Controller/after/HandleRequest/', array($this));
        Benchmark::Mark('rendered');
        Log::corewrite('Rendering elapsed time: [%s seconds]', 3, __CLASS__, __FUNCTION__, array(Benchmark::ElapsedTime(null, 'rendered')));
        Log::corewrite('At the end of method', 2, __CLASS__, __FUNCTION__);
    }

    // ####
    // SetFlash
    // @desc Sets flash message in Session instance
    // @args String $msg
    // - String message that will show up in the flash message
    // @args String $type
    // - 4 types of Flash messages:
    // - info -> To display helpful information
    // - warning -> To display warnings
    // - success -> To display when user accomplishes something
    // - error -> To display errors
    // @public
    // @dependent Session
    // @app
    // ##
    public function SetFlash($msg, $type)
    {
        $session = Session::getInstance();
        $session->flash = $msg;
        $session->flash_type = $type;
    }

    // ####
    // GetStaticProperty
    // @desc Attempts to get a static property from itself
    // @public
    // @static
    // @core
    // ##
    public static function GetStaticProperty($property)
    {
        if(property_exists(get_called_class(), $property))
        {
            $vars = get_class_vars(get_called_class());
            return $vars[$property];
        }
    }

    // ####
    // RenderSubView
    // @desc This static method allows the view of the action to be included
    // - inside the current Layout view.
    // @public
    // @static
    // @app
    // ##
    public static function RenderSubView()
    {
        if(!is_null(RenderHTML::$_subview_render_cache))
        {
            echo RenderHTML::$_subview_render_cache;
            return true;
        }
        extract(self::$_variables);
        foreach(self::$_subview_queue['before'] as $subview)
        {
            Log::corewrite('Opening subviewed page: [%s/%s]', 1, __CLASS__, __FUNCTION__, array($subview['view_dir'], $subview['view_page']));
            include_once(DIR_APP_VIEWS.'/'.$subview['view_dir'].'/'.$subview['view_page'].'.view.php');
        }
        Log::corewrite('Opening page: [%s/%s]', 1, __CLASS__, __FUNCTION__, array(self::$_subview_info['dir'], self::$_subview_info['view']));
        $view = DIR_APP_VIEWS.'/'.self::$_subview_info['dir'].'/'.self::$_subview_info['view'].'.view.php';
        if(file_exists($view))
            include_once($view);
        else
        {
            $msg = 'Looks like there is no view set up for this Route yet.<br>Create view ['.self::$_subview_info['view'].'.view.php] in the following directory: ['.self::$_subview_info['dir'].']';
            Error::LogError('VIEW NOT FOUND',$msg, $view, 0);
            if($GLOBALS['ENV'] !== 'PRO')
                Error::BuildMessage('VIEW NOT FOUND', $msg, $view, 0, 'f293ff');
            exit();
        }
        foreach(self::$_subview_queue['after'] as $subview)
        {
            Log::corewrite('Opening subviewed page: [%s/%s]', 1, __CLASS__, __FUNCTION__, array($subview['view_dir'], $subview['view_page']));
            include_once(DIR_APP_VIEWS.'/'.$subview['view_dir'].'/'.$subview['view_page'].'.view.php');
        }
    }

    // ####
    // SecurePost
    // @desc If index '_secure_post' is found in the $_variables property, it will be returned.
    // - inside the current Layout view.
    // @dependent Router::GetSalt
    // @return String
    // @public
    // @static
    // @app
    // ##
    public static function SecurePost()
    {
        if(isset(self::$_variables['_secure_post']))
            return self::$_variables['_secure_post'];
        else
            return Router::CreateHash(Router::GetSalt());
    }

    // ####
    // DisplayFlash
    // @desc Outputs to the current view an HTML flash notice.
    // @public
    // @static
    // @app
    // ##
    public static function DisplayFlash()
    {
        $session = Session::getInstance();
        if(isset($session->flash))
        {
            if(!isset($session->flash_type))
                $session->flash_type = 'info';
            
            if(empty(static::$flash_display))
            {
                switch($session->flash_type)
                {
                    case 'info':
                        $flash = '<div style="background-image: url(\''.self::IMG_LOC('flash/info.png').'\'); background-repeat: no-repeat; background-position: 10px center; font-weight: bold; background-color: #BDE5F8; border: 1px solid #00529B; padding:15px 10px 15px 50px; color: #00529B; margin: 10px 0px; font-family:Arial, Helvetica, sans-serif; font-size:13px;">';
                        break;
                    case 'warning':
                        $flash = '<div style="background-image: url(\''.self::IMG_LOC('flash/warning.png').'\'); background-repeat: no-repeat; background-position: 10px center; font-weight: bold; background-color: #FEEFB3; border: 1px solid #9F6000; padding:15px 10px 15px 50px; color: #9F6000; margin: 10px 0px; font-family:Arial, Helvetica, sans-serif; font-size:13px;">';
                        break;
                    case 'success':
                        $flash = '<div style="background-image: url(\''.self::IMG_LOC('flash/success.png').'\'); background-repeat: no-repeat; background-position: 10px center; font-weight: bold; background-color: #DFF2BF; border: 1px solid #4F8A10; padding:15px 10px 15px 50px; color: #4F8A10; margin: 10px 0px; font-family:Arial, Helvetica, sans-serif; font-size:13px;">';
                        break;
                    case 'error':
                        $flash = '<div style="background-image: url(\''.self::IMG_LOC('flash/error.png').'\'); background-repeat: no-repeat; background-position: 10px center; font-weight: bold; background-color: #FFBABA; border: 1px solid #D8000C; padding:15px 10px 15px 50px; color: #D8000C; margin: 10px 0px; font-family:Arial, Helvetica, sans-serif; font-size:13px;">';
                        break;
                    default:
                        $flash = '<div style="background-image: url(\''.self::IMG_LOC('flash/info.png').'\'); background-repeat: no-repeat; background-position: 10px center; font-weight: bold; background-color: #BDE5F8; border: 1px solid #00529B; padding:15px 10px 15px 50px; color: #00529B; margin: 10px 0px; font-family:Arial, Helvetica, sans-serif; font-size:13px;">';
                }
            } else {
                if(isset(static::$flash_display[$session->flash_type]))
                    $flash = static::$flash_display[$session->flash_type];
                else
                {
                    trigger_error("No custom flash type set for [".$session->flash_type."]", E_USER_NOTICE);
                    return false;
                }
            }
            $flash .= $session->flash;
            $flash .= '</div>';
            echo $flash;
            unset($session->flash);
            unset($session->flash_type);
        }
    }

    // ####
    // MethodPut
    // @desc Outputs to current view the HTML that is needed to submit a form using the PUT method
    // @return String
    // @public
    // @static
    // @app
    // @dependent ::Method
    // ##
    public static function MethodPut()
    {
        return self::Method('PUT');
    }
    
    // ####
    // MethodDelete
    // @desc Outputs to current view the HTML that is needed to submit a form using the DELETE method
    // @return String
    // @public
    // @static
    // @app
    // @dependent ::Method
    // ##
    public static function MethodDelete()
    {
        return self::Method('DELETE');
    }
    
    // ####
    // MethodDelete
    // @desc DRY method for outputting the required HTML for a form to be submitted
    // - as something other then GET or POST
    // @args String $type
    // - Method type
    // @return String
    // @private
    // @static
    // @app
    // @calledby ::MethodPut
    // @calledby ::MethodDelete
    // ##
    private static function Method($type)
    {
        $div = '<div style="display: none">';
        $div .= '<input type="hidden" value="'.strtoupper($type).'" name="REQUEST_METHOD"/>';
        $div .= '<input type="hidden" value="'.Router::CreateHash(Router::GetSalt()).'" name="token"/>';
        $div .= '</div>';
        return $div;
    }

    // ####
    // Assign
    // @desc Appends a variable to the $_variables property.
    // @args String $name
    // - Name of the variable
    // @args String $value
    // - Value of the variable
    // @public
    // @app
    // ##
    public function Assign($name, $value)
    {
        Event::PublishActionHook('/Controller/before/Assign/', array($this));
        self::$_variables[$name] = $value;
        Event::PublishActionHook('/Controller/after/Assign/', array($this));
    }

    // ####
    // RedirectTo
    // @desc Redirects controller to other page or controller action.
    // @args Mixed $url
    // - If is String -> Redirect to URL
    // - If is Array -> Redirect to $url['action'] (optional $url['params'])
    // @dependent ::HandleRequest
    // @public
    // @app
    // ##
    public function RedirectTo($url)
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
            header('Location: '.self::GetPageURL().$url);
            exit();
        }
        Log::corewrite('At end of method...', 2, __CLASS__, __FUNCTION__);
    }

    // ####
    // GetPageURL
    // @desc Get full page URL
    // @return String
    // @public
    // @static
    // @app
    // ##
    public static function GetPageURL()
    {
        $pageURL = 'http';
        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
            $pageURL .= 's';
        $pageURL .= '://';
        // if($_SERVER['SERVER_PORT'] != '80')
        //     $pageURL .= $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'];
        // else
            $pageURL .= $_SERVER['SERVER_NAME'];
        return $pageURL;
    }

    // ####
    // GetSubDomain
    // @desc Gets subdomain from URL
    // @return String
    // @public
    // @static
    // @app
    // ##
    public static function GetSubDomain()
    {
        $domain = explode('.', $_SERVER['SERVER_NAME']);
        if(count($domain) <= 2)
        {
            return 'www';
        }
        return $domain[0];
    }
    
    // ####
    // GetClientIP
    // @desc Get the client's IP address
    // @return String
    // @public
    // @static
    // @app
    // ##
    public static function GetClientIP()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
        //check ip from share internet
        {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        //to check ip is pass from proxy
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else
        {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    // ####
    // RunTask
    // @desc Hack-ish way of running Tasks.
    // @args String $task
    // - Task command
    // @args Array $params @default array()
    // - List of parameters for Task
    // @dependent Task
    // @public
    // @app
    // ##
    public function RunTask($task, $params = array())
    {
        //Hack-ish way of running Command line Task
        $params = array_reverse($params);
        $params[] = 'dont';
        $params[] = 'need';
        $params = array_values(array_reverse($params));
        $t = new Task($params, false);
        $t->HandleInput($task);
    }

    // ####
    // TruncateString
    // @desc Truncate a string to a certain amount of characters then add the hellip [...]
    // @args String $string
    // - String to be truncated
    // @args Integer $chars @default 100
    // - Number of characters allowed before the string is truncated
    // @return String
    // @public
    // @static
    // @app
    // ##
    public static function TruncateString($string, $chars = 100)
    {
        preg_match('/^.{0,' . $chars. '}/', $string, $matches);
        $new_string = $matches[0];
        if($new_string === $string)
        {
            return $string;
        } else {
            return $new_string.'&hellip;';
        }
    }
}
?>