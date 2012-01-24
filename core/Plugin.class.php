<?php
/**
 * Plugin Core Class
 *
 * This class allows the registering of plugins
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
 * @package Sky.Core
 */

class Plugin
{
    public static $plugin = array();
    
    public static function Register($name)
    {
        $name = strtolower($name);
        if(is_dir(PLUGINS_DIR.'/'.$name))
        {
            if(is_file(PLUGINS_DIR.'/'.$name.'/info.cnf'))
            {
                $info = file_get_contents(PLUGINS_DIR.'/'.$name.'/info.cnf');
                preg_match_all('/((?!#).+)=(.*)/', $info, $matches);
                for($i=0;$i<count($matches[1]);$i++)
                {
                    self::$plugin[$name][$matches[1][$i]] = $matches[2][$i];
                }
                if(!isset(self::$plugin[$name]['dir']))
                    self::$plugin[$name]['dir'] = PLUGINS_DIR.'/'.$name;
                
                require_once(self::$plugin[$name]['dir'].'/init.php');
            } else {
                unset(self::$plugin[$name]);
            }
        } else {
            unset(self::$plugin[$name]);
        }
    }
}
?>