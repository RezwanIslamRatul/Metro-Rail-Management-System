<?php
// Include necessary files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only allow access from localhost for security
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('Access denied.');
}

echo "<h1>Create New Admin User</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form submission
    $name = isset($_POST['name']) ? sanitizeInput($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validate input
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }
    
    // Check if email exists
    if (!empty($email)) {
        $existingUser = fetchRow("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existingUser) {
            $errors[] = 'Email already exists';
        }
    }
    
    if (empty($errors)) {
        // Create user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $userData = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $hashedPassword,
            'role' => 'admin',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $userId = insert('users', $userData);
        
        if ($userId) {
            echo "<div style='color:green;'>Admin user created successfully! ID: $userId</div>";
            echo "<p>You can now login with the following credentials:</p>";
            echo "<ul>";
            echo "<li>Email: $email</li>";
            echo "<li>Password: $password</li>";
            echo "</ul>";
        } else {
            echo "<div style='color:red;'>Failed to create admin user</div>";
        }
    } else {
        echo "<div style='color:red;'>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
}
?>

<form method="post" action="">
    <div>
        <label for="name">Name:</label>
        <input type="text" name="name" id="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
    </div>
    <div>
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
    </div>
    <div>
        <label for="phone">Phone:</label>
        <input type="text" name="phone" id="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
    </div>
    <div>
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" minlength="8" required>
    </div>
    <div>
        <button type="submit">Create Admin User</button>
    </div>
</form>

<hr>

<h2>Current Admin Users</h2>
<?php
$adminUsers = fetchRows("SELECT id, name, email, status FROM users WHERE role = 'admin'");

if (!empty($adminUsers)) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th></tr>";
    
    foreach ($adminUsers as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['status']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No admin users found</p>";
}
?>
