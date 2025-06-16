<?php
// Include the admin authentication file
require_once 'admin_auth.php';

// If we get here, the user is authenticated as admin
echo '<!DOCTYPE html>
<html>
<head>
    <title>Admin Test Page</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Admin Authentication Test</h1>
    <p class="success">Success! You are logged in as an admin.</p>
    
    <h2>Session Information:</h2>
    <p class="info">User ID: ' . $_SESSION['user_id'] . '</p>
    <p class="info">Name: ' . $_SESSION['user_name'] . '</p>
    <p class="info">Email: ' . $_SESSION['user_email'] . '</p>
    <p class="info">Role: ' . $_SESSION['user_role'] . '</p>
    
    <h2>Complete Session Data:</h2>
    <pre>' . print_r($_SESSION, true) . '</pre>
    
    <h2>Navigation:</h2>
    <p><a href="' . APP_URL . '/admin/index.php">Go to Admin Dashboard</a></p>
    <p><a href="' . APP_URL . '/logout.php">Logout</a></p>
</body>
</html>';
?>
