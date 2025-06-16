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
$logs_dir = '../logs';
if (!file_exists($logs_dir)) {
    mkdir($logs_dir, 0755, true);
}

// Check if admin user exists and reset password if requested
if (isset($_GET['reset']) && $_GET['reset'] == 'true') {
    $admin = fetchRow("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
    
    if ($admin) {
        $password = 'admin123';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $updated = update('users', ['password' => $hashedPassword], "id = ?", [$admin['id']]);
        
        if ($updated) {
            echo "<p style='color:green'>Admin password reset successfully to: <strong>$password</strong></p>";
            echo "<p>You can now log in using:</p>";
            echo "<p>Email: <strong>" . htmlspecialchars($admin['email']) . "</strong></p>";
            echo "<p>Password: <strong>$password</strong></p>";
            
            // Log the password reset
            file_put_contents($logs_dir . '/admin_password_reset.log', 
                date('Y-m-d H:i:s') . " - Admin password reset\n" .
                "Admin ID: " . $admin['id'] . "\n" .
                "Admin Email: " . $admin['email'] . "\n" .
                "New password: $password (hashed in DB)\n\n",
                FILE_APPEND
            );
        } else {
            echo "<p style='color:red'>Failed to reset admin password.</p>";
        }
    } else {
        echo "<p style='color:red'>No admin user found!</p>";
    }
}

// Display admin user info
$admin = fetchRow("SELECT * FROM users WHERE role = 'admin' LIMIT 1");

echo "<!DOCTYPE html>
<html>
<head>
    <title>Admin Login Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
        .card { border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; }
        .success { color: green; }
        .error { color: red; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        a.button { 
            display: inline-block; 
            background: #4CAF50; 
            color: white; 
            padding: 8px 16px; 
            text-decoration: none; 
            border-radius: 4px; 
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <h1>Metro Rail Admin Authentication Debug</h1>";

// Display admin information
echo "<div class='card'>
    <h2>Admin User Information</h2>";
    
if ($admin) {
    echo "<table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
        </tr>
        <tr>
            <td>" . $admin['id'] . "</td>
            <td>" . htmlspecialchars($admin['name']) . "</td>
            <td>" . htmlspecialchars($admin['email']) . "</td>
            <td>" . $admin['role'] . "</td>
            <td>" . $admin['status'] . "</td>
        </tr>
    </table>";
    
    echo "<p>Password hash: <code>" . substr($admin['password'], 0, 30) . "...</code></p>";
    echo "<p><a href='?reset=true' class='button'>Reset Admin Password to 'admin123'</a></p>";
} else {
    echo "<p class='error'>No admin user found in the database!</p>";
}

// Display session information
echo "</div>
<div class='card'>
    <h2>Current Session Information</h2>";

if (isset($_SESSION) && !empty($_SESSION)) {
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    
    echo "<p>isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false') . "</p>";
    echo "<p>isAdmin(): " . (isAdmin() ? 'true' : 'false') . "</p>";
} else {
    echo "<p>No active session.</p>";
}

// Create manual login session button
echo "<p><a href='create_admin_session.php' class='button'>Create Admin Session</a></p>";
echo "<p><a href='../logout.php' class='button'>Logout</a></p>";
echo "</div>

<div class='card'>
    <h2>Quick Links</h2>
    <p><a href='../login.php' class='button'>Go to Login Page</a></p>
    <p><a href='index.php' class='button'>Go to Admin Dashboard</a></p>
</div>

</body>
</html>";
?>
