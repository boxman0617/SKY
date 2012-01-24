<?php
/**
 * ObjectFactory Core Class
 *
 * Creates objects from the objects directory.
 * Objects are considered small user created objects that allow for OOP logic
 * in the application layer
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

/**
 * ObjectFactory class
 * Creates objects from the objects directory.
 * @package Sky.Core.ObjectFactory
 */
class ObjectFactory
{
    /**
     * Imports object file if found and returns an instantiation of that object
     * @access public
     * @static
     * @param string $type
     * @return object. Could also return false if no object is found
     */
    public static function Manufactor($type)
    {
        $ls = scandir(OBJECTS_DIR);
        if(in_array($type.'.object.php', $ls))
        {
            import(OBJECTS_DIR.'/'.$type.'.object.php');
            return new $type();
        }
        return false;
    }
}
?>