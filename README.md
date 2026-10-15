<div align="center">

### 🎓 [Trường Đại học Đại Nam — Khoa Công nghệ Thông tin](https://dainam.edu.vn)

## BÀI THỰC HÀNH PHÁT TRIỂN PHẦN MỀM MÃ NGUỒN MỞ (PTPMMNM)

<img src="assets/logo-header.png" alt="DNU AIoT Lab - Khoa CNTT - Đại học Đại Nam" width="480">

</div>

---

## 1. Thành viên nhóm

| STT | Họ và tên | Mã sinh viên | Lớp | Vai trò |
| :---: | :--- | :---: | :---: | :--- |
| 1 | **Lê Đình Huy** | 1871020307 | CNTT 18-10 | Nhóm trưởng |
| 2 | **Đào Thị Tươi** | 1871020639 | CNTT 18-10 | Thành viên |
| 3 | **Nguyễn Văn An** | 1871020003 | CNTT 18-10 | Thành viên |

---

## 2. Danh sách bài thực hành

| Bài Lab | Nội dung thực hành | Trạng thái | Nhánh Git | Thư mục |
| :---: | :--- | :---: | :---: | :---: |
| **Lab01** | Vận hành dự án trên Linux và viết Shell Script (`check-env.sh`, `scan-source.sh`) | ✅ Đạt 100% | [lab1](https://github.com/ledinhhuymeme/PTPMMNM-CNTT-1810-NHOM-10/tree/lab1) | `lab1/` |
| **Lab02** | Xây dựng Module PHP kiểm tra điều kiện đăng ký (`RegistrationPolicy.php`) | ✅ Đạt 100% | [lab2](https://github.com/ledinhhuymeme/PTPMMNM-CNTT-1810-NHOM-10/tree/lab2) | `lab2/` |
| **Lab03** | Hiển thị sự kiện, bộ lọc tìm kiếm đa tiêu chí và xử lý Form POST (`EventFilter.php`) | ✅ Đạt 100% | [lab3](https://github.com/ledinhhuymeme/PTPMMNM-CNTT-1810-NHOM-10/tree/lab3) | `lab3/` |
| **Lab04** | Quản lý bản nháp, Session và Cookie (`DraftGuard.php`) | ✅ Đạt 100% | [lab4](https://github.com/ledinhhuymeme/PTPMMNM-CNTT-1810-NHOM-10/tree/lab4) | `lab4/` |
| **Lab05** | Tích hợp CSDL PostgreSQL, chống trùng và thống kê (`RegistrationStats.php`) | ✅ Đạt 100% | [lab5](https://github.com/ledinhhuymeme/PTPMMNM-CNTT-1810-NHOM-10/tree/lab5) | `lab5/` |

---

## 3. Công nghệ sử dụng

- **Hệ điều hành:** Linux (Ubuntu / WSL 2), Windows
- **Ngôn ngữ:** PHP 8.1+ (PHP CLI, OOP, PSR-12, PDO)
- **Công cụ:** Git, GitHub, Visual Studio Code
- **Môi trường & Script:** Bash Shell Scripting, PowerShell
- **Giao diện Web:** HTML5, CSS3 hiện đại (Material 3 Tokens)
- **Cơ sở dữ liệu:** PostgreSQL (Lab 05)

---

## 4. Hướng dẫn xem và kiểm thử từng bài Lab

Mỗi bài Lab được lưu trữ trên một thư mục và một nhánh Git độc lập theo đúng quy cách của bộ môn:

- **`lab1`**: Bài thực hành 01 — Linux & Shell Script (`CHECK_ENV=PASS`, `SHELL_CASES=4 FAILED=0`).
- **`lab2`**: Bài thực hành 02 — Lập trình Module PHP (`TESTS=5 FAILED=0`, `TESTS=8 FAILED=0`).
- **`lab3`**: Bài thực hành 03 — Tìm kiếm và Form (`TESTS=7 FAILED=0`, `TESTS=8 FAILED=0`).
- **`lab4`**: Bài thực hành 04 — Session & Cookie (`TESTS=6 FAILED=0`, `TESTS=7 FAILED=0`).
- **`lab5`**: Bài thực hành 05 — PostgreSQL (`TESTS=6 FAILED=0`, `TESTS=7 FAILED=0`).

### Lệnh Clone và Chạy kiểm thử từng bài Lab:

```bash
# Clone và kiểm thử Lab 01
git clone --single-branch --branch lab1 https://github.com/ledinhhuymeme/PTPMMNM-CNTT-1810-NHOM-10.git lab1
cd lab1/Ma_nguon
bash lab.sh check && bash lab.sh test && bash lab.sh extend

# Clone và kiểm thử Lab 02
git clone --single-branch --branch lab2 https://github.com/ledinhhuymeme/PTPMMNM-CNTT-1810-NHOM-10.git lab2
cd lab2/Ma_nguon
bash lab.sh check && bash lab.sh test && bash lab.sh extend

# Clone và kiểm thử Lab 03
git clone --single-branch --branch lab3 https://github.com/ledinhhuymeme/PTPMMNM-CNTT-1810-NHOM-10.git lab3
cd lab3/Ma_nguon
bash lab.sh check && bash lab.sh test && bash lab.sh extend

# Clone và kiểm thử Lab 04
git clone --single-branch --branch lab4 https://github.com/ledinhhuymeme/PTPMMNM-CNTT-1810-NHOM-10.git lab4
cd lab4/Ma_nguon
bash lab.sh check && bash lab.sh test && bash lab.sh extend

# Clone và kiểm thử Lab 05
git clone --single-branch --branch lab5 https://github.com/ledinhhuymeme/PTPMMNM-CNTT-1810-NHOM-10.git lab5
cd lab5/Ma_nguon
bash lab.sh check && bash lab.sh test && bash lab.sh extend
```

---

## 5. Repository bài tập gốc

[https://github.com/FIT-DNU/Open-Source-Software-Development](https://github.com/FIT-DNU/Open-Source-Software-Development)