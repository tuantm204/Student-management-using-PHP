<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}
include '../config/database.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: companies.php");
    exit();
}

$company_id = $_GET['id'];
// Lấy thông tin công ty
try {
    $stmt = $conn->prepare("SELECT * FROM companies WHERE company_id = :company_id");
    $stmt->bindParam(':company_id', $company_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        header("Location: companies.php");
        exit();
    }
    
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}

// Lấy danh sách sinh viên đăng ký vào công ty này
try {
    $stmt = $conn->prepare("
        SELECT r.*, s.full_name, s.class, s.email, s.phone,
               AVG(g.score) / 10 * 4 as gpa
        FROM internship_registrations r
        JOIN students s ON r.student_id = s.student_id
        JOIN grades g ON r.student_id = g.student_id
        WHERE r.company_id = :company_id
        GROUP BY r.id, r.student_id, r.company_id, r.priority, r.registration_date, r.is_locked,
                 s.full_name, s.class, s.email, s.phone
        ORDER BY r.priority, gpa DESC
    ");
    $stmt->bindParam(':company_id', $company_id);
    $stmt->execute();
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}

// Lấy kết quả xét tuyển cho công ty này
try {
    $stmt = $conn->prepare("
        SELECT r.*, s.full_name, s.class, s.email, s.phone
        FROM internship_results r
        JOIN students s ON r.student_id = s.student_id
        WHERE r.company_id = :company_id
        ORDER BY r.gpa DESC
    ");
    $stmt->bindParam(':company_id', $company_id);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}

// Hiển thị trang
include '../templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Danh sách đăng ký - <?php echo htmlspecialchars($company['company_name']); ?></h4>
                    <a href="companies.php" class="btn btn-light">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php else: ?>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Thông tin công ty</h5>
                                        <p><strong>Mã công ty:</strong> <?php echo htmlspecialchars($company['company_id']); ?></p>
                                        <p><strong>Tên công ty:</strong> <?php echo htmlspecialchars($company['company_name']); ?></p>
                                        <p><strong>Vị trí:</strong> <?php echo htmlspecialchars($company['position']); ?></p>
                                        <p><strong>Chỉ tiêu:</strong> <?php echo htmlspecialchars($company['quota']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Thống kê đăng ký</h5>
                                        <p><strong>Tổng số đăng ký:</strong> <?php echo count($registrations); ?></p>
                                        <p><strong>Đã trúng tuyển:</strong> <?php echo count($results); ?></p>
                                        <p><strong>Còn lại:</strong> <?php echo $company['quota'] - count($results); ?> chỉ tiêu</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="registrations-tab" data-toggle="tab" href="#registrations" role="tab" aria-controls="registrations" aria-selected="true">
                                    <i class="fas fa-clipboard-list"></i> Danh sách đăng ký
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="results-tab" data-toggle="tab" href="#results" role="tab" aria-controls="results" aria-selected="false">
                                    <i class="fas fa-check-circle"></i> Kết quả xét tuyển
                                </a>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="myTabContent">
                            <!-- Tab Danh sách đăng ký -->
                            <div class="tab-pane fade show active" id="registrations" role="tabpanel" aria-labelledby="registrations-tab">
                                <div class="table-responsive mt-3">
                                    <table class="table table-bordered table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Mã SV</th>
                                                <th>Họ và tên</th>
                                                <th>Lớp</th>
                                                <th>GPA</th>
                                                <th>Nguyện vọng</th>
                                                <th>Email</th>
                                                <th>Điện thoại</th>
                                                <th>Ngày đăng ký</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($registrations) > 0): ?>
                                                <?php foreach ($registrations as $reg): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($reg['student_id']); ?></td>
                                                        <td><?php echo htmlspecialchars($reg['full_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($reg['class']); ?></td>
                                                        <td><?php echo number_format($reg['gpa'], 2); ?></td>
                                                        <td>
                                                            <span class="badge badge-<?php echo ($reg['priority'] == 1) ? 'primary' : (($reg['priority'] == 2) ? 'success' : 'info'); ?>">
                                                                Nguyện vọng <?php echo $reg['priority']; ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($reg['email'] ?? 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars($reg['phone'] ?? 'N/A'); ?></td>
                                                        <td><?php echo date('d/m/Y H:i', strtotime($reg['registration_date'])); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">Không có sinh viên nào đăng ký</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Tab Kết quả xét tuyển -->
                            <div class="tab-pane fade" id="results" role="tabpanel" aria-labelledby="results-tab">
                                <div class="table-responsive mt-3">
                                    <table class="table table-bordered table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Mã SV</th>
                                                <th>Họ và tên</th>
                                                <th>Lớp</th>
                                                <th>GPA</th>
                                                <th>Nguyện vọng</th>
                                                <th>Email</th>
                                                <th>Điện thoại</th>
                                                <th>Ngày xét tuyển</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($results) > 0): ?>
                                                <?php foreach ($results as $result): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($result['student_id']); ?></td>
                                                        <td><?php echo htmlspecialchars($result['full_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($result['class']); ?></td>
                                                        <td><?php echo number_format($result['gpa'], 2); ?></td>
                                                        <td>
                                                            <span class="badge badge-<?php echo ($result['priority'] == 1) ? 'primary' : (($result['priority'] == 2) ? 'success' : 'info'); ?>">
                                                                Nguyện vọng <?php echo $result['priority']; ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($result['email'] ?? 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars($result['phone'] ?? 'N/A'); ?></td>
                                                        <td><?php echo date('d/m/Y H:i', strtotime($result['result_date'])); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">Chưa có kết quả xét tuyển</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
