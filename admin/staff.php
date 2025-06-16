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
$editStaffId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Form values
$formStaff = [
    'id' => '',
    'name' => '',
    'email' => '',
    'phone' => '',
    'role' => 'staff',
    'status' => 'active',
    'password' => '',
    'confirm_password' => ''
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = '';
    
    if (isset($_POST['add_staff'])) {
        $formAction = 'add_staff';
    } else if (isset($_POST['update_staff'])) {
        $formAction = 'update_staff';
    } else if (isset($_POST['delete_staff'])) {
        $formAction = 'delete_staff';
    }
    
    if ($formAction === 'add_staff' || $formAction === 'update_staff') {
        // Get form data
        $formStaff = [
            'id' => isset($_POST['id']) ? (int)$_POST['id'] : 0,
            'name' => isset($_POST['name']) ? sanitizeInput($_POST['name']) : '',
            'email' => isset($_POST['email']) ? sanitizeInput($_POST['email']) : '',
            'phone' => isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '',
            'role' => 'staff', // Force role to be staff
            'status' => isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'active',
            'password' => isset($_POST['password']) ? $_POST['password'] : '',
            'confirm_password' => isset($_POST['confirm_password']) ? $_POST['confirm_password'] : ''
        ];
        
        // Validate form data
        if (empty($formStaff['name'])) {
            $errors[] = 'Name is required';
        }
        
        if (empty($formStaff['email'])) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($formStaff['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } else {
            // Check if email is already in use by another user
            $emailExists = fetchRow("
                SELECT id FROM users WHERE email = ? AND id != ?
            ", [$formStaff['email'], $formStaff['id']]);
            
            if ($emailExists) {
                $errors[] = 'Email is already in use by another user';
            }
        }
        
        if (empty($formStaff['phone'])) {
            $errors[] = 'Phone is required';
        } elseif (!preg_match('/^[0-9+\-\s]{6,20}$/', $formStaff['phone'])) {
            $errors[] = 'Invalid phone number format';
        }
        
        // Password validation
        if ($formAction === 'add_staff') {
            if (empty($formStaff['password'])) {
                $errors[] = 'Password is required';
            } elseif (strlen($formStaff['password']) < 6) {
                $errors[] = 'Password must be at least 6 characters';
            } elseif ($formStaff['password'] !== $formStaff['confirm_password']) {
                $errors[] = 'Passwords do not match';
            }
        } else if ($formAction === 'update_staff' && !empty($formStaff['password'])) {
            if (strlen($formStaff['password']) < 6) {
                $errors[] = 'Password must be at least 6 characters';
            } elseif ($formStaff['password'] !== $formStaff['confirm_password']) {
                $errors[] = 'Passwords do not match';
            }
        }
        
        // If no errors, add or update staff
        if (empty($errors)) {
            try {
                $staffData = [
                    'name' => $formStaff['name'],
                    'email' => $formStaff['email'],
                    'phone' => $formStaff['phone'],
                    'role' => 'staff', // Force role to be staff
                    'status' => $formStaff['status']
                ];
                
                if ($formAction === 'add_staff') {
                    // Add password for new staff
                    $staffData['password'] = password_hash($formStaff['password'], PASSWORD_DEFAULT);
                    $staffData['created_at'] = date('Y-m-d H:i:s');
                    
                    $insertId = insert('users', $staffData);
                    
                    if ($insertId) {
                        // Log activity
                        logActivity('staff_added', 'Added new staff member: ' . $formStaff['name'], $userId);
                        
                        // Set success message and redirect
                        setFlashMessage('success', 'Staff member added successfully.');
                        redirect(APP_URL . '/admin/staff.php');
                    } else {
                        $errors[] = 'Failed to add staff member';
                    }
                } else {
                    // Update existing staff
                    $staffData['updated_at'] = date('Y-m-d H:i:s');
                    
                    // Update password if provided
                    if (!empty($formStaff['password'])) {
                        $staffData['password'] = password_hash($formStaff['password'], PASSWORD_DEFAULT);
                    }
                    
                    $updated = update('users', $staffData, "id = " . $formStaff['id']);
                    
                    if ($updated !== false) {
                        // Log activity
                        logActivity('staff_updated', 'Updated staff member: ' . $formStaff['name'], $userId);
                        
                        // Set success message and redirect
                        setFlashMessage('success', 'Staff member updated successfully.');
                        redirect(APP_URL . '/admin/staff.php');
                    } else {
                        $errors[] = 'Failed to update staff member';
                    }
                }
            } catch (Exception $e) {
                $errors[] = 'An error occurred: ' . $e->getMessage();
            }
        }
    } else if ($formAction === 'delete_staff') {
        $deleteStaffId = isset($_POST['staff_id']) ? (int)$_POST['staff_id'] : 0;
        
        if ($deleteStaffId <= 0) {
            $errors[] = 'Invalid staff ID';
        } else {
            try {
                // Get staff name before deletion for activity log
                $staffToDelete = fetchRow("SELECT name FROM users WHERE id = ? AND role = 'staff'", [$deleteStaffId]);
                
                // Check if staff exists
                if (!$staffToDelete) {
                    $errors[] = 'Staff member not found';
                } else {
                    // Check if staff has associated records in other tables
                    // For example, maintenance schedules, announcements, etc.
                    $hasMaintenanceSchedules = fetchRow("
                        SELECT COUNT(*) as count FROM maintenance_schedules WHERE created_by = ?
                    ", [$deleteStaffId]);
                    
                    $hasAnnouncements = fetchRow("
                        SELECT COUNT(*) as count FROM announcements WHERE created_by = ?
                    ", [$deleteStaffId]);
                    
                    if (($hasMaintenanceSchedules && $hasMaintenanceSchedules['count'] > 0) || 
                        ($hasAnnouncements && $hasAnnouncements['count'] > 0)) {
                        $errors[] = 'Cannot delete staff as they have associated records';
                    } else {
                        // Delete the staff
                        $deleted = delete('users', "id = $deleteStaffId AND role = 'staff'");
                        
                        if ($deleted) {
                            // Log activity
                            logActivity('staff_deleted', 'Deleted staff member: ' . $staffToDelete['name'], $userId);
                            
                            // Set success message and redirect
                            setFlashMessage('success', 'Staff member deleted successfully.');
                            redirect(APP_URL . '/admin/staff.php');
                        } else {
                            $errors[] = 'Failed to delete staff member';
                        }
                    }
                }
            } catch (Exception $e) {
                $errors[] = 'An error occurred: ' . $e->getMessage();
            }
        }
    }
}

// Get staff to edit if in edit mode
if ($action === 'edit' && $editStaffId > 0) {
    $staffToEdit = fetchRow("SELECT * FROM users WHERE id = ? AND role = 'staff'", [$editStaffId]);
    
    if ($staffToEdit) {
        $formStaff = [
            'id' => $staffToEdit['id'],
            'name' => $staffToEdit['name'],
            'email' => $staffToEdit['email'],
            'phone' => $staffToEdit['phone'],
            'role' => 'staff',
            'status' => $staffToEdit['status'],
            'password' => '',
            'confirm_password' => ''
        ];
    } else {
        setFlashMessage('error', 'Staff member not found.');
        redirect(APP_URL . '/admin/staff.php');
    }
}

// Get flash messages
$successMessage = getFlashMessage('success');
$errorMessage = getFlashMessage('error');

// Clear form data if there's a success message
if ($successMessage) {
    $success = $successMessage;
    $formStaff = [
        'id' => '',
        'name' => '',
        'email' => '',
        'phone' => '',
        'role' => 'staff',
        'status' => 'active',
        'password' => '',
        'confirm_password' => ''
    ];
}

// Add error message to errors array
if ($errorMessage) {
    $errors[] = $errorMessage;
}

// Fetch staff for display
$filterStatus = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build query with filters
$query = "SELECT * FROM users WHERE role = 'staff'";
$params = [];

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

// Fetch staff based on filters
$staffMembers = fetchRows($query, $params);

// Fetch counts for dashboard
$totalStaff = fetchRow("SELECT COUNT(*) as count FROM users WHERE role = 'staff'")['count'];
$activeStaff = fetchRow("SELECT COUNT(*) as count FROM users WHERE role = 'staff' AND status = 'active'")['count'];
$inactiveStaff = fetchRow("SELECT COUNT(*) as count FROM users WHERE role = 'staff' AND status = 'inactive'")['count'];
$blockedStaff = fetchRow("SELECT COUNT(*) as count FROM users WHERE role = 'staff' AND status = 'blocked'")['count'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - Metro Rail Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/admin.css">
    <style>
        .staff-card {
            transition: transform 0.3s ease;
        }
        .staff-card:hover {
            transform: translateY(-5px);
        }
        .role-badge {
            font-size: 0.8rem;
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/admin_sidebar.php'; ?>
              <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-people-fill me-2"></i>Staff Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                            <i class="bi bi-person-plus"></i> Add New Staff
                        </button>
                    </div>
                </div>
                  <!-- Dashboard Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card staff-card shadow-sm">
                            <div class="card-body bg-primary text-white rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Staff</h6>
                                        <h2 class="mb-0"><?= $totalStaff ?></h2>
                                    </div>
                                    <div>
                                        <i class="bi bi-people-fill fs-1 opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card staff-card shadow-sm">
                            <div class="card-body bg-success text-white rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Active</h6>
                                        <h2 class="mb-0"><?= $activeStaff ?></h2>
                                    </div>
                                    <div>
                                        <i class="bi bi-check-circle-fill fs-1 opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card staff-card shadow-sm">
                            <div class="card-body bg-warning text-white rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Inactive</h6>
                                        <h2 class="mb-0"><?= $inactiveStaff ?></h2>
                                    </div>
                                    <div>
                                        <i class="bi bi-dash-circle-fill fs-1 opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card staff-card shadow-sm">
                            <div class="card-body bg-danger text-white rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Blocked</h6>
                                        <h2 class="mb-0"><?= $blockedStaff ?></h2>
                                    </div>
                                    <div>
                                        <i class="bi bi-x-circle-fill fs-1 opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                  <!-- Filter Form -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="bi bi-funnel me-2"></i>Filter Staff</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="get" class="row g-3">
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    <option value="blocked" <?= $filterStatus === 'blocked' ? 'selected' : '' ?>>Blocked</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="search" class="form-label">Search</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="search" name="search" placeholder="Name, Email, Phone" value="<?= htmlspecialchars($searchTerm) ?>">
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-funnel-fill me-1"></i> Apply Filters
                                </button>
                                <a href="<?= APP_URL ?>/admin/staff.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle me-1"></i> Clear Filters
                                </a>
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
                  <!-- Staff Table -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="bi bi-list-ul me-2"></i>Staff List</h5>
                        <span class="badge bg-primary"><?= count($staffMembers) ?> Records</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($staffMembers)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <div class="d-flex flex-column align-items-center">
                                                    <i class="bi bi-exclamation-circle text-muted mb-2" style="font-size: 2rem;"></i>
                                                    <p class="text-muted mb-0">No staff members found.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($staffMembers as $staff): ?>
                                            <tr>
                                                <td><?= $staff['id'] ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-initials bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                                            <?= strtoupper(substr($staff['name'], 0, 1)) ?>
                                                        </div>
                                                        <div>
                                                            <?= htmlspecialchars($staff['name']) ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($staff['email']) ?></td>
                                                <td><?= htmlspecialchars($staff['phone']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= 
                                                        $staff['status'] === 'active' ? 'success' : 
                                                        ($staff['status'] === 'inactive' ? 'warning' : 'danger') 
                                                    ?>">
                                                        <?= ucfirst($staff['status']) ?>
                                                    </span>
                                                </td>
                                                <td><i class="bi bi-calendar-event me-1"></i><?= date('d M Y', strtotime($staff['created_at'])) ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="<?= APP_URL ?>/admin/staff.php?action=edit&id=<?= $staff['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger delete-staff" data-bs-toggle="modal" data-bs-target="#deleteStaffModal<?= $staff['id'] ?>">
                                                            <i class="bi bi-trash"></i> Delete
                                                        </button>
                                                    </div>
                                                    
                                                    <!-- Delete Modal -->
                                                    <div class="modal fade" id="deleteStaffModal<?= $staff['id'] ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Confirm Delete</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Are you sure you want to delete staff member: <strong><?= htmlspecialchars($staff['name']) ?></strong>?</p>
                                                                    <p class="text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>This action cannot be undone.</p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <form method="post">
                                                                        <input type="hidden" name="delete_id" value="<?= $staff['id'] ?>">
                                                                        <button type="submit" name="delete_staff" class="btn btn-danger">Delete Staff</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>>
                                                            <i class="bi bi-trash"></i>
                                                        </button>
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
    
    <!-- Add Staff Modal -->
    <div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addStaffModalLabel">Add New Staff Member</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($formStaff['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($formStaff['email']) ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($formStaff['phone']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active" <?= $formStaff['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $formStaff['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    <option value="blocked" <?= $formStaff['status'] === 'blocked' ? 'selected' : '' ?>>Blocked</option>
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
                        <button type="submit" name="add_staff" class="btn btn-success">Add Staff</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Staff Modal -->
    <?php if ($action === 'edit' && isset($staffToEdit)): ?>
    <div class="modal fade" id="editStaffModal" tabindex="-1" aria-labelledby="editStaffModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="" method="post">
                    <input type="hidden" name="id" value="<?= $formStaff['id'] ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editStaffModalLabel">Edit Staff Member</h5>
                        <a href="<?= APP_URL ?>/admin/staff.php" class="btn-close" aria-label="Close"></a>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="edit_name" name="name" value="<?= htmlspecialchars($formStaff['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email" value="<?= htmlspecialchars($formStaff['email']) ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="edit_phone" name="phone" value="<?= htmlspecialchars($formStaff['phone']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_status" name="status" required>
                                    <option value="active" <?= $formStaff['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $formStaff['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    <option value="blocked" <?= $formStaff['status'] === 'blocked' ? 'selected' : '' ?>>Blocked</option>
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
                        <a href="<?= APP_URL ?>/admin/staff.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" name="update_staff" class="btn btn-primary">Update Staff</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Delete Staff Modal -->
    <div class="modal fade" id="deleteStaffModal" tabindex="-1" aria-labelledby="deleteStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="post">
                    <input type="hidden" name="staff_id" id="delete_staff_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteStaffModalLabel">Confirm Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete the staff member <strong id="delete_staff_name"></strong>?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_staff" class="btn btn-danger">Delete Staff</button>
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
            var editModal = new bootstrap.Modal(document.getElementById('editStaffModal'));
            editModal.show();
        });
        <?php endif; ?>
        
        // Delete staff modal
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-staff');
            const deleteStaffIdInput = document.getElementById('delete_staff_id');
            const deleteStaffNameSpan = document.getElementById('delete_staff_name');
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const staffId = this.getAttribute('data-staff-id');
                    const staffName = this.getAttribute('data-staff-name');
                    
                    deleteStaffIdInput.value = staffId;
                    deleteStaffNameSpan.textContent = staffName;
                    
                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteStaffModal'));
                    deleteModal.show();
                });
            });
        });
    </script>
</body>
</html>
