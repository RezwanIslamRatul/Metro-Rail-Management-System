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
$settingsUpdated = false;

// Load current settings
$settings = [];
$settingsQuery = "SELECT * FROM settings";
try {
    $settings = fetchRows($settingsQuery);
    
    // Convert to key-value pairs
    $settingsArr = [];
    foreach ($settings as $setting) {
        $settingsArr[$setting['setting_key']] = $setting['setting_value'];
    }
    $settings = $settingsArr;
} catch (Exception $e) {
    // If settings table doesn't exist yet, create it
    $createTableQuery = "CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) NOT NULL UNIQUE,
        setting_value TEXT NOT NULL,
        setting_description TEXT,
        setting_group VARCHAR(50) DEFAULT 'general',
        created_at DATETIME NOT NULL,
        updated_at DATETIME DEFAULT NULL
    )";
    
    global $conn;
    $conn->query($createTableQuery);
    
    // Initialize default settings
    $defaultSettings = [
        'site_name' => 'Metro Rail',
        'site_description' => 'Book your train tickets online',
        'contact_email' => 'contact@metrorail.com',
        'contact_phone' => '123-456-7890',
        'booking_fee' => '10.00',
        'tax_rate' => '7.5',
        'enable_online_booking' => 'yes',
        'maintenance_mode' => 'no',
        'terms_and_conditions' => 'Default Terms and Conditions',
        'privacy_policy' => 'Default Privacy Policy',
        'cancellation_policy' => 'Default Cancellation Policy',
        'currency' => 'USD',
        'currency_symbol' => '$',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i',
        'timezone' => 'UTC',
        'google_analytics_id' => '',
        'smtp_host' => '',
        'smtp_port' => '',
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'tls',
        'mail_from_address' => 'noreply@metrorail.com',
        'mail_from_name' => 'Metro Rail'
    ];
    
    // Insert default settings
    foreach ($defaultSettings as $key => $value) {
        $description = str_replace('_', ' ', $key);
        $description = ucwords($description);
        
        // Determine group
        $group = 'general';
        if (strpos($key, 'smtp_') === 0 || strpos($key, 'mail_') === 0) {
            $group = 'email';
        } elseif (strpos($key, 'currency') === 0 || strpos($key, 'tax') === 0 || strpos($key, 'fee') === 0) {
            $group = 'payment';
        } elseif (strpos($key, 'policy') !== false || strpos($key, 'terms') !== false) {
            $group = 'legal';
        }
        
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, setting_description, setting_group, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param('ssss', $key, $value, $description, $group);
        $stmt->execute();
    }
    
    // Load settings again
    $settings = fetchRows($settingsQuery);
    
    // Convert to key-value pairs
    $settingsArr = [];
    foreach ($settings as $setting) {
        $settingsArr[$setting['setting_key']] = $setting['setting_value'];
    }
    $settings = $settingsArr;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_general'])) {
        // Update general settings
        $siteName = isset($_POST['site_name']) ? sanitizeInput($_POST['site_name']) : '';
        $siteDescription = isset($_POST['site_description']) ? sanitizeInput($_POST['site_description']) : '';
        $contactEmail = isset($_POST['contact_email']) ? sanitizeInput($_POST['contact_email']) : '';
        $contactPhone = isset($_POST['contact_phone']) ? sanitizeInput($_POST['contact_phone']) : '';
        $enableOnlineBooking = isset($_POST['enable_online_booking']) ? sanitizeInput($_POST['enable_online_booking']) : 'no';
        $maintenanceMode = isset($_POST['maintenance_mode']) ? sanitizeInput($_POST['maintenance_mode']) : 'no';
        
        // Validate data
        if (empty($siteName)) {
            $errors[] = 'Site name is required';
        }
        
        if (empty($contactEmail)) {
            $errors[] = 'Contact email is required';
        } elseif (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid contact email format';
        }
        
        if (empty($errors)) {
            // Update settings
            updateSetting('site_name', $siteName);
            updateSetting('site_description', $siteDescription);
            updateSetting('contact_email', $contactEmail);
            updateSetting('contact_phone', $contactPhone);
            updateSetting('enable_online_booking', $enableOnlineBooking);
            updateSetting('maintenance_mode', $maintenanceMode);
            
            // Set success message
            $success = 'General settings updated successfully';
            $settingsUpdated = true;
        }
    } elseif (isset($_POST['update_payment'])) {
        // Update payment settings
        $bookingFee = isset($_POST['booking_fee']) ? sanitizeInput($_POST['booking_fee']) : '';
        $taxRate = isset($_POST['tax_rate']) ? sanitizeInput($_POST['tax_rate']) : '';
        $currency = isset($_POST['currency']) ? sanitizeInput($_POST['currency']) : '';
        $currencySymbol = isset($_POST['currency_symbol']) ? sanitizeInput($_POST['currency_symbol']) : '';
        
        // Validate data
        if (!is_numeric($bookingFee) || $bookingFee < 0) {
            $errors[] = 'Booking fee must be a valid non-negative number';
        }
        
        if (!is_numeric($taxRate) || $taxRate < 0 || $taxRate > 100) {
            $errors[] = 'Tax rate must be a valid number between 0 and 100';
        }
        
        if (empty($errors)) {
            // Update settings
            updateSetting('booking_fee', $bookingFee);
            updateSetting('tax_rate', $taxRate);
            updateSetting('currency', $currency);
            updateSetting('currency_symbol', $currencySymbol);
            
            // Set success message
            $success = 'Payment settings updated successfully';
            $settingsUpdated = true;
        }
    } elseif (isset($_POST['update_email'])) {
        // Update email settings
        $smtpHost = isset($_POST['smtp_host']) ? sanitizeInput($_POST['smtp_host']) : '';
        $smtpPort = isset($_POST['smtp_port']) ? sanitizeInput($_POST['smtp_port']) : '';
        $smtpUsername = isset($_POST['smtp_username']) ? sanitizeInput($_POST['smtp_username']) : '';
        $smtpPassword = isset($_POST['smtp_password']) ? $_POST['smtp_password'] : ''; // Don't sanitize password
        $smtpEncryption = isset($_POST['smtp_encryption']) ? sanitizeInput($_POST['smtp_encryption']) : '';
        $mailFromAddress = isset($_POST['mail_from_address']) ? sanitizeInput($_POST['mail_from_address']) : '';
        $mailFromName = isset($_POST['mail_from_name']) ? sanitizeInput($_POST['mail_from_name']) : '';
        
        // Validate data
        if (!empty($smtpHost) && empty($smtpPort)) {
            $errors[] = 'SMTP port is required when host is provided';
        }
        
        if (!empty($smtpHost) && !is_numeric($smtpPort)) {
            $errors[] = 'SMTP port must be a number';
        }
        
        if (empty($mailFromAddress)) {
            $errors[] = 'From address is required';
        } elseif (!filter_var($mailFromAddress, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid from address format';
        }
        
        if (empty($mailFromName)) {
            $errors[] = 'From name is required';
        }
        
        if (empty($errors)) {
            // Update settings
            updateSetting('smtp_host', $smtpHost);
            updateSetting('smtp_port', $smtpPort);
            updateSetting('smtp_username', $smtpUsername);
            
            // Only update password if a new one is provided
            if (!empty($smtpPassword)) {
                updateSetting('smtp_password', $smtpPassword);
            }
            
            updateSetting('smtp_encryption', $smtpEncryption);
            updateSetting('mail_from_address', $mailFromAddress);
            updateSetting('mail_from_name', $mailFromName);
            
            // Set success message
            $success = 'Email settings updated successfully';
            $settingsUpdated = true;
        }
    } elseif (isset($_POST['update_date_time'])) {
        // Update date and time settings
        $dateFormat = isset($_POST['date_format']) ? sanitizeInput($_POST['date_format']) : '';
        $timeFormat = isset($_POST['time_format']) ? sanitizeInput($_POST['time_format']) : '';
        $timezone = isset($_POST['timezone']) ? sanitizeInput($_POST['timezone']) : '';
        
        // Validate data
        if (empty($dateFormat)) {
            $errors[] = 'Date format is required';
        }
        
        if (empty($timeFormat)) {
            $errors[] = 'Time format is required';
        }
        
        if (empty($timezone)) {
            $errors[] = 'Timezone is required';
        }
        
        if (empty($errors)) {
            // Update settings
            updateSetting('date_format', $dateFormat);
            updateSetting('time_format', $timeFormat);
            updateSetting('timezone', $timezone);
            
            // Set success message
            $success = 'Date and time settings updated successfully';
            $settingsUpdated = true;
        }
    } elseif (isset($_POST['update_legal'])) {
        // Update legal settings
        $termsAndConditions = isset($_POST['terms_and_conditions']) ? $_POST['terms_and_conditions'] : ''; // Don't sanitize HTML content
        $privacyPolicy = isset($_POST['privacy_policy']) ? $_POST['privacy_policy'] : ''; // Don't sanitize HTML content
        $cancellationPolicy = isset($_POST['cancellation_policy']) ? $_POST['cancellation_policy'] : ''; // Don't sanitize HTML content
        
        // Validate data
        if (empty($termsAndConditions)) {
            $errors[] = 'Terms and conditions are required';
        }
        
        if (empty($privacyPolicy)) {
            $errors[] = 'Privacy policy is required';
        }
        
        if (empty($cancellationPolicy)) {
            $errors[] = 'Cancellation policy is required';
        }
        
        if (empty($errors)) {
            // Update settings
            updateSetting('terms_and_conditions', $termsAndConditions);
            updateSetting('privacy_policy', $privacyPolicy);
            updateSetting('cancellation_policy', $cancellationPolicy);
            
            // Set success message
            $success = 'Legal settings updated successfully';
            $settingsUpdated = true;
        }
    } elseif (isset($_POST['update_password'])) {
        // Update admin password
        $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : ''; // Don't sanitize password
        $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : ''; // Don't sanitize password
        $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : ''; // Don't sanitize password
        
        // Validate data
        if (empty($currentPassword)) {
            $errors[] = 'Current password is required';
        } elseif (!password_verify($currentPassword, $admin['password'])) {
            $errors[] = 'Current password is incorrect';
        }
        
        if (empty($newPassword)) {
            $errors[] = 'New password is required';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New password and confirm password do not match';
        }
        
        if (empty($errors)) {
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            global $conn;
            $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param('si', $hashedPassword, $userId);
            $success = $stmt->execute();
            
            if ($success) {
                // Set success message
                $success = 'Password updated successfully';
            } else {
                $errors[] = 'Failed to update password';
            }
        }
    }
    
    // Reload settings if updated
    if ($settingsUpdated) {
        // Load settings again
        $settings = fetchRows($settingsQuery);
        
        // Convert to key-value pairs
        $settingsArr = [];
        foreach ($settings as $setting) {
            $settingsArr[$setting['setting_key']] = $setting['setting_value'];
        }
        $settings = $settingsArr;
        
        // Log activity
        logActivity('settings_updated', 'Updated system settings', $userId);
    }
}

// Helper function to update a setting
function updateSetting($key, $value) {
    global $conn;
    $stmt = $conn->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
    $stmt->bind_param('ss', $value, $key);
    return $stmt->execute();
}

// Get timezones for dropdown
$timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

// Get currencies for dropdown
$currencies = [
    'USD' => ['name' => 'US Dollar', 'symbol' => '$'],
    'EUR' => ['name' => 'Euro', 'symbol' => '€'],
    'GBP' => ['name' => 'British Pound', 'symbol' => '£'],
    'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥'],
    'CAD' => ['name' => 'Canadian Dollar', 'symbol' => '$'],
    'AUD' => ['name' => 'Australian Dollar', 'symbol' => '$'],
    'INR' => ['name' => 'Indian Rupee', 'symbol' => '₹'],
    'CNY' => ['name' => 'Chinese Yuan', 'symbol' => '¥'],
    'HKD' => ['name' => 'Hong Kong Dollar', 'symbol' => '$'],
    'SGD' => ['name' => 'Singapore Dollar', 'symbol' => '$']
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Metro Rail Admin</title>    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/admin.css">
    <style>
        .nav-pills .nav-link {
            border-radius: 0;
            padding: 0.75rem 1rem;
            color: #495057;
            transition: all 0.2s;
        }
        .nav-pills .nav-link.active {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--admin-primary);
            border-left: 3px solid var(--admin-primary);
            font-weight: 500;
        }
        .nav-pills .nav-link:hover:not(.active) {
            background-color: rgba(0,0,0,0.03);
            border-left: 3px solid rgba(52, 152, 219, 0.3);
        }
        .settings-content {
            min-height: 300px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/admin_sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">System Settings</h1>
                </div>
                
                <!-- Alert Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error!</strong>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Success!</strong> <?= $success ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Settings Content -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="list-group mb-4" id="settings-tabs" role="tablist">
                            <a class="list-group-item list-group-item-action active" id="general-tab" data-bs-toggle="list" href="#general" role="tab" aria-controls="general">
                                <i class="bi bi-gear me-2"></i> General Settings
                            </a>
                            <a class="list-group-item list-group-item-action" id="payment-tab" data-bs-toggle="list" href="#payment" role="tab" aria-controls="payment">
                                <i class="bi bi-credit-card me-2"></i> Payment Settings
                            </a>
                            <a class="list-group-item list-group-item-action" id="email-tab" data-bs-toggle="list" href="#email" role="tab" aria-controls="email">
                                <i class="bi bi-envelope me-2"></i> Email Settings
                            </a>
                            <a class="list-group-item list-group-item-action" id="date-time-tab" data-bs-toggle="list" href="#date-time" role="tab" aria-controls="date-time">
                                <i class="bi bi-calendar me-2"></i> Date & Time
                            </a>
                            <a class="list-group-item list-group-item-action" id="legal-tab" data-bs-toggle="list" href="#legal" role="tab" aria-controls="legal">
                                <i class="bi bi-file-text me-2"></i> Legal Policies
                            </a>
                            <a class="list-group-item list-group-item-action" id="security-tab" data-bs-toggle="list" href="#security" role="tab" aria-controls="security">
                                <i class="bi bi-shield-lock me-2"></i> Security
                            </a>
                            <a class="list-group-item list-group-item-action" id="backup-tab" data-bs-toggle="list" href="#backup" role="tab" aria-controls="backup">
                                <i class="bi bi-cloud-download me-2"></i> Backup & Restore
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-9">
                        <div class="tab-content" id="settings-tab-content">
                            <!-- General Settings -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">General Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <form action="" method="post">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="site_name" class="form-label">Site Name</label>
                                                    <input type="text" class="form-control" id="site_name" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="site_description" class="form-label">Site Description</label>
                                                    <input type="text" class="form-control" id="site_description" name="site_description" value="<?= htmlspecialchars($settings['site_description'] ?? '') ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="contact_email" class="form-label">Contact Email</label>
                                                    <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="contact_phone" class="form-label">Contact Phone</label>
                                                    <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="<?= htmlspecialchars($settings['contact_phone'] ?? '') ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label d-block">Enable Online Booking</label>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="enable_online_booking" id="enable_booking_yes" value="yes" <?= (isset($settings['enable_online_booking']) && $settings['enable_online_booking'] === 'yes') ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="enable_booking_yes">Yes</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="enable_online_booking" id="enable_booking_no" value="no" <?= (isset($settings['enable_online_booking']) && $settings['enable_online_booking'] === 'no') ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="enable_booking_no">No</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label d-block">Maintenance Mode</label>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="maintenance_mode" id="maintenance_mode_yes" value="yes" <?= (isset($settings['maintenance_mode']) && $settings['maintenance_mode'] === 'yes') ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="maintenance_mode_yes">Yes</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="maintenance_mode" id="maintenance_mode_no" value="no" <?= (isset($settings['maintenance_mode']) && $settings['maintenance_mode'] === 'no') ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="maintenance_mode_no">No</label>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="text-end">
                                                <button type="submit" name="update_general" class="btn btn-primary">Save General Settings</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Payment Settings -->
                            <div class="tab-pane fade" id="payment" role="tabpanel" aria-labelledby="payment-tab">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Payment Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <form action="" method="post">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="booking_fee" class="form-label">Booking Fee ($)</label>
                                                    <input type="number" class="form-control" id="booking_fee" name="booking_fee" step="0.01" value="<?= htmlspecialchars($settings['booking_fee'] ?? '0.00') ?>">
                                                    <div class="form-text">Fee charged per booking transaction</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                                    <input type="number" class="form-control" id="tax_rate" name="tax_rate" step="0.01" value="<?= htmlspecialchars($settings['tax_rate'] ?? '0.00') ?>">
                                                    <div class="form-text">Percentage tax applied to bookings</div>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="currency" class="form-label">Currency</label>
                                                    <select class="form-select" id="currency" name="currency">
                                                        <?php foreach ($currencies as $code => $currencyData): ?>
                                                            <option value="<?= $code ?>" <?= (isset($settings['currency']) && $settings['currency'] === $code) ? 'selected' : '' ?>>
                                                                <?= $code ?> - <?= $currencyData['name'] ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="currency_symbol" class="form-label">Currency Symbol</label>
                                                    <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" value="<?= htmlspecialchars($settings['currency_symbol'] ?? '$') ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="text-end">
                                                <button type="submit" name="update_payment" class="btn btn-primary">Save Payment Settings</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Email Settings -->
                            <div class="tab-pane fade" id="email" role="tabpanel" aria-labelledby="email-tab">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Email Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <form action="" method="post">
                                            <h6 class="mb-3">SMTP Configuration</h6>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="smtp_host" class="form-label">SMTP Host</label>
                                                    <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="smtp_port" class="form-label">SMTP Port</label>
                                                    <input type="text" class="form-control" id="smtp_port" name="smtp_port" value="<?= htmlspecialchars($settings['smtp_port'] ?? '') ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="smtp_username" class="form-label">SMTP Username</label>
                                                    <input type="text" class="form-control" id="smtp_username" name="smtp_username" value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="smtp_password" class="form-label">SMTP Password</label>
                                                    <input type="password" class="form-control" id="smtp_password" name="smtp_password" placeholder="Leave blank to keep current password">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="smtp_encryption" class="form-label">SMTP Encryption</label>
                                                    <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                                        <option value="none" <?= (isset($settings['smtp_encryption']) && $settings['smtp_encryption'] === 'none') ? 'selected' : '' ?>>None</option>
                                                        <option value="tls" <?= (isset($settings['smtp_encryption']) && $settings['smtp_encryption'] === 'tls') ? 'selected' : '' ?>>TLS</option>
                                                        <option value="ssl" <?= (isset($settings['smtp_encryption']) && $settings['smtp_encryption'] === 'ssl') ? 'selected' : '' ?>>SSL</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <h6 class="mb-3 mt-4">Mail Settings</h6>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="mail_from_address" class="form-label">From Address</label>
                                                    <input type="email" class="form-control" id="mail_from_address" name="mail_from_address" value="<?= htmlspecialchars($settings['mail_from_address'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="mail_from_name" class="form-label">From Name</label>
                                                    <input type="text" class="form-control" id="mail_from_name" name="mail_from_name" value="<?= htmlspecialchars($settings['mail_from_name'] ?? '') ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="text-end">
                                                <button type="submit" name="update_email" class="btn btn-primary">Save Email Settings</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Date & Time Settings -->
                            <div class="tab-pane fade" id="date-time" role="tabpanel" aria-labelledby="date-time-tab">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Date & Time Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <form action="" method="post">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="date_format" class="form-label">Date Format</label>
                                                    <select class="form-select" id="date_format" name="date_format">
                                                        <option value="Y-m-d" <?= (isset($settings['date_format']) && $settings['date_format'] === 'Y-m-d') ? 'selected' : '' ?>>YYYY-MM-DD (2023-12-31)</option>
                                                        <option value="m/d/Y" <?= (isset($settings['date_format']) && $settings['date_format'] === 'm/d/Y') ? 'selected' : '' ?>>MM/DD/YYYY (12/31/2023)</option>
                                                        <option value="d/m/Y" <?= (isset($settings['date_format']) && $settings['date_format'] === 'd/m/Y') ? 'selected' : '' ?>>DD/MM/YYYY (31/12/2023)</option>
                                                        <option value="d.m.Y" <?= (isset($settings['date_format']) && $settings['date_format'] === 'd.m.Y') ? 'selected' : '' ?>>DD.MM.YYYY (31.12.2023)</option>
                                                        <option value="M d, Y" <?= (isset($settings['date_format']) && $settings['date_format'] === 'M d, Y') ? 'selected' : '' ?>>Mon DD, YYYY (Dec 31, 2023)</option>
                                                        <option value="F d, Y" <?= (isset($settings['date_format']) && $settings['date_format'] === 'F d, Y') ? 'selected' : '' ?>>Month DD, YYYY (December 31, 2023)</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="time_format" class="form-label">Time Format</label>
                                                    <select class="form-select" id="time_format" name="time_format">
                                                        <option value="H:i" <?= (isset($settings['time_format']) && $settings['time_format'] === 'H:i') ? 'selected' : '' ?>>24 Hour (14:30)</option>
                                                        <option value="h:i A" <?= (isset($settings['time_format']) && $settings['time_format'] === 'h:i A') ? 'selected' : '' ?>>12 Hour (02:30 PM)</option>
                                                        <option value="g:i A" <?= (isset($settings['time_format']) && $settings['time_format'] === 'g:i A') ? 'selected' : '' ?>>12 Hour without leading zero (2:30 PM)</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="timezone" class="form-label">Timezone</label>
                                                    <select class="form-select" id="timezone" name="timezone">
                                                        <?php foreach ($timezones as $tz): ?>
                                                            <option value="<?= $tz ?>" <?= (isset($settings['timezone']) && $settings['timezone'] === $tz) ? 'selected' : '' ?>>
                                                                <?= $tz ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="text-end">
                                                <button type="submit" name="update_date_time" class="btn btn-primary">Save Date & Time Settings</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Legal Policies -->
                            <div class="tab-pane fade" id="legal" role="tabpanel" aria-labelledby="legal-tab">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Legal Policies</h5>
                                    </div>
                                    <div class="card-body">
                                        <form action="" method="post">
                                            <div class="mb-3">
                                                <label for="terms_and_conditions" class="form-label">Terms and Conditions</label>
                                                <textarea class="form-control" id="terms_and_conditions" name="terms_and_conditions" rows="10"><?= htmlspecialchars($settings['terms_and_conditions'] ?? '') ?></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="privacy_policy" class="form-label">Privacy Policy</label>
                                                <textarea class="form-control" id="privacy_policy" name="privacy_policy" rows="10"><?= htmlspecialchars($settings['privacy_policy'] ?? '') ?></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="cancellation_policy" class="form-label">Cancellation Policy</label>
                                                <textarea class="form-control" id="cancellation_policy" name="cancellation_policy" rows="10"><?= htmlspecialchars($settings['cancellation_policy'] ?? '') ?></textarea>
                                            </div>
                                            
                                            <div class="text-end">
                                                <button type="submit" name="update_legal" class="btn btn-primary">Save Legal Policies</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Security Settings -->
                            <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Security Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <form action="" method="post">
                                            <h6 class="mb-3">Change Admin Password</h6>
                                            <div class="mb-3">
                                                <label for="current_password" class="form-label">Current Password</label>
                                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="new_password" class="form-label">New Password</label>
                                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                                <div class="form-text">Password must be at least 8 characters long</div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                            </div>
                                            
                                            <div class="text-end">
                                                <button type="submit" name="update_password" class="btn btn-primary">Change Password</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Backup & Restore -->
                            <div class="tab-pane fade" id="backup" role="tabpanel" aria-labelledby="backup-tab">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Backup & Restore</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <h6 class="alert-heading">Database Backup</h6>
                                            <p>Create a backup of your database to prevent data loss. Regular backups are recommended.</p>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <h6>Create Backup</h6>
                                            <p>Generate a backup of the entire database</p>
                                            <a href="#" class="btn btn-primary">Generate Backup</a>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <h6>Restore from Backup</h6>
                                            <p>Restore your database from a previous backup file</p>
                                            <form action="" method="post" enctype="multipart/form-data">
                                                <div class="mb-3">
                                                    <input class="form-control" type="file" id="backup_file" name="backup_file">
                                                </div>
                                                <button type="submit" name="restore_backup" class="btn btn-warning">Restore Backup</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
      <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize the tab system
            const triggerTabList = document.querySelectorAll('#settings-tabs a');
            triggerTabList.forEach(function(triggerEl) {
                const tabTrigger = new bootstrap.Tab(triggerEl);
                
                triggerEl.addEventListener('click', function(event) {
                    event.preventDefault();
                    tabTrigger.show();
                });
            });
            
            // Check for hash in URL to activate specific tab
            if (window.location.hash) {
                const hash = window.location.hash.substring(1);
                const tabToActivate = document.querySelector(`#settings-tabs a[href="#${hash}"]`);
                if (tabToActivate) {
                    const tab = new bootstrap.Tab(tabToActivate);
                    tab.show();
                }
            }
            
            // Auto-select timezone based on settings
            let timezoneSelect = document.getElementById('timezone');
            if (timezoneSelect) {
                timezoneSelect.value = '<?= $settings['timezone'] ?? 'UTC' ?>';
            }
            
            // Currency selector
            let currencySelect = document.getElementById('currency');
            let currencySymbolInput = document.getElementById('currency_symbol');
            
            if (currencySelect && currencySymbolInput) {
                currencySelect.addEventListener('change', function() {
                    let selectedCurrency = this.value;
                    let symbols = {
                        'USD': '$',
                        'EUR': '€',
                        'GBP': '£',
                        'JPY': '¥',
                        'CAD': '$',
                        'AUD': '$',
                        'INR': '₹',
                        'CNY': '¥',
                        'HKD': '$',
                        'SGD': '$'
                    };
                    
                    if (symbols[selectedCurrency]) {
                        currencySymbolInput.value = symbols[selectedCurrency];
                    }
                });
            }
        });
    </script>
</body>
</html>
