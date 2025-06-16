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
$announcementId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Form values
$formAnnouncement = [
    'id' => '',
    'title' => '',
    'content' => '',
    'start_date' => '',
    'end_date' => '',
    'status' => 'active'
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = '';
    
    if (isset($_POST['add_announcement'])) {
        $formAction = 'add_announcement';
    } else if (isset($_POST['update_announcement'])) {
        $formAction = 'update_announcement';
    } else if (isset($_POST['delete_announcement'])) {
        $formAction = 'delete_announcement';
    }
    
    if ($formAction === 'add_announcement' || $formAction === 'update_announcement') {
        // Get form data
        $formAnnouncement = [
            'id' => isset($_POST['id']) ? (int)$_POST['id'] : 0,
            'title' => isset($_POST['title']) ? sanitizeInput($_POST['title']) : '',
            'content' => isset($_POST['content']) ? sanitizeInput($_POST['content']) : '',
            'start_date' => isset($_POST['start_date']) ? sanitizeInput($_POST['start_date']) : '',
            'end_date' => isset($_POST['end_date']) ? sanitizeInput($_POST['end_date']) : '',
            'status' => isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'active'
        ];
        
        // Validate form data
        if (empty($formAnnouncement['title'])) {
            $errors[] = 'Title is required';
        }
        
        if (empty($formAnnouncement['content'])) {
            $errors[] = 'Content is required';
        }
        
        if (empty($formAnnouncement['start_date'])) {
            $errors[] = 'Start date is required';
        }
        
        if (empty($formAnnouncement['end_date'])) {
            $errors[] = 'End date is required';
        } elseif (strtotime($formAnnouncement['end_date']) < strtotime($formAnnouncement['start_date'])) {
            $errors[] = 'End date must be after start date';
        }
        
        // If no errors, add or update announcement
        if (empty($errors)) {
            try {
                $announcementData = [
                    'title' => $formAnnouncement['title'],
                    'content' => $formAnnouncement['content'],
                    'start_date' => $formAnnouncement['start_date'],
                    'end_date' => $formAnnouncement['end_date'],
                    'status' => $formAnnouncement['status'],
                    'created_by' => $userId
                ];
                
                if ($formAction === 'add_announcement') {
                    $announcementData['created_at'] = date('Y-m-d H:i:s');
                    
                    $insertId = insert('announcements', $announcementData);
                    
                    if ($insertId) {
                        // Log activity
                        logActivity('announcement_added', 'Added new announcement: ' . $formAnnouncement['title'], $userId);
                        
                        // Set success message and redirect
                        setFlashMessage('success', 'Announcement added successfully.');
                        redirect(APP_URL . '/admin/announcements.php');
                    } else {
                        $errors[] = 'Failed to add announcement';
                    }
                } else {
                    $announcementData['updated_at'] = date('Y-m-d H:i:s');
                    
                    $updated = update('announcements', $announcementData, "id = " . $formAnnouncement['id']);
                    
                    if ($updated !== false) {
                        // Log activity
                        logActivity('announcement_updated', 'Updated announcement: ' . $formAnnouncement['title'], $userId);
                        
                        // Set success message and redirect
                        setFlashMessage('success', 'Announcement updated successfully.');
                        redirect(APP_URL . '/admin/announcements.php');
                    } else {
                        $errors[] = 'Failed to update announcement';
                    }
                }
            } catch (Exception $e) {
                $errors[] = 'An error occurred: ' . $e->getMessage();
            }
        }
    } else if ($formAction === 'delete_announcement') {
        $deleteAnnouncementId = isset($_POST['announcement_id']) ? (int)$_POST['announcement_id'] : 0;
        
        if ($deleteAnnouncementId <= 0) {
            $errors[] = 'Invalid announcement ID';
        } else {
            try {
                // Get announcement title before deletion for activity log
                $announcementToDelete = fetchRow("SELECT title FROM announcements WHERE id = ?", [$deleteAnnouncementId]);
                
                if (!$announcementToDelete) {
                    $errors[] = 'Announcement not found';
                } else {
                    // Delete the announcement
                    $deleted = delete('announcements', "id = $deleteAnnouncementId");
                    
                    if ($deleted) {
                        // Log activity
                        logActivity('announcement_deleted', 'Deleted announcement: ' . $announcementToDelete['title'], $userId);
                        
                        // Set success message and redirect
                        setFlashMessage('success', 'Announcement deleted successfully.');
                        redirect(APP_URL . '/admin/announcements.php');
                    } else {
                        $errors[] = 'Failed to delete announcement';
                    }
                }
            } catch (Exception $e) {
                $errors[] = 'An error occurred: ' . $e->getMessage();
            }
        }
    }
}

// Get announcement to edit if in edit mode
if ($action === 'edit' && $announcementId > 0) {
    $announcementToEdit = fetchRow("SELECT * FROM announcements WHERE id = ?", [$announcementId]);
    
    if ($announcementToEdit) {
        $formAnnouncement = [
            'id' => $announcementToEdit['id'],
            'title' => $announcementToEdit['title'],
            'content' => $announcementToEdit['content'],
            'start_date' => $announcementToEdit['start_date'],
            'end_date' => $announcementToEdit['end_date'],
            'status' => $announcementToEdit['status']
        ];
    } else {
        setFlashMessage('error', 'Announcement not found.');
        redirect(APP_URL . '/admin/announcements.php');
    }
}

// Get flash messages
$successMessage = getFlashMessage('success');
$errorMessage = getFlashMessage('error');

// Clear form data if there's a success message
if ($successMessage) {
    $success = $successMessage;
    $formAnnouncement = [
        'id' => '',
        'title' => '',
        'content' => '',
        'start_date' => '',
        'end_date' => '',
        'status' => 'active'
    ];
}

// Add error message to errors array
if ($errorMessage) {
    $errors[] = $errorMessage;
}

// Fetch announcements for display
$filterStatus = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$filterDate = isset($_GET['date']) ? sanitizeInput($_GET['date']) : '';

// Build query with filters
$query = "SELECT a.*, u.name as created_by_name 
          FROM announcements a
          LEFT JOIN users u ON a.created_by = u.id
          WHERE 1=1";
$params = [];

if (!empty($filterStatus)) {
    $query .= " AND a.status = ?";
    $params[] = $filterStatus;
}

if (!empty($searchTerm)) {
    $query .= " AND (a.title LIKE ? OR a.content LIKE ?)";
    $searchPattern = "%$searchTerm%";
    $params[] = $searchPattern;
    $params[] = $searchPattern;
}

if (!empty($filterDate)) {
    $query .= " AND (? BETWEEN a.start_date AND a.end_date)";
    $params[] = $filterDate;
}

$query .= " ORDER BY a.status ASC, a.start_date DESC";

// Fetch announcements based on filters
$announcements = fetchRows($query, $params);

// Fetch counts for dashboard
$totalAnnouncements = fetchRow("SELECT COUNT(*) as count FROM announcements")['count'];
$activeAnnouncements = fetchRow("SELECT COUNT(*) as count FROM announcements WHERE status = 'active'")['count'];
$inactiveAnnouncements = fetchRow("SELECT COUNT(*) as count FROM announcements WHERE status = 'inactive'")['count'];
$currentAnnouncements = fetchRow("SELECT COUNT(*) as count FROM announcements WHERE status = 'active' AND CURDATE() BETWEEN start_date AND end_date")['count'];
$upcomingAnnouncements = fetchRow("SELECT COUNT(*) as count FROM announcements WHERE status = 'active' AND start_date > CURDATE()")['count'];
$pastAnnouncements = fetchRow("SELECT COUNT(*) as count FROM announcements WHERE end_date < CURDATE()")['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Metro Rail Admin</title>    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/admin.css">
    <style>
        .announcement-card {
            border-left: 4px solid #ccc;
        }
        .status-active {
            border-left-color: #198754;
        }
        .status-inactive {
            border-left-color: #dc3545;
        }
        .announcement-time-label {
            font-size: 0.8rem;
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
        }
        .current {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .upcoming {
            background-color: #cfe2ff;
            color: #084298;
        }
        .past {
            background-color: #f8d7da;
            color: #842029;
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
                    <h1 class="h2">Announcements</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                            <i class="bi bi-megaphone"></i> Add Announcement
                        </button>
                    </div>
                </div>
                
                <!-- Dashboard Cards -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card text-white bg-primary">
                            <div class="card-body py-2">
                                <h6 class="card-title">Total</h6>
                                <p class="card-text fs-4 mb-0"><?= $totalAnnouncements ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-white bg-success">
                            <div class="card-body py-2">
                                <h6 class="card-title">Active</h6>
                                <p class="card-text fs-4 mb-0"><?= $activeAnnouncements ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-white bg-danger">
                            <div class="card-body py-2">
                                <h6 class="card-title">Inactive</h6>
                                <p class="card-text fs-4 mb-0"><?= $inactiveAnnouncements ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-white bg-info">
                            <div class="card-body py-2">
                                <h6 class="card-title">Current</h6>
                                <p class="card-text fs-4 mb-0"><?= $currentAnnouncements ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-white bg-warning">
                            <div class="card-body py-2">
                                <h6 class="card-title">Upcoming</h6>
                                <p class="card-text fs-4 mb-0"><?= $upcomingAnnouncements ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-white bg-secondary">
                            <div class="card-body py-2">
                                <h6 class="card-title">Past</h6>
                                <p class="card-text fs-4 mb-0"><?= $pastAnnouncements ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Filter Announcements</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="get" class="row g-3">
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="date" class="form-label">View Announcements For Date</label>
                                <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($filterDate) ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" placeholder="Title or Content" value="<?= htmlspecialchars($searchTerm) ?>">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="<?= APP_URL ?>/admin/announcements.php" class="btn btn-secondary">Clear Filters</a>
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
                
                <!-- Announcements -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Announcements</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($announcements)): ?>
                            <div class="alert alert-info mb-0">
                                No announcements found. Click the "Add Announcement" button to create one.
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($announcements as $announcement): ?>
                                    <?php
                                    // Determine if announcement is current, upcoming, or past
                                    $today = date('Y-m-d');
                                    $startDate = $announcement['start_date'];
                                    $endDate = $announcement['end_date'];
                                    
                                    $timeStatus = '';
                                    $timeLabel = '';
                                    
                                    if ($today >= $startDate && $today <= $endDate && $announcement['status'] === 'active') {
                                        $timeStatus = 'current';
                                        $timeLabel = 'Current';
                                    } elseif ($today < $startDate) {
                                        $timeStatus = 'upcoming';
                                        $timeLabel = 'Upcoming';
                                    } else {
                                        $timeStatus = 'past';
                                        $timeLabel = 'Past';
                                    }
                                    
                                    // Format dates
                                    $formattedStartDate = date('M d, Y', strtotime($startDate));
                                    $formattedEndDate = date('M d, Y', strtotime($endDate));
                                    ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100 announcement-card status-<?= $announcement['status'] ?>">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h5 class="card-title mb-0"><?= htmlspecialchars($announcement['title']) ?></h5>
                                                <div>
                                                    <span class="announcement-time-label <?= $timeStatus ?>"><?= $timeLabel ?></span>
                                                    <span class="badge bg-<?= $announcement['status'] === 'active' ? 'success' : 'danger' ?>">
                                                        <?= ucfirst($announcement['status']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <p class="card-text">
                                                    <?= nl2br(htmlspecialchars($announcement['content'])) ?>
                                                </p>
                                                
                                                <div class="mt-3">
                                                    <strong>Display Period:</strong><br>
                                                    <i class="bi bi-calendar-event"></i> 
                                                    <?= $formattedStartDate ?> to <?= $formattedEndDate ?>
                                                </div>
                                                
                                                <div class="mt-2 small text-muted">
                                                    Created by: <?= htmlspecialchars($announcement['created_by_name']) ?> on 
                                                    <?= date('M d, Y', strtotime($announcement['created_at'])) ?>
                                                </div>
                                            </div>
                                            <div class="card-footer d-flex justify-content-between">
                                                <div>
                                                    <a href="<?= APP_URL ?>/admin/announcements.php?action=edit&id=<?= $announcement['id'] ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger delete-announcement" 
                                                            data-announcement-id="<?= $announcement['id'] ?>" 
                                                            data-announcement-title="<?= htmlspecialchars($announcement['title']) ?>">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </div>
                                                <div>
                                                    <?php if ($announcement['status'] === 'active'): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-danger toggle-status" 
                                                                data-announcement-id="<?= $announcement['id'] ?>" 
                                                                data-status="inactive">
                                                            <i class="bi bi-x-circle"></i> Deactivate
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-sm btn-outline-success toggle-status" 
                                                                data-announcement-id="<?= $announcement['id'] ?>" 
                                                                data-status="active">
                                                            <i class="bi bi-check-circle"></i> Activate
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Announcement Modal -->
    <div class="modal fade" id="addAnnouncementModal" tabindex="-1" aria-labelledby="addAnnouncementModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addAnnouncementModalLabel">Add Announcement</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($formAnnouncement['title']) ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="content" class="form-label">Content</label>
                                <textarea class="form-control" id="content" name="content" rows="5" required><?= htmlspecialchars($formAnnouncement['content']) ?></textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($formAnnouncement['start_date']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($formAnnouncement['end_date']) ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active" <?= $formAnnouncement['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $formAnnouncement['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_announcement" class="btn btn-primary">Add Announcement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Announcement Modal -->
    <?php if ($action === 'edit' && isset($announcementToEdit)): ?>
    <div class="modal fade" id="editAnnouncementModal" tabindex="-1" aria-labelledby="editAnnouncementModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="" method="post">
                    <input type="hidden" name="id" value="<?= $formAnnouncement['id'] ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editAnnouncementModalLabel">Edit Announcement</h5>
                        <a href="<?= APP_URL ?>/admin/announcements.php" class="btn-close" aria-label="Close"></a>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="edit_title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="edit_title" name="title" value="<?= htmlspecialchars($formAnnouncement['title']) ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="edit_content" class="form-label">Content</label>
                                <textarea class="form-control" id="edit_content" name="content" rows="5" required><?= htmlspecialchars($formAnnouncement['content']) ?></textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="edit_start_date" name="start_date" value="<?= htmlspecialchars($formAnnouncement['start_date']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="edit_end_date" name="end_date" value="<?= htmlspecialchars($formAnnouncement['end_date']) ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_status" name="status" required>
                                    <option value="active" <?= $formAnnouncement['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $formAnnouncement['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="<?= APP_URL ?>/admin/announcements.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" name="update_announcement" class="btn btn-primary">Update Announcement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Delete Announcement Modal -->
    <div class="modal fade" id="deleteAnnouncementModal" tabindex="-1" aria-labelledby="deleteAnnouncementModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="post">
                    <input type="hidden" name="announcement_id" id="delete_announcement_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteAnnouncementModalLabel">Confirm Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete the announcement <strong id="delete_announcement_title"></strong>?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_announcement" class="btn btn-danger">Delete Announcement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Toggle Status Modal -->
    <div class="modal fade" id="toggleStatusModal" tabindex="-1" aria-labelledby="toggleStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="post">
                    <input type="hidden" name="id" id="toggle_announcement_id">
                    <input type="hidden" name="title" id="toggle_announcement_title">
                    <input type="hidden" name="content" id="toggle_announcement_content">
                    <input type="hidden" name="start_date" id="toggle_start_date">
                    <input type="hidden" name="end_date" id="toggle_end_date">
                    <input type="hidden" name="status" id="toggle_status">
                    <div class="modal-header">
                        <h5 class="modal-title" id="toggleStatusModalLabel">Confirm Status Change</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to <span id="toggle_action_text"></span> this announcement?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_announcement" class="btn btn-primary">Confirm</button>
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
            var editModal = new bootstrap.Modal(document.getElementById('editAnnouncementModal'));
            editModal.show();
        });
        <?php endif; ?>
        
        document.addEventListener('DOMContentLoaded', function() {
            // Delete announcement modal
            const deleteButtons = document.querySelectorAll('.delete-announcement');
            const deleteAnnouncementIdInput = document.getElementById('delete_announcement_id');
            const deleteAnnouncementTitleSpan = document.getElementById('delete_announcement_title');
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const announcementId = this.getAttribute('data-announcement-id');
                    const announcementTitle = this.getAttribute('data-announcement-title');
                    
                    deleteAnnouncementIdInput.value = announcementId;
                    deleteAnnouncementTitleSpan.textContent = announcementTitle;
                    
                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteAnnouncementModal'));
                    deleteModal.show();
                });
            });
            
            // Toggle status
            const toggleButtons = document.querySelectorAll('.toggle-status');
            const toggleAnnouncementIdInput = document.getElementById('toggle_announcement_id');
            const toggleStatusInput = document.getElementById('toggle_status');
            const toggleActionTextSpan = document.getElementById('toggle_action_text');
            
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const announcementId = this.getAttribute('data-announcement-id');
                    const status = this.getAttribute('data-status');
                    
                    // Get the full announcement data from the page
                    const card = this.closest('.card');
                    const title = card.querySelector('.card-title').textContent;
                    const content = card.querySelector('.card-text').textContent.trim();
                    
                    // Get dates from the page (this would need to be improved for a real implementation)
                    const datesText = card.querySelector('.mt-3').textContent;
                    const dates = datesText.match(/(\w+ \d+, \d+) to (\w+ \d+, \d+)/);
                    
                    // Set all necessary form values
                    document.getElementById('toggle_announcement_id').value = announcementId;
                    document.getElementById('toggle_announcement_title').value = title;
                    document.getElementById('toggle_announcement_content').value = content;
                    document.getElementById('toggle_status').value = status;
                    
                    // Set action text
                    toggleActionTextSpan.textContent = status === 'active' ? 'activate' : 'deactivate';
                    
                    const toggleModal = new bootstrap.Modal(document.getElementById('toggleStatusModal'));
                    toggleModal.show();
                });
            });
        });
    </script>
</body>
</html>
