<?php
$_router = new Router();
import(DIR_CONFIGS.'/routes.php');
$_approute = new AppRoute();
$_approute->AppRoutes();
$_router->Follow($_approute->_GetMatches());
?>