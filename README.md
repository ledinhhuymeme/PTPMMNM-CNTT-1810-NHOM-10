# PHÁT TRIỂN PHẦN MỀM MÃ NGUỒN MỞ

## NHÓM 10 - CNTT 1810

**Trường:** Đại học Đại Nam  
**Khoa:** Công nghệ Thông tin  
**Học phần:** Phát triển phần mềm mã nguồn mở  
**Đề tài BTL:** Đề tài 10: Xây dựng hệ thống quản lý kho, điều chuyển hàng và kiểm kê tại nhiều điểm  

---

## 1. Thành viên nhóm

| STT | Họ và tên | Mã sinh viên | Lớp | Vai trò |
| :---: | :--- | :---: | :---: | :--- |
| 1 | **Lê Đình Huy** | 1871020307 | CNTT 18-10 | Nhóm trưởng |
| 2 | **Đào Thị Tươi** | 1871020639 | CNTT 18-10 | Thành viên |
| 3 | **Hoàng Văn Chí** | 1871020087 | CNTT 18-10 | Thành viên |

---

## 2. Danh sách bài thực hành

| Bài Lab | Nội dung thực hành | Trạng thái | Nhánh Git |
| :---: | :--- | :---: | :---: |
| **Lab01** | Vận hành dự án trên Linux và viết Shell Script (`check-env.sh`, `scan-source.sh`) | ✅ Đạt 100% | [lab1](https://github.com/ledinhhuymeme/PTPMMNM-CNTT-1810-NHOM-10/tree/lab1) |
| **Lab02** | Xây dựng Module PHP kiểm tra điều kiện đăng ký (`RegistrationPolicy.php`) | ✅ Đạt 100% | [lab2](https://github.com/ledinhhuymeme/PTPMMNM-CNTT-1810-NHOM-10/tree/lab2) |
| **Lab03** | Hiển thị sự kiện, bộ lọc tìm kiếm đa tiêu chí và xử lý Form POST (`EventFilter.php`) | ✅ Đạt 100% | [lab3](https://github.com/ledinhhuymeme/PTPMMNM-CNTT-1810-NHOM-10/tree/lab3) |
| **Lab04** | Quản lý bản nháp, Session và Cookie (`DraftGuard.php`) | ✅ Đạt 100% | [lab4](https://github.com/ledinhhuymeme/PTPMMNM-CNTT-1810-NHOM-10/tree/lab4) |
| **Lab05** | Tích hợp CSDL PostgreSQL, chống trùng và thống kê (`RegistrationStats.php`) | ✅ Đạt 100% | [lab5](https://github.com/ledinhhuymeme/PTPMMNM-CNTT-1810-NHOM-10/tree/lab5) |
| **BTL** | Hệ thống Quản lý kho Đa điểm, Điều chuyển (ACID) & Kiểm kê | ✅ Hoàn thành | [main](https://github.com/ledinhhuymeme/PTPMMNM-CNTT-1810-NHOM-10/tree/main) |

---

## 3. Công nghệ sử dụng

- **Hệ điều hành:** Linux (Ubuntu / WSL 2), Windows (XAMPP)
- **Ngôn ngữ:** PHP 8.1+ (PHP CLI, OOP, PSR-12, PDO)
- **Công cụ:** Git, GitHub, Visual Studio Code
- **Môi trường & Script:** Bash Shell Scripting, PowerShell, Batch
- **Giao diện Web:** HTML5, CSS3 hiện đại (Material 3 Tokens & Glassmorphism)
- **Cơ sở dữ liệu:** PostgreSQL (Lab 05), MySQL / MariaDB InnoDB (BTL Quản lý kho)

---

## 4. Cấu trúc các nhánh Git (Branch Structure)

Mỗi bài Lab được lưu trữ trên một nhánh Git độc lập theo đúng quy cách của bộ môn:

- **`main`**: Nhánh chính chứa thông tin nhóm, tổng quan đề tài và sản phẩm Bài tập lớn (BTL) hoàn chỉnh.
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

## 5. Hướng dẫn chạy sản phẩm Bài tập lớn (BTL)

Mã nguồn BTL nằm tại thư mục `/BTL` trên nhánh `main`:
1. Bật MySQL trong XAMPP Control Panel.
2. Chạy file `BTL/run.bat` hoặc lệnh:
   ```bash
   "e:\xampp\php\php.exe" -S 127.0.0.1:8080 -t "BTL/public"
   ```
3. Truy cập Web tại: `http://127.0.0.1:8080`
4. Chạy kiểm thử Rubric:
   ```bash
   "e:\xampp\php\php.exe" BTL/tests/rubric_test.php
   ```

---

## 6. Repository bài tập gốc

[https://github.com/FIT-DNU/Open-Source-Software-Development](https://github.com/FIT-DNU/Open-Source-Software-Development)