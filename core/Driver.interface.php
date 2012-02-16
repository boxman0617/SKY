<?php
interface iDriver {
    public function __construct();
    public function setTableName($name);
    public function setSchema();
    public function getSchema();
    public function doesTableExist($class_name);
    public function runQuery($query);
    public function save($data);
    
    public function escape($value);
    public function buildQuery($material);
    
}
?>