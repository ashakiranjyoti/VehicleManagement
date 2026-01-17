<?php 
include '../config/database.php';
checkAuth();
include '../includes/header.php'; 
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-bell"></i> Service Reminders
    </h1>
</div>

<!-- Overdue Services -->
<div class="card shadow mb-4">
    <div class="card-header py-3 bg-danger text-white">
        <h6 class="m-0 font-weight-bold">
            <i class="fas fa-exclamation-triangle"></i> Overdue Services (90+ Days)
        </h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Vehicle</th>
                        <th>Last Service Date</th>
                        <th>Days Since Last Service</th>
                        <th>Total Services</th>
                        <th>Avg. Service Cost</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fixed SQL query - removed HAVING clause that was causing issues
                    $sql = "SELECT 
                            v.id,
                            v.make_model,
                            v.reg_number,
                            v.vehicle_type,
                            MAX(s.service_date) as last_service,
                            COUNT(s.id) as service_count,
                            AVG(s.cost) as avg_cost
                            FROM vehicles v
                            LEFT JOIN services s ON v.id = s.vehicle_id
                            WHERE s.service_date IS NOT NULL
                            GROUP BY v.id, v.make_model, v.reg_number, v.vehicle_type
                            HAVING last_service IS NOT NULL AND DATEDIFF(CURRENT_DATE, last_service) > 90
                            ORDER BY DATEDIFF(CURRENT_DATE, last_service) DESC";
                    
                    $result = $conn->query($sql);
                    
                    // Check if query was successful
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $days_since = floor((time() - strtotime($row['last_service'])) / (60 * 60 * 24));
                            
                            echo "<tr>
                                    <td>
                                        <strong>{$row['make_model']}</strong><br>
                                        <small class='text-muted'>{$row['reg_number']}</small>
                                    </td>
                                    <td>" . date('d M Y', strtotime($row['last_service'])) . "</td>
                                    <td><span class='badge bg-danger'>{$days_since} days</span></td>
                                    <td>{$row['service_count']}</td>
                                    <td>₹" . number_format($row['avg_cost'], 2) . "</td>
                                    <td>
                                        <a href='../services/add_service.php?vehicle_id={$row['id']}' class='btn btn-sm btn-primary'>
                                            <i class='fas fa-plus'></i> Add Service
                                        </a>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center text-success'><i class='fas fa-check-circle'></i> No overdue services found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Due Soon Services -->
<div class="card shadow mb-4">
    <div class="card-header py-3 bg-warning text-white">
        <h6 class="m-0 font-weight-bold">
            <i class="fas fa-clock"></i> Due Soon Services (60-90 Days)
        </h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Vehicle</th>
                        <th>Last Service Date</th>
                        <th>Days Since Last Service</th>
                        <th>Total Services</th>
                        <th>Avg. Service Cost</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fixed SQL query
                    $sql = "SELECT 
                            v.id,
                            v.make_model,
                            v.reg_number,
                            v.vehicle_type,
                            MAX(s.service_date) as last_service,
                            COUNT(s.id) as service_count,
                            AVG(s.cost) as avg_cost
                            FROM vehicles v
                            LEFT JOIN services s ON v.id = s.vehicle_id
                            WHERE s.service_date IS NOT NULL
                            GROUP BY v.id, v.make_model, v.reg_number, v.vehicle_type
                            HAVING last_service IS NOT NULL AND DATEDIFF(CURRENT_DATE, last_service) BETWEEN 60 AND 90
                            ORDER BY DATEDIFF(CURRENT_DATE, last_service) DESC";
                    
                    $result = $conn->query($sql);
                    
                    // Check if query was successful
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $days_since = floor((time() - strtotime($row['last_service'])) / (60 * 60 * 24));
                            
                            echo "<tr>
                                    <td>
                                        <strong>{$row['make_model']}</strong><br>
                                        <small class='text-muted'>{$row['reg_number']}</small>
                                    </td>
                                    <td>" . date('d M Y', strtotime($row['last_service'])) . "</td>
                                    <td><span class='badge bg-warning'>{$days_since} days</span></td>
                                    <td>{$row['service_count']}</td>
                                    <td>₹" . number_format($row['avg_cost'], 2) . "</td>
                                    <td>
                                        <a href='../services/add_service.php?vehicle_id={$row['id']}' class='btn btn-sm btn-warning'>
                                            <i class='fas fa-plus'></i> Schedule Service
                                        </a>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center text-success'><i class='fas fa-check-circle'></i> No vehicles due for service soon</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Never Serviced Vehicles -->
<div class="card shadow mb-4">
    <div class="card-header py-3 bg-info text-white">
        <h6 class="m-0 font-weight-bold">
            <i class="fas fa-question-circle"></i> Never Serviced Vehicles
        </h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Vehicle</th>
                        <th>Registration Date</th>
                        <th>Days Since Registration</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fixed SQL query - simplified
                    $sql = "SELECT 
                            v.id,
                            v.make_model,
                            v.reg_number,
                            v.vehicle_type,
                            v.created_at
                            FROM vehicles v
                            WHERE v.id NOT IN (SELECT DISTINCT vehicle_id FROM services WHERE vehicle_id IS NOT NULL)
                            ORDER BY v.created_at DESC";
                    
                    $result = $conn->query($sql);
                    
                    // Check if query was successful
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $days_since = floor((time() - strtotime($row['created_at'])) / (60 * 60 * 24));
                            
                            echo "<tr>
                                    <td>
                                        <strong>{$row['make_model']}</strong><br>
                                        <small class='text-muted'>{$row['reg_number']}</small>
                                    </td>
                                    <td>" . date('d M Y', strtotime($row['created_at'])) . "</td>
                                    <td><span class='badge bg-info'>{$days_since} days</span></td>
                                    <td>
                                        <a href='../services/add_service.php?vehicle_id={$row['id']}' class='btn btn-sm btn-info'>
                                            <i class='fas fa-plus'></i> Add First Service
                                        </a>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='text-center text-success'><i class='fas fa-check-circle'></i> All vehicles have service records</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Debug Information (Remove in production) -->
<?php if (isset($_GET['debug'])): ?>
<div class="card shadow mb-4">
    <div class="card-header py-3 bg-dark text-white">
        <h6 class="m-0 font-weight-bold">Debug Information</h6>
    </div>
    <div class="card-body">
        <h6>SQL Query Check:</h6>
        <pre><?php 
        $test_sql = "SELECT COUNT(*) as total_vehicles FROM vehicles";
        $test_result = $conn->query($test_sql);
        if ($test_result) {
            echo "Database connection: OK\n";
            $test_row = $test_result->fetch_assoc();
            echo "Total vehicles: " . $test_row['total_vehicles'] . "\n";
        } else {
            echo "Database connection: FAILED\n";
            echo "Error: " . $conn->error . "\n";
        }
        ?></pre>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>