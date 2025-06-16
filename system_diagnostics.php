<?php
// Clean all output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Start fresh output buffer
ob_start();

// Include the base files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create logs directory if it doesn't exist
$log_dir = 'logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Function to test database connection
function testDatabase() {
    global $conn;
    $result = [
        'status' => 'success',
        'message' => 'Database connection successful'
    ];
    
    if ($conn->connect_error) {
        $result['status'] = 'error';
        $result['message'] = 'Database connection failed: ' . $conn->connect_error;
        return $result;
    }
    
    try {
        $testQuery = $conn->query("SELECT 1");
        if (!$testQuery) {
            $result['status'] = 'error';
            $result['message'] = 'Database query test failed: ' . $conn->error;
        }
    } catch (Exception $e) {
        $result['status'] = 'error';
        $result['message'] = 'Database query test exception: ' . $e->getMessage();
    }
    
    return $result;
}

// Function to test session functionality
function testSession() {
    $result = [
        'status' => 'success',
        'message' => 'Session functionality working properly'
    ];
    
    // Test setting a value
    $_SESSION['test_key'] = 'test_value_' . time();
    
    // Verify it was set
    if ($_SESSION['test_key'] !== 'test_value_' . time()) {
        $result['status'] = 'error';
        $result['message'] = 'Session test failed: Value not set correctly';
    }
    
    return $result;
}

// Function to test login functionality
function testLogin() {
    global $conn;
    $result = [
        'status' => 'success',
        'message' => 'Login functionality tests passed'
    ];
    
    // Test if there are any users in the database
    $query = $conn->query("SELECT COUNT(*) as count FROM users");
    if (!$query) {
        $result['status'] = 'error';
        $result['message'] = 'Failed to query users: ' . $conn->error;
        return $result;
    }
    
    $userCount = $query->fetch_assoc()['count'];
    if ($userCount == 0) {
        $result['status'] = 'warning';
        $result['message'] = 'No users found in database';
        return $result;
    }
    
    // Check if an admin user exists
    $query = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    if (!$query) {
        $result['status'] = 'error';
        $result['message'] = 'Failed to query admin users: ' . $conn->error;
        return $result;
    }
    
    $adminCount = $query->fetch_assoc()['count'];
    if ($adminCount == 0) {
        $result['status'] = 'warning';
        $result['message'] = 'No admin user found in database';
        return $result;
    }
    
    return $result;
}

// Function to test redirect functionality
function testRedirect() {
    $result = [
        'status' => 'success',
        'message' => 'Redirect functionality is configured correctly'
    ];
    
    if (!function_exists('redirect')) {
        $result['status'] = 'error';
        $result['message'] = 'Redirect function not defined';
    }
    
    return $result;
}

// Run all tests
$tests = [
    'database' => testDatabase(),
    'session' => testSession(),
    'login' => testLogin(),
    'redirect' => testRedirect()
];

// Include any output buffering issues
$tests['output_buffering'] = [
    'status' => 'success',
    'message' => 'Output buffering is working properly'
];

if (headers_sent($file, $line)) {
    $tests['output_buffering']['status'] = 'error';
    $tests['output_buffering']['message'] = "Headers already sent in $file on line $line";
}

// Calculate overall status
$overall = 'success';
foreach ($tests as $test) {
    if ($test['status'] == 'error') {
        $overall = 'error';
        break;
    } else if ($test['status'] == 'warning' && $overall != 'error') {
        $overall = 'warning';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metro Rail - System Diagnostics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-card {
            border-left-width: 5px;
        }
        .test-success {
            border-left-color: #198754;
        }
        .test-warning {
            border-left-color: #ffc107;
        }
        .test-error {
            border-left-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-stethoscope me-2"></i>System Diagnostic Results</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-<?php echo $overall == 'success' ? 'success' : ($overall == 'warning' ? 'warning' : 'danger'); ?>">
                            <h5 class="alert-heading">
                                <?php if ($overall == 'success'): ?>
                                    <i class="fas fa-check-circle me-2"></i>All Systems Operational
                                <?php elseif ($overall == 'warning'): ?>
                                    <i class="fas fa-exclamation-triangle me-2"></i>System Warnings Detected
                                <?php else: ?>
                                    <i class="fas fa-times-circle me-2"></i>System Errors Detected
                                <?php endif; ?>
                            </h5>
                            <p>See the detailed test results below.</p>
                        </div>
                        
                        <h5 class="mb-3">Test Results:</h5>
                        
                        <?php foreach ($tests as $name => $test): ?>
                            <div class="card mb-3 test-card test-<?php echo $test['status']; ?>">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?php if ($test['status'] == 'success'): ?>
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                        <?php elseif ($test['status'] == 'warning'): ?>
                                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle text-danger me-2"></i>
                                        <?php endif; ?>
                                        <?php echo ucfirst($name); ?> Test
                                    </h5>
                                    <p class="card-text"><?php echo $test['message']; ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <h5 class="mb-3 mt-4">System Information:</h5>
                        <table class="table table-striped">
                            <tr>
                                <th>PHP Version</th>
                                <td><?php echo phpversion(); ?></td>
                            </tr>
                            <tr>
                                <th>Session Status</th>
                                <td>
                                    <?php 
                                    switch(session_status()) {
                                        case PHP_SESSION_DISABLED:
                                            echo 'Sessions are disabled';
                                            break;
                                        case PHP_SESSION_NONE:
                                            echo 'Sessions are enabled but no session has been started';
                                            break;
                                        case PHP_SESSION_ACTIVE:
                                            echo 'Active (' . session_id() . ')';
                                            break;
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Output Buffering</th>
                                <td>Level <?php echo ob_get_level(); ?> (<?php echo ob_get_length(); ?> bytes)</td>
                            </tr>
                            <tr>
                                <th>APP_URL</th>
                                <td><?php echo defined('APP_URL') ? APP_URL : 'Not defined'; ?></td>
                            </tr>
                            <tr>
                                <th>Current URL</th>
                                <td><?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}"; ?></td>
                            </tr>
                        </table>
                        
                        <div class="mt-4">
                            <a href="system_fix.php" class="btn btn-primary"><i class="fas fa-tools me-2"></i>Go to System Fix Page</a>
                            <a href="index.php" class="btn btn-secondary ms-2"><i class="fas fa-home me-2"></i>Back to Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
