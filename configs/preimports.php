<?php
//Core system
import(CONFIGS_DIR.'/configure.php');
import(LOG_CLASS);
import(PLUGIN_CLASS);
import(EVENT_CLASS);
import(CONFIGS_DIR.'/plugins.php');
import(PRELOADER);
import(HTML_CLASS);
import(ERROR_CLASS);
import(SESSION_CLASS);
import(COOKIE_CLASS);
import(AUTH_CLASS);
import(CONTROLLER_CLASS);
import(MAILER_CLASS);
import(TASK_CLASS);

if(!function_exists('date_diff'))
{
    function date_diff($date1, $date2)
    { 
        $current = $date1; 
        $datetime2 = date_create($date2); 
        $count = 0; 
        while(date_create($current) < $datetime2){ 
            $current = gmdate("Y-m-d", strtotime("+1 day", strtotime($current))); 
            $count++; 
        } 
        return $count; 
    } 
}

Event::PublishActionHook('/preimports/after/');

$_errorHandler = Error::GetInstance();
?>