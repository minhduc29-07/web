<?php
require_once 'db.php';
$message = '';

if (isset($_SESSION['user_id'])) {
    header("Location: pos.php"); // <-- Đã sửa thành pos.php
    exit;
}

if (isset($_GET['registered'])) {
    $message = "Registration successful! Please log in.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password']; 

    // Lấy thông tin user
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Đăng nhập thành công
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // 2. SỬA CHỖ NÀY: Chuyển hướng vào trang POS sau khi đăng nhập thành công
            header("Location: pos.php"); // <-- Đã sửa thành pos.php
            exit;
        } else {
            $message = "Invalid username or password.";
        }
    } else {
        $message = "Invalid username or password.";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div id="login-view" class="view active auth-background">
        <div class="form-container">
            <img src="logo.png" alt="Logo" class="logo">
            <h2>Login</h2>
            
            <?php if(!empty($message)): ?>
                <p class="message <?php echo (isset($_GET['registered'])) ? 'success' : 'error'; ?>">
                    <?php echo html_safe($message); ?>
                </p>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login to POS</button>
            </form>
            <p>Don't have an account? <a href="register.php">Register now</a></p>
        </div>
    </div>
</body>
</html>
