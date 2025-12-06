<?php
// Bật hiển thị lỗi để dễ sửa
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. SỬA GIỜ PHP: Đặt về múi giờ Việt Nam
date_default_timezone_set('Asia/Ho_Chi_Minh');

session_start();

// --- THÔNG TIN KẾT NỐI (Bạn nhớ giữ nguyên thông tin Host/User/Pass của bạn nhé) ---
// Nếu đang chạy Localhost thì giữ nguyên, nếu chạy Hosting thì thay lại số của bạn
define('DB_SERVER', 'localhost'); 
define('DB_USERNAME', 'root');
define('DB_PASSWORD', ''); 
define('DB_NAME', 'shoe_store'); 

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. SỬA GIỜ MYSQL: Bắt buộc Database cũng phải hiểu là đang ở Việt Nam (+7)
$conn->query("SET time_zone = '+07:00'");

// Bắt buộc MySQL tự động lưu (commit)
$conn->autocommit(TRUE);

// Các hàm hỗ trợ
function html_safe($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function check_admin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    if ($_SESSION['role'] !== 'admin') {
        header("Location: index.php?error=admin_only");
        exit;
    }
}
?>
