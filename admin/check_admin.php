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
$log_dir = 'C:/xampp/htdocs/metro-rail/logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Log current session state and function results
file_put_contents($log_dir . '/admin_check.log', 
    date('Y-m-d H:i:s') . " - Admin check diagnostics\n" .
    "Session: " . print_r($_SESSION, true) . "\n" .
    "isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false') . "\n" .
    "isAdmin(): " . (isAdmin() ? 'true' : 'false') . "\n" .
    "hasRole('admin'): " . (hasRole('admin') ? 'true' : 'false') . "\n" .
    "User Role: " . (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'not set') . "\n\n",
    FILE_APPEND
);

// Get all admin users from database
$admins = fetchRows("SELECT id, name, email, role, status FROM users WHERE role = 'admin'");

// Get current PHP session path and status
$session_path = session_save_path();
$session_status = session_status();
$session_status_text = '';
switch ($session_status) {
    case PHP_SESSION_DISABLED:
        $session_status_text = 'PHP_SESSION_DISABLED';
        break;
    case PHP_SESSION_NONE:
        $session_status_text = 'PHP_SESSION_NONE';
        break;
    case PHP_SESSION_ACTIVE:
        $session_status_text = 'PHP_SESSION_ACTIVE';
        break;
}

// Output diagnostics
echo '<!DOCTYPE html>
<html>
<head>
    <title>Admin Login Diagnostics</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .section { margin-bottom: 30px; border: 1px solid #ddd; padding: 20px; border-radius: 5px; }
        .status { margin-bottom: 10px; }
        .status-ok { color: green; }
        .status-error { color: red; }
        .highlight { background-color: #fffbcc; padding: 3px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
    </style>
</head>
<body>
    <h1>Admin Login Diagnostics</h1>
    
    <div class="section">
        <h2>Session Status</h2>
        <div class="status ' . (session_status() === PHP_SESSION_ACTIVE ? 'status-ok' : 'status-error') . '">
            Current Session Status: ' . $session_status_text . '
        </div>
        <div class="status">
            Session Save Path: ' . $session_path . '
        </div>
        <div class="status ' . (isLoggedIn() ? 'status-ok' : 'status-error') . '">
            isLoggedIn(): ' . (isLoggedIn() ? 'true' : 'false') . '
        </div>
        <div class="status ' . (isAdmin() ? 'status-ok' : 'status-error') . '">
            isAdmin(): ' . (isAdmin() ? 'true' : 'false') . '
        </div>
        <div class="status ' . (hasRole('admin') ? 'status-ok' : 'status-error') . '">
            hasRole(\'admin\'): ' . (hasRole('admin') ? 'true' : 'false') . '
        </div>
        <div class="status">
            $_SESSION[\'user_role\']: ' . (isset($_SESSION['user_role']) ? '<span class="highlight">' . $_SESSION['user_role'] . '</span>' : 'not set') . '
        </div>
    </div>
    
    <div class="section">
        <h2>Admin Users in Database</h2>';
        
if (!empty($admins)) {
    echo '<table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
            </tr>';
            
    foreach ($admins as $admin) {
        echo '<tr>
                <td>' . $admin['id'] . '</td>
                <td>' . $admin['name'] . '</td>
                <td>' . $admin['email'] . '</td>
                <td>' . $admin['role'] . '</td>
                <td>' . $admin['status'] . '</td>
            </tr>';
    }
    
    echo '</table>';
} else {
    echo '<p class="status-error">No admin users found in the database!</p>';
}

echo '</div>
    
    <div class="section">
        <h2>Current Session Data</h2>
        <pre>' . print_r($_SESSION, true) . '</pre>
    </div>
    
    <div class="section">
        <h2>Function Definitions</h2>
        <h3>isLoggedIn() Function:</h3>
        <pre>' . htmlspecialchars(file_get_contents('../includes/functions.php', FILE_USE_INCLUDE_PATH, null, 1024, strpos(file_get_contents('../includes/functions.php'), 'function isLoggedIn') + 500)) . '</pre>
        
        <h3>hasRole() Function:</h3>
        <pre>' . htmlspecialchars(file_get_contents('../includes/functions.php', FILE_USE_INCLUDE_PATH, null, 1024, strpos(file_get_contents('../includes/functions.php'), 'function hasRole') + 500)) . '</pre>
        
        <h3>isAdmin() Function:</h3>
        <pre>' . htmlspecialchars(file_get_contents('../includes/functions.php', FILE_USE_INCLUDE_PATH, null, 1024, strpos(file_get_contents('../includes/functions.php'), 'function isAdmin') + 500)) . '</pre>
    </div>
    
    <div class="section">
        <h2>Actions</h2>
        <p><a href="fix_admin_login.php">Fix Admin Login</a> - This will manually set the admin session.</p>
        <p><a href="login_helper.php">Admin Login Helper</a> - Shows admin login credentials.</p>
        <p><a href="' . APP_URL . '/logout.php">Logout</a> - Clear the current session.</p>
        <p><a href="' . APP_URL . '/login.php">Login Page</a> - Go to the main login page.</p>
    </div>
</body>
</html>';
?>
