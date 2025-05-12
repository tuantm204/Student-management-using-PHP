<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}
include '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject_id = $_POST['subject_id'] ?? '';
    $subject_name = $_POST['subject_name'] ?? '';
    $credit = $_POST['credit'] ?? '';
    
    if (empty($subject_id) || empty($subject_name) || empty($credit)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } elseif (!is_numeric($credit) || $credit <= 0) {
        $error = 'Số tín chỉ phải là số dương';
    } else {
        try {
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM subjects WHERE subject_id = :subject_id");
            $check_stmt->bindParam(':subject_id', $subject_id);
            $check_stmt->execute();
            
            if ($check_stmt->fetchColumn() > 0) {
                $error = 'Mã môn học đã tồn tại trong hệ thống';
            } else {
                $stmt = $conn->prepare("INSERT INTO subjects (subject_id, subject_name, credit) 
                                        VALUES (:subject_id, :subject_name, :credit)");
                
                $stmt->bindParam(':subject_id', $subject_id);
                $stmt->bindParam(':subject_name', $subject_name);
                $stmt->bindParam(':credit', $credit);
                
                $stmt->execute();
                
                $success = 'Thêm môn học thành công';
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
                    <h4 class="mb-0">Thêm Môn học mới</h4>
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
                            <label for="subject_id">Mã môn học <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="subject_id" name="subject_id" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject_name">Tên môn học <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="subject_name" name="subject_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="credit">Số tín chỉ <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="credit" name="credit" min="1" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Thêm môn học</button>
                            <a href="index.php" class="btn btn-secondary">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
