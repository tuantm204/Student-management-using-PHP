<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['student_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../student_login.php");
    exit();
}

include '../config/database.php';

$student_id = $_SESSION['student_id'];
$error = '';
$success = '';

// Lấy thông tin sinh viên
try {
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = :student_id");
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        header("Location: ../student_login.php");
        exit();
    }
    
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}

// Xử lý đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Kiểm tra dữ liệu
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } elseif ($new_password != $confirm_password) {
        $error = 'Mật khẩu mới và xác nhận mật khẩu không khớp';
    } elseif (strlen($new_password) < 6) {
        $error = 'Mật khẩu mới phải có ít nhất 6 ký tự';
    } else {
        try {
            // Kiểm tra mật khẩu hiện tại
            if (!password_verify($current_password, $student['password'])) {
                $error = 'Mật khẩu hiện tại không đúng';
            } else {
                // Cập nhật mật khẩu mới
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("UPDATE students SET password = :password WHERE student_id = :student_id");
                $stmt->bindParam(':password', $password_hash);
                $stmt->bindParam(':student_id', $student_id);
                $stmt->execute();
                
                $success = 'Đổi mật khẩu thành công';
            }
        } catch(PDOException $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Hiển thị trang
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi mật khẩu - Hệ thống Quản lý Sinh viên</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card-dashboard {
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-dashboard .card-header {
            border-radius: 10px 10px 0 0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Hệ thống Quản lý Sinh viên</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="subjects.php">Bảng điểm</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="internship.php">Đăng ký thực tập</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown active">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown">
                            <?php echo htmlspecialchars($student['full_name']); ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="profile.php">Thông tin cá nhân</a>
                            <a class="dropdown-item active" href="change_password.php">Đổi mật khẩu</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="../logout.php">Đăng xuất</a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2>Đổi mật khẩu</h2>
                <p>Cập nhật mật khẩu đăng nhập của bạn</p>
            </div>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card card-dashboard">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-key"></i> Đổi mật khẩu
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="form-group">
                                <label for="current_password">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">Mật khẩu mới <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <small class="form-text text-muted">Mật khẩu phải có ít nhất 6 ký tự</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card card-dashboard">
                    <div class="card-header bg-info text-white">
                        <i class="fas fa-info-circle"></i> Lưu ý
                    </div>
                    <div class="card-body">
                        <p>Mật khẩu mặc định ban đầu là ngày tháng năm sinh của bạn (ddMMyyyy).</p>
                        <p>Để bảo mật tài khoản, bạn nên đổi mật khẩu mặc định và không chia sẻ mật khẩu với người khác.</p>
                        <p>Nếu quên mật khẩu, vui lòng liên hệ với quản trị viên để được hỗ trợ.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
