<?php 
include '../config/database.php';
checkAuth();
include '../includes/header.php'; 

// Debug function to check database
function debugDatabase($conn) {
    echo "<div class='alert alert-info'>";
    echo "<h6>Database Debug Information:</h6>";
    
    // Check vehicles table
    $check_vehicles = $conn->query("SELECT COUNT(*) as total FROM vehicles");
    if ($check_vehicles) {
        $vehicles_count = $check_vehicles->fetch_assoc();
        echo "Total Vehicles: " . $vehicles_count['total'] . "<br>";
    } else {
        echo "Error checking vehicles: " . $conn->error . "<br>";
    }
    
    // Check services table
    $check_services = $conn->query("SELECT COUNT(*) as total FROM services");
    if ($check_services) {
        $services_count = $check_services->fetch_assoc();
        echo "Total Services: " . $services_count['total'] . "<br>";
    } else {
        echo "Error checking services: " . $conn->error . "<br>";
    }
    
    echo "</div>";
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-search"></i> Registration Number Service Report
    </h1>
</div>

<?php
// Show debug info if no vehicles found
$check_vehicles = $conn->query("SELECT COUNT(*) as total FROM vehicles");
if ($check_vehicles) {
    $vehicles_count = $check_vehicles->fetch_assoc();
    if ($vehicles_count['total'] == 0) {
        echo "<div class='alert alert-warning'>
                <h5><i class='fas fa-exclamation-triangle'></i> No Vehicles Found!</h5>
                <p>Please add vehicles first to generate reports.</p>
                <a href='../vehicles/add_vehicle.php' class='btn btn-primary btn-sm'>
                    <i class='fas fa-plus'></i> Add First Vehicle
                </a>
              </div>";
    }
}
?>

<!-- Search Form -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Search by Registration Number</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Registration Number <span class="text-danger">*</span></label>
                <select name="reg_number" class="form-select" required>
                    <option value="">Select Registration Number</option>
                    <?php
                    $vehicles_sql = "SELECT reg_number, make_model, vehicle_type FROM vehicles ORDER BY reg_number";
                    $vehicles_result = $conn->query($vehicles_sql);
                    
                    if ($vehicles_result && $vehicles_result->num_rows > 0) {
                        while($vehicle = $vehicles_result->fetch_assoc()) {
                            $selected = ($_GET['reg_number'] ?? '') == $vehicle['reg_number'] ? 'selected' : '';
                            $icon = $vehicle['vehicle_type'] == 'bike' ? '🏍️' : '🚗';
                            echo "<option value='{$vehicle['reg_number']}' $selected>{$icon} {$vehicle['reg_number']} - {$vehicle['make_model']}</option>";
                        }
                    } else {
                        echo "<option value='' disabled>No vehicles found in database</option>";
                    }
                    ?>
                </select>
                <?php if ($vehicles_result && $vehicles_result->num_rows == 0): ?>
                <div class="form-text text-danger">
                    <i class="fas fa-exclamation-circle"></i> No vehicles found. 
                    <a href="../vehicles/add_vehicle.php">Add your first vehicle</a>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?php echo $_GET['from_date'] ?? ''; ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?php echo $_GET['to_date'] ?? ''; ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
        </form>
        
        <!-- Quick Registration Numbers -->
        <?php
        $quick_vehicles_sql = "SELECT reg_number, make_model FROM vehicles ORDER BY created_at DESC LIMIT 5";
        $quick_vehicles_result = $conn->query($quick_vehicles_sql);
        
        if ($quick_vehicles_result && $quick_vehicles_result->num_rows > 0):
        ?>
        <div class="mt-3">
            <small class="text-muted">Quick Search: </small>
            <?php
            while($vehicle = $quick_vehicles_result->fetch_assoc()) {
                echo "<a href='?reg_number={$vehicle['reg_number']}' class='btn btn-sm btn-outline-secondary me-1 mb-1'>{$vehicle['reg_number']}</a>";
            }
            ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Show debug information if requested
if (isset($_GET['debug'])) {
    debugDatabase($conn);
}

if (isset($_GET['reg_number']) && !empty($_GET['reg_number'])) {
    $reg_number = trim($_GET['reg_number']);
    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';
    
    // Get vehicle details
    $vehicle_sql = "SELECT * FROM vehicles WHERE reg_number = '" . $conn->real_escape_string($reg_number) . "'";
    $vehicle_result = $conn->query($vehicle_sql);
    
    if ($vehicle_result && $vehicle_result->num_rows > 0) {
        $vehicle = $vehicle_result->fetch_assoc();
        $vehicle_id = $vehicle['id'];
        
        // Build where clause for services
        $where = "WHERE s.vehicle_id = $vehicle_id";
        $date_range_text = "";
        
        if (!empty($from_date) && !empty($to_date)) {
            $where .= " AND s.service_date BETWEEN '$from_date' AND '$to_date'";
            $date_range_text = " from " . date('d M Y', strtotime($from_date)) . " to " . date('d M Y', strtotime($to_date));
        } elseif (!empty($from_date)) {
            $where .= " AND s.service_date >= '$from_date'";
            $date_range_text = " from " . date('d M Y', strtotime($from_date));
        } elseif (!empty($to_date)) {
            $where .= " AND s.service_date <= '$to_date'";
            $date_range_text = " up to " . date('d M Y', strtotime($to_date));
        }
        
        // Get service statistics
        $stats_sql = "SELECT 
                     COUNT(*) as total_services,
                     SUM(s.cost) as total_cost,
                     AVG(s.cost) as avg_cost,
                     MIN(s.service_date) as first_service,
                     MAX(s.service_date) as last_service,
                     MIN(s.cost) as min_cost,
                     MAX(s.cost) as max_cost
                     FROM services s 
                     $where";
        
        $stats_result = $conn->query($stats_sql);
        if ($stats_result) {
            $stats = $stats_result->fetch_assoc();
        } else {
            $stats = [
                'total_services' => 0,
                'total_cost' => 0,
                'avg_cost' => 0,
                'first_service' => null,
                'last_service' => null,
                'min_cost' => 0,
                'max_cost' => 0
            ];
        }
?>

<!-- Vehicle Summary -->
<div class="card shadow mb-4">
    <div class="card-header py-3 bg-primary text-white">
        <h6 class="m-0 font-weight-bold">
            <i class="fas fa-car-side"></i> Vehicle Summary - <?php echo htmlspecialchars($vehicle['reg_number']); ?>
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="card bg-light mb-3">
                    <div class="card-body text-center">
                        <h6 class="card-title text-primary">Total Services</h6>
                        <h2 class="text-primary"><?php echo $stats['total_services']; ?></h2>
                        <small class="text-muted"><?php echo $date_range_text; ?></small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card bg-light mb-3">
                    <div class="card-body text-center">
                        <h6 class="card-title text-success">Total Cost</h6>
                        <h2 class="text-success">₹<?php echo number_format($stats['total_cost'], 2); ?></h2>
                        <small class="text-muted">All services</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card bg-light mb-3">
                    <div class="card-body text-center">
                        <h6 class="card-title text-warning">Average Cost</h6>
                        <h2 class="text-warning">₹<?php echo number_format($stats['avg_cost'], 2); ?></h2>
                        <small class="text-muted">Per service</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card bg-light mb-3">
                    <div class="card-body text-center">
                        <h6 class="card-title text-info">Service Range</h6>
                        <h6 class="text-info">
                            <?php echo $stats['first_service'] ? date('M Y', strtotime($stats['first_service'])) : 'N/A'; ?> - 
                            <?php echo $stats['last_service'] ? date('M Y', strtotime($stats['last_service'])) : 'N/A'; ?>
                        </h6>
                        <small class="text-muted">First to Last</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Vehicle Details -->
        <div class="row mt-3">
            <div class="col-md-6">
                <table class="table table-sm table-bordered">
                    <tr>
                        <th width="40%">Make & Model:</th>
                        <td><?php echo htmlspecialchars($vehicle['make_model']); ?></td>
                    </tr>
                    <tr>
                        <th>Vehicle Type:</th>
                        <td>
                            <span class="badge <?php echo $vehicle['vehicle_type'] == 'bike' ? 'bg-primary' : 'bg-success'; ?>">
                                <i class="fas fa-<?php echo $vehicle['vehicle_type'] == 'bike' ? 'motorcycle' : 'car'; ?>"></i>
                                <?php echo ucfirst($vehicle['vehicle_type']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Chassis Number:</th>
                        <td><code><?php echo htmlspecialchars($vehicle['chassis_number']); ?></code></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-bordered">
                    <tr>
                        <th width="40%">Cost Range:</th>
                        <td>
                            <?php if ($stats['min_cost'] > 0): ?>
                                ₹<?php echo number_format($stats['min_cost'], 2); ?> - ₹<?php echo number_format($stats['max_cost'], 2); ?>
                            <?php else: ?>
                                No services
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Last Service:</th>
                        <td>
                            <?php if ($stats['last_service']): ?>
                                <?php echo date('d M Y', strtotime($stats['last_service'])); ?>
                                (<?php echo floor((time() - strtotime($stats['last_service'])) / (60 * 60 * 24)); ?> days ago)
                            <?php else: ?>
                                Never Serviced
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Service Frequency:</th>
                        <td>
                            <?php
                            if ($stats['total_services'] > 1 && $stats['first_service'] && $stats['last_service']) {
                                $days_between = (strtotime($stats['last_service']) - strtotime($stats['first_service'])) / (60 * 60 * 24);
                                $avg_days = $days_between / ($stats['total_services'] - 1);
                                echo round($avg_days) . " days average";
                            } else {
                                echo "N/A";
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Service Frequency by Month -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Service Frequency - Monthly Breakdown</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Year</th>
                        <th>Month</th>
                        <th>Services Count</th>
                        <th>Monthly Cost</th>
                        <th>Average Cost</th>
                        <th>Trend</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $frequency_sql = "SELECT 
                                     YEAR(service_date) as year,
                                     MONTH(service_date) as month,
                                     COUNT(*) as service_count,
                                     SUM(cost) as monthly_cost
                                     FROM services 
                                     WHERE vehicle_id = $vehicle_id
                                     GROUP BY YEAR(service_date), MONTH(service_date)
                                     ORDER BY year DESC, month DESC";
                    
                    $frequency_result = $conn->query($frequency_sql);
                    
                    if ($frequency_result && $frequency_result->num_rows > 0) {
                        $previous_count = 0;
                        while($freq = $frequency_result->fetch_assoc()) {
                            $month_name = date('F', mktime(0, 0, 0, $freq['month'], 1));
                            $avg_cost = $freq['service_count'] > 0 ? $freq['monthly_cost'] / $freq['service_count'] : 0;
                            
                            $trend = '';
                            if ($previous_count > 0) {
                                $change = (($freq['service_count'] - $previous_count) / $previous_count) * 100;
                                if ($change > 0) {
                                    $trend = "<span class='text-success'><i class='fas fa-arrow-up'></i> " . number_format($change, 0) . "%</span>";
                                } elseif ($change < 0) {
                                    $trend = "<span class='text-danger'><i class='fas fa-arrow-down'></i> " . number_format(abs($change), 0) . "%</span>";
                                } else {
                                    $trend = "<span class='text-muted'>No change</span>";
                                }
                            }
                            $previous_count = $freq['service_count'];
                            
                            echo "<tr>
                                    <td><strong>{$freq['year']}</strong></td>
                                    <td>{$month_name}</td>
                                    <td>
                                        <span class='badge bg-primary'>{$freq['service_count']} services</span>
                                    </td>
                                    <td>₹" . number_format($freq['monthly_cost'], 2) . "</td>
                                    <td>₹" . number_format($avg_cost, 2) . "</td>
                                    <td>{$trend}</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center'>No service frequency data available</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Detailed Service History -->
<div hidden class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Detailed Service History</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="serviceTable">
                <thead class="table-light">
                    <tr>
                        <th>Service Date</th>
                        <th>Service Type</th>
                        <th>Description</th>
                        <th>Cost</th>
                        <th>Created By</th>
                        <th>Bill</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $services_sql = "SELECT * FROM services $where ORDER BY service_date DESC, service_time DESC";
                    $services_result = $conn->query($services_sql);
                    
                    if ($services_result && $services_result->num_rows > 0) {
                        while($service = $services_result->fetch_assoc()) {
                            $bill_link = $service['bill_file'] ? 
                                "<a href='../uploads/{$service['bill_file']}' target='_blank' class='btn btn-sm btn-outline-info'>
                                    <i class='fas fa-file-invoice'></i> View
                                </a>" : 
                                "<span class='text-muted'>No Bill</span>";
                            
                            echo "<tr>
                                    <td>
                                        <strong>" . date('d M Y', strtotime($service['service_date'])) . "</strong><br>
                                        <small class='text-muted'>{$service['service_time']}</small>
                                    </td>
                                    <td>
                                        <span class='badge bg-success'>{$service['service_type']}</span>
                                    </td>
                                    <td>" . ($service['description'] ?: '<span class="text-muted">No description</span>') . "</td>
                                    <td>
                                        <strong class='text-primary'>₹" . number_format($service['cost'], 2) . "</strong>
                                    </td>
                                    <td>{$service['created_by']}</td>
                                    <td>{$bill_link}</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center'>No service records found for the selected criteria</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <!-- Export Button -->
        <?php if ($services_result && $services_result->num_rows > 0): ?>
        <div class="mt-3 text-end">
            <button onclick="exportToCSV()" class="btn btn-success">
                <i class="fas fa-download"></i> Export to CSV
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
    } else {
        echo "<div class='alert alert-warning'>Vehicle with registration number '{$reg_number}' not found!</div>";
    }
} elseif (isset($_GET['reg_number'])) {
    echo "<div class='alert alert-warning'>Please select a registration number!</div>";
}
?>

<!-- CSV Export Script -->
<script>
function exportToCSV() {
    const table = document.getElementById('serviceTable');
    const rows = table.querySelectorAll('tr');
    const csv = [];
    
    for (let i = 0; i < rows.length; i++) {
        const row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ');
            data = data.replace(/"/g, '""');
            row.push('"' + data + '"');
        }
        
        csv.push(row.join(','));
    }
    
    const csvString = csv.join('\n');
    const blob = new Blob([csvString], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.setAttribute('hidden', '');
    a.setAttribute('href', url);
    a.setAttribute('download', 'vehicle_services_<?php echo isset($reg_number) ? $reg_number : 'report'; ?>.csv');
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}
</script>

<?php include '../includes/footer.php'; ?>