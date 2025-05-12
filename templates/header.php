<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Quản lý Sinh viên</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #f8f9fa;
        }
        .content {
            padding: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <?php
            // Xác định đường dẫn gốc
            $root_path = dirname($_SERVER['PHP_SELF']);
            if(strpos($root_path, '/templates') !== false) {
                $root_path = dirname(dirname($_SERVER['PHP_SELF']));
            } elseif(strpos($root_path, '/students') !== false || 
                     strpos($root_path, '/subjects') !== false || 
                     strpos($root_path, '/grades') !== false ||
                     strpos($root_path, '/admin') !== false ||
                     strpos($root_path, '/student') !== false) {
                $root_path = dirname($root_path);
            }
            if($root_path == '/' || $root_path == '\\') {
                $root_path = '';
            }
            ?>
            <a class="navbar-brand" href="<?php echo $root_path; ?>/index.php">Quản lý Sinh viên</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <?php if(isset($_SESSION['admin_id'])): // Menu cho admin ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $root_path; ?>/students/index.php">Sinh viên</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $root_path; ?>/subjects/index.php">Môn học</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $root_path; ?>/grades/index.php">Điểm số</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $root_path; ?>/admin/companies.php">Công ty thực tập</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $root_path; ?>/admin/internship_management.php">Quản lý thực tập</a>
                        </li>
                    <?php elseif(isset($_SESSION['student_id'])): // Menu cho sinh viên ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $root_path; ?>/student/dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $root_path; ?>/student/subjects.php">Bảng điểm</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $root_path; ?>/student/internship.php">Đăng ký thực tập</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown">
                            <?php 
                            if(isset($_SESSION['admin_id'])) {
                                echo $_SESSION['username'] ?? 'Admin';
                            } elseif(isset($_SESSION['student_id'])) {
                                echo $_SESSION['student_name'] ?? 'Sinh viên';
                            } else {
                                echo 'Tài khoản';
                            }
                            ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <?php if(isset($_SESSION['student_id'])): ?>
                                <a class="dropdown-item" href="<?php echo $root_path; ?>/student/profile.php">Thông tin cá nhân</a>
                                <a class="dropdown-item" href="<?php echo $root_path; ?>/student/change_password.php">Đổi mật khẩu</a>
                                <div class="dropdown-divider"></div>
                            <?php endif; ?>
                            <a class="dropdown-item" href="<?php echo $root_path; ?>/logout.php">Đăng xuất</a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
