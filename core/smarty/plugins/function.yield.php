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
function smarty_function_yield($params, $template)
{
    $template->parent->display($template->parent->tpl_vars['MAIN_DIR'].'/'.$template->parent->tpl_vars['MAIN_PAGE'].'.view.sky');
}

?>