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

// Lấy danh sách học kỳ mà sinh viên đã học
try {
    $stmt = $conn->prepare("
        SELECT DISTINCT s.semester_id, s.name, s.year
        FROM grades g
        JOIN semesters s ON g.semester_id = s.semester_id
        WHERE g.student_id = :student_id
        ORDER BY s.year, s.name
    ");
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}

// Tính GPA tích lũy
$cumulative_credits = 0;
$cumulative_grade_points = 0;
$cumulative_gpa = 0;

try {
    $stmt = $conn->prepare("
        SELECT g.*, s.subject_name, s.credit
        FROM grades g
        JOIN subjects s ON g.subject_id = s.subject_id
        WHERE g.student_id = :student_id
    ");
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $all_grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($all_grades as $grade) {
        $cumulative_credits += $grade['credit'];
        $cumulative_grade_points += ($grade['score'] / 10 * 4) * $grade['credit']; // Chuyển đổi thang điểm 10 sang thang điểm 4
    }
    
    $cumulative_gpa = ($cumulative_credits > 0) ? ($cumulative_grade_points / $cumulative_credits) : 0;
} catch(PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}

// Hàm chuyển đổi điểm số sang xếp loại
function getGradeLevel($score) {
    if ($score >= 9.0) return 'Xuất sắc';
    if ($score >= 8.0) return 'Giỏi';
    if ($score >= 7.0) return 'Khá';
    if ($score >= 5.0) return 'Trung bình';
    if ($score >= 4.0) return 'Yếu';
    return 'Kém';
}

// Hàm chuyển đổi GPA sang xếp loại
function getGPALevel($gpa) {
    if ($gpa >= 3.6) return 'Xuất sắc';
    if ($gpa >= 3.2) return 'Giỏi';
    if ($gpa >= 2.5) return 'Khá';
    if ($gpa >= 2.0) return 'Trung bình';
    if ($gpa >= 1.0) return 'Yếu';
    return 'Kém';
}

// Hiển thị trang
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng điểm - Hệ thống Quản lý Sinh viên</title>
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
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item active">
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
                <h2>Bảng điểm sinh viên</h2>
                <p>Sinh viên: <strong><?php echo htmlspecialchars($student['full_name']); ?></strong> | Mã SV: <strong><?php echo htmlspecialchars($student['student_id']); ?></strong></p>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card card-dashboard">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-chart-line"></i> Tổng quan kết quả học tập
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Thông tin sinh viên</h5>
                                        <p><strong>Mã sinh viên:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
                                        <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($student['full_name']); ?></p>
                                        <p><strong>Lớp:</strong> <?php echo htmlspecialchars($student['class']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Điểm trung bình tích lũy (GPA)</h5>
                                        <div class="gpa-display"><?php echo number_format($cumulative_gpa, 2); ?>/4.0</div>
                                        <p class="lead"><?php echo getGPALevel($cumulative_gpa); ?></p>
                                        <p>Tổng số tín chỉ: <?php echo $cumulative_credits; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (count($semesters) > 0): ?>
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card card-dashboard">
                        <div class="card-header bg-info text-white">
                            <i class="fas fa-book"></i> Bảng điểm chi tiết
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-tabs" id="semesterTabs" role="tablist">
                                <?php foreach ($semesters as $index => $semester): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo ($index == 0) ? 'active' : ''; ?>" 
                                           id="semester-<?php echo $semester['semester_id']; ?>-tab" 
                                           data-toggle="tab" 
                                           href="#semester-<?php echo $semester['semester_id']; ?>" 
                                           role="tab" 
                                           aria-controls="semester-<?php echo $semester['semester_id']; ?>" 
                                           aria-selected="<?php echo ($index == 0) ? 'true' : 'false'; ?>">
                                            <?php echo htmlspecialchars($semester['name']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <div class="tab-content" id="semesterTabsContent">
                                <?php foreach ($semesters as $index => $semester): ?>
                                    <div class="tab-pane fade <?php echo ($index == 0) ? 'show active' : ''; ?>" 
                                         id="semester-<?php echo $semester['semester_id']; ?>" 
                                         role="tabpanel" 
                                         aria-labelledby="semester-<?php echo $semester['semester_id']; ?>-tab">
                                        
                                        <?php
                                        // Lấy danh sách môn học và điểm cho học kỳ này
                                        $stmt = $conn->prepare("
                                            SELECT g.*, s.subject_name, s.credit
                                            FROM grades g
                                            JOIN subjects s ON g.subject_id = s.subject_id
                                            WHERE g.student_id = :student_id AND g.semester_id = :semester_id
                                            ORDER BY s.subject_name
                                        ");
                                        $stmt->bindParam(':student_id', $student_id);
                                        $stmt->bindParam(':semester_id', $semester['semester_id']);
                                        $stmt->execute();
                                        $semester_grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        // Tính GPA cho học kỳ
                                        $total_credits = 0;
                                        $total_grade_points = 0;
                                        
                                        foreach ($semester_grades as $grade) {
                                            $total_credits += $grade['credit'];
                                            $total_grade_points += ($grade['score'] / 10 * 4) * $grade['credit'];
                                        }
                                        
                                        $semester_gpa = ($total_credits > 0) ? ($total_grade_points / $total_credits) : 0;
                                        ?>
                                        
                                        <div class="card mt-3">
                                            <div class="card-header bg-light">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h5 class="mb-0">Học kỳ: <?php echo htmlspecialchars($semester['name']); ?></h5>
                                                    </div>
                                                    <div class="col-md-6 text-right">
                                                        <h5 class="mb-0">GPA: <?php echo number_format($semester_gpa, 2); ?> (<?php echo getGPALevel($semester_gpa); ?>)</h5>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-hover">
                                                        <thead class="thead-light">
                                                            <tr>
                                                                <th>Mã môn học</th>
                                                                <th>Tên môn học</th>
                                                                <th>Số tín chỉ</th>
                                                                <th>Điểm số</th>
                                                                <th>Xếp loại</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if (count($semester_grades) > 0): ?>
                                                                <?php foreach ($semester_grades as $grade): ?>
                                                                    <tr>
                                                                        <td><?php echo htmlspecialchars($grade['subject_id']); ?></td>
                                                                        <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                                                                        <td><?php echo htmlspecialchars($grade['credit']); ?></td>
                                                                        <td><?php echo htmlspecialchars($grade['score']); ?></td>
                                                                        <td><?php echo getGradeLevel($grade['score']); ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <tr>
                                                                    <td colspan="5" class="text-center">Không có dữ liệu</td>
                                                                </tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                        <tfoot>
                                                            <tr class="table-info">
                                                                <td colspan="2" class="text-right"><strong>Tổng:</strong></td>
                                                                <td><strong><?php echo $total_credits; ?></strong></td>
                                                                <td colspan="2"><strong>GPA: <?php echo number_format($semester_gpa, 2); ?> (<?php echo getGPALevel($semester_gpa); ?>)</strong></td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        Bạn chưa có điểm cho bất kỳ môn học nào.
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
