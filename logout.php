<?php
// Include necessary files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug log directory
$log_dir = 'C:/xampp/htdocs/metro-rail/logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Create debug log file
$debug_file = $log_dir . '/logout_debug.txt';
file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Logout page accessed\n", FILE_APPEND);
file_put_contents($debug_file, "SESSION before logout: " . json_encode($_SESSION) . "\n", FILE_APPEND);

// Check if user is logged in
if (isLoggedIn()) {
    // Log activity
    logActivity('logout', 'User logged out', $_SESSION['user_id']);
    
    // Clear remember token if exists
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        // Delete token from database
        delete('remember_tokens', 'token = ?', [$token]);
        
        // Clear cookie
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
    
    // Destroy session
    session_unset();
    session_destroy();
}

// Redirect to login page with a message
$_SESSION['flash_message'] = 'You have been logged out successfully.';
$_SESSION['flash_type'] = 'success';

redirect(APP_URL . '/login.php');
?>
