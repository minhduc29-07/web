<?php
require_once 'db.php'; // Để dùng biến $conn

// Xóa Token trong Database (Để cookie cũ không còn tác dụng)
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $conn->query("UPDATE users SET remember_token = NULL WHERE id = $uid");
}

// Xóa Session
session_unset();
session_destroy();

// Xóa Cookie trình duyệt (Đặt thời gian về quá khứ)
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, "/");
}

header("Location: login.php");
exit;
?>
