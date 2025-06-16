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
$routeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stations = fetchRows("SELECT id, name, code FROM stations WHERE status = 'active' ORDER BY name ASC");

// Form values
$formRoute = [
    'id' => '',
    'name' => '',
    'code' => '',
    'description' => '',
    'status' => 'active'
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_route']) || isset($_POST['update_route'])) {
        // Get form data
        $formRoute = [
            'id' => isset($_POST['id']) ? (int)$_POST['id'] : 0,
            'name' => isset($_POST['name']) ? sanitizeInput($_POST['name']) : '',
            'code' => isset($_POST['code']) ? sanitizeInput($_POST['code']) : '',
            'description' => isset($_POST['description']) ? sanitizeInput($_POST['description']) : '',
            'status' => isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'active'
        ];
        
        // Get station data for route
        $stationIds = isset($_POST['station_ids']) ? $_POST['station_ids'] : [];
        $stopOrders = isset($_POST['stop_orders']) ? $_POST['stop_orders'] : [];
        $distances = isset($_POST['distances']) ? $_POST['distances'] : [];
        $estimatedTimes = isset($_POST['estimated_times']) ? $_POST['estimated_times'] : [];
        
        // Validate form data
        if (empty($formRoute['name'])) {
            $errors[] = 'Route name is required';
        }
        
        if (empty($formRoute['code'])) {
            $errors[] = 'Route code is required';
        } elseif (!preg_match('/^[A-Z0-9]{2,10}$/', $formRoute['code'])) {
            $errors[] = 'Route code must be 2-10 alphanumeric characters (uppercase)';
        } else {
            // Check if code is already in use by another route
            $codeExists = fetchRow("
                SELECT id FROM routes WHERE code = ? AND id != ?
            ", [$formRoute['code'], $formRoute['id']]);
            
            if ($codeExists) {
                $errors[] = 'Route code is already in use by another route';
            }
        }
        
        // Validate route stations
        if (empty($stationIds)) {
            $errors[] = 'At least one station must be added to the route';
        } else {
            // Check for duplicate stop orders
            $uniqueStopOrders = array_unique($stopOrders);
            if (count($uniqueStopOrders) !== count($stopOrders)) {
                $errors[] = 'Each station must have a unique stop order';
            }
            
            // Validate distances and times
            foreach ($distances as $distance) {
                if (!is_numeric($distance) || $distance < 0) {
                    $errors[] = 'All distances must be valid non-negative numbers';
                    break;
                }
            }
            
            foreach ($estimatedTimes as $time) {
                if (!is_numeric($time) || $time < 0) {
                    $errors[] = 'All estimated times must be valid non-negative numbers';
                    break;
                }
            }
        }
        
        // If no errors, add or update route
        if (empty($errors)) {
            global $conn;
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                $routeData = [
                    'name' => $formRoute['name'],
                    'code' => $formRoute['code'],
                    'description' => $formRoute['description'],
                    'status' => $formRoute['status']
                ];
                
                if (isset($_POST['add_route'])) {
                    // Add new route
                    $routeData['created_at'] = date('Y-m-d H:i:s');
                    
                    $insertId = insert('routes', $routeData);
                    
                    if ($insertId) {
                        $routeId = $insertId;
                        
                        // Add route stations
                        for ($i = 0; $i < count($stationIds); $i++) {
                            $stationId = (int)$stationIds[$i];
                            $stopOrder = (int)$stopOrders[$i];
                            $distance = (float)$distances[$i];
                            $estimatedTime = (int)$estimatedTimes[$i];
                            
                            $stmt = $conn->prepare("
                                INSERT INTO route_stations (route_id, station_id, stop_order, distance_from_start, estimated_time, created_at)
                                VALUES (?, ?, ?, ?, ?, NOW())
                            ");
                            
                            $stmt->bind_param('iiidi', $routeId, $stationId, $stopOrder, $distance, $estimatedTime);
                            $stmt->execute();
                        }
                        
                        // Log activity
                        logActivity('route_added', 'Added new route: ' . $formRoute['name'], $userId);
                        
                        $conn->commit();
                        
                        // Set success message and redirect
                        setFlashMessage('success', 'Route added successfully.');
                        redirect(APP_URL . '/admin/routes.php');
                    } else {
                        throw new Exception('Failed to add route');
                    }
                } else {
                    // Update existing route
                    $routeData['updated_at'] = date('Y-m-d H:i:s');
                    
                    $updated = update('routes', $routeData, "id = " . $formRoute['id']);
                    
                    if ($updated !== false) {
                        // Delete existing route stations
                        $stmt = $conn->prepare("DELETE FROM route_stations WHERE route_id = ?");
                        $stmt->bind_param('i', $formRoute['id']);
                        $stmt->execute();
                        
                        // Add updated route stations
                        for ($i = 0; $i < count($stationIds); $i++) {
                            $stationId = (int)$stationIds[$i];
                            $stopOrder = (int)$stopOrders[$i];
                            $distance = (float)$distances[$i];
                            $estimatedTime = (int)$estimatedTimes[$i];
                            
                            $stmt = $conn->prepare("
                                INSERT INTO route_stations (route_id, station_id, stop_order, distance_from_start, estimated_time, created_at)
                                VALUES (?, ?, ?, ?, ?, NOW())
                            ");
                            
                            $stmt->bind_param('iiidi', $formRoute['id'], $stationId, $stopOrder, $distance, $estimatedTime);
                            $stmt->execute();
                        }
                        
                        // Log activity
                        logActivity('route_updated', 'Updated route: ' . $formRoute['name'], $userId);
                        
                        $conn->commit();
                        
                        // Set success message and redirect
                        setFlashMessage('success', 'Route updated successfully.');
                        redirect(APP_URL . '/admin/routes.php');
                    } else {
                        throw new Exception('Failed to update route');
                    }
                }
            } catch (Exception $e) {
                // Roll back transaction if anything goes wrong
                $conn->rollback();
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['delete_route'])) {
        $deleteId = (int)$_POST['delete_id'];
        
        // Check if route is used in any schedules
        $routeInUse = fetchRow("
            SELECT COUNT(*) as count FROM schedules WHERE route_id = ?
        ", [$deleteId]);
        
        if ($routeInUse['count'] > 0) {
            setFlashMessage('error', 'Cannot delete route that is used in schedules. Remove all schedules for this route first.');
            redirect(APP_URL . '/admin/routes.php');
        } else {
            global $conn;
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Get route name for logging
                $route = fetchRow("SELECT name FROM routes WHERE id = ?", [$deleteId]);
                
                // Delete route stations
                $stmt = $conn->prepare("DELETE FROM route_stations WHERE route_id = ?");
                $stmt->bind_param('i', $deleteId);
                $stmt->execute();
                
                // Delete fares for this route
                $stmt = $conn->prepare("DELETE FROM fares WHERE route_id = ?");
                $stmt->bind_param('i', $deleteId);
                $stmt->execute();
                
                // Delete route
                $stmt = $conn->prepare("DELETE FROM routes WHERE id = ?");
                $stmt->bind_param('i', $deleteId);
                $stmt->execute();
                
                // Log activity
                if ($route) {
                    logActivity('route_deleted', 'Deleted route: ' . $route['name'], $userId);
                }
                
                $conn->commit();
                
                // Set success message and redirect
                setFlashMessage('success', 'Route deleted successfully.');
            } catch (Exception $e) {
                // Roll back transaction if anything goes wrong
                $conn->rollback();
                setFlashMessage('error', 'Database error: ' . $e->getMessage());
            }
            
            redirect(APP_URL . '/admin/routes.php');
        }
    }
}

// Get route for editing
if ($action === 'edit' && $routeId > 0) {
    $formRoute = fetchRow("SELECT * FROM routes WHERE id = ?", [$routeId]);
    
    if (!$formRoute) {
        setFlashMessage('error', 'Route not found.');
        redirect(APP_URL . '/admin/routes.php');
    }
}

// Get routes for display
$routes = fetchRows("SELECT r.*, COUNT(rs.id) as station_count 
                    FROM routes r 
                    LEFT JOIN route_stations rs ON r.id = rs.route_id 
                    GROUP BY r.id 
                    ORDER BY r.name ASC");

// Get flash messages
$flashSuccess = getFlashMessage('success');
$flashError = getFlashMessage('error');

// Page title
$pageTitle = 'Manage Routes';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metro Rail - Manage Routes</title>
    
    <!-- Modern Styling -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/admin.css">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        .route-station-row {
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .route-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .route-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .modal-header.bg-danger, .modal-header.bg-primary, .modal-header.bg-success {
            color: white;
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
                    <h1 class="h2"><i class="bi bi-map me-2"></i><?php echo $pageTitle; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRouteModal">
                            <i class="bi bi-plus-lg"></i> Add Route
                        </button>
                    </div>
                </div>
                
                <!-- Alert Messages -->
                <?php if (!empty($flashSuccess)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Success!</strong> <?php echo $flashSuccess; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($flashError)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error!</strong> <?php echo $flashError; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error!</strong>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                  <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Success!</strong> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card shadow-sm route-card bg-gradient">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-0">Total Routes</h6>
                                        <h2 class="mt-2 mb-0"><?php echo count($routes); ?></h2>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-map-fill text-primary fs-1"></i>
                                    </div>
                                </div>
                                <p class="card-text mt-3 mb-0">
                                    <span class="text-success">
                                        <i class="bi bi-circle-fill me-1 small"></i>
                                        <?php 
                                            $activeRoutes = array_filter($routes, function($r) { return $r['status'] === 'active'; });
                                            echo count($activeRoutes);
                                        ?> Active
                                    </span>
                                    <span class="text-danger ms-3">
                                        <i class="bi bi-circle-fill me-1 small"></i>
                                        <?php 
                                            $inactiveRoutes = array_filter($routes, function($r) { return $r['status'] !== 'active'; });
                                            echo count($inactiveRoutes);
                                        ?> Inactive
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm route-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-0">Avg. Stations Per Route</h6>
                                        <h2 class="mt-2 mb-0">
                                            <?php 
                                                $totalStations = array_sum(array_column($routes, 'station_count'));
                                                echo count($routes) > 0 ? round($totalStations / count($routes), 1) : 0;
                                            ?>
                                        </h2>
                                    </div>
                                    <div class="bg-success bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-geo-alt-fill text-success fs-1"></i>
                                    </div>
                                </div>
                                <p class="card-text mt-3 mb-0">
                                    <span class="text-primary">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <?php echo $totalStations; ?> Total Stations on Routes
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm route-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-0">Most Stations</h6>
                                        <?php 
                                            $maxStations = !empty($routes) ? max(array_column($routes, 'station_count')) : 0;
                                            $routeWithMostStations = array_filter($routes, function($r) use ($maxStations) { 
                                                return $r['station_count'] == $maxStations; 
                                            });
                                            $routeWithMostStations = !empty($routeWithMostStations) ? reset($routeWithMostStations) : null;
                                        ?>
                                        <h2 class="mt-2 mb-0"><?php echo $maxStations; ?></h2>
                                    </div>
                                    <div class="bg-info bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-signpost-split-fill text-info fs-1"></i>
                                    </div>
                                </div>
                                <p class="card-text mt-3 mb-0">
                                    <?php if($routeWithMostStations): ?>
                                    <span class="text-info">
                                        <i class="bi bi-award me-1"></i>
                                        Route: <?php echo htmlspecialchars($routeWithMostStations['name']); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        No routes configured
                                    </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Routes Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">All Routes</h5>
                        <a href="<?php echo APP_URL; ?>/admin/routes.php?action=add" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg"></i> New Route
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($routes)): ?>
                            <div class="alert alert-info mb-0">
                                No routes found. Click the "Add Route" button to create one.
                            </div>                        <?php else: ?>
                            <div class="table-responsive">
                                <table id="routesTable" class="table table-hover align-middle nowrap" style="width:100%">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Route Name</th>
                                            <th>Code</th>
                                            <th>Stations</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($routes as $route): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($route['name']); ?></strong>
                                                    <?php if (!empty($route['description'])): ?>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($route['description']); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="badge bg-dark"><?php echo htmlspecialchars($route['code']); ?></span></td>
                                                <td><?php echo (int)$route['station_count']; ?> stations</td>
                                                <td>
                                                    <span class="badge bg-<?php echo $route['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                        <?php echo ucfirst(htmlspecialchars($route['status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="<?php echo APP_URL; ?>/admin/routes.php?action=edit&id=<?php echo $route['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $route['id']; ?>">
                                                            <i class="bi bi-trash"></i> Delete
                                                        </button>
                                                    </div>
                                                    
                                                    <!-- Delete Modal -->
                                                    <div class="modal fade" id="deleteModal<?php echo $route['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $route['id']; ?>" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="deleteModalLabel<?php echo $route['id']; ?>">Delete Route</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Are you sure you want to delete route: <strong><?php echo htmlspecialchars($route['name']); ?></strong>?</p>
                                                                    <p class="text-danger">This action cannot be undone. All stations and fares associated with this route will also be deleted.</p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <form method="post" action="<?php echo APP_URL; ?>/admin/routes.php">
                                                                        <input type="hidden" name="delete_id" value="<?php echo $route['id']; ?>">
                                                                        <button type="submit" name="delete_route" class="btn btn-danger">Delete Route</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($action === 'edit' || $action === 'add'): ?>
                    <!-- Add/Edit Route Form -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?php echo $action === 'add' ? 'Add New Route' : 'Edit Route: ' . $formRoute['name']; ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="<?php echo APP_URL; ?>/admin/routes.php">
                                <?php if ($action === 'edit'): ?>
                                    <input type="hidden" name="id" value="<?php echo $formRoute['id']; ?>">
                                <?php endif; ?>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Route Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($formRoute['name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="code" class="form-label">Route Code <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="code" name="code" value="<?php echo htmlspecialchars($formRoute['code']); ?>" required>
                                        <small class="text-muted">2-10 characters (uppercase letters and numbers only)</small>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($formRoute['description']); ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="active" <?php echo $formRoute['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $formRoute['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <h5 class="mt-4 mb-3 border-bottom pb-2">Route Stations</h5>
                                <p class="text-muted">Add stations to this route with their stop order, distance from starting point, and estimated travel time.</p>
                                
                                <?php
                                // Get route stations if editing
                                $routeStations = [];
                                if ($action === 'edit' && $formRoute['id']) {
                                    $routeStations = fetchRows("
                                        SELECT rs.*, s.name as station_name, s.code as station_code 
                                        FROM route_stations rs
                                        JOIN stations s ON rs.station_id = s.id
                                        WHERE rs.route_id = ?
                                        ORDER BY rs.stop_order ASC
                                    ", [$formRoute['id']]);
                                }
                                ?>
                                
                                <div id="route-stations-container">
                                    <?php if (!empty($routeStations)): ?>
                                        <?php foreach ($routeStations as $index => $station): ?>
                                            <div class="row mb-3 route-station-row">
                                                <div class="col-md-4">
                                                    <select class="form-select station-select" name="station_ids[]" required>
                                                        <option value="">-- Select Station --</option>
                                                        <?php foreach ($stations as $s): ?>
                                                            <option value="<?php echo $s['id']; ?>" <?php echo $station['station_id'] == $s['id'] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($s['name'] . ' (' . $s['code'] . ')'); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <input type="number" class="form-control stop-order" name="stop_orders[]" placeholder="Stop Order" value="<?php echo $station['stop_order']; ?>" required min="1">
                                                </div>
                                                <div class="col-md-2">
                                                    <input type="number" class="form-control" name="distances[]" placeholder="Distance (km)" value="<?php echo $station['distance_from_start']; ?>" required min="0" step="0.01">
                                                </div>
                                                <div class="col-md-2">
                                                    <input type="number" class="form-control" name="estimated_times[]" placeholder="Time (min)" value="<?php echo $station['estimated_time']; ?>" required min="0">
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="button" class="btn btn-outline-danger remove-station"><i class="bi bi-trash"></i></button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="row mb-3 route-station-row">
                                            <div class="col-md-4">
                                                <select class="form-select station-select" name="station_ids[]" required>
                                                    <option value="">-- Select Station --</option>
                                                    <?php foreach ($stations as $station): ?>
                                                        <option value="<?php echo $station['id']; ?>">
                                                            <?php echo htmlspecialchars($station['name'] . ' (' . $station['code'] . ')'); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" class="form-control stop-order" name="stop_orders[]" placeholder="Stop Order" value="1" required min="1">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" class="form-control" name="distances[]" placeholder="Distance (km)" value="0" required min="0" step="0.01">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" class="form-control" name="estimated_times[]" placeholder="Time (min)" value="0" required min="0">
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-outline-danger remove-station"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <button type="button" id="add-station" class="btn btn-outline-primary">
                                        <i class="bi bi-plus-lg"></i> Add Station
                                    </button>
                                </div>
                                
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="<?php echo APP_URL; ?>/admin/routes.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Back to Routes
                                    </a>
                                    <button type="submit" name="<?php echo $action === 'add' ? 'add_route' : 'update_route'; ?>" class="btn btn-primary">
                                        <i class="bi bi-save"></i> <?php echo $action === 'add' ? 'Save Route' : 'Update Route'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Add Route Modal -->
    <div class="modal fade" id="addRouteModal" tabindex="-1" aria-labelledby="addRouteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRouteModalLabel">Add New Route</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Would you like to add a new route?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="<?php echo APP_URL; ?>/admin/routes.php?action=add" class="btn btn-primary">Add New Route</a>
                </div>
            </div>
        </div>
    </div>
      <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable with modern styling
            if (document.querySelector('table')) {
                $(document).ready(function() {
                    $('table').DataTable({
                        responsive: true,
                        pageLength: 10,
                        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                        dom: '<"top"lf>rt<"bottom"ip><"clear">'
                    });
                });
            }
            
            // Add station to route
            document.getElementById('add-station').addEventListener('click', function() {
                const container = document.getElementById('route-stations-container');
                const stations = <?php echo json_encode($stations); ?>;
                const rows = container.querySelectorAll('.route-station-row');
                
                const newRow = document.createElement('div');
                newRow.className = 'row mb-3 route-station-row';
                
                // Calculate next stop order
                let nextStopOrder = 1;
                if (rows.length > 0) {
                    const stopOrders = [...rows].map(row => parseInt(row.querySelector('.stop-order').value) || 0);
                    nextStopOrder = Math.max(...stopOrders) + 1;
                }
                
                // Create new station row
                newRow.innerHTML = `
                    <div class="col-md-4">
                        <select class="form-select station-select" name="station_ids[]" required>
                            <option value="">-- Select Station --</option>
                            ${stations.map(station => `
                                <option value="${station.id}">
                                    ${station.name} (${station.code})
                                </option>
                            `).join('')}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control stop-order" name="stop_orders[]" placeholder="Stop Order" value="${nextStopOrder}" required min="1">
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control" name="distances[]" placeholder="Distance (km)" value="0" required min="0" step="0.01">
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control" name="estimated_times[]" placeholder="Time (min)" value="0" required min="0">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger remove-station"><i class="bi bi-trash"></i></button>
                    </div>
                `;
                
                container.appendChild(newRow);
                
                // Add event listener to remove button
                newRow.querySelector('.remove-station').addEventListener('click', function() {
                    removeStationRow(this);
                });
            });
            
            // Remove station from route
            function removeStationRow(button) {
                const container = document.getElementById('route-stations-container');
                const row = button.closest('.route-station-row');
                
                // Only remove if there's more than one row
                if (container.querySelectorAll('.route-station-row').length > 1) {
                    container.removeChild(row);
                }
            }
            
            // Add event listeners to existing remove buttons
            document.querySelectorAll('.remove-station').forEach(button => {
                button.addEventListener('click', function() {
                    removeStationRow(this);
                });
            });
        });
    </script>
</body>
</html>
