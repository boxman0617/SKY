<?php
// ####
// Base Class
// 
// This class is the "Base" of all other classes.
// It allows for services to be shared across all
// instanciated objects.
//
// @license
// - This file may not be redistributed in whole or significant part, or
// - used on a web site without licensing of the enclosed code, and
// - software features.
//
// @author      Alan Tirado <alan@deeplogik.com>
// @copyright   2013 DeepLogik, All Rights Reserved
//
// @version     0.0.7 Starting from here
// ##

// ####
// Base Class
// @desc Shares services across all objects.
// @abstract
// @package SKY.Core.Main
// ##
abstract class Base
{
    protected static $_share = array();
}
?>