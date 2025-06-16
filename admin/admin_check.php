<?php
// Check if user is logged in
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug logging directory
$log_dir = 'C:/xampp/htdocs/metro-rail/logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Create debug log file
file_put_contents('C:/xampp/htdocs/metro-rail/admin_access_debug.txt', 
    date('Y-m-d H:i:s') . " - Admin index.php accessed\n" .
    "Session data: " . print_r($_SESSION, true) . "\n" .
    "isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false') . "\n" .
    "isAdmin(): " . (isAdmin() ? 'true' : 'false') . "\n" .
    "APP_URL: " . APP_URL . "\n",
    FILE_APPEND
);

// Check if user is logged in
if (!isLoggedIn()) {
    // Redirect to login page
    file_put_contents('C:/xampp/htdocs/metro-rail/admin_access_debug.txt', 
        date('Y-m-d H:i:s') . " - Not logged in, redirecting to login page\n",
        FILE_APPEND
    );
    redirect(APP_URL . '/login.php');
}

// Check if user is admin
if (!isAdmin()) {
    file_put_contents('C:/xampp/htdocs/metro-rail/admin_access_debug.txt', 
        date('Y-m-d H:i:s') . " - Not admin, redirecting to appropriate dashboard\n" .
        "User role: " . (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'undefined') . "\n",
        FILE_APPEND
    );
    // Redirect to appropriate dashboard
    if (isStaff()) {
        redirect(APP_URL . '/staff');
    } else {
        redirect(APP_URL . '/user');
    }
}

file_put_contents('C:/xampp/htdocs/metro-rail/admin_access_debug.txt', 
    date('Y-m-d H:i:s') . " - Admin access allowed\n",
    FILE_APPEND
);

// Get admin information
$userId = $_SESSION['user_id'];
$admin = fetchRow("SELECT * FROM users WHERE id = ?", [$userId]);
