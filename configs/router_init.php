<?php
date_default_timezone_set('America/Los_Angeles');
$_router = new Router();
SkyL::Import(DIR_CONFIGS.'/routes.php');
$_approute = new AppRoute();
$_approute->AppRoutes();
Benchmark::End();
Log::corewrite('CORE Loading time: [%s seconds]', 3, 'CORE', 'LOADING', array(Benchmark::ElapsedTime()));
$_router->Follow($_approute->_GetMatches());
?>