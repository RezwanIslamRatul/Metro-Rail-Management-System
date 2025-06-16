<?php
// Include necessary files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session
session_start();

// Get all active stations
$stations = fetchRows("SELECT * FROM stations WHERE status = 'active' ORDER BY name ASC");

// Get all active routes
$routes = fetchRows("SELECT * FROM routes WHERE status = 'active' ORDER BY name ASC");

// Initialize variables
$errors = [];
$formData = [
    'from_station_id' => isset($_GET['from']) ? sanitizeInput($_GET['from']) : '',
    'to_station_id' => isset($_GET['to']) ? sanitizeInput($_GET['to']) : '',
    'journey_date' => isset($_GET['date']) ? sanitizeInput($_GET['date']) : date('Y-m-d'),
    'day' => ''
];

// Set day of week based on journey date
$formData['day'] = date('N', strtotime($formData['journey_date']));

// Initialize schedules array
$schedules = [];

// Check if search parameters are set
if (!empty($formData['from_station_id']) || !empty($formData['to_station_id'])) {
    // Build query based on provided parameters
    $query = "
        SELECT s.*, 
               r.name as route_name, 
               t.name as train_name,
               t.train_number
        FROM schedules s
        JOIN routes r ON s.route_id = r.id
        JOIN trains t ON s.train_id = t.id
        WHERE s.status = 'active'
    ";
    
    $params = [];
    
    // Filter by day of week
    $query .= " AND FIND_IN_SET(?, s.days) > 0";
    $params[] = $formData['day'];
    
    // Filter by route
    if (!empty($formData['from_station_id']) && !empty($formData['to_station_id'])) {
        // Both from and to stations are specified, find routes with both stations
        $query .= " AND EXISTS (
            SELECT 1 FROM route_stations rs1
            JOIN route_stations rs2 ON rs1.route_id = rs2.route_id
            WHERE rs1.route_id = r.id
            AND rs1.station_id = ?
            AND rs2.station_id = ?
            AND rs1.stop_order < rs2.stop_order
        )";
        $params[] = $formData['from_station_id'];
        $params[] = $formData['to_station_id'];
    } elseif (!empty($formData['from_station_id'])) {
        // Only from station is specified
        $query .= " AND EXISTS (
            SELECT 1 FROM route_stations rs
            WHERE rs.route_id = r.id
            AND rs.station_id = ?
        )";
        $params[] = $formData['from_station_id'];
    } elseif (!empty($formData['to_station_id'])) {
        // Only to station is specified
        $query .= " AND EXISTS (
            SELECT 1 FROM route_stations rs
            WHERE rs.route_id = r.id
            AND rs.station_id = ?
        )";
        $params[] = $formData['to_station_id'];
    }
    
    // Order by departure time
    $query .= " ORDER BY s.departure_time ASC";
    
    // Execute query
    $schedules = fetchRows($query, $params);
    
    // If no schedules found
    if (empty($schedules)) {
        $errors[] = 'No schedules found for the selected criteria. Please try different options.';
    }
}

// Set page title
$pageTitle = 'Train Schedules';

// Include header
require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold"><i class="fas fa-clock me-2"></i>Train Schedules</h1>
                <p class="lead">Find train schedules for your journey. Filter by stations or view all available schedules.</p>
            </div>
            <div class="col-lg-6">
                <img src="images/schedule-illustration.svg" class="img-fluid" alt="Schedule Illustration" onerror="this.style.display='none'">
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <!-- Search Form -->
    <div class="card modern-card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-search me-2"></i>Search Schedules</h5>
        </div>
        <div class="card-body">
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET" id="schedule-search-form">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="form-floating mb-3">
                            <select class="form-select modern-select" id="from_station_id" name="from">
                                <option value="">Any Departure Station</option>
                                <?php foreach ($stations as $station): ?>
                                    <option value="<?php echo $station['id']; ?>" <?php echo $formData['from_station_id'] == $station['id'] ? 'selected' : ''; ?>>
                                        <?php echo $station['name']; ?> (<?php echo $station['code']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="from_station_id">From Station (Optional)</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating mb-3">
                            <select class="form-select modern-select" id="to_station_id" name="to">
                                <option value="">Any Arrival Station</option>
                                <?php foreach ($stations as $station): ?>
                                    <option value="<?php echo $station['id']; ?>" <?php echo $formData['to_station_id'] == $station['id'] ? 'selected' : ''; ?>>
                                        <?php echo $station['name']; ?> (<?php echo $station['code']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="to_station_id">To Station (Optional)</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating mb-3">
                            <input type="date" class="form-control" id="journey_date" name="date" 
                                   value="<?php echo $formData['journey_date']; ?>"
                                   min="<?php echo date('Y-m-d'); ?>"
                                   max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>"
                                   required>
                            <label for="journey_date">Date</label>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg action-button">
                        <i class="fas fa-search me-2"></i>Find Schedules
                    </button>
                </div>
            </form>
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
      <!-- Schedule Results -->
    <?php if (!empty($schedules)): ?>
        <div class="card modern-card">
            <div class="card-header schedule-header">
                <h5 class="mb-0"><i class="fas fa-train me-2"></i>Available Schedules</h5>
                <span class="badge rounded-pill bg-light text-dark schedule-date">
                    <?php echo formatDate($formData['journey_date'], 'l, F j, Y'); ?>
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover schedule-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Train</th>
                                <th>Route</th>
                                <th>Departure</th>
                                <th>Days</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $schedule): ?>
                                <tr class="schedule-row">
                                    <td>
                                        <div class="train-info">
                                            <span class="train-name"><?php echo $schedule['train_name']; ?></span>
                                            <span class="train-number"><?php echo $schedule['train_number']; ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo $schedule['route_name']; ?></td>
                                    <td><span class="time-badge"><?php echo date('h:i A', strtotime($schedule['departure_time'])); ?></span></td>
                                    <td>
                                        <div class="day-badges">
                                        <?php
                                        $days = explode(',', $schedule['days']);
                                        $dayNames = [
                                            '1' => 'Mon',
                                            '2' => 'Tue',
                                            '3' => 'Wed',
                                            '4' => 'Thu',
                                            '5' => 'Fri',
                                            '6' => 'Sat',
                                            '7' => 'Sun'
                                        ];
                                        foreach ($days as $day) {
                                            $isCurrentDay = $day == $formData['day'] ? 'current-day' : '';
                                            echo '<span class="day-badge '.$isCurrentDay.'">' . $dayNames[$day] . '</span>';
                                        }
                                        ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-success status-badge">Active</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="action-buttons">
                                            <a href="schedule-detail.php?id=<?php echo $schedule['id']; ?>&date=<?php echo $formData['journey_date']; ?>" class="btn btn-sm btn-outline-primary action-btn" data-bs-toggle="tooltip" title="View schedule details">
                                                <i class="fas fa-info-circle"></i> Details
                                            </a>
                                            
                                            <?php if (isLoggedIn()): ?>
                                                <a href="user/booking.php?schedule=<?php echo $schedule['id']; ?>&from=<?php echo $formData['from_station_id']; ?>&to=<?php echo $formData['to_station_id']; ?>&date=<?php echo $formData['journey_date']; ?>" class="btn btn-sm btn-success action-btn" data-bs-toggle="tooltip" title="Book a ticket for this schedule">
                                                    <i class="fas fa-ticket-alt"></i> Book
                                                </a>
                                            <?php else: ?>
                                                <a href="login.php?redirect=<?php echo urlencode('user/booking.php?schedule=' . $schedule['id'] . '&from=' . $formData['from_station_id'] . '&to=' . $formData['to_station_id'] . '&date=' . $formData['journey_date']); ?>" class="btn btn-sm btn-success action-btn" data-bs-toggle="tooltip" title="Login to book a ticket">
                                                    <i class="fas fa-ticket-alt"></i> Book
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">Showing <?php echo count($schedules); ?> schedules</small>
                    <?php if (isLoggedIn()): ?>
                        <a href="user/booking.php?from=<?php echo $formData['from_station_id']; ?>&to=<?php echo $formData['to_station_id']; ?>&date=<?php echo $formData['journey_date']; ?>" class="btn btn-primary action-button">
                            <i class="fas fa-ticket-alt me-2"></i>Book Ticket
                        </a>
                    <?php else: ?>
                        <a href="login.php?redirect=<?php echo urlencode('user/booking.php?from=' . $formData['from_station_id'] . '&to=' . $formData['to_station_id'] . '&date=' . $formData['journey_date']); ?>" class="btn btn-primary action-button">
                            <i class="fas fa-sign-in-alt me-2"></i>Login to Book
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php elseif (empty($errors) && (empty($formData['from_station_id']) && empty($formData['to_station_id']))): ?>
        <div class="alert alert-info alert-modern">
            <i class="fas fa-info-circle me-2"></i>Please select at least one station to search for schedules.
        </div>
    <?php endif; ?>
</div>

<script>
    // Add JavaScript to prevent from and to being the same station
    document.addEventListener('DOMContentLoaded', function() {
        const fromSelect = document.getElementById('from_station_id');
        const toSelect = document.getElementById('to_station_id');
        
        // Function to check and warn about same stations
        function checkStations() {
            if (fromSelect.value && toSelect.value && fromSelect.value === toSelect.value) {
                alert('Departure and arrival stations cannot be the same.');
                toSelect.value = '';
            }
        }
        
        // Add event listeners
        fromSelect.addEventListener('change', checkStations);
        toSelect.addEventListener('change', checkStations);
    });
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>
