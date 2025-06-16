<?php
// Start output buffering to prevent header issues
ob_start();

// Set page title
$pageTitle = 'Login';

// Include config and initialize session
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Ensure session is started
session_start();

// Debug log directory
$log_dir = 'logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Create debug log file
$debug_file = $log_dir . '/login_debug.txt';
file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Login page accessed\n", FILE_APPEND);

// Include header
require_once 'includes/header.php';

// Check if user is already logged in
if (isLoggedIn()) {
    // Redirect to appropriate dashboard
    if (isAdmin()) {
        redirect(APP_URL . '/admin');
    } elseif (isStaff()) {
        redirect(APP_URL . '/staff');
    } else {
        redirect(APP_URL . '/user');
    }
}

// Initialize variables
$error = '';
$email = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validate form data
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } else {
        // Fetch user by email
        $user = fetchRow("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);
        
        // Add detailed logging for debugging
        debug_log("Login attempt", [
            'email' => $email,
            'user_found' => ($user ? 'yes' : 'no'),
            'status' => ($user ? $user['status'] : 'n/a'),
            'role' => ($user ? $user['role'] : 'n/a')
        ], 'login_attempts.log');
        
        if ($user && password_verify($password, $user['password'])) {            // Password is correct, set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            
            // Log the successful login with the new debug_log function
            debug_log("Login successful", [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
                'session' => $_SESSION
            ], 'successful_logins.log');
            
            // Debug log for login
            // Create logs directory if it doesn't exist
            $log_dir = 'logs';
            if (!file_exists($log_dir)) {
                mkdir($log_dir, 0755, true);
            }
            
            $debug_log = fopen($log_dir . '/login_debug_log.txt', 'a');
            fwrite($debug_log, date('Y-m-d H:i:s') . " - Login successful for user: " . $user['email'] . "\n");
            fwrite($debug_log, "User ID: " . $user['id'] . "\n");
            fwrite($debug_log, "User Role: " . $user['role'] . "\n");
            fwrite($debug_log, "SESSION after login: " . json_encode($_SESSION) . "\n");
            
            // If remember me is checked, set cookie for 30 days
            if ($remember) {
                $token = generateRandomString(32);
                $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                // Store token in database
                $rememberData = [
                    'user_id' => $user['id'],
                    'token' => $token,
                    'expires_at' => $expiry,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                insert('remember_tokens', $rememberData);
                
                // Set cookie
                setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
            }
            
            // Log activity
            logActivity('login', 'User logged in', $user['id']);            // Redirect to appropriate dashboard
            if ($user['role'] === 'admin') {
                fwrite($debug_log, "Redirecting to admin dashboard\n");
                fwrite($debug_log, "Redirect URL: " . APP_URL . '/admin' . "\n");
                fclose($debug_log);
                
                // Double check admin role is correctly set
                debug_log("Admin redirect check", [
                    'user_role' => $_SESSION['user_role'],
                    'isAdmin()' => isAdmin() ? 'true' : 'false',
                    'redirect_url' => APP_URL . '/admin'
                ], 'admin_redirects.log');
                
                redirect(APP_URL . '/admin');
            } elseif ($user['role'] === 'staff') {
                fwrite($debug_log, "Redirecting to staff dashboard\n");
                fclose($debug_log);
                redirect(APP_URL . '/staff');
            } else {
                // Check if there's any redirect URL
                fwrite($debug_log, "Redirecting to user dashboard\n");
                fclose($debug_log);
                if (isset($_GET['redirect'])) {
                    redirect(urldecode($_GET['redirect']));
                } else {
                    redirect(APP_URL . '/user');
                }
            }
        } else {
            // Invalid credentials
            $error = 'Invalid email or password';
        }
    }
}

// Check if there's a timeout message
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $_SESSION['flash_message'] = 'Your session has expired. Please login again.';
    $_SESSION['flash_type'] = 'info';
}
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-sign-in-alt me-2"></i>Login to Your Account</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember Me</label>
                        </div>
                        <div>
                            <a href="forgot-password.php">Forgot Password?</a>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">Don't have an account? <a href="register.php">Register</a></p>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
