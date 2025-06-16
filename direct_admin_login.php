<?php
// Include necessary files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create logs directory if it doesn't exist
$logs_dir = 'logs';
if (!file_exists($logs_dir)) {
    mkdir($logs_dir, 0755, true);
}

// Set admin user manually
$admin = fetchRow("SELECT * FROM users WHERE role = 'admin' LIMIT 1");

if ($admin) {
    // Set session variables
    $_SESSION['user_id'] = $admin['id'];
    $_SESSION['user_name'] = $admin['name'];
    $_SESSION['user_email'] = $admin['email'];
    $_SESSION['user_role'] = $admin['role'];
    $_SESSION['last_activity'] = time();
    
    // Log the direct login
    file_put_contents($logs_dir . '/direct_admin_login.log', 
        date('Y-m-d H:i:s') . " - Direct admin login used\n" .
        "Admin ID: " . $admin['id'] . "\n" .
        "Admin Email: " . $admin['email'] . "\n" .
        "Session data: " . print_r($_SESSION, true) . "\n\n",
        FILE_APPEND
    );
    
    // Use debug_log for better tracking
    if (function_exists('debug_log')) {
        debug_log("Direct admin login", [
            'admin_id' => $admin['id'],
            'admin_email' => $admin['email'],
            'session' => $_SESSION
        ], 'direct_admin_login.log');
    }
    
    // Display diagnostic information
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Admin Login</title>
        <link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'>
    </head>
    <body>
        <div class='container mt-5'>
            <div class='card'>
                <div class='card-header bg-success text-white'>
                    <h2>Admin Session Created Successfully</h2>
                </div>
                <div class='card-body'>
                    <p>You are now logged in as admin: <strong>" . htmlspecialchars($admin['email']) . "</strong></p>
                    
                    <h4>Session Information:</h4>
                    <ul>
                        <li>User ID: " . $_SESSION['user_id'] . "</li>
                        <li>Role: " . $_SESSION['user_role'] . "</li>
                        <li>isAdmin(): " . (isAdmin() ? 'true' : 'false') . "</li>
                    </ul>
                    
                    <div class='mt-4'>
                        <a href='admin/index.php' class='btn btn-primary'>Go to Admin Dashboard</a>
                        <a href='index.php' class='btn btn-secondary ml-2'>Go to Homepage</a>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>";
} else {
    echo "<h2>Error</h2>";
    echo "<p>No admin user found in the database.</p>";
}
?>
