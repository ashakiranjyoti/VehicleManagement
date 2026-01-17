<?php
include '../config/database.php';
checkAuth();
include '../includes/header.php';

if (!isset($_GET['id'])) {
    header("Location: view_services.php");
    exit();
}

$service_id = intval($_GET['id']);

// Fetch service details with owner information
$sql = "SELECT s.*, v.make_model, v.reg_number, v.vehicle_type, v.owner_name as vehicle_owner,
               u.full_name as owner_full_name 
        FROM services s 
        JOIN vehicles v ON s.vehicle_id = v.id 
        LEFT JOIN users u ON v.owner_name = u.username
        WHERE s.id = $service_id";
$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    echo "<div class='alert alert-danger'>Service record not found!</div>";
    include '../includes/footer.php';
    exit();
}

$service = $result->fetch_assoc();
$bill_files = $service['bill_file'] ? explode(',', $service['bill_file']) : [];

if ($_POST) {
    $vehicle_id = $conn->real_escape_string($_POST['vehicle_id']);
    $service_date = $conn->real_escape_string($_POST['service_date']);
    $service_time = $conn->real_escape_string($_POST['service_time']);
    $service_type = $conn->real_escape_string($_POST['service_type']);
    $running_km = $conn->real_escape_string($_POST['running_km'] ?? '');
    $service_center_name = $conn->real_escape_string($_POST['service_center_name'] ?? '');
    $service_center_place = $conn->real_escape_string($_POST['service_center_place'] ?? '');
    $service_done_by = $conn->real_escape_string($_POST['service_done_by'] ?? '');
    $cost = $conn->real_escape_string($_POST['cost']);
    $description = $conn->real_escape_string($_POST['description'] ?? '');
    $updated_by = $_SESSION['username'] ?? 'admin';
    
    // Handle file uploads
    $new_bill_files = [];
    $upload_errors = [];
    
    // Keep existing files
    if (!empty($service['bill_file'])) {
        $new_bill_files = $bill_files;
    }
    
    // Add new files
    if (!empty($_FILES['bill_files']['name'][0])) {
        $target_dir = "../uploads/";
        
        for ($i = 0; $i < count($_FILES['bill_files']['name']); $i++) {
            $file_name = $_FILES['bill_files']['name'][$i];
            $file_tmp = $_FILES['bill_files']['tmp_name'][$i];
            $file_size = $_FILES['bill_files']['size'][$i];
            $file_error = $_FILES['bill_files']['error'][$i];
            
            if ($file_error === UPLOAD_ERR_OK) {
                $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    if ($file_size <= 5 * 1024 * 1024) {
                        $unique_name = time() . '_' . uniqid() . '_' . ($i + 1) . '.' . $file_extension;
                        $target_file = $target_dir . $unique_name;
                        
                        if (move_uploaded_file($file_tmp, $target_file)) {
                            $new_bill_files[] = $unique_name;
                        } else {
                            $upload_errors[] = "Failed to upload: " . $file_name;
                        }
                    } else {
                        $upload_errors[] = "File too large (max 5MB): " . $file_name;
                    }
                } else {
                    $upload_errors[] = "Invalid file type: " . $file_name;
                }
            }
        }
    }
    
    // Handle file removal
    if (isset($_POST['remove_files'])) {
        $files_to_remove = $_POST['remove_files'];
        foreach ($files_to_remove as $file_index) {
            if (isset($new_bill_files[$file_index])) {
                $file_to_delete = $new_bill_files[$file_index];
                if (file_exists("../uploads/" . $file_to_delete)) {
                    unlink("../uploads/" . $file_to_delete);
                }
                unset($new_bill_files[$file_index]);
            }
        }
        $new_bill_files = array_values($new_bill_files); // Reindex array
    }
    
    $bill_files_str = !empty($new_bill_files) ? implode(',', $new_bill_files) : '';
    
    // Check if updated_by column exists, if not use created_by
    $check_column_sql = "SHOW COLUMNS FROM services LIKE 'updated_by'";
    $column_result = $conn->query($check_column_sql);
    $has_updated_by = $column_result->num_rows > 0;
    
    if ($has_updated_by) {
        $update_sql = "UPDATE services SET 
                      vehicle_id = '$vehicle_id',
                      service_date = '$service_date',
                      service_time = '$service_time',
                      service_type = '$service_type',
                      running_km = " . ($running_km ? "'$running_km'" : "NULL") . ",
                      service_center_name = " . ($service_center_name ? "'$service_center_name'" : "NULL") . ",
                      service_center_place = " . ($service_center_place ? "'$service_center_place'" : "NULL") . ",
                      service_done_by = " . ($service_done_by ? "'$service_done_by'" : "NULL") . ",
                      cost = '$cost',
                      bill_file = " . ($bill_files_str ? "'$bill_files_str'" : "NULL") . ",
                      description = " . ($description ? "'$description'" : "NULL") . ",
                      updated_by = '$updated_by',
                      updated_at = CURRENT_TIMESTAMP
                      WHERE id = $service_id";
    } else {
        // Fallback: update without updated_by and updated_at columns
        $update_sql = "UPDATE services SET 
                      vehicle_id = '$vehicle_id',
                      service_date = '$service_date',
                      service_time = '$service_time',
                      service_type = '$service_type',
                      running_km = " . ($running_km ? "'$running_km'" : "NULL") . ",
                      service_center_name = " . ($service_center_name ? "'$service_center_name'" : "NULL") . ",
                      service_center_place = " . ($service_center_place ? "'$service_center_place'" : "NULL") . ",
                      service_done_by = " . ($service_done_by ? "'$service_done_by'" : "NULL") . ",
                      cost = '$cost',
                      bill_file = " . ($bill_files_str ? "'$bill_files_str'" : "NULL") . ",
                      description = " . ($description ? "'$description'" : "NULL") . "
                      WHERE id = $service_id";
    }
    
    if ($conn->query($update_sql) === TRUE) {
        echo '<div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Service record updated successfully!';
        
        if (!empty($upload_errors)) {
            echo '<br><small class="text-warning">Some files could not be uploaded: ' . implode(', ', $upload_errors) . '</small>';
        }
        
        echo '</div>';
        
        // Refresh service data
        $result = $conn->query($sql);
        $service = $result->fetch_assoc();
        $bill_files = $service['bill_file'] ? explode(',', $service['bill_file']) : [];
    } else {
        echo '<div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> Error updating service: ' . $conn->error . '
              </div>';
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-edit"></i> Edit Service Record
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="view_services.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Services
        </a>
    </div>
</div>

<div class="card fade-in">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" class="row g-3" id="serviceForm">
            <!-- Vehicle Selection -->
            <div class="col-md-6">
                <label class="form-label">Select Vehicle <span class="text-danger">*</span></label>
                <select name="vehicle_id" class="form-select" required id="vehicleSelect" onchange="updateVehicleOwner()">
                    <option value="">Select Vehicle</option>
                    <?php
                    $vehicles_sql = "SELECT v.*, u.full_name as owner_full_name 
                                   FROM vehicles v 
                                   LEFT JOIN users u ON v.owner_name = u.username 
                                   ORDER BY v.make_model";
                    $vehicles_result = $conn->query($vehicles_sql);
                    
                    if ($vehicles_result && $vehicles_result->num_rows > 0) {
                        while($vehicle = $vehicles_result->fetch_assoc()) {
                            $selected = $service['vehicle_id'] == $vehicle['id'] ? 'selected' : '';
                            $icon = $vehicle['vehicle_type'] == 'bike' ? '🏍️' : '🚗';
                            $owner_display = $vehicle['owner_full_name'] ? $vehicle['owner_full_name'] : ($vehicle['owner_name'] ? $vehicle['owner_name'] : 'Not specified');
                            echo "<option value='{$vehicle['id']}' 
                                    data-owner='{$vehicle['owner_name']}' 
                                    data-owner-display='{$owner_display}'
                                    data-type='{$vehicle['vehicle_type']}' 
                                    $selected>
                                    {$icon} {$vehicle['make_model']} ({$vehicle['reg_number']})
                                  </option>";
                        }
                    }
                    ?>
                </select>
            </div>
            
            <!-- Vehicle Owner (Auto-filled from vehicle selection) -->
            <div class="col-md-6">
                <label class="form-label">Vehicle Owner</label>
                <input type="text" id="vehicleOwnerDisplay" class="form-control" 
                       readonly style="background-color: #f8f9fa;">
                <div class="form-text">Auto-filled from selected vehicle</div>
            </div>
            
            <!-- Service Type -->
            <div class="col-md-6">
                <label class="form-label">Service Type <span class="text-danger">*</span></label>
                <select name="service_type" class="form-select" required id="serviceType">
                    <option value="">Select Service Type</option>
                    <option value="Regular Service" <?php echo $service['service_type'] == 'Regular Service' ? 'selected' : ''; ?>>Regular Service</option>
                    <option value="Breakdown Service" <?php echo $service['service_type'] == 'Breakdown Service' ? 'selected' : ''; ?>>Breakdown Service</option>
                </select>
                <div class="form-text">Select the type of service performed</div>
            </div>
            
            <!-- Service Done By -->
            <div class="col-md-6">
                <label class="form-label">Service Done By <span class="text-danger">*</span></label>
                <select name="service_done_by" class="form-select" required>
                    <option value="">Select Service Provider</option>
                    <?php
                    // Fetch all users from users table
                    $users_sql = "SELECT id, username, full_name FROM users ORDER BY full_name";
                    $users_result = $conn->query($users_sql);
                    
                    if ($users_result && $users_result->num_rows > 0) {
                        while($user = $users_result->fetch_assoc()) {
                            $selected = ($service['service_done_by'] ?? '') == $user['username'] ? 'selected' : '';
                            $display_name = $user['full_name'] ? $user['full_name'] . ' (' . $user['username'] . ')' : $user['username'];
                            echo "<option value='{$user['username']}' $selected>{$display_name}</option>";
                        }
                    } else {
                        // If no users in database, show current logged in user
                        $current_user = $_SESSION['username'] ?? 'admin';
                        $selected = ($service['service_done_by'] ?? '') == $current_user ? 'selected' : '';
                        echo "<option value='{$current_user}' $selected>{$current_user} (Current User)</option>";
                    }
                    ?>
                </select>
                <div class="form-text">Who performed the service? (Mechanic/Service person)</div>
            </div>
            
            <!-- Running KM -->
            <div class="col-md-6">
                <label class="form-label">Running KM</label>
                <input type="number" name="running_km" class="form-control" 
                       value="<?php echo $service['running_km']; ?>" 
                       placeholder="e.g., 15000" min="0" step="1">
                <div class="form-text">Current kilometer reading at service time</div>
            </div>
            
            <!-- Service Date & Time -->
            <div class="col-md-3">
                <label class="form-label">Service Date <span class="text-danger">*</span></label>
                <input type="date" name="service_date" class="form-control" 
                       value="<?php echo $service['service_date']; ?>" required>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Service Time <span class="text-danger">*</span></label>
                <input type="time" name="service_time" class="form-control" 
                       value="<?php echo $service['service_time']; ?>" required>
            </div>
            
            <!-- Service Center Information -->
            <div class="col-md-6">
                <label class="form-label">Service Center Name</label>
                <input type="text" name="service_center_name" class="form-control" 
                       value="<?php echo htmlspecialchars($service['service_center_name'] ?? ''); ?>" 
                       placeholder="e.g., ABC Auto Service, XYZ Garage">
            </div>
            
            <div class="col-md-6">
                <label class="form-label">Service Center Place/Location</label>
                <input type="text" name="service_center_place" class="form-control" 
                       value="<?php echo htmlspecialchars($service['service_center_place'] ?? ''); ?>" 
                       placeholder="e.g., Delhi, Noida, Gurgaon, etc.">
            </div>
            
            <!-- Cost -->
            <div class="col-md-6">
                <label class="form-label">Service Cost (₹) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input type="number" step="0.01" min="0" name="cost" class="form-control" 
                           value="<?php echo $service['cost']; ?>" required
                           placeholder="0.00">
                </div>
                <div class="form-text">Total cost of service including parts and labor</div>
            </div>
            
            <!-- Updated By (Auto-filled with logged in user) -->
            <div class="col-md-6">
                <label class="form-label">Updated By</label>
                <input type="text" class="form-control" 
                       value="<?php echo $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'admin'; ?>" 
                       readonly style="background-color: #f8f9fa;">
                <div class="form-text">You (Auto-filled with your login)</div>
            </div>
            
            <!-- Description -->
            <div class="col-12">
                <label class="form-label">Service Description & Details</label>
                <textarea name="description" class="form-control" rows="5" 
                          placeholder="Describe the service in detail:
• What work was done?
• What parts were replaced?
• Any special notes or observations?
• Problems identified?
• Next service recommendations..."><?php echo htmlspecialchars($service['description'] ?? ''); ?></textarea>
                <div class="form-text">Be as detailed as possible for future reference</div>
            </div>
            
            <!-- Existing Bill Files -->
            <?php if (!empty($bill_files)): ?>
            <div class="col-12">
                <label class="form-label">Existing Bill Files</label>
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($bill_files as $index => $file): 
                                $file_path = "../uploads/" . $file;
                                $file_exists = file_exists($file_path);
                                $file_extension = pathinfo($file, PATHINFO_EXTENSION);
                                $file_icon = getFileIcon($file_extension);
                            ?>
                            <div class="col-md-3 mb-2">
                                <div class="card file-card" id="file-card-<?php echo $index; ?>">
                                    <div class="card-body text-center">
                                        <i class="fas <?php echo $file_icon; ?> fa-2x mb-2"></i>
                                        <p class="small mb-1">File <?php echo $index + 1; ?></p>
                                        <p class="small text-muted mb-2"><?php echo $file_extension; ?></p>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($file_exists): ?>
                                            <a href="../uploads/<?php echo $file; ?>" target="_blank" class="btn btn-outline-success">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-outline-danger remove-file" data-index="<?php echo $index; ?>">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        </div>
                                        <input type="hidden" name="remove_files[]" id="remove_<?php echo $index; ?>" value="">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- New File Upload -->
            <div class="col-12">
                <label class="form-label">Add New Bill Files</label>
                <input type="file" name="bill_files[]" class="form-control" 
                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" multiple id="billFiles">
                <div class="form-text">
                    <i class="fas fa-info-circle"></i> You can select multiple files (Max 10 files, 5MB each)
                    <br>Allowed: PDF, JPG, JPEG, PNG, DOC, DOCX
                </div>
                <div id="filePreview" class="mt-2"></div>
            </div>
            
            <!-- Form Buttons -->
            <div class="col-12">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="view_services.php" class="btn btn-secondary me-md-2">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Service Record
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Service Information -->
<div class="card mt-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="fas fa-info-circle"></i> Service Information
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th width="40%">Vehicle:</th>
                        <td><?php echo htmlspecialchars($service['make_model'] . ' (' . $service['reg_number'] . ')'); ?></td>
                    </tr>
                    <tr>
                        <th>Vehicle Type:</th>
                        <td><?php echo ucfirst($service['vehicle_type']); ?></td>
                    </tr>
                    <tr>
                        <th>Vehicle Owner:</th>
                        <td><?php echo htmlspecialchars($service['owner_full_name'] ?: $service['vehicle_owner']); ?></td>
                    </tr>
                    <tr>
                        <th>Originally Created By:</th>
                        <td><?php echo htmlspecialchars($service['created_by']); ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th width="40%">Created Date:</th>
                        <td><?php echo date('d M Y, h:i A', strtotime($service['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <th>Last Updated:</th>
                        <td>
                            <?php 
                            // Check if updated_at column exists
                            $check_updated_sql = "SHOW COLUMNS FROM services LIKE 'updated_at'";
                            $check_result = $conn->query($check_updated_sql);
                            if ($check_result->num_rows > 0 && isset($service['updated_at']) && $service['updated_at']) {
                                echo date('d M Y, h:i A', strtotime($service['updated_at']));
                            } else {
                                echo 'Never';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Bill Files:</th>
                        <td><?php echo count($bill_files); ?> file(s)</td>
                    </tr>
                    <tr>
                        <th>Currently Updated By:</th>
                        <td><?php echo $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin'; ?> (You)</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript for dynamic form behavior
document.addEventListener('DOMContentLoaded', function() {
    // Initialize vehicle owner field
    updateVehicleOwner();
    
    // File preview functionality for new files
    const billFilesInput = document.getElementById('billFiles');
    const filePreview = document.getElementById('filePreview');
    
    if (billFilesInput) {
        billFilesInput.addEventListener('change', function() {
            filePreview.innerHTML = '';
            const files = this.files;
            
            if (files.length > 10) {
                alert('Maximum 10 files allowed. Please select fewer files.');
                this.value = '';
                return;
            }
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const fileSize = (file.size / (1024 * 1024)).toFixed(2); // Size in MB
                
                if (fileSize > 5) {
                    alert(`File "${file.name}" exceeds 5MB limit. Please select smaller files.`);
                    this.value = '';
                    filePreview.innerHTML = '';
                    return;
                }
                
                const fileItem = document.createElement('div');
                fileItem.className = 'alert alert-light d-flex justify-content-between align-items-center mb-2';
                fileItem.innerHTML = `
                    <div>
                        <i class="fas fa-file me-2"></i>
                        <strong>${file.name}</strong>
                        <small class="text-muted ms-2">(${fileSize} MB)</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeNewFile(${i})">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                filePreview.appendChild(fileItem);
            }
        });
    }
    
    // Handle existing file removal
    const removeButtons = document.querySelectorAll('.remove-file');
    
    removeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const index = this.getAttribute('data-index');
            const fileCard = document.getElementById('file-card-' + index);
            const removeInput = document.getElementById('remove_' + index);
            
            if (confirm('Are you sure you want to remove this file? The file will be deleted permanently.')) {
                // Mark file for removal in hidden input
                removeInput.value = index;
                
                // Hide the file card with fade effect
                fileCard.style.opacity = '0.5';
                fileCard.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    fileCard.style.display = 'none';
                    showToast('File marked for removal. Click Update to save changes.', 'warning');
                }, 300);
            }
        });
    });
});

// Update vehicle owner based on selected vehicle
function updateVehicleOwner() {
    const vehicleSelect = document.getElementById('vehicleSelect');
    const vehicleOwnerDisplay = document.getElementById('vehicleOwnerDisplay');
    
    if (vehicleSelect.value) {
        const selectedOption = vehicleSelect.options[vehicleSelect.selectedIndex];
        const ownerDisplay = selectedOption.getAttribute('data-owner-display');
        vehicleOwnerDisplay.value = ownerDisplay || 'Not specified';
    } else {
        vehicleOwnerDisplay.value = '';
    }
}

// Remove file from new files preview
function removeNewFile(index) {
    const dt = new DataTransfer();
    const input = document.getElementById('billFiles');
    const { files } = input;
    
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        if (index !== i) {
            dt.items.add(file);
        }
    }
    
    input.files = dt.files;
    
    // Trigger change event to update preview
    const event = new Event('change', { bubbles: true });
    input.dispatchEvent(event);
}

// Toast notification function
function showToast(message, type = 'info') {
    // Remove existing toast if any
    const existingToast = document.querySelector('.custom-toast');
    if (existingToast) {
        existingToast.remove();
    }
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `custom-toast alert alert-${type} fade show`;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1050;
        min-width: 250px;
        animation: slideInRight 0.3s ease;
    `;
    
    toast.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'} me-2"></i>
            <span>${message}</span>
            <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }
    }, 5000);
}

// Form submission confirmation
const form = document.getElementById('serviceForm');
if (form) {
    form.addEventListener('submit', function(e) {
        // Check if there are files marked for removal
        const removeInputs = document.querySelectorAll('input[name="remove_files[]"]');
        let filesToRemove = 0;
        
        removeInputs.forEach(input => {
            if (input.value !== '') {
                filesToRemove++;
            }
        });
        
        if (filesToRemove > 0) {
            if (!confirm(`${filesToRemove} file(s) will be permanently deleted. Are you sure you want to continue?`)) {
                e.preventDefault();
                return;
            }
        }
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            submitBtn.disabled = true;
        }
    });
}
</script>

<style>
@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

.file-card {
    transition: all 0.3s ease;
}

.file-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.fade-in {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<?php
// Helper function to get file icon
function getFileIcon($extension) {
    switch(strtolower($extension)) {
        case 'pdf': return 'fa-file-pdf';
        case 'jpg':
        case 'jpeg':
        case 'png': return 'fa-file-image';
        case 'doc':
        case 'docx': return 'fa-file-word';
        default: return 'fa-file';
    }
}

include '../includes/footer.php';
?>