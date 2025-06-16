<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start output buffering
ob_start();

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Create a session for testing
session_start();
$_SESSION['user_id'] = 1; // Assuming admin ID is 1
$_SESSION['user_role'] = 'admin';

// Try to get trains data
try {
    $trains = fetchRows("SELECT id, name as train_name, train_number, capacity, status, created_at, updated_at FROM trains ORDER BY name ASC");
    
    echo "<h2>Debug Information</h2>";
    echo "<p>Successfully retrieved trains data.</p>";
    echo "<h3>Trains Data:</h3>";
    echo "<pre>";
    print_r($trains);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h2>Error Information</h2>";
    echo "<p>Error fetching trains: " . $e->getMessage() . "</p>";
}

// Check database connection
echo "<h3>Database Connection Test:</h3>";
try {
    global $conn;
    if ($conn->ping()) {
        echo "<p>Database connection is working.</p>";
    } else {
        echo "<p>Database connection failed.</p>";
    }
} catch (Exception $e) {
    echo "<p>Database connection error: " . $e->getMessage() . "</p>";
}

// Display PHP information
echo "<h3>PHP Information:</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Output buffering: " . (ob_get_level() > 0 ? "Enabled (Level: " . ob_get_level() . ")" : "Disabled") . "</p>";

// Check for errors in your functions
echo "<h3>Function Tests:</h3>";
try {
    echo "<p>sanitizeInput test: " . sanitizeInput("Test<script>") . "</p>";
    
    echo "<p>isLoggedIn test: " . (isLoggedIn() ? "True" : "False") . "</p>";
    
    echo "<p>Session data:</p>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p>Function test error: " . $e->getMessage() . "</p>";
}
?>
