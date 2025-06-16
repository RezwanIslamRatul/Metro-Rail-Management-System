<?php
// Include necessary files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session
session_start();

// Get all active stations
$stations = fetchRows("SELECT * FROM stations WHERE status = 'active' ORDER BY name ASC");

// Set page title
$pageTitle = 'Stations';

// Include header
require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold"><i class="fas fa-map-marker-alt me-2"></i>Metro Stations</h1>
                <p class="lead">View all available metro stations in our network.</p>
            </div>
            <div class="col-lg-6">
                <img src="images/stations-illustration.svg" class="img-fluid" alt="Stations Illustration" onerror="this.style.display='none'">
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <!-- Stations Map (placeholder) -->
    <div class="card modern-card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-map me-2"></i>Metro Network Map</h5>
        </div>
        <div class="card-body text-center">
            <div class="alert alert-info alert-modern">
                <i class="fas fa-info-circle me-2"></i>Interactive metro map will be available soon. Below is a list of all our stations.
            </div>
            <img src="images/metro-map-placeholder.jpg" alt="Metro Network Map" class="img-fluid rounded shadow" onerror="this.style.display='none'">
        </div>
    </div>
    
    <!-- Stations List -->
    <div class="card modern-card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Stations</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($stations)): ?>
                <div class="station-grid">
                    <?php foreach ($stations as $station): ?>
                        <div class="station-card-modern">
                            <span class="station-code-badge"><?php echo $station['code']; ?></span>
                            <h5 class="station-name-modern"><?php echo $station['name']; ?></h5>
                            <p class="station-address-modern">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo $station['address']; ?>
                            </p>
                            <div class="station-actions-modern">
                                <a href="schedules.php?from=<?php echo $station['id']; ?>" class="btn btn-outline-primary" data-bs-toggle="tooltip" title="View departures from this station">
                                    <i class="fas fa-train me-1"></i>Departures
                                </a>
                                <a href="schedules.php?to=<?php echo $station['id']; ?>" class="btn btn-outline-primary" data-bs-toggle="tooltip" title="View arrivals to this station">
                                    <i class="fas fa-train fa-flip-horizontal me-1"></i>Arrivals
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning alert-modern">
                    <i class="fas fa-exclamation-triangle me-2"></i>No stations found.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
