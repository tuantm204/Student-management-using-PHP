<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../config/database.php';

// Xử lý xóa điểm
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $grade_id = $_GET['delete'];
    
    try {
        // Xóa điểm
        $stmt = $conn->prepare("DELETE FROM grades WHERE id = :id");
        $stmt->bindParam(':id', $grade_id);
        $stmt->execute();
        
        $delete_success = "Đã xóa điểm thành công.";
    } catch(PDOException $e) {
        $delete_error = "Lỗi khi xóa điểm: " . $e->getMessage();
    }
}

// Lấy danh sách điểm
try {
    $stmt = $conn->query("SELECT g.id, g.student_id, s.full_name, g.subject_id, sub.subject_name, sem.name as semester_name, g.score 
                          FROM grades g
                          JOIN students s ON g.student_id = s.student_id
                          JOIN subjects sub ON g.subject_id = sub.subject_id
                          JOIN semesters sem ON g.semester_id = sem.semester_id
                          ORDER BY g.id DESC");
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Lỗi khi lấy danh sách điểm: " . $e->getMessage();
}

// Hiển thị trang
include '../templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Danh sách Điểm số</h4>
                    <a href="add.php" class="btn btn-light"><i class="fas fa-plus"></i> Thêm Điểm</a>
                </div>
                <div class="card-body">
                    <?php if (isset($delete_success)): ?>
                        <div class="alert alert-success"><?php echo $delete_success; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($delete_error)): ?>
                        <div class="alert alert-danger"><?php echo $delete_error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Mã SV</th>
                                        <th>Tên sinh viên</th>
                                        <th>Mã môn học</th>
                                        <th>Tên môn học</th>
                                        <th>Học kỳ</th>
                                        <th>Điểm</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($grades) > 0): ?>
                                        <?php foreach ($grades as $grade): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($grade['id']); ?></td>
                                                <td><?php echo htmlspecialchars($grade['student_id']); ?></td>
                                                <td><?php echo htmlspecialchars($grade['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($grade['subject_id']); ?></td>
                                                <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                                                <td><?php echo htmlspecialchars($grade['semester_name']); ?></td>
                                                <td><?php echo htmlspecialchars($grade['score']); ?></td>
                                                <td>
                                                    <a href="edit.php?id=<?php echo $grade['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="index.php?delete=<?php echo $grade['id']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Bạn có chắc chắn muốn xóa điểm này?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">Không có điểm nào</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
