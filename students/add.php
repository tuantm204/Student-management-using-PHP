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

// Xử lý thêm sinh viên
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $class = $_POST['class'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    // Kiểm tra dữ liệu
    if (empty($student_id) || empty($full_name) || empty($dob) || empty($gender) || empty($class)) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
    } else {
        try {
            // Kiểm tra mã sinh viên đã tồn tại chưa
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE student_id = :student_id");
            $check_stmt->bindParam(':student_id', $student_id);
            $check_stmt->execute();
            
            if ($check_stmt->fetchColumn() > 0) {
                $error = 'Mã sinh viên đã tồn tại trong hệ thống';
            } else {
                // Tạo mật khẩu 
                $password = date('dmY', strtotime($dob));
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO students (student_id, full_name, dob, gender, class, email, phone, password) 
                                        VALUES (:student_id, :full_name, :dob, :gender, :class, :email, :phone, :password)");
                // Gắn các giá trị lấy từ form vào câu truy vấn
                $stmt->bindParam(':student_id', $student_id);
                $stmt->bindParam(':full_name', $full_name);
                $stmt->bindParam(':dob', $dob);
                $stmt->bindParam(':gender', $gender);
                $stmt->bindParam(':class', $class);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':password', $password_hash);
                $stmt->execute();
                $success = 'Thêm sinh viên thành công';
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
                    <h4 class="mb-0">Thêm Sinh viên mới</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                            <br>
                            <strong>Lưu ý:</strong> Mật khẩu mặc định của sinh viên là ngày tháng năm sinh (định dạng ddMMyyyy).
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="form-group">
                            <label for="student_id">Mã sinh viên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="student_id" name="student_id" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="full_name">Họ và tên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="dob">Ngày sinh <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="dob" name="dob" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="gender">Giới tính <span class="text-danger">*</span></label>
                            <select class="form-control" id="gender" name="gender" required>
                                <option value="">-- Chọn giới tính --</option>
                                <option value="Nam">Nam</option>
                                <option value="Nữ">Nữ</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="class">Lớp <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="class" name="class" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Số điện thoại</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Thêm sinh viên</button>
                            <a href="index.php" class="btn btn-secondary">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
