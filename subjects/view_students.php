<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../config/database.php';
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$subject_id = $_GET['id'];

// Lấy thông tin môn học
try {
    $stmt = $conn->prepare("SELECT * FROM subjects WHERE subject_id = :subject_id");
    $stmt->bindParam(':subject_id', $subject_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        header("Location: index.php");
        exit();
    }
    
    $subject = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}

// Lấy danh sách học kỳ
try {
    $stmt = $conn->query("SELECT * FROM semesters ORDER BY year DESC, name");
    $semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Lỗi khi lấy danh sách học kỳ: ' . $e->getMessage();
}

// Lọc theo học kỳ nếu có
$semester_id = isset($_GET['semester_id']) ? $_GET['semester_id'] : '';

// Lấy danh sách sinh viên có điểm cho môn học này
try {
    $query = "SELECT s.student_id, s.full_name, s.class, g.score, sem.name as semester_name, g.id as grade_id 
              FROM students s
              JOIN grades g ON s.student_id = g.student_id
              JOIN semesters sem ON g.semester_id = sem.semester_id
              WHERE g.subject_id = :subject_id";
    
    if (!empty($semester_id)) {
        $query .= " AND g.semester_id = :semester_id";
    }
    $query .= " ORDER BY s.class, s.full_name";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':subject_id', $subject_id);
    if (!empty($semester_id)) {
        $stmt->bindParam(':semester_id', $semester_id);
    }
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Lỗi khi lấy danh sách sinh viên: ' . $e->getMessage();
}

// Hiển thị trang
include '../templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Danh sách sinh viên học môn: <?php echo htmlspecialchars($subject['subject_name']); ?></h4>
                    <div>
                        <a href="add_student.php?id=<?php echo $subject_id; ?>" class="btn btn-success mr-2">
                            <i class="fas fa-user-plus"></i> Thêm sinh viên vào lớp
                        </a>
                        <a href="export_students.php?id=<?php echo $subject_id; ?><?php echo !empty($semester_id) ? '&semester_id='.$semester_id : ''; ?>" class="btn btn-warning mr-2">
                            <i class="fas fa-file-excel"></i> Xuất Excel
                        </a>
                        <a href="index.php" class="btn btn-light">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Thông tin môn học</h5>
                                        <p><strong>Mã môn học:</strong> <?php echo htmlspecialchars($subject['subject_id']); ?></p>
                                        <p><strong>Tên môn học:</strong> <?php echo htmlspecialchars($subject['subject_name']); ?></p>
                                        <p><strong>Số tín chỉ:</strong> <?php echo htmlspecialchars($subject['credit']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Lọc theo học kỳ</h5>
                                        <form method="get" action="">
                                            <input type="hidden" name="id" value="<?php echo $subject_id; ?>">
                                            <div class="form-group">
                                                <select class="form-control" name="semester_id" onchange="this.form.submit()">
                                                    <option value="">-- Tất cả học kỳ --</option>
                                                    <?php foreach ($semesters as $semester): ?>
                                                        <option value="<?php echo $semester['semester_id']; ?>" <?php echo ($semester_id == $semester['semester_id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($semester['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Mã SV</th>
                                        <th>Họ và tên</th>
                                        <th>Lớp</th>
                                        <th>Học kỳ</th>
                                        <th>Điểm</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($students) > 0): ?>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($student['class']); ?></td>
                                                <td><?php echo htmlspecialchars($student['semester_name']); ?></td>
                                                <td>
                                                    <?php 
                                                        echo htmlspecialchars($student['score']); 
                                                        // Hiển thị xếp loại
                                                        $score = $student['score'];
                                                        if ($score >= 8.5) echo ' <span class="badge badge-success">Giỏi</span>';
                                                        elseif ($score >= 7.0) echo ' <span class="badge badge-primary">Khá</span>';
                                                        elseif ($score >= 5.5) echo ' <span class="badge badge-info">Trung bình</span>';
                                                        elseif ($score >= 4.0) echo ' <span class="badge badge-warning">Yếu</span>';
                                                        else echo ' <span class="badge badge-danger">Kém</span>';
                                                    ?>
                                                </td>
                                                <td>
                                                    <a href="../grades/edit.php?id=<?php echo $student['grade_id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-edit"></i> Sửa điểm
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Không có sinh viên nào học môn này<?php echo !empty($semester_id) ? ' trong học kỳ đã chọn' : ''; ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Thống kê điểm -->
                        <?php if (count($students) > 0): ?>
                            <div class="mt-4">
                                <h5>Thống kê điểm số</h5>
                                <?php
                                    // Tính toán thống kê
                                    $total_students = count($students);
                                    $sum_score = 0;
                                    $count_excellent = 0; // >= 8.5
                                    $count_good = 0;      // >= 7.0
                                    $count_average = 0;   // >= 5.5
                                    $count_below = 0;     // >= 4.0
                                    $count_fail = 0;      // < 4.0
                                    
                                    foreach ($students as $student) {
                                        $score = $student['score'];
                                        $sum_score += $score;
                                        
                                        if ($score >= 8.5) $count_excellent++;
                                        elseif ($score >= 7.0) $count_good++;
                                        elseif ($score >= 5.5) $count_average++;
                                        elseif ($score >= 4.0) $count_below++;
                                        else $count_fail++;
                                    }
                                    
                                    $avg_score = $sum_score / $total_students;
                                ?>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-bordered">
                                            <tr>
                                                <th>Tổng số sinh viên</th>
                                                <td><?php echo $total_students; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Điểm trung bình</th>
                                                <td><?php echo number_format($avg_score, 2); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-bordered">
                                            <tr>
                                                <th>Giỏi (>= 8.5)</th>
                                                <td><?php echo $count_excellent; ?> (<?php echo number_format(($count_excellent / $total_students) * 100, 1); ?>%)</td>
                                            </tr>
                                            <tr>
                                                <th>Khá (>= 7.0)</th>
                                                <td><?php echo $count_good; ?> (<?php echo number_format(($count_good / $total_students) * 100, 1); ?>%)</td>
                                            </tr>
                                            <tr>
                                                <th>Trung bình (>= 5.5)</th>
                                                <td><?php echo $count_average; ?> (<?php echo number_format(($count_average / $total_students) * 100, 1); ?>%)</td>
                                            </tr>
                                            <tr>
                                                <th>Yếu (>= 4.0)</th>
                                                <td><?php echo $count_below; ?> (<?php echo number_format(($count_below / $total_students) * 100, 1); ?>%)</td>
                                            </tr>
                                            <tr>
                                                <th>Kém (< 4.0)</th>
                                                <td><?php echo $count_fail; ?> (<?php echo number_format(($count_fail / $total_students) * 100, 1); ?>%)</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
