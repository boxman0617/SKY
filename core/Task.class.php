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
 * @author Alan Tirado <root@deeplogik.com>
 * @copyright 2012 DeepLogiK, All Rights Reserved
 * @license http://www.deeplogik.com/sky/legal/license
 * @link http://www.deeplogik.com/sky/index
 * @version 1.0
 * @package Sky.Core
 */

require_once(dirname(__FILE__).'/../configs/defines.php');
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
    
    /**
     * Constructor sets up {@link $params} and {@link $taskfiles}
     */
    public function __construct($params)
    {
        unset($params[0], $params[1]);
        foreach($params as $p)
        {
            $tmp = explode("=", $p);
            if(!defined(strtoupper($tmp[0])))
                define(strtoupper($tmp[0]), $tmp[1]);
        }
        $files = scandir(TASKS_DIR);
        foreach($files as $file)
        {
            if(strpos($file, '.task'))
            {
                $this->taskfiles[] = $file;
            }
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
            import(TASKS_DIR.'/'.$file);
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