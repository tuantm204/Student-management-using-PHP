-- Bảng sinh viên
CREATE TABLE students (
    student_id VARCHAR(10) PRIMARY KEY,
    full_name VARCHAR(100),
    dob DATE,
    gender ENUM('Nam', 'Nữ'),
    class VARCHAR(50),
    email VARCHAR(100),
    phone VARCHAR(15)
);

-- Bảng môn học
CREATE TABLE subjects (
    subject_id VARCHAR(10) PRIMARY KEY,
    subject_name VARCHAR(100),
    credit INT
);

-- Bảng học kỳ
CREATE TABLE semesters (
    semester_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50), -- ví dụ: "Học kỳ 1 - 2024"
    year INT
);

-- Bảng điểm
CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(10),
    subject_id VARCHAR(10),
    semester_id INT,
    score FLOAT CHECK (score >= 0 AND score <= 10),
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
    FOREIGN KEY (semester_id) REFERENCES semesters(semester_id)
);

-- (Tùy chọn) Bảng tài khoản admin
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password_hash VARCHAR(255)
);
--companies (Danh sách công ty thực tập)
CREATE TABLE IF NOT EXISTS companies (
    company_id VARCHAR(10) PRIMARY KEY,
    company_name VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    quota INT NOT NULL DEFAULT 1
);
–internship_registrations (Đăng ký nguyện vọng)
CREATE TABLE IF NOT EXISTS internship_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(10) NOT NULL,
    company_id VARCHAR(10) NOT NULL,
    priority INT NOT NULL CHECK (priority BETWEEN 1 AND 3),
    registration_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_locked BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (company_id) REFERENCES companies(company_id),
    UNIQUE KEY (student_id, priority)
);
–internship_results (Kết quả xét tuyển)
CREATE TABLE IF NOT EXISTS internship_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(10) NOT NULL,
    company_id VARCHAR(10) NOT NULL,
    priority INT NOT NULL,
    gpa FLOAT NOT NULL,
    result_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (company_id) REFERENCES companies(company_id),
    UNIQUE KEY (student_id)
);

