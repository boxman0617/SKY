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
import(ROUTES_CLASS);
function smarty_function_method_put($params, $template)
{
    $div = '<div style="display: none">';
    $div .= '<input type="hidden" value="PUT" name="REQUEST_METHOD"/>';
    $div .= '<input type="hidden" value="'.Route::CreateHash(Route::GetSalt()).'" name="token"/>';
    $div .= '</div>';
    return $div;
}

?>