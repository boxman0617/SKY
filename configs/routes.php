<?php
require_once(dirname(__FILE__).'/defines.php');
import(PREIMPORTS);
import(ROUTES_CLASS);

$routes = new Route(); //Example

$routes->Home('Home#Index');
$routes->NotFound('Error#NotFound');
$routes->Match('/home', 'Home#Index', 'GET');
$routes->Match('/home/:id', 'Home#Show', 'GET');
$routes->Match('/home/:id/edit', 'Home#Edit', 'GET');
$routes->Match('/home/:id', 'Home#Update', 'PUT');
$routes->Match('/test/:status', 'Home#Test', 'GET');

//$routes->Match('/home/index', 'Home#Index', 'GET'); //Example

$routes->Follow();
?>