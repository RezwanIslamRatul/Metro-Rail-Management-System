<?php
// Include necessary files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session
session_start();

// Get all active stations
$stations = fetchRows("SELECT * FROM stations WHERE status = 'active' ORDER BY name ASC");

// Initialize variables
$errors = [];
$fareDetails = null;

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $fromStationId = isset($_POST['from_station_id']) ? sanitizeInput($_POST['from_station_id']) : '';
    $toStationId = isset($_POST['to_station_id']) ? sanitizeInput($_POST['to_station_id']) : '';
    
    // Validate form data
    if (empty($fromStationId)) {
        $errors[] = 'Please select a departure station';
    }
    
    if (empty($toStationId)) {
        $errors[] = 'Please select an arrival station';
    } elseif ($fromStationId === $toStationId) {
        $errors[] = 'Departure and arrival stations cannot be the same';
    }
    
    // If no errors, calculate fare
    if (empty($errors)) {
        // Get fare from database
        $fare = fetchRow("
            SELECT f.*, r.name as route_name, 
                   s1.name as from_station_name, s1.code as from_station_code,
                   s2.name as to_station_name, s2.code as to_station_code
            FROM fares f
            JOIN routes r ON f.route_id = r.id
            JOIN stations s1 ON f.from_station_id = s1.id
            JOIN stations s2 ON f.to_station_id = s2.id
            WHERE f.from_station_id = ? AND f.to_station_id = ?
        ", [$fromStationId, $toStationId]);
        
        if (!$fare) {
            // If fare not found, calculate based on distance
            $fromStation = fetchRow("SELECT * FROM stations WHERE id = ?", [$fromStationId]);
            $toStation = fetchRow("SELECT * FROM stations WHERE id = ?", [$toStationId]);
            
            $distance = calculateDistance(
                $fromStation['latitude'], 
                $fromStation['longitude'], 
                $toStation['latitude'], 
                $toStation['longitude']
            );
            
            $amount = calculateFare($distance);
            
            // Create fareDetails array
            $fareDetails = [
                'from_station_name' => $fromStation['name'],
                'from_station_code' => $fromStation['code'],
                'to_station_name' => $toStation['name'],
                'to_station_code' => $toStation['code'],
                'distance' => round($distance, 2),
                'amount' => $amount,
                'calculated' => true
            ];
        } else {
            // Use fare from database
            $fareDetails = [
                'from_station_name' => $fare['from_station_name'],
                'from_station_code' => $fare['from_station_code'],
                'to_station_name' => $fare['to_station_name'],
                'to_station_code' => $fare['to_station_code'],
                'route_name' => $fare['route_name'],
                'amount' => $fare['amount'],
                'calculated' => false
            ];
        }
    }
}

// Set page title
$pageTitle = 'Fare Calculator';

// Include header
require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold"><i class="fas fa-calculator me-2"></i>Fare Calculator</h1>
                <p class="lead">Calculate ticket fares between any two stations in our network.</p>
            </div>
            <div class="col-lg-6">
                <img src="images/fare-illustration.svg" class="img-fluid" alt="Fare Illustration" onerror="this.style.display='none'">
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="fare-card">
                <div class="fare-header">
                    <h4 class="fare-title"><i class="fas fa-calculator me-2"></i>Fare Calculator</h4>
                    <p class="fare-subtitle">Select your departure and arrival stations to calculate the fare</p>
                </div>
                <div class="fare-form">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-modern">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                      <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="fare-form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3 fare-station-select">
                                    <select class="form-select modern-select" id="from_station_id" name="from_station_id" required>
                                        <option value="" selected disabled>Select departure station</option>
                                        <?php foreach ($stations as $station): ?>
                                            <option value="<?php echo $station['id']; ?>" <?php echo isset($_POST['from_station_id']) && $_POST['from_station_id'] == $station['id'] ? 'selected' : ''; ?>>
                                                <?php echo $station['name']; ?> (<?php echo $station['code']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="from_station_id">From Station</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3 fare-station-select">
                                    <select class="form-select modern-select" id="to_station_id" name="to_station_id" required>
                                        <option value="" selected disabled>Select arrival station</option>
                                        <?php foreach ($stations as $station): ?>
                                            <option value="<?php echo $station['id']; ?>" <?php echo isset($_POST['to_station_id']) && $_POST['to_station_id'] == $station['id'] ? 'selected' : ''; ?>>
                                                <?php echo $station['name']; ?> (<?php echo $station['code']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="to_station_id">To Station</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fare-button">
                                <i class="fas fa-calculator me-2"></i>Calculate Fare
                            </button>
                        </div>
                    </form>
                      <?php if ($fareDetails): ?>
                        <div class="fare-result mt-4">
                            <h5 class="mb-3">Fare Details</h5>
                            
                            <div class="fare-details-grid">
                                <div class="fare-detail-item">
                                    <span class="fare-detail-label">From Station</span>
                                    <span class="fare-detail-value"><?php echo $fareDetails['from_station_name']; ?> (<?php echo $fareDetails['from_station_code']; ?>)</span>
                                </div>
                                <div class="fare-detail-item">
                                    <span class="fare-detail-label">To Station</span>
                                    <span class="fare-detail-value"><?php echo $fareDetails['to_station_name']; ?> (<?php echo $fareDetails['to_station_code']; ?>)</span>
                                </div>
                                <?php if (isset($fareDetails['route_name'])): ?>
                                <div class="fare-detail-item">
                                    <span class="fare-detail-label">Route</span>
                                    <span class="fare-detail-value"><?php echo $fareDetails['route_name']; ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (isset($fareDetails['distance'])): ?>
                                <div class="fare-detail-item">
                                    <span class="fare-detail-label">Distance</span>
                                    <span class="fare-detail-value"><?php echo $fareDetails['distance']; ?> km</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="fare-amount">
                                <div class="fare-amount-label">Fare Amount</div>
                                <div class="fare-amount-value"><?php echo formatCurrency($fareDetails['amount']); ?></div>
                                <?php if ($fareDetails['calculated']): ?>
                                <div class="fare-note">*Calculated based on distance</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="fare-actions">
                                <a href="schedules.php?from=<?php echo $fromStationId; ?>&to=<?php echo $toStationId; ?>" class="btn btn-outline-primary action-button" data-bs-toggle="tooltip" title="Find train schedules for this route">
                                    <i class="fas fa-clock me-2"></i>Find Schedules for This Route
                                </a>
                                <?php if (isLoggedIn()): ?>
                                    <a href="user/booking.php?from=<?php echo $fromStationId; ?>&to=<?php echo $toStationId; ?>" class="btn btn-success action-button" data-bs-toggle="tooltip" title="Proceed to booking">
                                        <i class="fas fa-ticket-alt me-2"></i>Book Ticket Now
                                    </a>
                                <?php else: ?>
                                    <a href="login.php?redirect=<?php echo urlencode('user/booking.php?from=' . $fromStationId . '&to=' . $toStationId); ?>" class="btn btn-success action-button" data-bs-toggle="tooltip" title="Login to book a ticket">
                                        <i class="fas fa-sign-in-alt me-2"></i>Login to Book Ticket
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
