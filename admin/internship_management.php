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

// Xử lý chạy thuật toán xét tuyển
if (isset($_POST['run_algorithm'])) {
    try {
        $conn->beginTransaction();
        // Xóa kết quả xét tuyển cũ
        $conn->exec("DELETE FROM internship_results");
        // Lấy danh sách sinh viên đã đăng ký thực tập, sắp xếp theo GPA giảm dần
        $stmt = $conn->query("
            SELECT r.student_id, r.company_id, r.priority,
                   AVG(g.score) / 10 * 4 as gpa
            FROM internship_registrations r
            JOIN grades g ON r.student_id = g.student_id
            GROUP BY r.student_id, r.company_id, r.priority
            ORDER BY gpa DESC
        ");
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Lấy chỉ tiêu của các công ty
        $stmt = $conn->query("SELECT company_id, quota FROM companies");
        $quotas = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $quotas[$row['company_id']] = $row['quota'];
        }
        // Lấy số lượng đã tuyển của mỗi công ty
        $selected_counts = [];
        foreach ($quotas as $company_id => $quota) {
            $selected_counts[$company_id] = 0;
        }
        // Danh sách sinh viên đã được xét tuyển
        $selected_students = [];
        // Xét tuyển theo GPA từ cao xuống thấp
        foreach ($students as $student) {
            // Bỏ qua nếu sinh viên đã được xét tuyển
            if (in_array($student['student_id'], $selected_students)) {
                continue;
            }
            // Lấy danh sách nguyện vọng của sinh viên, sắp xếp theo ưu tiên
            $stmt = $conn->prepare("
                SELECT company_id, priority
                FROM internship_registrations
                WHERE student_id = :student_id
                ORDER BY priority
            ");
            $stmt->bindParam(':student_id', $student['student_id']);
            $stmt->execute();
            $wishes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Xét từng nguyện vọng theo thứ tự ưu tiên
            foreach ($wishes as $wish) {
                $company_id = $wish['company_id'];
                $priority = $wish['priority'];
                // Nếu công ty còn chỉ tiêu
                if ($selected_counts[$company_id] < $quotas[$company_id]) {
                    // Thêm vào kết quả xét tuyển
                    $stmt = $conn->prepare("
                        INSERT INTO internship_results (student_id, company_id, priority, gpa)
                        VALUES (:student_id, :company_id, :priority, :gpa)
                    ");
                    $stmt->bindParam(':student_id', $student['student_id']);
                    $stmt->bindParam(':company_id', $company_id);
                    $stmt->bindParam(':priority', $priority);
                    $stmt->bindParam(':gpa', $student['gpa']);
                    $stmt->execute();
                    // Cập nhật số lượng đã tuyển
                    $selected_counts[$company_id]++;
                    // Thêm vào danh sách sinh viên đã được xét tuyển
                    $selected_students[] = $student['student_id'];
                    // Dừng xét các nguyện vọng tiếp theo
                    break;
                }
            }
        }
        $conn->commit();
        $success = "Đã chạy thuật toán xét tuyển thực tập thành công.";
    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Lỗi khi chạy thuật toán xét tuyển: " . $e->getMessage();
    }
}

// Lấy danh sách đăng ký thực tập
try {
    $stmt = $conn->query("
        SELECT r.*, s.full_name, s.class, c.company_name, c.position,
               AVG(g.score) / 10 * 4 as gpa
        FROM internship_registrations r
        JOIN students s ON r.student_id = s.student_id
        JOIN companies c ON r.company_id = c.company_id
        JOIN grades g ON r.student_id = g.student_id
        GROUP BY r.id, r.student_id, r.company_id, r.priority, r.registration_date, r.is_locked,
                 s.full_name, s.class, c.company_name, c.position
        ORDER BY r.student_id, r.priority
    ");
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Lỗi khi lấy danh sách đăng ký: " . $e->getMessage();
}

// Lấy kết quả xét tuyển
try {
    $stmt = $conn->query("
        SELECT r.*, s.full_name, s.class, c.company_name, c.position
        FROM internship_results r
        JOIN students s ON r.student_id = s.student_id
        JOIN companies c ON r.company_id = c.company_id
        ORDER BY r.gpa DESC
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Lỗi khi lấy kết quả xét tuyển: " . $e->getMessage();
}

// Hiển thị trang
include '../templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Quản lý Thực tập</h4>
                    <form method="post" action="" onsubmit="return confirm('Bạn có chắc chắn muốn chạy thuật toán xét tuyển? Kết quả cũ sẽ bị xóa.');">
                        <button type="submit" name="run_algorithm" class="btn btn-light">
                            <i class="fas fa-cogs"></i> Chạy thuật toán xét tuyển
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
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
                                            <th>Công ty</th>
                                            <th>Vị trí</th>
                                            <th>Nguyện vọng</th>
                                            <th>Ngày đăng ký</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($registrations) > 0): ?>
                                            <?php 
                                            $current_student = '';
                                            foreach ($registrations as $reg): 
                                                $is_new_student = ($current_student != $reg['student_id']);
                                                $current_student = $reg['student_id'];
                                            ?>
                                                <tr <?php echo $is_new_student ? 'class="table-primary"' : ''; ?>>
                                                    <td><?php echo htmlspecialchars($reg['student_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($reg['full_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($reg['class']); ?></td>
                                                    <td><?php echo number_format($reg['gpa'], 2); ?></td>
                                                    <td><?php echo htmlspecialchars($reg['company_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($reg['position']); ?></td>
                                                    <td><?php echo $reg['priority']; ?></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($reg['registration_date'])); ?></td>                                     
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="10" class="text-center">Không có đăng ký nào</td>
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
                                            <th>Công ty</th>
                                            <th>Vị trí</th>
                                            <th>Nguyện vọng</th>
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
                                                    <td><?php echo htmlspecialchars($result['company_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($result['position']); ?></td>
                                                    <td><?php echo $result['priority']; ?></td>
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
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
