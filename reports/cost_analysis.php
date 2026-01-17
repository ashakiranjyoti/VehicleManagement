<?php 
include '../config/database.php';
checkAuth();
include '../includes/header.php'; 
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-chart-pie"></i> Cost Analysis
    </h1>
</div>

<div class="row">
    <!-- Cost by Vehicle Type -->
    <div class="col-md-6">
        <div class="card fade-in">
            <div class="card-header">
                <h5 class="card-title mb-0">Cost Distribution by Vehicle Type</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Vehicle Type</th>
                                <th>Total Services</th>
                                <th>Total Cost</th>
                                <th>Average Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $type_sql = "SELECT 
                                        v.vehicle_type,
                                        COUNT(s.id) as service_count,
                                        SUM(s.cost) as total_cost,
                                        AVG(s.cost) as avg_cost
                                        FROM services s
                                        JOIN vehicles v ON s.vehicle_id = v.id
                                        GROUP BY v.vehicle_type
                                        ORDER BY total_cost DESC";
                            $type_result = $conn->query($type_sql);
                            
                            $total_all_cost = 0;
                            while($row = $type_result->fetch_assoc()) {
                                $total_all_cost += $row['total_cost'];
                                $icon = $row['vehicle_type'] == 'bike' ? 'fas fa-motorcycle' : 'fas fa-car';
                                $badge_class = $row['vehicle_type'] == 'bike' ? 'bg-primary' : 'bg-success';
                                
                                echo "<tr>
                                        <td>
                                            <span class='badge $badge_class'>
                                                <i class='$icon'></i> " . ucfirst($row['vehicle_type']) . "
                                            </span>
                                        </td>
                                        <td>{$row['service_count']}</td>
                                        <td>₹" . number_format($row['total_cost'], 2) . "</td>
                                        <td>₹" . number_format($row['avg_cost'], 2) . "</td>
                                      </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top Vehicles by Cost -->
    <div class="col-md-6">
        <div class="card fade-in">
            <div class="card-header">
                <h5 class="card-title mb-0">Top 10 Vehicles by Service Cost</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Services</th>
                                <th>Total Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $top_sql = "SELECT 
                                       v.make_model,
                                       v.reg_number,
                                       v.vehicle_type,
                                       COUNT(s.id) as service_count,
                                       SUM(s.cost) as total_cost
                                       FROM services s
                                       JOIN vehicles v ON s.vehicle_id = v.id
                                       GROUP BY v.id
                                       ORDER BY total_cost DESC
                                       LIMIT 10";
                            $top_result = $conn->query($top_sql);
                            
                            while($row = $top_result->fetch_assoc()) {
                                $icon = $row['vehicle_type'] == 'bike' ? 'fas fa-motorcycle' : 'fas fa-car';
                                
                                echo "<tr>
                                        <td>
                                            <i class='$icon'></i> 
                                            <strong>{$row['make_model']}</strong>
                                            <br><small class='text-muted'>{$row['reg_number']}</small>
                                        </td>
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

<!-- Monthly Trend -->
<div class="card mt-4 fade-in">
    <div class="card-header">
        <h5 class="card-title mb-0">Monthly Cost Trend (Last 6 Months)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Total Services</th>
                        <th>Total Cost</th>
                        <th>Average Cost/Service</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $trend_sql = "SELECT 
                                 DATE_FORMAT(service_date, '%Y-%m') as month,
                                 COUNT(*) as service_count,
                                 SUM(cost) as total_cost,
                                 AVG(cost) as avg_cost
                                 FROM services
                                 WHERE service_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
                                 GROUP BY DATE_FORMAT(service_date, '%Y-%m')
                                 ORDER BY month DESC";
                    $trend_result = $conn->query($trend_sql);
                    
                    while($row = $trend_result->fetch_assoc()) {
                        $month_name = date('F Y', strtotime($row['month'] . '-01'));
                        
                        echo "<tr>
                                <td><strong>{$month_name}</strong></td>
                                <td>{$row['service_count']}</td>
                                <td>₹" . number_format($row['total_cost'], 2) . "</td>
                                <td>₹" . number_format($row['avg_cost'], 2) . "</td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>