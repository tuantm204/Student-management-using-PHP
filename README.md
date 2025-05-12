# 🎓 Hệ thống Quản lý Sinh viên

![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)
![MySQL Version](https://img.shields.io/badge/MySQL-5.7%2B-orange)
![License](https://img.shields.io/badge/License-MIT-green)

Hệ thống quản lý sinh viên toàn diện được phát triển bằng PHP và MySQL, hỗ trợ quản lý thông tin sinh viên, điểm số, môn học và thực tập.

![Dashboard Demo](screenshots/dashboard.png)

## ✨ Tính năng chính

### 👨‍🎓 Quản lý sinh viên
- Thêm, sửa, xóa thông tin sinh viên
- Xem danh sách sinh viên theo lớp
- Hiển thị thông tin chi tiết của sinh viên
- Chúc mừng sinh nhật sinh viên tự động

### 📚 Quản lý môn học
- Thêm, sửa, xóa thông tin môn học
- Quản lý danh sách sinh viên theo môn học
- Xuất danh sách sinh viên ra Excel

### 📊 Quản lý điểm số
- Nhập điểm cho sinh viên theo môn học
- Tính toán GPA tự động
- Hiển thị bảng điểm theo học kỳ
- Thống kê và biểu đồ điểm số

### 🏢 Quản lý thực tập
- Quản lý thông tin công ty thực tập
- Sinh viên đăng ký nguyện vọng thực tập
- Thuật toán xét tuyển thực tập tự động
- Hiển thị kết quả xét tuyển

### 🔐 Hệ thống tài khoản
- Đăng nhập phân quyền (Admin/Sinh viên)
- Đổi mật khẩu
- Quản lý thông tin cá nhân

## 🛠️ Công nghệ sử dụng

- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript
- **Framework CSS:** Bootstrap 4.5
- **Thư viện JS:** jQuery, Chart.js
- **Icons:** Font Awesome 5

## 📋 Yêu cầu hệ thống

- PHP 7.4 trở lên
- MySQL 5.7 trở lên
- Web server (Apache/Nginx)
- PDO PHP Extension
- GD PHP Extension (cho xử lý hình ảnh)

## 🚀 Cài đặt và thiết lập

### Cài đặt thủ công

1. Clone repository về máy:
   \`\`\`bash
   git clone https://github.com/yourusername/student-management-system.git
   \`\`\`

2. Import database từ file `database.sql`:
   \`\`\`bash
   mysql -u username -p database_name < database.sql
   \`\`\`

3. Cấu hình kết nối database trong file `config/database.php`:
   \`\`\`php
   $host = 'localhost';
   $dbname = 'student_management';
   $username = 'root';
   $password = 'your_password';
   \`\`\`

4. Truy cập hệ thống qua trình duyệt:
   \`\`\`
   http://localhost/student-management-system
   \`\`\`

### Tài khoản mặc định

- **Admin:**
  - Username: admin
  - Password: admin123

- **Sinh viên:**
  - Mã sinh viên: [Mã sinh viên]
  - Password: [Ngày tháng năm sinh - định dạng ddMMyyyy]

## 📁 Cấu trúc dự án

\`\`\`
student-management-system/
├── admin/                  # Trang quản trị dành cho admin
├── config/                 # Cấu hình hệ thống
├── grades/                 # Quản lý điểm số
├── student/                # Trang dành cho sinh viên
├── students/               # Quản lý sinh viên
├── subjects/               # Quản lý môn học
├── templates/              # Template chung
├── index.php               # Trang chủ
├── login.php               # Đăng nhập admin
├── student_login.php       # Đăng nhập sinh viên
├── logout.php              # Đăng xuất
├── setup.php               # Thiết lập ban đầu
└── README.md               # Tài liệu
\`\`\`

## 📝 Hướng dẫn sử dụng

### Dành cho Admin

1. **Đăng nhập:**
   - Truy cập trang đăng nhập admin
   - Nhập username và password

2. **Quản lý sinh viên:**
   - Thêm sinh viên mới
   - Chỉnh sửa thông tin sinh viên
   - Xem bảng điểm của sinh viên

3. **Quản lý môn học:**
   - Thêm môn học mới
   - Thêm sinh viên vào lớp môn học
   - Xuất danh sách sinh viên ra Excel

4. **Quản lý điểm số:**
   - Nhập điểm cho sinh viên
   - Xem thống kê điểm số

5. **Quản lý thực tập:**
   - Thêm công ty thực tập
   - Chạy thuật toán xét tuyển
   - Xem kết quả xét tuyển

### Dành cho Sinh viên

1. **Đăng nhập:**
   - Truy cập trang đăng nhập sinh viên
   - Nhập mã sinh viên và mật khẩu (ngày sinh)

2. **Xem thông tin cá nhân:**
   - Xem và cập nhật thông tin liên lạc
   - Đổi mật khẩu

3. **Xem bảng điểm:**
   - Xem điểm theo học kỳ
   - Xem GPA tích lũy

4. **Đăng ký thực tập:**
   - Đăng ký nguyện vọng thực tập
   - Xem kết quả xét tuyển

## 🔄 Cập nhật và phát triển

### Phiên bản hiện tại: 1.0.0

#### Lịch sử cập nhật:
- **1.0.0** (01/05/2024): Phát hành phiên bản đầu tiên
- **0.9.0** (15/04/2024): Phiên bản beta, thêm tính năng quản lý thực tập
- **0.8.0** (01/04/2024): Phiên bản alpha, hoàn thiện các tính năng cơ bản

#### Kế hoạch phát triển:
- Thêm tính năng gửi email thông báo
- Tích hợp hệ thống học trực tuyến
- Phát triển ứng dụng di động

## 🤝 Đóng góp

Mọi đóng góp đều được hoan nghênh! Nếu bạn muốn đóng góp vào dự án, vui lòng:

1. Fork dự án
2. Tạo nhánh tính năng (`git checkout -b feature/amazing-feature`)
3. Commit thay đổi (`git commit -m 'Add some amazing feature'`)
4. Push lên nhánh (`git push origin feature/amazing-feature`)
5. Mở Pull Request

## 📜 Giấy phép

Dự án này được phân phối dưới giấy phép MIT. Xem file `LICENSE` để biết thêm chi tiết.

## 📞 Liên hệ

- **Email:** your.email@example.com
- **Website:** https://yourwebsite.com
- **GitHub:** https://github.com/yourusername

---

<p align="center">
  <sub>Developed with ❤️ by Your Name</sub>
</p>
