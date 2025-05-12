<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../config/database.php';
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}
$student_id = $_GET['id'];

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

// Lấy danh sách học kỳ mà sinh viên đã học
try {
    $stmt = $conn->prepare("
        SELECT DISTINCT s.semester_id, s.name, s.year
        FROM grades g
        JOIN semesters s ON g.semester_id = s.semester_id
        WHERE g.student_id = :student_id
        ORDER BY s.year, s.name
    ");
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}

// Tính GPA cho từng học kỳ và GPA tích lũy
$semester_gpas = [];
$semester_names = [];
$cumulative_gpas = [];
$cumulative_credits = 0;
$cumulative_grade_points = 0;

try {
    foreach ($semesters as $index => $semester) {
        $stmt = $conn->prepare("
            SELECT g.*, s.credit
            FROM grades g
            JOIN subjects s ON g.subject_id = s.subject_id
            WHERE g.student_id = :student_id AND g.semester_id = :semester_id
        ");
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':semester_id', $semester['semester_id']);
        $stmt->execute();
        $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Tính GPA cho học kỳ
        $total_credits = 0;
        $total_grade_points = 0;
        
        foreach ($grades as $grade) {
            $total_credits += $grade['credit'];
            $total_grade_points += ($grade['score'] / 10 * 4) * $grade['credit'];
        }
        
        $semester_gpa = ($total_credits > 0) ? ($total_grade_points / $total_credits) : 0;
        $semester_gpas[] = round($semester_gpa, 2);
        $semester_names[] = $semester['name'];
        
        // Cộng dồn cho GPA tích lũy
        $cumulative_credits += $total_credits;
        $cumulative_grade_points += $total_grade_points;
        $cumulative_gpa = ($cumulative_credits > 0) ? ($cumulative_grade_points / $cumulative_credits) : 0;
        $cumulative_gpas[] = round($cumulative_gpa, 2);
    }
} catch(PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}

// Hàm chuyển đổi GPA sang xếp loại
function getGPALevel($gpa) {
    if ($gpa >= 3.6) return 'Xuất sắc';
    if ($gpa >= 3.2) return 'Giỏi';
    if ($gpa >= 2.5) return 'Khá';
    if ($gpa >= 2.0) return 'Trung bình';
    if ($gpa >= 1.0) return 'Yếu';
    return 'Kém';
}

// Hiển thị trang
include '../templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Tổng quan GPA - <?php echo htmlspecialchars($student['full_name']); ?></h4>
                    <div>
                        <a href="view_subjects.php?id=<?php echo $student_id; ?>" class="btn btn-info mr-2">
                            <i class="fas fa-book"></i> Xem bảng điểm
                        </a>
                        <a href="index.php" class="btn btn-light">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php else: ?>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Thông tin sinh viên</h5>
                                        <p><strong>Mã sinh viên:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
                                        <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($student['full_name']); ?></p>
                                        <p><strong>Lớp:</strong> <?php echo htmlspecialchars($student['class']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Điểm trung bình tích lũy (GPA)</h5>
                                        <div class="text-center">
                                            <h2 class="display-4 font-weight-bold text-primary">
                                                <?php echo number_format(end($cumulative_gpas), 2); ?>
                                            </h2>
                                            <p class="lead"><?php echo getGPALevel(end($cumulative_gpas)); ?></p>
                                            <p>Tổng số tín chỉ: <?php echo $cumulative_credits; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (count($semesters) > 0): ?>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Biểu đồ GPA theo học kỳ</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="gpaChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Học kỳ</th>
                                            <th>GPA học kỳ</th>
                                            <th>Xếp loại học kỳ</th>
                                            <th>GPA tích lũy</th>
                                            <th>Xếp loại tích lũy</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($semesters as $index => $semester): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($semester['name']); ?></td>
                                                <td><?php echo number_format($semester_gpas[$index], 2); ?></td>
                                                <td><?php echo getGPALevel($semester_gpas[$index]); ?></td>
                                                <td><?php echo number_format($cumulative_gpas[$index], 2); ?></td>
                                                <td><?php echo getGPALevel($cumulative_gpas[$index]); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Sinh viên chưa có điểm cho bất kỳ môn học nào.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (count($semesters) > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('gpaChart').getContext('2d');
    var semesterNames = <?php echo json_encode($semester_names); ?>;
    var semesterGPAs = <?php echo json_encode($semester_gpas); ?>;
    var cumulativeGPAs = <?php echo json_encode($cumulative_gpas); ?>;
    
    var chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: semesterNames,
            datasets: [
                {
                    label: 'GPA học kỳ',
                    data: semesterGPAs,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                    pointRadius: 4
                },
                {
                    label: 'GPA tích lũy',
                    data: cumulativeGPAs,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                    pointRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: false,
                    min: 0,
                    max: 4,
                    ticks: {
                        stepSize: 0.5
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed.y.toFixed(2);
                            return label;
                        }
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php include '../templates/footer.php'; ?>
