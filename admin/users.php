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
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : '';
$editUserId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Form values
$formUser = [
    'id' => '',
    'name' => '',
    'email' => '',
    'phone' => '',
    'role' => 'user',
    'status' => 'active',
    'password' => '',
    'confirm_password' => ''
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = '';
    
    if (isset($_POST['add_user'])) {
        $formAction = 'add_user';
    } else if (isset($_POST['update_user'])) {
        $formAction = 'update_user';
    } else if (isset($_POST['delete_user'])) {
        $formAction = 'delete_user';
    }
    
    if ($formAction === 'add_user' || $formAction === 'update_user') {
        // Get form data
        $formUser = [
            'id' => isset($_POST['id']) ? (int)$_POST['id'] : 0,
            'name' => isset($_POST['name']) ? sanitizeInput($_POST['name']) : '',
            'email' => isset($_POST['email']) ? sanitizeInput($_POST['email']) : '',
            'phone' => isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '',
            'role' => isset($_POST['role']) ? sanitizeInput($_POST['role']) : 'user',
            'status' => isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'active',
            'password' => isset($_POST['password']) ? $_POST['password'] : '',
            'confirm_password' => isset($_POST['confirm_password']) ? $_POST['confirm_password'] : ''
        ];
        
        // Validate form data
        if (empty($formUser['name'])) {
            $errors[] = 'Name is required';
        }
        
        if (empty($formUser['email'])) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($formUser['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } else {
            // Check if email is already in use by another user
            $emailExists = fetchRow("
                SELECT id FROM users WHERE email = ? AND id != ?
            ", [$formUser['email'], $formUser['id']]);
            
            if ($emailExists) {
                $errors[] = 'Email is already in use by another user';
            }
        }
        
        if (empty($formUser['phone'])) {
            $errors[] = 'Phone is required';
        } elseif (!preg_match('/^[0-9+\-\s]{6,20}$/', $formUser['phone'])) {
            $errors[] = 'Invalid phone number format';
        }
        
        // Password validation
        if ($formAction === 'add_user') {
            if (empty($formUser['password'])) {
                $errors[] = 'Password is required';
            } elseif (strlen($formUser['password']) < 6) {
                $errors[] = 'Password must be at least 6 characters';
            } elseif ($formUser['password'] !== $formUser['confirm_password']) {
                $errors[] = 'Passwords do not match';
            }
        } else if ($formAction === 'update_user' && !empty($formUser['password'])) {
            if (strlen($formUser['password']) < 6) {
                $errors[] = 'Password must be at least 6 characters';
            } elseif ($formUser['password'] !== $formUser['confirm_password']) {
                $errors[] = 'Passwords do not match';
            }
        }
        
        // If no errors, add or update user
        if (empty($errors)) {
            try {
                $userData = [
                    'name' => $formUser['name'],
                    'email' => $formUser['email'],
                    'phone' => $formUser['phone'],
                    'role' => $formUser['role'],
                    'status' => $formUser['status']
                ];
                
                if ($formAction === 'add_user') {
                    // Add password for new user
                    $userData['password'] = password_hash($formUser['password'], PASSWORD_DEFAULT);
                    $userData['created_at'] = date('Y-m-d H:i:s');
                    
                    $insertId = insert('users', $userData);
                    
                    if ($insertId) {
                        // Log activity
                        logActivity('user_added', 'Added new user: ' . $formUser['name'], $userId);
                        
                        // Set success message and redirect
                        setFlashMessage('success', 'User added successfully.');
                        redirect(APP_URL . '/admin/users.php');
                    } else {
                        $errors[] = 'Failed to add user';
                    }
                } else {
                    // Update existing user
                    $userData['updated_at'] = date('Y-m-d H:i:s');
                    
                    // Update password if provided
                    if (!empty($formUser['password'])) {
                        $userData['password'] = password_hash($formUser['password'], PASSWORD_DEFAULT);
                    }
                    
                    $updated = update('users', $userData, "id = " . $formUser['id']);
                    
                    if ($updated !== false) {
                        // Log activity
                        logActivity('user_updated', 'Updated user: ' . $formUser['name'], $userId);
                        
                        // Set success message and redirect
                        setFlashMessage('success', 'User updated successfully.');
                        redirect(APP_URL . '/admin/users.php');
                    } else {
                        $errors[] = 'Failed to update user';
                    }
                }
            } catch (Exception $e) {
                $errors[] = 'An error occurred: ' . $e->getMessage();
            }
        }
    } else if ($formAction === 'delete_user') {
        $deleteUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        
        if ($deleteUserId <= 0) {
            $errors[] = 'Invalid user ID';
        } elseif ($deleteUserId === $userId) {
            $errors[] = 'You cannot delete your own account';
        } else {
            try {
                // Get user name before deletion for activity log
                $userToDelete = fetchRow("SELECT name FROM users WHERE id = ?", [$deleteUserId]);
                
                // Check if user exists
                if (!$userToDelete) {
                    $errors[] = 'User not found';
                } else {
                    // Check if user has associated records in other tables
                    // For example, bookings, payments, etc.
                    $hasBookings = fetchRow("
                        SELECT COUNT(*) as count FROM bookings WHERE user_id = ?
                    ", [$deleteUserId]);
                    
                    if ($hasBookings && $hasBookings['count'] > 0) {
                        $errors[] = 'Cannot delete user as they have associated bookings';
                    } else {
                        // Delete the user
                        $deleted = delete('users', "id = $deleteUserId");
                        
                        if ($deleted) {
                            // Log activity
                            logActivity('user_deleted', 'Deleted user: ' . $userToDelete['name'], $userId);
                            
                            // Set success message and redirect
                            setFlashMessage('success', 'User deleted successfully.');
                            redirect(APP_URL . '/admin/users.php');
                        } else {
                            $errors[] = 'Failed to delete user';
                        }
                    }
                }
            } catch (Exception $e) {
                $errors[] = 'An error occurred: ' . $e->getMessage();
            }
        }
    }
}

// Get user to edit if in edit mode
if ($action === 'edit' && $editUserId > 0) {
    $userToEdit = fetchRow("SELECT * FROM users WHERE id = ?", [$editUserId]);
    
    if ($userToEdit) {
        $formUser = [
            'id' => $userToEdit['id'],
            'name' => $userToEdit['name'],
            'email' => $userToEdit['email'],
            'phone' => $userToEdit['phone'],
            'role' => $userToEdit['role'],
            'status' => $userToEdit['status'],
            'password' => '',
            'confirm_password' => ''
        ];
    } else {
        setFlashMessage('error', 'User not found.');
        redirect(APP_URL . '/admin/users.php');
    }
}

// Get flash messages
$successMessage = getFlashMessage('success');
$errorMessage = getFlashMessage('error');

// Clear form data if there's a success message
if ($successMessage) {
    $success = $successMessage;
    $formUser = [
        'id' => '',
        'name' => '',
        'email' => '',
        'phone' => '',
        'role' => 'user',
        'status' => 'active',
        'password' => '',
        'confirm_password' => ''
    ];
}

// Add error message to errors array
if ($errorMessage) {
    $errors[] = $errorMessage;
}

// Fetch users for display
$filterRole = isset($_GET['role']) ? sanitizeInput($_GET['role']) : '';
$filterStatus = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build query with filters
$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($filterRole)) {
    $query .= " AND role = ?";
    $params[] = $filterRole;
}

if (!empty($filterStatus)) {
    $query .= " AND status = ?";
    $params[] = $filterStatus;
}

if (!empty($searchTerm)) {
    $query .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $searchPattern = "%$searchTerm%";
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
}

$query .= " ORDER BY id DESC";

// Fetch users based on filters
$users = fetchRows($query, $params);

// Fetch counts for dashboard
$totalUsers = fetchRow("SELECT COUNT(*) as count FROM users WHERE role = 'user'")['count'];
$totalStaff = fetchRow("SELECT COUNT(*) as count FROM users WHERE role = 'staff'")['count'];
$totalAdmins = fetchRow("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")['count'];
$totalActiveUsers = fetchRow("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'];
$totalBlockedUsers = fetchRow("SELECT COUNT(*) as count FROM users WHERE status = 'blocked'")['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Metro Rail Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/admin.css">
    <style>
        .user-card {
            transition: transform 0.3s ease;
        }
        .user-card:hover {
            transform: translateY(-5px);
        }
        .role-badge {
            font-size: 0.8rem;
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
        }
    </style>
</head>
<body>    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/admin_sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-people me-2"></i>User Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="bi bi-person-plus"></i> Add New User
                        </button>
                    </div>
                </div>
                
                <!-- Dashboard Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5 class="card-title">Total Users</h5>
                                <p class="card-text fs-3"><?= $totalUsers ?> <small class="fs-6">Users</small></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h5 class="card-title">Staff Members</h5>
                                <p class="card-text fs-3"><?= $totalStaff ?> <small class="fs-6">Staff</small></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <h5 class="card-title">Administrators</h5>
                                <p class="card-text fs-3"><?= $totalAdmins ?> <small class="fs-6">Admins</small></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Filter Users</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="get" class="row g-3">
                            <div class="col-md-4">
                                <label for="role" class="form-label">Role</label>
                                <select name="role" id="role" class="form-select">
                                    <option value="">All Roles</option>
                                    <option value="user" <?= $filterRole === 'user' ? 'selected' : '' ?>>User</option>
                                    <option value="staff" <?= $filterRole === 'staff' ? 'selected' : '' ?>>Staff</option>
                                    <option value="admin" <?= $filterRole === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    <option value="blocked" <?= $filterStatus === 'blocked' ? 'selected' : '' ?>>Blocked</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" placeholder="Name, Email, Phone" value="<?= htmlspecialchars($searchTerm) ?>">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="<?= APP_URL ?>/admin/users.php" class="btn btn-secondary">Clear Filters</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Alert Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error!</strong>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Success!</strong> <?= $success ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Users Table -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Users List</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No users found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?= $user['id'] ?></td>
                                                <td><?= htmlspecialchars($user['name']) ?></td>
                                                <td><?= htmlspecialchars($user['email']) ?></td>
                                                <td><?= htmlspecialchars($user['phone']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= 
                                                        $user['role'] === 'admin' ? 'info' : 
                                                        ($user['role'] === 'staff' ? 'success' : 'primary') 
                                                    ?>">
                                                        <?= ucfirst($user['role']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= 
                                                        $user['status'] === 'active' ? 'success' : 
                                                        ($user['status'] === 'inactive' ? 'warning' : 'danger') 
                                                    ?>">
                                                        <?= ucfirst($user['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="<?= APP_URL ?>/admin/users.php?action=edit&id=<?= $user['id'] ?>" class="btn btn-sm btn-primary">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <?php if ($user['id'] !== $userId): ?>
                                                            <button type="button" class="btn btn-sm btn-danger delete-user" data-user-id="<?= $user['id'] ?>" data-user-name="<?= htmlspecialchars($user['name']) ?>">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($formUser['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($formUser['email']) ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($formUser['phone']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="user" <?= $formUser['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                    <option value="staff" <?= $formUser['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                                    <option value="admin" <?= $formUser['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active" <?= $formUser['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $formUser['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    <option value="blocked" <?= $formUser['status'] === 'blocked' ? 'selected' : '' ?>>Blocked</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <?php if ($action === 'edit' && isset($userToEdit)): ?>
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="" method="post">
                    <input type="hidden" name="id" value="<?= $formUser['id'] ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                        <a href="<?= APP_URL ?>/admin/users.php" class="btn-close" aria-label="Close"></a>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="edit_name" name="name" value="<?= htmlspecialchars($formUser['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email" value="<?= htmlspecialchars($formUser['email']) ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="edit_phone" name="phone" value="<?= htmlspecialchars($formUser['phone']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_role" class="form-label">Role</label>
                                <select class="form-select" id="edit_role" name="role" required>
                                    <option value="user" <?= $formUser['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                    <option value="staff" <?= $formUser['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                                    <option value="admin" <?= $formUser['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_status" name="status" required>
                                    <option value="active" <?= $formUser['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $formUser['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    <option value="blocked" <?= $formUser['status'] === 'blocked' ? 'selected' : '' ?>>Blocked</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_password" class="form-label">Password (leave blank to keep current)</label>
                                <input type="password" class="form-control" id="edit_password" name="password">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="edit_confirm_password" name="confirm_password">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="<?= APP_URL ?>/admin/users.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="post">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteUserModalLabel">Confirm Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete the user <strong id="delete_user_name"></strong>?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="<?= APP_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show edit modal if in edit mode
        <?php if ($action === 'edit'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            var editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
            editModal.show();
        });
        <?php endif; ?>
        
        // Delete user modal
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-user');
            const deleteUserIdInput = document.getElementById('delete_user_id');
            const deleteUserNameSpan = document.getElementById('delete_user_name');
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    const userName = this.getAttribute('data-user-name');
                    
                    deleteUserIdInput.value = userId;
                    deleteUserNameSpan.textContent = userName;
                    
                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
                    deleteModal.show();
                });
            });
        });
    </script>
</body>
</html>
