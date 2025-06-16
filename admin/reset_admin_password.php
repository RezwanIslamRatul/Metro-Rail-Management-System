<?php
// Start output buffering
ob_start();

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

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
