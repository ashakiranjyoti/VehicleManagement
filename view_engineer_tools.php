<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
checkAuth();
include 'includes/header.php';

$engineer_id = $_GET['engineer_id'] ?? '';

if (empty($engineer_id)) {
    echo "<div class='alert alert-danger'>No engineer selected!</div>";
    include 'includes/footer.php';
    exit();
}

// Get engineer details
$engineer_sql = "SELECT * FROM engineers WHERE id = '$engineer_id'";
$engineer_result = $conn->query($engineer_sql);
$engineer = $engineer_result->fetch_assoc();

if (!$engineer) {
    echo "<div class='alert alert-danger'>Engineer not found!</div>";
    include 'includes/footer.php';
    exit();
}

// Get assigned tools
$tools_sql = "SELECT et.*, t.tool_name 
              FROM engineer_tools et
              JOIN tools t ON et.tool_id = t.id
              WHERE et.engineer_id = '$engineer_id'
              ORDER BY et.assigned_date DESC";
$tools_result = $conn->query($tools_sql);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-toolbox"></i> Engineer Tools Report
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
    <a href="manage_engineers.php" class="btn btn-secondary me-2">
        <i class="fas fa-arrow-left"></i> Back to Engineers
    </a>
    <a href="create_engineer_tools_pdf.php?engineer_id=<?php echo $engineer_id; ?>" 
       class="btn btn-primary" target="_blank">
        <i class="fas fa-file-pdf"></i> Download PDF
    </a>
</div>
</div>

<!-- Engineer Information -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-user-tie"></i> Engineer Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>Engineer Name:</strong><br>
                <h4><?php echo $engineer['eng_name']; ?></h4>
            </div>
            <div class="col-md-3">
                <strong>Designation:</strong><br>
                <h5><?php echo $engineer['designation']; ?></h5>
            </div>
            <div class="col-md-3">
                <strong>Mobile:</strong><br>
                <h5><i class="fas fa-phone"></i> <?php echo $engineer['mobile_number']; ?></h5>
            </div>
            <div class="col-md-3">
                <strong>Total Tools:</strong><br>
                <h5><?php echo $tools_result->num_rows; ?> tools</h5>
            </div>
        </div>
    </div>
</div>

<!-- Tools List -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-tools"></i> Assigned Tools List</h5>
    </div>
    <div class="card-body">
        <?php if ($tools_result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered" id="toolsReport">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Tool Name</th>
                        <th>Type/Capacity</th>
                        <th>Make/Model</th>
                        <th>Quantity</th>
                        <th>Assigned Date</th>
                        <th>Assigned By</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $counter = 1;
                    $total_quantity = 0;
                    while($tool = $tools_result->fetch_assoc()) {
                        $total_quantity += $tool['tool_quantity'];
                        echo "<tr>
                                <td>{$counter}</td>
                                <td><strong>{$tool['tool_name']}</strong></td>
                                <td>{$tool['tool_type_capacity']}</td>
                                <td>{$tool['tool_make_model']}</td>
                                <td>{$tool['tool_quantity']}</td>
                                <td>" . date('d M Y', strtotime($tool['assigned_date'])) . "</td>
                                <td>{$tool['assigned_by']}</td>
                                <td>" . nl2br(htmlspecialchars($tool['remarks'])) . "</td>
                              </tr>";
                        $counter++;
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr class="table-secondary">
                        <td colspan="4" class="text-end"><strong>Total:</strong></td>
                        <td><strong><?php echo $total_quantity; ?></strong></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-tools fa-2x text-muted mb-3"></i><br>
                No tools assigned.
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>