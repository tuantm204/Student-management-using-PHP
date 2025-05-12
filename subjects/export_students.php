    <?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../config/database.php';

// Kiểm tra ID môn học
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$subject_id = $_GET['id'];
$semester_id = isset($_GET['semester_id']) ? $_GET['semester_id'] : '';

// Lấy thông tin môn học
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
    die('Lỗi: ' . $e->getMessage());
}

// Lấy thông tin học kỳ nếu có
$semester_name = "Tất cả học kỳ";
if (!empty($semester_id)) {
    try {
        $stmt = $conn->prepare("SELECT name FROM semesters WHERE semester_id = :semester_id");
        $stmt->bindParam(':semester_id', $semester_id);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $semester = $stmt->fetch(PDO::FETCH_ASSOC);
            $semester_name = $semester['name'];
        }
    } catch(PDOException $e) {
        die('Lỗi: ' . $e->getMessage());
    }
}

// Lấy danh sách sinh viên có điểm cho môn học này
try {
    $query = "SELECT s.student_id, s.full_name, s.class, g.score, sem.name as semester_name
              FROM students s
              JOIN grades g ON s.student_id = g.student_id
              JOIN semesters sem ON g.semester_id = sem.semester_id
              WHERE g.subject_id = :subject_id";
    
    if (!empty($semester_id)) {
        $query .= " AND g.semester_id = :semester_id";
    }
    $query .= " ORDER BY s.class, s.full_name";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':subject_id', $subject_id);
    if (!empty($semester_id)) {
        $stmt->bindParam(':semester_id', $semester_id);
    }
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die('Lỗi: ' . $e->getMessage());
}

// Hàm chuyển đổi điểm số sang xếp loại
function getGradeLevel($score) {
    if ($score >= 8.5) return 'Giỏi';
    if ($score >= 7.0) return 'Khá';
    if ($score >= 5.5) return 'Trung bình';
    if ($score >= 4.0) return 'Yếu';
    return 'Kém';
}

// Tính toán thống kê
$total_students = count($students);
$sum_score = 0;
$count_excellent = 0; // >= 8.5
$count_good = 0;      // >= 7.0
$count_average = 0;   // >= 5.5
$count_below = 0;     // >= 4.0
$count_fail = 0;      // < 4.0

foreach ($students as $student) {
    $score = $student['score'];
    $sum_score += $score;
    
    if ($score >= 8.5) $count_excellent++;
    elseif ($score >= 7.0) $count_good++;
    elseif ($score >= 5.5) $count_average++;
    elseif ($score >= 4.0) $count_below++;
    else $count_fail++;
}

$avg_score = $total_students > 0 ? $sum_score / $total_students : 0;

// Thiết lập header cho file Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="danh_sach_sinh_vien_' . $subject_id . '.xls"');
header('Cache-Control: max-age=0');

// Xuất file Excel
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        table { border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 5px; }
        th { background-color: #f0f0f0; }
        .header { font-weight: bold; font-size: 16px; }
        .subheader { font-weight: bold; font-size: 14px; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="5" class="header">DANH SÁCH SINH VIÊN HỌC MÔN: <?php echo mb_strtoupper($subject['subject_name'], 'UTF-8'); ?></td>
        </tr>
        <tr>
            <td colspan="5">Mã môn học: <?php echo $subject['subject_id']; ?> - Số tín chỉ: <?php echo $subject['credit']; ?></td>
        </tr>
        <tr>
            <td colspan="5">Học kỳ: <?php echo $semester_name; ?></td>
        </tr>
        <tr>
            <td colspan="5">Ngày xuất: <?php echo date('d/m/Y H:i:s'); ?></td>
        </tr>
        <tr><td colspan="5"></td></tr>
        
        <tr>
            <th>STT</th>
            <th>Mã SV</th>
            <th>Họ và tên</th>
            <th>Lớp</th>
            <th>Học kỳ</th>
            <th>Điểm</th>
            <th>Xếp loại</th>
        </tr>
        
        <?php if (count($students) > 0): ?>
            <?php foreach ($students as $index => $student): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo $student['student_id']; ?></td>
                    <td><?php echo $student['full_name']; ?></td>
                    <td><?php echo $student['class']; ?></td>
                    <td><?php echo $student['semester_name']; ?></td>
                    <td><?php echo $student['score']; ?></td>
                    <td><?php echo getGradeLevel($student['score']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">Không có sinh viên nào học môn này<?php echo !empty($semester_id) ? ' trong học kỳ đã chọn' : ''; ?></td>
            </tr>
        <?php endif; ?>
        
        <tr><td colspan="7"></td></tr>
        
        <!-- Thống kê -->
        <tr>
            <td colspan="7" class="subheader">THỐNG KÊ ĐIỂM SỐ</td>
        </tr>
        <tr>
            <td colspan="3">Tổng số sinh viên</td>
            <td colspan="4"><?php echo $total_students; ?></td>
        </tr>
        <tr>
            <td colspan="3">Điểm trung bình</td>
            <td colspan="4"><?php echo number_format($avg_score, 2); ?></td>
        </tr>
        <tr>
            <td colspan="3">Giỏi (>= 8.5)</td>
            <td colspan="4"><?php echo $count_excellent; ?> (<?php echo number_format(($total_students > 0 ? ($count_excellent / $total_students) * 100 : 0), 1); ?>%)</td>
        </tr>
        <tr>
            <td colspan="3">Khá (>= 7.0)</td>
            <td colspan="4"><?php echo $count_good; ?> (<?php echo number_format(($total_students > 0 ? ($count_good / $total_students) * 100 : 0), 1); ?>%)</td>
        </tr>
        <tr>
            <td colspan="3">Trung bình (>= 5.5)</td>
            <td colspan="4"><?php echo $count_average; ?> (<?php echo number_format(($total_students > 0 ? ($count_average / $total_students) * 100 : 0), 1); ?>%)</td>
        </tr>
        <tr>
            <td colspan="3">Yếu (>= 4.0)</td>
            <td colspan="4"><?php echo $count_below; ?> (<?php echo number_format(($total_students > 0 ? ($count_below / $total_students) * 100 : 0), 1); ?>%)</td>
        </tr>
        <tr>
            <td colspan="3">Kém (< 4.0)</td>
            <td colspan="4"><?php echo $count_fail; ?> (<?php echo number_format(($total_students > 0 ? ($count_fail / $total_students) * 100 : 0), 1); ?>%)</td>
        </tr>
    </table>
</body>
</html>
