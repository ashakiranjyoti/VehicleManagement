<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
checkAuth();

// Add new tool
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tool'])) {
    $tool_name = $conn->real_escape_string($_POST['tool_name']);
    $description = $conn->real_escape_string($_POST['description'] ?? '');
    $created_by = $_SESSION['username'] ?? 'admin';
    
    // Check if tool already exists
    $check_sql = "SELECT id FROM tools WHERE tool_name = '$tool_name'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        $_SESSION['error'] = "Tool name already exists!";
    } else {
        $sql = "INSERT INTO tools (tool_name, description, created_by) 
                VALUES ('$tool_name', '$description', '$created_by')";
        
        if ($conn->query($sql) === TRUE) {
            $_SESSION['success'] = "Tool added successfully!";
        } else {
            $_SESSION['error'] = "Error adding tool: " . $conn->error;
        }
    }
    header("Location: manage_tools.php");
    exit();
}

// Update tool
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_tool'])) {
    $tool_id = $conn->real_escape_string($_POST['tool_id']);
    $tool_name = $conn->real_escape_string($_POST['tool_name']);
    $description = $conn->real_escape_string($_POST['description'] ?? '');
    $updated_by = $_SESSION['username'] ?? 'admin';
    
    // Check if tool name already exists (excluding current tool)
    $check_sql = "SELECT id FROM tools WHERE tool_name = '$tool_name' AND id != '$tool_id'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        $_SESSION['error'] = "Tool name already exists!";
    } else {
        $sql = "UPDATE tools SET 
                tool_name = '$tool_name',
                description = '$description',
                updated_by = '$updated_by',
                updated_at = CURRENT_TIMESTAMP
                WHERE id = '$tool_id'";
        
        if ($conn->query($sql) === TRUE) {
            $_SESSION['success'] = "Tool updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating tool: " . $conn->error;
        }
    }
    header("Location: manage_tools.php");
    exit();
}

// Delete tool
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // First check if tool is used in any service
    $check_usage_sql = "SELECT id FROM services WHERE FIND_IN_SET('$delete_id', tools_used)";
    $check_usage_result = $conn->query($check_usage_sql);
    
    if ($check_usage_result->num_rows > 0) {
        $_SESSION['error'] = "Cannot delete tool. It is being used in services.";
    } else {
        $sql = "DELETE FROM tools WHERE id = $delete_id";
        if ($conn->query($sql)) {
            $_SESSION['success'] = "Tool deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting tool: " . $conn->error;
        }
    }
    header("Location: manage_tools.php");
    exit();
}

// Display messages
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            ' . $_SESSION['success'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            ' . $_SESSION['error'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['error']);
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-tools"></i> Tool Management
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addToolModal">
            <i class="fas fa-plus"></i> Add New Tool
        </button>
    </div>
</div>

<div class="card fade-in">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="toolsTable">
                <thead>
                    <tr>
                        <th>Sr No</th>
                        <th>Tool Name</th>
                        <th>Description</th>
                        <th>Created By</th>
                        <th>Created Date</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT * FROM tools ORDER BY tool_name ASC";
                    $result = $conn->query($sql);
                    
                    if ($result && $result->num_rows > 0) {
                        $sr = 1;
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>
                                    <td>{$sr}</td>
                                    <td><strong>{$row['tool_name']}</strong></td>
                                    <td>" . ($row['description'] ? nl2br(htmlspecialchars($row['description'])) : '<span class="text-muted">No description</span>') . "</td>
                                    <td>{$row['created_by']}</td>
                                    <td>" . date('d M Y', strtotime($row['created_at'])) . "</td>
                                    <td>" . ($row['updated_at'] ? date('d M Y', strtotime($row['updated_at'])) : '-') . "</td>
                                    <td>
                                        <button class='btn btn-sm btn-outline-warning edit-tool' 
                                                data-id='{$row['id']}'
                                                data-tool_name='" . htmlspecialchars($row['tool_name'], ENT_QUOTES) . "'
                                                data-description='" . htmlspecialchars($row['description'], ENT_QUOTES) . "'>
                                            <i class='fas fa-edit'></i>
                                        </button>
                                        <a href='?delete_id={$row['id']}' class='btn btn-sm btn-outline-danger' 
                                           onclick='return confirm(\"Are you sure you want to delete tool: {$row['tool_name']}?\")'>
                                            <i class='fas fa-trash'></i>
                                        </a>
                                    </td>
                                  </tr>";
                            $sr++; // Yeh line add karein
                        }
                    } else {
                        echo "<tr>
                                <td colspan='7' class='text-center py-4'>
                                    <i class='fas fa-tools fa-2x text-muted mb-3'></i><br>
                                    No tools found. Add your first tool!
                                </td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Tool Modal -->
<div class="modal fade" id="addToolModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Tool</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="addToolForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tool Name <span class="text-danger">*</span></label>
                        <input type="text" name="tool_name" class="form-control" required 
                               placeholder="e.g., Screwdriver Set, Wrench, Diagnostic Tool">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4" 
                                  placeholder="Describe the tool, its specifications, uses..."></textarea>
                        <div class="form-text">Optional: Add details about the tool</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_tool" class="btn btn-primary">Add Tool</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Tool Modal -->
<div class="modal fade" id="editToolModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Tool</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="editToolForm">
                <input type="hidden" name="tool_id" id="edit_tool_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tool Name <span class="text-danger">*</span></label>
                        <input type="text" name="tool_name" id="edit_tool_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_tool" class="btn btn-primary">Update Tool</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Edit tool modal with better event handling
document.addEventListener('DOMContentLoaded', function() {
    // Initialize edit modal
    const editModal = new bootstrap.Modal(document.getElementById('editToolModal'));
    
    // Handle edit button clicks
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-tool')) {
            const button = e.target.closest('.edit-tool');
            const toolId = button.getAttribute('data-id');
            const toolName = button.getAttribute('data-tool_name');
            const description = button.getAttribute('data-description');
            
            document.getElementById('edit_tool_id').value = toolId;
            document.getElementById('edit_tool_name').value = toolName;
            document.getElementById('edit_description').value = description;
            
            editModal.show();
        }
    });
    
    // Reset add form when modal closes
    const addModal = document.getElementById('addToolModal');
    addModal.addEventListener('hidden.bs.modal', function () {
        document.getElementById('addToolForm').reset();
    });
    
    // Reset edit form when modal closes
    const editModalElement = document.getElementById('editToolModal');
    editModalElement.addEventListener('hidden.bs.modal', function () {
        document.getElementById('editToolForm').reset();
    });
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Delete confirmation with tool name
    document.querySelectorAll('a[href*="delete_id"]').forEach(link => {
        link.addEventListener('click', function(e) {
            const toolName = this.closest('tr').querySelector('td:nth-child(2) strong').textContent;
            const confirmDelete = confirm(`Are you sure you want to delete tool: ${toolName}?`);
            if (!confirmDelete) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php 
// Yeh line aapke file ke end mein honi chahiye
include 'includes/footer.php'; 
?>