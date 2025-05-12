<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}
include '../config/database.php';

// Tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Xử lý xóa sinh viên
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $student_id = $_GET['delete'];
    try {
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM grades WHERE student_id = :student_id");
        $check_stmt->bindParam(':student_id', $student_id);
        $check_stmt->execute();
        $has_grades = $check_stmt->fetchColumn() > 0;
        if ($has_grades) {
            $delete_error = "Không thể xóa sinh viên này vì đã có điểm trong hệ thống.";
        } else {
            $stmt = $conn->prepare("DELETE FROM students WHERE student_id = :student_id");
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            $delete_success = "Đã xóa sinh viên thành công.";
        }
    } catch (PDOException $e) {
        $delete_error = "Lỗi khi xóa sinh viên: " . $e->getMessage();
    }
}

// Lấy danh sách sinh viên
try {
    if (!empty($search)) {
        $stmt = $conn->prepare("SELECT * FROM students WHERE student_id LIKE :search OR full_name LIKE :search ORDER BY student_id");
        $search_param = "%$search%";
        $stmt->bindParam(':search', $search_param);
        $stmt->execute();
    } else {
        $stmt = $conn->query("SELECT * FROM students ORDER BY student_id");
    }
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Lỗi khi lấy danh sách sinh viên: " . $e->getMessage();
}

// Hiển thị giao diện
include '../templates/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card" style="width: 100%;">
                <!-- Header với ô tìm kiếm và nút thêm -->
                <div class="card-header bg-primary text-white">
                    <form method="GET" class="d-flex justify-content-between align-items-center flex-nowrap" style="gap: 10px; flex-wrap: nowrap;">
                        <h4 class="mb-0" style="white-space: nowrap;">Danh sách Sinh viên</h4>
                        <div class="d-flex align-items-center" style="gap: 10px; flex-grow: 1; justify-content: flex-end;">
                            <input type="text" name="search" class="form-control form-control-sm"
                                placeholder="Tìm mã SV hoặc tên..." value="<?php echo htmlspecialchars($search); ?>"
                                style="max-width: 200px;" />
                            <button type="submit" class="btn btn-light btn-sm">
                                <i class="fas fa-search"></i>
                            </button>
                            <a href="index.php" class="btn btn-secondary btn-sm" title="Xóa tìm kiếm">
                                <i class="fas fa-times"></i>
                            </a>
                            <a href="add.php" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i> Thêm Sinh viên
                            </a>
                        </div>
                    </form>
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
                            <table class="table table-bordered table-hover" style="width: 100%; table-layout: auto;">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Mã SV</th>
                                        <th>Họ và tên</th>
                                        <th>Ngày sinh</th>
                                        <th>Giới tính</th>
                                        <th>Lớp</th>
                                        <th>Email</th>
                                        <th>Điện thoại</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($students) > 0): ?>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($student['dob'])); ?></td>
                                                <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                                <td><?php echo htmlspecialchars($student['class']); ?></td>
                                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                                <td>
                                                    <a href="view_subjects.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-primary" title="Xem môn học">
                                                        <i class="fas fa-book"></i>
                                                    </a>
                                                    <a href="edit.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-info" title="Chỉnh sửa">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="index.php?delete=<?php echo $student['student_id']; ?>"
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Bạn có chắc chắn muốn xóa sinh viên này?')" title="Xóa sinh viên">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">Không có sinh viên nào.</td>
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