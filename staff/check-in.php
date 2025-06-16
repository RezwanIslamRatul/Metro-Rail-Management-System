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

// Check if user is staff
if (!isStaff()) {
    // Redirect to appropriate dashboard
    if (isAdmin()) {
        redirect(APP_URL . '/admin');
    } else {
        redirect(APP_URL . '/user');
    }
}

// Get staff information
$userId = $_SESSION['user_id'];
$staff = fetchRow("SELECT * FROM users WHERE id = ?", [$userId]);

// Initialize variables
$errors = [];
$success = '';

// Handle ticket check-in
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['check_in'])) {
        $ticketNumber = sanitizeInput($_POST['ticket_number']);
        
        if (empty($ticketNumber)) {
            $errors[] = 'Please enter a ticket number';
        } else {
            // Check if ticket exists
            $ticket = fetchRow("
                SELECT t.*, b.booking_number, b.journey_date, b.booking_status, u.name as user_name,
                       fs.name as from_station, ts.name as to_station,
                       s.departure_time, r.name as route_name, tr.name as train_name
                FROM tickets t
                JOIN bookings b ON t.booking_id = b.id
                JOIN users u ON b.user_id = u.id
                JOIN stations fs ON b.from_station_id = fs.id
                JOIN stations ts ON b.to_station_id = ts.id
                JOIN schedules s ON b.schedule_id = s.id
                JOIN routes r ON s.route_id = r.id
                JOIN trains tr ON s.train_id = tr.id
                WHERE t.ticket_number = ?
            ", [$ticketNumber]);
            
            if (!$ticket) {
                $errors[] = 'Invalid ticket number. Ticket not found.';
            } elseif ($ticket['status'] !== 'active') {
                $errors[] = 'Ticket is ' . $ticket['status'] . '. Cannot check in.';
            } elseif ($ticket['booking_status'] !== 'confirmed') {
                $errors[] = 'Booking is ' . $ticket['booking_status'] . '. Cannot check in.';
            } elseif ($ticket['checked_in']) {
                $errors[] = 'Ticket already checked in at ' . date('M d, Y h:i A', strtotime($ticket['checked_in_at']));
            } elseif (strtotime($ticket['journey_date']) < strtotime(date('Y-m-d')) || 
                     (strtotime($ticket['journey_date']) == strtotime(date('Y-m-d')) && 
                      strtotime($ticket['departure_time']) < strtotime('-30 minutes'))) {
                $errors[] = 'Ticket has expired. Journey date was ' . date('M d, Y', strtotime($ticket['journey_date']));
            } else {
                // Update ticket status to checked in
                $ticketData = [
                    'checked_in' => 1,
                    'checked_in_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $updated = update('tickets', $ticketData, "id = " . $ticket['id']);
                
                if ($updated) {
                    // Log activity
                    logActivity('ticket_checked_in', 'Checked in ticket: ' . $ticketNumber, $userId);
                    
                    $success = 'Ticket checked in successfully.';
                } else {
                    $errors[] = 'Failed to check in ticket. Please try again.';
                }
            }
        }
    }
}

// Get recent check-ins
$recentCheckIns = fetchRows("
    SELECT t.*, b.booking_number, b.journey_date, u.name as user_name,
           fs.name as from_station, ts.name as to_station
    FROM tickets t
    JOIN bookings b ON t.booking_id = b.id
    JOIN users u ON b.user_id = u.id
    JOIN stations fs ON b.from_station_id = fs.id
    JOIN stations ts ON b.to_station_id = ts.id
    WHERE t.checked_in = 1
    ORDER BY t.checked_in_at DESC
    LIMIT 10
");

// Set page title
$pageTitle = 'Ticket Check-In';

// Include header (the staff panel has a different layout)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metro Rail - <?php echo $pageTitle; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo APP_URL; ?>/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse staff-sidebar">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">
                            <i class="fas fa-train me-2"></i>Metro Rail
                        </h4>
                        <p class="text-white-50 small">Staff Panel</p>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="check-in.php">
                                <i class="fas fa-clipboard-check"></i> Ticket Check-In
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="bookings.php">
                                <i class="fas fa-ticket-alt"></i> Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="schedules.php">
                                <i class="fas fa-clock"></i> Schedules
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
                <!-- Page Title -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-clipboard-check me-2"></i><?php echo $pageTitle; ?></h2>
                    <div class="badge bg-primary p-2">
                        <i class="fas fa-clock me-1"></i> <?php echo date('F j, Y - h:i A'); ?>
                    </div>
                </div>
                
                <!-- Flash Messages -->
                <?php displayFlashMessages(); ?>
                
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
                    <div class="col-md-6">
                        <!-- Check-In Form -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-ticket-alt me-2"></i>Check-In Ticket</h5>
                            </div>
                            <div class="card-body">
                                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" class="mb-3">
                                    <div class="mb-3">
                                        <label for="ticket_number" class="form-label">Ticket Number</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control form-control-lg" id="ticket_number" name="ticket_number" 
                                                   placeholder="Enter ticket number" autofocus required>
                                            <button type="submit" name="check_in" class="btn btn-primary">
                                                <i class="fas fa-search me-2"></i>Verify & Check In
                                            </button>
                                        </div>
                                        <div class="form-text">
                                            Enter the ticket number (e.g., TK20250501123456)
                                        </div>
                                    </div>
                                </form>
                                
                                <div class="text-center">
                                    <p class="text-muted mb-2">or scan QR code</p>
                                    <button type="button" class="btn btn-outline-primary" id="scanQrBtn">
                                        <i class="fas fa-qrcode me-2"></i>Scan QR Code
                                    </button>
                                    
                                    <div id="qrScannerPlaceholder" class="mt-3 d-none">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>QR scanner functionality would be implemented in a production environment.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tips -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Check-In Guidelines</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">
                                        <i class="fas fa-check text-success me-2"></i> Verify passenger identity if needed
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-check text-success me-2"></i> Ensure ticket is for today's journey
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-check text-success me-2"></i> Direct passengers to the correct platform
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-check text-success me-2"></i> Check baggage meets size regulations
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-check text-success me-2"></i> Assist passengers with special needs
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <!-- Recent Check-Ins -->
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Check-Ins</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentCheckIns)): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>No recent check-ins found.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Ticket #</th>
                                                    <th>Passenger</th>
                                                    <th>Journey</th>
                                                    <th>Check-In Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentCheckIns as $checkIn): ?>
                                                    <tr>
                                                        <td><strong><?php echo $checkIn['ticket_number']; ?></strong></td>
                                                        <td><?php echo $checkIn['passenger_name']; ?></td>
                                                        <td>
                                                            <small>
                                                                <?php echo $checkIn['from_station']; ?> → <?php echo $checkIn['to_station']; ?><br>
                                                                <span class="text-muted"><?php echo date('M d, Y', strtotime($checkIn['journey_date'])); ?></span>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <small>
                                                                <?php echo date('h:i A', strtotime($checkIn['checked_in_at'])); ?><br>
                                                                <span class="text-muted"><?php echo date('M d, Y', strtotime($checkIn['checked_in_at'])); ?></span>
                                                            </small>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Today's Statistics -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Today's Statistics</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="text-center">
                                            <h3 class="mb-0">
                                                <?php
                                                $todayCheckins = fetchRow("
                                                    SELECT COUNT(*) as count 
                                                    FROM tickets 
                                                    WHERE checked_in = 1 
                                                    AND DATE(checked_in_at) = CURDATE()
                                                ")['count'];
                                                echo $todayCheckins;
                                                ?>
                                            </h3>
                                            <p class="text-muted">Check-ins</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="text-center">
                                            <h3 class="mb-0">
                                                <?php
                                                $pendingDepartures = fetchRow("
                                                    SELECT COUNT(*) as count 
                                                    FROM schedules 
                                                    WHERE DATE(CONCAT(CURDATE(), ' ', departure_time)) = CURDATE() 
                                                    AND CONCAT(CURDATE(), ' ', departure_time) > NOW()
                                                ")['count'];
                                                echo $pendingDepartures;
                                                ?>
                                            </h3>
                                            <p class="text-muted">Pending Departures</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="text-center">
                                            <h3 class="mb-0">
                                                <?php
                                                $totalPassengers = fetchRow("
                                                    SELECT SUM(b.passengers) as count 
                                                    FROM bookings b
                                                    JOIN schedules s ON b.schedule_id = s.id
                                                    WHERE b.journey_date = CURDATE() 
                                                    AND b.booking_status = 'confirmed'
                                                ")['count'] ?? 0;
                                                echo $totalPassengers;
                                                ?>
                                            </h3>
                                            <p class="text-muted">Expected Passengers</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="progress mb-2">
                                    <?php
                                    $checkInPercentage = ($totalPassengers > 0) ? ($todayCheckins / $totalPassengers) * 100 : 0;
                                    ?>
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $checkInPercentage; ?>%"
                                         aria-valuenow="<?php echo $checkInPercentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?php echo round($checkInPercentage); ?>%
                                    </div>
                                </div>
                                <div class="text-muted text-center small">Check-in Progress</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // QR Code scanner button
            $('#scanQrBtn').click(function() {
                $('#qrScannerPlaceholder').toggleClass('d-none');
            });
            
            // Auto-focus ticket number input
            $('#ticket_number').focus();
        });
    </script>
</body>
</html>
