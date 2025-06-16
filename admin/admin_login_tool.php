<?php
// Start output buffering
ob_start();

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

// Process actions
$message = '';
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Get admin user
$admin = fetchRow("SELECT * FROM users WHERE role = 'admin' LIMIT 1");

if ($action === 'reset_password') {
    $newPassword = 'admin123';
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $updated = update('users', ['password' => $hashedPassword], "id = ?", [$admin['id']]);
    
    if ($updated) {
        $message = "Admin password reset to: $newPassword";
        debug_log("Admin password reset", [
            'admin_id' => $admin['id'],
            'admin_email' => $admin['email'],
            'new_password' => $newPassword
        ], 'admin_password_reset.log');
    } else {
        $message = "Failed to reset admin password.";
    }
} else if ($action === 'test_login') {
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    
    if (empty($email) || empty($password)) {
        $message = "Email and password are required.";
    } else {
        // Fetch user by email
        $user = fetchRow("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);
        
        if (!$user) {
            $message = "User not found or inactive.";
        } else if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            
            debug_log("Test login successful", [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
                'session' => $_SESSION,
                'isAdmin()' => isAdmin() ? 'true' : 'false'
            ], 'test_login.log');
            
            $message = "Login successful. Session set for user: " . $user['name'];
        } else {
            $message = "Invalid password.";
        }
    }
} else if ($action === 'logout') {
    // Clear session
    session_unset();
    session_destroy();
    
    // Start a new session
    session_start();
    
    $message = "Logged out successfully.";
}

// Get current session data
$sessionData = [
    'is_logged_in' => isLoggedIn() ? 'Yes' : 'No',
    'is_admin' => isAdmin() ? 'Yes' : 'No',
    'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not set',
    'user_role' => isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Not set',
    'session_data' => $_SESSION
];

debug_log("Admin login tool accessed", [
    'admin_email' => $admin ? $admin['email'] : 'No admin found',
    'session_status' => $sessionData
], 'admin_login_tool.log');

// HTML output
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login Tool</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card-header { font-size: 18px; font-weight: bold; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #ddd; }
        .btn { display: inline-block; padding: 8px 16px; background: #4caf50; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn-secondary { background: #2196f3; }
        .btn-danger { background: #f44336; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .success { background-color: #dff0d8; color: #3c763d; }
        .error { background-color: #f2dede; color: #a94442; }
        .info { background-color: #d9edf7; color: #31708f; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        pre { background-color: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .step { background-color: #f9f9f9; padding: 15px; border-left: 4px solid #2196f3; margin-bottom: 15px; }
        .step-title { font-weight: bold; margin-bottom: 10px; }
        form { margin-bottom: 15px; }
        input[type="text"], input[type="email"], input[type="password"] { padding: 8px; width: 100%; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        label { font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Login Troubleshooter</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo (strpos($message, 'success') !== false) ? 'success' : ((strpos($message, 'fail') !== false || strpos($message, 'invalid') !== false) ? 'error' : 'info'); ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">Admin User Information</div>
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
                        <td><code><?php echo htmlspecialchars(substr($admin['password'], 0, 30) . '...'); ?></code></td>
                    </tr>
                </table>
                
                <a href="?action=reset_password" class="btn">Reset Password to 'admin123'</a>
            <?php else: ?>
                <p>No admin user found in the database!</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <div class="card-header">Current Session Status</div>
            <table>
                <tr>
                    <th>Logged In</th>
                    <td><?php echo $sessionData['is_logged_in']; ?></td>
                </tr>
                <tr>
                    <th>Is Admin</th>
                    <td><?php echo $sessionData['is_admin']; ?></td>
                </tr>
                <tr>
                    <th>User ID</th>
                    <td><?php echo $sessionData['user_id']; ?></td>
                </tr>
                <tr>
                    <th>User Role</th>
                    <td><?php echo $sessionData['user_role']; ?></td>
                </tr>
            </table>
            
            <div class="card-header">Session Data</div>
            <pre><?php print_r($_SESSION); ?></pre>
            
            <a href="?action=logout" class="btn btn-danger">Clear Session (Logout)</a>
        </div>
        
        <div class="card">
            <div class="card-header">Test Login Process</div>
            
            <div class="step">
                <div class="step-title">Step 1: Enter Admin Credentials</div>
                <form method="post" action="?action=test_login">
                    <div>
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo $admin ? htmlspecialchars($admin['email']) : ''; ?>" required>
                    </div>
                    <div>
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" value="admin123" required>
                    </div>
                    <button type="submit" class="btn">Test Login</button>
                </form>
            </div>
            
            <div class="step">
                <div class="step-title">Step 2: Verify Admin Role</div>
                <?php if (isLoggedIn()): ?>
                    <p>User is logged in as: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></p>
                    <p>User role is: <strong><?php echo htmlspecialchars($_SESSION['user_role']); ?></strong></p>
                    <p>isAdmin() function returns: <strong><?php echo isAdmin() ? 'true' : 'false'; ?></strong></p>
                    
                    <?php if (isAdmin()): ?>
                        <p class="success">Admin role verification successful!</p>
                    <?php else: ?>
                        <p class="error">User is logged in but not recognized as admin. Check if role is set correctly.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="info">Not logged in. Complete Step 1 first.</p>
                <?php endif; ?>
            </div>
            
            <div class="step">
                <div class="step-title">Step 3: Test Admin Access</div>
                <?php if (isLoggedIn() && isAdmin()): ?>
                    <p class="success">Admin session is correctly set up. You should be able to access the admin area.</p>
                    <a href="index.php" class="btn btn-secondary">Try accessing admin dashboard</a>
                <?php else: ?>
                    <p class="info">Complete Steps 1 and 2 first to set up admin session correctly.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Quick Links</div>
            <p><a href="../login.php" class="btn btn-secondary">Go to regular login page</a></p>
            <p><a href="../direct_admin_login.php" class="btn btn-secondary">Use direct admin login (bypass login form)</a></p>
            <p><a href="admin_diagnostics.php" class="btn btn-secondary">Admin Diagnostics Tool</a></p>
            <p><a href="index.php" class="btn btn-secondary">Admin Dashboard</a></p>
        </div>
        
        <div class="card">
            <div class="card-header">System Information</div>
            <table>
                <tr>
                    <th>APP_URL</th>
                    <td><?php echo APP_URL; ?></td>
                </tr>
                <tr>
                    <th>PHP Version</th>
                    <td><?php echo phpversion(); ?></td>
                </tr>
                <tr>
                    <th>Session Save Path</th>
                    <td><?php echo session_save_path(); ?></td>
                </tr>
                <tr>
                    <th>Server Software</th>
                    <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
