<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create logs directory if it doesn't exist
$log_dir = '../logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Get admin user from database
$admin = fetchRow("SELECT id, name, email, role, status FROM users WHERE role = 'admin' AND status = 'active' LIMIT 1");

// Initialize variables
$message = '';
$status = '';
$newPassword = 'admin123'; // Default new password

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    if (isset($_POST['admin_id']) && !empty($_POST['admin_id'])) {
        $adminId = (int)$_POST['admin_id'];
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update the password in the database
        $updateData = [
            'password' => $hashedPassword,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $updated = update('users', $updateData, "id = ?", [$adminId]);
        
        if ($updated) {
            $status = 'success';
            $message = "Admin password has been reset successfully to: {$newPassword}";
            
            // Log the password reset
            file_put_contents($log_dir . '/admin_password_reset.log', 
                date('Y-m-d H:i:s') . " - Admin password reset\n" .
                "Admin ID: " . $adminId . "\n" .
                "New password: " . $newPassword . " (hashed in DB)\n",
                FILE_APPEND
            );
        } else {
            $status = 'error';
            $message = "Failed to update the password.";
        }
    } else {
        $status = 'error';
        $message = "Invalid admin ID.";
    }
}

// Output HTML
echo '<!DOCTYPE html>
<html>
<head>
    <title>Admin Password Reset</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; max-width: 800px; margin: 0 auto; }
        .header { background-color: #4e73df; color: white; padding: 10px 20px; border-radius: 5px 5px 0 0; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { display: inline-block; background: #4e73df; color: white; padding: 10px 15px; 
               text-decoration: none; border-radius: 5px; margin-top: 10px; border: none; cursor: pointer; }
        .warning { color: #856404; background-color: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .password-box { background-color: #f9f9f9; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>Admin Password Reset Tool</h1>
        </div>
        
        <div class="warning">
            <strong>Note:</strong> This page should only be used for development and troubleshooting purposes.
            It allows resetting the admin password without verification.
        </div>';

// Display message if any
if (!empty($message)) {
    echo '<div class="' . $status . '">' . $message . '</div>';
    
    if ($status === 'success') {
        echo '<div class="info">
            <p>You can now login with these credentials:</p>
            <p><strong>Email:</strong> ' . htmlspecialchars($admin['email']) . '</p>
            <p><strong>Password:</strong> <span class="password-box">' . $newPassword . '</span></p>
            <p><a href="' . APP_URL . '/login.php" class="btn">Go to Login Page</a></p>
        </div>';
    }
}

if ($admin) {
    echo '
        <h2>Admin User Details</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>' . $admin['id'] . '</td>
                <td>' . $admin['name'] . '</td>
                <td>' . $admin['email'] . '</td>
                <td>' . $admin['role'] . '</td>
                <td>' . $admin['status'] . '</td>
            </tr>
        </table>
        
        <h2>Reset Admin Password</h2>
        <p>This will reset the admin password to: <strong>' . $newPassword . '</strong></p>
        <form method="post" action="">
            <input type="hidden" name="admin_id" value="' . $admin['id'] . '">
            <button type="submit" name="reset_password" class="btn">Reset Password</button>
        </form>';
} else {
    echo '<div class="error">
        <p>No admin user found in the database!</p>
        <p>Please check your database to ensure there is at least one user with role = "admin" and status = "active".</p>
    </div>';
}

echo '
        <h2>Other Tools</h2>
        <p><a href="fix_admin_login.php" class="btn">Fix Admin Login</a> - Manually set admin session</p>
        <p><a href="check_admin.php" class="btn">Check Admin Status</a> - Diagnostic information</p>
        <p><a href="test_admin.php" class="btn">Test Admin Authentication</a> - Check authentication</p>
        <p><a href="' . APP_URL . '/logout.php" class="btn">Logout</a> - Clear current session</p>
    </div>
</body>
</html>';
?>
