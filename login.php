<?php
session_start(); 
require_once 'db.php';

if (!function_exists('html_safe')) {
    function html_safe($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

$message = '';

if (isset($_GET['registered']) && $_GET['registered'] == 'true') {
    $message = "Đăng ký thành công! Vui lòng đăng nhập.";
    $message_type = 'success';
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $message = "Vui lòng nhập tên đăng nhập và mật khẩu.";
        $message_type = 'error';
    } else {
        
        $stmt = $conn->prepare("SELECT password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($hashed_password_from_db, $role);
            $stmt->fetch();
            
        
            if (password_verify($password, $hashed_password_from_db)) {
                
                // Đăng nhập thành công
                $_SESSION['loggedin'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role; // Lưu role để phân quyền

                // Chuyển hướng người dùng đến trang chính
                header("Location: index.php"); 
                exit;

            } else {
                // Mật khẩu không khớp
                $message = "Sai tên đăng nhập hoặc mật khẩu.";
                $message_type = 'error';
            }
        } else {
            // Tên đăng nhập không tồn tại
            $message = "Sai tên đăng nhập hoặc mật khẩu.";
            $message_type = 'error';
        }

        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div id="login-view" class="view active auth-background">
        <div class="form-container">
            <img src="logo.png" alt="Logo" class="logo">
            <h2>Đăng Nhập</h2>
            
            <?php if(!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo html_safe($message); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                
                <div class="form-group">
                    <label for="username">Tên đăng nhập</label>
                    <input type="text" id="username" name="username" placeholder="Tên đăng nhập" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" placeholder="Mật khẩu" required>
                </div>

                <button type="submit" style="margin-top: 15px;">Đăng nhập</button>
            </form>
            <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
        </div>
    </div>
</body>
</html>
