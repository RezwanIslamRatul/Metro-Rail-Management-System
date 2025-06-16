<?php
// Start output buffering
ob_start();

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create logs directory if it doesn't exist
$logs_dir = '../logs';
if (!file_exists($logs_dir)) {
    mkdir($logs_dir, 0755, true);
}

// Fetch the admin user
$admin = fetchRow("SELECT * FROM users WHERE role = 'admin' LIMIT 1");

if (!$admin) {
    die("No admin user found in the database!");
}

// Set session variables for admin login
$_SESSION['user_id'] = $admin['id'];
$_SESSION['user_name'] = $admin['name'];
$_SESSION['user_email'] = $admin['email'];
$_SESSION['user_role'] = $admin['role'];
$_SESSION['last_activity'] = time();

// Log the admin session creation
file_put_contents($logs_dir . '/admin_session.log', 
    date('Y-m-d H:i:s') . " - Manual admin session created\n" .
    "Admin ID: " . $admin['id'] . "\n" .
    "Admin Email: " . $admin['email'] . "\n" .
    "Session data: " . print_r($_SESSION, true) . "\n\n",
    FILE_APPEND
);

// Redirect to admin dashboard
header("Location: index.php");
exit;
?>
