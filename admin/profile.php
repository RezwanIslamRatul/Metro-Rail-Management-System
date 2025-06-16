<?php
// Include the admin authentication file
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start();

// Check if user is logged in
if (!isLoggedIn()) {
    // Redirect to login page
    redirect(APP_URL . '/login.php');
}

// Check if user is admin
if (!isAdmin()) {
    // Redirect to appropriate dashboard
    if (isStaff()) {
        redirect(APP_URL . '/staff');
    } else {
        redirect(APP_URL . '/user');
    }
}

// Get admin information
$userId = $_SESSION['user_id'];
$admin = fetchRow("SELECT * FROM users WHERE id = ?", [$userId]);

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
    } elseif ($email !== $admin['email']) {
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
            logActivity('profile_updated', 'Admin updated profile information', $userId);
            
            $success = 'Profile updated successfully.';
        } else {
            $errors[] = 'Failed to update profile. Please try again.';
        }
    }
}

// Set page title
$pageTitle = 'Admin Profile';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metro Rail - Admin - <?php echo $pageTitle; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo APP_URL; ?>/css/style.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/css/admin.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/css/admin_modern.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/admin_sidebar.php'; ?>
            
            <!-- Main Content -->            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 main-content">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/admin/index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">My Profile</li>
                    </ol>
                </nav>
                
                <!-- Page Title -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-user-circle me-2"></i>My Profile</h1>
                </div>
                
                <p class="lead">Manage your personal information and account settings.</p>
                
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
                
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <img src="https://via.placeholder.com/150" alt="Admin Profile Picture" class="rounded-circle img-thumbnail mb-3" style="width: 150px; height: 150px;">
                                <h5><?php echo $admin['name']; ?></h5>
                                <p class="text-muted mb-1">Administrator</p>
                                <p class="text-muted"><?php echo $admin['email']; ?></p>
                                <hr>
                                <div class="d-flex justify-content-center">
                                    <p class="mb-0"><strong>Member since: </strong><?php echo date('F j, Y', strtotime($admin['created_at'])); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Account Security</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info mb-0">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-info-circle fa-2x me-3"></i>
                                        <div>
                                            <strong>Security Tip:</strong> Make sure to use a strong, unique password for your account, and never share your login details with anyone.
                                        </div>
                                    </div>
                                </div>
                                <div class="d-grid gap-2 mt-3">
                                    <a href="change_password.php" class="btn btn-outline-primary">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <!-- Profile Form -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Profile Information</h5>
                            </div>
                            <div class="card-body">
                                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $admin['name']; ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $admin['email']; ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $admin['phone']; ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="role" class="form-label">Role</label>
                                        <input type="text" class="form-control" id="role" value="Administrator" disabled>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="created_at" class="form-label">Account Created</label>
                                        <input type="text" class="form-control" id="created_at" value="<?php echo date('F j, Y', strtotime($admin['created_at'])); ?>" disabled>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Update Profile
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Recent Activity -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activity</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Activity</th>
                                                <th>Date & Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>                                            <?php
                                            // Get recent admin activities
                                            $activities = fetchRows("
                                                SELECT * FROM activity_logs 
                                                WHERE user_id = ? 
                                                ORDER BY created_at DESC 
                                                LIMIT 5
                                            ", [$userId]);
                                            
                                            if ($activities) {
                                                foreach ($activities as $activity) {
                                                    echo "<tr>";
                                                    echo "<td>{$activity['description']}</td>";
                                                    echo "<td>" . date('M d, Y h:i A', strtotime($activity['created_at'])) . "</td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='2' class='text-center'>No recent activity found.</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="<?php echo APP_URL; ?>/js/script.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Apply hover effects to all cards
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.classList.add('shadow-lg');
                });
                card.addEventListener('mouseleave', function() {
                    this.classList.remove('shadow-lg');
                });
            });
        });
    </script>
</body>
</html>
