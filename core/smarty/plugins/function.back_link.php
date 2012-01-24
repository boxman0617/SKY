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
function smarty_function_back_link($params, $template)
{
    $output = 'back';
    $default = '#';
    if(isset($params['output']))
        $output = $params['output'];
    if(isset($params['default']))
        $default = $params['default'];
    if(isset($_SERVER['HTTP_REFERER']))
        echo "<a href='".$_SERVER['HTTP_REFERER']."'>".$output."</a>";
    else
        echo "<a href='".$default."'>".$output."</a>";
}

?>