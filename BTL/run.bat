@echo off
chcp 65001 >nul
echo ========================================================
echo   KHỞI ĐỘNG HỆ THỐNG BTL QUẢN LÝ KHO ĐA ĐIỂM - NHÓM 10
echo ========================================================
echo.
echo [1/2] Kiểm tra kết nối MySQL XAMPP...
"e:\xampp\mysql\bin\mysql.exe" -u root -e "USE qlkho_nhom10; SELECT 'MySQL XAMPP Kết nối THÀNH CÔNG!' AS TrangThai;"
if %errorlevel% neq 0 (
    echo [LỖI] Chưa bật MySQL trong XAMPP! Đang khởi động MySQL...
    start "" "e:\xampp\mysql_start.bat"
    timeout /t 3 >nul
)

echo.
echo [2/2] Khởi chạy máy chủ Web PHP tại địa chỉ: http://127.0.0.1:8080
echo Nhấn Ctrl+C để dừng máy chủ.
echo.
"e:\xampp\php\php.exe" -S 127.0.0.1:8080 -t "e:\PTPMMNM-CNTT-1810-NHOM-10\BTL\public"
