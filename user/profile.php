<?php
// Check if user is logged in
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start();

// Check if user is logged in
if (!isLoggedIn()) {
    // Redirect to login page
    redirect(APP_URL . '/login.php');
}

// Check if user is regular user
if (isAdmin()) {
    redirect(APP_URL . '/admin');
} elseif (isStaff()) {
    redirect(APP_URL . '/staff');
}

// Get user information
$userId = $_SESSION['user_id'];
$user = fetchRow("SELECT * FROM users WHERE id = ?", [$userId]);

// Initialize variables
$errors = [];
$success = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    
    // Validate form data
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    } elseif ($email !== $user['email']) {
        // Check if email is already in use by another user
        $existingUser = fetchRow("SELECT * FROM users WHERE email = ? AND id != ?", [$email, $userId]);
        if ($existingUser) {
            $errors[] = 'Email is already in use by another account';
        }
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    }
    
    // If no errors, update profile
    if (empty($errors)) {
        $userData = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $updated = update('users', $userData, "id = $userId");
        
        if ($updated) {
            // Update session data
            $_SESSION['user_name'] = $name;
            
            // Log activity
            logActivity('profile_updated', 'Updated profile information', $userId);
            
            $success = 'Profile updated successfully.';
        } else {
            $errors[] = 'Failed to update profile. Please try again.';
        }
    }
}

// Set page title
$pageTitle = 'My Profile';

// Include header
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- User Sidebar -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>User Profile</h5>
                </div>
                <div class="card-body text-center">
                    <img src="https://via.placeholder.com/150" alt="Profile Picture" class="rounded-circle img-thumbnail mb-3">
                    <h5><?php echo $user['name']; ?></h5>
                    <p class="text-muted"><?php echo $user['email']; ?></p>
                    <hr>
                    <div class="d-grid gap-2">
                        <a href="profile.php" class="btn btn-outline-primary">
                            <i class="fas fa-edit me-2"></i>Edit Profile
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="list-group mb-4">
                <a href="index.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
                <a href="booking.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-ticket-alt me-2"></i>Book Ticket
                </a>
                <a href="history.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-history me-2"></i>Booking History
                </a>
                <a href="profile.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-user-edit me-2"></i>My Profile
                </a>
                <a href="password.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-key me-2"></i>Change Password
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <!-- Page Title -->
            <div class="card mb-4">
                <div class="card-body bg-primary text-white">
                    <h4><i class="fas fa-user-edit me-2"></i>My Profile</h4>
                    <p class="mb-0">Update your personal information.</p>
                </div>
            </div>
            
            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Success Message -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <!-- Profile Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo $user['name']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $user['phone']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="created_at" class="form-label">Account Created</label>
                            <input type="text" class="form-control" id="created_at" value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" disabled>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Account Security Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Account Security</h5>
                </div>
                <div class="card-body">
                    <p>To update your password, please use the <a href="password.php">Change Password</a> page.</p>
                    
                    <div class="alert alert-info mb-0">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle fa-2x me-3"></i>
                            <div>
                                <strong>Security Tip:</strong> Make sure to use a strong, unique password for your account, and never share your login details with anyone.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>
