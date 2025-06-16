<?php
// Start output buffering
ob_start();

// Set page title
$pageTitle = 'System Quick Fix';

// Include config and initialize session
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug log directory
$log_dir = 'logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Process actions
$message = '';
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Get admin user
$admin = fetchRow("SELECT * FROM users WHERE role = 'admin' LIMIT 1");

switch ($action) {
    case 'reset_admin_password':
        if ($admin) {
            $newPassword = 'admin123';
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $updated = update('users', ['password' => $hashedPassword], "id = ?", [$admin['id']]);
            
            if ($updated) {
                $message = "Admin password reset to: admin123";
            } else {
                $message = "Failed to reset admin password.";
            }
        } else {
            $message = "No admin user found.";
        }
        break;
        
    case 'login_as_admin':
        if ($admin) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['user_name'] = $admin['name'];
            $_SESSION['user_email'] = $admin['email'];
            $_SESSION['user_role'] = $admin['role'];
            $_SESSION['last_activity'] = time();
            
            $message = "You are now logged in as admin: " . htmlspecialchars($admin['email']);
        } else {
            $message = "No admin user found.";
        }
        break;
        
    case 'check_session':
        $sessionInfo = $_SESSION;
        $message = "Session data: <pre>" . print_r($sessionInfo, true) . "</pre>";
        break;
        
    case 'logout':
        session_unset();
        session_destroy();
        $message = "You have been logged out.";
        break;
}

// Include header (but only if not doing an action)
if (empty($action)) {
    include 'includes/header.php';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metro Rail - System Quick Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-tools me-2"></i>System Quick Fix</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-info">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <h5>Available Actions:</h5>
                        <div class="list-group mb-4">
                            <a href="?action=reset_admin_password" class="list-group-item list-group-item-action">
                                <i class="fas fa-key me-2"></i>Reset Admin Password to 'admin123'
                            </a>
                            <a href="?action=login_as_admin" class="list-group-item list-group-item-action">
                                <i class="fas fa-sign-in-alt me-2"></i>Login as Admin
                            </a>
                            <a href="?action=check_session" class="list-group-item list-group-item-action">
                                <i class="fas fa-info-circle me-2"></i>Check Session Data
                            </a>
                            <a href="?action=logout" class="list-group-item list-group-item-action">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </div>
                        
                        <h5>System Information:</h5>
                        <table class="table table-striped">
                            <tr>
                                <th>PHP Version</th>
                                <td><?php echo phpversion(); ?></td>
                            </tr>
                            <tr>
                                <th>Database Connection</th>
                                <td><?php echo $conn->connect_error ? 'Failed: ' . $conn->connect_error : 'Connected'; ?></td>
                            </tr>
                            <tr>
                                <th>Session Status</th>
                                <td>
                                    <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not Active'; ?>
                                    (<?php echo session_id(); ?>)
                                </td>
                            </tr>
                            <tr>
                                <th>Logged In User</th>
                                <td>
                                    <?php
                                    if (isLoggedIn()) {
                                        echo htmlspecialchars($_SESSION['user_name']) . ' (' . htmlspecialchars($_SESSION['user_email']) . ')';
                                        echo ' [' . htmlspecialchars($_SESSION['user_role']) . ']';
                                    } else {
                                        echo 'Not logged in';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Admin Check</th>
                                <td>
                                    isAdmin() returns: <?php echo isAdmin() ? 'true' : 'false'; ?><br>
                                    isLoggedIn() returns: <?php echo isLoggedIn() ? 'true' : 'false'; ?>
                                </td>
                            </tr>
                        </table>
                        
                        <div class="mt-4 d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary"><i class="fas fa-home me-2"></i>Back to Home</a>
                            <?php if (isLoggedIn() && isAdmin()): ?>
                                <a href="admin/index.php" class="btn btn-primary"><i class="fas fa-tachometer-alt me-2"></i>Go to Admin Dashboard</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
