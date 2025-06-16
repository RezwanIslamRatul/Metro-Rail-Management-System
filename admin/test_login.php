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

// Function to test password verification
function test_password($email, $password) {
    // Fetch user by email
    $user = fetchRow("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);
    
    if (!$user) {
        return "User with email '$email' not found or not active.";
    }
    
    $verified = password_verify($password, $user['password']);
    
    return [
        'user_id' => $user['id'],
        'user_name' => $user['name'],
        'user_email' => $user['email'],
        'user_role' => $user['role'],
        'password_verified' => $verified ? 'Yes' : 'No',
        'password_hash' => substr($user['password'], 0, 20) . '...'
    ];
}

// Initialize message
$message = '';
$test_result = null;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validate form data
    if (empty($email) || empty($password)) {
        $message = 'Email and password are required';
    } else {
        $test_result = test_password($email, $password);
    }
}

// Default admin data
$admin = fetchRow("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
$admin_email = $admin ? $admin['email'] : '';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Admin Login Test</title>
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
        form { margin: 20px 0; }
        input, button { padding: 8px; margin: 5px 0; }
    </style>
</head>
<body>
    <h1>Admin Login Test Tool</h1>";

// Display test form
echo "<div class='card'>
    <h2>Test Login Credentials</h2>
    
    <form method='post' action=''>
        <div>
            <label for='email'>Email:</label><br>
            <input type='email' id='email' name='email' value='" . htmlspecialchars($admin_email) . "' required style='width:300px;'>
        </div>
        
        <div>
            <label for='password'>Password:</label><br>
            <input type='password' id='password' name='password' value='admin123' required style='width:300px;'>
        </div>
        
        <button type='submit'>Test Login</button>
    </form>
    
    " . (!empty($message) ? "<p class='error'>$message</p>" : "") . "
</div>";

// Display test results
if ($test_result) {
    echo "<div class='card'>
        <h2>Test Results</h2>";
        
    if (is_array($test_result)) {
        echo "<table>
            <tr><th>User ID</th><td>" . $test_result['user_id'] . "</td></tr>
            <tr><th>Name</th><td>" . htmlspecialchars($test_result['user_name']) . "</td></tr>
            <tr><th>Email</th><td>" . htmlspecialchars($test_result['user_email']) . "</td></tr>
            <tr><th>Role</th><td>" . $test_result['user_role'] . "</td></tr>
            <tr><th>Password Verified</th><td>" . 
                ($test_result['password_verified'] === 'Yes' 
                    ? "<span class='success'>Yes (Password is correct)</span>" 
                    : "<span class='error'>No (Password is incorrect)</span>") 
            . "</td></tr>
            <tr><th>Password Hash</th><td>" . $test_result['password_hash'] . "</td></tr>
        </table>";
    } else {
        echo "<p class='error'>$test_result</p>";
    }
    
    echo "</div>";
}

// Quick links
echo "<div class='card'>
    <h2>Quick Links</h2>
    <p><a href='fix_admin_login.php' class='button'>Admin Login Fix Tool</a></p>
    <p><a href='../login.php' class='button'>Go to Login Page</a></p>
    <p><a href='index.php' class='button'>Go to Admin Dashboard</a></p>
</div>

</body>
</html>";
?>
