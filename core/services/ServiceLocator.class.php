<?php
// ####
// ServiceLocator Class
// 
// This Singleton class will "locate" any registered services and return an Object
// that implements the Interface needed.
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
// ServiceLocator Class
// @desc Handles the lookup of registered services and returns an Object.
// @singleton
// @package SKY.Core.Services
// ##
class ServiceLocator
{
    private static $_instance = null;
    public static $ServicesCache = array();
    
    public function __construct()
    {
        $services = scandir(DIR_APP_SERVICES);
        foreach($services as $service)
        {
            $info_dir = DIR_APP_SERVICES.'/'.$service.'/info';
            if(is_dir($info_dir))
            {
                $info_file = SKY::UnderscoreToUpper(str_replace('service.', '', $service)).'Info';
                $info_path = $info_dir.'/'.$info_file.'.php';
                if(is_file($info_path))
                {
                    require_once($info_path);
                    self::$ServicesCache[] = new $info_file();
                }
            }
        }
    }
    
    public static function GetInstance()
    {
        if(is_null(self::$_instance))
            self::$_instance = new ServiceLocator();
        return self::$_instance;
    }
    
    public function GetService($interface_name)
    {
        foreach(self::$ServicesCache as $service)
        {
            if($service->DoYouHaveThisInterface($interface_name))
            {
                return $service->GiveMeAnImplementation($interface_name);
            }
        }
    }
}
?>