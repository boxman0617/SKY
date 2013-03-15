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
    public function __construct();
    public function setTableName($name);
    public function setSchema();
    public function getSchema();
    public function doesTableExist($class_name);
    public function runQuery($query);
    public function save($data);
    
    public function escape($value);
    public function buildQuery();
    
}
?>