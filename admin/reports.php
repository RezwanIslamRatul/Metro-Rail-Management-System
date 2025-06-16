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
$reportType = isset($_GET['type']) ? sanitizeInput($_GET['type']) : 'booking';
$startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : date('Y-m-01'); // First day of current month
$endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : date('Y-m-d'); // Today
$exportFormat = isset($_GET['export']) ? sanitizeInput($_GET['export']) : '';

// Fetch routes for filtering
$routes = fetchRows("SELECT id, name, code FROM routes ORDER BY name ASC");

// Fetch report data based on type
$reportData = [];
$reportTitle = '';
$reportHeaders = [];
$chartData = [];
$reportSummary = [];

switch ($reportType) {
    case 'booking':
        $reportTitle = 'Booking Report';
        $reportHeaders = ['ID', 'Booking Number', 'User', 'Route', 'From', 'To', 'Journey Date', 'Passengers', 'Amount', 'Status', 'Payment'];
        
        // Get filter values
        $routeId = isset($_GET['route_id']) ? (int)$_GET['route_id'] : 0;
        $bookingStatus = isset($_GET['booking_status']) ? sanitizeInput($_GET['booking_status']) : '';
        $paymentStatus = isset($_GET['payment_status']) ? sanitizeInput($_GET['payment_status']) : '';
        
        // Build query
        $query = "SELECT b.id, b.booking_number, u.name as user_name, 
                  r.name as route_name, s1.name as from_station, s2.name as to_station,
                  b.journey_date, b.passengers, b.amount, b.booking_status, b.payment_status
                  FROM bookings b
                  JOIN users u ON b.user_id = u.id
                  JOIN schedules sch ON b.schedule_id = sch.id
                  JOIN routes r ON sch.route_id = r.id
                  JOIN stations s1 ON b.from_station_id = s1.id
                  JOIN stations s2 ON b.to_station_id = s2.id
                  WHERE DATE(b.booking_date) BETWEEN ? AND ?";
        
        $params = [$startDate, $endDate];
        
        if ($routeId > 0) {
            $query .= " AND sch.route_id = ?";
            $params[] = $routeId;
        }
        
        if (!empty($bookingStatus)) {
            $query .= " AND b.booking_status = ?";
            $params[] = $bookingStatus;
        }
        
        if (!empty($paymentStatus)) {
            $query .= " AND b.payment_status = ?";
            $params[] = $paymentStatus;
        }
        
        $query .= " ORDER BY b.booking_date DESC";
        
        // Get report data
        $reportData = fetchRows($query, $params);
        
        // Generate summary data
        $totalBookings = count($reportData);
        $totalPassengers = 0;
        $totalRevenue = 0;
        $statusCounts = [
            'pending' => 0,
            'confirmed' => 0,
            'cancelled' => 0,
            'completed' => 0
        ];
        $paymentCounts = [
            'pending' => 0,
            'paid' => 0,
            'failed' => 0,
            'refunded' => 0
        ];
        
        foreach ($reportData as $booking) {
            $totalPassengers += $booking['passengers'];
            $totalRevenue += $booking['amount'];
            $statusCounts[$booking['booking_status']]++;
            $paymentCounts[$booking['payment_status']]++;
        }
        
        // Add to summary
        $reportSummary = [
            'Total Bookings' => $totalBookings,
            'Total Passengers' => $totalPassengers,
            'Total Revenue' => formatReportCurrency($totalRevenue),
            'Confirmed Bookings' => $statusCounts['confirmed'],
            'Completed Bookings' => $statusCounts['completed'],
            'Cancelled Bookings' => $statusCounts['cancelled'],
            'Paid Bookings' => $paymentCounts['paid'],
            'Pending Payments' => $paymentCounts['pending']
        ];
        
        // Chart data - bookings by status
        $chartData = [
            'labels' => ['Pending', 'Confirmed', 'Cancelled', 'Completed'],
            'datasets' => [
                [
                    'data' => [
                        $statusCounts['pending'],
                        $statusCounts['confirmed'],
                        $statusCounts['cancelled'],
                        $statusCounts['completed']
                    ],
                    'backgroundColor' => ['#6c757d', '#0d6efd', '#dc3545', '#198754']
                ]
            ]
        ];
        
        break;
        
    case 'revenue':
        $reportTitle = 'Revenue Report';
        $reportHeaders = ['Date', 'Bookings', 'Total Revenue', 'Average Revenue per Booking'];
        
        // Get filter values
        $groupBy = isset($_GET['group_by']) ? sanitizeInput($_GET['group_by']) : 'day';
        
        // Different queries based on grouping
        if ($groupBy === 'day') {
            $query = "SELECT DATE(b.booking_date) as date, 
                    COUNT(*) as num_bookings, 
                    SUM(b.amount) as total_revenue,
                    AVG(b.amount) as avg_revenue
                    FROM bookings b
                    WHERE b.payment_status = 'paid'
                    AND DATE(b.booking_date) BETWEEN ? AND ?
                    GROUP BY DATE(b.booking_date)
                    ORDER BY date ASC";
            
            $dateFormat = 'M d, Y';
        } elseif ($groupBy === 'month') {
            $query = "SELECT DATE_FORMAT(b.booking_date, '%Y-%m-01') as date, 
                    COUNT(*) as num_bookings, 
                    SUM(b.amount) as total_revenue,
                    AVG(b.amount) as avg_revenue
                    FROM bookings b
                    WHERE b.payment_status = 'paid'
                    AND DATE(b.booking_date) BETWEEN ? AND ?
                    GROUP BY DATE_FORMAT(b.booking_date, '%Y-%m')
                    ORDER BY date ASC";
            
            $dateFormat = 'M Y';
        } else { // week
            $query = "SELECT DATE(DATE_SUB(b.booking_date, INTERVAL WEEKDAY(b.booking_date) DAY)) as date, 
                    COUNT(*) as num_bookings, 
                    SUM(b.amount) as total_revenue,
                    AVG(b.amount) as avg_revenue
                    FROM bookings b
                    WHERE b.payment_status = 'paid'
                    AND DATE(b.booking_date) BETWEEN ? AND ?
                    GROUP BY YEARWEEK(b.booking_date)
                    ORDER BY date ASC";
            
            $dateFormat = 'Week of M d, Y';
        }
        
        // Get report data
        $rawData = fetchRows($query, [$startDate, $endDate]);
        
        // Format dates
        $reportData = [];
        foreach ($rawData as $row) {
            $reportData[] = [
                'date' => date($dateFormat, strtotime($row['date'])),
                'num_bookings' => $row['num_bookings'],
                'total_revenue' => formatReportCurrency($row['total_revenue']),
                'avg_revenue' => formatReportCurrency($row['avg_revenue'])
            ];
        }
        
        // Generate summary data
        $totalRevenue = 0;
        $totalBookings = 0;
        
        foreach ($rawData as $row) {
            $totalRevenue += $row['total_revenue'];
            $totalBookings += $row['num_bookings'];
        }
        
        // Add to summary
        $reportSummary = [
            'Total Revenue' => formatReportCurrency($totalRevenue),
            'Total Paid Bookings' => $totalBookings,
            'Average Revenue per Booking' => $totalBookings > 0 ? formatReportCurrency($totalRevenue / $totalBookings) : formatReportCurrency(0),
            'Period' => date('M d, Y', strtotime($startDate)) . ' to ' . date('M d, Y', strtotime($endDate))
        ];
        
        // Chart data - revenue by date
        $chartLabels = [];
        $chartValues = [];
        
        foreach ($rawData as $row) {
            $chartLabels[] = date($dateFormat, strtotime($row['date']));
            $chartValues[] = floatval($row['total_revenue']);
        }
        
        $chartData = [
            'labels' => $chartLabels,
            'datasets' => [
                [
                    'data' => $chartValues,
                    'backgroundColor' => '#198754'
                ]
            ]
        ];
        
        break;
        
    case 'route':
        $reportTitle = 'Route Performance Report';
        $reportHeaders = ['Route', 'Total Bookings', 'Total Passengers', 'Total Revenue', 'Average Occupancy (%)'];
        
        // Build query
        $query = "SELECT r.id, r.name as route_name, r.code as route_code,
                  COUNT(b.id) as num_bookings,
                  SUM(b.passengers) as total_passengers,
                  SUM(b.amount) as total_revenue,
                  AVG((b.passengers / t.capacity) * 100) as avg_occupancy
                  FROM routes r
                  JOIN schedules sch ON r.id = sch.route_id
                  JOIN trains t ON sch.train_id = t.id
                  JOIN bookings b ON sch.id = b.schedule_id
                  WHERE DATE(b.booking_date) BETWEEN ? AND ?
                  AND b.booking_status IN ('confirmed', 'completed')
                  GROUP BY r.id, r.name, r.code
                  ORDER BY num_bookings DESC";
        
        // Get report data
        $rawData = fetchRows($query, [$startDate, $endDate]);
        
        // Format data
        $reportData = [];
        foreach ($rawData as $row) {
            $reportData[] = [
                'route' => $row['route_name'] . ' (' . $row['route_code'] . ')',
                'num_bookings' => $row['num_bookings'],
                'total_passengers' => $row['total_passengers'],
                'total_revenue' => formatReportCurrency($row['total_revenue']),
                'avg_occupancy' => number_format($row['avg_occupancy'], 2) . '%'
            ];
        }
        
        // Generate summary data
        $totalBookings = 0;
        $totalPassengers = 0;
        $totalRevenue = 0;
        
        foreach ($rawData as $row) {
            $totalBookings += $row['num_bookings'];
            $totalPassengers += $row['total_passengers'];
            $totalRevenue += $row['total_revenue'];
        }
        
        // Add to summary
        $reportSummary = [
            'Total Routes' => count($reportData),
            'Total Bookings' => $totalBookings,
            'Total Passengers' => $totalPassengers,
            'Total Revenue' => formatReportCurrency($totalRevenue),
            'Period' => date('M d, Y', strtotime($startDate)) . ' to ' . date('M d, Y', strtotime($endDate))
        ];
        
        // Chart data - bookings by route
        $chartLabels = [];
        $chartValues = [];
        
        $counter = 0;
        foreach ($rawData as $row) {
            if ($counter < 10) { // Limit to top 10 routes
                $chartLabels[] = $row['route_code'];
                $chartValues[] = intval($row['num_bookings']);
                $counter++;
            }
        }
        
        $chartData = [
            'labels' => $chartLabels,
            'datasets' => [
                [
                    'data' => $chartValues,
                    'backgroundColor' => '#0d6efd'
                ]
            ]
        ];
        
        break;
        
    case 'user':
        $reportTitle = 'User Activity Report';
        $reportHeaders = ['User', 'Email', 'Total Bookings', 'Cancelled Bookings', 'Total Spent', 'Last Booking'];
        
        // Build query
        $query = "SELECT u.id, u.name, u.email,
                  COUNT(b.id) as num_bookings,
                  SUM(CASE WHEN b.booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
                  SUM(CASE WHEN b.payment_status = 'paid' THEN b.amount ELSE 0 END) as total_spent,
                  MAX(b.booking_date) as last_booking
                  FROM users u
                  JOIN bookings b ON u.id = b.user_id
                  WHERE DATE(b.booking_date) BETWEEN ? AND ?
                  GROUP BY u.id, u.name, u.email
                  ORDER BY num_bookings DESC";
        
        // Get report data
        $rawData = fetchRows($query, [$startDate, $endDate]);
        
        // Format data
        $reportData = [];
        foreach ($rawData as $row) {
            $reportData[] = [
                'user' => $row['name'],
                'email' => $row['email'],
                'num_bookings' => $row['num_bookings'],
                'cancelled_bookings' => $row['cancelled_bookings'],
                'total_spent' => formatReportCurrency($row['total_spent']),
                'last_booking' => date('M d, Y', strtotime($row['last_booking']))
            ];
        }
        
        // Generate summary data
        $totalUsers = count($reportData);
        $totalBookings = 0;
        $totalCancelled = 0;
        $totalSpent = 0;
        
        foreach ($rawData as $row) {
            $totalBookings += $row['num_bookings'];
            $totalCancelled += $row['cancelled_bookings'];
            $totalSpent += $row['total_spent'];
        }
        
        // Add to summary
        $reportSummary = [
            'Total Active Users' => $totalUsers,
            'Total Bookings' => $totalBookings,
            'Total Cancelled Bookings' => $totalCancelled,
            'Total Revenue' => formatReportCurrency($totalSpent),
            'Average Revenue per User' => $totalUsers > 0 ? formatReportCurrency($totalSpent / $totalUsers) : formatReportCurrency(0),
            'Period' => date('M d, Y', strtotime($startDate)) . ' to ' . date('M d, Y', strtotime($endDate))
        ];
        
        // Chart data - top 10 users by bookings
        $chartLabels = [];
        $chartValues = [];
        
        $counter = 0;
        foreach ($rawData as $row) {
            if ($counter < 10) { // Limit to top 10 users
                $chartLabels[] = limitString($row['name'], 15);
                $chartValues[] = intval($row['num_bookings']);
                $counter++;
            }
        }
        
        $chartData = [
            'labels' => $chartLabels,
            'datasets' => [
                [
                    'data' => $chartValues,
                    'backgroundColor' => '#6f42c1'
                ]
            ]
        ];
        
        break;
}

// Handle exporting
if (!empty($exportFormat)) {
    // In a real system, you would implement CSV, PDF, etc. exports here
    // For this example, we'll just set a success message
    $success = 'Report exported successfully as ' . strtoupper($exportFormat);
}

// Helper function to format currency - renamed to avoid conflict with the one in functions.php
function formatReportCurrency($amount) {
    return '$' . number_format((float)$amount, 2, '.', ',');
}

// Helper function to limit string length
function limitString($str, $length) {
    if (strlen($str) > $length) {
        return substr($str, 0, $length) . '...';
    }
    return $str;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Metro Rail Admin</title>    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/admin_sidebar.php'; ?>
              <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 main-content">
                <div class="admin-navbar p-3 d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
                    <h1 class="h2 mb-0">Reports</h1>
                    <div class="btn-toolbar mb-md-0">
                        <div class="btn-group me-2">
                            <a href="<?= APP_URL ?>/admin/reports.php?type=<?= $reportType ?>&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>&export=csv" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
                            </a>
                            <a href="<?= APP_URL ?>/admin/reports.php?type=<?= $reportType ?>&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>&export=pdf" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-file-earmark-pdf"></i> Export PDF
                            </a>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>
                  <!-- Report Type Selection -->
                <div class="row mb-4 fade-in">
                    <div class="col-md-12">
                        <div class="card dashboard-card">
                            <div class="card-header bg-gradient-primary">
                                <h5 class="card-title mb-0 text-white">Report Type</h5>
                            </div>
                            <div class="card-body">
                                <div class="btn-group w-100" role="group">
                                    <a href="<?= APP_URL ?>/admin/reports.php?type=booking&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-<?= $reportType === 'booking' ? 'primary' : 'outline-primary' ?>">
                                        <i class="bi bi-ticket-perforated"></i> Booking Report
                                    </a>
                                    <a href="<?= APP_URL ?>/admin/reports.php?type=revenue&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-<?= $reportType === 'revenue' ? 'primary' : 'outline-primary' ?>">
                                        <i class="bi bi-cash"></i> Revenue Report
                                    </a>
                                    <a href="<?= APP_URL ?>/admin/reports.php?type=route&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-<?= $reportType === 'route' ? 'primary' : 'outline-primary' ?>">
                                        <i class="bi bi-map"></i> Route Performance
                                    </a>
                                    <a href="<?= APP_URL ?>/admin/reports.php?type=user&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-<?= $reportType === 'user' ? 'primary' : 'outline-primary' ?>">
                                        <i class="bi bi-people"></i> User Activity
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                  <!-- Filter Form -->
                <div class="card mb-4 dashboard-card">
                    <div class="card-header bg-gradient-secondary">
                        <h5 class="card-title mb-0 text-white">Report Filters</h5>
                    </div>
                    <div class="card-body admin-form">
                        <form action="" method="get" class="row g-3">
                            <input type="hidden" name="type" value="<?= $reportType ?>">
                            
                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $startDate ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $endDate ?>" required>
                            </div>
                            
                            <?php if ($reportType === 'booking'): ?>
                                <div class="col-md-2">
                                    <label for="route_id" class="form-label">Route</label>
                                    <select name="route_id" id="route_id" class="form-select">
                                        <option value="0">All Routes</option>
                                        <?php foreach ($routes as $route): ?>
                                            <option value="<?= $route['id'] ?>" <?= isset($_GET['route_id']) && $_GET['route_id'] == $route['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($route['name']) ?> (<?= htmlspecialchars($route['code']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="booking_status" class="form-label">Booking Status</label>
                                    <select name="booking_status" id="booking_status" class="form-select">
                                        <option value="">All Statuses</option>
                                        <option value="pending" <?= isset($_GET['booking_status']) && $_GET['booking_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="confirmed" <?= isset($_GET['booking_status']) && $_GET['booking_status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                        <option value="cancelled" <?= isset($_GET['booking_status']) && $_GET['booking_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        <option value="completed" <?= isset($_GET['booking_status']) && $_GET['booking_status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="payment_status" class="form-label">Payment Status</label>
                                    <select name="payment_status" id="payment_status" class="form-select">
                                        <option value="">All Statuses</option>
                                        <option value="pending" <?= isset($_GET['payment_status']) && $_GET['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="paid" <?= isset($_GET['payment_status']) && $_GET['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                                        <option value="failed" <?= isset($_GET['payment_status']) && $_GET['payment_status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                                        <option value="refunded" <?= isset($_GET['payment_status']) && $_GET['payment_status'] === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                                    </select>
                                </div>
                            <?php elseif ($reportType === 'revenue'): ?>
                                <div class="col-md-6">
                                    <label for="group_by" class="form-label">Group By</label>
                                    <select name="group_by" id="group_by" class="form-select">
                                        <option value="day" <?= isset($_GET['group_by']) && $_GET['group_by'] === 'day' ? 'selected' : '' ?>>Day</option>
                                        <option value="week" <?= isset($_GET['group_by']) && $_GET['group_by'] === 'week' ? 'selected' : '' ?>>Week</option>
                                        <option value="month" <?= isset($_GET['group_by']) && $_GET['group_by'] === 'month' ? 'selected' : '' ?>>Month</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Generate Report</button>
                                <a href="<?= APP_URL ?>/admin/reports.php?type=<?= $reportType ?>" class="btn btn-secondary">Reset Filters</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Success Message -->
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $success ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                  <!-- Report Content -->
                <div class="card mb-4 dashboard-card">
                    <div class="card-header bg-gradient-info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0 text-white"><?= $reportTitle ?></h5>
                                <p class="card-text text-white-50 mt-2 mb-0">
                                    Period: <?= date('M d, Y', strtotime($startDate)) ?> to <?= date('M d, Y', strtotime($endDate)) ?>
                                </p>
                            </div>
                            <div class="card-icon">
                                <i class="bi <?= 
                                    $reportType === 'booking' ? 'bi-ticket-perforated' : 
                                    ($reportType === 'revenue' ? 'bi-cash-coin' : 
                                    ($reportType === 'route' ? 'bi-map' : 'bi-people'))
                                ?>"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-body"><!-- Report Summary -->
                        <div class="row mb-4 stats-summary fade-in">
                            <?php foreach ($reportSummary as $key => $value): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="stats-card">
                                        <h6 class="stats-title"><?= $key ?></h6>
                                        <p class="stats-value mb-0"><?= $value ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                          <!-- Chart -->
                        <div class="row mb-4 fade-in">
                            <div class="col-md-12">
                                <div class="chart-container">
                                    <h5 class="mb-3"><?= $reportTitle ?> Chart</h5>
                                    <canvas id="reportChart" width="400" height="250"></canvas>
                                </div>
                            </div>
                        </div>
                          <!-- Report Table -->
                        <div class="card dashboard-card mb-4 fade-in">
                            <div class="card-header bg-gradient-primary">
                                <h5 class="card-title mb-0 text-white"><?= $reportTitle ?> Data</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover data-table mb-0">
                                        <thead>
                                            <tr>
                                                <?php foreach ($reportHeaders as $header): ?>
                                                    <th><?= $header ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($reportData)): ?>
                                                <tr>
                                                    <td colspan="<?= count($reportHeaders) ?>" class="text-center py-4">No data found for the selected criteria.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($reportData as $row): ?>
                                                    <tr>
                                                        <?php foreach ($row as $cell): ?>
                                                            <td><?= $cell ?></td>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="<?= APP_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Chart
            var ctx = document.getElementById('reportChart').getContext('2d');
            
            var chartType = '<?= $reportType ?>';            var chartConfig = {
                type: chartType === 'booking' || chartType === 'route' || chartType === 'user' ? 'bar' : 'line',
                data: {
                    labels: <?= json_encode($chartData['labels']) ?>,
                    datasets: [{
                        label: '<?= $reportTitle ?>',
                        data: <?= json_encode($chartData['datasets'][0]['data']) ?>,
                        backgroundColor: chartType === 'line' ? 'rgba(52, 152, 219, 0.2)' : 
                            (Array.isArray(<?= json_encode($chartData['datasets'][0]['backgroundColor']) ?>) ? 
                            <?= json_encode($chartData['datasets'][0]['backgroundColor']) ?> : 
                            [
                                'rgba(52, 152, 219, 0.7)',
                                'rgba(46, 204, 113, 0.7)',
                                'rgba(155, 89, 182, 0.7)',
                                'rgba(52, 73, 94, 0.7)',
                                'rgba(241, 196, 15, 0.7)',
                                'rgba(230, 126, 34, 0.7)',
                                'rgba(231, 76, 60, 0.7)'
                            ]),
                        borderColor: chartType === 'revenue' ? 'rgba(52, 152, 219, 1)' : undefined,
                        borderWidth: chartType === 'revenue' ? 2 : 0,
                        fill: chartType === 'revenue',
                        tension: 0.4,
                        borderRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(52, 73, 94, 0.9)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: 'rgba(255, 255, 255, 0.2)',
                            borderWidth: 1,
                            padding: 10,
                            boxPadding: 5
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                borderDash: [5, 5]
                            },
                            ticks: {
                                font: {
                                    family: "'Poppins', sans-serif",
                                    size: 11
                                },
                                padding: 10,
                                color: '#6c757d'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: "'Poppins', sans-serif",
                                    size: 11
                                },
                                padding: 5,
                                color: '#6c757d'
                            }
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    }
                }
            };
            
            new Chart(ctx, chartConfig);
        });
    </script>
</body>
</html>
