<?php
/**
 * Task Core Class
 * 
 * This class handles tasks
 * Tasks are actions defined by a developer that you can put
 * dependencies on
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
 * @package     Core\Utils\Task
 * @version     1.0.0
 */

/**
 * All tasks must implement this interface
 */
interface Task
{
    /**
     * Use this method to declare all you dependencies
     * 
     * Internally, use the TaskManager::DependentOn method
     * to register a dependency
     */
    public function DeclareDependencies();
}

/**
 * This class handles all Task related tasks
 * 
 * This class is automated using the skyt command in the
 * command line, but can be used programmatically by doing the following:
 * 
 *      <?php
 *      SkyL::Import(SkyDefines::Call('TASKMANAGER_CLASS'));
 *      $tm = new TaskManager();
 *      $tm->LoadTask('MyTask');
 *      $tm->Run(); // To run all methods
 *      $tm->Run('MyMethod'); // To run a single method
 *      ?>
 */
class TaskManager
{
    /**
     * Full path to task file
     * @var string
     */
    private $_loaded_task;

    /**
     * Name of task class
     * @var string
     */
    private $_loaded_class;

    /**
     * Verbose flag
     * @var boolean
     */
    private $_v = false; // Verbose

    /**
     * SkyCLI object
     * @var SkyCLI
     */
    private $_cli;

    /**
     * Options for task
     * @var string[]
     */
    private $_options = array();

    /**
     * Array of depdendencies
     * @var string[]
     */
    private static $_dependencies = array();

    /**
     * String buffer for nesting
     * @var string
     */
    private $_lvl = '';

    const TASKFILEENDING = '.task.php';

    /**
     * Options
     *
     * This is the way you pass arguments to your
     * taks. If any of the options passed have a
     * '=' in them, this method will split them and
     * set the left hand side as the key, and the other
     * as the value.
     *
     * Example:
     * hello=world will be turned to array('hello' => 'world')
     *
     * @param mixed[] $options An array of options that will be
     * passed to the task
     */
    public function Options(array $options)
    {
        foreach($options as $key => $option)
        {
            if(strpos($option, '=') !== false)
            {
                $tmp = explode('=', $option);
                $this->_options[$tmp[0]] = $tmp[1];
            } else {
                if(is_numeric($key))
                    $this->_options[] = $option;
                else
                    $this->_options[$key] = $option;
            }
        }
    }

    /**
     * Verbose
     * 
     * Use this method echo out internal messages
     * throughout the life of the tasks
     * 
     * @param SkyCLI $cli Needs an SkyCLI object to be able to execute
     * command line methods
     */
    public function Verbose(SkyCLI $cli)
    {
        $this->_cli = $cli;
        $this->_v = true;
    }

    /**
     * Task loader
     * 
     * This method will find and "load" a task file
     * from either the CORE tasks directory or the
     * APP tasks directory.
     * 
     * @param string $task Name of task
     * @throws Exception If task is not found
     * @return TaskManager Itself
     */
    public function LoadTask($task)
    {
        $tasks = array_map(function($t) {
            return strtolower(str_replace(TaskManager::TASKFILEENDING, '', $t));
        }, array_merge(
            array_diff(scandir(SkyDefines::Call('DIR_LIB_TASKS')), array('.', '..')),
            array_diff(scandir(SkyDefines::Call('SKYCORE_LIB').'/tasks'), array('.', '..'))
        ));

        $this->VerboseOut('# Seeking Task file...');
        if(in_array(strtolower($task), $tasks))
        {
            $this->_loaded_class = $task;
            if(is_file(SkyDefines::Call('DIR_LIB_TASKS').'/'.self::TaskFileName($task)))
                $this->_loaded_task = SkyDefines::Call('DIR_LIB_TASKS').'/'.self::TaskFileName($task);
            else
                $this->_loaded_task = SkyDefines::Call('SKYCORE_LIB').'/tasks/'.self::TaskFileName($task);
            $this->VerboseOut('# Task loaded ['.$task.']');
        } else {
            $this->VerboseErr('Task ['.$task.'] not found!');
            throw new Exception('Task not found ['.$task.']');
        }
        return $this; // Chainable
    }

    /**
     * Public Run method
     *
     * This is the outward facing run method that will determine
     * how to process the request it is given by handing it
     * off to the correct sub method.
     *
     * @param mixed $method If a method name is passed, it will then hand it off to ::RunMethod()
     * else it will use ::RunAllMethods()
     * @return void
     */
    public function Run($method = false)
    {
        $this->VerboseOut('# Running...');
        SkyL::Import($this->_loaded_task);

        $class = $this->_loaded_class;
        $TASK = new $class();
        if(!empty($this->_options))
        {
            if(!property_exists($TASK, 'Options'))
                $this->VerboseErr('Task ['.$task.'] needs the Options public property.');
            $TASK->Options = $this->_options;
        }
        $this->VerboseOut('# Loading dependencies...');
        $TASK->DeclareDependencies();

        if($method === false)
            return $this->RunAllMethods($TASK);
        return $this->RunMethod($TASK, $method);
    }

    /**
     * Outputs messages
     *
     * If ::$_v is set to true, messages passed to it
     * will be printed to the screen
     *
     * @param string $msg
     */
    private function VerboseOut($msg)
    {
        if($this->_v)
            $this->_cli->PrintLn($msg);
    }

    /**
     * Outputs Errors and die
     *
     * If ::$_v is set to true, error messages passed to it
     * will be printed to the screen then the script
     * will exit
     *
     * @param string $err
     */
    private function VerboseErr($err)
    {
        if($this->_v)
            $this->_cli->ShowError($err);
    }

    /**
     * Get array of callable methods
     *
     * Will return an array of methods from the task
     * called that are public and not a dependency
     *
     * @param Task $task Task object
     * @return string[] Array of callable methods
     */
    private function GetCallableMethods(Task $task)
    {
        $class = new ReflectionClass($task);
        return array_diff(array_map(function($rm) {
            return $rm->getName();
        }, $class->getMethods(ReflectionMethod::IS_PUBLIC)), array_merge(
            array('DeclareDependencies'), self::$_dependencies)
        );
    }

    /**
     * Call all of a task's callable methods
     *
     * This method will get all of a task's callable
     * methods and pass them to a helper method that
     * calls the method
     *
     * @param Task $task Task to be used
     */
    private function RunAllMethods(Task $task)
    {
        $methods = $this->GetCallableMethods($task);

        foreach($methods as $method)
        {
            $this->_lvl = '';
            $this->_RunMethod($task, $method);
            $this->VerboseOut("#");
        }
    }

    /**
     * Call a single method from the task
     *
     * This method will check if the passed method
     * name is callable then uses the helper
     * method to call it
     *
     * @param Task $task Task to be used
     * @param string $method Method name
     */
    private function RunMethod(Task $task, $method)
    {
        if($this->IsMethodCallable(array($task, $method)))
            $this->_RunMethod($task, $method);
    }

    /**
     * Helper method to call methods
     *
     * This is what calls the actual method, but not
     * before checking if it has any dependencies and then
     * running them
     *
     * @param Task $task Task to be used
     * @param string $method Method name
     */
    private function _RunMethod(Task $task, $method)
    {
        $this->VerboseOut('# '.$this->_lvl.'-> Running [::'.$method.']');
        $this->HandleDependencies($task, $method);
        $task->$method();
    }

    /**
     * Checks for dependencies then runs them
     *
     * This method checks to see if the method
     * name passed has any dependencies, and them runs them
     * by using the helper ::_RunMethod method
     *
     * @param Task $task Task to be used
     * @param string $method Method name
     */
    private function HandleDependencies(Task $task, $method)
    {
        if(array_key_exists($method, self::$_dependencies))
        {
            $this->_lvl .= '-';
            $this->_RunMethod($task, self::$_dependencies[$method]);
        }
    }

    /**
     * Check if method is callable
     *
     * Checks if the method in the task exists
     * and it is callable from this scope
     *
     * @param mixed[] $task_method array(Task, 'methodName')
     * @return boolean
     */
    private function IsMethodCallable($task_method)
    {
        return (method_exists($task_method[0], $task_method[1]) && is_callable($task_method));
    }

    /**
     * Static method to add dependencies
     *
     * Use this static method to add a dependent
     * to a method in a task
     *
     * @param string $method Name of method that depends on another method
     * @param string $on Name of dependency method
     */
    public static function DependentOn($method, $on)
    {
        self::$_dependencies[$method] = $on;
    }

    /**
     * Helper static method for file name
     *
     * This method will concatinate the file name
     * with the constant ::TASKFILEENDING
     *
     * @param string $name Name of task file
     * @return string Full name of file with extension
     */
    public static function TaskFileName($name)
    {
        return $name.self::TASKFILEENDING;
    }
}
?>
