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
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : '';
$stationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Form values
$formStation = [
    'id' => '',
    'name' => '',
    'code' => '',
    'address' => '',
    'latitude' => '',
    'longitude' => '',
    'status' => 'active'
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_station']) || isset($_POST['update_station'])) {
        // Get form data
        $formStation = [
            'id' => isset($_POST['id']) ? (int)$_POST['id'] : 0,
            'name' => isset($_POST['name']) ? sanitizeInput($_POST['name']) : '',
            'code' => isset($_POST['code']) ? sanitizeInput($_POST['code']) : '',
            'address' => isset($_POST['address']) ? sanitizeInput($_POST['address']) : '',
            'latitude' => isset($_POST['latitude']) ? sanitizeInput($_POST['latitude']) : '',
            'longitude' => isset($_POST['longitude']) ? sanitizeInput($_POST['longitude']) : '',
            'status' => isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'active'
        ];
        
        // Validate form data
        if (empty($formStation['name'])) {
            $errors[] = 'Station name is required';
        }
        
        if (empty($formStation['code'])) {
            $errors[] = 'Station code is required';
        } elseif (!preg_match('/^[A-Z0-9]{2,10}$/', $formStation['code'])) {
            $errors[] = 'Station code must be 2-10 alphanumeric characters (uppercase)';
        } else {
            // Check if code is already in use by another station
            $codeExists = fetchRow("
                SELECT id FROM stations WHERE code = ? AND id != ?
            ", [$formStation['code'], $formStation['id']]);
            
            if ($codeExists) {
                $errors[] = 'Station code is already in use by another station';
            }
        }
        
        if (empty($formStation['address'])) {
            $errors[] = 'Station address is required';
        }
        
        if (empty($formStation['latitude']) || !is_numeric($formStation['latitude'])) {
            $errors[] = 'Valid latitude is required';
        } elseif ($formStation['latitude'] < -90 || $formStation['latitude'] > 90) {
            $errors[] = 'Latitude must be between -90 and 90';
        }
        
        if (empty($formStation['longitude']) || !is_numeric($formStation['longitude'])) {
            $errors[] = 'Valid longitude is required';
        } elseif ($formStation['longitude'] < -180 || $formStation['longitude'] > 180) {
            $errors[] = 'Longitude must be between -180 and 180';
        }
        
        // If no errors, add or update station
        if (empty($errors)) {
            $stationData = [
                'name' => $formStation['name'],
                'code' => $formStation['code'],
                'address' => $formStation['address'],
                'latitude' => $formStation['latitude'],
                'longitude' => $formStation['longitude'],
                'status' => $formStation['status']
            ];
            
            if (isset($_POST['add_station'])) {
                // Add new station
                $stationData['created_at'] = date('Y-m-d H:i:s');
                
                $stationId = insert('stations', $stationData);
                
                if ($stationId) {
                    // Log activity
                    logActivity('station_added', 'Added new station: ' . $formStation['name'], $userId);
                    
                    // Set success message and redirect
                    setFlashMessage('success', 'Station added successfully.');
                    redirect(APP_URL . '/admin/stations.php');
                } else {
                    $errors[] = 'Failed to add station. Please try again.';
                }
            } else {
                // Update existing station
                $stationData['updated_at'] = date('Y-m-d H:i:s');
                
                $updated = update('stations', $stationData, "id = " . $formStation['id']);
                
                if ($updated) {
                    // Log activity
                    logActivity('station_updated', 'Updated station: ' . $formStation['name'], $userId);
                    
                    // Set success message and redirect
                    setFlashMessage('success', 'Station updated successfully.');
                    redirect(APP_URL . '/admin/stations.php');
                } else {
                    $errors[] = 'Failed to update station. Please try again.';
                }
            }
        }
    } elseif (isset($_POST['delete_station'])) {
        $deleteId = (int)$_POST['delete_id'];
        
        // Check if station is used in any route_stations
        $stationInUse = fetchRow("
            SELECT COUNT(*) as count FROM route_stations WHERE station_id = ?
        ", [$deleteId]);
        
        if ($stationInUse['count'] > 0) {
            setFlashMessage('error', 'Cannot delete station that is part of a route. Remove it from all routes first.');
            redirect(APP_URL . '/admin/stations.php');
        }
        
        // Check if station is used in any bookings
        $stationInBookings = fetchRow("
            SELECT COUNT(*) as count 
            FROM bookings 
            WHERE from_station_id = ? OR to_station_id = ?
        ", [$deleteId, $deleteId]);
        
        if ($stationInBookings['count'] > 0) {
            setFlashMessage('error', 'Cannot delete station that has bookings associated with it.');
            redirect(APP_URL . '/admin/stations.php');
        }
        
        // Get station name for activity log
        $stationName = fetchRow("SELECT name FROM stations WHERE id = ?", [$deleteId])['name'];
        
        // Delete station
        $deleted = delete('stations', "id = $deleteId");
        
        if ($deleted) {
            // Log activity
            logActivity('station_deleted', 'Deleted station: ' . $stationName, $userId);
            
            // Set success message and redirect
            setFlashMessage('success', 'Station deleted successfully.');
            redirect(APP_URL . '/admin/stations.php');
        } else {
            setFlashMessage('error', 'Failed to delete station. Please try again.');
            redirect(APP_URL . '/admin/stations.php');
        }
    }
}

// If editing, get station data
if ($action === 'edit' && $stationId > 0) {
    $stationData = fetchRow("SELECT * FROM stations WHERE id = ?", [$stationId]);
    
    if ($stationData) {
        $formStation = $stationData;
    } else {
        setFlashMessage('error', 'Station not found.');
        redirect(APP_URL . '/admin/stations.php');
    }
}

// Get all stations
$stations = fetchRows("SELECT * FROM stations ORDER BY name ASC");

// Set page title
$pageTitle = 'Manage Stations';

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
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        .station-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .station-card:hover {
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
                    <h1 class="h2"><i class="bi bi-geo-alt-fill me-2"></i>Manage Stations</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStationModal">
                            <i class="bi bi-plus-circle me-1"></i> Add New Station
                        </button>
                    </div>
                </div>
                  <!-- Flash Messages -->
                <?php displayFlashMessages(); ?>
                
                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Please fix the following errors:
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Station Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card station-card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="bi bi-geo-alt-fill fs-1"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title">Total Stations</h5>
                                        <p class="card-text fs-3"><?= count($stations) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card station-card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="bi bi-check-circle-fill fs-1"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title">Active Stations</h5>
                                        <p class="card-text fs-3">
                                            <?php 
                                                $activeCount = 0;
                                                foreach ($stations as $station) {
                                                    if ($station['status'] === 'active') {
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
                        <div class="card station-card bg-warning text-dark">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="bi bi-cone-striped fs-1"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title">Under Maintenance</h5>
                                        <p class="card-text fs-3">
                                            <?php 
                                                $maintenanceCount = 0;
                                                foreach ($stations as $station) {
                                                    if ($station['status'] === 'under_maintenance') {
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
                  <!-- Stations List -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold">Stations List</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="stationsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Code</th>
                                        <th>Address</th>
                                        <th>Coordinates</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stations as $station): ?>
                                        <tr>
                                            <td><?php echo $station['id']; ?></td>
                                            <td>
                                                <strong><?php echo $station['name']; ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $station['code']; ?></span>
                                            </td>
                                            <td><?php echo $station['address']; ?></td>
                                            <td>
                                                <small><?php echo $station['latitude']; ?>, <?php echo $station['longitude']; ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = '';
                                                switch ($station['status']) {
                                                    case 'active':
                                                        $statusClass = 'success';
                                                        break;
                                                    case 'inactive':
                                                        $statusClass = 'warning';
                                                        break;
                                                    case 'under_maintenance':
                                                        $statusClass = 'danger';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass; ?>">
                                                    <?php echo str_replace('_', ' ', ucfirst($station['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="?action=edit&id=<?php echo $station['id']; ?>" class="btn btn-sm btn-primary" title="Edit Station">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger" title="Delete Station" 
                                                            data-bs-toggle="modal" data-bs-target="#deleteStationModal<?php echo $station['id']; ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                      <!-- Delete Station Modal -->
                                                    <div class="modal fade" id="deleteStationModal<?php echo $station['id']; ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-danger text-white">
                                                                    <h5 class="modal-title">
                                                                        <i class="bi bi-exclamation-triangle me-2"></i>Delete Station
                                                                    </h5>
                                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="text-center mb-3">
                                                                        <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 3rem;"></i>
                                                                    </div>
                                                                    <p class="text-center">Are you sure you want to delete station <strong><?php echo $station['name']; ?> (<?php echo $station['code']; ?>)</strong>?</p>
                                                                    <div class="alert alert-warning">
                                                                        <i class="bi bi-info-circle me-2"></i>You cannot delete a station if it is used in any active route or has bookings associated with it.
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                        <i class="bi bi-x-circle me-2"></i>Cancel
                                                                    </button>
                                                                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                                                                        <input type="hidden" name="delete_id" value="<?php echo $station['id']; ?>">
                                                                        <button type="submit" name="delete_station" class="btn btn-danger">
                                                                            <i class="bi bi-trash me-2"></i>Delete Station
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
      <!-- Add/Edit Station Modal -->
    <div class="modal fade" id="<?php echo ($action === 'edit') ? 'editStationModal' : 'addStationModal'; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-<?php echo ($action === 'edit') ? 'pencil' : 'plus-circle'; ?> me-2"></i>
                        <?php echo ($action === 'edit') ? 'Edit Station' : 'Add New Station'; ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="stationForm">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="id" value="<?php echo $formStation['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Station Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $formStation['name']; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="code" class="form-label">Station Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="code" name="code" value="<?php echo $formStation['code']; ?>" 
                                       pattern="[A-Z0-9]{2,10}" title="2-10 uppercase letters or numbers" required>
                                <div class="form-text">2-10 characters, uppercase letters and numbers only</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="address" name="address" rows="2" required><?php echo $formStation['address']; ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="latitude" class="form-label">Latitude <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                    <input type="number" class="form-control" id="latitude" name="latitude" 
                                           step="0.000001" min="-90" max="90" value="<?php echo $formStation['latitude']; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="longitude" class="form-label">Longitude <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-geo"></i></span>
                                    <input type="number" class="form-control" id="longitude" name="longitude" 
                                           step="0.000001" min="-180" max="180" value="<?php echo $formStation['longitude']; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" <?php echo $formStation['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $formStation['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="under_maintenance" <?php echo $formStation['status'] === 'under_maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="<?php echo ($action === 'edit') ? 'update_station' : 'add_station'; ?>" class="btn btn-primary">
                            <i class="bi bi-<?php echo ($action === 'edit') ? 'save' : 'plus-circle'; ?> me-1"></i>
                            <?php echo ($action === 'edit') ? 'Update Station' : 'Add Station'; ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
      <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable with modern styling
            $('#stationsTable').DataTable({
                order: [[0, 'asc']],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                language: {
                    search: "<i class='bi bi-search'></i> _INPUT_",
                    searchPlaceholder: "Search stations...",
                    lengthMenu: "<i class='bi bi-list'></i> Show _MENU_ stations",
                    info: "Showing _START_ to _END_ of _TOTAL_ stations",
                    paginate: {
                        first: "<i class='bi bi-chevron-double-left'></i>",
                        last: "<i class='bi bi-chevron-double-right'></i>",
                        next: "<i class='bi bi-chevron-right'></i>",
                        previous: "<i class='bi bi-chevron-left'></i>"
                    }
                }
            });
            
            <?php if ($action === 'edit'): ?>
                // Show edit modal on page load
                var editModal = new bootstrap.Modal(document.getElementById('editStationModal'));
                editModal.show();
            <?php endif; ?>
        });
    </script>
</body>
</html>
