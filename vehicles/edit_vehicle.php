<?php 
include '../config/database.php';
checkAuth();
include '../includes/header.php';

if (!isset($_GET['id'])) {
    header("Location: view_vehicles.php");
    exit();
}

$vehicle_id = intval($_GET['id']); // Sanitize input

// Fetch vehicle details with owner information
$sql = "SELECT v.*, u.full_name as owner_full_name 
        FROM vehicles v 
        LEFT JOIN users u ON v.owner_name = u.username 
        WHERE v.id = $vehicle_id";
$result = $conn->query($sql);

if (!$result) {
    // SQL error handling
    die("Database query failed: " . $conn->error);
}

if ($result->num_rows === 0) {
    echo "<div class='alert alert-danger'>Vehicle not found!</div>";
    include '../includes/footer.php';
    exit();
}

$vehicle = $result->fetch_assoc();

if ($_POST) {
    $vehicle_type = $conn->real_escape_string($_POST['vehicle_type']);
    $make_model = $conn->real_escape_string($_POST['make_model']);
    $reg_number = $conn->real_escape_string($_POST['reg_number']);
    $chassis_number = $conn->real_escape_string($_POST['chassis_number']);
    $owner_name = $conn->real_escape_string($_POST['owner_name']); // Updated owner field
    $updated_by_user = $_SESSION['username'] ?? 'admin'; // Auto-filled with logged in user
    
    // Check if registration number already exists (excluding current vehicle)
    $check_sql = "SELECT id FROM vehicles WHERE reg_number = '$reg_number' AND id != $vehicle_id";
    $check_result = $conn->query($check_sql);
    
    if ($check_result && $check_result->num_rows > 0) {
        echo '<div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> Error: Registration number already exists!
              </div>';
    } else {
        // Check if chassis number already exists (excluding current vehicle)
        $check_chassis_sql = "SELECT id FROM vehicles WHERE chassis_number = '$chassis_number' AND id != $vehicle_id";
        $check_chassis_result = $conn->query($check_chassis_sql);
        
        if ($check_chassis_result && $check_chassis_result->num_rows > 0) {
            echo '<div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> Error: Chassis number already exists!
                  </div>';
        } else {
            $update_sql = "UPDATE vehicles SET 
                    vehicle_type = '$vehicle_type',
                    make_model = '$make_model', 
                    reg_number = '$reg_number',
                    chassis_number = '$chassis_number',
                    owner_name = '$owner_name',
                    updated_by_user = '$updated_by_user',
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = $vehicle_id";
            
            if ($conn->query($update_sql) === TRUE) {
                echo '<div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Vehicle updated successfully!
                      </div>';
                
                // Refresh vehicle data using the original query
                $refresh_result = $conn->query($sql);
                if ($refresh_result && $refresh_result->num_rows > 0) {
                    $vehicle = $refresh_result->fetch_assoc();
                }
            } else {
                echo '<div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> Error updating vehicle: ' . $conn->error . '
                      </div>';
            }
        }
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-edit"></i> Edit Vehicle
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="view_vehicles.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Vehicles
        </a>
    </div>
</div>

<div class="card fade-in">
    <div class="card-body">
        <form method="POST" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                <select name="vehicle_type" class="form-select" required>
                    <option value="">Select Type</option>
                    <option value="bike" <?php echo $vehicle['vehicle_type'] == 'bike' ? 'selected' : ''; ?>>Bike</option>
                    <option value="car" <?php echo $vehicle['vehicle_type'] == 'car' ? 'selected' : ''; ?>>Car</option>
                </select>
            </div>
            
            <div class="col-md-6">
                <label class="form-label">Make & Model <span class="text-danger">*</span></label>
                <input type="text" name="make_model" class="form-control" value="<?php echo htmlspecialchars($vehicle['make_model']); ?>" required>
            </div>
            
            <div class="col-md-6">
                <label class="form-label">Registration Number <span class="text-danger">*</span></label>
                <input type="text" name="reg_number" class="form-control" value="<?php echo htmlspecialchars($vehicle['reg_number']); ?>" required>
                <div class="form-text">Unique registration number</div>
            </div>
            
            <div class="col-md-6">
                <label class="form-label">Chassis Number <span class="text-danger">*</span></label>
                <input type="text" name="chassis_number" class="form-control" value="<?php echo htmlspecialchars($vehicle['chassis_number']); ?>" required>
                <div class="form-text">Unique chassis number</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Vehicle Owner <span class="text-danger">*</span></label>
                <select name="owner_name" class="form-select" required id="ownerSelect">
                    <option value="">Select Owner</option>
                    <?php
                    // Fetch all users from users table
                    $users_sql = "SELECT id, username, full_name FROM users ORDER BY full_name";
                    $users_result = $conn->query($users_sql);
                    
                    if ($users_result && $users_result->num_rows > 0) {
                        while($user = $users_result->fetch_assoc()) {
                            $selected = ($vehicle['owner_name'] ?? '') == $user['username'] ? 'selected' : '';
                            $display_name = $user['full_name'] ? $user['full_name'] . ' (' . $user['username'] . ')' : $user['username'];
                            echo "<option value='{$user['username']}' $selected>{$display_name}</option>";
                        }
                    } else {
                        // If no users in database, show current owner
                        echo "<option value='{$vehicle['owner_name']}' selected>{$vehicle['owner_name']} (Current Owner)</option>";
                    }
                    ?>
                </select>
                <div class="form-text">Select the owner of this vehicle</div>
            </div>
            
            <div class="col-md-6">
                <label class="form-label">Updated By</label>
                <input type="text" class="form-control" 
                       value="<?php echo $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin'; ?>" 
                       readonly style="background-color: #f8f9fa;">
                <div class="form-text">You (Auto-filled)</div>
            </div>
            
            <div class="col-md-6">
                <label class="form-label">Last Updated</label>
                <input type="text" class="form-control" 
                       value="<?php echo $vehicle['updated_at'] ? date('d M Y, h:i A', strtotime($vehicle['updated_at'])) : 'Never'; ?>" 
                       readonly style="background-color: #f8f9fa;">
                <div class="form-text">Last update timestamp</div>
            </div>
            
            <div class="col-12">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="view_vehicles.php" class="btn btn-secondary me-md-2">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Vehicle
                    </button>
                </div>
            </div>
        </form>
        
        <!-- Vehicle Information -->
        <div hidden class="row mt-4">
            <div class="col-md-6">
                <div class="card bg-light">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-info-circle"></i> Original Vehicle Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th width="40%">Added By:</th>
                                <td><?php echo htmlspecialchars($vehicle['added_by_user'] ?? 'Unknown'); ?></td>
                            </tr>
                            <tr>
                                <th>Added Date:</th>
                                <td><?php echo date('d M Y, h:i A', strtotime($vehicle['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <th>Original Owner:</th>
                                <td><strong><?php echo htmlspecialchars($vehicle['owner_full_name'] ?? $vehicle['owner_name'] ?? 'Unknown'); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Original Registration:</th>
                                <td><code><?php echo htmlspecialchars($vehicle['reg_number']); ?></code></td>
                            </tr>
                            <tr>
                                <th>Original Chassis:</th>
                                <td><code><?php echo htmlspecialchars($vehicle['chassis_number']); ?></code></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vehicle Statistics -->
<div hidden class="card mt-4 fade-in">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-chart-bar"></i> Vehicle Statistics
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php
            $stats_sql = "SELECT 
                         COUNT(*) as total_services,
                         SUM(cost) as total_cost,
                         AVG(cost) as avg_cost,
                         MAX(service_date) as last_service,
                         MIN(service_date) as first_service
                         FROM services 
                         WHERE vehicle_id = $vehicle_id";
            $stats_result = $conn->query($stats_sql);
            
            if ($stats_result) {
                $stats = $stats_result->fetch_assoc();
                
                // Calculate days since last service
                $days_since_last = $stats['last_service'] ? 
                    floor((time() - strtotime($stats['last_service'])) / (60 * 60 * 24)) : 
                    null;
            } else {
                $stats = [
                    'total_services' => 0,
                    'total_cost' => 0,
                    'avg_cost' => 0,
                    'last_service' => null,
                    'first_service' => null
                ];
                $days_since_last = null;
            }
            ?>
            
            <div class="col-md-3">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body text-center">
                        <h6 class="card-title">Total Services</h6>
                        <h2 class="card-text"><?php echo $stats['total_services']; ?></h2>
                        <small>All Time</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body text-center">
                        <h6 class="card-title">Total Cost</h6>
                        <h2 class="card-text">₹<?php echo number_format($stats['total_cost'] ?? 0, 2); ?></h2>
                        <small>All Services</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body text-center">
                        <h6 class="card-title">Average Cost</h6>
                        <h2 class="card-text">₹<?php echo number_format($stats['avg_cost'] ?? 0, 2); ?></h2>
                        <small>Per Service</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card text-white bg-info mb-3">
                    <div class="card-body text-center">
                        <h6 class="card-title">Last Service</h6>
                        <h4 class="card-text">
                            <?php if ($stats['last_service']): ?>
                                <?php echo date('d M Y', strtotime($stats['last_service'])); ?>
                            <?php else: ?>
                                Never
                            <?php endif; ?>
                        </h4>
                        <small>
                            <?php if ($days_since_last): ?>
                                <?php echo $days_since_last; ?> days ago
                            <?php else: ?>
                                No services
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Service History Summary -->
        <?php if ($stats['total_services'] > 0): ?>
        <div class="row mt-3">
            <div class="col-12">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6>Service History Summary</h6>
                        <div class="row text-center">
                            <div class="col-md-4">
                                <strong>First Service:</strong><br>
                                <?php echo date('d M Y', strtotime($stats['first_service'])); ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Service Period:</strong><br>
                                <?php
                                $service_days = (strtotime($stats['last_service']) - strtotime($stats['first_service'])) / (60 * 60 * 24);
                                echo round($service_days) . ' days';
                                ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Service Frequency:</strong><br>
                                <?php
                                $avg_days = $service_days / ($stats['total_services'] - 1);
                                echo round($avg_days) . ' days average';
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="d-grid gap-2 d-md-flex">
                    <a href="../services/add_service.php?vehicle_id=<?php echo $vehicle_id; ?>" class="btn btn-success me-2">
                        <i class="fas fa-plus"></i> Add New Service
                    </a>
                    <a href="../services/service_history.php?vehicle_id=<?php echo $vehicle_id; ?>" class="btn btn-info me-2">
                        <i class="fas fa-history"></i> View Service History
                    </a>
                    <a href="view_vehicles.php" class="btn btn-secondary">
                        <i class="fas fa-list"></i> Back to Vehicles List
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Add new owner option if not in list
document.addEventListener('DOMContentLoaded', function() {
    const ownerSelect = document.getElementById('ownerSelect');
    
    // Check if ownerSelect exists
    if (ownerSelect) {
        // Add "Add New Owner" option
        const addNewOption = document.createElement('option');
        addNewOption.value = 'new_owner';
        addNewOption.textContent = '+ Add New Owner';
        ownerSelect.appendChild(addNewOption);
        
        ownerSelect.addEventListener('change', function() {
            if (this.value === 'new_owner') {
                const newOwnerName = prompt('Enter new owner name:');
                if (newOwnerName && newOwnerName.trim() !== '') {
                    // Add new option to select
                    const newOption = document.createElement('option');
                    newOption.value = newOwnerName.trim();
                    newOption.textContent = newOwnerName.trim() + ' (New)';
                    newOption.selected = true;
                    
                    // Insert before the "Add New Owner" option
                    ownerSelect.insertBefore(newOption, addNewOption);
                } else {
                    this.selectedIndex = 0;
                }
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>