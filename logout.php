<?php
require_once 'db.php';
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $conn->query("UPDATE users SET remember_token = NULL WHERE id = $uid");
}
session_unset();
session_destroy();
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, "/");
}

header("Location: login.php");
exit;
?>
