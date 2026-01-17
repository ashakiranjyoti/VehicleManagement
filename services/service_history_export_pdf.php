<?php
require_once('TCPDF-main/tcpdf.php');
include '../config/database.php';
checkAuth();

$vehicle_id = $_GET['vehicle_id'] ?? '';

// Custom PDF class with structured table
class SERVICE_PDF extends TCPDF
{
    public function ColoredTable($header, $data, $vehicle_info, $show_vehicle)
    {
        // Vehicle info section
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 8, 'SERVICE HISTORY REPORT', 0, 1, 'C');
        
        if (!empty($vehicle_info)) {
            $this->SetFont('helvetica', '', 10);
            $this->Cell(0, 6, 'Vehicle: ' . $vehicle_info['make_model'] . ' | Reg: ' . $vehicle_info['reg_number'] . ' | Owner: ' . ($vehicle_info['owner_full_name'] ?? $vehicle_info['owner_name'] ?? 'N/A'), 0, 1, 'C');
        }
        
        $this->Cell(0, 6, 'Report Date: ' . date('d-m-Y h:i A'), 0, 1, 'C');
        $this->Ln(5);
        
        // Table setup
        $this->SetFillColor(51, 122, 183); // Blue
        $this->SetTextColor(255);
        $this->SetDrawColor(128, 0, 0);
        $this->SetLineWidth(0.3);
        $this->SetFont('helvetica', 'B', 9);
        
        // Set column widths based on whether showing vehicle
        if ($show_vehicle) {
            $w = array(20, 30, 25, 15, 35, 35, 25, 25, 25, 40);
        } else {
            $w = array(20, 30, 25, 15, 35, 25, 25, 40);
        }
        
        // Header
        foreach ($header as $i => $heading) {
            $this->Cell($w[$i], 8, $heading, 1, 0, 'C', 1);
        }
        $this->Ln();
        
        // Data
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('helvetica', '', 8);
        
        $fill = 0;
        $total_cost = 0;
        
        foreach ($data as $row) {
            $rowHeight = 0;
            
            // Calculate height
            foreach ($row as $index => $column) {
                // For cost column, don't use htmlspecialchars on ₹ symbol
                if (strpos($column, '') === 0 || is_numeric(str_replace(['', ',', ' '], '', $column))) {
                    $cellHeight = $this->getStringHeight($w[$index], $column);
                } else {
                    $cellHeight = $this->getStringHeight($w[$index], htmlspecialchars($column, ENT_QUOTES, 'UTF-8'));
                }
                $rowHeight = max($rowHeight, $cellHeight);
            }
            
            // Draw cells
            foreach ($row as $index => $column) {
                $align = 'L';
                if (in_array($index, array(0, 3))) { // Date, Type
                    $align = 'C';
                } elseif (in_array($index, array(2, 5))) { // KM, Cost
                    $align = 'R';
                }
                
                // For cost column, don't encode ₹ symbol
                if (strpos($column, '') === 0 || is_numeric(str_replace(['', ',', ' '], '', $column))) {
                    $cellContent = $column;
                } else {
                    $cellContent = htmlspecialchars($column, ENT_QUOTES, 'UTF-8');
                }
                
                $this->MultiCell($w[$index], $rowHeight, $cellContent, 1, $align, $fill, 0);
                
                // Sum cost
                if (strpos($column, '') === 0) {
                    $cost = str_replace(array('', ','), '', $column);
                    $total_cost += (float)$cost;
                }
            }
            $this->Ln();
            $fill = !$fill;
            
            // Page break
            if ($this->GetY() + $rowHeight > $this->getPageHeight() - $this->getBreakMargin()) {
                $this->AddPage();
                $this->SetFont('helvetica', 'B', 9);
                $this->SetFillColor(51, 122, 183);
                $this->SetTextColor(255);
                foreach ($header as $i => $heading) {
                    $this->Cell($w[$i], 8, $heading, 1, 0, 'C', 1);
                }
                $this->Ln();
                $this->SetFillColor(224, 235, 255);
                $this->SetTextColor(0);
                $this->SetFont('helvetica', '', 8);
            }
        }
        
        // Total row - FIX: Use proper ₹ symbol
        $this->SetFont('helvetica', 'B', 9);
        $this->SetFillColor(200, 220, 255);
        
        if ($show_vehicle) {
            $this->Cell(array_sum(array_slice($w, 0, 8)), 8, 'TOTAL SERVICE COST:', 1, 0, 'R', 1);
            $this->Cell($w[8], 8, '' . number_format($total_cost, 2), 1, 0, 'R', 1); // FIXED
            $this->Cell($w[9], 8, '', 1, 1, 'C', 1);
        } else {
            $this->Cell(array_sum(array_slice($w, 0, 5)), 8, 'TOTAL SERVICE COST:', 1, 0, 'R', 1);
            $this->Cell($w[5], 8, '' . number_format($total_cost, 2), 1, 0, 'R', 1); // FIXED
            $this->Cell(array_sum(array_slice($w, 6)), 8, '', 1, 1, 'C', 1);
        }
        
        $this->Cell(array_sum($w), 0, '', 'T');
    }
}

// Get vehicle info
$vehicle_info = array();
$show_vehicle_column = true;

if (!empty($vehicle_id)) {
    $vehicle_sql = "SELECT v.*, u.full_name as owner_full_name 
                   FROM vehicles v 
                   LEFT JOIN users u ON v.owner_name = u.username 
                   WHERE v.id = $vehicle_id";
    $vehicle_result = $conn->query($vehicle_sql);
    if ($vehicle_result && $vehicle_result->num_rows > 0) {
        $vehicle_info = $vehicle_result->fetch_assoc();
        $show_vehicle_column = false;
    }
}

// Query services
$where = "";
if (!empty($vehicle_id)) {
    $where = "WHERE s.vehicle_id = '" . intval($vehicle_id) . "'";
}

$sql = "SELECT s.*, v.make_model, v.reg_number, v.vehicle_type, v.owner_name
       FROM services s 
       JOIN vehicles v ON s.vehicle_id = v.id 
       $where 
       ORDER BY s.service_date DESC, s.service_time DESC";

$result = $conn->query($sql);

// Create PDF
$pdf = new SERVICE_PDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator('Vehicle Management System');
$pdf->SetAuthor($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin');
$pdf->SetTitle('Service History');

// Set margins
$pdf->SetMargins(10, 20, 10);

// Auto page break
$pdf->SetAutoPageBreak(TRUE, 20);

$pdf->AddPage();

// Prepare data - FIX: Don't add ₹ symbol in data array, add it only in display
$data = array();
$truncate = function($text, $length = 15) {
    return (strlen($text) > $length) ? substr($text, 0, $length) . '...' : $text;
};

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        if ($show_vehicle_column) {
            $data[] = array(
                date('d-m-y', strtotime($row['service_date'])),
                $truncate($row['make_model']),
                $row['reg_number'],
                strtoupper(substr($row['vehicle_type'], 0, 1)),
                $truncate($row['service_type']),
                $row['running_km'] ? number_format($row['running_km']) : '-',
                $truncate($row['service_center_name'] ?? '-'),
                $truncate($row['service_done_by'] ?? '-'),
                number_format($row['cost'], 2), // Store just number
                $truncate($row['created_by'])
            );
        } else {
            $data[] = array(
                date('d-m-y', strtotime($row['service_date'])),
                $truncate($row['service_type']),
                $row['running_km'] ? number_format($row['running_km']) : '-',
                $truncate($row['service_center_name'] ?? '-'),
                $truncate($row['service_done_by'] ?? '-'),
                number_format($row['cost'], 2), // Store just number
                $truncate($row['created_by']),
                $truncate($row['description'] ?? '-', 20)
            );
        }
    }
}

// Headers
if ($show_vehicle_column) {
    $header = array('Date', 'Vehicle', 'Reg No', 'T', 'Service Type', 'KM', 'Center', 'Done By', 'Cost ', 'Created By');
} else {
    $header = array('Date', 'Service Type', 'KM', 'Center', 'Done By', 'Cost ', 'Created By', 'Description');
}

// Generate table - MODIFIED to add ₹ symbol during display
if (!empty($data)) {
    // Modify data array to add ₹ symbol to cost column
    $display_data = array();
    foreach ($data as $row) {
        $display_row = array();
        foreach ($row as $index => $value) {
            // Add ₹ symbol to cost column (index 8 for vehicle view, 5 for non-vehicle view)
            if (($show_vehicle_column && $index == 8) || (!$show_vehicle_column && $index == 5)) {
                $display_row[] = '' . $value;
            } else {
                $display_row[] = $value;
            }
        }
        $display_data[] = $display_row;
    }
    
    $pdf->ColoredTable($header, $display_data, $vehicle_info, $show_vehicle_column);
} else {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'NO SERVICE RECORDS FOUND', 0, 1, 'C');
}

// Output
$filename = 'service_history_' . (!empty($vehicle_id) ? 'vehicle_' . $vehicle_id : 'all') . '_' . date('Ymd_His') . '.pdf';
$pdf->Output($filename, 'D');

$conn->close();
exit();
?>