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

// Xử lý thêm công ty
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    $company_id = $_POST['company_id'] ?? '';
    $company_name = $_POST['company_name'] ?? '';
    $position = $_POST['position'] ?? '';
    $quota = $_POST['quota'] ?? '';
    if (empty($company_id) || empty($company_name) || empty($position) || empty($quota)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } elseif (!is_numeric($quota) || $quota <= 0) {
        $error = 'Chỉ tiêu phải là số dương';
    } else {
        try {
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM companies WHERE company_id = :company_id");
            $check_stmt->bindParam(':company_id', $company_id);
            $check_stmt->execute();
            
            if ($check_stmt->fetchColumn() > 0) {
                $error = 'Mã công ty đã tồn tại trong hệ thống';
            } else {
                // Thêm công ty mới
                $stmt = $conn->prepare("INSERT INTO companies (company_id, company_name, position, quota) 
                                        VALUES (:company_id, :company_name, :position, :quota)");
                
                $stmt->bindParam(':company_id', $company_id);
                $stmt->bindParam(':company_name', $company_name);
                $stmt->bindParam(':position', $position);
                $stmt->bindParam(':quota', $quota);
                
                $stmt->execute();
                
                $success = 'Thêm công ty thành công';
            }
        } catch(PDOException $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Xử lý cập nhật công ty
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $company_id = $_POST['edit_company_id'] ?? '';
    $company_name = $_POST['edit_company_name'] ?? '';
    $position = $_POST['edit_position'] ?? '';
    $quota = $_POST['edit_quota'] ?? '';
    
    // Kiểm tra dữ liệu
    if (empty($company_id) || empty($company_name) || empty($position) || empty($quota)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } elseif (!is_numeric($quota) || $quota <= 0) {
        $error = 'Chỉ tiêu phải là số dương';
    } else {
        try {
            // Cập nhật thông tin công ty
            $stmt = $conn->prepare("UPDATE companies SET 
                                    company_name = :company_name, 
                                    position = :position, 
                                    quota = :quota 
                                    WHERE company_id = :company_id");
            
            $stmt->bindParam(':company_name', $company_name);
            $stmt->bindParam(':position', $position);
            $stmt->bindParam(':quota', $quota);
            $stmt->bindParam(':company_id', $company_id);
            
            $stmt->execute();
            
            $success = 'Cập nhật thông tin công ty thành công';
        } catch(PDOException $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Xử lý xóa công ty
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $company_id = $_GET['delete'];
    
    try {
        // Kiểm tra xem công ty có sinh viên đăng ký không
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM internship_registrations WHERE company_id = :company_id");
        $check_stmt->bindParam(':company_id', $company_id);
        $check_stmt->execute();
        $has_registrations = $check_stmt->fetchColumn() > 0;
        
        // Kiểm tra xem công ty có kết quả xét tuyển không
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM internship_results WHERE company_id = :company_id");
        $check_stmt->bindParam(':company_id', $company_id);
        $check_stmt->execute();
        $has_results = $check_stmt->fetchColumn() > 0;
        
        if ($has_registrations || $has_results) {
            $error = "Không thể xóa công ty này vì đã có sinh viên đăng ký hoặc đã có kết quả xét tuyển.";
        } else {
            // Xóa công ty
            $stmt = $conn->prepare("DELETE FROM companies WHERE company_id = :company_id");
            $stmt->bindParam(':company_id', $company_id);
            $stmt->execute();
            
            $success = "Đã xóa công ty thành công.";
        }
    } catch(PDOException $e) {
        $error = "Lỗi khi xóa công ty: " . $e->getMessage();
    }
}

// Lấy danh sách công ty
try {
    $stmt = $conn->query("SELECT * FROM companies ORDER BY company_name");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Lỗi khi lấy danh sách công ty: " . $e->getMessage();
}

// Hiển thị trang
include '../templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Quản lý Công ty thực tập</h4>
                    <button type="button" class="btn btn-light" data-toggle="modal" data-target="#addCompanyModal">
                        <i class="fas fa-plus"></i> Thêm Công ty
                    </button>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Mã công ty</th>
                                    <th>Tên công ty</th>
                                    <th>Vị trí</th>
                                    <th>Chỉ tiêu</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($companies) > 0): ?>
                                    <?php foreach ($companies as $company): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($company['company_id']); ?></td>
                                            <td><?php echo htmlspecialchars($company['company_name']); ?></td>
                                            <td><?php echo htmlspecialchars($company['position']); ?></td>
                                            <td><?php echo htmlspecialchars($company['quota']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info edit-company" 
                                                        data-id="<?php echo $company['company_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($company['company_name']); ?>"
                                                        data-position="<?php echo htmlspecialchars($company['position']); ?>"
                                                        data-quota="<?php echo $company['quota']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="companies.php?delete=<?php echo $company['company_id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Bạn có chắc chắn muốn xóa công ty này?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <a href="company_registrations.php?id=<?php echo $company['company_id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-users"></i> Xem đăng ký
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Không có công ty nào</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm Công ty -->
<div class="modal fade" id="addCompanyModal" tabindex="-1" role="dialog" aria-labelledby="addCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addCompanyModalLabel">Thêm Công ty mới</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="post" action="">
                    <div class="form-group">
                        <label for="company_id">Mã công ty <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="company_id" name="company_id" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="company_name">Tên công ty <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="company_name" name="company_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="position">Vị trí <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="position" name="position" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="quota">Chỉ tiêu <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="quota" name="quota" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add" class="btn btn-primary">Thêm công ty</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Sửa Công ty -->
<div class="modal fade" id="editCompanyModal" tabindex="-1" role="dialog" aria-labelledby="editCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editCompanyModalLabel">Chỉnh sửa thông tin Công ty</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="post" action="">
                    <input type="hidden" id="edit_company_id" name="edit_company_id">
                    
                    <div class="form-group">
                        <label for="edit_company_name">Tên công ty <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_company_name" name="edit_company_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_position">Vị trí <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_position" name="edit_position" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_quota">Chỉ tiêu <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="edit_quota" name="edit_quota" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="update" class="btn btn-primary">Cập nhật</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Xử lý sự kiện khi nhấn nút sửa
    $('.edit-company').click(function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var position = $(this).data('position');
        var quota = $(this).data('quota');
        
        $('#edit_company_id').val(id);
        $('#edit_company_name').val(name);
        $('#edit_position').val(position);
        $('#edit_quota').val(quota);
        
        $('#editCompanyModal').modal('show');
    });
});
</script>

<?php include '../templates/footer.php'; ?>
