<?php
require_once 'db.php';
$message = '';

// Nếu đã đăng nhập thì vào thẳng POS
if (isset($_SESSION['user_id'])) {
    header("Location: pos.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password']; 
    // Lấy giá trị checkbox Remember Me
    $remember = isset($_POST['remember']);

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // 1. Lưu Session (Đăng nhập bình thường)
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // 2. XỬ LÝ COOKIE (NẾU CHỌN REMEMBER ME)
            if ($remember) {
                // Tạo một chuỗi mã ngẫu nhiên
                $token = bin2hex(random_bytes(32)); 
                
                // Lưu token này vào Database để đối chiếu sau này
                $uid = $user['id'];
                $conn->query("UPDATE users SET remember_token = '$token' WHERE id = $uid");

                // Lưu Cookie vào trình duyệt: Dạng "ID:Token" (Lưu trong 30 ngày)
                $cookie_value = $uid . ':' . $token;
                setcookie('remember_user', $cookie_value, time() + (86400 * 30), "/");
            }

            header("Location: pos.php");
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
    <style>
        /* CSS cho checkbox đẹp hơn */
        .checkbox-group { display: flex; align-items: center; margin-bottom: 15px; text-align: left; }
        .checkbox-group input { width: auto; margin-right: 10px; margin-bottom: 0; }
    </style>
</head>
<body>
    <div id="login-view" class="view active auth-background">
        <div class="form-container">
            <img src="logo.png" alt="Logo" class="logo">
            <h2>Login</h2>
            
            <?php if(!empty($message)): ?>
                <p class="message error"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember" style="margin-bottom:0; cursor:pointer;">Remember Me</label>
                </div>

                <button type="submit">Login to POS</button>
            </form>
            <p>Don't have an account? <a href="register.php">Register now</a></p>
        </div>
    </div>
</body>
</html>
