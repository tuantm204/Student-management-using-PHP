<?php
session_start();

if (!isset($_SESSION['student_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../student_login.php");
    exit();
}
include '../config/database.php';

$student_id = $_SESSION['student_id'];
$error = '';
$success = '';

try {
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = :student_id");
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}

try {
    $stmt = $conn->prepare("
        SELECT AVG(g.score) as avg_score
        FROM grades g
        WHERE g.student_id = :student_id
    ");
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $avg_score = $result['avg_score'] ?? 0;
    $gpa = ($avg_score / 10) * 4; 
} catch(PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}

// Lấy danh sách công ty thực tập
try {
    $stmt = $conn->query("SELECT * FROM companies ORDER BY company_name");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}

// Lấy thông tin đăng ký thực tập hiện tại
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
    $is_locked = false;
    if (!empty($registrations)) {
        $is_locked = $registrations[0]['is_locked'];
    }
} catch(PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}

try {
    $stmt = $conn->prepare("
        SELECT r.*, c.company_name, c.position
        FROM internship_results r
        JOIN companies c ON r.company_id = c.company_id
        WHERE r.student_id = :student_id
    ");
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $internship_result = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}

// Xử lý đăng ký nguyện vọng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    if (!empty($internship_result)) {
        $error = 'Bạn đã được xét tuyển thực tập, không thể đăng ký lại.';
    } 
    // Kiểm tra xem đăng ký có bị khóa không
    elseif (!empty($registrations) && $is_locked) {
        $error = 'Đăng ký của bạn đã bị khóa, không thể thay đổi. Vui lòng liên hệ quản trị viên nếu cần mở khóa.';
    } else {
        $priority1 = $_POST['priority1'] ?? '';
        $priority2 = $_POST['priority2'] ?? '';
        $priority3 = $_POST['priority3'] ?? '';
        if (empty($priority1)) {
            $error = 'Vui lòng chọn ít nhất nguyện vọng 1';
        } elseif ($priority1 == $priority2 || ($priority1 == $priority3 && !empty($priority3)) || ($priority2 == $priority3 && !empty($priority2) && !empty($priority3))) {
            $error = 'Các nguyện vọng không được trùng nhau';
        } else {
            try {
                $conn->beginTransaction();
                $stmt = $conn->prepare("DELETE FROM internship_registrations WHERE student_id = :student_id");
                $stmt->bindParam(':student_id', $student_id);
                $stmt->execute();
                $stmt = $conn->prepare("
                    INSERT INTO internship_registrations (student_id, company_id, priority, is_locked) 
                    VALUES (:student_id, :company_id, 1, TRUE)
                ");
                $stmt->bindParam(':student_id', $student_id);
                $stmt->bindParam(':company_id', $priority1);
                $stmt->execute();
                if (!empty($priority2)) {
                    $stmt = $conn->prepare("
                        INSERT INTO internship_registrations (student_id, company_id, priority, is_locked) 
                        VALUES (:student_id, :company_id, 2, TRUE)
                    ");
                    $stmt->bindParam(':student_id', $student_id);
                    $stmt->bindParam(':company_id', $priority2);
                    $stmt->execute();
                }
                if (!empty($priority3)) {
                    $stmt = $conn->prepare("
                        INSERT INTO internship_registrations (student_id, company_id, priority, is_locked) 
                        VALUES (:student_id, :company_id, 3, TRUE)
                    ");
                    $stmt->bindParam(':student_id', $student_id);
                    $stmt->bindParam(':company_id', $priority3);
                    $stmt->execute();
                }
                
                $conn->commit();
                
                $success = 'Đăng ký nguyện vọng thực tập thành công';
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
                if (!empty($registrations)) {
                    $is_locked = $registrations[0]['is_locked'];
                }
                
            } catch(PDOException $e) {
                $conn->rollBack();
                $error = 'Lỗi: ' . $e->getMessage();
            }
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
    <title>Đăng ký thực tập - Hệ thống Quản lý Sinh viên</title>
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
                    <li class="nav-item active">
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
                <h2>Đăng ký thực tập</h2>
                <p>Sinh viên: <strong><?php echo htmlspecialchars($student['full_name']); ?></strong> | GPA: <strong><?php echo number_format($gpa, 2); ?></strong></p>
            </div>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($internship_result)): ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-dashboard">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-check-circle"></i> Kết quả xét tuyển thực tập
                        </div>
                        <div class="card-body">
                            <div class="alert alert-success">
                                <h5>Chúc mừng! Bạn đã được xét tuyển thực tập.</h5>
                                <hr>
                                <p><strong>Công ty:</strong> <?php echo htmlspecialchars($internship_result['company_name']); ?></p>
                                <p><strong>Vị trí:</strong> <?php echo htmlspecialchars($internship_result['position']); ?></p>
                                <p><strong>Nguyện vọng:</strong> <?php echo htmlspecialchars($internship_result['priority']); ?></p>
                                <p><strong>Ngày xét tuyển:</strong> <?php echo date('d/m/Y', strtotime($internship_result['result_date'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="card card-dashboard">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-briefcase"></i> Đăng ký nguyện vọng thực tập
                        </div>
                        <div class="card-body">
                            <?php if (!empty($registrations)): ?>
                                <div class="alert alert-info">
                                    <h5>Thông tin đăng ký hiện tại</h5>
                                    <ul class="list-group mt-3">
                                        <?php foreach ($registrations as $reg): ?>
                                            <li class="list-group-item">
                                                <strong>Nguyện vọng <?php echo $reg['priority']; ?>:</strong> 
                                                <?php echo htmlspecialchars($reg['company_name']); ?> - 
                                                <?php echo htmlspecialchars($reg['position']); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <div class="mt-3">
                                        <p>Trạng thái: 
                                            <?php if ($is_locked): ?>
                                                <span class="badge badge-warning">Đã khóa (không thể thay đổi)</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">Có thể chỉnh sửa</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (empty($registrations) || !$is_locked): ?>
                                <form method="post" action="">
                                    <div class="form-group">
                                        <label for="priority1"><strong>Nguyện vọng 1 (bắt buộc)</strong></label>
                                        <select class="form-control" id="priority1" name="priority1" required>
                                            <option value="">-- Chọn công ty --</option>
                                            <?php foreach ($companies as $company): ?>
                                                <option value="<?php echo $company['company_id']; ?>">
                                                    <?php echo htmlspecialchars($company['company_name'] . ' - ' . $company['position']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="priority2">Nguyện vọng 2 (không bắt buộc)</label>
                                        <select class="form-control" id="priority2" name="priority2">
                                            <option value="">-- Chọn công ty --</option>
                                            <?php foreach ($companies as $company): ?>
                                                <option value="<?php echo $company['company_id']; ?>">
                                                    <?php echo htmlspecialchars($company['company_name'] . ' - ' . $company['position']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="priority3">Nguyện vọng 3 (không bắt buộc)</label>
                                        <select class="form-control" id="priority3" name="priority3">
                                            <option value="">-- Chọn công ty --</option>
                                            <?php foreach ($companies as $company): ?>
                                                <option value="<?php echo $company['company_id']; ?>">
                                                    <?php echo htmlspecialchars($company['company_name'] . ' - ' . $company['position']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="alert alert-warning">
                                        <strong>Lưu ý:</strong> Sau khi đăng ký, bạn sẽ không thể thay đổi nguyện vọng trừ khi được quản trị viên mở khóa.
                                    </div>
                                    
                                    <button type="submit" name="register" class="btn btn-primary">Đăng ký nguyện vọng</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card card-dashboard">
                        <div class="card-header bg-info text-white">
                            <i class="fas fa-building"></i> Danh sách công ty thực tập
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Mã công ty</th>
                                            <th>Tên công ty</th>
                                            <th>Vị trí</th>
                                            <th>Chỉ tiêu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($companies as $company): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($company['company_id']); ?></td>
                                                <td><?php echo htmlspecialchars($company['company_name']); ?></td>
                                                <td><?php echo htmlspecialchars($company['position']); ?></td>
                                                <td><?php echo htmlspecialchars($company['quota']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
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
