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

// Check if user is regular user
if (isAdmin()) {
    redirect(APP_URL . '/admin');
} elseif (isStaff()) {
    redirect(APP_URL . '/staff');
}

// Get user information
$userId = $_SESSION['user_id'];
$user = fetchRow("SELECT * FROM users WHERE id = ?", [$userId]);

// Initialize variables
$filter = isset($_GET['filter']) ? sanitizeInput($_GET['filter']) : 'all';

// Get bookings based on filter
$bookingsQuery = "
    SELECT b.*, 
           fs.name as from_station, 
           ts.name as to_station,
           s.departure_time,
           r.name as route_name,
           t.name as train_name,
           t.train_number
    FROM bookings b
    JOIN stations fs ON b.from_station_id = fs.id
    JOIN stations ts ON b.to_station_id = ts.id
    JOIN schedules s ON b.schedule_id = s.id
    JOIN routes r ON s.route_id = r.id
    JOIN trains t ON s.train_id = t.id
    WHERE b.user_id = ?
";

switch ($filter) {
    case 'upcoming':
        $bookingsQuery .= " AND b.journey_date >= CURDATE() AND b.booking_status NOT IN ('completed', 'cancelled')";
        break;
    case 'past':
        $bookingsQuery .= " AND (b.journey_date < CURDATE() OR b.booking_status = 'completed')";
        break;
    case 'cancelled':
        $bookingsQuery .= " AND b.booking_status = 'cancelled'";
        break;
    default:
        // No additional filter - show all
        break;
}

$bookingsQuery .= " ORDER BY b.journey_date DESC, s.departure_time ASC";
$bookings = fetchRows($bookingsQuery, [$userId]);

// Handle booking cancellation
if (isset($_POST['cancel_booking']) && isset($_POST['booking_id'])) {
    $bookingId = (int)$_POST['booking_id'];
      // Verify booking belongs to user
    $booking = fetchRow("SELECT * FROM bookings WHERE id = ? AND user_id = ?", [$bookingId, $userId]);
    
    file_put_contents('C:/xampp/htdocs/metro-rail/debug_log.txt', 
        date('Y-m-d H:i:s') . " - Attempting to cancel booking ID: " . $bookingId . "\n", 
        FILE_APPEND
    );
    
    if ($booking) {
        file_put_contents('C:/xampp/htdocs/metro-rail/debug_log.txt', 
            date('Y-m-d H:i:s') . " - Booking found for user: " . $userId . "\n", 
            FILE_APPEND
        );
        
        // Check if booking can be cancelled (not in the past and not already cancelled)
        if (strtotime($booking['journey_date']) > strtotime('today') && $booking['booking_status'] !== 'cancelled') {
            file_put_contents('C:/xampp/htdocs/metro-rail/debug_log.txt', 
                date('Y-m-d H:i:s') . " - Booking date valid for cancellation. Journey date: " . $booking['journey_date'] . "\n", 
                FILE_APPEND
            );
            
            $updateData = [
                'booking_status' => 'cancelled',
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            file_put_contents('C:/xampp/htdocs/metro-rail/debug_log.txt', 
                date('Y-m-d H:i:s') . " - Attempting update with data: " . json_encode($updateData) . "\n", 
                FILE_APPEND
            );
              $updated = update('bookings', $updateData, "id = ?", [$bookingId]);
            
            file_put_contents('C:/xampp/htdocs/metro-rail/debug_log.txt', 
                date('Y-m-d H:i:s') . " - Update result: " . ($updated ? "Success" : "Failed") . "\n", 
                FILE_APPEND
            );
            
            if ($updated) {                
                // Update ticket status
                $ticketData = [
                    'status' => 'cancelled',
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $ticketUpdated = update('tickets', $ticketData, "booking_id = ?", [$bookingId]);
                
                file_put_contents('C:/xampp/htdocs/metro-rail/debug_log.txt', 
                    date('Y-m-d H:i:s') . " - Ticket update result: " . ($ticketUpdated ? "Success" : "Failed") . "\n", 
                    FILE_APPEND
                );
                
                // Log activity
                logActivity('booking_cancelled', 'Cancelled booking #' . $booking['booking_number'], $userId);
                
                // Set success message in session and redirect to refresh page
                setFlashMessage('success', 'Booking cancelled successfully.');
                redirect($_SERVER['PHP_SELF'] . '?filter=' . $filter);
            } else {
                setFlashMessage('error', 'Failed to cancel booking. Please try again.');
                redirect($_SERVER['PHP_SELF'] . '?filter=' . $filter);
            }
        } else {
            setFlashMessage('error', 'This booking cannot be cancelled. It may be in the past or already cancelled.');
            redirect($_SERVER['PHP_SELF'] . '?filter=' . $filter);
        }
    } else {
        setFlashMessage('error', 'Invalid booking or you do not have permission to cancel it.');
        redirect($_SERVER['PHP_SELF'] . '?filter=' . $filter);
    }
}

// Set page title
$pageTitle = 'Booking History';

// Include header
require_once '../includes/header.php';

// Add booking history specific CSS
echo '<link href="' . APP_URL . '/css/booking-history.css" rel="stylesheet">';
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
                <a href="index.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
                <a href="booking.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-ticket-alt me-2"></i>Book Ticket
                </a>
                <a href="history.php" class="list-group-item list-group-item-action active">
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
            <!-- Page Title -->
            <div class="card mb-4">
                <div class="card-body bg-primary text-white">
                    <h4><i class="fas fa-history me-2"></i>Booking History</h4>
                    <p class="mb-0">View your past and upcoming bookings.</p>
                </div>
            </div>
            
            <!-- Flash Messages -->
            <?php displayFlashMessages(); ?>
            
            <!-- Booking Filter -->
            <div class="card mb-4">
                <div class="card-body p-3">
                    <div class="btn-group w-100" role="group">
                        <a href="?filter=all" class="btn btn<?php echo $filter === 'all' ? '-primary' : '-outline-primary'; ?>">
                            <i class="fas fa-list me-2"></i>All Bookings
                        </a>
                        <a href="?filter=upcoming" class="btn btn<?php echo $filter === 'upcoming' ? '-primary' : '-outline-primary'; ?>">
                            <i class="fas fa-calendar-alt me-2"></i>Upcoming
                        </a>
                        <a href="?filter=past" class="btn btn<?php echo $filter === 'past' ? '-primary' : '-outline-primary'; ?>">
                            <i class="fas fa-calendar-check me-2"></i>Past
                        </a>
                        <a href="?filter=cancelled" class="btn btn<?php echo $filter === 'cancelled' ? '-primary' : '-outline-primary'; ?>">
                            <i class="fas fa-ban me-2"></i>Cancelled
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Bookings List -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-ticket-alt me-2"></i>
                        <?php
                        switch ($filter) {
                            case 'upcoming':
                                echo 'Upcoming Bookings';
                                break;
                            case 'past':
                                echo 'Past Bookings';
                                break;
                            case 'cancelled':
                                echo 'Cancelled Bookings';
                                break;
                            default:
                                echo 'All Bookings';
                                break;
                        }
                        ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($bookings)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No bookings found.
                            <?php if ($filter !== 'all'): ?>
                                <a href="?filter=all" class="alert-link">View all bookings</a>.
                            <?php else: ?>
                                <a href="booking.php" class="alert-link">Book a ticket</a> now.
                            <?php endif; ?>
                        </div>                    <?php else: ?>
                        <div class="table-responsive booking-history-table">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Booking #</th>
                                        <th>Journey</th>
                                        <th>Date & Time</th>
                                        <th>Train</th>
                                        <th>Passengers</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th class="actions-column">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): ?>
                                        <?php
                                        $journeyDate = date('M d, Y', strtotime($booking['journey_date']));
                                        $departureTime = date('h:i A', strtotime($booking['departure_time']));
                                        $isUpcoming = strtotime($booking['journey_date']) >= strtotime('today');
                                        $canCancel = $isUpcoming && $booking['booking_status'] !== 'cancelled';
                                        $statusClass = '';
                                        
                                        switch ($booking['booking_status']) {
                                            case 'confirmed':
                                                $statusClass = 'success';
                                                break;
                                            case 'cancelled':
                                                $statusClass = 'danger';
                                                break;
                                            case 'completed':
                                                $statusClass = 'info';
                                                break;
                                            default:
                                                $statusClass = 'warning';
                                                break;
                                        }
                                        ?>
                                        <tr>
                                            <td><strong><?php echo $booking['booking_number']; ?></strong></td>
                                            <td>
                                                <span class="d-block"><?php echo $booking['from_station']; ?></span>
                                                <i class="fas fa-arrow-right text-primary"></i>
                                                <span class="d-block"><?php echo $booking['to_station']; ?></span>
                                            </td>
                                            <td>
                                                <span class="d-block"><?php echo $journeyDate; ?></span>
                                                <small class="text-muted"><?php echo $departureTime; ?></small>
                                            </td>
                                            <td>
                                                <span class="d-block"><?php echo $booking['train_name']; ?></span>
                                                <small class="text-muted"><?php echo $booking['train_number']; ?></small>
                                            </td>
                                            <td class="text-center"><?php echo $booking['passengers']; ?></td>
                                            <td><?php echo formatCurrency($booking['amount']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $statusClass; ?>">
                                                    <?php echo ucfirst($booking['booking_status']); ?>
                                                </span>
                                            </td>                                            <td class="text-nowrap">
                                                <div class="btn-group btn-group-sm action-buttons">
                                                    <a href="view-booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-outline-primary" title="View Booking">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <?php if ($canCancel): ?>
                                                        <button type="button" class="btn btn-outline-danger" title="Cancel Booking" 
                                                                data-bs-toggle="modal" data-bs-target="#cancelBookingModal<?php echo $booking['id']; ?>">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                        
                                                        <!-- Cancel Booking Modal -->
                                                        <div class="modal fade" id="cancelBookingModal<?php echo $booking['id']; ?>" tabindex="-1" aria-hidden="true">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header bg-danger text-white">
                                                                        <h5 class="modal-title">
                                                                            <i class="fas fa-exclamation-triangle me-2"></i>Cancel Booking
                                                                        </h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <p>Are you sure you want to cancel your booking <strong><?php echo $booking['booking_number']; ?></strong>?</p>
                                                                        <p><strong>Journey:</strong> <?php echo $booking['from_station']; ?> to <?php echo $booking['to_station']; ?></p>
                                                                        <p><strong>Date:</strong> <?php echo $journeyDate; ?> at <?php echo $departureTime; ?></p>
                                                                        <div class="alert alert-warning">
                                                                            <i class="fas fa-info-circle me-2"></i>This action cannot be undone.
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                            <i class="fas fa-times me-2"></i>No, Keep My Booking
                                                                        </button>
                                                                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>?filter=<?php echo $filter; ?>" method="POST">
                                                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                                            <button type="submit" name="cancel_booking" class="btn btn-danger">
                                                                                <i class="fas fa-check me-2"></i>Yes, Cancel Booking
                                                                            </button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
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
            
            <!-- Booking Tips -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Important Information</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-0">
                        <h6 class="mb-3">Booking Policies:</h6>
                        <ul class="mb-0">
                            <li>Bookings can be cancelled up to 1 hour before departure.</li>
                            <li>Keep your ticket ready for inspection during the journey.</li>
                            <li>Arrive at the station at least 15 minutes before departure.</li>
                            <li>For any assistance, contact our customer support.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>
