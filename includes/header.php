<?php
// Make sure nothing is sent before headers
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if necessary files are already included
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}
if (!function_exists('executeQuery')) {
    require_once __DIR__ . '/db.php';
}
if (!function_exists('redirect')) {
    require_once __DIR__ . '/functions.php';
}

// Check for session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    // Session has expired
    session_unset();
    session_destroy();
    redirect(APP_URL . '/login.php?timeout=1');
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Get current page
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metro Rail - <?php echo isset($pageTitle) ? $pageTitle : 'Welcome'; ?></title>    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo APP_URL; ?>/css/style.css" rel="stylesheet">
    <!-- Modern UI CSS -->
    <link href="<?php echo APP_URL; ?>/css/modern.css" rel="stylesheet">
    <!-- Pages CSS -->
    <link href="<?php echo APP_URL; ?>/css/pages.css" rel="stylesheet">    <!-- Booking CSS -->
    <link href="<?php echo APP_URL; ?>/css/booking.css?v=<?php echo time(); ?>" rel="stylesheet">
    <!-- User CSS -->
    <link href="<?php echo APP_URL; ?>/css/user.css?v=<?php echo time(); ?>" rel="stylesheet">
    
    <?php if (isset($extraCss)): ?>
        <?php foreach ($extraCss as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo APP_URL; ?>">
                <i class="fas fa-train me-2"></i>Metro Rail
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>">
                            Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'schedule.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/schedules.php">
                            Schedules
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'stations.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/stations.php">
                            Stations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'fare.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/fare.php">
                            Fare Calculator
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'contact.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/contact.php">
                            Contact Us
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/admin">
                                    Admin Panel
                                </a>
                            </li>
                        <?php elseif (isStaff()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/staff">
                                    Staff Panel
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/user">
                                    My Dashboard
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li>
                                    <a class="dropdown-item" href="<?php echo APP_URL; ?>/user/profile.php">
                                        <i class="fas fa-user me-2"></i>Profile
                                    </a>
                                </li>
                                <?php if (!isStaff() && !isAdmin()): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo APP_URL; ?>/user/booking.php">
                                            <i class="fas fa-ticket-alt me-2"></i>Book Ticket
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo APP_URL; ?>/user/history.php">
                                            <i class="fas fa-history me-2"></i>Booking History
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo APP_URL; ?>/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage === 'login.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage === 'register.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/register.php">
                                <i class="fas fa-user-plus me-1"></i>Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container my-4">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['flash_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>
