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
    </style>
</head>
<body>
    <h1>Admin Authentication Test</h1>
    <p class="success">Success! You are logged in as an admin.</p>
    <p class="info">User ID: ' . $_SESSION['user_id'] . '</p>
    <p class="info">Name: ' . $_SESSION['user_name'] . '</p>
    <p class="info">Email: ' . $_SESSION['user_email'] . '</p>
    <p class="info">Role: ' . $_SESSION['user_role'] . '</p>
    <p><a href="' . APP_URL . '/admin/index.php">Go to Admin Dashboard</a></p>
    <p><a href="' . APP_URL . '/logout.php">Logout</a></p>
</body>
</html>';
?>
