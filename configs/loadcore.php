<?php
import(LOG_CLASS);
import(PLUGIN_CLASS);
import(EVENT_CLASS);
import(DIR_CONFIGS.'/plugins.php');
import(SKYCORE_CONFIGS.'/preloader.php');
import(HTML_CLASS);
import(ERROR_CLASS);
import(CACHE_CLASS);
import(SESSION_CLASS);
import(COOKIE_CLASS);
import(AUTH_CLASS);
import(CONTROLLER_CLASS);
import(MAILER_CLASS);
import(ROUTER_CLASS);
import(ROUTE_CLASS);
import(SKY_CLASS);

Event::PublishActionHook('/preimports/after/');

// #Starting Error Instance
$_errorHandler = Error::GetInstance();
?>