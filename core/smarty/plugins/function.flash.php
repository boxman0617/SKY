<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage PluginsFunction
 */

/**
 * Smarty {counter} function plugin
 *
 * Type:     function<br>
 * Name:     counter<br>
 * Purpose:  print out a counter value
 * @author Monte Ohrt <monte at ohrt dot com>
 * @link http://smarty.php.net/manual/en/language.function.counter.php {counter}
 *       (Smarty online manual)
 * @param array parameters
 * @param Smarty
 * @param object $template template object
 * @return string|null
 */
require_once(dirname(__FILE__).'/../../../configs/defines.php');
import(SESSION_CLASS);
function smarty_function_flash($params, $template)
{
    $session = Session::getInstance();
    if(isset($session->flash))
    {
        if(!isset($params['type']))
            $params['type'] = 'info';
            
        switch($params['type'])
        {
            case 'info':
                $flash = '<div style="background-image: url(\'/core/smarty/plugins/images/info.png\'); background-repeat: no-repeat; background-position: 10px center; font-weight: bold; background-color: #BDE5F8; border: 1px solid #00529B; padding:15px 10px 15px 50px; color: #00529B; margin: 10px 0px; font-family:Arial, Helvetica, sans-serif; font-size:13px;">';
                break;
            case 'warning':
                $flash = '<div style="background-image: url(\'/core/smarty/plugins/images/warning.png\'); background-repeat: no-repeat; background-position: 10px center; font-weight: bold; background-color: #FEEFB3; border: 1px solid #9F6000; padding:15px 10px 15px 50px; color: #9F6000; margin: 10px 0px; font-family:Arial, Helvetica, sans-serif; font-size:13px;">';
                break;
            case 'success':
                $flash = '<div style="background-image: url(\'/core/smarty/plugins/images/success.png\'); background-repeat: no-repeat; background-position: 10px center; font-weight: bold; background-color: #DFF2BF; border: 1px solid #4F8A10; padding:15px 10px 15px 50px; color: #4F8A10; margin: 10px 0px; font-family:Arial, Helvetica, sans-serif; font-size:13px;">';
                break;
            case 'error':
                $flash = '<div style="background-image: url(\'/core/smarty/plugins/images/error.png\'); background-repeat: no-repeat; background-position: 10px center; font-weight: bold; background-color: #FFBABA; border: 1px solid #D8000C; padding:15px 10px 15px 50px; color: #D8000C; margin: 10px 0px; font-family:Arial, Helvetica, sans-serif; font-size:13px;">';
                break;
            default:
                $flash = '<div style="background-image: url(\'/core/smarty/plugins/images/info.png\'); background-repeat: no-repeat; background-position: 10px center; font-weight: bold; background-color: #BDE5F8; border: 1px solid #00529B; padding:15px 10px 15px 50px; color: #00529B; margin: 10px 0px; font-family:Arial, Helvetica, sans-serif; font-size:13px;">';
        }
        $flash .= $session->flash;
        $flash .= '</div>';
        echo $flash;
    }
}

?>