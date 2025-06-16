<?php
// Include necessary files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session
session_start();

// Initialize variables
$errors = [];
$success = '';
$formData = [
    'name' => '',
    'email' => '',
    'subject' => '',
    'message' => ''
];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'name' => isset($_POST['name']) ? sanitizeInput($_POST['name']) : '',
        'email' => isset($_POST['email']) ? sanitizeInput($_POST['email']) : '',
        'subject' => isset($_POST['subject']) ? sanitizeInput($_POST['subject']) : '',
        'message' => isset($_POST['message']) ? sanitizeInput($_POST['message']) : ''
    ];
    
    // Validate form data
    if (empty($formData['name'])) {
        $errors[] = 'Name is required';
    }
    
    if (empty($formData['email'])) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address';
    }
    
    if (empty($formData['subject'])) {
        $errors[] = 'Subject is required';
    }
    
    if (empty($formData['message'])) {
        $errors[] = 'Message is required';
    }
    
    // If no errors, save contact message
    if (empty($errors)) {
        // Insert contact message into database
        $contactData = [
            'name' => $formData['name'],
            'email' => $formData['email'],
            'subject' => $formData['subject'],
            'message' => $formData['message'],
            'status' => 'unread',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $inserted = insert('contact_messages', $contactData);
        
        if ($inserted) {
            // Log activity
            logActivity('contact_message_sent', 'Sent a contact message: ' . $formData['subject'], isLoggedIn() ? $_SESSION['user_id'] : null);
            
            // Send email notification (placeholder)
            $emailSent = sendEmail('admin@metrorail.com', 'New Contact Message: ' . $formData['subject'], $formData['message']);
            
            // Set success message
            $success = 'Your message has been sent successfully! Our team will get back to you soon.';
            
            // Reset form data
            $formData = [
                'name' => '',
                'email' => '',
                'subject' => '',
                'message' => ''
            ];
        } else {
            $errors[] = 'Failed to send message. Please try again.';
        }
    }
}

// Set page title
$pageTitle = 'Contact Us';

// Include header
require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold"><i class="fas fa-envelope me-2"></i>Contact Us</h1>
                <p class="lead">Have questions or feedback? We'd love to hear from you!</p>
            </div>
            <div class="col-lg-6">
                <img src="images/contact-illustration.svg" class="img-fluid" alt="Contact Illustration" onerror="this.style.display='none'">
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row">
        <!-- Contact Form -->
        <div class="col-lg-8">
            <div class="card modern-card contact-form-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Send Us a Message</h5>
                </div>
                <div class="card-body">
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
                        <div class="alert alert-success alert-modern">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="contact-form">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $formData['name']; ?>" required>
                                    <label for="name">Your Name</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $formData['email']; ?>" required>
                                    <label for="email">Email Address</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="subject" name="subject" value="<?php echo $formData['subject']; ?>" required>
                            <label for="subject">Subject</label>
                        </div>
                        
                        <div class="form-floating mb-4">
                            <textarea class="form-control" id="message" name="message" style="height: 150px" required><?php echo $formData['message']; ?></textarea>
                            <label for="message">Message</label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg action-button">
                                <i class="fas fa-paper-plane me-2"></i>Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Contact Information -->
        <div class="col-lg-4">
            <div class="card modern-card contact-info-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Contact Information</h5>
                </div>
                <div class="card-body">
                    <div class="contact-info-item">
                        <div class="contact-info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-info-content">
                            <h5>Address</h5>
                            <p>
                                123 Metro Station Road<br>
                                City Center, State 12345<br>
                                Country
                            </p>
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <div class="contact-info-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div class="contact-info-content">
                            <h5>Phone</h5>
                            <p>
                                Customer Service: +1 (555) 123-4567<br>
                                Support: +1 (555) 987-6543
                            </p>
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <div class="contact-info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-info-content">
                            <h5>Email</h5>
                            <p>
                                info@metrorail.com<br>
                                support@metrorail.com
                            </p>
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <div class="contact-info-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="contact-info-content">
                            <h5>Operating Hours</h5>
                            <p>
                                Monday - Friday: 6:00 AM - 11:00 PM<br>
                                Saturday - Sunday: 7:00 AM - 10:00 PM
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card modern-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i>Quick Links</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="stations.php" class="list-group-item list-group-item-action d-flex align-items-center">
                            <i class="fas fa-map-marker-alt me-3 text-primary"></i>Find Stations
                            <i class="fas fa-chevron-right ms-auto"></i>
                        </a>
                        <a href="schedules.php" class="list-group-item list-group-item-action d-flex align-items-center">
                            <i class="fas fa-clock me-3 text-primary"></i>Train Schedules
                            <i class="fas fa-chevron-right ms-auto"></i>
                        </a>
                        <a href="fare.php" class="list-group-item list-group-item-action d-flex align-items-center">
                            <i class="fas fa-calculator me-3 text-primary"></i>Fare Calculator
                            <i class="fas fa-chevron-right ms-auto"></i>
                        </a>
                        <a href="faq.php" class="list-group-item list-group-item-action d-flex align-items-center">
                            <i class="fas fa-question-circle me-3 text-primary"></i>FAQs
                            <i class="fas fa-chevron-right ms-auto"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
