<?php
// Auth Table/Model
AppConfig::AuthModel('users');
AppConfig::AuthModelUsername('username');
AppConfig::AuthModelPassword('password');
AppConfig::AuthSalt('Sky');

// Log system options
AppConfig::EnableLogging(true);

// Logging levels
// @level LOG_LEVEL_HIGH - Most info. Main core mechanics
// @level LOG_LEVEL_MID - Info at major parts of the core
// @level LOG_LEVEL_LOW - Startup info for methods
AppConfig::SetLoggingLevel(SkyDefines::Call('LOG_LEVEL_HIGH'));

// Internal MySQL Query Caching
AppConfig::EnableMySQLQueryCache(true);

// $_FILES Auto Clean up
AppConfig::EnableFileArrayCleanup(true);

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
?>
