<?php
include '../config/database.php';
checkAuth();

// Start output buffering
ob_start();

// Build WHERE clause EXACTLY like in view_services.php
$where = "WHERE 1=1";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $safe_search = $conn->real_escape_string($_GET['search']);
    $where .= " AND (s.service_type LIKE '%$safe_search%' OR s.description LIKE '%$safe_search%' OR v.make_model LIKE '%$safe_search%' OR v.reg_number LIKE '%$safe_search%' OR s.service_center_name LIKE '%$safe_search%' OR s.service_done_by LIKE '%$safe_search%')";
}

if (isset($_GET['vehicle_type']) && !empty($_GET['vehicle_type'])) {
    $safe_type = $conn->real_escape_string($_GET['vehicle_type']);
    $where .= " AND v.vehicle_type = '$safe_type'";
}

if (isset($_GET['service_type']) && !empty($_GET['service_type'])) {
    $safe_service_type = $conn->real_escape_string($_GET['service_type']);
    $where .= " AND s.service_type = '$safe_service_type'";
}

if (isset($_GET['from_date']) && !empty($_GET['from_date'])) {
    $where .= " AND s.service_date >= '" . $conn->real_escape_string($_GET['from_date']) . "'";
}

if (isset($_GET['to_date']) && !empty($_GET['to_date'])) {
    $where .= " AND s.service_date <= '" . $conn->real_escape_string($_GET['to_date']) . "'";
}

if (isset($_GET['vehicle_id']) && !empty($_GET['vehicle_id'])) {
    $where .= " AND s.vehicle_id = '" . intval($_GET['vehicle_id']) . "'";
}

$sql = "SELECT s.*, v.make_model, v.reg_number, v.vehicle_type, v.owner_name
        FROM services s
        JOIN vehicles v ON s.vehicle_id = v.id
        $where
        ORDER BY s.service_date DESC, s.service_time DESC";

$result = $conn->query($sql);

// Set headers for Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="service_records_' . date('Y-m-d_H-i') . '.xls"');
?>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #4CAF50; color: white; padding: 8px; text-align: left; }
        td { border: 1px solid #ddd; padding: 8px; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="title">Service Records Report</div>
    <div>Generated on: <?php echo date('d/m/Y H:i:s'); ?></div>
    <div>User: <?php echo $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin'; ?></div>
    <br>
    
    <?php
    // Display filter info
    $hasFilters = false;
    $filterInfo = '';
    
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $filterInfo .= "Search: " . htmlspecialchars($_GET['search']) . "<br>";
        $hasFilters = true;
    }
    if (isset($_GET['vehicle_type']) && !empty($_GET['vehicle_type'])) {
        $filterInfo .= "Vehicle Type: " . htmlspecialchars($_GET['vehicle_type']) . "<br>";
        $hasFilters = true;
    }
    if (isset($_GET['service_type']) && !empty($_GET['service_type'])) {
        $filterInfo .= "Service Type: " . htmlspecialchars($_GET['service_type']) . "<br>";
        $hasFilters = true;
    }
    if (isset($_GET['from_date']) && !empty($_GET['from_date'])) {
        $filterInfo .= "From Date: " . htmlspecialchars($_GET['from_date']) . "<br>";
        $hasFilters = true;
    }
    if (isset($_GET['to_date']) && !empty($_GET['to_date'])) {
        $filterInfo .= "To Date: " . htmlspecialchars($_GET['to_date']) . "<br>";
        $hasFilters = true;
    }
    if (isset($_GET['vehicle_id']) && !empty($_GET['vehicle_id'])) {
        $filterInfo .= "Vehicle ID: " . htmlspecialchars($_GET['vehicle_id']) . "<br>";
        $hasFilters = true;
    }
    
    if ($hasFilters) {
        echo "<div><strong>Filters Applied:</strong><br>" . $filterInfo . "</div><br>";
    }
    ?>
    
    <table border="1">
        <tr>
            <th>Date</th>
            <th>Time</th>
            <th>Vehicle</th>
            <th>Reg Number</th>
            <th>Vehicle Type</th>
            <th>Service Type</th>
            <th>Running KM</th>
            <th>Service Center</th>
            <th>Location</th>
            <th>Done By</th>
            <th>Cost (₹)</th>
            <th>Description</th>
            <th>Created By</th>
        </tr>
        <?php
        $total_cost = 0;
        $record_count = 0;
        
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $record_count++;
                $total_cost += $row['cost'];
                echo "<tr>
                        <td>" . date('d/m/Y', strtotime($row['service_date'])) . "</td>
                        <td>" . $row['service_time'] . "</td>
                        <td>" . htmlspecialchars($row['make_model']) . "</td>
                        <td>" . $row['reg_number'] . "</td>
                        <td>" . ucfirst($row['vehicle_type']) . "</td>
                        <td>" . $row['service_type'] . "</td>
                        <td>" . ($row['running_km'] ? number_format($row['running_km']) : 'N/A') . "</td>
                        <td>" . htmlspecialchars($row['service_center_name'] ?? 'N/A') . "</td>
                        <td>" . htmlspecialchars($row['service_center_place'] ?? 'N/A') . "</td>
                        <td>" . htmlspecialchars($row['service_done_by'] ?? 'N/A') . "</td>
                        <td>" . number_format($row['cost'], 2) . "</td>
                        <td>" . htmlspecialchars(substr($row['description'] ?? 'No description', 0, 100)) . "</td>
                        <td>" . $row['created_by'] . "</td>
                      </tr>";
            }
            
            // Summary row
            echo "<tr style='background-color: #FFFFCC; font-weight: bold;'>
                    <td colspan='10' style='text-align: right;'>Total Records: " . $record_count . "</td>
                    <td>₹" . number_format($total_cost, 2) . "</td>
                    <td colspan='2'></td>
                  </tr>";
        } else {
            echo "<tr><td colspan='13' style='text-align: center; color: red; padding: 20px;'>No service records found for the selected criteria</td></tr>";
        }
        ?>
    </table>
    
    <br>
    <div style="font-size: 11px; color: #666;">
        Generated by Vehicle Management System | <?php echo date('d/m/Y H:i:s'); ?>
    </div>
</body>
</html>
<?php
ob_end_flush();
?>