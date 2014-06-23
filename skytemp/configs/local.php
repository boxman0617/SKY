<?php
/**
 * This file will should not be sent to a repo unless it is private.
 * The idea is that this file is ONLY used locally on a machine.
 */

// Database Enviroments
AppConfig::DatabaseENV('DEV', array(
    ':driver'   => 'MySQL',
    ':server'   => 'localhost',
    ':username' => 'root',
    ':password' => '',
    ':database' => 'dev_database'
));

AppConfig::DatabaseENV('TEST', array(
    ':driver'   => 'MySQL',
    ':server'   => 'localhost',
    ':username' => 'root',
    ':password' => '',
    ':database' => 'test_database'
));

AppConfig::DatabaseENV('PRO', array(
    ':driver'   => 'MySQL',
    ':server'   => 'localhost',
    ':username' => 'root',
    ':password' => '',
    ':database' => 'pro_database'
));
