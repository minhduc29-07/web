<?php
// Cấu hình hiển thị lỗi (để dễ debug)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cài đặt múi giờ Việt Nam
date_default_timezone_set('Asia/Ho_Chi_Minh');

session_start();

// --- CẤU HÌNH DATABASE ---
define('DB_SERVER', 'localhost'); // Sửa lại thông tin nếu cần
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'shoe_store');

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set font chữ tiếng Việt và múi giờ cho MySQL
$conn->set_charset("utf8mb4");
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

// --- CÁC HÀM HỖ TRỢ (HELPER FUNCTIONS) ---

// 1. Hàm làm sạch dữ liệu đầu ra (Chống hack XSS)
function html_safe($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// 2. Hàm bắt buộc đăng nhập (Dùng cho các trang nội bộ)
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

// 3. Hàm kiểm tra quyền Admin (QUAN TRỌNG: ĐÃ THÊM LẠI HÀM NÀY)
function check_admin() {
    // Nếu chưa đăng nhập -> Đẩy về login
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    // Nếu đăng nhập rồi mà không phải admin -> Đẩy về trang chủ báo lỗi
    if ($_SESSION['role'] !== 'admin') {
        header("Location: index.php?error=admin_only");
        exit;
    }
}
?>
