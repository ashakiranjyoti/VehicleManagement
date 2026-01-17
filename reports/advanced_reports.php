<?php 
include '../config/database.php';
checkAuth();
include '../includes/header.php'; 
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-chart-line"></i> Advanced Reports
    </h1>
</div>

<div class="row">
    <!-- Report Cards -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Service Cost (This Year)
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php
                            $sql = "SELECT SUM(cost) as total FROM services WHERE YEAR(service_date) = YEAR(CURRENT_DATE())";
                            $result = $conn->query($sql);
                            $row = $result->fetch_assoc();
                            echo '₹' . number_format($row['total'] ?? 0, 2);
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-rupee-sign fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Services This Month
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php
                            $sql = "SELECT COUNT(*) as total FROM services WHERE MONTH(service_date) = MONTH(CURRENT_DATE()) AND YEAR(service_date) = YEAR(CURRENT_DATE())";
                            $result = $conn->query($sql);
                            $row = $result->fetch_assoc();
                            echo $row['total'];
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-tools fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Active Vehicles
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php
                            $sql = "SELECT COUNT(DISTINCT vehicle_id) as total FROM services WHERE service_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)";
                            $result = $conn->query($sql);
                            $row = $result->fetch_assoc();
                            echo $row['total'];
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-car-side fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Avg. Service Cost
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php
                            $sql = "SELECT AVG(cost) as avg_cost FROM services";
                            $result = $conn->query($sql);
                            $row = $result->fetch_assoc();
                            echo '₹' . number_format($row['avg_cost'] ?? 0, 2);
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calculator fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Service Trend Chart -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Service Trend - Last 12 Months</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Total Services</th>
                                <th>Total Cost</th>
                                <th>Avg. Cost</th>
                                <th>Trend</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT 
                                    DATE_FORMAT(service_date, '%Y-%m') as month,
                                    COUNT(*) as service_count,
                                    SUM(cost) as total_cost,
                                    AVG(cost) as avg_cost
                                    FROM services
                                    WHERE service_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
                                    GROUP BY DATE_FORMAT(service_date, '%Y-%m')
                                    ORDER BY month DESC";
                            $result = $conn->query($sql);
                            
                            $previous_cost = 0;
                            while($row = $result->fetch_assoc()) {
                                $month_name = date('M Y', strtotime($row['month'] . '-01'));
                                $trend = '';
                                
                                if ($previous_cost > 0) {
                                    $change = (($row['total_cost'] - $previous_cost) / $previous_cost) * 100;
                                    if ($change > 0) {
                                        $trend = "<span class='text-danger'><i class='fas fa-arrow-up'></i> " . number_format($change, 1) . "%</span>";
                                    } else {
                                        $trend = "<span class='text-success'><i class='fas fa-arrow-down'></i> " . number_format(abs($change), 1) . "%</span>";
                                    }
                                }
                                $previous_cost = $row['total_cost'];
                                
                                echo "<tr>
                                        <td><strong>{$month_name}</strong></td>
                                        <td>{$row['service_count']}</td>
                                        <td>₹" . number_format($row['total_cost'], 2) . "</td>
                                        <td>₹" . number_format($row['avg_cost'], 2) . "</td>
                                        <td>{$trend}</td>
                                      </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Service Type Distribution -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Top Service Types</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Service Type</th>
                                <th>Count</th>
                                <th>Total Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT 
                                    service_type,
                                    COUNT(*) as service_count,
                                    SUM(cost) as total_cost
                                    FROM services
                                    GROUP BY service_type
                                    ORDER BY total_cost DESC
                                    LIMIT 10";
                            $result = $conn->query($sql);
                            
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$row['service_type']}</td>
                                        <td>{$row['service_count']}</td>
                                        <td>₹" . number_format($row['total_cost'], 2) . "</td>
                                      </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Vehicle Performance Report -->
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Vehicle Performance Report</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Total Services</th>
                                <th>Total Cost</th>
                                <th>Last Service</th>
                                <th>Avg. Service Interval (Days)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT 
                                    v.id,
                                    v.make_model,
                                    v.reg_number,
                                    v.vehicle_type,
                                    COUNT(s.id) as service_count,
                                    SUM(s.cost) as total_cost,
                                    MAX(s.service_date) as last_service,
                                    DATEDIFF(MAX(s.service_date), MIN(s.service_date)) / COUNT(s.id) as avg_interval
                                    FROM vehicles v
                                    LEFT JOIN services s ON v.id = s.vehicle_id
                                    GROUP BY v.id
                                    HAVING service_count > 0
                                    ORDER BY total_cost DESC";
                            $result = $conn->query($sql);
                            
                            while($row = $result->fetch_assoc()) {
                                $last_service = $row['last_service'] ? date('d M Y', strtotime($row['last_service'])) : 'Never';
                                $avg_interval = $row['avg_interval'] ? round($row['avg_interval']) : 'N/A';
                                
                                // Status based on last service date
                                $status = '';
                                if ($row['last_service']) {
                                    $days_since_last_service = floor((time() - strtotime($row['last_service'])) / (60 * 60 * 24));
                                    if ($days_since_last_service > 90) {
                                        $status = "<span class='badge bg-danger'>Overdue</span>";
                                    } elseif ($days_since_last_service > 60) {
                                        $status = "<span class='badge bg-warning'>Due Soon</span>";
                                    } else {
                                        $status = "<span class='badge bg-success'>Good</span>";
                                    }
                                }
                                
                                echo "<tr>
                                        <td>
                                            <strong>{$row['make_model']}</strong><br>
                                            <small class='text-muted'>{$row['reg_number']}</small>
                                        </td>
                                        <td>{$row['service_count']}</td>
                                        <td>₹" . number_format($row['total_cost'], 2) . "</td>
                                        <td>{$last_service}</td>
                                        <td>{$avg_interval}</td>
                                        <td>{$status}</td>
                                      </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>