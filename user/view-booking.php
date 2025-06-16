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

// Handle booking cancellation
if (isset($_POST['cancel_booking']) && isset($_POST['booking_id'])) {
    $bookingId = (int)$_POST['booking_id'];
    
    // Verify booking belongs to user
    $bookingToCancel = fetchRow("SELECT * FROM bookings WHERE id = ? AND user_id = ?", [$bookingId, $userId]);
    
    if ($bookingToCancel) {        // Check if booking can be cancelled (not in the past and not already cancelled)
        if (strtotime($bookingToCancel['journey_date']) >= strtotime('today') && $bookingToCancel['booking_status'] !== 'cancelled') {
            $updateData = [
                'booking_status' => 'cancelled',
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $updated = update('bookings', $updateData, "id = ?", [$bookingId]);
            
            if ($updated) {
                // Update ticket status
                $ticketData = [
                    'status' => 'cancelled',
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $ticketUpdated = update('tickets', $ticketData, "booking_id = ?", [$bookingId]);
                
                // Log activity
                logActivity('booking_cancelled', 'Cancelled booking #' . $bookingToCancel['booking_number'], $userId);
                
                // Set success message and redirect
                setFlashMessage('success', 'Booking cancelled successfully.');
                redirect(APP_URL . '/user/history.php');
            } else {
                setFlashMessage('error', 'Failed to cancel booking. Please try again.');
            }
        } else {
            setFlashMessage('error', 'This booking cannot be cancelled. It may be in the past or already cancelled.');
        }
    } else {
        setFlashMessage('error', 'Invalid booking or you do not have permission to cancel it.');
    }
}

// Check if booking ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'Invalid booking ID.');
    redirect(APP_URL . '/user/history.php');
}

$bookingId = (int)$_GET['id'];

// Get booking details
$booking = fetchRow("
    SELECT b.*, 
           fs.name as from_station, 
           fs.code as from_station_code,
           ts.name as to_station,
           ts.code as to_station_code,
           s.departure_time,
           r.name as route_name,
           r.code as route_code,
           t.name as train_name,
           t.train_number
    FROM bookings b
    JOIN stations fs ON b.from_station_id = fs.id
    JOIN stations ts ON b.to_station_id = ts.id
    JOIN schedules s ON b.schedule_id = s.id
    JOIN routes r ON s.route_id = r.id
    JOIN trains t ON s.train_id = t.id
    WHERE b.id = ? AND b.user_id = ?
", [$bookingId, $userId]);

// If booking not found or doesn't belong to user
if (!$booking) {
    setFlashMessage('error', 'Booking not found or you do not have permission to view it.');
    redirect(APP_URL . '/user/history.php');
}

// Get tickets for this booking
$tickets = fetchRows("
    SELECT *
    FROM tickets
    WHERE booking_id = ?
    ORDER BY id ASC
", [$bookingId]);

// Set page title
$pageTitle = 'View Booking';

// Include header
require_once '../includes/header.php';

// Format dates and times
$journeyDate = date('l, F j, Y', strtotime($booking['journey_date']));
$departureTime = date('h:i A', strtotime($booking['departure_time']));
$bookingDate = date('M d, Y h:i A', strtotime($booking['booking_date']));

// Calculate status
$isUpcoming = strtotime($booking['journey_date']) >= strtotime('today');
$canCancel = $isUpcoming && ($booking['booking_status'] !== 'cancelled');
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

<div class="container-fluid">
    <div class="row">        <!-- User Sidebar -->
        <div class="col-md-3">
            <div class="card modern-card mb-4">
                <div class="card-header">
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
            <!-- Page Title -->
            <div class="card mb-4">
                <div class="card-body bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4><i class="fas fa-ticket-alt me-2"></i>Booking Details</h4>
                            <p class="mb-0">Booking #<?php echo $booking['booking_number']; ?></p>
                        </div>
                        <div>
                            <a href="history.php" class="btn btn-light">
                                <i class="fas fa-arrow-left me-2"></i>Back to History
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Flash Messages -->
            <?php displayFlashMessages(); ?>
            
            <!-- Booking Status -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h4>
                                <span class="badge bg-<?php echo $statusClass; ?> me-2">
                                    <?php echo ucfirst($booking['booking_status']); ?>
                                </span>
                                <?php echo $booking['from_station']; ?> to <?php echo $booking['to_station']; ?>
                            </h4>
                            <p class="text-muted mb-0">
                                <?php echo $journeyDate; ?> at <?php echo $departureTime; ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end">                            <div class="booking-actions">
                                <?php if ($canCancel): ?>
                                    <button type="button" class="btn btn-outline-danger btn-modern me-2" data-bs-toggle="modal" data-bs-target="#cancelBookingModal">
                                        <i class="fas fa-times me-2"></i>Cancel Booking
                                    </button>
                                <?php else: ?>
                                    <div class="alert alert-warning py-1 px-2 mb-2">
                                        <small><i class="fas fa-info-circle me-1"></i> This booking cannot be modified.</small>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($booking['booking_status'] === 'confirmed'): ?>
                                    <a href="javascript:window.print();" class="btn btn-primary btn-modern">
                                        <i class="fas fa-print me-2"></i>Print Tickets
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Booking Info -->
            <div class="row">
                <div class="col-md-7">                    <!-- Journey Details -->
                    <div class="card modern-card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-route me-2"></i>Journey Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="journey-details">
                                <div class="row mb-4">
                                    <div class="col-md-5">
                                        <h6>From</h6>
                                        <div class="station-info">
                                            <h5><?php echo $booking['from_station']; ?></h5>
                                            <div class="badge bg-primary mb-2"><?php echo $booking['from_station_code']; ?></div>
                                            <p class="mb-0"><?php echo $departureTime; ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-2 d-flex justify-content-center align-items-center">
                                        <div class="journey-arrow">
                                            <i class="fas fa-long-arrow-alt-right fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-5">
                                        <h6>To</h6>
                                        <div class="station-info">
                                            <h5><?php echo $booking['to_station']; ?></h5>
                                            <div class="badge bg-primary mb-2"><?php echo $booking['to_station_code']; ?></div>
                                            <p class="mb-0">Arrival time may vary</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="divider mb-4"></div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h6 class="mb-1">Date</h6>
                                        <p><i class="far fa-calendar-alt text-primary me-2"></i><?php echo $journeyDate; ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="mb-1">Route</h6>
                                        <p><i class="fas fa-map-signs text-primary me-2"></i><?php echo $booking['route_name']; ?> (<?php echo $booking['route_code']; ?>)</p>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h6 class="mb-1">Train</h6>
                                        <p><i class="fas fa-train text-primary me-2"></i><?php echo $booking['train_name']; ?> (<?php echo $booking['train_number']; ?>)</p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="mb-1">Passengers</h6>
                                        <p><i class="fas fa-users text-primary me-2"></i><?php echo $booking['passengers']; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tickets -->                    <div class="card modern-card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-ticket-alt me-2"></i>Tickets (<?php echo count($tickets); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($tickets)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>No tickets found for this booking.
                                </div>
                            <?php else: ?>
                                <div class="tickets-container">
                                    <?php foreach ($tickets as $index => $ticket): ?>
                                        <div class="ticket-card mb-3">
                                            <div class="ticket-header d-flex justify-content-between align-items-center p-3 bg-light rounded-top">
                                                <div class="ticket-number">
                                                    <i class="fas fa-ticket-alt me-2 text-primary"></i>
                                                    <strong>Ticket #<?php echo $ticket['ticket_number']; ?></strong>
                                                </div>
                                                <div class="ticket-status">
                                                    <span class="badge bg-<?php echo $ticket['status'] === 'active' ? 'success' : ($ticket['status'] === 'used' ? 'info' : 'danger'); ?> p-2">
                                                        <?php echo ucfirst($ticket['status']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="ticket-body p-3 border border-top-0 rounded-bottom">
                                                <div class="row align-items-center">
                                                    <div class="col-md-7">
                                                        <div class="ticket-info">
                                                            <div class="passenger-info mb-3">
                                                                <label class="text-muted small mb-1">Passenger:</label>
                                                                <div class="d-flex align-items-center">
                                                                    <div class="passenger-icon me-2">
                                                                        <i class="fas fa-user text-primary"></i>
                                                                    </div>
                                                                    <div class="passenger-name">
                                                                        <strong><?php echo $ticket['passenger_name']; ?></strong>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="seat-info mb-3">
                                                                <label class="text-muted small mb-1">Seat Assignment:</label>
                                                                <div class="d-flex align-items-center">
                                                                    <div class="seat-icon me-2">
                                                                        <i class="fas fa-chair text-primary"></i>
                                                                    </div>
                                                                    <div class="seat-number">
                                                                        <strong><?php echo $ticket['seat_number']; ?></strong>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="validation-info">
                                                                <label class="text-muted small mb-1">Validation Code:</label>
                                                                <div class="validation-code">
                                                                    <code><?php echo substr($ticket['barcode'], 0, 8) . '...'; ?></code>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <?php if ($ticket['status'] === 'active' && $booking['booking_status'] === 'confirmed'): ?>
                                                    <div class="col-md-5 text-center">
                                                        <div class="ticket-qr-container p-2 bg-white border rounded mb-2 mx-auto" style="width: 150px;">
                                                            <!-- In a real app, this would be an actual QR code -->
                                                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo $ticket['ticket_number']; ?>" alt="Ticket QR Code" class="img-fluid">
                                                        </div>
                                                        <div class="qr-instructions">
                                                            <p class="small text-muted mb-0">Scan this QR code at the station gate</p>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-5">                <!-- Payment Information -->
                    <div class="card modern-card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Payment Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="payment-info-card p-3 mb-3 rounded bg-light">
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Base Fare:</span>
                                    <span><?php echo formatCurrency($booking['amount'] / $booking['passengers']); ?> × <?php echo $booking['passengers']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Service Fee:</span>
                                    <span><?php echo formatCurrency(0); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Tax:</span>
                                    <span><?php echo formatCurrency(0); ?></span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong>Total Amount:</strong>
                                    <div class="total-amount-badge p-2 px-3 rounded bg-primary text-white">
                                        <strong><?php echo formatCurrency($booking['amount']); ?></strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="payment-status-card p-3 rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Payment Status</h6>
                                        <p class="mb-0 text-muted small">Transaction ID: <?php echo $booking['booking_number']; ?></p>
                                    </div>
                                    <span class="badge bg-<?php echo $booking['payment_status'] === 'paid' || $booking['payment_status'] === 'completed' ? 'success' : 'warning'; ?> p-2">
                                        <?php echo ucfirst($booking['payment_status']); ?>
                                    </span>
                                </div>
                                <div class="mt-3 text-center">
                                    <i class="fas fa-check-circle fa-2x <?php echo $booking['payment_status'] === 'paid' || $booking['payment_status'] === 'completed' ? 'text-success' : 'text-warning'; ?>"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                      <!-- Booking Information -->
                    <div class="card modern-card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Booking Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6 class="mb-1">Booking Reference</h6>
                                <p class="text-primary fw-bold"><?php echo $booking['booking_number']; ?></p>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="mb-1">Booked On</h6>
                                <p><?php echo $bookingDate; ?></p>
                            </div>
                            
                            <?php if ($booking['updated_at']): ?>
                                <div class="mb-3">
                                    <h6 class="mb-1">Last Updated</h6>
                                    <p><?php echo date('M d, Y h:i A', strtotime($booking['updated_at'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                      <!-- Important Information -->
                    <div class="card modern-card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i>Important Information</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i> Please arrive at the station at least 15 minutes before departure.
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i> Keep your ticket or booking reference handy for verification.
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i> Follow all safety guidelines and instructions from metro staff.
                                </li>
                                <li>
                                    <i class="fas fa-check-circle text-success me-2"></i> For assistance, contact our support at <?php echo CONTACT_EMAIL; ?>.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>    </div>
</div>

<!-- Cancel Booking Modal -->
<div class="modal fade" id="cancelBookingModal" tabindex="-1" aria-labelledby="cancelBookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="cancelBookingModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Cancel Booking
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="cancel-confirmation text-center mb-4">
                    <div class="warning-icon mb-3">
                        <i class="fas fa-exclamation-circle fa-4x text-danger"></i>
                    </div>
                    <h5>Are you sure you want to cancel this booking?</h5>
                    <p class="text-muted">This action cannot be undone. All tickets associated with this booking will be cancelled.</p>
                </div>
                
                <div class="booking-summary p-3 mb-3 bg-light rounded">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Booking Number:</span>
                        <strong><?php echo $booking['booking_number']; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Journey Date:</span>
                        <strong><?php echo date('d M Y', strtotime($booking['journey_date'])); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Total Amount:</span>
                        <strong><?php echo formatCurrency($booking['amount']); ?></strong>
                    </div>
                </div>
                
                <form action="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $bookingId; ?>" method="POST" id="cancelBookingForm">
                    <input type="hidden" name="booking_id" value="<?php echo $bookingId; ?>">
                    <input type="hidden" name="cancel_booking" value="1">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>No, Keep Booking
                </button>
                <button type="submit" form="cancelBookingForm" class="btn btn-danger">
                    <i class="fas fa-check me-2"></i>Yes, Cancel Booking
                </button>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>
