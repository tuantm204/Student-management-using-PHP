<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../config/database.php';

$error = '';
$success = '';

// Lấy danh sách sinh viên
try {
    $stmt = $conn->query("SELECT student_id, full_name FROM students ORDER BY full_name");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Lỗi khi lấy danh sách sinh viên: ' . $e->getMessage();
}

// Lấy danh sách môn học
try {
    $stmt = $conn->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name");
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Lỗi khi lấy danh sách môn học: ' . $e->getMessage();
}

// Lấy danh sách học kỳ
try {
    $stmt = $conn->query("SELECT semester_id, name FROM semesters ORDER BY year DESC, name");
    $semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Lỗi khi lấy danh sách học kỳ: ' . $e->getMessage();
}

// Xử lý thêm điểm
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'] ?? '';
    $subject_id = $_POST['subject_id'] ?? '';
    $semester_id = $_POST['semester_id'] ?? '';
    $score = $_POST['score'] ?? '';
    
    // Kiểm tra dữ liệu
    if (empty($student_id) || empty($subject_id) || empty($semester_id) || $score === '') {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } elseif (!is_numeric($score) || $score < 0 || $score > 10) {
        $error = 'Điểm phải là số từ 0 đến 10';
    } else {
        try {
            // Kiểm tra xem đã có điểm cho sinh viên, môn học và học kỳ này chưa
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM grades 
                                         WHERE student_id = :student_id 
                                         AND subject_id = :subject_id 
                                         AND semester_id = :semester_id");
            $check_stmt->bindParam(':student_id', $student_id);
            $check_stmt->bindParam(':subject_id', $subject_id);
            $check_stmt->bindParam(':semester_id', $semester_id);
            $check_stmt->execute();
            
            if ($check_stmt->fetchColumn() > 0) {
                $error = 'Sinh viên này đã có điểm cho môn học và học kỳ này';
            } else {
                // Thêm điểm mới
                $stmt = $conn->prepare("INSERT INTO grades (student_id, subject_id, semester_id, score) 
                                        VALUES (:student_id, :subject_id, :semester_id, :score)");
                
                $stmt->bindParam(':student_id', $student_id);
                $stmt->bindParam(':subject_id', $subject_id);
                $stmt->bindParam(':semester_id', $semester_id);
                $stmt->bindParam(':score', $score);
                
                $stmt->execute();
                
                $success = 'Thêm điểm thành công';
            }
        } catch(PDOException $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Hiển thị trang
include '../templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Thêm Điểm mới</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="form-group">
                            <label for="student_id">Sinh viên <span class="text-danger">*</span></label>
                            <select class="form-control" id="student_id" name="student_id" required>
                                <option value="">-- Chọn sinh viên --</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['student_id']; ?>">
                                        <?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject_id">Môn học <span class="text-danger">*</span></label>
                            <select class="form-control" id="subject_id" name="subject_id" required>
                                <option value="">-- Chọn môn học --</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['subject_id']; ?>">
                                        <?php echo htmlspecialchars($subject['subject_id'] . ' - ' . $subject['subject_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="semester_id">Học kỳ <span class="text-danger">*</span></label>
                            <select class="form-control" id="semester_id" name="semester_id" required>
                                <option value="">-- Chọn học kỳ --</option>
                                <?php foreach ($semesters as $semester): ?>
                                    <option value="<?php echo $semester['semester_id']; ?>">
                                        <?php echo htmlspecialchars($semester['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="score">Điểm <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="score" name="score" min="0" max="10" step="0.1" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Thêm điểm</button>
                            <a href="index.php" class="btn btn-secondary">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
