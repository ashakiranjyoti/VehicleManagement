<?php 
include '../config/database.php';
checkAuth();
include '../includes/header.php'; 
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-plus"></i> Add New Vehicle
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="view_vehicles.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Vehicles
        </a>
    </div>
</div>

<?php
if ($_POST) {
    $vehicle_type = $_POST['vehicle_type'];
    $make_model = $_POST['make_model'];
    $reg_number = $_POST['reg_number'];
    $chassis_number = $_POST['chassis_number'];
    $owner_name = $_POST['owner_name']; // Vehicle owner
    
    // Check if registration number already exists
    $check_sql = "SELECT id FROM vehicles WHERE reg_number = '$reg_number'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        echo '<div class="alert alert-danger">Error: Registration number already exists!</div>';
    } else {
        // Check if chassis number already exists
        $check_chassis_sql = "SELECT id FROM vehicles WHERE chassis_number = '$chassis_number'";
        $check_chassis_result = $conn->query($check_chassis_sql);
        
        if ($check_chassis_result->num_rows > 0) {
            echo '<div class="alert alert-danger">Error: Chassis number already exists!</div>';
        } else {
            $added_by_user = $_SESSION['username'] ?? 'admin';
            
            $sql = "INSERT INTO vehicles (vehicle_type, make_model, reg_number, chassis_number, owner_name, added_by_user) 
                    VALUES ('$vehicle_type', '$make_model', '$reg_number', '$chassis_number', '$owner_name', '$added_by_user')";
            
            if ($conn->query($sql) === TRUE) {
                echo '<div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Vehicle added successfully!
                      </div>';
                
                // Clear form after successful submission
                $_POST = array();
            } else {
                echo '<div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> Error: ' . $conn->error . '
                      </div>';
            }
        }
    }
}
?>

<div class="card fade-in">
    <div class="card-body">
        <form method="POST" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                <select name="vehicle_type" class="form-select" required>
                    <option value="">Select Type</option>
                    <option value="bike" <?php echo ($_POST['vehicle_type'] ?? '') == 'bike' ? 'selected' : ''; ?>>Bike</option>
                    <option value="car" <?php echo ($_POST['vehicle_type'] ?? '') == 'car' ? 'selected' : ''; ?>>Car</option>
                </select>
            </div>
            
            <div class="col-md-6">
                <label class="form-label">Make & Model <span class="text-danger">*</span></label>
                <input type="text" name="make_model" class="form-control" 
                       value="<?php echo $_POST['make_model'] ?? ''; ?>" 
                       required placeholder="e.g., Honda Activa, Maruti Swift">
            </div>
            
            <div class="col-md-6">
                <label class="form-label">Registration Number <span class="text-danger">*</span></label>
                <input type="text" name="reg_number" class="form-control" 
                       value="<?php echo $_POST['reg_number'] ?? ''; ?>" 
                       required placeholder="e.g., UP32AB1234">
                <div class="form-text">Unique registration number</div>
            </div>
            
            <div class="col-md-6">
                <label class="form-label">Chassis Number <span class="text-danger">*</span></label>
                <input type="text" name="chassis_number" class="form-control" 
                       value="<?php echo $_POST['chassis_number'] ?? ''; ?>" 
                       required placeholder="e.g., ABC123XYZ456">
                <div class="form-text">Unique chassis number</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Vehicle Owner Name <span class="text-danger">*</span></label>
                <select name="owner_name" class="form-select" required id="ownerSelect">
                    <option value="">Select Owner</option>
                    <?php
                    // Fetch all users from users table for owner selection
                    $users_sql = "SELECT id, username, full_name FROM users ORDER BY full_name";
                    $users_result = $conn->query($users_sql);
                    
                    if ($users_result && $users_result->num_rows > 0) {
                        while($user = $users_result->fetch_assoc()) {
                            $selected = ($_POST['owner_name'] ?? '') == $user['username'] ? 'selected' : '';
                            $display_name = $user['full_name'] ? $user['full_name'] . ' (' . $user['username'] . ')' : $user['username'];
                            echo "<option value='{$user['username']}' $selected>{$display_name}</option>";
                        }
                    } else {
                        // If no users in database, show current logged in user
                        $current_user = $_SESSION['username'] ?? 'admin';
                        $current_full_name = $_SESSION['full_name'] ?? 'Admin';
                        echo "<option value='{$current_user}' selected>{$current_full_name} ({$current_user})</option>";
                    }
                    ?>
                </select>
                <div class="form-text">Select the owner of this vehicle</div>
            </div>
            
            <div class="col-md-6">
                <label class="form-label">Added By</label>
                <input type="text" class="form-control" 
                       value="<?php echo $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin'; ?>" 
                       readonly style="background-color: #f8f9fa;">
                <div class="form-text">You (Auto-filled)</div>
            </div>
            
            <div class="col-md-6">
                <label class="form-label">Date & Time</label>
                <input type="text" class="form-control" 
                       value="<?php echo date('d M Y, h:i A'); ?>" 
                       readonly style="background-color: #f8f9fa;">
                <div class="form-text">Current date and time</div>
            </div>
            
            <div class="col-12">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="reset" class="btn btn-secondary me-md-2">
                        <i class="fas fa-redo"></i> Reset Form
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Vehicle
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Quick Help Section -->
<div hidden class="card mt-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="fas fa-info-circle"></i> Vehicle Information Guidelines
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <h6>Owner Information:</h6>
                <ul class="small">
                    <li>Select the actual owner of the vehicle</li>
                    <li>This will be used for all service records</li>
                    <li>Cannot be changed after adding services</li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6>Registration Number:</h6>
                <ul class="small">
                    <li>Format: XX00XX0000 (e.g., UP32AB1234)</li>
                    <li>Must be unique for each vehicle</li>
                    <li>No special characters allowed</li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6>Chassis Number:</h6>
                <ul class="small">
                    <li>Unique identification number</li>
                    <li>Usually 17 characters long</li>
                    <li>Found on vehicle's chassis</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Add new owner option if not in list
document.addEventListener('DOMContentLoaded', function() {
    const ownerSelect = document.getElementById('ownerSelect');
    
    // Add "Add New Owner" option
    const addNewOption = document.createElement('option');
    addNewOption.value = 'new_owner';
    addNewOption.textContent = '+ Add New Owner';
    ownerSelect.appendChild(addNewOption);
    
    ownerSelect.addEventListener('change', function() {
        if (this.value === 'new_owner') {
            const newOwnerName = prompt('Enter new owner name:');
            if (newOwnerName && newOwnerName.trim() !== '') {
                // Add new option to select
                const newOption = document.createElement('option');
                newOption.value = newOwnerName.trim();
                newOption.textContent = newOwnerName.trim() + ' (New)';
                newOption.selected = true;
                
                // Insert before the "Add New Owner" option
                ownerSelect.insertBefore(newOption, addNewOption);
            } else {
                this.selectedIndex = 0;
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>