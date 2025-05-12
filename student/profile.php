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

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    try {
        // Cập nhật thông tin sinh viên
        $stmt = $conn->prepare("UPDATE students SET 
                                email = :email, 
                                phone = :phone 
                                WHERE student_id = :student_id");
        
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':student_id', $student_id);
        
        $stmt->execute();
        
        $success = 'Cập nhật thông tin thành công';
        
        // Cập nhật lại thông tin sinh viên sau khi sửa
        $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = :student_id");
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = 'Lỗi: ' . $e->getMessage();
    }
}

// Hiển thị trang
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân - Hệ thống Quản lý Sinh viên</title>
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
                            <a class="dropdown-item active" href="profile.php">Thông tin cá nhân</a>
                            <a class="dropdown-item" href="change_password.php">Đổi mật khẩu</a>
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
                <h2>Thông tin cá nhân</h2>
                <p>Cập nhật thông tin liên lạc của bạn</p>
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
                        <i class="fas fa-user-edit"></i> Cập nhật thông tin
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="form-group row">
                                <label for="student_id" class="col-sm-3 col-form-label">Mã sinh viên</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control-plaintext" id="student_id" value="<?php echo htmlspecialchars($student['student_id']); ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="form-group row">
                                <label for="full_name" class="col-sm-3 col-form-label">Họ và tên</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control-plaintext" id="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="form-group row">
                                <label for="dob" class="col-sm-3 col-form-label">Ngày sinh</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control-plaintext" id="dob" value="<?php echo date('d/m/Y', strtotime($student['dob'])); ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="form-group row">
                                <label for="gender" class="col-sm-3 col-form-label">Giới tính</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control-plaintext" id="gender" value="<?php echo htmlspecialchars($student['gender']); ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="form-group row">
                                <label for="class" class="col-sm-3 col-form-label">Lớp</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control-plaintext" id="class" value="<?php echo htmlspecialchars($student['class']); ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="form-group row">
                                <label for="email" class="col-sm-3 col-form-label">Email</label>
                                <div class="col-sm-9">
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group row">
                                <label for="phone" class="col-sm-3 col-form-label">Số điện thoại</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group row">
                                <div class="col-sm-9 offset-sm-3">
                                    <button type="submit" class="btn btn-primary">Cập nhật thông tin</button>
                                </div>
                            </div>
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
                        <p>Thông tin cá nhân như mã sinh viên, họ tên, ngày sinh, giới tính và lớp không thể thay đổi. Nếu có sai sót, vui lòng liên hệ với quản trị viên.</p>
                        <p>Email và số điện thoại có thể cập nhật để thuận tiện cho việc liên lạc.</p>
                        <p>Để thay đổi mật khẩu, vui lòng sử dụng chức năng <a href="change_password.php">Đổi mật khẩu</a>.</p>
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
