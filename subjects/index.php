<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}
include '../config/database.php';

if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $subject_id = $_GET['delete'];
    
    try {
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM grades WHERE subject_id = :subject_id");
        $check_stmt->bindParam(':subject_id', $subject_id);
        $check_stmt->execute();
        $has_grades = $check_stmt->fetchColumn() > 0;
        
        if ($has_grades) {
            $delete_error = "Không thể xóa môn học này vì đã có điểm trong hệ thống.";
        } else {
            $stmt = $conn->prepare("DELETE FROM subjects WHERE subject_id = :subject_id");
            $stmt->bindParam(':subject_id', $subject_id);
            $stmt->execute();
            
            $delete_success = "Đã xóa môn học thành công.";
        }
    } catch(PDOException $e) {
        $delete_error = "Lỗi khi xóa môn học: " . $e->getMessage();
    }
}

// Xử lý tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
try {
    if (!empty($search)) {
        $stmt = $conn->prepare("SELECT * FROM subjects WHERE subject_id LIKE :search OR subject_name LIKE :search ORDER BY subject_id");
        $searchTerm = "%$search%";
        $stmt->bindParam(':search', $searchTerm);
        $stmt->execute();
    } else {
        $stmt = $conn->query("SELECT * FROM subjects ORDER BY subject_id");
    }
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Lỗi khi lấy danh sách môn học: " . $e->getMessage();
}

// Hiển thị trang
include '../templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <!-- PHẦN HEADER + TÌM KIẾM -->
                <div class="card-header bg-primary text-white">
                    <form method="GET" class="d-flex justify-content-between align-items-center flex-nowrap" style="gap: 10px; flex-wrap: nowrap;">
                        <h4 class="mb-0" style="white-space: nowrap;">Danh sách Môn học</h4>
                        <div class="d-flex align-items-center" style="gap: 10px; flex-grow: 1; justify-content: flex-end;">
                            <input type="text" name="search" class="form-control form-control-sm"
                                   placeholder="Tìm mã môn hoặc tên môn..." value="<?php echo htmlspecialchars($search); ?>"
                                   style="max-width: 200px;" />
                            <button type="submit" class="btn btn-light btn-sm">
                                <i class="fas fa-search"></i>
                            </button>
                            <a href="index.php" class="btn btn-secondary btn-sm" title="Xóa tìm kiếm">
                                <i class="fas fa-times"></i>
                            </a>
                            <a href="add.php" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i> Thêm Môn học
                            </a>
                        </div>
                    </form>
                </div>

                <!-- PHẦN THÔNG BÁO -->
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
                        <!-- BẢNG DỮ LIỆU -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Mã môn học</th>
                                        <th>Tên môn học</th>
                                        <th>Số tín chỉ</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($subjects) > 0): ?>
                                        <?php foreach ($subjects as $subject): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($subject['subject_id']); ?></td>
                                                <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                                <td><?php echo htmlspecialchars($subject['credit']); ?></td>
                                                <td>
                                                    <a href="view_students.php?id=<?php echo $subject['subject_id']; ?>" class="btn btn-sm btn-info" title="Xem sinh viên">
                                                        <i class="fas fa-users"></i>
                                                    </a>
                                                    <a href="edit.php?id=<?php echo $subject['subject_id']; ?>" class="btn btn-sm btn-info" title="Sửa">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="index.php?delete=<?php echo $subject['subject_id']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Bạn có chắc chắn muốn xóa môn học này?')" title="Xóa">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Không có môn học nào phù hợp.</td>
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