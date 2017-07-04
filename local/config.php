<?php

//
// Set up production environment
//
error_reporting(E_ALL);
ini_set('display_errors', 1);

//
// Database configuration
//
define('DB_FILE', __DIR__ . '/database.db');
define('DB_DSN', 'sqlite:' . constant('DB_FILE'));
