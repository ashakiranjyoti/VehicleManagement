<?php 
include '../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
checkAuth();
include '../includes/header.php'; 

$vehicle_id = $_GET['vehicle_id'] ?? '';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-history"></i> Service History
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add_service.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Service
        </a>
        <div class="btn-group ms-2">
            <a href="service_history_export_excel.php<?php echo !empty($vehicle_id) ? ('?vehicle_id=' . urlencode($vehicle_id)) : ''; ?>" 
               class="btn btn-sm btn-warning">
                <i class="fas fa-file-excel"></i> Excel
            </a>
            <a href="service_history_export_pdf.php<?php echo !empty($vehicle_id) ? ('?vehicle_id=' . urlencode($vehicle_id)) : ''; ?>" 
               class="btn btn-sm btn-danger">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <form method="GET" class="row g-3" id="filterForm">
            <div class="col-md-8">
                <select name="vehicle_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Vehicles</option>
                    <?php
                    $result = $conn->query("SELECT * FROM vehicles ORDER BY make_model");
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $selected = ($vehicle_id == $row['id']) ? 'selected' : '';
                            $icon = $row['vehicle_type'] == 'bike' ? '🏍️' : '🚗';
                            echo "<option value='{$row['id']}' $selected>{$icon} {$row['make_model']} ({$row['reg_number']})</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-4">
                <a href="service_history.php" class="btn btn-secondary w-100">Show All</a>
            </div>
        </form>
    </div>
</div>

<?php
// Get vehicle details if specific vehicle is selected
$vehicle_details = null;
$vehicle_owner = '';
if (!empty($vehicle_id)) {
    $vehicle_sql = "SELECT v.*, u.full_name as owner_full_name 
                   FROM vehicles v 
                   LEFT JOIN users u ON v.owner_name = u.username 
                   WHERE v.id = $vehicle_id";
    $vehicle_result = $conn->query($vehicle_sql);
    
    if ($vehicle_result && $vehicle_result->num_rows > 0) {
        $vehicle_details = $vehicle_result->fetch_assoc();
        $vehicle_owner = $vehicle_details['owner_full_name'] ?? $vehicle_details['owner_name'] ?? 'Not specified';
        
        echo "<div class='alert alert-info fade-in'>
                <div class='row'>
                    <div class='col-md-8'>
                        <h5><i class='fas fa-car-side'></i> {$vehicle_details['make_model']} - {$vehicle_details['reg_number']}</h5>
                        <strong>Owner:</strong> {$vehicle_owner} | 
                        <strong>Type:</strong> " . ucfirst($vehicle_details['vehicle_type']) . " | 
                        <strong>Chassis:</strong> {$vehicle_details['chassis_number']}
                    </div>
                    <div class='col-md-4 text-end'>
                        <a href='add_service.php?vehicle_id={$vehicle_id}' class='btn btn-success btn-sm'>
                            <i class='fas fa-plus'></i> Add Service for this Vehicle
                        </a>
                    </div>
                </div>
              </div>";
    }
}
?>

<div class="card fade-in">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-list"></i> Service Records
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered" id="servicesTable">
                <thead class="table-light">
                    <tr>
                        <th>Date & Time</th>
                        <?php if (empty($vehicle_id)): ?>
                        <th>Vehicle</th>
                        <?php endif; ?>
                        <th>Service Type</th>
                        <th>Running KM</th>
                        <th>Service Center</th>
                        <th>Service Done By</th>
                        <th>Cost</th>
                        <th>Description</th>
                        <th>Created By</th>
                        <th>Bill/Proof</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $where = "";
                    if (!empty($vehicle_id)) {
                        $where = "WHERE s.vehicle_id = '$vehicle_id'";
                    }
                    
                    $sql = "SELECT s.*, v.make_model, v.reg_number, v.vehicle_type, v.owner_name
                           FROM services s 
                           JOIN vehicles v ON s.vehicle_id = v.id 
                           $where 
                           ORDER BY s.service_date DESC, s.service_time DESC";
                    $result = $conn->query($sql);
                    
                    if ($result && $result->num_rows > 0) {
                        $total_cost = 0;
                        $total_services = $result->num_rows;
                        
                        while($row = $result->fetch_assoc()) {
                            $total_cost += $row['cost'];
                            $icon = $row['vehicle_type'] == 'bike' ? 'fas fa-motorcycle' : 'fas fa-car';
                            $vehicle_badge_class = $row['vehicle_type'] == 'bike' ? 'bg-primary' : 'bg-success';
                            
                            // Service type badge colors
                            $service_badge_class = 'bg-secondary';
                            if ($row['service_type'] == 'Regular Service') {
                                $service_badge_class = 'bg-success';
                            } elseif ($row['service_type'] == 'Breakdown Service') {
                                $service_badge_class = 'bg-danger';
                            }
                            
                            // Bill files handling
                            $bill_files = $row['bill_file'] ? explode(',', $row['bill_file']) : [];
                            $bill_count = count($bill_files);
                            
                            if ($bill_count > 1) {
                                $bill_link = "<div class='dropdown'>
                                                <button class='btn btn-sm btn-outline-info dropdown-toggle' type='button' data-bs-toggle='dropdown'>
                                                    <i class='fas fa-file-invoice'></i> {$bill_count} files
                                                </button>
                                                <ul class='dropdown-menu'>";
                                foreach ($bill_files as $index => $file) {
                                    $bill_link .= "<li><a class='dropdown-item' href='../uploads/{$file}' target='_blank'>
                                                    File " . ($index + 1) . "
                                                  </a></li>";
                                }
                                $bill_link .= "</ul></div>";
                            } elseif ($bill_count == 1) {
                                $bill_link = "<a href='../uploads/{$bill_files[0]}' target='_blank' class='btn btn-sm btn-outline-info'>
                                                <i class='fas fa-file-invoice'></i> Bill
                                              </a>";
                            } else {
                                $bill_link = "<span class='text-muted'><i class='fas fa-times'></i></span>";
                            }
                            
                            echo "<tr>
                                    <td>
                                        <strong>" . date('d M Y', strtotime($row['service_date'])) . "</strong><br>
                                        <small class='text-muted'>{$row['service_time']}</small>
                                    </td>";
                            
                            if (empty($vehicle_id)) {
                                echo "<td>
                                        <span class='badge {$vehicle_badge_class} mb-1'>
                                            <i class='{$icon}'></i> " . ucfirst($row['vehicle_type']) . "
                                        </span><br>
                                        <strong>{$row['make_model']}</strong><br>
                                        <small class='text-muted'>{$row['reg_number']}</small>
                                      </td>";
                            }
                            
                            echo "<td>
                                    <span class='badge {$service_badge_class}'>{$row['service_type']}</span>
                                  </td>
                                  <td>
                                    " . ($row['running_km'] ? 
                                        "<strong>" . number_format($row['running_km']) . "</strong> km" : 
                                        "<span class='text-muted'>N/A</span>") . "
                                  </td>
                                  <td>
                                    " . ($row['service_center_name'] ? 
                                        "<strong>{$row['service_center_name']}</strong><br>" . 
                                        "<small class='text-muted'>{$row['service_center_place']}</small>" : 
                                        "<span class='text-muted'>N/A</span>") . "
                                  </td>
                                  <td>
                                    " . ($row['service_done_by'] ? 
                                        "<small class='badge bg-light text-dark'>{$row['service_done_by']}</small>" : 
                                        "<span class='text-muted'>N/A</span>") . "
                                  </td>
                                  <td>
                                    <strong class='text-success'>₹" . number_format($row['cost'], 2) . "</strong>
                                  </td>
                                  <td style='max-width: 200px;'>
                                    " . ($row['description'] ? 
                                        "<small>" . nl2br(htmlspecialchars(substr($row['description'], 0, 150))) . 
                                        (strlen($row['description']) > 150 ? "..." : "") . "</small>" : 
                                        "<span class='text-muted'>No description</span>") . "
                                  </td>
                                  <td>
                                    <small>{$row['created_by']}</small>
                                  </td>
                                  <td class='text-center'>{$bill_link}</td>
                                  <td>
                                    <div class='btn-group btn-group-sm' role='group'>
                                        " . (isAdmin() ? "
                                        <a href='delete_service.php?id={$row['id']}' class='btn btn-outline-danger' title='Delete' onclick='return confirm(\"Are you sure you want to delete this service record?\")'>
                                            <i class='fas fa-trash'></i>
                                        </a>
                                        " : "") . "
                                        <a href='view_services.php?search=" . urlencode($row['reg_number']) . "' class='btn btn-outline-primary' title='View All Services'>
                                            <i class='fas fa-eye'></i>
                                        </a>
                                    </div>
                                  </td>
                                  </tr>";
                        }
                    } else {
                        $colspan = empty($vehicle_id) ? 11 : 10;
                        echo "<tr>
                                <td colspan='{$colspan}' class='text-center py-4'>
                                    <i class='fas fa-info-circle fa-2x text-muted mb-3'></i><br>
                                    No service records found.
                                    " . (!empty($vehicle_id) ? 
                                        "<br><a href='add_service.php?vehicle_id={$vehicle_id}' class='btn btn-primary btn-sm mt-2'>Add First Service for this Vehicle</a>" : 
                                        "") . "
                                </td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <?php if (isset($total_services) && $total_services > 0): ?>
        <div hidden class="mt-4 p-4 bg-light rounded">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="card border-0 bg-white">
                        <div class="card-body">
                            <h4 class="text-primary"><?php echo $total_services; ?></h4>
                            <small class="text-muted">Total Services</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-white">
                        <div class="card-body">
                            <h4 class="text-success">₹<?php echo number_format($total_cost, 2); ?></h4>
                            <small class="text-muted">Total Cost</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-white">
                        <div class="card-body">
                            <h4 class="text-warning">₹<?php echo number_format($total_cost / $total_services, 2); ?></h4>
                            <small class="text-muted">Average Cost</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-white">
                        <div class="card-body">
                            <?php
                            // Calculate service frequency
                            if ($total_services > 1 && !empty($vehicle_id)) {
                                $first_last_sql = "SELECT MIN(service_date) as first_date, MAX(service_date) as last_date 
                                                  FROM services WHERE vehicle_id = $vehicle_id";
                                $first_last_result = $conn->query($first_last_sql);
                                if ($first_last_result) {
                                    $dates = $first_last_result->fetch_assoc();
                                    $days_between = (strtotime($dates['last_date']) - strtotime($dates['first_date'])) / (60 * 60 * 24);
                                    $avg_days = $days_between / ($total_services - 1);
                                    echo "<h4 class='text-info'>" . round($avg_days) . "</h4>
                                          <small class='text-muted'>Avg. Days between Services</small>";
                                } else {
                                    echo "<h4 class='text-info'>N/A</h4><small class='text-muted'>Frequency</small>";
                                }
                            } else {
                                echo "<h4 class='text-info'>N/A</h4><small class='text-muted'>Frequency</small>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Service Type Breakdown -->
            <?php if (!empty($vehicle_id)): ?>
            <div hidden class="row mt-3">
                <div class="col-12">
                    <div class="card bg-white">
                        <div class="card-body">
                            <h6>Service Type Breakdown</h6>
                            <?php
                            $type_sql = "SELECT service_type, COUNT(*) as count, SUM(cost) as total_cost 
                                        FROM services WHERE vehicle_id = $vehicle_id 
                                        GROUP BY service_type";
                            $type_result = $conn->query($type_sql);
                            
                            if ($type_result && $type_result->num_rows > 0) {
                                echo '<div class="row">';
                                while($type = $type_result->fetch_assoc()) {
                                    $percentage = ($type['count'] / $total_services) * 100;
                                    $badge_color = $type['service_type'] == 'Regular Service' ? 'success' : 'danger';
                                    
                                    echo '<div class="col-md-4 mb-2">
                                            <div class="d-flex justify-content-between">
                                                <span class="badge bg-' . $badge_color . '">' . $type['service_type'] . '</span>
                                                <span>' . $type['count'] . ' services</span>
                                            </div>
                                            <div class="progress mt-1" style="height: 8px;">
                                                <div class="progress-bar bg-' . $badge_color . '" 
                                                     style="width: ' . $percentage . '%" 
                                                     role="progressbar"></div>
                                            </div>
                                            <small class="text-muted">Cost: ₹' . number_format($type['total_cost'], 2) . '</small>
                                          </div>';
                                }
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Export Options -->
        <?php if (isset($total_services) && $total_services > 0): ?>
        <div class="mt-3 text-end">
            <div class="btn-group">
                <form hidden id="exportCSV" method="GET" action="service_history_export_csv.php" target="_blank" style="display: inline;">
                    <?php if (!empty($vehicle_id)): ?>
                    <input type="hidden" name="vehicle_id" value="<?php echo $vehicle_id; ?>">
                    <?php endif; ?>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-file-csv"></i> CSV
                    </button>
                </form>
                
                <form id="exportExcel" method="GET" action="service_history_export_excel.php" target="_blank" style="display: inline;">
                    <?php if (!empty($vehicle_id)): ?>
                    <input type="hidden" name="vehicle_id" value="<?php echo $vehicle_id; ?>">
                    <?php endif; ?>
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                </form>
                
                <form id="exportPDF" method="GET" action="service_history_export_pdf.php" target="_blank" style="display: inline;">
                    <?php if (!empty($vehicle_id)): ?>
                    <input type="hidden" name="vehicle_id" value="<?php echo $vehicle_id; ?>">
                    <?php endif; ?>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </form>
                
                <button hidden type="button" class="btn btn-info btn-sm" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Service Type Legend -->
<div hidden class="card mt-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="fas fa-tags"></i> Service Type Legend
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 text-center">
                <span class="badge bg-success">Regular Service</span>
                <br><small class="text-muted">Scheduled maintenance</small>
            </div>
            <div class="col-md-6 text-center">
                <span class="badge bg-danger">Breakdown Service</span>
                <br><small class="text-muted">Emergency repairs</small>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>