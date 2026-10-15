-- Database Schema cho Đề tài 10: Quản lý kho, điều chuyển và kiểm kê đa điểm
-- Tương thích MySQL / MariaDB (XAMPP)

CREATE DATABASE IF NOT EXISTS `qlkho_nhom10` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `qlkho_nhom10`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `stock_movements`;
DROP TABLE IF EXISTS `stock_count_lines`;
DROP TABLE IF EXISTS `stock_counts`;
DROP TABLE IF EXISTS `voucher_lines`;
DROP TABLE IF EXISTS `vouchers`;
DROP TABLE IF EXISTS `user_warehouses`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `batches`;
DROP TABLE IF EXISTS `inventory`;
DROP TABLE IF EXISTS `items`;
DROP TABLE IF EXISTS `warehouses`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Bảng Kho hàng (Warehouses)
CREATE TABLE `warehouses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `address` VARCHAR(255) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Bảng Người dùng & Quyền (Users & Warehouse assignment)
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'manager', 'staff') NOT NULL DEFAULT 'staff',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `user_warehouses` (
    `user_id` INT NOT NULL,
    `warehouse_id` INT NOT NULL,
    PRIMARY KEY (`user_id`, `warehouse_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Bảng Mặt hàng (Items)
CREATE TABLE `items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sku` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(200) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `unit` VARCHAR(30) NOT NULL DEFAULT 'Cái',
    `min_stock` INT DEFAULT 5,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. Bảng Lô hàng (Batches)
CREATE TABLE `batches` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NOT NULL,
    `batch_number` VARCHAR(50) NOT NULL,
    `mfg_date` DATE NULL,
    `exp_date` DATE NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_item_batch` (`item_id`, `batch_number`),
    FOREIGN KEY (`item_id`) REFERENCES `items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Bảng Tồn kho hiện tại (Inventory Balance per warehouse + item + batch)
CREATE TABLE `inventory` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `warehouse_id` INT NOT NULL,
    `item_id` INT NOT NULL,
    `batch_id` INT NULL,
    `quantity` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_inv` (`warehouse_id`, `item_id`, `batch_id`),
    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`item_id`) REFERENCES `items`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`batch_id`) REFERENCES `batches`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Bảng Chứng từ / Phiếu (Vouchers)
-- Loại phiếu: IMPORT (Nhập), EXPORT (Xuất), TRANSFER (Điều chuyển), ADJUSTMENT (Điều chỉnh kiểm kê)
CREATE TABLE `vouchers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `type` ENUM('IMPORT', 'EXPORT', 'TRANSFER', 'ADJUSTMENT') NOT NULL,
    `source_warehouse_id` INT NULL,
    `target_warehouse_id` INT NULL,
    `created_by` INT NOT NULL,
    `confirmed_by` INT NULL,
    `status` ENUM('DRAFT', 'CONFIRMED', 'CANCELLED') NOT NULL DEFAULT 'DRAFT',
    `note` TEXT NULL,
    `idempotency_key` VARCHAR(100) NULL UNIQUE,
    `confirmed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`source_warehouse_id`) REFERENCES `warehouses`(`id`),
    FOREIGN KEY (`target_warehouse_id`) REFERENCES `warehouses`(`id`),
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
    FOREIGN KEY (`confirmed_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB;

-- 7. Chi tiết dòng hàng trong chứng từ (Voucher Lines)
CREATE TABLE `voucher_lines` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `voucher_id` INT NOT NULL,
    `item_id` INT NOT NULL,
    `batch_id` INT NULL,
    `quantity` DECIMAL(12, 2) NOT NULL,
    `unit_price` DECIMAL(15, 2) DEFAULT 0.00,
    `note` VARCHAR(255) NULL,
    FOREIGN KEY (`voucher_id`) REFERENCES `vouchers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`item_id`) REFERENCES `items`(`id`),
    FOREIGN KEY (`batch_id`) REFERENCES `batches`(`id`)
) ENGINE=InnoDB;

-- 8. Sổ phát sinh kho (Stock Movements Ledger - Bất biến)
CREATE TABLE `stock_movements` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `voucher_id` INT NOT NULL,
    `warehouse_id` INT NOT NULL,
    `item_id` INT NOT NULL,
    `batch_id` INT NULL,
    `quantity` DECIMAL(12, 2) NOT NULL, -- Giá trị dương là nhập/tăng, giá trị âm là xuất/giảm
    `balance_after` DECIMAL(12, 2) NOT NULL,
    `movement_type` VARCHAR(30) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`voucher_id`) REFERENCES `vouchers`(`id`),
    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`),
    FOREIGN KEY (`item_id`) REFERENCES `items`(`id`),
    FOREIGN KEY (`batch_id`) REFERENCES `batches`(`id`)
) ENGINE=InnoDB;

-- 9. Đợt kiểm kê kho (Stock Counts)
CREATE TABLE `stock_counts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `warehouse_id` INT NOT NULL,
    `created_by` INT NOT NULL,
    `status` ENUM('OPEN', 'CONFIRMED', 'CANCELLED') NOT NULL DEFAULT 'OPEN',
    `adjustment_voucher_id` INT NULL,
    `note` TEXT NULL,
    `confirmed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`),
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
    FOREIGN KEY (`adjustment_voucher_id`) REFERENCES `vouchers`(`id`)
) ENGINE=InnoDB;

-- 10. Chi tiết kiểm kê theo từng mặt hàng (Stock Count Lines)
CREATE TABLE `stock_count_lines` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `stock_count_id` INT NOT NULL,
    `item_id` INT NOT NULL,
    `batch_id` INT NULL,
    `book_quantity` DECIMAL(12, 2) NOT NULL,
    `actual_quantity` DECIMAL(12, 2) NOT NULL,
    `difference` DECIMAL(12, 2) NOT NULL,
    `reason` VARCHAR(255) NULL,
    FOREIGN KEY (`stock_count_id`) REFERENCES `stock_counts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`item_id`) REFERENCES `items`(`id`),
    FOREIGN KEY (`batch_id`) REFERENCES `batches`(`id`)
) ENGINE=InnoDB;
