# Lab 01 — Vận hành dự án trên Linux và viết Shell Script

**Học phần:** Phát triển phần mềm mã nguồn mở  
**Đối tượng:** Sinh viên năm 3 — Chuyên ngành Phát triển phần mềm

## Mô tả

Bài thực hành sử dụng mã nguồn **CommunityPass** — ứng dụng đăng ký tham dự sự kiện. Sinh viên sẽ:
- Sử dụng lệnh Linux để xác định đúng thư mục và tệp của dự án
- Viết script có biến, hàm, điều kiện và vòng lặp; đọc đúng mã thoát
- Kiểm tra công cụ và tệp cần thiết; xử lý trường hợp thiếu điều kiện chạy
- Viết và kiểm thử script quét nhiều tệp PHP, tổng hợp kết quả và trả đúng mã thoát

## Chuẩn bị

Sử dụng Ubuntu/WSL, PHP từ 8.1 và Git:

```bash
sudo apt update
sudo apt install php-cli git unzip
php -v
git --version
```

## Tải bài

```bash
git clone --single-branch --branch lab1 https://github.com/FIT-DNU/Open-Source-Software-Development.git lab1
cd lab1/Ma_nguon
```

## Chạy bài

```bash
bash lab.sh check
bash lab.sh run
```

Mở http://127.0.0.1:8101. Giữ terminal mở; Ctrl+C để dừng.

## Kiểm tra

```bash
bash lab.sh test
bash lab.sh extend
```

- `test` kiểm tra phần mẫu (`check-env.sh`) — kỳ vọng `CHECK_ENV=PASS`
- `extend` kiểm tra phần tự làm (`scan-source.sh`) — kỳ vọng `SHELL_CASES=4 FAILED=0`