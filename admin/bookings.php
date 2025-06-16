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
$bookingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_booking_status'])) {
        $updateId = (int)$_POST['booking_id'];
        $newStatus = sanitizeInput($_POST['new_status']);
        
        if ($updateId > 0 && in_array($newStatus, ['pending', 'confirmed', 'cancelled', 'completed'])) {
            global $conn;
            
            try {
                // Start transaction
                $conn->begin_transaction();
                
                // Update booking status
                $stmt = $conn->prepare("
                    UPDATE bookings 
                    SET booking_status = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->bind_param('si', $newStatus, $updateId);
                $stmt->execute();
                
                // If booking is cancelled, change tickets status to 'cancelled'
                if ($newStatus == 'cancelled') {
                    $stmt = $conn->prepare("
                        UPDATE tickets 
                        SET status = 'cancelled', updated_at = NOW() 
                        WHERE booking_id = ?
                    ");
                    $stmt->bind_param('i', $updateId);
                    $stmt->execute();
                }
                
                // Log activity
                logActivity('booking_status_updated', 'Updated booking #' . $updateId . ' status to ' . $newStatus, $userId);
                
                $conn->commit();
                
                setFlashMessage('success', 'Booking status updated successfully to ' . ucfirst($newStatus) . '.');
            } catch (Exception $e) {
                $conn->rollback();
                setFlashMessage('error', 'Error updating booking status: ' . $e->getMessage());
            }
            
            redirect(APP_URL . '/admin/bookings.php');
        } else {
            setFlashMessage('error', 'Invalid booking ID or status.');
            redirect(APP_URL . '/admin/bookings.php');
        }
    } elseif (isset($_POST['update_payment_status'])) {
        $updateId = (int)$_POST['booking_id'];
        $newStatus = sanitizeInput($_POST['new_payment_status']);
        
        if ($updateId > 0 && in_array($newStatus, ['pending', 'paid', 'failed', 'refunded'])) {
            global $conn;
            
            try {
                // Start transaction
                $conn->begin_transaction();
                
                // Update payment status
                $stmt = $conn->prepare("
                    UPDATE bookings 
                    SET payment_status = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->bind_param('si', $newStatus, $updateId);
                $stmt->execute();
                
                // Get booking details
                $booking = fetchRow("SELECT * FROM bookings WHERE id = ?", [$updateId]);
                
                // If the booking exists, update related records
                if ($booking) {
                    // If status changed to paid
                    if ($newStatus == 'paid') {
                        // Update payments table if exists
                        $payment = fetchRow("SELECT id FROM payments WHERE booking_id = ?", [$updateId]);
                        
                        if ($payment) {
                            // Update existing payment
                            $stmt = $conn->prepare("
                                UPDATE payments 
                                SET status = 'completed', 
                                    payment_date = NOW(), 
                                    updated_at = NOW() 
                                WHERE booking_id = ?
                            ");
                            $stmt->bind_param('i', $updateId);
                            $stmt->execute();
                        } else {
                            // Create new payment record
                            $stmt = $conn->prepare("
                                INSERT INTO payments (
                                    booking_id, 
                                    transaction_id, 
                                    payment_method, 
                                    amount, 
                                    status, 
                                    payment_date, 
                                    created_at
                                ) VALUES (
                                    ?, 
                                    ?, 
                                    'admin_override', 
                                    ?, 
                                    'completed', 
                                    NOW(), 
                                    NOW()
                                )
                            ");
                            
                            $transactionId = 'ADMIN' . time() . rand(1000, 9999);
                            $stmt->bind_param('isd', $updateId, $transactionId, $booking['amount']);
                            $stmt->execute();
                        }
                        
                        // Set booking status to confirmed if it was pending
                        if ($booking['booking_status'] == 'pending') {
                            $confirmStatus = 'confirmed';
                            $stmt = $conn->prepare("
                                UPDATE bookings 
                                SET booking_status = ? 
                                WHERE id = ?
                            ");
                            $stmt->bind_param('si', $confirmStatus, $updateId);
                            $stmt->execute();
                        }
                    }
                    
                    // If status changed to refunded
                    if ($newStatus == 'refunded') {
                        // Update payments table
                        $stmt = $conn->prepare("
                            UPDATE payments 
                            SET status = 'refunded', 
                                updated_at = NOW() 
                            WHERE booking_id = ?
                        ");
                        $stmt->bind_param('i', $updateId);
                        $stmt->execute();
                        
                        // Cancel booking and tickets if not already cancelled
                        if ($booking['booking_status'] != 'cancelled') {
                            $cancelStatus = 'cancelled';
                            
                            // Cancel booking
                            $stmt = $conn->prepare("
                                UPDATE bookings 
                                SET booking_status = ? 
                                WHERE id = ?
                            ");
                            $stmt->bind_param('si', $cancelStatus, $updateId);
                            $stmt->execute();
                            
                            // Cancel tickets
                            $stmt = $conn->prepare("
                                UPDATE tickets 
                                SET status = 'cancelled', 
                                    updated_at = NOW() 
                                WHERE booking_id = ?
                            ");
                            $stmt->bind_param('i', $updateId);
                            $stmt->execute();
                        }
                    }
                }
                
                // Log activity
                logActivity('payment_status_updated', 'Updated booking #' . $updateId . ' payment status to ' . $newStatus, $userId);
                
                $conn->commit();
                
                setFlashMessage('success', 'Payment status updated successfully to ' . ucfirst($newStatus) . '.');
            } catch (Exception $e) {
                $conn->rollback();
                setFlashMessage('error', 'Error updating payment status: ' . $e->getMessage());
            }
            
            redirect(APP_URL . '/admin/bookings.php');
        } else {
            setFlashMessage('error', 'Invalid booking ID or payment status.');
            redirect(APP_URL . '/admin/bookings.php');
        }
    }
}

// Set default filters
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$paymentFilter = isset($_GET['payment']) ? sanitizeInput($_GET['payment']) : '';
$dateFrom = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : '';
$searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build query based on filters
$query = "
    SELECT b.*, 
           u.name as user_name, u.email as user_email,
           fs.name as from_station_name, ts.name as to_station_name,
           r.name as route_name, 
           s.departure_time,
           t.name as train_name, t.train_number
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN stations fs ON b.from_station_id = fs.id
    JOIN stations ts ON b.to_station_id = ts.id
    JOIN schedules s ON b.schedule_id = s.id
    JOIN routes r ON s.route_id = r.id
    JOIN trains t ON s.train_id = t.id
    WHERE 1=1
";

$params = [];

// Add filters to query
if (!empty($statusFilter)) {
    $query .= " AND b.booking_status = ?";
    $params[] = $statusFilter;
}

if (!empty($paymentFilter)) {
    $query .= " AND b.payment_status = ?";
    $params[] = $paymentFilter;
}

if (!empty($dateFrom)) {
    $query .= " AND b.journey_date >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $query .= " AND b.journey_date <= ?";
    $params[] = $dateTo;
}

if (!empty($searchTerm)) {
    $query .= " AND (b.booking_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $searchParam = "%{$searchTerm}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

// Add order by
$query .= " ORDER BY b.booking_date DESC";

// Get bookings based on filters
$bookings = fetchRows($query, $params);

// If viewing a specific booking
$bookingDetail = null;
$tickets = [];
$payments = [];

if ($action === 'view' && $bookingId > 0) {
    // Get booking details
    $bookingDetail = fetchRow("
        SELECT b.*, 
               u.name as user_name, u.email as user_email, u.phone as user_phone,
               fs.name as from_station_name, fs.code as from_station_code,
               ts.name as to_station_name, ts.code as to_station_code,
               r.name as route_name, r.code as route_code,
               s.departure_time,
               t.name as train_name, t.train_number
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN stations fs ON b.from_station_id = fs.id
        JOIN stations ts ON b.to_station_id = ts.id
        JOIN schedules s ON b.schedule_id = s.id
        JOIN routes r ON s.route_id = r.id
        JOIN trains t ON s.train_id = t.id
        WHERE b.id = ?
    ", [$bookingId]);
    
    if (!$bookingDetail) {
        setFlashMessage('error', 'Booking not found.');
        redirect(APP_URL . '/admin/bookings.php');
    }
    
    // Get tickets for this booking
    $tickets = fetchRows("
        SELECT * FROM tickets WHERE booking_id = ? ORDER BY id ASC
    ", [$bookingId]);
    
    // Get payments for this booking
    $payments = fetchRows("
        SELECT * FROM payments WHERE booking_id = ? ORDER BY payment_date DESC
    ", [$bookingId]);
}

// Get flash messages
$flashSuccess = getFlashMessage('success');
$flashError = getFlashMessage('error');

// Page title
$pageTitle = 'Manage Bookings';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metro Rail - Admin - <?php echo $pageTitle; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet">    <!-- Custom CSS -->
    <link href="<?php echo APP_URL; ?>/css/style.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/css/admin.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/css/admin_modern.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 6px 10px rgba(0,0,0,.08), 0 0 6px rgba(0,0,0,.05);
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,.12), 0 4px 8px rgba(0,0,0,.06);
        }
        .stat-card .card-body {
            padding: 1.5rem;
        }
        .stat-card .card-title {
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-card .stat-icon {
            font-size: 2.5rem;
            color: rgba(0, 123, 255, 0.6);
        }
        .modal-header {
            border-radius: 8px 8px 0 0;
        }
        .btn {
            border-radius: 6px;
        }
        .badge {
            font-weight: 500;
            padding: 0.55em 0.8em;
            font-size: 0.75em;
        }
        .status-badge {
            font-size: 0.85em;
        }
        .booking-badge {
            min-width: 90px;
            text-align: center;
        }    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/admin_sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Header / Navbar -->
                <nav class="navbar navbar-expand-lg navbar-light bg-white mb-4 rounded shadow-sm">
                    <div class="container-fluid">
                        <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target=".admin-sidebar">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <span class="navbar-brand mb-0 h1">
                            <i class="fas fa-ticket-alt me-2 text-primary"></i><?php echo $pageTitle; ?>
                        </span>
                        <div class="d-flex">
                            <div class="dropdown">
                                <button class="btn btn-link dropdown-toggle text-decoration-none" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i> <?php echo $admin['name']; ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userMenu">
                                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2 text-primary"></i>Profile</a></li>
                                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>"><i class="fas fa-home me-2 text-primary"></i>Main Site</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2 text-danger"></i>Logout</a></li>
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
                            </div>                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <?php
                            // Get total bookings count
                            $totalBookings = fetchRow("SELECT COUNT(*) as count FROM bookings");
                        ?>
                        <div class="card stat-card border-left-primary h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="card-title text-primary mb-1">Total Bookings</div>
                                        <div class="h3 mb-0 font-weight-bold"><?php echo number_format($totalBookings['count']); ?></div>
                                        <div class="text-muted small">All time bookings</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-ticket-alt stat-icon text-primary-light"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <?php
                            // Get today's bookings
                            $todayBookings = fetchRow("SELECT COUNT(*) as count FROM bookings WHERE DATE(booking_date) = CURDATE()");
                        ?>
                        <div class="card stat-card border-left-success h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="card-title text-success mb-1">Today's Bookings</div>
                                        <div class="h3 mb-0 font-weight-bold"><?php echo number_format($todayBookings['count']); ?></div>
                                        <div class="text-muted small">Booked today</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-day stat-icon text-success-light"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <?php
                            // Get pending bookings count
                            $pendingBookings = fetchRow("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'pending'");
                        ?>
                        <div class="card stat-card border-left-warning h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="card-title text-warning mb-1">Pending Bookings</div>
                                        <div class="h3 mb-0 font-weight-bold"><?php echo number_format($pendingBookings['count']); ?></div>
                                        <div class="text-muted small">Awaiting confirmation</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-hourglass-half stat-icon text-warning-light"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">                        <?php
                            // Get total revenue
                            $totalRevenue = fetchRow("SELECT SUM(amount) as total FROM bookings WHERE payment_status = 'paid'");
                            $totalAmount = $totalRevenue['total'] ?? 0; // Use null coalescing operator to prevent errors
                        ?>
                        <div class="card stat-card border-left-info h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="card-title text-info mb-1">Total Revenue</div>
                                        <div class="h3 mb-0 font-weight-bold">$<?php echo number_format($totalAmount, 2); ?></div>
                                        <div class="text-muted small">From paid bookings</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign stat-icon text-info-light"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                  <?php if ($action === 'view' && $bookingDetail): ?>
                    <!-- Booking Detail View -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center bg-primary text-white">
                            <h6 class="m-0 font-weight-bold">
                                <i class="fas fa-ticket-alt me-2"></i>
                                Booking #<?php echo htmlspecialchars($bookingDetail['booking_number']); ?>
                            </h6>
                            <a href="<?php echo APP_URL; ?>/admin/bookings.php" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to Bookings
                            </a>
                        </div>
                        <div class="card-body">
                            <!-- Booking Status Badges -->
                            <div class="mb-4 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-<?php 
                                        echo $bookingDetail['booking_status'] === 'confirmed' ? 'success' : 
                                            ($bookingDetail['booking_status'] === 'pending' ? 'warning text-dark' : 
                                                ($bookingDetail['booking_status'] === 'completed' ? 'info' : 'danger')); 
                                    ?> p-2 me-2 booking-badge">
                                        <i class="fas fa-<?php 
                                            echo $bookingDetail['booking_status'] === 'confirmed' ? 'check-circle' : 
                                                ($bookingDetail['booking_status'] === 'pending' ? 'hourglass-half' : 
                                                    ($bookingDetail['booking_status'] === 'completed' ? 'flag-checkered' : 'times-circle')); 
                                        ?> me-1"></i>
                                        Status: <?php echo ucfirst(htmlspecialchars($bookingDetail['booking_status'])); ?>
                                    </span>
                                    
                                    <span class="badge bg-<?php 
                                        echo $bookingDetail['payment_status'] === 'paid' ? 'success' : 
                                            ($bookingDetail['payment_status'] === 'pending' ? 'warning text-dark' : 
                                                ($bookingDetail['payment_status'] === 'refunded' ? 'info' : 'danger')); 
                                    ?> p-2 booking-badge">
                                        <i class="fas fa-<?php 
                                            echo $bookingDetail['payment_status'] === 'paid' ? 'check-circle' : 
                                                ($bookingDetail['payment_status'] === 'pending' ? 'hourglass-half' : 
                                                    ($bookingDetail['payment_status'] === 'refunded' ? 'undo' : 'times-circle')); 
                                        ?> me-1"></i>
                                        Payment: <?php echo ucfirst(htmlspecialchars($bookingDetail['payment_status'])); ?>
                                    </span>
                                </div>
                                
                                <div>
                                    <button type="button" class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#statusModal">
                                        <i class="fas fa-edit"></i> Update Status
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                        <i class="fas fa-money-bill-wave"></i> Update Payment
                                    </button>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Booking Information</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="40%">Booking Number</th>
                                            <td><?php echo htmlspecialchars($bookingDetail['booking_number']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Booking Date</th>
                                            <td><?php echo date('M d, Y h:i A', strtotime($bookingDetail['booking_date'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Journey Date</th>
                                            <td><?php echo date('M d, Y', strtotime($bookingDetail['journey_date'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Booking Status</th>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-<?php 
                                                        echo $bookingDetail['booking_status'] === 'confirmed' ? 'success' : 
                                                            ($bookingDetail['booking_status'] === 'pending' ? 'warning' : 
                                                                ($bookingDetail['booking_status'] === 'completed' ? 'info' : 'danger')); 
                                                    ?> me-2">
                                                        <?php echo ucfirst(htmlspecialchars($bookingDetail['booking_status'])); ?>
                                                    </span>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#changeStatusModal">
                                                        Change
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Payment Status</th>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-<?php 
                                                        echo $bookingDetail['payment_status'] === 'paid' ? 'success' : 
                                                            ($bookingDetail['payment_status'] === 'pending' ? 'warning' : 
                                                                ($bookingDetail['payment_status'] === 'refunded' ? 'info' : 'danger')); 
                                                    ?> me-2">
                                                        <?php echo ucfirst(htmlspecialchars($bookingDetail['payment_status'])); ?>
                                                    </span>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#changePaymentStatusModal">
                                                        Change
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Amount</th>
                                            <td>$<?php echo number_format($bookingDetail['amount'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Passengers</th>
                                            <td><?php echo (int)$bookingDetail['passengers']; ?></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <div class="col-md-6">
                                    <h6>Journey Information</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="40%">Route</th>
                                            <td><?php echo htmlspecialchars($bookingDetail['route_name'] . ' (' . $bookingDetail['route_code'] . ')'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Train</th>
                                            <td><?php echo htmlspecialchars($bookingDetail['train_name'] . ' (' . $bookingDetail['train_number'] . ')'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Departure Time</th>
                                            <td><?php echo substr($bookingDetail['departure_time'], 0, 5); ?></td>
                                        </tr>
                                        <tr>
                                            <th>From Station</th>
                                            <td><?php echo htmlspecialchars($bookingDetail['from_station_name'] . ' (' . $bookingDetail['from_station_code'] . ')'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>To Station</th>
                                            <td><?php echo htmlspecialchars($bookingDetail['to_station_name'] . ' (' . $bookingDetail['to_station_code'] . ')'); ?></td>
                                        </tr>
                                    </table>
                                    
                                    <h6 class="mt-4">Customer Information</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="40%">Name</th>
                                            <td><?php echo htmlspecialchars($bookingDetail['user_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Email</th>
                                            <td><?php echo htmlspecialchars($bookingDetail['user_email']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Phone</th>
                                            <td><?php echo htmlspecialchars($bookingDetail['user_phone']); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                              <!-- Tickets -->
                            <div class="card mb-4 border-0 bg-light">
                                <div class="card-header bg-primary text-white py-2">
                                    <h6 class="m-0 font-weight-bold"><i class="fas fa-ticket-alt me-2"></i>Tickets (<?php echo count($tickets); ?>)</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover" id="ticketsTable" width="100%" cellspacing="0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Ticket Number</th>
                                                    <th>Passenger Name</th>
                                                    <th>Seat Number</th>
                                                    <th>Status</th>
                                                    <th>Checked In</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($tickets)): ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center">No tickets found for this booking.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($tickets as $ticket): ?>
                                                        <tr>
                                                            <td><span class="fw-bold text-primary"><?php echo htmlspecialchars($ticket['ticket_number']); ?></span></td>
                                                            <td><?php echo htmlspecialchars($ticket['passenger_name']); ?></td>
                                                            <td>
                                                                <?php if ($ticket['seat_number']): ?>
                                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($ticket['seat_number']); ?></span>
                                                                <?php else: ?>
                                                                    <span class="text-muted">Not Assigned</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge booking-badge bg-<?php 
                                                                    echo $ticket['status'] === 'active' ? 'success' : 
                                                                        ($ticket['status'] === 'used' ? 'info' : 'danger'); 
                                                                ?>">
                                                                    <i class="fas fa-<?php 
                                                                        echo $ticket['status'] === 'active' ? 'check-circle' : 
                                                                            ($ticket['status'] === 'used' ? 'flag-checkered' : 'times-circle'); 
                                                                    ?> me-1"></i>
                                                                    <?php echo ucfirst(htmlspecialchars($ticket['status'])); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php if ($ticket['checked_in']): ?>
                                                                    <span class="text-success">
                                                                        <i class="fas fa-check-circle me-1"></i> Yes
                                                                        <span class="text-muted d-block small">
                                                                            <?php echo date('M d, Y h:i A', strtotime($ticket['checked_in_at'])); ?>
                                                                        </span>
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="text-muted"><i class="fas fa-times-circle me-1"></i> Not checked in</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Payments -->
                            <div class="card mb-4 border-0 bg-light">
                                <div class="card-header bg-success text-white py-2">
                                    <h6 class="m-0 font-weight-bold"><i class="fas fa-money-bill-wave me-2"></i>Payments</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover" id="paymentsTable" width="100%" cellspacing="0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Transaction ID</th>
                                                    <th>Payment Method</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                    <th>Payment Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                        <?php if (empty($payments)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No payment records found for this booking.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($payments as $payment): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($payment['transaction_id'] ?: 'N/A'); ?></td>
                                                    <td><?php echo str_replace('_', ' ', ucfirst(htmlspecialchars($payment['payment_method']))); ?></td>
                                                    <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $payment['status'] === 'completed' ? 'success' : 
                                                                ($payment['status'] === 'pending' ? 'warning' : 
                                                                    ($payment['status'] === 'refunded' ? 'info' : 'danger')); 
                                                        ?>">
                                                            <?php echo ucfirst(htmlspecialchars($payment['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php echo $payment['payment_date'] ? date('M d, Y h:i A', strtotime($payment['payment_date'])) : 'N/A'; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                              <!-- Change Status Modal -->
                            <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title" id="statusModalLabel">
                                                <i class="fas fa-edit me-2"></i>Update Booking Status
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="post" action="<?php echo APP_URL; ?>/admin/bookings.php" class="status-update-modal">
                                            <input type="hidden" name="booking_id" value="<?php echo $bookingDetail['id']; ?>">
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label for="new_status" class="form-label fw-bold">New Status</label>
                                                    <select class="form-select form-select-lg mb-3" id="new_status" name="new_status" required>
                                                        <option value="">-- Select Status --</option>
                                                        <option value="pending" <?php echo $bookingDetail['booking_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="confirmed" <?php echo $bookingDetail['booking_status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                        <option value="cancelled" <?php echo $bookingDetail['booking_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                        <option value="completed" <?php echo $bookingDetail['booking_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                    </select>
                                                </div>
                                                <div class="alert alert-warning">
                                                    <p class="mb-1 fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Important Notes:</p>
                                                    <ul class="mb-0 small">
                                                        <li>If you set a booking to <strong>Cancelled</strong>, all related tickets will also be cancelled.</li>
                                                        <li>Changes to booking status do not automatically affect payment status.</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                                    <i class="fas fa-times me-1"></i> Cancel
                                                </button>
                                                <button type="submit" name="update_booking_status" class="btn btn-primary confirm-status-update">
                                                    <i class="fas fa-save me-1"></i> Update Status
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Change Payment Status Modal -->
                            <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title" id="paymentModalLabel">
                                                <i class="fas fa-money-bill-wave me-2"></i>Update Payment Status
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="post" action="<?php echo APP_URL; ?>/admin/bookings.php">
                                            <input type="hidden" name="booking_id" value="<?php echo $bookingDetail['id']; ?>">
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label for="new_payment_status" class="form-label fw-bold">New Payment Status</label>
                                                    <select class="form-select form-select-lg mb-3" id="new_payment_status" name="new_payment_status" required>
                                                        <option value="">-- Select Status --</option>
                                                        <option value="pending" <?php echo $bookingDetail['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="paid" <?php echo $bookingDetail['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                        <option value="failed" <?php echo $bookingDetail['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                                        <option value="refunded" <?php echo $bookingDetail['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                                    </select>
                                                </div>
                                                <div class="alert alert-info">
                                                    <p class="mb-1 fw-bold"><i class="fas fa-info-circle me-2"></i> Payment Status Effects:</p>
                                                    <ul class="mb-0 small">
                                                        <li>Setting to <strong>Paid</strong> will automatically confirm a pending booking and create a payment record if one doesn't exist.</li>
                                                        <li>Setting to <strong>Refunded</strong> will update any payment records and set the booking to cancelled.</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                                    <i class="fas fa-times me-1"></i> Cancel
                                                </button>
                                                <button type="submit" name="update_payment_status" class="btn btn-success">
                                                    <i class="fas fa-save me-1"></i> Update Payment Status
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Bookings List -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold">Filter Bookings</h6>
                        </div>
                        <div class="card-body">
                            <form method="get" action="<?php echo APP_URL; ?>/admin/bookings.php" class="row g-3">
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Booking Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">All</option>
                                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="payment" class="form-label">Payment Status</label>
                                    <select class="form-select" id="payment" name="payment">
                                        <option value="">All</option>
                                        <option value="pending" <?php echo $paymentFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="paid" <?php echo $paymentFilter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                        <option value="failed" <?php echo $paymentFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                        <option value="refunded" <?php echo $paymentFilter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="date_from" class="form-label">Journey Date From</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $dateFrom; ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="date_to" class="form-label">Journey Date To</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $dateTo; ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="search" class="form-label">Search</label>
                                    <input type="text" class="form-control" id="search" name="search" placeholder="Booking # or User" value="<?php echo $searchTerm; ?>">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                                    <a href="<?php echo APP_URL; ?>/admin/bookings.php" class="btn btn-secondary">Reset</a>
                                </div>
                            </form>
                        </div>
                    </div>
                      <div class="card shadow-sm mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold">Bookings List</h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#filtersCollapse">
                                <i class="fas fa-filter"></i> Filter Options
                            </button>
                        </div>
                        <div class="card-body">
                            <!-- Filters Collapse Section -->
                            <div class="collapse mb-4" id="filtersCollapse">
                                <div class="card card-body bg-light border-0">
                                    <form method="get" action="<?php echo APP_URL; ?>/admin/bookings.php" class="row g-3">
                                        <div class="col-md-3">
                                            <label for="status" class="form-label">Booking Status</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="">All Statuses</option>
                                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="payment" class="form-label">Payment Status</label>
                                            <select class="form-select" id="payment" name="payment">
                                                <option value="">All Payment Statuses</option>
                                                <option value="pending" <?php echo $paymentFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="paid" <?php echo $paymentFilter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                <option value="failed" <?php echo $paymentFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                                <option value="refunded" <?php echo $paymentFilter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="date_from" class="form-label">Date From</label>
                                            <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $dateFrom; ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="date_to" class="form-label">Date To</label>
                                            <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $dateTo; ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="search" class="form-label">Search</label>
                                            <input type="text" class="form-control" id="search" name="search" value="<?php echo $searchTerm; ?>" placeholder="Booking #, Name, Email">
                                        </div>
                                        <div class="col-12 text-end">
                                            <a href="<?php echo APP_URL; ?>/admin/bookings.php" class="btn btn-secondary me-2">Reset</a>
                                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="bookingsTable" width="100%" cellspacing="0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Booking #</th>
                                            <th>User</th>
                                            <th>Route & Train</th>
                                            <th>Journey Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Payment</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($bookings)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No bookings found matching your filters.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($bookings as $booking): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($booking['booking_number']); ?></strong></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <span class="avatar-sm bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                                <i class="fas fa-user"></i>
                                                            </span>
                                                            <div>
                                                                <?php echo htmlspecialchars($booking['user_name']); ?><br>
                                                                <small class="text-muted"><?php echo htmlspecialchars($booking['user_email']); ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    </td>                                                    <td>
                                                        <div>
                                                            <strong>Route:</strong> <?php echo htmlspecialchars($booking['route_name']); ?><br>
                                                            <strong>Train:</strong> <?php echo htmlspecialchars($booking['train_name']); ?> 
                                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($booking['train_number']); ?></span><br>
                                                            <small class="text-muted">
                                                                <i class="fas fa-route me-1"></i>
                                                                <?php echo htmlspecialchars($booking['from_station_name']); ?> → <?php echo htmlspecialchars($booking['to_station_name']); ?>
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <span class="fw-bold"><?php echo date('M d, Y', strtotime($booking['journey_date'])); ?></span>
                                                            <small class="text-muted">
                                                                <i class="fas fa-clock me-1"></i>
                                                                Departure: <?php echo substr($booking['departure_time'], 0, 5); ?>
                                                            </small>
                                                            <small class="text-muted">
                                                                <i class="fas fa-calendar-alt me-1"></i>
                                                                Booked: <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold">$<?php echo number_format($booking['amount'], 2); ?></span><br>
                                                        <small class="text-muted">
                                                            <i class="fas fa-users me-1"></i>
                                                            Passengers: <?php echo (int)$booking['passengers']; ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge booking-badge bg-<?php 
                                                            echo $booking['booking_status'] === 'confirmed' ? 'success' : 
                                                                ($booking['booking_status'] === 'pending' ? 'warning text-dark' : 
                                                                    ($booking['booking_status'] === 'completed' ? 'info' : 'danger')); 
                                                        ?>">
                                                            <i class="fas fa-<?php 
                                                                echo $booking['booking_status'] === 'confirmed' ? 'check-circle' : 
                                                                    ($booking['booking_status'] === 'pending' ? 'hourglass-half' : 
                                                                        ($booking['booking_status'] === 'completed' ? 'flag-checkered' : 'times-circle')); 
                                                            ?> me-1"></i>
                                                            <?php echo ucfirst(htmlspecialchars($booking['booking_status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge booking-badge bg-<?php 
                                                            echo $booking['payment_status'] === 'paid' ? 'success' : 
                                                                ($booking['payment_status'] === 'pending' ? 'warning text-dark' : 
                                                                    ($booking['payment_status'] === 'refunded' ? 'info' : 'danger')); 
                                                        ?>">
                                                            <i class="fas fa-<?php 
                                                                echo $booking['payment_status'] === 'paid' ? 'check-circle' : 
                                                                    ($booking['payment_status'] === 'pending' ? 'hourglass-half' : 
                                                                        ($booking['payment_status'] === 'refunded' ? 'undo' : 'times-circle')); 
                                                            ?> me-1"></i>
                                                            <?php echo ucfirst(htmlspecialchars($booking['payment_status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex gap-1">
                                                            <a href="<?php echo APP_URL; ?>/admin/bookings.php?action=view&id=<?php echo $booking['id']; ?>" class="btn btn-primary btn-sm">
                                                                <i class="fas fa-eye"></i> View
                                                            </a>
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
        </div>
    </div>
      <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
    
    <!-- Custom JavaScript -->    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTables for bookings table
            if (document.getElementById('bookingsTable')) {
                let bookingsTable = new DataTable('#bookingsTable', {
                    responsive: true,
                    order: [[3, 'desc']], // Sort by journey date by default
                    language: {
                        search: '<i class="fas fa-search"></i>',
                        searchPlaceholder: 'Search bookings...',
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ bookings",
                        paginate: {
                            first: '<i class="fas fa-angle-double-left"></i>',
                            previous: '<i class="fas fa-angle-left"></i>',
                            next: '<i class="fas fa-angle-right"></i>',
                            last: '<i class="fas fa-angle-double-right"></i>'
                        }
                    },
                    dom: '<"row mb-3"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
                    columnDefs: [
                        { targets: -1, orderable: false } // Disable sorting on actions column
                    ],
                    pageLength: 25
                });
                
                // Custom styling for DataTables inputs
                document.querySelector('.dataTables_filter input').classList.add('form-control');
                document.querySelector('.dataTables_length select').classList.add('form-select');
            }
            
            // Initialize DataTables for tickets and payments tables
            if (document.getElementById('ticketsTable')) {
                new DataTable('#ticketsTable', {
                    responsive: true,
                    order: [[0, 'asc']], // Sort by ticket number
                    language: {
                        search: '<i class="fas fa-search"></i>',
                        searchPlaceholder: 'Search tickets...'
                    },
                    pageLength: 10
                });
            }
            
            if (document.getElementById('paymentsTable')) {
                new DataTable('#paymentsTable', {
                    responsive: true,
                    order: [[4, 'desc']], // Sort by payment date
                    language: {
                        search: '<i class="fas fa-search"></i>',
                        searchPlaceholder: 'Search payments...'
                    },
                    pageLength: 10
                });
            }
            
            // Apply hover effects to all cards
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.classList.add('shadow-lg');
                });
                card.addEventListener('mouseleave', function() {
                    this.classList.remove('shadow-lg');
                });
            });
            
            // Status update modals
            const statusUpdateModals = document.querySelectorAll('.status-update-modal');
            statusUpdateModals.forEach(modal => {
                const form = modal.querySelector('form');
                const statusSelect = form.querySelector('select[name="new_status"]');
                
                if (statusSelect) {
                    statusSelect.addEventListener('change', function() {
                        const confirmButton = form.querySelector('.confirm-status-update');
                        const status = this.value;
                        
                        // Update button color based on status
                        confirmButton.className = 'btn confirm-status-update ';
                        
                        if (status === 'confirmed') {
                            confirmButton.className += 'btn-success';
                        } else if (status === 'cancelled') {
                            confirmButton.className += 'btn-danger';
                        } else if (status === 'completed') {
                            confirmButton.className += 'btn-info';
                        } else {
                            confirmButton.className += 'btn-warning';
                        }
                    });
                }
            });
        });
    </script>
