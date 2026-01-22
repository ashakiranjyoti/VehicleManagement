<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
checkAuth();
include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-search"></i> Tool Tracking
    </h1>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-search"></i> Find Tool Location</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Select Tool (Dropdown)</label>
                        <select name="tool_id" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Select Tool --</option>
                            <?php
                            $tools_sql = "SELECT * FROM tools ORDER BY tool_name";
                            $tools_result = $conn->query($tools_sql);
                            while($tool = $tools_result->fetch_assoc()) {
                                $selected = isset($_GET['tool_id']) && $_GET['tool_id'] == $tool['id'] ? 'selected' : '';
                                echo "<option value='{$tool['id']}' $selected>{$tool['tool_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">OR Search by Make/Model</label>
                        <input type="text" name="make_model" class="form-control" 
                               placeholder="Enter make/model"
                               value="<?php echo $_GET['make_model'] ?? ''; ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid gap-2 d-md-flex">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="tool_tracking.php" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
                
                <hr>
                
                <?php
                if (isset($_GET['tool_id']) || isset($_GET['make_model'])) {
                    $tool_id = $_GET['tool_id'] ?? '';
                    $make_model = $_GET['make_model'] ?? '';
                    
                    $sql = "SELECT et.*, t.tool_name, e.eng_name, e.designation, e.mobile_number
                            FROM engineer_tools et
                            JOIN tools t ON et.tool_id = t.id
                            JOIN engineers e ON et.engineer_id = e.id
                            WHERE et.is_current = 1 ";
                    
                    if (!empty($tool_id)) {
                        $tool_id = $conn->real_escape_string($tool_id);
                        $sql .= " AND t.id = '$tool_id'";
                    }
                    
                    if (!empty($make_model)) {
                        $make_model = $conn->real_escape_string($make_model);
                        $sql .= " AND et.tool_make_model LIKE '%$make_model%'";
                    }
                    
                    $sql .= " ORDER BY e.eng_name";
                    
                    $result = $conn->query($sql);
                    
                    if ($result && $result->num_rows > 0) {
                        echo '<h5 class="mt-3">Search Results:</h5>';
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-hover">';
                        echo '<thead><tr>
                                <th>Tool Name</th>
                                <th>Make/Model</th>
                                <th>Size/Capacity</th>
                                <th>Engineer Name</th>
                                <th>Designation</th>
                                <th>Mobile</th>
                                <th>Quantity</th>
                                <th>Assigned Date</th>
                                <th>Status</th>
                              </tr></thead>';
                        echo '<tbody>';
                        
                        while($row = $result->fetch_assoc()) {
                            $status_class = $row['status'] == 'assigned' ? 'success' : 
                                          ($row['status'] == 'damaged' ? 'danger' : 'warning');
                            
                            echo "<tr>
                                    <td>{$row['tool_name']}</td>
                                    <td>{$row['tool_make_model']}</td>
                                    <td>{$row['tool_type_capacity']}</td>
                                    <td><a href='assign_tools.php?engineer_id={$row['engineer_id']}'>{$row['eng_name']}</a></td>
                                    <td>{$row['designation']}</td>
                                    <td>{$row['mobile_number']}</td>
                                    <td>{$row['tool_quantity']}</td>
                                    <td>" . date('d M Y', strtotime($row['assigned_date'])) . "</td>
                                    <td><span class='badge bg-{$status_class}'>{$row['status']}</span></td>
                                  </tr>";
                        }
                        
                        echo '</tbody></table></div>';
                        
                        // Summary
                        echo "<div class='alert alert-info mt-3'>
                                <i class='fas fa-info-circle'></i> Found {$result->num_rows} active assignment(s) 
                              </div>";
                    } else {
                        echo '<div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle"></i> No active assignments found for the search criteria
                              </div>';
                    }
                }
                ?>
            </div>
        </div>
        
        <!-- Currently Assigned Tools Overview -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list"></i> All Currently Assigned Tools</h5>
            </div>
            <div class="card-body">
                <?php
                $all_sql = "SELECT t.tool_name, 
                           GROUP_CONCAT(DISTINCT e.eng_name ORDER BY e.eng_name SEPARATOR ', ') as assigned_to,
                           COUNT(DISTINCT et.engineer_id) as engineer_count,
                           SUM(et.tool_quantity) as total_qty
                           FROM engineer_tools et
                           JOIN tools t ON et.tool_id = t.id
                           JOIN engineers e ON et.engineer_id = e.id
                           WHERE et.is_current = 1
                           GROUP BY t.id
                           ORDER BY t.tool_name";
                
                $all_result = $conn->query($all_sql);
                
                if ($all_result && $all_result->num_rows > 0) {
                    echo '<div class="table-responsive">';
                    echo '<table class="table table-sm table-bordered">';
                    echo '<thead><tr>
                            <th>Tool Name</th>
                            <th>Assigned To (Engineers)</th>
                            <th>No. of Engineers</th>
                            <th>Total Quantity</th>
                            <th>Action</th>
                          </tr></thead>';
                    echo '<tbody>';
                    
                    while($tool = $all_result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$tool['tool_name']}</td>
                                <td>{$tool['assigned_to']}</td>
                                <td>{$tool['engineer_count']}</td>
                                <td>{$tool['total_qty']}</td>
                                <td>
                                    <a href='tool_tracking.php?tool_name={$tool['tool_name']}' 
                                       class='btn btn-sm btn-outline-info'>
                                        <i class='fas fa-eye'></i> View Details
                                    </a>
                                </td>
                              </tr>";
                    }
                    
                    echo '</tbody></table></div>';
                } else {
                    echo '<div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No tools currently assigned to any engineer
                          </div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
