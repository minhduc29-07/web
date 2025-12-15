<?php
require_once 'db.php';
check_admin(); // Chỉ Admin mới được vào trang này

$message = '';
$message_type = '';

// Biến cho form
$edit_user_id = '';
$edit_username = '';
$edit_email = '';
$edit_dob = '';
$edit_location = '';
$edit_role = 'staff';
$is_editing = false;

// --- 1. XỬ LÝ XÓA USER (ĐÂY LÀ PHẦN BẠN BỊ THIẾU) ---
if (isset($_GET['delete'])) {
    $user_id_to_delete = (int)$_GET['delete'];

    // Chặn không cho xóa chính mình
    if ($user_id_to_delete == $_SESSION['user_id']) {
        $message = "Error: You cannot delete your own account!";
        $message_type = 'error';
    } else {
        // Thực hiện xóa
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id_to_delete);
        
        if ($stmt->execute()) {
            $message = "Account deleted successfully.";
            $message_type = 'success';
        } else {
            $message = "Error deleting account: " . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// --- 2. XỬ LÝ FORM (TẠO MỚI & CẬP NHẬT) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_user'])) {

    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password']; // Mật khẩu thô
    $email = $conn->real_escape_string($_POST['email']);
    $dob = $conn->real_escape_string($_POST['dob']);
    $location = $conn->real_escape_string($_POST['location']);
    $role = $conn->real_escape_string($_POST['role']);
    $user_id = $conn->real_escape_string($_POST['user_id']); // Nếu rỗng là tạo mới

    // Giữ lại giá trị nhập nếu lỗi
    $edit_user_id = $user_id;
    $edit_username = $username;
    $edit_email = $email;
    $edit_dob = $dob;
    $edit_location = $location;
    $edit_role = $role;

    // Validate cơ bản
    if (empty($username) || empty($email) || empty($dob) || empty($location) || empty($role)) {
        $message = "Error: Please fill in all fields (except Password if not changing).";
        $message_type = 'error';
    }
    // Nếu tạo mới thì bắt buộc có pass
    else if (empty($user_id) && empty($password)) {
        $message = "Error: Please enter a password for the new account.";
        $message_type = 'error';
    }
    // Nếu có nhập pass thì phải >= 6 ký tự
    else if (!empty($password) && strlen($password) < 6) { 
        $message = "Password must be at least 6 characters.";
        $message_type = 'error';
    } 
    else {
        // --- TẠO MỚI (INSERT) ---
        if (empty($user_id)) {

            // Kiểm tra trùng username/email
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt_check->bind_param("ss", $username, $email);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $message = "Username or Email already exists.";
                $message_type = 'error';
            } else {
                // Mã hóa mật khẩu
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                if (!$hashed_password) {
                    $message = "Critical Error: password_hash() failed.";
                    $message_type = 'error';
                } else {
                    $stmt = $conn->prepare("INSERT INTO users (username, password, role, email, dob, location) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssss", $username, $hashed_password, $role, $email, $dob, $location);

                    if ($stmt->execute()) {
                        $message = "User created successfully!";
                        $message_type = 'success';
                        // Reset form
                        $edit_username = $edit_email = $edit_dob = $edit_location = '';
                        $edit_role = 'staff';
                    } else {
                        $message = "Error adding user: " . $stmt->error;
                        $message_type = 'error';
                    }
                    $stmt->close();
                }
            }
            $stmt_check->close();
        } 
        
        // --- CẬP NHẬT (UPDATE) ---
        else {
            $stmt = null;

            // Nếu admin nhập pass mới -> Đổi pass
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, email = ?, dob = ?, location = ?, role = ? WHERE id = ?");
                $stmt->bind_param("ssssssi", $username, $hashed_password, $email, $dob, $location, $role, $user_id);
            } else {
                // Không đổi pass
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, dob = ?, location = ?, role = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $username, $email, $dob, $location, $role, $user_id);
            }

            if ($stmt) {
                if ($stmt->execute()) {
                    $message = "User updated successfully!";
                    $message_type = 'success';
                    $is_editing = false;
                    $edit_user_id = '';
                    // Reset form variables
                    $edit_username = $edit_email = $edit_dob = $edit_location = '';
                    $edit_role = 'staff';
                } else {
                    $message = "Error updating user: " . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
            }
        }
    }
}

// --- 3. LẤY DỮ LIỆU ĐỂ SỬA ---
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

// --- 4. HIỂN THỊ DANH SÁCH ---
$users_list = [];
$result_read = $conn->query("SELECT id, username, email, dob, location, role FROM users ORDER BY username ASC");
while ($row = $result_read->fetch_assoc()) {
    $users_list[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php require_once 'navigation.php'; ?>

    <div class="container">
        
        <div class="form-container" style="max-width: none; box-shadow: none; padding: 0; margin: 0;">
            <h3><?php echo $is_editing ? 'Update Account' : 'Create New Account'; ?></h3>
            
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form action="manage_users.php" method="POST">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($edit_user_id); ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($edit_username); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" 
                               placeholder="<?php echo $is_editing ? 'Leave blank to keep current password' : 'Required'; ?>"
                               <?php echo !$is_editing ? 'required' : ''; ?>>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($edit_email); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($edit_dob); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($edit_location); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="staff" <?php echo ($edit_role == 'staff') ? 'selected' : ''; ?>>Staff</option>
                            <option value="admin" <?php echo ($edit_role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                </div>

                <button type="submit" name="save_user">
                    <?php echo $is_editing ? 'Update Account' : 'Create Account'; ?>
                </button>
                
                <?php if ($is_editing): ?>
                    <a href="manage_users.php" class="btn-cancel">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <h2>Account List</h2>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Date of Birth</th>
                        <th>Location</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users_list)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No accounts found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users_list as $user): ?>
                            <tr <?php echo ($user['id'] == $_SESSION['user_id']) ? 'style="background-color: #e6f7ff;"' : ''; ?>>
                                <td>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                    <?php echo ($user['id'] == $_SESSION['user_id']) ? ' <strong>(You)</strong>' : ''; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['dob']); ?></td>
                                <td><?php echo htmlspecialchars($user['location']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a href="manage_users.php?edit=<?php echo $user['id']; ?>" class="btn-edit">Edit</a>
                                        
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="manage_users.php?delete=<?php echo $user['id']; ?>" class="btn-delete"
                                               onclick="return confirm('Are you sure you want to delete the account: <?php echo htmlspecialchars($user['username']); ?>?');">Delete</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>
