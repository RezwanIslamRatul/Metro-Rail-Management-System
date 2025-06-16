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
$maintenanceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get routes and stations for dropdowns
$routes = fetchRows("SELECT id, name, code FROM routes WHERE status = 'active' ORDER BY name ASC");
$stations = fetchRows("SELECT id, name, code FROM stations WHERE status = 'active' ORDER BY name ASC");

// Form values
$formMaintenance = [
    'id' => '',
    'title' => '',
    'description' => '',
    'start_datetime' => '',
    'end_datetime' => '',
    'affected_routes' => [],
    'affected_stations' => [],
    'status' => 'scheduled'
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = '';
    
    if (isset($_POST['add_maintenance'])) {
        $formAction = 'add_maintenance';
    } else if (isset($_POST['update_maintenance'])) {
        $formAction = 'update_maintenance';
    } else if (isset($_POST['delete_maintenance'])) {
        $formAction = 'delete_maintenance';
    }
    
    if ($formAction === 'add_maintenance' || $formAction === 'update_maintenance') {
        // Get form data
        $formMaintenance = [
            'id' => isset($_POST['id']) ? (int)$_POST['id'] : 0,
            'title' => isset($_POST['title']) ? sanitizeInput($_POST['title']) : '',
            'description' => isset($_POST['description']) ? sanitizeInput($_POST['description']) : '',
            'start_datetime' => isset($_POST['start_datetime']) ? sanitizeInput($_POST['start_datetime']) : '',
            'end_datetime' => isset($_POST['end_datetime']) ? sanitizeInput($_POST['end_datetime']) : '',
            'affected_routes' => isset($_POST['affected_routes']) ? $_POST['affected_routes'] : [],
            'affected_stations' => isset($_POST['affected_stations']) ? $_POST['affected_stations'] : [],
            'status' => isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'scheduled'
        ];
        
        // Validate form data
        if (empty($formMaintenance['title'])) {
            $errors[] = 'Title is required';
        }
        
        if (empty($formMaintenance['start_datetime'])) {
            $errors[] = 'Start date and time are required';
        }
        
        if (empty($formMaintenance['end_datetime'])) {
            $errors[] = 'End date and time are required';
        } elseif (strtotime($formMaintenance['end_datetime']) <= strtotime($formMaintenance['start_datetime'])) {
            $errors[] = 'End date and time must be after start date and time';
        }
        
        if (empty($formMaintenance['affected_routes']) && empty($formMaintenance['affected_stations'])) {
            $errors[] = 'At least one route or station must be affected';
        }
        
        // If no errors, add or update maintenance schedule
        if (empty($errors)) {
            try {
                // Prepare affected routes and stations as comma-separated strings
                $affectedRoutes = implode(',', $formMaintenance['affected_routes']);
                $affectedStations = implode(',', $formMaintenance['affected_stations']);
                
                $maintenanceData = [
                    'title' => $formMaintenance['title'],
                    'description' => $formMaintenance['description'],
                    'start_datetime' => $formMaintenance['start_datetime'],
                    'end_datetime' => $formMaintenance['end_datetime'],
                    'affected_routes' => $affectedRoutes,
                    'affected_stations' => $affectedStations,
                    'status' => $formMaintenance['status'],
                    'created_by' => $userId
                ];
                
                if ($formAction === 'add_maintenance') {
                    $maintenanceData['created_at'] = date('Y-m-d H:i:s');
                    
                    $insertId = insert('maintenance_schedules', $maintenanceData);
                    
                    if ($insertId) {
                        // Log activity
                        logActivity('maintenance_added', 'Added new maintenance schedule: ' . $formMaintenance['title'], $userId);
                        
                        // Set success message and redirect
                        setFlashMessage('success', 'Maintenance schedule added successfully.');
                        redirect(APP_URL . '/admin/maintenance.php');
                    } else {
                        $errors[] = 'Failed to add maintenance schedule';
                    }
                } else {
                    $maintenanceData['updated_at'] = date('Y-m-d H:i:s');
                    
                    $updated = update('maintenance_schedules', $maintenanceData, "id = " . $formMaintenance['id']);
                    
                    if ($updated !== false) {
                        // Log activity
                        logActivity('maintenance_updated', 'Updated maintenance schedule: ' . $formMaintenance['title'], $userId);
                        
                        // Set success message and redirect
                        setFlashMessage('success', 'Maintenance schedule updated successfully.');
                        redirect(APP_URL . '/admin/maintenance.php');
                    } else {
                        $errors[] = 'Failed to update maintenance schedule';
                    }
                }
            } catch (Exception $e) {
                $errors[] = 'An error occurred: ' . $e->getMessage();
            }
        }
    } else if ($formAction === 'delete_maintenance') {
        $deleteMaintenanceId = isset($_POST['maintenance_id']) ? (int)$_POST['maintenance_id'] : 0;
        
        if ($deleteMaintenanceId <= 0) {
            $errors[] = 'Invalid maintenance schedule ID';
        } else {
            try {
                // Get maintenance title before deletion for activity log
                $maintenanceToDelete = fetchRow("SELECT title FROM maintenance_schedules WHERE id = ?", [$deleteMaintenanceId]);
                
                if (!$maintenanceToDelete) {
                    $errors[] = 'Maintenance schedule not found';
                } else {
                    // Delete the maintenance schedule
                    $deleted = delete('maintenance_schedules', "id = $deleteMaintenanceId");
                    
                    if ($deleted) {
                        // Log activity
                        logActivity('maintenance_deleted', 'Deleted maintenance schedule: ' . $maintenanceToDelete['title'], $userId);
                        
                        // Set success message and redirect
                        setFlashMessage('success', 'Maintenance schedule deleted successfully.');
                        redirect(APP_URL . '/admin/maintenance.php');
                    } else {
                        $errors[] = 'Failed to delete maintenance schedule';
                    }
                }
            } catch (Exception $e) {
                $errors[] = 'An error occurred: ' . $e->getMessage();
            }
        }
    } else if (isset($_POST['update_status'])) {
        $statusMaintenanceId = isset($_POST['status_maintenance_id']) ? (int)$_POST['status_maintenance_id'] : 0;
        $newStatus = isset($_POST['new_status']) ? sanitizeInput($_POST['new_status']) : '';
        
        if ($statusMaintenanceId <= 0) {
            $errors[] = 'Invalid maintenance schedule ID';
        } elseif (empty($newStatus)) {
            $errors[] = 'New status is required';
        } else {
            try {
                // Get maintenance title for activity log
                $maintenanceToUpdate = fetchRow("SELECT title FROM maintenance_schedules WHERE id = ?", [$statusMaintenanceId]);
                
                if (!$maintenanceToUpdate) {
                    $errors[] = 'Maintenance schedule not found';
                } else {
                    // Update the status
                    $updated = update('maintenance_schedules', 
                        ['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')], 
                        "id = $statusMaintenanceId"
                    );
                    
                    if ($updated !== false) {
                        // Log activity
                        logActivity('maintenance_status_updated', 
                            'Updated maintenance schedule status: ' . $maintenanceToUpdate['title'] . ' to ' . $newStatus, 
                            $userId
                        );
                        
                        // Set success message and redirect
                        setFlashMessage('success', 'Maintenance schedule status updated successfully.');
                        redirect(APP_URL . '/admin/maintenance.php');
                    } else {
                        $errors[] = 'Failed to update maintenance schedule status';
                    }
                }
            } catch (Exception $e) {
                $errors[] = 'An error occurred: ' . $e->getMessage();
            }
        }
    }
}

// Get maintenance schedule to edit if in edit mode
if ($action === 'edit' && $maintenanceId > 0) {
    $maintenanceToEdit = fetchRow("SELECT * FROM maintenance_schedules WHERE id = ?", [$maintenanceId]);
    
    if ($maintenanceToEdit) {
        $formMaintenance = [
            'id' => $maintenanceToEdit['id'],
            'title' => $maintenanceToEdit['title'],
            'description' => $maintenanceToEdit['description'],
            'start_datetime' => $maintenanceToEdit['start_datetime'],
            'end_datetime' => $maintenanceToEdit['end_datetime'],
            'affected_routes' => !empty($maintenanceToEdit['affected_routes']) ? explode(',', $maintenanceToEdit['affected_routes']) : [],
            'affected_stations' => !empty($maintenanceToEdit['affected_stations']) ? explode(',', $maintenanceToEdit['affected_stations']) : [],
            'status' => $maintenanceToEdit['status']
        ];
    } else {
        setFlashMessage('error', 'Maintenance schedule not found.');
        redirect(APP_URL . '/admin/maintenance.php');
    }
}

// Get flash messages
$successMessage = getFlashMessage('success');
$errorMessage = getFlashMessage('error');

// Clear form data if there's a success message
if ($successMessage) {
    $success = $successMessage;
    $formMaintenance = [
        'id' => '',
        'title' => '',
        'description' => '',
        'start_datetime' => '',
        'end_datetime' => '',
        'affected_routes' => [],
        'affected_stations' => [],
        'status' => 'scheduled'
    ];
}

// Add error message to errors array
if ($errorMessage) {
    $errors[] = $errorMessage;
}

// Fetch maintenance schedules for display
$filterStatus = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$filterDate = isset($_GET['date']) ? sanitizeInput($_GET['date']) : '';

// Build query with filters
$query = "SELECT ms.*, u.name as created_by_name 
          FROM maintenance_schedules ms
          LEFT JOIN users u ON ms.created_by = u.id
          WHERE 1=1";
$params = [];

if (!empty($filterStatus)) {
    $query .= " AND ms.status = ?";
    $params[] = $filterStatus;
}

if (!empty($searchTerm)) {
    $query .= " AND (ms.title LIKE ? OR ms.description LIKE ?)";
    $searchPattern = "%$searchTerm%";
    $params[] = $searchPattern;
    $params[] = $searchPattern;
}

if (!empty($filterDate)) {
    $query .= " AND (DATE(ms.start_datetime) = ? OR DATE(ms.end_datetime) = ?)";
    $params[] = $filterDate;
    $params[] = $filterDate;
}

$query .= " ORDER BY ms.start_datetime DESC";

// Fetch maintenance schedules based on filters
$maintenanceSchedules = fetchRows($query, $params);

// Fetch counts for dashboard
$totalMaintenance = fetchRow("SELECT COUNT(*) as count FROM maintenance_schedules")['count'];
$scheduledMaintenance = fetchRow("SELECT COUNT(*) as count FROM maintenance_schedules WHERE status = 'scheduled'")['count'];
$inProgressMaintenance = fetchRow("SELECT COUNT(*) as count FROM maintenance_schedules WHERE status = 'in_progress'")['count'];
$completedMaintenance = fetchRow("SELECT COUNT(*) as count FROM maintenance_schedules WHERE status = 'completed'")['count'];
$cancelledMaintenance = fetchRow("SELECT COUNT(*) as count FROM maintenance_schedules WHERE status = 'cancelled'")['count'];
$upcomingMaintenance = fetchRow("SELECT COUNT(*) as count FROM maintenance_schedules WHERE start_datetime > NOW() AND status != 'cancelled'")['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Management - Metro Rail Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/admin.css">
    <style>
        .maintenance-card {
            border-left: 4px solid #ccc;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .maintenance-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .status-scheduled {
            border-left-color: #0d6efd;
        }
        .status-in_progress {
            border-left-color: #ffc107;
        }
        .status-completed {
            border-left-color: #198754;
        }
        .status-cancelled {
            border-left-color: #dc3545;
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
                    <h1 class="h2"><i class="bi bi-tools me-2"></i>Maintenance Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMaintenanceModal">
                            <i class="bi bi-plus-lg me-1"></i> Add Maintenance Schedule
                        </button>
                    </div>
                </div>
                  <!-- Dashboard Cards -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card maintenance-card shadow-sm">
                            <div class="card-body bg-primary text-white rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-1">Total</h6>
                                        <h3 class="mb-0"><?= $totalMaintenance ?></h3>
                                    </div>
                                    <div>
                                        <i class="bi bi-list-check fs-2 opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card maintenance-card shadow-sm">
                            <div class="card-body bg-info text-white rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-1">Upcoming</h6>
                                        <h3 class="mb-0"><?= $upcomingMaintenance ?></h3>
                                    </div>
                                    <div>
                                        <i class="bi bi-calendar-event fs-2 opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card maintenance-card shadow-sm">
                            <div class="card-body bg-primary text-white rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-1">Scheduled</h6>
                                        <h3 class="mb-0"><?= $scheduledMaintenance ?></h3>
                                    </div>
                                    <div>
                                        <i class="bi bi-calendar2-check fs-2 opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card maintenance-card shadow-sm">
                            <div class="card-body bg-warning text-white rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-1">In Progress</h6>
                                        <h3 class="mb-0"><?= $inProgressMaintenance ?></h3>
                                    </div>
                                    <div>
                                        <i class="bi bi-gear-wide-connected fs-2 opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card maintenance-card shadow-sm">
                            <div class="card-body bg-success text-white rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-1">Completed</h6>
                                        <h3 class="mb-0"><?= $completedMaintenance ?></h3>
                                    </div>
                                    <div>
                                        <i class="bi bi-check-circle fs-2 opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>                    </div>
                    <div class="col-md-2">
                        <div class="card maintenance-card shadow-sm">
                            <div class="card-body bg-danger text-white rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-1">Cancelled</h6>
                                        <h3 class="mb-0"><?= $cancelledMaintenance ?></h3>
                                    </div>
                                    <div>
                                        <i class="bi bi-x-circle fs-2 opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Form -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="bi bi-funnel me-2"></i>Filter Maintenance Schedules</h5>
                    </div>                    <div class="card-body">
                        <form action="" method="get" class="row g-3">
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="scheduled" <?= $filterStatus === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                    <option value="in_progress" <?= $filterStatus === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="date" class="form-label">Date</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                                    <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($filterDate) ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="search" name="search" placeholder="Title or Description" value="<?= htmlspecialchars($searchTerm) ?>">
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-funnel-fill me-1"></i> Apply Filters
                                </button>
                                <a href="<?= APP_URL ?>/admin/maintenance.php" class="btn btn-secondary">
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
                  <!-- Maintenance Schedules -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="bi bi-tools me-2"></i>Maintenance Schedules</h5>
                        <span class="badge bg-primary"><?= count($maintenanceSchedules) ?> Records</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($maintenanceSchedules)): ?>
                            <div class="alert alert-info mb-0">
                                <div class="d-flex flex-column align-items-center py-3">
                                    <i class="bi bi-exclamation-circle text-info mb-2" style="font-size: 2rem;"></i>
                                    <p class="mb-0">No maintenance schedules found. Click the "Add Maintenance Schedule" button to create one.</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($maintenanceSchedules as $maintenance): ?>
                                    <?php
                                    // Get affected routes and stations
                                    $affectedRouteIds = !empty($maintenance['affected_routes']) ? explode(',', $maintenance['affected_routes']) : [];
                                    $affectedStationIds = !empty($maintenance['affected_stations']) ? explode(',', $maintenance['affected_stations']) : [];
                                    
                                    // Status badge class
                                    $statusClasses = [
                                        'scheduled' => 'bg-primary',
                                        'in_progress' => 'bg-warning',
                                        'completed' => 'bg-success',
                                        'cancelled' => 'bg-danger'
                                    ];
                                    $statusBadgeClass = isset($statusClasses[$maintenance['status']]) ? $statusClasses[$maintenance['status']] : 'bg-secondary';
                                    
                                    // Date formatting
                                    $startDate = new DateTime($maintenance['start_datetime']);
                                    $endDate = new DateTime($maintenance['end_datetime']);
                                    $isUpcoming = $startDate > new DateTime();
                                    $isActive = $startDate <= new DateTime() && $endDate >= new DateTime();
                                    $isPast = $endDate < new DateTime();
                                    ?>                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100 maintenance-card shadow-sm status-<?= $maintenance['status'] ?>">
                                            <div class="card-header d-flex justify-content-between align-items-center bg-light">
                                                <h5 class="card-title mb-0"><?= htmlspecialchars($maintenance['title']) ?></h5>
                                                <span class="badge <?= $statusBadgeClass ?>"><?= ucfirst(str_replace('_', ' ', $maintenance['status'])) ?></span>
                                            </div>
                                            <div class="card-body">
                                                <p class="card-text">
                                                    <?= nl2br(htmlspecialchars($maintenance['description'])) ?>
                                                </p>
                                                
                                                <div class="mt-3">
                                                    <strong><i class="bi bi-calendar-range me-2"></i>Schedule:</strong><br>
                                                    <div class="d-flex align-items-center mt-1">
                                                        <span class="badge bg-light text-dark p-2 me-2">
                                                            <i class="bi bi-calendar-event me-1"></i> 
                                                            <?= $startDate->format('d M Y h:i A') ?>
                                                        </span>
                                                        <i class="bi bi-arrow-right mx-2"></i>
                                                        <span class="badge bg-light text-dark p-2">
                                                            <i class="bi bi-calendar-check me-1"></i> 
                                                            <?= $endDate->format('d M Y h:i A') ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <?php if ($isUpcoming): ?>
                                                        <span class="badge bg-info mt-2"><i class="bi bi-hourglass me-1"></i>Upcoming</span>
                                                    <?php elseif ($isActive): ?>
                                                        <span class="badge bg-warning mt-2"><i class="bi bi-lightning-charge me-1"></i>Active Now</span>
                                                    <?php elseif ($isPast): ?>
                                                        <span class="badge bg-secondary mt-2"><i class="bi bi-clock-history me-1"></i>Past</span>
                                                    <?php endif; ?>
                                                </div>
                                                  <?php if (!empty($affectedRouteIds)): ?>
                                                    <div class="mt-3">
                                                        <strong><i class="bi bi-map me-2"></i>Affected Routes:</strong>
                                                        <div class="mt-1">
                                                            <?php 
                                                            $routeNames = [];
                                                            foreach ($routes as $route) {
                                                                if (in_array($route['id'], $affectedRouteIds)) {
                                                                    echo '<span class="badge bg-primary me-1 mb-1">' . $route['name'] . ' (' . $route['code'] . ')</span>';
                                                                }
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($affectedStationIds)): ?>
                                                    <div class="mt-3">
                                                        <strong><i class="bi bi-geo-alt me-2"></i>Affected Stations:</strong>
                                                        <div class="mt-1">
                                                            <?php 
                                                            $stationNames = [];
                                                            foreach ($stations as $station) {
                                                                if (in_array($station['id'], $affectedStationIds)) {
                                                                    echo '<span class="badge bg-secondary me-1 mb-1">' . $station['name'] . ' (' . $station['code'] . ')</span>';
                                                                }
                                                            }
                                                            ?>
                                                            echo implode(', ', $stationNames);
                                                            ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                  <div class="mt-3 small text-muted">
                                                    <i class="bi bi-person me-1"></i> Created by: <?= htmlspecialchars($maintenance['created_by_name']) ?> on 
                                                    <i class="bi bi-calendar me-1"></i> <?= date('d M Y', strtotime($maintenance['created_at'])) ?>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-light d-flex justify-content-between">
                                                <!-- Action buttons -->
                                                <div>
                                                    <a href="<?= APP_URL ?>/admin/maintenance.php?action=edit&id=<?= $maintenance['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteMaintenanceModal<?= $maintenance['id'] ?>">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                    
                                                    <!-- Delete Modal -->
                                                    <div class="modal fade" id="deleteMaintenanceModal<?= $maintenance['id'] ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Confirm Delete</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Are you sure you want to delete maintenance schedule: <strong><?= htmlspecialchars($maintenance['title']) ?></strong>?</p>
                                                                    <p class="text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>This action cannot be undone.</p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <form method="post">
                                                                        <input type="hidden" name="delete_id" value="<?= $maintenance['id'] ?>">
                                                                        <button type="submit" name="delete_maintenance" class="btn btn-danger">Delete Schedule</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Status change dropdown -->
                                                <?php if ($maintenance['status'] !== 'completed' && $maintenance['status'] !== 'cancelled'): ?>
                                                <div>
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        <i class="bi bi-arrow-repeat me-1"></i> Change Status
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <?php if ($maintenance['status'] !== 'scheduled'): ?>
                                                            <li>
                                                                <button class="dropdown-item change-status" 
                                                                        data-maintenance-id="<?= $maintenance['id'] ?>" 
                                                                        data-status="scheduled">
                                                                    <i class="bi bi-calendar-check me-2"></i> Scheduled
                                                                </button>
                                                            </li>
                                                        <?php endif; ?>
                                                          <?php if ($maintenance['status'] !== 'in_progress'): ?>
                                                            <li>
                                                                <button class="dropdown-item change-status" 
                                                                        data-maintenance-id="<?= $maintenance['id'] ?>" 
                                                                        data-status="in_progress">
                                                                    <i class="bi bi-gear-wide-connected me-2"></i> In Progress
                                                                </button>
                                                            </li>
                                                        <?php endif; ?>
                                                        
                                                        <li>
                                                            <button class="dropdown-item change-status" 
                                                                    data-maintenance-id="<?= $maintenance['id'] ?>" 
                                                                    data-status="completed">
                                                                    <i class="bi bi-check-circle me-2"></i> Completed
                                                            </button>
                                                        </li>
                                                        
                                                        <li>
                                                            <button class="dropdown-item change-status" 
                                                                    data-maintenance-id="<?= $maintenance['id'] ?>" 
                                                                    data-status="cancelled">
                                                                    <i class="bi bi-x-circle me-2"></i> Cancelled
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <?php endif; ?>
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
    
    <!-- Add Maintenance Modal -->
    <div class="modal fade" id="addMaintenanceModal" tabindex="-1" aria-labelledby="addMaintenanceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addMaintenanceModalLabel">Add Maintenance Schedule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($formMaintenance['title']) ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($formMaintenance['description']) ?></textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="start_datetime" class="form-label">Start Date and Time</label>
                                <input type="datetime-local" class="form-control" id="start_datetime" name="start_datetime" value="<?= htmlspecialchars($formMaintenance['start_datetime']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="end_datetime" class="form-label">End Date and Time</label>
                                <input type="datetime-local" class="form-control" id="end_datetime" name="end_datetime" value="<?= htmlspecialchars($formMaintenance['end_datetime']) ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="affected_routes" class="form-label">Affected Routes</label>
                                <select class="form-select" id="affected_routes" name="affected_routes[]" multiple size="5">
                                    <?php foreach ($routes as $route): ?>
                                        <option value="<?= $route['id'] ?>" <?= in_array($route['id'], $formMaintenance['affected_routes']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($route['name']) ?> (<?= htmlspecialchars($route['code']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Hold Ctrl/Cmd to select multiple routes</div>
                            </div>
                            <div class="col-md-6">
                                <label for="affected_stations" class="form-label">Affected Stations</label>
                                <select class="form-select" id="affected_stations" name="affected_stations[]" multiple size="5">
                                    <?php foreach ($stations as $station): ?>
                                        <option value="<?= $station['id'] ?>" <?= in_array($station['id'], $formMaintenance['affected_stations']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($station['name']) ?> (<?= htmlspecialchars($station['code']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Hold Ctrl/Cmd to select multiple stations</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="scheduled" <?= $formMaintenance['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                    <option value="in_progress" <?= $formMaintenance['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="completed" <?= $formMaintenance['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="cancelled" <?= $formMaintenance['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_maintenance" class="btn btn-primary">Add Maintenance Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Maintenance Modal -->
    <?php if ($action === 'edit' && isset($maintenanceToEdit)): ?>
    <div class="modal fade" id="editMaintenanceModal" tabindex="-1" aria-labelledby="editMaintenanceModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="" method="post">
                    <input type="hidden" name="id" value="<?= $formMaintenance['id'] ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editMaintenanceModalLabel">Edit Maintenance Schedule</h5>
                        <a href="<?= APP_URL ?>/admin/maintenance.php" class="btn-close" aria-label="Close"></a>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="edit_title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="edit_title" name="title" value="<?= htmlspecialchars($formMaintenance['title']) ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="edit_description" class="form-label">Description</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="3"><?= htmlspecialchars($formMaintenance['description']) ?></textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_start_datetime" class="form-label">Start Date and Time</label>
                                <input type="datetime-local" class="form-control" id="edit_start_datetime" name="start_datetime" value="<?= htmlspecialchars($formMaintenance['start_datetime']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_end_datetime" class="form-label">End Date and Time</label>
                                <input type="datetime-local" class="form-control" id="edit_end_datetime" name="end_datetime" value="<?= htmlspecialchars($formMaintenance['end_datetime']) ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_affected_routes" class="form-label">Affected Routes</label>
                                <select class="form-select" id="edit_affected_routes" name="affected_routes[]" multiple size="5">
                                    <?php foreach ($routes as $route): ?>
                                        <option value="<?= $route['id'] ?>" <?= in_array($route['id'], $formMaintenance['affected_routes']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($route['name']) ?> (<?= htmlspecialchars($route['code']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Hold Ctrl/Cmd to select multiple routes</div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_affected_stations" class="form-label">Affected Stations</label>
                                <select class="form-select" id="edit_affected_stations" name="affected_stations[]" multiple size="5">
                                    <?php foreach ($stations as $station): ?>
                                        <option value="<?= $station['id'] ?>" <?= in_array($station['id'], $formMaintenance['affected_stations']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($station['name']) ?> (<?= htmlspecialchars($station['code']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Hold Ctrl/Cmd to select multiple stations</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_status" name="status" required>
                                    <option value="scheduled" <?= $formMaintenance['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                    <option value="in_progress" <?= $formMaintenance['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="completed" <?= $formMaintenance['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="cancelled" <?= $formMaintenance['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="<?= APP_URL ?>/admin/maintenance.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" name="update_maintenance" class="btn btn-primary">Update Maintenance Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Delete Maintenance Modal -->
    <div class="modal fade" id="deleteMaintenanceModal" tabindex="-1" aria-labelledby="deleteMaintenanceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="post">
                    <input type="hidden" name="maintenance_id" id="delete_maintenance_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteMaintenanceModalLabel">Confirm Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete the maintenance schedule <strong id="delete_maintenance_title"></strong>?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_maintenance" class="btn btn-danger">Delete Maintenance Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Change Status Modal -->
    <div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="post">
                    <input type="hidden" name="status_maintenance_id" id="status_maintenance_id">
                    <input type="hidden" name="new_status" id="new_status">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changeStatusModalLabel">Change Maintenance Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to change the status to <strong id="status_text"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Change Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
      <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show edit modal if in edit mode
        <?php if ($action === 'edit'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            var editModal = new bootstrap.Modal(document.getElementById('editMaintenanceModal'));
            editModal.show();
        });
        <?php endif; ?>
        
        // Delete maintenance modal
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-maintenance');
            const deleteMaintenanceIdInput = document.getElementById('delete_maintenance_id');
            const deleteMaintenanceTitleSpan = document.getElementById('delete_maintenance_title');
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const maintenanceId = this.getAttribute('data-maintenance-id');
                    const maintenanceTitle = this.getAttribute('data-maintenance-title');
                    
                    deleteMaintenanceIdInput.value = maintenanceId;
                    deleteMaintenanceTitleSpan.textContent = maintenanceTitle;
                    
                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteMaintenanceModal'));
                    deleteModal.show();
                });
            });
            
            // Change status buttons
            const statusButtons = document.querySelectorAll('.change-status');
            const statusMaintenanceIdInput = document.getElementById('status_maintenance_id');
            const newStatusInput = document.getElementById('new_status');
            const statusTextSpan = document.getElementById('status_text');
            
            statusButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const maintenanceId = this.getAttribute('data-maintenance-id');
                    const status = this.getAttribute('data-status');
                    
                    statusMaintenanceIdInput.value = maintenanceId;
                    newStatusInput.value = status;
                    statusTextSpan.textContent = status.replace('_', ' ');
                    
                    const statusModal = new bootstrap.Modal(document.getElementById('changeStatusModal'));
                    statusModal.show();
                });
            });
        });
    </script>
</body>
</html>
