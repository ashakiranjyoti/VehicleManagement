<?php 
include '../config/database.php';
checkAuth();
include '../includes/header.php'; 
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-chart-pie"></i> Financial Reports
    </h1>
</div>

<!-- Yearly Financial Summary -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Yearly Financial Summary</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Total Services</th>
                        <th>Total Cost</th>
                        <th>Avg. Cost/Service</th>
                        <th>Cost per Vehicle</th>
                        <th>Growth</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT 
                            YEAR(service_date) as year,
                            COUNT(*) as service_count,
                            SUM(cost) as total_cost,
                            AVG(cost) as avg_cost,
                            COUNT(DISTINCT vehicle_id) as vehicle_count,
                            SUM(cost) / COUNT(DISTINCT vehicle_id) as cost_per_vehicle
                            FROM services
                            GROUP BY YEAR(service_date)
                            ORDER BY year DESC";
                    $result = $conn->query($sql);
                    
                    $previous_year_cost = 0;
                    while($row = $result->fetch_assoc()) {
                        $growth = '';
                        if ($previous_year_cost > 0) {
                            $growth_percent = (($row['total_cost'] - $previous_year_cost) / $previous_year_cost) * 100;
                            if ($growth_percent > 0) {
                                $growth = "<span class='text-danger'><i class='fas fa-arrow-up'></i> " . number_format($growth_percent, 1) . "%</span>";
                            } else {
                                $growth = "<span class='text-success'><i class='fas fa-arrow-down'></i> " . number_format(abs($growth_percent), 1) . "%</span>";
                            }
                        }
                        $previous_year_cost = $row['total_cost'];
                        
                        echo "<tr>
                                <td><strong>{$row['year']}</strong></td>
                                <td>{$row['service_count']}</td>
                                <td>₹" . number_format($row['total_cost'], 2) . "</td>
                                <td>₹" . number_format($row['avg_cost'], 2) . "</td>
                                <td>₹" . number_format($row['cost_per_vehicle'], 2) . "</td>
                                <td>{$growth}</td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Monthly Cost Breakdown -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Monthly Cost Breakdown - Current Year</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Bike Services</th>
                        <th>Bike Cost</th>
                        <th>Car Services</th>
                        <th>Car Cost</th>
                        <th>Total Cost</th>
                        <th>Bike vs Car Ratio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT 
                            DATE_FORMAT(s.service_date, '%Y-%m') as month,
                            SUM(CASE WHEN v.vehicle_type = 'bike' THEN 1 ELSE 0 END) as bike_services,
                            SUM(CASE WHEN v.vehicle_type = 'bike' THEN s.cost ELSE 0 END) as bike_cost,
                            SUM(CASE WHEN v.vehicle_type = 'car' THEN 1 ELSE 0 END) as car_services,
                            SUM(CASE WHEN v.vehicle_type = 'car' THEN s.cost ELSE 0 END) as car_cost,
                            SUM(s.cost) as total_cost
                            FROM services s
                            JOIN vehicles v ON s.vehicle_id = v.id
                            WHERE YEAR(s.service_date) = YEAR(CURRENT_DATE())
                            GROUP BY DATE_FORMAT(s.service_date, '%Y-%m')
                            ORDER BY month DESC";
                    $result = $conn->query($sql);
                    
                    while($row = $result->fetch_assoc()) {
                        $month_name = date('M Y', strtotime($row['month'] . '-01'));
                        $bike_ratio = $row['total_cost'] > 0 ? ($row['bike_cost'] / $row['total_cost']) * 100 : 0;
                        $car_ratio = $row['total_cost'] > 0 ? ($row['car_cost'] / $row['total_cost']) * 100 : 0;
                        
                        echo "<tr>
                                <td><strong>{$month_name}</strong></td>
                                <td>{$row['bike_services']}</td>
                                <td>₹" . number_format($row['bike_cost'], 2) . "</td>
                                <td>{$row['car_services']}</td>
                                <td>₹" . number_format($row['car_cost'], 2) . "</td>
                                <td><strong>₹" . number_format($row['total_cost'], 2) . "</strong></td>
                                <td>
                                    <small>Bike: " . number_format($bike_ratio, 1) . "%</small><br>
                                    <small>Car: " . number_format($car_ratio, 1) . "%</small>
                                </td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Cost per Service Type -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Cost Analysis by Service Type</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Service Type</th>
                        <th>Total Services</th>
                        <th>Total Cost</th>
                        <th>Avg. Cost</th>
                        <th>Min Cost</th>
                        <th>Max Cost</th>
                        <th>Cost Distribution</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT 
                            service_type,
                            COUNT(*) as service_count,
                            SUM(cost) as total_cost,
                            AVG(cost) as avg_cost,
                            MIN(cost) as min_cost,
                            MAX(cost) as max_cost
                            FROM services
                            GROUP BY service_type
                            ORDER BY total_cost DESC";
                    $result = $conn->query($sql);
                    
                    // Get total cost for percentage calculation
                    $total_cost_sql = "SELECT SUM(cost) as grand_total FROM services";
                    $total_result = $conn->query($total_cost_sql);
                    $total_row = $total_result->fetch_assoc();
                    $grand_total = $total_row['grand_total'];
                    
                    while($row = $result->fetch_assoc()) {
                        $percentage = $grand_total > 0 ? ($row['total_cost'] / $grand_total) * 100 : 0;
                        
                        echo "<tr>
                                <td><strong>{$row['service_type']}</strong></td>
                                <td>{$row['service_count']}</td>
                                <td>₹" . number_format($row['total_cost'], 2) . "</td>
                                <td>₹" . number_format($row['avg_cost'], 2) . "</td>
                                <td>₹" . number_format($row['min_cost'], 2) . "</td>
                                <td>₹" . number_format($row['max_cost'], 2) . "</td>
                                <td>
                                    <div class='progress' style='height: 20px;'>
                                        <div class='progress-bar' role='progressbar' style='width: {$percentage}%' 
                                             aria-valuenow='{$percentage}' aria-valuemin='0' aria-valuemax='100'>
                                            " . number_format($percentage, 1) . "%
                                        </div>
                                    </div>
                                </td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>