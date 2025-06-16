<?php
// Start output buffering to prevent header issues
ob_start();

// Check if user is logged in
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isLoggedIn()) {
    // Redirect to login page with redirect parameter
    redirect(APP_URL . '/login.php?redirect=' . urlencode(APP_URL . '/user/booking.php'));
}

// Get user information
$userId = $_SESSION['user_id'];
$user = fetchRow("SELECT * FROM users WHERE id = ?", [$userId]);

// Get all active stations
$stations = fetchRows("SELECT * FROM stations WHERE status = 'active' ORDER BY name ASC");

// Get all active routes
$routes = fetchRows("SELECT * FROM routes WHERE status = 'active' ORDER BY name ASC");

// Initialize variables
$errors = [];
$success = '';
$formData = [
    'route_id' => '',
    'from_station_id' => '',
    'to_station_id' => '',
    'journey_date' => '',
    'passengers' => 1,
    'schedule_id' => ''
];

// Get pre-filled form data from query string if available
if (isset($_GET['from']) && isset($_GET['to'])) {
    $formData['from_station_id'] = sanitizeInput($_GET['from']);
    $formData['to_station_id'] = sanitizeInput($_GET['to']);
}

if (isset($_GET['date'])) {
    $formData['journey_date'] = sanitizeInput($_GET['date']);
}

if (isset($_GET['passengers'])) {
    $formData['passengers'] = max(1, min(5, (int)$_GET['passengers']));
}

// Check if form is submitted for schedule selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] === 'find_schedules') {
    // Get form data
    $formData = [
        'route_id' => isset($_POST['route_id']) ? sanitizeInput($_POST['route_id']) : '',
        'from_station_id' => isset($_POST['from_station_id']) ? sanitizeInput($_POST['from_station_id']) : '',
        'to_station_id' => isset($_POST['to_station_id']) ? sanitizeInput($_POST['to_station_id']) : '',
        'journey_date' => isset($_POST['journey_date']) ? sanitizeInput($_POST['journey_date']) : '',
        'passengers' => isset($_POST['passengers']) ? max(1, min(5, (int)$_POST['passengers'])) : 1
    ];
    
    // Validate form data
    if (empty($formData['from_station_id'])) {
        $errors[] = 'Please select a departure station';
    }
    
    if (empty($formData['to_station_id'])) {
        $errors[] = 'Please select an arrival station';
    } elseif ($formData['from_station_id'] === $formData['to_station_id']) {
        $errors[] = 'Departure and arrival stations cannot be the same';
    }
    
    if (empty($formData['journey_date'])) {
        $errors[] = 'Please select a journey date';
    } elseif (strtotime($formData['journey_date']) < strtotime(date('Y-m-d'))) {
        $errors[] = 'Journey date cannot be in the past';
    } elseif (strtotime($formData['journey_date']) > strtotime('+30 days')) {
        $errors[] = 'Journey date cannot be more than 30 days in the future';
    }
    
    // Find routes containing both stations
    if (empty($errors)) {                // Get schedules for the selected journey
        $dayOfWeek = date('N', strtotime($formData['journey_date']));
        $schedules = fetchRows("
            SELECT s.*,
                   r.name as route_name,
                   r.id as route_id,
                   t.name as train_name,
                   t.train_number,
                   rs_from.stop_order as from_order,
                   rs_to.stop_order as to_order
            FROM schedules s
            JOIN routes r ON s.route_id = r.id
            JOIN trains t ON s.train_id = t.id
            JOIN route_stations rs_from ON r.id = rs_from.route_id AND rs_from.station_id = ?
            JOIN route_stations rs_to ON r.id = rs_to.route_id AND rs_to.station_id = ?
            WHERE s.status = 'active'
            AND FIND_IN_SET(?, s.days) > 0
            AND rs_from.stop_order < rs_to.stop_order
            ORDER BY s.departure_time ASC
        ", [
            $formData['from_station_id'],
            $formData['to_station_id'],
            $dayOfWeek
        ]);
        
        // If no schedules found
        if (empty($schedules)) {
            $errors[] = 'No schedules found for the selected journey. Please try different stations or date.';
        }
    }
}

// Check if form is submitted for final booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] === 'confirm_booking') {
    // Get form data
    $formData = [
        'route_id' => isset($_POST['route_id']) ? sanitizeInput($_POST['route_id']) : '',
        'from_station_id' => isset($_POST['from_station_id']) ? sanitizeInput($_POST['from_station_id']) : '',
        'to_station_id' => isset($_POST['to_station_id']) ? sanitizeInput($_POST['to_station_id']) : '',
        'journey_date' => isset($_POST['journey_date']) ? sanitizeInput($_POST['journey_date']) : '',
        'passengers' => isset($_POST['passengers']) ? max(1, min(5, (int)$_POST['passengers'])) : 1,
        'schedule_id' => isset($_POST['schedule_id']) ? sanitizeInput($_POST['schedule_id']) : ''
    ];
    
    // Validate form data
    if (empty($formData['schedule_id'])) {
        $errors[] = 'Please select a schedule';
    }
    
    // If no errors, proceed with booking
    if (empty($errors)) {
        // Get fare
        $fare = fetchRow("
            SELECT amount
            FROM fares
            WHERE route_id = ?
            AND from_station_id = ?
            AND to_station_id = ?
        ", [
            $formData['route_id'],
            $formData['from_station_id'],
            $formData['to_station_id']
        ]);
        
        if (!$fare) {
            // If fare not found, calculate based on distance
            $fromStation = fetchRow("SELECT * FROM stations WHERE id = ?", [$formData['from_station_id']]);
            $toStation = fetchRow("SELECT * FROM stations WHERE id = ?", [$formData['to_station_id']]);
            
            $distance = calculateDistance(
                $fromStation['latitude'], 
                $fromStation['longitude'], 
                $toStation['latitude'], 
                $toStation['longitude']
            );
            
            $amount = calculateFare($distance);
        } else {
            $amount = $fare['amount'];
        }
        
        // Calculate total amount
        $totalAmount = $amount * $formData['passengers'];        // Payment selection form
        echo '<div class="booking-content">';
        echo '<div class="card modern-card">';
        echo '<div class="card-header">';
        echo '<h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Details</h5>';
        echo '</div>';
        echo '<div class="card-body">';
        echo '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';
        echo '<input type="hidden" name="step" value="process_payment">';
        echo '<input type="hidden" name="route_id" value="' . $formData['route_id'] . '">';
        echo '<input type="hidden" name="from_station_id" value="' . $formData['from_station_id'] . '">';
        echo '<input type="hidden" name="to_station_id" value="' . $formData['to_station_id'] . '">';
        echo '<input type="hidden" name="journey_date" value="' . $formData['journey_date'] . '">';
        echo '<input type="hidden" name="passengers" value="' . $formData['passengers'] . '">';
        echo '<input type="hidden" name="schedule_id" value="' . $formData['schedule_id'] . '">';
        echo '<input type="hidden" name="amount" value="' . $totalAmount . '">';
        
        echo '<div class="payment-summary mb-4">';
        echo '<div class="row justify-content-between align-items-center p-3 bg-light rounded">';
        echo '<div class="col-md-6">';
        echo '<h5 class="mb-1">Booking Summary</h5>';
        
        // Get station names
        $fromStation = fetchRow("SELECT name FROM stations WHERE id = ?", [$formData['from_station_id']]);
        $toStation = fetchRow("SELECT name FROM stations WHERE id = ?", [$formData['to_station_id']]);
        
        echo '<p class="text-muted mb-0">From: <strong>' . $fromStation['name'] . '</strong></p>';
        echo '<p class="text-muted mb-0">To: <strong>' . $toStation['name'] . '</strong></p>';
        echo '<p class="text-muted mb-0">Date: <strong>' . date('d M Y', strtotime($formData['journey_date'])) . '</strong></p>';
        echo '<p class="text-muted mb-0">Passengers: <strong>' . $formData['passengers'] . '</strong></p>';
        echo '</div>';
        echo '<div class="col-md-5 text-md-end">';
        echo '<div class="total-amount p-3 rounded bg-primary text-white text-center">';
        echo '<h6 class="mb-0">Total Amount</h6>';
        echo '<h3 class="mb-0">' . number_format($totalAmount, 2) . '</h3>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '<h6 class="mb-3">Select Payment Method</h6>';
        echo '<div class="row payment-options gx-3 mb-4">';
          // Phone payment option
        echo '<div class="col-md-6 mb-3">';
        echo '<label class="payment-container" for="phone_payment">';
        echo '<div class="payment-option">';
        echo '<input class="payment-radio" type="radio" name="payment_method" id="phone_payment" value="phone" required>';
        echo '<div class="payment-content">';
        echo '<div class="payment-icon"><i class="fas fa-mobile-alt"></i></div>';
        echo '<div class="payment-text">';
        echo '<strong>Mobile Payment</strong>';
        echo '<span>Pay using mobile wallet</span>';
        echo '</div>';
        echo '</div>';
        echo '<div class="check-indicator"></div>';
        echo '</div>';
        echo '</label>';
        echo '</div>';
        
        // Card payment option
        echo '<div class="col-md-6 mb-3">';
        echo '<label class="payment-container" for="card_payment">';
        echo '<div class="payment-option">';
        echo '<input class="payment-radio" type="radio" name="payment_method" id="card_payment" value="card" required>';
        echo '<div class="payment-content">';
        echo '<div class="payment-icon"><i class="fas fa-credit-card"></i></div>';
        echo '<div class="payment-text">';
        echo '<strong>Card Payment</strong>';
        echo '<span>Pay using debit/credit card</span>';
        echo '</div>';
        echo '</div>';
        echo '<div class="check-indicator"></div>';
        echo '</div>';
        echo '</label>';
        echo '</div>';
        echo '</div>';        echo '<div class="d-grid mt-4">';
        echo '<button type="submit" class="btn btn-primary btn-lg modern-button payment-submit-btn">';
        echo '<i class="fas fa-lock me-2"></i>Complete Payment';
        echo '</button>';
        echo '</div>';
        
        // Add JavaScript for better interactivity
        echo '<script>
            // When the page is loaded
            document.addEventListener("DOMContentLoaded", function() {
                // Get all payment radio inputs
                const paymentRadios = document.querySelectorAll(".payment-radio");
                
                // Add event listeners to each radio button
                paymentRadios.forEach(radio => {
                    radio.addEventListener("change", function() {
                        // Remove selected class from all payment options
                        document.querySelectorAll(".payment-option").forEach(option => {
                            option.classList.remove("selected");
                        });
                        
                        // Add selected class to the container of the checked radio
                        if (this.checked) {
                            this.closest(".payment-option").classList.add("selected");
                        }
                    });
                    
                    // Initialize - check if any radio is already selected
                    if (radio.checked) {
                        radio.closest(".payment-option").classList.add("selected");
                    }
                });
            });
        </script>';
        
        echo '</form>';
        echo '</div>'; // End of card-body
        echo '</div>'; // End of modern-card
        echo '</div>'; // End of booking-content
        return;
    }
}

// Process payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] === 'process_payment') {
    // Get form data
    $formData = [
        'route_id' => isset($_POST['route_id']) ? sanitizeInput($_POST['route_id']) : '',
        'from_station_id' => isset($_POST['from_station_id']) ? sanitizeInput($_POST['from_station_id']) : '',
        'to_station_id' => isset($_POST['to_station_id']) ? sanitizeInput($_POST['to_station_id']) : '',
        'journey_date' => isset($_POST['journey_date']) ? sanitizeInput($_POST['journey_date']) : '',
        'passengers' => isset($_POST['passengers']) ? max(1, min(5, (int)$_POST['passengers'])) : 1,
        'schedule_id' => isset($_POST['schedule_id']) ? sanitizeInput($_POST['schedule_id']) : '',
        'amount' => isset($_POST['amount']) ? sanitizeInput($_POST['amount']) : '',
        'payment_method' => isset($_POST['payment_method']) ? sanitizeInput($_POST['payment_method']) : ''
    ];

    // Validate form data
    if (empty($formData['payment_method'])) {
        $errors[] = 'Please select a payment method';
    }

    // If no errors, proceed with booking
    if (empty($errors)) {
        // Simulate payment processing
        $paymentStatus = 'success'; // Assume payment is successful for dummy integration

        // Generate booking number
        $bookingNumber = 'MR' . date('YmdHis') . rand(1000, 9999);
        
        // Insert booking
        $bookingData = [
            'booking_number' => $bookingNumber,
            'user_id' => $userId,
            'schedule_id' => $formData['schedule_id'],
            'from_station_id' => $formData['from_station_id'],
            'to_station_id' => $formData['to_station_id'],
            'journey_date' => $formData['journey_date'],
            'booking_date' => date('Y-m-d H:i:s'),
            'passengers' => $formData['passengers'],
            'amount' => $formData['amount'],
            'payment_status' => $paymentStatus === 'success' ? 'completed' : 'failed',
            'booking_status' => 'confirmed',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $bookingId = insert('bookings', $bookingData);
        
        if ($bookingId) {
            // For each passenger, generate a ticket
            for ($i = 0; $i < $formData['passengers']; $i++) {
                $ticketNumber = 'TK' . date('YmdHis') . rand(1000, 9999);
                $passengerName = $i === 0 ? $user['name'] : 'Guest ' . ($i + 1);
                $seatNumber = 'A' . rand(1, 50); // In a real system, this would be based on available seats
                $barcode = generateRandomString(20);
                
                $ticketData = [
                    'booking_id' => $bookingId,
                    'ticket_number' => $ticketNumber,
                    'passenger_name' => $passengerName,
                    'seat_number' => $seatNumber,
                    'barcode' => $barcode,
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                insert('tickets', $ticketData);
            }
            
            // Log activity
            logActivity('booking_created', 'Created a new booking #' . $bookingNumber, $userId);
            
            // Set success message
            $success = 'Booking created successfully! Your booking number is ' . $bookingNumber;
            
            // In a real system, redirect to payment page
            redirect(APP_URL . '/user/view-booking.php?id=' . $bookingId);
        } else {
            $errors[] = 'Failed to create booking. Please try again.';
        }
    }
}

// Set page title
$pageTitle = 'Book Ticket';

// Include header
require_once '../includes/header.php';
?>

<div class="container-fluid my-4">
    <div class="row">
        <!-- User Sidebar -->
        <div class="col-md-3">
            <div class="card modern-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>User Profile</h5>
                </div>
                <div class="card-body text-center">
                    <img src="https://via.placeholder.com/150" alt="Profile Picture" class="rounded-circle img-thumbnail mb-3 profile-image">
                    <h5 class="user-name"><?php echo $user['name']; ?></h5>
                    <p class="text-muted user-email"><?php echo $user['email']; ?></p>
                    <hr>
                    <div class="d-grid">
                        <a href="profile.php" class="btn btn-outline-primary">
                            <i class="fas fa-edit me-2"></i>Edit Profile
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="modern-list-group mb-4">
                <a href="index.php" class="modern-list-item">
                    <div class="item-icon"><i class="fas fa-tachometer-alt"></i></div>
                    <div class="item-text">Dashboard</div>
                    <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                </a>
                <a href="booking.php" class="modern-list-item active">
                    <div class="item-icon"><i class="fas fa-ticket-alt"></i></div>
                    <div class="item-text">Book Ticket</div>
                    <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                </a>
                <a href="history.php" class="modern-list-item">
                    <div class="item-icon"><i class="fas fa-history"></i></div>
                    <div class="item-text">Booking History</div>
                    <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                </a>
                <a href="profile.php" class="modern-list-item">
                    <div class="item-icon"><i class="fas fa-user-edit"></i></div>
                    <div class="item-text">My Profile</div>
                    <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                </a>
                <a href="password.php" class="modern-list-item">
                    <div class="item-icon"><i class="fas fa-key"></i></div>
                    <div class="item-text">Change Password</div>
                    <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <div class="booking-wrapper">
                <!-- Page Title -->
                <div class="booking-header">
                    <h2 class="display-6 fw-bold mb-2"><i class="fas fa-ticket-alt me-2 text-primary"></i>Book Your Metro Ticket</h2>
                    <p class="text-muted">Select your journey details and book your ticket in a few easy steps.</p>
                </div>
                
                <!-- Booking Progress -->
                <div class="booking-progress">
                    <div class="progress-step <?php echo !isset($_POST['step']) || $_POST['step'] === 'find_schedules' ? 'active' : (isset($_POST['step']) && ($_POST['step'] === 'select_schedule' || $_POST['step'] === 'confirm_booking') ? 'completed' : ''); ?>">
                        <div class="progress-step-number">1</div>
                        <div class="progress-step-label">Select Journey</div>
                    </div>
                    <div class="progress-step <?php echo isset($_POST['step']) && $_POST['step'] === 'select_schedule' ? 'active' : (isset($_POST['step']) && $_POST['step'] === 'confirm_booking' ? 'completed' : ''); ?>">
                        <div class="progress-step-number">2</div>
                        <div class="progress-step-label">Select Schedule</div>
                    </div>
                    <div class="progress-step <?php echo isset($_POST['step']) && $_POST['step'] === 'confirm_booking' ? 'active' : ''; ?>">
                        <div class="progress-step-number">3</div>
                        <div class="progress-step-label">Confirm & Pay</div>
                    </div>
                </div>
                
                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-modern">
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
                
                <!-- Step 1: Journey Selection Form -->
                <?php if (!isset($_POST['step']) || (isset($_POST['step']) && $_POST['step'] === 'find_schedules' && !empty($errors))): ?>
                    <div class="booking-content">
                        <div class="card modern-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-search me-2"></i>Find Your Journey</h5>
                            </div>
                            <div class="card-body">
                                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="journey-form">
                                    <input type="hidden" name="step" value="find_schedules">
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="form-floating mb-3">
                                                <select class="form-select modern-select" id="from_station_id" name="from_station_id" required>
                                                    <option value="" selected disabled>Select departure station</option>
                                                    <?php foreach ($stations as $station): ?>
                                                        <option value="<?php echo $station['id']; ?>" <?php echo $formData['from_station_id'] == $station['id'] ? 'selected' : ''; ?>>
                                                            <?php echo $station['name']; ?> (<?php echo $station['code']; ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <label for="from_station_id">From Station</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating mb-3">
                                                <select class="form-select modern-select" id="to_station_id" name="to_station_id" required>
                                                    <option value="" selected disabled>Select arrival station</option>
                                                    <?php foreach ($stations as $station): ?>
                                                        <option value="<?php echo $station['id']; ?>" <?php echo $formData['to_station_id'] == $station['id'] ? 'selected' : ''; ?>>
                                                            <?php echo $station['name']; ?> (<?php echo $station['code']; ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <label for="to_station_id">To Station</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="form-floating mb-3">
                                                <input type="date" class="form-control modern-input" id="journey_date" name="journey_date" 
                                                       min="<?php echo date('Y-m-d'); ?>" 
                                                       max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" 
                                                       value="<?php echo $formData['journey_date'] ?: date('Y-m-d'); ?>"
                                                       required>
                                                <label for="journey_date">Journey Date</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating mb-3">
                                                <select class="form-select modern-select" id="passengers" name="passengers" required>
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <option value="<?php echo $i; ?>" <?php echo $formData['passengers'] == $i ? 'selected' : ''; ?>>
                                                            <?php echo $i; ?> <?php echo $i === 1 ? 'passenger' : 'passengers'; ?>
                                                        </option>
                                                    <?php endfor; ?>
                                                </select>
                                                <label for="passengers">Number of Passengers</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary modern-button">
                                            <i class="fas fa-search me-2"></i>Find Schedules
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Step 2: Schedule Selection Form -->
                <?php if (isset($_POST['step']) && $_POST['step'] === 'find_schedules' && empty($errors) && !empty($schedules)): ?>
                    <div class="booking-content">
                        <div class="card modern-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Select Your Schedule</h5>
                            </div>
                            <div class="card-body">
                                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="schedule-form">
                                    <input type="hidden" name="step" value="confirm_booking">
                                    <input type="hidden" name="route_id" value="<?php echo $formData['route_id']; ?>">
                                    <input type="hidden" name="from_station_id" value="<?php echo $formData['from_station_id']; ?>">
                                    <input type="hidden" name="to_station_id" value="<?php echo $formData['to_station_id']; ?>">
                                    <input type="hidden" name="journey_date" value="<?php echo $formData['journey_date']; ?>">
                                    <input type="hidden" name="passengers" value="<?php echo $formData['passengers']; ?>">

                                    <?php foreach ($schedules as $schedule): ?>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="radio" name="schedule_id" id="schedule_<?php echo $schedule['id']; ?>" value="<?php echo $schedule['id']; ?>" required>
                                            <label class="form-check-label" for="schedule_<?php echo $schedule['id']; ?>">
                                                <strong><?php echo $schedule['train_name']; ?> (<?php echo $schedule['train_number']; ?>)</strong><br>
                                                Departure: <?php echo date('h:i A', strtotime($schedule['departure_time'])); ?><br>
                                                Route: <?php echo $schedule['route_name']; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary modern-button">
                                            <i class="fas fa-check me-2"></i>Confirm Schedule
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
ob_end_flush();
?>
