<?php
// Bật hiển thị lỗi để gỡ lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Bắt đầu session ở đầu mỗi trang cần dùng session
session_start();

// --- THÔNG TIN CẤU HÌNH CSDL (ĐÃ RESET MẬT KHẨU VỀ RỖNG) ---
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', ''); // <-- Mật khẩu rỗng (''')
define('DB_NAME', 'shoe_store'); 

// Tạo kết nối
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// BẮT BUỘC MySQL tự động lưu (commit)
$conn->autocommit(TRUE);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Connection failed: " ->connect_error); // Đã dịch
}

// Hàm để chống XSS
function html_safe($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Hàm bắt buộc đăng nhập
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

// Hàm bắt buộc là Admin
function check_admin() {
    // Đầu tiên, kiểm tra đã đăng nhập chưa
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    // Sau đó, kiểm tra có phải là admin không
    if ($_SESSION['role'] !== 'admin') {
        // Nếu không phải admin, đá về trang index với thông báo lỗi
        header("Location: index.php?error=admin_only");
        exit;
    }
}
?>