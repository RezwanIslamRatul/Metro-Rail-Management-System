<?php
// Start output buffering
ob_start();

// Include the admin authentication file
require_once 'admin_auth.php';

// Get admin information
$userId = $_SESSION['user_id'];
$admin = fetchRow("SELECT * FROM users WHERE id = ?", [$userId]);

// Debug logging
file_put_contents($log_dir . '/admin_debug.txt', 
    date('Y-m-d H:i:s') . " - Admin Index Page accessed\n" .
    "SESSION: " . json_encode($_SESSION) . "\n" .
    "APP_URL: " . APP_URL . "\n" .
    "isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false') . "\n" .
    "isAdmin(): " . (isAdmin() ? 'true' : 'false') . "\n",
    FILE_APPEND
);

// Check if user is logged in
if (!isLoggedIn()) {
    // Redirect to login page
    debug_log("Not logged in, redirecting to login page", null, 'admin_debug.txt');
    redirect(APP_URL . '/login.php');
}

// Check if user is admin
if (!isAdmin()) {
    debug_log("Not admin, redirecting to appropriate dashboard", null, 'admin_debug.txt');
    // Redirect to appropriate dashboard
    if (isStaff()) {
        debug_log("Redirecting to staff dashboard", null, 'admin_debug.txt');
        redirect(APP_URL . '/staff');    } else {
        debug_log("Redirecting to user dashboard", null, 'admin_debug.txt');
        redirect(APP_URL . '/user');
    }
}

debug_log("Admin access allowed", null, 'admin_debug.txt');

// Get admin information
$userId = $_SESSION['user_id'];
$admin = fetchRow("SELECT * FROM users WHERE id = ?", [$userId]);

// Get various counts for dashboard
$totalUsers = fetchRow("SELECT COUNT(*) as count FROM users WHERE role = 'user'")['count'];
$totalStaff = fetchRow("SELECT COUNT(*) as count FROM users WHERE role = 'staff'")['count'];
$totalStations = fetchRow("SELECT COUNT(*) as count FROM stations")['count'];
$totalTrains = fetchRow("SELECT COUNT(*) as count FROM trains")['count'];
$totalRoutes = fetchRow("SELECT COUNT(*) as count FROM routes")['count'];
$totalBookings = fetchRow("SELECT COUNT(*) as count FROM bookings")['count'];

// Get today's bookings
$todayBookings = fetchRow("SELECT COUNT(*) as count FROM bookings WHERE DATE(booking_date) = CURDATE()")['count'];

// Get pending bookings
$pendingBookings = fetchRow("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'pending'")['count'];

// Get recent bookings
$recentBookings = fetchRows("
    SELECT b.*, 
           u.name as user_name,
           fs.name as from_station, 
           ts.name as to_station
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN stations fs ON b.from_station_id = fs.id
    JOIN stations ts ON b.to_station_id = ts.id
    ORDER BY b.booking_date DESC
    LIMIT 5
");

// Get recent users
$recentUsers = fetchRows("
    SELECT *
    FROM users
    WHERE role = 'user'
    ORDER BY created_at DESC
    LIMIT 5
");

// Get active maintenance schedules
$maintenanceSchedules = fetchRows("
    SELECT *
    FROM maintenance_schedules
    WHERE status IN ('scheduled', 'in_progress')
    AND end_datetime >= NOW()
    ORDER BY start_datetime ASC
    LIMIT 3
");

// Set page title
$pageTitle = 'Admin Dashboard';

// Include header (the admin panel has a different layout)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Metro Rail Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/admin_sidebar.php'; ?>
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
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-printer me-1"></i> Print
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-download me-1"></i> Export
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
                            <i class="bi bi-calendar3 me-1"></i> Today
                        </button>
                    </div>
                </div>

                <!-- Dashboard Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-primary dashboard-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Total Users</h6>
                                        <h2 class="my-2"><?= $totalUsers ?></h2>
                                        <p class="card-text mb-0"><small>Registered users</small></p>
                                    </div>
                                    <div class="fs-1 text-white-50">
                                        <i class="bi bi-people"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-success dashboard-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Today's Bookings</h6>
                                        <h2 class="my-2"><?= $todayBookings ?></h2>
                                        <p class="card-text mb-0"><small>New bookings today</small></p>
                                    </div>
                                    <div class="fs-1 text-white-50">
                                        <i class="bi bi-calendar-check"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-info dashboard-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Total Bookings</h6>
                                        <h2 class="my-2"><?= $totalBookings ?></h2>
                                        <p class="card-text mb-0"><small>All time bookings</small></p>
                                    </div>
                                    <div class="fs-1 text-white-50">
                                        <i class="bi bi-ticket-perforated"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-warning dashboard-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Pending Bookings</h6>
                                        <h2 class="my-2"><?= $pendingBookings ?></h2>
                                        <p class="card-text mb-0"><small>Awaiting confirmation</small></p>
                                    </div>
                                    <div class="fs-1 text-white-50">
                                        <i class="bi bi-hourglass-split"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Second row of cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-4">
                        <div class="card border-primary dashboard-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-primary mb-0">Staff Members</h6>
                                        <h2 class="my-2"><?= $totalStaff ?></h2>
                                        <p class="card-text mb-0"><small>Active staff</small></p>
                                    </div>
                                    <div class="fs-1 text-primary opacity-25">
                                        <i class="bi bi-person-badge"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card border-success dashboard-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-success mb-0">Stations</h6>
                                        <h2 class="my-2"><?= $totalStations ?></h2>
                                        <p class="card-text mb-0"><small>Active stations</small></p>
                                    </div>
                                    <div class="fs-1 text-success opacity-25">
                                        <i class="bi bi-geo-alt"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card border-info dashboard-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-info mb-0">Trains</h6>
                                        <h2 class="my-2"><?= $totalTrains ?></h2>
                                        <p class="card-text mb-0"><small>Active trains</small></p>
                                    </div>
                                    <div class="fs-1 text-info opacity-25">
                                        <i class="bi bi-train-front"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card border-warning dashboard-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-warning mb-0">Routes</h6>
                                        <h2 class="my-2"><?= $totalRoutes ?></h2>
                                        <p class="card-text mb-0"><small>Active routes</small></p>
                                    </div>
                                    <div class="fs-1 text-warning opacity-25">
                                        <i class="bi bi-signpost-split"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity and Charts -->
                <div class="row">
                    <!-- Recent Bookings -->
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0"><i class="bi bi-list-check me-2"></i>Recent Bookings</h5>
                                <a href="<?= APP_URL ?>/admin/bookings.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Booking ID</th>
                                                <th>User</th>
                                                <th>From - To</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recentBookings)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No recent bookings found.</td>
                                            </tr>
                                            <?php else: ?>
                                                <?php foreach ($recentBookings as $booking): ?>
                                                <tr>
                                                    <td><?= $booking['id'] ?></td>
                                                    <td><?= htmlspecialchars($booking['user_name']) ?></td>
                                                    <td><?= htmlspecialchars($booking['from_station']) ?> - <?= htmlspecialchars($booking['to_station']) ?></td>
                                                    <td><?= date('d M Y', strtotime($booking['booking_date'])) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= 
                                                            $booking['booking_status'] === 'confirmed' ? 'success' : 
                                                            ($booking['booking_status'] === 'pending' ? 'warning' : 
                                                            ($booking['booking_status'] === 'completed' ? 'info' : 'danger')) 
                                                        ?>">
                                                            <?= ucfirst($booking['booking_status']) ?>
                                                        </span>
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

                    <!-- Stats and New Users -->
                    <div class="col-md-4 mb-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="bi bi-graph-up me-2"></i>Booking Statistics</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="bookingsChart" width="100%" height="200"></canvas>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0"><i class="bi bi-people me-2"></i>New Users</h5>
                                <a href="<?= APP_URL ?>/admin/users.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php if (empty($recentUsers)): ?>
                                        <li class="list-group-item text-center">No recent users found.</li>
                                    <?php else: ?>
                                        <?php foreach ($recentUsers as $user): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0"><?= htmlspecialchars($user['name']) ?></h6>
                                                    <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                                </div>
                                                <span class="badge bg-primary rounded-pill"><?= date('d M', strtotime($user['created_at'])) ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maintenance Schedules -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0"><i class="bi bi-tools me-2"></i>Active Maintenance Schedules</h5>
                                <a href="<?= APP_URL ?>/admin/maintenance.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($maintenanceSchedules)): ?>
                                    <p class="text-center">No active maintenance schedules.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Description</th>
                                                    <th>Start Time</th>
                                                    <th>End Time</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($maintenanceSchedules as $schedule): ?>
                                                    <tr>
                                                        <td><?= $schedule['id'] ?></td>
                                                        <td><?= htmlspecialchars($schedule['description']) ?></td>
                                                        <td><?= date('d M Y, h:i A', strtotime($schedule['start_datetime'])) ?></td>
                                                        <td><?= date('d M Y, h:i A', strtotime($schedule['end_datetime'])) ?></td>
                                                        <td>
                                                            <span class="badge bg-<?= $schedule['status'] === 'in_progress' ? 'info' : 'warning' ?>">
                                                                <?= $schedule['status'] === 'in_progress' ? 'In Progress' : 'Scheduled' ?>
                                                            </span>
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
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Bookings chart
        const ctx = document.getElementById('bookingsChart').getContext('2d');
        const bookingsChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Confirmed', 'Pending', 'Cancelled', 'Completed'],
                datasets: [{
                    data: [
                        <?= fetchRow("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'confirmed'")['count'] ?>,
                        <?= fetchRow("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'pending'")['count'] ?>,
                        <?= fetchRow("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'cancelled'")['count'] ?>,
                        <?= fetchRow("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'completed'")['count'] ?>
                    ],
                    backgroundColor: [
                        '#2ecc71',
                        '#f39c12',
                        '#e74c3c',
                        '#3498db'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    </script>
</body>
</html>
