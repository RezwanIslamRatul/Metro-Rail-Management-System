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
$scheduleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get active routes for dropdown
$routes = fetchRows("SELECT id, name, code FROM routes WHERE status = 'active' ORDER BY name ASC");

// Get active trains for dropdown
$trains = fetchRows("SELECT id, name, train_number, capacity FROM trains WHERE status = 'active' ORDER BY name ASC");

// Form values
$formSchedule = [
    'id' => '',
    'route_id' => '',
    'train_id' => '',
    'departure_time' => '',
    'days' => [],
    'status' => 'active'
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_schedule']) || isset($_POST['update_schedule'])) {
        // Get form data
        $formSchedule = [
            'id' => isset($_POST['id']) ? (int)$_POST['id'] : 0,
            'route_id' => isset($_POST['route_id']) ? (int)$_POST['route_id'] : 0,
            'train_id' => isset($_POST['train_id']) ? (int)$_POST['train_id'] : 0,
            'departure_time' => isset($_POST['departure_time']) ? sanitizeInput($_POST['departure_time']) : '',
            'days' => isset($_POST['days']) ? $_POST['days'] : [],
            'status' => isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'active'
        ];
        
        // Validate form data
        if (empty($formSchedule['route_id'])) {
            $errors[] = 'Route is required';
        }
        
        if (empty($formSchedule['train_id'])) {
            $errors[] = 'Train is required';
        }
        
        if (empty($formSchedule['departure_time'])) {
            $errors[] = 'Departure time is required';
        } elseif (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $formSchedule['departure_time'])) {
            $errors[] = 'Departure time must be in the format HH:MM (24-hour)';
        }
        
        if (empty($formSchedule['days'])) {
            $errors[] = 'At least one day must be selected';
        }
        
        // If no errors, add or update schedule
        if (empty($errors)) {
            global $conn;
            
            // Convert days array to comma-separated string
            $daysString = implode(',', $formSchedule['days']);
            
            try {
                // Start transaction
                $conn->begin_transaction();
                
                if (isset($_POST['add_schedule'])) {
                    // Add new schedule
                    $stmt = $conn->prepare("
                        INSERT INTO schedules (route_id, train_id, departure_time, days, status, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    
                    $stmt->bind_param('iisss', 
                        $formSchedule['route_id'], 
                        $formSchedule['train_id'], 
                        $formSchedule['departure_time'], 
                        $daysString, 
                        $formSchedule['status']
                    );
                    
                    $stmt->execute();
                    $scheduleId = $conn->insert_id;
                    
                    if ($scheduleId) {
                        // Generate schedule stations based on route
                        $routeStations = fetchRows("
                            SELECT station_id, stop_order, estimated_time
                            FROM route_stations 
                            WHERE route_id = ? 
                            ORDER BY stop_order ASC
                        ", [$formSchedule['route_id']]);
                        
                        if (!empty($routeStations)) {
                            $departureTime = strtotime('2023-01-01 ' . $formSchedule['departure_time']);
                            
                            foreach ($routeStations as $routeStation) {
                                $stationId = $routeStation['station_id'];
                                $estimatedTime = $routeStation['estimated_time'];
                                
                                // First station has only departure time
                                if ($routeStation['stop_order'] == 1) {
                                    $arrivalTime = null;
                                    $departureTimeFormatted = date('H:i:s', $departureTime);
                                } else {
                                    // Calculate arrival time based on previous station's departure time + estimated time
                                    $arrivalTimeFormatted = date('H:i:s', $departureTime + ($estimatedTime * 60));
                                    
                                    // Set departure time (2 minutes after arrival for stations in between)
                                    $departureTime = $departureTime + ($estimatedTime * 60) + 120;
                                    $departureTimeFormatted = date('H:i:s', $departureTime);
                                    
                                    // Last station has only arrival time
                                    if ($routeStation['stop_order'] == count($routeStations)) {
                                        $departureTimeFormatted = null;
                                    }
                                }
                                
                                // Insert schedule station
                                $stmt = $conn->prepare("
                                    INSERT INTO schedule_stations (schedule_id, station_id, arrival_time, departure_time)
                                    VALUES (?, ?, ?, ?)
                                ");
                                
                                $stmt->bind_param('iiss', 
                                    $scheduleId, 
                                    $stationId, 
                                    $arrivalTimeFormatted, 
                                    $departureTimeFormatted
                                );
                                
                                $stmt->execute();
                            }
                        }
                        
                        // Log activity
                        logActivity('schedule_added', 'Added new schedule', $userId);
                        
                        $conn->commit();
                        
                        // Set success message and redirect
                        setFlashMessage('success', 'Schedule added successfully.');
                        redirect(APP_URL . '/admin/schedules.php');
                    } else {
                        throw new Exception('Failed to add schedule');
                    }
                } else {
                    // Update existing schedule
                    $stmt = $conn->prepare("
                        UPDATE schedules 
                        SET route_id = ?, train_id = ?, departure_time = ?, days = ?, status = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    
                    $stmt->bind_param('iisssi', 
                        $formSchedule['route_id'], 
                        $formSchedule['train_id'], 
                        $formSchedule['departure_time'], 
                        $daysString, 
                        $formSchedule['status'],
                        $formSchedule['id']
                    );
                    
                    $stmt->execute();
                    
                    if ($stmt->affected_rows !== -1) {
                        // Delete existing schedule stations
                        $stmt = $conn->prepare("DELETE FROM schedule_stations WHERE schedule_id = ?");
                        $stmt->bind_param('i', $formSchedule['id']);
                        $stmt->execute();
                        
                        // Generate new schedule stations
                        $routeStations = fetchRows("
                            SELECT station_id, stop_order, estimated_time
                            FROM route_stations 
                            WHERE route_id = ? 
                            ORDER BY stop_order ASC
                        ", [$formSchedule['route_id']]);
                        
                        if (!empty($routeStations)) {
                            $departureTime = strtotime('2023-01-01 ' . $formSchedule['departure_time']);
                            
                            foreach ($routeStations as $routeStation) {
                                $stationId = $routeStation['station_id'];
                                $estimatedTime = $routeStation['estimated_time'];
                                
                                // First station has only departure time
                                if ($routeStation['stop_order'] == 1) {
                                    $arrivalTime = null;
                                    $departureTimeFormatted = date('H:i:s', $departureTime);
                                } else {
                                    // Calculate arrival time based on previous station's departure time + estimated time
                                    $arrivalTimeFormatted = date('H:i:s', $departureTime + ($estimatedTime * 60));
                                    
                                    // Set departure time (2 minutes after arrival for stations in between)
                                    $departureTime = $departureTime + ($estimatedTime * 60) + 120;
                                    $departureTimeFormatted = date('H:i:s', $departureTime);
                                    
                                    // Last station has only arrival time
                                    if ($routeStation['stop_order'] == count($routeStations)) {
                                        $departureTimeFormatted = null;
                                    }
                                }
                                
                                // Insert schedule station
                                $stmt = $conn->prepare("
                                    INSERT INTO schedule_stations (schedule_id, station_id, arrival_time, departure_time)
                                    VALUES (?, ?, ?, ?)
                                ");
                                
                                $stmt->bind_param('iiss', 
                                    $formSchedule['id'], 
                                    $stationId, 
                                    $arrivalTimeFormatted, 
                                    $departureTimeFormatted
                                );
                                
                                $stmt->execute();
                            }
                        }
                        
                        // Log activity
                        logActivity('schedule_updated', 'Updated schedule', $userId);
                        
                        $conn->commit();
                        
                        // Set success message and redirect
                        setFlashMessage('success', 'Schedule updated successfully.');
                        redirect(APP_URL . '/admin/schedules.php');
                    } else {
                        throw new Exception('Failed to update schedule');
                    }
                }
            } catch (Exception $e) {
                // Roll back transaction if anything goes wrong
                $conn->rollback();
                $errors[] = 'Database error: ' . $e->getMessage();
                debug_log('Schedule error: ' . $e->getMessage(), null, 'schedules_debug.log');
            }
        }
    } elseif (isset($_POST['delete_schedule'])) {
        $deleteId = (int)$_POST['delete_id'];
        
        // Check if schedule is used in any bookings
        $scheduleInUse = fetchRow("
            SELECT COUNT(*) as count FROM bookings WHERE schedule_id = ?
        ", [$deleteId]);
        
        if ($scheduleInUse && $scheduleInUse['count'] > 0) {
            setFlashMessage('error', 'Cannot delete schedule that has bookings. Cancel all bookings for this schedule first.');
            redirect(APP_URL . '/admin/schedules.php');
        } else {
            global $conn;
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Delete schedule stations
                $stmt = $conn->prepare("DELETE FROM schedule_stations WHERE schedule_id = ?");
                $stmt->bind_param('i', $deleteId);
                $stmt->execute();
                
                // Delete schedule
                $stmt = $conn->prepare("DELETE FROM schedules WHERE id = ?");
                $stmt->bind_param('i', $deleteId);
                $stmt->execute();
                
                // Log activity
                logActivity('schedule_deleted', 'Deleted schedule', $userId);
                
                $conn->commit();
                
                // Set success message and redirect
                setFlashMessage('success', 'Schedule deleted successfully.');
            } catch (Exception $e) {
                // Roll back transaction if anything goes wrong
                $conn->rollback();
                setFlashMessage('error', 'Database error: ' . $e->getMessage());
                debug_log('Schedule delete error: ' . $e->getMessage(), null, 'schedules_debug.log');
            }
            
            redirect(APP_URL . '/admin/schedules.php');
        }
    }
}

// Get schedule for editing
if ($action === 'edit' && $scheduleId > 0) {
    $schedule = fetchRow("SELECT * FROM schedules WHERE id = ?", [$scheduleId]);
    
    if ($schedule) {
        $formSchedule = [
            'id' => $schedule['id'],
            'route_id' => $schedule['route_id'],
            'train_id' => $schedule['train_id'],
            'departure_time' => substr($schedule['departure_time'], 0, 5), // Format as HH:MM
            'days' => explode(',', $schedule['days']),
            'status' => $schedule['status']
        ];
    } else {
        setFlashMessage('error', 'Schedule not found.');
        redirect(APP_URL . '/admin/schedules.php');
    }
}

// Get schedules for display
$schedules = fetchRows("
    SELECT s.*, 
           r.name as route_name, r.code as route_code,
           t.name as train_name, t.train_number,
           (SELECT COUNT(*) FROM schedule_stations WHERE schedule_id = s.id) as station_count
    FROM schedules s
    JOIN routes r ON s.route_id = r.id
    JOIN trains t ON s.train_id = t.id
    ORDER BY s.departure_time ASC
");

// Get flash messages
$flashSuccess = getFlashMessage('success');
$flashError = getFlashMessage('error');

// Page title
$pageTitle = 'Manage Schedules';

// Days of week
$daysOfWeek = [
    1 => 'Monday',
    2 => 'Tuesday',
    3 => 'Wednesday',
    4 => 'Thursday',
    5 => 'Friday',
    6 => 'Saturday',
    7 => 'Sunday'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metro Rail - <?php echo $pageTitle; ?></title>
    
    <!-- Modern Styling -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/admin.css">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        .schedule-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .schedule-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .modal-header.bg-danger, .modal-header.bg-primary, .modal-header.bg-success {
            color: white;
        }
        .days-badge {
            margin-right: 5px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse admin-sidebar">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">
                            <i class="fas fa-train me-2"></i>Metro Rail
                        </h4>
                        <p class="text-white-50 small">Admin Panel</p>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="stations.php">
                                <i class="fas fa-map-marker-alt"></i> Stations
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="trains.php">
                                <i class="fas fa-train"></i> Trains
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="routes.php">
                                <i class="fas fa-route"></i> Routes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="schedules.php">
                                <i class="fas fa-clock"></i> Schedules
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="fares.php">
                                <i class="fas fa-money-bill-wave"></i> Fares
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="bookings.php">
                                <i class="fas fa-ticket-alt"></i> Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="staff.php">
                                <i class="fas fa-user-tie"></i> Staff
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="maintenance.php">
                                <i class="fas fa-tools"></i> Maintenance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="announcements.php">
                                <i class="fas fa-bullhorn"></i> Announcements
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                        </li>
                    </ul>
                    
                    <hr class="text-white-50">
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>">
                                <i class="fas fa-home"></i> Main Site
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Header / Navbar -->
                <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4 rounded shadow-sm">
                    <div class="container-fluid">
                        <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target=".admin-sidebar">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <span class="navbar-brand mb-0 h1"><i class="fas fa-clock me-2"></i><?php echo $pageTitle; ?></span>
                        <div class="d-flex">
                            <div class="dropdown">
                                <button class="btn btn-link dropdown-toggle text-decoration-none" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i> <?php echo $admin['name']; ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>"><i class="fas fa-home me-2"></i>Main Site</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
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
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card shadow-sm schedule-card bg-gradient">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-0">Total Schedules</h6>
                                        <h2 class="mt-2 mb-0"><?php echo count($schedules); ?></h2>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-calendar-check text-primary fs-1"></i>
                                    </div>
                                </div>
                                <p class="card-text mt-3 mb-0">
                                    <span class="text-success">
                                        <i class="bi bi-circle-fill me-1 small"></i>
                                        <?php 
                                            $activeSchedules = array_filter($schedules, function($s) { return $s['status'] === 'active'; });
                                            echo count($activeSchedules);
                                        ?> Active
                                    </span>
                                    <span class="text-danger ms-3">
                                        <i class="bi bi-circle-fill me-1 small"></i>
                                        <?php 
                                            $cancelledSchedules = array_filter($schedules, function($s) { return $s['status'] === 'cancelled'; });
                                            echo count($cancelledSchedules);
                                        ?> Cancelled
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm schedule-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-0">Average Stations</h6>
                                        <h2 class="mt-2 mb-0">
                                            <?php 
                                                $totalStations = array_sum(array_column($schedules, 'station_count'));
                                                echo count($schedules) > 0 ? round($totalStations / count($schedules), 1) : 0;
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
                                        <?php echo $totalStations; ?> Total Stops
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm schedule-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-0">Most Popular Day</h6>
                                        <?php 
                                            // Count schedules per day
                                            $daysCount = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0];
                                            foreach ($schedules as $schedule) {
                                                $scheduleDays = explode(',', $schedule['days']);
                                                foreach ($scheduleDays as $day) {
                                                    if (isset($daysCount[$day])) {
                                                        $daysCount[$day]++;
                                                    }
                                                }
                                            }
                                            // Find most popular day
                                            $maxCount = max($daysCount);
                                            $popularDays = array_keys(array_filter($daysCount, function($count) use ($maxCount) {
                                                return $count == $maxCount;
                                            }));
                                            $popularDay = !empty($popularDays) ? $popularDays[0] : 0;
                                        ?>
                                        <h2 class="mt-2 mb-0"><?php echo $popularDay > 0 ? $daysOfWeek[$popularDay] : 'N/A'; ?></h2>
                                    </div>
                                    <div class="bg-info bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-calendar-week text-info fs-1"></i>
                                    </div>
                                </div>
                                <p class="card-text mt-3 mb-0">
                                    <span class="text-info">
                                        <i class="bi bi-calendar-check me-1"></i>
                                        <?php echo $maxCount; ?> schedules on this day
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                  <?php if ($action === 'add' || $action === 'edit'): ?>
                    <!-- Add/Edit Schedule Form -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="card-title m-0">
                                <i class="bi bi-<?php echo $action === 'add' ? 'plus-circle' : 'pencil-square'; ?> me-2"></i>
                                <?php echo $action === 'add' ? 'Add New Schedule' : 'Edit Schedule: ' . date('H:i', strtotime($formSchedule['departure_time'])) . ' - ' . implode(', ', array_map(function($day) use ($daysOfWeek) { return substr($daysOfWeek[$day], 0, 3); }, $formSchedule['days'])); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="<?php echo APP_URL; ?>/admin/schedules.php">
                                <?php if ($action === 'edit'): ?>
                                    <input type="hidden" name="id" value="<?php echo $formSchedule['id']; ?>">
                                <?php endif; ?>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="route_id" class="form-label">Route *</label>
                                        <select class="form-select" id="route_id" name="route_id" required>
                                            <option value="">-- Select Route --</option>
                                            <?php foreach ($routes as $route): ?>
                                                <option value="<?php echo $route['id']; ?>" <?php echo $formSchedule['route_id'] == $route['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($route['name'] . ' (' . $route['code'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="train_id" class="form-label">Train *</label>
                                        <select class="form-select" id="train_id" name="train_id" required>
                                            <option value="">-- Select Train --</option>
                                            <?php foreach ($trains as $train): ?>
                                                <option value="<?php echo $train['id']; ?>" <?php echo $formSchedule['train_id'] == $train['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($train['name'] . ' (' . $train['train_number'] . ') - Capacity: ' . $train['capacity']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="departure_time" class="form-label">Departure Time *</label>
                                        <input type="time" class="form-control" id="departure_time" name="departure_time" value="<?php echo $formSchedule['departure_time']; ?>" required>
                                        <small class="text-muted">This is the departure time from the first station</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="status" class="form-label">Status *</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="active" <?php echo $formSchedule['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="cancelled" <?php echo $formSchedule['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Days of Operation *</label>
                                    <div class="row">
                                        <?php foreach ($daysOfWeek as $day => $dayName): ?>
                                            <div class="col-md-3 col-sm-4 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="days[]" id="day<?php echo $day; ?>" value="<?php echo $day; ?>" <?php echo in_array($day, $formSchedule['days']) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="day<?php echo $day; ?>">
                                                        <?php echo $dayName; ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="mt-2">
                                        <button type="button" id="select-weekdays" class="btn btn-sm btn-outline-secondary me-2">Select Weekdays</button>
                                        <button type="button" id="select-weekends" class="btn btn-sm btn-outline-secondary me-2">Select Weekends</button>
                                        <button type="button" id="select-all-days" class="btn btn-sm btn-outline-secondary me-2">Select All Days</button>
                                        <button type="button" id="clear-days" class="btn btn-sm btn-outline-secondary">Clear All</button>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="alert alert-info" role="alert">
                                        <p><i class="fas fa-info-circle me-2"></i> <strong>Station Schedule Information:</strong></p>
                                        <p>
                                            When you save this schedule, the system will automatically calculate the arrival and departure times 
                                            for each station based on the route's estimated travel times between stations.
                                        </p>
                                        <p class="mb-0">
                                            Each intermediate station will have a default 2-minute stop time. The first station has only departure time, 
                                            and the last station has only arrival time.
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="<?php echo APP_URL; ?>/admin/schedules.php" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" name="<?php echo $action === 'add' ? 'add_schedule' : 'update_schedule'; ?>" class="btn btn-primary">
                                        <?php echo $action === 'add' ? 'Add Schedule' : 'Update Schedule'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>                <?php else: ?>
                    <!-- Schedules List -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-clock me-2"></i>All Schedules</h5>
                            <a href="<?php echo APP_URL; ?>/admin/schedules.php?action=add" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-lg"></i> Add New Schedule
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($schedules)): ?>
                                <div class="alert alert-info mb-0">
                                    No schedules found. Click the "Add New Schedule" button to create one.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table id="schedulesTable" class="table table-hover align-middle nowrap" style="width:100%">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Route</th>
                                                <th>Train</th>
                                                <th>Departure</th>
                                                <th>Days</th>
                                                <th>Stations</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($schedules as $schedule): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-dark"><?php echo htmlspecialchars($schedule['route_code']); ?></span>
                                                        <span class="ms-2"><?php echo htmlspecialchars($schedule['route_name']); ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($schedule['train_number']); ?></span>
                                                        <span class="ms-2"><?php echo htmlspecialchars($schedule['train_name']); ?></span>
                                                    </td>
                                                    <td><span class="badge bg-primary"><?php echo substr($schedule['departure_time'], 0, 5); ?></span></td>
                                                    <td>
                                                        <?php 
                                                            $scheduleDays = explode(',', $schedule['days']);
                                                            foreach ($scheduleDays as $day):
                                                                $bgColor = '';
                                                                switch ($day) {
                                                                    case 1:
                                                                    case 2:
                                                                    case 3:
                                                                    case 4:
                                                                    case 5:
                                                                        $bgColor = 'bg-info';
                                                                        break;
                                                                    case 6:
                                                                    case 7:
                                                                        $bgColor = 'bg-warning';
                                                                        break;
                                                                }
                                                        ?>
                                                            <span class="badge <?php echo $bgColor; ?> days-badge"><?php echo substr($daysOfWeek[$day], 0, 3); ?></span>
                                                        <?php endforeach; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success"><?php echo $schedule['station_count']; ?> stations</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $schedule['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                            <?php echo ucfirst(htmlspecialchars($schedule['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="<?php echo APP_URL; ?>/admin/schedules.php?action=edit&id=<?php echo $schedule['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="bi bi-pencil"></i> Edit
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $schedule['id']; ?>">
                                                                <i class="bi bi-eye"></i> View
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $schedule['id']; ?>">
                                                                <i class="bi bi-trash"></i> Delete
                                                            </button>
                                                        </div>
                                                          <!-- View Modal -->
                                                        <div class="modal fade" id="viewModal<?php echo $schedule['id']; ?>" tabindex="-1" aria-labelledby="viewModalLabel<?php echo $schedule['id']; ?>" aria-hidden="true">
                                                            <div class="modal-dialog modal-lg">
                                                                <div class="modal-content">
                                                                    <div class="modal-header bg-primary text-white">
                                                                        <h5 class="modal-title" id="viewModalLabel<?php echo $schedule['id']; ?>">
                                                                            <i class="bi bi-info-circle me-2"></i>Schedule Details
                                                                        </h5>
                                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-card-list me-2"></i>Schedule Information</h6>
                                                                        <div class="card mb-4">
                                                                            <div class="card-body p-0">
                                                                                <table class="table table-striped mb-0">
                                                                                    <tr>
                                                                                        <th width="30%" class="ps-3">Route</th>
                                                                                        <td><?php echo htmlspecialchars($schedule['route_name'] . ' (' . $schedule['route_code'] . ')'); ?></td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <th class="ps-3">Train</th>
                                                                                        <td><?php echo htmlspecialchars($schedule['train_name'] . ' (' . $schedule['train_number'] . ')'); ?></td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <th class="ps-3">Departure Time</th>
                                                                                        <td><?php echo substr($schedule['departure_time'], 0, 5); ?></td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <th class="ps-3">Days of Operation</th>
                                                                                <td>
                                                                                    <?php 
                                                                                    $daysList = explode(',', $schedule['days']);
                                                                                    $daysText = [];
                                                                                    
                                                                                    foreach ($daysList as $day) {
                                                                                        if (isset($daysOfWeek[$day])) {
                                                                                            $daysText[] = $daysOfWeek[$day];
                                                                                        }
                                                                                    }
                                                                                    
                                                                                    echo implode(', ', $daysText);
                                                                                    ?>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th>Status</th>
                                                                                <td>
                                                                                    <span class="badge bg-<?php echo $schedule['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                                                        <?php echo ucfirst(htmlspecialchars($schedule['status'])); ?>
                                                                                    </span>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                        
                                                                        <h6>Station Schedule</h6>
                                                                        <?php
                                                                        // Get schedule stations
                                                                        $stationSchedules = fetchRows("
                                                                            SELECT ss.*, s.name as station_name, s.code as station_code, rs.stop_order
                                                                            FROM schedule_stations ss
                                                                            JOIN stations s ON ss.station_id = s.id
                                                                            JOIN route_stations rs ON ss.station_id = rs.station_id AND rs.route_id = ?
                                                                            WHERE ss.schedule_id = ?
                                                                            ORDER BY rs.stop_order ASC
                                                                        ", [$schedule['route_id'], $schedule['id']]);
                                                                        ?>
                                                                        
                                                                        <?php if (empty($stationSchedules)): ?>
                                                                            <div class="alert alert-warning">
                                                                                No station schedule information available.
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <table class="table table-bordered table-hover">
                                                                                <thead>
                                                                                    <tr>
                                                                                        <th>Stop Order</th>
                                                                                        <th>Station</th>
                                                                                        <th>Arrival Time</th>
                                                                                        <th>Departure Time</th>
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                    <?php foreach ($stationSchedules as $stationSchedule): ?>
                                                                                        <tr>
                                                                                            <td><?php echo $stationSchedule['stop_order']; ?></td>
                                                                                            <td><?php echo htmlspecialchars($stationSchedule['station_name'] . ' (' . $stationSchedule['station_code'] . ')'); ?></td>
                                                                                            <td>
                                                                                                <?php 
                                                                                                if ($stationSchedule['arrival_time']) {
                                                                                                    echo substr($stationSchedule['arrival_time'], 0, 5);
                                                                                                } else {
                                                                                                    echo '<span class="text-muted">N/A</span>';
                                                                                                }
                                                                                                ?>
                                                                                            </td>
                                                                                            <td>
                                                                                                <?php 
                                                                                                if ($stationSchedule['departure_time']) {
                                                                                                    echo substr($stationSchedule['departure_time'], 0, 5);
                                                                                                } else {
                                                                                                    echo '<span class="text-muted">N/A</span>';
                                                                                                }
                                                                                                ?>
                                                                                            </td>
                                                                                        </tr>
                                                                                    <?php endforeach; ?>
                                                                                </tbody>
                                                                            </table>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                          <!-- Delete Modal -->
                                                        <div class="modal fade" id="deleteModal<?php echo $schedule['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $schedule['id']; ?>" aria-hidden="true">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header bg-danger text-white">
                                                                        <h5 class="modal-title" id="deleteModalLabel<?php echo $schedule['id']; ?>">Delete Schedule</h5>
                                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <p>Are you sure you want to delete this schedule?</p>
                                                                        <ul class="list-group mb-3">
                                                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                Route: 
                                                                                <span class="badge bg-dark rounded-pill">
                                                                                    <?php echo htmlspecialchars($schedule['route_name'] . ' (' . $schedule['route_code'] . ')'); ?>
                                                                                </span>
                                                                            </li>
                                                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                Train: 
                                                                                <span class="badge bg-secondary rounded-pill">
                                                                                    <?php echo htmlspecialchars($schedule['train_name'] . ' (' . $schedule['train_number'] . ')'); ?>
                                                                                </span>
                                                                            </li>
                                                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                Departure Time: 
                                                                                <span class="badge bg-primary rounded-pill">
                                                                                    <?php echo substr($schedule['departure_time'], 0, 5); ?>
                                                                                </span>
                                                                            </li>
                                                                        </ul>
                                                                        <p class="text-danger">This action cannot be undone. All station schedules associated with this schedule will also be deleted.</p>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <form method="post" action="<?php echo APP_URL; ?>/admin/schedules.php">
                                                                            <input type="hidden" name="delete_id" value="<?php echo $schedule['id']; ?>">
                                                                            <button type="submit" name="delete_schedule" class="btn btn-danger">Delete Schedule</button>
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
                <?php endif; ?>
            </div>
        </div>    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable with modern styling
            if (document.querySelector('#schedulesTable')) {
                $(document).ready(function() {
                    $('#schedulesTable').DataTable({
                        responsive: true,
                        pageLength: 10,
                        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                        dom: '<"top"lf>rt<"bottom"ip><"clear">',
                        language: {
                            search: "<i class='bi bi-search'></i> _INPUT_",
                            searchPlaceholder: "Search schedules..."
                        }
                    });
                });
            }
            
            // Day selection buttons
            document.getElementById('select-weekdays').addEventListener('click', function() {
                const weekdays = ['1', '2', '3', '4', '5'];
                document.querySelectorAll('input[name="days[]"]').forEach(function(checkbox) {
                    checkbox.checked = weekdays.includes(checkbox.value);
                });
            });
            
            document.getElementById('select-weekends').addEventListener('click', function() {
                const weekends = ['6', '7'];
                document.querySelectorAll('input[name="days[]"]').forEach(function(checkbox) {
                    checkbox.checked = weekends.includes(checkbox.value);
                });
            });
            
            document.getElementById('select-all-days').addEventListener('click', function() {
                document.querySelectorAll('input[name="days[]"]').forEach(function(checkbox) {
                    checkbox.checked = true;
                });
            });
            
            document.getElementById('clear-days').addEventListener('click', function() {
                document.querySelectorAll('input[name="days[]"]').forEach(function(checkbox) {
                    checkbox.checked = false;
                });
            });
            
            // Form validation before submit
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(event) {
                    const dayCheckboxes = document.querySelectorAll('input[name="days[]"]:checked');
                    if (dayCheckboxes.length === 0) {
                        event.preventDefault();
                        alert('Please select at least one day of operation.');
                    }
                });
            }
        });
    </script>
</body>
</html>
