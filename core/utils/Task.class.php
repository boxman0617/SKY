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
     * Internally, use the TaskManager::DependsOn method
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
     * Array of depdendencies
     * @var string[]
     */
    private static $_dependencies = array();

    const TASKFILEENDING = '.task.php';

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

    private function VerboseErr($err)
    {
        if($this->_v)
            $this->_cli->ShowError($err);
    }

    private function GetCallableMethods($task)
    {
        $class = new ReflectionClass($task);
        return array_diff(array_map(function($rm) {
            return $rm->getName();
        }, $class->getMethods(ReflectionMethod::IS_PUBLIC)), array_merge(
            array('DeclareDependencies'), self::$_dependencies)
        );
    }

    private function RunAllMethods($task)
    {
        $methods = $this->GetCallableMethods($task);

        foreach($methods as $method)
        {
            $this->_lvl = '';
            $this->_RunMethod($task, $method);
            $this->VerboseOut("#");
        }
    }

    private function RunMethod($task, $method)
    {
        if($this->IsMethodCallable(array($task, $method)))
            $this->_RunMethod($task, $method);
    }

    private $_lvl = '';
    private function _RunMethod($task, $method)
    {
        $this->VerboseOut('# '.$this->_lvl.'-> Running [::'.$method.']');
        $this->HandleDependencies($task, $method);
        $task->$method();
    }

    private function HandleDependencies($task, $method)
    {
        if(array_key_exists($method, self::$_dependencies))
        {
            $this->_lvl .= '-';
            $this->_RunMethod($task, self::$_dependencies[$method]);
        }
    }

    private function IsMethodCallable($task_method)
    {
        return (method_exists($task_method[0], $task_method[1]) && is_callable($task_method));
    }

    // ##Static
    public static function DependentOn($method, $on)
    {
        self::$_dependencies[$method] = $on;
    }

    public static function TaskFileName($name)
    {
        return $name.self::TASKFILEENDING;
    }
}
?>
