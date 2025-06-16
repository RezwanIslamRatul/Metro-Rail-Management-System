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
$fareId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$selectedRouteId = isset($_GET['route_id']) ? (int)$_GET['route_id'] : 0;

// Get routes for dropdown
$routes = fetchRows("SELECT id, name, code FROM routes WHERE status = 'active' ORDER BY name ASC");

// Get stations for dropdowns
$stations = fetchRows("SELECT id, name, code FROM stations WHERE status = 'active' ORDER BY name ASC");

// Form values
$formFare = [
    'id' => '',
    'route_id' => $selectedRouteId,
    'from_station_id' => '',
    'to_station_id' => '',
    'amount' => ''
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_fare']) || isset($_POST['update_fare'])) {
        // Get form data
        $formFare = [
            'id' => isset($_POST['id']) ? (int)$_POST['id'] : 0,
            'route_id' => isset($_POST['route_id']) ? (int)$_POST['route_id'] : 0,
            'from_station_id' => isset($_POST['from_station_id']) ? (int)$_POST['from_station_id'] : 0,
            'to_station_id' => isset($_POST['to_station_id']) ? (int)$_POST['to_station_id'] : 0,
            'amount' => isset($_POST['amount']) ? (float)$_POST['amount'] : 0
        ];
        
        // Validate form data
        if (empty($formFare['route_id'])) {
            $errors[] = 'Route is required';
        }
        
        if (empty($formFare['from_station_id'])) {
            $errors[] = 'From station is required';
        }
        
        if (empty($formFare['to_station_id'])) {
            $errors[] = 'To station is required';
        }
        
        if ($formFare['from_station_id'] == $formFare['to_station_id']) {
            $errors[] = 'From and To stations cannot be the same';
        }
        
        if ($formFare['amount'] <= 0) {
            $errors[] = 'Fare amount must be greater than zero';
        }
        
        // Check if stations are in the selected route
        if (!empty($formFare['route_id']) && !empty($formFare['from_station_id']) && !empty($formFare['to_station_id'])) {
            $fromStationInRoute = fetchRow("
                SELECT COUNT(*) as count FROM route_stations 
                WHERE route_id = ? AND station_id = ?
            ", [$formFare['route_id'], $formFare['from_station_id']]);
            
            $toStationInRoute = fetchRow("
                SELECT COUNT(*) as count FROM route_stations 
                WHERE route_id = ? AND station_id = ?
            ", [$formFare['route_id'], $formFare['to_station_id']]);
            
            if ($fromStationInRoute['count'] == 0) {
                $errors[] = 'From station is not in the selected route';
            }
            
            if ($toStationInRoute['count'] == 0) {
                $errors[] = 'To station is not in the selected route';
            }
            
            // Check for existing fare with same route and stations (only when adding)
            if (isset($_POST['add_fare'])) {
                $existingFare = fetchRow("
                    SELECT id FROM fares 
                    WHERE route_id = ? AND from_station_id = ? AND to_station_id = ?
                ", [$formFare['route_id'], $formFare['from_station_id'], $formFare['to_station_id']]);
                
                if ($existingFare) {
                    $errors[] = 'A fare already exists for this route and stations. Please update the existing fare instead.';
                }
            }
        }
        
        // If no errors, add or update fare
        if (empty($errors)) {
            if (isset($_POST['add_fare'])) {
                // Add new fare
                $fareId = insert('fares', [
                    'route_id' => $formFare['route_id'],
                    'from_station_id' => $formFare['from_station_id'],
                    'to_station_id' => $formFare['to_station_id'],
                    'amount' => $formFare['amount']
                ]);
                
                if ($fareId) {
                    // Log activity
                    logActivity('fare_added', 'Added new fare', $userId);
                    
                    // Create return fare automatically
                    $returnFareId = insert('fares', [
                        'route_id' => $formFare['route_id'],
                        'from_station_id' => $formFare['to_station_id'],
                        'to_station_id' => $formFare['from_station_id'],
                        'amount' => $formFare['amount']
                    ]);
                    
                    // Set success message and redirect
                    setFlashMessage('success', 'Fare added successfully. A return fare was also created automatically.');
                    redirect(APP_URL . '/admin/fares.php?route_id=' . $formFare['route_id']);
                } else {
                    $errors[] = 'Failed to add fare. Please try again.';
                }
            } else {
                // Update existing fare
                $updated = update('fares', [
                    'route_id' => $formFare['route_id'],
                    'from_station_id' => $formFare['from_station_id'],
                    'to_station_id' => $formFare['to_station_id'],
                    'amount' => $formFare['amount']
                ], "id = " . $formFare['id']);
                
                if ($updated) {
                    // Log activity
                    logActivity('fare_updated', 'Updated fare', $userId);
                    
                    // Set success message and redirect
                    setFlashMessage('success', 'Fare updated successfully.');
                    redirect(APP_URL . '/admin/fares.php?route_id=' . $formFare['route_id']);
                } else {
                    $errors[] = 'Failed to update fare. Please try again.';
                }
            }
        }
    } elseif (isset($_POST['delete_fare'])) {
        $deleteId = (int)$_POST['delete_id'];
        $routeId = (int)$_POST['route_id'];
        
        // Delete fare
        $deleted = delete('fares', "id = " . $deleteId);
        
        if ($deleted) {
            // Log activity
            logActivity('fare_deleted', 'Deleted fare', $userId);
            
            // Set success message and redirect
            setFlashMessage('success', 'Fare deleted successfully.');
        } else {
            setFlashMessage('error', 'Failed to delete fare. Please try again.');
        }
        
        redirect(APP_URL . '/admin/fares.php?route_id=' . $routeId);
    } elseif (isset($_POST['bulk_update'])) {
        $routeId = (int)$_POST['route_id'];
        $increasePercent = isset($_POST['increase_percent']) ? (float)$_POST['increase_percent'] : 0;
        
        if ($routeId > 0 && $increasePercent != 0) {
            global $conn;
            
            try {
                // Start transaction
                $conn->begin_transaction();
                
                // Update all fares for the selected route
                $stmt = $conn->prepare("
                    UPDATE fares 
                    SET amount = amount * (1 + ?/100) 
                    WHERE route_id = ?
                ");
                
                $stmt->bind_param('di', $increasePercent, $routeId);
                $stmt->execute();
                
                // Log activity
                logActivity('fares_bulk_updated', 'Bulk updated fares for route ID: ' . $routeId, $userId);
                
                $conn->commit();
                
                // Set success message and redirect
                setFlashMessage('success', 'Fares bulk updated successfully. All fares for the selected route were adjusted by ' . $increasePercent . '%.');
            } catch (Exception $e) {
                // Roll back transaction if anything goes wrong
                $conn->rollback();
                setFlashMessage('error', 'Database error: ' . $e->getMessage());
            }
            
            redirect(APP_URL . '/admin/fares.php?route_id=' . $routeId);
        } else {
            setFlashMessage('error', 'Invalid input for bulk update.');
            redirect(APP_URL . '/admin/fares.php');
        }
    } elseif (isset($_POST['generate_fares'])) {
        $routeId = (int)$_POST['route_id'];
        $baseAmount = isset($_POST['base_amount']) ? (float)$_POST['base_amount'] : 0;
        $farePerKm = isset($_POST['fare_per_km']) ? (float)$_POST['fare_per_km'] : 0;
        
        if ($routeId > 0 && $baseAmount >= 0 && $farePerKm > 0) {
            global $conn;
            
            try {
                // Start transaction
                $conn->begin_transaction();
                
                // Get all stations in the route
                $routeStations = fetchRows("
                    SELECT station_id, stop_order, distance_from_start
                    FROM route_stations 
                    WHERE route_id = ? 
                    ORDER BY stop_order ASC
                ", [$routeId]);
                
                if (count($routeStations) > 1) {
                    // Delete existing fares for this route
                    $stmt = $conn->prepare("DELETE FROM fares WHERE route_id = ?");
                    $stmt->bind_param('i', $routeId);
                    $stmt->execute();
                    
                    $insertStmt = $conn->prepare("
                        INSERT INTO fares (route_id, from_station_id, to_station_id, amount)
                        VALUES (?, ?, ?, ?)
                    ");
                    
                    // Generate fares for each station pair
                    for ($i = 0; $i < count($routeStations); $i++) {
                        $fromStation = $routeStations[$i];
                        $fromStationId = $fromStation['station_id'];
                        $fromDistance = $fromStation['distance_from_start'];
                        
                        for ($j = 0; $j < count($routeStations); $j++) {
                            if ($i != $j) {
                                $toStation = $routeStations[$j];
                                $toStationId = $toStation['station_id'];
                                $toDistance = $toStation['distance_from_start'];
                                
                                // Calculate distance between stations
                                $distance = abs($toDistance - $fromDistance);
                                
                                // Calculate fare amount based on formula
                                $fareAmount = $baseAmount + ($farePerKm * $distance);
                                $fareAmount = max(round($fareAmount, 2), 0.5); // Minimum fare of 0.50
                                
                                // Insert fare
                                $insertStmt->bind_param('iiid', $routeId, $fromStationId, $toStationId, $fareAmount);
                                $insertStmt->execute();
                            }
                        }
                    }
                    
                    // Log activity
                    logActivity('fares_generated', 'Generated fares for route ID: ' . $routeId, $userId);
                    
                    $conn->commit();
                    
                    // Set success message and redirect
                    setFlashMessage('success', 'Fares generated successfully for all station pairs in the route.');
                } else {
                    throw new Exception('Route must have at least two stations to generate fares');
                }
            } catch (Exception $e) {
                // Roll back transaction if anything goes wrong
                $conn->rollback();
                setFlashMessage('error', 'Error generating fares: ' . $e->getMessage());
            }
            
            redirect(APP_URL . '/admin/fares.php?route_id=' . $routeId);
        } else {
            setFlashMessage('error', 'Invalid input for fare generation.');
            redirect(APP_URL . '/admin/fares.php');
        }
    }
}

// Get fare for editing
if ($action === 'edit' && $fareId > 0) {
    $formFare = fetchRow("SELECT * FROM fares WHERE id = ?", [$fareId]);
    
    if (!$formFare) {
        setFlashMessage('error', 'Fare not found.');
        redirect(APP_URL . '/admin/fares.php');
    }
}

// Get route stations if a route is selected
$routeStations = [];
if ($selectedRouteId > 0) {
    $routeStations = fetchRows("
        SELECT rs.*, s.name as station_name, s.code as station_code
        FROM route_stations rs
        JOIN stations s ON rs.station_id = s.id
        WHERE rs.route_id = ?
        ORDER BY rs.stop_order ASC
    ", [$selectedRouteId]);
}

// Get fares for display
$fares = [];
if ($selectedRouteId > 0) {
    $fares = fetchRows("
        SELECT f.*, 
               fs.name as from_station_name, fs.code as from_station_code,
               ts.name as to_station_name, ts.code as to_station_code
        FROM fares f
        JOIN stations fs ON f.from_station_id = fs.id
        JOIN stations ts ON f.to_station_id = ts.id
        WHERE f.route_id = ?
        ORDER BY fs.name ASC, ts.name ASC
    ", [$selectedRouteId]);
}

// Get flash messages
$flashSuccess = getFlashMessage('success');
$flashError = getFlashMessage('error');

// Page title
$pageTitle = 'Manage Fares';
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
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet">
      <!-- Custom CSS -->
    <link href="<?php echo APP_URL; ?>/css/style.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/css/admin_modern.css" rel="stylesheet">
    
    <style>body {
            font-family: 'Poppins', sans-serif;
        }        .card {
            border-radius: 12px;
            box-shadow: 0 6px 10px rgba(0,0,0,.08), 0 0 6px rgba(0,0,0,.05);
            transition: transform .2s ease, box-shadow .2s ease;
            border: none;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,.12), 0 4px 8px rgba(0,0,0,.06);
        }
        .stat-card .card-body {
            padding: 1.5rem;
        }
        .stat-card .card-title {
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
        }        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.6rem 1rem;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
            border-color: #3498db;
        }
        
        .input-group {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .input-group-text {
            border: none;
            font-weight: 500;
        }
        .stat-card .h3 {
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        .modal-header {
            border-radius: 8px 8px 0 0;
        }
        .btn {
            border-radius: 6px;
            font-weight: 500;
        }
        .badge {
            font-weight: 500;
            padding: 0.55em 0.8em;
            font-size: 0.75em;
        }
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        .text-primary-light {
            color: rgba(13, 110, 253, 0.7);
        }
        .text-success-light {
            color: rgba(25, 135, 84, 0.7);
        }
        .text-info-light {
            color: rgba(13, 202, 240, 0.7);
        }
        .text-warning-light {
            color: rgba(255, 193, 7, 0.7);
        }
        .border-left-primary {
            border-left: 4px solid #0d6efd;
        }
        .border-left-success {
            border-left: 4px solid #198754;
        }
        .border-left-info {
            border-left: 4px solid #0dcaf0;
        }
        .border-left-warning {
            border-left: 4px solid #ffc107;
        }
        /* Animation delay for cards */
        .stat-card {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
        }    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/admin_sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Header / Navbar -->
                <nav class="navbar navbar-expand-lg navbar-light bg-white mb-4 rounded shadow-sm">
                    <div class="container-fluid">
                        <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target=".admin-sidebar">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <span class="navbar-brand mb-0 h1">
                            <i class="fas fa-money-bill-wave me-2 text-primary"></i><?php echo $pageTitle; ?>
                        </span>
                        <div class="d-flex">
                            <div class="dropdown">
                                <button class="btn btn-link dropdown-toggle text-decoration-none" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i> <?php echo $admin['name']; ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userMenu">
                                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2 text-primary"></i>Profile</a></li>
                                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>"><i class="fas fa-home me-2 text-primary"></i>Main Site</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2 text-danger"></i>Logout</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>
                
                <!-- Main Content Area -->
                <div class="row">
                    <div class="col-12">
                        <?php if (!empty($flashSuccess)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $flashSuccess; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($flashError)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $flashError; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
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
                                <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <?php
                            // Get total fares count
                            $totalFares = fetchRow("SELECT COUNT(*) as count FROM fares");
                        ?>
                        <div class="card stat-card border-left-primary h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="card-title text-primary mb-1">Total Fares</div>
                                        <div class="h3 mb-0 font-weight-bold"><?php echo number_format($totalFares['count']); ?></div>
                                        <div class="text-muted small">Across all routes</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-money-bill-wave stat-icon text-primary-light"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <?php
                            // Get average fare amount
                            $avgFare = fetchRow("SELECT AVG(amount) as avg FROM fares");
                        ?>
                        <div class="card stat-card border-left-success h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="card-title text-success mb-1">Average Fare</div>
                                        <div class="h3 mb-0 font-weight-bold">$<?php echo number_format($avgFare['avg'], 2); ?></div>
                                        <div class="text-muted small">System-wide average</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign stat-icon text-success-light"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <?php
                            // Get route with most fares
                            $topRoute = fetchRow("
                                SELECT r.name, COUNT(*) as count 
                                FROM fares f 
                                JOIN routes r ON f.route_id = r.id 
                                GROUP BY f.route_id 
                                ORDER BY count DESC LIMIT 1
                            ");
                        ?>
                        <div class="card stat-card border-left-info h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="card-title text-info mb-1">Top Route</div>
                                        <div class="h3 mb-0 font-weight-bold"><?php echo $topRoute ? htmlspecialchars($topRoute['name']) : 'N/A'; ?></div>
                                        <div class="text-muted small"><?php echo $topRoute ? $topRoute['count'] . ' fares' : 'No routes with fares'; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-route stat-icon text-info-light"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <?php
                            // Get max fare price
                            $maxFare = fetchRow("SELECT MAX(amount) as max FROM fares");
                        ?>
                        <div class="card stat-card border-left-warning h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="card-title text-warning mb-1">Highest Fare</div>
                                        <div class="h3 mb-0 font-weight-bold">$<?php echo number_format($maxFare['max'], 2); ?></div>
                                        <div class="text-muted small">Maximum fare in system</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-arrow-up stat-icon text-warning-light"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                  <!-- Route Selection -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3 bg-light">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-route me-2"></i>Select Route</h6>
                    </div>
                    <div class="card-body">
                        <form method="get" action="<?php echo APP_URL; ?>/admin/fares.php" class="row g-3 align-items-end">
                            <div class="col-md-8">
                                <label for="route_id" class="form-label">Route</label>
                                <select class="form-select shadow-sm" id="route_id" name="route_id" required>
                                    <option value="">-- Select Route --</option>
                                    <?php foreach ($routes as $route): ?>
                                        <option value="<?php echo $route['id']; ?>" <?php echo $selectedRouteId == $route['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($route['name'] . ' (' . $route['code'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100 shadow-sm">
                                    <i class="fas fa-search me-2"></i>View Fares
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                  <?php if ($action === 'add' || $action === 'edit'): ?>
                    <!-- Add/Edit Fare Form -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3 bg-light">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-<?php echo $action === 'add' ? 'plus' : 'edit'; ?> me-2"></i>
                                <?php echo $action === 'add' ? 'Add New Fare' : 'Edit Fare'; ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="post" action="<?php echo APP_URL; ?>/admin/fares.php">
                                <?php if ($action === 'edit'): ?>
                                    <input type="hidden" name="id" value="<?php echo $formFare['id']; ?>">
                                <?php endif; ?>
                                  <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="route_id" class="form-label">Route *</label>
                                        <select class="form-select shadow-sm" id="route_id" name="route_id" required <?php echo $action === 'edit' ? 'disabled' : ''; ?>>
                                            <option value="">-- Select Route --</option>
                                            <?php foreach ($routes as $route): ?>
                                                <option value="<?php echo $route['id']; ?>" <?php echo $formFare['route_id'] == $route['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($route['name'] . ' (' . $route['code'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if ($action === 'edit'): ?>
                                            <input type="hidden" name="route_id" value="<?php echo $formFare['route_id']; ?>">
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="from_station_id" class="form-label">From Station *</label>
                                        <select class="form-select shadow-sm" id="from_station_id" name="from_station_id" required <?php echo $action === 'edit' ? 'disabled' : ''; ?>>
                                            <option value="">-- Select Station --</option>
                                            <?php foreach ($routeStations as $station): ?>
                                                <option value="<?php echo $station['station_id']; ?>" <?php echo $formFare['from_station_id'] == $station['station_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($station['station_name'] . ' (' . $station['station_code'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if ($action === 'edit'): ?>
                                            <input type="hidden" name="from_station_id" value="<?php echo $formFare['from_station_id']; ?>">
                                        <?php endif; ?>                                    </div>
                                    <div class="col-md-4">
                                        <label for="to_station_id" class="form-label">To Station *</label>
                                        <select class="form-select shadow-sm" id="to_station_id" name="to_station_id" required <?php echo $action === 'edit' ? 'disabled' : ''; ?>>
                                            <option value="">-- Select Station --</option>
                                            <?php foreach ($routeStations as $station): ?>
                                                <option value="<?php echo $station['station_id']; ?>" <?php echo $formFare['to_station_id'] == $station['station_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($station['station_name'] . ' (' . $station['station_code'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if ($action === 'edit'): ?>
                                            <input type="hidden" name="to_station_id" value="<?php echo $formFare['to_station_id']; ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">                                        <label for="amount" class="form-label">Fare Amount *</label>
                                        <div class="input-group shadow-sm">
                                            <span class="input-group-text bg-success text-white"><i class="fas fa-dollar-sign"></i></span>
                                            <input type="number" class="form-control" id="amount" name="amount" value="<?php echo $formFare['amount']; ?>" step="0.01" min="0.01" required>
                                        </div>
                                        <small class="text-muted">Fare amount in dollars</small>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="<?php echo APP_URL; ?>/admin/fares.php?route_id=<?php echo $formFare['route_id']; ?>" class="btn btn-secondary shadow-sm">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" name="<?php echo $action === 'add' ? 'add_fare' : 'update_fare'; ?>" class="btn btn-primary shadow-sm">
                                        <i class="fas fa-<?php echo $action === 'add' ? 'plus' : 'save'; ?> me-2"></i><?php echo $action === 'add' ? 'Add Fare' : 'Update Fare'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php elseif ($selectedRouteId > 0): ?>
                    <!-- Fares Management -->
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Fares List -->                            <div class="card shadow-sm mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center bg-light">
                                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-2"></i>Fares List</h6>
                                <a href="<?php echo APP_URL; ?>/admin/fares.php?action=add&route_id=<?php echo $selectedRouteId; ?>" class="btn btn-primary btn-sm shadow-sm">
                                    <i class="fas fa-plus me-1"></i> Add New Fare
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover align-middle" id="faresTable" width="100%" cellspacing="0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>From Station</th>
                                                <th>To Station</th>
                                                <th>Fare Amount</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($fares)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-4">No fares found for this route.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($fares as $fare): ?>
                                                    <tr>
                                                        <td><span class="badge bg-light text-dark border me-1"><?php echo htmlspecialchars($fare['from_station_code']); ?></span> <?php echo htmlspecialchars($fare['from_station_name']); ?></td>
                                                        <td><span class="badge bg-light text-dark border me-1"><?php echo htmlspecialchars($fare['to_station_code']); ?></span> <?php echo htmlspecialchars($fare['to_station_name']); ?></td>
                                                        <td><span class="badge bg-success"><?php echo '$' . number_format($fare['amount'], 2); ?></span></td>
                                                        <td class="text-center">
                                                            <a href="<?php echo APP_URL; ?>/admin/fares.php?action=edit&id=<?php echo $fare['id']; ?>&route_id=<?php echo $selectedRouteId; ?>" class="btn btn-primary btn-sm me-1">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </a>
                                                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $fare['id']; ?>">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </button>
                                                                  <!-- Delete Modal -->                                                            <!-- Delete Modal -->
                                                            <div class="modal fade" id="deleteModal<?php echo $fare['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $fare['id']; ?>" aria-hidden="true">
                                                                <div class="modal-dialog">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header bg-danger text-white">
                                                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $fare['id']; ?>">Delete Fare</h5>
                                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <p>Are you sure you want to delete this fare?</p>
                                                                            <div class="card border-0 bg-light p-3 mb-3">
                                                                                <div class="d-flex justify-content-between mb-2">
                                                                                    <span class="text-muted">From:</span>
                                                                                    <span class="fw-bold"><?php echo htmlspecialchars($fare['from_station_name']); ?> <span class="badge bg-info text-dark"><?php echo htmlspecialchars($fare['from_station_code']); ?></span></span>
                                                                                </div>
                                                                                <div class="d-flex justify-content-between mb-2">
                                                                                    <span class="text-muted">To:</span>
                                                                                    <span class="fw-bold"><?php echo htmlspecialchars($fare['to_station_name']); ?> <span class="badge bg-info text-dark"><?php echo htmlspecialchars($fare['to_station_code']); ?></span></span>
                                                                                </div>
                                                                                <div class="d-flex justify-content-between">
                                                                                    <span class="text-muted">Amount:</span>
                                                                                    <span class="fw-bold"><span class="badge bg-success">$<?php echo number_format($fare['amount'], 2); ?></span></span>
                                                                                </div>
                                                                            </div>
                                                                            <p class="text-danger fw-bold">This action cannot be undone.</p>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                            <form method="post" action="<?php echo APP_URL; ?>/admin/fares.php">
                                                                                <input type="hidden" name="delete_id" value="<?php echo $fare['id']; ?>">
                                                                                <input type="hidden" name="route_id" value="<?php echo $selectedRouteId; ?>">
                                                                                <button type="submit" name="delete_fare" class="btn btn-danger">Delete Fare</button>
                                                                            </form>
                                                                        </div>
                                                                    </div>
                                                                </div>
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
                          <div class="col-md-4">
                            <!-- Bulk Operations -->
                            <div class="card shadow-sm mb-4">
                                <div class="card-header py-3 bg-light">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-tools me-2"></i>Bulk Operations</h6>
                                </div>
                                <div class="card-body">                                    
                                    <!-- Bulk Update Fares -->
                                    <div class="mb-4">
                                        <h6 class="fw-bold mb-3 border-bottom pb-2"><i class="fas fa-sync-alt me-2 text-warning"></i>Bulk Update Fares</h6>
                                        <form method="post" action="<?php echo APP_URL; ?>/admin/fares.php">
                                            <input type="hidden" name="route_id" value="<?php echo $selectedRouteId; ?>">
                                            
                                            <div class="mb-3">
                                                <label for="increase_percent" class="form-label">Percentage Change</label>
                                                <div class="input-group shadow-sm">
                                                    <span class="input-group-text bg-warning text-dark"><i class="fas fa-percentage"></i></span>
                                                    <input type="number" class="form-control" id="increase_percent" name="increase_percent" value="0" step="0.1" required>
                                                    <span class="input-group-text">%</span>
                                                </div>
                                                <small class="text-muted">Use positive values for increase, negative for decrease</small>
                                            </div>
                                            
                                            <button type="submit" name="bulk_update" class="btn btn-warning w-100 py-2 shadow-sm">
                                                <i class="fas fa-sync-alt me-2"></i> Apply Percentage Change
                                            </button>
                                        </form>                                    </div>
                                    
                                    <hr>
                                    
                                    <!-- Generate Fares -->
                                    <div>
                                        <h6 class="fw-bold mb-3 border-bottom pb-2"><i class="fas fa-magic me-2 text-info"></i>Generate Distance-Based Fares</h6>
                                        <form method="post" action="<?php echo APP_URL; ?>/admin/fares.php">
                                            <input type="hidden" name="route_id" value="<?php echo $selectedRouteId; ?>">
                                            
                                            <div class="mb-3">
                                                <label for="base_amount" class="form-label">Base Fare Amount</label>
                                                <div class="input-group shadow-sm">
                                                    <span class="input-group-text bg-primary text-white"><i class="fas fa-dollar-sign"></i></span>
                                                    <input type="number" class="form-control" id="base_amount" name="base_amount" value="2.00" step="0.01" min="0" required>
                                                </div>
                                                <small class="text-muted">Minimum fare for any journey</small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="fare_per_km" class="form-label">Fare per Kilometer</label>
                                                <div class="input-group shadow-sm">
                                                    <span class="input-group-text bg-primary text-white"><i class="fas fa-dollar-sign"></i></span>
                                                    <input type="number" class="form-control" id="fare_per_km" name="fare_per_km" value="0.25" step="0.01" min="0.01" required>
                                                    <span class="input-group-text">/km</span>
                                                </div>
                                                <small class="text-muted">Amount to charge per km of travel</small>
                                            </div>
                                            
                                            <button type="submit" name="generate_fares" class="btn btn-info w-100 py-2 shadow-sm" onclick="return confirm('This will replace ALL existing fares for this route. Are you sure?')">
                                                <i class="fas fa-magic me-2"></i> Generate Distance-Based Fares
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
      <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
      <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTables
            if (document.getElementById('faresTable')) {
                let faresTable = new DataTable('#faresTable', {
                    responsive: true,
                    order: [[0, 'asc']],
                    language: {
                        search: '<i class="fas fa-search"></i>',
                        searchPlaceholder: 'Search fares...',
                        emptyTable: 'No fares found for this route',
                        zeroRecords: 'No matching fares found',
                        info: 'Showing _START_ to _END_ of _TOTAL_ fares',
                        infoEmpty: 'Showing 0 to 0 of 0 fares',
                        infoFiltered: '(filtered from _MAX_ total fares)',
                        paginate: {
                            first: '<i class="fas fa-angle-double-left"></i>',
                            previous: '<i class="fas fa-angle-left"></i>',
                            next: '<i class="fas fa-angle-right"></i>',
                            last: '<i class="fas fa-angle-double-right"></i>'
                        }
                    },
                    dom: '<"row mb-3"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
                    columnDefs: [
                        { targets: -1, orderable: false, width: '15%' } // Disable sorting on actions column
                    ],
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    pageLength: 10
                });
                
                // Custom styling for DataTables
                document.querySelector('.dataTables_filter input').classList.add('form-control');
                document.querySelector('.dataTables_length select').classList.add('form-select');
            }
            
            // Route selection changes - redirect to fare page with route_id
            const routeIdSelect = document.getElementById('route_id');
            if (routeIdSelect) {
                routeIdSelect.addEventListener('change', function() {
                    // If this is the selector in the fare add/edit form, update stations options
                    const fromStationSelect = document.getElementById('from_station_id');
                    const toStationSelect = document.getElementById('to_station_id');
                    
                    if (fromStationSelect && toStationSelect) {
                        // Clear station selects
                        fromStationSelect.innerHTML = '<option value="">-- Select Station --</option>';
                        toStationSelect.innerHTML = '<option value="">-- Select Station --</option>';
                        
                        // Redirect not needed as this is the add/edit form
                        return;
                    }
                });
            }
            
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
            
            // Add animation to fares stats cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100 + (index * 100));
                }, 0);
            });
        });
    </script>
</body>
</html>
