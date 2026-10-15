# CommunityPass — Lab 01

Ứng dụng đăng ký tham dự sự kiện. Mã nguồn dùng cho bài thực hành môn **Phát triển phần mềm mã nguồn mở**.

## Chuẩn bị

Sử dụng Ubuntu hoặc WSL. Cài đặt công cụ:

```bash
sudo apt update
sudo apt install php-cli git unzip
php -v
git --version
```

PHP yêu cầu từ **8.1** trở lên.

## Cách chạy

```bash
cd Ma_nguon

# Kiểm tra môi trường
bash lab.sh check

# Chạy máy chủ (giữ terminal mở)
bash lab.sh run
```

Mở trình duyệt tại: **http://127.0.0.1:8101**

## Kiểm tra bài làm

Mở terminal thứ hai tại `Ma_nguon`, hoặc dừng máy chủ trước (Ctrl+C):

```bash
# Kiểm tra phần mẫu (check-env.sh)
bash lab.sh test

# Kiểm tra phần mở rộng (scan-source.sh)
bash lab.sh extend
```

Kết quả mong đợi:
- `test`: xuất `CHECK_ENV=PASS`, mã thoát 0
- `extend`: xuất `SHELL_CASES=4 FAILED=0`, mã thoát 0

## Cấu trúc thư mục

```
Ma_nguon/
├── lab.sh                  ← Script điều khiển chính
├── public/
│   ├── index.php           ← Trang mẫu CommunityPass
│   └── assets/css/
│       ├── tokens.css      ← Biến màu (Material 3)
│       └── app.css         ← Giao diện ứng dụng
├── scripts/
│   ├── check-env.sh        ← [Phần mẫu] Kiểm tra môi trường
│   └── scan-source.sh      ← [Bài tự làm] Quét tệp PHP
├── tests/
│   └── extension.sh        ← Kiểm tra bài mở rộng
└── fixtures/               ← Dữ liệu kiểm tra
    ├── broken.txt          ← PHP sai cú pháp
    └── valid/
        ├── a.php           ← PHP hợp lệ
        └── sub folder/
            └── b.php       ← PHP hợp lệ (tên thư mục có khoảng trắng)
```
