<?php 
include '../config/database.php';
checkAuth();
include '../includes/header.php'; 

// Pre-fill vehicle if coming from vehicle page
$preselected_vehicle = $_GET['vehicle_id'] ?? '';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-tools"></i> Add Service Record
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="service_history.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Services
        </a>
    </div>
</div>

<?php
if ($_POST) {
    $vehicle_id = $_POST['vehicle_id'];
    $service_date = $_POST['service_date'];
    $service_time = $_POST['service_time'];
    $service_type = $_POST['service_type'];
    $running_km = $_POST['running_km'] ?? null;
    $service_center_name = $_POST['service_center_name'] ?? '';
    $service_center_place = $_POST['service_center_place'] ?? '';
    $service_done_by = $_POST['service_done_by'] ?? '';
    $cost = $_POST['cost'];
    $description = $_POST['description'];
    $created_by = $_SESSION['username'] ?? 'admin'; // Auto-filled with logged in user
    
    // Multiple file upload handling
    $bill_files = [];
    $upload_errors = [];
    
    if (!empty($_FILES['bill_files']['name'][0])) {
        $target_dir = "../uploads/";
        
        // Loop through each uploaded file
        for ($i = 0; $i < count($_FILES['bill_files']['name']); $i++) {
            $file_name = $_FILES['bill_files']['name'][$i];
            $file_tmp = $_FILES['bill_files']['tmp_name'][$i];
            $file_size = $_FILES['bill_files']['size'][$i];
            $file_error = $_FILES['bill_files']['error'][$i];
            
            // Check for upload errors
            if ($file_error === UPLOAD_ERR_OK) {
                $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    if ($file_size <= 5 * 1024 * 1024) { // 5MB limit
                        $unique_name = time() . '_' . uniqid() . '_' . ($i + 1) . '.' . $file_extension;
                        $target_file = $target_dir . $unique_name;
                        
                        if (move_uploaded_file($file_tmp, $target_file)) {
                            $bill_files[] = $unique_name;
                        } else {
                            $upload_errors[] = "Failed to upload: " . $file_name;
                        }
                    } else {
                        $upload_errors[] = "File too large (max 5MB): " . $file_name;
                    }
                } else {
                    $upload_errors[] = "Invalid file type: " . $file_name;
                }
            } else {
                $upload_errors[] = "Upload error for: " . $file_name;
            }
        }
    }
    
    // Convert bill files array to comma-separated string for database
    $bill_files_str = !empty($bill_files) ? implode(',', $bill_files) : '';
    
    $sql = "INSERT INTO services (vehicle_id, service_date, service_time, service_type, running_km, service_center_name, service_center_place, service_done_by, cost, bill_file, description, created_by) 
            VALUES ('$vehicle_id', '$service_date', '$service_time', '$service_type', '$running_km', '$service_center_name', '$service_center_place', '$service_done_by', '$cost', '$bill_files_str', '$description', '$created_by')";
    
    if ($conn->query($sql) === TRUE) {
        echo '<div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Service record added successfully!';
        
        // Show upload errors if any
        if (!empty($upload_errors)) {
            echo '<br><small class="text-warning">Some files could not be uploaded: ' . implode(', ', $upload_errors) . '</small>';
        }
        
        echo '</div>';
        
        // Clear form after successful submission
        $_POST = array();
        $preselected_vehicle = '';
    } else {
        echo '<div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> Error: ' . $conn->error . '
              </div>';
    }
}
?>

<div class="card fade-in">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" class="row g-3" id="serviceForm">
            <!-- Vehicle Selection -->
            <div class="col-md-6">
                <label class="form-label">Select Vehicle <span class="text-danger">*</span></label>
                <select name="vehicle_id" class="form-select" required id="vehicleSelect" onchange="updateVehicleOwner()">
                    <option value="">Select Vehicle</option>
                    <?php
                    $result = $conn->query("SELECT v.*, u.full_name as owner_full_name 
                                           FROM vehicles v 
                                           LEFT JOIN users u ON v.owner_name = u.username 
                                           ORDER BY v.make_model");
                    while($row = $result->fetch_assoc()) {
                        $selected = ($preselected_vehicle == $row['id'] || $_POST['vehicle_id'] == $row['id']) ? 'selected' : '';
                        $icon = $row['vehicle_type'] == 'bike' ? '🏍️' : '🚗';
                        $owner_display = $row['owner_full_name'] ? $row['owner_full_name'] : ($row['owner_name'] ? $row['owner_name'] : 'Not specified');
                        echo "<option value='{$row['id']}' 
                                data-owner='{$row['owner_name']}' 
                                data-owner-display='{$owner_display}'
                                data-type='{$row['vehicle_type']}' 
                                $selected>
                                {$icon} {$row['make_model']} ({$row['reg_number']})
                              </option>";
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
            
            <!-- Service Type (Only 2 options) -->
            <div class="col-md-6">
                <label class="form-label">Service Type <span class="text-danger">*</span></label>
                <select name="service_type" class="form-select" required id="serviceType">
                    <option value="">Select Service Type</option>
                    <option value="Regular Service" <?php echo ($_POST['service_type'] ?? '') == 'Regular Service' ? 'selected' : ''; ?>>Regular Service</option>
                    <option value="Breakdown Service" <?php echo ($_POST['service_type'] ?? '') == 'Breakdown Service' ? 'selected' : ''; ?>>Breakdown Service</option>
                </select>
                <div class="form-text">Select the type of service performed</div>
            </div>
            
            <!-- Service Done By (Who performed the service) -->
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
                            $selected = ($_POST['service_done_by'] ?? '') == $user['username'] ? 'selected' : '';
                            $display_name = $user['full_name'] ? $user['full_name'] . ' (' . $user['username'] . ')' : $user['username'];
                            echo "<option value='{$user['username']}' $selected>{$display_name}</option>";
                        }
                    } else {
                        // If no users in database, show current logged in user
                        $current_user = $_SESSION['username'] ?? 'admin';
                        echo "<option value='{$current_user}' selected>{$current_user} (Current User)</option>";
                    }
                    ?>
                </select>
                <div class="form-text">Who performed the service? (Mechanic/Service person)</div>
            </div>
            
            <!-- Running KM -->
            <div class="col-md-6">
                <label class="form-label">Running KM</label>
                <input type="number" name="running_km" class="form-control" 
                       value="<?php echo $_POST['running_km'] ?? ''; ?>" 
                       placeholder="e.g., 15000" min="0" step="1">
                <div class="form-text">Current kilometer reading at service time</div>
            </div>
            
            <!-- Service Date & Time -->
            <div class="col-md-3">
                <label class="form-label">Service Date <span class="text-danger">*</span></label>
                <input type="date" name="service_date" class="form-control" 
                       value="<?php echo $_POST['service_date'] ?? date('Y-m-d'); ?>" required>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Service Time <span class="text-danger">*</span></label>
                <input type="time" name="service_time" class="form-control" 
                       value="<?php echo $_POST['service_time'] ?? date('H:i'); ?>" required>
            </div>
            
            <!-- Service Center Information -->
            <div class="col-md-6">
                <label class="form-label">Service Center Name</label>
                <input type="text" name="service_center_name" class="form-control" 
                       value="<?php echo $_POST['service_center_name'] ?? ''; ?>" 
                       placeholder="e.g., ABC Auto Service, XYZ Garage">
            </div>
            
            <div class="col-md-6">
                <label class="form-label">Service Center Place/Location</label>
                <input type="text" name="service_center_place" class="form-control" 
                       value="<?php echo $_POST['service_center_place'] ?? ''; ?>" 
                       placeholder="e.g., Delhi, Noida, Gurgaon, etc.">
            </div>
            
            <!-- Cost -->
            <div class="col-md-6">
                <label class="form-label">Service Cost (₹) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input type="number" step="0.01" min="0" name="cost" class="form-control" 
                           value="<?php echo $_POST['cost'] ?? ''; ?>" required
                           placeholder="0.00">
                </div>
                <div class="form-text">Total cost of service including parts and labor</div>
            </div>
            
            <!-- Created By (Auto-filled with logged in user) -->
            <div class="col-md-6">
                <label class="form-label">Created By</label>
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
• Next service recommendations..."><?php echo $_POST['description'] ?? ''; ?></textarea>
                <div class="form-text">Be as detailed as possible for future reference</div>
            </div>
            
            <!-- Multiple Bill/Proof Upload -->
            <div class="col-12">
                <label class="form-label">Upload Bills/Proof Documents</label>
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
                    <button type="reset" class="btn btn-secondary me-md-2" onclick="resetForm()">
                        <i class="fas fa-redo"></i> Reset Form
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Service Record
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Service Information Guidelines -->
<div hidden class="card mt-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="fas fa-info-circle"></i> Service Information Guidelines
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Service Types:</h6>
                <ul class="small">
                    <li><strong class="text-success">Regular Service:</strong> Scheduled maintenance as per vehicle manual timeline</li>
                    <li><strong class="text-danger">Breakdown Service:</strong> Emergency repairs due to vehicle breakdown or failure</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>Important Notes:</h6>
                <ul class="small">
                    <li><strong>Vehicle Owner:</strong> Auto-filled from vehicle registration</li>
                    <li><strong>Service Done By:</strong> Select who performed the service</li>
                    <li><strong>Multiple Proofs:</strong> Upload all bills, receipts, photos as proof</li>
                    <li><strong>Running KM:</strong> Essential for tracking service intervals</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript for dynamic form behavior
document.addEventListener('DOMContentLoaded', function() {
    // Initialize vehicle owner field
    updateVehicleOwner();
    
    // File preview functionality
    const billFilesInput = document.getElementById('billFiles');
    const filePreview = document.getElementById('filePreview');
    
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
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile(${i})">
                    <i class="fas fa-times"></i>
                </button>
            `;
            filePreview.appendChild(fileItem);
        }
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

// Remove file from preview
function removeFile(index) {
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

// Reset form completely
function resetForm() {
    document.getElementById('serviceForm').reset();
    document.getElementById('filePreview').innerHTML = '';
    updateVehicleOwner();
}
</script>

<?php include '../includes/footer.php'; ?>