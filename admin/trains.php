<?php
// Start output buffering
ob_start();

// Include the admin authentication file
require_once 'admin_auth.php';

// Get admin information
$userId = $_SESSION['user_id'];
$admin = fetchRow("SELECT * FROM users WHERE id = ?", [$userId]);

// Set page title
$pageTitle = 'Manage Trains';

// Check for form submissions
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Add new train
                $trainName = isset($_POST['train_name']) ? sanitizeInput($_POST['train_name']) : '';
                $trainNumber = isset($_POST['train_number']) ? sanitizeInput($_POST['train_number']) : '';
                $capacity = isset($_POST['capacity']) ? (int)$_POST['capacity'] : 0;
                $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'active';
                
                // Validate inputs
                if (empty($trainName) || empty($trainNumber) || $capacity <= 0) {
                    $error = 'Please fill in all required fields.';
                } else {
                    // Check if train number already exists
                    $existingTrain = fetchRow("SELECT id FROM trains WHERE train_number = ?", [$trainNumber]);
                    
                    if ($existingTrain) {
                        $error = 'Train number already exists. Please use a unique train number.';
                    } else {                        try {
                            // Insert new train
                            global $conn;
                            $stmt = $conn->prepare("INSERT INTO trains (name, train_number, capacity, status, created_at) VALUES (?, ?, ?, ?, NOW())");
                            $stmt->bind_param("ssis", $trainName, $trainNumber, $capacity, $status);
                            $success = $stmt->execute();
                            
                            if ($success) {
                                $message = 'Train added successfully.';
                            } else {
                                $error = 'Failed to add train.';
                            }
                        } catch (Exception $e) {
                            // Log the error
                            debug_log('Train insert error: ' . $e->getMessage(), null, 'trains_error.log');
                            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                                $error = 'Train number already exists. Please use a unique train number.';
                            } else {
                                $error = 'An error occurred while adding the train: ' . $e->getMessage();
                            }
                        }
                    }
                }
                break;
                
            case 'update':
                // Update existing train
                $trainId = isset($_POST['train_id']) ? (int)$_POST['train_id'] : 0;
                $trainName = isset($_POST['train_name']) ? sanitizeInput($_POST['train_name']) : '';
                $trainNumber = isset($_POST['train_number']) ? sanitizeInput($_POST['train_number']) : '';
                $capacity = isset($_POST['capacity']) ? (int)$_POST['capacity'] : 0;
                $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'active';
                
                // Validate inputs
                if ($trainId <= 0 || empty($trainName) || empty($trainNumber) || $capacity <= 0) {
                    $error = 'Please fill in all required fields.';
                } else {
                    // Check if train number already exists for another train
                    $existingTrain = fetchRow("SELECT id FROM trains WHERE train_number = ? AND id != ?", [$trainNumber, $trainId]);
                    
                    if ($existingTrain) {
                        $error = 'Train number already exists. Please use a unique train number.';
                    } else {
                        try {
                            // Update train
                            $result = executeQuery(
                                "UPDATE trains SET name = ?, train_number = ?, capacity = ?, status = ?, updated_at = NOW() WHERE id = ?",
                                [$trainName, $trainNumber, $capacity, $status, $trainId]
                            );
                            
                            if ($result) {
                                $message = 'Train updated successfully.';
                            } else {
                                $error = 'Failed to update train.';
                            }
                        } catch (Exception $e) {
                            // Log the error
                            debug_log('Train update error: ' . $e->getMessage(), null, 'trains_error.log');
                            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                                $error = 'Train number already exists. Please use a unique train number.';
                            } else {
                                $error = 'An error occurred while updating the train: ' . $e->getMessage();
                            }
                        }
                    }
                }
                break;
                
            case 'delete':                // Delete train
                $trainId = isset($_POST['train_id']) ? (int)$_POST['train_id'] : 0;
                
                if ($trainId <= 0) {
                    $error = 'Invalid train ID.';
                } else {
                    try {
                        // Check if train is used in schedules - first check if the table exists
                        $tableExists = fetchRow("SELECT COUNT(*) as count FROM information_schema.tables 
                                               WHERE table_schema = '".DB_NAME."' 
                                               AND table_name = 'train_schedules'");
                        
                        $canDelete = true;
                        if ($tableExists && $tableExists['count'] > 0) {
                            // Table exists, now check if train is used
                            $used = fetchRow("SELECT COUNT(*) as count FROM train_schedules WHERE train_id = ?", [$trainId]);
                            if ($used && $used['count'] > 0) {
                                $error = 'Cannot delete train as it is used in schedules.';
                                $canDelete = false;
                            }
                        }
                        
                        if ($canDelete) {
                            global $conn;
                            $stmt = $conn->prepare("DELETE FROM trains WHERE id = ?");
                            $stmt->bind_param("i", $trainId);
                            $success = $stmt->execute();
                            
                            if ($success) {
                                $message = 'Train deleted successfully.';
                            } else {
                                $error = 'Failed to delete train.';
                            }
                        }
                    } catch (Exception $e) {
                        debug_log('Train delete error: ' . $e->getMessage(), null, 'trains_error.log');
                        $error = 'An error occurred while deleting the train: ' . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Get trains for display
$trains = fetchRows("SELECT id, name as train_name, train_number, capacity, status, created_at, updated_at FROM trains ORDER BY name ASC");

// Include header (the admin panel has a different layout)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metro Rail - <?php echo $pageTitle; ?></title>
    
    <!-- Modern Styling -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/admin.css">
    
    <style>
        .train-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .train-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/admin_sidebar.php'; ?>
              <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-train-front me-2"></i>Manage Trains</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTrainModal">
                            <i class="bi bi-plus-circle me-1"></i> Add New Train
                        </button>
                    </div>
                </div>                <!-- Alert Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-1"></i> <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-1"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Trains Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card train-card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="bi bi-train-front fs-1"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title">Total Trains</h5>
                                        <p class="card-text fs-3"><?= count($trains) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card train-card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="bi bi-check-circle fs-1"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title">Active Trains</h5>
                                        <p class="card-text fs-3">
                                            <?php 
                                                $activeCount = 0;
                                                foreach ($trains as $train) {
                                                    if ($train['status'] === 'active') {
                                                        $activeCount++;
                                                    }
                                                }
                                                echo $activeCount;
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card train-card bg-warning text-dark">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="bi bi-tools fs-1"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title">Under Maintenance</h5>
                                        <p class="card-text fs-3">
                                            <?php 
                                                $maintenanceCount = 0;
                                                foreach ($trains as $train) {
                                                    if ($train['status'] === 'under_maintenance') {
                                                        $maintenanceCount++;
                                                    }
                                                }
                                                echo $maintenanceCount;
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Trains Table -->                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold">Trains List</h6>
                    </div>
                    <div class="card-body">
                          <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Train Name</th>
                                        <th>Train Number</th>
                                        <th>Capacity</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($trains)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No trains found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($trains as $train): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($train['train_name']); ?></td>
                                                <td><?php echo htmlspecialchars($train['train_number']); ?></td>
                                                <td><?php echo htmlspecialchars($train['capacity']); ?></td>
                                                <td>
                                                    <span class="badge <?php 
                                                        echo $train['status'] === 'active' ? 'bg-success' : 
                                                            ($train['status'] === 'under_maintenance' ? 'bg-warning text-dark' : 'bg-danger'); 
                                                    ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($train['status']))); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-primary edit-train" 
                                                                data-id="<?php echo $train['id']; ?>"
                                                                data-name="<?php echo htmlspecialchars($train['train_name']); ?>"
                                                                data-number="<?php echo htmlspecialchars($train['train_number']); ?>"
                                                                data-capacity="<?php echo htmlspecialchars($train['capacity']); ?>"
                                                                data-status="<?php echo htmlspecialchars($train['status']); ?>"
                                                                data-bs-toggle="modal" data-bs-target="#editTrainModal">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger delete-train" 
                                                                data-id="<?php echo $train['id']; ?>"
                                                                data-name="<?php echo htmlspecialchars($train['train_name']); ?>"
                                                                data-bs-toggle="modal" data-bs-target="#deleteTrainModal">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
      <!-- Add Train Modal -->
    <div class="modal fade" id="addTrainModal" tabindex="-1" aria-labelledby="addTrainModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addTrainModalLabel"><i class="bi bi-plus-circle me-2"></i>Add New Train</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="post">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="train_name" class="form-label">Train Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="train_name" name="train_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="train_number" class="form-label">Train Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="train_number" name="train_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="capacity" class="form-label">Capacity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="capacity" name="capacity" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="under_maintenance">Under Maintenance</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> Add Train</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Train Modal -->
    <div class="modal fade" id="editTrainModal" tabindex="-1" aria-labelledby="editTrainModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editTrainModalLabel"><i class="bi bi-pencil me-2"></i>Edit Train</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="post">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="train_id" id="edit_train_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_train_name" class="form-label">Train Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_train_name" name="train_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_train_number" class="form-label">Train Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_train_number" name="train_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_capacity" class="form-label">Capacity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_capacity" name="capacity" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="under_maintenance">Under Maintenance</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Update Train</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Train Modal -->
    <div class="modal fade" id="deleteTrainModal" tabindex="-1" aria-labelledby="deleteTrainModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteTrainModalLabel"><i class="bi bi-trash me-2"></i>Delete Train</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="post">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="train_id" id="delete_train_id">
                    <div class="modal-body">
                        <div class="text-center mb-3">
                            <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 3rem;"></i>
                        </div>
                        <p class="text-center">Are you sure you want to delete this train?</p>
                        <p id="delete_train_name" class="text-center fw-bold"></p>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            This action cannot be undone. All data associated with this train will be permanently removed.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i> Delete Train</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
      <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Edit Train Modal
            const editButtons = document.querySelectorAll('.edit-train');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const number = this.getAttribute('data-number');
                    const capacity = this.getAttribute('data-capacity');
                    const status = this.getAttribute('data-status');
                    
                    document.getElementById('edit_train_id').value = id;
                    document.getElementById('edit_train_name').value = name;
                    document.getElementById('edit_train_number').value = number;
                    document.getElementById('edit_capacity').value = capacity;
                    document.getElementById('edit_status').value = status;
                });
            });
            
            // Delete Train Modal
            const deleteButtons = document.querySelectorAll('.delete-train');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    
                    document.getElementById('delete_train_id').value = id;
                    document.getElementById('delete_train_name').textContent = name;
                });
            });
        });
    </script>
</body>
</html>
