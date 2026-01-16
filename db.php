<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Ho_Chi_Minh');

session_start();
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'shoe_store');

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
$conn->query("SET time_zone = '+07:00'");
$conn->autocommit(TRUE);
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_user'])) {   
    $cookie_data = explode(':', $_COOKIE['remember_user']);    
    if (count($cookie_data) == 2) {
        $uid = $conn->real_escape_string($cookie_data[0]);
        $token = $conn->real_escape_string($cookie_data[1]);
        $stmt = $conn->prepare("SELECT id, username, role, remember_token FROM users WHERE id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows == 1) {
            $user = $res->fetch_assoc();
            if ($user['remember_token'] === $token) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
            }
        }
        $stmt->close();
    }
}
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
