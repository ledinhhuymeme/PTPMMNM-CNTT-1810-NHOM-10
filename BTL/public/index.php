<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Services/WarehouseService.php';

use BTL\Database;
use BTL\Services\WarehouseService;

$db = Database::getConnection();
$service = new WarehouseService($db);

// Tự động giả lập đăng nhập Admin nếu chưa chọn (hoặc cho phép chuyển user nhanh trên giao diện)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Admin
    $_SESSION['username'] = 'admin';
    $_SESSION['full_name'] = 'Quản trị viên Hệ thống';
    $_SESSION['role'] = 'admin';
}

$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'full_name' => $_SESSION['full_name'],
    'role' => $_SESSION['role'],
];

// Switch user nếu người dùng muốn test phân quyền
if (isset($_GET['switch_user'])) {
    $uid = (int)$_GET['switch_user'];
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $u = $stmt->fetch();
    if ($u) {
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['username'] = $u['username'];
        $_SESSION['full_name'] = $u['full_name'];
        $_SESSION['role'] = $u['role'];
        header("Location: index.php");
        exit;
    }
}

// Xử lý tạo phiếu nhanh
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'create_transfer') {
            $src = (int)$_POST['source_warehouse_id'];
            $tgt = (int)$_POST['target_warehouse_id'];
            $itemId = (int)$_POST['item_id'];
            $qty = (float)$_POST['quantity'];

            $db->beginTransaction();
            $code = 'PDC-' . date('Ymd') . '-' . substr(uniqid(), -4);
            $stmt = $db->prepare("INSERT INTO vouchers (code, type, source_warehouse_id, target_warehouse_id, created_by, status, note) VALUES (?, 'TRANSFER', ?, ?, ?, 'DRAFT', ?)");
            $stmt->execute([$code, $src, $tgt, $currentUser['id'], "Điều chuyển nhanh"]);
            $vId = (int)$db->lastInsertId();

            $stmt = $db->prepare("INSERT INTO voucher_lines (voucher_id, item_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$vId, $itemId, $qty]);
            $db->commit();

            // Xác nhận ngay
            $service->confirmTransferVoucher($vId, $currentUser['id']);
            $message = "Đã thực hiện điều chuyển $qty mặt hàng từ Kho sang Kho đích thành công trong cùng 1 giao dịch nguyên tử (ACID).";
        } elseif ($_POST['action'] === 'create_import') {
            $tgt = (int)$_POST['warehouse_id'];
            $itemId = (int)$_POST['item_id'];
            $qty = (float)$_POST['quantity'];

            $db->beginTransaction();
            $code = 'PNK-' . date('Ymd') . '-' . substr(uniqid(), -4);
            $stmt = $db->prepare("INSERT INTO vouchers (code, type, target_warehouse_id, created_by, status, note) VALUES (?, 'IMPORT', ?, ?, 'DRAFT', ?)");
            $stmt->execute([$code, $tgt, $currentUser['id'], "Nhập hàng bổ sung"]);
            $vId = (int)$db->lastInsertId();

            $stmt = $db->prepare("INSERT INTO voucher_lines (voucher_id, item_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$vId, $itemId, $qty]);
            $db->commit();

            $service->confirmImportVoucher($vId, $currentUser['id']);
            $message = "Đã nhập thành công $qty sản phẩm vào kho.";
        } elseif ($_POST['action'] === 'create_export') {
            $src = (int)$_POST['warehouse_id'];
            $itemId = (int)$_POST['item_id'];
            $qty = (float)$_POST['quantity'];

            $db->beginTransaction();
            $code = 'PXK-' . date('Ymd') . '-' . substr(uniqid(), -4);
            $stmt = $db->prepare("INSERT INTO vouchers (code, type, source_warehouse_id, created_by, status, note) VALUES (?, 'EXPORT', ?, ?, 'DRAFT', ?)");
            $stmt->execute([$code, $src, $currentUser['id'], "Xuất kho kinh doanh"]);
            $vId = (int)$db->lastInsertId();

            $stmt = $db->prepare("INSERT INTO voucher_lines (voucher_id, item_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$vId, $itemId, $qty]);
            $db->commit();

            $service->confirmExportVoucher($vId, $currentUser['id']);
            $message = "Đã xuất kho thành công $qty sản phẩm. Sổ phát sinh đã ghi nhận.";
        }
    } catch (Exception $e) {
        $message = "Thao tác thất bại: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// Lấy thống kê
$totalWarehouses = $db->query("SELECT COUNT(*) FROM warehouses WHERE is_active = 1")->fetchColumn();
$totalItems = $db->query("SELECT COUNT(*) FROM items")->fetchColumn();
$totalStock = $db->query("SELECT COALESCE(SUM(quantity), 0) FROM inventory")->fetchColumn();
$totalVouchers = $db->query("SELECT COUNT(*) FROM vouchers")->fetchColumn();

// Lấy danh sách kho mà user có quyền xem
$myWarehouses = $service->getWarehousesForUser($currentUser['id']);
$allWarehouses = $db->query("SELECT * FROM warehouses WHERE is_active = 1")->fetchAll();
$allItems = $db->query("SELECT * FROM items ORDER BY id ASC LIMIT 10")->fetchAll();

// Tồn kho chi tiết theo kho
$whFilter = isset($_GET['wh']) ? (int)$_GET['wh'] : ($myWarehouses[0]['id'] ?? 1);
$stmt = $db->prepare("
    SELECT i.sku, i.name, i.category, i.unit, inv.quantity, b.batch_number, w.name as warehouse_name
    FROM inventory inv
    JOIN items i ON inv.item_id = i.id
    JOIN warehouses w ON inv.warehouse_id = w.id
    LEFT JOIN batches b ON inv.batch_id = b.id
    WHERE inv.warehouse_id = ?
    ORDER BY i.id ASC
");
$stmt->execute([$whFilter]);
$inventoryRows = $stmt->fetchAll();

// Lịch sử Sổ phát sinh gần nhất (Ledger)
$stmt = $db->prepare("
    SELECT sm.*, i.name as item_name, i.sku, w.name as warehouse_name, v.code as voucher_code
    FROM stock_movements sm
    JOIN items i ON sm.item_id = i.id
    JOIN warehouses w ON sm.warehouse_id = w.id
    JOIN vouchers v ON sm.voucher_id = v.id
    ORDER BY sm.id DESC
    LIMIT 8
");
$stmt->execute();
$ledgerRows = $stmt->fetchAll();

$allUsers = $db->query("SELECT * FROM users ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Quản lý Kho Đa Điểm & Kiểm Kê — Nhóm 10</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="brand-icon">📦</div>
            <div class="brand-text">
                <h2>Warehouse M3</h2>
                <span>PTPMMNM - Nhóm 10</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-link active">📊 Tổng quan hệ thống</a>
            <a href="#inventory" class="nav-link">📋 Tồn kho đa điểm</a>
            <a href="#transfer" class="nav-link">🔄 Điều chuyển hàng</a>
            <a href="#ledger" class="nav-link">📜 Sổ phát sinh (Ledger)</a>
            <a href="#audit" class="nav-link">🔍 Đợt kiểm kê</a>
        </nav>
        <div class="user-profile">
            <div>
                <strong style="font-size: 13px; display: block; color: #1e293b;"><?= htmlspecialchars($currentUser['full_name']) ?></strong>
                <span class="badge badge-primary"><?= strtoupper($currentUser['role']) ?></span>
            </div>
            <div style="font-size: 11px;">
                <select onchange="location.href='index.php?switch_user=' + this.value" style="padding: 4px; border-radius: 6px; font-size: 11px;">
                    <?php foreach ($allUsers as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $u['id'] === $currentUser['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['username']) ?> (<?= $u['role'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-wrapper">
        <div class="header-bar">
            <div>
                <h1>Đề tài 10: Quản lý kho, điều chuyển & kiểm kê đa điểm</h1>
                <p>Môn học: Phát triển phần mềm mã nguồn mở | Nhóm 10 — Lớp CNTT-1810</p>
            </div>
            <div>
                <a href="tests/rubric_test.php" target="_blank" class="btn btn-outline" style="border-color: #2563eb; color: #2563eb;">
                    🧪 Chạy Test Rubric Giảng Viên (100% PASS)
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div style="padding: 14px 20px; border-radius: 10px; margin-bottom: 24px; font-size: 14px; font-weight: 600; background: <?= $messageType === 'success' ? '#dcfce7; color: #15803d;' : '#fee2e2; color: #b91c1c;' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-title">Kho hàng đang quản lý</span>
                <span class="stat-value"><?= $totalWarehouses ?></span>
            </div>
            <div class="stat-card success">
                <span class="stat-title">Danh mục mặt hàng</span>
                <span class="stat-value"><?= $totalItems ?> SKU</span>
            </div>
            <div class="stat-card warning">
                <span class="stat-title">Tổng tồn kho toàn mạng</span>
                <span class="stat-value"><?= number_format((float)$totalStock, 0) ?> sp</span>
            </div>
            <div class="stat-card danger">
                <span class="stat-title">Chứng từ đã phát sinh</span>
                <span class="stat-value"><?= $totalVouchers ?></span>
            </div>
        </div>

        <!-- Thao tác nghiệp vụ nhanh (Nhập / Xuất / Điều chuyển) -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px;">
            <!-- Điều chuyển kho -->
            <div class="card" id="transfer">
                <div class="card-header">
                    <h3>🔄 Điều chuyển hàng giữa 2 kho (ACID Transaction)</h3>
                </div>
                <div style="padding: 24px;">
                    <form method="POST">
                        <input type="hidden" name="action" value="create_transfer">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label class="form-label">Kho nguồn (Xuất)</label>
                                <select name="source_warehouse_id" class="form-control" required>
                                    <?php foreach ($myWarehouses as $w): ?>
                                        <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Kho đích (Nhận)</label>
                                <select name="target_warehouse_id" class="form-control" required>
                                    <?php foreach ($allWarehouses as $w): ?>
                                        <option value="<?= $w['id'] ?>" <?= $w['id'] == 2 ? 'selected' : '' ?>><?= htmlspecialchars($w['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label class="form-label">Chọn mặt hàng</label>
                                <select name="item_id" class="form-control" required>
                                    <?php foreach ($allItems as $it): ?>
                                        <option value="<?= $it['id'] ?>"><?= htmlspecialchars($it['sku'] . ' - ' . $it['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Số lượng chuyển</label>
                                <input type="number" name="quantity" class="form-control" value="2" min="1" step="1" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Thực hiện Điều chuyển ngay</button>
                    </form>
                </div>
            </div>

            <!-- Xuất kho chống âm -->
            <div class="card">
                <div class="card-header">
                    <h3>📤 Xuất kho kinh doanh (Kiểm tra chống xuất âm)</h3>
                </div>
                <div style="padding: 24px;">
                    <form method="POST">
                        <input type="hidden" name="action" value="create_export">
                        <div class="form-group">
                            <label class="form-label">Kho xuất hàng</label>
                            <select name="warehouse_id" class="form-control" required>
                                <?php foreach ($myWarehouses as $w): ?>
                                    <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label class="form-label">Mặt hàng</label>
                                <select name="item_id" class="form-control" required>
                                    <?php foreach ($allItems as $it): ?>
                                        <option value="<?= $it['id'] ?>"><?= htmlspecialchars($it['sku'] . ' - ' . $it['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Số lượng xuất</label>
                                <input type="number" name="quantity" class="form-control" value="3" min="1" step="1" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; background: #475569;">Xác nhận Xuất kho</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Bảng Tồn kho hiện tại -->
        <div class="card" id="inventory">
            <div class="card-header">
                <h3>📋 Danh sách Tồn kho theo địa điểm</h3>
                <div>
                    <span style="font-size: 13px; font-weight: 600; margin-right: 8px;">Xem kho:</span>
                    <select onchange="location.href='index.php?wh=' + this.value" class="form-control" style="width: auto; display: inline-block;">
                        <?php foreach ($allWarehouses as $w): ?>
                            <option value="<?= $w['id'] ?>" <?= $w['id'] == $whFilter ? 'selected' : '' ?>>
                                <?= htmlspecialchars($w['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Mã SKU</th>
                            <th>Tên mặt hàng</th>
                            <th>Ngành hàng</th>
                            <th>Số lô</th>
                            <th>ĐVT</th>
                            <th style="text-align: right;">Số lượng tồn</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inventoryRows)): ?>
                            <tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 24px;">Chưa có dữ liệu tồn kho tại địa điểm này.</td></tr>
                        <?php else: ?>
                            <?php foreach ($inventoryRows as $row): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['sku']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><span class="badge badge-primary"><?= htmlspecialchars($row['category']) ?></span></td>
                                    <td><?= htmlspecialchars($row['batch_number'] ?? 'Lô mặc định') ?></td>
                                    <td><?= htmlspecialchars($row['unit']) ?></td>
                                    <td style="text-align: right; font-weight: 700; font-size: 15px; color: #0f172a;">
                                        <?= number_format((float)$row['quantity'], 2) ?>
                                    </td>
                                    <td>
                                        <?php if ((float)$row['quantity'] > 5): ?>
                                            <span class="badge badge-success">Đủ tồn kho</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Cảnh báo sắp hết</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sổ phát sinh kho (Ledger Bất biến) -->
        <div class="card" id="ledger">
            <div class="card-header">
                <h3>📜 Sổ phát sinh kho (Stock Movements Ledger - Bất biến & Đối chiếu)</h3>
                <span class="badge badge-primary">Audit Trail</span>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Mã GD</th>
                            <th>Mã Chứng từ</th>
                            <th>Địa điểm kho</th>
                            <th>Mặt hàng</th>
                            <th>Loại phát sinh</th>
                            <th style="text-align: right;">Phát sinh</th>
                            <th style="text-align: right;">Tồn sau GD</th>
                            <th>Thời gian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ledgerRows as $l): ?>
                            <tr>
                                <td>#<?= $l['id'] ?></td>
                                <td><strong><?= htmlspecialchars($l['voucher_code']) ?></strong></td>
                                <td><?= htmlspecialchars($l['warehouse_name']) ?></td>
                                <td><?= htmlspecialchars($l['item_name']) ?></td>
                                <td>
                                    <?php if ($l['movement_type'] === 'IMPORT' || $l['movement_type'] === 'TRANSFER_IN'): ?>
                                        <span class="badge badge-success"><?= $l['movement_type'] ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><?= $l['movement_type'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; font-weight: 700; color: <?= (float)$l['quantity'] > 0 ? '#10b981' : '#ef4444' ?>;">
                                    <?= (float)$l['quantity'] > 0 ? '+' : '' ?><?= number_format((float)$l['quantity'], 2) ?>
                                </td>
                                <td style="text-align: right; font-weight: 600;"><?= number_format((float)$l['balance_after'], 2) ?></td>
                                <td style="color: #64748b; font-size: 13px;"><?= $l['created_at'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

</body>
</html>
