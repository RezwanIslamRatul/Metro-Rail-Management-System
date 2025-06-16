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
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate form data
    if (empty($currentPassword)) {
        $errors[] = 'Current password is required';
    } else {
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            $errors[] = 'Current password is incorrect';
        }
    }
    
    if (empty($newPassword)) {
        $errors[] = 'New password is required';
    } elseif (strlen($newPassword) < 8) {
        $errors[] = 'New password must be at least 8 characters long';
    }
    
    if (empty($confirmPassword)) {
        $errors[] = 'Please confirm your new password';
    } elseif ($newPassword !== $confirmPassword) {
        $errors[] = 'New passwords do not match';
    }
    
    // If no errors, update password
    if (empty($errors)) {
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $userData = [
            'password' => $hashedPassword,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $updated = update('users', $userData, "id = $userId");
        
        if ($updated) {
            // Log activity
            logActivity('password_changed', 'Changed account password', $userId);
            
            $success = 'Password updated successfully.';
        } else {
            $errors[] = 'Failed to update password. Please try again.';
        }
    }
}

// Set page title
$pageTitle = 'Change Password';

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
                <a href="profile.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-user-edit me-2"></i>My Profile
                </a>
                <a href="password.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-key me-2"></i>Change Password
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <!-- Page Title -->
            <div class="card mb-4">
                <div class="card-body bg-primary text-white">
                    <h4><i class="fas fa-key me-2"></i>Change Password</h4>
                    <p class="mb-0">Update your account password.</p>
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
            
            <!-- Password Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Change Your Password</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required>
                            <div class="form-text">
                                Password must be at least 8 characters long.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Password Security Tips -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Password Security Tips</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-0">
                        <h6><i class="fas fa-info-circle me-2"></i>For a strong password:</h6>
                        <ul class="mb-0">
                            <li>Use at least 8 characters</li>
                            <li>Include uppercase and lowercase letters</li>
                            <li>Include numbers and special characters</li>
                            <li>Avoid using personal information</li>
                            <li>Use a unique password not used elsewhere</li>
                        </ul>
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
