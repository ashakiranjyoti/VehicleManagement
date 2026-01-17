<?php 
include '../config/database.php';
checkAuth();

// Only admin can access this page
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

include '../includes/header.php'; 
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-users"></i> User Management
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus"></i> Add User
        </button>
    </div>
</div>

<?php
// Add new user
if ($_POST && isset($_POST['add_user'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $conn->real_escape_string($_POST['password']); // Store as plain text
    $email = $conn->real_escape_string($_POST['email']);
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $role = $conn->real_escape_string($_POST['role']);
    
    // Check if username already exists
    $check_sql = "SELECT id FROM users WHERE username = '$username'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        echo '<div class="alert alert-danger">Username already exists!</div>';
    } else {
        $sql = "INSERT INTO users (username, password, email, full_name, role) 
                VALUES ('$username', '$password', '$email', '$full_name', '$role')";
        
        if ($conn->query($sql) === TRUE) {
            echo '<div class="alert alert-success">User added successfully!</div>';
        } else {
            echo '<div class="alert alert-danger">Error adding user: ' . $conn->error . '</div>';
        }
    }
}

// Update user
if ($_POST && isset($_POST['update_user'])) {
    $user_id = $conn->real_escape_string($_POST['user_id']);
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $role = $conn->real_escape_string($_POST['role']);
    
    // Check if password is being updated
    $password_update = "";
    if (!empty($_POST['password'])) {
        $password = $conn->real_escape_string($_POST['password']);
        $password_update = ", password = '$password'";
    }
    
    // Check if username already exists (excluding current user)
    $check_sql = "SELECT id FROM users WHERE username = '$username' AND id != '$user_id'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        echo '<div class="alert alert-danger">Username already exists!</div>';
    } else {
        $sql = "UPDATE users SET 
                username = '$username',
                email = '$email',
                full_name = '$full_name',
                role = '$role'
                $password_update
                WHERE id = '$user_id'";
        
        if ($conn->query($sql) === TRUE) {
            echo '<div class="alert alert-success">User updated successfully!</div>';
        } else {
            echo '<div class="alert alert-danger">Error updating user: ' . $conn->error . '</div>';
        }
    }
}

// Delete user
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // Prevent admin from deleting themselves
    if ($delete_id != $_SESSION['user_id']) {
        $sql = "DELETE FROM users WHERE id = $delete_id";
        if ($conn->query($sql)) {
            echo '<div class="alert alert-success">User deleted successfully!</div>';
        } else {
            echo '<div class="alert alert-danger">Error deleting user: ' . $conn->error . '</div>';
        }
    } else {
        echo '<div class="alert alert-danger">You cannot delete your own account!</div>';
    }
}
?>

<div class="card fade-in">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="usersTable">
                <thead>
                    <tr>
                        <th>Sr No</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Password</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                                        <?php
                    $sql = "SELECT * FROM users ORDER BY created_at DESC";
                    $result = $conn->query($sql);
                    $sr = 0;  // Changed from " = 0;"
                    while($row = $result->fetch_assoc()) {  // Changed from "while( = ->fetch_assoc())"
                        $sr++;  // Changed from "++;"
                        $role_badge = $row['role'] == 'admin' ? 'bg-danger' : 'bg-primary';
                        $is_current_user = $row['id'] == $_SESSION['user_id'];
                        
                        // Show password (plain text)
                        $password_display = '<span class="text-muted password-field" data-password="' . htmlspecialchars($row['password']) . '">
                            <i class="fas fa-eye toggle-password" style="cursor: pointer;"></i>
                            <span class="password-text">••••••••</span>
                         </span>';
                        
                        echo "<tr>
                                <td>{$sr}</td>
                                <td>
                                    {$row['username']}
                                    " . ($is_current_user ? ' <span class="badge bg-warning">You</span>' : '') . "
                                </td>
                                <td>{$row['full_name']}</td>
                                <td>{$row['email']}</td>
                                <td>{$password_display}</td>
                                <td><span class='badge $role_badge'>{$row['role']}</span></td>
                                <td>" . date('d M Y', strtotime($row['created_at'])) . "</td>
                                <td>
                                    <button class='btn btn-sm btn-outline-warning edit-user' 
                                            data-id='{$row['id']}'
                                            data-username='{$row['username']}'
                                            data-email='{$row['email']}'
                                            data-full_name='{$row['full_name']}'
                                            data-role='{$row['role']}'
                                            data-password='{$row['password']}'>
                                        <i class='fas fa-edit'></i>
                                    </button>
                                    " . (!$is_current_user ? "
                                    <a href='?delete_id={$row['id']}' class='btn btn-sm btn-outline-danger' onclick='return confirm(\"Are you sure you want to delete this user?\")'>
                                        <i class='fas fa-trash'></i>
                                    </a>
                                    " : '<span class="text-muted">Current User</span>') . "
                                </td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="text" name="password" class="form-control" required>
                        <div class="form-text">Password will be stored as plain text</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" id="edit_username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="text" name="password" id="edit_password" class="form-control">
                        <div class="form-text">Leave empty to keep current password</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" id="edit_role" class="form-select" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Password show/hide functionality
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(icon => {
        icon.addEventListener('click', function() {
            const passwordField = this.closest('.password-field');
            const passwordText = passwordField.querySelector('.password-text');
            const actualPassword = passwordField.getAttribute('data-password');
            
            if (passwordText.textContent === '••••••••') {
                passwordText.textContent = actualPassword;
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                passwordText.textContent = '••••••••';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    });
    
    // Edit user modal
    const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
    
    document.querySelectorAll('.edit-user').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-id');
            const username = this.getAttribute('data-username');
            const email = this.getAttribute('data-email');
            const fullName = this.getAttribute('data-full_name');
            const role = this.getAttribute('data-role');
            const password = this.getAttribute('data-password');
            
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_full_name').value = fullName;
            document.getElementById('edit_role').value = role;
            document.getElementById('edit_password').placeholder = "Current: " + password;
            
            editModal.show();
        });
    });
    
    // Generate random password
    document.getElementById('generatePassword')?.addEventListener('click', function() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        let password = '';
        for (let i = 0; i < 10; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.querySelector('input[name="password"]').value = password;
    });
    
    // Generate random password for edit
    document.getElementById('generateEditPassword')?.addEventListener('click', function() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        let password = '';
        for (let i = 0; i < 10; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById('edit_password').value = password;
    });
});
</script>

<style>
.password-field {
    cursor: pointer;
}
.password-field:hover {
    color: #0d6efd;
}
.password-text {
    margin-left: 5px;
    font-family: monospace;
}
</style>

<?php include '../includes/footer.php'; ?>