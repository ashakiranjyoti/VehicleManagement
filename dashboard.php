<?php 
require_once __DIR__ . '/includes/auth.php';
checkAuth();
require_once __DIR__ . '/config/database.php';
include 'includes/header.php'; 
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="services/add_service.php" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Add Service
            </a>
        </div>
    </div>
</div>

<!-- Search Box -->
<div class="row mb-4">
    <div class="col-md-6">
        <form method="GET" action="vehicles/view_vehicles.php" class="search-box">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search by Reg No or Chassis No...">
                <button class="btn btn-outline-secondary" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Stats Cards -->
<div class="row fade-in">
    <div class="col-xl-3 col-md-6">
        <div class="card card-primary text-white mb-4">
            <div class="card-body">
                <?php
                $result = $conn->query("SELECT COUNT(*) as total FROM vehicles");
                $row = $result->fetch_assoc();
                ?>
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="text-white-50 small">Total Vehicles</div>
                        <div class="stats-number"><?php echo $row['total']; ?></div>
                    </div>
                    <div class="mt-2">
                        <i class="fas fa-motorcycle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card card-success text-white mb-4">
            <div class="card-body">
                <?php
                $result = $conn->query("SELECT COUNT(*) as total FROM services");
                $row = $result->fetch_assoc();
                ?>
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="text-white-50 small">Total Services</div>
                        <div class="stats-number"><?php echo $row['total']; ?></div>
                    </div>
                    <div class="mt-2">
                        <i class="fas fa-tools fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card card-warning text-white mb-4">
            <div class="card-body">
                <?php
                $result = $conn->query("SELECT SUM(cost) as total_cost FROM services WHERE MONTH(service_date) = MONTH(CURRENT_DATE())");
                $row = $result->fetch_assoc();
                $monthly_cost = $row['total_cost'] ?? 0;
                ?>
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="text-white-50 small">Monthly Cost</div>
                        <div class="stats-number">₹<?php echo number_format($monthly_cost, 2); ?></div>
                    </div>
                    <div class="mt-2">
                        <i class="fas fa-rupee-sign fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card card-danger text-white mb-4">
            <div class="card-body">
                <?php
                $result = $conn->query("SELECT SUM(cost) as total_cost FROM services");
                $row = $result->fetch_assoc();
                ?>
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="text-white-50 small">Total Cost</div>
                        <div class="stats-number">₹<?php echo number_format($row['total_cost'], 2); ?></div>
                    </div>
                    <div class="mt-2">
                        <i class="fas fa-chart-line fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Reports Section -->
<div hidden class="row mt-4">
    <div class="col-md-6">
        <div class="card fade-in">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-exclamation-triangle text-danger"></i> Overdue Services
                </h6>
            </div>
            <div class="card-body">
                <?php
                $sql = "SELECT COUNT(DISTINCT v.id) as overdue_count 
                       FROM vehicles v 
                       LEFT JOIN services s ON v.id = s.vehicle_id 
                       GROUP BY v.id 
                       HAVING MAX(s.service_date) IS NOT NULL AND DATEDIFF(CURRENT_DATE, MAX(s.service_date)) > 90";
                $result = $conn->query($sql);
                $overdue_count = $result->num_rows;
                ?>
                
                <?php if ($overdue_count > 0): ?>
                    <div class="alert alert-danger">
                        <strong><?php echo $overdue_count; ?> vehicles</strong> have overdue services (90+ days)
                    </div>
                    <a href="reports/service_reminders.php" class="btn btn-sm btn-danger">View Details</a>
                <?php else: ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> No overdue services
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card fade-in">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-chart-line text-primary"></i> This Month Summary
                </h6>
            </div>
            <div class="card-body">
                <?php
                $sql = "SELECT 
                       COUNT(*) as services_this_month,
                       SUM(cost) as cost_this_month
                       FROM services 
                       WHERE MONTH(service_date) = MONTH(CURRENT_DATE()) 
                       AND YEAR(service_date) = YEAR(CURRENT_DATE())";
                $result = $conn->query($sql);
                $month_data = $result->fetch_assoc();
                ?>
                
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-primary"><?php echo $month_data['services_this_month']; ?></h4>
                        <small class="text-muted">Services</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success">₹<?php echo number_format($month_data['cost_this_month'] ?? 0, 2); ?></h4>
                        <small class="text-muted">Cost</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Services -->
    <div class="col-md-8">
        <div class="card fade-in">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history"></i> Recent Services
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Service Date</th>
                                <th>Service Type</th>
                                <th>Cost</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT s.*, v.make_model, v.reg_number 
                                    FROM services s 
                                    JOIN vehicles v ON s.vehicle_id = v.id 
                                    ORDER BY s.service_date DESC 
                                    LIMIT 8";
                            $result = $conn->query($sql);
                            
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                            <td>
                                                <strong>{$row['make_model']}</strong><br>
                                                <small class='text-muted'>{$row['reg_number']}</small>
                                            </td>
                                            <td>{$row['service_date']}</td>
                                            <td><span class='badge-service'>{$row['service_type']}</span></td>
                                            <td>₹" . number_format($row['cost'], 2) . "</td>
                                            <td>
                                                <a href='services/view_services.php?vehicle_id={$row['vehicle_id']}' class='btn btn-sm btn-outline-primary'>
                                                    <i class='fas fa-eye'></i>
                                                </a>
                                            </td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center'>No service records found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="col-md-4">
        <div class="card fade-in">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt"></i> Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="vehicles/add_vehicle.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Vehicle
                    </a>
                    <a href="services/add_service.php" class="btn btn-success">
                        <i class="fas fa-tools"></i> Add Service Record
                    </a>
                    <a hidden href="reports/registration_report.php" class="btn btn-info">
                <i class="fas fa-search"></i> Registration Report
            </a>
                    <a hidden href="reports/monthly_report.php" class="btn btn-warning">
                        <i class="fas fa-chart-pie"></i> Generate Report
                    </a>
                    <a href="vehicles/view_vehicles.php" class="btn btn-info">
                        <i class="fas fa-list"></i> View All Vehicles
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Vehicle Distribution -->
        <div class="card mt-4 fade-in">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie"></i> Vehicle Distribution
                </h5>
            </div>
            <div class="card-body">
                <?php
                $result = $conn->query("SELECT vehicle_type, COUNT(*) as count FROM vehicles GROUP BY vehicle_type");
                $vehicle_types = [];
                while($row = $result->fetch_assoc()) {
                    $vehicle_types[$row['vehicle_type']] = $row['count'];
                }
                ?>
                <div class="text-center">
                    <div class="mb-3">
                        <span class="badge bg-primary me-2">
                            <i class="fas fa-motorcycle"></i> Bikes: <?php echo $vehicle_types['bike'] ?? 0; ?>
                        </span>
                        <span class="badge bg-success">
                            <i class="fas fa-car"></i> Cars: <?php echo $vehicle_types['car'] ?? 0; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>