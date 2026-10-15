# BÁO CÁO BÀI TẬP LỚN: HỆ THỐNG QUẢN LÝ KHO, ĐIỀU CHUYỂN VÀ KIỂM KÊ ĐA ĐIỂM

**Học phần:** Phát triển phần mềm mã nguồn mở  
**Nhóm:** Nhóm 10 — Lớp CNTT-1810  
**Đề tài số 10:** Xây dựng hệ thống quản lý kho, điều chuyển hàng và kiểm kê tại nhiều điểm  

---

## 1. Công nghệ & Kiến trúc hệ thống
- **Ngôn ngữ:** PHP 8.1+ (Thuần, OOP, Tuân thủ PSR-12, thiết kế Service-Repository)
- **Cơ sở dữ liệu:** MySQL / MariaDB (XAMPP Engine InnoDB hỗ trợ giao dịch ACID)
- **Giao diện:** HTML5, CSS3 hiện đại theo ngôn ngữ thiết kế Material 3 & Glassmorphism, chuẩn responsive.
- **Mã nguồn mở & Giấy phép:** MIT License.

---

## 2. Các chức năng hoàn thành (Theo Rubric 10 điểm)
1. **Quản lý danh mục & phân quyền kho:**
   - 3 kho hàng mẫu: Kho Tổng Hà Nội, Kho Trung chuyển Đà Nẵng, Kho Phân phối TP.HCM.
   - 30 mặt hàng phân loại theo ngành hàng (IT, Mạng, Linh kiện, Thiết bị kho), đơn vị tính, mức tồn tối thiểu.
   - Quản lý số lô (Batches) và hạn sử dụng.
   - Phân quyền theo kho và vai trò người dùng (Admin, Thủ kho từng kho, Nhân viên kiểm kê).
2. **Nhập / Xuất kho & Sổ phát sinh:**
   - Lập và duyệt phiếu nhập, xuất.
   - Khóa bản ghi (Pessimistic Locking `FOR UPDATE`) kiểm soát số lượng tồn tức thời.
   - Chặn tuyệt đối xuất âm khi nhiều yêu cầu xuất đồng thời.
   - Sổ phát sinh (Stock Movements Ledger) ghi nhận bất biến từng lần tăng/giảm.
   - Chống gửi lại chứng từ lặp (Idempotent).
3. **Điều chuyển hàng giữa hai kho (ACID):**
   - Trừ kho nguồn và cộng kho đích trong cùng một transaction.
   - Khi có sự cố giữa chừng, hệ thống tự động rollback 100%, không làm thất thoát hoặc nhân đôi hàng hóa.
4. **Kiểm kê & Điều chỉnh kho:**
   - Nhập số thực tế đếm được, tự động đối chiếu số sách và tính chênh lệch thừa/thiếu.
   - Tự động sinh phiếu điều chỉnh (ADJUSTMENT) có ghi rõ lý do.
5. **Tra cứu & Báo cáo:**
   - Báo cáo tồn kho đa điểm theo thời gian thực.
   - Báo cáo lịch sử thẻ kho / sổ phát sinh.

---

## 3. Kết quả 5 phép thử bắt buộc của Giảng viên
Toàn bộ 5 phép thử bắt buộc được tự động hóa tại file `BTL/tests/rubric_test.php` với kết quả **100% PASS**:
1. `[PASS]` Kho còn 5 sản phẩm, 2 yêu cầu cùng xuất 4: Chỉ 1 phiếu được ghi nhận, số tồn không âm.
2. `[PASS]` Gặp lỗi mô phỏng giữa lúc trừ kho A và cộng kho B: Không có thay đổi dở dang (Rollback hoàn toàn).
3. `[PASS]` Gửi lại chứng từ đã xác nhận: Số tồn và sổ phát sinh không tăng thêm (Idempotency).
4. `[PASS]` Kiểm tra phân quyền: Thủ kho Hà Nội bị chặn phía server khi cố tình thao tác kho khác.
5. `[PASS]` Điều chỉnh kiểm kê có lý do và chứng từ: Tổng tồn sau kiểm kê khớp hoàn toàn với sổ phát sinh.

---

## 4. Hướng dẫn chạy chương trình
1. Mở XAMPP Control Panel, đảm bảo MySQL đang chạy (Start).
2. Chạy file `run.bat` trong thư mục `BTL/`.
3. Mở trình duyệt tại: `http://127.0.0.1:8080`
4. Để chạy bộ test chấm điểm:
   ```bash
   "e:\xampp\php\php.exe" e:\PTPMMNM-CNTT-1810-NHOM-10\BTL\tests\rubric_test.php
   ```
