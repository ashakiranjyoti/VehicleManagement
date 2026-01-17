<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
checkAuth();
include 'includes/header.php';

// Users fetch karne ke liye function
function getUsersFromDatabase($conn) {
    $users = [];
    $sql = "SELECT id, username, full_name FROM users ORDER BY username ASC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    return $users;
}

$users = getUsersFromDatabase($conn);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-user-tie"></i> Engineer Management
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEngineerModal">
            <i class="fas fa-plus"></i> Add New Engineer
        </button>
    </div>
</div>

<?php
// Add new engineer
if ($_POST && isset($_POST['add_engineer'])) {
    $eng_name = $conn->real_escape_string($_POST['eng_name']);
    $designation = $conn->real_escape_string($_POST['designation']);
    $mobile_number = $conn->real_escape_string($_POST['mobile_number']);
    $created_by = $_SESSION['username'] ?? 'admin';
    
    // Check if engineer already exists (name only - double entry rokne ke liye)
    $check_sql = "SELECT id FROM engineers WHERE eng_name = '$eng_name'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        echo '<div class="alert alert-danger">Engineer with this name already exists!</div>';
    } else {
        // Mobile number bhi check karein
        $check_mobile_sql = "SELECT id FROM engineers WHERE mobile_number = '$mobile_number'";
        $check_mobile_result = $conn->query($check_mobile_sql);
        
        if ($check_mobile_result->num_rows > 0) {
            echo '<div class="alert alert-danger">Engineer with this mobile number already exists!</div>';
        } else {
            $sql = "INSERT INTO engineers (eng_name, designation, mobile_number, created_by) 
                    VALUES ('$eng_name', '$designation', '$mobile_number', '$created_by')";
            
            if ($conn->query($sql) === TRUE) {
                echo '<div class="alert alert-success">Engineer added successfully!</div>';
            } else {
                echo '<div class="alert alert-danger">Error adding engineer: ' . $conn->error . '</div>';
            }
        }
    }
}

// Update engineer
if ($_POST && isset($_POST['update_engineer'])) {
    $engineer_id = $conn->real_escape_string($_POST['engineer_id']);
    $eng_name = $conn->real_escape_string($_POST['eng_name']);
    $designation = $conn->real_escape_string($_POST['designation']);
    $mobile_number = $conn->real_escape_string($_POST['mobile_number']);
    
    // Check if engineer name already exists (excluding current engineer)
    $check_name_sql = "SELECT id FROM engineers WHERE eng_name = '$eng_name' AND id != '$engineer_id'";
    $check_name_result = $conn->query($check_name_sql);
    
    if ($check_name_result->num_rows > 0) {
        echo '<div class="alert alert-danger">Engineer with this name already exists!</div>';
    } else {
        // Mobile number bhi check karein (excluding current engineer)
        $check_mobile_sql = "SELECT id FROM engineers WHERE mobile_number = '$mobile_number' AND id != '$engineer_id'";
        $check_mobile_result = $conn->query($check_mobile_sql);
        
        if ($check_mobile_result->num_rows > 0) {
            echo '<div class="alert alert-danger">Engineer with this mobile number already exists!</div>';
        } else {
            $sql = "UPDATE engineers SET 
                    eng_name = '$eng_name',
                    designation = '$designation',
                    mobile_number = '$mobile_number',
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = '$engineer_id'";
            
            if ($conn->query($sql) === TRUE) {
                echo '<div class="alert alert-success">Engineer updated successfully!</div>';
            } else {
                echo '<div class="alert alert-danger">Error updating engineer: ' . $conn->error . '</div>';
            }
        }
    }
}

// Delete engineer
if (isset($_GET['delete_id'])) {
    $delete_id = $conn->real_escape_string($_GET['delete_id']);
    
    // First check if engineer has assigned tools
    $check_tools_sql = "SELECT id FROM engineer_tools WHERE engineer_id = '$delete_id' AND is_current = 1";
    $check_tools_result = $conn->query($check_tools_sql);
    
    if ($check_tools_result->num_rows > 0) {
        echo '<div class="alert alert-danger">Cannot delete engineer. Engineer has tools assigned. Please remove tools first.</div>';
    } else {
        $sql = "DELETE FROM engineers WHERE id = $delete_id";
        if ($conn->query($sql)) {
            echo '<div class="alert alert-success">Engineer deleted successfully!</div>';
        } else {
            echo '<div class="alert alert-danger">Error deleting engineer: ' . $conn->error . '</div>';
        }
    }
}
?>

<div class="card fade-in">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="engineersTable">
                <thead>
                    <tr>
                        <th>SR No</th>
                        <th>Engineer Name</th>
                        <th>Designation</th>
                        <th>Mobile Number</th>
                        <th>Created By</th>
                        <th>Created Date</th>
                        <th>Current Tools</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT e.*, 
                           COUNT(CASE WHEN et.is_current = 1 THEN et.id END) as current_tools
                           FROM engineers e
                           LEFT JOIN engineer_tools et ON e.id = et.engineer_id
                           GROUP BY e.id
                           ORDER BY e.eng_name ASC";
                    $result = $conn->query($sql);
                    
                    if ($result && $result->num_rows > 0) {
                        $serial = 1;
                        while($row = $result->fetch_assoc()) {
                            $engineer_id = $row['id'];
                            echo "<tr>
                                    <td>{$serial}</td>
                                    <td><strong>{$row['eng_name']}</strong></td>
                                    <td>{$row['designation']}</td>
                                    <td>{$row['mobile_number']}</td>
                                    <td>{$row['created_by']}</td>
                                    <td>" . date('d M Y', strtotime($row['created_at'])) . "</td>
                                    <td>
                                        <span class='badge " . ($row['current_tools'] > 0 ? 'bg-success' : 'bg-secondary') . "'>
                                            {$row['current_tools']} tool(s)
                                        </span>
                                    </td>
                                    <td>
                                        <div class='btn-group btn-group-sm' role='group'>
                                            <button class='btn btn-outline-warning edit-engineer' 
                                                    data-engineer_id='{$engineer_id}'
                                                    data-eng_name='" . htmlspecialchars($row['eng_name']) . "'
                                                    data-designation='" . htmlspecialchars($row['designation']) . "'
                                                    data-mobile_number='{$row['mobile_number']}'>
                                                <i class='fas fa-edit' title='Edit Engineer'></i>
                                            </button>
                                            <a href='?delete_id={$engineer_id}' class='btn btn-outline-danger' 
                                               onclick='return confirm(\"Are you sure you want to delete engineer: {$row['eng_name']}?\")'
                                               title='Delete Engineer'>
                                                <i class='fas fa-trash'></i>
                                            </a>
                                            <a href='assign_tools.php?engineer_id={$engineer_id}' class='btn btn-outline-primary' 
                                               title='Assign/Manage Tools'>
                                                <i class='fas fa-tools'></i> Tools
                                            </a>
                                            <a href='view_engineer_tools.php?engineer_id={$engineer_id}' class='btn btn-outline-info' 
                                               title='View Tools History'>
                                                <i class='fas fa-history'></i>
                                            </a>
                                        </div>
                                    </td>
                                  </tr>";
                            $serial++;
                        }
                    } else {
                        echo "<tr>
                                <td colspan='8' class='text-center py-4'>
                                    <i class='fas fa-user-tie fa-2x text-muted mb-3'></i><br>
                                    No engineers found. Add your first engineer!
                                </td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Engineer Modal -->
<div class="modal fade" id="addEngineerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Engineer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="addEngineerForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Engineer Name <span class="text-danger">*</span></label>
                        <select name="eng_name" class="form-control" required id="add_eng_name">
                            <option value="">-- Select Engineer --</option>
                            <?php 
                            // Sirf wo users show karein jo engineers table mein nahi hain
                            $existing_engineers = [];
                            $existing_sql = "SELECT eng_name FROM engineers";
                            $existing_result = $conn->query($existing_sql);
                            if ($existing_result && $existing_result->num_rows > 0) {
                                while($row = $existing_result->fetch_assoc()) {
                                    $existing_engineers[] = $row['eng_name'];
                                }
                            }
                            
                            foreach($users as $user): 
                                // Agar user already engineer hai toh dropdown mein na show karein
                                if (in_array($user['username'], $existing_engineers)) {
                                    continue;
                                }
                            ?>
                                <option value="<?php echo htmlspecialchars($user['username']); ?>">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                    <?php if(!empty($user['full_name'])): ?>
                                        (<?php echo htmlspecialchars($user['full_name']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Select engineer name from dropdown</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Designation <span class="text-danger">*</span></label>
                        <input type="text" name="designation" class="form-control" required 
                               placeholder="e.g., Senior Engineer, Technician, Supervisor">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                        <input type="text" name="mobile_number" class="form-control" required 
                               placeholder="e.g., 9876543210" pattern="[0-9]{10}" maxlength="10">
                        <div class="form-text">Enter 10 digit mobile number</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_engineer" class="btn btn-primary">Add Engineer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Engineer Modal -->
<div class="modal fade" id="editEngineerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Engineer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editEngineerForm">
                <input type="hidden" name="engineer_id" id="edit_engineer_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Engineer Name <span class="text-danger">*</span></label>
                        <select name="eng_name" id="edit_eng_name" class="form-control" required>
                            <option value="">-- Select Engineer --</option>
                            <?php foreach($users as $user): ?>
                                <option value="<?php echo htmlspecialchars($user['username']); ?>">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                    <?php if(!empty($user['full_name'])): ?>
                                        (<?php echo htmlspecialchars($user['full_name']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Designation <span class="text-danger">*</span></label>
                        <input type="text" name="designation" id="edit_designation" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                        <input type="text" name="mobile_number" id="edit_mobile_number" class="form-control" required 
                               pattern="[0-9]{10}" maxlength="10">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_engineer" class="btn btn-primary">Update Engineer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.bootstrap) {
        const editModal = new bootstrap.Modal(document.getElementById('editEngineerModal'));
        
        document.querySelectorAll('.edit-engineer').forEach(button => {
            button.addEventListener('click', function() {
                const engineerId = this.getAttribute('data-engineer_id');
                const engName = this.getAttribute('data-eng_name');
                const designation = this.getAttribute('data-designation');
                const mobileNumber = this.getAttribute('data-mobile_number');
                
                // Set form values
                document.getElementById('edit_engineer_id').value = engineerId;
                
                // Select the correct option in dropdown
                const engNameSelect = document.getElementById('edit_eng_name');
                for (let i = 0; i < engNameSelect.options.length; i++) {
                    if (engNameSelect.options[i].value === engName) {
                        engNameSelect.selectedIndex = i;
                        break;
                    }
                }
                
                document.getElementById('edit_designation').value = designation;
                document.getElementById('edit_mobile_number').value = mobileNumber;
                
                editModal.show();
            });
        });
        
        // Delete confirmation
        document.querySelectorAll('a[href*="delete_id"]').forEach(link => {
            link.addEventListener('click', function(e) {
                const engineerName = this.closest('tr').querySelector('td:nth-child(2)').textContent;
                const confirmDelete = confirm(`Are you sure you want to delete engineer: ${engineerName}?`);
                if (!confirmDelete) {
                    e.preventDefault();
                }
            });
        });
        
        // Refresh dropdown options after modal close (Add modal)
        const addModal = document.getElementById('addEngineerModal');
        if (addModal) {
            addModal.addEventListener('hidden.bs.modal', function() {
                // Form reset karein
                document.getElementById('addEngineerForm').reset();
            });
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>