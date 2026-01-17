<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
checkAuth();

require_once('services/TCPDF-main/tcpdf.php');

set_time_limit(0);
ini_set('memory_limit', '512M');

$engineer_id = $_GET['engineer_id'] ?? '';

if (empty($engineer_id)) {
    die('No engineer selected!');
}

// Get engineer details
$engineer_sql = "SELECT * FROM engineers WHERE id = '$engineer_id'";
$engineer_result = $conn->query($engineer_sql);
$engineer = $engineer_result->fetch_assoc();

if (!$engineer) {
    die('Engineer not found!');
}

// Get assigned tools
$tools_sql = "SELECT et.*, t.tool_name 
              FROM engineer_tools et
              JOIN tools t ON et.tool_id = t.id
              WHERE et.engineer_id = '$engineer_id'
              ORDER BY et.assigned_date DESC";
$tools_result = $conn->query($tools_sql);

// Custom PDF class
class ENGINEER_PDF extends TCPDF
{
    private $engineer_name;
    private $report_date;
    
    public function setEngineerInfo($name, $date)
    {
        $this->engineer_name = $name;
        $this->report_date = $date;
    }
    
    // Page header
    public function Header()
    {
        // Logo
        // $image_file = 'images/Inventory Management Systemlogo.png';
        // $this->Image($image_file, 10, 10, 15, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        
        // Set font
        $this->SetFont('helvetica', 'B', 16);
        
        // Title
        $this->Cell(0, 10, 'TOOL MANAGEMENT SYSTEM', 0, 1, 'C');
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 10, 'ENGINEER TOOLS REPORT', 0, 1, 'C');
        
        // Engineer info line
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 6, 'Engineer: ' . $this->engineer_name, 0, 1, 'C');
        $this->Cell(0, 6, 'Report Date: ' . $this->report_date, 0, 1, 'C');
        
        // Line separator
        $this->Ln(2);
        $this->SetLineWidth(0.5);
        $this->Line(10, 40, 287, 40);
        $this->Ln(5);
    }
    
    // Page footer
    public function Footer()
    {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        
        // Page number
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
    
    // Colored table method like your example
    public function ColoredTable($header, $data, $engineer_info)
    {
        // Display engineer summary
        $this->SetFont('helvetica', 'B', 11);
        $this->Cell(0, 8, 'ENGINEER INFORMATION', 0, 1, 'L');
        
        $this->SetFont('helvetica', '', 10);
        $this->Cell(70, 6, 'Name: ' . $engineer_info['eng_name'], 0, 0, 'L');
        $this->Cell(70, 6, 'Designation: ' . $engineer_info['designation'], 0, 0, 'L');
        $this->Cell(70, 6, 'Mobile: ' . $engineer_info['mobile_number'], 0, 1, 'L');
        
        $this->Cell(70, 6, 'Total Tools: ' . count($data) . ' items', 0, 0, 'L');
        $this->Cell(70, 6, 'Report Generated: ' . date('d-m-Y h:i A'), 0, 0, 'L');
        $this->Cell(70, 6, '', 0, 1, 'L');
        
        $this->Ln(5);
        
        // Table header colors
        $this->SetFillColor(51, 122, 183); // Blue color
        $this->SetTextColor(255); // White text
        $this->SetDrawColor(128, 0, 0); // Dark red border
        $this->SetLineWidth(0.3);
        $this->SetFont('helvetica', 'B', 9);
        
        // Column widths (adjusted for 8 columns)
        $w = array(10, 60, 30, 30, 25, 28, 32, 60); 
        
        // Header
        foreach ($header as $i => $heading) {
            $this->Cell($w[$i], 8, $heading, 1, 0, 'C', 1);
        }
        $this->Ln();
        
        // Table content
        $this->SetFillColor(224, 235, 255); // Light blue
        $this->SetTextColor(0); // Black text
        $this->SetFont('helvetica', '', 8);
        
        $fill = 0;
        $total_quantity = 0;
        
        foreach ($data as $row) {
            $rowHeight = 0;
            
            // Calculate height needed for each row
            foreach ($row as $index => $column) {
                $cellHeight = $this->getStringHeight($w[$index], htmlspecialchars($column, ENT_QUOTES, 'UTF-8'));
                $rowHeight = max($rowHeight, $cellHeight);
            }
            
            // Output row with calculated height
            foreach ($row as $index => $column) {
                if ($index == 4) { // Quantity column (5th column)
                    $alignment = 'C';
                } else {
                    $alignment = 'L';
                }
                
                $this->MultiCell($w[$index], $rowHeight, htmlspecialchars($column, ENT_QUOTES, 'UTF-8'), 1, $alignment, $fill, 0, '', '', true);
                
                // Add to total quantity (column index 4 is quantity)
                if ($index == 4) {
                    $total_quantity += (int)$column;
                }
            }
            $this->Ln();
            $fill = !$fill;
            
            // Check for page break
            if ($this->GetY() + $rowHeight > $this->getPageHeight() - $this->getBreakMargin()) {
                $this->AddPage();
                // Reprint header
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
        
        // Total row
        $this->SetFont('helvetica', 'B', 9);
        $this->SetFillColor(200, 220, 255);
        $this->Cell(array_sum(array_slice($w, 0, 4)), 8, 'TOTAL QUANTITY:', 1, 0, 'R', 1);
        $this->Cell($w[4], 8, $total_quantity, 1, 0, 'C', 1);
        $this->Cell(array_sum(array_slice($w, 5)), 8, '', 1, 1, 'C', 1);
        
        $this->Cell(array_sum($w), 0, '', 'T');
    }
}

// Create PDF in Landscape
$pdf = new ENGINEER_PDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set PDF information
$pdf->SetCreator('Tool Management System');
$pdf->SetAuthor('Admin');
$pdf->SetTitle('Engineer Tools Report - ' . $engineer['eng_name']);
$pdf->SetSubject('Tools Report');

// Set engineer info for header
$pdf->setEngineerInfo($engineer['eng_name'], date('d-m-Y'));

// Remove default header/footer (we have custom)
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);

// Set margins
$pdf->SetMargins(10, 45, 10); // Top margin increased for header

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Add a page
$pdf->AddPage();

// Set font for main content
$pdf->SetFont('helvetica', '', 8);

// Table header
$header = array(
    'S.No',
    'Tool Name',
    'Type/Capacity',
    'Make/Model',
    'Qty',
    'Assigned Date',
    'Assigned By',
    'Remarks'
);

// Prepare data
$data = array();
$serial_no = 1;

// Function to truncate long text
$truncate = function($text, $length = 50) {
    return (strlen($text) > $length) ? substr($text, 0, $length-3).'...' : $text;
};

while ($tool = $tools_result->fetch_assoc()) {
    $remarks = $tool['remarks'] ?? '';
    if (empty($remarks)) {
        $remarks = '-';
    }
    
    $data[] = array(
        $serial_no,
        $truncate($tool['tool_name']),
        htmlspecialchars($tool['tool_type_capacity'] ?? '-', ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($tool['tool_make_model'] ?? '-', ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($tool['tool_quantity'] ?? '0', ENT_QUOTES, 'UTF-8'),
        date('d-m-Y', strtotime($tool['assigned_date'])),
        htmlspecialchars($tool['assigned_by'] ?? '-', ENT_QUOTES, 'UTF-8'),
        $truncate($remarks, 40)
    );
    $serial_no++;
}

// Generate colored table
$pdf->ColoredTable($header, $data, $engineer);

// Add signature section
$pdf->Ln(15);
$pdf->SetFont('helvetica', '', 10);



// $pdf->writeHTML($signature_html, true, false, true, false, '');

// Add footer note
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(100, 100, 100);
// $pdf->Cell(0, 10, 'Note: This is a computer generated report.', 0, 1, 'C');

// Output PDF
$clean_name = preg_replace('/[^a-zA-Z0-9]/', '_', $engineer['eng_name']);
$filename = 'Engineer_Tools_Report_' . $clean_name . '_' . date('Ymd_His') . '.pdf';

$pdf->Output($filename, 'D');

// Close database connection
$conn->close();
exit();
?>