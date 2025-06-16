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

// Check if user is not a regular user (e.g., admin or staff)
if (!hasRole('user')) {
    // Redirect to appropriate dashboard
    if (isAdmin()) {
        redirect(APP_URL . '/admin');
    } elseif (isStaff()) {
        redirect(APP_URL . '/staff');
    }
}

// Get user information
$userId = $_SESSION['user_id'];
$user = fetchRow("SELECT * FROM users WHERE id = ?", [$userId]);

// Get user's bookings (recent and upcoming)
$upcomingBookings = fetchRows("
    SELECT b.*, 
           fs.name as from_station, 
           ts.name as to_station,
           s.departure_time,
           r.name as route_name
    FROM bookings b
    JOIN stations fs ON b.from_station_id = fs.id
    JOIN stations ts ON b.to_station_id = ts.id
    JOIN schedules s ON b.schedule_id = s.id
    JOIN routes r ON s.route_id = r.id
    WHERE b.user_id = ? 
    AND b.journey_date >= CURDATE()
    AND b.booking_status != 'cancelled'
    ORDER BY b.journey_date ASC, s.departure_time ASC
    LIMIT 5
", [$userId]);

$recentBookings = fetchRows("
    SELECT b.*, 
           fs.name as from_station, 
           ts.name as to_station,
           s.departure_time,
           r.name as route_name
    FROM bookings b
    JOIN stations fs ON b.from_station_id = fs.id
    JOIN stations ts ON b.to_station_id = ts.id
    JOIN schedules s ON b.schedule_id = s.id
    JOIN routes r ON s.route_id = r.id
    WHERE b.user_id = ? 
    AND (b.journey_date < CURDATE() OR b.booking_status = 'cancelled')
    ORDER BY b.booking_date DESC
    LIMIT 5
", [$userId]);

// Get active announcements
$announcements = fetchRows("
    SELECT * FROM announcements
    WHERE status = 'active'
    AND start_date <= CURDATE()
    AND end_date >= CURDATE()
    ORDER BY created_at DESC
    LIMIT 3
");

// Set page title
$pageTitle = 'User Dashboard';

// Include header
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- User Sidebar -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>User Profile</h5>
                </div>
                <div class="card-body text-center">
                    <img src="https://via.placeholder.com/150" alt="Profile Picture" class="rounded-circle img-thumbnail mb-3">
                    <h5><?php echo $user['name']; ?></h5>
                    <p class="text-muted"><?php echo $user['email']; ?></p>
                    <hr>
                    <div class="d-grid gap-2">
                        <a href="profile.php" class="btn btn-outline-primary">
                            <i class="fas fa-edit me-2"></i>Edit Profile
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="list-group mb-4">
                <a href="index.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
                <a href="booking.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-ticket-alt me-2"></i>Book Ticket
                </a>
                <a href="history.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-history me-2"></i>Booking History
                </a>
                <a href="profile.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-user-edit me-2"></i>My Profile
                </a>
                <a href="password.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-key me-2"></i>Change Password
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <!-- Welcome Section -->
            <div class="card mb-4">
                <div class="card-body bg-primary text-white">
                    <h4><i class="fas fa-home me-2"></i>Welcome, <?php echo $user['name']; ?>!</h4>
                    <p>Here's your Metro Rail dashboard. You can book tickets, view your booking history, and manage your profile.</p>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-ticket-alt fa-3x text-primary mb-3"></i>
                            <h5>Book a Ticket</h5>
                            <p>Book a new ticket for your journey</p>
                            <a href="booking.php" class="btn btn-primary">Book Now</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-alt fa-3x text-primary mb-3"></i>
                            <h5>View Schedule</h5>
                            <p>Check train schedules and plan your journey</p>
                            <a href="../schedule.php" class="btn btn-primary">View Schedules</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-map-marked-alt fa-3x text-primary mb-3"></i>
                            <h5>Stations Map</h5>
                            <p>View all stations on the metro network</p>
                            <a href="../stations.php" class="btn btn-primary">View Map</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Upcoming Bookings -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Upcoming Bookings</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($upcomingBookings)): ?>
                        <div class="text-center p-4">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <p class="lead">You don't have any upcoming bookings.</p>
                            <a href="booking.php" class="btn btn-primary">Book a Ticket</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Booking #</th>
                                        <th>Journey Date</th>
                                        <th>Route</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Departure</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcomingBookings as $booking): ?>
                                        <tr>
                                            <td><?php echo $booking['booking_number']; ?></td>
                                            <td><?php echo formatDate($booking['journey_date'], 'M d, Y'); ?></td>
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
                                                    default:
                                                        $statusClass = 'bg-secondary';
                                                }
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>">
                                                    <?php echo ucfirst($booking['booking_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view-booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($booking['booking_status'] !== 'cancelled'): ?>
                                                    <a href="cancel-booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to cancel this booking?');">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (count($upcomingBookings) >= 5): ?>
                            <div class="text-center mt-3">
                                <a href="history.php" class="btn btn-outline-primary">View All Bookings</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Bookings -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Bookings</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recentBookings)): ?>
                        <div class="text-center p-4">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <p class="lead">You don't have any recent booking history.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Booking #</th>
                                        <th>Journey Date</th>
                                        <th>Route</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentBookings as $booking): ?>
                                        <tr>
                                            <td><?php echo $booking['booking_number']; ?></td>
                                            <td><?php echo formatDate($booking['journey_date'], 'M d, Y'); ?></td>
                                            <td><?php echo $booking['route_name']; ?></td>
                                            <td><?php echo $booking['from_station']; ?></td>
                                            <td><?php echo $booking['to_station']; ?></td>
                                            <td><?php echo formatCurrency($booking['amount']); ?></td>
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
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (count($recentBookings) >= 5): ?>
                            <div class="text-center mt-3">
                                <a href="history.php" class="btn btn-outline-primary">View Full History</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Announcements -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-bullhorn me-2"></i>Announcements</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($announcements)): ?>
                        <div class="text-center p-4">
                            <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                            <p class="lead">No current announcements.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $announcement['title']; ?></h5>
                                    <p class="card-text"><?php echo $announcement['content']; ?></p>
                                    <p class="text-muted small">Posted on <?php echo formatDate($announcement['created_at']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="text-center mt-3">
                            <a href="../announcements.php" class="btn btn-outline-primary">View All Announcements</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>
