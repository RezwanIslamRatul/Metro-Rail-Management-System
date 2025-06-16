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

// Log debugging information
debug_log("Testing train creation", null, "train_test.log");

$result = false;

// Test adding a new train
if (isset($_POST['submit'])) {
    $trainName = isset($_POST['train_name']) ? sanitizeInput($_POST['train_name']) : '';
    $trainNumber = isset($_POST['train_number']) ? sanitizeInput($_POST['train_number']) : '';
    $capacity = isset($_POST['capacity']) ? (int)$_POST['capacity'] : 0;
    $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'active';
    
    // Validate inputs
    if (empty($trainName) || empty($trainNumber) || $capacity <= 0) {
        $error = 'Please fill in all required fields.';
    } else {
        // Insert new train
        debug_log("Inserting train: $trainName, $trainNumber, $capacity, $status", null, "train_test.log");
        
        $result = executeQuery(
            "INSERT INTO trains (name, train_number, capacity, status, created_at) VALUES (?, ?, ?, ?, NOW())",
            [$trainName, $trainNumber, $capacity, $status]
        );
        
        if ($result) {
            $message = 'Train added successfully.';
            debug_log("Train added successfully", null, "train_test.log");
        } else {
            $error = 'Failed to add train.';
            debug_log("Failed to add train", null, "train_test.log");
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Add Train</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, select { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>Test Add Train</h1>
    
    <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($message)): ?>
        <div class="success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($result): ?>
        <p>Train ID: <?php echo $conn->insert_id; ?></p>
    <?php endif; ?>
    
    <form method="post">
        <div class="form-group">
            <label for="train_name">Train Name:</label>
            <input type="text" name="train_name" id="train_name" required>
        </div>
        
        <div class="form-group">
            <label for="train_number">Train Number:</label>
            <input type="text" name="train_number" id="train_number" required>
        </div>
        
        <div class="form-group">
            <label for="capacity">Capacity:</label>
            <input type="number" name="capacity" id="capacity" min="1" required>
        </div>
        
        <div class="form-group">
            <label for="status">Status:</label>
            <select name="status" id="status" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="under_maintenance">Under Maintenance</option>
            </select>
        </div>
        
        <button type="submit" name="submit">Add Train</button>
    </form>
    
    <p><a href="test_trains.php">View Trains</a></p>
</body>
</html>
