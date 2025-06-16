<?php
// Include necessary files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = 'Welcome';

// Include header
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-7">
                <h1>Experience Modern City Transit</h1>
                <p class="lead">Fast, reliable, and comfortable urban transportation at your fingertips.</p>
                <p>Book your tickets online, check schedules, and enjoy hassle-free travel across the city with our modern metro rail network.</p>
                <div class="d-grid gap-2 d-md-flex justify-content-md-start mt-4">
                    <a href="schedules.php" class="btn btn-light btn-lg px-4 me-md-2">
                        <i class="fas fa-clock me-2"></i>View Schedules
                    </a>
                    <?php if (!isLoggedIn()): ?>
                        <a href="register.php" class="btn btn-outline-light btn-lg px-4">
                            <i class="fas fa-user-plus me-2"></i>Register Now
                        </a>
                    <?php else: ?>
                        <a href="user/booking.php" class="btn btn-outline-light btn-lg px-4">
                            <i class="fas fa-ticket-alt me-2"></i>Book Ticket
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-5 d-none d-md-block text-end">
                <img src="images/metro-train.png" alt="Metro Train" class="img-fluid rounded shadow-lg">
            </div>
        </div>
    </div>
</div>

<!-- Quick Booking Section -->
<div class="container py-5">
    <div class="search-box booking-form-blue">
        <h3 class="mb-4 text-primary"><i class="fas fa-ticket-alt me-2"></i>Quick Booking</h3>
        <form action="<?php echo isLoggedIn() ? 'user/booking.php' : 'login.php'; ?>" method="GET">
            <div class="row g-3">
                <div class="col-md-3 mb-3">
                    <label for="from-station" class="form-label">From</label>
                    <select class="form-select" id="from-station" name="from" required>
                        <option value="" selected disabled>Select departure station</option>
                        <?php
                        // Get stations from database
                        $stations = fetchRows("SELECT * FROM stations WHERE status = 'active' ORDER BY name ASC");
                        foreach ($stations as $station): 
                        ?>
                            <option value="<?php echo $station['id']; ?>">
                                <?php echo $station['name']; ?> (<?php echo $station['code']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="to-station" class="form-label">To</label>
                    <select class="form-select" id="to-station" name="to" required>
                        <option value="" selected disabled>Select arrival station</option>
                        <?php foreach ($stations as $station): ?>
                            <option value="<?php echo $station['id']; ?>">
                                <?php echo $station['name']; ?> (<?php echo $station['code']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="journey-date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="journey-date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                </div>                <div class="col-md-3 mb-3">
                    <label for="passengers" class="form-label">Passengers</label>
                    <select class="form-select" id="passengers" name="passengers" required>
                        <option value="1" selected>1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                </div>
            </div>
            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-search me-2"></i>Find Trains
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Features Section -->
<div class="container py-5 bg-light rounded-3">
    <div class="text-center mb-5">
        <h2>Why Choose Metro Rail</h2>
        <p class="lead text-muted">Experience the best in urban transportation with our modern features</p>
    </div>
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="feature-box">
                <div class="feature-icon">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <h4>Extensive Network</h4>
                <p>Our metro rail network covers all major areas of the city with convenient connections and transfers between stations.</p>
                <a href="stations.php" class="btn btn-sm btn-outline-primary mt-2">View Network Map</a>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="feature-box">
                <div class="feature-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h4>Punctual Service</h4>
                <p>Our trains operate on a strict schedule, ensuring you reach your destination on time, every time with minimal delays.</p>
                <a href="schedules.php" class="btn btn-sm btn-outline-primary mt-2">Check Schedules</a>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="feature-box">
                <div class="feature-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h4>Digital Tickets</h4>
                <p>Book tickets online and receive digital QR codes for a contactless travel experience throughout our system.</p>
                <a href="<?php echo isLoggedIn() ? 'user/booking.php' : 'login.php'; ?>" class="btn btn-sm btn-outline-primary mt-2">Book Now</a>
            </div>
        </div>
    </div>
</div>

<!-- Latest Updates Section -->
<div class="card shadow mb-5">
    <div class="card-header bg-primary text-white">
        <h3><i class="fas fa-bullhorn me-2"></i>Latest Updates</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">New East Line Opening Next Month</h5>
                        <p class="card-text">The much-awaited East Line connecting Downtown to Tech Park will be operational from next month.</p>
                        <p class="text-muted"><small>Posted on May 15, 2025</small></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Weekend Maintenance Schedule</h5>
                        <p class="card-text">Planned maintenance work on the North Line this weekend. Check alternate routes.</p>                        <p class="text-muted"><small>Posted on May 16, 2025</small></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center mt-3">
            <a href="#" class="btn btn-outline-primary">View All Updates</a>
        </div>
    </div>
</div>

<!-- Popular Routes Section -->
<div class="card shadow mb-5">
    <div class="card-header bg-primary text-white">
        <h3><i class="fas fa-route me-2"></i>Popular Routes</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Route</th>
                        <th>Duration</th>
                        <th>Distance</th>
                        <th>Fare</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Central Station <i class="fas fa-arrow-right text-primary mx-2"></i> Business District</td>
                        <td>15 mins</td>
                        <td>7.5 km</td>
                        <td>$2.50</td>
                        <td><a href="user/booking.php?from=1&to=6" class="btn btn-sm btn-outline-primary">Book Now</a></td>
                    </tr>
                    <tr>
                        <td>North Terminal <i class="fas fa-arrow-right text-primary mx-2"></i> University</td>
                        <td>22 mins</td>
                        <td>11 km</td>
                        <td>$3.75</td>
                        <td><a href="user/booking.php?from=2&to=8" class="btn btn-sm btn-outline-primary">Book Now</a></td>
                    </tr>
                    <tr>
                        <td>Central Station <i class="fas fa-arrow-right text-primary mx-2"></i> Airport</td>
                        <td>35 mins</td>
                        <td>18 km</td>
                        <td>$5.50</td>
                        <td><a href="user/booking.php?from=1&to=10" class="btn btn-sm btn-outline-primary">Book Now</a></td>
                    </tr>
                    <tr>
                        <td>East Junction <i class="fas fa-arrow-right text-primary mx-2"></i> Shopping Mall</td>
                        <td>18 mins</td>
                        <td>9 km</td>
                        <td>$3.00</td>
                        <td><a href="user/booking.php?from=3&to=7" class="btn btn-sm btn-outline-primary">Book Now</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Testimonials Section -->
<div class="card shadow mb-5">
    <div class="card-header bg-primary text-white">
        <h3><i class="fas fa-comments me-2"></i>What Our Passengers Say</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-center mb-3">
                            <span class="text-warning">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </span>
                        </div>
                        <p class="card-text text-center">
                            "The online booking system is so convenient! I can book tickets from anywhere and the QR code tickets make travel seamless."
                        </p>
                        <p class="text-center mb-0"><strong>Sarah Johnson</strong></p>
                        <p class="text-muted text-center"><small>Regular Commuter</small></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-center mb-3">
                            <span class="text-warning">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </span>
                        </div>
                        <p class="card-text text-center">
                            "I've been using Metro Rail for my daily commute for over a year now. It's reliable, clean, and much faster than driving."
                        </p>
                        <p class="text-center mb-0"><strong>Michael Chen</strong></p>
                        <p class="text-muted text-center"><small>Daily Passenger</small></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-center mb-3">
                            <span class="text-warning">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </span>
                        </div>
                        <p class="card-text text-center">
                            "The fare calculator helps me plan my budget for travel expenses. The trains are always on time and the staff is friendly."
                        </p>
                        <p class="text-center mb-0"><strong>Emily Rodriguez</strong></p>
                        <p class="text-muted text-center"><small>Weekend Traveler</small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Call to Action -->
<div class="card bg-primary text-white shadow">
    <div class="card-body p-4 text-center">
        <h2 class="fw-bold">Ready to Experience Hassle-Free Travel?</h2>
        <p class="lead">Join thousands of satisfied passengers who rely on Metro Rail for their daily commute.</p>
        <?php if (!isLoggedIn()): ?>
            <a href="register.php" class="btn btn-light btn-lg px-4 me-md-2">
                <i class="fas fa-user-plus me-2"></i>Register Now
            </a>
            <a href="login.php" class="btn btn-outline-light btn-lg px-4">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </a>
        <?php else: ?>
            <a href="user/booking.php" class="btn btn-light btn-lg px-4">
                <i class="fas fa-ticket-alt me-2"></i>Book Your Journey
            </a>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
