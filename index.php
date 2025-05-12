<?php
session_start();

// Kiểm tra đăng nhập
if (isset($_SESSION['admin_id'])) {
    // Nếu đã đăng nhập với tư cách admin
    header("Location: admin/index.php");
    exit();
} elseif (isset($_SESSION['student_id'])) {
    // Nếu đã đăng nhập với tư cách sinh viên
    header("Location: student/dashboard.php");
    exit();
}

// Chuyển hướng đến trang đăng nhập sinh viên
header("Location: student_login.php");
exit();
?>
