<?php
// Include necessary files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session
session_start();

// Initialize variables
$errors = [];
$success = '';
$scheduleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$journeyDate = isset($_GET['date']) ? sanitizeInput($_GET['date']) : date('Y-m-d');

// Validate schedule ID
if ($scheduleId <= 0) {
    $errors[] = 'Invalid schedule ID';
}

// If no errors, get schedule details
if (empty($errors)) {
    // Get schedule details
    $schedule = fetchRow("
        SELECT s.*, 
               r.name as route_name, r.description as route_description,
               t.name as train_name, t.train_number, t.capacity as train_capacity,
               t.description as train_description
        FROM schedules s
        JOIN routes r ON s.route_id = r.id
        JOIN trains t ON s.train_id = t.id
        WHERE s.id = ?
    ", [$scheduleId]);
    
    if (!$schedule) {
        $errors[] = 'Schedule not found';
    } else {
        // Get all stations on this route
        $routeStations = fetchRows("
            SELECT rs.*, s.name as station_name, s.code as station_code, s.address
            FROM route_stations rs
            JOIN stations s ON rs.station_id = s.id
            WHERE rs.route_id = ?
            ORDER BY rs.stop_order ASC
        ", [$schedule['route_id']]);
        
        // Get schedule stations with arrival/departure times
        $scheduleStations = fetchRows("
            SELECT ss.*, s.name as station_name, s.code as station_code
            FROM schedule_stations ss
            JOIN stations s ON ss.station_id = s.id
            WHERE ss.schedule_id = ?
            ORDER BY CASE 
                WHEN ss.arrival_time IS NULL THEN ss.departure_time 
                ELSE ss.arrival_time 
            END ASC
        ", [$scheduleId]);
        
        // Convert days string to array
        $schedule['days_array'] = explode(',', $schedule['days']);
        
        // Check if schedule operates on the selected date
        $dayOfWeek = date('N', strtotime($journeyDate));
        $operatesOnDate = in_array($dayOfWeek, $schedule['days_array']);
    }
}

// Set page title
$pageTitle = 'Schedule Details';

// Include header
require_once 'includes/header.php';
?>

<div class="container my-5">
    <!-- Page Title -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="text-primary"><i class="fas fa-train me-2"></i>Schedule Details</h2>
                <a href="schedules.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Schedules
                </a>
            </div>
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
    
    <!-- Schedule Details -->
    <?php if (isset($schedule) && !empty($schedule)): ?>
        <!-- Basic Info Card -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Schedule Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h5><?php echo $schedule['train_name']; ?></h5>
                                <p class="text-muted mb-1">Train Number: <?php echo $schedule['train_number']; ?></p>
                                <p class="text-muted mb-1">Capacity: <?php echo $schedule['train_capacity']; ?> passengers</p>
                                <p class="text-muted mb-0"><?php echo $schedule['train_description']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5><?php echo $schedule['route_name']; ?> Route</h5>
                                <p class="text-muted mb-1"><?php echo $schedule['route_description']; ?></p>
                                <p class="text-muted mb-1">
                                    Departure: <strong><?php echo date('h:i A', strtotime($schedule['departure_time'])); ?></strong>
                                </p>
                                <p class="badge bg-<?php echo $operatesOnDate ? 'success' : 'warning'; ?> mb-0">
                                    <?php echo $operatesOnDate ? 'Operates on ' . formatDate($journeyDate, 'l, F j, Y') : 'Does not operate on ' . formatDate($journeyDate, 'l, F j, Y'); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="bg-light p-3 rounded mb-3">
                            <h6>Schedule Days</h6>
                            <div class="d-flex flex-wrap">
                                <?php 
                                $daysOfWeek = [
                                    '1' => 'Monday',
                                    '2' => 'Tuesday',
                                    '3' => 'Wednesday',
                                    '4' => 'Thursday',
                                    '5' => 'Friday',
                                    '6' => 'Saturday',
                                    '7' => 'Sunday'
                                ];
                                
                                foreach ($daysOfWeek as $dayNum => $dayName): 
                                ?>
                                    <span class="badge <?php echo in_array($dayNum, $schedule['days_array']) ? 'bg-primary' : 'bg-secondary text-decoration-line-through'; ?> me-2 mb-2">
                                        <?php echo $dayName; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <?php if ($operatesOnDate): ?>
                            <div class="mt-4">
                                <?php if (isLoggedIn()): ?>
                                    <a href="user/booking.php?schedule=<?php echo $schedule['id']; ?>&date=<?php echo $journeyDate; ?>" class="btn btn-success">
                                        <i class="fas fa-ticket-alt me-2"></i>Book Ticket for This Train
                                    </a>
                                <?php else: ?>
                                    <a href="login.php?redirect=<?php echo urlencode('user/booking.php?schedule=' . $schedule['id'] . '&date=' . $journeyDate); ?>" class="btn btn-success">
                                        <i class="fas fa-sign-in-alt me-2"></i>Login to Book Ticket
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Availability Calendar</h5>
                    </div>
                    <div class="card-body">
                        <div class="availability-calendar">
                            <!-- Simple availability display for next 7 days -->
                            <p class="mb-2 text-muted">Train availability for the next 7 days:</p>
                            <div class="list-group">
                                <?php
                                for ($i = 0; $i < 7; $i++) {
                                    $date = date('Y-m-d', strtotime('+' . $i . ' days'));
                                    $dayNum = date('N', strtotime($date));
                                    $available = in_array($dayNum, $schedule['days_array']);
                                    ?>
                                    <a href="schedule-detail.php?id=<?php echo $scheduleId; ?>&date=<?php echo $date; ?>" 
                                       class="list-group-item list-group-item-action <?php echo $journeyDate == $date ? 'active' : ''; ?>">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo formatDate($date, 'l'); ?></h6>
                                            <small><?php echo formatDate($date, 'M j'); ?></small>
                                        </div>
                                        <div class="d-flex w-100 justify-content-between align-items-center">
                                            <small><?php echo formatDate($date, 'F j, Y'); ?></small>
                                            <?php if ($available): ?>
                                                <span class="badge bg-success">Available</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Not Running</span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Schedule Timeline -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Route Timeline</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($scheduleStations)): ?>
                    <div class="route-timeline">
                        <?php foreach ($scheduleStations as $index => $station): ?>
                            <div class="route-timeline-item">
                                <div class="route-timeline-point <?php echo $index === 0 ? 'starting-point' : ($index === count($scheduleStations) - 1 ? 'ending-point' : ''); ?>"></div>
                                <div class="route-timeline-content">
                                    <div class="card mb-0">
                                        <div class="card-body py-2">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <h6><?php echo $station['station_name']; ?> (<?php echo $station['station_code']; ?>)</h6>
                                                </div>
                                                <div class="col-md-4">
                                                    <?php if ($station['arrival_time']): ?>
                                                        <div class="mb-1">
                                                            <small class="text-muted me-2">Arrival:</small>
                                                            <span class="text-primary"><?php echo date('h:i A', strtotime($station['arrival_time'])); ?></span>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="mb-1">
                                                            <small class="text-muted me-2">Arrival:</small>
                                                            <span class="text-muted">Starting Point</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-4">
                                                    <?php if ($station['departure_time']): ?>
                                                        <div class="mb-1">
                                                            <small class="text-muted me-2">Departure:</small>
                                                            <span class="text-primary"><?php echo date('h:i A', strtotime($station['departure_time'])); ?></span>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="mb-1">
                                                            <small class="text-muted me-2">Departure:</small>
                                                            <span class="text-muted">Terminal Point</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>No station schedule data is available for this route.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    /* Route Timeline Styles */
    .route-timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .route-timeline::before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        left: 15px;
        width: 2px;
        background-color: #dee2e6;
    }
    
    .route-timeline-item {
        position: relative;
        margin-bottom: 20px;
    }
    
    .route-timeline-point {
        position: absolute;
        left: -30px;
        top: 20px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background-color: #6c757d;
        border: 3px solid #ffffff;
        z-index: 1;
    }
    
    .route-timeline-point.starting-point {
        background-color: #28a745;
    }
    
    .route-timeline-point.ending-point {
        background-color: #dc3545;
    }
    
    .route-timeline-content {
        padding-bottom: 10px;
    }
    
    /* Availability Calendar Styles */
    .list-group-item.active {
        z-index: 2;
        color: #fff;
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?>
