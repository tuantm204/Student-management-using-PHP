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
$error = '';
$success = '';

// Lấy danh sách sinh viên chưa có điểm cho môn học này
try {
    $stmt = $conn->prepare("
        SELECT s.* 
        FROM students s
        WHERE s.student_id NOT IN (
            SELECT g.student_id 
            FROM grades g 
            WHERE g.subject_id = :subject_id
        )
        ORDER BY s.class, s.full_name
    ");
    $stmt->bindParam(':subject_id', $subject_id);
    $stmt->execute();
    $available_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Lỗi khi lấy danh sách sinh viên: ' . $e->getMessage();
}

// Lấy danh sách học kỳ
try {
    $stmt = $conn->query("SELECT * FROM semesters ORDER BY year DESC, name");
    $semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Lỗi khi lấy danh sách học kỳ: ' . $e->getMessage();
}

// Xử lý thêm sinh viên vào môn học
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_ids = $_POST['student_ids'] ?? [];
    $semester_id = $_POST['semester_id'] ?? '';
    $score = $_POST['score'] ?? '';
    
    // Kiểm tra dữ liệu
    if (empty($student_ids)) {
        $error = 'Vui lòng chọn ít nhất một sinh viên';
    } elseif (empty($semester_id)) {
        $error = 'Vui lòng chọn học kỳ';
    } elseif ($score === '' || !is_numeric($score) || $score < 0 || $score > 10) {
        $error = 'Điểm phải là số từ 0 đến 10';
    } else {
        try {
            $conn->beginTransaction();
            $stmt = $conn->prepare("INSERT INTO grades (student_id, subject_id, semester_id, score) VALUES (:student_id, :subject_id, :semester_id, :score)");
            $success_count = 0;
            foreach ($student_ids as $student_id) {
                $stmt->bindParam(':student_id', $student_id);
                $stmt->bindParam(':subject_id', $subject_id);
                $stmt->bindParam(':semester_id', $semester_id);
                $stmt->bindParam(':score', $score);
                $stmt->execute();
                $success_count++;
            }
            $conn->commit();
            $success = "Đã thêm $success_count sinh viên vào môn học thành công";
            // Cập nhật lại danh sách sinh viên khả dụng
            $stmt = $conn->prepare("
                SELECT s.* 
                FROM students s
                WHERE s.student_id NOT IN (
                    SELECT g.student_id 
                    FROM grades g 
                    WHERE g.subject_id = :subject_id
                )
                ORDER BY s.class, s.full_name
            ");
            $stmt->bindParam(':subject_id', $subject_id);
            $stmt->execute();
            $available_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            $conn->rollBack();
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Hiển thị trang
include '../templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Thêm sinh viên vào môn: <?php echo htmlspecialchars($subject['subject_name']); ?></h4>
                    <div>
                        <a href="view_students.php?id=<?php echo $subject_id; ?>" class="btn btn-info mr-2">
                            <i class="fas fa-users"></i> Xem danh sách sinh viên
                        </a>
                        <a href="index.php" class="btn btn-light">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Thông tin môn học</h5>
                                <p><strong>Mã môn học:</strong> <?php echo htmlspecialchars($subject['subject_id']); ?></p>
                                <p><strong>Tên môn học:</strong> <?php echo htmlspecialchars($subject['subject_name']); ?></p>
                                <p><strong>Số tín chỉ:</strong> <?php echo htmlspecialchars($subject['credit']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (count($available_students) > 0): ?>
                        <form method="post" action="">
                            <div class="form-group">
                                <label for="semester_id">Chọn học kỳ <span class="text-danger">*</span></label>
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
                                <label for="score">Điểm số <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="score" name="score" min="0" max="10" step="0.1" required>
                                <small class="form-text text-muted">Nhập điểm từ 0 đến 10. Điểm này sẽ được áp dụng cho tất cả sinh viên được chọn.</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Chọn sinh viên <span class="text-danger">*</span></label>
                                <div class="mb-2">
                                    <button type="button" class="btn btn-sm btn-secondary" id="select-all">Chọn tất cả</button>
                                    <button type="button" class="btn btn-sm btn-secondary" id="deselect-all">Bỏ chọn tất cả</button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th width="50px">Chọn</th>
                                                <th>Mã SV</th>
                                                <th>Họ và tên</th>
                                                <th>Lớp</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($available_students as $student): ?>
                                                <tr>
                                                    <td class="text-center">
                                                        <input type="checkbox" name="student_ids[]" value="<?php echo $student['student_id']; ?>" class="student-checkbox">
                                                    </td>
                                                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['class']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Thêm sinh viên vào môn học</button>
                                <a href="view_students.php?id=<?php echo $subject_id; ?>" class="btn btn-secondary">Hủy</a>
                            </div>
                        </form>
                        
                        <script>
                            document.getElementById('select-all').addEventListener('click', function() {
                                var checkboxes = document.getElementsByClassName('student-checkbox');
                                for (var i = 0; i < checkboxes.length; i++) {
                                    checkboxes[i].checked = true;
                                }
                            });
                            
                            document.getElementById('deselect-all').addEventListener('click', function() {
                                var checkboxes = document.getElementsByClassName('student-checkbox');
                                for (var i = 0; i < checkboxes.length; i++) {
                                    checkboxes[i].checked = false;
                                }
                            });
                        </script>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Tất cả sinh viên đã được thêm vào môn học này hoặc chưa có sinh viên nào trong hệ thống.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
