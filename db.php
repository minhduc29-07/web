<?php
// Cấu hình hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Ho_Chi_Minh');

session_start();

// CẤU HÌNH DATABASE
define('DB_SERVER', 'localhost'); // Sửa lại nếu trên hosting
define('DB_USERNAME', 'root');    // Sửa lại nếu trên hosting
define('DB_PASSWORD', '');        // Sửa lại nếu trên hosting
define('DB_NAME', 'shoe_store');  // Sửa lại nếu trên hosting

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->query("SET time_zone = '+07:00'");
$conn->autocommit(TRUE);

// --- TÍNH NĂNG TỰ ĐĂNG NHẬP BẰNG COOKIE (AUTO LOGIN) ---
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_user'])) {
    // Cookie lưu dạng: "ID:Token"
    $cookie_data = explode(':', $_COOKIE['remember_user']);
    
    if (count($cookie_data) == 2) {
        $uid = $conn->real_escape_string($cookie_data[0]);
        $token = $conn->real_escape_string($cookie_data[1]);

        // Kiểm tra xem ID và Token có khớp trong Database không
        $stmt = $conn->prepare("SELECT id, username, role, remember_token FROM users WHERE id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows == 1) {
            $user = $res->fetch_assoc();
            // Nếu Token khớp nhau -> Tự động đăng nhập
            if ($user['remember_token'] === $token) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
            }
        }
        $stmt->close();
    }
}
// --------------------------------------------------------

function html_safe($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}
?>
