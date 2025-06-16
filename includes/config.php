<?php
/**
 * Database Configuration
 */

// Define database constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'metro_rail');

// Debug mode (set to false in production)
define('DEBUG_MODE', true);

// Application URL (change this to your actual domain in production)
define('APP_URL', 'http://localhost/metro-rail');

// Session timeout in seconds (1 hour)
define('SESSION_TIMEOUT', 3600);

// Default timezone
date_default_timezone_set('UTC');

// Contact information
define('CONTACT_EMAIL', 'support@metrorail.com');
define('CONTACT_PHONE', '+1-800-METRO-RAIL');

// Error reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
