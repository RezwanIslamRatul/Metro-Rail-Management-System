<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create logs directory if it doesn't exist
$log_dir = '../logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Set the admin password to a known value
$newPassword = 'admin123';

// Fetch the admin user
$admin = fetchRow("SELECT * FROM users WHERE role = 'admin' LIMIT 1");

if ($admin) {
    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update the admin user's password
    $updated = update('users', ['password' => $hashedPassword], "id = ?", [$admin['id']]);
    
    if ($updated) {
        echo "<h2>Admin Password Reset Successfully</h2>";
        echo "<p>The admin password has been reset to: <strong>{$newPassword}</strong></p>";
        echo "<p>You can now log in with:</p>";
        echo "<p>Email: <strong>" . htmlspecialchars($admin['email']) . "</strong></p>";
        echo "<p>Password: <strong>{$newPassword}</strong></p>";
        
        // Log the password reset
        file_put_contents($log_dir . '/admin_password_reset.log', 
            date('Y-m-d H:i:s') . " - Admin password reset via direct script\n" .
            "Admin ID: " . $admin['id'] . "\n" .
            "Admin Email: " . $admin['email'] . "\n" .
            "New password: " . $newPassword . " (hashed in DB)\n\n",
            FILE_APPEND
        );
        
        echo "<p><a href='../login.php'>Go to Login Page</a></p>";
    } else {
        echo "<h2>Password Reset Failed</h2>";
        echo "<p>There was an error updating the password.</p>";
    }
} else {
    echo "<h2>Error</h2>";
    echo "<p>No admin user found in the database.</p>";
}
?>
