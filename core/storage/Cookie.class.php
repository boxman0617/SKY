<?php
/**
 * Cookie Core Class
 *
 * This class handles all cookie actions
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
 * @link        http://www.codethesky.com/docs/cookieclass
 * @package     Sky.Core
 */

/**
 * Cookie class
 * This class handles all cookie actions
 * @package Sky.Core.Cookie
 */
class Cookie
{
    const Session = null;
    const OneDay = 86400;
    const SevenDays = 604800;
    const ThirtyDays = 2592000;
    const SixMonths = 15811200;
    const OneYear = 31536000;
    const Lifetime = -1; // 2030-01-01 00:00:00
    
    // THE only instance of the class
    private static $instance;
    
    private function __construct() {}
    
    /**
    * Returns THE instance of 'Cookie'.
    * @return    object
    */
    public static function getInstance()
    {
        if ( !isset(self::$instance))
        {
            self::$instance = new self;
        }
        
        return self::$instance;
    }
    
    public function __get($name)
    {
        return (isset($_COOKIE[$name]) ? $_COOKIE[$name] : null);
    }
    
    public function __isset($name)
    {
        return isset($_COOKIE[$name]);
    }
    
    /**
    * Delete a cookie.
    *
    * @param string $name
    * @param string $path
    * @param string $domain
    * @param bool $remove_from_global Set to true to remove this cookie from this request.
    * @return bool
    */
    public function Delete($name, $path = '/', $domain = false, $remove_from_global = false)
    {
        $retval = false;
        if (!headers_sent())
        {
          if ($domain === false)
          {
            $domain = $_SERVER['HTTP_HOST'];
            if(strpos($domain, ':'))
            {
                $tmp = explode(':', $domain);
                $domain = $tmp[0];
            }
          }
          $retval = setcookie($name, '', time() - 3600, $path, $domain);
        
          if ($remove_from_global)
            unset($_COOKIE[$name]);
        }
        return $retval;
    }
    
    /**
    * Set a cookie. Silently does nothing if headers have already been sent.
    *
    * @param string $name
    * @param string $value
    * @param mixed $expiry
    * @param string $path
    * @param string $domain
    * @return bool
    */
    public function Set($name, $value, $expiry = self::OneYear, $path = '/', $domain = false)
    {
        Log::corewrite('Setting up cookie [%s] [%s]', 3, __CLASS__, __FUNCTION__, array($name, $value));
        $retval = false;
        if (!headers_sent())
        {
            Log::corewrite('No headers where sent yet.', 1, __CLASS__, __FUNCTION__);
          if ($domain === false)
          {
            $domain = $_SERVER['HTTP_HOST'];
            if(strpos($domain, ':'))
            {
                $tmp = explode(':', $domain);
                $domain = $tmp[0];
            }
          }
        
          if ($expiry === -1)
            $expiry = 1893456000; // Lifetime = 2030-01-01 00:00:00
          elseif (is_numeric($expiry))
            $expiry += time();
          else
            $expiry = strtotime($expiry);
        Log::corewrite('Writing cookie: [%s][%s][%s][%s][%s]', 1, __CLASS__, __FUNCTION__, array(
            $name, $value, $expiry, $path, $domain
        ));
          $retval = @setcookie($name, $value, $expiry, $path, $domain);
          if ($retval)
            $_COOKIE[$name] = $value;
        }
        Log::corewrite('At end of method...', 2, __CLASS__, __FUNCTION__);
        return $retval;
    }
}
?>