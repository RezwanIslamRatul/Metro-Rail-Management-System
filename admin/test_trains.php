<?php
// Start output buffering
ob_start();

// Include configuration and database files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Create a basic session for testing
session_start();
$_SESSION['user_id'] = 1; // Assume admin ID is 1
$_SESSION['user_role'] = 'admin';
$_SESSION['user_name'] = 'Admin';

// Get all trains
$trains = fetchRows("SELECT id, name as train_name, train_number, capacity, status, created_at, updated_at FROM trains ORDER BY name ASC");

// Display the results
echo "<h1>Train Listing Test</h1>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>ID</th><th>Train Name</th><th>Train Number</th><th>Capacity</th><th>Status</th></tr>";

foreach ($trains as $train) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($train['id']) . "</td>";
    echo "<td>" . htmlspecialchars($train['train_name']) . "</td>";
    echo "<td>" . htmlspecialchars($train['train_number']) . "</td>";
    echo "<td>" . htmlspecialchars($train['capacity']) . "</td>";
    echo "<td>" . htmlspecialchars($train['status']) . "</td>";
    echo "</tr>";
}

echo "</table>";
?>
