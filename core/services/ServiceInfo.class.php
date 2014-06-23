<?php
// ####
// ServiceInfo Class
// 
// This class allos a service to announce itself to the system.
//
// @license
// - This file may not be redistributed in whole or significant part, or
// - used on a web site without licensing of the enclosed code, and
// - software features.
//
// @author      Alan Tirado <alan@deeplogik.com>
// @copyright   2013 DeepLogik, All Rights Reserved
//
// @version     0.0.8.1 Starting from here
// ##

// ####
// ServiceInfo Class
// @desc Handles the announcement of a service.
// @abstract
// @package SKY.Core.Services
// ##
abstract class ServiceInfo
{
    private static $_info = array();
    private static $_interfaces = array();
    
    public final function __construct()
    {
        static::Announce();
    }
    
    public final static function Set($info = array())
    {
        self::$_info[get_called_class()] = $info;
    }
    
    public final static function PublicInterfaces($interfaces = array())
    {
        self::$_interfaces[get_called_class()] = $interfaces;
    }
    
    public final function DoYouHaveThisInterface($interface)
    {
        if(in_array($interface, self::$_interfaces[get_called_class()]))
            return true;
        return false;
    }
    
    public final function GiveMeAnImplementation($interface, $filters = null)
    {
        if(array_key_exists($interface, self::$_info[get_called_class()]))
        {
            $keys = array_keys(self::$_info[get_called_class()][$interface]);
            $winner = $keys[0];
            foreach(self::$_info[get_called_class()][$interface] as $impl => $info)
            {
                // @ToDo: Create filters
                if(!is_null($filters))
                {
                    
                }
                
                if(array_key_exists('rank', $info))
                {
                    if(!array_key_exists('rank', self::$_info[get_called_class()][$interface][$winner]))
                        $winner = $impl;
                    else {
                        if(self::$_info[get_called_class()][$interface][$winner]['rank'] < $info['rank'])
                            $winner = $impl;
                    }
                }
            }
            $file_name = $winner.'.implementation.php';
            $file_path = SkyDefines::Call('DIR_APP_SERVICES').'/service.'.strtolower(str_replace('Info', '', get_class($this))).'/src/implementations/'.$file_name;
            if(is_file($file_path))
            {
                SkyL::Import($file_path);
                return new $winner();
            }
        }
    }
}
