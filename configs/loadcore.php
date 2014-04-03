<?php
SkyL::Import(SkyDefines::Call('EXCEPTIONS_CLASS'));
SkyL::Import(SkyDefines::Call('LOG_CLASS'));
SkyL::Import(SkyDefines::Call('PLUGIN_CLASS'));
SkyL::Import(SkyDefines::Call('EVENT_CLASS'));
SkyL::Import(SkyDefines::Call('DIR_CONFIGS').'/plugins.php');
SkyL::Import(SkyDefines::Call('SKYCORE_CONFIGS').'/autoloader.php');
SkyL::Import(SkyDefines::Call('HTML_CLASS'));
SkyL::Import(SkyDefines::Call('ERROR_CLASS'));
SkyL::Import(SkyDefines::Call('CACHE_CLASS'));
SkyL::Import(SkyDefines::Call('SESSION_CLASS'));
SkyL::Import(SkyDefines::Call('COOKIE_CLASS'));
SkyL::Import(SkyDefines::Call('AUTH_CLASS'));
SkyL::Import(SkyDefines::Call('CONTROLLER_CLASS'));
SkyL::Import(SkyDefines::Call('MAILER_CLASS'));
SkyL::Import(SkyDefines::Call('ROUTETO_CLASS'));
SkyL::Import(SkyDefines::Call('ROUTER_CLASS'));
SkyL::Import(SkyDefines::Call('ROUTE_CLASS'));
SkyL::Import(SkyDefines::Call('PUBLISHAPI_CLASS'));
SkyL::Import(SkyDefines::Call('SKY_CLASS'));
SkyL::Import(SkyDefines::Call('HELPER_CLASS'));
SkyL::Import(SkyDefines::Call('SERVICELOCATOR_CLASS'));

Event::PublishActionHook('/preimports/after/');

// #Starting Error Instance
$_errorHandler = Error::GetInstance();
?>