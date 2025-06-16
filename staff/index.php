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

// Get today's count
$todayBookingsCount = fetchRow("SELECT COUNT(*) as count FROM bookings WHERE DATE(booking_date) = CURDATE()")['count'];
$pendingBookingsCount = fetchRow("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'pending'")['count'];

// Get upcoming schedules
$upcomingSchedules = fetchRows("
    SELECT s.*, r.name as route_name, t.name as train_name, t.train_number
    FROM schedules s
    JOIN routes r ON s.route_id = r.id
    JOIN trains t ON s.train_id = t.id
    WHERE s.status = 'active'
    ORDER BY s.departure_time ASC
    LIMIT 5
");

// Get active maintenance
$activeMaintenances = fetchRows("
    SELECT * FROM maintenance_schedules
    WHERE status IN ('scheduled', 'in_progress')
    AND end_datetime >= NOW()
    ORDER BY start_datetime ASC
");

// Get today's bookings
$todayBookings = fetchRows("
    SELECT b.*, 
           u.name as user_name,
           fs.name as from_station, 
           ts.name as to_station,
           s.departure_time,
           r.name as route_name
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN stations fs ON b.from_station_id = fs.id
    JOIN stations ts ON b.to_station_id = ts.id
    JOIN schedules s ON b.schedule_id = s.id
    JOIN routes r ON s.route_id = r.id
    WHERE DATE(b.booking_date) = CURDATE()
    ORDER BY s.departure_time ASC
");

// Get pending bookings
$pendingBookings = fetchRows("
    SELECT b.*, 
           u.name as user_name,
           fs.name as from_station, 
           ts.name as to_station,
           s.departure_time,
           r.name as route_name
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN stations fs ON b.from_station_id = fs.id
    JOIN stations ts ON b.to_station_id = ts.id
    JOIN schedules s ON b.schedule_id = s.id
    JOIN routes r ON s.route_id = r.id
    WHERE b.booking_status = 'pending'
    ORDER BY b.journey_date ASC, s.departure_time ASC
");

// Set page title
$pageTitle = 'Staff Dashboard';

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
            <div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse admin-sidebar">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">
                            <i class="fas fa-train me-2"></i>Metro Rail
                        </h4>
                        <p class="text-white-50 small">Staff Panel</p>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="schedules.php">
                                <i class="fas fa-clock"></i> Schedules
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="bookings.php">
                                <i class="fas fa-ticket-alt"></i> Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="check-in.php">
                                <i class="fas fa-qrcode"></i> Check-in
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
                        <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <span class="navbar-brand mb-0 h1"><i class="fas fa-tachometer-alt me-2"></i><?php echo $pageTitle; ?></span>
                        <div class="d-flex">
                            <div class="dropdown">
                                <button class="btn btn-link dropdown-toggle text-decoration-none" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i> <?php echo $staff['name']; ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
                                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>
                
                <!-- Main Dashboard Content -->
                <div class="row mb-4">
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card shadow border-start-primary border-5 h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                            Today's Bookings
                                        </div>
                                        <div class="h5 mb-0 fw-bold"><?php echo $todayBookingsCount; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0">
                                <a href="bookings.php?date=today" class="text-decoration-none small text-primary">
                                    View Details <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card shadow border-start-warning border-5 h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                            Pending Bookings
                                        </div>
                                        <div class="h5 mb-0 fw-bold"><?php echo $pendingBookingsCount; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0">
                                <a href="bookings.php?status=pending" class="text-decoration-none small text-warning">
                                    View Details <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card shadow border-start-success border-5 h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                            Scan Ticket
                                        </div>
                                        <div class="h5 mb-0 fw-bold">Check-in</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-qrcode fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0">
                                <a href="check-in.php" class="text-decoration-none small text-success">
                                    Start Scanning <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card shadow border-start-info border-5 h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                            New Announcement
                                        </div>
                                        <div class="h5 mb-0 fw-bold">Create</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-bullhorn fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0">
                                <a href="announcements.php?action=new" class="text-decoration-none small text-info">
                                    Create Now <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Today's Bookings -->
                    <div class="col-xl-8 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 fw-bold text-primary">Today's Bookings</h6>
                                <a href="bookings.php?date=today" class="text-decoration-none small">
                                    View All <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($todayBookings)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-ticket-alt fa-3x mb-3"></i>
                                        <p>No bookings found for today.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Booking #</th>
                                                    <th>Passenger</th>
                                                    <th>Route</th>
                                                    <th>From</th>
                                                    <th>To</th>
                                                    <th>Departure</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($todayBookings as $booking): ?>
                                                    <tr>
                                                        <td><?php echo $booking['booking_number']; ?></td>
                                                        <td><?php echo $booking['user_name']; ?></td>
                                                        <td><?php echo $booking['route_name']; ?></td>
                                                        <td><?php echo $booking['from_station']; ?></td>
                                                        <td><?php echo $booking['to_station']; ?></td>
                                                        <td><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></td>
                                                        <td>
                                                            <?php
                                                            $statusClass = '';
                                                            switch ($booking['booking_status']) {
                                                                case 'confirmed':
                                                                    $statusClass = 'bg-success';
                                                                    break;
                                                                case 'pending':
                                                                    $statusClass = 'bg-warning text-dark';
                                                                    break;
                                                                case 'cancelled':
                                                                    $statusClass = 'bg-danger';
                                                                    break;
                                                                case 'completed':
                                                                    $statusClass = 'bg-info';
                                                                    break;
                                                                default:
                                                                    $statusClass = 'bg-secondary';
                                                            }
                                                            ?>
                                                            <span class="badge <?php echo $statusClass; ?>">
                                                                <?php echo ucfirst($booking['booking_status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="view-booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-primary">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                                <?php if ($booking['booking_status'] === 'pending'): ?>
                                                                    <a href="update-booking.php?id=<?php echo $booking['id']; ?>&status=confirmed" class="btn btn-success" onclick="return confirm('Confirm this booking?');">
                                                                        <i class="fas fa-check"></i>
                                                                    </a>
                                                                <?php endif; ?>
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
                    </div>

                    <!-- Upcoming Schedules -->
                    <div class="col-xl-4 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 fw-bold text-primary">Upcoming Schedules</h6>
                                <a href="schedules.php" class="text-decoration-none small">
                                    View All <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($upcomingSchedules)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-clock fa-3x mb-3"></i>
                                        <p>No upcoming schedules found.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($upcomingSchedules as $schedule): ?>
                                            <div class="list-group-item px-0">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo $schedule['route_name']; ?></h6>
                                                    <small class="text-primary">
                                                        <?php echo date('h:i A', strtotime($schedule['departure_time'])); ?>
                                                    </small>
                                                </div>
                                                <p class="mb-1">Train: <?php echo $schedule['train_name']; ?> (<?php echo $schedule['train_number']; ?>)</p>
                                                <small class="text-muted">
                                                    Days: 
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
                                                        $dayLabels = [];
                                                        foreach ($days as $day) {
                                                            if (isset($dayNames[$day])) {
                                                                $dayLabels[] = $dayNames[$day];
                                                            }
                                                        }
                                                        echo implode(', ', $dayLabels);
                                                    ?>
                                                </small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Pending Bookings -->
                    <div class="col-xl-8 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 fw-bold text-primary">Pending Bookings</h6>
                                <a href="bookings.php?status=pending" class="text-decoration-none small">
                                    View All <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($pendingBookings)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                                        <p>No pending bookings at the moment.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Booking #</th>
                                                    <th>Passenger</th>
                                                    <th>Journey Date</th>
                                                    <th>From</th>
                                                    <th>To</th>
                                                    <th>Amount</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pendingBookings as $booking): ?>
                                                    <tr>
                                                        <td><?php echo $booking['booking_number']; ?></td>
                                                        <td><?php echo $booking['user_name']; ?></td>
                                                        <td><?php echo formatDate($booking['journey_date'], 'M d, Y'); ?></td>
                                                        <td><?php echo $booking['from_station']; ?></td>
                                                        <td><?php echo $booking['to_station']; ?></td>
                                                        <td><?php echo formatCurrency($booking['amount']); ?></td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="view-booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-primary">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                                <a href="update-booking.php?id=<?php echo $booking['id']; ?>&status=confirmed" class="btn btn-success" onclick="return confirm('Confirm this booking?');">
                                                                    <i class="fas fa-check"></i>
                                                                </a>
                                                                <a href="update-booking.php?id=<?php echo $booking['id']; ?>&status=cancelled" class="btn btn-danger" onclick="return confirm('Cancel this booking?');">
                                                                    <i class="fas fa-times"></i>
                                                                </a>
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
                    </div>

                    <!-- Active Maintenance -->
                    <div class="col-xl-4 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 fw-bold text-primary">Active Maintenance</h6>
                                <a href="maintenance.php" class="text-decoration-none small">
                                    View All <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($activeMaintenances)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-tools fa-3x mb-3"></i>
                                        <p>No active maintenance scheduled.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($activeMaintenances as $maintenance): ?>
                                            <div class="list-group-item px-0">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo $maintenance['title']; ?></h6>
                                                    <span class="badge bg-warning text-dark">
                                                        <?php echo ucfirst($maintenance['status']); ?>
                                                    </span>
                                                </div>
                                                <p class="mb-1 small"><?php echo $maintenance['description']; ?></p>
                                                <small class="text-muted">
                                                    <?php echo formatDate($maintenance['start_datetime'], 'M d, h:i A'); ?> - 
                                                    <?php echo formatDate($maintenance['end_datetime'], 'M d, h:i A'); ?>
                                                </small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-grid gap-2 mt-3">
                                    <a href="maintenance.php?action=new" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus me-1"></i> Schedule Maintenance
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 fw-bold text-primary">Quick Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="row justify-content-center">
                                    <div class="col-md-3 col-sm-6 mb-4">
                                        <div class="text-center">
                                            <a href="check-in.php" class="btn btn-lg btn-outline-primary rounded-circle mb-3">
                                                <i class="fas fa-qrcode fa-2x"></i>
                                            </a>
                                            <h6>Scan Ticket</h6>
                                            <p class="small text-muted">Check-in passengers</p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 col-sm-6 mb-4">
                                        <div class="text-center">
                                            <a href="bookings.php?action=new" class="btn btn-lg btn-outline-success rounded-circle mb-3">
                                                <i class="fas fa-ticket-alt fa-2x"></i>
                                            </a>
                                            <h6>New Booking</h6>
                                            <p class="small text-muted">Create a new booking</p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 col-sm-6 mb-4">
                                        <div class="text-center">
                                            <a href="announcements.php?action=new" class="btn btn-lg btn-outline-info rounded-circle mb-3">
                                                <i class="fas fa-bullhorn fa-2x"></i>
                                            </a>
                                            <h6>Announcement</h6>
                                            <p class="small text-muted">Create announcement</p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 col-sm-6 mb-4">
                                        <div class="text-center">
                                            <a href="reports.php" class="btn btn-lg btn-outline-warning rounded-circle mb-3">
                                                <i class="fas fa-chart-bar fa-2x"></i>
                                            </a>
                                            <h6>Reports</h6>
                                            <p class="small text-muted">View booking reports</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <footer class="bg-white sticky-footer mt-4">
                    <div class="container">
                        <div class="copyright text-center">
                            <span>Copyright &copy; Metro Rail System <?php echo date('Y'); ?></span>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
