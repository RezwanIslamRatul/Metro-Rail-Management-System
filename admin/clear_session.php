<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear session data
session_unset();
session_destroy();

echo "<p>Session data cleared.</p>";
echo "<p><a href='check_admin_user.php'>Go back</a></p>";
echo "<p><a href='../login.php'>Go to login page</a></p>";
