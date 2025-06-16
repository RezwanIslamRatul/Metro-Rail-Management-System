<!-- Sidebar -->
<div class="col-md-3 col-lg-2 d-md-block sidebar collapse admin-sidebar">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4">
            <h4 class="text-white">
                <i class="fas fa-train me-2"></i>Metro Rail
            </h4>
            <p class="text-white-50 small">Admin Panel</p>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/index.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'stations.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/stations.php">
                    <i class="fas fa-map-marker-alt"></i> Stations
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'trains.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/trains.php">
                    <i class="fas fa-train"></i> Trains
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'routes.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/routes.php">
                    <i class="fas fa-route"></i> Routes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'schedules.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/schedules.php">
                    <i class="fas fa-calendar-alt"></i> Schedules
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'fares.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/fares.php">
                    <i class="fas fa-money-bill-alt"></i> Fares
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/bookings.php">
                    <i class="fas fa-ticket-alt"></i> Bookings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/users.php">
                    <i class="fas fa-users"></i> Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'staff.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/staff.php">
                    <i class="fas fa-user-tie"></i> Staff
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'maintenance.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/maintenance.php">
                    <i class="fas fa-wrench"></i> Maintenance
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/announcements.php">
                    <i class="fas fa-bullhorn"></i> Announcements
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/reports.php">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/settings.php">
                    <i class="fas fa-cogs"></i> Settings
                </a>
            </li>
        </ul>
        
        <hr class="text-white-50">
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL; ?>/admin/profile.php">
                    <i class="fas fa-user"></i> Profile
                </a>
            </li>
            <li class="nav-item">
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
