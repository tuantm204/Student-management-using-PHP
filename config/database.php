<?php
// Thông tin kết nối cơ sở dữ liệu
$host = 'localhost';
$dbname = 'student_management';
$username = 'root';
$password = '';

try {
    // Tạo kết nối PDO - thử kết nối không có tên database trước
    $conn = new PDO("mysql:host=$host", $username, $password);
    // Thiết lập chế độ lỗi
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Kiểm tra xem database đã tồn tại chưa
    $stmt = $conn->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
    $dbExists = (bool)$stmt->fetchColumn();
    
    if (!$dbExists) {
        // Tạo database nếu chưa tồn tại
        $conn->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
        echo "<script>console.log('Đã tạo cơ sở dữ liệu $dbname');</script>";
    }
    
    // Kết nối lại với database đã chọn
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    echo "Lỗi kết nối: " . $e->getMessage();
    die();
}
?>
