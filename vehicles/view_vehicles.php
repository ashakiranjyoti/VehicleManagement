<?php 
include '../config/database.php';
checkAuth();
include '../includes/header.php'; 
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-motorcycle"></i> Vehicles
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add_vehicle.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Vehicle
        </a>
    </div>
</div>

<!-- Search Form -->
<div class="card mb-4 fade-in">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-search"></i> Search & Filter Vehicles
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Reg No, Chassis, Model..." 
                       value="<?php echo $_GET['search'] ?? ''; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Vehicle Type</label>
                <select name="vehicle_type" class="form-select">
                    <option value="">All Types</option>
                    <option value="bike" <?php echo ($_GET['vehicle_type'] ?? '') == 'bike' ? 'selected' : ''; ?>>Bike</option>
                    <option value="car" <?php echo ($_GET['vehicle_type'] ?? '') == 'car' ? 'selected' : ''; ?>>Car</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Owner</label>
                <select name="owner" class="form-select">
                    <option value="">All Owners</option>
                    <?php
                    // Safe query for owners
                    $owners_sql = "SELECT DISTINCT v.owner_name FROM vehicles v ORDER BY v.owner_name";
                    $owners_result = $conn->query($owners_sql);
                    if ($owners_result && $owners_result->num_rows > 0) {
                        while($owner = $owners_result->fetch_assoc()) {
                            $selected = ($_GET['owner'] ?? '') == $owner['owner_name'] ? 'selected' : '';
                            echo "<option value='{$owner['owner_name']}' $selected>{$owner['owner_name']}</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <a href="view_vehicles.php" class="btn btn-secondary w-100">
                    <i class="fas fa-refresh"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card fade-in">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-list"></i> Vehicle List
        </h5>
    </div>
    <div class="card-body">
        <?php
        // Build WHERE clause safely
        $where = "WHERE 1=1";
        $search = $_GET['search'] ?? '';
        $vehicle_type = $_GET['vehicle_type'] ?? '';
        $owner_filter = $_GET['owner'] ?? '';
        
        if (!empty($search)) {
            $safe_search = $conn->real_escape_string($search);
            $where .= " AND (v.reg_number LIKE '%$safe_search%' OR v.chassis_number LIKE '%$safe_search%' OR v.make_model LIKE '%$safe_search%' OR v.owner_name LIKE '%$safe_search%')";
        }
        
        if (!empty($vehicle_type)) {
            $safe_type = $conn->real_escape_string($vehicle_type);
            $where .= " AND v.vehicle_type = '$safe_type'";
        }
        
        if (!empty($owner_filter)) {
            $safe_owner = $conn->real_escape_string($owner_filter);
            $where .= " AND v.owner_name = '$safe_owner'";
        }
        
        // Main query for vehicles
        $sql = "SELECT v.*, 
               (SELECT COUNT(*) FROM services s WHERE s.vehicle_id = v.id) as service_count,
               (SELECT SUM(cost) FROM services s WHERE s.vehicle_id = v.id) as total_cost
               FROM vehicles v 
               $where 
               ORDER BY v.created_at DESC";
        
        $result = $conn->query($sql);
        
        if (!$result) {
            // SQL error - show helpful message
            echo '<div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> Database Error</h5>
                    <p>Error executing query: ' . $conn->error . '</p>
                    <small>Please check if vehicles table exists and has proper structure.</small>
                  </div>';
            include '../includes/footer.php';
            exit();
        }
        ?>
        
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Type</th>
                        <th>Vehicle Details</th>
                        <th>Registration No</th>
                        <th>Chassis No</th>
                        <th>Owner</th>
                        <th>Added By</th>
                        <th>Added Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $icon = $row['vehicle_type'] == 'bike' ? 'fas fa-motorcycle' : 'fas fa-car';
                            $badge_class = $row['vehicle_type'] == 'bike' ? 'bg-primary' : 'bg-success';
                            $owner_display = $row['owner_name'] ? $row['owner_name'] : 'Not specified';
                            
                            echo "<tr>
                                    <td>
                                        <span class='badge $badge_class'>
                                            <i class='$icon'></i> " . ucfirst($row['vehicle_type']) . "
                                        </span>
                                    </td>
                                    <td>
                                        <strong>{$row['make_model']}</strong>
                                        <br>
                                        <small class='text-muted'>
                                            <i class='fas fa-tools'></i> Services: {$row['service_count']} | 
                                            <i class='fas fa-rupee-sign'></i> Total: ₹" . number_format($row['total_cost'] ?? 0, 2) . "
                                        </small>
                                    </td>
                                    <td>
                                        <code class='bg-light p-1 rounded'>{$row['reg_number']}</code>
                                    </td>
                                    <td>
                                        <small class='text-muted'>{$row['chassis_number']}</small>
                                    </td>
                                    <td>
                                        <strong>{$owner_display}</strong>
                                    </td>
                                    <td>
                                        <small>{$row['added_by_user']}</small>
                                    </td>
                                    <td>
                                        " . date('d M Y', strtotime($row['created_at'])) . "
                                        <br><small class='text-muted'>" . date('h:i A', strtotime($row['created_at'])) . "</small>
                                    </td>
                                    <td>
                                        <div class='btn-group btn-group-sm' role='group'>
                                            <a href='../services/service_history.php?vehicle_id={$row['id']}' class='btn btn-outline-primary' title='View Services'>
                                                <i class='fas fa-tools'></i>
                                            </a>
                                            <a href='../services/add_service.php?vehicle_id={$row['id']}' class='btn btn-outline-success' title='Add Service'>
                                                <i class='fas fa-plus'></i>
                                            </a>
                                            <a href='edit_vehicle.php?id={$row['id']}' class='btn btn-outline-warning' title='Edit Vehicle'>
                                                <i class='fas fa-edit'></i>
                                            </a>
                                            " . (isAdmin() ? "
                                            <a href='delete_vehicle.php?id={$row['id']}' class='btn btn-outline-danger' title='Delete' onclick='return confirm(\"Are you sure you want to delete this vehicle?\")'>
                                                <i class='fas fa-trash'></i>
                                            </a>
                                            " : "") . "
                                        </div>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr>
                                <td colspan='8' class='text-center py-4'>
                                    <i class='fas fa-info-circle fa-2x text-muted mb-3'></i><br>
                                    No vehicles found matching your criteria.
                                    <br><a href='add_vehicle.php' class='btn btn-primary btn-sm mt-2'>Add First Vehicle</a>
                                </td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <!-- Summary Stats -->
        <?php
        // Simplified stats query
        $stats_sql = "SELECT 
                     COUNT(*) as total_vehicles,
                     SUM(CASE WHEN vehicle_type = 'bike' THEN 1 ELSE 0 END) as bikes,
                     SUM(CASE WHEN vehicle_type = 'car' THEN 1 ELSE 0 END) as cars
                     FROM vehicles $where";
        
        $stats_result = $conn->query($stats_sql);
        
        if ($stats_result) {
            $stats = $stats_result->fetch_assoc();
        } else {
            $stats = [
                'total_vehicles' => 0,
                'bikes' => 0,
                'cars' => 0
            ];
        }
        
        // Get unique owners count
        $owners_count_sql = "SELECT COUNT(DISTINCT owner_name) as unique_owners FROM vehicles $where";
        $owners_count_result = $conn->query($owners_count_sql);
        $owners_count = $owners_count_result ? $owners_count_result->fetch_assoc()['unique_owners'] : 0;
        ?>
        
        <div hidden class="mt-4 p-4 bg-light rounded">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="card border-0 bg-white">
                        <div class="card-body">
                            <h4 class="text-primary"><?php echo $stats['total_vehicles']; ?></h4>
                            <small class="text-muted">Total Vehicles</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-white">
                        <div class="card-body">
                            <h4 class="text-success"><?php echo $stats['bikes']; ?></h4>
                            <small class="text-muted">Bikes</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-white">
                        <div class="card-body">
                            <h4 class="text-warning"><?php echo $stats['cars']; ?></h4>
                            <small class="text-muted">Cars</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-white">
                        <div class="card-body">
                            <h4 class="text-info"><?php echo $owners_count; ?></h4>
                            <small class="text-muted">Unique Owners</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Debug Information (Remove in production) -->
<?php if (isset($_GET['debug'])): ?>
<div class="card mt-4">
    <div class="card-header bg-dark text-white">
        <h6 class="m-0">Debug Information</h6>
    </div>
    <div class="card-body">
        <pre><?php
        echo "SQL Query: " . htmlspecialchars($sql) . "\n\n";
        echo "Tables Check:\n";
        
        // Check vehicles table
        $check = $conn->query("SHOW TABLES LIKE 'vehicles'");
        echo "Vehicles table exists: " . ($check->num_rows > 0 ? 'Yes' : 'No') . "\n";
        
        if ($check->num_rows > 0) {
            $columns = $conn->query("SHOW COLUMNS FROM vehicles");
            echo "Columns in vehicles table:\n";
            while($col = $columns->fetch_assoc()) {
                echo "  - " . $col['Field'] . "\n";
            }
        }
        
        echo "\nServices table exists: ";
        $check2 = $conn->query("SHOW TABLES LIKE 'services'");
        echo ($check2->num_rows > 0 ? 'Yes' : 'No') . "\n";
        ?></pre>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>