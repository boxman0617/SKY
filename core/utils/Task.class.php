<?php
/**
 * Task Core Class
 *
 * This class handles tasks
 * Tasks are actions defined by a developer that you can put
 * dependencies on
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
 * @link        http://www.codethesky.com/docs/taskclass
 * @package     Sky.Core
 */

import(MODEL_CLASS);

/**
 * Task class
 * Handles tasks are actions defined by a developer that you can pur dependencies on
 * @package Sky.Core.Task
 */
class Task
{
    /**
     * Standard INput
     * @access private
     * @var string
     */
    private $STDIN;
    /**
     * Namespace for tasks
     * @access private
     * @var string
     */
    private $namespace = "global";
    /**
     * Command property
     * @access private
     * @var string
     */
    private $command = null;
    /**
     * List of user defined classes
     * @access private
     * @var array
     */
    private $user_classes = array();
    /**
     * List of user defined functions
     * @access private
     * @var array
     */
    private $user_functions = array();
    /**
     * Static dependencies
     * @access private
     * @var array
     */
    private static $dependents = array();
    /**
     * Parameters
     * @access private
     * @var array
     */
    private $params;
    /**
     * Task files found in task dir
     * @access public
     * @var array
     */
    public $taskfiles = array();
    
    protected $verbose;
    
    /**
     * Constructor sets up {@link $params} and {@link $taskfiles}
     */
    public function __construct($params, $verbose = true)
    {
        Log::corewrite('Starting up tasks', 3, __CLASS__, __FUNCTION__);
        $this->verbose = $verbose;
        unset($params[0], $params[1]);
        foreach($params as $p)
        {
            $tmp = explode("=", $p);
            Log::corewrite('Defining [%s]', 1, __CLASS__, __FUNCTION__, array($tmp[0]));
            if(!defined(strtoupper($tmp[0])))
                define(strtoupper($tmp[0]), $tmp[1]);
        }
        $files = scandir(DIR_LIB_TASKS);
        foreach($files as $file)
        {
            Log::corewrite('Getting task file [%s]', 1, __CLASS__, __FUNCTION__, array($file));
            if(strpos($file, '.task'))
            {
                $this->taskfiles[] = $file;
            }
        }
        Log::corewrite('At the end of method...', 2, __CLASS__, __FUNCTION__);
    }
    
    public function ShowTasks()
    {
        $class_schema = array();
        $function_schema = array();
        foreach($this->taskfiles as $file)
        {
            $content = file_get_contents(DIR_LIB_TASKS.'/'.$file);
            preg_match_all('/class\s([a-zA-Z_]+)/', $content, $classes);
            if(!empty($classes[1]))
            {
                import(DIR_LIB_TASKS.'/'.$file);
                foreach($classes[1] as $class)
                {
                    $class_schema[$class] = get_class_methods($class);
                }
            }
        }
        $tmp_func = array();
        foreach($this->taskfiles as $file)
        {
            $content = file_get_contents(DIR_LIB_TASKS.'/'.$file);
            preg_match_all('/function\s([a-zA-Z_]+)\(\)/', $content, $functions);
            if(isset($functions[1]))
            {
                foreach($functions[1] as $func)
                    $tmp_func[] = $func;
            }
        }
        
        foreach($tmp_func as $k => $f)
        {
            foreach($class_schema as $c => $m)
            {
                if(in_array($f, $m))
                    unset($tmp_func[$k]);
            }
        }
        $function_schema = array_values($tmp_func);
        
        $task_desc = array();
        foreach($this->taskfiles as $file)
        {
            $content = file_get_contents(DIR_LIB_TASKS.'/'.$file);
            preg_match_all('/\/\*\sTASK_DESCRIPTION:\s\[([a-zA-Z]+)\]\s([a-zA-Z0-9\ -]+)\*\//', $content, $desc);
            if(isset($desc[1]) && !empty($desc[1]))
            {
                foreach($desc[1] as $k => $v)
                {
                    $task_desc[$v] = $desc[2][$k];
                }
            }
        }
        
        echo "in (".getcwd()."):\n";
        echo "Available tasks:\n";
        foreach($class_schema as $class => $methods)
        {
            foreach($methods as $m)
            {
                echo " ".$class.":".$m;
                if(isset($task_desc[$m]))
                    echo " - ".$task_desc[$m];
                echo "\n";
            }
        }
        foreach($function_schema as $f)
        {
            echo " ".$f;
            if(isset($task_desc[$f]))
                echo " - ".$task_desc[$f];
            echo "\n";
        }
    }
    
    /**
     * Handles STDIN
     * Sets up
     *  {@link $command}
     *  {@link $user_classes}
     *  {@link $user_functions}
     * @access public
     * @param string $input
     */
    public function HandleInput($input)
    {
        Log::corewrite('Handling input [%s]', 3, __CLASS__, __FUNCTION__, array($input));
        $this->STDIN = trim($input);
        if(strpos($this->STDIN, ":"))
        {
            $tmp = explode(":", $this->STDIN);
            $this->namespace = $tmp[0];
            $this->command = $tmp[1];
        }
        
        if($this->command == null)
            $this->command = $this->STDIN;
            
        foreach($this->taskfiles as $file)
        {
            import(DIR_LIB_TASKS.'/'.$file);
        }
        $classes = get_declared_classes();
        $func = get_defined_functions();
        
        foreach($classes as $k => $class)
        {
            if($class != "Task")
            {
                unset($classes[$k]);
            } else {
                break;
            }
        }
        $this->user_classes = $classes;
        $this->user_functions = $func['user'];
        
        if($this->verbose)
            echo "in (".getcwd()."):\n";
        
        if($this->namespace != "global")
        {
            if(in_array($this->namespace, $this->user_classes))
            {
                $class = $this->namespace;
                $method = $this->command;
                $this->HandleDependencies($class.":".$method);
                $obj = new $class();
                $obj->$method();
            }
            return true;
        } else {
            if(in_array($this->command, $this->user_functions))
            {
                $function = $this->command;
                $this->HandleDependencies($function);
                $function();
            }
        }
    }
    
    /**
     * Handles dependencies, triggered by functions
     * @access private
     * @param string $function
     */
    private function HandleDependencies($function)
    {
        if(!function_exists('RunAction'))
        {
            function RunAction($action)
            {
                if(strpos($action, ":"))
                {
                    $tmp = explode(":", $action);
                    $class = $tmp[0];
                    $method = $tmp[1];
                    $obj = new $class();
                    $obj->$method();
                } else {
                    $action();
                }
                return true;
            }
        }
        if(isset(self::$dependents[$function]))
        {
            if(is_array(self::$dependents[$function]))
            {
                foreach(self::$dependents[$function] as $action)
                {
                    if(!isset(self::$dependents[$action]))
                    {
                        RunAction($action);
                    } else {
                        $this->HandleDependencies($action);
                        RunAction($action);
                    }
                }
            } else {
                $action = self::$dependents[$function];
                if(!isset(self::$dependents[$action]))
                {
                    RunAction($action);
                } else {
                    $this->HandleDependencies($action);
                    RunAction($action);
                }
            }
        }
    }
    
    /**
     * Adds to static {@link $dependents}
     * @access public
     * @param string $function_name
     * @param string $depends_on
     */
    public static function Dependent($function_name, $depends_on)
    {
        self::$dependents[$function_name] = $depends_on;
    }
}
?>