<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}
include '../config/database.php';

// Kiểm tra ID sinh viên
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}
$student_id = $_GET['id'];

// Lấy thông tin sinh viên
try {
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = :student_id");
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        header("Location: index.php");
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
        $cumulative_grade_points += ($grade['score'] / 10 * 4) * $grade['credit']; 
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
include '../templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Bảng điểm - <?php echo htmlspecialchars($student['full_name']); ?></h4>
                    <div>
                        <a href="gpa_overview.php?id=<?php echo $student_id; ?>" class="btn btn-info mr-2">
                            <i class="fas fa-chart-line"></i> Xem tổng quan GPA
                        </a>
                        <a href="index.php" class="btn btn-light">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php else: ?>
                        <div class="row mb-4">
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
                                    <div class="card-body">
                                        <h5 class="card-title">Điểm trung bình tích lũy (GPA)</h5>
                                        <div class="text-center">
                                            <h2 class="display-4 font-weight-bold text-primary">
                                                <?php echo number_format($cumulative_gpa, 2); ?>
                                            </h2>
                                            <p class="lead"><?php echo getGPALevel($cumulative_gpa); ?></p>
                                            <p>Tổng số tín chỉ: <?php echo $cumulative_credits; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (count($semesters) > 0): ?>
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
                                <!-- Nếu có học kỳ, hiển thị thanh tab để chuyển giữa các học kỳ -->
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
                        <?php else: ?>
                            <div class="alert alert-info">
                                Sinh viên chưa có điểm cho bất kỳ môn học nào.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
