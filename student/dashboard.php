<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['student_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../student_login.php");
    exit();
}

include '../config/database.php';

$student_id = $_SESSION['student_id'];

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

// Tính GPA
try {
    $stmt = $conn->prepare("
        SELECT AVG(g.score) as avg_score, SUM(s.credit) as total_credits
        FROM grades g
        JOIN subjects s ON g.subject_id = s.subject_id
        WHERE g.student_id = :student_id
    ");
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $avg_score = $result['avg_score'] ?? 0;
    $total_credits = $result['total_credits'] ?? 0;
    $gpa = ($avg_score / 10) * 4; // Chuyển đổi thang điểm 10 sang thang điểm 4
} catch(PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}

// Lấy thông tin đăng ký thực tập
try {
    $stmt = $conn->prepare("
        SELECT r.*, c.company_name, c.position
        FROM internship_registrations r
        JOIN companies c ON r.company_id = c.company_id
        WHERE r.student_id = :student_id
        ORDER BY r.priority
    ");
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}

// Lấy kết quả xét tuyển thực tập
try {
    $stmt = $conn->prepare("
        SELECT r.*, c.company_name, c.position
        FROM internship_results r
        JOIN companies c ON r.company_id = c.company_id
        WHERE r.student_id = :student_id
    ");
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}

// Hiển thị trang
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Sinh viên - Hệ thống Quản lý Sinh viên</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #f8f9fa;
        }
        .content {
            padding: 20px;
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
        .gpa-display {
            font-size: 2.5rem;
            font-weight: bold;
            color: #007bff;
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
                    <li class="nav-item active">
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
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown">
                            <?php echo htmlspecialchars($student['full_name']); ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="profile.php">Thông tin cá nhân</a>
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
                <h2>Dashboard Sinh viên</h2>
                <p>Chào mừng, <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>!</p>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card card-dashboard">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-user-graduate"></i> Thông tin sinh viên
                    </div>
                    <div class="card-body">
                        <p><strong>Mã sinh viên:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
                        <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($student['full_name']); ?></p>
                        <p><strong>Lớp:</strong> <?php echo htmlspecialchars($student['class']); ?></p>
                        <p><strong>Ngày sinh:</strong> <?php echo date('d/m/Y', strtotime($student['dob'])); ?></p>
                        <p><strong>Giới tính:</strong> <?php echo htmlspecialchars($student['gender']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email'] ?? 'Chưa cập nhật'); ?></p>
                        <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($student['phone'] ?? 'Chưa cập nhật'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card card-dashboard">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-chart-line"></i> Kết quả học tập
                    </div>
                    <div class="card-body text-center">
                        <h5>Điểm trung bình tích lũy (GPA)</h5>
                        <div class="gpa-display"><?php echo number_format($gpa, 2); ?>/4.0</div>
                        <p>Tổng số tín chỉ: <?php echo $total_credits; ?></p>
                        <hr>
                        <a href="subjects.php" class="btn btn-primary">Xem bảng điểm chi tiết</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card card-dashboard">
                    <div class="card-header bg-info text-white">
                        <i class="fas fa-briefcase"></i> Thực tập
                    </div>
                    <div class="card-body">
                        <?php if (!empty($result)): ?>
                            <div class="alert alert-success">
                                <h5>Kết quả xét tuyển thực tập</h5>
                                <p><strong>Công ty:</strong> <?php echo htmlspecialchars($result['company_name']); ?></p>
                                <p><strong>Vị trí:</strong> <?php echo htmlspecialchars($result['position']); ?></p>
                                <p><strong>Nguyện vọng:</strong> <?php echo htmlspecialchars($result['priority']); ?></p>
                                <p><strong>Ngày xét tuyển:</strong> <?php echo date('d/m/Y', strtotime($result['result_date'])); ?></p>
                            </div>
                        <?php elseif (!empty($registrations)): ?>
                            <h5>Đã đăng ký nguyện vọng thực tập</h5>
                            <ul class="list-group">
                                <?php foreach ($registrations as $reg): ?>
                                    <li class="list-group-item">
                                        <strong>Nguyện vọng <?php echo $reg['priority']; ?>:</strong> 
                                        <?php echo htmlspecialchars($reg['company_name']); ?> - 
                                        <?php echo htmlspecialchars($reg['position']); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="mt-3">
                                <p>Trạng thái: <span class="badge badge-warning">Đang chờ xét tuyển</span></p>
                            </div>
                        <?php else: ?>
                            <p>Bạn chưa đăng ký nguyện vọng thực tập.</p>
                            <a href="internship.php" class="btn btn-primary">Đăng ký ngay</a>
                        <?php endif; ?>
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
