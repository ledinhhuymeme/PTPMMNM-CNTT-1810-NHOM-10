<?php
// Cấu hình kết nối Cơ sở dữ liệu MySQL / MariaDB (XAMPP)

return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'qlkho_nhom10',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ],
    'app' => [
        'name' => 'Hệ thống Quản lý kho Đa điểm & Kiểm kê — Nhóm 10',
        'base_url' => '',
        'session_lifetime' => 86400
    ]
];
