<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin user exists
$admin = fetchRow("SELECT * FROM users WHERE role = 'admin' LIMIT 1");

echo "<h2>Admin User Details</h2>";
if ($admin) {
    echo "<pre>";
    print_r($admin);
    echo "</pre>";
} else {
    echo "No admin user found.";
}

// Display current hashed password if available
if (isset($admin['password'])) {
    echo "<p>Current hashed password: " . $admin['password'] . "</p>";
}

// Set new password if requested
if (isset($_GET['reset']) && $_GET['reset'] == 'true') {
    $password = "admin123";
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $updated = update('users', ['password' => $hashedPassword], "id = " . $admin['id']);
    
    if ($updated) {
        echo "<p style='color:green'>Password reset successfully to: " . $password . "</p>";
        echo "<p>New hashed password: " . $hashedPassword . "</p>";
    } else {
        echo "<p style='color:red'>Error resetting password.</p>";
    }
}

echo "<p><a href='?reset=true'>Reset password to 'admin123'</a></p>";

// Reset button to clear session
echo "<p><a href='clear_session.php'>Clear session data</a></p>";
