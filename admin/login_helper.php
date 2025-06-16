<?php
// If this script is accessed directly, show admin login details
echo '<!DOCTYPE html>
<html>
<head>
    <title>Admin Login Helper</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; line-height: 1.6; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .login-details { background-color: #f5f5f5; padding: 15px; border-radius: 5px; }
        .btn { display: inline-block; background: #4e73df; color: white; padding: 10px 15px; 
               text-decoration: none; border-radius: 5px; margin-top: 10px; }
        .warning { color: #856404; background-color: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Admin Login Helper</h1>
    
    <div class="warning">
        <strong>Note:</strong> This page should only be used for development and troubleshooting purposes. 
        It displays sensitive information and should be removed in production.
    </div>
    
    <div class="card">
        <h2>Admin Login Details</h2>
        <div class="login-details">
            <p><strong>Email:</strong> admin@metrorail.com</p>
            <p><strong>Password:</strong> admin123</p>
        </div>
        <a href="/metro-rail/login.php" class="btn">Go to Login Page</a>
    </div>
    
    <div class="card">
        <h2>Login Troubleshooting</h2>
        <p>If you\'re having trouble logging in as admin, consider the following:</p>
        <ul>
            <li>Make sure sessions are working correctly in PHP</li>
            <li>Check the session timeout in the config file</li>
            <li>Ensure the admin user exists in the database</li>
            <li>Check the logs directory for any error messages</li>
        </ul>
        <p>The admin authentication flow is:</p>
        <ol>
            <li>login.php - sets session variables after successful login</li>
            <li>admin/admin_auth.php - checks for valid admin session</li>
            <li>admin pages - include admin_auth.php for protection</li>
        </ol>
    </div>
    
    <div class="card">
        <h2>Test Pages</h2>
        <ul>
            <li><a href="/metro-rail/admin/test_admin.php">Admin Test Page</a> - Test admin login</li>
            <li><a href="/metro-rail/admin/index.php">Admin Dashboard</a> - Main admin dashboard</li>
        </ul>
    </div>
</body>
</html>';
?>
