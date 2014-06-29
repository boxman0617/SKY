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
 * @author      Alan Tirado <root@deeplogik.com>
 * @copyright   2013 DeepLogik, All Rights Reserved
 * @license     http://www.codethesky.com/license
 * @link        http://www.codethesky.com/docs/pluginclass
 * @package     Sky.Core
 */

/**
 * Plugin class
 * Handles the registering of user created plugins
 * @package Sky.Core.Plugin
 */
class Plugin
{
    const PUBLISH_FILE = 'plugin.json';
    const PLUGINS_FILE = 'plugins.json';

    public static $JSON = array();

    private static $_gets = array();

    public static function Init()
    {
      $json = json_decode(file_get_contents(SkyDefines::Call('DIR_CONFIGS').'/'.self::PLUGINS_FILE), true);
      foreach($json as $plugin => $props)
      {
        if(array_key_exists('getID', $props))
          self::$_gets[$props['getID']] = $plugin;

        if(array_key_exists('onLoad', $props))
        {
          require_once(SkyDefines::Call('SKYCORE_LIB_PLUGINS').'/'.$plugin.'/'.$props['onLoad']);
        }
      }
    }

    public static function Get($get_id)
    {
      if(array_key_exists($get_id, self::$_gets))
      {
        return new self::$_gets[$get_id]();
      }
    }

    public static function GetPluginDir($plugin_name)
    {
      return SkyDefines::Call('SKYCORE_LIB_PLUGINS').'/'.$plugin_name;
    }

    public static function GetLocalPluginDir($plugin_name)
    {
      return SkyDefines::Call('DIR_LIB_PLUGINS').'/'.$plugin_name;
    }

    public static function GetFile($plugin_name)
    {
      $json = self::ReadJSON($plugin_name);
      return self::GetPluginDir($plugin_name).'/'.$json['file'];
    }

    public static function ReadJSON($plugin_name)
    {
      return json_decode(file_get_contents(SkyDefines::Call('SKYCORE_LIB_PLUGINS').'/'.$plugin_name.'/'.self::PUBLISH_FILE), true);
    }
}
