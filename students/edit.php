<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}
include '../config/database.php';
$error = '';
$success = '';

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

// Xử lý cập nhật sinh viên
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $class = $_POST['class'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    // Kiểm tra dữ liệu
    if (empty($full_name) || empty($dob) || empty($gender) || empty($class)) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
    } else {
        try {
            // Cập nhật thông tin sinh viên
            $stmt = $conn->prepare("UPDATE students SET 
                                    full_name = :full_name, 
                                    dob = :dob, 
                                    gender = :gender, 
                                    class = :class, 
                                    email = :email, 
                                    phone = :phone 
                                    WHERE student_id = :student_id");
            
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':dob', $dob);
            $stmt->bindParam(':gender', $gender);
            $stmt->bindParam(':class', $class);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();       
            $success = 'Cập nhật thông tin sinh viên thành công';
            // Cập nhật lại thông tin sinh viên sau khi sửa
            $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = :student_id");
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
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
                    <h4 class="mb-0">Chỉnh sửa thông tin Sinh viên</h4>
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
                            <label for="student_id">Mã sinh viên</label>
                            <input type="text" class="form-control" id="student_id" value="<?php echo htmlspecialchars($student['student_id']); ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="full_name">Họ và tên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="dob">Ngày sinh <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="dob" name="dob" value="<?php echo $student['dob']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="gender">Giới tính <span class="text-danger">*</span></label>
                            <select class="form-control" id="gender" name="gender" required>
                                <option value="">-- Chọn giới tính --</option>
                                <option value="Nam" <?php echo ($student['gender'] == 'Nam') ? 'selected' : ''; ?>>Nam</option>
                                <option value="Nữ" <?php echo ($student['gender'] == 'Nữ') ? 'selected' : ''; ?>>Nữ</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="class">Lớp <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="class" name="class" value="<?php echo htmlspecialchars($student['class']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Số điện thoại</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Cập nhật</button>
                            <a href="index.php" class="btn btn-secondary">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
