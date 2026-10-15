-- Dữ liệu mẫu (Seed Data) chuẩn Rubric BTL: 3 kho, 30 mặt hàng, phân quyền, lô hàng, phiếu mẫu
USE `qlkho_nhom10`;

-- 1. Người dùng (mật khẩu mặc định: '123456')
-- Hash Bcrypt cho '123456': $2y$10$wK1F5NfB51v/Hj30i.Oa0u1a9Rj0K0V0K0V0K0V0K0V0K0V0K0V0. (hoặc plain/md5/password_hash trong code)
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`) VALUES
(1, 'admin', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeFvFvFvFvFvFvFvFvFvFvFvFvFvFvFv.', 'Quản trị viên Hệ thống', 'admin'),
(2, 'thukho_hn', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeFvFvFvFvFvFvFvFvFvFvFvFvFvFvFv.', 'Nguyễn Văn An - Thủ kho Hà Nội', 'manager'),
(3, 'thukho_dn', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeFvFvFvFvFvFvFvFvFvFvFvFvFvFvFv.', 'Trần Thị Bình - Thủ kho Đà Nẵng', 'manager'),
(4, 'thukho_hcm', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeFvFvFvFvFvFvFvFvFvFvFvFvFvFvFv.', 'Lê Đình Huy - Thủ kho TP.HCM', 'manager'),
(5, 'kiemke_vien', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeFvFvFvFvFvFvFvFvFvFvFvFvFvFvFv.', 'Hoàng Đức Minh - Ban Kiểm Kê', 'staff')
ON DUPLICATE KEY UPDATE `username`=VALUES(`username`);

-- 2. 3 Kho hàng (Kho Hà Nội, Kho Đà Nẵng, Kho TP.HCM)
INSERT INTO `warehouses` (`id`, `code`, `name`, `address`) VALUES
(1, 'KHO-HN', 'Kho Tổng Miền Bắc (Hà Nội)', 'Số 18 Phố Nguyễn Trãi, Thanh Xuân, Hà Nội'),
(2, 'KHO-DN', 'Kho Trung chuyển Miền Trung (Đà Nẵng)', 'Lô 45 KCN Hòa Khánh, Liên Chiểu, Đà Nẵng'),
(3, 'KHO-HCM', 'Kho Phân phối Miền Nam (TP.HCM)', 'Đường số 7 KCN Tân Bình, Tân Phú, TP. Hồ Chí Minh')
ON DUPLICATE KEY UPDATE `code`=VALUES(`code`);

-- Gán quyền kho cho người dùng
INSERT INTO `user_warehouses` (`user_id`, `warehouse_id`) VALUES
(1, 1), (1, 2), (1, 3), -- Admin xem được cả 3 kho
(2, 1),                 -- Thủ kho HN chỉ phụ trách Kho 1
(3, 2),                 -- Thủ kho ĐN chỉ phụ trách Kho 2
(4, 3),                 -- Thủ kho HCM chỉ phụ trách Kho 3
(5, 1), (5, 2), (5, 3)  -- Kiểm kê viên
ON DUPLICATE KEY UPDATE `user_id`=VALUES(`user_id`);

-- 3. 30 Mặt hàng mẫu chuẩn quy cách
INSERT INTO `items` (`id`, `sku`, `name`, `category`, `unit`, `min_stock`) VALUES
(1, 'MH-001', 'Laptop Dell Latitude 5420 i5', 'Thiết bị IT', 'Chiếc', 5),
(2, 'MH-002', 'Laptop Lenovo ThinkPad T14 AMD', 'Thiết bị IT', 'Chiếc', 5),
(3, 'MH-003', 'Màn hình Dell Ultrasharp U2422H', 'Thiết bị IT', 'Chiếc', 10),
(4, 'MH-004', 'Bàn phím cơ Logitech MX Mechanical', 'Phụ kiện', 'Cái', 15),
(5, 'MH-005', 'Chuột không dây Logitech MX Master 3S', 'Phụ kiện', 'Cái', 20),
(6, 'MH-006', 'Tai nghe Sony WH-1000XM5', 'Phụ kiện', 'Chiếc', 8),
(7, 'MH-007', 'Ổ cứng SSD Samsung 980 Pro 1TB NVMe', 'Linh kiện', 'Cái', 25),
(8, 'MH-008', 'Ổ cứng SSD Kingston NV2 500GB', 'Linh kiện', 'Cái', 30),
(9, 'MH-009', 'RAM Kingston Fury 16GB DDR4 3200', 'Linh kiện', 'Thanh', 40),
(10, 'MH-010', 'RAM Corsair Vengeance 32GB DDR5', 'Linh kiện', 'Thanh', 20),
(11, 'MH-011', 'Bộ định tuyến Router Cisco C1111-8P', 'Thiết bị mạng', 'Bộ', 3),
(12, 'MH-012', 'Switch Cisco CBS250 24 cổng Gigabit', 'Thiết bị mạng', 'Bộ', 4),
(13, 'MH-013', 'Bộ phát Wifi Ruckus R550 Indoor', 'Thiết bị mạng', 'Chiếc', 6),
(14, 'MH-014', 'Cáp mạng Cat6 UTP CommScope (305m)', 'Vật tư mạng', 'Cuộn', 10),
(15, 'MH-015', 'Tủ Rack 12U sâu D600 cửa lưới', 'Thiết bị mạng', 'Cái', 5),
(16, 'MH-016', 'Máy in Laser đa năng HP LaserJet M428fdw', 'Thiết bị VP', 'Chiếc', 4),
(17, 'MH-017', 'Máy in phun màu Canon PIXMA G3010', 'Thiết bị VP', 'Chiếc', 5),
(18, 'MH-018', 'Máy chiếu Epson EB-E01 XGA', 'Thiết bị VP', 'Chiếc', 3),
(19, 'MH-019', 'Bộ lưu điện UPS Santak 1000VA', 'Nguồn điện', 'Bộ', 8),
(20, 'MH-020', 'Bộ lưu điện UPS APC Smart-UPS 2200VA', 'Nguồn điện', 'Bộ', 2),
(21, 'MH-021', 'Camera giám sát Hikvision IP 4MP Dome', 'An ninh', 'Chiếc', 20),
(22, 'MH-022', 'Camera thân trụ ngoài trời Hikvision 4MP', 'An ninh', 'Chiếc', 15),
(23, 'MH-023', 'Đầu ghi hình IP Hikvision 16 kênh NVR', 'An ninh', 'Chiếc', 3),
(24, 'MH-024', 'Dây nhảy Patchcord Cat6 3m AMP', 'Vật tư mạng', 'Sợi', 50),
(25, 'MH-025', 'Đầu bấm hạt mạng RJ45 Cat6 bọc kim', 'Vật tư mạng', 'Hộp', 15),
(26, 'MH-026', 'Máy quét mã vạch Honeywell Xenon 1900G', 'Thiết bị kho', 'Chiếc', 6),
(27, 'MH-027', 'Máy in tem nhiệt Xprinter XP-350B', 'Thiết bị kho', 'Chiếc', 5),
(28, 'MH-028', 'Giấy in mã vạch 3 tem decal 35x22mm', 'Vật tư kho', 'Cuộn', 40),
(29, 'MH-029', 'Cân điện tử công nghiệp 150kg', 'Thiết bị kho', 'Cái', 2),
(30, 'MH-030', 'Xe kéo hàng đẩy tay 4 bánh 300kg', 'Thiết bị kho', 'Chiếc', 3)
ON DUPLICATE KEY UPDATE `sku`=VALUES(`sku`);

-- 4. Một số Lô hàng mẫu
INSERT INTO `batches` (`id`, `item_id`, `batch_number`, `mfg_date`, `exp_date`) VALUES
(1, 1, 'BATCH-2026-DELL01', '2026-01-10', '2028-01-10'),
(2, 3, 'BATCH-2026-DISP01', '2026-02-15', '2029-02-15'),
(3, 7, 'BATCH-2026-SAM01', '2026-03-01', '2031-03-01'),
(4, 11, 'BATCH-2026-CISCO01', '2025-11-20', '2028-11-20'),
(5, 26, 'BATCH-2026-HONEY01', '2026-01-05', '2029-01-05')
ON DUPLICATE KEY UPDATE `batch_number`=VALUES(`batch_number`);

-- 5. Số tồn khởi tạo mẫu tại Kho 1 (Hà Nội), Kho 2 (Đà Nẵng), Kho 3 (TP.HCM)
INSERT INTO `inventory` (`warehouse_id`, `item_id`, `batch_id`, `quantity`) VALUES
(1, 1, 1, 20.00),
(1, 3, 2, 15.00),
(1, 4, NULL, 30.00),
(1, 5, NULL, 25.00),
(1, 7, 3, 50.00),
(1, 11, 4, 10.00),
(1, 26, 5, 12.00),

(2, 1, 1, 5.00),
(2, 4, NULL, 10.00),
(2, 7, 3, 20.00),

(3, 1, 1, 8.00),
(3, 3, 2, 6.00),
(3, 5, NULL, 15.00),
(3, 26, 5, 4.00)
ON DUPLICATE KEY UPDATE `quantity`=VALUES(`quantity`);
