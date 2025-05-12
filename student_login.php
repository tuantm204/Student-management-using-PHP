<?php
session_start();
include 'config/database.php';

// Nếu đã đăng nhập, chuyển hướng đến trang chủ sinh viên
if (isset($_SESSION['student_id'])) {
    header("Location: student/dashboard.php");
    exit();
}
$error = '';

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'] ?? '';
    $password = $_POST['password'] ?? '';
    if (empty($student_id) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } else {
        try {
            $stmt = $conn->prepare("SELECT student_id, full_name, password FROM students WHERE student_id = :student_id");
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                if (password_verify($password, $student['password'])) {
                    $_SESSION['student_id'] = $student['student_id'];
                    $_SESSION['student_name'] = $student['full_name'];
                    $_SESSION['user_type'] = 'student';
                    header("Location: student/dashboard.php");
                    exit();
                } else {
                    $error = 'Mã sinh viên hoặc mật khẩu không đúng';
                }
            } else {
                $error = 'Mã sinh viên hoặc mật khẩu không đúng';
            }
        } catch(PDOException $e) {
            $error = 'Đã xảy ra lỗi: ' . $e->getMessage();
        }
    }
}
// Lấy 3 sinh viên có GPA cao nhất
try {
    $stmt = $conn->query("
        SELECT s.student_id, s.full_name, s.class, 
               AVG(g.score) as avg_score,
               SUM(sub.credit) as total_credits
        FROM students s
        JOIN grades g ON s.student_id = g.student_id
        JOIN subjects sub ON g.subject_id = sub.subject_id
        GROUP BY s.student_id, s.full_name, s.class
        ORDER BY avg_score DESC
        LIMIT 3
    ");
    $top_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_top = 'Lỗi khi lấy danh sách sinh viên xuất sắc: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống Quản lý Sinh viên</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 50px auto;
        }
        .top-students {
            margin-top: 30px;
        }
        .card-header {
            background-color: #007bff;
            color: white;
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center mt-4">
                <h2>Hệ thống Quản lý Sinh viên</h2>
            </div>
        </div>
        <!-- Top 3 sinh viên xuất sắc -->
        <div class="row top-students justify-content-center mt-5">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0 text-center">Top 3 Sinh viên xuất sắc</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error_top)): ?>
                            <div class="alert alert-danger"><?php echo $error_top; ?></div>
                        <?php elseif (empty($top_students)): ?>
                            <div class="alert alert-info">Chưa có dữ liệu điểm để xác định sinh viên xuất sắc.</div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($top_students as $index => $student): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card border-light shadow-sm">
                                            <div class="card-body text-center">
                                                <h5 class="card-title"><?php echo htmlspecialchars($student['full_name']); ?></h5>
                                                <p class="card-text"><strong>Mã SV:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
                                                <p class="card-text"><strong>Lớp:</strong> <?php echo htmlspecialchars($student['class']); ?></p>
                                                <p class="card-text"><strong>Điểm TB:</strong> <?php echo number_format($student['avg_score'], 2); ?></p>
                                                <p class="card-text"><strong>GPA:</strong> <?php echo number_format(($student['avg_score'] / 10 * 4), 2); ?></p>
                                            </div>
                                            <div class="card-footer text-muted text-center">
                                                <small>Hạng <?php echo $index + 1; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-12">
                <ul class="nav nav-tabs justify-content-center" id="loginTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="student-tab" data-toggle="tab" href="#student-login" role="tab" aria-controls="student-login" aria-selected="true">
                            <i class="fas fa-user-graduate"></i> Sinh viên
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="admin-tab" data-toggle="tab" href="#admin-login" role="tab" aria-controls="admin-login" aria-selected="false">
                            <i class="fas fa-user-shield"></i> Quản trị viên
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="tab-content" id="loginTabsContent">
            <!-- Đăng nhập sinh viên -->
            <div class="tab-pane fade show active" id="student-login" role="tabpanel" aria-labelledby="student-tab">
                <div class="login-container">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Đăng nhập Sinh viên</h4>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            
                            <form method="post" action="">
                                <div class="form-group">
                                    <label for="student_id">Mã sinh viên</label>
                                    <input type="text" class="form-control" id="student_id" name="student_id" required>
                                </div>
                                <div class="form-group">
                                    <label for="password">Mật khẩu</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <small class="form-text text-muted">Mật khẩu mặc định là ngày tháng năm sinh (ddMMyyyy)</small>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Đăng nhập admin -->
            <div class="tab-pane fade" id="admin-login" role="tabpanel" aria-labelledby="admin-tab">
                <div class="login-container">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Đăng nhập Quản trị viên</h4>
                        </div>
                        <div class="card-body">
                            <form method="post" action="login.php">
                                <div class="form-group">
                                    <label for="username">Tên đăng nhập</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="form-group">
                                    <label for="admin_password">Mật khẩu</label>
                                    <input type="password" class="form-control" id="admin_password" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<?php include 'templates/footer.php'; ?>
