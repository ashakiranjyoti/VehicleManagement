<?php 
include '../config/database.php';
checkAuth();
include '../includes/header.php'; 
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-chart-bar"></i> Monthly Reports
    </h1>
</div>

<!-- Report Filters -->
<div class="card mb-4 fade-in">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Month</label>
                <select name="month" class="form-select">
                    <?php
                    $months = [
                        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                    ];
                    $selected_month = $_GET['month'] ?? date('n');
                    foreach ($months as $num => $name) {
                        $selected = $num == $selected_month ? 'selected' : '';
                        echo "<option value='$num' $selected>$name</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Year</label>
                <select name="year" class="form-select">
                    <?php
                    $current_year = date('Y');
                    for ($year = $current_year; $year >= 2020; $year--) {
                        $selected = ($_GET['year'] ?? $current_year) == $year ? 'selected' : '';
                        echo "<option value='$year' $selected>$year</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Vehicle Type</label>
                <select name="vehicle_type" class="form-select">
                    <option value="">All Types</option>
                    <option value="bike" <?php echo ($_GET['vehicle_type'] ?? '') == 'bike' ? 'selected' : ''; ?>>Bike</option>
                    <option value="car" <?php echo ($_GET['vehicle_type'] ?? '') == 'car' ? 'selected' : ''; ?>>Car</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Generate Report
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
// Generate report based on filters
$month = $_GET['month'] ?? date('n');
$year = $_GET['year'] ?? date('Y');
$vehicle_type = $_GET['vehicle_type'] ?? '';

$where = "WHERE MONTH(s.service_date) = $month AND YEAR(s.service_date) = $year";
if (!empty($vehicle_type)) {
    $where .= " AND v.vehicle_type = '$vehicle_type'";
}

// Monthly Summary
$summary_sql = "SELECT 
                COUNT(DISTINCT s.vehicle_id) as total_vehicles,
                COUNT(s.id) as total_services,
                SUM(s.cost) as total_cost,
                AVG(s.cost) as avg_cost
                FROM services s
                JOIN vehicles v ON s.vehicle_id = v.id
                $where";
$summary_result = $conn->query($summary_sql);
$summary = $summary_result->fetch_assoc();
?>

<!-- Summary Cards -->
<div class="row mb-4 fade-in">
    <div class="col-md-3">
        <div class="card card-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Total Vehicles Serviced</h6>
                <h2 class="stats-number"><?php echo $summary['total_vehicles']; ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card card-success text-white">
            <div class="card-body">
                <h6 class="card-title">Total Services</h6>
                <h2 class="stats-number"><?php echo $summary['total_services']; ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card card-warning text-white">
            <div class="card-body">
                <h6 class="card-title">Total Cost</h6>
                <h2 class="stats-number">₹<?php echo number_format($summary['total_cost'], 2); ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card card-danger text-white">
            <div class="card-body">
                <h6 class="card-title">Average Cost/Service</h6>
                <h2 class="stats-number">₹<?php echo number_format($summary['avg_cost'], 2); ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Report -->
<div class="card fade-in">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-list"></i> Service Details - <?php echo $months[$month] . ' ' . $year; ?>
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Vehicle</th>
                        <th>Service Type</th>
                        <th>Description</th>
                        <th>Cost</th>
                        <th>Created By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $details_sql = "SELECT s.*, v.make_model, v.reg_number, v.vehicle_type
                                   FROM services s
                                   JOIN vehicles v ON s.vehicle_id = v.id
                                   $where
                                   ORDER BY s.service_date DESC";
                    $details_result = $conn->query($details_sql);
                    
                    if ($details_result->num_rows > 0) {
                        while($row = $details_result->fetch_assoc()) {
                            $icon = $row['vehicle_type'] == 'bike' ? 'fas fa-motorcycle' : 'fas fa-car';
                            
                            echo "<tr>
                                    <td>{$row['service_date']}</td>
                                    <td>
                                        <i class='$icon'></i> 
                                        {$row['make_model']} 
                                        <br><small class='text-muted'>{$row['reg_number']}</small>
                                    </td>
                                    <td><span class='badge-service'>{$row['service_type']}</span></td>
                                    <td>{$row['description']}</td>
                                    <td>₹" . number_format($row['cost'], 2) . "</td>
                                    <td>{$row['created_by']}</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center'>No services found for selected period</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>