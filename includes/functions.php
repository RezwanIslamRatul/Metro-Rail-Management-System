<?php
/**
 * Helper Functions
 */

/**
 * Sanitize user input
 * 
 * @param string $input
 * @return string
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL
 * 
 * @param string $url
 * @return void
 */
function redirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
    } else {
        echo "<script>window.location.href='$url';</script>";
    }
    exit;
}

/**
 * Check if user is logged in
 * 
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has a specific role
 * 
 * @param string $role
 * @return bool
 */
function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    // Add debug logging
    $log_dir = 'logs';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    $debug_file = $log_dir . '/role_debug.txt';
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Checking role: " . $role . "\n", FILE_APPEND);
    
    if (!isset($_SESSION['user_role'])) {
        file_put_contents($debug_file, "SESSION user_role is not set!\n", FILE_APPEND);
        return false;
    }
    
    file_put_contents($debug_file, "SESSION user_role: " . $_SESSION['user_role'] . "\n", FILE_APPEND);
    
    // Compare case-insensitive to avoid common issues
    return strcasecmp($_SESSION['user_role'], $role) === 0;
}

/**
 * Log detailed debug information to a file
 * 
 * @param string $message The message to log
 * @param array|object $data Optional data to log
 * @param string $filename The filename to log to
 * @return void
 */
function debug_log($message, $data = null, $filename = 'debug.log') {
    // Check if we are in the admin directory
    $is_admin = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
    
    // Create logs directory if it doesn't exist
    $log_dir = $is_admin ? '../logs' : 'logs';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/' . $filename;
    
    $log_data = date('Y-m-d H:i:s') . " - " . $message . "\n";
    
    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $log_data .= print_r($data, true) . "\n";
        } else {
            $log_data .= $data . "\n";
        }
    }
    
    file_put_contents($log_file, $log_data, FILE_APPEND);
}

/**
 * Check if current user is an admin
 * 
 * @return bool
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Check if current user is a staff
 * 
 * @return bool
 */
function isStaff() {
    return hasRole('staff');
}

/**
 * Get logged in user data
 * 
 * @return array|null
 */
function getLoggedInUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $userId = $_SESSION['user_id'];
    
    return fetchRow("SELECT * FROM users WHERE id = ?", [$userId]);
}

/**
 * Generate a random string
 * 
 * @param int $length
 * @return string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $index = rand(0, strlen($characters) - 1);
        $randomString .= $characters[$index];
    }
    
    return $randomString;
}

/**
 * Display error message
 * 
 * @param string $message
 * @return string
 */
function showError($message) {
    return '<div class="alert alert-danger" role="alert">' . $message . '</div>';
}

/**
 * Display success message
 * 
 * @param string $message
 * @return string
 */
function showSuccess($message) {
    return '<div class="alert alert-success" role="alert">' . $message . '</div>';
}

/**
 * Display info message
 * 
 * @param string $message
 * @return string
 */
function showInfo($message) {
    return '<div class="alert alert-info" role="alert">' . $message . '</div>';
}

/**
 * Format date to a readable format
 * 
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'M d, Y h:i A') {
    return date($format, strtotime($date));
}

/**
 * Format currency
 * 
 * @param float $amount
 * @param string $currency
 * @return string
 */
function formatCurrency($amount, $currency = '$') {
    return $currency . number_format($amount, 2);
}

/**
 * Generate pagination links
 * 
 * @param int $currentPage
 * @param int $totalPages
 * @param string $url
 * @return string
 */
function generatePagination($currentPage, $totalPages, $url) {
    $pagination = '<nav aria-label="Page navigation"><ul class="pagination">';
    
    // Previous button
    if ($currentPage > 1) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . ($currentPage - 1) . '">Previous</a></li>';
    } else {
        $pagination .= '<li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>';
    }
    
    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == $currentPage) {
            $pagination .= '<li class="page-item active"><a class="page-link" href="#">' . $i . '</a></li>';
        } else {
            $pagination .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . ($currentPage + 1) . '">Next</a></li>';
    } else {
        $pagination .= '<li class="page-item disabled"><a class="page-link" href="#">Next</a></li>';
    }
    
    $pagination .= '</ul></nav>';
    
    return $pagination;
}

/**
 * Calculate distance between two coordinates
 * 
 * @param float $lat1
 * @param float $lon1
 * @param float $lat2
 * @param float $lon2
 * @return float
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    
    // Convert to kilometers
    return $miles * 1.609344;
}

/**
 * Calculate fare based on distance
 * 
 * @param float $distance
 * @param float $baseRate
 * @param float $perKmRate
 * @return float
 */
function calculateFare($distance, $baseRate = 10, $perKmRate = 5) {
    return $baseRate + ($distance * $perKmRate);
}

/**
 * Generate QR code for ticket
 * 
 * @param string $data
 * @return string
 */
function generateQRCode($data) {
    // Placeholder function - in a real project, you would use a QR code library
    return 'QR code for: ' . $data;
}

/**
 * Send email notification
 * 
 * @param string $to
 * @param string $subject
 * @param string $message
 * @return bool
 */
function sendEmail($to, $subject, $message) {
    // Placeholder function - in a real project, you would use a proper email library
    $headers = 'From: noreply@metrorail.com' . "\r\n" .
               'Reply-To: noreply@metrorail.com' . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Log system activity
 * 
 * @param string $action
 * @param string $description
 * @param int $userId
 * @return bool
 */
function logActivity($action, $description, $userId = null) {
    if ($userId === null && isLoggedIn()) {
        $userId = $_SESSION['user_id'];
    }
    
    $data = [
        'user_id' => $userId,
        'action' => $action,
        'description' => $description,
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    return insert('activity_logs', $data);
}

/**
 * Set flash message to be displayed on next page load
 * 
 * @param string $type Type of message (success, error, info)
 * @param string $message The message to display
 * @return void
 */
function setFlashMessage($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    
    if (!isset($_SESSION['flash_messages'][$type])) {
        $_SESSION['flash_messages'][$type] = [];
    }
    
    $_SESSION['flash_messages'][$type][] = $message;
}

/**
 * Display flash messages from session
 * 
 * @return void
 */
function displayFlashMessages() {
    if (isset($_SESSION['flash_messages']) && !empty($_SESSION['flash_messages'])) {
        foreach ($_SESSION['flash_messages'] as $type => $messages) {
            foreach ($messages as $message) {
                switch ($type) {
                    case 'success':
                        echo showSuccess($message);
                        break;
                    case 'error':
                        echo showError($message);
                        break;
                    case 'info':
                        echo showInfo($message);
                        break;
                    default:
                        echo showInfo($message);
                        break;
                }
            }
        }
        // Clear flash messages after displaying them
        unset($_SESSION['flash_messages']);
    }
}

/**
 * Get flash message of specific type
 * 
 * @param string $type Type of message (success, error, info)
 * @return string|null The first message of specified type or null if none exists
 */
function getFlashMessage($type) {
    if (isset($_SESSION['flash_messages'][$type]) && !empty($_SESSION['flash_messages'][$type])) {
        $message = $_SESSION['flash_messages'][$type][0];
        // Remove the retrieved message
        array_shift($_SESSION['flash_messages'][$type]);
        // Remove the type array if empty
        if (empty($_SESSION['flash_messages'][$type])) {
            unset($_SESSION['flash_messages'][$type]);
        }
        return $message;
    }
    return null;
}
