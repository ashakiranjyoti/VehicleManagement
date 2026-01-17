<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
checkAuth();
include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-chart-pie"></i> Tool Reports
    </h1>
</div>

<div class="row">
    <!-- Summary Cards -->
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h6 class="card-title">Total Tools Assigned</h6>
                <?php
                $total_sql = "SELECT SUM(tool_quantity) as total FROM engineer_tools WHERE is_current = 1";
                $total_result = $conn->query($total_sql);
                $total = $total_result->fetch_assoc();
                echo '<h3>' . ($total['total'] ?? 0) . '</h3>';
                ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h6 class="card-title">Active Engineers</h6>
                <?php
                $eng_sql = "SELECT COUNT(DISTINCT engineer_id) as total FROM engineer_tools WHERE is_current = 1";
                $eng_result = $conn->query($eng_sql);
                $eng = $eng_result->fetch_assoc();
                echo '<h3>' . ($eng['total'] ?? 0) . '</h3>';
                ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h6 class="card-title">Tools Returned</h6>
                <?php
                $returned_sql = "SELECT COUNT(*) as total FROM engineer_tools WHERE status = 'returned'";
                $returned_result = $conn->query($returned_sql);
                $returned = $returned_result->fetch_assoc();
                echo '<h3>' . ($returned['total'] ?? 0) . '</h3>';
                ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <h6 class="card-title">Damaged/Lost</h6>
                <?php
                $damaged_sql = "SELECT COUNT(*) as total FROM engineer_tools WHERE status IN ('damaged', 'lost')";
                $damaged_result = $conn->query($damaged_sql);
                $damaged = $damaged_result->fetch_assoc();
                echo '<h3>' . ($damaged['total'] ?? 0) . '</h3>';
                ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-users"></i> Engineers with Tools</h5>
            </div>
            <div class="card-body">
                <?php
                $top_sql = "SELECT e.id, e.eng_name, e.designation, 
                           COUNT(et.id) as tool_count, 
                           SUM(et.tool_quantity) as total_qty
                           FROM engineer_tools et
                           JOIN engineers e ON et.engineer_id = e.id
                           WHERE et.is_current = 1
                           GROUP BY e.id
                           ORDER BY total_qty DESC";
                $top_result = $conn->query($top_sql);
                
                if ($top_result && $top_result->num_rows > 0) {
                    echo '<div class="table-responsive">';
                    echo '<table class="table table-sm">';
                    echo '<thead><tr>
                            <th>Engineer</th>
                            <th>Designation</th>
                            <th>No. of Tools</th>
                            <th>Total Quantity</th>
                            <th>Action</th>
                          </tr></thead>';
                    echo '<tbody>';
                    
                    while($eng = $top_result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$eng['eng_name']}</td>
                                <td>{$eng['designation']}</td>
                                <td>{$eng['tool_count']}</td>
                                <td>{$eng['total_qty']}</td>
                                <td>
                                    <a href='assign_tools.php?engineer_id={$eng['id']}' 
                                       class='btn btn-sm btn-outline-info'>
                                        <i class='fas fa-eye'></i> View Tools
                                    </a>
                                </td>
                              </tr>";
                    }
                    
                    echo '</tbody></table></div>';
                } else {
                    echo '<div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No tools currently assigned to engineers
                          </div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-tools"></i> Most Common Tools</h5>
            </div>
            <div class="card-body">
                <?php
                $common_sql = "SELECT t.tool_name,
                               COUNT(DISTINCT et.engineer_id) as assigned_to_engineers,
                               SUM(CASE WHEN et.is_current = 1 THEN et.tool_quantity ELSE 0 END) as current_qty
                               FROM tools t
                               LEFT JOIN engineer_tools et ON t.id = et.tool_id
                               GROUP BY t.id
                               HAVING current_qty > 0
                               ORDER BY current_qty DESC
                               LIMIT 10";
                
                $common_result = $conn->query($common_sql);
                
                if ($common_result && $common_result->num_rows > 0) {
                    echo '<div class="table-responsive">';
                    echo '<table class="table table-sm">';
                    echo '<thead><tr>
                            <th>Tool Name</th>
                            <th>Currently With</th>
                            <th>Current Qty</th>
                          </tr></thead>';
                    echo '<tbody>';
                    
                    while($tool = $common_result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$tool['tool_name']}</td>
                                <td>{$tool['assigned_to_engineers']} engineer(s)</td>
                                <td>{$tool['current_qty']}</td>
                              </tr>";
                    }
                    
                    echo '</tbody></table></div>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history"></i> Recent Activities</h5>
            </div>
            <div class="card-body">
                <?php
                $activity_sql = "SELECT th.*, t.tool_name, e.eng_name
                                FROM tool_history th
                                JOIN tools t ON th.tool_id = t.id
                                LEFT JOIN engineers e ON th.engineer_id = e.id
                                ORDER BY th.action_date DESC
                                LIMIT 10";
                $activity_result = $conn->query($activity_sql);
                
                if ($activity_result && $activity_result->num_rows > 0) {
                    echo '<div class="table-responsive">';
                    echo '<table class="table table-sm">';
                    echo '<thead><tr>
                            <th>Date/Time</th>
                            <th>Tool</th>
                            <th>Engineer</th>
                            <th>Action</th>
                            <th>By</th>
                          </tr></thead>';
                    echo '<tbody>';
                    
                    while($act = $activity_result->fetch_assoc()) {
                        $action_color = $act['action_type'] == 'assign' ? 'text-success' : 
                                      ($act['action_type'] == 'return' ? 'text-warning' : 'text-info');
                        
                        echo "<tr>
                                <td>" . date('d M, h:i A', strtotime($act['action_date'])) . "</td>
                                <td>{$act['tool_name']}</td>
                                <td>{$act['eng_name']}</td>
                                <td><span class='{$action_color}'><strong>{$act['action_type']}</strong></span></td>
                                <td>{$act['action_by']}</td>
                              </tr>";
                    }
                    
                    echo '</tbody></table></div>';
                } else {
                    echo '<div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No recent activities found
                          </div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>