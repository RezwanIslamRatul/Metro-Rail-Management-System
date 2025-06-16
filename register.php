<?php
// Set page title
$pageTitle = 'Register';

// Include header
require_once 'includes/header.php';

// Initialize variables
$errors = [];
$formData = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'password' => '',
    'confirm_password' => ''
];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'name' => isset($_POST['name']) ? sanitizeInput($_POST['name']) : '',
        'email' => isset($_POST['email']) ? sanitizeInput($_POST['email']) : '',
        'phone' => isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '',
        'password' => isset($_POST['password']) ? $_POST['password'] : '',
        'confirm_password' => isset($_POST['confirm_password']) ? $_POST['confirm_password'] : ''
    ];
    
    // Validate form data
    if (empty($formData['name'])) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($formData['email'])) {
        $errors[] = 'Email address is required';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address format';
    } else {
        // Check if email already exists
        $existingUser = fetchRow("SELECT id FROM users WHERE email = ?", [$formData['email']]);
        
        if ($existingUser) {
            $errors[] = 'Email address is already registered';
        }
    }
    
    if (empty($formData['phone'])) {
        $errors[] = 'Phone number is required';
    } elseif (!preg_match('/^[0-9]{10,15}$/', $formData['phone'])) {
        $errors[] = 'Invalid phone number format (10-15 digits only)';
    }
    
    if (empty($formData['password'])) {
        $errors[] = 'Password is required';
    } elseif (strlen($formData['password']) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    if (empty($formData['confirm_password'])) {
        $errors[] = 'Confirm password is required';
    } elseif ($formData['password'] !== $formData['confirm_password']) {
        $errors[] = 'Passwords do not match';
    }
    
    // If no errors, register user
    if (empty($errors)) {
        // Hash password
        $hashedPassword = password_hash($formData['password'], PASSWORD_DEFAULT);
        
        // Insert user data
        $userData = [
            'name' => $formData['name'],
            'email' => $formData['email'],
            'phone' => $formData['phone'],
            'password' => $hashedPassword,
            'role' => 'user',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $result = insert('users', $userData);
        
        if ($result) {
            // Registration successful, set session message
            $_SESSION['flash_message'] = 'Registration successful! You can now login.';
            $_SESSION['flash_type'] = 'success';
            
            // Redirect to login page
            redirect(APP_URL . '/login.php');
        } else {
            $errors[] = 'Registration failed. Please try again later.';
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i>Create an Account</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $formData['name']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $formData['email']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $formData['phone']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">Password must be at least 8 characters long.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="terms.php" target="_blank">Terms & Conditions</a> and <a href="privacy.php" target="_blank">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Register
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">Already have an account? <a href="login.php">Login</a></p>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
