<?php
/**
 * iDriver interface
 *
 * This is a template for Model back-end drivers.
 *
 * LICENSE:
 *
 * This file may not be redistributed in whole or significant part, or
 * used on a web site without licensing of the enclosed code, and
 * software features.
 *
 * @author      Alan Tirado <root@deeplogik.com>
 * @copyright   2013 DeepLogik, All Rights Reserved
 * @license     http://www.codethesky.com/license
 * @link        http://www.codethesky.com/docs/idriverinterface
 * @package     Sky.Core
 */

interface iDriver {
    public function __construct($db);
    public function setTableName($name);
    public function setPrimaryKey(&$key);
    public function buildModelInfo(&$model);
    
    public function escape($value);
    public function run();
}
?>