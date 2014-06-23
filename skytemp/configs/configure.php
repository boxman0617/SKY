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
