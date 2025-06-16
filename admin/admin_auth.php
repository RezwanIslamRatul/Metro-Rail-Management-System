<?php
// Start output buffering to prevent header issues
ob_start();

// Include necessary files if not already included
if (!function_exists('isLoggedIn')) {
    require_once '../includes/config.php';
    require_once '../includes/db.php';
    require_once '../includes/functions.php';
}

// Create logs directory if it doesn't exist
$log_dir = '../logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log the session data for debugging
file_put_contents($log_dir . '/admin_access.log', 
    date('Y-m-d H:i:s') . " - Admin page accessed\n" .
    "Session data: " . print_r($_SESSION, true) . "\n" .
    "isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false') . "\n" .
    "Role: " . (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'undefined') . "\n" .
    "isAdmin(): " . (isAdmin() ? 'true' : 'false') . "\n" .
    "Request URI: " . $_SERVER['REQUEST_URI'] . "\n\n",
    FILE_APPEND
);

// Check if user is logged in
if (!isLoggedIn()) {
    // Add log entry
    file_put_contents($log_dir . '/admin_access.log', 
        date('Y-m-d H:i:s') . " - Not logged in, redirecting to login page\n\n",
        FILE_APPEND
    );
    
    // Use debug_log for better tracking
    debug_log("Admin access denied - Not logged in", [
        'request_uri' => $_SERVER['REQUEST_URI'],
        'redirect_to' => APP_URL . '/login.php'
    ], 'admin_access_denied.log');
    
    // Redirect to login page
    redirect(APP_URL . '/login.php');
    exit;
}

// Check if user is admin
if (!isAdmin()) {
    // Add log entry
    file_put_contents($log_dir . '/admin_access.log', 
        date('Y-m-d H:i:s') . " - Not admin, redirecting to appropriate dashboard\n" .
        "User role: " . $_SESSION['user_role'] . "\n\n",
        FILE_APPEND
    );
    
    // Use debug_log for better tracking
    debug_log("Admin access denied - Not admin role", [
        'user_id' => $_SESSION['user_id'],
        'user_role' => $_SESSION['user_role'],
        'is_admin' => isAdmin() ? 'true' : 'false'
    ], 'admin_access_denied.log');
    
    // Redirect to appropriate dashboard
    if (isStaff()) {
        redirect(APP_URL . '/staff');
    } else {
        redirect(APP_URL . '/user');
    }
    exit;
}

// User is logged in and is admin
file_put_contents($log_dir . '/admin_access.log', 
    date('Y-m-d H:i:s') . " - Admin access granted\n\n",
    FILE_APPEND
);
