<?php
session_start();
$_SESSION = array();
// Hủy phiên session
session_destroy();
// Xác định đường dẫn gốc của ứng dụng
$root_path = dirname($_SERVER['PHP_SELF']);
if($root_path == '/' || $root_path == '\\') {
    $root_path = '';
}
// Chuyển hướng về trang đăng nhập
header("Location: $root_path/student_login.php");
exit();
?>
