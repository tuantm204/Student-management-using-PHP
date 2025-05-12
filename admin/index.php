<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Hiển thị trang chủ
include '../templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="jumbotron">
                <h1 class="display-4">Hệ thống Quản lý Sinh viên</h1>
                <p class="lead">Chào mừng đến với hệ thống quản lý sinh viên</p>
                <hr class="my-4">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Quản lý Sinh viên</h5>
                                <p class="card-text">Xem danh sách, thêm, sửa, xóa thông tin sinh viên</p>
                                <a href="../students/index.php" class="btn btn-primary">Truy cập</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Quản lý Môn học</h5>
                                <p class="card-text">Xem danh sách, thêm, sửa, xóa thông tin môn học</p>
                                <a href="../subjects/index.php" class="btn btn-primary">Truy cập</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Quản lý Điểm số</h5>
                                <p class="card-text">Xem danh sách, thêm, sửa, xóa thông tin điểm số</p>
                                <a href="../grades/index.php" class="btn btn-primary">Truy cập</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Quản lý Công ty thực tập</h5>
                                <p class="card-text">Thêm, sửa, xóa thông tin công ty thực tập</p>
                                <a href="companies.php" class="btn btn-primary">Truy cập</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Quản lý Thực tập</h5>
                                <p class="card-text">Xem đăng ký, xét tuyển thực tập cho sinh viên</p>
                                <a href="internship_management.php" class="btn btn-primary">Truy cập</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
