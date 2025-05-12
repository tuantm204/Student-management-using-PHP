<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}
include '../config/database.php';

$error = '';
$success = '';
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$subject_id = $_GET['id'];
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
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject_name = $_POST['subject_name'] ?? '';
    $credit = $_POST['credit'] ?? '';
    
    if (empty($subject_name) || empty($credit)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } elseif (!is_numeric($credit) || $credit <= 0) {
        $error = 'Số tín chỉ phải là số dương';
    } else {
        try {
            $stmt = $conn->prepare("UPDATE subjects SET 
                                    subject_name = :subject_name, 
                                    credit = :credit 
                                    WHERE subject_id = :subject_id");
            
            $stmt->bindParam(':subject_name', $subject_name);
            $stmt->bindParam(':credit', $credit);
            $stmt->bindParam(':subject_id', $subject_id);
            
            $stmt->execute();
            
            $success = 'Cập nhật thông tin môn học thành công';
            $stmt = $conn->prepare("SELECT * FROM subjects WHERE subject_id = :subject_id");
            $stmt->bindParam(':subject_id', $subject_id);
            $stmt->execute();
            $subject = $stmt->fetch(PDO::FETCH_ASSOC);
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
                    <h4 class="mb-0">Chỉnh sửa thông tin Môn học</h4>
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
                            <label for="subject_id">Mã môn học</label>
                            <input type="text" class="form-control" id="subject_id" value="<?php echo htmlspecialchars($subject['subject_id']); ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject_name">Tên môn học <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="subject_name" name="subject_name" value="<?php echo htmlspecialchars($subject['subject_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="credit">Số tín chỉ <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="credit" name="credit" min="1" value="<?php echo htmlspecialchars($subject['credit']); ?>" required>
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
