<?php 
include '../config/database.php';
checkAuth();
include '../includes/header.php'; 

$vehicle_id = $_GET['vehicle_id'] ?? '';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-tools"></i> All Services
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add_service.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Service
        </a>
        <div class="btn-group ms-2">
            <button type="button" class="btn btn-sm btn-warning" onclick="exportExcel()">
                <i class="fas fa-file-excel"></i> Excel
            </button>
            <button type="button" class="btn btn-sm btn-danger" onclick="exportPDF()">
                <i class="fas fa-file-pdf"></i> PDF
            </button>
        </div>
    </div>
</div>

<!-- Advanced Filters -->
<div class="card mb-4 fade-in">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-filter"></i> Advanced Filters
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3" id="filterForm">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search services..." 
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
                <label class="form-label">Service Type</label>
                <select name="service_type" class="form-select">
                    <option value="">All Services</option>
                    <option value="Regular Service" <?php echo ($_GET['service_type'] ?? '') == 'Regular Service' ? 'selected' : ''; ?>>Regular Service</option>
                    <option value="Breakdown Service" <?php echo ($_GET['service_type'] ?? '') == 'Breakdown Service' ? 'selected' : ''; ?>>Breakdown Service</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?php echo $_GET['from_date'] ?? ''; ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?php echo $_GET['to_date'] ?? ''; ?>">
            </div>
            
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <a href="view_services.php" class="btn btn-secondary w-100">
                    <i class="fas fa-refresh"></i>
                </a>
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
                            <i class='fas fa-plus'></i> Add Service
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
                        <th>Done By</th>
                        <th>Cost</th>
                        <th>Description</th>
                        <th>Created By</th>
                        <th>Bill/Proof</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $where = "WHERE 1=1";
                    $search = $_GET['search'] ?? '';
                    $vehicle_type = $_GET['vehicle_type'] ?? '';
                    $service_type = $_GET['service_type'] ?? '';
                    $from_date = $_GET['from_date'] ?? '';
                    $to_date = $_GET['to_date'] ?? '';
                    
                    if (!empty($search)) {
                        $safe_search = $conn->real_escape_string($search);
                        $where .= " AND (s.service_type LIKE '%$safe_search%' OR s.description LIKE '%$safe_search%' OR v.make_model LIKE '%$safe_search%' OR v.reg_number LIKE '%$safe_search%' OR s.service_center_name LIKE '%$safe_search%' OR s.service_done_by LIKE '%$safe_search%')";
                    }
                    
                    if (!empty($vehicle_type)) {
                        $safe_type = $conn->real_escape_string($vehicle_type);
                        $where .= " AND v.vehicle_type = '$safe_type'";
                    }
                    
                    if (!empty($service_type)) {
                        $safe_service_type = $conn->real_escape_string($service_type);
                        $where .= " AND s.service_type = '$safe_service_type'";
                    }
                    
                    if (!empty($from_date)) {
                        $where .= " AND s.service_date >= '$from_date'";
                    }
                    
                    if (!empty($to_date)) {
                        $where .= " AND s.service_date <= '$to_date'";
                    }
                    
                    if (!empty($vehicle_id)) {
                        $where .= " AND s.vehicle_id = '$vehicle_id'";
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
                                        "<small>" . nl2br(htmlspecialchars(substr($row['description'], 0, 100))) . 
                                        (strlen($row['description']) > 100 ? "..." : "") . "</small>" : 
                                        "<span class='text-muted'>No description</span>") . "
                                  </td>
                                  <td>
                                    <small>{$row['created_by']}</small>
                                  </td>
                                  <td class='text-center'>{$bill_link}</td>
                                  <td>
                                    <div class='btn-group btn-group-sm' role='group'>
                                        <a href='edit_service.php?id={$row['id']}' class='btn btn-outline-warning' title='Edit Service'>
                                            <i class='fas fa-edit'></i>
                                        </a>
                                        <a href='service_history.php?vehicle_id={$row['vehicle_id']}' class='btn btn-outline-primary' title='View History'>
                                            <i class='fas fa-history'></i>
                                        </a>
                                        " . (isAdmin() ? "
                                        <a href='delete_service.php?id={$row['id']}' class='btn btn-outline-danger' title='Delete' onclick='return confirm(\"Are you sure you want to delete this service record?\")'>
                                            <i class='fas fa-trash'></i>
                                        </a>
                                        " : "") . "
                                    </div>
                                  </td>
                                  </tr>";
                        }
                    } else {
                        $colspan = empty($vehicle_id) ? 11 : 10;
                        echo "<tr>
                                <td colspan='{$colspan}' class='text-center py-4'>
                                    <i class='fas fa-info-circle fa-2x text-muted mb-3'></i><br>
                                    No service records found matching your criteria.
                                    <br><a href='add_service.php' class='btn btn-primary btn-sm mt-2'>Add First Service</a>
                                </td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <!-- Summary Stats -->
        <?php
        $summary_sql = "SELECT 
                       COUNT(*) as total_services, 
                       SUM(cost) as total_cost,
                       AVG(cost) as avg_cost,
                       COUNT(DISTINCT vehicle_id) as unique_vehicles
                       FROM services s 
                       JOIN vehicles v ON s.vehicle_id = v.id 
                       $where";
        $summary_result = $conn->query($summary_sql);
        
        if ($summary_result) {
            $summary = $summary_result->fetch_assoc();
        } else {
            $summary = [
                'total_services' => 0,
                'total_cost' => 0,
                'avg_cost' => 0,
                'unique_vehicles' => 0
            ];
        }
        ?>
        
        <div hidden class="mt-4 p-4 bg-light rounded">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="card border-0 bg-white">
                        <div class="card-body">
                            <h4 class="text-primary"><?php echo $summary['total_services']; ?></h4>
                            <small class="text-muted">Total Services</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-white">
                        <div class="card-body">
                            <h4 class="text-success">₹<?php echo number_format($summary['total_cost'], 2); ?></h4>
                            <small class="text-muted">Total Cost</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-white">
                        <div class="card-body">
                            <h4 class="text-warning">₹<?php echo number_format($summary['avg_cost'], 2); ?></h4>
                            <small class="text-muted">Average Cost</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-white">
                        <div class="card-body">
                            <h4 class="text-info"><?php echo $summary['unique_vehicles']; ?></h4>
                            <small class="text-muted">Vehicles Serviced</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Export Options -->
        <div class="mt-3 text-end">
            <div class="btn-group">
                <button hidden type="button" class="btn btn-success btn-sm" onclick="exportCSV()">
                    <i class="fas fa-file-csv"></i> CSV
                </button>
                <button type="button" class="btn btn-warning btn-sm" onclick="exportExcel()">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
                <button type="button" class="btn btn-danger btn-sm" onclick="exportPDF()">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
                <button hidden type="button" class="btn btn-info btn-sm" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
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

<script>
// Simple table export to CSV
function exportTableToCSV() {
    const table = document.getElementById('servicesTable');
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
    
    // Download CSV file
    const csvString = csv.join('\n');
    const blob = new Blob([csvString], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.setAttribute('hidden', '');
    a.setAttribute('href', url);
    a.setAttribute('download', 'service_records_' + new Date().toISOString().split('T')[0] + '.csv');
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

// Export to Excel with filters
function exportExcel() {
    // Collect all current filter values
    let params = new URLSearchParams(window.location.search);
    
    // Also get values from form fields
    const filters = ['search', 'vehicle_type', 'service_type', 'from_date', 'to_date', 'vehicle_id'];
    filters.forEach(filter => {
        const input = document.querySelector(`[name="${filter}"]`);
        if (input && input.value) {
            params.set(filter, input.value);
        }
    });
    
    // Open export URL
    window.open('export_excel.php?' + params.toString(), '_blank');
}

// Export to PDF with filters
function exportPDF() {
    // Collect all current filter values
    let params = new URLSearchParams(window.location.search);
    
    // Also get values from form fields
    const filters = ['search', 'vehicle_type', 'service_type', 'from_date', 'to_date', 'vehicle_id'];
    filters.forEach(filter => {
        const input = document.querySelector(`[name="${filter}"]`);
        if (input && input.value) {
            params.set(filter, input.value);
        }
    });
    
    // Open export URL
    window.open('export_pdf.php?' + params.toString(), '_blank');
}

// Export to CSV with filters
function exportCSV() {
    // Collect all current filter values
    let params = new URLSearchParams(window.location.search);
    
    // Also get values from form fields
    const filters = ['search', 'vehicle_type', 'service_type', 'from_date', 'to_date', 'vehicle_id'];
    filters.forEach(filter => {
        const input = document.querySelector(`[name="${filter}"]`);
        if (input && input.value) {
            params.set(filter, input.value);
        }
    });
    
    // Open export URL
    window.open('export_csv.php?' + params.toString(), '_blank');
}
</script>

<?php include '../includes/footer.php'; ?>