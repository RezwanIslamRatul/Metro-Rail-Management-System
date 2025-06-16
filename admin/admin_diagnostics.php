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

// Initialize variables
$isResetClicked = isset($_GET['reset']) && $_GET['reset'] === 'true';
$isTestLoginClicked = isset($_GET['test_login']) && $_GET['test_login'] === 'true';
$loginStatus = null;

// Get admin user from database
$admin = fetchRow("SELECT * FROM users WHERE role = 'admin' LIMIT 1");

// Reset admin password if requested
if ($isResetClicked && $admin) {
    $newPassword = 'admin123';
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $updateData = [
        'password' => $hashedPassword,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $updated = update('users', $updateData, "id = ?", [$admin['id']]);
    
    if ($updated) {
        $loginStatus = [
            'success' => true,
            'message' => 'Admin password has been reset to: ' . $newPassword
        ];
    } else {
        $loginStatus = [
            'success' => false,
            'message' => 'Failed to update the password.'
        ];
    }
}

// Test login with the admin credentials
if ($isTestLoginClicked && $admin) {
    $password = 'admin123';
    $verified = password_verify($password, $admin['password']);
    
    if ($verified) {
        // Set session data for the admin
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['user_name'] = $admin['name'];
        $_SESSION['user_email'] = $admin['email'];
        $_SESSION['user_role'] = $admin['role'];
        $_SESSION['last_activity'] = time();
        
        file_put_contents($logs_dir . '/admin_diagnostics.log', 
            date('Y-m-d H:i:s') . " - Test login successful\n" .
            "SESSION data: " . print_r($_SESSION, true) . "\n" .
            "isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false') . "\n" .
            "isAdmin(): " . (isAdmin() ? 'true' : 'false') . "\n\n",
            FILE_APPEND
        );
        
        $loginStatus = [
            'success' => true,
            'message' => 'Login successful! Session set for admin.'
        ];
    } else {
        $loginStatus = [
            'success' => false,
            'message' => 'Password verification failed.'
        ];
    }
}

// Output HTML
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login Diagnostics</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .card-header { background-color: #f5f5f5; padding: 10px; border-bottom: 1px solid #ddd; margin: -20px -20px 20px; border-radius: 8px 8px 0 0; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; }
        .button { display: inline-block; background-color: #4CAF50; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px; }
        .button.secondary { background-color: #2196F3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Login Diagnostics</h1>
        
        <?php if ($loginStatus): ?>
            <div class="card">
                <div class="card-header">
                    <h2>Action Result</h2>
                </div>
                <div class="<?php echo $loginStatus['success'] ? 'success' : 'error'; ?>">
                    <?php echo $loginStatus['message']; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2>Admin User Information</h2>
            </div>
            
            <?php if ($admin): ?>
                <table>
                    <tr>
                        <th>ID</th>
                        <td><?php echo $admin['id']; ?></td>
                    </tr>
                    <tr>
                        <th>Name</th>
                        <td><?php echo htmlspecialchars($admin['name']); ?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                    </tr>
                    <tr>
                        <th>Role</th>
                        <td><?php echo $admin['role']; ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><?php echo $admin['status']; ?></td>
                    </tr>
                    <tr>
                        <th>Password Hash</th>
                        <td><code><?php echo htmlspecialchars(substr($admin['password'], 0, 30)) . '...'; ?></code></td>
                    </tr>
                </table>
                
                <div style="margin-top: 20px;">
                    <a href="?reset=true" class="button">Reset Password to 'admin123'</a>
                    <a href="?test_login=true" class="button secondary">Test Login with 'admin123'</a>
                </div>
            <?php else: ?>
                <p class="error">No admin user found in the database!</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Session Information</h2>
            </div>
            
            <?php if (!empty($_SESSION)): ?>
                <pre><?php print_r($_SESSION); ?></pre>
                <p>isLoggedIn(): <?php echo isLoggedIn() ? '<span class="success">true</span>' : '<span class="error">false</span>'; ?></p>
                <p>isAdmin(): <?php echo isAdmin() ? '<span class="success">true</span>' : '<span class="error">false</span>'; ?></p>
            <?php else: ?>
                <p>No active session.</p>
            <?php endif; ?>
            
            <div style="margin-top: 20px;">
                <a href="../logout.php" class="button">Logout (Clear Session)</a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Navigation Links</h2>
            </div>
            <p><a href="../login.php" class="button">Go to Login Page</a></p>
            <p><a href="../direct_admin_login.php" class="button">Use Direct Admin Login</a></p>
            <p><a href="index.php" class="button">Go to Admin Dashboard</a></p>
            <p><a href="fix_admin_login.php" class="button">Admin Login Fix Tool</a></p>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>System Information</h2>
            </div>
            <table>
                <tr>
                    <th>APP_URL</th>
                    <td><?php echo APP_URL; ?></td>
                </tr>
                <tr>
                    <th>Current PHP Script</th>
                    <td><?php echo $_SERVER['SCRIPT_NAME']; ?></td>
                </tr>
                <tr>
                    <th>Current URL</th>
                    <td><?php echo $_SERVER['REQUEST_URI']; ?></td>
                </tr>
                <tr>
                    <th>Session Status</th>
                    <td>
                        <?php 
                        switch (session_status()) {
                            case PHP_SESSION_DISABLED:
                                echo 'Sessions are disabled';
                                break;
                            case PHP_SESSION_NONE:
                                echo 'Sessions are enabled, but no session has started';
                                break;
                            case PHP_SESSION_ACTIVE:
                                echo 'Sessions are enabled, and a session is active';
                                break;
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
