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

if (!is_numeric($engineer_id)) {
    echo "<div class='alert alert-danger'>Invalid engineer ID!</div>";
    include 'includes/footer.php';
    exit();
}

$engineer_sql = "SELECT * FROM engineers WHERE id = '" . $conn->real_escape_string($engineer_id) . "'";
$engineer_result = $conn->query($engineer_sql);

if (!$engineer_result || $engineer_result->num_rows === 0) {
    echo "<div class='alert alert-danger'>Engineer not found!</div>";
    include 'includes/footer.php';
    exit();
}

$engineer = $engineer_result->fetch_assoc();

function cleanMakeModelForDisplay($make_model) {
    $clean = preg_replace('/\s*\[.*?\]\s*$/', '', $make_model);
    return trim($clean);
}

// Delete tool assignment
if ($_POST && isset($_POST['delete_assignment'])) {
    $assignment_id = $conn->real_escape_string($_POST['assignment_id']);
    $delete_reason = $conn->real_escape_string($_POST['delete_reason'] ?? '');
    $deleted_by = $_SESSION['username'] ?? 'admin';
    
    // Get assignment details before deletion
    $get_sql = "SELECT et.*, t.tool_name FROM engineer_tools et 
                JOIN tools t ON et.tool_id = t.id 
                WHERE et.id = '$assignment_id'";
    $get_result = $conn->query($get_sql);
    
    if ($get_result && $get_result->num_rows > 0) {
        $assignment = $get_result->fetch_assoc();
        
        // Clean make/model for history
        $clean_make_model = cleanMakeModelForDisplay($assignment['tool_make_model']);
        
        // First, add to history before deleting
        $history_sql = "INSERT INTO tool_history (tool_id, engineer_id, action_type, details, action_by) 
                       VALUES ('{$assignment['tool_id']}', '{$assignment['engineer_id']}', 'delete', 
                       'Deleted assignment: {$assignment['tool_quantity']} {$clean_make_model} ({$assignment['tool_name']} - {$assignment['tool_type_capacity']}). Reason: $delete_reason', 
                       '$deleted_by')";
        
        if ($conn->query($history_sql)) {
            // Now delete the assignment
            $delete_sql = "DELETE FROM engineer_tools WHERE id = '$assignment_id'";
            
            if ($conn->query($delete_sql)) {
                echo '<div class="alert alert-success">Tool assignment deleted successfully!</div>';
            } else {
                echo '<div class="alert alert-danger">Error deleting assignment: ' . $conn->error . '</div>';
            }
        } else {
            echo '<div class="alert alert-danger">Error adding to history: ' . $conn->error . '</div>';
        }
    } else {
        echo '<div class="alert alert-danger">Assignment not found!</div>';
    }
}

// Assign tool to engineer
if ($_POST && isset($_POST['assign_tool'])) {
    $engineer_id = $conn->real_escape_string($_POST['engineer_id']);
    $tool_id = $conn->real_escape_string($_POST['tool_id']);
    $tool_type_capacity = trim($conn->real_escape_string($_POST['tool_type_capacity'] ?? ''));
    $original_make_model = trim($conn->real_escape_string($_POST['tool_make_model'] ?? ''));
    
    $tool_name_sql = "SELECT tool_name FROM tools WHERE id = '$tool_id'";
    $tool_name_result = $conn->query($tool_name_sql);
    $tool_name_row = $tool_name_result->fetch_assoc();
    $tool_name = $tool_name_row['tool_name'];
    
    $check_sql = "SELECT id FROM engineer_tools WHERE engineer_id = '$engineer_id' 
                  AND tool_id = '$tool_id' 
                  AND tool_make_model = '" . $conn->real_escape_string($original_make_model) . "' 
                  AND tool_type_capacity = '" . $conn->real_escape_string($tool_type_capacity) . "'
                  AND is_current = 1";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        echo '<div class="alert alert-danger">This tool with same make/model AND same capacity/size is already assigned to this engineer!</div>';
    } else {
        $check_diff_sql = "SELECT id, tool_type_capacity FROM engineer_tools 
                          WHERE engineer_id = '$engineer_id' 
                          AND tool_id = '$tool_id' 
                          AND tool_make_model = '" . $conn->real_escape_string($original_make_model) . "'
                          AND tool_type_capacity != '" . $conn->real_escape_string($tool_type_capacity) . "'
                          AND is_current = 1";
        $check_diff_result = $conn->query($check_diff_sql);
        
        if ($check_diff_result->num_rows > 0) {
            $db_make_model = $original_make_model . " [" . $tool_type_capacity . "]";
        } else {
            $db_make_model = $original_make_model;
        }
        
        $tool_quantity = $conn->real_escape_string($_POST['tool_quantity'] ?? 1);
        $remarks = $conn->real_escape_string($_POST['remarks'] ?? '');
        $assigned_by = $_SESSION['username'] ?? 'admin';
        $assigned_date = $conn->real_escape_string($_POST['assigned_date'] ?? date('Y-m-d'));
        $status = $conn->real_escape_string($_POST['status'] ?? 'assigned');
        
        $sql = "INSERT INTO engineer_tools (engineer_id, tool_id, tool_type_capacity, tool_make_model, 
                tool_quantity, remarks, assigned_by, assigned_date, status, is_current) 
                VALUES ('$engineer_id', '$tool_id', '" . $conn->real_escape_string($tool_type_capacity) . "', 
                '" . $conn->real_escape_string($db_make_model) . "', 
                '$tool_quantity', '$remarks', '$assigned_by', '$assigned_date', '$status', TRUE)";
        
        if ($conn->query($sql) === TRUE) {
            
            $history_sql = "INSERT INTO tool_history (tool_id, engineer_id, action_type, details, action_by) 
                           VALUES ('$tool_id', '$engineer_id', 'assign', 
                           'Assigned {$tool_quantity} {$original_make_model} ({$tool_name} - {$tool_type_capacity}) to engineer', '$assigned_by')";
            $conn->query($history_sql);
            
            echo '<div class="alert alert-success">Tool assigned successfully!</div>';
        } else {
            if (strpos($conn->error, 'Duplicate entry') !== false) {
                $db_make_model = $original_make_model . " | " . $tool_type_capacity;
                
                $sql2 = "INSERT INTO engineer_tools (engineer_id, tool_id, tool_type_capacity, tool_make_model, 
                        tool_quantity, remarks, assigned_by, assigned_date, status, is_current) 
                        VALUES ('$engineer_id', '$tool_id', '" . $conn->real_escape_string($tool_type_capacity) . "', 
                        '" . $conn->real_escape_string($db_make_model) . "', 
                        '$tool_quantity', '$remarks', '$assigned_by', '$assigned_date', '$status', TRUE)";
                
                if ($conn->query($sql2) === TRUE) {
                    $history_sql = "INSERT INTO tool_history (tool_id, engineer_id, action_type, details, action_by) 
                                   VALUES ('$tool_id', '$engineer_id', 'assign', 
                                   'Assigned {$tool_quantity} {$original_make_model} ({$tool_name} - {$tool_type_capacity}) to engineer', '$assigned_by')";
                    $conn->query($history_sql);
                    
                    echo '<div class="alert alert-success">Tool assigned successfully!</div>';
                } else {
                    echo '<div class="alert alert-danger">Error assigning tool: ' . $conn->error . '</div>';
                }
            } else {
                echo '<div class="alert alert-danger">Error assigning tool: ' . $conn->error . '</div>';
            }
        }
    }
}

if ($_POST && isset($_POST['return_tool'])) {
    $assignment_id = $conn->real_escape_string($_POST['assignment_id']);
    $returned_to = $conn->real_escape_string($_POST['returned_to'] ?? 'Store');
    $return_reason = $conn->real_escape_string($_POST['return_reason'] ?? '');
    $returned_by = $_SESSION['username'] ?? 'admin';
    
    $get_sql = "SELECT et.*, t.tool_name FROM engineer_tools et 
                JOIN tools t ON et.tool_id = t.id 
                WHERE et.id = '$assignment_id'";
    $get_result = $conn->query($get_sql);
    $assignment = $get_result->fetch_assoc();
    
    if ($assignment) {
        $sql = "UPDATE engineer_tools SET 
                status = 'returned',
                returned_date = CURRENT_DATE,
                returned_to = '$returned_to',
                return_reason = '$return_reason',
                is_current = FALSE,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = '$assignment_id'";
        
        if ($conn->query($sql) === TRUE) {
            $clean_make_model = cleanMakeModelForDisplay($assignment['tool_make_model']);
            
            $history_sql = "INSERT INTO tool_history (tool_id, engineer_id, action_type, details, action_by) 
                           VALUES ('{$assignment['tool_id']}', '{$assignment['engineer_id']}', 'return', 
                           'Returned {$assignment['tool_quantity']} {$clean_make_model} ({$assignment['tool_name']}) to $returned_to. Reason: $return_reason', 
                           '$returned_by')";
            $conn->query($history_sql);
            
            echo '<div class="alert alert-success">Tool returned successfully!</div>';
        } else {
            echo '<div class="alert alert-danger">Error returning tool: ' . $conn->error . '</div>';
        }
    }
}

if ($_POST && isset($_POST['update_assigned_tool'])) {
    $assignment_id = $conn->real_escape_string($_POST['assignment_id']);
    $tool_type_capacity = $conn->real_escape_string($_POST['tool_type_capacity'] ?? '');
    $tool_make_model = $conn->real_escape_string($_POST['tool_make_model'] ?? '');
    $tool_quantity = $conn->real_escape_string($_POST['tool_quantity'] ?? 1);
    $remarks = $conn->real_escape_string($_POST['remarks'] ?? '');
    $assigned_date = $conn->real_escape_string($_POST['assigned_date'] ?? date('Y-m-d'));
    
    // Get old data for history
    $old_sql = "SELECT * FROM engineer_tools WHERE id = '$assignment_id'";
    $old_result = $conn->query($old_sql);
    $old_data = $old_result->fetch_assoc();
    
    $sql = "UPDATE engineer_tools SET 
            tool_type_capacity = '$tool_type_capacity',
            tool_make_model = '$tool_make_model',
            tool_quantity = '$tool_quantity',
            remarks = '$remarks',
            assigned_date = '$assigned_date',
            updated_at = CURRENT_TIMESTAMP
            WHERE id = '$assignment_id'";
    
    if ($conn->query($sql) === TRUE) {
        // Add to history
        $history_sql = "INSERT INTO tool_history (tool_id, engineer_id, action_type, details, action_by) 
                       VALUES ('{$old_data['tool_id']}', '{$old_data['engineer_id']}', 'update', 
                       'Updated tool assignment. Old: {$old_data['tool_make_model']} ({$old_data['tool_type_capacity']}) Qty:{$old_data['tool_quantity']} | New: {$tool_make_model} ({$tool_type_capacity}) Qty:{$tool_quantity}', 
                       '{$_SESSION['username']}')";
        $conn->query($history_sql);
        
        echo '<div class="alert alert-success">Tool assignment updated successfully!</div>';
    } else {
        echo '<div class="alert alert-danger">Error updating assignment: ' . $conn->error . '</div>';
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-tools"></i> Tool Management: 
        <span class="text-primary"><?php echo htmlspecialchars($engineer['eng_name']); ?></span>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="manage_engineers.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Engineers
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-user-tie"></i> Engineer Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>Name:</strong> <?php echo htmlspecialchars($engineer['eng_name']); ?>
            </div>
            <div class="col-md-3">
                <strong>Designation:</strong> <?php echo htmlspecialchars($engineer['designation']); ?>
            </div>
            <div class="col-md-3">
                <strong>Mobile:</strong> <?php echo htmlspecialchars($engineer['mobile_number']); ?>
            </div>
            <div class="col-md-3">
                <?php
                $count_sql = "SELECT COUNT(*) as total FROM engineer_tools WHERE engineer_id = '$engineer_id' AND is_current = 1";
                $count_result = $conn->query($count_sql);
                $count = $count_result->fetch_assoc();
                ?>
                <strong>Current Tools:</strong> 
                <span class="badge bg-success"><?php echo $count['total']; ?></span>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs" id="toolTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="current-tab" data-bs-toggle="tab" data-bs-target="#current" type="button">
            <i class="fas fa-toolbox"></i> Current Tools
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="assign-tab" data-bs-toggle="tab" data-bs-target="#assign" type="button">
            <i class="fas fa-plus"></i> Assign New Tool
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button">
            <i class="fas fa-history"></i> Tool History
        </button>
    </li>
</ul>

<div class="tab-content mt-4" id="toolTabsContent">
    <div class="tab-pane fade show active" id="current" role="tabpanel">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-toolbox"></i> Currently Assigned Tools</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $current_sql = "SELECT et.*, t.tool_name 
                                        FROM engineer_tools et
                                        JOIN tools t ON et.tool_id = t.id
                                        WHERE et.engineer_id = '$engineer_id' AND et.is_current = 1
                                        ORDER BY et.assigned_date DESC";
                        $current_result = $conn->query($current_sql);
                        
                        if ($current_result && $current_result->num_rows > 0) {
                            echo '<div class="table-responsive">';
                            echo '<table class="table table-hover">';
                            echo '<thead><tr>
                                    <th>Tool Name</th>
                                    <th>Make/Model</th>
                                    <th>Size/Capacity</th>
                                    <th>Qty</th>
                                    <th>Assigned On</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                  </tr></thead>';
                            echo '<tbody>';
                            
                            while($tool = $current_result->fetch_assoc()) {
                                $status_class = $tool['status'] == 'assigned' ? 'success' : 
                                              ($tool['status'] == 'damaged' ? 'danger' : 
                                              ($tool['status'] == 'lost' ? 'warning' : 'secondary'));
                                
                                // Clean make/model for display (remove capacity from brackets)
                                $display_make_model = cleanMakeModelForDisplay($tool['tool_make_model']);
                                
                                echo "<tr>
                                        <td>{$tool['tool_name']}</td>
                                        <td>" . htmlspecialchars($display_make_model) . "</td>
                                        <td>{$tool['tool_type_capacity']}</td>
                                        <td>{$tool['tool_quantity']}</td>
                                        <td>" . date('d M Y', strtotime($tool['assigned_date'])) . "</td>
                                        <td><span class='badge bg-{$status_class}'>{$tool['status']}</span></td>
                                        <td>
                                            <div class='btn-group btn-group-sm'>
                                                <button class='btn btn-outline-warning edit-assignment' 
                                                        data-id='{$tool['id']}'
                                                        data-tool_type_capacity='" . htmlspecialchars($tool['tool_type_capacity']) . "'
                                                        data-tool_make_model='" . htmlspecialchars($tool['tool_make_model']) . "'
                                                        data-tool_quantity='{$tool['tool_quantity']}'
                                                        data-remarks='" . htmlspecialchars($tool['remarks']) . "'
                                                        data-assigned_date='{$tool['assigned_date']}'>
                                                    <i class='fas fa-edit'></i> Edit
                                                </button>
                                                <button class='btn btn-outline-info return-tool' 
                                                        data-id='{$tool['id']}'
                                                        data-tool_name='" . htmlspecialchars($tool['tool_name']) . "'
                                                        data-tool_make_model='" . htmlspecialchars($tool['tool_make_model']) . "'>
                                                    <i class='fas fa-undo'></i> Return
                                                </button>
                                                <button class='btn btn-outline-danger delete-assignment' 
                                                        data-id='{$tool['id']}'
                                                        data-tool_name='" . htmlspecialchars($tool['tool_name']) . "'
                                                        data-tool_make_model='" . htmlspecialchars($tool['tool_make_model']) . "'
                                                        data-tool_capacity='" . htmlspecialchars($tool['tool_type_capacity']) . "'>
                                                    <i class='fas fa-trash'></i> Delete
                                                </button>
                                            </div>
                                        </td>
                                      </tr>";
                            }
                            
                            echo '</tbody></table></div>';
                            
                            $total_sql = "SELECT SUM(tool_quantity) as total_qty FROM engineer_tools 
                                         WHERE engineer_id = '$engineer_id' AND is_current = 1";
                            $total_result = $conn->query($total_sql);
                            $total = $total_result->fetch_assoc();
                            
                        } else {
                            echo '<div class="text-center py-4">
                                    <i class="fas fa-tools fa-3x text-muted mb-3"></i><br>
                                    <h5>No tools currently assigned</h5>
                                    <p class="text-muted">Assign tools using the "Assign New Tool" tab</p>
                                  </div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="tab-pane fade" id="assign" role="tabpanel">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-plus"></i> Assign New Tool to Engineer</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="engineer_id" value="<?php echo $engineer_id; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Select Tool <span class="text-danger">*</span></label>
                                <select name="tool_id" class="form-select" required id="tool_select">
                                    <option value="">Select Tool</option>
                                    <?php
                                    $tools_sql = "SELECT * FROM tools ORDER BY tool_name";
                                    $tools_result = $conn->query($tools_sql);
                                    while($tool = $tools_result->fetch_assoc()) {
                                        $selected = isset($_POST['tool_id']) && $_POST['tool_id'] == $tool['id'] ? 'selected' : '';
                                        echo "<option value='{$tool['id']}' $selected>{$tool['tool_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tool Size/Capacity <span class="text-danger">*</span></label>
                                        <input type="text" name="tool_type_capacity" class="form-control" 
                                               placeholder="e.g., 12hp, 20-20, 56hp, Digital, 500V"
                                               value="<?php echo htmlspecialchars($_POST['tool_type_capacity'] ?? ''); ?>"
                                               required>
                                        <small class="text-muted">Required - This makes each tool unique even with same make/model</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Assigned Date</label>
                                        <input type="date" name="assigned_date" class="form-control" 
                                               value="<?php echo $_POST['assigned_date'] ?? date('Y-m-d'); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Tool Make/Model <span class="text-danger">*</span></label>
                                <input type="text" name="tool_make_model" class="form-control" required
                                       placeholder="e.g., M4, Bosch GSB 550, Fluke 117"
                                       value="<?php echo htmlspecialchars($_POST['tool_make_model'] ?? ''); ?>">
                                <small class="text-muted">Can be same as other tools if capacity is different</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" name="tool_quantity" class="form-control" value="<?php echo $_POST['tool_quantity'] ?? 1; ?>" min="1">
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Condition/Status</label>
                                        <select name="status" class="form-select">
                                            <option value="assigned" <?php echo (($_POST['status'] ?? 'assigned') == 'assigned') ? 'selected' : ''; ?>>Good</option>
                                            <option value="damaged" <?php echo (($_POST['status'] ?? '') == 'damaged') ? 'selected' : ''; ?>>Damaged</option>
                                            <option value="repair" <?php echo (($_POST['status'] ?? '') == 'repair') ? 'selected' : ''; ?>>Needs Repair</option>
                                            <option value="lost" <?php echo (($_POST['status'] ?? '') == 'lost') ? 'selected' : ''; ?>>Lost</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Remarks/Notes</label>
                                <textarea name="remarks" class="form-control" rows="3" 
                                          placeholder="Any special notes, serial number, condition, etc."><?php echo htmlspecialchars($_POST['remarks'] ?? ''); ?></textarea>
                            </div>
                            
                            <button type="submit" name="assign_tool" class="btn btn-primary w-100">
                                <i class="fas fa-check"></i> Assign Tool to Engineer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="tab-pane fade" id="history" role="tabpanel">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-history"></i> Tool Assignment History</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        $history_sql = "SELECT et.*, t.tool_name,
                                        CASE 
                                            WHEN et.is_current = 1 THEN 'Currently With Engineer'
                                            ELSE 'Previously With Engineer'
                                        END as history_status
                                        FROM engineer_tools et
                                        JOIN tools t ON et.tool_id = t.id
                                        WHERE et.engineer_id = '$engineer_id'
                                        ORDER BY et.assigned_date DESC";
                        $history_result = $conn->query($history_sql);
                        
                        if ($history_result && $history_result->num_rows > 0) {
                            echo '<h5>All Tool Assignments for This Engineer</h5>';
                            echo '<div class="table-responsive">';
                            echo '<table class="table table-hover" id="historyTable">';
                            echo '<thead><tr>
                                    <th>Tool Name</th>
                                    <th>Make/Model</th>
                                    <th>Size/Capacity</th>
                                    <th>Qty</th>
                                    <th>Assigned On</th>
                                    <th>Returned On</th>
                                    <th>Status</th>
                                    <th>History Status</th>
                                  </tr></thead>';
                            echo '<tbody>';
                            
                            while($history = $history_result->fetch_assoc()) {
                                $status_class = $history['status'] == 'assigned' ? 'success' : 
                                              ($history['status'] == 'returned' ? 'secondary' : 
                                              ($history['status'] == 'damaged' ? 'danger' : 'warning'));
                                
                                $history_status_class = $history['is_current'] == 1 ? 'success' : 'secondary';
                                
                                $display_make_model = cleanMakeModelForDisplay($history['tool_make_model']);
                                
                                echo "<tr>
                                        <td>{$history['tool_name']}</td>
                                        <td>" . htmlspecialchars($display_make_model) . "</td>
                                        <td>{$history['tool_type_capacity']}</td>
                                        <td>{$history['tool_quantity']}</td>
                                        <td>" . date('d M Y', strtotime($history['assigned_date'])) . "</td>
                                        <td>" . ($history['returned_date'] ? date('d M Y', strtotime($history['returned_date'])) : '-') . "</td>
                                        <td><span class='badge bg-{$status_class}'>{$history['status']}</span></td>
                                        <td><span class='badge bg-{$history_status_class}'>
                                            {$history['history_status']}</span></td>
                                      </tr>";
                            }
                            
                            echo '</tbody></table></div>';
                        } else {
                            echo '<div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No tool assignment history found for this engineer
                                  </div>';
                        }
                        
                        echo '<hr><h5 class="mt-4">Detailed Activity Log</h5>';
                        
                        $tool_history_sql = "SELECT th.*, t.tool_name 
                                            FROM tool_history th
                                            JOIN tools t ON th.tool_id = t.id
                                            WHERE th.engineer_id = '$engineer_id'
                                            ORDER BY th.action_date DESC";
                        $tool_history_result = $conn->query($tool_history_sql);
                        
                        if ($tool_history_result && $tool_history_result->num_rows > 0) {
                            echo '<div class="table-responsive mt-3">';
                            echo '<table class="table table-sm table-bordered">';
                            echo '<thead><tr>
                                    <th>Date & Time</th>
                                    <th>Tool</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>Action By</th>
                                  </tr></thead>';
                            echo '<tbody>';
                            
                            while($log = $tool_history_result->fetch_assoc()) {
                                $action_class = $log['action_type'] == 'assign' ? 'success' : 
                                              ($log['action_type'] == 'return' ? 'warning' : 
                                              ($log['action_type'] == 'delete' ? 'danger' : 'info'));
                                
                                echo "<tr>
                                        <td>" . date('d M Y h:i A', strtotime($log['action_date'])) . "</td>
                                        <td>{$log['tool_name']}</td>
                                        <td><span class='badge bg-{$action_class}'>{$log['action_type']}</span></td>
                                        <td>{$log['details']}</td>
                                        <td>{$log['action_by']}</td>
                                      </tr>";
                            }
                            
                            echo '</tbody></table></div>';
                        } else {
                            echo '<div class="alert alert-warning mt-3">
                                    <i class="fas fa-exclamation-triangle"></i> No activity log found
                                  </div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Return Tool Modal (existing) -->
<div class="modal fade" id="returnToolModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Return Tool</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="assignment_id" id="return_assignment_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tool</label>
                        <input type="text" id="return_tool_display" class="form-control" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Returned To <span class="text-danger">*</span></label>
                        <select name="returned_to" class="form-select" required>
                            <option value="Store">Store</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Repair">Repair Department</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Return Reason</label>
                        <textarea name="return_reason" class="form-control" rows="3" 
                                  placeholder="Why is this tool being returned?"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="return_tool" class="btn btn-warning">Return Tool</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Assignment Modal (existing) -->
<div class="modal fade" id="editAssignmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Tool Assignment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="assignment_id" id="edit_assignment_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tool</label>
                        <input type="text" id="edit_tool_name_display" class="form-control" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tool Size/Capacity</label>
                        <input type="text" name="tool_type_capacity" id="edit_tool_type_capacity" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tool Make/Model <span class="text-danger">*</span></label>
                        <input type="text" name="tool_make_model" id="edit_tool_make_model" class="form-control" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Quantity</label>
                                <input type="number" name="tool_quantity" id="edit_tool_quantity" class="form-control" min="1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Assigned Date</label>
                                <input type="date" name="assigned_date" id="edit_assigned_date" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Remarks/Notes</label>
                        <textarea name="remarks" id="edit_remarks" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_assigned_tool" class="btn btn-primary">Update Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- NEW: Delete Assignment Modal -->
<div class="modal fade" id="deleteAssignmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Delete Tool Assignment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="assignment_id" id="delete_assignment_id">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle"></i> <strong>Warning:</strong> This action cannot be undone!
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tool to Delete</label>
                        <input type="text" id="delete_tool_display" class="form-control" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Delete Reason <span class="text-danger">*</span></label>
                        <textarea name="delete_reason" class="form-control" rows="3" 
                                  placeholder="Why are you deleting this assignment? (Required)"
                                  required></textarea>
                        <small class="text-muted">Please provide a reason for deletion for audit purposes.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_assignment" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Permanently
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.bootstrap) {
        const editAssignmentModal = new bootstrap.Modal(document.getElementById('editAssignmentModal'));
        const returnToolModal = new bootstrap.Modal(document.getElementById('returnToolModal'));
        const deleteAssignmentModal = new bootstrap.Modal(document.getElementById('deleteAssignmentModal'));
        
        // Edit assignment button
        document.querySelectorAll('.edit-assignment').forEach(button => {
            button.addEventListener('click', function() {
                const assignmentId = this.getAttribute('data-id');
                const toolTypeCapacity = this.getAttribute('data-tool_type_capacity');
                const toolMakeModel = this.getAttribute('data-tool_make_model');
                const toolQuantity = this.getAttribute('data-tool_quantity');
                const remarks = this.getAttribute('data-remarks');
                const assignedDate = this.getAttribute('data-assigned_date');
                const toolName = this.closest('tr').querySelector('td:first-child').textContent;
                
                document.getElementById('edit_assignment_id').value = assignmentId;
                document.getElementById('edit_tool_name_display').value = toolName;
                document.getElementById('edit_tool_type_capacity').value = toolTypeCapacity || '';
                document.getElementById('edit_tool_make_model').value = toolMakeModel || '';
                document.getElementById('edit_tool_quantity').value = toolQuantity || 1;
                document.getElementById('edit_remarks').value = remarks || '';
                document.getElementById('edit_assigned_date').value = assignedDate || '';
                
                editAssignmentModal.show();
            });
        });
        
        // Return tool button
        document.querySelectorAll('.return-tool').forEach(button => {
            button.addEventListener('click', function() {
                const assignmentId = this.getAttribute('data-id');
                const toolName = this.getAttribute('data-tool_name');
                const toolMakeModel = this.getAttribute('data-tool_make_model');
                
                document.getElementById('return_assignment_id').value = assignmentId;
                document.getElementById('return_tool_display').value = `${toolName} - ${toolMakeModel}`;
                
                returnToolModal.show();
            });
        });
        
        // NEW: Delete assignment button
        document.querySelectorAll('.delete-assignment').forEach(button => {
            button.addEventListener('click', function() {
                const assignmentId = this.getAttribute('data-id');
                const toolName = this.getAttribute('data-tool_name');
                const toolMakeModel = this.getAttribute('data-tool_make_model');
                const toolCapacity = this.getAttribute('data-tool_capacity');
                
                document.getElementById('delete_assignment_id').value = assignmentId;
                document.getElementById('delete_tool_display').value = `${toolName} - ${toolMakeModel} (${toolCapacity})`;
                
                deleteAssignmentModal.show();
            });
        });
        
        <?php if (isset($_POST['assign_tool']) || isset($_POST['return_tool']) || isset($_POST['update_assigned_tool']) || isset($_POST['delete_assignment'])): ?>
            const currentTab = document.getElementById('current-tab');
            if (currentTab) {
                currentTab.click();
                window.scrollTo(0, 0);
            }
        <?php endif; ?>
    }
});
</script>

<?php include 'includes/footer.php'; ?>
