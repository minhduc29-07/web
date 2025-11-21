<?php
require_once 'db.php';
check_admin(); // Only Admins can access this page

$message = '';
$message_type = '';

// Form variables
$edit_user_id = '';
$edit_username = '';
$edit_email = '';
$edit_dob = '';
$edit_location = '';
$edit_role = 'staff';
$is_editing = false;

// --- HANDLE POST (CREATE & UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_user'])) {
    
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password']; // Plain text password
    $email = $conn->real_escape_string($_POST['email']);
    $dob = $conn->real_escape_string($_POST['dob']);
    $location = $conn->real_escape_string($_POST['location']);
    $role = $conn->real_escape_string($_POST['role']);
    $user_id = $conn->real_escape_string($_POST['user_id']);

    // Retain values
    $edit_username = $username;
    $edit_email = $email;
    $edit_dob = $dob;
    $edit_location = $location;
    $edit_role = $role;

    // Validation
    if (empty($username) || empty($email) || empty($dob) || empty($location) || empty($role)) {
        $message = "Error: Please fill in all fields (except Password if not changing)."; // Translated
        $message_type = 'error';
    } else {
        
        try {
            if (!empty($user_id)) {
                // --- UPDATE (U) ---
                $is_editing = true;
                $edit_user_id = $user_id;

                if (!empty($password)) {
                    // Update WITH password
                    $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, email = ?, dob = ?, location = ?, role = ? WHERE id = ?");
                    $stmt->bind_param("ssssssi", $username, $password, $email, $dob, $location, $role, $user_id);
                } else {
                    // Update WITHOUT password
                    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, dob = ?, location = ?, role = ? WHERE id = ?");
                    $stmt->bind_param("sssssi", $username, $email, $dob, $location, $role, $user_id);
                }
                
                $stmt->execute();
                
                if ($stmt->affected_rows > 0) {
                    $message = "Account updated successfully!"; // Translated
                    $message_type = 'success';
                } else {
                    $message = "No changes made or error during update."; // Translated
                    $message_type = 'info';
                }

            } else {
                // --- CREATE (C) ---
                if (empty($password)) {
                    $message = "Error: Password is required when creating a new account."; // Translated
                    $message_type = 'error';
                } else {
                    $stmt = $conn->prepare("INSERT INTO users (username, password, email, dob, location, role) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssss", $username, $password, $email, $dob, $location, $role);
                    
                    $stmt->execute();

                    if ($stmt->affected_rows > 0) {
                        $message = "New account created successfully!"; // Translated
                        $message_type = 'success';
                        // Clear form
                        $edit_username = $edit_email = $edit_dob = $edit_location = '';
                        $edit_role = 'staff';
                    } else {
                         $message = "Error: Could not create account."; // Translated
                         $message_type = 'error';
                    }
                }
            }
            $stmt->close();

        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) { // Duplicate entry
                $message = "Error: Username or Email already exists."; // Translated
                $message_type = 'error';
            } else {
                $message = "Database Error: " . $e->getMessage(); // Translated
                $message_type = 'error';
            }
        }
    }
}

// --- HANDLE GET (DELETE / EDIT) ---

// --- DELETE (D) ---
if (isset($_GET['delete'])) {
    $user_id_to_delete = (int)$_GET['delete'];
    
    // Safety: Don't let admin delete themselves
    if ($user_id_to_delete == $_SESSION['user_id']) {
        $message = "Error: You cannot delete your own account."; // Translated
        $message_type = 'error';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id_to_delete);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Account deleted successfully."; // Translated
            $message_type = 'success';
        } else {
            $message = "Error deleting account."; // Translated
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// --- READ (R) - Get 1 record for Edit ---
if (isset($_GET['edit'])) {
    $user_id_to_edit = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT username, email, dob, location, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $edit_user_id = $user_id_to_edit;
        $edit_username = $user['username'];
        $edit_email = $user['email'];
        $edit_dob = $user['dob'];
        $edit_location = $user['location'];
        $edit_role = $user['role'];
        $is_editing = true;
    }
    $stmt->close();
}

// --- READ (R) - Get all users ---
$users_list = [];
$result_read = $conn->query("SELECT id, username, email, dob, location, role FROM users ORDER BY username ASC");
while ($row = $result_read->fetch_assoc()) {
    $users_list[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en"> <!-- Changed lang to en -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title> <!-- Translated -->
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php require_once 'navigation.php'; ?>

    <div class="container">
        
        <!-- Add/Edit User Form -->
        <div class="form-container" style="max-width: none; box-shadow: none; padding: 0; margin: 0;">
            <h3><?php echo $is_editing ? 'Update Account' : 'Create New Account'; ?></h3> <!-- Translated -->
            
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo html_safe($message); ?>
                </div>
            <?php endif; ?>

            <form action="manage_users.php" method="POST">
                <input type="hidden" name="user_id" value="<?php echo html_safe($edit_user_id); ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="username">Username</label> <!-- Translated -->
                        <input type="text" id="username" name="username" value="<?php echo html_safe($edit_username); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label> <!-- Translated -->
                        <input type="password" id="password" name="password" 
                               placeholder="<?php echo $is_editing ? 'Leave blank to keep same password' : 'Required'; ?>" 
                               <?php echo !$is_editing ? 'required' : ''; ?>> <!-- Translated -->
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label> <!-- Translated -->
                        <input type="email" id="email" name="email" value="<?php echo html_safe($edit_email); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="dob">Date of Birth</label> <!-- Translated -->
                        <input type="date" id="dob" name="dob" value="<?php echo html_safe($edit_dob); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label> <!-- Translated -->
                        <input type="text" id="location" name="location" value="<?php echo html_safe($edit_location); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="role">Role</label> <!-- Translated -->
                        <select id="role" name="role" required>
                            <option value="staff" <?php echo ($edit_role == 'staff') ? 'selected' : ''; ?>>
                                Staff
                            </option> <!-- Translated -->
                            <option value="admin" <?php echo ($edit_role == 'admin') ? 'selected' : ''; ?>>
                                Admin
                            </option> <!-- Translated -->
                        </select>
                    </div>
                </div>

                <button type="submit" name="save_user">
                    <?php echo $is_editing ? 'Update Account' : 'Create Account'; ?> <!-- Translated -->
                </button>
                
                <?php if ($is_editing): ?>
                    <a href="manage_users.php" class="btn-cancel">Cancel Edit</a> <!-- Translated -->
                <?php endif; ?>
            </form>
        </div>

        <!-- User List Table -->
        <div class="table-container">
            <h2>Account List</h2> <!-- Translated -->
            <table>
                <thead>
                    <tr>
                        <th>Username</th> <!-- Translated -->
                        <th>Email</th> <!-- Translated -->
                        <th>Date of Birth</th> <!-- Translated -->
                        <th>Location</th> <!-- Translated -->
                        <th>Role</th> <!-- Translated -->
                        <th>Actions</th> <!-- Translated -->
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users_list)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No accounts found.</td> <!-- Translated -->
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users_list as $user): ?>
                            <tr <?php echo ($user['id'] == $_SESSION['user_id']) ? 'style="background-color: #e6f7ff;"' : ''; ?>>
                                <td>
                                    <?php echo html_safe($user['username']); ?>
                                    <?php echo ($user['id'] == $_SESSION['user_id']) ? ' <strong>(You)</strong>' : ''; ?> <!-- Translated -->
                                </td>
                                <td><?php echo html_safe($user['email']); ?></td>
                                <td><?php echo html_safe($user['dob']); ?></td>
                                <td><?php echo html_safe($user['location']); ?></td>
                                <td><?php echo html_safe($user['role']); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a href="manage_users.php?edit=<?php echo $user['id']; ?>" class="btn-edit">Edit</a> <!-- Translated -->
                                        
                                        <?php if ($user['id'] != $_SESSION['user_id']): // Don't allow self-delete ?>
                                            <a href="manage_users.php?delete=<?php echo $user['id']; ?>" class="btn-delete" 
                                               onclick="return confirm('Are you sure you want to delete the account: <?php echo html_safe($user['username']); ?>?');">Delete</a> <!-- Translated -->
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div> <!-- /container -->

</body>
</html>